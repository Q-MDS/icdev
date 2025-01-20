<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

// Ajust for 7, 14, 21
$tot_days = 2;
$compare_list = array();
$carrier_list = array();
$batch = array();

function get_compare_list()
{
	global $conn;

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
	global $conn;

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

	$today = date('Y-m-d');

	foreach ($compare_list as $key => $value) 
	{
		$route = $value['ROUTE'];
		$from = $value['STOP_FROM_NAME'];
		$to = $value['STOP_TO_NAME'];

		for ($i=0; $i < $tot_days; $i++) 
		{ 
			$date = date('Y-m-d', strtotime($today . ' + ' . $i . ' days'));

			$batch[] = array(
				'route' => $route,
				'from' => $from,
				'to' => $to,
				'date' => $date
			);
		}
	}

	return $batch;
}

$compare_list = get_compare_list();
$carrier_list = carrier_list();
$trips = build_batch($compare_list);

start($trips);

function start($trips)
{
	foreach ($trips as $trip)
	{
		crawl($trip['route'], $trip['from'], $trip['to'], $trip['date']);
		// analyse();
	}
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
	process_data($response, $ctk_date, $route_no);
}

// function analyse()
function analyse($route_no, $ctk_from, $ctk_to, $ctk_date, $response)
{
	/*
	$route = 209;
	$ctk_from = 'ZAZABUTTERWORTH';
	$ctk_to = 'ZAZAJOHANNESBURG';
	$ctk_date = '2025-01-13';
	$response = '{"messages":[{"type":"received","data":"{\"type\":\"sessionResponse\",\"data\":{\"sessionId\":\"1736769780720-xxu7btiyvsqjhx03bf9nq\"}}"},{"type":"received","data":"{\"type\":\"avalibilityResponse\",\"data\":{\"data\":null,\"message\":\"Your request is processing\",\"isLoading\":true}}"},{"type":"received","data":"{\"type\":\"avalibilityResponse\",\"data\":{\"data\":null,\"message\":\"Your request is processing\",\"isLoading\":true}}"},{"type":"received","data":"{\"type\":\"getNotificationResponse\",\"data\":{\"displayText\":\"Currently searching 10 routes with 7 different carriers.\",\"metadata\":{\"gwtt\":8,\"catproductId\":\"0\",\"urlOnCreate\":\"computicket.com\",\"messageId\":\"1736769781069-1736769780720-xxu7btiyvsqjhx03bf9nq-availability\",\"channelType\":\"WEB\",\"sessionId\":\"1736769780720-xxu7btiyvsqjhx03bf9nq\",\"userName\":\"computicket.com\",\"userId\":\"c40d0f1c-40f0-4ce0-baab-5c1c7b26e7a1\",\"profileId\":\"0\",\"width\":800,\"operation\":\"notify\",\"channelId\":\"1960\",\"productType\":\"bus\",\"height\":600,\"username\":\"computicket.com\"}}}"},{"type":"received","data":"{\"type\":\"multidayResponse\",\"data\":{\"metadata\":{\"gwtt\":13,\"catproductId\":\"0\",\"urlOnCreate\":\"computicket.com\",\"messageId\":\"1736769781068-1736769780720-xxu7btiyvsqjhx03bf9nq-multiday\",\"channelType\":\"WEB\",\"sessionId\":\"1736769780720-xxu7btiyvsqjhx03bf9nq\",\"userName\":\"computicket.com\",\"userId\":\"c40d0f1c-40f0-4ce0-baab-5c1c7b26e7a1\",\"profileId\":\"0\",\"width\":800,\"operation\":\"multiday\",\"channelId\":\"1960\",\"productType\":\"bus\",\"height\":600,\"username\":\"computicket.com\"},\"multiday\":[{\"invalidDate\":true},{\"invalidDate\":true},{\"carrier\":\"City To City\",\"travelDate\":\"2025-01-13\",\"arrive\":\"ZAZABUTTERWORTH\",\"price\":470,\"depart\":\"ZAZAJOHANNESBURG\",\"createDate\":\"2025-01-13 10:52:45\"},{\"carrier\":\"Intercape Budgetliner\",\"travelDate\":\"2025-01-14\",\"arrive\":\"ZAZABUTTERWORTH\",\"price\":460,\"depart\":\"ZAZAJOHANNESBURG\",\"createDate\":\"2025-01-13 11:34:20\"},{\"carrier\":\"Eagle Liner Transport\",\"travelDate\":\"2025-01-15\",\"arrive\":\"ZAZABUTTERWORTH\",\"price\":400,\"depart\":\"ZAZAJOHANNESBURG\",\"createDate\":\"2025-01-13 10:54:10\"}]}}"},{"type":"received","data":"{\"type\":\"avalibilityResponse\",\"data\":{\"metadata\":{\"gwtt\":2401,\"catproductId\":\"0\",\"urlOnCreate\":\"computicket.com\",\"messageId\":\"1736769781069-1736769780720-xxu7btiyvsqjhx03bf9nq-availability\",\"channelType\":\"WEB\",\"sessionId\":\"1736769780720-xxu7btiyvsqjhx03bf9nq\",\"userName\":\"computicket.com\",\"userId\":\"c40d0f1c-40f0-4ce0-baab-5c1c7b26e7a1\",\"profileId\":\"0\",\"width\":800,\"operation\":\"availability\",\"channelId\":\"1960\",\"productType\":\"bus\",\"height\":600,\"username\":\"computicket.com\"},\"availability\":[{\"totalDuration\":\"12h40m\",\"travelTime\":45600000,\"serviceNumber\":\"C83380\",\"availableSeats\":21,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1878/logo_city_to_city_e1CUpiv7tx35CKQ724yqaD.png\",\"routeDesc\":\"Johannesburg to Butterworth\",\"carrierName\":\"City To City\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"city to city\",\"remCheck\":\"BUTTERWORTH RAILWAY STATION  \",\"province\":\"Butterworth\",\"citycode\":\"ZAZABUTTERWORTH\",\"city\":\"Butterworth\",\"description\":\"Butterworth Railway Station\",\"suburb\":\"Butterworth\",\"id\":24667,\"remotecode\":\"BWS\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-14 08:20:00\",\"dateTimeMS\":1736842800000},\"price\":{\"totalPrice\":470,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":470,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"citytocity\",\"id\":\"sMa.pp42AsOrx4YENncL-5vQzxfpdhInEq3ryNu.7HXwsX2gZQM3jYW7gI3rpDhjwbJPkvg3Y-lC1A-fzb9eVZCCQsoNWd5JOcpzB3R2fIlMp4LfYWaeAfqAls1Tn4AiPLY4vIA\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"city to city\",\"remCheck\":\"Park Station Cnr Rissik & Wolmarans Street Braamfo\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Park Station, Cnr Rissik & Wolmarans Street, Braamfontein\",\"suburb\":\"Johannesburg\",\"id\":3070,\"remotecode\":\"JNB\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-13 19:40:00\",\"dateTimeMS\":1736797200000}},{\"totalDuration\":\"12h30m\",\"travelTime\":45000000,\"serviceNumber\":\"ET3078\",\"availableSeats\":9,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/9421/logo_eagle_liner_transport_1KAksQrwXHhGoH632g6fuz.jpg\",\"routeDesc\":\"Johannesburg to Butterworth\",\"carrierName\":\"Eagle Liner Transport\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"eagle liner transport\",\"remCheck\":\"ELLERINE STORE, 14 HIGH STREET, BUTTERWORTH , BUTTERWORTH\",\"province\":\"Butterworth\",\"citycode\":\"ZAZABUTTERWORTH\",\"city\":\"Butterworth\",\"description\":\"RAILWAY STATION\",\"suburb\":\"Butterworth\",\"id\":48088,\"remotecode\":\"10266\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-14 07:45:00\",\"dateTimeMS\":1736840700000},\"price\":{\"totalPrice\":530,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":530,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"eaglelinertransport\",\"id\":\"sMa.pp42AsOrx5oULn8L47ucm1PlXg5jP8jm-abHBPEx1MAx6Wsf-YWLgKnnsCxbycZLkoQbb5VS1AODxct2XZiWQroBRdJ9McJ7IwgKbJ1Y34bbBKKGZBfJA3B2e-HiHMI06UUBYwkp4Wm-IAE2FNNWD-Q\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"eagle liner transport\",\"remCheck\":\"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK ST, JOHANNESBURG, 2000 , JOHANNESBURG\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK STREET\",\"suburb\":\"Johannesburg\",\"id\":48121,\"remotecode\":\"10293\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-13 19:15:00\",\"dateTimeMS\":1736795700000}},{\"totalDuration\":\"15h25m\",\"travelTime\":55500000,\"serviceNumber\":\"IB1231\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_budgetliner_s2HhDfBjdxsksoriXMEjHd.png\",\"routeDesc\":\"Johannesburg to Butterworth\",\"carrierName\":\"Intercape Budgetliner\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Ellerines, High Street\",\"province\":\"Butterworth\",\"citycode\":\"ZAZABUTTERWORTH\",\"city\":\"Butterworth\",\"description\":\"Ellerines, High Street\",\"suburb\":\"Butterworth\",\"id\":1223,\"remotecode\":\"BUTTERWORTH\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-14 08:55:00\",\"dateTimeMS\":1736844900000},\"price\":{\"totalPrice\":540,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":540,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.pp41AsOrx4IYGm8L04fQvw.ZfnZiQt3roMveBZQ45KRJ7Ws7gYW3jI3rqDRX1bZf52XCgiSvLd4WFCr3geVL1y-UnF.EyF.qwwgKbJ1Y34bbGKaGeAPJA3B6D.36HL4wjIElAuRgdNSfxGDzhKqSF7EXZll.ycr9FK2LZGvc\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":1182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-13 17:30:00\",\"dateTimeMS\":1736789400000}},{\"totalDuration\":\"14h00m\",\"travelTime\":50400000,\"serviceNumber\":\"IM0249\",\"availableSeats\":10,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_mainliner_aModybrB325AfEoJfPAHX5.png\",\"routeDesc\":\"Johannesburg to Butterworth\",\"carrierName\":\"Intercape Mainliner\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Ellerines, High Street\",\"province\":\"Butterworth\",\"citycode\":\"ZAZABUTTERWORTH\",\"city\":\"Butterworth\",\"description\":\"Ellerines, High Street\",\"suburb\":\"Butterworth\",\"id\":1223,\"remotecode\":\"BUTTERWORTH\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-14 09:00:00\",\"dateTimeMS\":1736845200000},\"price\":{\"totalPrice\":660,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":660,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.pp41AsOrx4IcPnML04fQvw.ZfnZiQt3.pPvqGZwg4LhJ7Ws3gZGnhJn.rDhP2b5X52XCgiSvLd4WFCr3geVL1y-UnF.EyF.qwwgKbJ1Y34bbGKa-dAPJA3B6D.36HL4wjIEhFvBgdNSbxGz7hKqSF7EXZll.ycr9FJGPZHf8\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":1182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-13 19:00:00\",\"dateTimeMS\":1736794800000}},{\"totalDuration\":\"15h25m\",\"travelTime\":55500000,\"serviceNumber\":\"IM0209\",\"availableSeats\":9,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_mainliner_aModybrB325AfEoJfPAHX5.png\",\"routeDesc\":\"Johannesburg to Butterworth\",\"carrierName\":\"Intercape Mainliner\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Ellerines, High Street\",\"province\":\"Butterworth\",\"citycode\":\"ZAZABUTTERWORTH\",\"city\":\"Butterworth\",\"description\":\"Ellerines, High Street\",\"suburb\":\"Butterworth\",\"id\":1223,\"remotecode\":\"BUTTERWORTH\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-14 09:25:00\",\"dateTimeMS\":1736846700000},\"price\":{\"totalPrice\":700,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":700,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.pp41AsOrx4IYGlsL04fQvw.ZfnZiQt3vrNfaFZQk3LBJ7Ws3gZWjkIHnjDRL8aZv52XCgiSvLd4WFCr3geVL1y-UnF.EyF.qwwgKbJ1Y34bbGKa6dAPJA3B6D.36HL4wjIEhHuRgdNSTxGjjhKqSF7EXZll.ycr9FJGPZGf8\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":1182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-13 18:00:00\",\"dateTimeMS\":1736791200000}},{\"totalDuration\":\"14h25m\",\"travelTime\":51900000,\"serviceNumber\":\"BI8043\",\"availableSeats\":8,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_bigsky_mdLsWP3oYve7dFyZGKKMiZ.png\",\"routeDesc\":\"Johannesburg to Butterworth\",\"carrierName\":\"Big Sky\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Ellerines, High Street\",\"province\":\"Butterworth\",\"citycode\":\"ZAZABUTTERWORTH\",\"city\":\"Butterworth\",\"description\":\"Ellerines, High Street\",\"suburb\":\"Butterworth\",\"id\":1223,\"remotecode\":\"BUTTERWORTH\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-14 07:25:00\",\"dateTimeMS\":1736839500000},\"price\":{\"totalPrice\":800,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":800,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.pp41AsOrx4IYGn8L04fQvw.ZfnZiQt33rNfGBYA41KBJ7Ws3gZW.hI3LoChHzZZr52XCgiSvLd4WFCr3geVL1y-UnF.EyF.qwwgKbJ1Y34bbGKaGdAPJA3B6D.36HL4wjIEZHuRgdNSXxFTjhKqSF7EXZll.ycr9OIGvbHfU\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)\",\"province\":\"Johannesburg\",\"citycode\":\"ZAZAJOHANNESBURG\",\"city\":\"Johannesburg\",\"description\":\"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )\",\"suburb\":\"Johannesburg\",\"id\":1182,\"remotecode\":\"JOHANNESBURG\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-13 17:00:00\",\"dateTimeMS\":1736787600000}},{\"totalDuration\":\"16h35m\",\"travelTime\":59700000,\"serviceNumber\":\"IM9038\",\"availableSeats\":1,\"groupID\":\"ungrouped\",\"icon\":\"https://cf-content.computicket.com/bus/1892/def_logo_intercape_mainliner_aModybrB325AfEoJfPAHX5.png\",\"routeDesc\":\"Bez Valley to Butterworth\",\"carrierName\":\"Intercape Mainliner\",\"arrive\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"Ellerines, High Street\",\"province\":\"Butterworth\",\"citycode\":\"ZAZABUTTERWORTH\",\"city\":\"Butterworth\",\"description\":\"Ellerines, High Street\",\"suburb\":\"Butterworth\",\"id\":1223,\"remotecode\":\"BUTTERWORTH\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-14 09:00:00\",\"dateTimeMS\":1736845200000},\"price\":{\"totalPrice\":1058,\"numPax\":1,\"currency\":\"ZAR\",\"prices\":[{\"quantity\":1,\"individualPrice\":1058,\"discountID\":\"ADULT\"}]},\"carrierCode\":\"intercape\",\"id\":\"sMa.pp42AsOrx4IQNmsL04fQvw.ZfnZiQt3jrMPGCYQswJRJzVMbhY2nlInrrDBj0b5v50Xqy6DPEfpqCBsLlAUT02uM1CvQpDYPK3wKeJVI247bDKqOdAO9CwR6G.XqGKo0uIEFFvAUdNSbsGDD8RtSZj2nugWXeC9tBUGPYEQ\",\"depart\":{\"stop\":{\"country\":\"South Africa\",\"carrier\":\"intercape\",\"remCheck\":\"1 Fourth Street, Albertina Sisulu Road (Bezuidenhout Valley)\",\"province\":\"Bez Valley\",\"citycode\":\"ZAZABEZVALLEY\",\"city\":\"Bez Valley\",\"description\":\"1 Fourth Street, Albertina Sisulu Road ( Bezuidenhout Valley )\",\"suburb\":\"Bez Valley\",\"id\":45572,\"remotecode\":\"BEZ VALLEY\"},\"timezone\":\"+02:00\",\"dateTimeLocal\":\"2025-01-13 16:25:00\",\"dateTimeMS\":1736785500000}}]}}"}]}';
	*/

	// Get data
	process_data($response, $ctk_date, $route);
}

