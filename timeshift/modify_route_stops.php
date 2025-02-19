<HTML>
<body oncontextmenu="showMenu(); return false"; bgcolor="#FFFFFF" text="#000000"
 link="#000000" vlink="#000000" alink="#000000">
<? require_once ("../php3/context.inc"); ?>
<H3>
Serial #<?echo $serial?> (route: <?echo $passrn?>)<br>
<?echo $passft?>
</H3>
<?
if ($printable!="Y")
	echo "<form method=post action=modify_route_stops.phtml>";
?>

<script language="javascript">
function tickbox(textbox,tickbox)
{
}

function clearbox(textbox,tickbox)
{
}
</script>

<?
require_once ("../php3/oracle.inc");
require_once ("../php3/sec.inc");
require_once ("../php3/colors.inc");
require_once ("../php3/logs.inc");
require_once ("../php3/checkdate.inc");
require_once ("../php3/misc.inc");

if (!open_oracle()) { Exit;};
if (!AllowedAccess("MODIFY_ROUTE")) { Exit; };

$username=getenv("REMOTE_USER");
get_colors("$username");


ora_parse($cursor,"select sub_route_one,sub_route_two, crossover from route_details where route_serial='$serial' and (sub_route_one is not null or sub_route_two is not null)");
ora_exec($cursor);
if (ora_fetch($cursor)) {
	$serial1=getdata($cursor,0);
	$serial2=getdata($cursor,1);
	$crossover_stop=getdata($cursor,2);
	echo "</form><B>NB: This is a virtual route, consisting of sub-routes.  The stop details must be edited on the individual routes</b><hr>";
	unset($doupdate);
	$subroute=true;
}
else
	echo "<a href=price_stop_groups.phtml?route_serial=$serial>Edit Stop Groups</a> | ";
echo " <a href=modify_route_stops.phtml?serial=$serial&printable=Y>Show printable version</a><BR>";

$changes_made = false;

