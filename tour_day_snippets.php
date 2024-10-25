<?php
ob_start();
require_once ("./php3/oracle.inc");
require_once ("./php3/misc.inc");
ob_end_clean();

global $conn;
$cursor = ora_open($conn);
$ajax_data = file_get_contents("php://input");
$json_data = json_decode($ajax_data);

$action = $json_data->action;
$data = $json_data->data;

switch ($action)
{
	case '0':
		add_snippet($data);
	break;
	case '1':
		edit_snippet($data);
	break;
	case '2':
		delete_snippet($data);
	break;
	case '3':
		fetch_snippets($data);
	break;
}

function start()
{
	echo "Test";
}

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

function fetch_snippets($data)
{
	$category = $data->category;
	$category = intval($category);

	$conn = oci_conn();
	
	$sql = "SELECT * FROM tour_day_snippets WHERE category = $category ORDER BY id";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	$snippets = array();
	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$snippets[] = $row;
	}

	oci_close($conn);
	
	echo json_encode($snippets);
}

function add_snippet($data)
{
	// Add snippet

	$category = $data->category;
	$snippet = $data->snippet;

	$snippet = str_replace("'","",$snippet);

	if ($snippet != '')
	{
		$conn = oci_conn();
		
		$sql = "INSERT INTO tour_day_snippets VALUES (TOUR_DAY_SNIPPETS_SEQ.NEXTVAL, $category, '$snippet')";
					
		$cursor = oci_parse($conn, $sql);
		oci_execute($cursor);
		oci_close($conn);
	}

	echo 1;
}

function edit_snippet($data)
{
	// Edit snippet
	$snippet_id = $data->snippet_id;
	$category = $data->category;
	$snippet = $data->snippet;

	$snippet = htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8');
	$snippet_escaped = "q'[" . $snippet . "]'";

	if ($snippet != '')
	{
		$conn = oci_conn();
		
		$sql = "UPDATE tour_day_snippets SET snippet = $snippet_escaped WHERE id = $snippet_id";
					
		$cursor = oci_parse($conn, $sql);
		oci_execute($cursor);
		oci_close($conn);
	}

	echo 1;
}

function delete_snippet($data)
{
	// Delete snippet

	$snippet_id = $data->snippet_id;
	$snippet_id = intval($snippet_id);

	if ($snippet_id != '' || $snippet_id != 0 || $snippet_id != null)
	{
		$conn = oci_conn();
		$sql = "DELETE FROM tour_day_snippets WHERE id = $snippet_id";
					
		$cursor = oci_parse($conn, $sql);
		oci_execute($cursor);
		oci_close($conn);
	}

	echo 1;
}

?>