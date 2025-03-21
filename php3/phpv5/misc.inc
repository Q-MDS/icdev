<?
/* ************************************************************************************** */
/* ************ A lot of commonly used functions found in here, important, READ ********* */
/* ************************************************************************************** */

 $my_curr_name="";

$_misc_included=true;


// setup graph paths:
$graphpaths[1]="../graph350/";
$graphpaths[2]="../graph/";
$graphpaths[3]="../booking/leave/jpgraph/";
$graphpaths[4]="../booking/stats/jpgraph/";
$graphpaths[5]="../booking/stats/jpgraph/src/";
$graphpaths[6]="../graph4/";
$graphpaths[7]="../jpgraph/";
$graphpaths[8]="../jpgraph/pkg/stripped-src/";
$graphpaths[9]="../jpgraph/pkg/src/";

function safe_arg ( $arg) {

        $arg=str_replace("|","",$arg);
        $arg=str_replace("`","",$arg);
        $arg=str_replace(">","",$arg);
        $arg=str_replace("<","",$arg);

        return $arg;
}

function mask_cc_number ($ccno) {

	//522118******9615  

	$ccno=trim($ccno);
	$len=strlen($ccno);
	$new=substr($ccno,0,6);
	for ($a=7;$a<=$len-4;$a++)
		$new.="*";
	$new=$new.substr($ccno,-4);
	return $new;

} // mask_cc_number

function makeselect2($data,$value="___UNSET___1234")
// create select options. data =  [0]=Home,[1]=Work and value is pre-selected value, if any.  Returns a string (rather than echo itself) to either echo or add to an output string
{
if (!is_array($data))
        return "";
while (list($key,$val)=each($data)):
        $str.="<option";
        $str.=" value='$key'";
        if ("$key"=="$value")
                $str.= " SELECTED";
        $str.=">$val</option>\n";
endwhile;
return $str;
} // function makeselet2

function myaddress($recipient="",$sender="") {
        global $conn;

        $mcur=ora_open($conn);
	if ($sender=="")
	        $username=getenv("REMOTE_USER");
	else
		$username=$sender;
	if (is_numeric($sender)) 
		ora_parse($mcur,"select email,username from user_Details where user_serial='$username'" );
	else
	        ora_parse($mcur,"select email from user_Details where username='$username' and is_current='Y'");
        ora_exec($mcur);
        ora_fetch($mcur);
        $email=strtolower(trim(ora_getcolumn($mcur,0)));
	if (is_numeric($username))
		$username=trim(ora_getcolumn($mcur,1));
        if (!strstr($email,"@"))
                $email=strtolower($username)."@cavmail.co.za";
        if ($recipient!="") {
		if (!strstr($recipient,"@")) {
			ora_parse($mcur,"select email from user_Details where username='$recipient' and is_current='Y'");
		        ora_exec($mcur);
		        ora_fetch($mcur);
		        $recipient=strtolower(trim(ora_getcolumn($mcur,0)));
		        if (!strstr($recipient,"@"))
		                $recipient=strtolower($recipient)."@cavmail.co.za";
		}
		else {

                  $recipient=strtolower($recipient);
                  if (!strstr($recipient,"@intercape.co.za") && !strstr($recipient,"@cavmail.co.za") && $recipient!=$sender && $recipient!="") {
                        // outside mail - make the change
                        $email=str_replace("@cavmail.co.za","@intercape.co.za",$email);

                  }
		}
        }

        ora_close($mcur);
        return $email;
}


function send_sms_msg ($number, $msg, $cs="", $replyto="", $reference="") {

	require_once("grapevine.inc");

	$reference=substr($reference,0,15);

	$number=str_replace(" ","",$number);
	$number=str_replace("-","",$number);


	 if (substr($number,0,2)=="00")
                $number="+".substr($number,2,999);
	   elseif ($number[0]=="0")
        {
                        $number="+27".substr($number,1,99);
        }

	$testnum=substr($number,1,3);
	if ($testnum>=270 && $testnum<=275)
		return false;
	
	$testnum=substr($number,1,999);
	if (!is_numeric($testnum))
	{
		return false;
	}
	

// function send_grapevine_sms ( $cellno,$msg, $cs="", $replyto="", $reference="")
	if (send_grapevine_sms ($number,$msg,$cs,$replyto,$reference))
		return true;

	/// otherwise, try the old way...

	

        $mysql_conn=mysql_connect("192.168.10.4:3306","updown","tellme!");
        if (!$mysql_conn) {
                echo "SMS DB Connect failed<br>\n";
		mail("sms@intercape.co.za","$number",$msg);
		mail("keith@intercape.co.za","Failed connect  send_sms_msg","This has been sent to sms@intercape.co.za:\n$msg\n".mysql_error());
		return true;
        }

       $msg=mysql_escape_string(substr($msg,0,160));
//       echo strlen($qry);

        $qry= "INSERT INTO sms.sms_inbox (date_time, number, message, priority) VALUES(NOW(),'$number','$msg', 10)";
	
       // echo "$qry\n";
        if (mysql_query( $qry,$mysql_conn))
        {
                return true;
        } else {
		$myerrors=mysql_error();
		mysql_close();

                echo "SMS DB Query failed<br>\n";
		mail("sms@intercape.co.za","$number",$msg);
		mail("keith@intercape.co.za","$number failed send_sms_msg","This has been sent to sms@intercape.co.za:\n$msg\n$myerrors");
        }

	mysql_close();
	return true;

} // send_sms




