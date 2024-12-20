<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

// Get user id from system
$user_id = '0210';

$branch = '1';
$tv_list = array();
$tv_settings = array();
$route_list = array();

// Step 1: get branch from USER_DETAILS
function get_user_branch()
{
	global $conn, $user_id, $branch;
	
	$cursor = ora_open($conn);
	
	$sql = "SELECT BRANCH FROM USER_DETAILS WHERE STAFF_NO = '$user_id'";

	ora_parse($cursor, $sql);
	ora_exec($cursor);

	ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC);

	if ($row === false) {
        echo "No rows found for user ID: $user_id";
    } else {
        $branch = $row['BRANCH'];
    }

	ora_close($cursor);
}

// Step 2: get list of TVs from DEPARTURE TVS where branch = branch
function get_tv_list()
{
	global $conn, $branch, $tv_list;

	$cursor = ora_open($conn);

	echo "Looking for $branch<bR>";
	$sql = "SELECT * FROM DEPARTURE_TVS WHERE BRANCH = '$branch' AND IS_ACTIVE = 1";
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	// while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
	{
		$tv_list[] = $row;
	}

	ora_close($cursor);
}

// Step 3: get route list
function get_route_list()
{
	global $conn, $route_list;

	// Dates are important in order to retrieve the list
	$date_from = date('Ymd');
	$date_to = date('Ymd');

	$cursor = ora_open($conn);

	$sql = "SELECT CARRIER_CODE, ROUTE_NO, DESCRIPTION FROM ROUTE_DETAILS WHERE DATE_TO >= $date_from AND DATE_FROM <= $date_to";

	ora_parse($cursor, $sql);
	ora_exec($cursor);

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
	{
		$route_list[] = $row;
	}

	ora_close($cursor);
}

// Step 4: look for record in DEPARTURE_TV_SETTINGS. If found use that record else use loop record with no settings
function get_settings($tv_list)
{
	global $conn, $tv_settings;

	$cursor = ora_open($conn);

	foreach ($tv_list as $tv) 
	{
		$screen_id = $tv['SCREEN_ID'];

		$sql = "SELECT * FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = '$screen_id'";

		ora_parse($cursor, $sql);
		ora_exec($cursor);

		// $row = oci_fetch_array($stid, OCI_ASSOC); // OCI
		ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC);

		if ($row === false) 
		{
			$tv['SETTINGS'] = array();
		} 
		else 
		{
			$tv['SETTINGS'] = $row;
		}

		$tv_settings[] = $tv;
	}

	ora_close($cursor);
}

get_user_branch();	
get_tv_list();
get_route_list();
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
	foreach ($tv_settings as $tv) { ?>
	<div style="display: grid; grid-template-columns: 1fr; width: 100%; border: 1px solid #000;">
		<div style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; padding-left: 10px;">
			<div style="padding: 17px 20px; margin-top: 10px; border: 1px solid #000; background-color: #cacaca">
				<input type="text" value="<?php echo $tv['SCREEN_ID']; ?>" style="display: none;" readonly>
				<?php echo $tv['NAME']; ?>
			</div>
		</div>
		<div>
			<div style="padding: 10px;">
				<?php
				echo "<div>Screen ID: ".$tv['SCREEN_ID']."</div>";
				if (count($tv['SETTINGS']) > 0)
				{
					echo "<div>Route: {$tv['SETTINGS']['ROUTE_NO']} - {$tv['SETTINGS']['ROUTE_DESCRIPTION']}</div>";
					echo '<div style="padding-top: 5px"><input type="button" value="Remove" style="width: 75px; height: 40px;" onclick="removeRoute(' . $tv['SCREEN_ID'] . ')"></div>';
				}
				else
				{
					echo '<select name="route_' . $tv['SCREEN_ID'] , '" id="route_' . $tv['SCREEN_ID'] . '" style="max-width: 340px; height: 35px">';
					echo "<option value=''>Select...</option>";
					foreach ($route_list as $route) 
					{
						$route_no = $route['ROUTE_NO'];
						$carrier_code = $route['CARRIER_CODE'];
						$description = $route['DESCRIPTION'];

						$value = array($route_no, $carrier_code, $description);
						$option_value = json_encode($value);

						echo "<option value='{$option_value}'>{$route['ROUTE_NO']} - {$route['DESCRIPTION']}</option>";
					}
					echo '</select>';
					echo '<div style="padding-top: 5px"><input type="button" value="Add" style="width: 75px; height: 40px;" onclick="addRoute(' . $tv['SCREEN_ID'] . ')"></div>';
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
	const route_value = JSON.parse(route.value);

	const route_no = route_value[0];
	const brand = route_value[1];;
	const route_description = route_value[2];

	const formData = { "action" : 0, "screen_id": screen_id, "route_no": route_no, "brand": brand, "route_description": route_description };

	const result = sendData(formData)
	.then(result => 
	{
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
