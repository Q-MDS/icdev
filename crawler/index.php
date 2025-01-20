<?php
/**
 * https://computicket.com/travel/busses/search?from=ZAZABUTTERWORTH&to=ZAZAJOHANNESBURG&date=2024-11-08&adult=1&senior=0&child=0&student=0&sapsandf=0
 * 
 * PHASE 1: COLLECT -> RUN -> OUTPUT
 * √. Read data from ctk_compare
 * √. Build array of calls
 * 3. Run calls/simulate run_capture on dev setup
 * 4. Output results to table : CTK_TEMP
 * 5. Check that run has completed
 * PHASE 2: ANALYSE AND PUT IN CTK_LOG
 * 1. ...
 */
$tot_days = 1;
$compare_list = array();
$carrier_list = array();
$carriers_not_found = array();
$batch = array();

function oci_conn()
{
	$host = 'localhost';
	$port = '1521';
	$sid = 'XE';
	$username = 'SYSTEM';
	$password = 'dontletmedown3';

	$conn = oci_connect($username, $password, "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port)))(CONNECT_DATA=(SID=$sid)))");

	if (!$conn) 
	{
		$e = oci_error();
		// echo "Connection failed: " . $e['message'];
		exit;
	} 
	else 
	{
		// echo "Connection succeeded";
	}

	return $conn;
}

function get_compare_list()
{
	$conn = oci_conn();

	$sql = "SELECT 
		main_table.ROUTE,
		main_table.STOP_FROM,
		stop_from_table.STOP_ZAZA AS STOP_FROM_NAME,
		main_table.STOP_TO,
		stop_to_table.STOP_ZAZA AS STOP_TO_NAME
	FROM 
		CTK_COMPARE main_table
	LEFT JOIN 
		CTK_STOPS stop_from_table ON main_table.STOP_FROM = stop_from_table.STOP_NUMBER
	LEFT JOIN 
		CTK_STOPS stop_to_table ON main_table.STOP_TO = stop_to_table.STOP_NUMBER
	";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$compare_list[] = $row;
	}

	oci_free_statement($cursor);
	oci_close($conn);

	return $compare_list;
}

function carrier_list()
{
	$conn = oci_conn();

	$sql = "SELECT * FROM CTK_CARRIERS";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$carrier_list[] = $row;
	}

	oci_free_statement($cursor);
	oci_close($conn);

	return $carrier_list;
}

function build_batch($compare_list)
{
	global $tot_days;

	$dup_array = array();

	$today = date('Y-m-d');

	foreach($compare_list as $compare)
	{
		$route = $compare['ROUTE'];
		$from = TRIM($compare['STOP_FROM_NAME']);
		$to = TRIM($compare['STOP_TO_NAME']);

		if ($from != "" && $to != "") 
		{
			$str = $from . "@@" . $to;
	
			$dup_array[$str][] = $route;
		}
	}

	foreach($dup_array as $key => $routes)
	{
		$from_to = explode('@@', $key);
		$from = $from_to[0];
		$to = $from_to[1];
		$route_str = "";

		foreach ($routes as $route) 
		{
			$route = sprintf("%04d", $route); 
			$route_str .= $route . ",";
		}
		$route_str = rtrim($route_str, ',');

		for ($i=0; $i < $tot_days; $i++) 
		{ 
			$date = date('Y-m-d', strtotime($today . ' + ' . $i . ' days'));

			$batch[] = array(
				'route' => $route_str,
				'from' => $from,
				'to' => $to,
				'date' => $date
			);
		}
	}
	
	// echo "Batch count: " . count($batch) . "\n";
	// print_r($batch);

	return $batch;
}

$compare_list = get_compare_list();

$carrier_list = carrier_list();

// Build a list of carrier names only
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

function start($trips)
{
	global $carriers_not_found;
	
	$start_ts = time();

	log_event("--- START --------------------------------------------------------------------------------------------------------------------------------" . "\r\n[" . $timestamp = date('Y-m-d H:i:s') ."]" . "\r\n");

	foreach ($trips as $trip)
	{
		crawl($trip['route'], $trip['from'], $trip['to'], $trip['date']);
		// analyse();
	}
	
	$carriers_not_found = array_unique($carriers_not_found);
	$carriers_not_found = array_values($carriers_not_found);
	
	if (count($carriers_not_found) > 0)
	{
		foreach ($carriers_not_found as $carrier) 
		{
			addToCtkCarriers($carrier);
		}
		log_event("Added new carriers to CTK_CARRIERS " . json_encode($carriers_not_found));
	}

	log_event("--- END ----------------------------------------------------------------------------------------------------------------------------------" . "\r\n");

	$end_ts = time();

	$duration = $end_ts - $start_ts;
	$hours = floor($duration / 3600);
	$minutes = floor(($duration % 3600) / 60);
	$seconds = $duration % 60;

	echo "Completed " . date("Y-m-d H:i:s") . " Took: {$hours} hours, {$minutes} minutes, {$seconds} seconds" . "\n";
}

function crawl($route_no, $ctk_from, $ctk_to, $ctk_date)
{
	$data = json_encode(['from' => $ctk_from, 'to' => $ctk_to, 'date' => $ctk_date]);

	$ch = curl_init('http://localhost:3000/run-capture');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$response = curl_exec($ch);
	curl_close($ch);

	// Analyse and save to the database
	process_data($response, $ctk_date, $route_no,$ctk_from, $ctk_to);
}

// function analyse(): Delete me
function analyse($route_no, $ctk_from, $ctk_to, $ctk_date, $response)
{
	/*
	$route = 209;
	$ctk_from = 'ZAZABUTTERWORTH';
	$ctk_to = 'ZAZAJOHANNESBURG';
	$ctk_date = '2025-01-13';
	*/

	// Get data
	process_data($response, $ctk_date, $route);
}

