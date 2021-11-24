Import data from LDAP/MS ActiveDirectory to Moodle.
===================================================
This scrip create new users in moodle and update existing users too. If will be some user in LDAP or AD disable, that this script will be disabled this user in Moodle too. 
Variable for autodisable user is "$suspendOldUsers".


Function "import_ldap()" use classic OU queries. This function can be run ore times.
import_ldap("OU=users,OU=company1,DC=se-europe,DC=domain,DC=int");
import_ldap("OU=users,OU=company2,DC=se-europe,DC=domain,DC=int");

Variables
=========
$ldapServer="dc.domain.int";\n
$ldapUsername="domain\\username";
$ldapPassword="Password";
$defaultUserPassword="Passw0rd1";
$defaultUserLanguage="en"; //cs, en, de, sk

$allowUpdate=1; //1-yes, 0-no
$allowInsertNew=1; //1-yes, 0-no
$suspendOldUsers=1; //1-yes, 0-no, All LDAP users will be suspended before insert or update. During insert or update will be unspended.





This script is by OUBRECHT.com. 
