<?php
// echo '{
// 	"brand": "BS",
// 	"route": "2151",
// 	"description": "Bloemfontein via Colesberg via Beaufort West"
// }';

$ajax_data = file_get_contents("php://input");
$json_data = json_decode($ajax_data);

$screen_id = $json_data->screen_id;

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

	$records = array();

	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$brand = $row['BRAND'];
		$route_no = $row['ROUTE_NO'];
		$route_desc = $row['ROUTE_DESCRIPTION'];

		$records = array(
			"brand" => $brand,
			"route_no" => $route_no,
			"route_desc" => $route_desc
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

get_data($screen_id);