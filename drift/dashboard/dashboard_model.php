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

	public function getDepotTotals($month, $depot_list)
	{
		global $depot_totals;

		$ret = array();
		$result = array();
		// $month = 20241101;

		$conn = $this->oci_conn();

		foreach ($depot_list as $depot)
		{
			$sql = "SELECT * FROM DRIFT_DEPOT_TOTALS WHERE FOR_MONTH = $month AND DEPOT = '$depot'";

			$stid = oci_parse($conn, $sql);

			oci_execute($stid);

			$found = false;

			while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
			{
				$row['FOUND'] = 1;
				$depot_totals[] = $row;
				$found = true;
			}

			oci_free_statement($stid);

			if (!$found) {
				$depot_totals[] = array(
					'ID' => '1',
					'DEPOT' => $depot,
					'FOR_MONTH' => $month,
					'TRAINING_TRIPS' => 0,
					'OLD_CONTRACTS' => 0,
					'COMPLETED_TRAINING' => 0,
					'DISMISSED' => 0,
					'RESIGNED' => 0,
					'CLASS_TRAINING' => 0,
					'INTERVIEW' => 0,
					'CVS' => 0,
					'K53' => 0,
					'FOUND'	=> 0
				);
				$result[] = $depot;
			}
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

	public function getActiveDriversData($depot_list)
	{
		$data = array();
		$active_drivers = array();

		$conn = $this->oci_conn();
		
		$sql = "SELECT CASE WHEN DEPOT = 'CHB' THEN 'BLM' ELSE DEPOT END DEPOT, COUNT(*) DRIVERS FROM HC_PEOPLE WHERE ACTIVE != 'N' AND UPPER(ORG_UNIT) LIKE '%OPS DRIVER%' AND DEPOT != 'GON' AND DEPOT != 'INC' AND DEPOT != 'XXX' GROUP BY CASE WHEN DEPOT = 'CHB' THEN 'BLM' ELSE DEPOT END";

		$stid = oci_parse($conn, $sql);

		oci_execute($stid);

		while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
		{
			$data[] = $row;
		}

		oci_free_statement($stid);

		oci_close($conn);

		foreach ($depot_list as $depot)
		{
			$found = false;
			foreach ($data as $row)
			{
				if ($row['DEPOT'] == $depot)
				{
					$found = true;
					$active_drivers[] = $row['DRIVERS'];
				}
			}
			if (!$found)
			{
				$data[] = array('DEPOT' => $depot, 'DRIVERS' => 0);
			}
		}
		$total = array_sum($active_drivers);
		$active_drivers[] = $total;

		return $active_drivers;
	}

	public function getTrainingData($depot_list)
	{
		$data = array();
		$training = array();

		$conn = $this->oci_conn();
		
		$sql = "SELECT
			CASE WHEN DRIVER_DEPOT = 'CHB'
				THEN 'BLM'
				ELSE DRIVER_DEPOT
			END DRIVER_DEPOT,
			COUNT(*) DRIVERS
		FROM HC_PEOPLE A
		LEFT JOIN HC_TOWNS B
			ON A.PAYPOINT = B.TOWN_SERIAL
		WHERE ACTIVE != 'N'
			AND UPPER(ORG_UNIT) LIKE '%OPS DRIVER%'
			AND DEPOT = 'TRN'
		GROUP BY CASE WHEN DRIVER_DEPOT = 'CHB'
				THEN 'BLM'
				ELSE DRIVER_DEPOT
			END";

		$stid = oci_parse($conn, $sql);

		oci_execute($stid);

		while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
		{
			$data[] = $row;
		}

		oci_free_statement($stid);

		oci_close($conn);

		foreach ($depot_list as $depot)
		{
			$found = false;
			foreach ($data as $row)
			{
				if ($row['DRIVER_DEPOT'] == $depot)
				{
					$found = true;
					$training[] = $row['DRIVERS'];

				}
			}
			if (!$found)
			{
				$training[] = 0;
			}
		}

		$total = array_sum($training);
		$training[] = $total;

		return $training;
	}

	public function getDormantDriversData($depot_list)
	{
		$ret = array();
		$data = array();
		$dormant_drivers = array();
		$dormant_driver_counts = array();
		$hover_data = array();

		$conn = $this->oci_conn();
		
		$sql = "SELECT
			DEPOT,
			COUNT(*) DORMANT_DRIVERS
		FROM
			(SELECT DISTINCT
				DEPOT,
				PERSON_SERIAL
			FROM HC_PEOPLE A
			WHERE
				ACTIVE != 'N'
				AND UPPER(ORG_UNIT) LIKE '%OPS DRIVER%'
				AND DEPOT != 'GON'
				AND DEPOT != 'INC'
				AND DEPOT != 'XXX') A
		LEFT JOIN
			(SELECT DISTINCT
				SERIAL_NO
			FROM OPS_INFO
			WHERE ENTRY_TYPE = 'o'
				AND TO_DATE(RUNDATE,'YYYYMMDD') > SYSDATE - 3
				AND TO_DATE(RUNDATE,'YYYYMMDD') <= SYSDATE) B
		ON A.PERSON_SERIAL = B.SERIAL_NO
			WHERE B.SERIAL_NO IS NULL
		GROUP BY DEPOT
		";

		$stid = oci_parse($conn, $sql);

		oci_execute($stid);

		while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
		{
			$data[] = $row;
		}

		oci_free_statement($stid);

		oci_close($conn);

		// Test data
		// $data = array(
		// 	array('DEPOT' => 'CA', 'DORMANT_DRIVERS' => 230),
		// 	array('DEPOT' => 'WHK', 'DORMANT_DRIVERS' => 14),
		// );

		foreach ($depot_list as $depot)
		{
			$result = $this->find_depot($depot, $data);

			if ($result !== null)
			{
				$dormant_drivers[] = $result;
			}
			else
			{
				$dormant_drivers[] = 0;
			}
		}
		$total = array_sum($dormant_drivers);
		$dormant_drivers[] = $total;

		$hover_data = $this->getDormantDriversHover();

		$hover = array();
		foreach ($depot_list as $depot)
		{
			$string = '';
			
			foreach ($hover_data as $row)
			{
				if ($row['DEPOT'] == $depot)
				{
					$driver = $row['DRIVER'];
					$string .= $driver . ', ';
				} 
				else 
				{
					$string .= '';
				}
				
			}
			$hover[$depot] = $string;
		}

		$ret['dormant_drivers'] = $dormant_drivers;
		$ret['dormant_drivers_hover'] = $hover;
		
		return $ret;
	}

	private function find_depot($depot, $data)
	{
		foreach ($data as $item) {
			if ($item['DEPOT'] === $depot) {
				return $item['DORMANT_DRIVERS'];
			}
		}
		return null;
	}

	private function getDormantDriversHover()
	{
		$data = array();

		$conn = $this->oci_conn();
		
		$sql = "SELECT DISTINCT 
			DEPOT,
			UPPER(STAFFNO || ' - ' || NAME || ' ' || SURNAME || ' - ' || TO_DATE(MAX_RUNDATE,'YYYYMMDD')) DRIVER
		FROM HC_PEOPLE A
		LEFT JOIN
			(SELECT DISTINCT
				SERIAL_NO
			FROM OPS_INFO
			WHERE ENTRY_TYPE = 'o'
				AND TO_DATE(RUNDATE,'YYYYMMDD') > SYSDATE - 3
				AND TO_DATE(RUNDATE,'YYYYMMDD') <= SYSDATE) B
			ON A.PERSON_SERIAL = B.SERIAL_NO
		LEFT JOIN
			(SELECT
				SERIAL_NO,
				MAX(RUNDATE) MAX_RUNDATE
			FROM OPS_INFO
			WHERE ENTRY_TYPE = 'o'
			GROUP BY SERIAL_NO) C
			ON A.PERSON_SERIAL = C.SERIAL_NO
		WHERE
			ACTIVE != 'N'
			AND UPPER(ORG_UNIT) LIKE '%OPS DRIVER%'
			AND DEPOT != 'GON'
			AND DEPOT != 'INC'
			AND DEPOT != 'XXX'
			AND B.SERIAL_NO IS NULL
		";

		$stid = oci_parse($conn, $sql);

		oci_execute($stid);

		while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
		{
			$data[] = $row;
		}

		// Test data
		// $data = array(
		// 	array('DEPOT' => 'CA', 'DRIVER' => 'Bob'),
		// 	array('DEPOT' => 'CA', 'DRIVER' => 'Harry'),
		// 	array('DEPOT' => 'WHK', 'DRIVER' => 'John'),
		// 	array('DEPOT' => 'WHK', 'DRIVER' => 'Smools'),
		// );

		oci_free_statement($stid);

		oci_close($conn);

		return $data;
	}
}
?>