function process_data($json_string, $ctk_date, $route_no)
{
	// log_event(" _                \n| |    ___   __ _ \n| |   / _ \ / _` |\n| |__| (_) | (_| |\n|_____\___/ \__, |\n            |___/ ");
	log_event("--- START --------------------------------------------------------------------------------------------------------------------------------" . "\r\n[" . $timestamp = date('Y-m-d H:i:s') ."]" . "\r\n");

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
		$route_name = $trip['routeDesc'];
		$search_date = strtotime($ctk_date);
		$to_stop = $trip['arrive']['stop']['citycode'];

		// Build carrier name array
		$carrier_names[] = $carrier;

		if (in_array($carrier, $ic_carriers)) 
		{
			$gotic = true;
		}
		
		// Get carrier code and serial
		$carrier_data = searchCarrierList($carrier);
		$carrier_serial = $carrier_data['SERIAL'];
		$carrier_code = $carrier_data['CODE'];
		
		//echo "Record data: $arraive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_stop, $position, $price, $route_name, $route_no, $search_date, $to_stop\n";
		add_to_log($arraive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_stop, $position, $price, $route_name, $route_no, $search_date, $to_stop);

		$i++;
	}
	
	log_event("From " . $from_stop . " to " . $to_stop . "\r\n" . "Search Date: $ctk_date");
	// log_event("From" . $from_stop . " to" . $to_stop)  . "\r\n");

	// CHECK #1
	// If there were no intercape trips, check if there is a scheduled trip for the route on the date
	if (!$gotic)
	{
		// Check if there was a service
		$is_service = is_service($route_no, $ctk_date, $from_stop, $to_stop);

		if ($is_service)
		{
			log_event("\r\n" . "Computicket ? - No Intercape trips found in results (" . count($trips) . "), but scheduled Intercape service was found" . "\r\n" . "Please check CTK from $from_stop to $to_stop on $ctk_date - No Intercape" . "\r\n");
		}
	}

	// CHECK #2
	// If there were no trips at all, but there is a scheduled trip for the route on the date, log an alert
	if (count($trips) == 0)
	{
		// Check if there was a service
		$is_service = is_service($route_no, $ctk_date, $from_stop, $to_stop);

		if ($is_service)
		{
			log_event("Computicket ? - No results (0) found, but scheduled Intercape service was found" . "\r\n" . "Please check CTK from $from_stop to $to_stop on $ctk_date - No Carriers" . "\r\n");
		}
	}

	// OUTPUT ALL CARRIERS FOUND
	log_event("------------\n" . "CARRIER LIST" . "\n------------" . "\r\n" . json_encode($carrier_names) . "\r\n");

	log_event("--- END ----------------------------------------------------------------------------------------------------------------------------------" . "\r\n");

	echo "Completed\n";
}

function is_service ($route_no, $date, $from, $to)
{
	// global $cursor, $conn;
	global $conn;

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

		if (oci_fetch_assoc($cursor)) 
		{
			if (date("Ymd")==$date) 
			{
				$start=getdata($cursor,0);
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
		oci_execute($cursor);

		if (oci_fetch_assoc($cursor)) 
		{
			$cs=getdata($cursor,0);
			$rs=getdata($cursor,1);
			$av=availseats($cs,$key,$key2);

			if ($av>0)
			{
				// check time...
				if (date("Ymd")==$date) 
				{
					echo "this is for today: select  depart_time from route_stops where route_serial='$rs' order by stop_order\n";
					$sql ="select depart_time from route_stops where route_serial='$rs' order by stop_order";
					oci_execute($cursor);
					if (oci_fetch_assoc($cursor)) 
					{
						$start=getdata($cursor,0);
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