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
		// Save
		$tv_name = $json_data->tv_name;
		$tv_name = htmlspecialchars($tv_name, ENT_QUOTES, 'UTF-8');

		$tv_branch = $json_data->tv_branch;
		$tv_branch = htmlspecialchars($tv_branch, ENT_QUOTES, 'UTF-8');
		
		$tv_stop_serial = $json_data->tv_stop_serial;
		$tv_stop_serial = htmlspecialchars($tv_stop_serial, ENT_QUOTES, 'UTF-8');

		save($tv_name, $tv_branch, $tv_stop_serial);
	break;
	case 1:
		$tv_id = $json_data->tv_id;
		$tv_id = intval($tv_id);

		getRecord($tv_id);
	break;
	case 2:
		// Update
		$tv_id = $json_data->tv_id;
		$tv_id = intval($tv_id);
		$tv_name = $json_data->tv_name;
		$tv_name = str_replace("'", "", $tv_name);
		$tv_name = TRIM($tv_name);
		$tv_branch = $json_data->tv_branch;
		$tv_branch = str_replace("'", "", $tv_branch);
		$tv_branch = TRIM($tv_branch);
		$tv_stop_serial = $json_data->tv_stop_serial;
		$tv_stop_serial = str_replace("'", "", $tv_stop_serial);
		
		update($tv_id, $tv_name, $tv_branch, $tv_stop_serial);
	break;
	case 3:
		// Remove
		$tv_id = $json_data->tv_id;
		remove($tv_id);
	break;
}

function save($tv_name, $tv_branch, $tv_stop_serial)
{
	global $conn;

	if ($tv_name != '' && $tv_branch != '0' && $tv_stop_serial != '0')
	{
		$sql = "INSERT INTO DEPARTURE_TVS (SCREEN_ID, NAME, BRANCH, STOP_SERIAL, IS_ACTIVE) VALUES (SCREEN_ID_SEQ.NEXTVAL, '$tv_name', '$tv_branch', $tv_stop_serial, '1')";
		
		$cursor = oci_parse($conn, $sql);
		
		oci_execute($cursor);

		oci_free_statement($cursor);

		oci_close($conn);

		echo 1;
	}
	else
	{
		echo 0;
	}
}

function getRecord($tv_id)
{
	global $conn;
	
	$record = array();

	$cursor = ora_open($conn);

	$sql = "SELECT * FROM DEPARTURE_TVS WHERE SCREEN_ID = $tv_id";
	
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC))
	{
		$record = $row;
	}

	ora_close($cursor);

	echo json_encode($record);
}

function update($tv_id, $tv_name, $tv_branch, $tv_stop_serial)
{
	global $conn;

	$cursor = ora_open($conn);

	try 
	{
		$sql = "UPDATE DEPARTURE_TVS SET NAME = '$tv_name', BRANCH = '$tv_branch', STOP_SERIAL = $tv_stop_serial WHERE SCREEN_ID = $tv_id";
		ora_parse($cursor, $sql);
		ora_exec($cursor);
		
		$result = '1';
	} 
	catch (Exception $e) 
	{
		$result = '0';
	}

	ora_close($cursor);

	echo $result;
}

function remove($tv_id)
{
	// ORA
	global $conn;

	$cursor = ora_open($conn);

	try 
	{
		$sql = "UPDATE DEPARTURE_TVS SET IS_ACTIVE = '0' WHERE SCREEN_ID = $tv_id";
		ora_parse($cursor, $sql);
		ora_exec($cursor);
		
		$result = '1';
	} 
	catch (Exception $e) 
	{
		$result = '0';
	}

	ora_close($cursor);

	echo $result;
}
