<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

// Ajust for 7, 14, 21
if (!isset($tot_days) || !is_numeric($tot_days)) {
        $tot_days = 7;
}
if ($tot_days > 93) {
        $tot_days = 93;
}
if (!isset($start_date)) {
        $start_date = date("Y-m-d");
}

$compare_list = array();
$carrier_list = array();
$carriers_not_found = array();
$ctk_stops = array();
$batch = array();


set_time_limit(0);

function get_single_list($route, $from, $to)
{
        global $conn;

        $sql = "SELECT
                '$route' AS ROUTE,
                stop_from_table.STOP_NUMBER AS STOP_FROM,
		stop_from_table.STOP_NAME as FROM_NAME,
                stop_from_table.STOP_ZAZA AS STOP_FROM_NAME,
                stop_to_table.STOP_NUMBER STOP_TO,
		stop_to_table.STOP_NAME as TO_NAME,
                stop_to_table.STOP_ZAZA AS STOP_TO_NAME
        FROM
                CTK_STOPS stop_from_table, CTK_STOPS stop_to_table
        WHERE
                (stop_from_table.stop_number='$from')
        AND
                (stop_to_table.stop_number='$to')
        ";

        $cursor = oci_parse($conn, $sql);
        oci_execute($cursor);

        while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS))
        {
                $compare_list[] = $row;
        }

        oci_free_statement($cursor);

        return $compare_list;
}


function get_compare_list()
{
	global $conn;

	$yesterday = date("Ymd",time()-86400);

	$sql = "SELECT 
		main_table.ROUTE,
		main_table.STOP_FROM,
		stop_from_table.STOP_NAME AS FROM_NAME,
		stop_from_table.STOP_ZAZA AS STOP_FROM_NAME,
		main_table.STOP_TO,
		stop_to_table.STOP_NAME AS TO_NAME,
		stop_to_table.STOP_ZAZA AS STOP_TO_NAME
	FROM 
		CTK_COMPARE main_table
	LEFT JOIN 
		CTK_STOPS stop_from_table ON main_table.STOP_FROM = stop_from_table.STOP_NUMBER
	LEFT JOIN 
		CTK_STOPS stop_to_table ON main_table.STOP_TO = stop_to_table.STOP_NUMBER 
	WHERE main_table.route in (select distinct to_number(route_no) from open_coach where run_date>=$yesterday)
	";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	$compare_list = array();

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$compare_list[] = $row;
	}

	if (count($compare_list)==0) {
		echo "No rows: $sql<br>";
	}

	oci_free_statement($cursor);

	return $compare_list;
}

function carrier_list()
{
	global $conn;

	$sql = "SELECT * FROM CTK_CARRIERS";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$carrier_list[] = $row;
	}

	oci_free_statement($cursor);

	return $carrier_list;
}

function get_ctk_stops()
{
	global $conn;

	$results = array();

	$sql = "SELECT stop_zaza FROM CTK_STOPS";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$results[] = $row['STOP_ZAZA'];
	}

	oci_free_statement($cursor);

	return $results;
}

function build_batch($compare_list)
{
	global $tot_days, $start_date;

	$dup_array = array();

	$today = $start_date;

	foreach($compare_list as $compare)
	{
		$route = $compare['ROUTE'];
		$from_name = TRIM($compare['FROM_NAME']);
		$from = TRIM($compare['STOP_FROM_NAME']);
		$to_name = TRIM($compare['TO_NAME']);
		$to = TRIM($compare['STOP_TO_NAME']);

		if ($from != "" && $to != "") 
		{
			$str = $from . "@@" . $to . "@@" . $from_name . "@@" . $to_name;
	
			$dup_array[$str][] = $route;
		}
	}

	foreach($dup_array as $key => $routes)
	{
		$from_to = explode('@@', $key);
		$from = $from_to[0];
		$to = $from_to[1];
		$from_name = $from_to[2];
		$to_name = $from_to[3];
		$route_str = "";

		foreach ($routes as $route) 
		{
			$route = sprintf("%04d", $route);
			$route_str .= $route . ",";
		}
		$route_str = rtrim($route_str, ',');

		for ($i=0; $i < $tot_days; $i++) 
		{ 
			$date = date('Y-m-d', strtotime($start_date . ' + ' . $i . ' days'));

			$batch[] = array(
				'route' => $route_str,
				'from' => $from,
				'to' => $to,
				'from_name' => $from_name,
				'to_name' => $to_name,
				'date' => $date
			);
		}
	}
	
	// echo "Batch count: " . count($batch) . "\n";
	// print_r($batch);

	return $batch;
}

