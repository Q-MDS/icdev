<?
//setcookie("shoprite_icape_ip","");


$_scriptstart=time();
$scriptruntime=time();



// FOR PHP V7
if (!function_exists("mysql_connect")) {

	function mysql_connect() {
		return false;
	}
}


if (!function_exists('ora_parse'))
{
        $myphpversion=5;
        require_once("ora_old.inc");
} else {
        $myphpversion=4;
        if (!function_exists("oci_fetch_row")) {

                function oci_fetch_row($st,$flags=0) {
                        $info=array();
                        if (ocifetchinto($st,$info,$flags))
                                return $info;
                        else
                                return false;
                }

		function oci_free_statement($st) {
			return ocifreestatement($st); 
		}

		function oci_close($conn) {
			 @ocilogoff($globalocidbconn);

		}

        }

}



function new_ic_login_screen() {
	global $ic_orig_url, $cursor, $conn, $dbconn, $REMOTE_USER, $ic_force_login;

	$ic_force_login = false;

	if (isset($_POST["ic_login_password"])) {
		$password = $_POST["ic_login_password"];
		$username = $_POST["ic_login_username"];
		$redirect = false;
		
	}
	if (isset($_SERVER["PHP_AUTH_USER"]) && $_SERVER["PHP_AUTH_USER"]!="") {
		$username = $_SERVER["PHP_AUTH_USER"];
		$password = $_SERVER["PHP_AUTH_PW"];
		$redirect = true;
	}

	$authenticated = false;

	if (isset($username) && $username!="" && $password!= "") {

/*
		// check!!
		if ($username=="dummy" && $password=="ydum4")
			$authenticated = true;

		if ($username=="Keith" && $password=="abc") {
			$authenticated = true;
		}
*/
		
		$username = str_replace("'","",$username);
		$username = substr($username, 0, 20);


		ora_parse($cursor,"select * from user_details where username='$username' and is_current='Y'");
		ora_Exec($cursor);
		$user1 = oci_fetch_object($cursor[1]);
		if ($user1 === false || $user1 === null) {
			// user doesnt exist
			echo "Bad login: Username $username";
			exit;
		}
		$temp = (array)$user1;
		$user = (object)array_combine(array_map('strtolower', array_keys($temp)), $temp);

		$user->username=chop($user->username);

                // check password
                $pass = crypt($password, $user->password);
                if(hash_equals($pass , $user->password)){

                    // set session vars
                    //session_start();
		        $_SESSION["user"] = $user;
			$_SESSION["user_active"] = $user;

                    // set cookie
                    @setcookie('IgniteUsername', trim($user->username), 0, '/'); // this fails because of previous output in the page that called this

                    ora_parse($cursor,"update user_Details set bad_pw_counter=0 where username='$username'");
	  	    ora_exec($cursor);

			$authenticated = true;

                }else{
		    echo "<font color=red>Invalid Password</font><bR>";
                    // update bad password
                    ora_parse($cursor,"update user_Details set bad_pw_counter=bad_pw_counter+3 where username='$username'");
		    ora_exec($cursor);

			if ($user->is_current == "L")
				echo "<b>YOUR ACCOUNT IS CURRENTLY LOCKED OUT</b>";


                    // check counter
                    if($user->bad_pw_counter > 8){
                       ora_parse($cursor,"update user_details set is_current='L' where is_current='Y' and username='$username'");
			echo "<b>YOUR ACCOUNT HAS BEEN LOCKED OUT</b>";

                    } else echo "After 3 failed attempts, your account will be LOCKED<bR>";

		    echo "<hr>PLEASE LOG IN. Username & Passwords are Case Sensitive<BR>";
                    echo "<form method=post><input type=hidden name=ic_orig_url value='$ic_orig_url'>";
                    echo "Username: <input name=ic_login_username size=20 maxlength=20 value='$username'><bR>";
                    echo "Password: <input name=ic_login_password type=password  size=20 maxlength=20 ><bR>";
                    echo "<input type=submit value='Login'></form>";

		    echo "If you auto-saved your password and cant remember it, <a href='/booking/oldpw/index.php?url=$ic_orig_url'>Click here</a>";

		    exit;
                }


	}


	if ($authenticated) {
//		echo "You are logged in as $username<hr>";
		$username=chop($username);
		putenv("REMOTE_USER=$username");
		$REMOTE_USER=$username;

	} else { // show form
		if (!isset($username)) {
			$username = "";
		}
		echo "PLEASE LOG IN. Username & Passwords are Case Sensitive<BR>";
		echo "<form method=post><input type=hidden name=ic_orig_url value='$ic_orig_url'>";
		echo "Username: <input name=ic_login_username size=20 maxlength=20 value='$username'><bR>";
		echo "Password: <input name=ic_login_password type=password  size=20 maxlength=20 ><bR>";
		echo "<input type=submit value='Login'></form>";
//		echo "<pre>";
//		var_dump($_SERVER);

                    echo "If you auto-saved your password and cant remember it, <a href='/booking/oldpw/index.php?url=$ic_orig_url'>Click here</a>";


		exit;
	}


} // ic_login


