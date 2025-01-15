<?php
namespace Intercape\AgentPortalMailer;

/**
 * Author: Quintin
 * Decription: Fetch data from DEBTORS_INFO where STATEMENT REMINDER = Y and send email to the email address in the DEBTORS_INFO table
 * Other: Update mail code
 */
// Step 1: Fetch records

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

function get_email_list()
{
	$conn = oci_conn();

	$query = "SELECT ACCOUNT_CODE, STATEMENT_EMAIL FROM DEBTORS_INFO WHERE IS_DEALER='Y' AND IS_CURRENT='Y' AND STATEMENT_REMINDER = 'Y' AND STATEMENT_EMAIL IS NOT NULL";
	
	$stid = oci_parse($conn, $query);
	oci_execute($stid);

	$email_list = [];

	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$email_list[] = $row;
	}

	oci_free_statement($stid);
	oci_close($conn);

	return $email_list;
}

function get_statement_list()
{
	$directory = 'C:/xampp/htdocs/icdev/debtor_statement/email/files';

	$files_and_dirs = scandir($directory);

	// Filter out the directories, keeping only the files
	$files = array_filter($files_and_dirs, function($item) use ($directory) {
		return is_file($directory . DIRECTORY_SEPARATOR . $item);
	});

	return $files;
}

