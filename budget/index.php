<?php
// view-source:http://localhost/icdev/budget/index.php?m=Jan

$year2425 = array(
	"07" => "'062024'",
	"08" => "'062024','072024'",
	"09" => "'062024','072024','082024'",
	"10" => "'062024','072024','082024','092024'",
	"11" => "'062024','072024','082024','092024','102024'",
	"12" => "'062024','072024','082024','092024','102024','112024'",
	"01" => "'062024','072024','082024','092024','102024','112024','122024'",
	"02" => "'062024','072024','082024','092024','102024','112024','122024','012025'",
	"03" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025'",
	"04" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025','032025'",
	"05" => "'062024','072024','082024','092024','102024','112024','122024','012025','022025','032025','042025'"
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

$cm_pb = '122024';
$cm_pr = '202412'; 
$nm_pb = '012025';

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

// loop thru amounts and then get used from used array
// Do a calc.
// Output if it is an over budget
// If under budget
    // 22 000 - 20 000 = 2000
   // amount = amount - used -> 20 000
   // amount = amount + used -> 22 000 + 2 000 = 24 000 for next month
   // Do commit and rollback

   //12942
//    27 750 - 741.54 = 27 008.46
//    27750 - 27008.46 = 741.54
//    NM: 27750 + 27008.46 = 54758.46


// print_r($get_budget_amounts['results']);
// print_r($budget_used);

die();


// function start($budget_amounts, $budget_serials, $budget_used, $budget_names)
// {
// 	$under_budget = array();
// 	$over_budget = array();

// 	foreach ($budget_serials as $serial) 
// 	{
// 		$amount = $budget_amounts[$serial];
// 		if (isset($budget_used[$serial]))
// 		{
// 			$used_total = $budget_used[$serial];
// 		} 
// 		else 
// 		{
// 			$used_total = 0;
// 		}
// 		$name = $budget_names[$serial];

// 		$diff = $amount - $used_total;

// 		if ($diff < 0)
// 		{
// 			$over_budget[$serial] = $diff;
// 		}
// 		else
// 		{
// 			$under_budget[$serial] = array("amount" => $amount, "used_total" => $used_total, "diff" => $diff);
// 		}

// 		//echo "Serial: " . $name . " -> " . $serial . " -> . " . $amount . " -> " . $used_total . " === " . $diff . "\n";
// 	}

// 	// Output overbudget items
// 	/*
// 	log_event("OVERBUDGET" . "\n\n" . str_pad("Budget Name", 60)  . str_pad("Serial", 20, " ", STR_PAD_LEFT) . str_pad("Budget Total", 20, " ", STR_PAD_LEFT) . str_pad("Used Total", 20, " ", STR_PAD_LEFT) . str_pad("NETT", 20, " ", STR_PAD_LEFT));
// 	foreach ($over_budget as $serial => $diff) 
// 	{
// 		$amount = $budget_amounts[$serial];
// 		$used_total = $budget_used[$serial];
// 		$name = $budget_names[$serial];

// 		$diff = $amount - $used_total;

// 		log_event(str_pad($name, 60)  . str_pad($serial, 20, " ", STR_PAD_LEFT) . str_pad($amount, 20, " ", STR_PAD_LEFT) . str_pad($used_total, 20, " ", STR_PAD_LEFT) . str_pad($diff, 20, " ", STR_PAD_LEFT));
// 	}
// 	*/

// 	// Update budget tables
// 	move_budgets($under_budget, $budget_used, $budget_names);
// }

// function move_budgets($under_budget)
// {
// 	global $year2425;

// 	$conn = oci_conn();

// 	// Get current and next month
// 	$month_index = date("m");
// 	$current_month = date("mY");
// 	$next_month = date("mY", strtotime("+1 month"));
// 	$get_run_dates = $year2425[$month_index];
// 	$prep_for_array = str_replace("'", "", $get_run_dates);
// 	$run_dates = explode(",", $prep_for_array);

// 	foreach($run_dates as $run_date)
// 	{
// 		echo "Run Date: " . $run_date . "\n";
		
// 	}


// 	echo "Current Month: " . $current_month . " - " . $prep_for_array . "\n";

// 	// Remove from current budget
// 	// Work out the expected value after the update
// 	// Do the update
// 	// Read the value back and check it is correct
// 	// Commit or rollback


// 	// print_r($under_budget);
// 	$expected_total_after_minus = 0;

// 	// print_r($under_budget);
// 	// die();

// 	$count = 0;

// 	// print_r($under_budget);
// 	// die();
// 	foreach ($under_budget as $serial => $value) 
// 	{
// 		if ($count == 2)
// 		{
// 			break;
// 		}
// 		$amount = $value['amount'];
// 		$used_total = $value['used_total'];
// 		$diff = $value['diff'];

// 		$expected_total_after_minus	= $amount - $diff;
// 		echo "XXX: $serial >>>  $expected_total_after_minus\n";

// 		foreach ($run_dates as $run_date)
// 		{
// 			$sql = "SELECT total FROM purchase_running6 WHERE serial = " . $serial . " AND rundate = '" . $run_date . "';";
// 			$total = 19406.6;
// 			echo $sql . "\n";
// 			$sql = "UPDATE purchase_budget SET amount = " . $total . " WHERE serial = " . $serial . " AND rundate = '" . $run_date . "';";
// 			echo $sql . "\n";
// 			// $cursor = oci_parse($conn, $sql);
// 			// oci_execute($cursor);
// 			// oci_free_statement($cursor);
// 		}
// 		$sql = "UPDATE purchase_budget SET amount = amount + " . $diff . "  WHERE serial = " . $serial . " AND rundate = '" . $next_month . "';";

// 		echo $sql . "\n";

// 		// $sql = "UPDATE purchase_budget SET amount=amount-2000 WHERE serial = " . $serial . " AND rundate = '" . $current_month . "';";
// 		// $cursor = oci_parse($conn, $sql);
// 		// oci_execute($cursor);

// 		// oci_free_statement($cursor);
		
// 		$expected_total_after_minus = 0;

// 		$count++;
// 	}
	

// 	oci_close($conn);
// }