if ($doupdate=="Update")
{
	$i=0;
	$cupdate=0;
	$days_after=0;

	while ($i<$numrecs)
	{
		$allcorrect = true;

		$query = "update route_stops set ";
		$newvar = "arr_$i";
		$changes_made = true;

	        $atime = checktime(${$newvar});
        	if ($atime==-1)
                {$allcorrect=show_error("Stop $i: Invalid Hour : ${$newvar}");};
        	if ($atime==-2)
                {$allcorrect=show_error("Stop $i: Invalid Min: ${$newvar}");};
        	if ($atime<-2)
                {$allcorrect=show_error("Stop $i: Invalid Time: ${$newvar}");};

		$query = $query."arrive_time='$atime', ";

		$newvar = "dep_$i";

	        $dtime = checktime(${$newvar});
        	if ($dtime==-1)
                {$allcorrect=show_error("Stop $i: Invalid Hour : ${$newvar}");};
        	if ($dtime==-2)
                {$allcorrect=show_error("Stop $i: Invalid Min: ${$newvar}");};
        	if ($dtime<-2)
                {$allcorrect=show_error("Stop $i: Invalid Time: ${$newvar}");};

		$query = $query."depart_time='$dtime', ";

		$newvar = "nextday_$i";
		if (${$newvar}!="Y"): 
			${$newvar}="N"; 
		else:
			$days_after++;
		endif;
		$query = $query."next_day='${$newvar}', ";

		$newvar = "major_$i";
		if (${$newvar}!="Y"):
			${$newvar}="N";
		endif;
		$query = $query."major_stop='${$newvar}', ";

		$newvar = "crossover_$i";
		if (${$newvar}!="Y"):
			${$newvar}="N";
		endif;
		$query = $query."crossover='${$newvar}', ";

                $newvar = "print_m_$i";
                if (${$newvar}!="Y"):
                        ${$newvar}="N";
                endif;
                $query = $query."print_manifest='${$newvar}', ";

		$newvar = "shut_$i";
                if (${$newvar}!="Y"):
                        ${$newvar}="N";
                endif;
                $query = $query."shuttle='${$newvar}', ";

		$newvar = "chng_$i";
		$changeat=str_replace("'","",${$newvar});

                $query = $query."change_bus='$changeat', ";




		$newvar = "rsatime_$i";
		$query = $query."rsatime='${$newvar}', ";

		$newvar = "passport_$i";
		if (${$newvar}!="Y"):
			${$newvar}="N";
		endif;
		$query = $query."passport='${$newvar}', ";

		// include this stop as a start stop on a partition
		$newvar = "pstart_$i";
                if (${$newvar}!="Y"):
                        ${$newvar}="N";
                endif;
                $query = $query."start_include='${$newvar}', ";

		// include this stop as an end stop on a partition
		$newvar = "pend_$i";
                if (${$newvar}!="Y"):
                        ${$newvar}="N";
                endif;
                $query = $query."end_include='${$newvar}', ";

		// start a new partition here
		$newvar = "partn_$i";
                if (${$newvar}!="Y"):
                        ${$newvar}="N";
                endif;
                $query = $query."new_partition='${$newvar}', ";



		$newvar = "snotes_$i";
		$snotes=$_POST[$newvar];
		$snotes=str_replace("'","",$snotes);
		$snotes=substr($snotes,0,80);
		$query = $query."stop_notes='$snotes', ";

		$newvar = "pfee_$i";
		$query = $query."pass_fee=${$newvar}, ";

		$newvar = "stp_$i";
		$query = $query."stop_order=${$newvar}, ";

		$query = $query."day_after=$days_after ";

		$query = $query."where route_serial='$serial' ";

		$newvar = "short_$i";
		$query = $query."and short_name='${$newvar}' ";
	
		if ($allcorrect):
			@ora_parse($cursor,$query);
			if (@ora_exec($cursor)):
				$cupdate++;
			else:
				$tcnum=$i; $tcnum++;
				echo "Stop $tcnum <B>NOT</B> updated!<br>";
				echo "REASON: ";
				echo ora_error($cursor);
				echo "<br>";
				echo "QUERY: $query<br>";
			endif;	
		endif;
		$i++;
	}
	echo "<font color=#FF0000>"; 
	if ($cupdate!=$numrecs):
		echo "Only $cupdate/$numrecs records were correctly updated";
	else:
		echo "$cupdate/$numrecs records correctly updated";
	endif;
       log_manage(getenv("REMOTE_USER"),$serial,"ROUTE","Modified Route Stops");
	echo "</font>";
}

