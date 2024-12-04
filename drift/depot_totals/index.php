<?php
$depot = '';
// if (isset($_POST['depot'])) 
// {
// 	$depot = $_POST['depot'];
// 	getRecord();
// }



// $depot = 'CA';
// $current_month = date('Ym01');
$record = array();

if (isset($_GET['action']))
{
	$action = $_GET['action'];

	if ($action == 'update')
	{
		$ajax_data = file_get_contents("php://input");
		$json_data = json_decode($ajax_data);
		$_check_gets_return = true;

		$depot_totals_id = $json_data->depot_totals_id;
		$month = $json_data->month;
		$data_type = $json_data->data_type;
		$data_value = $json_data->data_value;

		updateDepotTotals($depot_totals_id, $month, $data_type, $data_value);
		exit;
	}

	if ($action == 'select_depot')
	{
		global $depot;

		$ajax_data = file_get_contents("php://input");
		$json_data = json_decode($ajax_data);
		$_check_gets_return = true;

		$depot = $json_data->depot;

		exit;
	}

	if ($action == "select_month")
	{
		global $current_month;

		$ajax_data = file_get_contents("php://input");
		$json_data = json_decode($ajax_data);
		$_check_gets_return = true;
		
		$month = $json_data->month;
		$depot = $json_data->depot;

		$record = getRecord($month, $depot);

		echo json_encode($record);

		exit;
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

function getRecord($month, $depot)
{
	// global $record, $depot, $current_month;
	global $record;

	$conn = oci_conn();

	$sql = "SELECT * FROM DRIFT_DEPOT_TOTALS WHERE DEPOT = '$depot' AND FOR_MONTH = $month";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$record = $row;
	}

	if (count($record) == 0)
	{
		$insert_id = addRecord($depot, $month);

		$record = array('ID' => $insert_id, 'DEPOT' => $depot, 'FOR_MONTH' => $month, 'TRAINING_TRIPS' => 0, 'OLD_CONTRACTS' => 0, 'COMPLETED_TRAINING' => 0, 'DISMISSED' => 0, 'RESIGNED' => 0, 'CLASS_TRAINING' => 0, 'INTERVIEW' => 0, 'CVS' => 0, 'K53' => 0);
	}

	oci_close($conn);

	return $record;
}

function addRecord($depot, $current_month)
{
	// OCI
	$conn = oci_conn();

	$sql = "
	INSERT INTO 
		DRIFT_DEPOT_TOTALS (ID, DEPOT, FOR_MONTH, TRAINING_TRIPS, OLD_CONTRACTS, COMPLETED_TRAINING, DISMISSED, RESIGNED, CLASS_TRAINING, INTERVIEW, CVS, K53) 
	VALUES 
		(DRIFT_DEPOT_TOTALS_ID_SEQ.NEXTVAL, :depot, :current_month, 0, 0, 0, 0, 0, 0, 0, 0, 0)
	RETURNING ID INTO :insert_id
		";
	$cursor = oci_parse($conn, $sql);

	oci_bind_by_name($cursor, ':depot', $depot);
	oci_bind_by_name($cursor, ':current_month', $current_month);

	// Bind the output variable
	oci_bind_by_name($cursor, ':insert_id', $insert_id, -1, SQLT_INT);

	oci_execute($cursor);

	oci_close($conn);

	return $insert_id;
}

function updateDepotTotals($depot_totals_id, $month, $data_type, $value)
{
	$conn = oci_conn();

	$sql = "UPDATE DRIFT_DEPOT_TOTALS SET $data_type = $value WHERE ID = $depot_totals_id";

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
	<title>Depot Totals</title>
	<style>
		* {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Ensure html and body take up the full viewport height */
        html, body {
            height: 100%;
            width: 100%;
            font-family: Arial, sans-serif;
			font-size: 14px;
			padding: 20px;
        }

		.form_row {
			display: flex;
			flex-direction: row;
			align-items: center;
			column-gap: 10px;
		}
		.title {
			font-size: 1em;
			width: 200px;
		}
		.form_input {
			padding: 5px;
			border-radius: 5px;
		}
	</style>
</head>
<body>
	<div>
		<div style="display: flex; flex-direction: row; align-items: center; column-gap: 10px; margin-bottom: 10px">
				<div style="width: 100px">Select depot</div>
					<select name="depot" id="depot" value="PE" style="width: 100px; padding: 5px; border-radius: 5px;" onchange="selectDepot()">
						<option value="">Select...</option>
						<option value="BLM">BLM</option>
						<option value="CA">CA</option>
						<option value="CBS">CBS</option>
						<option value="DBN">DBN</option>
						<option value="GAB">GAB</option>
						<option value="MAP">MAP</option>
						<option value="MTH">MTH</option>
						<option value="PE">PE</option>
						<option value="PTA">PTA</option>
						<option value="UPT">UPT</option>
						<option value="WHK">WHK</option>
					</select>
				</div>
			</div>
		<div style="display: flex; flex-direction: row; align-items: center; column-gap: 10px;">
			<div style="width: 100px">Select date</div>
			<?php
			$back = 24;
			$forward = 12;
			// $now = date('Y-m-01');
			
			echo '<div>'; 
				echo '<select name="month" id="month" style="width: 100px; padding: 5px; border-radius: 5px;" onchange="selectMonth()">';
				echo '<option value="">Select...</option>';
				for ($i = -$back; $i <= $forward; $i++) 
				{
					$date = date('Ym01', strtotime("$i months"));
					$label_date = date('Y-m-01', strtotime("$i months"));
					echo '<option value="' . $date . '">' . $label_date . '</option>';
					// if ($date == $current_month)
					// {
					// 	echo '<option value="' . $date . '" selected>' . $label_date . '</option>';
					// }
					// else
					// {
						
					// }
				}
				echo '</select>';
			echo '</div>';
			?>
		</div>
		
		<div id="form" style="display: none">
			<div style="margin-top: 20px; margin-bottom: 15px; font-weight: bold">
				Please enter totals in the form below
				<input type="text" id="depot_totals_id" value="" style="display: none" />
			</div>
			<div style="display: flex; flex-direction: column; row-gap: 10px;">
				<div class="form_row">
					<div class="title">On Training Trips</div>
					<div><input type="number" id="training_trips" class="form_input" value="" onchange="update(this.id)" /></div>
				</div>
				<div class="form_row">
					<div class="title">Old Contracts</div>
					<div><input type="number" id="old_contracts" class="form_input" value="" onchange="update(this.id)" /></div>
				</div>
				<div class="form_row">
					<div class="title">Completed Training/Passed</div>
					<div><input type="number" id="completed_training" class="form_input" value="" onchange="update(this.id)" /></div>
				</div>
				<div class="form_row">
					<div class="title">Dismissed</div>
					<div><input type="number" id="dismissed" class="form_input" value="" onchange="update(this.id)" /></div>
				</div>
				<div class="form_row">
					<div class="title">Resigned</div>
					<div><input type="number" id="resigned" class="form_input" value="" onchange="update(this.id)" /></div>
				</div>
				<div class="form_row">
					<div class="title">Class Training</div>
					<div><input type="number" id="class_training" class="form_input" value="" onchange="update(this.id)" /></div>
				</div>
				<div class="form_row">
					<div class="title">Interview Process</div>
					<div><input type="number" id="interview" class="form_input" value="" onchange="update(this.id)" /></div>
				</div>
				<div class="form_row">
					<div class="title">CVs In Hand</div>
					<div><input type="number" id="cvs" class="form_input" value="" onchange="update(this.id)" /></div>
				</div>
				<div class="form_row">
					<div class="title">K53</div>
					<div><input type="number" id="k53" class="form_input" value="" onchange="update(this.id)" /></div>
				</div>
			</div>
		</div>
		
	</div>
	<script>
		const baseUrl = window.location.protocol + "//" + window.location.hostname + "/icdev/drift/";

		function selectDepot()
		{
			const form = document.getElementById('form');
			const month = document.getElementById('month');
			form.style.display = 'none';
			month.value = '';
		}

		function selectMonth()
		{
			const form = document.getElementById('form');
			const month = document.getElementById('month').value;
			const depot = document.getElementById('depot').value;

			const formData = { "month": month, "depot": depot };

			const result = sendData(formData, "select_month")
			.then(result => 
			{
				const record = JSON.parse(result);
				
				document.getElementById('depot_totals_id').value = record.ID;
				document.getElementById('training_trips').value = record.TRAINING_TRIPS;
				document.getElementById('old_contracts').value = record.OLD_CONTRACTS;
				document.getElementById('completed_training').value = record.COMPLETED_TRAINING;
				document.getElementById('dismissed').value = record.DISMISSED;
				document.getElementById('resigned').value = record.RESIGNED;
				document.getElementById('class_training').value = record.CLASS_TRAINING;
				document.getElementById('interview').value = record.INTERVIEW;
				document.getElementById('cvs').value = record.CVS;
				document.getElementById('k53').value = record.K53;
			});

			form.style.display = 'block';
		}

		function update(dataType)
		{
			const depotTotalsId = document.getElementById('depot_totals_id').value;
			const month = document.getElementById('month').value;
			const dataValue = document.getElementById(dataType).value;

			const formData = { "depot_totals_id" : depotTotalsId, "month": month, "data_type": dataType, "data_value": dataValue };

			const result = sendData(formData, "update")
			.then(result => 
			{
				console.log('Result: ', result);
				// window.location.reload(); 
			});
		}

		async function sendData(formData, action) 
		{
			const phpUrl = baseUrl + 'depot_totals/index.php?action=' + action;

			const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
			const result = await response.text();

			return result;
		}
	</script>
</body>
</html>