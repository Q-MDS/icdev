<!-- 1. do the includes which presents the login
2. $user = getenv(“REMOTE_USER”); 
3. select branch from user_Details where is_current=’Y’ and username=’$user’ 
4. Get list of tvs from DEPARTURE_TVS where branch=’$branch’
5. Draw layout
   - Heasding: Departure TVs
   - grid - 2 columns
	 - 1st column: rectangle with tv code in center
	 - 2nd column: Select route dropdown - Row 1
	 - 2nd column: Add/remove button - Row 2 (if empty button is disabled, if has a route the active and ADD, if has route the active and REMOVE )
	   - read DEPARTURE_TV_SETTINGS for the tv code and route - if no record found button is disabled, user selects route from dropdown, button becomes enabled and ADD
	   - read DEPARTURE_TV_SETTINGS for the tv code and route - if record IS found, set route from data and button is enabled and called REMOVE -->
<?php
$user_id = '1052';
$branch = '1';
$tv_list = array();
$tv_settings = array();
$route_list = array();

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

// Step 1: get branch from USER_DETAILS
function get_user_branch()
{
	global $user_id, $branch;
	// $user_id = '1052';
	$conn = oci_conn();
	
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
	global $branch, $tv_list;

	$conn = oci_conn();

	$sql = "SELECT * FROM DEPARTURE_TVS WHERE BRANCH = '$branch'";
	$stid = oci_parse($conn, $sql);

	oci_execute($stid);

	while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
		$tv_list[] = $row;
	}

	oci_free_statement($stid);

	oci_close($conn);
}

// Step 3: get route list
function get_route_list()
{
	global $route_list;

	$date_from = date('Ymd');
	$date_to = date('Ymd');

	$conn = oci_conn();

	$sql = "SELECT CARRIER_CODE, ROUTE_NO, DESCRIPTION FROM ROUTE_DETAILS WHERE DATE_TO >= $date_from AND DATE_FROM <= $date_to";

	$stid = oci_parse($conn, $sql);

	oci_execute($stid);

	while ($row = oci_fetch_array($stid, OCI_ASSOC)) 
	{
		$route_list[] = $row;
	}

	oci_free_statement($stid);

	oci_close($conn);
}

function get_settings($tv_list)
{
	global $tv_settings;

	$conn = oci_conn();

	foreach ($tv_list as $tv) 
	{
		$screen_id = $tv['SCREEN_ID'];

		$sql = "SELECT * FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = '$screen_id'";

		$stid = oci_parse($conn, $sql);

		oci_execute($stid);

		$row = oci_fetch_array($stid, OCI_ASSOC);

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

// Step 4: look for record in DEPARTURE_TV_SETTINGS. If found use that record else use loop record with no settings
// use array $tv_list - cycle thru and see if there is a record in departure_tv_settings
// if there is then build an option for that tv, if NOT then leave it blank
// in the render loop, if there was a record in d_t_s then use it as the select and adjust the button to REMOVE, EELLSSEE no selected and button is ADD
// select screen_id from departure_tv_details where 

// Step 5: Present the TVs in the layout

// Step 6: Do button logic

// Step 7: Update DEPARTURE_TV_SETTINGS depending on button action

get_user_branch();	
get_tv_list();
get_route_list();
get_settings($tv_list);

// echo "AAA: $branch";

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Document</title>
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
				// echo '<div>' . count($tv['SETTINGS']) . '</div>'
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
baseUrl = window.location.protocol + "//" + window.location.hostname + "/icdev/";

function addRoute(screen_id)
{
	console.log("Add route: ", screen_id);
	const route = document.getElementById('route_' + screen_id);
	const route_value = JSON.parse(route.value);
	// const route_value = route.value;

	const route_no = route_value[0];
	const brand = route_value[1];;
	const route_description = route_value[2];

	const formData = { "action" : 0, "screen_id": screen_id, "route_no": route_no, "brand": brand, "route_description": route_description };

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
		// console.log('Result: ', result);
		window.location.reload(); 
	});
}
async function sendData(formData) 
{
	const phpUrl = baseUrl + 'tvroute/tv_route_model.php';
	
	const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
	const result = await response.text();
	
	return result;
}
</script>
</body>
</html>
