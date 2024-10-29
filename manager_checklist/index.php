<?php
// Global vars
if (isset($_POST['set_depot']))
{
	$the_depot = $_POST['set_depot'];
}
else
{
	$the_depot = 'PTA';
}
$num_rows = 0;

function init()
{
	global $the_depot;
	
	$vehicles = [];
	$vehicle_checklist_id = [];
	$vehicle_checklist_serial = [];

	// OCI
	$conn = oci_conn();

	// Get all vehicle_checklist serials into an array
	$sql = "SELECT id, serial FROM vehicle_checklist";
	
	$cur2 = oci_parse($conn, $sql);
	oci_execute($cur2);
	while ($row = oci_fetch_array($cur2, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$id = $row['ID'];
		$serial = $row['SERIAL'];
		$vehicle_checklist_id[] = $id;
		$vehicle_checklist_serial[] = $serial;
	}
	// ORA: ora_close($cur2);
	// print_r($vehicle_checklist_serial);


	// Read vehicles and compare
	$sql = "SELECT SERIAL, DEPOT_AT, CLASS FROM vehicles WHERE is_current='Y' AND schedule = 'Y' AND class in ('o','c')";
	//  AND schedule = 'Y' AND class in ('o','c')
	
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	
	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$serial = $row['SERIAL'];
		$depot = $row['DEPOT_AT'];
		$class = $row['CLASS'];
		
		// Check if serial exists in vehicle_checklist
		// echo "Serial: $serial<br>";
		$index = array_search($serial, $vehicle_checklist_serial);

		if ($index !== false) 
		{
			// echo "Serial $serial found at index $index.<br>";
			update_checklist_vehicle($vehicle_checklist_id[$index], $depot, $class);
		} 
		else 
		{
			// echo "Serial $serial not found in the array.<br>";
			add_checklist_vehicle($serial, $depot, $class);
		}
	}
	
	oci_close($conn);
}

function add_checklist_vehicle($serial, $depot, $class)
{
	// OCI
	$conn = oci_conn();

	// Add with today as the work_date and add_date, checked is false, depot and class
	$today = strtotime(date('Y-m-d'));
	
	$sql = "INSERT INTO vehicle_checklist (id, serial, add_date, work_date, checked, depot, class) VALUES (VEHICLE_CHECKLIST_ID_SEQ.NEXTVAL,'" . $serial . "', '" . $today . "', '" . $today . "',0, '" . $depot . "', '" . $class . "')";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
}

function update_checklist_vehicle($id, $depot, $class)
{
	// OCI
	$conn = oci_conn();

	// Update if found
	$today = strtotime(date('Y-m-d'));
	// $today = strtotime('2024-10-22');
	$sql = "UPDATE vehicle_checklist SET work_date = '" . $today . "', depot = '" . $depot . "', class = '" . $class . "' WHERE id = '" . $id . "' AND work_date < '" . $today . "' AND checked = 0";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
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

function get_vehicles()
{
	global $the_depot, $num_rows;
	$today = strtotime(date('Y-m-d'));
	// $today = strtotime('2024-10-23');

	// OCI
	$conn = oci_conn();

	if ($the_depot == 0)
	{
		$sql = "SELECT vc.SERIAL, vc.CLASS, vc.ID, v.CODE, v.REG_NO, v.MAKE, v.MODEL FROM vehicle_checklist vc JOIN vehicles v ON vc.SERIAL = v.SERIAL WHERE vc.DEPOT IS NULL AND vc.WORK_DATE = " . $today . " ORDER BY vc.SERIAL";
	}
	else 
	{
		$sql = "SELECT vc.SERIAL, vc.CLASS, vc.ID, v.CODE, v.REG_NO, v.MAKE, v.MODEL FROM vehicle_checklist vc JOIN vehicles v ON vc.SERIAL = v.SERIAL WHERE vc.DEPOT = '" . $the_depot . "' AND vc.WORK_DATE = " . $today . " ORDER BY vc.SERIAL";
	}
	
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	
	$results = [];
	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$results[] = $row;
	}

	$num_rows = count($results);
	
	oci_close($conn);
	
	return $results;
}

