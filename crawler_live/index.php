<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");
require_once("class.html.mime.mail.inc");

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
	WHERE 
    	ROWNUM <= 2
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
	// *** Add new carrier: New carrier is added in process data function: Lines 144 have been commented out during testing
	// global $carriers_not_found;
	// *** Add new carrier: New carrier is added in process data function: Lines 144 have been commented out during testing
	
	$start_ts = time();

	log_event("--- START --------------------------------------------------------------------------------------------------------------------------------" . "\r\n[" . $timestamp = date('Y-m-d H:i:s') ."]" . "\r\n");

	foreach ($trips as $trip)
	{
		crawl($trip['route'], $trip['from'], $trip['to'], $trip['date'], $trip['from_name'], $trip['to_name']);
	}

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
	/**/$data = json_encode(['from' => $ctk_from, 'to' => $ctk_to, 'date' => $ctk_date]);

	$ch = curl_init('http://10.50.0.180:3001/run-capture');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$response = curl_exec($ch);
	curl_close($ch);

	// Test data
	//$response = '{"jsonData":{"type":"avalibilityResponse","data":{"metadata":{"gwtt":6,"catproductId":"0","urlOnCreate":"computicket.com","messageId":"1737720417500-1737720417218-g2uhuv88qibwcdneyfy9ho-availability","channelType":"WEB","sessionId":"1737720417218-g2uhuv88qibwcdneyfy9ho","userName":"computicket.com","userId":"c40d0f1c-40f0-4ce0-baab-5c1c7b26e7a1","cacheTime":1737720040343,"profileId":"0","width":800,"operation":"availability","channelId":"1960","productType":"bus","height":600,"username":"computicket.com"},"availability":[{"totalDuration":"16h15m","travelTime":58500000,"serviceNumber":"IB1232","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1892/def_logo_intercape_budgetliner_s2HhDfBjdxsksoriXMEjHd.png","routeDesc":"Butterworth to Johannesburg","carrierName":"Intercape Budgetliner","arrive":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )","suburb":"Johannesburg","id":1182,"remotecode":"JOHANNESBURG"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 08:30:00","dateTimeMS":1737793800000},"price":{"totalPrice":490,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":490,"discountID":"ADULT"}]},"carrierCode":"intercape","id":"sMa.p541Eue394oUPmsL04fQvw.ZfnZiQt3LnMPWBYAgyKRJ7Ws7gYW3jIHnjBhXzaJD50Wq8nCDXZZmVC6eKHl.o3v8sAPU.EPy.wgKbJ1Y34bXBKaCcBfJA3B6D.36HL48iIElGvBgdNSfxGTHhKqSF7E3sqmjsa79FK2LZGvQ","depart":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Ellerines, High Street","province":"Butterworth","citycode":"ZAZABUTTERWORTH","city":"Butterworth","description":"Ellerines, High Street","suburb":"Butterworth","id":1223,"remotecode":"BUTTERWORTH"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 16:15:00","dateTimeMS":1737735300000}},{"totalDuration":"14h25m","travelTime":51900000,"serviceNumber":"IM0250","availableSeats":5,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1892/def_logo_intercape_mainliner_aModybrB325AfEoJfPAHX5.png","routeDesc":"Butterworth to Johannesburg","carrierName":"Intercape Mainliner","arrive":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )","suburb":"Johannesburg","id":1182,"remotecode":"JOHANNESBURG"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 05:30:00","dateTimeMS":1737783000000},"price":{"totalPrice":610,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":610,"discountID":"ADULT"}]},"carrierCode":"intercape","id":"sMa.p541Eue394oQGl8L04fQvw.ZfnZiQt3vmN.WJYwkxLBJ7Ws7gZ2jmK33qCRP9a5X50Wq8nCDXZZmVC6eKHl.o3v8sAPU.EPy.wgKbJ1Y34bXBKaOdBfJA3B6D.36HL48iIERGvBgdNSXxGznhKqSF7E3sqmjsa79FJGPZHPY","depart":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Ellerines, High Street","province":"Butterworth","citycode":"ZAZABUTTERWORTH","city":"Butterworth","description":"Ellerines, High Street","suburb":"Butterworth","id":1223,"remotecode":"BUTTERWORTH"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 15:05:00","dateTimeMS":1737731100000}},{"totalDuration":"16h45m","travelTime":60300000,"serviceNumber":"IM9037","availableSeats":4,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1892/def_logo_intercape_mainliner_aModybrB325AfEoJfPAHX5.png","routeDesc":"Butterworth to Bez Valley","carrierName":"Intercape Mainliner","arrive":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"1 Fourth Street, Albertina Sisulu Road (Bezuidenhout Valley)","province":"Bez Valley","citycode":"ZAZABEZVALLEY","city":"Bez Valley","description":"1 Fourth Street, Albertina Sisulu Road ( Bezuidenhout Valley )","suburb":"Bez Valley","id":45572,"remotecode":"BEZ VALLEY"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 07:50:00","dateTimeMS":1737791400000},"price":{"totalPrice":1540,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":1540,"discountID":"ADULT"}]},"carrierCode":"intercape","id":"sMa.p540Eue3664wKm8L04fQvw.ZfnZiQsn7nNfaAZgs5MAZzVM3jYW7gI3nuBhH3a46Wxmu8jTfSfYSTF8LlEUqAyfAuCeMkaJzI3QWbJFEz4bLFLaadHfBAwxmD.HmCLooiIEFFoRgAKSLoHSWQNsjylmLOpVCnb981WWDc","depart":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Ellerines, High Street","province":"Butterworth","citycode":"ZAZABUTTERWORTH","city":"Butterworth","description":"Ellerines, High Street","suburb":"Butterworth","id":1223,"remotecode":"BUTTERWORTH"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 15:05:00","dateTimeMS":1737731100000}}]}}}';
	//$response = '{"jsonData":{"type":"avalibilityResponse","data":{"metadata":{"gwtt":1445,"catproductId":"0","urlOnCreate":"computicket.com","messageId":"1737720419811-1737720419518-kcrftp1bb3hqrs8t7epgxf-availability","channelType":"WEB","sessionId":"1737720419518-kcrftp1bb3hqrs8t7epgxf","userName":"computicket.com","userId":"c40d0f1c-40f0-4ce0-baab-5c1c7b26e7a1","profileId":"0","width":800,"operation":"availability","channelId":"1960","productType":"bus","height":600,"username":"computicket.com"},"availability":[{"totalDuration":"16h00m","travelTime":57600000,"serviceNumber":"FG3814","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1884/logo_f_f_gertse_ba8Kxyx1AcUeMJb8bAS63u.png","routeDesc":"Cape Town to Johannesburg","carrierName":"FF Gertse","arrive":{"stop":{"country":"South Africa","carrier":"f f gertse","remCheck":"A 41, PARK STATION, RISSIK ST, CBD, JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"A 41, Park Station, Rissik Street, Johannesburg C B D","suburb":"Johannesburg","id":46737,"remotecode":"JOHANNESBURG"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 07:00:00","dateTimeMS":1737788400000},"price":{"totalPrice":300,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":300,"discountID":"ADULT"}]},"carrierCode":"ffgertse","id":"sMa.p542Euen74ocInsL76ecvw-FNiNCOvnrrKvGJYgo1KQ98QLySAx7wRgSNcQyOE-uV3XGtmyfQYJHqbd-VYSCRrYVTcJZNdZ7V3QCZIFM24rLFL6adAPJdyAGA.XuaX4w6RDY29WZbNVGbHjDgMw","depart":{"stop":{"country":"South Africa","carrier":"f f gertse","remCheck":"1 OLD MARINE DR, FORESHORE","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"1 Old Marine Drive, Foreshore","suburb":"Cape Town","id":46738,"remotecode":"CAPE TOWN"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 15:00:00","dateTimeMS":1737730800000}},{"totalDuration":"16h00m","travelTime":57600000,"serviceNumber":"FG3803","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1884/logo_f_f_gertse_ba8Kxyx1AcUeMJb8bAS63u.png","routeDesc":"Cape Town to Johannesburg","carrierName":"FF Gertse","arrive":{"stop":{"country":"South Africa","carrier":"f f gertse","remCheck":"A 41, PARK STATION, RISSIK ST, CBD, JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"A 41, Park Station, Rissik Street, Johannesburg C B D","suburb":"Johannesburg","id":46737,"remotecode":"JOHANNESBURG"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 10:40:00","dateTimeMS":1737801600000},"price":{"totalPrice":300,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":300,"discountID":"ADULT"}]},"carrierCode":"ffgertse","id":"sMa.p542Euen74ocIm8L76ecvw-FNiNCOvnvsKvGJYw01KQ98QLySAx7wRgSNcQyOE-uV3XGtmyfQYJHqbd-VYSCRrYVTfZJNdZ7V3QCZIFM24rLEKKKdAPJdxgGA.XuaX4w6RDY29WZbNVGbHjDhNA","depart":{"stop":{"country":"South Africa","carrier":"f f gertse","remCheck":"1 OLD MARINE DR, FORESHORE","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"1 Old Marine Drive, Foreshore","suburb":"Cape Town","id":46738,"remotecode":"CAPE TOWN"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 18:40:00","dateTimeMS":1737744000000}},{"totalDuration":"16h20m","travelTime":58800000,"serviceNumber":"DE7527","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1879/logo_delta_coaches_oSddEafjkEwkb1wso7NsYC.png","routeDesc":"Cape Town to Johannesburg","carrierName":"Delta Coaches","arrive":{"stop":{"country":"South Africa","carrier":"delta coaches","remCheck":"PARK STATION, 41 RISSIK STREET , JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Park Station, 41 Rissik Street","suburb":"Johannesburg","id":24712,"remotecode":"6117"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 08:30:00","dateTimeMS":1737793800000},"price":{"totalPrice":310,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":310,"discountID":"ADULT"}]},"carrierCode":"deltacoaches","id":"sMa.p542Euen74oMNnsL56uw-0PZRjJ7V4zjyMPeDZBMyLQh-XMziZGr9JH3rCAzybZLjvg3Y-lC1A-TzbtmWZCCQsoNSd5NNdJzN3wiYJVM3.bbYK6edHvJdsB2emQz0Z.NhPTUwux0fLw","depart":{"stop":{"country":"South Africa","carrier":"delta coaches","remCheck":"TRAIN STATION,1 OLD MARINE DRIVE, CAPE TOWN , CAPE TOWN","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"Train Station, 1 Old Marine Drive, Cape Town","suburb":"Cape Town","id":24714,"remotecode":"6617"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 16:10:00","dateTimeMS":1737735000000}},{"totalDuration":"17h45m","travelTime":63900000,"serviceNumber":"DE7505","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1879/logo_delta_coaches_oSddEafjkEwkb1wso7NsYC.png","routeDesc":"Cape Town to Johannesburg","carrierName":"Delta Coaches","arrive":{"stop":{"country":"South Africa","carrier":"delta coaches","remCheck":"PARK STATION, 41 RISSIK STREET , JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Park Station, 41 Rissik Street","suburb":"Johannesburg","id":24712,"remotecode":"6117"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 11:45:00","dateTimeMS":1737805500000},"price":{"totalPrice":310,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":310,"discountID":"ADULT"}]},"carrierCode":"deltacoaches","id":"sMa.p542Euen74oMNmsL56uw-0PZRjJ7V4zjyMPeBZhMyLQh-XMziYWj9JH3rCAzybZLjvg3Y-lC1A-TzbteXZCCQsoNSd5NNdJzN3gGfIFM3.bbYK6edHvJdsB2emQz0Z.NhPTUwux0dLQ","depart":{"stop":{"country":"South Africa","carrier":"delta coaches","remCheck":"TRAIN STATION,1 OLD MARINE DRIVE, CAPE TOWN , CAPE TOWN","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"Train Station, 1 Old Marine Drive, Cape Town","suburb":"Cape Town","id":24714,"remotecode":"6617"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 18:00:00","dateTimeMS":1737741600000}},{"totalDuration":"18h15m","travelTime":65700000,"serviceNumber":"AW1001","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1868/logo_apmwc_u6CwEdB3raDHHTbVdjt3Mc.png","routeDesc":"Cape Town to Johannesburg","carrierName":"African People Mover WC","arrive":{"stop":{"country":"South Africa","carrier":"apmwc","remCheck":"JOHANNESBURG PARK STATION , JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"JOHANNESBURG PARK STATION","suburb":"Johannesburg","id":33440,"remotecode":"3340"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 11:15:00","dateTimeMS":1737803700000},"price":{"totalPrice":360,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":360,"discountID":"ADULT"}]},"carrierCode":"apmwc","id":"sMa.p540Euen744QJn8L8.-090rgP3c2Mq3jvMvqDYQ0yLxJ5Xs3nfmjjJnv3DRH2aZPloQvZ.1W1Aubqbd-VYSCRrYRTdJdIdZ7V3h2YI1Mp4Kq0Kbv5d4EJv1qejByGLo0m","depart":{"stop":{"country":"South Africa","carrier":"apmwc","remCheck":"RAILWAY STATION , CAPE TOWN","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"RAILWAY STATION","suburb":"Cape Town","id":33420,"remotecode":"3324"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 17:00:00","dateTimeMS":1737738000000}},{"totalDuration":"17h30m","travelTime":63000000,"serviceNumber":"ET3071","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/9421/logo_eagle_liner_transport_1KAksQrwXHhGoH632g6fuz.jpg","routeDesc":"Cape Town to Johannesburg","carrierName":"Eagle Liner Transport","arrive":{"stop":{"country":"South Africa","carrier":"eagle liner transport","remCheck":"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK ST, JOHANNESBURG, 2000 , JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK STREET","suburb":"Johannesburg","id":48121,"remotecode":"10293"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 10:30:00","dateTimeMS":1737801000000},"price":{"totalPrice":400,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":400,"discountID":"ADULT"}]},"carrierCode":"eaglelinertransport","id":"sMa.p542Euen74oMNnsL47ucm1PlXg5jP8jm-abHBPEx1MAx6Ws7-YWLiJ3vtCxf1cZLkoQnf5VS1AO.0ct2XZiWQroNWdJFNdZ7IwgKbJ1Y34bXAKaaeAPJA3B2e-XuHMI06UUBY2G9uYVmqAE2FNNWD8A","depart":{"stop":{"country":"South Africa","carrier":"eagle liner transport","remCheck":"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION, 8001 , CAPE TOWN","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION","suburb":"Cape Town","id":48089,"remotecode":"10267"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 17:00:00","dateTimeMS":1737738000000}},{"totalDuration":"18h25m","travelTime":66300000,"serviceNumber":"IX7067","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/6639/logo_intercity_xpress_4wwBPNTXSJHtuZm1HG5EQF.jpg","routeDesc":"Cape Town to Johannesburg","carrierName":"Intercity Xpress","arrive":{"stop":{"country":"South Africa","carrier":"intercity xpress","remCheck":"JOHANNESBURG PARK STATION (BAY21) ,96 RISSIK ST, JOHANNESBURG, 2000 , JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"JOHANNESBURG PARK STATION (BAY21) , 96 RISSIK STREET","suburb":"Johannesburg","id":48262,"remotecode":"10293"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 12:25:00","dateTimeMS":1737807900000},"price":{"totalPrice":420,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":420,"discountID":"ADULT"}]},"carrierCode":"intercityxpress","id":"sMa.p540Euen744QImcL04fQvw.ZXmYTF9jm6dLGcZA43KhJ4VM3mYGznJ3P3DhH2apT5og.a8VaoAOb1at-WZiSRp4FSdZZQd57K2gCaJ1Y24rXAKKaAAe9Ewxyd.Wb2L5BDVzIMwl4AUU.rHT7m","depart":{"stop":{"country":"South Africa","carrier":"intercity xpress","remCheck":"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION, 8001 , CAPE TOWN","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"BUS TERMINAL, OLD MARINE DRIVE ,CAPE TOWN STATION","suburb":"Cape Town","id":48230,"remotecode":"10267"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 18:00:00","dateTimeMS":1737741600000}},{"totalDuration":"18h15m","travelTime":65700000,"serviceNumber":"AW1081","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1868/logo_apmwc_u6CwEdB3raDHHTbVdjt3Mc.png","routeDesc":"Cape Town to Johannesburg","carrierName":"African People Mover WC","arrive":{"stop":{"country":"South Africa","carrier":"apmwc","remCheck":"JOHANNESBURG PARK STATION , JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"JOHANNESBURG PARK STATION","suburb":"Johannesburg","id":33440,"remotecode":"3340"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 12:15:00","dateTimeMS":1737807300000},"price":{"totalPrice":470,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":470,"discountID":"ADULT"}]},"carrierCode":"apmwc","id":"sMa.p540Euen744QJm8L8.-090rgP3cWMq3jvP.CCawwwLBJ5Xs3nfmjjJnv3DRH2aZPloQvZ8FW1Aubqbd-VYSCRrYRTd5dIdZ7V3h2fIlMp4Kq0Kbv5d4EJv1qejByGLoUm","depart":{"stop":{"country":"South Africa","carrier":"apmwc","remCheck":"RAILWAY STATION , CAPE TOWN","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"RAILWAY STATION","suburb":"Cape Town","id":33420,"remotecode":"3324"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 18:00:00","dateTimeMS":1737741600000}},{"totalDuration":"18h55m","travelTime":68100000,"serviceNumber":"AW1010","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1868/logo_apmwc_u6CwEdB3raDHHTbVdjt3Mc.png","routeDesc":"Cape Town to Johannesburg","carrierName":"African People Mover WC","arrive":{"stop":{"country":"South Africa","carrier":"apmwc","remCheck":"JOHANNESBURG PARK STATION , JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"JOHANNESBURG PARK STATION","suburb":"Johannesburg","id":33440,"remotecode":"3340"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 12:55:00","dateTimeMS":1737809700000},"price":{"totalPrice":530,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":530,"discountID":"ADULT"}]},"carrierCode":"apmwc","id":"sMa.p540Euen744QJl8L8.-090rgP3cyNq3jtNPOIYwo5KBJ5Xs3nfmjjJnv3DRH2aZPloQvZ8FW1Aubqbd-VYSCRrYRTd5NIdZ7V3h2eJlMp4Kq0Kbv5d4EJv1qejByGLown","depart":{"stop":{"country":"South Africa","carrier":"apmwc","remCheck":"RAILWAY STATION , CAPE TOWN","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"RAILWAY STATION","suburb":"Cape Town","id":33420,"remotecode":"3324"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 18:00:00","dateTimeMS":1737741600000}},{"totalDuration":"19h45m","travelTime":71100000,"serviceNumber":"IB1639","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1892/def_logo_intercape_budgetliner_s2HhDfBjdxsksoriXMEjHd.png","routeDesc":"Cape Town to Johannesburg","carrierName":"Intercape Budgetliner","arrive":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )","suburb":"Johannesburg","id":1182,"remotecode":"JOHANNESBURG"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 12:45:00","dateTimeMS":1737809100000},"price":{"totalPrice":550,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":550,"discountID":"ADULT"}]},"carrierCode":"intercape","id":"sMa.p540Euen744cGmsL04fQvw.ZfnZiQt3.nM.KBYw4xKhJ7Ws7gZmnkIHnjDRf1ZZD50H64jUXRfYGJcqXoHFHu0fQxB.MvAoPK3wKeJVI15LbCKKadAO9CwR6G.XqFK4wlJERFvAUZNSLpHSWQNsjghkjCjWunb9A9X2DS","depart":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )","suburb":"Cape Town","id":1184,"remotecode":"CAPE TOWN"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 17:00:00","dateTimeMS":1737738000000}},{"totalDuration":"18h30m","travelTime":66600000,"serviceNumber":"IM0105","availableSeats":8,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1892/def_logo_intercape_mainliner_aModybrB325AfEoJfPAHX5.png","routeDesc":"Cape Town to Johannesburg","carrierName":"Intercape Mainliner","arrive":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )","suburb":"Johannesburg","id":1182,"remotecode":"JOHANNESBURG"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 13:30:00","dateTimeMS":1737811800000},"price":{"totalPrice":550,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":550,"discountID":"ADULT"}]},"carrierCode":"intercape","id":"sMa.p540Euen744AOnsL04fQvw.ZfnZiQt3npMPKBYgk2KBJ7WszkYGPgJn3sDRDzb5r50H64jUXRfYGJcqXoHFHu0fQxB.MvAoPK3wKeJVI15LbMKKadAO9CwR6G.XqFK4wkI0FFvAUcNSLpHSWQNsjghkjCjWunb988WGPe","depart":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )","suburb":"Cape Town","id":1184,"remotecode":"CAPE TOWN"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 19:00:00","dateTimeMS":1737745200000}},{"totalDuration":"19h00m","travelTime":68400000,"serviceNumber":"GP13379","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1889/logo_greyhound_premium_goGSwKyCM6ycEXaVPCVSrh.png","routeDesc":"Cape Town to Johannesburg","carrierName":"Greyhound Premium","arrive":{"stop":{"country":"South Africa","carrier":"greyhound premium","remCheck":"Park City Transit Centre, 96 Rissik St, Johannesburg CBD , JOHANNESBURG","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Park City Transit Centre, 96 Rissik Street, Johannesburg Cbd","suburb":"Johannesburg","id":45668,"remotecode":"8720"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 13:00:00","dateTimeMS":1737810000000},"price":{"totalPrice":650,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":650,"discountID":"ADULT"}]},"carrierCode":"greyhoundpremium","id":"sMa.p542Euen74oMNnML6.eUz2fpLg5nN9C6ybrfcfg8yLghzQM3qY23oInjsCQz8a5Ljvgff-lWoAOb1at-WZiSRp4FSdZZQd57K2gCaJ1Y247fFKKaAAe9GxByd.Wb2L5BDVzIMwl4AX0ftHjvmPg","depart":{"stop":{"country":"South Africa","carrier":"greyhound premium","remCheck":"Long Distance Bus Facility, 1 Old Marine Drive, Cape Town , CAPE TOWN","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"Long Distance Bus Facility, 1 Old Marine Drive","suburb":"Cape Town","id":45616,"remotecode":"8717"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 18:00:00","dateTimeMS":1737741600000}},{"totalDuration":"20h15m","travelTime":72900000,"serviceNumber":"IS2615","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1892/def_logo_intercape_sleepliner_f9xiquDyhsd2A1kKNDsGCv.png","routeDesc":"Cape Town to Johannesburg","carrierName":"Intercape Sleepliner","arrive":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )","suburb":"Johannesburg","id":1182,"remotecode":"JOHANNESBURG"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 14:45:00","dateTimeMS":1737816300000},"price":{"totalPrice":650,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":650,"discountID":"ADULT"}]},"carrierCode":"intercape","id":"sMa.p540Euen744APmML04fQvw.ZfnZiQt3jmNfeJZwg4LRJ7Ws7gZmnjJ3riDBD0a5v50H64jUXRfYGJcqXoHFHu0fQxB.MvAoPK3wKeJVI15LbNK6adAO9CwR6G.XqFK4wjJERFvAUdNSHpHSWQNsjghkjCjWunb8E-X2Le","depart":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )","suburb":"Cape Town","id":1184,"remotecode":"CAPE TOWN"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 18:30:00","dateTimeMS":1737743400000}},{"totalDuration":"19h20m","travelTime":69600000,"serviceNumber":"IS2105","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1892/def_logo_intercape_sleepliner_f9xiquDyhsd2A1kKNDsGCv.png","routeDesc":"Cape Town to Johannesburg","carrierName":"Intercape Sleepliner","arrive":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )","suburb":"Johannesburg","id":1182,"remotecode":"JOHANNESBURG"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 13:20:00","dateTimeMS":1737811200000},"price":{"totalPrice":690,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":690,"discountID":"ADULT"}]},"carrierCode":"intercape","id":"sMa.p540Euen744cGlsL04fQvw.ZfnZiQt3LtM.WGZwk0KhJ7Ws7gZmnjIXjiDBD0a5v50H64jUXRfYGJcqXoHFHu0fQxB.MvAoPK3wKeJVI15LbNKKadAO9CwR6G.XqFK4wkIkFFvAUeNSHlHSWQNsjghkjCjWunb8E-WGPe","depart":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )","suburb":"Cape Town","id":1184,"remotecode":"CAPE TOWN"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 18:00:00","dateTimeMS":1737741600000}},{"totalDuration":"26h00m","travelTime":93600000,"serviceNumber":"IM0221","availableSeats":10,"groupID":"ungrouped","icon":"https://cf-content.computicket.com/bus/1892/def_logo_intercape_mainliner_aModybrB325AfEoJfPAHX5.png","routeDesc":"Cape Town to Johannesburg","carrierName":"Intercape Mainliner","arrive":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Intercape Office, C/O Rissik and Wolmarans Street (Johannesburg Station)","province":"Johannesburg","citycode":"ZAZAJOHANNESBURG","city":"Johannesburg","description":"Intercape Office, C/O Rissik and Wolmarans Street ( Johannesburg Station )","suburb":"Johannesburg","id":1182,"remotecode":"JOHANNESBURG"},"timezone":"+02:00","dateTimeLocal":"2025-01-25 20:15:00","dateTimeMS":1737836100000},"price":{"totalPrice":860,"numPax":1,"currency":"ZAR","prices":[{"quantity":1,"individualPrice":860,"discountID":"ADULT"}]},"carrierCode":"intercape","id":"sMa.p542Euen744APnML04fQvw.ZfnZiQv3PrP.SBYA0yMA59XMznZ2LnInPjDxDxceCVw3rInCrSfPuNEKfmGl7lzPM3F-FQd57K2gCaJ1c26LbAKKaAAvJCxByC.36FLowiIEFYvgUVLifxbDn8U6L3uEXN7lTHFqA-WA","depart":{"stop":{"country":"South Africa","carrier":"intercape","remCheck":"Unit 2 Intercape Office, Old Marine Drive (Cape Town Station)","province":"Cape Town","citycode":"ZAZACAPETOWN","city":"Cape Town","description":"Unit 2 Intercape Office, Old Marine Drive ( Cape Town Station )","suburb":"Cape Town","id":1184,"remotecode":"CAPE TOWN"},"timezone":"+02:00","dateTimeLocal":"2025-01-24 18:15:00","dateTimeMS":1737742500000}}]}}}';

	// Analyse and save to the database
	process_data($response, $ctk_date, $route_no,$ctk_from, $ctk_to, $from_name, $to_name);
}

