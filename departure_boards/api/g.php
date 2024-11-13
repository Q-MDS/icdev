<?php
// echo '{
// 	"brand": "BS",
// 	"route": "2151",
// 	"description": "Bloemfontein via Colesberg via Beaufort West"
// }';

/*$ajax_data = file_get_contents("php://input");
$json_data = json_decode($ajax_data);

$screen_id = $json_data->screen_id;*/
$screen_id = 4;

// echo "PHP says that the screen id is: " . $screen_id;

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
		exit;
	} 
	else 
	{
		// echo "Connection succeeded";
	}

	return $conn;
}

function get_data()
{
	global $screen_id;

	$conn = oci_conn();
	
	$sql = "SELECT * FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = :screen_id";

	$stid = oci_parse($conn, $sql);

	oci_bind_by_name($stid, ':screen_id', $screen_id);

	oci_execute($stid);

	// $stops = array("Durbs", "Bloem", "Fartville", "Buur");
	

	$records = array();

	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$brand = TRIM($row['BRAND']);
		$route_no = TRIM($row['ROUTE_NO']);
		$route_desc = TRIM($row['ROUTE_DESCRIPTION']);

		$stops = get_route_stops($route_no, "PAARL");

		$records = array(
			"brand" => $brand,
			"route_no" => $route_no,
			"route_desc" => $route_desc,
			"stops" => $stops
		);	
	}

	oci_free_statement($stid);

	oci_close($conn);

	if (count($records) == 0)
	{
		echo 0;
	}
	else 
	{
		echo json_encode($records);
	}
}

function get_route_stops($route, $current_stop)
{
	//select short_name from route_stops where route_no='2105' and date_from<=20241108 and date_to>=20241108 order by stop_order;
	$conn = oci_conn();

	$date_from = date("Ymd");
	$date_to = date("Ymd");
	
	$sql = "SELECT SHORT_NAME from ROUTE_STOPS WHERE ROUTE_NO='$route' AND DATE_FROM<=$date_from AND DATE_TO>=$date_to ORDER BY STOP_ORDER";

	$stid = oci_parse($conn, $sql);

	oci_execute($stid);

	// $stops = array("Durbs", "Bloem", "Fartville", "Buur");

	$result = array();
	$records = array();

	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$short_name = TRIM($row['SHORT_NAME']);
		$result[] = $short_name;
	}

	oci_free_statement($stid);

	oci_close($conn);

	foreach ($result as $key => $value) 
	{
		if ($value == $current_stop)
		{
			$records = array_slice($result, $key);
			break;
		}
	}

	$filtered_array = array_filter($records, function($item) {
		return stripos($item, 'DEPOT') === false;
	});

	$filtered_array = array_unique($filtered_array);
	$filtered_array = array_values($filtered_array);

	return $filtered_array;
}

get_data($screen_id);
