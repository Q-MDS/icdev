<?php
// Global vars
$manager_id = '1';
$depot = 'CA';


get_vehicles();


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

function get_vehicles()
{
	global $depot;

	// OCI
	$conn = oci_conn();

	// ORA
	// global $conn;

	$sql = "SELECT serial, code, depot FROM vehicles WHERE is_current='Y' AND class in ('o','c') AND depot = '" . $depot . "' ORDER BY serial FETCH FIRST 10 ROWS ONLY";
	
	// OCI
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	// ORA
	// ora_parse($cursor, $sql);
	// ora_exec($cursor);

	// OCI
	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$serial = $row['SERIAL'];
		$code = $row['CODE'];
		$depot = $row['DEPOT'];
		
		echo '<div>' . $serial . ' > ' . $code . ' > ' . $depot . '</div>';
	}

	// ORA
	// while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
	// {
	// 	$serial = $row['SERIAL'];
		
	// 	echo '<div>' . $serial . '</div>';
	// }

	// OCI
	oci_close($conn);

	// ORA
	// ora_close($cursor);
}

function ora_vehicles()
{
	$conn = oci_conn();

	$cursor = ora_open($conn);

	$sql = "SELECT * FROM vehicles ORDER BY serial FETCH FIRST 10 ROWS ONLY";

	ora_parse($cursor, $sql);
	ora_exec($cursor);

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
	{
		$serial = $row['SERIAL'];
		
		echo '<div>' . $serial . '</div>';
	}
	ora_close($cursor);
}
?>