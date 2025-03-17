<?php
ob_start();
require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");
require_once ("../php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

$ajax_data = file_get_contents("php://input");
$json_data = json_decode($ajax_data);
$_check_gets_return = true; // dont show oracle gets at the end, which breaks JSON

$action = $json_data->action;

switch ($action)
{
	case 0:
		$vc_id = $json_data->vc_id;
		no_issues($vc_id);
	break;
	case 1:
		$mtr_id = $json_data->mtr_id;
		$mtr_status = $json_data->mtr_status;
		did_not_read($mtr_id, $mtr_status);
	break;
	case 2:
		fetch_faults();
	break;
	case 3:
		$vc_id = $json_data->vc_id;
		$vehicle_serial = $json_data->vehicle_serial;
		$fault_description = $json_data->fault_description;
		$fault = $json_data->fault;
		$fault_picture = $json_data->fault_picture;
		$more = $json_data->more;
		$full_fault = $json_data->full_fault;
		save_fault($vc_id, $vehicle_serial, $fault_description, $fault, $fault_picture, $more, $full_fault);
	break;
}

function no_issues($vc_id)
{
	global $conn;

	$cursor = ora_open($conn);

	// Need the current date plus 30 days: 1729548000
	$today = date('Y-m-d');
	$today = strtotime($today);
	$next_check_date = strtotime("+6 months", $today);

	try 
	{
		$sql = "UPDATE vehicle_checklist SET WORK_DATE = $next_check_date WHERE id = $vc_id";
		ora_parse($cursor, $sql);
		ora_exec($cursor);
		
		$result = '1';
	} 
	catch (Exception $e) 
	{
		$result = '0';
	}

	ora_close($cursor);

	echo $result;
}

function fetch_faults()
{
	global $conn;

	$cursor = ora_open($conn);

	$sql = "SELECT TFC_ID, TFC_REF_CATEGORY, TFC_NAME FROM TECHNICAL_FAULTS_CATEGORY WHERE TFC_IS_DELETED = 0 AND TFC_IS_OTHER = 0 ORDER BY TFC_ID";
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	$fetch_faults = array();

	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC))  
	{
		$fetch_faults[] = $row;
	}

	ora_close($cursor);

	echo json_encode($fetch_faults);
}

function save_fault($vc_id, $vehicle_serial, $fault_description, $fault, $fault_picture, $more, $full_fault)
{
	global $conn;

	// HARD CODED **** REMOVE ****
	// $user_id = 123;
	$REMOTE_USER_SERIAL = getuserserial();
	$REMOTE_USER = getenv("REMOTE_USER");
	$now = strtotime("now");
	
	//$insert_id = 999;
	
	// $conn = oci_conn();

	$reported_date = date('d/M/y', $now);

	$sql = "INSERT INTO 
        MOVE_JOBCARDITEMS (ITEMSERIAL, JOBCARDSERIAL, UNITSERIAL, REPORTEDWHO, REPORTEDDATE, FAULTCLASS, FAULTDESC, FAULTPICTURE, TYPE, FAULTVALID, STATUSENGINEER, REPORTCOMMENTS, FAULT_CATEGORY) 
        VALUES 
        (MOVE_ITEMS.nextval, 0, :vehicle_serial, :remote_user, CURRENT_TIMESTAMP, 14616, :fault_description, :fault_picture, '1', 'N', 'Z', '', :fault)
        RETURNING ITEMSERIAL INTO :itemserial";

	$cursor = oci_parse($conn, $sql);

	oci_bind_by_name($cursor, ':vehicle_serial', $vehicle_serial);
	oci_bind_by_name($cursor, ':remote_user', $REMOTE_USER);
	oci_bind_by_name($cursor, ':reported_date', $reported_date);
	oci_bind_by_name($cursor, ':fault_description', $fault_description);
	oci_bind_by_name($cursor, ':fault_picture', $fault_picture);
	oci_bind_by_name($cursor, ':fault', $fault);

	// Bind the output variable
	oci_bind_by_name($cursor, ':itemserial', $itemserial, -1, SQLT_INT);

	oci_execute($cursor);

	// Add record to vehicle_checklist_detail
	$now = time();
	try{
		$sql = "INSERT INTO VEHICLE_CHECKLIST_DETAIL (ID, VEHICLE_CHECKLIST_ID, CHECK_BY_ID, CHECK_DATE, FAULT_ID, FAULT_FULL, ITEMSERIAL) VALUES (VEHICLE_CHECKLIST_DETAIL_ID_SEQ.NEXTVAL, $vc_id, $REMOTE_USER_SERIAL, $now, '$fault', '$full_fault', $itemserial)";
		
		$cursor = oci_parse($conn, $sql);
		oci_execute($cursor);
		
	} catch (Exception $e) {
		echo "Error: " . $e->getMessage();
	}

	// Update vehicle_checklist: work_date + 30
	if ($more == 0)
	{
		no_issues($vc_id);
	} 
	else 
	{
		echo '1';
		// echo "Boo: " . $itemserial;
	}

	oci_close($conn);
}
