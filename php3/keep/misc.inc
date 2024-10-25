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
$graphpaths[10]="../jpgraph-4.2.6-pro/src/";

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
$str = "";
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

	global $argv, $conn, $smscursor, $grapevinetimeout, $dont_use_telegram;
	if (trim($msg)=="") {
		echo "<font color=red>NOT SENDING A BLANK MESSAGE</font><br>";	
		return false;
	}

        if ($_SERVER["HTTP_HOST"]=="192.168.10.239")
        {
                return true;
               $number="0846575577";
        }

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

	
	// TELEGRAM DIVERT
	if (file_exists("/tmp/telegram$number") && $number[0]=="+" && is_numeric(substr($number,1,99)) && !$dont_use_telegram) {
		$included="Y";
		require_once("/usr/local/www/pages/telegram_inbound_msg.php");
		$chatid=file_get_contents("/tmp/telegram$number");
		if ($debug) {
			if (is_numeric($chatid))
				echo "numeric $chatid<br>";
			else
				echo "NOT numeric $chatid<br>";
		}
		$sender=getenv("REMOTE_USER");
		$origmsg=$msg;
		if ($sender=="" || $sender=="dummy")
			$msg="System SMS:\n$msg";
		else
			$msg="SMS Sent from system by $sender:\n$msg";
		if (send_telegram($chatid,$msg))
		{
			if (isset($conn)) {
                                if (!isset($smscursor))
                                        $smscursor=ora_open($conn);
                                $reference=str_replace("'","",$reference);
                                $msg=str_replace("'","",$msg);
                                $qry="insert into sms_sent (serial, date_time, number_to, message, coach_serial, replyto, sent_by, reference) values (sms_id.nextval, CURRENT_TIMESTAMP, '$number','$msg','$cs','$replyto','$sender','$reference')";
                                ora_parse($smscursor,$qry);
                                ora_exec($smscursor);
                                //$number, $msg, $cs="", $replyto="", $reference=""

                        }

			return true;
		
		}
		// if it fails, we send by SMS:
		$msg=$origmsg;
	}
	
	// EMAIL DIVERT
        $divert_filename="/usr/local/www/sms_to_email/divert$number";

        if (file_exists($divert_filename))
        {
                        $email_address=file_get_contents($divert_filename);
                        if ($debug) echo "Diverted to $email_address<br>";
                        $sender=getenv("REMOTE_USER");
                        $origmsg=$msg;
                        if ($sender=="" || $sender=="dummy")
                               $subject="System SMS";
                        else
                               $subject="SMS Sent from system by $sender";
                        $msg="  $subject:\n  $msg";

                        mail($email_address,$subject,$msg);
                        if (isset($conn)) {
                                if (!isset($smscursor))
                                        $smscursor=ora_open($conn);
                                $reference=str_replace("'","",$reference);
                                $msg=str_replace("'","",$msg);
                                $qry="insert into sms_sent (serial, date_time, number_to, message, coach_serial, replyto, sent_by, reference) values (sms_id.nextval, CURRENT_TIMESTAMP, '$number','$msg','$cs','$replyto','$sender','$reference')";
                                ora_parse($smscursor,$qry);
                                ora_exec($smscursor);
                                //$number, $msg, $cs="", $replyto="", $reference=""

                        }
                        return true;

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


	if (isset($argv) && isset($argv[1]) && $argv[1]=="test")
		echo "X";
	if (send_grapevine_sms ($number,$msg,$cs,$replyto,$reference))
		return true;
	if (isset($argv) && isset($argv[1]) && $argv[1]=="test")
                echo "Y";


	/// otherwise, try the old way...


        $myDB="sms";
        $myServer="127.0.0.1:3306";
        $myUser="root";
        $myPass="fast#SQL!";

      try {
          $mysql_conn = new PDO  ("mysql:host=$myServer;dbname=$myDB",$myUser,$myPass);
          } catch ( PDOException $e){
            echo "Error connecting to Mysql on $myServer<bR>";
            $myerror= $e->getMessage();
  	    echo $myerror;
	    $mysql_conn=false;
	}

	if (!$mysql_conn) {
		mail("sms@intercape.co.za","$number",$msg);
                mail("keith@intercape.co.za","Failed connect  send_sms_msg","This has been sent to sms@intercape.co.za:\n$msg\n$myerror");
                return true;
        }

		
        $msg=str_replace("'","",substr($msg,0,160));
//      echo strlen($qry);

        $qry= "INSERT INTO sms.sms_inbox (date_time, number, message, priority) VALUES(NOW(),'$number','$msg', 10)";
	

        $stmt=$mysql_conn->prepare($qry);

        if ($stmt->execute())
        {
                        return true;
        } else {
		$myerrors=implode("\n",$stmt->errorInfo());
		unset($mysql_conn);

                echo "SMS DB Query failed<br>\n";
		mail("sms@intercape.co.za","$number",$msg);
		mail("keith@intercape.co.za","$number failed send_sms_msg","This has been sent to sms@intercape.co.za:\n$msg\n$myerrors");
        }

	unset($mysql_conn);
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

	if (!is_numeric($value))	
		$value=0;

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


function getuserserial($getcuruser=true)
{
	global $conn,$is_staff_flag,$my_staff_no, $my_curr_name, $my_master_user, $my_user_name, $my_primary_user, $mystafftype, $my_branch_name;
	$gus = ora_open($conn);
	$tuser = getenv("REMOTE_USER");
	ora_parse($gus,"select user_serial,staff_member,staff_no,master_user,current_user,m_currency,username,primary_user,stafftype, branch from user_details where username='$tuser' and is_current='Y'");
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
			ora_parse($gus,"select user_serial,staff_member,staff_no,master_user,current_user,m_currency,username,primary_user,stafftype, branch from user_details where user_serial=$curuser ");
		        ora_exec($gus);
		        ora_fetch($gus);
			$retval = $curuser;
		}
	$is_staff_flag=chop(ora_getcolumn($gus,1));
	$my_staff_no=chop(ora_getcolumn($gus,2));
	$my_branch_name=chop(ora_getcolumn($gus,9));
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

	if (!is_numeric($num_days))
		return $old_date;
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


	if (!isset($debug))
		$debug = false;	

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

      mail("keitha@cavmail.co.za","Currency error",$msg."\n".getenv("REQUEST_URI").":".$_PHP_SELF."\nArgs: ".implode("&",$_POST)); 
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

                mail("keitha@cavmail.co.za","Currency error",$msg); 
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
		$newprice = ceil($newprice);
	
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

	if ($REMOTE_USER=="xKeith" || getenv("REMOTE_ADDR")=="192.168.10.64"):
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
		$qry = "select rate from sage_currency where currency='$from_curr' and start_date<=CURRENT_TIMESTAMP order by start_date desc, uploaded desc";
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
		$qry = "select rate from sage_currency where currency='$to_curr' and start_date<=CURRENT_TIMESTAMP order by start_date desc, uploaded desc";
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
	global $conn,$zzzdebug, $REMOTE_USER, $no_convert_round, $convert_cursor;


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

      mail("keitha@cavmail.co.za","Currency error",$msg."\n".getenv("REQUEST_URI").":".$_PHP_SELF."\nArgs: ".implode("&",$_POST)); 
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

                mail("keitha@cavmail.co.za","Currency error",$msg); 
		           echo "<h2>This page will not be correct/complete - please report this error immediately!</h2>\n";
		/*exit;
*/
		return false;
	endif;
	$UZAR_to = chop(ora_getColumn($cmc,0));
	$roundup = chop(ora_getColumn($cmc,1));
	if (!isset($convert_cursor))
		ora_Close($cmc);

	if (!isset($debug))
		$debug = false;

	echodb($debug,"converting from $from_curr to $to_curr");
	echodb($debug,"$amount, $UZAR_from, $UZAR_to");

	$newprice = ($amount / $UZAR_from) * $UZAR_to;
	if (!$no_convert_round)
		$newprice = ceil($newprice);
	
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

function availseats($coachserial, $gofrom, $goto, $debug=false,$gotus="0",$extendedmode=false)
{
	// NOTE: this is almost the same as ops_availseats in /ops/getavail.inc, but has additional code (and return values) for wasted seats
	global $conn,$global_availseats,$global_stoporders, $global_onoff, $gc_totpax, $tblprefix, $non_ee_count_start, $non_ee_count_end, $non_ee_perc_start, $non_ee_perc_end, $ee_startpoint, $ee_endpoint; // global_availseats added june 2006 for new pricing system


					// global_stoporders added sep 2006 " "  " " "  "
	// non_ee_count = number of seats "wasted" on non end-to-end pax. - added Aug 2018 - only populated when $extendedmode=true
	// ee split of start and end added May 2019


	$non_ee_perc_start=0;
	$non_ee_count_start=0;
	$non_ee_perc_end=0;
	$non_ee_count_end=0;
	$ee_startpoint=""; // this will be the starting place of the end to end price that we must search if the wasted seats is too high
	$ee_endpoint=""; // this will be the ending place of the end to end price that we must search if the wasted seats is too high

	//echo "!$extendedmode";

/*	if (getenv("REMOTE_USER")=="Keith" && $coachserial=="15523817696871") {
		$debug=true;
		echo "<hr>Calc for $gofrom to $goto on $coachserial<br>";
	}
*/
	$ascur = ora_open($conn);
	$stopOrders = array();

	$stopgroup=array();

	if ($extendedmode) {
		//$first_stops=array();
		//$last_stops=array();
		$first_part_stops=array();
                $last_part_stops=array();

		ora_parse($ascur,"select stop_name, group_no from price_stop_groups where route_serial=(select route_serial from open_coach where coach_serial='$coachserial')");
		ora_exec($ascur);
		while (ora_Fetch($ascur)) {
			$stopgroup[getdata($ascur,0)]=getdata($ascur,1);
		}
	}

	$cap_stop="";
	$cap_at=99999;
	ora_parse($ascur,"select stop_name,max_pax from cap_pax_at_stop where coach_serial='$coachserial' and stop_name in ('$gofrom','$goto')");
	ora_exec($ascur);
	if (ora_fetch($ascur)) {
		$cap_stop=getdata($ascur,0);
		$cap_at=getdata($ascur,1);
	}
	if ($debug) {
		echo "CAP STOP: select stop_name,max_pax from cap_pax_at_stop where coach_serial='$coachserial' and stop_name in ('$gofrom','$goto')<Br>Gets $cap_stop / $cap_at<br>";
	}

	//                  0              1             2              3            4             5              6                  7
	$qry = "select A.stop_order, A.short_name, A.route_serial, B.max_seats, B.run_date, A.start_include, A.end_include, A.new_partition from $tblprefix"."route_stops A, $tblprefix"."open_coach B where A.route_serial = B.route_serial and B.coach_serial='$coachserial' order by stop_order";
	tcshow($qry);
	ora_parse($ascur, $qry);
	ora_exec($ascur);
	
	$startat = 0;
	$endat = 0;
	$rs = 0;
	$gc_totpax=0;
//	$first_stops=array();
//	$highest_start_point=0;
	$stopNames=array();
	$totSeatsAtStop=array();
        $start_include=array();
        $end_include=array();
        $start_partition=array();


	while(ora_fetch($ascur))
	{
		$this_stop_order=getdata($ascur,0);

		$stopName = getdata($ascur,1);
		$coachdate= getdata($ascur,4);
		$coachrs  = getdata($ascur,2);

		$start_include[$this_stop_order]=getdata($ascur,5);
		$end_include[$this_stop_order]=getdata($ascur,6);
		$start_partition[$this_stop_order]=getdata($ascur,7);

	
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
		}
		$stopOrders[$stopName] = getdata($ascur,0);
		if ($extendedmode)
                       $stopNames[getdata($ascur,0)]=$stopName;
		$last_stop_order=getdata($ascur,0);

		if ($debug)
                       echo "$stopName = $stopOrders[$stopName] ";

		if($rs == 0)
		{
			$rs = getdata($ascur,2);
			$max = getdata($ascur,3);
		}
		 // set number of seats
                $totSeatsAtStop[getdata($ascur,0)]=getdata($ascur,3);

                if ($debug)
                        echo "=".getdata($ascur,3)."<br>";

	} // while fetch 



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

	// these two include blocked seats
	$getOn = array(); 
	$getOff = array();

	// these two do not include blocked seats (used for wasted seat data)
	$preblockon = array();
        $preblockoff = array();



	reset($stopOrders);

	while(list($k,$v) = each($stopOrders))
	{
		$getOn[$k] = 0;
		$getOff[$k] = 0;
		$preblockon[$k] = 0;
                $preblockoff[$k] = 0;
	}

	$qry = "select t.numtickets, t.go_from, t.go_to, 'NORMAL' from $tblprefix"."tickets t where t.coach_serial='$coachserial' union all select h.numtickets, h.go_from, h.go_to, h.payhow from $tblprefix"."on_hold_tickets h where h.coach_serial='$coachserial'";
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
		if (!isset($global_onoff[$coachserial][$stopName][$stopName2]))
			$global_onoff[$coachserial][$stopName][$stopName2] = 0;
		$global_onoff[$coachserial][$stopName][$stopName2]+=$amt;
		$gc_totpax+=$amt;
		if ($debug) echo "Found $amt from $stopName to $stopName2 ".getdata($ascur,3)."<br>";

		if ($extendedmode) {
		  if (getdata($ascur,3)=="RESERVE") {

	                $stopOrder1=$stopOrders[$stopName];
       		        $stopOrder2=$stopOrders[$stopName2];

	                if ($stopOrder2>$stoporder1) {
	                        if ($debug)
	                                echo "Block ".getdata($ascur,0)." from $stopName $stopOrder1 to $stopName2 $stopOrder2<BR>";
	                        for ($sloop=$stopOrder1 ; $sloop<$stopOrder2; $sloop++) {
	                                if (isset($totSeatsAtStop[$sloop])) {
	                                        if ($debug)
	                                                echo "Remove ".getdata($ascur,0). " from stop $sloop ".$totSeatsAtStop[$sloop];
	                                        $totSeatsAtStop[$sloop]-=getdata($ascur,0);
	 
	                                        if ($debug)
	                                                echo " = ".$totSeatsAtStop[$sloop]."<br>";
       		                         }
       	       		        } // for
 
	                } //if


		  } // if RESERVE
		  else { // NOT Reserve
			$preblockon[$stopName] += $amt;
			$preblockoff[$stopName2] += $amt;
			if ($debug) echo "#<br>";
		  }
		} // extendedmode

	} // while


	if ($cap_stop!="")
        {
                $cap_stop=$stopOrders[$cap_stop];
		$cap_difference=$totSeatsAtStop[$cap_stop]-$cap_at;
//                $totSeatsAtStop[$cap_stop]=$cap_at; - dont do this - it will create a partition
                if ($debug) {
                        echo "CAP STOP is $cap_stop<bR>";
                        print_r($totSeatsAtStop);
                }
        } else {
		$cap_stop=-1;
	}



	$availNum = $max;
	$min = $max;

	reset($stopOrders);

	while(list($k,$v) = each($stopOrders))
	{
		$availNum -= $getOn[$k];
		$availNum += $getOff[$k];

		// new Feb 2020 - capping of stops
		if ($cap_stop==$v) 
		{
			if ($k==$gofrom)
			{
				$usenum=$cap_at-$getOn[$k];
				if ($debug || getenv("REMOTE_USER")=="Keeith")
	                                echo "First calc is $usenum = $cap_at-$getOn[$k] getting on here<bR>";
			}
			else {
				$usenum=$cap_at-$getOff[$k];
				if ($debug || getenv("REMOTE_USER")=="Keeith")
                                        echo "First calc is $usenum = $cap_at-$getOff[$k] getting OFF here<bR>";
				if ($min > $usenum)
					$min=$usenum; // required for last stop as it is excluded from the code below

			}
			// if the main bus has LESS seats available, we use that number:
			if ($availNum<$usenum)
				$usenum=$availNum;
		}
		else
			$usenum=$availNum;

		$global_availseats[$coachserial][$v]=$usenum; // NEW: Used for new pricing model, 2006
	
		if($startat <= $v && $v < $endat && $min > $usenum)
		{
			$min = $usenum;
		}
	}
	ora_close($ascur); /* added by kw 15/2/2001 */

	if ($extendedmode) {

	// NEW: May 2019  - Partitions
        $partitions=array();
	$copystops=$stopOrders;
        reset($stopOrders);
        $startcapacity="";
        foreach ($stopOrders as $stopkeyname => $stopvalorder) {
                if ($startcapacity=="") {
                        $startcapacity=$totSeatsAtStop[$stopvalorder];
                        $startcapacitystop=$stopvalorder;
                        $last_stop_key= $stopvalorder;
                }
                if ($stopvalorder!=$last_stop_order) { // we ignore the last stop
                        if ($totSeatsAtStop[$stopvalorder]!=$startcapacity || $start_partition[$stopvalorder]=="Y") {
                                $partition=array();
                                $partition["START"]=$startcapacitystop;
                                $partition["END"]=$last_stop_key;
                                $partition["CAPACITY"]=$startcapacity;

                                $partitions[]=$partition;

                                // setup the next one
                                $startcapacity=$totSeatsAtStop[$stopvalorder];
                                $startcapacitystop=$stopvalorder;

                        }
                        $last_stop_key= $stopvalorder;
                }
        } // foreach

        // store the last partition
        $partition=array();
        $partition["START"]=$startcapacitystop;
        $partition["END"]=$last_stop_order; // make it the end of the trip
        $partition["CAPACITY"]=$startcapacity;
        $totSeatsAtStop[$last_stop_order]=$startcapacity; // fix the last stop
        $partitions[]=$partition;
	


        foreach ($partitions as $partnumber => $partdata) {
		$part_start=$partdata["START"];
                $part_end=$partdata["END"];

		// find lowest end (and extra end points)
		$lowest_end=$part_end; 
                $lastpartstop=$stopNames[$part_end];
                $last_part_stops[$partnumber][$lastpartstop]=true;
                if (isset($stopgroup[$lastpartstop])) {
                        $lastpartgroup=$stopgroup[$lastpartstop];
                        reset($stopOrders);
                        $lastpartkey="NONE";
                        while (list($key,$val)=each($stopOrders))
                        {
                                if (isset($stopgroup[$key]))
                                        if ($stopgroup[$key]==$lastpartgroup)
					{
                                                $last_part_stops[$partnumber][$key]=true;
						if ($val<$lowest_end)
							$lowest_end=$val; // earliest "last" stop
					}
				if ($val>=$part_start && $val<=$part_end && $end_include[$val]=="Y") {
					$last_part_stops[$partnumber][$key]=true;
                                        if ($val<$lowest_end)
                                                  $lowest_end=$val; // earliest "last" stop
				}
			
                                $lastpartkey=$key;
                        } // while
                } // if
                if ($debug) {
                        echo "Last stops for partition $partnumber are: ";
                        foreach ($last_part_stops[$partnumber] as $lpkey => $lpval) {
                                echo "$lpkey,";
                        }
                        echo " (First $lowest_end / ".$stopNames[$lowest_end].")<BR>";
                }

		// find highest start (and extra start points)
                $highest_start=$part_start;
		$firstpartstop=$stopNames[$part_start];
                $first_part_stops[$partnumber][$firstpartstop]=true;
                if (isset($stopgroup[$firstpartstop])) {
                        $firstpartgroup=$stopgroup[$firstpartstop];
                        reset($stopOrders);
                        while (list($key,$val)=each($stopOrders))
                        {
                                if (isset($stopgroup[$key]))
                                        if ($stopgroup[$key]==$firstpartgroup)
                                        {
                                                $first_part_stops[$partnumber][$key]=true;
                                                if ($val>$highest_start)
                                                        $highest_start=$val; // latest "first" stop
                                        }
                                if ($val>=$part_start && $val<=$part_end && $start_include[$val]=="Y") {
						$first_part_stops[$partnumber][$key]=true;
                                                if ($val>$highest_start)
                                                        $highest_start=$val; // latest "first" stop

					
                                }

                        } // while
                } // if
                if ($debug) {
                        echo "First stops for partition $partnumber are: ";
                        foreach ($first_part_stops[$partnumber] as $lpkey => $lpval) {
                                echo "$lpkey,";
                        }
                        echo " (Last $highest_start / ".$stopNames[$highest_start].")<BR>";
                }
		$partitions[$partnumber]["PINITIAL"]=$highest_start;
		$partitions[$partnumber]["PFINAL"]=$lowest_end;
        } // foreach
	
        if ($debug)
        {
                echo "<font style='background: yellow'>TEST:</font><hr><pre>";
                print_r($totSeatsAtStop);
                echo "<hr>";
                print_r($partitions);
                echo "</pre><hr>";
                ob_end_flush();
        }


		// Partitions Part 2
          foreach ($partitions as $partitionkey => $pdata) {
                $pstartplace=$pdata["START"];
                $pendplace=$pdata["END"];
                $pcapacity=$pdata["CAPACITY"];
		$pinitial=$pdata["PINITIAL"];
		$pfinal=$pdata["PFINAL"];
                if ($debug)
                        echo "<hr>Partition from $pstartplace ".$stopNames[$pstartplace]." to $pendplace ".$stopNames[$pendplace]." ($pcapacity seats):<Br>";

                unset($highest_peak);
                unset($lowest_peak);
		unset($highest_stop);
		unset($lowest_stop);
                reset($stopOrders);
                $pax=0;
                while (list($stop,$order)=each($stopOrders)) {
                        $pax+=$preblockon[$stop];
                        $pax-=$preblockoff[$stop];

                        if ($debug)
                              echo "$order) AT $stop, $preblockon[$stop] get on, $preblockoff[$stop] get off, now we have <b>$pax</b> on board:<font size=-1> ";

			//NB: we look at all relevant stops on this partition for the lowest number, but only stops relevant to our trip for the highest number
                        if ($order>=$pstartplace && $order<=$pendplace ) {
                                if ($debug)
                                        echo " (relevant) ";

  			  // we should only look FROM the "highest start" point onwards, and ignore the "last stops" for this partition
			  if (isset($first_part_stops[$partitionkey][$stop]) || isset($last_part_stops[$partitionkey][$stop]) || $order==$pinitial ) {
				if ($debug) echo " (<font color=red>ignoring</font> - it's an endpoint) ";
			  } else {
                                        if ($debug)  echo " (<font color=green>using it</font>: not endpoint) ";


				// when looking for the highest number, only look at stops relevant to our trip. 
				if ($order>=$startat && $order<$endat) {
                                  if (!isset($highest_peak) || ($pax>$highest_peak)) {
                                        if ($debug) echo " (<font color=magenta>new high</font>) ";
                                        $highest_peak=$pax;
                                        $highest_stop=$stop;
                                  }
				}
			
				// check all stops for lowest point = the end to end pax 
                                if (!isset($lowest_peak) || ($pax<$lowest_peak)) {
                                        if ($debug) echo " (<font color=magenta>new low</font>) ";
                                        $lowest_peak=$pax;
                                        $lowest_stop=$stop;
                                }
                          }

                        }
                        elseif ($debug) echo " (<font color=red>not relevant $order must be $pstartplace - $pendplace and $startat - $endat</font>) ";
                        if ($debug)
                                echo "</font><BR>";

                }
                if ($debug)
                       echo "Highest peak: $highest_peak at $highest_stop, Lowest dip: $lowest_peak at $lowest_stop<Br>";
                $waste_calc=$highest_peak-$lowest_peak;
		if ($pcapacity==0)
			$pperc=0;
		else
       		        $pperc=(100*$waste_calc/$pcapacity);
                $pperc=sprintf("%d",$pperc);
		if ($debug)
                {

                       echo "So, Non end to end seat blockage level is $waste_calc/$pcapacity = $pperc %<br>";
                }

		// note: pinitial is my last FIRST stop and pfinal is my first LAST stop on this partition

		if ($debug)
			echo "Check starting point $startat against $pinitial - $pfinal:";

		// I dont worry about starting at the first or last stop
		if ($startat>$pinitial && $startat<$pfinal) {
			if ($debug)
                                echo "<font color=green>Yes</font><bR>";
			$non_ee_perc_start=$pperc;
			$non_ee_count_start=$waste_calc;
			$ee_startpoint=$stopNames[$pstartplace]; // pricing.phtml will use this IF the perc is over the threshold

		} elseif  ($debug) echo "<font color=red>Not</font><br>";

		if ($debug)
                        echo "Check ending point $endat against $pinitial - $pfinal:";

		// I dont worry about ending at the first or last stop
                if ($endat>$pinitial && $endat<$pfinal) {
                        if ($debug)
                                echo "<font color=green>Yes</font><bR>";
                        $non_ee_perc_end=$pperc;
                        $non_ee_count_end=$waste_calc;
                        $ee_endpoint=$stopNames[$pendplace]; // pricing.phtml will use this IF the perc is over the threshold
                } elseif  ($debug) echo "<font color=red>Not</font><br>";
          } // foreach

	if ($debug)
		echo "Final value: Start ($ee_startpoint) $non_ee_count_start  : Percentage: $non_ee_perc_start . End ($ee_endpoint) $non_ee_count_end : Percentage: $non_ee_perc_end<br>";
		
// TEST!!
//	if ($debug)
//		$non_ee_perc_start+=15;


		// END OF PARTITIONS

	} // extendedmode	

	return $min;
} // function availseats 


?>
