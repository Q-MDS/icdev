<?php
// ORA

/**
 * 
 * Intercape inclues needed for standalone pages
 * 
 */
ob_start();
require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");
require_once ("../php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

/** 
 * 
 * Load from table into data grid 
 * 
 * */
global $conn;
	
$cursor = ora_open($conn);

$sql = "SELECT * FROM move_tech_bulletins_read WHERE mtr_ref_hc_people = '$user_id' AND mtr_date_start = '$monday'";
	
ora_parse($cursor, $sql);
ora_exec($cursor);

if (ora_fetch_into($cursor, $mtr_row, ORA_FETCHINTO_ASSOC)) 
	{
		$mtr_id = $mtr_row['MTR_ID'];
		$bulletin_id = $mtr_row['MTR_REF_MOVE_TECH_BULLETINS'];
		$mtr_status = $mtr_row['MTR_STATUS'];
		$mtr_date_updated = $mtr_row['MTR_DATE_UPDATED'];
		
		/** Use a second cursor */
		$cur2 = ora_open($conn);
		$sql = "SELECT * FROM move_tech_bulletins WHERE mtb_id = $bulletin_id";
		ora_parse($cur2, $sql);
		ora_exec($cur2);
	
		if (ora_fetch_into($cur2, $mtb_row, ORA_FETCHINTO_ASSOC)) 
		{
			$bulletin_name = $mtb_row['MTB_NAME'];
			$bulletin_url = $mtb_row['MTB_URL'];
		}
		/** Close cursor here */
		ora_close($cur2);
	} 
	else 
	{
		// No record found: create one
		create_mtr($user_id);
	}
	ora_close($cursor);
?>
<div id="bulletin" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; border: 1px solid #000; padding: 10px 0px; column-gap: 0px">
	<div style="padding-left: 40px; padding-right: 40px;">
		<a href="board.htm" target="_blank">Bulletin Board</a>
		<input id="mtr_id" type="text" value="<?php echo $mtr_id; ?>" style="display: none" />
		<input id="mtr_status" type="text" value="<?php echo $mtr_status; ?>" style="display: none" />
	</div>
	<div>Please read the following bulletin:</div>
	<div id="bulletin_name" style="flex: 1"><a href="<?php echo $bulletin_url; ?>" target="_blank"><?php echo $bulletin_name; ?></a></div>
	<div style="padding-right: 10px">Revision:</div>
	<div style="padding-right: 10px"><?php echo $mtb_revision; ?></div>
	<div style="background-color: #f5f5f5; color: #000; border-radius: 5px; border: 1px solid #000; padding: 5px 20px; margin-right: 10px; cursor: pointer" onclick="didRead()">I have read the bulletin</div>
	<?php
	if ($mtr_status != 2)
	{
	?>
	<div style="background-color: #f5f5f5; color: #000; border-radius: 5px; border: 1px solid #000; padding: 5px 20px; margin-right: 10px; cursor: pointer" onclick="didNotRead()">I have not read the bulletin</div>
	<?php
	}
	?>
</div>


<?php
/** 
 * 
 * Add a record 
 * 
 * */
function create_mtr($user_id)
{
	global $conn, $user_id, $monday, $mtb_revision;

	$result = get_active_mtb();
	$mtb_id = $result['mtb_id'];
	$mtb_revision = $result['mtb_revision'];
	
	$cursor = ora_open($conn);

	$sql = "INSERT INTO move_tech_bulletins_read 
		(mtr_id, MTR_REF_MOVE_TECH_BULLETINS, mtr_revision, mtr_ref_hc_people, mtr_status, mtr_date_start) 
		VALUES ( MTBR_ID_SEQ.NEXTVAL, $mtb_id, $mtb_revision, $user_id, 0, '$monday'
	)";
	
	ora_parse($cursor, $sql);
	ora_exec($cursor);
	ora_close($cursor);
}

/** 
 * 
 * Simple fetch 
 * 
 * */
function simple_fetch()
{
	global $conn, $monday;

	$data = array();

	$cursor = ora_open($conn);

	$sql = "SELECT mtb_id, mtb_revision FROM move_tech_bulletins WHERE mtb_use_date = '$monday'";
	ora_parse($cursor, $sql);
	ora_exec($cursor);
	

	if (ora_fetch_into($cursor, $mtr_row, ORA_FETCHINTO_ASSOC)) 
	{
		$mtb_id = $mtr_row['MTB_ID'];
		$mtb_revision = $mtr_row['MTB_REVISION'];
	} 

	ora_close($cursor);

	$data= array('mtb_id' => $mtb_id, 'mtb_revision' => $mtb_revision);

	return $data;
}

/**
 * 
 * Update a record
 * 
 */
function did_read($mtr_id)
{
	global $conn;

	$cursor = ora_open($conn);

	$now = strtotime("now");

	$sql = "UPDATE move_tech_bulletins_read SET mtr_status = 100, mtr_date_updated = $now  WHERE mtr_id = $mtr_id";

	ora_parse($cursor, $sql);
	ora_exec($cursor);

	ora_close($cursor);
}

/** Both OCI and ORA sample */
function get_vehicles()
{
	// OCI
	$conn = oci_conn();

	// ORA
	global $conn;

	$sql = "SELECT * FROM vehicles ORDER BY serial FETCH FIRST 10 ROWS ONLY";
	
	// OCI
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);

	// ORA
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	// OCI
	while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
	{
		$serial = $row['SERIAL'];
		
		echo '<div>' . $serial . '</div>';
	}

	// ORA
	while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
	{
		$serial = $row['SERIAL'];
		
		echo '<div>' . $serial . '</div>';
	}

	// OCI
	oci_close($conn);

	// ORA
	ora_close($cursor);
}