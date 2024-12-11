<?php
$depot = '';
$user_serial = 'Harry';
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

		$dirty = $json_data->dirty;
		$depot_totals_id = $json_data->depot_totals_id;
		$month = $json_data->month;
		$training_trips = $json_data->training_trips;
		$old_contracts = $json_data->old_contracts;
		$completed_training = $json_data->completed_training;
		$dismissed = $json_data->dismissed;
		$resigned = $json_data->resigned;
		$class_training = $json_data->class_training;
		$interview = $json_data->interview;
		$cvs = $json_data->cvs;
		$k53 = $json_data->k53;

		updateDepotTotals($dirty, $depot_totals_id, $month, $depot, $training_trips, $old_contracts, $completed_training, $dismissed, $resigned, $class_training, $interview, $cvs, $k53);
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

function getCurrent($id)
{
	$conn = oci_conn();

	$sql = "SELECT * FROM DRIFT_DEPOT_TOTALS WHERE ID = $id";
		
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$record = $row;
	}

	oci_close($conn);

	return $record;
}

function addHistory($depot_totals_id, $previous_data)
{
	global $user_serial;

	$conn = oci_conn();

	$now = time();

	$sql = "
	INSERT INTO 
		DRIFT_DEPOT_TOTALS_HISTORY (LINK_ID, PREVIOUS_DATA, UDPATE_DATE, UPDATE_BY) 
	VALUES 
		($depot_totals_id, :previous_data, :update_date, :user_serial)
	";
	$cursor = oci_parse($conn, $sql);

	oci_bind_by_name($cursor, ':previous_data', $previous_data);
	oci_bind_by_name($cursor, ':update_date', $now);
	oci_bind_by_name($cursor, ':user_serial', $user_serial);

	oci_execute($cursor);

	oci_close($conn);
}

