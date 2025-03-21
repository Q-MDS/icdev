<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");
require_once("class.html.mime.mail.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

function get_budget_serials()
{
	global $conn, $range_budget;

	$data = array();
	
	$get_range = $range_budget;
	$range = json_encode($get_range);
	$range = str_replace('[', '', $range);
	$range = str_replace(']', '', $range);
	$range = str_replace('"', '\'', $range);

	$sql = "SELECT serial FROM purchase_budget WHERE rundate IN ($range) GROUP BY serial";

	$cursor = oci_parse($conn, $sql);

	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) {
		// $data[] = $row['AMOUNT'];
		$data[] = $row['SERIAL'];
	}

	// Free the statement resource
	oci_free_statement($cursor);
	
	return $data;
}

function get_budget_amounts($serial)
{
	global $conn, $range_budget;

	$data = array();
	
	$range = $range_budget;

	// $range = array('062024','072024','082024','092024','102024','112024','122024','012025');

	foreach ($range as $rundate) 
	{
		$sql = "SELECT amount, rundate FROM purchase_budget WHERE rundate = :rundate AND serial = :serial";
	
		$cursor = oci_parse($conn, $sql);
	
		// Bind variables to prevent SQL injection
		oci_bind_by_name($cursor, ':rundate', $rundate);
		oci_bind_by_name($cursor, ':serial', $serial);

		// Initialize the value to 0
		$data[$rundate] = 0;
	
		oci_execute($cursor);
	
		while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) {
			// $data[] = $row['AMOUNT'];
			$data[$row['RUNDATE']] = $row['AMOUNT'];
		}
	
		// Free the statement resource
		oci_free_statement($cursor);
	}
	

	return $data;
}