function start($trips)
{
	// *** Add new carrier: New carrier is added in process data function: Lines 144 have been commented out during testing
	// global $carriers_not_found;
	// *** Add new carrier: New carrier is added in process data function: Lines 144 have been commented out during testing
	
	$start_ts = time();

	log_event("--- START --------------------------------------------------------------------------------------------------------------------------------" . "\r\n[" . $timestamp = date('Y-m-d H:i:s') ."]" . "\r\n");

	foreach ($trips as $trip)
	{
		crawl($trip['route'], $trip['from'], $trip['to'], $trip['date'], $trip['from_name'], $trip['to_name']);
		// analyse();
	}
	
	// *** Add new carrier: New carrier is added in process data function: Lines 156 - 166 have been commented out during testing
	/*$carriers_not_found = array_unique($carriers_not_found);
	$carriers_not_found = array_values($carriers_not_found);
	
	if (count($carriers_not_found) > 0)
	{
		foreach ($carriers_not_found as $carrier) 
		{
			addToCtkCarriers($carrier);
		}
		log_event("Added new carriers to CTK_CARRIERS " . json_encode($carriers_not_found));
	}*/

	log_event("--- END ----------------------------------------------------------------------------------------------------------------------------------" . "\r\n");

	$end_ts = time();

	$duration = $end_ts - $start_ts;
	$hours = floor($duration / 3600);
	$minutes = floor(($duration % 3600) / 60);
	$seconds = $duration % 60;

	echo "Completed " . date("Y-m-d H:i:s") . " Took: {$hours} hours, {$minutes} minutes, {$seconds} seconds" . "\n";
}

function crawl($route_no, $ctk_from, $ctk_to, $ctk_date, $from_name, $to_name)
{
	$data = json_encode(['from' => $ctk_from, 'to' => $ctk_to, 'date' => $ctk_date]);

	echo "Connecting to 3001<bR>";
	$ch = curl_init('http://10.50.0.180:3001/run-capture');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$response = curl_exec($ch);
	curl_close($ch);

	// Analyse and save to the database
	process_data($response, $ctk_date, $route_no,$ctk_from, $ctk_to, $from_name, $to_name);
}

