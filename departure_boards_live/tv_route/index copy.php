<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

// Get user id from system
// $user_id = '0210';
//$branch = '1';

$user_id = getuserserial();;
$branch = $my_branch_name;

$tv_list = array();
$tv_settings = array();
$route_list = array();

// Step 1: get branch from USER_DETAILS
function get_user_branch()
{
	global $conn, $user_id, $branch;
	
	$sql = "SELECT BRANCH FROM USER_DETAILS WHERE STAFF_NO = '$user_id'";

	$stid = oci_parse($conn, $sql);

	oci_execute($stid);

	$row = oci_fetch_array($stid, OCI_ASSOC);

	if ($row === false) {
        echo "No rows found for user ID: $user_id";
    } else {
        $branch = $row['BRANCH'];
    }

	oci_free_statement($stid);

	oci_close($conn);
}

// Step 2: get list of TVs from DEPARTURE TVS where branch = branch
function get_tv_list()
{
	global $conn, $branch, $tv_list;

	//$sql = "SELECT * FROM DEPARTURE_TVS WHERE BRANCH = '$branch' AND IS_ACTIVE = 1";
	if (AllowedFlag("DEVELOPERS"))
	{
		$sql = "SELECT * FROM DEPARTURE_TVS WHERE IS_ACTIVE = 1 ORDER BY NAME";
	}
	else
	{
		$sql = "SELECT * FROM DEPARTURE_TVS WHERE BRANCH = '$branch' AND IS_ACTIVE = 1 ORDER BY NAME";
	}

	$stid = oci_parse($conn, $sql);

	oci_execute($stid);

	while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
	{
		$stop_serial = $row['STOP_SERIAL'];
		$short_name = get_stop_name($stop_serial);
		$route_list = get_route_list($short_name);
		$row['SHORT_NAME'] = $short_name;
		$row['ROUTE_LIST'] = $route_list;
		
		$tv_list[] = $row;
	}

	oci_free_statement($stid);

	oci_close($conn);
}

// Step 3: get route list
function get_route_list($short_name)
{
	// Dates are important in order to retrieve the list
	global $conn;

	$route_list = array();
	$date_from = date('Ymd');
	$date_to = date('Ymd');

	//$sql = "SELECT CARRIER_CODE, ROUTE_NO, DESCRIPTION FROM ROUTE_DETAILS WHERE DATE_TO >= $date_from AND DATE_FROM <= $date_to";

	$sql = "SELECT CARRIER_CODE, ROUTE_NO, DESCRIPTION FROM ROUTE_DETAILS WHERE DATE_FROM <= $date_from and DATE_TO >= $date_to
	AND ROUTE_SERIAL IN (
	SELECT ROUTE_SERIAL
	FROM ROUTE_STOPS
	WHERE SHORT_NAME = '$short_name' AND DATE_FROM <= $date_from and DATE_TO >= $date_to
	) ORDER BY ROUTE_NO
	";

	$stid = oci_parse($conn, $sql);

	oci_execute($stid);

	while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
	{
		$route_list[] = $row;
	}

	oci_free_statement($stid);

	oci_close($conn);

	return $route_list;
}

// Step 4: look for record in DEPARTURE_TV_SETTINGS. If found use that record else use loop record with no settings
function get_settings($tv_list)
{
	global $conn, $tv_settings;

	foreach ($tv_list as $tv) 
	{
		$screen_id = $tv['SCREEN_ID'];

		$sql = "SELECT * FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = '$screen_id'";

		$stid = oci_parse($conn, $sql);

		oci_execute($stid);

		$row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);

		if ($row === false) 
		{
			$tv['SETTINGS'] = array();
		} 
		else 
		{
			$tv['SETTINGS'] = $row;
		}

		$tv_settings[] = $tv;

		oci_free_statement($stid);
	}

	oci_close($conn);
}

// Get shortname from stop_serial
function get_stop_name($stop_serial)
{
	global $conn;

	$sql = "SELECT SHORTNAME FROM STOP_DETAILS2 WHERE STOP_SERIAL = '$stop_serial'";

	$stid = oci_parse($conn, $sql);

	oci_execute($stid);

	$row = oci_fetch_array($stid, OCI_ASSOC);

	$stop_name = TRIM($row['SHORTNAME']);

	oci_free_statement($stid);

	oci_close($conn);

	return $stop_name;
}