function make_files()
{
	$fl = array("0834IFM_Statement_20250114.pdf",
	"0836IFM_Statement_20250114.pdf",
	"0996IFM_Statement_20250114.pdf",
	"1018IFM_Statement_20250114.pdf",
	"1088IFM_Statement_20250114.pdf",
	"1147IFM_Statement_20250114.pdf",
	"1158 - Excel statement 2025.01.12.xls",
	"1158IFM_Statement_20250114.pdf",
	"1211IFM_Statement_20250114.pdf",
	"1275IFM_Statement_20250114.pdf",
	"1327IFM_Statement_20250114.pdf",
	"1366IFM_Statement_20250114.pdf",
	"1385IFM_Statement_20250114.pdf",
	"1400 - Excel Statement 2025.01.12.xls",
	"1400IFM_Statement_20250114.pdf",
	"1408IFM_Statement_20250114.pdf",
	"1411IFM_Statement_20250114.pdf",
	"1442IFM_Statement_20250114.pdf",
	"1469IFM_Statement_20250114.pdf",
	"1493 - Weekly Statement 2025.01.12.pdf",
	"1517IFM_Statement_20250114.pdf",
	"1581IFM_Statement_20250114.pdf",
	"1595 - Excel Statement 2025.01.12.xls",
	"1595IFM_Statement_20250114.pdf",
	"1604 - Excel Statement 2025.01.12.xls",
	"1604IFM_Statement_20250114.pdf",
	"1611IFM_Statement_20250114.pdf",
	"1612IFM_Statement_20250114.pdf",
	"1643IFM_Statement_20250114.pdf",
	"1650IFM_Statement_20250114.pdf",
	"1677IFM_Statement_20250114.pdf",
	"1694 - Excel Statement 2025.01.12.xls",
	"1694IFM_Statement_20250114.pdf",
	"1701 - Weekly Statement 2025.01.12.pdf",
	"1706 - Excel Statement 2025.01.12.xls",
	"1706IFM_Statement_20250114.pdf",
	"1711IFM_Statement_20250114.pdf",
	"1714 - Excel Statement 2025.01.12.xls",
	"1714IFM_Statement_20250114.pdf",
	"1721IFM_Statement_20250114.pdf",
	"1725 - Weekly Statement 2025.01.12.pdf",
	"1735 - Excel Statement 2025.01.12.xls",
	"1735IFM_Statement_20250114.pdf",
	"1738IFM_Statement_20250114.pdf",
	"1742IFM_Statement_20250114.pdf",
	"1774 - Weekly Statement 2025.01.12.pdf",
	"1784 - Weekly Statement 2025.01.12.pdf",
	"1787 - Excel Statement 2025.01.12.xls",
	"1787 - Weekly Statement 2025.01.12.pdf",
	"1787IFM_Statement_20250114.pdf",
	"1789IFM_Statement_20250114.pdf",
	"1797 - Excel Statement 2025.01.12.xls",
	"1797IFM_Statement_20250114.pdf",
	"1806 - Excel Statement 2025.01.12.xls",
	"1806IFM_Statement_20250114.pdf",
	"1814IFM_Statement_20250114.pdf",
	"1819IFM_Statement_20250114.pdf",
	"1825IFM_Statement_20250114.pdf",
	"1836IFM_Statement_20250114.pdf",
	"1842IFM_Statement_20250114.pdf",
	"1851 - Excel Statement 2025.01.12.xls",
	"1851IFM_Statement_20250114.pdf",
	"1874IFM_Statement_20250114.pdf",
	"1882 - Excel Statement 2025.01.12.xls",
	"1882IFM_Statement_20250114.pdf",
	"1884IFM_Statement_20250114.pdf",
	"1885 - Excel Statement 2025.01.12.xls",
	"1885IFM_Statement_20250114.pdf",
	"1891IFM_Statement_20250114.pdf",
	"1892 - Weekly Statement 2025.01.12.pdf",
	"1894IFM_Statement_20250114.pdf",
	"1896IFM_Statement_20250114.pdf",
	"1900IFM_Statement_20250114.pdf",
	"1905 - Weekly Statement 2025.01.12.pdf",
	"1911IFM_Statement_20250114.pdf",
	"1914IFM_Statement_20250114.pdf",
	"1917IFM_Statement_20250114.pdf",
	"1918IFM_Statement_20250114.pdf",
	"1921IFM_Statement_20250114.pdf",
	"2000IFM_Statement_20250114.pdf",
	"2006IFM_Statement_20250114.pdf",
	"AMA001 - Statement 2025.01.07.pdf",
	"AMA006 - Statement 2025.01.12.pdf",
	"BON007 - Statement 2025.01.07.pdf",
	"CAP016 - Statement 2025.01.07.pdf",
	"DEP016 - Statement 2025.01.07.pdf",
	"DEP016 - Statement 2025.01.13.pdf",
	"DIA001 - Statement 2025.01.07.pdf",
	"DIA001 - Statement 2025.01.13.pdf",
	"DIS002 - Statement 2025.01.07.pdf",
	"DIS002 - Statement 2025.01.13.pdf",
	"ET0002 - Weekly Statement 2025.01.07.pdf",
	"FRE008 - Statement 2025.01.13.pdf",
	"FRE008 - Weekly Statement 2025.01.07.pdf",
	"GOT002 - Statement 2025.01.07.pdf",
	"GOT002 - Statement 2025.01.13.pdf",
	"HOE006 - Statement 2025.01.13.pdf",
	"HOE013 - Statement 2025.01.13.pdf",
	"INT003IFM_Statement_20250114.pdf",
	"IRI001IFM_Statement_20250107.pdf",
	"IRI001IFM_Statement_20250114.pdf",
	"KUS001 - Statement 2025.01.07.pdf",
	"KUS001 - Statement 2025.01.13.pdf",
	"M00001 - Statement 2025.01.07.pdf",
	"M00001 - Statement 2025.01.13.pdf",
	"MAP001 - Statement 2025.01.07.pdf",
	"NAM007 - Statement 2025.01.07.pdf",
	"NAM007 - Statement 2025.01.13.pdf",
	"NEO003IFM_Statement_20250107.pdf",
	"NEO003IFM_Statement_20250114.pdf",
	"ONE004 - Statement 2025.01.07.pdf",
	"ONE004 - Statement 2025.01.13.pdf",
	"ONE008 - Statement 2025.01.07.pdf",
	"ONE008 - Statement 2025.01.13.pdf",
	"PAR009 - Statement 2025.01.07.pdf",
	"Sanlam Invoice #0049 Dec 24.pdf",
	"Sanlam-20241201-20241231.xlsx",
	"SKY001 - Statement 2025.01.07.pdf",
	"SKY001 - Statement 2025.01.13.pdf",
	"TSG002 - Statement 2025.01.13.pdf",
	"UNI008 - Statement 2025.01.07.pdf",
	"UNI008 - Statement 2025.01.13.pdf",
	"ZCC003 - Statement 2025.01.13.pdf"
	);
	
	$directory = 'C:/xampp/htdocs/icdev/debtor_statement/email/files';
	$f2 = array(
		"UNI008 - Statement 2025.01.13.pdf",
		"ZCC003 - Statement 2025.01.13.pdf"
	);

	foreach ($fl as $filename) {
        // Create the full path to the file
        $file_path = $directory . DIRECTORY_SEPARATOR . $filename;

        // Open the file for writing (this will create the file if it does not exist)
        $handle = fopen($file_path, 'w');

        // Check if the file was created successfully
        if ($handle) {
            // Close the file handle
            fclose($handle);
            echo "Created file: $file_path\n";
        } else {
            echo "Failed to create file: $file_path\n";
        }
    }
}