function makeselect($data,$value="___UNSET___1234")
// create select options. data =  1|Home,2|Work or Home|Work and value is pre-selected value, if any.  Returns a string (rather than echo itself) to either echo or add to an output string
{
while (list($key,$val2)=each($data)):
        $val=explode("|",$val2);
        $str.="<option";
        if (isset($val[1]))
                $str.=" value='$val[0]'";
        else
                $val[1]=$val[0];
        if ($val[0]==$value)
                $str.= " SELECTED";
        $str.=">$val[1]</option>\n";


endwhile;
return $str;

}

function chop5($value)
{
$value=sprintf("%.2f",round($value*100)/100);
return sprintf("%.2f",$value-(((100*$value)%100)/100));
}

function roundup5($value)
{
//echo "Start: $value, ";
$value=sprintf("%.2f",$value);
//echo "Then $value, ";
if ($value==round($value))
{
//	echo "Ret $value<bR>";
        return $value;
}
else {
//	echo "Ret ". sprintf("%.2f",ceil($value));
	 return sprintf("%.2f",ceil($value) );
}
} // roundup5


function chopcents($value)
{
        global $norounding;

        if (isset($norounding))
                if ($norounding)
                        return sprintf("%.2f",$value);


    $value=sprintf("%.2f",round($value*100)/100);


	return $value;
	//***@settype($value, "integer");
	//***return round($value);
}

function getdatax($tcur,$tcol,$show=false)
{
	if ($show):
		echo "DB: $tcol<br>";
	endif;
	$retval = ora_getColumn($tcur,$tcol);
	if ($show):
		echo "DB:", ora_error($tcur)," with $retval($tcol)<br>";
	endif;
	return chop($retval);
}

/*
function getdata($tcur,$tcol,$details=false)
{
	$details=false;
	if ($details):
		return getdatax($tcur,$tcol,$details);
	else:
		return chop(ora_getColumn($tcur,$tcol));
	endif;
}
*/

function OLD_availseats($coachserial, $gofrom, $goto, $debug=true)
{
	global $conn;
	$ascur = ora_open($conn);
	if (!ora_parse($ascur,"select max_seats, computicket, route_serial from open_coach where coach_serial='$coachserial'")):
		echo "$coachserial - $gofrom - $goto<Br>";
		exit;
	endif;
	ora_exec($ascur);
	ora_fetch($ascur);
	$maxseats = ora_getcolumn($ascur,0); 
	$compu = ora_getcolumn($ascur,1);
	$rs = ora_getcolumn($ascur,2);
	$avail = $maxseats - $compu;

	if (!ora_parse($ascur,"select stop_order from route_stops where short_name='$goto' and route_serial='$rs'")):
		echo "$coachserial - $gofrom - $goto<br>";
		exit;
	endif;
	ora_exec($ascur);
	ora_fetch($ascur);
	$endno = ora_getcolumn($ascur,0);

	if (!ora_parse($ascur,"select stop_order from route_stops where short_name='$gofrom' and route_serial='$rs'")):
		echo "$coachserial - $gofrom - $goto<br>";
		exit;
	endif;
	ora_exec($ascur);
	ora_fetch($ascur);
	$startno = ora_getcolumn($ascur,0);

	if (!ora_parse($ascur,"select sum(numtickets) from tickets where coach_serial = '$coachserial' and go_from in (select short_name from route_stops where stop_order<$endno and route_serial='$rs')")):
		echo "$coachserial - $gofrom - $goto<br>";
		exit;
	endif;

	ora_exec($ascur);
	ora_fetch($ascur);
	$geton = ora_getcolumn($ascur,0);

	if (!ora_parse($ascur,"select sum(numtickets) from on_hold_tickets where coach_serial = '$coachserial' and go_from in (select short_name from route_stops where stop_order<$endno and route_serial='$rs')")):
		echo "$coachserial - $gofrom - $goto<br>";
		exit;
	endif;
	ora_exec($ascur);
	ora_fetch($ascur);
	$geton += ora_getcolumn($ascur,0);

	if (!ora_parse($ascur,"select sum(numtickets) from tickets where coach_serial = '$coachserial' and go_to in (select short_name from route_stops where stop_order<=$startno and route_serial='$rs')")):
		echo "$coachserial - $gofrom - $goto<br>";
		exit;
	endif;
	ora_exec($ascur);
	ora_fetch($ascur);
	$getoff = ora_getcolumn($ascur,0);

	if (!ora_parse($ascur,"select sum(numtickets) from on_hold_tickets where coach_serial = '$coachserial' and go_to in (select short_name from route_stops where stop_order<=$startno and route_serial='$rs')")):
		echo "$coachserial - $gofrom - $goto<br>";
		exit;
	endif;
	ora_exec($ascur);
	ora_fetch($ascur);
	$getoff += ora_getcolumn($ascur,0);

	$avail = $avail - $geton + $getoff;
	ora_close($ascur);

	if ($avail<0):
		$avail=0;
	endif;

	return $avail;
}

