<?php
// Add on line 433: // echo '<input type="button" value="BUYER ACTION REQUIRED" style="padding: 5px 10px; margin-bottom: 10px;">';

$stores = 111;
$user_type = 's'; // s->store, b->buyer
$buyer_id = 222;
$pr_serial = 44445555;
$buyer_records = array();
$action = 0;

if (isset($_GET['action'])) 
{
	$action = $_GET['action'];
	
	switch($action)
	{
		case 1:
			echo 'Add buyer action here';
			buyer_action_required();
		break;
		case 2:
			echo 'Show buyer inbox';
			get_buyer_records();
		break;
		case 3:
			echo 'Take ownership';
			take_ownership();
		break;
		case 3:
			echo 'Parts received';
		break;
	}
}

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
		exit;
	} 
	else 
	{
		// echo "Connection succeeded";
	}

	return $conn;
}

function buyer_action_required()
{
	global $stores, $buyer, $pr_serial;

	$now = strtotime(date("Y-m-d H:i:s"));

	$conn = oci_conn();

	$sql = "UPDATE MOVE_JOBS_PART_REQUESTS SET BUYER_REQUESTED_BY = $stores, BUYER_REQUESTED_DATE = $now WHERE PR_SERIAL = $pr_serial";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	oci_close($conn);
}

function get_buyer_records()
{
	global $buyer_records;

	$conn = oci_conn();

	$sql = "SELECT * FROM MOVE_JOBS_PART_REQUESTS WHERE BUYER_REQUESTED_DATE IS NOT NULL  ORDER BY BUYER_REQUESTED_DATE DESC";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$buyer_records[] = $row;
	}

	oci_close($conn);
}

function take_ownership()
{
	global $buyer_id, $pr_serial;

	$now = strtotime(date("Y-m-d H:i:s"));

	$conn = oci_conn();

	$sql = "UPDATE MOVE_JOBS_PART_REQUESTS SET BUYER_OWNER = $buyer_id, BUYER_OWNER_DATE = $now WHERE PR_SERIAL = $pr_serial";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	oci_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
</head>
<body>
	<hr/>
	<?php
	if ($user_type == 's')
	{
		echo '<div style="margin-top: 5px; margin-bottom: 5px;">';
			echo '<a href="index.php?action=1" style="text-decoration: none;">';
			echo '<div style="display: flex; align-items: center; justify-content: center; border-radius: 4px; background-color: #eaeaea; border: 1px solid #000; padding: 5px 10px; width: 230px; ">';
			echo 'BUYER ACTION REQUIRED';
			echo '</div>';
			echo '</a>';
		echo '</div>';
	}
	?>
	<p>
	<?php
	if ($user_type == 'b')
	{
		echo '<a href="index.php?action=2" style="text-decoration: none">';
		echo '<div style="display: flex; align-items: center; justify-content: center; border-radius: 4px; background-color: #eaeaea; border: 1px solid #000; padding: 5px 10px; width: 230px; ">';
		echo 'BUYER INBOX';
		echo '</div>';
		echo '</a>';
	}
	?>
	<hr/>

	<?php
	if ($action == 2)
	{
		echo '<div style="display: grid; grid-template-columns: 120px 100px 1fr 120px 120px 120px 80px 180px; row-gap: 5px">';
		echo '<div style="grid-column: span 8;"><h3>Buyer action required</h3></div>';
			echo '<div>Jobcard Serial</div>';
			echo '<div>PR Number</div>';
			echo '<div style="grid-column: span 2;">Part</div>';
			echo '<div>Requested Date</div>';
			echo '<div>Requested By</div>';
			echo '<div>Days Open</div>';
			echo '<div>Action</div>';

			echo '<div style="grid-column: span 8;"><hr/></div>';

			foreach($buyer_records as $row)
			{
				if ($row['BUYER_REQUESTED_DATE'] != NULL && $row['BUYER_OWNER'] == NULL)
				{
					$now = strtotime(date("Y-m-d H:i:s"));
					// $days_open = date_diff(date_create($row['BUYER_REQUESTED_DATE']), date_create(date('Y-m-d')))->format('%a');
					$days_open = ($now - $row['BUYER_REQUESTED_DATE']) / 86400;

					echo '<div>' . $row['JOBCARDSERIAL'] . '</div>';
					echo '<div>' . $row['PR_SERIAL'] . '</div>';
					echo '<div style="grid-column: span 2">' . $row['REQUEST_TEXT'] . '</div>';
					echo '<div>' . date("Y-m-d", $row['BUYER_REQUESTED_DATE']) . '</div>';
					echo '<div>' . $row['BUYER_REQUESTED_BY'] . '</div>';
					echo '<div>' . number_format($days_open, 0) . '</div>';
					echo '<div>';
						echo '<a href="index.php?action=3" style="text-decoration: none">';
							echo '<div style="display: flex; align-items: center; justify-content: center; border-radius: 4px; background-color: #eaeaea; border: 1px solid #000; padding: 5px 10px; width: 150px; ">';
								echo 'TAKE OWNERSHIP';
							echo '</div>';
						echo '</a>';
					echo '</div>';
				}
			}

			echo '<div style="grid-column: span 8; font-weight: bold;"><hr/></div>';
			echo '<div style="grid-column: span 8; font-weight: bold;"><h3>My buyer requests</h3></div>';

			echo '<div>Jobcard Serial</div>';
			echo '<div>PR Number</div>';
			echo '<div>Part</div>';
			echo '<div>Owenership Date</div>';
			echo '<div>Requested Date</div>';
			echo '<div>Requested By</div>';
			echo '<div>Days Open</div>';
			echo '<div>Action</div>';

			echo '<div style="grid-column: span 8; font-weight: bold;"><hr/></div>';

			foreach($buyer_records as $row)
			{
				if ($row['BUYER_REQUESTED_DATE'] != NULL && $row['BUYER_OWNER'] == $buyer_id)
				{
					$now = strtotime(date("Y-m-d H:i:s"));
					$days_open = ($now - $row['BUYER_REQUESTED_DATE']) / 86400;

					echo '<div>' . $row['JOBCARDSERIAL'] . '</div>';
					echo '<div>' . $row['PR_SERIAL'] . '</div>';
					echo '<div>' . $row['REQUEST_TEXT'] . '</div>';
					echo '<div>' . date("Y-m-d", $row['BUYER_OWNER_DATE']) . '</div>';
					echo '<div>' . date("Y-m-d", $row['BUYER_REQUESTED_DATE']) . '</div>';
					echo '<div>' . $row['BUYER_REQUESTED_BY'] . '</div>';
					echo '<div>' . number_format($days_open, 0) . '</div>';
					echo '<div>';
						echo '<a href="index.php?action=4" style="text-decoration: none">';
							echo '<div style="display: flex; align-items: center; justify-content: center; border-radius: 4px; background-color: #eaeaea; border: 1px solid #000; padding: 5px 10px; width: 150px; ">';
								echo 'RECEIVED';
							echo '</div>';
						echo '</a>';
					echo '</div>';
				}
			}
		echo '</div>';
	}

	?>

</body>
</html>