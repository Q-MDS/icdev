<?
//setcookie("shoprite_icape_ip","");


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
global $conn,$cursor, $REMOTE_USER, $changedb, $_SHOPRITE, $_shoprite_ip, $testit;


$REMOTE_USER=getenv("REMOTE_USER");
/*
if (!$testit)
if (($REMOTE_USER!="Ivor")&&($REMOTE_USER!="Keith")&&($REMOTE_USER!="Russell")):
	echo "<h3>$REMOTE_USER, This test system is not available except for testing new software, due to licensing agreements with Oracle..</h3>";
//	echo "<h3>The System is down for emergency repairs for approx 5 to 10mins <a href=http://192.168.10.10/backup/index.phtml>** Click here for manifests and ops pages **</a><p><font color=blue></font><p>To try again, press the F5 key at the top of your keyboard<hr><font color=white>oracle";
	//do_ora_refresh(false);
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

$dontcloseoracle=true;
$conn = Ora_pLogon($logonstr,$pass);
if (!$conn)
{
	sleep(5);
	$logline = date("d/m/y H:i")." - $REMOTE_USER - ";
	$logline.= "Connect 1 Failed: ".$php_errormsg;
	$call="echo \"$logline\" >> /usr/local/www/pages/php_error.log";
	system($call);
	$conn = Ora_pLogon($logonstr, $pass);

	if (!$conn):
		sleep(5);
		$logline = date("d/m/y H:i")." - $REMOTE_USER - ";
		$logline.= "Connect 2 Failed: ".$php_errormsg;
		$call="echo \"$logline\" >> /usr/local/www/pages/php_error.log";
		system($call);
		$conn = Ora_pLogon($logonstr, $pass);

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
	
	if (trim($ip)=="") {

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

function close_oracle()
{
	global $cursor, $conn , $PHP_SELF , $REMOTE_USER,$dontcloseoracle;

	

	if ($dontcloseoracle) {
		ora_commit($conn);
	} else {

	if ($cursor):
		@ora_close($cursor);
       endif;

	if ($conn):
		ora_logoff($conn);
		$conn=0;
	endif;
	}
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
	global $cursor, $conn , $PHP_SELF , $REMOTE_USER, $dontcloseoracle , $al_cur, $bccur, $bcur, $bcursor, $bigcursor, $c2, $c_cursor, $ccur, $checkcur, $cmc, $cone, $cpcur, $cps_cursor, $cr_cur, $csa, $cscur, $ctc, $ctwo, $cur1, $cur2, $cursor, $cursor2, $cursor3, $cursor4, $curt, $cvc, $damn, $dcur, $ecur, $excur, $fcor, $fcos, $fcur, $geturl, $gst, $gus, $hcur, $infocur, $k2cur, $kcur, $kcur2, $kcur3, $kcursor, $kwcur, $kwcursor, $lcur, $mcur, $mcursor, $mn_cur, $ncursor, $newc, $newc2, $ohtcur, $passcur, $pcur, $rsa, $sp, $tcr, $tcur, $tcur2, $tcursor, $testcur, $tmp, $tmp_cursor, $tmpcur, $tmpcursor, $ttcur, $ucur, $ucursor, $writecur, $xcur, $xtcur, $ycur;

	if ($dontcloseoracle)
	{

	ora_commit($conn);
        $pid=getmypid();
        $logmsg = Date("H:i:s")." - page $PHP_SELF END $REMOTE_USER $pid\n";
        error_log($logmsg,  3, "/usr/local/www/pages/php_all.log");

if (isset($al_cur))  @ora_close($al_cur);
if (isset($bccur))  @ora_close($bccur);
if (isset($bcur))  @ora_close($bcur);
if (isset($bcursor))  @ora_close($bcursor);
if (isset($bigcursor))  @ora_close($bigcursor);
if (isset($c2))  @ora_close($c2);
if (isset($c_cursor))  @ora_close($c_cursor);
if (isset($ccur))  @ora_close($ccur);
if (isset($checkcur))  @ora_close($checkcur);
if (isset($cmc))  @ora_close($cmc);
if (isset($cone))  @ora_close($cone);
if (isset($cpcur))  @ora_close($cpcur);
if (isset($cps_cursor))  @ora_close($cps_cursor);
if (isset($cr_cur))  @ora_close($cr_cur);
if (isset($csa))  @ora_close($csa);
if (isset($cscur))  @ora_close($cscur);
if (isset($ctc))  @ora_close($ctc);
if (isset($ctwo))  @ora_close($ctwo);
if (isset($cur1))  @ora_close($cur1);
if (isset($cur2))  @ora_close($cur2);
if (isset($cursor2))  @ora_close($cursor2);
if (isset($cursor3))  @ora_close($cursor3);
if (isset($cursor4))  @ora_close($cursor4);
if (isset($curt))  @ora_close($curt);
if (isset($cvc))  @ora_close($cvc);
if (isset($damn))  @ora_close($damn);
if (isset($dcur))  @ora_close($dcur);
if (isset($ecur))  @ora_close($ecur);
if (isset($excur))  @ora_close($excur);
if (isset($fcor))  @ora_close($fcor);
if (isset($fcos))  @ora_close($fcos);
if (isset($fcur))  @ora_close($fcur);
if (isset($geturl))  @ora_close($geturl);
if (isset($gst))  @ora_close($gst);
if (isset($gus))  @ora_close($gus);
if (isset($hcur))  @ora_close($hcur);
if (isset($infocur))  @ora_close($infocur);
if (isset($k2cur))  @ora_close($k2cur);
if (isset($kcur))  @ora_close($kcur);
if (isset($kcur2))  @ora_close($kcur2);
if (isset($kcur3))  @ora_close($kcur3);
if (isset($kcursor))  @ora_close($kcursor);
if (isset($kwcur))  @ora_close($kwcur);
if (isset($kwcursor))  @ora_close($kwcursor);
if (isset($lcur))  @ora_close($lcur);
if (isset($mcur))  @ora_close($mcur);
if (isset($mcursor))  @ora_close($mcursor);
if (isset($mn_cur))  @ora_close($mn_cur);
if (isset($ncursor))  @ora_close($ncursor);
if (isset($newc))  @ora_close($newc);
if (isset($newc2))  @ora_close($newc2);
if (isset($ohtcur))  @ora_close($ohtcur);
if (isset($passcur))  @ora_close($passcur);
if (isset($pcur))  @ora_close($pcur);
if (isset($rsa))  @ora_close($rsa);
if (isset($sp))  @ora_close($sp);
if (isset($tcr))  @ora_close($tcr);
if (isset($tcur))  @ora_close($tcur);
if (isset($tcur2))  @ora_close($tcur2);
if (isset($tcursor))  @ora_close($tcursor);
if (isset($testcur))  @ora_close($testcur);
if (isset($tmp))  @ora_close($tmp);
if (isset($tmp_cursor))  @ora_close($tmp_cursor);
if (isset($tmpcur))  @ora_close($tmpcur);
if (isset($tmpcursor))  @ora_close($tmpcursor);
if (isset($ttcur))  @ora_close($ttcur);
if (isset($ucur))  @ora_close($ucur);
if (isset($ucursor))  @ora_close($ucursor);
if (isset($writecur))  @ora_close($writecur);
if (isset($xcur))  @ora_close($xcur);
if (isset($xtcur))  @ora_close($xtcur);
if (isset($ycur))  @ora_close($ycur);
if (isset($cursor)) @ora_close($cursor);

	ora_commit($conn);
	exit;

	}
	else {
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
}

register_shutdown_function("final_close_oracle");
?>