init();
get_vehicles();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Manager Checklist</title>
	<style>
	/* Modal styles */
	.modal {
		display: none; /* Hidden by default */
		position: fixed; /* Stay in place */
		z-index: 1; /* Sit on top */
		left: 0;
		top: 0;
		width: 100%; /* Full width */
		height: 100%; /* Full height */
		overflow: auto; /* Enable scroll if needed */
		background-color: rgb(0,0,0); /* Fallback color */
		background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
	}

	/* Modal content */
	.modal-content {
		background-color: #fefefe;
		margin: 15% auto; /* 15% from the top and centered */
		padding: 20px;
		border: 1px solid #888;
		width: 80%; /* Could be more or less, depending on screen size */
		max-width: 400px;
	}

	/* Buttons */
	.modal-button {
		padding: 10px 20px;
		margin: 10px;
		cursor: pointer;
	}

	.modal-button.yes {
		background-color: green;
		color: white;
	}

	.modal-button.no {
		background-color: red;
		color: white;
	}

	.data_row {
    display: contents;
	}
	.data_row:hover div {
		background-color: #d5d5d5;
	}
	</style>
</head>
<body onload="fetchFaults();">
	

<div>
	<!-- Mock tablet menu -->
	<div style="display: flex; flex-direction: 'row'; align-items: center; border: 1px solid #000; padding: 8px 10px;">
		<div style="width: 300px">MANAGER TABLET MENU =></div>
		<?php
		$d = date('Y-m-d');
		?>
		<div style="flex: 1; cursor: pointer;">Vehicle Checklist<?php echo " Date: ". strtotime(date("Y-M-d")); ?></div>
	</div>
	
	<form action="index.php" method="post">
		<div style="display: flex; flex-direction: row; align-items: center; margin-top: 20px; margin-bottom: 10px; column-gap: 10px">
			<div>Select Depot:</div>
			<div>
			<select id="set_depot" name="set_depot">
				<option value="">Select depot</option>
				<option value="0" <?php echo ($GLOBALS['the_depot'] == '0') ? 'selected' : ''; ?>>Not at depot yet</option>
				<option value="BLM" <?php echo ($GLOBALS['the_depot'] == 'BLM') ? 'selected' : ''; ?>> BLM</option> 
				<option value="CA" <?php echo ($GLOBALS['the_depot'] == 'CA') ? 'selected' : ''; ?>>CA</option> 
				<option value="CBS" <?php echo ($GLOBALS['the_depot'] == 'CBS') ? 'selected' : ''; ?>>CBS</option> 
				<option value="DBN" <?php echo ($GLOBALS['the_depot'] == 'DBN') ? 'selected' : ''; ?>>DBN</option> 
				<option value="DEA" <?php echo ($GLOBALS['the_depot'] == 'DEA') ? 'selected' : ''; ?>>DEA</option> 
				<option value="ESL" <?php echo ($GLOBALS['the_depot'] == 'ESL') ? 'selected' : ''; ?>>ESL</option> 
				<option value="GAB" <?php echo ($GLOBALS['the_depot'] == 'GAB') ? 'selected' : ''; ?>>GAB</option> 
				<option value="JHB" <?php echo ($GLOBALS['the_depot'] == 'JHB') ? 'selected' : ''; ?>>JHB</option> 
				<option value="MAL" <?php echo ($GLOBALS['the_depot'] == 'MAL') ? 'selected' : ''; ?>>MAL</option> 
				<option value="MAP" <?php echo ($GLOBALS['the_depot'] == 'MAP') ? 'selected' : ''; ?>>MAP</option> 
				<option value="MAR" <?php echo ($GLOBALS['the_depot'] == 'MAR') ? 'selected' : ''; ?>>MAR</option> 
				<option value="MTH" <?php echo ($GLOBALS['the_depot'] == 'MTH') ? 'selected' : ''; ?>>MTH</option> 
				<option value="OSH" <?php echo ($GLOBALS['the_depot'] == 'OSH') ? 'selected' : ''; ?>>OSH</option> 
				<option value="PE" <?php echo ($GLOBALS['the_depot'] == 'PE') ? 'selected' : ''; ?>>PE</option> 
				<option value="POL" <?php echo ($GLOBALS['the_depot'] == 'POL') ? 'selected' : ''; ?>>POL</option> 
				<option value="PTA" <?php echo ($GLOBALS['the_depot'] == 'PTA') ? 'selected' : ''; ?>>PTA</option> 
				<option value="QTN" <?php echo ($GLOBALS['the_depot'] == 'QTN') ? 'selected' : ''; ?>>QTN</option> 
				<option value="UPT" <?php echo ($GLOBALS['the_depot'] == 'UPT') ? 'selected' : ''; ?>>UPT</option> 
				<option value="VIC" <?php echo ($GLOBALS['the_depot'] == 'VIC') ? 'selected' : ''; ?>>VIC</option> 
				<option value="WHK" <?php echo ($GLOBALS['the_depot'] == 'WHK') ? 'selected' : ''; ?>>WHK</option> 

			</select>
			</div>
			<div><input type="submit" value="View"></div>
		</div>
	</form>

	<div style="margin-top: 20px; margin-bottom: 10px; font-size: 18px;">Vehicle List (Records found: <?php echo $num_rows; ?>)</div>

	<!-- Grid for matching vehicles -->
	<!-- 
	Serial, Code, Reg No, Make, Model
	-->
	<div style="display: grid; grid-template-columns: repeat(8, auto); column-gap: 0px; row-gap: 5px; border: 1px solid #000; padding: 8px 10px; max-height: 335px; overflow: hidden; overflow-y: auto">
		<div>Serial</div>
		<div>Code</div>
		<div>Reg No</div>
		<div>Make</div>
		<div>Model</div>
		<div>Class</div>
		<div>Inspection Result</div>
		<div>&nbsp;</div>

		<div style="grid-column: span 8; height: 1px; background-color: #000;"></div>

		<?php
		$results = get_vehicles();

		foreach ($results as $row)
		{
			$id = $row['ID'];
			$serial = $row['SERIAL'];
			$code = $row['CODE'];
			$reg_no = $row['REG_NO'];
			$make = $row['MAKE'];
			$model = $row['MODEL'];
			$vehicle_class = $row['CLASS'];
			// $link_vehicle = $row['LINK_TO_VEHICLE'];
			// $link_docs = $row['LINK_TO_DOCUMENTS'];
			
			echo "<div class='data_row'>";
				echo '<div style="display: flex; align-items: center;">' . $serial . '</div>';
				echo '<div style="display: flex; align-items: center;">' . $code . '</div>';
				echo '<div style="display: flex; align-items: center;">' . $reg_no . '</div>';
				echo '<div style="display: flex; align-items: center;">' . $make . '</div>';
				echo '<div style="display: flex; align-items: center;">' . $model . '</div>';
				echo '<div style="display: flex; align-items: center;">' . $vehicle_class . '</div>';
				echo '<div id="y_' . $id . '_' . $serial . '" style="display: flex; align-items: center; justify-content: center; background: red; color: white; border-radius: 5px; border: 1px solid #000; padding: 5px 20px; cursor: pointer;" onclick="hasIssues(this.id);">Issues found</div>';
				echo '<div id="n_' . $id . '" style="display: flex; align-items: center; justify-content: center; background: green; color: white; border-radius: 5px; border: 1px solid #000; margin-left: 10px; padding: 5px 20px; cursor: pointer;" onclick="noIssues(this.id);">No issues</div>';
			echo "</div>";
		}
		?>
	</div>
	<p>	
	
	<!-- Fault picker -->
	<div style="display: flex; flex-direction: column; row-gap: 10px;">
	<div style="display: none;"><input id="vc_id" type="text" style="display: block" /></div>
	<div style="display: none;"><input id="vehicle_serial" type="text" /></div>
	</div>
	<div id="breadcrumbs" style="display: none; flex-direction: row; align-items: center; column-gap: 10px;"></div>
	<div id="fault_picker" style="display: none; flex-direction: column; row-gap: 10px; margin-top: 15px;"></div>
	<div id="the_fault" style="display: block; margin-top: 5px;"></div>
	<div id="fault_desc_div" style="display: none; margin-top: 15px">
		<label for="fault_desc">Please type in details of fault:</label>
		<textarea id="fault_desc" style="display: block; width: 50%; height: 100px;"></textarea>
	</div>
	<div id="form_footer" style="display: none; margin-top: 10px">
		<div style="display: flex; flex-direction: row; align-items: center; column-gap: 10px;">
			<div>Fault Picture</div>
			<div><input type="file" id="fault_picture" accept="image/*" /></div>
			<div id="upload_status"></div>
		</div>
		<!-- <div style="display: flex; align-items: center; justify-content: center; width: 100px; background: #cacaca; color: black; border-radius: 5px; border: 1px solid #000; margin-top: 10px; padding: 5px 20px; cursor: pointer;" onclick="saveIssue()">Save</div> -->
		<div style="display: flex; align-items: center; justify-content: center; width: 100px; background: #cacaca; color: black; border-radius: 5px; border: 1px solid #000; margin-top: 10px; padding: 5px 20px; cursor: pointer;" onclick="showCustomConfirm()">Save</div>
	</div>
