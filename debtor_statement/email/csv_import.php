<?php
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

function get_csv($filename)
{
		$csv = array();
		$csv_file = __DIR__ . '/' . $filename; // Get the full path to the CSV file
	
		if (!file_exists($csv_file) || !is_readable($csv_file)) {
			return false; // Return false if the file does not exist or is not readable
		}
	
		$handle = fopen($csv_file, 'r');
		if ($handle !== FALSE) {
			$header = fgetcsv($handle, 1000, ','); // Read the first row as headers
			while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
				$csv[] = array_combine($header, $data); // Combine headers with data
			}
			fclose($handle);
		}
	
		return $csv;
}

function update($conn, $csv_mail, $account_code)
{
	$sql = "UPDATE DEBTORS_INFO SET STATEMENT_EMAIL = '$csv_mail', STATEMENT_REMINDER = 'N' WHERE ACCOUNT_CODE = '$account_code'";
	
	$cursor = oci_parse($conn, $sql);
	
	oci_execute($cursor);

	$rows_updated = oci_num_rows($cursor);

	oci_free_statement($cursor);

	return $rows_updated;
}

function start($csv_data)
{
	$conn = oci_conn();

	$found = array();
	$not_found = array();

	foreach ($csv_data as $row) 
	{
		$csv_mail = TRIM($row['CSV_MAIL']);
		$account_code = TRIM($row['ACCOUNT_CODE']);
		
		$result = update($conn, $csv_mail, $account_code);
		
		if ($result == 1)
		{
			$found[] = $account_code;
		}
		else
		{
			$not_found[] = $row;
		}
	}

	echo "Tots: " . count($found) . " >>> " . count($not_found) . "<br>";

	$i = 0;
	
	foreach ($not_found as $row) 
	{
		$csv_mail = TRIM($row['CSV_MAIL']);
		$account_code = TRIM($row['ACCOUNT_CODE']);
		$i++;
		add_not_found($conn, $csv_mail, $account_code);
	}

	echo "TOT: " . $i . "<br>";
	oci_close($conn);

}

function add_not_found($conn, $csv_mail, $account_code)
{
	$sql = "INSERT INTO DEBTORS_INFO (COMPANY_NAME, ACCOUNT_CODE, STATEMENT_EMAIL, STATEMENT_REMINDER) VALUES ('Import add', '$account_code', '$csv_mail', 'N')";
	
	$cursor = oci_parse($conn, $sql);
	
	oci_execute($cursor);

	oci_free_statement($cursor);
}

function remove_duplicates($array) 
{
    $unique_array = [];
    $account_codes = [];

    foreach ($array as $item) 
	{
        if (!in_array($item['ACCOUNT_CODE'], $account_codes))
		{
            $account_codes[] = $item['ACCOUNT_CODE'];
            $unique_array[] = $item;
        }
    }

    return $unique_array;
}

$csv_data = get_csv('DEBTORS_INFO.csv');

$csv_data = remove_duplicates($csv_data);

start($csv_data);

echo "END";
?>