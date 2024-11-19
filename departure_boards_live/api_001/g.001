<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

$ajax_data = file_get_contents("php://input");
$json_data = json_decode($ajax_data);

$screen_id = $json_data->screen_id;


// echo "PHP says that the screen id is: " . $screen_id;
// http://192.168.10.239/move/departure_boards/tv_app/index.php?s=100
// http://192.168.10.239/move/departure_boards/api/g.php

function get_data()
{
	global $conn, $screen_id;

	$cursor = ora_open($conn);
	
	$sql = "SELECT * FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = $screen_id";

	ora_parse($cursor, $sql);
	ora_exec($cursor);

	$records = array();

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC))
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

	ora_close($cursor);

	if (count($records) == 0)
	{
		echo 0;
	}
	else 
	{
		echo json_encode($records);
	}
}

get_data();
?>