function getuserserial($getcuruser=true)
{
	global $conn,$is_staff_flag,$my_staff_no, $my_curr_name, $my_master_user, $my_user_name, $my_primary_user, $mystafftype;
	$gus = ora_open($conn);
	$tuser = getenv("REMOTE_USER");
	ora_parse($gus,"select user_serial,staff_member,staff_no,master_user,current_user,m_currency,username,primary_user,stafftype from user_details where username='$tuser' and is_current='Y'");
	ora_exec($gus);
	if (!ora_fetch($gus)) {
		echo "Current user $tuser not found / account locked out.<hr>TO UNLOCK AND CHANGE YOUR PASSWORD, SEND THE WORD <b>PASSWORD</b> TO +27825635497 / 082 563 5497.<hr> Alternatively Please contact HR (If you are a staff member) or Marketing (if you are an agent) on +27 21 3804444 / (021) 3804444<br>";
		exit;
	}
	$retval = chop(ora_getcolumn($gus,0));
	$mystafftype=ora_getcolumn($gus,8);
	$my_primary_user=chop(ora_getcolumn($gus,7));
	if ($my_primary_user==-1 || $my_primary_user=="")
		$my_primary_user=$retval;
	$curuser=ora_getcolumn($gus,4);
	if (is_numeric($curuser) && $getcuruser)
		if ($curuser!=$retval) {
			ora_parse($gus,"select user_serial,staff_member,staff_no,master_user,current_user,m_currency,username from user_details where user_serial=$curuser ");
		        ora_exec($gus);
		        ora_fetch($gus);
			$retval = $curuser;
		}
	$is_staff_flag=chop(ora_getcolumn($gus,1));
	$my_staff_no=chop(ora_getcolumn($gus,2));
	if ($tuser=="dummy" && $_GET['xmlcurrency']!="")
		$my_curr_name=str_replace("'","",$_GET['xmlcurrency']);
	else
		$my_curr_name=chop(ora_getcolumn($gus,5));
	$my_user_name=chop(ora_getcolumn($gus,6));
	$my_master_user=chop(ora_getcolumn($gus,3));
	ora_close($gus);
	return $retval;
}

function getusername($user_serial)
{
        global $conn;
        $gus = ora_open($conn);
        
        ora_parse($gus,"select username from user_details where user_serial='$user_serial'");
        ora_exec($gus);
        ora_fetch($gus);
        $retval = chop(ora_getcolumn($gus,0));
        ora_close($gus);
        return $retval;
}                                     

function jstatus($msg)
{
	echo " onmouseover='javascript:status=\"$msg\"'";
	echo " onmouseout ='javascript:status=\"\"' ";
}

function show_error($msg)
{
	global $text_nb;
	echo "<font color='#$text_nb'>$msg<br></font>";
	return false;
}

function echodb($debug,$message)
{
	if ($debug)
	{
		echo "DEBUG: $message<br>";
	}
}

Function string_date ($ndate)
{
	$y = Date("Y",$ndate);
	$m = Date("m",$ndate);
	$d = Date("d",$ndate);

        if (strlen($m)<2):
                $numeric = sprintf("%s0%s",$y,$m);
        else:
                $numeric = sprintf("%s%s",$y,$m);
        endif;
        if (strlen($d)<2):
                $numeric = sprintf("%s0%s",$numeric,$d);
        else:
                $numeric = sprintf("%s%s",$numeric,$d);
        endif;
        return $numeric;
};

Function days_in_month( $m, $y)
{
        switch ($m)
        {
                case 2: if ($y%4==0):
                                $dc=29;
                        else:
                                $dc=28;
                        endif;
                        break;
                case 4: $dc=30;
                        break;
                case 6: $dc=30;
                        break;
                case 9: $dc=30;
                        break;
                case 11: $dc=30;
                        break;
                default: $dc=31;
                        break;
        }
        return $dc;
};

Function add_days( $old_date, $num_days)
{
	$hour = Date("H",$old_date);
	$min = Date("i",$old_date);
	$sec = Date("s",$old_date);
	$day = Date("d",$old_date);
	$month = Date("m",$old_date);
	$year = Date("Y",$old_date);

	$newdate = mktime($hour,$min,$sec,$month,$day+$num_days,$year);
	return $newdate;

/*
	SetType($num_days,"integer");
	SetType($day,"integer");
	SetType($month,"integer");
	SetType($year,"integer");

	$day+=$num_days;
	if ($day > days_in_month($month,$year)):
		$day = $day - days_in_month($month,$year);
		$month++;
	endif;
	if ($month>12):
		$month -=12;
		$year++;
	endif;
 	$new_date = MkTime($hour,$min,$sec,$month,$day,$year);
	return $new_date;	
*/
};

Function add_dmy($day, $month, $year, $numdays)
{
	$day+=$numdays;
	if ($day > days_in_month($month,$year)):
		$day = $day - days_in_month($month,$year);
		$month++;
	endif;
	if ($month>12):
		$month -=12;
		$year++;
	endif;
 	$new_date = MkTime(0,0,0,$month,$day,$year);
	$new_date = string_date($new_date);
	return $new_date;	
};

