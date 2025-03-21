<?php
ob_start();
require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");
require_once ("../php3/sec.inc");

if (!isset($conn)) {
	if (!open_oracle()) { Exit; };
	if (!AllowedAccess("")) { Exit; };
}

// Test mode only
// $test_user = $_GET['u'];
// $test_date = $_GET['d'];

// Global vars
$bulletin_name = 'none';
$bulletin_url = '';
$mtb_revision = '0';
$monday = start_date();
$next_mbr_id = 0;
// Test
// $user_id = $test_user;
// Live
$user_id = getuserserial();
$mbr_id = 0;
$mbr_status = 0;
$mbr_date_updated = 0;
$now = strtotime(date("Y-m-d H:i:s"));
$cycle = 12 * 60 * 60; // 12 hours
// $cycle = 20; // 12 seconds

load_banner();

function start_date()
{
	global $monday, $test_date;
	// Get date of current Monday
	$date = new DateTime();
	// Use below to test a new week
	// $date = new DateTime($test_date);
	$date->modify('this week monday');
	$monday = $date->format('Y-m-d');
	$monday = strtotime($monday);
	
	return $monday;
}

function check_user_group($user_id)
{
	global $conn;

	$data = array();

	$cursor = ora_open($conn);
	if ($cursor === false)
		exit;

	$sql = "SELECT ctm_ref_roster_group FROM clocking_roster_group_members A WHERE ctm_end_date is null AND ctm_ref_user = $user_id";
	// $sql = "SELECT ctm_ref_roster_group FROM clocking_roster_group_members A WHERE ctm_end_date is null AND ctm_ref_user = 2147478626";
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	while (ora_fetch_into($cursor, $mbr_row, ORA_FETCHINTO_ASSOC)) {
        $data[] = $mbr_row['CTM_REF_ROSTER_GROUP'];
    }

	ora_close($cursor);

	return $data;
}

function get_active_mtb()
{
	global $conn, $monday, $mtb_id;

	$data = array();

	$cursor = ora_open($conn);
	if ($cursor === false)
		exit;

	$sql = "SELECT mtb_id, mtb_revision FROM move_tech_bulletins WHERE mtb_use_date = '$monday'";
	ora_parse($cursor, $sql);
	ora_exec($cursor);
	

	if (ora_fetch_into($cursor, $mbr_row, ORA_FETCHINTO_ASSOC)) 
	{
		$mtb_id = $mbr_row['MTB_ID'];
		$mtb_revision = $mbr_row['MTB_REVISION'];
	} 
	else 
	{
		// No record found: create one: Need to set the next active bulletin
		$result = get_next_bulletin();
		$mtb_id = $result['mtb_id'];
		$mtb_revision = $result['mtb_revision'];
	}

	ora_close($cursor);

	$data= array('mtb_id' => $mtb_id, 'mtb_revision' => $mtb_revision);

	return $data;
}