function get_budget_spend($serial)
{
	global $conn, $range_spend;

	$data = array();

	$range = $range_spend;
	// $range = array('202406','202407','202408','202409','202410','202411','202412','202501');

	foreach ($range as $budget_month)
	{
		$sql = "SELECT budget_month, total FROM icape.purchase_running6@livesys WHERE budget = :budget AND budget_month = :budget_month";

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


	return $data;
}

function upd_budget_amounts($new_budget, $serial)
{
    global $conn;

    try {
        foreach ($new_budget as $rundate => $amount) 
		{
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
        echo "Error updating budget amounts: " . $e->getMessage();
        // Return failure result
        return ['status' => 'error', 'message' => 'Transaction rolled back due to an error: ' . $e->getMessage()];
    } finally {
        // Close the connection
    }
}

function get_budget_names()
{
	global $conn;

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

	return $result;
}

function backupTables()
{
	global $conn;
	
	$backup_table_name = 'purchase_budget_backup_' . date('ymdHis');

	$sql = "CREATE TABLE " . $backup_table_name . " AS SELECT * FROM purchase_budget";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	oci_commit($conn);
	oci_free_statement($cursor);
}

function add_pbl_entry($from_budget_serial, $to_budget_serial, $amount, $transfer_date, $company, $bud_to_ym, $bud_from_ym, $reason, $user_name)
{
	global $conn;

	$sql = "INSERT INTO purchase_budget_log (log_id, from_budget_serial, to_budget_serial, amount, TRANFER_DATE, company, BUDGET_TO_YM, BUDGET_FROM_YM, reason, username) VALUES (PURCHASE_BUDGET_LOG_ID.nextval, :from_bud_serial, :to_bud_serial, :amount, :transfer_date, :company, :bud_to_ym, :bud_from_ym, :reason, :user_name)";
		
	$cursor = oci_parse($conn, $sql);

	oci_bind_by_name($cursor, ':from_bud_serial', $from_budget_serial);
	oci_bind_by_name($cursor, ':to_bud_serial', $to_budget_serial);
	oci_bind_by_name($cursor, ':amount', $amount);
	oci_bind_by_name($cursor, ':transfer_date', $transfer_date);
	oci_bind_by_name($cursor, ':company', $company);
	oci_bind_by_name($cursor, ':bud_to_ym', $bud_to_ym);
	oci_bind_by_name($cursor, ':bud_from_ym', $bud_from_ym);
	oci_bind_by_name($cursor, ':reason', $reason);
	oci_bind_by_name($cursor, ':user_name', $user_name);

	oci_execute($cursor);
	oci_commit($conn);
	oci_free_statement($cursor);
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

function clearEmailLog()
{
	$log_file = 'email.html';
	$file_handle = fopen($log_file, 'w');
	fclose($file_handle);
	$this_month = date('M Y');

	$email_html = "";

	log_email("<html><head><title>Budget Update Report</title></head><body><h1>Budget Update Report</h1>");
	log_email("<p>Report generated on: " . date('Y-m-d H:i:s') . "</p>");
	log_email("<table border='1'><tr><th align='left'>Budget Name</th><th align='right'>YTD Budget</th><th align='right'>YTD Spend</th><th align='right'>$this_month Adjustment</th><th align='right'>$this_month Budget</th></tr>");
}

function log_email($message) 
{
	$log_file = 'email.html';

    $file_handle = fopen($log_file, 'a');

    if ($file_handle) 
	{
        $log_message = "$message";
        fwrite($file_handle, $log_message);
        fclose($file_handle);
    } 
	else 
	{
        echo "Error: Unable to open email log file.";
    }
}

function send_email()
{
	global $noreply_email;

	// $email_list = ["keith@intercape.co.za", "quintin@moderndaystrategy.com"];
	$email_list = ["keith@intercape.co.za", "quintin@pxo.co.za"];
	$from = $noreply_email;
	$subject = "Budget Amounts Shifted";
	$text_message = str_replace("<br>", "\n", $html_message);

	// Read the contents of the email.html file
    $html_message = file_get_contents('email.html');
    if ($html_message === false) {
        echo "Error reading email.html file.";
        return;
    }

	// Convert HTML to plain text
    $text_message = strip_tags($html_message);

	try 
	{
		$mail = new html_mime_mail('X-Mailer: Html Mime Mail Class');
		$mail->add_html($html_message, $text_message);
		$mail->build_message();

		foreach ($email_list as $email_address) 
		{
			$result = $mail->smtp_send($from, $subject, $email_address);
			if (!$result) {
                echo "Failed to send email to $email_address";
            }
		}
	} 
	catch (Exception $e) 
	{
		// Log any exceptions that occur
		echo "Exception caught while sending email: " . $e->getMessage();
	}
}

function generate_range_budget() 
{
    $ret = array();
	$budget_range = array();
	$spend_range = array();
	$work_month_budget = '';
	$work_month_spend = '';
	$next_month_budget = '';
	$next_month_spend = '';
    $current_year = date("Y");
    $current_month = date("m");
	$range_counter = 0;

	if ($current_month > 0 && $current_month < 6) 
	{
		$range_counter = $current_month + 7;
	}
	if ($current_month > 6 && $current_month < 13) 
	{
		$range_counter = $current_month - 5;
	}

	$start_date = '06' . date("Y");
	$date = DateTime::createFromFormat('mY', $start_date);
	$date->modify('-1 year');

	for ($i = 0; $i < $range_counter; $i++)
	{
		$budget_range[] = $date->format('mY');
		$spend_range[] = $date->format('Ym');
		$date->modify('+1 month');
	}

	// Work dates
	$today_work_month = date("mY");
	$date = DateTime::createFromFormat('mY', $today_work_month);
	$date->modify('-1 month');
	$work_month_budget = $date->format('mY');
	$work_month_spend = $date->format('Ym');

	// Next dates
	$next_month_budget = date("mY");
	$next_month_spend = date("Ym");

	$ret['budget'] = $budget_range;
	$ret['spend'] = $spend_range;
	$ret['work_month_budget'] = $work_month_budget;
	$ret['work_month_spend'] = $work_month_spend;
	$ret['next_month_budget'] = $next_month_budget;
	$ret['next_month_spend'] = $next_month_spend;

    return $ret;
} 
// ---------------------------------------------------------------------------------------------------------------------------------------------------

$the_month = date('n');

// WARNNG
// $the_month = 2;
// WARNNG

if ($the_month == 6) 
{
	die("Cannot run in June");
}

$get_ranges = generate_range_budget();
$range_budget = $get_ranges['budget'];
$range_spend = $get_ranges['spend'];
$work_month_budget = $get_ranges['work_month_budget'];
$work_month_spend = $get_ranges['work_month_spend'];
$next_month_budget = $get_ranges['next_month_budget'];
$next_month_spend = $get_ranges['next_month_spend'];

log_event("Selected date range: " . json_encode($range_budget) . "\n");

// Get data arrays
$budget_serials = get_budget_serials($range_budget);
$budget_names = get_budget_names();
?>