Function sub_days($olddate, $numdays)
{
	$day = date("d", $olddate);
	$month = date("m", $olddate);
	$year = date("Y", $olddate);
	$day-=$numdays;
	if ($day < 1):
		$month--;
		if ($month<1):
			$year--;
			$month=12;
		endif;
		$day = days_in_month($month,$year)+$day;
	endif;
 	$new_date = MkTime(0,0,0,$month,$day,$year);
	return $new_date;	
};

Function sub_dmy($day, $month, $year, $numdays)
{
	$day-=$numdays;
	if ($day < 1):
		$month--;
		if ($month<1):
			$year--;
			$month=12;
		endif;
		$day = days_in_month($month,$year)+$day;
	endif;
 	$new_date = MkTime(0,0,0,$month,$day,$year);
	$new_date = string_date($new_date);
	return $new_date;	
};



function convert_money_history( $from_curr, $to_curr, $amount, $date)
{
	global $conn,$debug, $REMOTE_USER, $no_convert_round, $convert_cursor;

	

	if ($debug)
		echo "DATE GIVEN: $date<bR>";
	$date.="1200";


	if ($REMOTE_USER=="Keeith"):
		echo "CM: $from_curr, $to_curr, $amount<br>";
	endif;

	if ($amount==0):
		return 0;
	endif;

	if (isset($convert_cursor))
		$cmc=$convert_cursor;
	else
		$cmc = Ora_Open($conn);
	
	$qry = "select UZAR from currency_history where name='$from_curr' and start_date<=to_date('$date','YYYYMMDDHH24MI') and end_date>=to_date('$date','YYYYMMDDHH24MI')";
/*	echo "query=($qry)<br>\n";
*/

	ora_parse($cmc,$qry);
	ora_exec($cmc);
	if (!ora_fetch($cmc)) {
		if ($debug)
			echo "Using current rate for $from_curr<br>";
		$qry = "select UZAR from currency_info where name='$from_curr'";
        	ora_parse($cmc,$qry);
	        ora_exec($cmc);
	   if (!ora_fetch($cmc)):
		echo "<h2>Conversion Error 1: invalid currency $from_curr. </h2>($qry)<br>\n";
		echo "<h2>This page will not be correct/complete - please report this error immediately!</h2>\n";
            echo "<pre>CM: $from_curr, $to_curr, $amount</pre><br>";
		$host=getenv("REMOTE_ADDR");
		$msg="Currency conversion error username $REMOTE_USER ($host) query $qry converting from $from_curr to $to_curr amount $amount  page $PHP_SELF\n";
		reset($_GET);
		while(list($key,$val)=each($_GET))
			$msg.="$key=$val\n";
		reset($_POST);
                while(list($key,$val)=each($_POST))
                        $msg.="$key=$val\n";

      mail("keitha@intercape.co.za","Currency error",$msg."\n".getenv("REQUEST_URI").":".$_PHP_SELF."\nArgs: ".implode("&",$_POST)); 
		echo "<hr>A Currency Conversion Error has occurred - transaction stopped<bR>";
		ora_rollback($conn);
		exit;

		return false;
	   endif;
	}
	$UZAR_from = chop(ora_getcolumn($cmc,0));
	if (!isset($convert_cursor))
	{
		ora_close($cmc);
		$cmc = Ora_Open($conn);
	}
	$qry = "select UZAR, roundup from currency_history where name='$to_curr' and start_date<=to_date('$date','YYYYMMDDHH24MI') and end_date>=to_date('$date','YYYYMMDDHH24MI')";
	if ($debug)
		echo "$qry<bR>";
        ora_parse($cmc,$qry);
        ora_exec($cmc);
        if (!ora_fetch($cmc)) {
		if ($to_curr!="S.A. Rand" && $debug)
			echo "Using current rate for $to_curr<Br>";
		$qry = "select UZAR, roundup from currency_info where name='$to_curr'";
		ora_parse($cmc,$qry);
		ora_exec($cmc);
	  if (!ora_fetch($cmc)):
		echo "<h2>Conversion Error 2: invalid currency $to_curr. </h2>($qry)<br>\n";
            echo "CM: $from_curr, $to_curr, $amount<br>";
		$host=getenv("REMOTE_ADDR");
                $msg="Currency conversion error username $REMOTE_USER ($host) query $qry converting from $from_curr to $to_curr amount $amount  page $PHP_SELF\n";
		 reset($_GET);
                while(list($key,$val)=each($_GET))
                        $msg.="$key=$val\n";
                reset($_POST);
                while(list($key,$val)=each($_POST))
                        $msg.="$key=$val\n";

                mail("keitha@intercape.co.za","Currency error",$msg); 
		           echo "<h2>This page will not be correct/complete - please report this error immediately!</h2>\n";
		return false;
	  endif;
	}
	$UZAR_to = chop(ora_getColumn($cmc,0));
	$roundup = chop(ora_getColumn($cmc,1));
	if (!isset($convert_cursor))
		ora_Close($cmc);

	echodb($debug,"converting from $from_curr to $to_curr");
	echodb($debug,"$amount, $UZAR_from, $UZAR_to");
	if ($debug)
		echo "RATE: $UZAR_from $UZAR_to ".sprintf("%.2f",1/$UZAR_from)." ".sprintf("%.2f",1/$UZAR_to)."<Br>";

	$newprice = ($amount / $UZAR_from) * $UZAR_to;
	if (!$no_convert_round)
		$newprice = IntVal($newprice);
	
	if ($roundup>0 && !$no_convert_round) 
  	  while (($newprice%$roundup)!=0):
		$newprice++;
	  endwhile;

	if ($debug)
		echo "DEBUG: Returning $newprice = ".chopcents($newprice)."<br>";
	if (!$no_convert_round)
		return chopcents($newprice);
	else
		return sprintf("%.2f",round($newprice*100)/100);
} // convert_money_history


