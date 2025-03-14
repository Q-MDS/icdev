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
		get_notes();
	break;
	case 1:
		// Add note
		$dropdown = $json_data->dropdown;
		
		add_note($dropdown);
	break;
	case 2:
		// Edit note
		$dropdown_serial = $json_data->dropdown_serial;
		$dropdown = $json_data->dropdown;

		edit_note($dropdown_serial, $dropdown);
	break;
	case 3:
		// Remove note
		$dropdown_serial = $json_data->dropdown_serial;

		remove_note($dropdown_serial);
	break;
	
}

// OCI Only
function oci_conn()
{
	$host = 'localhost';
	$port = '1521';
	$sid = 'XE';
	$username = 'SYSTEM';
	$password = 'dontletmedown4';

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

function getdata($cursor, $column_index) 
{
    $data = oci_result($cursor, $column_index);
    return $data !== false ? $data : '';
}

function get_notes()
{
	$data = array();

	$conn = oci_conn();

	$sql = "SELECT * FROM ROUTE_STOPS_NOTES_DROPDOWN WHERE active = 'Y' ORDER BY DROPDOWN ASC";
	$cursor = oci_parse($conn, $sql);
	
	oci_execute($cursor);


	while (ocifetch($cursor))
	{
		$data[] = array('dropdown_serial' => getdata($cursor, 2), 'dropdown' => getdata($cursor, 1));
	}

	echo json_encode($data);
}

function add_note($dropdown)
{
	$conn = oci_conn();

	$sql = "INSERT INTO ROUTE_STOPS_NOTES_DROPDOWN (DROPDOWN, DROPDOWN_SERIAL, ACTIVE) VALUES (:dropdown, ROUTE_STOPS_NOTES_DROPDOWN_SEQ.nextval, 'Y') RETURNING DROPDOWN_SERIAL INTO :dropdown_serial";
	
	$cursor = oci_parse($conn, $sql);

	// Bind the input and output variables
    oci_bind_by_name($cursor, ':dropdown', $dropdown);
    oci_bind_by_name($cursor, ':dropdown_serial', $dropdown_serial, 32);
	
	oci_execute($cursor);

	oci_free_statement($cursor);

	oci_close($conn); // ???

	echo $dropdown_serial;
}

function edit_note($dropdown_serial, $dropdown)
{
	$conn = oci_conn();
	try
	{
		$sql = "UPDATE ROUTE_STOPS_NOTES_DROPDOWN SET DROPDOWN = :dropdown WHERE DROPDOWN_SERIAL = :dropdown_serial";
			
		$cursor = oci_parse($conn, $sql);

		oci_bind_by_name($cursor, ':dropdown_serial', $dropdown_serial);
		oci_bind_by_name($cursor, ':dropdown', $dropdown);

		oci_execute($cursor);

		oci_free_statement($cursor);

		oci_commit($conn);

		echo 1;
	} 
	catch (Exception $e) 
	{
		oci_rollback($conn);
		
		echo 0;
	} 
	finally 
	{
		// Close the connection
	}


}

function remove_note($dropdown_serial)
{
	$conn = oci_conn();

	$sql = "DELETE FROM ROUTE_STOPS_NOTES_DROPDOWN WHERE DROPDOWN_SERIAL = $dropdown_serial";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	oci_free_statement($cursor);
	oci_close($conn);

	echo 1;
}