/* ************************************************************************************** */
/* ************** Oracle logon sequence for Intercape Booking Sequence ****************** */
/* ************************************************************************************** */

unset($_SHOPRITE); // dont allow poisoning

$_check_ip=getenv("REMOTE_ADDR");

if (getenv("REMOTE_ADDR")=="127.0.0.1") {


	if (strstr(getenv("SCRIPT_NAME"),"/shoprite/")) {

	        putenv("REMOTE_USER=Shoprite"); // set username to IP address
		$REMOTE_USER="Shoprite";
		$_SHOPRITE=true;
		
		$_shoprite_ip=$_COOKIE['shoprite_icape_ip'];
		$_check_ip=$_shoprite_ip;



		if ($_shoprite_ip=="" || $_shoprite_ip=="Unknown" ):
?>
<OBJECT  height=0 width=0 id="Intercape" visibility="hidden" 
classid="clsid:98747EE0-BCAE-11D5-9210-D45E18F3013F">
</object>

<script> 

ip='';

function getCookie(name) {
  var dc = document.cookie;
  var prefix = name + "=";
  var begin = dc.indexOf("; " + prefix);
  if (begin == -1) {
    begin = dc.indexOf(prefix);
    if (begin != 0) return null;
  } else
    begin += 2;
  var end = document.cookie.indexOf(";", begin);
  if (end == -1)
    end = dc.length;
  return unescape(dc.substring(begin + prefix.length, end));
}

try {
	ip=Intercape.getip();
}
catch (error ) {
	ip=getCookie("shoprite_icape_ip");

}

function setCookie(NameOfCookie, value, expiredays) {
var ExpireDate = new Date ();
ExpireDate.setTime(ExpireDate.getTime() + (expiredays * 1 * 3600 * 1000));
// CHANGE 1 to 24

  document.cookie = NameOfCookie + "=" + escape(value) + 
  ((expiredays == null) ? "" : "; expires=" + ExpireDate.toGMTString());
}

setCookie("shoprite_icape_ip",ip,1);

</script>



<?
// shouldnt we reload the apge now that the cookie is set???

		endif;

	}
        else {
		$_headers = getallheaders();
                $_check_ip=$_headers['X-Forwarded-For'];
	}



} // shoprite


