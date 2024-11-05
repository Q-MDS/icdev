<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

$records = array();
$branch_list = array();

function get_branch_list()
{
	global $conn, $branch_list;

	$cursor = ora_open($conn);

	// $sql = "SELECT BRANCH FROM USER_DETAILS GROUP BY BRANCH ORDER BY BRANCH";
	$sql = "SELECT branch_name FROM branch_info WHERE is_dealer='N' ORDER BY branch_name";
		
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
	{
		$branch_list[] = $row;
	}

	ora_close($cursor);

	return $branch_list;
}

function get_records()
{
	global $conn, $records;

	// OCI
	$cursor = ora_open($conn);

	$sql = "SELECT * FROM DEPARTURE_TVS WHERE IS_ACTIVE = '1' ORDER BY NAME, BRANCH";
		
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
	{
		$records[] = $row;
	}

	ora_close($cursor);

	// ORA
	/*global $conn;

	$cursor = ora_open($conn);

	// Get all vehicle_checklist serials into an array
	$sql = "SELECT id, vehicleserial FROM vehicle_checklist";
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
	{
		$records[] = $row;
	}
	ora_close($cursor);*/
}

get_branch_list();
get_records();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>TV Manager</title>
	<style>
		html {
			box-sizing: box;
		}
	</style>
</head>
<body>
	
<div>
	<div style="display: grid; grid-template-columns: 150px 250px 90px 70px 70px; column-gap: 10px; align-items: center; row-gap: 5px">
		<!-- Column titles -->
		<div style="grid-column: span 5;">
			<h2>Departure TVs</h2>
		</div>
		<div>Name</div>
		<div>Branch</div>
		<div>Stop Serial</div>
		<div style="grid-column: span 2;">Actions</div>

		<div style="grid-column: span 5; height: 2px; background-color: #000;"></div>
	
		<!-- Row 2: Input form -->
		<div style="display: flex; flex-direction: row; align-items: center; width: 150px; height: 40px;">
			<input type="text" maxlength="10" name="tv_name" id="tv_name" value="" placeholder="Name" style="height: 26px; width: 100%;">
		</div>
		<div style="display: flex; flex-direction: row; align-items: center; width: 250px; height: 40px;">
			<select name="tv_branch" id="tv_branch" style="width: 100%; height: 32px">
				<option value="0">Branch...</option>
				<?php
				foreach($branch_list as $row)
				{
					echo '<option value="' . $row['BRANCH_NAME'] . '">' . $row['BRANCH_NAME'] . '</option>';
				}

				?>
			</select>
		</div>
		<div style="display: flex; flex-direction: row; align-items: center; width: 90px; height: 40px;">
			<input type="number" min="0" max="9" step="1" name="tv_stop_serial" id="tv_stop_serial" value="" placeholder="Stop Serial" style="width: 100%; height: 26px;" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
		</div>
		<div style="grid-column: span 2; display: flex; flex-direction: row; align-items: center; width: 100%; height: 40px;">
			<input type="text" id="tv_id" value="" style="display: none;">
			<button id="add_button" style="width: 100%; height: 32px;" onclick="saveTv()">Add</button>
			<button id="edit_button" style="width: 100%; height: 32px; display: none" onclick="updateTv()">Update</button>
		</div>

		<div style="grid-column: span 5; height: 2px; background-color: #000;"></div>

		<!-- Row 3: TV List -->
		<?php
			foreach($records as $row)
			{
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 1px solid #000; padding-left: 5px; height: 26px">'.$row['NAME'].'</div>';
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 1px solid #000; padding-left: 5px; height: 26px">'.$row['BRANCH'].'</div>';
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 1px solid #000; padding-left: 5px; height: 26px">'.$row['STOP_SERIAL'].'</div>';
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 0px solid #000; padding-left: 5px; height: 26px"><button id="' . $row['SCREEN_ID'] . '" style=" width: 100%; height: 28px;" onclick="edit(this.id)">Edit</button></div>';
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 0px solid #000; padding-left: 5px; height: 26px"><button id="d_' . $row['SCREEN_ID'] . '" style=" width: 100%; height: 28px;" onclick="removeTv(this.id)">Remove</button></div>';
			}
		?>
	</div>
</div>
</body>
</html>
<script>
baseUrl = window.location.protocol + "//" + window.location.hostname + "/move/";

function saveTv()
{
	const tv_name = document.getElementById('tv_name').value;
	const tv_branch = document.getElementById('tv_branch').value;
	const tv_stop_serial = document.getElementById('tv_stop_serial').value;

	const formData = {
		action: 0,
		tv_name: tv_name,
		tv_branch: tv_branch,
		tv_stop_serial: tv_stop_serial
	};

	const result = sendData(formData)
	.then(result => 
	{
		// console.log('Result: ', result);
		window.location.reload(); 
	});
}
function edit(tvId)
{
	const formData = {
		action: 1,
		tv_id: tvId
	};

	const result = sendData(formData)
	.then(result => 
	{
		const record = JSON.parse(result);
		console.log('Result xxx: ', record);
		if (record.length == 0)
		{
			console.log('Nothing');	
		}
		else
		{
			document.getElementById('add_button').style.display = "none";
			document.getElementById('edit_button').style.display = "block";
			document.getElementById('tv_id').value = record.SCREEN_ID;
			document.getElementById('tv_name').value = record.NAME;
			document.getElementById('tv_branch').value = record.BRANCH;
			document.getElementById('tv_stop_serial').value = record.STOP_SERIAL;
		}
	});
}
function updateTv()
{
	const tv_id = document.getElementById('tv_id').value;
	const tv_name = document.getElementById('tv_name').value;
	const tv_branch = document.getElementById('tv_branch').value;
	const tv_stop_serial = document.getElementById('tv_stop_serial').value;

	const formData = {
		action: 2,
		tv_id: tv_id,
		tv_name: tv_name,
		tv_branch: tv_branch,
		tv_stop_serial: tv_stop_serial
	};

	const result = sendData(formData)
	.then(result => 
	{
		window.location.reload(); 
	});
}

function removeTv(id)
{
	const tv_id = id.split('_')[1];

	if (confirm('Are you sure you want to remove this TV?'))
	{
		console.log('Remove TV XXX: ', id);
		const formData = { action: 3, tv_id: tv_id };

		const result = sendData(formData)
		.then(result => 
		{
			window.location.reload(); 
		});
	}
}

async function sendData(formData) 
{
	console.log('Send data: ', formData);
	
	const phpUrl = baseUrl + 'departure_boards/tv_admin/tv_model.php';
	
	const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
	const result = await response.text();
	
	return result;
}
</script>