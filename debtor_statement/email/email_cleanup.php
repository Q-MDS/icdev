<?php
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

function fetch_email($conn)
{
	$conn = oci_conn();

	$data = array();

	$sql = "SELECT statement_email FROM debtors_info WHERE statement_email IS NOT NULL";
	
	$cursor = oci_parse($conn, $sql);

	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) {
		// $data[] = $row['AMOUNT'];
		$data[] = $row['STATEMENT_EMAIL'];
	}

	// Free the statement resource
	oci_free_statement($cursor);
	
	oci_close($conn);

	return $data;
}

function check($emails)
{
	foreach ($emails as $email)
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
		{
			// Add invalid email to the list
			$invalid_emails[] = array('email' => $email);
		}
	}

	echo "TOTAL EMAILS: " . count($emails) . "<br>";
	echo "INVALID EMAILS: " . count($invalid_emails) . "<br>";

	print_r($invalid_emails);
}

$data = fetch_email(oci_conn());

check($data);

die();