if ($doupdate=="Add Stop")
{
$tcursor = ora_Open($conn);
$done = false;

ora_parse($cursor,"select * from route_stops where route_serial='$serial' and short_name='$newstopname'");
ora_exec($cursor);
if (ora_fetch($cursor)):
	echo "Stop $newname is already assigned in this route.<br>";
	$done=true;
endif;

if (!$done)
{
	ora_parse($cursor,"select * from route_stops where route_serial='$serial' order by stop_order asc");
	ora_exec($cursor);
	while (ora_fetch($cursor))
	{
		$qso = chop(ora_getColumn($cursor,9));
		$qsn = chop(ora_getColumn($cursor,4));
		$qrn = chop(ora_getColumn($cursor,1));
		$qdf = chop(ora_getColumn($cursor,2));
		$qdt = chop(ora_getColumn($cursor,3));
		if ($qso>$newstopmarker)
		{
			$neworder=$qso+1;
			$qry="update route_stops set stop_order=$neworder ";
			$qry=$qry."where route_serial='$serial' and ";
			$qry=$qry."short_name='$qsn'";
			ora_parse($tcursor,$qry);
			ora_exec($tcursor);
			$changes_made = true;
		}
	}
	ora_close($tcursor);


	$prev_stop = $newstopmarker;
	$newstopmarker++;

	if ($prev_stop==0):
		ora_parse($cursor,"select * from route_stops where route_serial='$serial' and stop_order=2");
	else:
		ora_parse($cursor,"select * from route_stops where route_serial='$serial' and stop_order=$prev_stop");
	endif;
	ora_exec($cursor);
	ora_fetch($cursor);

	$qrn = chop(ora_getColumn($cursor,1));
	$qdf = chop(ora_getColumn($cursor,2));
	$qdt = chop(ora_getColumn($cursor,3));
	$qda = chop(ora_getColumn($cursor,12));

	
        if ($qrn=="")
                $qrn=$passrn;

	if ($qda=="")
		$qda=0;
	if ($qdf=="") {

		ora_parse($cursor,"select date_from,date_to from route_details where route_serial='$serial'");
		ora_exec($cursor);
		ora_fetch($cursor);
		$qdf=getdata($cursor,0);
		$qdt=getdata($cursor,1);
	}



	$changes_made = true;
	$qry = "insert into route_stops values ('$serial','$qrn','$qdf',";
	$qry = $qry."'$qdt','$newstopname','0000','0000','N',";
	$qry = $qry."'N',$newstopmarker,'N',0,$qda,'N','NONE','N','N',null,null,'N','N','N',null)";
	
//	echo "$qry<Br>";
	ora_parse($cursor,$qry);
	ora_exec($cursor);

	// get previous stop...
	$prevstop=-1;
	ora_parse($cursor,"select short_name from route_stops where route_serial='$serial' and stop_order<$newstopmarker order by stop_order desc");
	ora_exec($cursor);
	if (ora_fetch($cursor)) {
		$theprevstop=getdata($cursor,0);
		ora_parse($cursor,"select stop_Serial from stop_details2 where shortname='$theprevstop'");
		ora_exec($cursor);
		if (ora_fetch($cursor)) {
			$prevstop = getdata($cursor,0);
			if ($debug) echo "prev stop is $prevstop<bR>";
		}
	}
	$nextstop=999;
	ora_parse($cursor,"select short_name from route_stops where route_serial='$serial' and stop_order>$newstopmarker order by stop_order");
        ora_exec($cursor);
        if (ora_fetch($cursor)) {
                $thenextstop=getdata($cursor,0);
                ora_parse($cursor,"select stop_Serial from stop_details2 where shortname='$thenextstop'");
                ora_exec($cursor);
                if (ora_fetch($cursor)) {
                        $nextstop = getdata($cursor,0);
                        if ($debug) echo "next stop is $nextstop<bR>";
                }
        }

	$stopadded=true;


	if (false) {
	// check grid
	ora_parse($cursor,"select other_price_grid from route_details where route_serial='$serial' and other_price_grid is not null");
	ora_exec($cursor);
	if (ora_fetch($cursor)) {
		$grid=getdata($cursor,0);
		ora_parse($cursor,"Select stop_serials from master_other_price_gridnames where grid_name_serial=$grid");
		ora_Exec($cursor);
		if (ora_fetch($cursor)) {
			$stopserials=getdata($cursor,0);
			ora_parse($cursor,"Select stop_serial from stop_Details2 where shortname='$newstopname'");
			ora_exec($cursor);
			if (ora_fetch($cursor)) {
				$stopserial=getdata($cursor,0);
				if ($debug) echo "Checking for $stopserial in $stopserials....<Br>";
				$ssfound=false;
				$lookin=explode(",",$stopserials);
				$newarr=array();
				foreach ($lookin as $look1 => $look2) {
					if ($look2 == $stopserial)
						$ssfound=true;
				}
				if ($ssfound)
				{
					if ($debug) echo "Already got it<Br>";
				}
				else {
					$added=false;
					unset($firststop);
					foreach ($lookin as $look1 => $look2) if (is_numeric($look2)) {
							if (!isset($firststop))
								$firststop=$look2;
							$newarr[]=$look2;
							if ($look2 == $prevstop && $lookin[$look1+1] == $nextstop) {
								$added=true;
								$newarr[]=$stopserial;	
								if ($debug) echo "Added after position $look1 (stop $look2) between $nextstop and $prevstop<br>";

							}
							if ($look2 == $nextstop && $lookin[$look1+1] == $prevstop) {
                                                                $added=true;
                                                                $newarr[]=$stopserial;
								if ($debug) echo "Added after position $look1 (stop $look2) between $nextstop and $prevstop<br>";
                                                        }
							$laststop=$look2;
					}
					if (!$added) {
						if ($debug) echo "grid is from $firststop to $laststop - compare to $prevstop/$nextstop for end of trip<br>";
						if ($debug) echo "if (($nextstop==$laststop && $prevstop==-1) || ($prevstop==$laststop && $nextstop == -1)  )<br>";

						if (($nextstop==$laststop && $prevstop==-1) || ($prevstop==$laststop && $nextstop == 999)  )
						{
							$newarr[]=$stopserial;
							if ($debug) echo "Yes - end of grid list<br>";
						}
						else {
							if ($debug) echo "No - beginning of grid list<br>";
							$newarr[-1]=$stopserial;
							ksort($newarr,SORT_NUMERIC);
						}
					}
					$newval=implode(",",$newarr);
					if ($debug) echo "changed from $stopserials to<Br>$newval<br>";
					ora_parse($cursor,"update master_other_price_gridnames set stop_Serials='$newval' where grid_name_serial=$grid");
			                ora_Exec($cursor);
				}
				
	
			}
/*  			if (getenv("REMOTE_USER")=="Keith") {

				echo "ROLLBACK";
				ora_rollback($conn);
				exit;
			}
*/

//			echo "<br><a target='_blank' href=other_price_grid.phtml?master=$grid>Edit Price Grid</a><br>";
		}
	}

     }
}
echo "<script language=javascript>";
echo "alert('Remember to add the prices for\\r\\nthe stop you just added.\\r\\n Price Packet');";
echo "</script>";
echo "<font size=+3 color=red style='background: yellow; border: 3px black bold'>Remember to add the prices for the stop you just added. Price Packet</font><br>";
}


