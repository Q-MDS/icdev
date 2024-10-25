<?
/* *********** THIS WAS WHILE I WAS DEBUGGING THE AVAILABILITY, NO LONGER USED, */
/* *********** HAS BEEN INCLUDED IN MISC.INC */

function availseats2($coachserial, $gofrom, $goto, $debug=true)
{
	if ($debug) { echo "From: $gofrom to $goto<br>"; };
	global $conn;
	$ascur = ora_open($conn);

	$qry = "select A.stop_order, A.route_serial, B.max_seats from route_stops A, open_coach B where A.route_serial = B.route_serial and B.coach_serial='$coachserial' and A.short_name='$goto'";
	ora_parse($ascur, $qry);
	if (!ora_exec($ascur)):
		echo "$qry<br>";
		exit;
	endif;
	$endat = chop(ora_getColumn($ascur,0));
	$rs = chop(ora_getColumn($ascur,1));
	$max = chop(ora_getColumn($ascur,2));

	if ($debug):
		echo "route serial: $rs<br>";
		echo "End Stop No : $endat<br>";
		echo "Max Seats Av: $max<br>";
	endif;

	$total =0;
	$i=0;
	while ($i<$endat):
		$stopno = $i+1;
		$qry = "select sum(numtickets) from tickets where coach_serial='$coachserial' and go_from = (select short_name from route_stops where route_Serial='$rs' and stop_order=$stopno)";
		ora_parse($ascur,$qry);
		if (!ora_exec($ascur)):
			echo "$qry<br>";
			exit;
		endif;
		ora_fetch($ascur);
		$total+= chop(ora_getColumn($ascur,0));

		$qry = "select sum(numtickets) from tickets where coach_serial='$coachserial' and go_to = (select short_name from route_stops where route_Serial='$rs' and stop_order=$stopno)";
		ora_parse($ascur,$qry);
		if (!ora_exec($ascur)):
			echo "$qry<br>";
			exit;
		endif;
		ora_fetch($ascur);
		$total-= chop(ora_getColumn($ascur,0));

		$asarray[$i] = $total;
		$i++;
	endwhile;
	
	rsort($asarray);
	if ($debug):
		$i=0;
		while ($i<count($asarray)):
			echo $asarray[$i], "<Br>";
			$i++;
		endwhile;
	endif;

	ora_close($ascur);

	return $max - $asarray[0];
}
