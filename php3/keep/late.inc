<?
function addsms($cellno,$ticket)
{
global $sms_tosend,$sms_queued;

if (!isset($sms_queued[$cellno][$ticket])):

	$sms_queued[$cellno][$ticket]=1;
	$sms_tosend[$cellno].=substr($ticket,10,strlen($ticket)).". ";

endif;



}


function latebooking($tn,$cs)
{
global $conn,$sms_tosend;


$cursor=ora_open($conn);


$debug=0;
$x=0;
$day1=date("dmy");
$day2=date("dmy",time()-86400);

//$longtoday=date("Ymd");
$kcur=ora_open($conn);
while (isset($tn[$x])):
   $tdate=substr($tn[$x],0,6);
   if (($tdate==$day1)||($tdate==$day2)):
	ora_parse($cursor,"select depart_time,stop_order from route_stops A,open_coach B where B.coach_serial='$cs[$x]' and A.route_serial=B.route_serial order by stop_order");
	ora_exec($cursor);
	if (ora_fetch($cursor)):
		$starttime=getdata($cursor,0);
	else: // could not get stop time
		echo "Unable start time for route on $tn[$x]<br>";
		$starttime=time()+86400;
	endif;

	$starttime=$starttime-5400; // manifest printed 90 minutes before departure
	if (time()>$starttime):

	$longtoday="20".substr($tn[$x],4,2).substr($tn[$x],2,2).substr($tn[$x],0,2);
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
	if (strlen(info)>3) {$infoo=substr($infoo,3,strlen($infoo)-3); }
//	$qry="select B.tel_1,B.tel_2,B.name,B.surname from ops_info A,attendants B where trim(A.serial_no)=trim(B.attendant_serial) and A.entry_type='A' and A.table=1";

	if ($infoo==""):
		echo "No ops routes found for $tn[$x]<br>";
		exit;
	endif;

	ora_parse($cursor,"select tel_1,tel_2,name,surname from ops_info A,attendants B where A.serial_no=trim(B.attendant_serial) and A.entry_type='a' and rundate=$longtoday and A.table_no=0 and ($infoo)");
	ora_exec($cursor);


	$attfound=0;
	while (ora_fetch($cursor)):
		$tel1=getdata($cursor,0);
		$tel2=getdata($cursor,1);
		$tel1=str_replace(" ","",$tel1);
		$tel2=str_replace(" ","",$tel2);
		if ($debug) {echo "Got $tel1 $tel2<br>"; }
		switch (substr($tel1,0,3)) {
			case "082":
			case "072":
			case "073":
			case "083":
			case "084":
					$sms[$tel1].=$tn[$x]." ";
					if ($debug) {echo "Sms $tel1 $tn[$x]<br>"; }
					addsms($tel1,$tn[$x]);
					$attfound++;
					break;
			case "081":
					echo "Namibian number not currently supported<br>";
					break;
		} // switch

                switch (substr($tel2,0,3)) {
                        case "082":
                        case "072":
                        case "073":
                        case "083":
			case "084":
				        $sms[$tel2].=$tn[$x]." ";
                                        if ($debug) {echo "Sms $tel2 $tn[$x]<br>"; }
					addsms($tel2,$tn[$x]);
                                        $attfound++;
                                        break;
                        case "081":
                                        echo "Namibian number not currently supported<br>";
                                        break;
                } // switch
	endwhile;
	if ($attfound==0):
		echo "No attendants cell phones found - SMSing the operator...";
	        ora_parse($cursor,"select cellno,home_phone,surname from ops_info A,operators B where A.serial_no=B.operator_serial and A.entry_type='o' and rundate=$longtoday and A.table_no=0 and ($infoo)");
        	ora_exec($cursor);


        	while (ora_fetch($cursor)):
               	 $tel1=getdata($cursor,0);
               	 $tel2=getdata($cursor,1);
               	 $tel1=str_replace(" ","",$tel1);
               	 $tel2=str_replace(" ","",$tel2);
               	 if ($debug) {echo "Got $tel1 $tel2<br>"; }
               	 switch (substr($tel1,0,3)) {
                        case "082":
                        case "072":
                        case "073":
                        case "083":
			case "084":
                                        $sms[$tel1].=$tn[$x]." ";
                                        if ($debug) {echo "Sms $tel1 $tn[$x]<br>"; }
					addsms($tel1,$tn[$x]);
                                        $attfound++;
                                        break;
                        case "081":
                                        echo "Namibian number not currently supported<br>";
                                        break;
               	 } // switch

               	 switch (substr($tel2,0,3)) {
                        case "082":
                        case "072":
                        case "073":
                        case "083":
			case "084":
                                        $sms[$tel2].=$tn[$x]." ";
                                        if ($debug) {echo "Sms $tel2 $tn[$x]<br>"; }
					addsms($tel2,$tn[$x]);
                                        $attfound++;
                                        break;
                        case "081":
                                        echo "Namibian number not currently supported<br>";
                                        break;
               	 } // switch
        	endwhile;	

		if ($attfound==0):
			echo "Could not even get a cell no for the operator!  Giving up!<br>";
		endif;


	endif; //==0

	$putmsg++;

	else:
	if ($debug==1) {		echo "time OK - not late<br>"; }
	
	endif; // time

   else:
	if ($debug==1) {		echo "date OK - not late<br>"; }

   endif; // tdate

   $x++;
endwhile;


$sms_sent=0;

if (is_array($sms_tosend)):
reset($sms_tosend);
     while (list ($key, $val) = each ($sms_tosend)) {
        $sms_sent++;
        $val="New Booking(s): ".$val;
        if ($debug) {echo "Len: ".strlen($val)."<br>"; }
        while (strlen($val)>0):
                $val2=substr($val,0,123);
                if (strlen($val)>123):
                        $val2.=">>>";
                endif;
                if ($debug==1) {echo "SEND: $key: $val2<br>"; }
                mail ("keith@intercape.co.za","$key","$val2");
                //sleep(2);
                $val=substr($val,123,strlen($val));
        endwhile;



        }

endif;
if ($putmsg>0):
if ($debug==1) {echo "Sent $sms_sent SMS messages to attendants informing of late booking<br>"; }
endif;


ora_close($cursor);

}

/*
$tn[0]="2609019999XG174 Keith van der Waters";
$tn[1]="2609019999WQ326 Ivor de la Kettles";
$tn[2]="2609019999XG175 Angela van der Waters";
$tn[3]="2609019999WQ327 Madeleine de la Kettles";


latebooking($tn);
*/
?>