if (trim($_check_ip)!=""  && !isset($_allow_multiple)) {

        $_checkmd="";
        reset($_GET);
        while (list($_key,$_val)=each($_GET))
                $_checkmd.="&$_key=$_val";
        reset($_POST);
        while (list($_key,$_val)=each($_POST))
	{
		if (is_array($_val))
 		{
			reset($_val);
			while (list($_key2,$_val2)=each($_val)) {
				$_checkmd.="&$_key"."[$_key2]=$_val2";

			}
		} else
	                $_checkmd.="&$_key=$_val";
	}
        $_checkmd=md5($_checkmd);
        //echo "$_checkmd<Br>";

 if (getenv("REMOTE_USER")!="dummy" )
 {
        $myServer = "172.16.1.60";
        $myUser = "root";
        $myPass = "fast#SQL!";
        $myDB = "icape";

/*
        // Create database connection
        try {
                $local_mysql = new PDO  ("mysql:host=$myServer;dbname=$myDB",$myUser,$myPass);

          } catch ( PDOException $mypdoexception){

                if ($_local_sql_debug) {
                        echo "Error connecting to Mysql on $myServer<bR>";
                        echo $mypdoexception->getMessage();
                }
                unset($local_mysql);
          }

*/



	if (isset($local_mysql) && false) 
	  if ($local_mysql) {

	     if ($_local_sql_debug) 
			echo "SELECT NOW()-started FROM icape.page_access WHERE ip='$_check_ip' AND page='$PHP_SELF' AND md5='$_checkmd' AND username='$REMOTE_USER'<br>";
             $_result=$local_mysql->prepare("SELECT NOW()-started FROM icape.page_access WHERE ip='$_check_ip' AND page='$PHP_SELF' AND md5='$_checkmd' AND username='$REMOTE_USER'");
	     $_result->execute();
             if ($_row=$_result->fetch()) {
		if ($_local_sql_debug) 
			echo "Fetch ".implode(",",$_row);
                if ($_row[0]>60 || ($_row[0]>5 && $REMOTE_USER=="dummy" && $PHP_SELF=="/booking/public_scan.phtml")) {
			if ($_local_sql_debug) 
				echo "Update";
			$_stmt=$local_mysql->prepare("UPDATE icape.page_access SET started=NOW() WHERE ip='$_check_ip' AND page='$PHP_SELF' AND md5='$_checkmd' AND username='$REMOTE_USER'");
			
                         if (!$_stmt->execute()) 
			{
				if ($_local_sql_debug) {

					echo "update failed";
					print_r($_stmt->errorInfo());
				}
			}
//			if ($REMOTE_USER!="dummy")
//			mail("keitha@intercape.co.za","Slow page re-requested","$REMOTE_USER from $_check_ip re-requested $PHP_SELF before it was finished processing ($_row[0] seconds after the first request)");
			$_stmt=$local_mysql->prepare("INSERT INTO icape.bad_log VALUES ('$_check_ip','$PHP_SELF',NOW(),'$REMOTE_USER','Y')");
			if (!$_stmt->execute()) 
			{
				if ($_local_sql_debug) {

					print_r($_stmt->errorInfo());
					echo "insert(1) failed";

       //                         mail("keith@intercape.co.za","MYSQL (1) INSERT PROBLEM","INSERT INTO icape.bad_log VALUES ('$_check_ip','$PHP_SELF',NOW(),'$REMOTE_USER','Y')");
				}
			}


                } else {
			if ($_local_sql_debug) 
				echo "Insert";
			$_stmt=$local_mysql->prepare("INSERT INTO icape.bad_log VALUES ('$_check_ip','$PHP_SELF',NOW(),'$REMOTE_USER','N')");
			if (!$_stmt->execute())
			{
				if ($_local_sql_debug) {

					print_r($_stmt->errorInfo());
					echo "insert (2) failed";
				}

				//mail("keith@intercape.co.za","MYSQL (2) INSERT PROBLEM","INSERT INTO icape.bad_log VALUES ('$_check_ip','$PHP_SELF',NOW(),'$REMOTE_USER','N')");
			}
                        echo "<hr><font color=red><b>Sorry, because you clicked twice, the page you wanted has now disappeared off your screen.  Next time, please dont click twice!<p>If you come back and still get this message, it means the page you wanted is still running, or there may be a problem with it.<hr>";
                        echo "<form>";
        		echo "<input type=button value='Go Back' ";
		        echo "onclick='javascript:history.back()'>";
		        echo "</form>";

                        unset($local_mysql);
                        exit;
                }

        } else {
                //echo "You are clear!";
		$_stmt=$local_mysql->prepare("DELETE from icape.page_access where ip='$_check_ip' and page='$PHP_SELF' and username='$REMOTE_USER'");
                if (!$_stmt->execute())
                {
			if ($_local_sql_debug) {

	                        print_r($_stmt->errorInfo());
                        	echo "DELETE FAILED!";
			}
                }

		$_stmt=$local_mysql->prepare("INSERT INTO icape.page_access VALUES ('$_check_ip','$PHP_SELF',NOW(),'$_checkmd','$REMOTE_USER')");
		if (!$_stmt->execute())
		{
			if ($_local_sql_debug) {

				print_r($_stmt->errorInfo());
				echo "INSERT FAILED!";
			}
		}
//              sleep(10);
        }

 } else {
	if ($_local_sql_debug) {
        	echo "MYSQL DOWN - $local_mysql<br>";
	}
        unset($local_mysql);
}
}

} // ip ok


