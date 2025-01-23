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
$ctk_stops = array();
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

function get_ctk_stops()
{
	$conn = oci_conn();

	$results = array();

	$sql = "SELECT stop_zaza FROM CTK_STOPS";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$results[] = $row['STOP_ZAZA'];
	}

	oci_free_statement($cursor);
	oci_close($conn);

	return $results;
}

function build_batch($compare_list)
{
	global $tot_days;

	$dup_array = array();

	$today = date('Y-m-d');

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
			$date = date('Y-m-d', strtotime($today . ' + ' . $i . ' days'));

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
	global $carriers_not_found;
	
	$start_ts = time();

	log_event("--- START --------------------------------------------------------------------------------------------------------------------------------" . "\r\n[" . $timestamp = date('Y-m-d H:i:s') ."]" . "\r\n");

	foreach ($trips as $trip)
	{
		// if ($trip['from'] == 'ZAZACAPETOWN' && $trip['to'] == 'ZAZAJOHANNESBURG')
		// {
			crawl($trip['route'], $trip['from'], $trip['to'], $trip['date'], $trip['from_name'], $trip['to_name']);
		// }
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

	// Send email here


	echo "Completed " . date("Y-m-d H:i:s") . " Took: {$hours} hours, {$minutes} minutes, {$seconds} seconds" . "\n";
}

function crawl($route_no, $ctk_from, $ctk_to, $ctk_date, $from_name, $to_name)
{
	/**/
	$data = json_encode(['from' => $ctk_from, 'to' => $ctk_to, 'date' => $ctk_date]);

	$ch = curl_init('http://localhost:3000/run-capture');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$response = curl_exec($ch);
	curl_close($ch);

	// Analyse and save to the database
	// $response = '{"messages":[{"type":"received","data":"{\"type\":\"sessionResponse\",\"data\":{\"sessionId\":\"1737445601227-y1gwn3j0b4dwou5lwqkokf\"}}"},{"type":"received","data":"{\"type\":\"avalibilityResponse\",\"data\":{\"data\":null,\"message\":\"Your request is processing\",\"isLoading\":true}}"},{"type":"received","data":"{\"type\":\"avalibilityResponse\",\"data\":{\"data\":null,\"message\":\"Your request is processing\",\"isLoading\":true}}"},{"type":"received","data":"{\"type\":\"avalibilityResponse\",\"data\":{\"metadata\":{\"gwtt\":8,\"catproductId\":\"0\",\"urlOnCreate\":\"computicket.com\",\"messageId\":\"1737445601435-1737445601227-y1gwn3j0b4dwou5lwqkokf-availability\",\"channelType\":\"WEB\",\"sessionId\":\"1737445601227-y1gwn3j0b4dwou5lwqkokf\",\"userName\":\"computicket.com\",\"userId\":\"c40d0f1c-40f0-4ce0-baab-5c1c7b26e7a1\",\"cacheTime\":1737445189474,\"profileId\":\"0\",\"width\":800,\"operation\":\"availability\",\"channelId\":\"1960\",\"productType\":\"bus\",\"height\":600,\"username\":\"computicket.com\"},\"availability\":[{\"totalDuration\":\"16h00m\",\"travelTime\":57600000,\"serviceNumber\":\"FG3803\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1884/logo_f_f_gertse_ba8Kxyx1AcUeMJb8bAS63u.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"FF Gertse\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"f f gertse\",\"remCheck\":\"A 41, PARK STATION, RISSIK ST, CBD, JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURGBLOB\",\"city\":\"Johannesburg\",\"description\":\"A 41, Park Station, Rissik Street, Johannesburg C B D\",\"suburb\":\"Johannesburg\",\"id\":46737,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 10:40:00\",\"dateTimeMS\":1737542400000},\"price\":{\"totalPrice\":370,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":370,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"ffgertse\",\"id\":\"sMa.p502CvOzx6oIPnML76ecvw-FNiNCOvnvsKvGJYw01KQ95QLySAx7wRgSNcQyOE-uV3XGtmyfQYJHqbd-VYSCRrYBTfZJNdZ7V3QCZIFM24rXEKKKdAPJdxgGA-nuaX4w6YDMa-GQUNVGbHjDhNA\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"f f gertse\",\"remCheck\":\"1 OLD MARINE DR, FORESHORE\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWNSNOB\",\"city\":\"Cape Town\",\"description\":\"1 Old Marine Drive, Foreshore\",\"suburb\":\"Cape Town\",\"id\":46738,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 18:40:00\",\"dateTimeMS\":1737484800000}},{\"totalDuration\":\"17h10m\",\"travelTime\":61800000,\"serviceNumber\":\"DE7553\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1879/logo_delta_coaches_oSddEafjkEwkb1wso7NsYC.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Delta Coaches\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"delta coaches\",\"remCheck\":\"PARK STATION, 41 RISSIK STREET , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Park Station, 41 Rissik Street\",\"suburb\":\"Johannesburg\",\"id\":24712,\"remotecode\":\"6117\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 06:30:00\",\"dateTimeMS\":1737527400000},\"price\":{\"totalPrice\":395,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":395,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"deltacoaches\",\"id\":\"sMa.p502CvOzx64cNncL56uw-0PZRjJ7V4zjyMPeEYBMyLQh7W8jnYW79JH3rCAzybZLjvg3Y-lC1A-T2btyVZCCQsoNSd5NNdJzK3waYJVM3.bbYK6-YHvJdsB2evQnYavEuPTUwux0YKw\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"delta coaches\",\"remCheck\":\"TRAIN STATION,1 OLD MARINE DRIVE, CAPE TOWN , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Train Station, 1 Old Marine Drive, Cape Town\",\"suburb\":\"Cape Town\",\"id\":24714,\"remotecode\":\"6617\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 13:20:00\",\"dateTimeMS\":1737465600000}},{\"totalDuration\":\"16h20m\",\"travelTime\":58800000,\"serviceNumber\":\"DE7527\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1879/logo_delta_coaches_oSddEafjkEwkb1wso7NsYC.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Delta Coaches\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"delta coaches\",\"remCheck\":\"PARK STATION, 41 RISSIK STREET , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Park Station, 41 Rissik Street\",\"suburb\":\"Johannesburg\",\"id\":24712,\"remotecode\":\"6117\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 08:30:00\",\"dateTimeMS\":1737534600000},\"price\":{\"totalPrice\":395,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":395,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"deltacoaches\",\"id\":\"sMa.p502CvOzx64cNmML56uw-0PZRjJ7V4zjyMPeDZBMyLQh7W8niYWj9JH3rCAzybZLjvg3Y-lC1A-T2btmWZCCQsoNSd5NNdJzK3wiYJVM3.bbYK6-YHvJdsB2evQnYavEuPTUwux0fLw\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"delta coaches\",\"remCheck\":\"TRAIN STATION,1 OLD MARINE DRIVE, CAPE TOWN , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWNBOB\",\"city\":\"Cape Town\",\"description\":\"Train Station, 1 Old Marine Drive, Cape Town\",\"suburb\":\"Cape Town\",\"id\":24714,\"remotecode\":\"6617\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 16:10:00\",\"dateTimeMS\":1737475800000}},{\"totalDuration\":\"17h45m\",\"travelTime\":63900000,\"serviceNumber\":\"DE7505\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1879/logo_delta_coaches_oSddEafjkEwkb1wso7NsYC.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Delta Coaches\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"delta coaches\",\"remCheck\":\"PARK STATION, 41 RISSIK STREET , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Park Station, 41 Rissik Street\",\"suburb\":\"Johannesburg\",\"id\":24712,\"remotecode\":\"6117\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 11:45:00\",\"dateTimeMS\":1737546300000},\"price\":{\"totalPrice\":395,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":395,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"deltacoaches\",\"id\":\"sMa.p502CvOzx64cMnsL56uw-0PZRjJ7V4zjyMPeBZhMyLQh7W8nja2z9JH3rCAzybZLjvg3Y-lC1A-T2bteXZCCQsoNSd5NNdJzK3gGfIFM3.bbYK6-YHvJdsB2evQnYavEuPTUwux0dLQ\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"delta coaches\",\"remCheck\":\"TRAIN STATION,1 OLD MARINE DRIVE, CAPE TOWN , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Train Station, 1 Old Marine Drive, Cape Town\",\"suburb\":\"Cape Town\",\"id\":24714,\"remotecode\":\"6617\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 18:00:00\",\"dateTimeMS\":1737482400000}},{\"totalDuration\":\"17h40m\",\"travelTime\":63600000,\"serviceNumber\":\"ET3065\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/9421/logo_eagle_liner_transport_1KAksQrwXHhGoH632g6fuz.jpg\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Eagle Liner Transport\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"eagle liner transport\",\"remCheck\":\"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK ST, JOHANNESBURG, 2000 , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK STREET\",\"suburb\":\"Johannesburg\",\"id\":48121,\"remotecode\":\"10293\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 06:10:00\",\"dateTimeMS\":1737526200000},\"price\":{\"totalPrice\":400,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":400,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"eaglelinertransport\",\"id\":\"sMa.p502CvOzx6o0KlsL47ucm1PlXg5jP8jm-abHBPEx1MAx6W8r-YWLiIH7iCxjxcZLkoQnf5VS1AO.0ct2XZiWQroNTdJROdZ7IwgKbJ1Y34bXHKKCcAPJA3B2e-XuHMI06UUBY.GpCbFvlAE2FNNWC9A\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"eagle liner transport\",\"remCheck\":\"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION, 8001 , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION\",\"suburb\":\"Cape Town\",\"id\":48089,\"remotecode\":\"10267\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 12:30:00\",\"dateTimeMS\":1737462600000}},{\"totalDuration\":\"17h30m\",\"travelTime\":63000000,\"serviceNumber\":\"ET3071\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/9421/logo_eagle_liner_transport_1KAksQrwXHhGoH632g6fuz.jpg\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Eagle Liner Transport\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"eagle liner transport\",\"remCheck\":\"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK ST, JOHANNESBURG, 2000 , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK STREET\",\"suburb\":\"Johannesburg\",\"id\":48121,\"remotecode\":\"10293\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 10:30:00\",\"dateTimeMS\":1737541800000},\"price\":{\"totalPrice\":400,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":400,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"eaglelinertransport\",\"id\":\"sMa.p502CvOzx6o0JnML47ucm1PlXg5jP8jm-abHBPEx1MAx6Ws7-YWLiIH3qCxjwcZLkoQnf5VS1AO.0ct2XZiWQroNTdJFNdZ7IwgKbJ1Y34bXHKaaeAPJA3B2e-XuHMI06UUBY.GpCbFvlAE2FNNWD8A\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"eagle liner transport\",\"remCheck\":\"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION, 8001 , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION\",\"suburb\":\"Cape Town\",\"id\":48089,\"remotecode\":\"10267\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 17:00:00\",\"dateTimeMS\":1737478800000}},{\"totalDuration\":\"18h00m\",\"travelTime\":64800000,\"serviceNumber\":\"FG3812\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1884/logo_f_f_gertse_ba8Kxyx1AcUeMJb8bAS63u.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"FF Gertse\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"f f gertse\",\"remCheck\":\"A 41, PARK STATION, RISSIK ST, CBD, JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"A 41, Park Station, Rissik Street, Johannesburg C B D\",\"suburb\":\"Johannesburg\",\"id\":46737,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 07:00:00\",\"dateTimeMS\":1737529200000},\"price\":{\"totalPrice\":400,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":400,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"ffgertse\",\"id\":\"sMa.p502CvOzx6oEGmcL76ecvw-FNiNCOvnrtKvGJYgw1KQ95QLySAx7wRgSNcQyOE-uV3XGtmyfQYJHqbd-VYSCRrYBTdpZNdZ7V3QCZIFM24rXFL6adAPJdyQGH.XuaX4w6YDMa-GQUNVGbHjDgNQ\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"f f gertse\",\"remCheck\":\"1 OLD MARINE DR, FORESHORE\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"1 Old Marine Drive, Foreshore\",\"suburb\":\"Cape Town\",\"id\":46738,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 13:00:00\",\"dateTimeMS\":1737464400000}},{\"totalDuration\":\"16h00m\",\"travelTime\":57600000,\"serviceNumber\":\"FG3814\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1884/logo_f_f_gertse_ba8Kxyx1AcUeMJb8bAS63u.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"FF Gertse\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"f f gertse\",\"remCheck\":\"A 41, PARK STATION, RISSIK ST, CBD, JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"A 41, Park Station, Rissik Street, Johannesburg C B D\",\"suburb\":\"Johannesburg\",\"id\":46737,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 07:00:00\",\"dateTimeMS\":1737529200000},\"price\":{\"totalPrice\":400,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":400,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"ffgertse\",\"id\":\"sMa.p502CvOzx6oEGl8L76ecvw-FNiNCOvnrrKvGJYgo1KQ95QLySAx7wRgSNcQyOE-uV3XGtmyfQYJHqbd-VYSCRrYBTcJZNdZ7V3QCZIFM24rXFL6adAPJdyAGH.XuaX4w6YDMa-GQUNVGbHjDgMw\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"f f gertse\",\"remCheck\":\"1 OLD MARINE DR, FORESHORE\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"1 Old Marine Drive, Foreshore\",\"suburb\":\"Cape Town\",\"id\":46738,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 15:00:00\",\"dateTimeMS\":1737471600000}},{\"totalDuration\":\"18h45m\",\"travelTime\":67500000,\"serviceNumber\":\"IX7069\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/6639/logo_intercity_xpress_4wwBPNTXSJHtuZm1HG5EQF.jpg\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Intercity Xpress\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercity xpress\",\"remCheck\":\"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK ST, JOHANNESBURG, 2000 , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"JOHANNESBURG PARK STATION (BAY21) , 96 RISSIK STREET\",\"suburb\":\"Johannesburg\",\"id\":48262,\"remotecode\":\"10293\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 07:45:00\",\"dateTimeMS\":1737531900000},\"price\":{\"totalPrice\":420,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":420,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercityxpress\",\"id\":\"sMa.p500CvOzx64cPmcL04fQvw.ZXmYTF9jm6dLGcZA43JBJ5XMzlZmrpJnL3DhH2apT5og.a8VaoAOb1at-WZiGRrIFSdZZQd57K2gCaJ1E357PAKKaAAe9Ewxyd.Wb2L5BnUh4BwBEAUU.rHT7o\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercity xpress\",\"remCheck\":\"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION, 8001 , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION\",\"suburb\":\"Cape Town\",\"id\":48230,\"remotecode\":\"10267\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 13:00:00\",\"dateTimeMS\":1737464400000}},{\"totalDuration\":\"18h25m\",\"travelTime\":66300000,\"serviceNumber\":\"IX7067\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/6639/logo_intercity_xpress_4wwBPNTXSJHtuZm1HG5EQF.jpg\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Intercity Xpress\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercity xpress\",\"remCheck\":\"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK ST, JOHANNESBURG, 2000 , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"JOHANNESBURG PARK STATION (BAY21) , 96 RISSIK STREET\",\"suburb\":\"Johannesburg\",\"id\":48262,\"remotecode\":\"10293\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 12:25:00\",\"dateTimeMS\":1737548700000},\"price\":{\"totalPrice\":450,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":450,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercityxpress\",\"id\":\"sMa.p500CvOzx64cOnsL04fQvw.ZXmYTF9jm6dLGcZA43KhJ4VM3hamvkIHn3DhH2apT5og.a8VaoAOb1at-WZiGRp4FSdZZQd57K2gCaJ1E24rXAKKaAAe9ExByd.Wb2L5BnUh4BwBEAUU.rHT7m\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercity xpress\",\"remCheck\":\"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION, 8001 , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION\",\"suburb\":\"Cape Town\",\"id\":48230,\"remotecode\":\"10267\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 18:00:00\",\"dateTimeMS\":1737482400000}},{\"totalDuration\":\"20h20m\",\"travelTime\":73200000,\"serviceNumber\":\"GP13375\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1889/logo_greyhound_premium_goGSwKyCM6ycEXaVPCVSrh.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Greyhound Premium\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"greyhound premium\",\"remCheck\":\"Park City Transit Centre, 96 Rissik St, Johannesburg CBD , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Park City Transit Centre, 96 Rissik Street, Johannesburg Cbd\",\"suburb\":\"Johannesburg\",\"id\":45668,\"remotecode\":\"8720\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 07:20:00\",\"dateTimeMS\":1737530400000},\"price\":{\"totalPrice\":530,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":530,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"greyhoundpremium\",\"id\":\"sMa.p502CvOzx64cKnML6.eUz2fpLg5nN9C6ybrfcfg8yLgh.QM3qY2.gJXjoDwz8a5Ljvgff-lWoAOb1at-WZiGRroFSdZZQd57K2gCaJ1E357XFKKaAAe9Fwhyd.Wb2L5BnUh4BwBEAX0ftHjvmMg\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"greyhound premium\",\"remCheck\":\"Long Distance Bus Facility, 1 Old Marine Drive, Cape Town , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Long Distance Bus Facility, 1 Old Marine Drive\",\"suburb\":\"Cape Town\",\"id\":45616,\"remotecode\":\"8717\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 11:00:00\",\"dateTimeMS\":1737457200000}},{\"totalDuration\":\"19h30m\",\"travelTime\":70200000,\"serviceNumber\":\"GP13377\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1889/logo_greyhound_premium_goGSwKyCM6ycEXaVPCVSrh.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Greyhound Premium\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"greyhound premium\",\"remCheck\":\"Park City Transit Centre, 96 Rissik St, Johannesburg CBD , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Park City Transit Centre, 96 Rissik Street, Johannesburg Cbd\",\"suburb\":\"Johannesburg\",\"id\":45668,\"remotecode\":\"8720\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 09:00:00\",\"dateTimeMS\":1737536400000},\"price\":{\"totalPrice\":530,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":530,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"greyhoundpremium\",\"id\":\"sMa.p502CvOzx64cKmML6.eUz2fpLg5nN9C6ybrfcfg8yLgh9QM3qY2.iIX.tDgz8a5Ljvgff-lWoAOb1at-WZiGRrIJSdZZQd57K2gCaJ1E36bfFKKaAAe9Fwhyd.Wb2L5BnUh4BwBEAX0ftHjvmMA\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"greyhound premium\",\"remCheck\":\"Long Distance Bus Facility, 1 Old Marine Drive, Cape Town , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Long Distance Bus Facility, 1 Old Marine Drive\",\"suburb\":\"Cape Town\",\"id\":45616,\"remotecode\":\"8717\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 13:30:00\",\"dateTimeMS\":1737466200000}},{\"totalDuration\":\"19h00m\",\"travelTime\":68400000,\"serviceNumber\":\"GP13379\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1889/logo_greyhound_premium_goGSwKyCM6ycEXaVPCVSrh.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Greyhound Premium\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"greyhound premium\",\"remCheck\":\"Park City Transit Centre, 96 Rissik St, Johannesburg CBD , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Park City Transit Centre, 96 Rissik Street, Johannesburg Cbd\",\"suburb\":\"Johannesburg\",\"id\":45668,\"remotecode\":\"8720\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 13:00:00\",\"dateTimeMS\":1737550800000},\"price\":{\"totalPrice\":530,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":530,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"greyhoundpremium\",\"id\":\"sMa.p502CvOzx64cKl8L6.eUz2fpLg5nN9C6ybrfcfg8yLghzQM3qY2.jIHvtDgz8a5Ljvgff-lWoAOb1at-WZiGRp4FSdZZQd57K2gCaJ1E247fFKKaAAe9Fwhyd.Wb2L5BnUh4BwBEAX0ftHjvmPg\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"greyhound premium\",\"remCheck\":\"Long Distance Bus Facility, 1 Old Marine Drive, Cape Town , CAPE TOWN\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Long Distance Bus Facility, 1 Old Marine Drive\",\"suburb\":\"Cape Town\",\"id\":45616,\"remotecode\":\"8717\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 18:00:00\",\"dateTimeMS\":1737482400000}},{\"totalDuration\":\"19h00m\",\"travelTime\":68400000,\"serviceNumber\":\"BI8003\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_bigsky_mdLsWP3oYve7dFyZGKKMiZ.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Big Sky\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":1182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 14:00:00\",\"dateTimeMS\":1737554400000},\"price\":{\"totalPrice\":530,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":530,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.p501CvOzx64AIncL04fQvw.ZfnZiQs3.mMPGIZggyMA59XMzmYWjnK3rjDhX1aY6X0m-t6DHKZZjqFaDvFV7u2uIgEPQ6aJzI3QWbJFE24b7FKKadHfBAwxmD.HmFL4knIEFFoR0ALSTsAEngKpX2rn.3-jDIb6o8WWA\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )\",\"suburb\":\"Cape Town\",\"id\":1184,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 19:00:00\",\"dateTimeMS\":1737486000000}},{\"totalDuration\":\"20h25m\",\"travelTime\":73500000,\"serviceNumber\":\"BI8001\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_bigsky_mdLsWP3oYve7dFyZGKKMiZ.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Big Sky\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":1182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 06:40:00\",\"dateTimeMS\":1737528000000},\"price\":{\"totalPrice\":550,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":550,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.p501CvOzx64AJnsL04fQvw.ZfnZiQsnvoNfCIZg4xMA59XMzmYWjhIXvpChb3aY6X0m-t6DHKZZjqFaDvFV7u2uIgEPQ6aJzI3QWbJFE24bfELaadHfBAwxmD.HmFLosjIEFFoRoALSLsAEngKpX2rn.3-jDIb6o8WWI\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )\",\"suburb\":\"Cape Town\",\"id\":1184,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 10:15:00\",\"dateTimeMS\":1737454500000}},{\"totalDuration\":\"19h45m\",\"travelTime\":71100000,\"serviceNumber\":\"IS2107\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_sleepliner_f9xiquDyhsd2A1kKNDsGCv.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Intercape Sleepliner\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":1182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 06:00:00\",\"dateTimeMS\":1737525600000},\"price\":{\"totalPrice\":690,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":690,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.p501CvOzx64AKmML04fQvw.ZfnZiQvnroMvKGagcwMA59XMzmYWnpJH7rDhTzbY6X0m-t6DHKZZjqFaDvFV7u2uIgEPQ6aJzI3QWbJFE24bfELaadHfBAwxmD.HmFLosnIEFFoRgALi7sAEngKpX2rn.3-jDDdaA9WWQ\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )\",\"suburb\":\"Cape Town\",\"id\":1184,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 10:15:00\",\"dateTimeMS\":1737454500000}},{\"totalDuration\":\"19h20m\",\"travelTime\":69600000,\"serviceNumber\":\"IS2105\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_sleepliner_f9xiquDyhsd2A1kKNDsGCv.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Intercape Sleepliner\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":1182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 13:20:00\",\"dateTimeMS\":1737552000000},\"price\":{\"totalPrice\":690,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":690,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.p500CvOzx64AJmsL04fQvw.ZfnZiQt3LtM.WGZwk0KhJ7Ws7gZmnjIXjiBxf1aJP50H64jUXRfYGJcqXoHFHu0fQxB.MvAoPK3wKeJVI14bbNKKadAO9CwR6G.XqFLIwkIkFFvAUZNSHlHSWQNsjEg2TPjySnb8E-WGPe\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )\",\"suburb\":\"Cape Town\",\"id\":1184,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 18:00:00\",\"dateTimeMS\":1737482400000}},{\"totalDuration\":\"20h15m\",\"travelTime\":72900000,\"serviceNumber\":\"IS2615\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_sleepliner_f9xiquDyhsd2A1kKNDsGCv.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Intercape Sleepliner\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":1182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 14:45:00\",\"dateTimeMS\":1737557100000},\"price\":{\"totalPrice\":690,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":690,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.p500CvOzx64AIn8L04fQvw.ZfnZiQt3jmNfeJZwg4LRJ7Ws7gZmnjJ3riBxf1aJP50H64jUXRfYGJcqXoHFHu0fQxB.MvAoPK3wKeJVI14bbNK6adAO9CwR6G.XqFLIwjJERFvAUcNSHlHSWQNsjEg2TPjySnb8E-X2Le\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )\",\"suburb\":\"Cape Town\",\"id\":1184,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 18:30:00\",\"dateTimeMS\":1737484200000}},{\"totalDuration\":\"26h00m\",\"travelTime\":93600000,\"serviceNumber\":\"IM0221\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_mainliner_aModybrB325AfEoJfPAHX5.png\",\"routeDesc\":\"Cape Town to Johannesburg\",\"carrierName\":\"Intercape Mainliner\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZANORWOOD\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":9991182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 20:15:00\",\"dateTimeMS\":1737576900000},\"price\":{\"totalPrice\":860,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":860,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.p501CvOzx64AJmcL04fQvw.ZfnZiQv3PrP.SBYA0yMA59XMznZ2LnInPvChf9a46X0m-t6DHKZZjqFaDvFV7u2uIgEPQ6aJzI3QWbJFE24b.ELaadHfBAwxmD.HmFLI0mJUFFoRsAICHsAEngKpX2rn.3-jDDa6I-W2I\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )\",\"suburb\":\"Cape Town\",\"id\":1184,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 18:15:00\",\"dateTimeMS\":1737483300000}},{\"totalDuration\":\"21h35m\",\"travelTime\":77700000,\"serviceNumber\":\"IM9033\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_mainliner_aModybrB325AfEoJfPAHX5.png\",\"routeDesc\":\"Cape Town to Bez Valley\",\"carrierName\":\"Intercape Mainliner\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"1 Fourth Street, Albertina Sisulu Road (Bezuidenhout Valley)\",\"province\":\"Bez Valley\",\"citycode\":\"ZAZABEZVALLEY\",\"city\":\"Bez Valley\",\"description\":\"1 Fourth Street, Albertina Sisulu Road ( Bezuidenhout Valley )\",\"suburb\":\"Bez Valley\",\"id\":45572,\"remotecode\":\"BEZ VALLEY\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-22 07:50:00\",\"dateTimeMS\":1737532200000},\"price\":{\"totalPrice\":1042,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":1042,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.p501CvOzx6owImcL04fQvw.ZfnZiQt3LsNPKFYg81LxJzVMbhY2nlInroDhj0b5D50H64jUXRfYGJcq3iDjD23v0uAP9Qd57K2gCaJ1I24LbAKKaAAvJCxByC.3mHKYgnIEFYvAUcKCPuAEngKpX2rn.3-jDDa6s8WmA\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)\",\"province\":\"Cape Town\",\"citycode\":\"ZAZACAPETOWN\",\"city\":\"Cape Town\",\"description\":\"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )\",\"suburb\":\"Cape Town\",\"id\":1184,\"remotecode\":\"CAPE TOWN\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-21 10:15:00\",\"dateTimeMS\":1737454500000}}]}}"},{"type":"received","data":"{\"type\":\"multidayResponse\",\"data\":{\"metadata\":{\"gwtt\":13,\"catproductId\":\"0\",\"urlOnCreate\":\"computicket.com\",\"messageId\":\"1737445601435-1737445601227-y1gwn3j0b4dwou5lwqkokf-multiday\",\"channelType\":\"WEB\",\"sessionId\":\"1737445601227-y1gwn3j0b4dwou5lwqkokf\",\"userName\":\"computicket.com\",\"userId\":\"c40d0f1c-40f0-4ce0-baab-5c1c7b26e7a1\",\"profileId\":\"0\",\"width\":800,\"operation\":\"multiday\",\"channelId\":\"1960\",\"productType\":\"bus\",\"height\":600,\"username\":\"computicket.com\"},\"multiday\":[{\"invalidDate\":true},{\"invalidDate\":true},{\"carrier\":\"FF Gertse\",\"travelDate\":\"2025-01-21\",\"arrive\":\"ZAZAJOHANNESBURG\",\"price\":370,\"depart\":\"ZAZACAPETOWN\",\"createDate\":\"2025-01-21 07:39:49\"},{\"carrier\":\"FF Gertse\",\"travelDate\":\"2025-01-22\",\"arrive\":\"ZAZAJOHANNESBURG\",\"price\":400,\"depart\":\"ZAZACAPETOWN\",\"createDate\":\"2025-01-21 07:28:17\"},{\"carrier\":\"FF Gertse\",\"travelDate\":\"2025-01-23\",\"arrive\":\"ZAZAJOHANNESBURG\",\"price\":400,\"depart\":\"ZAZACAPETOWN\",\"createDate\":\"2025-01-21 07:39:45\"}]}}"}]}';
	
	process_data($response, $ctk_date, $route_no,$ctk_from, $ctk_to, $from_name, $to_name);
}

function process_data($json_string, $ctk_date, $route_no, $ctk_from, $ctk_to, $from_name, $to_name)
{
	global $ctk_carrier_names, $carriers_not_found, $ctk_stops;

	// log_event(" _                \n| |    ___   __ _ \n| |   / _ \ / _` |\n| |__| (_) | (_| |\n|_____\___/ \__, |\n            |___/ ");

	// Decode JSON string
	$data = json_decode($json_string, true);
	
	// Initialize variables
	// $results = [];
	$trips = array();
	$carrier_names = [];
	$ic_carriers = ['Intercape Budgetliner', 'Intercape Mainliner', 'Big Sky Intercity', 'Intercape', 'Intercape Inter-Connect', 'Intercape Sleepliner', 'Intercape Express'];
	$gotic = false;
	
	// Iterate through creawler response
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

	if (isset($filtered_array[0]['availability']))
	{
		$trips = $filtered_array[0]['availability'];
	}

	if (count($trips) == 0)
	{
		$from_stop = $ctk_from;
		$to_stop = $ctk_to;
		log_event("No services available for this route/date combination: [$route_no] $ctk_from to $ctk_to on $ctk_date\r\n");
		return;
	} 
	else 
	{
		$i = 1;
		$stops_not_found = array();
		$ctk_routes = array();
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

			// Build carrier name array
			$carrier_names[] = $carrier;

			if (in_array($carrier, $ic_carriers)) 
			{
				$gotic = true;

				// Collect ctk routes
				$just_route = substr($service_number, 2);
				$ctk_routes[] = $just_route;
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
			$carrier_code = "";
			
			add_to_log($arraive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_name, $position, $price, $route_name, $route_no, $search_date, $to_name);

			$i++;
		} // End foreach

		// Check if any new stops found in crawler data were added. Add if found
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

		// $is_service = is_service(1235, $ctk_date, $from_stop, $to_stop); //1182, 45572, 1184
		
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
			log_event("------------\n" . "CARRIERS LIST" . "\n------------" . "\r\n" . json_encode($carrier_names) . "\r\n");
		}
		

		// echo "Completed\n";
	}

	
}

