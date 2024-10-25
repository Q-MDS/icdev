<?
/* ************************************************************************************** */
/* ************** Oracle logon sequence for Intercape Booking Sequence ****************** */
/* ************************************************************************************** */
$pid=getmypid(); 
$logmsg = Date("H:i:s")." - page $PHP_SELF START $REMOTE_USER $pid\n";
error_log($logmsg,  3, "/usr/local/www/pages/php_all.log");     
/*
if ($REMOTE_USER!="Ivor"):

  echo "<h2>Booking system is currently down - please try again in 5 minutes.  We are aware of the problem</h2>";
  exit;
endif;
*/
function do_ora_refresh()
{
        echo "<form>";
        echo "<input type=button value='TRY AGAIN - CLICK HERE' ";
        echo "onclick='javascript:location.reload()'>";
        echo "</form>";
}

function open_oracle()
{
global $conn,$cursor, $REMOTE_USER;


//echo "<h2>THE BOOKING SYSTEM IS GOING DOWN IN <strong>30 secs</strong>.<br> PLEASE FINISH YOUR WORK AND LOGOFF<br></h2>";
//echo "IT WILL BE BACK UP BY 09:00";

//if ($REMOTE_USER!="tw"):
//	echo "The Booking System has been shutdown until 09:00.<br>";
//	echo "Please try again later.<br>";
//	exit;
//endif;


$pass="icapeoracle";
$logonstr="icape@192.168.10.8";

$conn = Ora_Logon($logonstr,$pass);
if (!$conn)
{
	sleep(5);
	$logline = date("d/m/y H:i")." - $REMOTE_USER - ";
	$logline.= "Connect 1 Failed: ".$php_errormsg;
	$call="echo \"$logline\" >> /usr/local/www/pages/php_error.log";
	system($call);
	$conn = Ora_Logon($logonstr, $pass);

	if (!$conn):
		sleep(5);
		$logline = date("d/m/y H:i")." - $REMOTE_USER - ";
		$logline.= "Connect 2 Failed: ".$php_errormsg;
		$call="echo \"$logline\" >> /usr/local/www/pages/php_error.log";
		system($call);
		$conn = Ora_Logon($logonstr, $pass);

		if (!$conn):
		$logline = date("d/m/y H:i")." - $REMOTE_USER - ";
		$logline.= "Connect 3 Failed: ".$php_errormsg;
	  	$call="echo \"$logline\" >> /usr/local/www/pages/php_error.log";
		system($call);
		echo "<b>Could not connect to Oracle.</b><br>";
		do_ora_refresh();
		exit;
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

return true;
}

function close_oracle()
{
	global $cursor, $conn , $PHP_SELF , $REMOTE_USER;

	if ($cursor):
		@ora_close($cursor);
       endif;

	if ($conn):
		ora_logoff($conn);
	endif;
}

function final_close_oracle()
{
	global $cursor, $conn , $PHP_SELF , $REMOTE_USER;

	if ($cursor):
		@ora_close($cursor);
       endif;

	if ($conn):
		ora_logoff($conn);
	endif;
      
	$pid=getmypid();
	$logmsg = Date("H:i:s")." - page $PHP_SELF END $REMOTE_USER $pid\n";
        error_log($logmsg,  3, "/usr/local/www/pages/php_all.log"); 
}

register_shutdown_function("final_close_oracle");
?>
