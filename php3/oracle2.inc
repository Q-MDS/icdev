<?
/* ************************************************************************************** */
/* ************** Oracle logon sequence for Intercape Booking Sequence ****************** */
/* ************************************************************************************** */
$pid=getmypid(); 
$logmsg = Date("H:i:s")." - page $PHP_SELF START $REMOTE_USER $pid\n";
error_log($logmsg,  3, "/usr/local/www/pages/php_all.log");     


function do_ora_refresh()
{
        echo "<form>";
//	echo "<b>Please wait - we are busy reloading the server - wait 60 seconds, then click the button</b><br>";
        echo "<input type=button value='TRY AGAIN - CLICK HERE' ";
        echo "onclick='javascript:location.reload()'>";
        echo "</form>";
}

function open_oracle()
{
global $conn,$cursor, $REMOTE_USER, $whichdb;


//echo "<b><font color=red>THE BOOKING SYSTEM IS DOWN FOR 5 MINUTES FOR MAINTAINANCE. </b></font><br>";
//exit;



/*


if (($REMOTE_USER!="Ivor")&&($REMOTE_USER!="Keith")):
	echo "<h2>The Booking System is down for maintainance and will be back in about 5 mins</h2>";
exit;
endif;

*/


















$pass="icapeoracle";
if (!isset($whichdb)):
   $whichdb="192.168.10.210";
endif;
$logonstr="icape@$whichdb";

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
		echo "<b>Could not connect to Oracle. $whichdb</b><br>";

 		echo "<META HTTP-EQUIV=Refresh CONTENT=30>";
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

	if ($conn>1):
		ora_logoff($conn);
	endif;
      
	$pid=getmypid();
	$logmsg = Date("H:i:s")." - page $PHP_SELF END $REMOTE_USER $pid\n";
        error_log($logmsg,  3, "/usr/local/www/pages/php_all.log"); 
}

register_shutdown_function("final_close_oracle");
?>