function get_next_bulletin()
{
	global $conn, $monday;
	
	$data = array();

	$cursor = ora_open($conn);
	if ($cursor === false)
		exit;
	$cur2 = ora_open($conn);
	if ($cur2 === false)
		exit;

	$sql = "SELECT mtb_id, mtb_revision FROM move_tech_bulletins WHERE mtb_status != 0 AND mtb_use_date IS NULL OR TRIM(mtb_use_date) = '' ORDER BY mtb_status DESC, mtb_date DESC FETCH FIRST 1 ROWS ONLY";
	ora_parse($cursor, $sql);
	if (!ora_exec($cursor)) {
		exit;
	}

	if (ora_fetch_into($cursor, $mbr_row, ORA_FETCHINTO_ASSOC)) 
	{
		$mtb_id = $mbr_row['MTB_ID'];
		$mtb_revision = $mbr_row['MTB_REVISION'];

		$sql = "UPDATE move_tech_bulletins SET mtb_use_date = $monday WHERE mtb_id = $mtb_id";
		ora_parse($cur2, $sql);
		ora_exec($cur2);
	} 
	else 
	{
		// Cant find a bulletin to use: mtb_use_date all have valid timestamps
		// Options: 1. Start from beginning, 2. Start from the last bulletin, 3. Pick the latest priority bulletin
		// For now, we will start from the beginning
		
		$sql = "SELECT mtb_id, mtb_revision FROM move_tech_bulletins ORDER BY mtb_id ASC FETCH FIRST 1 ROWS ONLY";
		ora_parse($cur2, $sql);
		ora_exec($cur2);

		if (ora_fetch_into($cur2, $mbr_row, ORA_FETCHINTO_ASSOC)) 
		{
			$mtb_id = $mbr_row['MTB_ID'];
			$mtb_revision = $mbr_row['MTB_REVISION'];

			// Set all mtb_use_date to null
			$sql = "UPDATE move_tech_bulletins SET mtb_use_date = ''";
			ora_parse($cur2, $sql);
			ora_exec($cur2);

			// Set the new bulletin to active
			$sql = "UPDATE move_tech_bulletins SET mtb_use_date = $monday WHERE mtb_id = $mtb_id";
			ora_parse($cur2, $sql);
			ora_exec($cur2);
		} 
		else 
		{
			// echo "Got zilch!!!<br>";
			$mtb_id = -1;
			$mtb_revision = -1;
		}
	}

	ora_close($cursor);
	ora_close($cur2);

	$data= array('mtb_id' => $mtb_id, 'mtb_revision' => $mtb_revision);

	return $data;
}

function create_mtr($user_id)
{
	global $conn, $user_id, $monday, $mtb_revision;

	$result = get_active_mtb();
	$mtb_id = $result['mtb_id'];
	$mtb_revision = $result['mtb_revision'];
	
	$cursor = ora_open($conn);

	$sql = "INSERT INTO move_tech_bulletins_read 
		(mbr_id, MBR_REF_MOVE_TECH_BULLETINS, mbr_revision, mbr_ref_hc_people, mbr_status, mbr_date_start) 
		VALUES ( MBR_ID_SEQ.NEXTVAL, $mtb_id, $mtb_revision, $user_id, 0, '$monday'
	)";
	
	ora_parse($cursor, $sql);
	ora_exec($cursor);
	ora_close($cursor);

	load_banner();
}

function load_banner()
{
	global $conn, $bulletin_name, $bulletin_url, $monday, $user_id, $mtb_revision, $mbr_id, $mbr_status, $mbr_date_updated;
	
	$cursor = ora_open($conn);

	$sql = "SELECT * FROM move_tech_bulletins_read WHERE mbr_ref_hc_people = '$user_id' AND mbr_date_start = '$monday'";
	
	ora_parse($cursor, $sql);
	ora_exec($cursor);
	
	// if (ora_fetch_into($cur4, $data, ORA_FETCHINTO_ASSOC))
	if (ora_fetch_into($cursor, $mbr_row, ORA_FETCHINTO_ASSOC)) 
	{
		$mbr_id = $mbr_row['MBR_ID'];
		$bulletin_id = $mbr_row['MBR_REF_MOVE_TECH_BULLETINS'];
		$mbr_status = $mbr_row['MBR_STATUS'];
		$mbr_date_updated = $mbr_row['MBR_DATE_UPDATED'];
		
		$cur2 = ora_open($conn);
		$sql = "SELECT * FROM move_tech_bulletins WHERE mtb_id = $bulletin_id";
		ora_parse($cur2, $sql);
		ora_exec($cur2);
	
		if (ora_fetch_into($cur2, $mtb_row, ORA_FETCHINTO_ASSOC)) 
		{
			$bulletin_name = $mtb_row['MTB_NAME'];
			$bulletin_url = $mtb_row['MTB_URL'];
		}
		ora_close($cur2);
	} 
	else 
	{
		// No record found: create one
		create_mtr($user_id);
	}
	ora_close($cursor);
}


?>
<?php 
// Uncomment to view 12 hour timer
// echo "Now - Updated: " . $now . " >>> " . $mbr_date_updated . " = " . $now - $mbr_date_updated. "<br>";
// echo "Status: " . $now - $mbr_date_updated . "<br>"; 