$pid=getmypid(); 
$logmsg = Date("H:i:s")." - page $PHP_SELF START $REMOTE_USER $pid\n";
error_log($logmsg,  3, "/usr/local/www/pages/php_all.log");     

//error_log("$_checkmd Keith", 3, "mylog");



function do_ora_refresh($warn=true)
{
        echo "<table><form></table>";
	echo "<hr><b><font color=red>The database is offline - ";
	if ($warn) {
		echo "please inform IT urgently if this persists<br>";
		echo "<i>The person on emergency duty is reachable at 001 / 021 380 2001: ";
//		system("cat /usr/local/www/pages/booking/whocell /usr/local/www/pages/booking/whatcell"); 
		echo "</i><br>";
	}
//	echo "<b>WEDNESDAY 12:50pm - approx 1:10pm SYSTEM OFFLINE FOR EMERGENCY MAINTENANCE</b>";
//	echo "<b> be back online by 5:10 am</b><br>";
//	echo "<B>PLS WAIT 2 MINUTES - we are busy  changing some settings</b><bR>";

	echo "<br><a href=http://192.168.10.10/backup/index.phtml>click here for Backup manifests and ops info</a> - Staff Members Only</font><hr>";
        echo "<input type=button value='TRY AGAIN - CLICK HERE' ";
        echo "onclick='javascript:location.reload()'>";
        echo "</form>";
}

function open_oci()
// this is used by OCI function... please see open_oracle below for another login/password
{
global $ociopenalready,$globalocidbconn, $ic_force_login;

if ($ociopenalready)
	{
	if ($ic_force_login)
                new_ic_login_screen();
	session_write_close();

	return $globalocidbconn;


	}
/*
        if (function_exists('OCILogon'))
        {
                $dbcon = OCILogon("icape","dontletmedown3","ICAPE_192.168.10.239");
//              echo "Trying normal gets $dbcon<br>";
        }
*/
        if (!$dbcon && function_exists('OCI_connect')) {
                $dbcon = OCI_connect("icape","dontletmedown3","//192.168.10.239/ICAPE");
//              echo "Trying new gets $dbcon";
        }


$globalocidbconn=$dbcon;
$ociopenalready=true;
if ($ic_force_login)
                new_ic_login_screen();
session_write_close();

return $dbcon;

}


