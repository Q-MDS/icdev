<?php
/*ob_start();
require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");
require_once ("../php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };*/

$ajax_data = file_get_contents("php://input");
$json_data = json_decode($ajax_data);

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

function no_issues($vc_id)
{
	global $conn;

	$conn = oci_conn();

	// Need the current date plus 30 days: 1729548000
	$today = date('Y-m-d');
	$today = strtotime($today);
	$next_check_date = strtotime("+30 days", $today);

	try 
	{
		$sql = "UPDATE vehicle_checklist SET WORK_DATE = $next_check_date WHERE id = $vc_id";
		$cursor = oci_parse($conn, $sql);
		oci_execute($cursor);
		oci_free_statement($cursor);
		
		$result = '1';
	} 
	catch (Exception $e) 
	{
		$result = '0';
	}

	oci_close($conn);

	echo $result;

	// ORA
	/*global $conn;
	$cursor = ora_open($conn);

	$now = strtotime("now");

	$sql = "UPDATE move_tech_bulletins_read SET mtr_status = 100, mtr_date_updated = $now  WHERE mtr_id = $mtr_id";
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	ora_close($cursor);*/
}

function fetch_faults()
{
	global $conn;

	$conn = oci_conn();

	// $sql = "SELECT TFC_ID, TFC_REF_CATEGORY, TFC_NAME FROM TECHNICAL_FAULTS_CATEGORY WHERE TFC_IS_DELETED != 1 ORDER BY TFC_ID FETCH FIRST 1050 ROWS ONLY";
	$sql = "SELECT TFC_ID, TFC_REF_CATEGORY, TFC_NAME FROM TECHNICAL_FAULTS_CATEGORY WHERE TFC_IS_DELETED = 0 AND TFC_IS_OTHER = 0 ORDER BY TFC_ID";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	$fetch_faults = array();

	while ($row = oci_fetch_assoc($cursor)) 
	{
		$fetch_faults[] = $row;
	}

	oci_free_statement($cursor);
	oci_close($conn);

	echo json_encode($fetch_faults);
}

function save_fault($vc_id, $vehicle_serial, $fault_description, $fault, $fault_picture, $more, $full_fault)
{
	global $conn;

	// HARD CODED **** REMOVE ****
	// $user_id = 123;
	// $REMOTE_USER = getenv(“REMOTE_USER”); 
	$REMOTE_USER = 123; 
	$now = strtotime("now");
	
	$insert_id = 999;
	
	$conn = oci_conn();

	// Add record to vehicle_checklist_detail
	// $sql = "INSERT INTO VEHICLE_CHECKLIST_DETAIL (ID, VEHICLE_CHECKLIST_ID, CHECK_BY_ID, CHECK_DATE, FAULT_ID, FAULT_FULL) VALUES (VEHICLE_CHECKLIST_DETAIL_ID_SEQ.NEXTVAL, $vc_id, $REMOTE_USER, $now, '$fault', '$full_fault')";
	
	// $cursor = oci_parse($conn, $sql);
	// oci_execute($cursor);
	
	//$reported_date = '23/OCT/24';
	$reported_date = date('d/M/y', $now);

	// Add record to move_jobcarditems
	// $sql = "INSERT INTO 
	// 	MOVE_JOBCARDITEMS (ITEMSERIAL, JOBCARDSERIAL, UNITSERIAL, REPORTEDWHO, REPORTEDDATE, FAULTCLASS, FAULTDESC, FAULTPICTURE, TYPE, FAULTVALID, STATUSENGINEER, REPORTCOMMENTS, FAULT_CATEGORY) 
	// 	VALUES 
	// 	(MOVE_ITEMS.nextval, 0, $vehicle_serial, '$REMOTE_USER', '$reported_date', 14616, '$fault_description', '$fault_picture', '1', 'N', 'Z', '', $fault)
	// ";
	$sql = "INSERT INTO 
        MOVE_JOBCARDITEMS (ITEMSERIAL, JOBCARDSERIAL, UNITSERIAL, REPORTEDWHO, REPORTEDDATE, FAULTCLASS, FAULTDESC, FAULTPICTURE, TYPE, FAULTVALID, STATUSENGINEER, REPORTCOMMENTS, FAULT_CATEGORY) 
        VALUES 
        (MOVE_ITEMS.nextval, 0, :vehicle_serial, :remote_user, TO_DATE(:reported_date, 'YYYY-MM-DD HH24:MI:SS'), 14616, :fault_description, :fault_picture, '1', 'N', 'Z', '', :fault)
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
	$sql = "INSERT INTO VEHICLE_CHECKLIST_DETAIL (ID, VEHICLE_CHECKLIST_ID, CHECK_BY_ID, CHECK_DATE, FAULT_ID, FAULT_FULL, ITEMSERIAL) VALUES (VEHICLE_CHECKLIST_DETAIL_ID_SEQ.NEXTVAL, $vc_id, $REMOTE_USER, $now, '$fault', '$full_fault', $itemserial)";
	
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	// $REMOTE_USER = getenv(“REMOTE_USER”); 
	// INSERT INTO MOVE_JOBCARDITEMS ( itemserial, jobcardserial, unitserial, 
	// reportedwho, reporteddate, faultclass, faultdesc, faultpicture, type, 
	// faultvalid, statusengineer,reportcomments, fault_category ) VALUES( 
	// MOVE_ITEMS.nextval, 0, '$vehicleserial', '$REMOTE_USER',CURRENT_TIMESTAMP, 
	// 14616, '$faultdescription', 'N', '1', 'N', 'Z', '', $fault ) 
	// if there is a picture, set faultpicture to “Y” and upload the file to 
	// /usr/local/www/pages/move/uploads/$itemserial 
	// ($faultdescription will be any text entered describing the issue,  $fault is the 
	// tfc_id from technical_faults_category 

	// Update vehicle_checklist: work_date + 30
	if ($more == 0)
	{
		no_issues($vc_id);
	} 
	else 
	{
		// echo '1';
		echo $itemserial;
	}

	oci_close($conn);
}
