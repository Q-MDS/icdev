<?php
// ob_start();
// require_once ("../php3/oracle.inc");
// require_once ("../php3/misc.inc");
// require_once ("../php3/sec.inc");

// if (!open_oracle()) { Exit; };
// if (!AllowedAccess("")) { Exit; };

$ajax_data = file_get_contents("php://input");
$json_data = json_decode($ajax_data);

$action = $json_data->action;

switch ($action)
{
	case 0:
		$screen_id = $json_data->screen_id;
		$screen_id = intval($screen_id);

		$route_no = $json_data->route_no;
		$route_no = htmlspecialchars($route_no, ENT_QUOTES, 'UTF-8');
		$route_no = trim($route_no);
		
		$brand = $json_data->brand;
		$brand = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
		$brand = trim($brand);
		
		$route_description = $json_data->route_description;
		$route_description = htmlspecialchars($route_description, ENT_QUOTES, 'UTF-8');

		add_route($screen_id, $route_no, $brand, $route_description);
	break;
	case 1:
		$screen_id = $json_data->screen_id;
		$screen_id = intval($screen_id);

		remove_route($screen_id);
	break;
}

// OCI Only
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

function add_route($screen_id, $route_no, $brand, $route_description)
{
	echo "XXX: $screen_id, $route_no,$brand, $route_description" . " >>> " . strlen(trim($brand));
	$conn = oci_conn();

	$sql = "INSERT INTO DEPARTURE_TV_SETTINGS (SCREEN_ID, BRAND, ROUTE_NO, ROUTE_DESCRIPTION) VALUES ($screen_id, '$brand', '$route_no', '$route_description')";
	
	$cursor = oci_parse($conn, $sql);
	
	oci_execute($cursor);

	oci_free_statement($cursor);

	oci_close($conn);

	echo 1;

	/*global $conn;

	$cursor = ora_open($conn);

	try 
	{
		$sql = "INSERT INTO DEPARTURE_TVS (SCREEN_ID, NAME, BRANCH, STOP_SERIAL, IS_ACTIVE) VALUES (SCREEN_ID_SEQ.NEXTVAL, '$tv_name', '$tv_branch', $tv_stop_serial, '1')";
		ora_parse($cursor, $sql);
		ora_exec($cursor);
		
		$result = '1';
	} 
	catch (Exception $e) 
	{
		$result = '0';
	}

	ora_close($cursor);

	echo $result;*/
}

function remove_route($screen_id)
{
	// OCI
	$record = array();

	$conn = oci_conn();

	$sql = "DELETE FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = $screen_id";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	oci_free_statement($cursor);
	oci_close($conn);

	echo 1;

	// ORA
	/*global $conn;

	$cursor = ora_open($conn);

	$record = array();

	$sql = "SELECT * FROM DEPARTURE_TVS WHERE SCREEN_ID = $tv_id)";
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC))  
	{
		$record = $row;
	}

	ora_close($cursor);

	echo json_encode($record);*/
}

