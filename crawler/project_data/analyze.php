<?

require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");


$thelog=array();

function logit ($to,$sub,$body) {
	$outfile=fopen("/tmp/ctkerr.log","a+");
	fputs($outfile,"$body\n");
	fclose($outfile);

}

if (!open_oracle()) { Exit; };

$gotic=false;

$globfrom=strtoupper($argv[1]);
$globto=strtoupper($argv[2]);

$globfrom=str_replace("'","`",$globfrom);
$globfrom=str_replace("\\","",$globfrom);

$globto=str_replace("'","`",$globto);
$globto=str_replace("\\","",$globto);


	function is_service ($routeno,$date,$from="",$to="") {
		global $cursor,$conn;

                        $av="";
                        if ($routeno==0 || $routeno=="0000")
			{
				$from=strtoupper($from);
				$to=strtoupper($to);
				if ($from=="") {
					echo "THIS SHOULD NOT HAPPEN\n";
					return true; /// WE DONT KNOW..!!
				} 
				if (date("Ymd")==$date)
					ora_parse($cursor,"select max (depart_time) from route_stops where short_name='$from' and route_Serial in (
select A.route_Serial from route_stops A, route_stops B, open_coach C  where A.route_serial=B.route_serial and A.short_name='$to' and B.short_name='$from'
and A.route_Serial=C.route_serial and C.run_date=$date and is_open='Y' and max_seats>0 and A.stop_order>B.stop_order) ");
				else
					ora_parse($cursor,"select A.route_Serial from route_stops A, route_stops B, open_coach C  where A.route_serial=B.route_serial and A.short_name='$to' and B.short_name='$from' and A.route_Serial=C.route_serial and C.run_date=$date and is_open='Y' and max_seats>0 and A.stop_order>B.stop_order ");

				ora_exec($cursor);
				if (ora_fetch($cursor)) {
					if (date("Ymd")==$date) {
							$start=getdata($cursor,0);
                                                        echo "Start time $start\n";
                                                        if ($start<date("Hi")) {
                                                                echo "BUS $routeno today ($date) has left already ($start)\n";
                                                                return false;
                                                        }
					} 
					echo "Bus $routeno is running on $date from $from to $to\n";
					return true;
				} else {
					echo "No departure for $route on $date\n";
					return false;
				}

			}
                        else {

                                $routeno=sprintf("%04d",$routeno);
                                ora_parse($cursor,"select coach_serial,route_serial from open_coach where route_no='$routeno' and run_date=$date order by is_open desc");
                                ora_exec($cursor);
                                if (ora_fetch($cursor)) {
                                        $cs=getdata($cursor,0);
					$rs=getdata($cursor,1);
                                        $av=availseats($cs,$key,$key2);
					if ($av>0)
					{
						// check time...
						if (date("Ymd")==$date) {
						  echo "this is for today: select  depart_time from route_stops where route_serial='$rs' order by stop_order\n";
						  ora_parse($cursor,"select depart_time from route_stops where route_serial='$rs' order by stop_order");
						  ora_exec($cursor);
						  if (ora_fetch($cursor)) {
							$start=getdata($cursor,0);
							echo "Start time $start\n";
							if ($start<date("Hi")) {
								echo "BUS $routeno today ($date) has left already ($start)\n";
								return false;
							}
						  }
						} // date
						else echo "This is Not for today\n";
		
						echo "IC still has $av seats on $routeno on $date\n";
						return true;
		
					}
					else
					{
						echo "IC sold out $routeno on $date\n";
						return false;
					}


                                }
                                else return false;
                        }
	} // is_service

/*
echo "Arguments:\n";
print_r($argv);
echo "\n";


    [1] => Johannesburg
    [2] => Port Elizabeth
    [3] => 0
    [4] => 2105

*/

$rn=$argv[4];
$rn=sprintf("%04d",$rn);


$now=date("YmdHis");
$data=explode("<a href=",file_get_contents("ctk5.html"));

$then=date("Ymd",time()+$argv[3]*86400);

if ($argv[5]=="NoService") {
       $date=date("YmdHis")."_$rn";
//        system("mkdir $date; cp -dp * $date ; cp /tmp/doctk.* $date");

        if (is_service($rn,$then,$argv[1],$argv[2])) {
		echo "THERE IS A SERVICE!!";
		require_once("../php3/opstimes.inc");
		$therundate=afrikdate($then,true);

		$qry = "SELECT email_address FROM email_reports WHERE department_id='C' AND active='Y'"; 
		$harvest="";

		ora_parse($cursor,$qry);  ora_exec($cursor);

		while (ora_fetch_into($cursor, $data, ORA_FETCHINTO_ASSOC)){
		        $harvest .= $data['EMAIL_ADDRESS'].",";
		        unset($data);
		}

		$harvest=substr($harvest,0,-1);
	
		logit($harvest,"Computicket? $argv[1] to $argv[2] on $therundate - no carriers","Please check CTK from $argv[1] to $argv[2] on $therundate - no carriers\n\nDebug info in $date");
	}
	else 
		echo "No service, so we dont worry\n";

	exit;
}

