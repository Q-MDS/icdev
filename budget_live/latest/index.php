<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");
require_once("class.html.mime.mail.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

function get_budget_serials($month)
{
	global $conn, $range_budget;

	$data = array();
	
	$get_range = $range_budget[$month];
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
	
	oci_close($conn);

	return $data;
}

function get_budget_amounts($month, $serial)
{
	global $conn, $range_budget;

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

function get_budget_spend($month, $serial)
{
	global $conn, $range_spend;

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
	$email_list = ["quintin@moderndaystrategy.com", "quintin@pxo.co.za"];
	$from = $noreply_email;
	$subject = "Budget Update Report - " . date("Y-m-d H:i:s");
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
			$result = $mail->smtp_send($from, $email_address);
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

// ---------------------------------------------------------------------------------------------------------------------------------------------------
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

$month_budget = array("7" => '062024',"8" => '072024',"9" => '082024',"10" => '092024',"11" => '102024',"12" => '112024',"1" => '122024',"2" => '012025',"3" => '022025',"4" => '032025',"5" => '042025');
$month_spend = array("7" => '202406',"8" => '202407',"9" => '202408',"10" => '202409',"11" => '202410',"12" => '202411',"1" => '202412',"2" => '202501',"3" => '202502',"4" => '202503',"5" => '202504');
$month_next = array("7" => '072024',"8" => '082024',"9" => '092024',"10" => '102024',"11" => '112024',"12" => '122024',"1" => '012025',"2" => '022025',"3" => '032025',"4" => '042025',"5" => '052025');
$month_next_spend = array("7" => '202407',"8" => '202408',"9" => '202409',"10" => '202410',"11" => '202411',"12" => '202412',"1" => '202501',"2" => '202502',"3" => '202503',"4" => '202504',"5" => '202505');

$the_month = date('n');

// WARNNG
// $the_month = 2;
// WARNNG

if ($the_month == 6) 
{
	die("Cannot run in June");
}

// Get month for calcs
$work_month_budget = $month_budget[$the_month];
$work_month_spend = $month_spend[$the_month];
$next_month_budget = $month_next[$the_month];
$next_month_spend = $month_next_spend[$the_month];

log_event("Selected date range: " . json_encode($range_budget[$the_month]) . "\n");

// Get data arrays
$budget_serials = get_budget_serials($the_month);
$budget_names = get_budget_names();
?>
