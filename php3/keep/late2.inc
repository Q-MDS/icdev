<?

if (!isset($latecount))
	$latecount=0;
if (!isset($putmsg))
	$putmsg = 0;


function addsms($cellno,$ticket)
{
global $sms_tosend,$sms_queued;

// TEMP:
//$cellno="0846575577";

if (!isset($sms_queued[$cellno][$ticket])):
	$sms_queued[$cellno][$ticket]=1;
	$sms_tosend[$cellno][]=substr($ticket,6,160);
endif;

} // function addsms



//$smsqueue=array();


                function try_sms($tel1,$tn) {

                   global $debug; //,$smsqueue;

                   switch (substr($tel1,0,2)) {
			case "+2":
                        case "08":
                        case "07":
			case "06":
                        case "00":
                                if (substr($tel1,0,3)!="087" && substr($tel1,0,3)!="086" && substr($tel1,0,3)!="080") {
                                   //     $smsqueue[$tel1].=$tn." ";
                                        if ($debug) {echo "Sms $tel1 $tn<br>"; }
                                        addsms($tel1,$tn);
                                        return true;
                                        break;
                                }
                    } // switch
                    return false;
                } // try_sms


function latebooking($tn,$cs,$rs,$late_title="New Booking")
{
global $conn,$sms_tosend,$is_ctk, $debug, $ts, $ticketserial;

// NB: inputs are arrays (multiple tickets)

/*
if ($late_title=="New Booking" && substr($tn,6,1)!="4") {
	return true; // we dont send these any more
}
*/
$putmsg = 0;

if ($ticketserial=="")
	$smsref=$ts;
else
	$smsref=$ticketserial;

if ($debug) {
echo "<br><b>Run with</b> TN:";
print_r($tn);
echo "<br>CS:";
print_r($cs);
echo "<Br>";
}

//$debug=true;

// used to be  ONLY for Call Centre bookings...  that chagned 20 June 2013

//echo "Running latebooking..<br>";

$username=getenv("REMOTE_USER");

/*
if (!$is_ctk)
  {

    if (!strstr($late_title,"Cancel")) {
      $ip=getenv("REMOTE_ADDR");
      $bits=explode(".",$ip);
      if (!($bits[2]==10 || $bits[2]==11 || $bits[2]==210 || $bits[2]==236 || $bits[2]==36)) {

	  $cursor=ora_open($conn);
	  ora_parse($cursor,"select staff_member from user_details where username='$username' and is_current='Y'");
	  ora_exec($cursor);
	  if (ora_fetch($cursor))
		$staffmember=getdata($cursor,0);
	  ora_close($conn);
	
//	echo "IGNORING due to IP $ip ($bits[2])";
	if ($staffmember=="Y")
		return false;
      }
//	else echo "IP ok<br>";
    }
//	else echo "cancel...<br>";
   
}
//else echo "CTK<br>";
*/
	

$cursor=ora_open($conn);


if (getenv("REMOTE_USER")=="Keeith" /*|| $is_ctk*/):
	echo "RUNNING LATEBOOK:";
	$debug=1;
endif;

$x=0;
$day1=date("Ymd");
$day2=date("Ymd",time()-86400);

if ($debug)
	echo "DEBUG: $debug/$day1/$day2<Br>";
$hi=date("Hi",time()+3600); // 60 minutes

//$longtoday=date("Ymd");

$kcur=ora_open($conn);
while (isset($tn[$x])):
    $islate=false;
    $tnshort=substr($tn[$x],0,15);
   ora_parse($kcur,"select run_date from open_coach  where coach_serial='$cs[$x]'");
	

   ora_exec($kcur);
   if (!ora_fetch($kcur))
  {
	echo "ERROR GETTING DATE!!<br>";
	$tdate=$day2;
   }
  else
   $tdate=getdata($kcur,0);
if ($debug)
   echo "Comparing $tdate ($tn[$x]) with $day1 or $day2<Br>";
   $islate=false;
   if (isset($late[$cs[$x]]))
	{
	$islate=$late[$cs[$x]];
		if ($debug)
		{
		if ($islate)
			echo "set ISLATE(1)=true<bR>";
		else
			echo "set ISLATE(1)=false<Br>";
		}
	}
   elseif ($tdate==$day2) {
	$islate=true;
	$late[$cs[$x]]=true;
	if ($debug) {
		if ($islate)
                        echo "set ISLATE(2)=true<bR>";
                else
                        echo "set ISLATE(2)=false<Br>";

	}
   } elseif ($tdate==$day1) {
	if ($debug)
	        echo "I seem to think that $tdate = $day1<Br>";
	ora_parse($cursor,"select depart_time from route_stops A where A.route_serial=$rs[$x] order by stop_order");
	ora_exec($cursor);
	ora_fetch($cursor);
	$stoptime=getdata($cursor,0);
	if ($stoptime<$hi) {
		if ($debug)
			echo "LATE: Bus departed $stoptime, which is before $hi<bR>";
		$islate=true;
	        $late[$cs[$x]]=true;
		if ($debug)
			echo "set ISLATE(3)=true - $tdate==$day1 and $stoptime<$hi<br>";
	} else 
	{
		if ($debug)
			echo "First bus departs $stoptime, which is not before $hi<br>";
	}

   } else $late[$cs[$x]]=false;


   if ($islate) {
	if ($debug)
		echo "... LATE!! ";

	$longtoday=$tdate;
	if ($debug) {echo "Ticket $tn[$x] ($longtoday)<br>"; }
	$infoo="";
	$route=substr($tn[$x],6,4);
	if ($debug) {echo "route is $route<br>"; }
	ora_parse($cursor,"select short_name from ops_routes where first_route_no='$route' or second_route_no='$route'");
	ora_exec($cursor);
	while (ora_fetch($cursor)):
		$rtname=getdata($cursor,0);
		if ($debug) {echo "rtname is $rtname<br>"; }
		$infoo.=" or A.info='$rtname'";
		ora_parse($kcur,"select short_name from ops_routes where description like '%*$rtname%'");
		ora_exec($kcur);
		while (ora_fetch($kcur)):
			$rtname=getdata($kcur,0);
			$infoo.=" or A.info='$rtname'";
		endwhile;	
	endwhile;	
	if (strlen($infoo)>3) {$infoo=substr($infoo,3,strlen($infoo)-3); }
//	$qry="select B.tel_1,B.tel_2,B.name,B.surname from ops_info A,attendants B where trim(A.serial_no)=trim(B.attendant_serial) and A.entry_type='A' and A.table=1";

	if ($infoo==""):
		if ($debug) {echo "No ops routes found for $tn[$x]<br>"; }
		exit;
	endif;

	ora_parse($cursor,"select tel_1,tel_2,name,surname,tel_3,cellno_3,cellno_4,cellno_5,cellno_6,cellno_7,cellno_8 from ops_info A,attendants B where A.serial_no=trim(B.attendant_serial) and A.entry_type='a' and rundate=$longtoday and A.table_no=0 and ($infoo)");
	ora_exec($cursor);

	if ($debug)
		echo "select tel_1,tel_2,name,surname,tel_3,cellno_3,cellno_4,cellno_5,cellno_6,cellno_7,cellno_8 from ops_info A,attendants B where A.serial_no=trim(B.attendant_serial) and A.entry_type='a' and rundate=$longtoday and A.table_no=0 and ($infoo)<br>";


	$attfound=0;
	while (ora_fetch($cursor)):
		$tel1=getdata($cursor,0);
		$tel2=getdata($cursor,1);
                $tel3=getdata($cursor,4);
                $tel4=getdata($cursor,5);
                $tel5=getdata($cursor,6);
                $tel6=getdata($cursor,7);
                $tel7=getdata($cursor,8);
                $tel8=getdata($cursor,9);
                $tel9=getdata($cursor,10);


		$tel1=str_replace(" ","",$tel1);
		$tel2=str_replace(" ","",$tel2);
                $tel3=str_replace(" ","",$tel3);
                $tel4=str_replace(" ","",$tel4);
                $tel5=str_replace(" ","",$tel5);
                $tel6=str_replace(" ","",$tel6);
                $tel7=str_replace(" ","",$tel7);
                $tel8=str_replace(" ","",$tel8);
                $tel9=str_replace(" ","",$tel9);

		if ($debug) {echo "Got $tel1 $tel2<br>"; }

		if (try_sms($tel1,$tn[$x]))
			$attfound++;

                if (try_sms($tel2,$tn[$x]))
                        $attfound++;

                if (try_sms($tel3,$tn[$x]))
                        $attfound++;

                if (try_sms($tel4,$tn[$x]))
                        $attfound++;

                if (try_sms($tel5,$tn[$x]))
                        $attfound++;

                if (try_sms($tel6,$tn[$x]))
                        $attfound++;

                if (try_sms($tel7,$tn[$x]))
                        $attfound++;

                if (try_sms($tel8,$tn[$x]))
                        $attfound++;

                if (try_sms($tel9,$tn[$x]))
                        $attfound++;
	endwhile;

	if ($attfound==0):
		if ($debug) {echo "No attendants cell phones found - SMSing the operator..."; }
	        ora_parse($cursor,"select cellno,home_phone,firstname,surname,cellno_2,cellno_3,cellno_4,cellno_5,cellno_6,cellno_7,cellno_8 from ops_info A,operators B where A.serial_no=B.operator_serial and A.entry_type='o' and rundate=$longtoday and A.table_no=0 and ($infoo)");
        	ora_exec($cursor);

		if ($debug)
			echo "select cellno,home_phone,firstname,surname,cellno_2,cellno_3,cellno_4,cellno_5,cellno_6,cellno_7,cellno_8 from ops_info A,operators B where A.serial_no=B.operator_serial and A.entry_type='o' and rundate=$longtoday and A.table_no=0 and ($infoo)<br>";
       	   while (ora_fetch($cursor)):
                $tel1=getdata($cursor,0);
                $tel2=getdata($cursor,1);
                $tel3=getdata($cursor,4);
                $tel4=getdata($cursor,5);
                $tel5=getdata($cursor,6);
                $tel6=getdata($cursor,7);
                $tel7=getdata($cursor,8);
                $tel8=getdata($cursor,9);
                $tel9=getdata($cursor,10);

                $tel1=str_replace(" ","",$tel1);
                $tel2=str_replace(" ","",$tel2);
                $tel3=str_replace(" ","",$tel3);
                $tel4=str_replace(" ","",$tel4);
                $tel5=str_replace(" ","",$tel5);
                $tel6=str_replace(" ","",$tel6);
                $tel7=str_replace(" ","",$tel7);
                $tel8=str_replace(" ","",$tel8);
                $tel9=str_replace(" ","",$tel9);

                if ($debug) {echo "Got $tel1 $tel2<br>"; }

                if (try_sms($tel1,$tn[$x]))
                        $attfound++;

                if (try_sms($tel2,$tn[$x]))
                        $attfound++;

                if (try_sms($tel3,$tn[$x]))
                        $attfound++;

                if (try_sms($tel4,$tn[$x]))
                        $attfound++;

                if (try_sms($tel5,$tn[$x]))
                        $attfound++;

                if (try_sms($tel6,$tn[$x]))
                        $attfound++;

                if (try_sms($tel7,$tn[$x]))
                        $attfound++;

                if (try_sms($tel8,$tn[$x]))
                        $attfound++;

                if (try_sms($tel9,$tn[$x]))
                        $attfound++;
            endwhile;



		if ($attfound==0):
			if ($debug) { echo "<font color=red>Could not even get a cell no for the operator!  Giving up!</font><br>"; }
		endif;


	endif; //==0

	$putmsg++;

	} // found

   $x++;
endwhile;


$sms_sent=0;

if (is_array($sms_tosend)){
  reset($sms_tosend);
     while (list ($key, $val) = each ($sms_tosend)) {
	while (list($key2,$val2)=each($val)) {
	        $sms_sent++;
		$val2="$late_title: $val2";	
                $val2=substr($val2,0,160);
		$thistime=date("Y/m/d H:i");
		$key=str_replace('"',"",$key);
                $key=str_replace("`","",$key);
		$val2=str_replace('"',"",$val2);
                $val2=str_replace("`","",$val2);

                if ($debug) {echo "SEND: $key: $val2<br>"; }
                system ("echo \"$thistime,$key,$val2\" >> /usr/local/www/pages/latesms.log");
		send_sms_msg ($key, $val2,"","",$smsref);
		//  send_sms_msg ($number, $msg, $cs="", $replyto="", $reference="")
                //sleep(2);
	}

        }
	$sms_tosend=array();

}
if ($putmsg>0) {
	if ($debug==1) {echo "Sent $sms_sent SMS messages to attendants informing of late booking<br>"; }
}


ora_close($cursor);

} // function latebooking()

?>
