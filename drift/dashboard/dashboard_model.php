<?php
class Dashboard_model
{
	private $depot_totals;

	public function __construct()
	{
		$this->depot_totals = array();
	}

	private function oci_conn()
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
			exit;
		} 
		else 
		{
			// echo "Connection succeeded";
		}
	
		return $conn;	
	}

	public function getDepotTotals($month)
	{
		global $depot_totals;

		$conn = $this->oci_conn();

		$sql = "SELECT * FROM DRIFT_DEPOT_TOTALS WHERE FOR_MONTH = $month ORDER BY DEPOT";

		$stid = oci_parse($conn, $sql);

		oci_execute($stid);

		while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
		{
			$depot_totals[] = $row;
		}

		oci_free_statement($stid);

		oci_close($conn);

		return $depot_totals;
	}
}




?>