if ($stopadded && false) { // this removed
  echo "<hr>";
  $today=date("Ymd");
  $xtra=" and valid_to>=$today ";
  $kcur=ora_open($conn);
  ora_parse($cursor,"select price_group_serial,valid_from,valid_to,days,pred_yield from price_group where route_serial=$serial $xtra order by valid_from,price_group_serial");
  ora_exec($cursor);
  $lastpgs="";
  unset($data);
  echo "<table border=1><tr><td>Price Serial</td><td>Date Range</td><td>Days of week</td><td>Max Price</td><td>Pred Yield</td></tr>";
  $lookfor=$today;
  while (ora_fetch_into($cursor,$data)) {
        ora_parse($kcur,"select 'x' from price_data where price_group_serial=$data[0] and price_class='X'");
        ora_exec($kcur);
        if (ora_fetch($kcur))
                echo "<tr bgcolor=yellow>";
        else
                echo "<tr>";
        echo "<td>";
        if ($data[0]!=$lastpgs) {
                $last5=sprintf("%05d",$data[0]%10000);
//              echo "<a href=modify_new_prices.phtml?pgs=$data[0]&rs=$serial>$last5</a> Copy:<input type=radio name=copyfrom value=$data[0]>";
                $lastpgs=$data[0];
        }
//      else
//              echo "&nbsp;";
        echo "<a target='_blank' href='modify_new_prices.phtml?pgs=$data[0]&rs=$serial&highlight=$highlight&returnto=$returnto'>$last5</a> ";

        $data[3]=str_replace(" ","-",$data[3]);
        echo "</td><td>";
        if ($data[1]==$lookfor && $data[2]==$lookfor) {
                $lookfor=nextdate($lookfor);
        }
        if ($data[1]<=$today && $data[2]>=$today)
                echo "<font color=red>";
        if (is_numeric($highlight))
          if ($data[1]<=$highlight && $data[2]>=$highlight)
                echo "<font color=red style='background:yellow'>";

        echo substr($data[1],6,2)."/".substr($data[1],4,2)."/".substr($data[1],0,4)." - ".substr($data[2],6,2)."/".substr($data[2],4,2)."/".substr($data[2],0,4);
        echo "</td><td><font face='Courier New'>$data[3]</td><td align=right>";

        ora_parse($tcursor,"select max(price) from ( select max(price) price  from price_data where price_group_serial=$data[0] union select max(price) price from price_other where price_group_serial=$data[0])");
        ora_exec($tcursor);
        if (ora_fetch($tcursor))
                echo getdata($tcursor,0);
        else
                echo "&nbsp;";

        echo "<td align=right>$data[4]</tR>";
        unset($data);
  } // while
  echo "</table>";
  
  echo "<hr>";
//  echo "<a href=modify_route_stops.phtml?serial=$serial><font size=+2 style='background: yellow'>Back to Stops</font></a> or <a href=modify_route_2.phtml?serial=$serial>Back to Modify Route</a>";
//  exit;
} // stopadded

