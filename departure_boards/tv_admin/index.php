<?php
$records = array();
$branch_list = array();
$stop_list = array();

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

function get_branch_list()
{
	global $branch_list;

	$conn = oci_conn();

	$sql = "SELECT BRANCH FROM USER_DETAILS GROUP BY BRANCH ORDER BY BRANCH";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$branch_list[] = $row;
	}

	oci_free_statement($cursor);
	oci_close($conn);

	// return $branch_list;
}

function get_stop_list()
{
	global $stop_list;

	$conn = oci_conn();

	$sql = "SELECT STOP_SERIAL, SHORTNAME FROM STOP_DETAILS2 ORDER BY SHORTNAME";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS))
	{
		$stop_list[] = $row;
	}

	oci_free_statement($cursor);
	oci_close($conn);
}

function get_records()
{
	global $records;

	// OCI
	$conn = oci_conn();

	$sql = "SELECT * FROM DEPARTURE_TVS WHERE IS_ACTIVE = '1' ORDER BY NAME, BRANCH";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$records[] = $row;
	}

	oci_close($conn);
}

get_branch_list();
get_stop_list();
get_records();

// print_r($branch_list);
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
	<div style="display: grid; grid-template-columns: 100px 150px 250px 200px 70px 70px; column-gap: 10px; align-items: center; row-gap: 5px">
		<!-- Column titles -->
		<div style="grid-column: span 6;">
			<h2>Departure TVs</h2>
		</div>
		<div style="grid-column: span 6;">
			Add TV
		</div>
		<!-- <div>Screen Id</div>
		<div>Name</div>
		<div>Branch</div>
		<div>Stop Serial</div>
		<div style="grid-column: span 2;">Actions</div> -->

		<div style="grid-column: span 6; height: 2px; background-color: #000;"></div>
	
		<!-- Row 2: Input form -->
		<div style="grid-column: span 2; display: flex; flex-direction: row; align-items: center; width: 100%; height: 40px;">
			<input type="text" maxlength="10" name="tv_name" id="tv_name" value="" placeholder="Name" style="height: 26px; width: 100%;">
		</div>

		<div style="display: flex; flex-direction: row; align-items: center; width: 250px; height: 40px;">
			<select name="tv_branch" id="tv_branch" style="width: 250px; height: 32px">
				<option value="0">Branch...</option>
				<?php
				foreach($branch_list as $row)
				{
					echo '<option value="' . $row['BRANCH'] . '">' . $row['BRANCH'] . '</option>';
				}
				?>
			</select>
		</div>

		<div style="display: flex; flex-direction: row; align-items: center; width: 200px; height: 40px;">
			<select name="tv_stop_serial" id="tv_stop_serial" style="width: 100%; height: 32px">
				<option value="0">Stop serial...</option>
				<?php
				foreach($stop_list as $row)
				{
					echo '<option value="' . $row['STOP_SERIAL'] . '">' . $row['SHORTNAME'] . '</option>';
				}
				?>
			</select>
			<!-- <input type="number" min="0" max="9" step="1" name="tv_stop_serial" id="tv_stop_serial" value="" placeholder="Stop Serial" style="width: 100%; height: 26px;" oninput="this.value = this.value.replace(/[^0-9]/g, '');"> -->
		</div>

		<div style="grid-column: span 2; display: flex; flex-direction: row; align-items: center; width: 100%; height: 40px;">
			<input type="text" id="tv_id" value="" style="display: none;">
			<button id="add_button" style="width: 100%; height: 32px;" onclick="saveTv()">Add</button>
			<button id="edit_button" style="width: 100%; height: 32px; display: none" onclick="updateTv()">Update</button>
		</div>

		<div style="grid-column: span 6; height: 2px; background-color: #000;"></div>

		<div>Screen Id</div>
		<div>Name</div>
		<div>Branch</div>
		<div>Stop Serial</div>
		<div style="grid-column: span 2;">Actions</div>

		<!-- Row 3: TV List -->
		<?php
			foreach($records as $row)
			{
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 1px solid #000; padding-left: 5px; height: 26px">'.$row['SCREEN_ID'].'</div>';
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
baseUrl = window.location.protocol + "//" + window.location.hostname + "/icdev/";

function saveTv()
{
	const tv_name = document.getElementById('tv_name').value;
	const tv_branch = document.getElementById('tv_branch').value;
	const tv_stop_serial = document.getElementById('tv_stop_serial').value;

	const formData = {
		action: 0,
		tv_name: tv_name,
		tv_branch: tv_branch,
		tv_stop_serial: tv_stop_serial,
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