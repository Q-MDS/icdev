<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

$records = array();
$from_date = 0;
$to_date = 0;

function get_records()
{
	global $conn, $records, $from_date, $to_date;

	$cursor = ora_open($conn);

	// 1709244000 - Friday, March 1, 2024 12:00:00 AM
	// 1730102398 - Monday, October 28, 2024 9:59:58 AM

	$sql = "SELECT affiliate_code, affiliate_name, PP.currency, sum(PP.total) total_rand, COUNT(PP.ticketno) tickets FROM AFFILIATES A, PURCHASER_INFO PU, PASSENGER_INFO PI, PRICE_PAID PP
		WHERE PU.paiddate >= $from_date AND PU.paiddate <= $to_date AND PU.commission_agent = affiliate_code AND PU.ticket_Serial = PI.ticket_serial
		AND PI.paid = 'Y'  AND PI.ticket_no = PP.ticketno GROUP BY PP.currency, affiliate_code, affiliate_name";
		
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
	{
		$records[] = $row;
	}

	ora_close($cursor);

	return $records;
}

if (isset($_POST['from_date']) && isset($_POST['to_date']))
{
	$get_from_date = $_POST['from_date'];
	$get_to_date = $_POST['to_date'];
	$from_date = strtotime($get_from_date);
	$to_date = strtotime($get_to_date);

	get_records();
} 
else 
{
	$get_from_date = '';
	$get_to_date = '';
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Affiliate Report</title>
	<link rel="stylesheet" href="litepicker.css"/>
	<script type="text/javascript" src="litepicker.js"></script>
</head>
<body>
	<div style="font-size: 24px;  padding: 5px; 20px;">Affiliate Report</div>
	<form action="index.php" method="post">
		<div style="background-color: #cacaca; height: 1px; width: 100%;  margin-top: 5px; margin-bottom: 5px;" ></div>
		<div style="display: flex; flex-direction: row; align-items: center; column-gap: 10px; padding: 5px; 20px;">
			<div>Enter date range:</div>
			<div><input type="text" id="from_date" name="from_date" value="<?php echo $get_from_date; ?>" style="width: 90px" placeholder="From date" /></div>
			<div><input type="text" id="to_date" name="to_date" value="<?php echo $get_to_date; ?>" style="width: 90px" placeholder="To date" /></div>
			<div><input type="submit" value="View"></div>
		</div>
	</form>
	<div style="background-color: #cacaca; height: 2px; width: 100%;  margin-top: 5px; margin-bottom: 5px;" ></div>
	<div style="padding: 5px 5px;">
		<?php
		if (count($records) > 0)
		{
			echo '<div style="display: grid; grid-template-columns: repeat(5, auto);">';
				echo '<div style="font-weight: bold;">Affiliate Code</div>';
				echo '<div style="font-weight: bold;">Affiliate Name</div>';
				echo '<div style="font-weight: bold;">Currency</div>';
				echo '<div style="font-weight: bold;">Total</div>';
				echo '<div style="font-weight: bold;">Tickets</div>';
				echo '<div style="grid-column: span 5; background-color: #cacaca; height: 1px; width: 100%;  margin-top: 5px; margin-bottom: 5px;" ></div>';
				foreach ($records as $record)
				{
					echo '<div style="padding: 3px 0px">' . $record['AFFILIATE_CODE'] . '</div>';
					echo '<div style="padding: 3px 0px">' . $record['AFFILIATE_NAME'] . '</div>';
					echo '<div style="padding: 3px 0px">' . $record['CURRENCY'] . '</div>';
					echo '<div style="padding: 3px 0px">' . $record['TOTAL_RAND'] . '</div>';
					echo '<div style="padding: 3px 0px">' . $record['TICKETS'] . '</div>';
					echo '<div style="grid-column: span 5; background-color: #cacaca; height: 1px; width: 100%;  margin-top: 5px; margin-bottom: 5px;" ></div>';
				}


			echo '</div>';
		}
		else 
		{
			echo '<div>No records found</div>';
		}
		?>
		</div>
	</div>
	<script>
		const fromDate = new Litepicker({ element: document.getElementById('from_date') });
		const toDate = new Litepicker({ element: document.getElementById('to_date') });
	</script>
</body>
</html>