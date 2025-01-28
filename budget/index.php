<?php
// view-source:http://localhost/icdev/budget/index.php?m=Jan

$year2425my = array(
	"07" => "'062024'",
	"08" => "'062024','072024'",
	"09" => "'062024','072024','082024'",
	"10" => "'062024','072024','082024','092024'",
	"11" => "'062024','072024','082024','092024','102024'",
	"12" => "'062024','072024','082024','092024','102024','112024'",
	"01" => "'062024','072024','082024','092024','102024','112024','122024','012025'",
	"02" => "'062024','072024','082024','092024','102024','112024','122024','012025'",
	"03" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025'",
	"04" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025','032025'",
	"05" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025','032025','042025'"
);
$year2425ym = array(
	"07" => "'202406'",
	"08" => "'202406','202407'",
	"09" => "'202406','202407','202408'",
	"10" => "'202406','202407','202408','202409'",
	"11" => "'202406','202407','202408','202409','202410'",
	"12" => "'202406','202407','202408','202409','202410','202411'",
	"01" => "'202406','202407','202408','202409','202410','202411','202412','012025'",
	"02" => "'202406','202407','202408','202409','202410','202411','202412','202501'",
	"03" => "'202406','202407','202408','202409','202410','202411','202412','202501','202502'",
	"04" => "'202406','202407','202408','202409','202410','202411','202412','202501','202502','202503'",
	"05" => "'202406','202407','202408','202409','202410','202411','202412','202501','202502','202503','202504'"
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

function get_budget_amounts($month, $serial)
{
	global $year2425my;

	$conn = oci_conn();

	$data = array();
	
	$range = $year2425my[$month];

	$range = array('062024','072024','082024','092024','102024','112024','122024','012025');

	foreach ($range as $rundate) 
	{
		$sql = "SELECT amount, rundate FROM purchase_budget WHERE rundate = :rundate AND serial = :serial";
	
		$cursor = oci_parse($conn, $sql);
	
		// Bind variables to prevent SQL injection
		oci_bind_by_name($cursor, ':rundate', $rundate);
		oci_bind_by_name($cursor, ':serial', $serial);
	
		oci_execute($cursor);
	
		while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) {
			// $data[] = $row['AMOUNT'];
			$data[$row['RUNDATE']] = $row['AMOUNT'];
		}
	
		// Free the statement resource
		oci_free_statement($cursor);
	}
	
	oci_close($conn);

	return $data;
}

function get_budget_spend($month, $serial)
{
	global $year2425ym;

	$conn = oci_conn();

	$data = array();

	// $range = $year2425ym[$month];
	$range = array('202406','202407','202408','202409','202410','202411','202412','202501');

	foreach ($range as $budget_month)
	{
		$sql = "SELECT budget_month, total FROM purchase_running6 WHERE budget = :budget AND budget_month = :budget_month";

		$cursor = oci_parse($conn, $sql);
	
		// Bind variables to prevent SQL injection
		oci_bind_by_name($cursor, ':budget_month', $budget_month);
		oci_bind_by_name($cursor, ':budget', $serial);

		// Initialize the value to 0
		$data[$budget_month] = 0;
	
		oci_execute($cursor);
	
		while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) {
			$data[$row['BUDGET_MONTH']] = $row['TOTAL'];
		}
	
		// Free the statement resource
		oci_free_statement($cursor);
	}

	oci_close($conn);

	return $data;
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
        //echo "Transaction committed successfully.";
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

function move_budget($nm_pb, $serial, $amount)
{
	$conn = oci_conn();

	$sql = "UPDATE purchase_budget SET amount = $amount WHERE serial = $serial AND rundate = '$nm_pb'";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	oci_commit($conn);

	oci_free_statement($cursor);
}

