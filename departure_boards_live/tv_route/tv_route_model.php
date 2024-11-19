<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

$_check_gets_return = true; 

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

		$route_no_b = $json_data->route_no_b;
		$route_no_b = htmlspecialchars($route_no_b, ENT_QUOTES, 'UTF-8');
		$route_no_b = trim($route_no_b);
		$brand_b = $json_data->brand_b;
		$brand_b = htmlspecialchars($brand_b, ENT_QUOTES, 'UTF-8');
		$brand_b = trim($brand_b);
		$route_description_b = $json_data->route_description_b;
		$route_description_b = htmlspecialchars($route_description_b, ENT_QUOTES, 'UTF-8');
		$route_description_b = trim($route_description_b);

		add_route($screen_id, $route_no, $brand, $route_description, $route_no_b, $brand_b, $route_description_b);
	break;
	case 1:
		$screen_id = $json_data->screen_id;
		$screen_id = intval($screen_id);

		remove_route($screen_id);
	break;
}

function add_route($screen_id, $route_no, $brand, $route_description, $route_no_b, $brand_b, $route_description_b)
{
	global $conn;

	$route_description = TRIM($route_description);

	$sql = "INSERT INTO DEPARTURE_TV_SETTINGS (SCREEN_ID, BRAND, ROUTE_NO, ROUTE_DESCRIPTION, BRAND_B, ROUTE_NO_B, ROUTE_DESCRIPTION_B) VALUES ($screen_id, '$brand', '$route_no', '$route_description', '$brand_b', '$route_no_b', '$route_description_b')";
	
	$cursor = oci_parse($conn, $sql);
	
	oci_execute($cursor);

	oci_free_statement($cursor);

	oci_close($conn);

	echo 1;
}

function remove_route($screen_id)
{
	global $conn;

	$record = array();

	$sql = "DELETE FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = $screen_id";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	oci_free_statement($cursor);
	oci_close($conn);

	echo 1;
}