</div>

<!-- Confirm modal -->
<div id="customConfirmModal" class="modal">
    <div class="modal-content">
		<p>Do you want to add another fault?</p>
		<button class="modal-button yes" onclick="handleYes()">Yes</button>
		<button class="modal-button no" onclick="handleNo()">No</button>
    </div>
</div>
</body>
</html>

<script>
tfcFaults = [];
breadCrumbs = [];
let saveFault = {};

function fetchFaults()
{
	// Fetch faults from server
	const formData = { "action": 2 };

	sendData(formData)
	.then(result => 
	{ 
		tfcFaults = JSON.parse(result);
		initFaults();
	});
}

function initFaults()
{
	const faults = getFaults(null);
	let faultList = '';

	faults.forEach(fault => 
	{
		faultList += '<div style="display: flex; align-items: center; cursor: pointer;" onclick="faultList(' + fault.TFC_ID + ')">' + fault.TFC_NAME + '</div>';
	});
	
	document.getElementById('fault_picker').innerHTML = faultList;
	
	breadCrumbs = [];
	const home = { TFC_ID: 'null', TFC_REF_CATEGORY: 'null', TFC_NAME: 'Home' };
	breadCrumbs.push(home);
	setBreadcrumbs();
}

function faultList(id)
{
	console.log('SelectedFault: ', saveFault);
	updateBreadCrumbs(id);
	const faultDescDiv = document.getElementById('fault_desc_div');
	const faultDesc = document.getElementById('fault_desc');
	const theFault = document.getElementById('the_fault');
	const formFooter = document.getElementById('form_footer');
	const faults = getFaults(id);
	
	let faultList = '';
	if (faults.length > 0)
	{
		faultDescDiv.style.display = 'none';
		theFault.style.display = "none";
		
		faults.forEach(fault => 
		{
			faultList += '<div style="display: flex; align-items: center; cursor: pointer;" onclick="faultList(' + fault.TFC_ID + ')">' + fault.TFC_NAME + '</div>';
		});
	} 
	else 
	{
		const fault = getSelectedFault(id);
		// saveFault.push(fault);
		saveFault = fault;
		console.log('Fault: ', fault); 
		theFault.style.display = "block";
		theFault.innerHTML = '<div style="display: flex; align-items: center; cursor: pointer; color: green" onclick="saveIssue()">A fault has been selected</div>';
		faultDescDiv.style.display = 'block';
		formFooter.style.display = 'block';
		faultDesc.focus();
	}

	document.getElementById('fault_picker').innerHTML = faultList;
	
	const selectedFault = getSelectedFault(id);
	
	breadCrumbs.push(selectedFault[0]);
	setBreadcrumbs();
}