function process_data($json_string, $ctk_date, $route_no, $ctk_from, $ctk_to, $from_name, $to_name)
{
	// global $ctk_carrier_names, $carriers_not_found;
	global $ctk_carrier_names, $ctk_stops;

	// Decode JSON string
	$data = json_decode($json_string, true);
	
	// Initialize variables
	$results = [];
	$trips = array();
	$carrier_names = [];
	$ic_carriers = ['Intercape Budgetliner', 'Intercape Mainliner', 'Big Sky Intercity', 'Intercape', 'Intercape Inter-Connect', 'Intercape Sleepliner', 'Intercape Express'];
	$gotic = false;
	
	$trips = $data['jsonData']['data']['availability'];

	if (count($trips) == 0)
	{
		$from_stop = $ctk_from;
		$to_stop = $ctk_to;
		log_event("No services available for this route/date combination: [$route_no] $ctk_from to $ctk_to on $ctk_date\r\n");

		// "No services available "" - when NOTHING comes back from computicket - when this happens, you must still do the check to see if our bus is running that day.

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
				log_event("No services available for this route/date combination: [$route_no] $ctk_from to $ctk_to on $ctk_date");

				// Check if Intercape has routes running that day
				$bits = explode(",", $route_no);
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

				log_event("No services available for this route/date combination: [$route_no] $ctk_from to $ctk_to on $ctk_date\r\n");

				//"No services available "" - when NOTHING comes back from computicket - when this happens, you must still do the check to see if our bus is running that day.
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
				addToCtkCarriers($carrier);
				carrier_list();
				getCarrierNames();
			}
			
			// Get carrier code and serial
			$carrier_data = searchCarrierList($carrier);
			$carrier_serial = $carrier_data['SERIAL'];
			$carrier_code = "";
			
			//echo "Record data: $arraive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_stop, $position, $price, $route_name, $route_no, $search_date, $to_stop\n";
			add_to_log($arraive_time, $available_seats, $carrier_code, $carrier_serial, $date_logged, $depart_time, $duration, $from_name, $position, $price, $route_name, $route_no, $search_date, $to_name);

			$i++;
		} // End of foreach

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

		// Check 3: 

		// OUTPUT ALL CARRIERS FOUND
		if (count($carrier_names) > 0)
		{
			log_event("------------\n" . "CARRIER LIST" . "\n------------" . "\r\n" . json_encode($carrier_names) . "\r\n");
		}
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

	oci_free_statement($cursor);

}