$email_list = get_email_list();
$statement_list = get_statement_list();

print_r($statement_list);

die();

/*


define('FPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);

require_once("../php3/oracle.inc");
open_oracle();

/**
 * Parses a csv file into a multidimensional array
 * where $data[''headers'] is the headers, and $data['rows'] is the rows
 * 
 * @param string $filename - The csv file, this must be full path of the file
 * @return array multidimensional array with 'headers' and 'rows'
*/
/*
function parseCSV($filename){
    $data = [
        'headers' => null,
        'rows' => []
    ];

    if (($handle = fopen($filename, "r")) !== FALSE) {
        
        $data['headers'] = fgetcsv($handle, 1000, ",");

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $data['rows'][] = $row;
        }

        fclose($handle);
    }

    return $data;
}*/
/*
class Mail{
    public $from;
    public $to = [];
    public $cc = [];
    public $subject;
    public $message;
    public $attachments = [];

    private $body;

    function add_attachment($filename, $attachment_name){
        $this->attachments[$attachment_name] = $filename;
    }

    function send_disabled(){
        $this->body .= "--boundary\r\n";
        $this->body .= "Content-Type: text/html; charset=utf-8\r\n";
        $this->body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $this->body .= chunk_split(base64_encode($this->message));

        foreach ($this->attachments as $attachment_name => $filename){
            $attachment = chunk_split(base64_encode(file_get_contents($filename)));
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            $this->body .= "--boundary\r\n";
            $this->body .= "Content-Type: text/csv; name=\"" . basename($filename) . "\"\r\n";
            $this->body .= "Content-Transfer-Encoding: base64\r\n";
            $this->body .= "Content-Disposition: attachment; filename=\"" . "$attachment_name.$ext" . "\"\r\n\r\n";
            $this->body .= $attachment;
        }

        $this->body .= "--boundary--";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"boundary\"\r\n";

        if ($this->from){
            $headers .= "From: {$this->from}\r\n";
        }

        if (!empty($this->cc)){
            $headers .= 'CC: '.implode(', ', $this->cc);
        }

        if (mail(implode(', ', $this->to), $this->subject, $this->body, $headers)){
            return true;
        } else {
            $error = error_get_last();
            return false;
        }
    }
}

$agent_portal_link = 'https://secure.intercape.co.za/booking/invoices.phtml';

$mail = new Mail();
$mail->from = $noreply_email;
$mail->cc = [];
$mail->message = "<p style='font-family: 'Georgia', serif; font-size: 18px; letter-spacing: 0.5px; line-height: 1.6; color: #333; margin: 20px 0; padding: 10px;'>Good day
<br><br>
Hope you are well. 
<br><br>
Please note that your weekly statement can now be downloaded from the agent portal with link<br>
<a href='{$agent_portal_link}'>Agent Portal Link.</a>
<br><br>
Kindly email your proof of payment and remittance to <a href='mailto:debtors@intercape.co.za'>debtors@intercape.co.za</a>.
<br><br>
Please do not reply to this mail.
<br><br>
Kind regards,<br>
Intercape Debtors Controllers</p>";

$filename = '/usr/local/www/DEBTORS_INFO.csv';

$exist = file_exists($filename);

if (file_exists($filename)){
    $data = parseCSV($filename);

    if (!empty($data)){

        foreach ($data['rows'] as $row){
            list($account_code, $email) = $row;

            $mail->subject = "{$account_code} - Weekly Statement";
            $mail->to = [$email];

            if (!$mail->send()){
                $error = error_get_last();
            }
        }
    }
}
*/
?>