function getFaults(id)
{
	return tfcFaults.filter(fault => fault.TFC_REF_CATEGORY == id);
}

function getSelectedFault(id)
{
	return tfcFaults.filter(fault => fault.TFC_ID == id);
}

function setBreadcrumbs()
{
	cleanBreadCrumbs();
	const bc = document.getElementById('breadcrumbs');

	let breadcrumbs = '';

	breadCrumbs.forEach(breadcrumb => 
	{
		breadcrumbs += '<div style="background: #f5f5f5; border-radius: 5; padding: 3px 10px; border: 1px solid #000; cursor: pointer" onclick="faultList(' + breadcrumb.TFC_ID + ')">' + breadcrumb.TFC_NAME + '</div>';
	});

	bc.innerHTML = breadcrumbs;
}

function updateBreadCrumbs(id) 
{
    cleanBreadCrumbs();

    if (id == null) 
	{
		initFaults();
		return;
	} 

	const index = breadCrumbs.findIndex(breadcrumb => breadcrumb.TFC_ID == id);
	
	if (index !== -1) 
	{
		breadCrumbs = breadCrumbs.slice(0, index);
    }
}

function cleanBreadCrumbs() 
{
    breadCrumbs = breadCrumbs.filter(breadcrumb => breadcrumb !== undefined);
}