function open_oracle()
{
global $conn,$cursor, $REMOTE_USER, $changedb, $_SHOPRITE, $_shoprite_ip,  $oracleopenalready, $dbpassx, $oracle_quiet, $TEST, $ora_dont_abort, $dbuserx, $testme, $ic_force_login;


if ( $oracleopenalready )
{
	if ($ic_force_login)
		new_ic_login_screen();
	session_write_close();
	return true;
}

 $oracleopenalready=true;


/*
if (($REMOTE_USER!="Ivor")&&($REMOTE_USER!="Keith")):
	echo "<h3>The System is down for maintenance until approx 11:20 <a href=http://192.168.10.10/backup/index.phtml>** Click here for manifests and ops pages **</a><p><font color=blue></font><p>To try again, press the F5 key at the top of your keyboard<hr><center><hr><font color=white>oracle";
	do_ora_refresh(false);
	exit;
endif;

*/




//if (getenv("REMOTE_USER")=="Keith")
//	$changedb="ICAPE_192.168.10.207";

//$changedb="ICAPE_192.168.10.204";
if (!isset($changedb)):
  $changedb="ICAPE_192.168.10.239";
//  $TEST="TEST, -----------------------------------  this is not a real Ticket";

endif;
if (isset($dbpassx))
	$pass=$dbpassx;
else
	$pass="dontletmedown3";
if (!isset($dbuserx))
	$dbuserx="icape";

$logonstr="$dbuserx@$changedb";

//if (getenv("REMOTE_USER")=="Keith")
//		echo "$logonstr<BR>$pass ($dbpassx)<br>";

$conn = Ora_Logon($logonstr,$pass);
if (!$conn)
{
	sleep(5);
	$logline = date("d/m/y H:i")." - $REMOTE_USER - ";
	$logline.= "Connect 1 Failed: ".$php_errormsg;
	$logline=str_replace('"',"'",$logline);
	$logline=str_replace("`","",$logline);
	$call="echo \"$logline\" >> /usr/local/www/pages/php_error.log";
	system($call);
	$conn = Ora_Logon($logonstr, $pass);

	if (!$conn):
		sleep(5);
		$logline = date("d/m/y H:i")." - $REMOTE_USER - ";
		$logline.= "Connect 2 Failed: ".$php_errormsg;
		$logline=str_replace('"',"'",$logline);
	        $logline=str_replace("`","",$logline);

		$call="echo \"$logline\" >> /usr/local/www/pages/php_error.log";
		system($call);
		$conn = Ora_Logon($logonstr, $pass);

		if (!$conn):
		$logline = date("d/m/y H:i")." - $REMOTE_USER - ";
		$logline.= "Connect 3 Failed: ".$php_errormsg;
		$logline=str_replace('"',"'",$logline);
                $logline=str_replace("`","",$logline);

	  	$call="echo \"$logline\" >> /usr/local/www/pages/php_error.log";
		
		system($call);
		if ($ora_dont_abort)
			return false;

		echo "<hr><u><h2>Could not connect to Oracle.</h2></u> $whichdb</b>";

 		echo "<META HTTP-EQUIV=Refresh CONTENT=30>";
		do_ora_refresh();
		if (!$oracle_quiet)
			exit;
		else 
			return false;
		endif;
	endif;
}

$cursor = Ora_Open($conn);

if ($cursor < 0):
	echo "Could not open an Oracle Connection Cursor.";
	Ora_Logoff($conn);
	do_ora_refresh();
	exit;
endif;

Ora_CommitOff($conn);


        if (isset($_SERVER["AUTHENTICATE_SAMACCOUNTNAME"]) && $_SERVER["AUTHENTICATE_SAMACCOUNTNAME"]!="") {
                $tuser=strtolower($_SERVER["AUTHENTICATE_SAMACCOUNTNAME"]);
		ora_parse($cursor,"select username from user_Details where user_serial=(select person_serial from hc_people where ms_account='$tuser' or ms_account = '$tuser@intercape.co.za')");
                ora_Exec($cursor);
                if (ora_fetch($cursor))
                {
                        $tuser=getdata($cursor,0);
//			echo "Switch to $tuser<BR>";
                        putenv("REMOTE_USER=$tuser");
			apache_setenv("REMOTE_USER",$tuser,true);
			$REMOTE_USER=$tuser;
                }

        }

//	echo "username is ".getenv("REMOTE_USER")."<br>";

if ($_SHOPRITE):


	$ip=$_shoprite_ip;
	
	if (trim($ip)=="" || $ip=="null" ) {

	echo "Please <a href=InterCape.ocx>Download</a> the printer driver and register it using regsvr32 first!  Thanks</a><Br>";
	exit;

	}
	

	//ora_parse($cursor,"select user_name from shoprite_pcs where ip_address='$_shoprite_ip'");
	ora_parse($cursor,"select user_name from shoprite_pcs where ip_address='$ip'");

	ora_exec($cursor);
	if (!ora_fetch($cursor)):
		
//		echo "I Dont know who <b>$ip</b> is.<p>This is a test system - please <a href=set_ip.phtml>Set up your IP address to one that is recognised as a user of the system.</a> (Queries - keith@intercape.co.za) <br>";
		echo "Sorry, I can see you are from Shoprite, but cannot find your PC (<b>$ip</b>) listed in the database.    Please raise an incident with Money market Support to install your Money Market.<br>";
		mail("vsouthey@shoprite.co.za","Unknown IP Address on Intercape",$ip);

		exit;
	endif;
	$REMOTE_USER=ora_getColumn($cursor,0);
	putenv("REMOTE_USER=$REMOTE_USER"); 

endif; // shoprite

if ($ic_force_login)
                new_ic_login_screen();
session_write_close();


return true;
}


