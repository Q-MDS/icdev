<?php
// echo "Got to tour_day_snippets.php: START";
ob_start();
require_once ("../php3/oracle.inc");
// require_once ("../php3/colors.inc");
// require_once ("../php3/logs.inc");
require_once ("../php3/misc.inc");
require_once ("../php3/sec.inc");
// require_once ("../php3/opstimes.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };
// get_colors(getenv("REMOTE_USER"));
// require_once("tour-include.phtml");




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
	case '4':
		fetch_all();
	break;
}

function start()
{
	echo "Test";
}

function oci_conn()
{
	// $host = 'localhost';
	// $port = '1521';
	// $sid = 'XE';
	// $username = 'SYSTEM';
	// $password = 'dontletmedown3';

	// $conn = oci_connect($username, $password, "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port)))(CONNECT_DATA=(SID=$sid)))");

	// if (!$conn) 
	// {
	// 	$e = oci_error();
	// 	// echo "Connection failed: " . $e['message'];
	// 	exit;
	// } 
	// else 
	// {
	// 	// echo "Connection succeeded";
	// }

	// return $conn;
}

function fetch_snippets($data)
{
	global $conn;
	$cursor = ora_open($conn);

	$category = $data->category;
	$category = intval($category);
	
	$sql = "SELECT * FROM tour_day_snippets WHERE category = $category ORDER BY id";
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	$snippets = array();
	// while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	while (ora_fetch_into($cursor, $data, ORA_FETCHINTO_ASSOC))
	{
		$snippets[] = $data;
	}

	ora_close($cursor);

	echo json_encode($snippets);
}

function add_snippet($data)
{
	// Add snippet
	global $conn;
	$cursor = ora_open($conn);

	$category = $data->category;
	$snippet = $data->snippet;

	$snippet = str_replace("'","",$snippet);

	if ($snippet != '')
	{
		// $conn = oci_conn();
		$sql = "INSERT INTO tour_day_snippets VALUES (TOUR_DAY_SNIPPETS_SEQ.NEXTVAL, $category, '$snippet')";
					
		ora_parse($cursor, $sql);
		ora_exec($cursor);
	}

	ora_close($cursor);

	echo 1;
}

function edit_snippet($data)
{
	// Edit snippet
	global $conn;
	$cursor = ora_open($conn);

	$snippet_id = $data->snippet_id;
	$category = $data->category;
	$snippet = $data->snippet;

	$snippet = htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8');
	$snippet_escaped = "q'[" . $snippet . "]'";

	if ($snippet != '')
	{
		$sql = "UPDATE tour_day_snippets SET snippet = $snippet_escaped WHERE id = $snippet_id";
					
		ora_parse($cursor, $sql);
		ora_exec($cursor);
	}

	ora_close($cursor);

	echo 1;
}

function delete_snippet($data)
{
	// Delete snippet
	global $conn;
	$cursor = ora_open($conn);

	$snippet_id = $data->snippet_id;
	$snippet_id = intval($snippet_id);

	if ($snippet_id != '' || $snippet_id != 0 || $snippet_id != null)
	{
		$sql = "DELETE FROM tour_day_snippets WHERE id = $snippet_id";
					
		ora_parse($cursor, $sql);
		ora_exec($cursor);
	}

	ora_close($cursor);

	echo 1;
}

function fetch_all()
{
	global $conn;
	$cursor = ora_open($conn);

	$data = array();
	$common = array();
	$trip = array();
	$places = array();
	$other = array();
	
	//$rcur = ora_open($conn);
	$sql = "SELECT * FROM tour_day_snippets ORDER BY id";
	ora_parse($cursor, $sql);
	ora_exec($cursor);
	
	$snippets = array();
	
	while (ora_fetch_into($cursor, $data, ORA_FETCHINTO_ASSOC))
	{
		$category = $data['CATEGORY'];
		switch ($category) {
			case 1:
				$common[] = $data;
				break;
			case 2:
				$trip[] = $data;
				break;
			case 3:
				$places[] = $data;
				break;
			case 4:
				$other[] = $data;
				break;
		}
	}

	$data['common'] = $common;
	$data['trip'] = $trip;
	$data['places'] = $places;
	$data['other'] = $other;

	ora_close($cursor);

	echo json_encode($data);
}

?>