if ($doupdate=="Delete")
{
$cnt=0;
$done=false;
$tcursor = ora_Open($conn);
/*
ora_parse($cursor,"select * from shop_basket where go_from='$DeleteStop' or go_to='$DeleteStop'");
ora_exec($cursor);
if (ora_fetch($cursor)):
	$stopqry = "select name||' '||lastname from user_details A where exists (select 1 from shop_basket B where B.user_serial=A.user_serial and go_from='$DeleteStop' or go_to='$DeleteStop')";
	echo "<br><bR><b>$stopqry</b><br><br>";
	ora_parse($tcursor, $stopqry);
	ora_exec($tcursor);
	ora_fetch($tcursor);
	echo "<font color='#FF0000'>";
	echo "User ";
	echo chop(ora_getColumn($tcursor,0));
	echo " has stop $DeleteStop in his/her shopping basket.<br>";
	echo "Stop $DeleteStop has NOT been deleted.<br>";
	echo "</font>";
	$done=true;
endif;
*/

ora_parse($cursor,"select * from route_stops where route_serial='$serial' and short_name='$DeleteStop'");
ora_exec($cursor);
if (!ora_fetch($cursor)):
	echo "Can not find the stop you want to delete.<br>";
	$done=true;
endif;

if (!$done)
{
	$dsn = chop(ora_getColumn($cursor,9));
	ora_parse($cursor,"delete from route_stops where route_serial='$serial' and short_name='$DeleteStop'");
	if (ora_exec($cursor)):
		echo "Stop $DeleteStop has been deleted.<br>";
	else:
		echo "Stop $DeleteStop could not be deleted.<br>";
		$done=true;
	endif;

	if (!$done)
	{
		ora_parse($cursor,"select * from route_stops where route_serial='$serial' order by stop_order asc");
		ora_exec($cursor);
		while(ora_fetch($cursor))
		{
			$qsn = chop(ora_getColumn($cursor,4));
			$qso = chop(ora_getColumn($cursor,9));

			if ($qso>$dsn):
				$newso = $qso-1;
				$tcqry = "update route_stops set stop_order=$newso where route_serial='$serial' and short_name='$qsn'";
				$querylist[$cnt]=$tcqry;
				$changes_made = true;
				$cnt++;
			endif;
		}

		$i=0;
		while ($i<$cnt)
		{
			ora_parse($cursor,$querylist[$i]);
			ora_exec($cursor);
			$i++;
		}
	
		ora_parse($cursor,"delete from route_prices where route_serial='$serial' and (go_from='$DeleteStop' OR go_to='$DeleteStop')");
		ora_exec($cursor);
	}
}
ora_close($tcursor);
}


if ($changes_made) {

	$todaysdate=date("Ymd");
	$twolegfound = false;
	ora_parse($cursor,"select route_no, route_serial from route_details where date_to>=$todaysdate and (sub_route_one='$serial' or sub_route_two='$serial')");
	ora_exec($cursor);
	while (ora_fetch($cursor)) {
		$child_rs = getdata($cursor,1);
		$child_rn = getdata($cursor,0);
		if (!$twolegfound) {
			echo "<script> alert('This is a sub route... please see warning in red'); </script>";
			$twolegfound = true;
			echo "<hr><b><u><font color=red>WARNING!  Make sure you check out combined routes:</font></u></b><br>";
		}
		echo "<a href=modify_route_2.phtml?serial=$child_rs>Route $child_rn  (#$child_rs) uses this as a subroute</a><br>";

	}
	if ($twolegfound)
		echo "<hr>";
}