function process_data($json_string, $ctk_date, $route_no, $ctk_from, $ctk_to, $from_name, $to_name)
{
	// global $ctk_carrier_names, $carriers_not_found;
	global $ctk_carrier_names, $ctk_stops;

	$trips = array();

	// Decode JSON string
	$data = json_decode($json_string, true);
	
	// Initialize variables
	$results = [];
	$carrier_names = [];
	$ic_carriers = ['Intercape Budgetliner', 'Intercape Mainliner', 'Big Sky Intercity', 'Intercape', 'Intercape Inter-Connect', 'Intercape Sleepliner', 'Intercape Express'];
	$gotic = false;
	
	// Iterate through messages
	foreach ($data['messages'] as $message) 
	{
		echo "A";
		$result = [];
		
		$inner_data = json_decode($message['data'], true);
		if ($inner_data['type'] == 'avalibilityResponse') 
		{
			if (isset($inner_data['data']['availability'])) 
			{
				echo " B";
				$result['availability'] = $inner_data['data']['availability'];
			}
			
			$results[] = $result;
		}
	}
	
	$filtered_array = array_filter($results, 'isNotEmpty');
	$filtered_array = array_values($filtered_array);

	if (isset($filtered_array[0]['availability']))
	{
		echo " C";
		$trips = $filtered_array[0]['availability'];
	}

	if (count($trips) == 0)
	{
		echo " D($route_no/$ctk_date)";
		$from_stop = $ctk_from;
		$to_stop = $ctk_to;
		log_event("No services available for this route/date combination: [$route_no] $ctk_from to $ctk_to on $ctk_date\r\n");
		return;
	} 
	else 
	{
		echo " E";
		$i = 1;
		$stops_not_found = array();
		$ctk_routes = array();

		foreach ($trips as $trip)
		{
			echo " F";
			if (isset($trip['message'])) 
			{
				$from_stop = $ctk_from;
				$to_stop = $ctk_to;
				log_event("No services available for this route/date combination: [$route_no] $ctk_from to $ctk_to on $ctk_date\r\n");
				continue;
			}
			
			$arraive_time = $trip['arrive']['dateTimeLocal'];
			$arrive_ts = $trip['arrive']['dateTimeMS'];
			$depart_time = $trip['depart']['dateTimeLocal'];
			$depart_ts = $trip['depart']['dateTimeMS'];
			$available_seats = $trip['availableSeats'];
			$carrier = $trip['carrierName'];
			$date_logged = date('Y-m-d');
			$duration = $arrive_ts - $depart_ts;
			$from_stop = $trip['depart']['stop']['citycode'];
			$from_stop_id = $trip['depart']['stop']['id'];
			$from_stop_city = $trip['depart']['stop']['city'];
			$position = $i;
			$price = $trip['price']['totalPrice'];
			$service_number = $trip['serviceNumber'];
			$route_name = $trip['routeDesc'] . "(" . $service_number . ")";
			$get_search_date = strtotime($ctk_date);
			$search_date = date('Ymd', $get_search_date);
			$to_stop = $trip['arrive']['stop']['citycode'];
			$to_stop_id = $trip['arrive']['stop']['id'];
			$to_stop_city = $trip['arrive']['stop']['city'];
			echo " G";
			// Check if stop is in the CTK_STOPS
			if (!in_array($from_stop, $ctk_stops))
			{
				//echo "Stop $from_stop is in the list\n";
				$stops_not_found[] = array($from_stop_id, $from_stop, $from_stop_city);
			}
			if (!in_array($to_stop, $ctk_stops))
			{
				//echo "Stop $to_stop is in the list\n";
				$stops_not_found[] = array($to_stop_id, $to_stop, $to_stop_city);
			}
			echo " H";
			// Build carrier name array
			$carrier_names[] = $carrier;

			if (in_array($carrier, $ic_carriers)) 
			{
				$gotic = true;

				// Collect ctk routes
				$just_route = substr($service_number, 2);
				$ctk_routes[] = $just_route;
			}
			echo " I";
			// Check if carrier is in the CTK_CARRIERS
			if (!in_array($carrier, $ctk_carrier_names))
			{
				
				// Not used as carrier is added here and not at the end $carriers_not_found[] = $carrier;

				// *** Add new carrier here
				addToCtkCarriers($carrier);
				// *** Add new carrier here
				
			}
			echo " J";	
			// Get carrier code and serial
			$carrier_data = searchCarrierList($carrier);
			$carrier_serial = $carrier_data['SERIAL'];

			// Carrier code is not used and Keith said it can just be null so line below has been commented out but left in for reference
			$carrier_code = "";
			echo " K";
			//echo "Record data: $arraive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_stop, $position, $price, $route_name, $route_no, $search_date, $to_stop\n";
			add_to_log($arraive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_name, $position, $price, $route_name, $route_no, $search_date, $to_name);

			$i++;
			echo " L";
		} // End of foreach

		echo " M";

		// Check if any new stops found in crawler data were added. Add if found
		print_r($stops_not_found);
		if (count($stops_not_found) > 0)
		{
			add_new_stops($stops_not_found);
		}
		
		log_event("From " . $from_stop . " to " . $to_stop . "\r\n" . "Search Date: $ctk_date");
		
		$bits = explode(",", $route_no);

		// CHECK ALERTS: START
		// Get/have a list of all the routes from ctx_compare: 1345, 1346, 949,6450...
		// Get/have a ;ist of routes from crawler data: 1346, 949,6450...
		   // do an in_array and with above data 1345 IS NOT in crawler data -> alert - IC has route but compu doesan't have it
		   // if crawler has a route that compare does not have, then alert - compu has route but IC does not have it
		// CTK has IC routes 1,2,3,4
		// Script has routes: 1,2,3,4,5 - means that IC has a route that CTK does not have: ALERT: Intercape has route no 5 running but Computicket does not have it
		// OR
		// 1,2,3,4 - nothing to do here
		// OR
		// 1,2,3 -means that CTK has a route that IC does not have: ALERT: Computicket has route no 4 running but Intercape does not have it

		// CHECK 1: IC has routes that we not listed in the CTK data
		echo "Routes from CTK_COMPARE: \n";
		print_r($bits);
		echo "Routes from Crawler/Computicket: \n";
		print_r($ctk_routes);

		log_event("\n" . "CHECK 1: IC has routes that we not listed in the CTK data");
		log_event("--------------------------------------------------------");
		foreach ($bits as $ic_route)
		{
			if (!in_array($ic_route, $ctk_routes))
			{
				// A route has been isolated as not being in the CTK data
				// Need to dbl check (is_service) before outputing the log
				$is_service = is_service($route_no, $ctk_date, $from_stop, $to_stop);
				{
					log_event("Intercape has route $ic_route running but CTK does not have it" . "\r\n" . "Please check IC route $ic_route on $ctk_date - No Intercape" . "\n");
				}
			}
		}


		// CHECK 2: CTK has a route that IC does not have
		log_event("\n" . "CHECK 2: CTK has a route that IC does not have");
		log_event("----------------------------------------------");
		foreach($ctk_routes as $ctk_route)
		{
			$is_service = is_service($route_no, $ctk_date, $from_stop, $to_stop);
			{
				if (!in_array($ctk_route, $bits))
				{
					log_event("CTK has route $ctk_route running but Intercape does not have it" . "\r\n" . "Please check CTK route $ctk_route on $ctk_date - No CTK" . "\n");
				}
			}
		}
		// CHECK ALERTS: END




		// CHECK #1
		// If there were no intercape trips, check if there is a scheduled trip for the route on the date
		// if (!$gotic)
		// {
		// 	// Check if there was a service
		// 	foreach ($bits as $route) 
		// 	{
		// 		$is_service = is_service($route_no, $ctk_date, $from_stop, $to_stop);
		
		// 		if ($is_service)
		// 		{
		// 			log_event("\r\n" . "Computicket ? - No Intercape trips found in results (" . count($trips) . "), but scheduled Intercape service was found" . "\r\n" . "Please check CTK from $from_stop to $to_stop on $ctk_date - No Intercape" . "\r\n");
		// 		}
		// 	}
		// }

		// CHECK #2
		// If there were no trips at all, but there is a scheduled trip for the route on the date, log an alert
		// if (!isset($trips) || count($trips) == 0)
		// {
		// 	// Check if there was a service
		// 	foreach ($bits as $route) 
		// 	{
		// 		$is_service = is_service($route_no, $ctk_date, $from_stop, $to_stop);

		// 		if ($is_service)
		// 		{
		// 			log_event("Computicket ? - No results (0) found, but scheduled Intercape service was found" . "\r\n" . "Please check CTK from $from_stop to $to_stop on $ctk_date - No Carriers" . "\r\n");
		// 		}
		// 	}
		// }

		// OUTPUT ALL CARRIERS FOUND
		if (count($carrier_names) > 0)
		{
			log_event("------------\n" . "CARRIER LIST" . "\n------------" . "\r\n" . json_encode($carrier_names) . "\r\n");
		}

		// echo "Completed\n";
	}
}