// get_user_branch();	
get_tv_list();
get_settings($tv_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TV Route Manager</title>
<style>
	body {
		padding: 0px;
		margin: 10px;
		box-sizing: border-box;
	}
</style>
</head>
<body>

<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; row-gap: 10px">
	<div style="width: 100%; margin-top: 0px; margin-bottom: 5px; font-weight: bold;">
		Departure TVs
	</div>
	<?php 
	// print_r($tv_settings);
	foreach ($tv_settings as $tv) { //print_r($tv); ?>
	<div style="display: grid; grid-template-columns: 1fr; width: 100%; border: 1px solid #000;">
		<div style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; padding-left: 10px;">
			<div style="padding: 17px 20px; margin-top: 10px; border: 1px solid #000; background-color: #cacaca">
				<input type="text" value="<?php echo $tv['SCREEN_ID']; ?>" style="display: none;" readonly>
				<?php echo $tv['NAME']; ?>
			</div>
			<div style="padding-left: 20px;">Screen Id: <?php echo $tv['SCREEN_ID']; ?></div>
		</div>
		<div>
			<div style="padding: 10px;">
				<?php
				if (count($tv['SETTINGS']) > 0)
				{
					if ($tv['SETTINGS']['ROUTE_NO_B']) 
					{ 
						echo "<div>Route 1 (Left): {$tv['SETTINGS']['ROUTE_NO']} - {$tv['SETTINGS']['ROUTE_DESCRIPTION']}</div>";
						echo "<div>Route 2 (Right): {$tv['SETTINGS']['ROUTE_NO_B']} - {$tv['SETTINGS']['ROUTE_DESCRIPTION_B']}</div>";
					} 
					else 
					{
						echo "<div>Route 1 (Full): {$tv['SETTINGS']['ROUTE_NO']} - {$tv['SETTINGS']['ROUTE_DESCRIPTION']}</div>";
					}
					echo '<div style="padding-top: 5px"><input type="button" value="Remove" style="width: 75px; height: 40px;" onclick="removeRoute(' . $tv['SCREEN_ID'] . ')"></div>';
				}
				else
				{
					echo '<div>Route 1 (Full/Left)</div>';
					echo '<div>';
						echo '<select name="route_' . $tv['SCREEN_ID'] , '" id="route_' . $tv['SCREEN_ID'] . '" style="max-width: 340px; height: 35px">';
						echo "<option value=''>Select...</option>";
						$routes = $tv['ROUTE_LIST'];
						
						foreach ($routes as $route) 
						{
							$route_no = $route['ROUTE_NO'];
							$carrier_code = $route['CARRIER_CODE'];
							$description = $route['DESCRIPTION'];

							$value = array($route_no, $carrier_code, $description);
							$option_value = json_encode($value);

							echo "<option value='{$option_value}'>{$route['ROUTE_NO']} - {$route['DESCRIPTION']}</option>";
						}
						echo '</select>';
					echo '</div>';

					echo '<div>Route 2 (Right)</div>';
					echo '<div>';
						echo '<select name="route_b_' . $tv['SCREEN_ID'] , '" id="route_b_' . $tv['SCREEN_ID'] . '" style="max-width: 340px; height: 35px">';
						echo "<option value=''>Select...</option>";
						$routes = $tv['ROUTE_LIST'];
						foreach ($routes as $route) 
						{
							$route_no = $route['ROUTE_NO'];
							$carrier_code = $route['CARRIER_CODE'];
							$description = $route['DESCRIPTION'];

							$value = array($route_no, $carrier_code, $description);
							$option_value = json_encode($value);

							echo "<option value='{$option_value}'>{$route['ROUTE_NO']} - {$route['DESCRIPTION']}</option>";
						}
						echo '</select>';
					echo '</div>';

					echo '<div style="padding-top: 5px"><input type="button" value="Add" style="width: 75px; height: 40px;" onclick="addRoute(' . $tv['SCREEN_ID'] . ')"></div>';
					// echo '</div>';
				}
				?>
			</div>
		</div>
	</div>
<?php } ?>
</div>
<script>
baseUrl = window.location.protocol + "//" + window.location.hostname + "/booking/";

function addRoute(screen_id)
{
	console.log("Add route: ", screen_id);
	const route = document.getElementById('route_' + screen_id);

	if (!route.value)
	{
		alert('Please select a route for Route 1');
		return;
	}
	
	const route_value = JSON.parse(route.value);
	const route_no = route_value[0];
	const brand = route_value[1];
	const route_description = route_value[2];

	const route_b = document.getElementById('route_b_' + screen_id);
	let route_no_b = '';
	let brand_b = '';
	let route_description_b = '';
	console.log('Route B: ', route_b.value);
	if (route_b.value != 'null' && route_b.value != '')
	{
		route_b_value = JSON.parse(route_b.value);
		route_no_b = route_b_value[0];
		brand_b = route_b_value[1];
		route_description_b = route_b_value[2];
	}

	const formData = { "action" : 0, "screen_id": screen_id, "route_no": route_no, "brand": brand, "route_description": route_description, "route_no_b": route_no_b, "brand_b": brand_b, "route_description_b": route_description_b };

	const result = sendData(formData)
	.then(result => 
	{
		// console.log('Result: ', result);
		window.location.reload(); 
	});
}
function removeRoute(screen_id)
{
	console.log("Remove route: ", screen_id);

	const formData = { "action" : 1, "screen_id": screen_id };

	const result = sendData(formData)
	.then(result => 
	{
		window.location.reload(); 
	});
}
async function sendData(formData) 
{
	const phpUrl = baseUrl + 'tv_route/tv_route_model.php';
	
	const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
	const result = await response.text();
	
	return result;
}
</script>
</body>
</html>