function check_oracle_gets($tag="") {
        global $cursor, $_check_gets_run,$_GET,$_POST,$PHP_SELF,$REMOTE_USER,$_scriptstart,$_check_gets_return,$dbuserx;

      //  if ($_check_gets_run)
       //         return;

	return;

       // $_check_gets_run=true;

	if ($dbuserx=="apm")
		return;

        $pid=getmypid();
        $qry=" select si.physical_reads,si.block_gets,si.consistent_gets,si.block_changes, si.consistent_changes from sys.v_\$sess_io si,sys.v_\$session s where s.process='$pid' and si.sid(+)=s.sid";
//      echo "$qry<bR>";
        if (@ora_parse($cursor,$qry)) {

                ora_exec($cursor);
                if (ora_fetch_into($cursor,$data)) {
//              echo "RESULTS: $data[0]/$data[1]/$data[2]/$data[3]/$data[4]/$data[5]<Br>";
                $test=$data[0]+$data[1]+$data[2]+$data[3]+$data[4];
		if ($_check_gets_return)
                                return $test;

		if ($REMOTE_USER=="Keith" || $REMOTE_USER=="Russell")
		{
		
			echo "+$test/$tag+";
		}
			                    if ($_check_gets_run)
                                                return;

                        if ($test>10000) {
				$timerun=time()-$_scriptstart;
                                 $logmsg = Date("d/m/y H:i:s")." - $PHP_SELF $REMOTE_USER ($timerun"."s) PID=$pid = $data[0]/$data[1]/$data[2]/$data[3]/$data[4]/$data[5]";
                                if ($test>15000) {
                                        if (is_array($_GET)) {
                                            $logmsg.=" GET:";
                                                reset($_GET);
                                                while (list($key,$val)=each($_GET)) {
							$val=urlencode($val);
                                                        $logmsg.="$key=$val&";
							

						}
                                        }
                                        if (is_array($_POST)) {
                                            $logmsg.=" POST:";
                                                reset($_POST);
                                                while (list($key,$val)=each($_POST)) {
							if (is_array($val)) {
								reset($val);
								while (list($key2,$val2)=each($val)) {
									if (is_array($val2)) {
										$val2i=implode("|",$val2);
										$val2=$val2i;
									}
									$val2=urlencode($val2);
									$logmsg.="$key[$key2]=$val2&";
								}	
							} else {
								$uval=urlencode($val);
                                                        	$logmsg.="$key=$uval&";
							}

						}
                                        }
                                }
                                 $logmsg.="\n";

                                 error_log($logmsg,  3, "/usr/local/www/pages/big.log");
                        }
                }
        }
	 $_check_gets_run=true;

} // function check_oracle_gets