$position=0;
while (list($dkey,$dval)=each($data)) {
	$lines=explode(">",$dval);
	while (list($lineno,$line)=each($lines)) {
		if (strstr($line,"/strong")) {
			$line=str_replace("GRAAFF - REINET","GRAAFF REINET",$line);
			$dt=explode(" - ",$line,2);
/*
			if (!isset($globfrom))	
			{
				$bb=explode("(",trim($dt[0]));
				$globfrom=trim($bb[0]);
			}
*/
			$entry[FROM]=$globfrom;

			$bits=explode("(R",$dt[1]);
			$bits2=explode(")",$bits[1]);

/*			if (!isset($globto))
			{
				$bb=explode("(",trim($bits[0]));
				$globto=trim($bits[0]);
			}
*/
			$entry[TO]=$globto;
			$entry[PRICE]=trim($bits2[0]);
//			echo "Headline gave from $entry[FROM] to $entry[TO] for R$entry[PRICE]<br>\n";
		} elseif (strstr( $line,":")) {
			$dt=explode(":",$line,2);
			$bits=explode("<",$dt[1]);
			$dt[0]=trim($dt[0]);
			if ($dt[0]=="Depart" || $dt[0]=="Arrive") {
				$bits[0]=date("YmdHi",strtotime(trim($bits[0]))); // ." (Was $bits[0])";
			}
			elseif ($dt[0]=="Duration") {
				$bits[0]=str_replace("hours","h",$bits[0]);
				$bits[0]=str_replace("minutes","m",$bits[0]);
			}
			$entry[trim($dt[0])]=trim($bits[0]);
//			echo "$line gave me $dt[0] = $dt[1]<bR>\n";
		}
//		else echo "Ignoring $line<bR>\n";
	} // while
//	echo "<hr>";
	if (isset($entry[FROM]) ) {
		$position++;
		$entry[POSITION]=$position;
		print_r($entry);
		echo "<hr>\n";
		switch ($entry[Carrier]) {
			case "S A Roadlink":  $carrier="R";  break;
			case "Intercape":  $carrier="M"; $gotic=true; break;
			case "Intercape Sleepliner"; $gotic=true; $carrier="S"; break;
			case "Translux"; $carrier="T"; break;
			case "Greyhound"; $carrier="G"; break;
			case "Citiliner"; $carrier="g"; break;
			case "City To City"; $carrier="t"; break;
			case "Intercity Express"; $carrier="X"; break;


			default:	$carrier=$entry[Carrier][0];
		} //switch
                if (!isset($carriers[$entry[Carrier]]))
                        $carriers[$entry[Carrier]]=$carrier;

		$qry="insert into ctk_log values (to_date('$now','YYYYMMDDHH24MISS'),'$carrier',$position,'$entry[FROM]','$entry[TO]',$entry[PRICE],to_date('$entry[Depart]','YYYYMMDDHH24MI'),to_date('$entry[Arrive]','YYYYMMDDHH24MI'),'$entry[Duration]',$then,'$rn')";
		echo "$qry\n";
		ora_parse($cursor,$qry);
		ora_exec($cursor);
	
	}
	else  {
//		echo "<font color=red>";
//		print_r($lines);
//		echo "</font><bR>\n";
	}
	unset($entry);

	
} // list 

echo "\n\n";
if (!is_array($carriers)) {
	$date=date("YmdHis")."_$rn";
//	system("mkdir $date; cp -dp * $date ; cp /tmp/doctk.* $date");
	echo "\n\nNothing to show... exiting ($rn) see $date\n\n\n";
       if (is_service($rn,$then,$argv[1],$argv[2])) {
                echo "THERE IS A SERVICE!!";
                require_once("../php3/opstimes.inc");
                $therundate=afrikdate($then,true);

                $qry = "SELECT email_address FROM email_reports WHERE department_id='C' AND active='Y'";
                $harvest="";

                ora_parse($cursor,$qry);  ora_exec($cursor);

                while (ora_fetch_into($cursor, $data, ORA_FETCHINTO_ASSOC)){
                        $harvest .= $data['EMAIL_ADDRESS'].",";
                        unset($data);
                }

                $harvest=substr($harvest,0,-1);

                logit($harvest,"Computicket? $argv[1] to $argv[2] on $therundate - no carriers","Please check CTK from $argv[1] to $argv[2] on $therundate - no carriers\n\nDebug info in $date");

        }
        else
                echo "No service, so we dont worry\n";

        exit;


}

if (!$gotic) {
	echo "No Intercape $then $rn\n";
        if (is_service($rn,$then,$argv[1],$argv[2])) {
                echo "THERE IS A SERVICE!!";
                require_once("../php3/opstimes.inc");
                $therundate=afrikdate($then,true);

                $qry = "SELECT email_address FROM email_reports WHERE department_id='C' AND active='Y'";
                $harvest="";

                ora_parse($cursor,$qry);  ora_exec($cursor);

                while (ora_fetch_into($cursor, $data, ORA_FETCHINTO_ASSOC)){
                        $harvest .= $data['EMAIL_ADDRESS'].",";
                        unset($data);
                }

                $harvest=substr($harvest,0,-1);

                logit($harvest,"Computicket? $argv[1] to $argv[2] on $therundate - No Intercape","Please check CTK from $argv[1] to $argv[2] on $therundate - No Intercape");

        }
        else
                echo "No service, so we dont worry about an alert\n";
}

print_r($carriers);
reset($carriers);
while (list($key,$val)=each($carriers)) {
	$fname="Carrier $key $val";
	$fname=str_replace(" ","_",$fname);
	$fname2="";
	for ($a=0;$a<strlen($fname);$a++)
		if ($fname[$a]=="_" || ($fname[$a]>="a" && $fname[$a]<="z") || ($fname[$a]>="A" && $fname[$a]<="Z"))
			$fname2.=$fname[$a];
	if (!file_exists($fname2))
	{
		$outfile=fopen($fname2,"w+");
		echo "Created $fname2  (Was $fname, was $key.$val)<br>\n";
		fclose($outfile);	

	}
}



?>
