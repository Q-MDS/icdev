<?php
ob_start();
require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");
require_once ("../php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

$ajax_data = file_get_contents("php://input");
$json_data = json_decode($ajax_data);

$mtr_action = $json_data->mtr_action;

switch ($mtr_action)
{
	case 0:
		$mtr_id = $json_data->mtr_id;
		$mtr_status = $json_data->mtr_status;
		did_read($mtr_id);
	break;
	case 1:
		$mtr_id = $json_data->mtr_id;
		$mtr_status = $json_data->mtr_status;
		did_not_read($mtr_id, $mtr_status);
	break;
	case 2:
		$bulletin_name = $json_data->bulletin_name;
		$bulletin_revision = $json_data->bulletin_revision;
		$bulletin_url = $json_data->bulletin_url;

		add_bulletin($bulletin_name, $bulletin_revision, $bulletin_url);
	break;
	case 3:
		$mtb_id = $json_data->mtb_id;
		$mtb_status = $json_data->mtb_status;
		set_priority($mtb_id, $mtb_status);
	break;
	
	case 4:
		$mtb_id = $json_data->mtb_id;
		remove_bulletin($mtb_id);
	break;
	case 5:
		$mtb_id = $json_data->mtb_id;
		$mtb_order = $json_data->mtb_order;
		set_order($mtb_id, $mtb_order);
	break;
	case 6:
		$mtb_id = $json_data->mtb_id;
		$mtb_active = $json_data->mtb_active;
		set_active($mtb_id, $mtb_active);
	break;
}

// function oci_conn()
// {
// 	$host = 'localhost';
// 	$port = '1521';
// 	$sid = 'XE';
// 	$username = 'SYSTEM';
// 	$password = 'dontletmedown3';

// 	$conn = oci_connect($username, $password, "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port)))(CONNECT_DATA=(SID=$sid)))");

// 	if (!$conn) 
// 	{
// 		$e = oci_error();
// 		// echo "Connection failed: " . $e['message'];
// 		exit;
// 	} 
// 	else 
// 	{
// 		// echo "Connection succeeded";
// 	}

// 	return $conn;
// }

function did_read($mtr_id)
{
	global $conn;
	$cursor = ora_open($conn);

	$now = strtotime("now");

	$sql = "UPDATE move_tech_bulletins_read SET mtr_status = 100, mtr_date_updated = $now  WHERE mtr_id = $mtr_id";
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	ora_close($cursor);
}

function did_not_read($mtr_id, $mtr_status)
{
	global $conn;
	$cursor = ora_open($conn);

	if ($mtr_status == 0)
	{
		$mtr_status = 1;
	}
	else 
	{
		$mtr_status = 2;
	}
	
	$now = strtotime("now");
	
	$sql = "UPDATE move_tech_bulletins_read SET mtr_status = $mtr_status, mtr_date_updated = $now  WHERE mtr_id = $mtr_id";
	
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	ora_close($cursor);
}

function add_bulletin($bulletin_name, $bulletin_revision, $bulletin_url)
{
	global $conn;
	
	$result = '0';

	$cursor = ora_open($conn);

	$add_date = strtotime(date("Y-m-d"));

	try 
	{
		$sql = "INSERT INTO move_tech_bulletins (mtb_id, mtb_name, mtb_url, mtb_date, mtb_revision, mtb_status, mtb_order) VALUES (MTB_ID_SEQ.NEXTVAL, '$bulletin_name', '$bulletin_url', $add_date, $bulletin_revision, 1, 99999)";
		ora_parse($cursor, $sql);
		ora_exec($cursor);
		
		$result = 1;
	} 
	catch (Exception $e) 
	{
		// echo "Error: " . $e->getMessage();
	}

	ora_close($cursor);

	echo $result;
}

function set_priority($mtb_id, $mtb_status)
{
	global $conn;
	
	$result = '0';
	
	$cursor = ora_open($conn);

	try 
	{
		$sql = "UPDATE move_tech_bulletins SET mtb_status = $mtb_status WHERE mtb_id = $mtb_id";
		ora_parse($cursor, $sql);
		ora_exec($cursor);

		$result = 1;
	} 
	catch (Exception $e) 
	{
		// echo "Error: " . $e->getMessage();
	}

	ora_close($cursor);

	echo $result;
}

function remove_bulletin($mtb_id)
{
	global $conn;

	$result = '0';

	$cursor = ora_open($conn);

	try 
	{
		$sql = "UPDATE move_tech_bulletins SET mtb_status = 0 WHERE mtb_id = $mtb_id";
		ora_parse($cursor, $sql);
		ora_exec($cursor);

		$result = 1;
	} 
	catch (Exception $e) 
	{
		// echo "Error: " . $e->getMessage();
	}

	ora_close($cursor);

	echo $result;
}

function set_order($mtb_id, $mtb_order)
{
	global $conn;
	
	$result = '0';

	$cursor = ora_open($conn);

	try 
	{
		$sql = "UPDATE move_tech_bulletins SET mtb_order = $mtb_order WHERE mtb_id = $mtb_id";
		ora_parse($cursor, $sql);
		ora_exec($cursor);

		$result = 1;
	} 
	catch (Exception $e) 
	{
		// echo "Error: " . $e->getMessage();
	}

	ora_close($cursor);

	echo $result;
}

function set_active($mtb_id, $mtb_active)
{
	global $conn;

	$result = '0';

	$cursor = ora_open($conn);

	try 
	{
		$sql = "UPDATE move_tech_bulletins SET mtb_active = $mtb_active WHERE mtb_id = $mtb_id";
		ora_parse($cursor, $sql);
		ora_exec($cursor);

		$result = 1;
	} 
	catch (Exception $e) 
	{
		// echo "Error: " . $e->getMessage();
	}

	ora_close($cursor);

	echo $result;
}
?>