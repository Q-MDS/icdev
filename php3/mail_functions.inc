<?php

$cache_mail_check=array();

function check_local_email_address($email) {

	global $mail_mysql,$cache_mail_check;

	if (isset($cache_mail_check[$email]))
		return $cache_mail_check[$email];

	if (!isset($mail_mysql)) {
          $mysql_username="mailcheck"; // read only access

          if(($mail_mysql = mysql_connect ("192.168.25.6:3306", $mysql_username, "fastermail!"))) {
                mysql_query("use vimbadminl",$mail_mysql);
//                echo "mysql Connected to mail server!\n";
          } else {
                echo "<font color=red>Mysql to mail server Connection failed!</font><br>";
                return true; // assume it exists
          }

          mysql_select_db('vimbadmin',$mail_mysql);

  	}

	$email=strtolower($email);
	$result=mysql_query("select 'x' from alias where address='$email'",$mail_mysql);
	if ($result===false)
		return true; // assume it exists
	if ($row=mysql_fetch_row($result)) 
	{
		$check_local_email_address=true;
		return true;
	}
	else
	{
		$check_local_email_address=false;
		return false;
	}
	


}


/* TEST:
if (check_local_email_address("keith@intercape.co.za"))
		echo "Keith OK\n";
else
		echo "Keith NOT OK\n";



if (check_local_email_address("notkeith@intercape.co.za"))
                echo "Strange - it says notkeith is OK\n";
else
                echo "NotKeith OK (not found, which is right)\n";
*/








?>
