<?php
// view-source:http://localhost/icdev/budget/index.php?m=Jan

// $year2425 = array(
// 	"07" => "'062024'",
// 	"08" => "'062024','072024'",
// 	"09" => "'062024','072024','082024'",
// 	"10" => "'062024','072024','082024','092024'",
// 	"11" => "'062024','072024','082024','092024','102024'",
// 	"12" => "'062024','072024','082024','092024','102024','112024'",
// 	"01" => "'062024','072024','082024','092024','102024','112024','122024'",
// 	"02" => "'062024','072024','082024','092024','102024','112024','122024','012025'",
// 	"03" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025'",
// 	"04" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025','032025'",
// 	"05" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025','032025','042025'"
// );

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

function get_budget_amounts($month)
{
	global $year2425;

	$conn = oci_conn();

	$ret = array();
	$result = array();
	$budget_serials = array();
	// $range = $year2425[$month];

	// $sql = "SELECT serial, sum(amount) AS amount FROM purchase_budget WHERE rundate IN ('062024','072024','082024','092024','102024','112024','122024') GROUP BY serial";
	// $sql = "SELECT serial, sum(amount) AS amount FROM purchase_budget WHERE rundate IN (" . $range . ") AND serial = 11888 GROUP BY serial";
	// $sql = "SELECT serial, sum(amount) AS amount FROM purchase_budget WHERE rundate = $month AND serial = 11888 GROUP BY serial";
	$sql = "SELECT serial, sum(amount) AS amount FROM purchase_budget WHERE rundate = $month GROUP BY serial";
		
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

function get_budget_used($month)
{
	$conn = oci_conn();

	$result = array();

	// $sql = "SELECT budget AS SERIAL, sum(total) AS total FROM purchase_running6 WHERE budget_month IN ('202406','202407','202408','202409','202410','202411','202412') GROUP BY budget";
	$sql = "SELECT budget AS SERIAL, sum(total) AS total FROM purchase_running6 WHERE budget_month = $month GROUP BY budget";
		
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

function adjust_current_budget($cm_pb, $serial, $total)
{
	$conn = oci_conn();

	// $sql = "UPDATE purchase_budget SET amount = amount - " . $total . " WHERE serial = " . $serial . " AND rundate = '" . $cm_pb . "'";
	$sql = $sql = "UPDATE purchase_budget SET amount = amount - :total WHERE serial = :serial AND rundate = :cm_pb";
	$cursor = oci_parse($conn, $sql);

	oci_bind_by_name($cursor, ':total', $total);
    oci_bind_by_name($cursor, ':serial', $serial);
    oci_bind_by_name($cursor, ':cm_pb', $cm_pb);

	$r = oci_execute($cursor, OCI_NO_AUTO_COMMIT);
	
	if (!$r) {
        $e = oci_error($cursor);
        echo "SQL execution failed: " . $e['message'];
        oci_rollback($conn);
        oci_free_statement($cursor);
        oci_close($conn);
        return;
    }

    // Commit the transaction
    $r = oci_commit($conn);

    if (!$r) {
        $e = oci_error($conn);
        echo "Commit failed: " . $e['message'];
        oci_rollback($conn);
    } else {
        echo "Transaction committed successfully.";
    }

    // Free the statement and close the connection
    oci_free_statement($cursor);
    oci_close($conn);
}

function adjust_next_budget($cm_pb, $serial, $total)
{
	$conn = oci_conn();

	$sql = "UPDATE purchase_budget SET amount = amount + " . $total . " WHERE serial = " . $serial . " AND rundate = '" . $cm_pb . "'";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	oci_commit($conn);

	oci_free_statement($cursor);
}

$month_array = array(
	["cp_pb" => "062024", "cm_pr" => "202406", "nm_pb" => "072024"],
	["cp_pb" => "072024", "cm_pr" => "202407", "nm_pb" => "082024"],
	["cp_pb" => "082024", "cm_pr" => "202408", "nm_pb" => "092024"],
	["cp_pb" => "092024", "cm_pr" => "202409", "nm_pb" => "102024"],
	["cp_pb" => "102024", "cm_pr" => "202410", "nm_pb" => "112024"],
	["cp_pb" => "112024", "cm_pr" => "202411", "nm_pb" => "122024"],
	["cp_pb" => "122024", "cm_pr" => "202412", "nm_pb" => "012025"]
);

print_r($month_array);
die();

$cm_pb = '122024';
$cm_pr = '202412'; 
$nm_pb = '012025';

foreach ($month_array as $month)
{

}



$get_budget_amounts = get_budget_amounts($cm_pb);
$budget_amounts = $get_budget_amounts['results'];
$budget_serials = $get_budget_amounts['budget_serials'];

$budget_used = get_budget_used($cm_pr);
$budget_names = get_budget_names();

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

	if ($diff < 0)
	{
		$over_budget[$serial] = $diff;
		echo "Over budget";
	}
	else
	{
		$under_budget[$serial] = array("amount" => $amount, "used_total" => $used_total, "diff" => $diff);
		$a = $amount - $diff;
		$b = $amount + $diff;

		// Decrease current budget
		$a = adjust_current_budget($cm_pb, $serial, $diff);
		echo $a;

		// Increase next month budget
		adjust_next_budget($nm_pb, $serial, $diff);

		echo "NM1: " . $amount . " => " . $used_total . " => " . $diff . "\n";
		echo "Current amount becomes: " . $a . "\n";
		echo "Next month amount becomes: " . $b . "\n\n";
	}

	//echo "Serial: " . $name . " -> " . $serial . " -> . " . $amount . " -> " . $used_total . " === " . $diff . "\n";
}