function addToCtkCarriers($new_carrier)
{
	global $conn;

	$sql = "INSERT INTO CTK_CARRIERS (NAME, SERIAL) VALUES (:NAME, CTK_CARRIER_SEQ.NEXTVAL)";
	$cursor = oci_parse($conn, $sql);

	oci_bind_by_name($cursor, ':NAME', $new_carrier);

	oci_execute($cursor);

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

function getCarrierNames() 
{
	global $ctk_carrier_names, $carrier_list;

	$results = array();

	foreach ($carrier_list as $carrier) 
	{
		if (isset($carrier['NAME'])) 
		{
			$results[] = $carrier['NAME'];
		}
	}

	$ctk_carrier_names = $results;
}

function isNotEmpty($value) 
{
    return !empty($value);
}

function log_event($message) 
{
    $log_file = '/tmp/ctkerr.log';
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

function send_error_email($html_message, $text_message)
{
	// $message = '';
	$email_list = ["keith@intercape.co.za", "quintin@moderndaystrategy.com"];
	$from = $noreply_email;
	$subject = "CTK Crawl Error Report - " . date("Y-m-d H:i:s");
	// $html_message .= "Error list: <br>";
	// $text_message .= "Error list: \n";

	$mail = new html_mime_mail('X-Mailer: Html Mime Mail Class');
	$mail->add_html($html_message, $text_message);
	$mail->build_message();

	foreach ($email_list as $email_address) 
	{
		$mail->smtp_send($from, $email_address);
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