function process_data($json_string, $ctk_date, $route_no, $ctk_from, $ctk_to)
{
	global $ctk_carrier_names, $carriers_not_found;

	// log_event(" _                \n| |    ___   __ _ \n| |   / _ \ / _` |\n| |__| (_) | (_| |\n|_____\___/ \__, |\n            |___/ ");

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
		$result = [];
		
		$inner_data = json_decode($message['data'], true);
		if ($inner_data['type'] == 'avalibilityResponse') 
		{
			if (isset($inner_data['data']['availability'])) 
			{
				$result['availability'] = $inner_data['data']['availability'];
			}
			
			$results[] = $result;
		}
	}
	
	$filtered_array = array_filter($results, 'isNotEmpty');
	$filtered_array = array_values($filtered_array);

	$trips = $filtered_array[0]['availability'];

	$i = 1;

	foreach ($trips as $trip)
	{
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
		$position = $i;
		$price = $trip['price']['totalPrice'];
		$service_number = $trip['serviceNumber'];
		$route_name = $trip['routeDesc'] . "(" . $service_number . ")";
		$get_search_date = strtotime($ctk_date);
		$search_date = date('Ymd', $get_search_date);
		$to_stop = $trip['arrive']['stop']['citycode'];

		// Build carrier name array
		$carrier_names[] = $carrier;

		if (in_array($carrier, $ic_carriers)) 
		{
			$gotic = true;
		}

		// Check if carrier is in the CTK_CARRIERS
		if (!in_array($carrier, $ctk_carrier_names))
		{
			//echo "Carrier $carrier is in the list\n";
			$carriers_not_found[] = $carrier;
		}
				
		// Get carrier code and serial
		$carrier_data = searchCarrierList($carrier);
		$carrier_serial = $carrier_data['SERIAL'];
		// Carrier code is not used and Keith said it can just be null so line below has been commented out but left in for reference
		// $carrier_code = $carrier_data['CODE'];
		$carrier_code = "";
		
		//echo "Record data: $arraive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_stop, $position, $price, $route_name, $route_no, $search_date, $to_stop\n";
		add_to_log($arraive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_stop, $position, $price, $route_name, $route_no, $search_date, $to_stop);

		$i++;
	}
	
	log_event("From " . $from_stop . " to " . $to_stop . "\r\n" . "Search Date: $ctk_date");
	
	$bits = explode(",", $route_no);

	// CHECK #1
	// If there were no intercape trips, check if there is a scheduled trip for the route on the date
	if (!$gotic)
	{
		// Check if there was a service
		foreach ($bits as $route) 
		{
			$is_service = is_service($route_no, $ctk_date, $from_stop, $to_stop);
	
			if ($is_service)
			{
				log_event("\r\n" . "Computicket ? - No Intercape trips found in results (" . count($trips) . "), but scheduled Intercape service was found" . "\r\n" . "Please check CTK from $from_stop to $to_stop on $ctk_date - No Intercape" . "\r\n");
			}
		}
	}

	// CHECK #2
	// If there were no trips at all, but there is a scheduled trip for the route on the date, log an alert
	// if (!isset($trips) || count($trips) == 0)

	$is_service = is_service(2105, $ctk_date, $from_stop, $to_stop);
	
	if (!isset($trips) || count($trips) == 0)
	{
		// Check if there was a service
		foreach ($bits as $route) 
		{
			$is_service = is_service($route_no, $ctk_date, $from_stop, $to_stop);

			if ($is_service)
			{
				log_event("Computicket ? - No results (0) found, but scheduled Intercape service was found" . "\r\n" . "Please check CTK from $from_stop to $to_stop on $ctk_date - No Carriers" . "\r\n");
			}
		}
	}

	// OUTPUT ALL CARRIERS FOUND
	log_event("------------\n" . "CARRIER LIST" . "\n------------" . "\r\n" . json_encode($carrier_names) . "\r\n");

	// echo "Completed\n";
}

function is_service ($routeno, $date, $from, $to)
{
	// global $cursor, $conn;
	$conn = oci_conn();

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
				$start=$data['depart_time'];
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
			$cs=$data['coach_serial'];
			$ra = $data['route_serial'];
			// $cs=getdata($cursor,0);
			// $rs=getdata($cursor,1);
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
						// $start=getdata($cursor,0);
						$start=$data['depart_time'];
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
	$conn = oci_conn();

	// Remove ZAZA from stop names
	$zaza_check = strpos($from_stop, 'ZAZA');
	if ($zaza_check !== false) 
	{
		if ($zaza_check == 0) 
		{
			$from_stop = str_replace('ZAZA', '', $from_stop);
			$to_stop = str_replace('ZAZA', '', $to_stop);
		}
	}

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
	TO_DATE(:DATE_LOGGED, 'YYYY-MM-DD HH24:MI:SS'), 
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
	oci_bind_by_name($cursor, ':DATE_LOGGED', $date_logged);
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

	oci_free_statement($cursor);

	oci_close($conn);
}

function addToCtkCarriers($new_carrier)
{
	$conn = oci_conn();

	$sql = "INSERT INTO CTK_CARRIERS (NAME) VALUES (:NAME)";
	$cursor = oci_parse($conn, $sql);

	oci_bind_by_name($cursor, ':NAME', $new_carrier);

	oci_execute($cursor);

	oci_free_statement($cursor);

	oci_close($conn);
}

function searchCarrierList($name) 
{
	global $carrier_list;

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
	];; // Return null if no match is found
}

function isNotEmpty($value) 
{
    return !empty($value);
}

function log_event($message) 
{
    $log_file = 'ctkerr.log';

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