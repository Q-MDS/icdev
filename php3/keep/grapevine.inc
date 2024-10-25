<?php

/* 

OPERATIONAL ACCOUNT - Use for operational messages:
--------------------
Affiliate Code: INT011-001
Login URL: http://www.gvi.bms27.vine.co.za/webAccess/Login.do 
Username: Keith@intercape.co.za
Password: NY5A63DFQ
Authentication Code: INTERCAPEOPERATIONAL


MARKETING ACCOUNT - USE FOR MARKETING / Bulk :
-----------------
Your user details for: AppLink
Affiliate Code: INT011
Login URL: http://www.gvi.bms9.vine.co.za/webAccess/Login.do
Username: INTERCAPEAPPLINK
Password: 483YDUFAU
Authentication Code: INTERCAPEFMAPPLINK



*/


function send_grapevine_sms ( $cellno,$msg, $cs="", $replyto="", $reference="", $serial="") {



// seiral is used as a reference to tie up the reply

global $smscursor,$conn,$cookieip,$_misc_included,$grapevinetimeout,$smsdb_already_done,$use_sms_system;

if ($replyto=="M" && $use_sms_system=="")
	$use_sms_system="marketing";

if ($grapevinetimeout)
	return false; // previous message from this process already failed

//return false; // disable - use another supplier

$cellno=str_replace(" ","",$cellno);


if (isset($conn)) {
	if (!isset($smscursor))
		$smscursor=ora_open($conn);
	if ($serial=="") {
	
		ora_parse($smscursor,"select sms_id.nextval from dual");
		ora_Exec($smscursor);
		ora_fetch($smscursor);
		$serial=ora_getcolumn($smscursor,0);

	}

}

$now=time();
$omsg=str_replace("'","",$msg);

$msg=str_replace("&","&amp;",$msg);

$msg=str_replace("<","&lt;",$msg);

$msg=str_replace(">","&gt;",$msg);

$msg=str_replace("\"","&quot;",$msg);

$msg=str_replace("'","&apos;",$msg);

$uri=$_SERVER['REQUEST_URI'];

$uri=str_replace("&","&amp;",$uri);

$uri=str_replace("<","&lt;",$uri);

$uri=str_replace(">","&gt;",$uri);

$uri=str_replace("\"","&quot;",$uri);

$uri=str_replace("'","&apos;",$uri);

$oreference=str_replace("'","",$reference);

$reference=str_replace("&","&amp;",$reference);

$reference=str_replace("<","&lt;",$reference);

$reference=str_replace(">","&gt;",$reference);

$reference=str_replace("\"","&quot;",$reference);

$reference=str_replace("'","&apos;",$reference);

if (substr($cellno,0,2)=="00")
	$cellno="+".substr($cellno,2,999);

$number=$cellno; // for database
if ($cellno[0]=="+")
	$cellno=substr($cellno,1,999);

if (substr($cellno,0,2)=="27" || $cellno[0]=="0") {

	if ($cellno[0]=="0")
	{
		$checkit=$cellno[1];
		$checkit2=$cellno[2];
	}
	else
	{
		$checkit=$cellno[2];
		$checkit2=$cellno[3];
	}
	if ($checkit<6)
		return false; // not a SA cell number
	if ($checkit=="8" && ($checkit2=="7" || $checkit2=="6" || $checkit2=="0"))
		return false;   // not a SA cell number

}


if (!is_numeric($cellno))
	return false; // NOT A NUMBER!!

$myip=getenv("REMOTE_ADDR");
if ($myip=="127.0.0.1") {
               $myip="S:".$_SERVER['HTTP_X_FORWARDED_FOR'];
}

 $whoami=getenv("REMOTE_USER");
                if ($whoami=="")
                        $whoami=$otrusername;

$uri=str_replace(",","|",$uri);


if ($use_sms_system=="marketing" )
{

$xml='<?xml version="1.0" encoding="UTF-8"?>
<gviSmsMessage>
<affiliateCode>INT011</affiliateCode>
<authenticationCode>INTERCAPEFMAPPLINK</authenticationCode>
<submitDateTime>'.date("Y-m-d",$now).'T'.date("His",$now).'</submitDateTime>
<originator>'.$whoami.' '.$uri.'</originator>
<messageType>text</messageType>
<recipientList>
<message>'.$msg.'</message>
<recipient>
<msisdn>'.$cellno.'</msisdn>
<customData><serial>'.$serial.'</serial><reference>'.$reference.'</reference><page>'.$uri.'</page><logip>'.$myip.'/'.$cookieip.'</logip></customData>
</recipient>
</recipientList>
</gviSmsMessage>';

$gviserver="www.gvi.bms9.vine.co.za";
//echo "Marketing";


} else {
$xml='<?xml version="1.0" encoding="UTF-8"?>
<gviSmsMessage>
<affiliateCode>INT011-001</affiliateCode>
<authenticationCode>INTERCAPEOPERATIONAL</authenticationCode>
<submitDateTime>'.date("Y-m-d",$now).'T'.date("His",$now).'</submitDateTime>
<originator>'.$whoami.' '.$uri.'</originator>
<messageType>text</messageType>
<recipientList>
<message>'.$msg.'</message>
<recipient>
<msisdn>'.$cellno.'</msisdn>
<customData><serial>'.$serial.'</serial><reference>'.$reference.'</reference><page>'.$uri.'</page><logip>'.$myip.'/'.$cookieip.'</logip></customData>
</recipient>
</recipientList>
</gviSmsMessage>';

$gviserver="www.gvi.bms27.vine.co.za"; // 27

//echo "Operational<Br>";


}

//echo $xml."\n\n";
//echo "<pre>\n".str_replace("<","&lt;",$xml)."</pre>";
$xml=str_replace("\r","",$xml);

$xml=str_replace("\n","",$xml);

$xml=urlencode($xml);

$time1=time();
 
    $curlout=`/usr/local/bin/curl  -m 12 -i -v -d 'XML=$xml'   http://$gviserver/httpInputhandler/ApplinkUpload  2> /dev/null`; 

//echo "/usr/local/bin/curl  -m 12 -i -v -d 'XML=$xml'   http://$gviserver/httpInputhandler/ApplinkUpload";

	//echo  $curlout;

///Log time taken

$time2=time();
$takentime=$time2-$time1;

if ($takentime>10) {
	$timestamp=date("Y/m/d H:i:s");
	$outfile=fopen("/tmp/grapetest","a+");
	fputs($outfile,"$timestamp $cellno = $takentime s\n");
	fclose($outfile);
}



$bits=explode("resultCode",$curlout);
//print_r($bits);
if ($bits[1]==">0</") {
//	echo "\nSUCCESS!\n";
	
	if (!isset($smsdb_already_done))
	{
		
	        $myDB="sms";
       		$myServer="127.0.0.1:3306";
	        $myUser="root";
        	$myPass="fast#SQL!";

	      try {
			if (isset($argv) && isset($argv[1]) && $argv[1]=="test")
		                echo "A";

       		  $mysql_conn = new PDO  ("mysql:host=$myServer;dbname=$myDB",$myUser,$myPass);
	          } catch ( PDOException $e){
       			    echo "Error connecting to Mysql on $myServer<bR>";
		            echo $e->getMessage();
				$mysql_conn=false;
        	}
		if (isset($argv) && isset($argv[1]) && $argv[1]=="test")
                                echo "A";


	        //$mysql_conn=mysql_connect("127.0.0.1:3306","updown","tellme!");
	}
	
        if ($mysql_conn) {
		$smsdb_already_done=true;
        	$uomsg=str_replace("'","",substr($omsg,0,160));
	        $qry= "INSERT INTO sms.sms_sent (date_time, number, message, owner) VALUES(NOW(),'$number','$uomsg', 25)";
		$result=$mysql_conn->prepare($qry);
			
       		if (!$result->execute()) {
			print_r($result->errorInfo());
			echo $qry."<br><b>SMS Sent, but not logged</b><Br>";

		}
	}
	if (isset($conn)) {
		if (!isset($smscursor))
			$smscursor=ora_open($conn);

		if ($cs=="")
			$cs="null";
		else
			$cs="'$cs'";
		$nowtime=date("YmdHis",$now);
		$qry="insert into sms_sent (serial, date_time, number_to, message, coach_serial, replyto, sent_by, reference) values ($serial, to_Date('$nowtime','YYYYMMDDHH24MISS'), '$number','$omsg',$cs,'$replyto','$whoami','$oreference')";
		if (!ora_parse($smscursor,$qry))
			echo "<b>SMS Sent, but not logged.</b> Err: $qry<bR>";
		elseif (!ora_exec($smscursor))
			echo "<b>SMS Sent, but not logged.</b> Err: $qry<bR>";
	
	}

	return true;

}

if ($takentime>10)
	$grapevinetimeout=true; // dont bother calling it again

 $timestamp=date("Y/m/d H:i:s");
 $outfile=fopen("/tmp/grapetest","a+");
 fputs($outfile,"$timestamp $cellno = $takentime s FAILED: $bits[1]\n");
 fclose($outfile);


return false;

} // function send_grapevine_sms;



?>