function updateDepotTotals($dirty, $depot_totals_id, $month, $depot, $training_trips, $old_contracts, $completed_training, $dismissed, $resigned, $class_training, $interview, $cvs, $k53)
{
	// if dirty > 0
	// Get record
	// Add record to history
	// Update record
	if ($dirty > 0)
	{
		$current_record = getCurrent($depot_totals_id);
		$previous_data = json_encode($current_record);
		addHistory($depot_totals_id, $previous_data);
		print_r($current_record);
	}

	$conn = oci_conn();

	$sql = "UPDATE DRIFT_DEPOT_TOTALS SET TRAINING_TRIPS = $training_trips, OLD_CONTRACTS = $old_contracts, COMPLETED_TRAINING = $completed_training, DISMISSED = $dismissed, RESIGNED = $resigned, CLASS_TRAINING = $class_training, INTERVIEW = $interview, CVS = $cvs, K53 = $k53 WHERE ID = $depot_totals_id";

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
			<div style="margin-top: 20px; margin-bottom: 15px; padding-bottom: 15px; font-weight: bold; border-bottom: 1px solid #a9a9a9">
				Please enter totals in the form below
				<input type="text" id="depot_totals_id" value="" style="display: none" />
			</div>
			<div style="display: flex; flex-direction: column; row-gap: 10px;">
				<div class="form_row">
					<div class="title">On Training Trips</div>
					<div><input type="number" id="training_trips" class="form_input" value="" onchange="validate(this.id)" /></div>
					<div style="display: none"><input type="number" id="training_trips_curr" value="" /></div>
					<div id="training_trips_chk" style="display: none"><img src="warning.svg" id="training_trips_img"></div>
				</div>
				<div class="form_row">
					<div class="title">Old Contracts</div>
					<div><input type="number" id="old_contracts" class="form_input" value="" onchange="validate(this.id)" /></div>
					<div style="display: none"><input type="number" id="old_contracts_curr" value="" /></div>
					<div id="old_contracts_chk" style="display: none"><img src="warning.svg" id="old_contracts_img"></div>
				</div>
				<div class="form_row">
					<div class="title">Completed Training/Passed</div>
					<div><input type="number" id="completed_training" class="form_input" value="" onchange="validate(this.id)" /></div>
					<div style="display: none"><input type="number" id="completed_training_curr" value="" /></div>
					<div id="completed_training_chk" style="display: none"><img src="warning.svg" id="completed_training_img"></div>
				</div>
				<div class="form_row">
					<div class="title">Dismissed</div>
					<div><input type="number" id="dismissed" class="form_input" value="" onchange="validate(this.id)" /></div>
					<div style="display: none"><input type="number" id="dismissed_curr" value="" /></div>
					<div id="dismissed_chk" style="display: none"><img src="warning.svg" id="dismissed_img"></div>
				</div>
				<div class="form_row">
					<div class="title">Resigned</div>
					<div><input type="number" id="resigned" class="form_input" value="" onchange="validate(this.id)" /></div>
					<div style="display: none"><input type="number" id="resigned_curr" value="" /></div>
					<div id="resigned_chk" style="display: none"><img src="warning.svg" id="resigned_img"></div>
				</div>
				<div class="form_row">
					<div class="title">Class Training</div>
					<div><input type="number" id="class_training" class="form_input" value="" onchange="validate(this.id)" /></div>
					<div style="display: none"><input type="number" id="class_training_curr" value="" /></div>
					<div id="class_training_chk" style="display: none"><img src="warning.svg" id="class_training_img"></div>
				</div>
				<div class="form_row">
					<div class="title">Interview Process</div>
					<div><input type="number" id="interview" class="form_input" value="" onchange="validate(this.id)" /></div>
					<div style="display: none"><input type="number" id="interview_curr" value="" /></div>
					<div id="interview_chk" style="display: none"><img src="warning.svg" id="interview_img"></div>
				</div>
				<div class="form_row">
					<div class="title">CVs In Hand</div>
					<div><input type="number" id="cvs" class="form_input" value="" onchange="validate(this.id)" /></div>
					<div style="display: none"><input type="number" id="cvs_curr" value="" /></div>
					<div id="cvs_chk" style="display: none"><img src="warning.svg" id="cvs_img"></div>
				</div>
				<div class="form_row">
					<div class="title">K53</div>
					<div><input type="number" id="k53" class="form_input" value="" onchange="validate(this.id)" /></div>
					<div style="display: none"><input type="number" id="k53_curr" value="" /></div>
					<div id="k53_chk" style="display: none"><img src="warning.svg" id="k53_img"></div>
				</div>
				<div class="form_row" style="border-top: 1px solid #a9a9a9; padding-top: 15px;">
					<div style="display: flex; align-items: center; justify-content: center; background-color: #EC7A31; padding: 7px 10px; color: white; border-radius: 5px; border: 0; width: 60px; cursor: pointer" onclick="save()">SAVE</div>
				</div>
			</div>
		</div>


		
	</div>
	<script>
		const baseUrl = window.location.protocol + "//" + window.location.hostname + "/icdev/drift/";

		let isDirty = false;

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
				document.getElementById('training_trips_curr').value = record.TRAINING_TRIPS;
				document.getElementById('old_contracts').value = record.OLD_CONTRACTS;
				document.getElementById('old_contracts_curr').value = record.OLD_CONTRACTS;
				document.getElementById('completed_training').value = record.COMPLETED_TRAINING;
				document.getElementById('completed_training_curr').value = record.COMPLETED_TRAINING;
				document.getElementById('dismissed').value = record.DISMISSED;
				document.getElementById('dismissed_curr').value = record.DISMISSED;
				document.getElementById('resigned').value = record.RESIGNED;
				document.getElementById('resigned_curr').value = record.RESIGNED;
				document.getElementById('class_training').value = record.CLASS_TRAINING;
				document.getElementById('class_training_curr').value = record.CLASS_TRAINING;
				document.getElementById('interview').value = record.INTERVIEW;
				document.getElementById('interview_curr').value = record.INTERVIEW;
				document.getElementById('cvs').value = record.CVS;
				document.getElementById('cvs_curr').value = record.CVS;
				document.getElementById('k53').value = record.K53;
				document.getElementById('k53_curr').value = record.K53;
			});

			form.style.display = 'block';
		}

		function validate(id)
		{
			const trainingTrips = document.getElementById('training_trips').value;
			const trainingTripsCurr = document.getElementById('training_trips_curr').value;
			const oldContracts = document.getElementById('old_contracts').value;
			const oldContractsCurr = document.getElementById('old_contracts_curr').value;
			const completedTraining = document.getElementById('completed_training').value;
			const completedTrainingCurr = document.getElementById('completed_training_curr').value;
			const dismissed = document.getElementById('dismissed').value;
			const dismissedCurr = document.getElementById('dismissed_curr').value;
			const resigned = document.getElementById('resigned').value;
			const resignedCurr = document.getElementById('resigned_curr').value;
			const classTraining = document.getElementById('class_training').value;
			const classTrainingCurr = document.getElementById('class_training_curr').value;
			const interview = document.getElementById('interview').value;
			const interviewCurr = document.getElementById('interview_curr').value;
			const cvs = document.getElementById('cvs').value;
			const cvsCurr = document.getElementById('cvs_curr').value;
			const k53 = document.getElementById('k53').value;
			const k53Curr = document.getElementById('k53_curr').value;

			if (trainingTrips != trainingTripsCurr)
			{
				document.getElementById('training_trips_chk').style.display = 'block';
			}
			else
			{
				document.getElementById('training_trips_chk').style.display = 'none';
			}
			if (oldContracts != oldContractsCurr)
			{
				document.getElementById('old_contracts_chk').style.display = 'block';
			}
			else
			{
				document.getElementById('old_contracts_chk').style.display = 'none';
			}
			if (completedTraining != completedTrainingCurr)
			{
				document.getElementById('completed_training_chk').style.display = 'block';
			}
			else
			{
				document.getElementById('completed_training_chk').style.display = 'none';
			}
			if (dismissed != dismissedCurr)
			{
				document.getElementById('dismissed_chk').style.display = 'block';
			}
			else
			{
				document.getElementById('dismissed_chk').style.display = 'none';
			}
			if (resigned != resignedCurr)
			{
				document.getElementById('resigned_chk').style.display = 'block';
			}
			else
			{
				document.getElementById('resigned_chk').style.display = 'none';
			}
			if (classTraining != classTrainingCurr)
			{
				document.getElementById('class_training_chk').style.display = 'block';
			}
			else
			{
				document.getElementById('class_training_chk').style.display = 'none';
			}
			if (interview != interviewCurr)
			{
				document.getElementById('interview_chk').style.display = 'block';
			}
			else
			{
				document.getElementById('interview_chk').style.display = 'none';
			}
			if (cvs != cvsCurr)
			{
				document.getElementById('cvs_chk').style.display = 'block';
			}
			else
			{
				document.getElementById('cvs_chk').style.display = 'none';
			}
			if (k53 != k53Curr)
			{
				document.getElementById('k53_chk').style.display = 'block';
			}
			else
			{
				document.getElementById('k53_chk').style.display = 'none';
			}
		}

		function save()
		{
			let ctr = 0;
			isDirty = false;
			
			const depotTotalsId = document.getElementById('depot_totals_id').value;
			const month = document.getElementById('month').value;
			const depot = document.getElementById('depot').value;

			const training_trips = document.getElementById('training_trips').value;
			const training_trips_curr = document.getElementById('training_trips_curr').value;
			if (training_trips != training_trips_curr) { ctr++; }
			const old_contracts = document.getElementById('old_contracts').value;
			const old_contracts_curr = document.getElementById('old_contracts_curr').value;
			if (old_contracts != old_contracts_curr) { ctr++; }
			const completed_training = document.getElementById('completed_training').value;
			const completed_training_curr = document.getElementById('completed_training_curr').value;
			if (completed_training != completed_training_curr) { ctr++; }
			const dismissed = document.getElementById('dismissed').value;
			const dismissed_curr = document.getElementById('dismissed_curr').value;
			if (dismissed != dismissed_curr) { ctr++; }
			const resigned = document.getElementById('resigned').value;
			const resigned_curr = document.getElementById('resigned_curr').value;
			if (resigned != resigned_curr) { ctr++; }
			const class_training = document.getElementById('class_training').value;
			const class_training_curr = document.getElementById('class_training_curr').value;
			if (class_training != class_training_curr) { ctr++; }
			const interview = document.getElementById('interview').value;
			const interview_curr = document.getElementById('interview_curr').value;
			if (interview != interview_curr) { ctr++; }
			const cvs = document.getElementById('cvs').value;
			const cvs_curr = document.getElementById('cvs_curr').value;
			if (cvs != cvs_curr) { ctr++; }
			const k53 = document.getElementById('k53').value;
			const k53_curr = document.getElementById('k53_curr').value;
			if (k53 != k53_curr) { ctr++; }

			const formData = { "dirty": ctr, "depot_totals_id" : depotTotalsId, "month": month, "depot": depot, "training_trips": training_trips, "old_contracts": old_contracts, "completed_training": completed_training, "dismissed": dismissed, "resigned": resigned, "class_training": class_training, "interview": interview, "cvs": cvs, "k53": k53 };	

			const result = sendData(formData, "update")
			.then(result => 
			{
				console.log('Result: ', result);
				window.location.reload(); 
			});

			console.log('Save: ', ctr);
		}

		function checkIfDirty() 
		{
			let ctr = 0;
			const training_trips = document.getElementById('training_trips').value;
			const training_trips_curr = document.getElementById('training_trips_curr').value;
			if (training_trips != training_trips_curr) { ctr++; }
			const old_contracts = document.getElementById('old_contracts').value;
			const old_contracts_curr = document.getElementById('old_contracts_curr').value;
			if (old_contracts != old_contracts_curr) { ctr++; }
			const completed_training = document.getElementById('completed_training').value;
			const completed_training_curr = document.getElementById('completed_training_curr').value;
			if (completed_training != completed_training_curr) { ctr++; }
			const dismissed = document.getElementById('dismissed').value;
			const dismissed_curr = document.getElementById('dismissed_curr').value;
			if (dismissed != dismissed_curr) { ctr++; }
			const resigned = document.getElementById('resigned').value;
			const resigned_curr = document.getElementById('resigned_curr').value;
			if (resigned != resigned_curr) { ctr++; }
			const class_training = document.getElementById('class_training').value;
			const class_training_curr = document.getElementById('class_training_curr').value;
			if (class_training != class_training_curr) { ctr++; }
			const interview = document.getElementById('interview').value;
			const interview_curr = document.getElementById('interview_curr').value;
			if (interview != interview_curr) { ctr++; }
			const cvs = document.getElementById('cvs').value;
			const cvs_curr = document.getElementById('cvs_curr').value;
			if (cvs != cvs_curr) { ctr++; }
			const k53 = document.getElementById('k53').value;
			const k53_curr = document.getElementById('k53_curr').value;
			if (k53 != k53_curr) { ctr++; }

			isDirty = ctr > 0;
		}

		document.querySelectorAll('input').forEach(input => {
			input.addEventListener('change', checkIfDirty);
		});

		window.addEventListener('beforeunload', function (e) 
		{
			if (isDirty) {
				const confirmationMessage = 'You have unsaved changes. Are you sure you want to leave?';
				e.returnValue = confirmationMessage; // Standard for most browsers
				return confirmationMessage; // For some older browsers
			}
		});

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