Function convert_money_sage( $from_curr, $to_curr, $amount, $datetouse="")
{
	global $conn,$debug, $REMOTE_USER, $no_convert_round, $convert_cursor, $convert_infinite;


	if ($debug) echo "Date Sent as $datetouse<BR>";
	
	if ($datetouse=="")
		$datetouse=date("Ymd");	

	if ($REMOTE_USER=="Keith" || getenv("REMOTE_ADDR")=="192.168.10.64"):
		$debug=true;
		if ($no_convert_round)
			echo "NO ROUND";		
		else
			echo "ROUND";
		echo "CM: $from_curr, $to_curr, $amount<br>";
	endif;

	if ($amount==0):
		return 0;
	endif;

	if (isset($convert_cursor))
		$cmc=$convert_cursor;
	else
		$cmc = Ora_Open($conn);
	if ($datetouse==date("Ymd"))
	{
		$uselive=true;
		$qry = "select rate from sage_currency where currency='$from_curr' and start_date<=SYSTIMESTAMP order by start_date desc, uploaded desc";
	}
	else {
		$uselive=false;
		$datetouse.="120000"; // midday
		$qry = "select rate from sage_currency where currency='$from_curr' and start_date<=to_Date('$datetouse','YYYYMMDDHH24MISS')  order by start_date desc, uploaded desc";

	}
/*	echo "query=($qry)<br>\n";
*/

	ora_parse($cmc,$qry);
	ora_exec($cmc);
	if (!ora_fetch($cmc)):
		echo "<h2>Conversion Error 1: invalid currency $from_curr. </h2>\n";
		echo "<h2>This page will not be correct/complete - please report this error immediately!</h2>\n";
            echo "<pre>CM: $from_curr, $to_curr, $amount</pre><br>";
		$host=getenv("REMOTE_ADDR");
		$msg="Currency conversion error username $REMOTE_USER ($host) query $qry converting from $from_curr to $to_curr amount $amount  page $PHP_SELF\n";
		reset($_GET);
		while(list($key,$val)=each($_GET))
			$msg.="$key=$val\n";
		reset($_POST);
                while(list($key,$val)=each($_POST))
                        $msg.="$key=$val\n";

      mail("keith@intercape.co.za","SAGE Currency error",$msg."\n".getenv("REQUEST_URI").":".$_PHP_SELF."\nArgs: ".implode("&",$_POST)); 
		echo "<hr>A Currency Conversion Error has occurred - transaction stopped<bR>";
		ora_rollback($conn);
		exit;

		return false;
	endif;
	$UZAR_from = chop(ora_getcolumn($cmc,0));
	if (!isset($convert_cursor))
	{
		ora_close($cmc);
		$cmc = Ora_Open($conn);
	}
	if ($uselive)
		$qry = "select rate from sage_currency where currency='$to_curr' and start_date<=SYSTIMESTAMP order by start_date desc, uploaded desc";
	else {
		$qry = "select rate from sage_currency where currency='$to_curr' and start_date<=to_Date('$datetouse','YYYYMMDDHH24MISS') order by start_date desc, uploaded desc";
	}
	ora_parse($cmc,$qry);
	ora_exec($cmc);
	if (!ora_fetch($cmc)):
		echo "<h2>Conversion Error 2: invalid currency $to_curr. </h2>\n";
            echo "CM: $from_curr, $to_curr, $amount<br>";
		$host=getenv("REMOTE_ADDR");
                $msg="SAGE Currency conversion error username $REMOTE_USER ($host) query $qry converting from $from_curr to $to_curr amount $amount  page $PHP_SELF\n";
		 reset($_GET);
                while(list($key,$val)=each($_GET))
                        $msg.="$key=$val\n";
                reset($_POST);
                while(list($key,$val)=each($_POST))
                        $msg.="$key=$val\n";

                mail("keith@intercape.co.za","SAGE Currency error",$msg); 
		           echo "<h2>This page will not be correct/complete - please report this error immediately!</h2>\n";
		/*exit;
*/
		return false;
	endif;
	$UZAR_to = chop(ora_getColumn($cmc,0));
	if (!isset($convert_cursor))
		ora_Close($cmc);

	echodb($debug,"converting from $from_curr to $to_curr");
	echodb($debug,"$amount, $UZAR_from, $UZAR_to");

	$newprice = ($amount * $UZAR_from) / $UZAR_to;
	if (!$no_convert_round)
		$newprice = IntVal($newprice);

	if ($debug) echo "Rates Used: $UZAR_from/$UZAR_to<Br>";
	
	if ($debug)
		echo "DEBUG: Returning $newprice = ".chopcents($newprice)."<br>";
	if (!$no_convert_round)
		return chopcents($newprice);
	else {
		if ($convert_infinite)
			return $newprice;
		else
			return sprintf("%.2f",round($newprice*100)/100);
	}
};


