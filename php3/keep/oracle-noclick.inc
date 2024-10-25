<?
//setcookie("shoprite_icape_ip","");


$_scriptstart=time();

/* ************************************************************************************** */
/* ************** Oracle logon sequence for Intercape Booking Sequence ****************** */
/* ************************************************************************************** */

unset($_SHOPRITE); // dont allow poisoning


if (getenv("REMOTE_ADDR")=="127.0.0.1") {


	if (strstr(getenv("SCRIPT_NAME"),"/shoprite/")) {

	        putenv("REMOTE_USER=Shoprite"); // set username to IP address
		$REMOTE_USER="Shoprite";
		$_SHOPRITE=true;
		
		$_shoprite_ip=$_COOKIE['shoprite_icape_ip'];


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

		endif;

	}

} // shoprite



$pid=getmypid(); 
$logmsg = Date("H:i:s")." - page $PHP_SELF START $REMOTE_USER $pid\n";
error_log($logmsg,  3, "/usr/local/www/pages/php_all.log");     




function do_ora_refresh($warn=true)
{
        echo "<table><form></table>";
	echo "<hr><b><font color=red>The database is offline - ";
	if ($warn) {
		echo "please inform IT urgently if this persists<br>";
		echo "<i>The person on emergency duty is: ";
		system("cat /usr/local/www/pages/booking/whocell /usr/local/www/pages/booking/whatcell"); 
		echo "</i><br>";
	}
//	echo "<b>FRIDAY 11am-12pm TEST SYSTEM OFFLINE FOR REPAIRS</b>";
//	echo "<b> be back online by 5:10 am</b><br>";
	echo "<br><a href=http://192.168.10.10/backup/index.phtml>click here for Backup manifests and ops info</a> </font><hr>";
        echo "<input type=button value='TRY AGAIN - CLICK HERE' ";
        echo "onclick='javascript:location.reload()'>";
        echo "</form>";
}

function open_oci()
// this is used by OCI function... please see open_oracle below for another login/password
{
$dbcon = OCILogon("icape","dontletmedown","ICAPE_192.168.10.205");
return $dbcon;

}


function open_oracle()
{
global $conn,$cursor, $REMOTE_USER, $changedb, $_SHOPRITE, $_shoprite_ip;


/*

if (($REMOTE_USER!="Ivor")&&($REMOTE_USER!="Keith")):
	echo "<h3>The System is down for emergency repairs for approx 5 to 10mins <a href=http://192.168.10.10/backup/index.phtml>** Click here for manifests and ops pages **</a><p><font color=blue></font><p>To try again, press the F5 key at the top of your keyboard<hr><font color=white>oracle";
	do_ora_refresh(false);
	exit;
endif;
 
*/




//$changedb="ICAPE_192.168.10.204";
//if (getenv("REMOTE_USER")=="Keith")
//	$changedb="ICAPE_192.168.10.64";
if (!isset($changedb)):
  $changedb="ICAPE_192.168.10.205";
endif;
$pass="dontletmedown";
$logonstr="icape@$changedb";

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
		echo "<hr><u><h2>Could not connect to Oracle.</h2></u> $whichdb</b>";

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

return true;
}


function check_oracle_gets() {
        global $cursor, $_check_gets_run,$_GET,$_POST,$PHP_SELF,$REMOTE_USER,$_scriptstart;

        if ($_check_gets_run)
                return;

        $_check_gets_run=true;

        $pid=getmypid();
        $qry=" select si.physical_reads,si.block_gets,si.consistent_gets,si.block_changes, si.consistent_changes from sys.v_\$sess_io si,sys.v_\$session s where s.process='$pid' and si.sid(+)=s.sid";
//      echo "$qry<bR>";
        if (@ora_parse($cursor,$qry)) {

                ora_exec($cursor);
                if (ora_fetch_into($cursor,$data)) {
//              echo "RESULTS: $data[0]/$data[1]/$data[2]/$data[3]/$data[4]/$data[5]<Br>";
                $test=$data[0]+$data[1]+$data[2]+$data[3]+$data[4]+$data[5];
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
							$val=urlencode($val);
                                                        $logmsg.="$key=$val&";

						}
                                        }
                                }
                                 $logmsg.="\n";

                                 error_log($logmsg,  3, "/usr/local/www/pages/big.log");
                        }
                }
        }
} // function check_oracle_gets


function close_oracle()
{
	global $cursor, $conn , $PHP_SELF , $REMOTE_USER;

	check_oracle_gets();

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
	global $cursor, $conn , $PHP_SELF , $REMOTE_USER, $dontcloseoracle;

	check_oracle_gets();

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
		ora_logoff($conn);
	endif;
      
	$pid=getmypid();
	$logmsg = Date("H:i:s")." - page $PHP_SELF END $REMOTE_USER $pid\n";
        error_log($logmsg,  3, "/usr/local/www/pages/php_all.log"); 
}

register_shutdown_function("final_close_oracle");
?>