function is_service ($routeno, $date, $from, $to)
{
	// global $cursor, $conn;
	// echo "is_service($routeno, $date, $from, $to)";
	echo "1";
	$conn = oci_conn();

	$av="";
	if ($routeno==0 || $routeno=="0000")
	{
		echo "a";
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
			echo "No departure for $routeno on $date\n";
			return false;
		}

	}
	else 
	{
		echo "b: $routeno";
		$routeno=sprintf("%04d",$routeno);
		$sql = "select coach_serial,route_serial from open_coach where route_no='$routeno' and run_date=$date order by is_open desc";
		$cursor = oci_parse($conn, $sql);
		oci_execute($cursor);

		if ($data = oci_fetch_assoc($cursor)) 
		{
			echo "c";
			$cs=$data['COACH_SERIAL'];
			$ra = $data['ROUTE_SERIAL'];
			
			// $av=availseats($cs,$key,$key2);
			$av=0;

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
		
		else echo "D"; return false;
	}
}

function add_to_log($arrive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_name, $position, $price, $route_name, $route_no, $search_date, $to_name)
{
	$conn = oci_conn();

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
	oci_bind_by_name($cursor, ':FROM_STOP', $from_name);
	oci_bind_by_name($cursor, ':POSITION', $position);
	oci_bind_by_name($cursor, ':PRICE', $price);
	oci_bind_by_name($cursor, ':ROUTE_NAME', $route_name);
	oci_bind_by_name($cursor, ':ROUTE_NO', $route_no);
	oci_bind_by_name($cursor, ':SEARCH_DATE', $search_date);
	oci_bind_by_name($cursor, ':TO_STOP', $to_name);

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

function add_new_stops($stops_not_found)
{
	$conn = oci_conn();

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

		oci_free_statement($cursor);

		log_event("Added new stop: " . "Name: $stop_city, Number: $stop_id, Zaza: $stop_name");
	}
	log_event("\n");

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

$compare_list = get_compare_list();

$carrier_list = carrier_list();
$ctk_stops = get_ctk_stops();


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