Function convert_money( $from_curr, $to_curr, $amount)
{
	global $conn,$debug, $REMOTE_USER, $no_convert_round, $convert_cursor;


	if ($REMOTE_USER=="Keeith"):
		echo "CM: $from_curr, $to_curr, $amount<br>";
	endif;

	if ($amount==0):
		return 0;
	endif;

	if (isset($convert_cursor))
		$cmc=$convert_cursor;
	else
		$cmc = Ora_Open($conn);
	$qry = "select UZAR from currency_info where name='$from_curr'";
/*	echo "query=($qry)<br>\n";
*/

	ora_parse($cmc,$qry);
	ora_exec($cmc);
	if (!ora_fetch($cmc)):
		echo "<h2>Conversion Error 1: invalid currency $from_curr. </h2>\n";
		echo "<h2>This page will not be correct/complete - please report this error immediately!</h2>\n";
            echo "<pre>CM: $from_curr, $to_curr, $amount</pre><br>";
		$host=getenv("REMOTE_ADDR");
		$msg="Currency conversion error username $REMOTE_USER ($host) query $qry converting from $from_curr to $to_curr amount $amount  page $PHP_SELF\n";
		reset($_GET);
		while(list($key,$val)=each($_GET))
			$msg.="$key=$val\n";
		reset($_POST);
                while(list($key,$val)=each($_POST))
                        $msg.="$key=$val\n";

      mail("keitha@intercape.co.za","Currency error",$msg."\n".getenv("REQUEST_URI").":".$_PHP_SELF."\nArgs: ".implode("&",$_POST)); 
		echo "<hr>A Currency Conversion Error has occurred - transaction stopped<bR>";
		ora_rollback($conn);
		exit;

		return false;
	endif;
	$UZAR_from = chop(ora_getcolumn($cmc,0));
	if (!isset($convert_cursor))
	{
		ora_close($cmc);
		$cmc = Ora_Open($conn);
	}
	$qry = "select UZAR, roundup from currency_info where name='$to_curr'";
	ora_parse($cmc,$qry);
	ora_exec($cmc);
	if (!ora_fetch($cmc)):
		echo "<h2>Conversion Error 2: invalid currency $to_curr. </h2>\n";
            echo "CM: $from_curr, $to_curr, $amount<br>";
		$host=getenv("REMOTE_ADDR");
                $msg="Currency conversion error username $REMOTE_USER ($host) query $qry converting from $from_curr to $to_curr amount $amount  page $PHP_SELF\n";
		 reset($_GET);
                while(list($key,$val)=each($_GET))
                        $msg.="$key=$val\n";
                reset($_POST);
                while(list($key,$val)=each($_POST))
                        $msg.="$key=$val\n";

                mail("keitha@intercape.co.za","Currency error",$msg); 
		           echo "<h2>This page will not be correct/complete - please report this error immediately!</h2>\n";
		/*exit;
*/
		return false;
	endif;
	$UZAR_to = chop(ora_getColumn($cmc,0));
	$roundup = chop(ora_getColumn($cmc,1));
	if (!isset($convert_cursor))
		ora_Close($cmc);

	echodb($debug,"converting from $from_curr to $to_curr");
	echodb($debug,"$amount, $UZAR_from, $UZAR_to");

	$newprice = ($amount / $UZAR_from) * $UZAR_to;
	if (!$no_convert_round)
		$newprice = IntVal($newprice);
	
	if ($roundup>0 && !$no_convert_round) 
  	  while (($newprice%$roundup)!=0):
		$newprice++;
	  endwhile;

	if ($debug)
		echo "DEBUG: Returning $newprice = ".chopcents($newprice)."<br>";
	if (!$no_convert_round)
		return chopcents($newprice);
	else
		return sprintf("%.2f",round($newprice*100)/100);
};

function showaqry($qry,$no)
{
	return;
	global $REMOTE_USER;
	if ($REMOTE_USER=="tw"):
		echo "$no: $qry<br><br>";
	endif;
}

function tcshow($msg)
{
	return;
	global $REMOTE_USER;

	if ($REMOTE_USER=="tw"):
		echo "$msg<br>";
	endif;
}

