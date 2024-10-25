<?
$pid=getmypid(); 
$logmsg = Date("H:i:s")." - page $PHP_SELF START $REMOTE_USER $pid\n";
error_log($logmsg,  3, "/usr/local/www/pages/php_all.log");     

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