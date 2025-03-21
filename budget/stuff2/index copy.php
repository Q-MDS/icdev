<?php
$range_budget = array(
	"7" => array('062024','072024'),
	"8" => array('062024','072024','082024'),
	"9" => array('062024','072024','082024','092024'),
	"10" => array('062024','072024','082024','092024','102024'),
	"11" => array('062024','072024','082024','092024','102024','112024'),
	"12" => array('062024','072024','082024','092024','102024','112024','122024'),
	"1" => array('062024','072024','082024','092024','102024','112024','122024','012025'),
	"2" => array('062024','072024','082024','092024','102024','112024','122024','012025','022025'),
	"3" => array('062024','072024','082024','092024','102024','112024','122024','012025','022025','032025'),
	"4" => array('062024','072024','082024','092024','102024','112024','122024','012025','022025','032025','042025'),
	"5" => array('062024','072024','082024','092024','102024','112024','122024','012025','022025','032025','042025','052025')
);

$range_spend = array (
	"7" => array('202406','202407'),
	"8" => array('202406','202407','202408'),
	"9" => array('202406','202407','202408','202409'),
	"10" => array('202406','202407','202408','202409','202410'),
	"11" => array('202406','202407','202408','202409','202410','202411'),
	"12" => array('202406','202407','202408','202409','202410','202411','202412'),
	"1" => array('202406','202407','202408','202409','202410','202411','202412','202501'),
	"2" => array('202406','202407','202408','202409','202410','202411','202412','202501','202502'),
	"3" => array('202406','202407','202408','202409','202410','202411','202412','202501','202502','202503'),
	"4" => array('202406','202407','202408','202409','202410','202411','202412','202501','202502','202503','202504'),
	"5" => array('202406','202407','202408','202409','202410','202411','202412','202501','202502','202503','202504','202505')
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

function get_budget_serials($month)
{
	global $range_budget;

	$conn = oci_conn();

	$data = array();
	
	$get_range = $range_budget[$month];
	$range = json_encode($get_range);
	$range = str_replace('[', '', $range);
	$range = str_replace(']', '', $range);
	$range = str_replace('"', '\'', $range);

	$sql = "SELECT serial FROM purchase_budget WHERE rundate IN ($range)";

	$cursor = oci_parse($conn, $sql);

	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) {
		// $data[] = $row['AMOUNT'];
		$data[] = $row['SERIAL'];
	}

	// Free the statement resource
	oci_free_statement($cursor);
	
	oci_close($conn);

	return $data;
}