function dontavailseats($coachserial, $gofrom, $goto, $debug=false)
{
	tcshow("From: $gofrom to $goto<br>");
	global $conn;
	$ascur = ora_open($conn);

	$qry = "select A.stop_order, A.route_serial, B.max_seats from route_stops A, open_coach B where A.route_serial = B.route_serial and B.coach_serial='$coachserial' and A.short_name='$goto'";
	tcshow($qry);
	ora_parse($ascur, $qry);
	if (!ora_exec($ascur)):
		echo "$qry<br>";
		exit;
	endif;
	$endat = chop(ora_getColumn($ascur,0));
	$rs = chop(ora_getColumn($ascur,1));
	$max = chop(ora_getColumn($ascur,2));

	$qry = "select A.stop_order from route_stops A, open_coach B where A.route_serial=B.route_serial and B.coach_serial='$coachserial' and A.short_name='$gofrom'";
	tcshow($qry);
	ora_parse($ascur, $qry);
	if (!ora_exec($ascur)):
		echo "$qry<br>";
		exit;
	endif;
	$startat = chop(ora_getColumn($ascur,0));

	tcshow("route serial: $rs");
	tcshow("End Stop No : $endat");
	tcshow("Start Stop No : $startat");
	tcshow("Max Seats Av: $max");

	$total =0;
	$i=0;
	while ($i<$endat):
		$stopno = $i+1;
		$qry = "select sum(numtickets) from tickets where coach_serial='$coachserial' and go_from = (select short_name from route_stops where route_Serial='$rs' and stop_order=$stopno)";
		tcshow($qry);
		ora_parse($ascur,$qry);
		if (!ora_exec($ascur)):
			echo "$qry<br>";
			exit;
		endif;
		ora_fetch($ascur);
		$total+= chop(ora_getColumn($ascur,0));
		tcshow($total);

		$qry = "select sum(numtickets) from tickets where coach_serial='$coachserial' and go_to = (select short_name from route_stops where route_Serial='$rs' and stop_order=$stopno)";
		tcshow($qry);
		ora_parse($ascur,$qry);
		if (!ora_exec($ascur)):
			echo "$qry<br>";
			exit;
		endif;
		ora_fetch($ascur);
		$total-= chop(ora_getColumn($ascur,0));
		tcshow($total);

		$qry = "select sum(numtickets) from on_hold_tickets where coach_serial='$coachserial' and ";
		$qry.= "go_from = (select short_name from route_stops where route_serial='$rs' ";
		$qry.= "and stop_order=$stopno)";
		tcshow($qry);
		ora_parse($ascur, $qry);
		if (!ora_exec($ascur)):
			echo "$qry<br>";
			exit;
		endif;
		ora_fetch($ascur);
		tcshow("OH: ".chop(ora_getColumn($ascur,0)));
		$total+= chop(ora_getColumn($ascur,0));
		tcshow($total);

		$qry = "select sum(numtickets) from on_hold_tickets where coach_serial='$coachserial' and ";
		$qry.= "go_to = (select short_name from route_stops where route_serial='$rs' ";
		$qry.= "and stop_order=$stopno)";
		tcshow($qry);
		ora_parse($ascur, $qry);
		if (!ora_exec($ascur)):
			echo "$qry<br>";
			exit;
		endif;
		ora_fetch($ascur);
		tcshow("OH: ".chop(ora_getColumn($ascur,0)));
		$total-= chop(ora_getColumn($ascur, 0));
		tcshow($total);


		$asarray[$i] = $total;
		$i++;
	endwhile;
	
	$startat--;
	$endat--;
	//rsort($asarray);
	$booked=0;
	if (true):
		$i=0;
		while ($i<count($asarray)):
			$showme = $i."-".$asarray[$i];
			tcshow($showme);
			if ($i>=$startat && $i<=$endat):
				if ($asarray[$i]>$booked):
					$booked = $asarray[$i];
				endif;
			endif;
			$i++;
		endwhile;
	endif;

	ora_close($ascur);

	//$retval = $max - $asarray[0];
	$retval = $max - $booked;
	tcshow("retval $retval");
	tcshow("start $startat<br>end: $endat");
	tcshow("booked $booked<br>max $max");
	tcshow("END<br><br>");
	
	return $retval;
}