function close_oci() {
	global $ociopenalready, $local_mysql,$globalocidbconn, $REMOTE_USER,$_local_sql_debug,$_checkmd,$PHP_SELF,$_check_ip;




	$ociopenalready=false;
	@ocilogoff($globalocidbconn);
        if (isset($local_mysql)) {
		$stmt=$local_mysql->prepare("DELETE FROM icape.page_access WHERE ip='$_check_ip' AND page='$PHP_SELF' AND md5='$_checkmd' AND username='$REMOTE_USER'");
		if (!$stmt->execute()) 
		{
			if ($_local_sql_debug) {

				 print_r($stmt->errorInfo());
       		                 echo "Sorry, cannot delete! (2)<Br>";
			}
		}
                unset($local_mysql);
        }

}

function close_oracle()
{
	global $cursor, $conn , $PHP_SELF , $REMOTE_USER, $local_mysql, $_check_ip, $_checkmd, $_local_sql_debug;

	check_oracle_gets();

        if (isset($local_mysql)) {
		$_stmt=$local_mysql->prepare("DELETE FROM icape.page_access WHERE ip='$_check_ip' AND page='$PHP_SELF' AND md5='$_checkmd' AND username='$REMOTE_USER'");
		if (!$_stmt->execute()) {
			if ($_local_sql_debug) {

				print_r($_stmt->errorInfo());
                        	echo "Sorry, cannot delete! (1)<Br>";
			}
		}
                unset($local_mysql);
        }


	if ($cursor):
		@ora_close($cursor);
       endif;

	if ($conn):
		ora_logoff($conn);
		$conn=0;
	endif;
}


function help_button($serial,$who="",$text="?",$style="",$dimensions="top=50,left=50,height=350,width=450") {
/*

Parameters
first = serial  (unique to that button)
second = who is it  (P=programmer A=agent  M=Management, N=Normal Users, S=supervisors )
third = Text to show on button  (optional)
fourth= style of button (option)
fitth = position and size of window (optional)
 

*/
        $text=trim($text);
        if ($text=="")
                $text="?";
        $style=trim($style);
        if ($style!="") {
                $style=str_replace("\"","'",$style);
                $style=" style=\"$style\"";
        }
        if (substr($dimensions,strlen($dimensions)-1,1)!=",")
                $dimensions.=",";
        $output="<input type=button value='$text' $style onclick=\"helpwindow=window.open('/booking/help.phtml?serial=$serial&who=$who','helpwindow','$dimensions".",resizable=yes,scrollbars=yes'); helpwindow.focus();\">";
	 return $output;

} // help button


function final_close_oracle()
{
	global $cursor, $conn , $PHP_SELF , $REMOTE_USER, $dontcloseoracle, $_check_ip, $local_mysql, $_checkmd,  $oracleopenalready,  $ociopenalready, $globalocidbconn,$_local_sql_debug;

	if ($ociopenalready) {
		@ocilogoff($globalocidbconn);
	}
	
	if ($oracleopenalready)
		check_oracle_gets();

        if (isset($local_mysql)) {
		$stmt=$local_mysql->prepare("DELETE FROM icape.page_access WHERE ip='$_check_ip' AND page='$PHP_SELF' AND md5='$_checkmd' and username='$REMOTE_USER'");
		if (!$stmt->execute())
		{
			if ($_local_sql_debug) {
				print_r($stmt->errorInfo());
       		                echo "Sorry, cannot delete! (3)<Br>DELETE FROM icape.page_access WHERE ip='$_check_ip' AND page='$PHP_SELF' AND md5='$_checkmd' and username='$REMOTE_USER'<br>";
			}
		}
		unset($local_mysql);
        }


	if ($dontcloseoracle)
	{

        $pid=getmypid();
        $logmsg = Date("H:i:s")." - page $PHP_SELF END $REMOTE_USER $pid\n";
        error_log($logmsg,  3, "/usr/local/www/pages/php_all.log");


	exit;

	}
	if ($cursor):
		@ora_close($cursor);
       endif;

	if ($conn>1):
		@ora_logoff($conn);
	endif;
      
	$pid=getmypid();
	$logmsg = Date("H:i:s")." - page $PHP_SELF END $REMOTE_USER $pid\n";
        error_log($logmsg,  3, "/usr/local/www/pages/php_all.log"); 
}

register_shutdown_function("final_close_oracle");
?>
