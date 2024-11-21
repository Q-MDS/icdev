<?php
// echo '{
// 	"brand": "BS",
// 	"route": "2151",
// 	"description": "Bloemfontein via Colesberg via Beaufort West"
// }';

$ajax_data = file_get_contents("php://input");
$json_data = json_decode($ajax_data);

$screen_id = $json_data->screen_id;
// $screen_id = 4;

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
		$brand_b = TRIM($row['BRAND_B']);
		
		$stop_serial = get_stop_serial($screen_id);
		$current_stop = TRIM(get_stop_name($stop_serial));
		$stops = get_route_stops($route_no, $current_stop);

		if ($brand_b == "")
		{
			$screen_layout = 0;
		}
		else if ($brand != "BI" && $brand_b == "BI")
		{
			$screen_layout = 1;
		}
		else if ($brand == "BI" && $brand_b != "BI")
		{
			$screen_layout = 2;
		}
		else if ($brand != "BI" && $brand_b != "BI")
		{
			$screen_layout = 3;
		}
		else if ($brand == "BI" && $brand_b == "BI")
		{
			$screen_layout = 4;
		}

		$records = array(
			"brand" => $brand,
			"route_no" => $route_no,
			"route_desc" => $route_desc,
			"stops" => $stops,
			"brand_b" => $brand_b,
			"screen_layout" => $screen_layout
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

function get_stop_serial($screen_id)
{
	$conn = oci_conn();
	
	$sql = "SELECT STOP_SERIAL FROM DEPARTURE_TVS WHERE SCREEN_ID = :screen_id";

	$stid = oci_parse($conn, $sql);

	oci_bind_by_name($stid, ':screen_id', $screen_id);

	oci_execute($stid);

	$row = oci_fetch_array($stid, OCI_ASSOC);

	$stop_serial = $row['STOP_SERIAL'];

	oci_free_statement($stid);

	oci_close($conn);

	return $stop_serial;
}

function get_stop_name($stop_serial)
{
	$conn = oci_conn();
	
	$sql = "SELECT SHORTNAME FROM STOP_DETAILS2 WHERE STOP_SERIAL = :stop_serial";

	$stid = oci_parse($conn, $sql);

	oci_bind_by_name($stid, ':stop_serial', $stop_serial);

	oci_execute($stid);

	$row = oci_fetch_array($stid, OCI_ASSOC);

	$stop_name = TRIM($row['SHORTNAME']);

	oci_free_statement($stid);

	oci_close($conn);

	return $stop_name;
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

	$result = array();
	$records = array();

	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$short_name = TRIM($row['SHORT_NAME']);
		$result[] = $short_name;
	}

	oci_free_statement($stid);

	oci_close($conn);

	if (count($result) == 0)
	{
		$filtered_array = array("0" => "-");
	}
	else 
	{
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
		array_shift($filtered_array);
		$filtered_array = array_values($filtered_array);
	}

	return $filtered_array;
}

get_data($screen_id);