function availseats($coachserial, $gofrom, $goto, $debug=false,$gotus="0",$extendedmode=false)
{
	global $conn,$global_availseats,$global_stoporders, $global_onoff, $gc_totpax, $tblprefix, $non_ee_count, $non_ee_perc; // global_availseats added june 2006 for new pricing system
					// global_stoporders added sep 2006 " "  " " "  "
	// non_ee_count = number of seats "wasted" on non end-to-end pax. - added Aug 2018 - only populated when $extendedmode=true

	//echo "!$extendedmode";

	$ascur = ora_open($conn);
	$stopOrders = array();

	$stopgroup=array();

	if ($extendedmode) {
		$first_stops=array();
		$last_stops=array();
		ora_parse($ascur,"select stop_name, group_no from price_stop_groups where route_serial=(select route_serial from open_coach where coach_serial='$coachserial')");
		ora_exec($ascur);
		while (ora_Fetch($ascur)) {
			$stopgroup[getdata($ascur,0)]=getdata($ascur,1);
		}
	}


	$qry = "select A.stop_order, A.short_name, A.route_serial, B.max_seats, B.run_date from $tblprefix"."route_stops A, $tblprefix"."open_coach B where A.route_serial = B.route_serial and B.coach_serial='$coachserial' order by stop_order";
	tcshow($qry);
	ora_parse($ascur, $qry);
	ora_exec($ascur);
	
	$startat = 0;
	$endat = 0;
	$rs = 0;
	$gc_totpax=0;
	$first_stops=array();

	while(ora_fetch($ascur))
	{
		$stopName = getdata($ascur,1);
		$coachdate= getdata($ascur,4);
		$coachrs  = getdata($ascur,2);
	
		// special case for Nelspruit evening
		if ($coachrs=="125298087") 
		{
			if ($coachdate<20170727)
			{
				if ($stopName=="NELSPRUIT EVENING")
				{

					$stopName="NELSPRUIT";
				}
			}
	
		}

		if (!isset($firststop))
		{
			$firststop=$stopName;
			if ($extendedmode) {
				$first_stops[$firststop]=true;
				if (isset($stopgroup[$firststop]))
					$firstgroup=$stopgroup[$firststop];
			}
		} elseif ($extendedmode) {
			if (isset($stopgroup[$stopName])) 
				if ($stopgroup[$stopName]==$firstgroup) 
					$first_stops[$stopName]=true;
		}
		$stopOrders[$stopName] = getdata($ascur,0);
		if($rs == 0)
		{
			$rs = getdata($ascur,2);
			$max = getdata($ascur,3);
		}
	} // while fetch 

	$last_stops=array();

	if ($extendedmode) {
		$laststop=$stopName;
		$last_stops[$laststop]=true;
		if (isset($stopgroup[$laststop])) {
			$lastgroup=$stopgroup[$laststop];  	
			reset($stopOrders);
			$lastkey="NONE";
			while (list($key,$val)=each($stopOrders)) 
			{
				if (isset($stopgroup[$key]))
					if ($stopgroup[$key]==$lastgroup)		
						$last_stops[$key]=true;
				if (!isset($last_stops[$lastkey]) && isset($last_stops[$key]))
					$last_stops[$lastkey]=true; // people leaving the last non-end point are getting off at an end-point
				
				$lastkey=$key;				
			}
		}
	}


/*
	echo "First Stops: ";
	print_r($first_stops);
	echo "<br>Last STops: ";
	print_r($last_stops);
	echo "<Br>";
*/

	if ($gofrom.$goto=="") {
		$gofrom=$firststop;
		$goto=$stopName;
	}

	$global_stoporders[$coachserial]=$stopOrders;

	$startat = $stopOrders[$gofrom];
	$endat = $stopOrders[$goto];

        if ($startat>=$endat)
                return -100; // bad order!!!

	$total = 0;
	$getOn = array();
	$getOff = array();

	reset($stopOrders);

	while(list($k,$v) = each($stopOrders))
	{
		$getOn[$k] = 0;
		$getOff[$k] = 0;
	}

	$qry = "select t.numtickets, t.go_from, t.go_to from $tblprefix"."tickets t where t.coach_serial='$coachserial' union all select h.numtickets, h.go_from, h.go_to from $tblprefix"."on_hold_tickets h where h.coach_serial='$coachserial'";
	if ($gotus!="0"):
		$qry.=" and user_serial<>'$gotus'";
	endif;
	if ($debug)
		echo "$qry<bR>";
	ora_parse($ascur, $qry);
	ora_exec($ascur);
	unset($global_onoff[$coachserial]);
	while(ora_fetch($ascur))
	{
		$amt=getdata($ascur,0);

		$stopName = getdata($ascur,1);
		$getOn[$stopName] += $amt;
		$stopName2 = getdata($ascur,2);
		$getOff[$stopName2] += $amt;
		$global_onoff[$coachserial][$stopName][$stopName2]+=$amt;
		$gc_totpax+=$amt;

	}

	$availNum = $max;
	$min = $max;
	reset($stopOrders);

	while(list($k,$v) = each($stopOrders))
	{
		$availNum -= $getOn[$k];
		$availNum += $getOff[$k];

		$global_availseats[$coachserial][$v]=$availNum; // NEW: Used for new pricing model, 2006
	
		if($startat <= $v && $v < $endat && $min > $availNum)
		{
			$min = $availNum;
		}
	}
	ora_close($ascur); /* added by kw 15/2/2001 */

	if ($extendedmode) {
		unset($highest_peak);
		unset($lowest_peak);
		reset($stopOrders);
		$pax=0;
		while (list($stop,$order)=each($stopOrders)) {
			$pax+=$getOn[$stop];
			$pax-=$getOff[$stop];
		//	echo "AT $stop, $getOn[$stop] get on, $getOff[$stop] get off, now we have $pax on board<BR>";



				if($startat <= $order && $order < $endat) {

					// highest_peak will be the max number of pax on the bus for this portion of the trip you are searching

					if (!isset($highest_peak) || ($pax>$highest_peak)) {
						$highest_peak=$pax;
						$highest_stop=$stop;
					}
					  
			  	} elseif ($debug) echo "Ignore $stop for highest ($startat-$endat vs $order)<Br>";

	
			if (!isset($first_stops[$stop]) && !isset($last_stops[$stop])) {

				// the eventual value of $lowest_peak will be the number of end-to-end tickets on this bus

			        if (!isset($lowest_peak) || ($pax<$lowest_peak)) {
                                        $lowest_peak=$pax;
                                        $lowest_stop=$stop;
      		                }
			}
			
		}	
		if ($debug)
			echo "Highest peak: $highest_peak at $highest_stop, Lowest dip: $lowest_peak at $lowest_stop<Br>";
		$waste_calc=$highest_peak-$lowest_peak;
		if ($debug)
			echo "So, Non end to end seat blockage level is $waste_calc<br>";
		$non_ee_count=$waste_calc;

	}	
//	else echo "#";


	if ($max==0)
		$non_ee_perc=0;
	else
		$non_ee_perc=round($non_ee_count*100/$max);

	return $min;
}


?>
