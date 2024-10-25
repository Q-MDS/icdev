<?
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

function getdata($tcur,$tcol,$details=false)
{
	$details=false;
	if ($details):
		return getdatax($tcur,$tcol,$details);
	else:
		return chop(ora_getColumn($tcur,$tcol));
	endif;
}

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

function getuserserial()
{
	global $conn;
	$gus = ora_open($conn);
	$tuser = getenv("REMOTE_USER");
	ora_parse($gus,"select user_serial from user_details where username='$tuser' and is_current='Y'");
	ora_exec($gus);
	ora_fetch($gus);
	$retval = chop(ora_getcolumn($gus,0));
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

Function convert_money( $from_curr, $to_curr, $amount)
{
	global $conn,$debug, $REMOTE_USER;

/*
	if ($REMOTE_USER=="tw"):
		echo "CM: $from_curr, $to_curr, $amount<br>";
	endif;
*/

	$cmc = Ora_Open($conn);
	$qry = "select UZAR from currency_info where name='$from_curr'";
	ora_parse($cmc,$qry);
	ora_exec($cmc);
	if (!ora_fetch($cmc)):
		echo "invalid currency $from_curr. ";
		return false;
	endif;
	$UZAR_from = chop(ora_getcolumn($cmc,0));
	ora_close($cmc);

	$cmc = Ora_Open($conn);
	$qry = "select UZAR, roundup from currency_info where name='$to_curr'";
	ora_parse($cmc,$qry);
	ora_exec($cmc);
	if (!ora_fetch($cmc)):
		echo "invalid currency $to_curr. ";
		return false;
	endif;
	$UZAR_to = chop(ora_getColumn($cmc,0));
	$roundup = chop(ora_getColumn($cmc,1));
	ora_Close($cmc);

	echodb($debug,"converting from $from_curr to $to_curr");
	echodb($debug,"$amount, $UZAR_from, $UZAR_to");

	$newprice = ($amount / $UZAR_from) * $UZAR_to;
	$newprice = IntVal($newprice);
	
	while (($newprice%$roundup)!=0):
		$newprice++;
	endwhile;

	return $newprice;
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

function availseats($coachserial, $gofrom, $goto, $debug=false)
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

?>