$delstops=array();
ora_parse($cursor,"Select shortname from stop_details2 where active='N'");
ora_Exec($cursor);
while (ora_fetch($cursor)) {
        $delstops[getdata($cursor,0)]=true;
}



if ($subroute)
{
        ora_parse($cursor,"select short_name, route_no, stop_order from route_stops where route_serial='$serial1' order by stop_order");
        ora_exec($cursor);
        ora_fetch($cursor);
        $sr1firststop = chop(ora_getColumn($cursor,0));
        $sr1routeno = chop(ora_getColumn($cursor,1));


        while (ora_fetch($cursor)) {
                $sr1laststop = chop(ora_getColumn($cursor,0));
		if ($sr1laststop==$crossover_stop)
	                $gothru[$sr1laststop]=getdata($cursor,2);
//              echo "Set $sr1laststop<Br>";
        }
	$gotconnect="";
        ora_parse($cursor,"select short_name, route_no, stop_order from route_stops where route_serial='$serial2' order by stop_order");
        ora_exec($cursor);
        ora_fetch($cursor);
        $sr2firststop = chop(ora_getColumn($cursor,0));
        $sr2routeno = chop(ora_getColumn($cursor,1));
        if (isset($gothru[$sr2firststop]))
	{
                $gotconnect=$sr2firststop;
		$switch1=$gothru[$sr2firststop];
                $switch2=getdata($cursor,2);

	}
 //     else
//              echo "$sr2firststop is not connect<bR>";
        while (ora_fetch($cursor)) {
                $sr2laststop = chop(ora_getColumn($cursor,0));
                if (/*$gotconnect=="" &&*/ isset($gothru[$sr2laststop]))
		{
                        $gotconnect=$sr2laststop;
			$switch1=$gothru[$sr2laststop];
			$switch2=getdata($cursor,2);
		}
        }

$qry="select  route_serial,route_no,date_from,date_to,short_name,arrive_time,depart_time,next_day,major_stop,stop_order,passport,pass_fee,day_after,crossover,rsatime,print_manifest,shuttle,alternate_stop,stop_notes from route_stops where route_serial='$serial1' and stop_order<=$switch1  union select route_serial,route_no,date_from,date_to,short_name,arrive_time,depart_time,next_day,major_stop,stop_order+100,passport,pass_fee,day_after,crossover,rsatime,print_manifest,shuttle,alternate_stop,stop_notes  from route_stops where route_serial='$serial2' and stop_order>=$switch2  order by 10 asc";
ora_parse($cursor,$qry);
//echo "$qry<BR>";
}
else
ora_parse($cursor,"select * from route_stops where route_serial='$serial' order by stop_order asc");
ora_exec($cursor);
?>

