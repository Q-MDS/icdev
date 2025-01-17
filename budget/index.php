<?php
$year2425 = array(
	"Jun" => "'062024'",
	"Jul" => "'062024','072024'",
	"Aug" => "'062024','072024','082024'",
	"Sep" => "'062024','072024','082024','092024'",
	"Oct" => "'062024','072024','082024','092024','102024'",
	"Nov" => "'062024','072024','082024','092024','102024','112024','122024'",
	"Dec" => "'062024','072024','082024','092024','102024','112024','122024','012025'",
	"Jan" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025'",
);
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

function get_budget_amounts()
{
	$conn = oci_conn();

	$ret = array();
	$result = array();
	$budget_serials = array();

	$sql = "SELECT serial, sum(amount) AS amount FROM purchase_budget WHERE rundate IN ('062024','072024','082024','092024','102024','112024','122024') GROUP BY serial";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$serial = $row['SERIAL'];
		$amount = $row['AMOUNT'];
		$budget_serials[] = $serial;
		$result[$serial] = $amount;
	}

	oci_free_statement($cursor);
	oci_close($conn);

	$ret['results'] = $result;
	$ret['budget_serials'] = $budget_serials;

	return $ret;
}

function get_budget_used()
{
	$conn = oci_conn();

	$result = array();

	$sql = "SELECT budget AS SERIAL, sum(total) AS total FROM purchase_running6 WHERE budget_month IN ('202406','202407','202408','202409','202410','202411','202412') GROUP BY budget";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$serial = $row['SERIAL'];
		$total = $row['TOTAL'];

		$result[$serial] = $total;
	}

	oci_free_statement($cursor);
	oci_close($conn);

	return $result;
}

function get_budget_names()
{
	$conn = oci_conn();

	$result = array();

	$sql = "SELECT SERIAL, name FROM purchase_budget_names";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$serial = $row['SERIAL'];
		$name = $row['NAME'];
		$result[$serial] = $name;
	}

	oci_free_statement($cursor);
	oci_close($conn);

	return $result;
}

function start($budget_amounts, $budget_serials, $budget_used, $budget_names)
{
	foreach ($budget_serials as $serial) 
	{
		$amount = $budget_amounts[$serial];
		if (isset($budget_used[$serial]))
		{
			$used_total = $budget_used[$serial];
		} 
		else 
		{
			$used_total = 0;
		}
		$name = $budget_names[$serial];

		$diff = $amount - $used_total;

		//echo "Serial: " . $name . " -> " . $serial . " -> . " . $amount . " -> " . $used_total . " === " . $diff . "\n";
	}
}

function log_event($message) 
{
    $log_file = 'budget_update_report.log';

    $file_handle = fopen($log_file, 'a');

    if ($file_handle) 
	{
        $log_message = "$message\n";
        fwrite($file_handle, $log_message);
        fclose($file_handle);
    } 
	else 
	{
        echo "Error: Unable to open log file.";
    }
}

$get_budget_amounts = get_budget_amounts();
$budget_amounts = $get_budget_amounts['results'];
$budget_serials = $get_budget_amounts['budget_serials'];

$budget_used = get_budget_used();
$budget_names = get_budget_names();

$run_date = date("Ym");
$june_check = date("Y" . "06");

if ($run_date != $june_check)
{
	log_event("Start");
	start($budget_amounts, $budget_serials, $budget_used, $budget_names);
}
else
{
	echo "Beginning of the financial year and you may not move budget(s) from a previous financial year.";
}









?>