if (isset($_GET['m']))
{
	$month = $_GET['m'];
	$work_month_budget = 122024;
	$work_month_spend = 202412;
	$next_month_budget = '012025';
	$next_month_spend = 202501;

	$budget_names = get_budget_names();
	$budget_amounts = get_budget_amounts($month, 11888);
	$new_budget = $budget_amounts;
	$budget_spend = get_budget_spend($month, 11888);


	echo "Budget Amounts<br>";
	print_r($budget_amounts);
	print_r($budget_spend);

	$total_budget = array_sum($budget_amounts);
	$total_used = array_sum($budget_spend);

	// Get YTD budget
	$ytd_budget_amount = 0;
	$ytd_budget = array();
	foreach ($budget_amounts as $month => $amount) {
		// Check if the month is in the same year and before or equal to the target month
		if (substr($month, 2) === substr($work_month_budget, 2) && $month <= $work_month_budget) {
			
			$ytd_budget_amount += $amount;
			if (!isset($ytd_budget[$month])) {
				$ytd_budget[$month] = 0;
			}
	
			$ytd_budget[$month] += $ytd_budget_amount;
		}
	}

	// Get YTD Spend
	$ytd_spend_amount = 0;
	$ytd_spend = array();
	foreach ($budget_spend as $month => $amount) {

		echo "Month: $month > $amount \n";
		// Check if the month is in the same year and before or equal to the target month
		if (substr($month, 0, -2) === substr($work_month_spend, 0, -2) && $month <= $work_month_spend) {
			$ytd_spend_amount += $amount;
			if (!isset($ytd_spend[$month])) {
				$ytd_spend[$month] = 0;
			}
	
			$ytd_spend[$month] += $ytd_spend_amount;
		}
	}

	$diff = $ytd_budget[$work_month_budget] - $ytd_spend[$work_month_spend];
	$adjustment = $budget_spend[$work_month_spend] - $diff;
	// Get work month new budget
	$work_month_new_budget = $budget_amounts[$work_month_budget] + $adjustment;
	// Get next month new budget
	echo "XXX: " . $next_month_budget . " >>> " . $budget_amounts[$next_month_budget] . " >>> " . $adjustment;
	$next_month_new_budget = $budget_amounts[$next_month_budget] - $adjustment;
	

	echo "Total budget: $total_budget\n";
	echo "Total used: $total_used\n";
	print_r($ytd_budget);
	print_r($ytd_spend);

	echo "Year-to-date total for $work_month_budget: " . array_sum($ytd_budget) . "\n";
	echo "Year-to-date total for $work_month_spend: " . array_sum($ytd_spend) . "\n";
	
	echo "DIFF: $diff \n";
	echo "ADJUSTMENT: $adjustment \n";
	echo "WORK NEW BUDGET: $work_month_new_budget \n";
	echo "NEXT NEW BUDGET: $next_month_new_budget \n";
	print_r($new_budget);
	$new_budget[$work_month_budget] = $work_month_new_budget;
	$new_budget[$next_month_budget] = $next_month_new_budget;
	print_r($new_budget);


}
else
{
	echo "Missing month parameter";
}





















// $month_array = array(
// 	["cm_pb" => "062024", "cm_pr" => "202406", "nm_pb" => "072024"],
// 	["cm_pb" => "072024", "cm_pr" => "202407", "nm_pb" => "082024"],
// 	["cm_pb" => "082024", "cm_pr" => "202408", "nm_pb" => "092024"],
// 	["cm_pb" => "092024", "cm_pr" => "202409", "nm_pb" => "102024"],
// 	["cm_pb" => "102024", "cm_pr" => "202410", "nm_pb" => "112024"],
// 	["cm_pb" => "112024", "cm_pr" => "202411", "nm_pb" => "122024"],
// 	["cm_pb" => "122024", "cm_pr" => "202412", "nm_pb" => "012025"]
// );
// $month_array = array(
// 	["cm_pb" => "062024", "cm_pr" => "202406", "nm_pb" => "072024"],
// 	["cp_pb" => "072024", "cm_pr" => "202407", "nm_pb" => "082024"]
// );

// print_r($month_array);
// die();

// $cm_pb = '122024';
// $cm_pr = '202412'; 
// $nm_pb = '012025';


// foreach ($month_array as $month)
// {
	// $cm_pb = $month['cm_pb'];
	// $cm_pr = $month['cm_pr']; 
	// $nm_pb = $month['nm_pb'];

	// $get_budget_amounts = get_budget_amounts($cm_pb);
	// $budget_amounts = $get_budget_amounts['results'];
	// $budget_serials = $get_budget_amounts['budget_serials'];

	// $budget_used = get_budget_used($cm_pr);

	// print_r($budget_amounts);
	// print_r($budget_serials);
	// print_r($budget_used);
	// print_r($budget_names);

	// foreach ($budget_serials as $serial) 
	// {
// 		$serial = 11888;
// 		$amount = $budget_amounts[$serial];
		
// 		if (isset($budget_used[$serial]))
// 		{
// 			$used_total = $budget_used[$serial];
// 			$diff = $amount - $used_total;
// 			adjust_current_budget($cm_pb, $serial, $diff);
// 			adjust_next_budget($nm_pb, $serial, $diff);
// 		} 
// 		else 
// 		{
// 			echo "Zilch found\n";
// 			$used_total = 0;
// 			move_budget($nm_pb, $serial, $amount);
// 		}
// 		$name = $budget_names[$serial];

		
// echo "ZZZ: $amount > $used_total > $diff\n";	
		// if ($diff < 0)
		// {
		// 	$over_budget[$serial] = $diff;
		// 	echo "Over budget";
		// }
		// else
		// {
			// $under_budget[$serial] = array("amount" => $amount, "used_total" => $used_total, "diff" => $diff);
			// $a = $amount - $diff;
			// $b = $amount + $diff;

			// Decrease current budget
			
			// echo $a;

			// Increase next month budget
			// adjust_next_budget($nm_pb, $serial, $diff);

			// echo $nm_pb . " > NM1: " . $amount . " => " . $used_total . " => " . $diff . "\n";
			// echo "Current amount becomes: " . $a . "\n";
			// echo "Next month amount becomes: " . $b . "\n\n";
		// }

		// echo "Serial: " . $name . " -> " . $serial . " -> . " . $amount . " -> " . $used_total . " === " . $diff . "\n";
	// }
	// echo "Completed: " . json_encode($month) . "\n";
// }





