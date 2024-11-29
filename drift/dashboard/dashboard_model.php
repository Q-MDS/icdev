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

		$ret = array();
		$result = 0;
		// $month = 20241101;

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

		if ($depot_totals == null)
		{
			$depot_totals = array(
				0 => array('ID' => '1', 'DEPOT' => 'BLM', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				1 => array('ID' => '1', 'DEPOT' => 'CA', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				2 => array('ID' => '1', 'DEPOT' => 'CBS', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				3 => array('ID' => '1', 'DEPOT' => 'DBN', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				4 => array('ID' => '1', 'DEPOT' => 'GAB', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				5 => array('ID' => '1', 'DEPOT' => 'MAP', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				6 => array('ID' => '1', 'DEPOT' => 'MTH', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				7 => array('ID' => '1', 'DEPOT' => 'PE', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				8 => array('ID' => '1', 'DEPOT' => 'PTA', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				9 => array('ID' => '1', 'DEPOT' => 'UPT', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
				10 => array('ID' => '1', 'DEPOT' => 'WDH', 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' =>  0, 'COMPLETED_TRAINING' =>  0, 'DISMISSED' =>  0, 'RESIGNED' =>  0, 'CLASS_TRAINING' =>  0, 'INTERVIEW' =>  0, 'CVS' => 0),
			);
		} 
		else 
		{
			$result = 1;
		}

		$ret = array('result' => $result, 'depot_totals' => $depot_totals);

		return $ret;
	}

	public function getDriftData($month)
	{
		$ret = array();
		$data = array();
		$result = 0;
		$month = 20241101;
		$month_timestamp = strtotime($month);
		$date_range_name = date('F Y', $month_timestamp);

		$last_updated = array();
		$min_drivers_needed = array();
		$total_trips = array();

		$depot = array("BLM", "CA", "CBS", "DBN", "GAB", "MAP", "MTH", "PE", "PTA", "UPT", "WHK");

		$conn = $this->oci_conn();
		
		$sql = "SELECT A.RUN_ID, A.DEPOT, A.LAST_GENERATED, B.DATE_RANGE_NAME, C.NAME, C.VALUE FROM DRIFT_RUN A LEFT JOIN DRIFT_DATE_RANGES B ON A.TIME_PERIOD = B.DATE_RANGE_SERIAL LEFT JOIN DRIFT_OUTPUT_LINES C ON A.RUN_ID = C.RUN_ID LEFT JOIN  (SELECT DEPOT, DATE_RANGE_NAME, MAX(A.LAST_GENERATED) LAST_GENERATED FROM DRIFT_RUN A LEFT JOIN DRIFT_DATE_RANGES B ON A.TIME_PERIOD = B.DATE_RANGE_SERIAL WHERE B.IS_CURRENT = 'Y' GROUP BY DEPOT, DATE_RANGE_NAME) D ON B.DATE_RANGE_NAME = D.DATE_RANGE_NAME AND A.LAST_GENERATED = D.LAST_GENERATED AND A.DEPOT = D.DEPOT WHERE B.IS_CURRENT = 'Y' AND D.DATE_RANGE_NAME IS NOT NULL";

		$stid = oci_parse($conn, $sql);

		oci_execute($stid);

		while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
		{
			$data[] = $row;
		}

		oci_free_statement($stid);

		oci_close($conn);

		// Calcs
		foreach ($data as $row)
		{
			$depot = $row['DEPOT'];
			$drn = $row['DATE_RANGE_NAME'];
			if (isset($row['NAME']))
			{
				$name = $row['NAME'];
			}
			else
			{
				$name = null;
			}
			if (isset($row['VALUE']))
			{
				$value = $row['VALUE'];
			}
			else
			{
				$value = null;
			}
			// $name = $row['NAME'];
			$last_generated = $row['LAST_GENERATED'];
			
			if ($drn == $date_range_name)
			{
				if ($depot == 'BLM')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'CA')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'CBS')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'DBN')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'GAB')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'MAP')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'MTH')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'PE')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'PTA')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'UPT')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
				if ($depot == 'WHK')
				{
					if ($name == 'Total Trips')
					{
						$total_trips[] = $value;
					}
					if ($name == 'Minimum Drivers Needed')
					{
						$min_drivers_needed[] = $value;
						$last_updated[] = $last_generated;
					}
				}
			}
		}
		// $total_trips[] = 999;
		// $min_drivers_needed[] = 888;
		// print_r($total_trips);
		// print_r($min_drivers_needed);
		// print_r($last_updated);
		
		// die();


		$ret['last_updated'] = $last_updated;
		$ret['min_drivers_needed'] = $min_drivers_needed;
		$ret['total_trips'] = $total_trips;

		// return array("2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20");
		return $ret;
	}
}




?>