function is_service ($routeno, $date, $from, $to)
{
	global $cursor, $conn;

	$av="";
	if ($routeno==0 || $routeno=="0000")
	{
		$from=strtoupper($from);
		$to=strtoupper($to);
		if ($from=="") 
		{
			echo "THIS SHOULD NOT HAPPEN\n";
			return true; /// WE DONT KNOW..!!
		} 
		if (date("Ymd")==$date)
		{
			$sql = "select max (depart_time) from route_stops where short_name='$from' and route_Serial in (
			select A.route_Serial from route_stops A, route_stops B, open_coach C  where A.route_serial=B.route_serial and A.short_name='$to' and B.short_name='$from'
			and A.route_Serial=C.route_serial and C.run_date=$date and is_open='Y' and max_seats>0 and A.stop_order>B.stop_order) ";
		} 
		else 
		{
			$sql = "select A.route_Serial from route_stops A, route_stops B, open_coach C  where A.route_serial=B.route_serial and A.short_name='$to' and B.short_name='$from' and A.route_Serial=C.route_serial and C.run_date=$date and is_open='Y' and max_seats>0 and A.stop_order>B.stop_order ";
		}
		$cursor = oci_parse($conn, $sql);

		if ($data = oci_fetch_assoc($cursor)) 
		{
			if (date("Ymd")==$date) 
			{
				// $start=getdata($cursor,0);
				$start=$data['DEPART_TIME'];
				echo "Start time $start\n";

				if ($start<date("Hi")) 
				{
					echo "BUS $routeno today ($date) has left already ($start)\n";
					return false;
				}
			} 
			echo "Bus $routeno is running on $date from $from to $to\n";
			return true;
		} 
		else 
		{
			echo "No departure for $route on $date\n";
			return false;
		}

	}
	else 
	{
		$routeno=sprintf("%04d",$routeno);
		$sql = "select coach_serial,route_serial from open_coach where route_no='$routeno' and run_date=$date order by is_open desc";
		$cursor = oci_parse($conn, $sql);
		oci_execute($cursor);

		if ($data = oci_fetch_assoc($cursor)) 
		{
			// $cs=getdata($cursor,0);
			// $rs=getdata($cursor,1);
			$cs=$data['COACH_SERIAL'];
			$rs=$data['ROUTE_SERIAL'];

			$av=availseats($cs,$key,$key2);

			if ($av>0)
			{
				// check time...
				if (date("Ymd")==$date) 
				{
					echo "this is for today: select  depart_time from route_stops where route_serial='$rs' order by stop_order\n";
					$sql ="select depart_time from route_stops where route_serial='$rs' order by stop_order";
					oci_execute($cursor);
					
					if ($data = oci_fetch_assoc($cursor)) 
					{
						//$start=getdata($cursor,0);
						$start=$data['DEPART_TIME'];
						echo "Start time $start\n";
						if ($start<date("Hi")) 
						{
							echo "BUS $routeno today ($date) has left already ($start)\n";
							return false;
						}
					}
				} // date
				else
				{ 
					echo "This is Not for today\n";
				}

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
}

function add_to_log($arrive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_stop, $position, $price, $route_name, $route_no, $search_date, $to_stop)
{
	global $conn;


	// Remove ZAZA from stop names
	// $zaza_check = strpos($from_stop, 'ZAZA');
	// if ($zaza_check !== false) 
	// {
	// 	if ($zaza_check == 0) 
	// 	{
	// 		$from_stop = str_replace('ZAZA', '', $from_stop);
	// 		$to_stop = str_replace('ZAZA', '', $to_stop);
	// 	}
	// }

	$duration = ($duration / 1000);
	$hours = $duration / 3600;
	$int_hours = floor($hours);
	$minutes = ($hours - $int_hours) * 60;
	$decimal_hours = $int_hours + ($minutes / 60);
	$duration_decimal = number_format($decimal_hours, 1);

	$sql = "INSERT INTO CTK_LOG 
	(ARRIVE_TIME, AVAILABLE_SEATS, CARRIER, CARRIER_SERIAL, DATE_LOGGED, DEPART_TIME, DURATION, FROM_STOP, POSITION, PRICE, ROUTE_NAME, ROUTE_NO, SEARCH_DATE, TO_STOP) 
	VALUES 
	(TO_DATE(:ARRIVE_TIME, 'YYYY-MM-DD HH24:MI:SS'), 
	:AVAILABLE_SEATS,  
	:CARRIER,
	:CARRIER_SERIAL,
	CURRENT_TIMESTAMP, 
	TO_DATE(:DEPART_TIME, 'YYYY-MM-DD HH24:MI:SS'), 
	:DURATION, 
	:FROM_STOP,
	:POSITION,
	:PRICE,
	:ROUTE_NAME,
	:ROUTE_NO,
	:SEARCH_DATE,
	:TO_STOP)";

	$cursor = oci_parse($conn, $sql);
	
	oci_bind_by_name($cursor, ':ARRIVE_TIME', $arrive_time);
	oci_bind_by_name($cursor, ':AVAILABLE_SEATS', $available_seats);
	oci_bind_by_name($cursor, ':CARRIER', $carrier_code);
	oci_bind_by_name($cursor, ':CARRIER_SERIAL', $carrier_serial);
	oci_bind_by_name($cursor, ':DEPART_TIME', $depart_time);
	oci_bind_by_name($cursor, ':DURATION', $duration_decimal);
	oci_bind_by_name($cursor, ':FROM_STOP', $from_stop);
	oci_bind_by_name($cursor, ':POSITION', $position);
	oci_bind_by_name($cursor, ':PRICE', $price);
	oci_bind_by_name($cursor, ':ROUTE_NAME', $route_name);
	oci_bind_by_name($cursor, ':ROUTE_NO', $route_no);
	oci_bind_by_name($cursor, ':SEARCH_DATE', $search_date);
	oci_bind_by_name($cursor, ':TO_STOP', $to_stop);

	oci_execute($cursor);
	echo "$arrive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_stop, $position, $price, $route_name, $route_no, $search_date, $to_stop<bR>";

	oci_commit($conn);

	oci_free_statement($cursor);

}

function addToCtkCarriers($new_carrier)
{
	global $conn;

	$sql = "INSERT INTO CTK_CARRIERS (NAME, SERIAL) VALUES (:NAME, CTK_CARRIER_SEQ.NEXTVAL)";
	$cursor = oci_parse($conn, $sql);

	oci_bind_by_name($cursor, ':NAME', $new_carrier);

	oci_execute($cursor);
	oci_commit($conn);



	oci_free_statement($cursor);
}

function add_new_stops($stops_not_found)
{
	global $conn;

	foreach ($stops_not_found as $stop)
	{
		$stop_id = $stop[0];
		$stop_name = $stop[1];
		$stop_city = $stop[2];

		$sql = "INSERT INTO CTK_STOPS (STOP_NAME, STOP_NUMBER, STOP_ZAZA) VALUES (:STOP_NAME, :STOP_NUMBER, :STOP_ZAZA)";
		$cursor = oci_parse($conn, $sql);

		oci_bind_by_name($cursor, ':STOP_NAME', $stop_city);
		oci_bind_by_name($cursor, ':STOP_NUMBER', $stop_id);
		oci_bind_by_name($cursor, ':STOP_ZAZA', $stop_name);

		oci_execute($cursor);
		oci_commit($conn);



		oci_free_statement($cursor);

		log_event("Added new stop: " . "Name: $stop_city, Number: $stop_id, Zaza: $stop_name");
	}
	
	log_event("\n");
}

function searchCarrierList($name) 
{
	// global $carrier_list;
	$carrier_list = carrier_list();

    foreach ($carrier_list as $item) 
	{
        if (strcasecmp($item['NAME'], $name) == 0) 
		{
            return [
                'CODE' => $item['CODE'],
                'SERIAL' => $item['SERIAL']
            ];
        }
    }

    return [
		'CODE' => 'Z',
		'SERIAL' => '0'
	]; // Return null if no match is found
}

function isNotEmpty($value) 
{
    return !empty($value);
}

function log_event($message) 
{
    $log_file = '/tmp/devctkerr.log';
    // $log_file = 'ctkerr.log';

    $file_handle = fopen($log_file, 'a');

    if ($file_handle) 
	{
        $log_message = "$message\n";
        fwrite($file_handle, $log_message);
        fclose($file_handle);
    } 
	else 
	{
        echo "Error: Unable to open log file.";
    }
}


if (isset($from) && isset($route) && isset($to)) {
        $tot_days = 1;
        echo "Running a single query for $from-$to on $route for $start_date<bR>";
        $compare_list = get_single_list($route,$from,$to);
} else {
        $compare_list = get_compare_list();
}

$carrier_list = carrier_list();
$ctk_stops = get_ctk_stops();
$ctk_carrier_names = array();

foreach ($carrier_list as $carrier) 
{
    if (isset($carrier['NAME'])) 
	{
        $ctk_carrier_names[] = $carrier['NAME'];
    }
}

$trips = build_batch($compare_list);

start($trips);
