<?php
//Made by OUBRECHT.com
//this script must be in root folder of Moodle

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once("config.php");


//config
$ldapServer="dc.domain.int";
$ldapUsername="domain\\username";
$ldapPassword="Password";
$defaultUserPassword="Passw0rd1";
$defaultUserLanguage="en"; //cs, en, de, sk

$allowUpdate=1; //1-yes, 0-no
$allowInsertNew=1; //1-yes, 0-no
$suspendOldUsers=1; //1-yes, 0-no, All LDAP users will be suspended before insert or update. During insert or update will be unspended.

// Connect to MySQLi
$mysqlConnection = mysqli_connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname);
mysqli_set_charset($mysqli_connection, 'utf8');


//Suspend all AD users before update or insert
if($suspendOldUsers==1){
    $sql = "UPDATE mdl_user SET suspended='1' WHERE auth='ldap'";
    mysqli_query($mysqlConnection, $sql) or die("MySQL error: " . mysqli_error($mysqlConnection));
;};



//run import/update from AD" import_ldap(adQuery)
import_ldap("OU=users,OU=company1,DC=se-europe,DC=domain,DC=int");
import_ldap("OU=users,OU=company2,DC=se-europe,DC=domain,DC=int");








function import_ldap($ldapTree=NULL){
    echo "Import: ".$ldapTree."<BR>";
    //global $CFG;
    global $ldapServer;
    global $ldapUsername;
    global $ldapPassword;
    global $defaultUserPassword;
    global $defaultUserLanguage;
    global $allowUpdate;
    global $allowInsertNew;
    global $mysqlConnection;

    if($ldapTree==NULL){return "ldapTree is NULL";};
    if($allowUpdate!=1 AND $allowInsertNew!=1){return"Update and Insert are not allow.";};
    if($ldapServer==NULL){return "Ldap server is NULL";};





    //mysqli_query($mysqlConnection, " DELETE FROM $mysqli_tb_kontakty WHERE AdID='$AdID' ") or die("Chyba MySQL: " . mysqli_error($mysqlConnection));

    // connect to LADP
    $ldapConnection = ldap_connect($ldapServer) or die("Could not connect to LDAP server.");
    ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION,3);
    ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS,0);

    if($ldapConnection) {
        // binding to ldap server
        $ldapbind = ldap_bind($ldapConnection, $ldapUsername, $ldapPassword) or die ("Error trying to bind: ".ldap_error($ldapConnection));
        // verify binding
        if ($ldapbind) {
            //echo "LDAP připojen...<BR><br /><br />";
            
            
            $result = ldap_search($ldapConnection,$ldapTree, "(&(objectCategory=person)(objectClass=user)(name=*)(!(UserAccountControl:1.2.840.113556.1.4.803:=2)))") or die ("Error in search query: ".ldap_error($ldapConnection));
            $data = ldap_get_entries($ldapConnection, $result);
            
            /*SHOW ALL DATA
            echo '<h1>Dump all data</h1><pre>';
            print_r($data);    
            echo '</pre>';*/
            
            
            // iterate over array and print data for each entry
            echo 'Imported:<BR>';
            $n=0;
            for ($i=0; $i<$data["count"]; $i++) {

                $skip=0;
                $suspended="0";
                $fullName = $data[$i]["displayname"][0];
                $lastName = $data[$i]["sn"][0];
                $firstName = $data[$i]["givenname"][0];
                $username = $data[$i]["samaccountname"][0];
                $email = $data[$i]["mail"][0];
                $mobile = $data[$i]["mobile"][0];
                $phone = $data[$i]["telephonenumber"][0];
                $department = $data[$i]["department"][0];
                $office = $data[$i]["physicaldeliveryofficename"][0];
                $company = $data[$i]["company"][0];
                $description = $data[$i]["description"][0];                
                $position = $data[$i]["title"][0];
                $streetAddress = $data[$i]["streetAddress"][0];
                $city = $data[$i]["l"][0];
                $country = $data[$i]["st"][0];
                $language = $data[$i]["st"][0];
                $hideInAddressBook = strtolower($data[$i]["msexchhidefromaddresslists"][0]);  // textová hodnota true  - true znamená schovat
                //$personalnumber = $data[$i]["extensionattribute10"][0];
                //$czechName = $data[$i]["extensionattribute1"][0];
                

                // korekce hodnot
                $email = str_replace(" ", "", $email);
                $email = strtolower($email);
                $mobile = str_replace(" ", "", $mobile);
                $phone = str_replace(" ", "", $phone);

                //skip users
                if($email==NULL){continue;}; //skip user without e-mail
                if(str_replace(" ", "", strtolower($department))=="system"){$skip=1;;}; //skip system users
                if(str_replace(" ", "", strtolower($description))=="system"){$skip=1;}; //skip system users
                if(str_replace(" ", "", strtolower($firstName))==NULL AND str_replace(" ", "", strtolower($lastName))==NULL){$skip=1;}; //skip empty name
                if(str_replace(" ", "", strtolower($fullName))==NULL){$skip=1;}; //skip empty name


                //echo $czechName." - ".$personalnumber."<BR>";

                //search in DB
                $search =  mysqli_fetch_array(mysqli_query($mysqlConnection, "SELECT username FROM mdl_user WHERE username='$username' LIMIT 1"), MYSQLI_ASSOC)["username"];

                //update in DB
                if(strtolower($search)==strtolower($username) AND $allowUpdate==1 AND $email!=NULL){
                    if($skip==1){$suspended="1";};
                    $sql = "UPDATE mdl_user SET 
                    auth = 'ldap',
                    confirmed = '1',
                    policyagreed = '0',
                    deleted = '0',
                    suspended = '$suspended',
                    mnethostid = '1',
                    firstname = '$firstName',
                    lastname = '$lastName',
                    email = '$email',
                    phone1 = '$phone',
                    phone2 = '$mobile',
                    institution = '$company',
                    department = '$department',
                    address = '$streetAddress',
                    city = '$city',
                    country = '$country',
                    lang = '$defaultUserLanguage'
                    WHERE username = '$username' ";
                    //echo $sql."\n<BR>";
                    mysqli_query($mysqlConnection, $sql) or die("MySQL error: " . mysqli_error($mysqlConnection));
                    echo "update: ".$username." - $fullName<BR>";
                ;};


                //insert new to DB
                if(strtolower($search)!=strtolower($username) AND $allowInsertNew==1 AND $email!=NULL AND $skip!=1){
                    $sql = "INSERT INTO mdl_user (auth, confirmed, policyagreed, deleted, suspended, mnethostid, username, password, firstname, lastname, email, phone1, phone2, institution, department, address, city, country, lang, theme, lastip)
                    VALUES ('ldap', '1', '0', '0', '$suspended', '1', '$username', '$defaultUserPassword', '$firstName', '$lastName', '$email', '$phone', '$mobile', '$company', '$department', '$streetAddress', '$city', '$country', '$defaultUserLanguage', '', '0.0.0.0')";
                    //echo $sql."\n<BR>";
                    mysqli_query($mysqlConnection, $sql) or die("MySQL error: " . mysqli_error($mysqlConnection));
                    echo "insert: ".$username." - $fullName<BR>";
                ;};



                
                $n++;

            ;};
            // print number of entries found
            echo "Count: $n<HR>";
            
        ;};

    ;};




// all done? clean up
ldap_close($ldapConnection);
;};


;?>