<TABLE border=0 bgcolor='#<? echo $table_bg ?>'>
<tr bgcolor='#<? echo $table_title?>'>
<td>No</td>
<td>Stop<br>Name</td>
<td>Days<br>After Start</td>
<td>Arrive<br>Time</td>
<td>Depart<br>Time</td>
<td>Next<br>Day</td>
<td>Major<br>Stop</td>
<td>Cross<br>Over</td>
<td>Manifest<br>Prt</td>
<td>Shuttle</td>
<td>Change <font size=2>to shuttle</font> @</td>
<td>Country<br>Time</td>
<td>Passport</td>
<td>Notes for driver</td>
<td>Incl<br>Start</td>
<td>Incl<br>End</td>
<td>New<br>Partn</td>
</tr>

	<?
	$reccnt=0;
	$i=0;
	$stops_so_far=array();
	$lastnumber="";
	while (ora_fetch($cursor))
	{
		if (getdata($cursor,9)>99)
			echo "<tr bgcolor='#$table_alt'>";
		else
			echo "<tr bgcolor='#$table_cell'>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,9);
			else {
			echo "<input size=3 type=text name=stp_$i value='";
			echo chop(ora_getColumn($cursor,9));
			echo "'>";
			}
		if (getdata($cursor,9)==$lastnumber) {
			echo "<font color=red><b>DUPE!!</b></font>";
		}
		$lastnumber=getdata($cursor,9);
		echo "</td>";
		echo "<td>";
			if (isset($delstops[chop(ora_getColumn($cursor,4))]))
				echo "<font color=red>* ";
			echo chop(ora_getColumn($cursor,4));
			if ($printable!="Y") {
			echo "<input type=hidden name='short_$i' value='";
			echo chop(ora_getColumn($cursor,4));
			echo "'>";
			}
		echo "</td>";
		echo "<td>";
			echo chop(ora_getColumn($cursor,12));
		echo "</td>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,5);
			else {
			echo "<input size=5 type=text name=arr_$i value='";
			echo chop(ora_getColumn($cursor,5));
			echo "'>";
			}
		echo "</td>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,6);
			else {
			echo "<input size=5 type=text name=dep_$i value='";
			echo chop(ora_getColumn($cursor,6));
			echo "'>";
			}
		echo "</td>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,7);
			else {
			echo "<input type=checkbox name=nextday_$i value='Y'";
			if (chop(ora_getColumn($cursor,7))=="Y"):
				echo " checked ";
			endif;
			echo ">";
			}
		echo "</td>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,8);
			else {
			echo "<input type=checkbox name=major_$i value='Y'";
			if (chop(ora_getColumn($cursor,8))=="Y"):
				echo " checked ";
			endif;
			echo ">";
			}
		echo "</td>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,13);
			else {
			echo "<input type=checkbox name=crossover_$i value='Y'";
			if (chop(ora_getColumn($cursor,13))=="Y"):
				echo " checked ";
			endif;
			echo ">";
			}
		echo "</td>";
			echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,15);
			else {
					echo "<input type=checkbox name=print_m_$i value='Y'";
					if (chop(ora_getColumn($cursor,15))=="Y"):
							echo " checked ";
					endif;
					echo ">";
			}
			echo "</td>";
			echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,16);
			else {
					echo "<input type=checkbox name=shut_$i value='Y'";
					if (chop(ora_getColumn($cursor,16))=="Y"):
							echo " checked ";
					endif;
					echo ">";
			}
			echo "</td>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,22);
			else {
				if (chop(ora_getColumn($cursor,16))=="Y") {
				echo "<select name=chng_$i><option value=''>NONE (Shuttle First)<option value='$previous_stop'>$previous_stop";
				echo makeselect2 ($stops_so_far,getdata($cursor,22));
				echo "</select>";
				} else echo "n/a";
			}
		echo "</td>";

		$stops_so_far[getdata($cursor,4)]=getdata($cursor,4);
		if (chop(ora_getColumn($cursor,16))!="Y")
			$previous_stop=getdata($cursor,4);


		echo "<td>";

			
			$rsa = ora_open($conn);

			if ($printable!="Y")
			echo "<select name=rsatime_$i>";
			$ctme = getdata($cursor,14);
			ora_parse($rsa,"select * from gmt_info where code='$ctme'");
			ora_exec($rsa);
			if (ora_fetch($rsa)):
				if ($printable=="Y")
					echo getdata($rsa,1);
				else
					echo "<option value='$ctme'>", getdata($rsa,1),"</option>";
			endif;
			if ($printable!="Y") {
			ora_parse($rsa,"select * from gmt_info where code<>'$ctme' order by country");
			ora_exec($rsa);
			while(ora_fetch($rsa)):
				
				echo "<option value='", getdata($rsa,0), "'>";
				echo getdata($rsa,1), "</option>";
			endwhile;
			}

			if ($printable!="Y")
				echo "</select>";

			ora_close($rsa);
		echo "<td>";
			if ($printable=="Y")
					echo getdata($cursor,10);
			else {
			echo "<input type=checkbox name=passport_$i value='Y'";
			if (chop(ora_getColumn($cursor,10))=="Y"):
				echo " checked ";
			endif;
			echo " onclick='javscript:clearbox(pfee_$i,passport_$i)'>";
			}
		echo "</td>";
		echo "<td><input type=hidden name=pfee_$i value=0>";
			if ($printable=="Y")
				echo getdata($cursor,18);
			else {
			echo "<input size=30 type=text name=snotes_$i maxlength=80 value='";
			echo chop(ora_getColumn($cursor,18));
			echo "'>";
			}
		echo "</td>";

		if (!$subroute) {
			echo "<td>";
					if ($printable=="Y")
							echo getdata($cursor,19);
					else {
					echo "<input type=checkbox name=pstart_$i value='Y'";
					if (chop(ora_getColumn($cursor,19))=="Y"):
							echo " checked ";
					endif;
					echo ">";
					}
			echo "</td>";
			echo "<td>";
					if ($printable=="Y")
							echo getdata($cursor,20);
					else {
					echo "<input type=checkbox name=pend_$i value='Y'";
					if (chop(ora_getColumn($cursor,20))=="Y"):
							echo " checked ";
					endif;
					echo ">";
					}
			echo "</td>";

			echo "<td>";
					if ($printable=="Y")
							echo getdata($cursor,21);
					else {
					echo "<input type=checkbox name=partn_$i value='Y'";
					if (chop(ora_getColumn($cursor,21))=="Y"):
							echo " checked ";
					endif;
					echo ">";
					}
			echo "</td>";
		}

	echo "</tr>";
	$i++;
	}
	$reccnt=$i;

	?>
	</table>
