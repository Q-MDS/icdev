<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

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

function add_route($screen_id, $route_no, $brand, $route_description)
{
	global $conn;

	$cursor = ora_open($conn);

	$sql = "INSERT INTO DEPARTURE_TV_SETTINGS (SCREEN_ID, BRAND, ROUTE_NO, ROUTE_DESCRIPTION) VALUES ($screen_id, '$brand', '$route_no', '$route_description')";
	
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	oci_close($conn);

	echo 1;
}

function remove_route($screen_id)
{
	global $conn;
	// OCI
	$record = array();

	$cursor = ora_open($conn);

	$sql = "DELETE FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = $screen_id";

	ora_parse($cursor, $sql);
	ora_exec($cursor);

	ora_close($cursor);

	echo 1;
}