function noIssues(id)
{
	// All that needs to happen is that the workday increases by 30 days
	console.log('No issues clicked: ' + id);
	let bits = id.split('_');
	const vcId = bits[1];

	const formData = { "action": 0, "vc_id": vcId };

	sendData(formData)
	.then(result => 
	{ 
		console.log('Result 2: ', result);
		if (result == 1)
		{
			window.location.reload(); 
		}
		else
		{
			alert('There was an error saving the results');
		}
	});
}

function hasIssues(id)
{
	console.log('Has issues clicked: ' + id);
	const bread_crumbs = document.getElementById('breadcrumbs');
	const fault_picker = document.getElementById('fault_picker');
	const vcId = document.getElementById('vc_id');
	const vehicleSerial = document.getElementById('vehicle_serial');

	// Show fault picker
	fault_picker.style.display = 'flex';
	bread_crumbs.style.display = 'flex';

	let bits = id.split('_');
	const getVcId = bits[1];
	vcId.value = getVcId;
	const getVehicleSerial = bits[2];
	vehicleSerial.value = getVehicleSerial;
}

function saveIssue(more)
{
	const vcId = document.getElementById('vc_id').value;
	const vehicleSerial = document.getElementById('vehicle_serial').value;
	const faultDesc = document.getElementById('fault_desc').value;

	let faultPicture = 'Y';
	const fileInput = document.getElementById('fault_picture');
    const file = fileInput.files[0];
    if (!file) 
	{
        faultPicture = 'N';
    } 
	else 
	{
		uploadPicture();
	}

	const selectedFault = saveFault[0].TFC_ID;

	console.log('Save issue: ', faultPicture + ' >>> ' + vehicleSerial, ' >>> ', faultDesc, ' >>> ', selectedFault);

	const formData = { "action": 3, "vc_id": vcId, "vehicle_serial": vehicleSerial, "fault_description": faultDesc, "fault": selectedFault, "fault_picture": faultPicture, "more": more };

	sendData(formData)
	.then(result => 
	{ 
	console.log('Result Save: ', result);
		if (result == 1)
		{
			window.location.reload(); 
		}
		else
		{
			alert('There was an error saving the results');
		}
	});
}

function closeFaultPicker()
{
	const fault_picker = document.getElementById('fault_picker');
	fault_picker.style.display = 'none';
}	

// Confirm modal functions
function showCustomConfirm() 
{
	document.getElementById('customConfirmModal').style.display = 'block';
}

function handleYes() 
{
	console.log('MORE');
	document.getElementById('customConfirmModal').style.display = 'none';
	saveIssue(1);
}

function handleNo() 
{
	console.log('NO MORE');
	document.getElementById('customConfirmModal').style.display = 'none';
	saveIssue(0);
}

// Close the modal when clicking outside of it
window.onclick = function(event) {
	const modal = document.getElementById('customConfirmModal');
	if (event.target == modal) {
	modal.style.display = 'none';
	}
}

async function uploadPicture() 
{
    const fileInput = document.getElementById('fault_picture');
    const file = fileInput.files[0];
    if (!file) {
        alert('Please select a file to upload.');
        return;
    }
	const vehicleSerial = document.getElementById('vehicle_serial').value;
    const formData = new FormData();
    formData.append('vehicle_serial', vehicleSerial);
    formData.append('fault_picture', file);

    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('upload_status').innerText = 'Upload successful!';
        } else {
            document.getElementById('upload_status').innerText = 'Upload failed: ' + data.message;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('upload_status').innerText = 'Upload failed: ' + error.message;
    });
}

async function sendData(formData) 
{
	console.log('Send data: ', formData);
	const phpUrl = 'http://localhost/icdev/manager_checklist/manager_checklist_modal.php';
	// const phpUrl = 'http://192.168.10.239/move/manager_checklist/manager_checklist_modal.php';
	const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
	const result = await response.text();
	
	return result;
}
</script>