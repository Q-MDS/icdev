<?php
$records = array(
	array('name' => 'JHB 1', 'branch' => 'JHB', 'stop_serial' => '217'),
	array('name' => 'JHB 1', 'branch' => 'JHB', 'stop_serial' => '217'),
	array('name' => 'JHB 1', 'branch' => 'JHB', 'stop_serial' => '217'),
	array('name' => 'JHB 1', 'branch' => 'JHB', 'stop_serial' => '217'),
	array('name' => 'JHB 1', 'branch' => 'JHB', 'stop_serial' => '217'),
	array('name' => 'JHB 1', 'branch' => 'JHB', 'stop_serial' => '217'),
	array('name' => 'JHB 1', 'branch' => 'JHB', 'stop_serial' => '217')
);
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

$records = array();

// OCI
$conn = oci_conn();

$sql = "SELECT * FROM DEPARTURE_TVS ORDER BY NAME, BRANCH";
	
$cursor = oci_parse($conn, $sql);
oci_execute($cursor);

while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
{
	$records[] = $row;
}

oci_close($conn);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
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
				<option value="1">Route 1</option>
				<option value="2">Route 2</option>
				<option value="3">Route 3</option>
			</select>
		</div>
		<div style="display: flex; flex-direction: row; align-items: center; width: 90px; height: 40px;">
			<input type="number" min="0" max="9" step="1" name="tv_stop_serial" id="tv_stop_serial" value="" placeholder="Stop Serial" style="width: 100%; height: 26px;" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
		</div>
		<div style="grid-column: span 2; display: flex; flex-direction: row; align-items: center; width: 100%; height: 40px;">
			<button id="add_remove" style="width: 100%; height: 32px;" onclick="saveTv()">Add</button>
		</div>

		<div style="grid-column: span 5; height: 2px; background-color: #000;"></div>

		<!-- Row 3: TV List -->
		<?php
			foreach($records as $row)
			{
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 1px solid #000; padding-left: 5px; height: 26px">'.$row['NAME'].'</div>';
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 1px solid #000; padding-left: 5px; height: 26px">'.$row['BRANCH'].'</div>';
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 1px solid #000; padding-left: 5px; height: 26px">'.$row['STOP_SERIAL'].'</div>';
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 0px solid #000; padding-left: 5px; height: 26px"><button id="add_remove" style=" width: 100%; height: 28px;" onclick="edit(' . json_encode($row) . ')>Edit</button></div>';
				echo '<div style="display: flex; flex-direction: row; align-items: center; border: 0px solid #000; padding-left: 5px; height: 26px"><button id="add_remove" style=" width: 100%; height: 28px;">Remove</button></div>';
			}
		?>
	</div>
</div>
</body>
</html>
<script>
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
		console.log('Result: ', result);
		window.location.reload(); 
	});
	console.log('Result: ', result);
}
function edit(data)
{
console.log('Data: ', data);
}
function updateTv()
{

}
function removeTv()
{

}

async function sendData(formData) 
{
	console.log('Send data: ', formData);
	const phpUrl = 'http://localhost/icdev/tv/tv_model.php';
	// const phpUrl = 'http://192.168.10.239/move/tv/tv_model.php';
	
	const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
	const result = await response.text();
	
	return result;
}
</script>