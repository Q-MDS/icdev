<?php
require_once ("../php3/oracle.inc");
require_once ("../php3/logs.inc");
require_once ("../php3/misc.inc");
require_once ("../php3/sec.inc");

//echo "Test ".ENT_XML1."\n";
//iexit;

if (!open_oracle()) { Exit; };

include("Sms.php");

$sms=new CI_Sms;

$options["ReplyTo"]="K";
$options["Reference"]="KW Test";

$sendto[0]="0846575577";
//$sendto[1]="0740612462";

$result=$sms->send_sms($sendto, "Good News! Students get 30kg free luggage + 50% off extra luggage costs on all Intercape departures Dec-Feb (Make sure you specify student discount when booking)", $options );
//this is a test from keith >test< <test> thanks.

//$result=$sms->send_sms("0846575577","testing 123",$options);


var_dump($result);

?>