function get_budget_amounts($month, $serial)
{
	global $range_budget;

	$conn = oci_conn();

	$data = array();
	
	$range = $range_budget[$month];

	// $range = array('062024','072024','082024','092024','102024','112024','122024','012025');

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
	global $range_spend;

	$conn = oci_conn();

	$data = array();

	$range = $range_spend[$month];
	// $range = array('202406','202407','202408','202409','202410','202411','202412','202501');

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


function upd_budget_amounts($new_budget, $serial)
{
    $conn = oci_conn();

    try {
        foreach ($new_budget as $rundate => $amount) {
            $sql = "UPDATE purchase_budget SET amount = :amount WHERE rundate = :rundate AND serial = :serial";
        
            $cursor = oci_parse($conn, $sql);
        
            // Bind variables to prevent SQL injection
            oci_bind_by_name($cursor, ':amount', $amount);
            oci_bind_by_name($cursor, ':rundate', $rundate);
            oci_bind_by_name($cursor, ':serial', $serial);
        
            oci_execute($cursor);
        
            // Free the statement resource
            oci_free_statement($cursor);
        }

        // Commit the transaction
        oci_commit($conn);
        // Return success result
        return ['status' => 'success', 'message' => 'Transaction committed successfully.'];
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        oci_rollback($conn);
        // Log the error message
        error_log("Error updating budget amounts: " . $e->getMessage());
        // Return failure result
        return ['status' => 'error', 'message' => 'Transaction rolled back due to an error: ' . $e->getMessage()];
    } finally {
        // Close the connection
        oci_close($conn);
    }
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

function clearLogFile()
{
	$log_file = 'budget_update_report.log';
	$file_handle = fopen($log_file, 'w');
	fclose($file_handle);

	log_event("_                ");
	log_event("| |    ___   __ _ ");
	log_event("| |   / _ \ / _` |");
	log_event("| |__| (_) | (_| |");
	log_event("|_____\___/ \__, |");
	log_event("            |___/ " . "\n");
}

function backupTables()
{
	$backup_table_name = 'purchase_budget_backup_' . date('ymdHis');

	$conn = oci_conn();

	$sql = "CREATE TABLE " . $backup_table_name . " AS SELECT * FROM purchase_budget";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	oci_commit($conn);
	oci_free_statement($cursor);
	oci_close($conn);
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

function proces_batch($batch, $the_month, $budget_names, $work_month_budget, $work_month_spend, $next_month_budget)
{
	foreach ($batch as $serial)
	{
		// if ($i < 3)
		// {
			// Process each budget/serial indiviually
			$budget_amounts = get_budget_amounts($the_month, $serial);
			$new_budget = $budget_amounts;
			$budget_spend = get_budget_spend($the_month, $serial);
			$budget_name = $budget_names[$serial];

			$total_budget = array_sum($budget_amounts);
			$total_used = array_sum($budget_spend);

			// Get YTD budget
			$ytd_budget_amount = 0;
			$ytd_budget = array();

			foreach ($budget_amounts as $month => $amount) 
			{
				// Check if the month is in the same year and before or equal to the target month
				if (substr($month, 2) === substr($work_month_budget, 2) && $month <= $work_month_budget) 
				{
					$ytd_budget_amount += $amount;
					if (!isset($ytd_budget[$month])) 
					{
						$ytd_budget[$month] = 0;
					}
			
					$ytd_budget[$month] += $ytd_budget_amount;
				}
			}

			// Get YTD Spend
			$ytd_spend_amount = 0;
			$ytd_spend = array();

			foreach ($budget_spend as $month => $amount) 
			{
				// Check if the month is in the same year and before or equal to the target month
				if (substr($month, 0, -2) === substr($work_month_spend, 0, -2) && $month <= $work_month_spend) 
				{
					$ytd_spend_amount += $amount;
					if (!isset($ytd_spend[$month])) 
					{
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
			$next_month_new_budget = $budget_amounts[$next_month_budget] - $adjustment;

			log_event("- Budget name: " . $budget_name . " - Budget serial: " . $serial . "- Total budget: " . $total_budget . " - Total spend: " . $total_used . " - Difference: " . $total_budget - $total_used);

			// echo "Total budget: $total_budget\n";
			// echo "Total used: $total_used\n";
			// print_r($ytd_budget);
			// print_r($ytd_spend);

			// echo "Year-to-date total for $work_month_budget: " . array_sum($ytd_budget) . "\n";
			// echo "Year-to-date total for $work_month_spend: " . array_sum($ytd_spend) . "\n";
			
			// echo "DIFF: $diff \n";
			// echo "ADJUSTMENT: $adjustment \n";
			// echo "WORK NEW BUDGET: $work_month_new_budget \n";
			// echo "NEXT NEW BUDGET: $next_month_new_budget \n";
			// print_r($new_budget);

			$new_budget[$work_month_budget] = $work_month_new_budget;
			$new_budget[$next_month_budget] = $next_month_new_budget;
			// print_r($new_budget);
			

			// Borrow
			// Calculate the total sum
			$total_sum = array_sum($new_budget);

			// Check if the second last item is negative
			$keys = array_keys($new_budget);
			$second_last_key = $keys[count($keys) - 2];
			$last_key = $keys[count($keys) - 1];
			$last_value = $new_budget[$last_key];

			if ($new_budget[$second_last_key] < 0) 
			{
				// Calculate the adjustment needed
				$adjustment = abs($new_budget[$second_last_key]);
				$new_budget[$second_last_key] = 0;

				// Adjust the previous items to maintain the total sum
				for ($i = count($keys) - 3; $i >= 0; $i--) 
				{
					$key = $keys[$i];
					if ($new_budget[$key] >= $adjustment) 
					{
						$new_budget[$key] -= $adjustment;
						break;
					} 
					else 
					{
						$adjustment -= $new_budget[$key];
						$new_budget[$key] = 0;
					}
				}

				// Ensure the total sum remains the same
				$new_budget[$keys[count($keys) - 1]] = $total_sum - array_sum($new_budget);
			}

			$new_budget[$last_key] = $last_value;

			log_event("- New budget amounts: " . json_encode($new_budget));

			// Update budget table
			$result = upd_budget_amounts($new_budget, $serial);

			log_event("- Update result: " . json_encode($result) . "\n");
		// } 
		// else 
		// { 
		// 	break; 
		// }
	}
}

// ---------------------------------------------------------------------------------------------------------------------------------------------------
$month_budget = array("7" => '062024',"8" => '072024',"9" => '082024',"10" => '092024',"11" => '102024',"12" => '112024',"1" => '122024',"2" => '012025',"3" => '022025',"4" => '032025',"5" => '042025');
$month_spend = array("7" => '202406',"8" => '202407',"9" => '202408',"10" => '202409',"11" => '202410',"12" => '202411',"1" => '202412',"2" => '202501',"3" => '202502',"4" => '202503',"5" => '202504');
$month_next = array("7" => '072024',"8" => '072024',"9" => '082024',"10" => '102024',"11" => '112024',"12" => '122024',"1" => '012025',"2" => '022025',"3" => '032025',"4" => '042025',"5" => '052025');
// runs in June do nothing

// ### Time of testing: 2106 budgets to do ###
// START

$the_month = date('n');

if ($the_month == 6) 
{
	die("Cannot run in June");
}

// Clear the text/report file
clearLogFile();

// Backup the budget table
// backupTables();

log_event("Budget update started: " . date('Y-m-d H:i:s') . "\n");

// Get month for calcs
// $work_month_budget = '122024';
// $work_month_spend = '202412';
// $next_month_budget = '012025';
$work_month_budget = $month_budget[$the_month];
$work_month_spend = $month_spend[$the_month];
$next_month_budget = $month_next[$the_month];

log_event("Selected date range: " . json_encode($range_budget[$the_month]) . "\n");

// Get data arrays
$budget_serials = get_budget_serials($the_month);
$budget_names = get_budget_names();

$batch_size = 100;
$chunks = array_chunk($budget_serials, $batch_size, true);

$i = 1;
// foreach ($chunks as $chunk) 
// {
// 	echo "Processing batch $i\n";

//     proces_batch($chunk, $the_month, $budget_names, $work_month_budget, $work_month_spend, $next_month_budget);

//     usleep(500000); // Sleep for 0.5 seconds

// 	if ($i == 2) 
// 	{
// 		break;
// 	}

// 	$i++;
// }
log_event("Budget update ended: " . date('Y-m-d H:i:s'));

if ($i == 22)
{
	echo "Budget update completed successfully";
}
else
{
	echo "Budget update failed";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bugdet Update</title>
</head>
<body>
	<div style="display: block;"><input type="text" value="22"></div>
	<div></div>
</body>
</html>