if ($mbr_date_updated == '')
{
	$show = 1;
}
else 
{
	$diff = $now - $mbr_date_updated;
	
	if ($diff > $cycle)
	{
		$show = 1;
	}
	else 
	{
		$show = 0;
	}
}

if ($mbr_status == 100)
{
	$show = 0;
}

/**
 * Quintin
 * 06-02-2025
 * Check if user is part of certain clocking group to decide whether to show the banner or not
 */
$show_to_groups = array(82,83,95,31,35,30,58,29,43,44,72,32,33,34,80,84,85,77,36,78,76,62,64,63,71,68,61,69,66,67,65,70,81,93);

$user_groups = check_user_group($user_id);

$common_groups = array_intersect($user_groups, $show_to_groups);

if (!empty($common_groups))
{
	// echo "User is part of the group<br>";
	$show = 0;
} 
else 
{
	// echo "User is not part of the group<br>";
	$show = 1;
}

if (($show == 1))
{
	echo "<script> allowed_to_read= false; </script>";
	$bulletin_url = '/move/pdf.php?url='.urlencode($bulletin_url);
?>
<div id="bulletin" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; border: 5px solid #F00; padding: 10px 0px; column-gap: 0px">
	<div style="padding-left: 40px; padding-right: 40px;">
		<a href="https://secure.intercape.co.za/newjump/technical-bulletins/" target="_blank">Bulletin Board</a>
		<input id="mbr_id" type="text" value="<?php echo $mbr_id; ?>" style="display: none" />
		<input id="mbr_status" type="text" value="<?php echo $mbr_status; ?>" style="display: none" />
	</div>
	<div>Please read the following bulletin:</div>
	<div id="bulletin_name" style="flex: 1"><a onclick="allowed_to_read = true;" href="<?php echo $bulletin_url; ?>" target="_blank"><?php echo $bulletin_name; ?></a></div>
	<div style="padding-right: 10px">Revision:</div>
	<div style="padding-right: 10px"><?php echo $mtb_revision; ?></div>
	<div id=haveread style="background-color: #f5f5f5; color: #000; border-radius: 5px; border: 1px solid #000; padding: 5px 20px; margin-right: 10px; cursor: pointer" onclick="if (allowed_to_read) {didRead(); document.getElementById('readwarning').innerHTML='';} else { document.getElementById('readwarning').innerHTML='<font color=red>Please open the document and read it first!</font>&nbsp;';   }  ">I have read the bulletin</div>
	<div id=readwarning></div>
	<?php
	if ($mbr_status != 2)
	{
	?>
	<div style="background-color: #f5f5f5; color: #000; border-radius: 5px; border: 1px solid #000; padding: 5px 20px; margin-right: 10px; cursor: pointer" onclick="didNotRead()">I have not read the bulletin</div>
	<?php
	}
	?>
</div>
<?php
}
?>
<script>
	function hideBanner()
	{
		let bulletin = document.getElementById("bulletin");
		bulletin.style.display = "none";
	}
	function didRead()
	{
		// 0 = Needs to read, 1 = Deferred once, 2 = Deferred Twice, 100 = Has Read
		const mtrId = document.getElementById("mbr_id").value;
		const mtrStatus = document.getElementById("mbr_status").value;

		console.log('Mtr Id: ', mtrId, ' > ', mtrStatus);
		sendData(0, mtrId, mtrStatus);

		hideBanner();
	}
	function didNotRead()
	{
		const mtrId = document.getElementById("mbr_id").value;
		const mtrStatus = document.getElementById("mbr_status").value;

		console.log('Mtr Id: ', mtrId, ' > ', mtrStatus);
		sendData(1, mtrId, mtrStatus);

		hideBanner();
	}
	async function sendData(mtrAction, mtrId, mtrStatus) 
	{
		const formData = { "mbr_action": mtrAction, "mbr_id": mtrId, "mbr_status": mtrStatus };

		// const phpUrl = 'http://localhost/icdev/bulletin/move_bulletins_modal.php?a=' + mtrAction;
		const phpUrl = '/move/bulletin/move_bulletins_modal.php';
		const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
		const result = await response.text();
		console.log('Result:', result);
	}
</script>