<? if ($subroute)
	exit;
?>
<br><input type=submit name=doupdate value="Update"><br><br>

<table border=0>
<tr>
<td>Add</td>
<td><select name="newstopname">
<?
	ora_parse($cursor,"select * from stop_details where active='Y' order by short_name");
	ora_exec($cursor);
	while (ora_fetch($cursor))
	{
		echo "<option value='";
		echo chop(ora_getColumn($cursor,0));
		echo "'>";
		echo chop(ora_getColumn($cursor,0));
		echo " - ";
		echo chop(ora_getColumn($cursor,1));
		echo "</option>";
	}
?>
</select></td>
<td>After Stop:</td>
<td><select name="newstopmarker">
<?
	$i=0;
	$i_to=$reccnt;
	while ($i<=$i_to)
	{
		echo "<option value='$i'>$i</option>";
		$i++;
	}
?>
</select></td><td>
<input type=submit name=doupdate value="Add Stop"></td>
</tr>

<tr>
<td>Delete</td>
<td>
<select name='DeleteStop'>
<?
	ora_parse($cursor,"select short_name from route_stops where route_serial='$serial' order by short_name");
	ora_exec($cursor);
	while (ora_fetch($cursor))
	{
		echo "<option value='";
		echo chop(ora_getColumn($cursor,0));
		echo "'>";
		echo chop(ora_getColumn($cursor,0));
		echo "</option>";
	}

?>
</select></td><td></td><td></td><td>
<input type=submit name=doupdate value='Delete'></td>
</td>
</table>
<br>
<input type=hidden name=serial value="<? echo $serial ?>">
<input type=hidden name=passrn value="<? echo $passrn ?>">
<input type=hidden name=passft value="<? echo $passft ?>">
<input type=hidden name=numrecs value="<? echo $reccnt ?>">
</form>

<form method=post action=modify_route_2.phtml>
<input type=hidden name=serial value="<? echo $serial ?>">
<input type=submit value="Back to Modify Route #<?echo $serial?>">
</form>
<?
echo "<input type=hidden name=passrn value='$passrn'>";

/*
  // check grid
        ora_parse($cursor,"select other_price_grid from route_details where route_serial='$serial' and other_price_grid is not null");
        ora_exec($cursor);
        if (ora_fetch($cursor)) {
                $grid=getdata($cursor,0);
		  echo "<hr><a target='_blank' href=other_price_grid.phtml?master=$grid>Edit Other-Other Price Grid</a><br>";
	}

*/

?>
</BODY>
</HTML>
<? close_oracle() ?>
