<?php
ob_start();
require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");
require_once ("../php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

// Test mode only
$test_user = $_GET['u'];
$test_date = $_GET['d'];

// Global vars
$bulletin_name = 'none';
$bulletin_url = '';
$mtb_revision = '0';
$monday = start_date();
$next_mbr_id = 0;
$user_id = $test_user;
$mtr_id = 0;
$mtr_status = 0;
$mtr_date_updated = 0;
$now = strtotime(date("Y-m-d H:i:s"));
$cycle = 12 * 60 * 60; // 12 hours
// $cycle = 20; // 12 seconds

load_banner();

function start_date()
{
	global $monday, $test_date;
	// Get date of current Monday
	// $date = new DateTime();
	// Use below to test a new week
	// $date = new DateTime('2024-11-04');
	$date = new DateTime($test_date);
	$date->modify('this week monday');
	$monday = $date->format('Y-m-d');
	$monday = strtotime($monday);
	
	return $monday;
}

function get_active_mtb()
{
	global $conn, $monday, $mtb_id;

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
	$cur2 = ora_open($conn);

	$sql = "SELECT mtb_id, mtb_revision FROM move_tech_bulletins WHERE mtb_status != 0 AND mtb_use_date IS NULL OR TRIM(mtb_use_date) = '' ORDER BY mtb_status DESC, mtb_date DESC FETCH FIRST 1 ROWS ONLY";
	ora_parse($cursor, $sql);
	ora_exec($cursor);

	if (ora_fetch_into($cursor, $mtr_row, ORA_FETCHINTO_ASSOC)) 
	{
		$mtb_id = $mtr_row['MTB_ID'];
		$mtb_revision = $mtr_row['MTB_REVISION'];

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

		if (ora_fetch_into($cur2, $mtr_row, ORA_FETCHINTO_ASSOC)) 
		{
			$mtb_id = $mtr_row['MTB_ID'];
			$mtb_revision = $mtr_row['MTB_REVISION'];

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
		(mtr_id, MTR_REF_MOVE_TECH_BULLETINS, mtr_revision, mtr_ref_hc_people, mtr_status, mtr_date_start) 
		VALUES ( MTBR_ID_SEQ.NEXTVAL, $mtb_id, $mtb_revision, $user_id, 0, '$monday'
	)";
	
	ora_parse($cursor, $sql);
	ora_exec($cursor);
	ora_close($cursor);

	load_banner();
}

function load_banner()
{
	global $conn, $bulletin_name, $bulletin_url, $monday, $user_id, $mtb_revision, $mtr_id, $mtr_status, $mtr_date_updated;
	
	$cursor = ora_open($conn);

	$sql = "SELECT * FROM move_tech_bulletins_read WHERE mtr_ref_hc_people = '$user_id' AND mtr_date_start = '$monday'";
	
	ora_parse($cursor, $sql);
	ora_exec($cursor);
	
	// if (ora_fetch_into($cur4, $data, ORA_FETCHINTO_ASSOC))
	if (ora_fetch_into($cursor, $mtr_row, ORA_FETCHINTO_ASSOC)) 
	{
		$mtr_id = $mtr_row['MTR_ID'];
		$bulletin_id = $mtr_row['MTR_REF_MOVE_TECH_BULLETINS'];
		$mtr_status = $mtr_row['MTR_STATUS'];
		$mtr_date_updated = $mtr_row['MTR_DATE_UPDATED'];
		
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
// echo "Now - Updated: " . $now . " >>> " . $mtr_date_updated . " = " . $now - $mtr_date_updated. "<br>";
// echo "Status: " . $now - $mtr_date_updated . "<br>"; 

if ($mtr_date_updated == '')
{
	$show = 1;
}
else 
{
	$diff = $now - $mtr_date_updated;
	
	if ($diff > $cycle)
	{
		$show = 1;
	}
	else 
	{
		$show = 0;
	}
}

if ($mtr_status == 100)
{
	$show = 0;
}

if (($show == 1))
{
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
		const mtrId = document.getElementById("mtr_id").value;
		const mtrStatus = document.getElementById("mtr_status").value;

		console.log('Mtr Id: ', mtrId, ' > ', mtrStatus);
		sendData(0, mtrId, mtrStatus);

		hideBanner();
	}
	function didNotRead()
	{
		const mtrId = document.getElementById("mtr_id").value;
		const mtrStatus = document.getElementById("mtr_status").value;

		console.log('Mtr Id: ', mtrId, ' > ', mtrStatus);
		sendData(1, mtrId, mtrStatus);

		hideBanner();
	}
	async function sendData(mtrAction, mtrId, mtrStatus) 
	{
		const formData = { "mtr_action": mtrAction, "mtr_id": mtrId, "mtr_status": mtrStatus };

		// const phpUrl = 'http://localhost/icdev/bulletin/move_bulletins_modal.php?a=' + mtrAction;
		const phpUrl = 'http://192.168.10.239/move/bulletin/move_bulletins_modal.php';
		const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
		const result = await response.text();
		console.log('Result:', result);
	}
</script>