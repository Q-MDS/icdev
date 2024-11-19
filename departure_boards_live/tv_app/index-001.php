<?php
@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
@header("Cache-Control: no-cache, must-revalidate");
@header("Pragma: no-cache");

ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };

// Must be passed in as parameter
$stop_serial = 0;
$screen_id = 0;
$screen_layout = 0; 

if (isset($_GET['s']))
{
	$screen_id = $_GET['s'];
}

function get_stop_serial()
{
	global $conn, $stop_serial, $screen_id, $screen_layout;

	$sql = "SELECT STOP_SERIAL FROM DEPARTURE_TVS WHERE SCREEN_ID = :screen_id";

	$stid = oci_parse($conn, $sql);

	oci_bind_by_name($stid, ':screen_id', $screen_id);

	oci_execute($stid);

	$row = oci_fetch_array($stid, OCI_ASSOC);

	$stop_serial = $row['STOP_SERIAL'];

	oci_free_statement($stid);

	oci_close($conn);
}

function get_layout()
{
	global $conn, $screen_id, $screen_layout;

	$sql = "SELECT * FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = :screen_id";

	$stid = oci_parse($conn, $sql);

	oci_bind_by_name($stid, ':screen_id', $screen_id);

	oci_execute($stid);

	$row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);

	$brand_a = $row['BRAND'];
	$brand_b = $row['BRAND_B'];

	if ($brand_b == "")
	{
		$screen_layout = 0;
	}
	else if ($brand_a != "BI" && $brand_b == "BI")
	{
		$screen_layout = 1;
	}
	else if ($brand_a == "BI" && $brand_b != "BI")
	{
		$screen_layout = 2;
	}
	else if ($brand_a != "BI" && $brand_b != "BI")
	{
		$screen_layout = 3;
	}
	else if ($brand_a == "BI" && $brand_b == "BI")
	{
		$screen_layout = 4;
	}

	oci_free_statement($stid);

	oci_close($conn);
}

get_stop_serial();
get_layout();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Boarding Board</title>
<style>
	html, body {
		margin: 0;
		padding: 0;
		width: 100vw;
		height: 100%;
		font-family: Arial, sans-serif;
		overflow: hidden;
		box-sizing: border-box;
	}
</style>
</head>
<body onload="init(<?php echo $screen_layout; ?>);">

<div id="fullscreen" style="display: block; width: 100%; height: 100%; padding: 0px; margin: 0px; border: 0px;">
	<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; height: 100%;">
		<div style="display: flex; align-items: center; justify-content: center; flex: 1; height: 100%; box-sizing: border-box;">
			<?php
			if ($screen_layout == 0) 
			{
				?>
				<!-- FULLSCREEN -->
				<!-- ---------- -->
				<!-- IC Board -->
				<iframe id="ic_board" src="ic.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
				<!-- BI Board -->
				<iframe id="bi_board" src="bi.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
				<?php
			}
			else if ($screen_layout == 1) // IC/BI
			{
				?>
				<!-- IC/BI -->
				<!-- ----- -->
				<iframe id="split_board" src="ic_bi.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
				<?php
			} 
			elseif ($screen_layout == 2) 
			{
				?>
				<!-- BI/IC -->
				<!-- ----- -->
				<iframe id="split_board" src="bi_ic.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
				<?php
			}
			elseif($screen_layout == 3)
			{
				?>
				<!-- IC/IC -->
				<!-- ----- -->
				<iframe id="split_board" src="ic_ic.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
				<?php
			}
			else 
			{
				?>
				<!-- BI/BI -->
				<!-- ----- -->
				<iframe id="split_board" src="bi_bi.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
				<?php
			}
			?>
			<!-- Arrivals -->
			<iframe id="arrivals" src="https://secure.intercape.co.za/ignite/index.php?c=no_auth&m=vdeparture_boards&type=1&stop=<?php echo $stop_serial; ?>"  style="display: none; width: 100%; height: 100%; border: 0px;"></iframe>
			<!-- Offline -->
			<div id="offline" style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%; background: #f17d32">
			<div>
				<div style="font-size: 3rem; font-style: italic; font-weight: 900; color: #fff; text-shadow: 3px 3px 3px rgba(0, 0, 0, 0.75);">Please check back later</div>
					<input type="text" id="screen_id" value="<?php echo $screen_id; ?>" style="display: none">
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
<script>
// baseUrl = window.location.protocol + "//" + window.location.hostname + "/noauth/";
baseUrl = window.location.protocol + "//" + window.location.hostname + "/move/";
screenLayout = <?php echo $screen_layout; ?>;

function init()
{
	console.log('You are: ' + screenLayout);
	if (screenLayout == 0)
	{
		// Fullscreen
		fetchData();
		refresh();
	}
	else 
	{
		fetchSplitData();
		refreshSplit();
	}
}

function refresh()
{
	setInterval(fetchData, 5000);
}

function refreshSplit()
{
	setInterval(fetchSplitData, 5000);
}

async function fetchData() 
{
	// Fullscreen
	const screenId = document.getElementById('screen_id').value;

	if (navigator.onLine) 
	{
		const phpUrl = baseUrl + 'departure_boards/api/g.php';
		const formData = { "screen_id": screenId };
		
		const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
		const result = await response.text()
		.then(result => 
		{
			// console.log('Result:', result);
			const arrivals = document.getElementById('arrivals');
			const offline = document.getElementById('offline');
			const icBoard = document.getElementById('ic_board');
			const biBoard = document.getElementById('bi_board');

			if (result == 0)
			{
				// No data: show arrival screen
				console.log('No data found');
				document.body.style.backgroundColor = "#333";
				
				icBoard.style.display = 'none';
				biBoard.style.display = 'none';
				arrivals.style.display = 'block';
				offline.style.display = 'none';
			}
			else 
			{
				const data = JSON.parse(result);

				arrivals.style.display = 'none';
				offline.style.display = 'none';
				
				switch(data.brand)
				{
					case 'IC':
					case 'IS':
					case 'BS':
					case 'IM':
					case 'IB':
					case 'AR':
					case 'ZZ':
						biBoard.style.display = 'none';
						icBoard.style.display = 'block';
						
						sendMessageToIc(data);
					break;
					case 'BI':
						icBoard.style.display = 'none';
						biBoard.style.display = 'block';
						
						sendMessageToBi(data);
					break;
					biBoard.style.display = 'none';
					icBoard.style.display = 'block';
					
					sendMessageToIc(data);
				}
			}
		});
	}
	else 
	{
		// System is offline
		console.log('Cannot fetch data, you are offline');
		
		document.getElementById('ic_board').style.display = 'none';
		document.getElementById('bi_board').style.display = 'none';
		document.getElementById('arrivals').style.display = 'none';
		document.getElementById('offline').style.display = 'flex';
	}
}

async function fetchSplitData()
{
	// Fullscreen
	const screenId = document.getElementById('screen_id').value;

	if (navigator.onLine) 
	{
		const phpUrl = baseUrl + 'departure_boards/api/s.php';
		const formData = { "screen_id": screenId };
		
		const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
		const result = await response.text()
		.then(result => 
		{
			// console.log('Result:', result);
			const arrivals = document.getElementById('arrivals');
			const offline = document.getElementById('offline');
			const splitBoard = document.getElementById('split_board');

			if (result == 0)
			{
				// No data: show arrival screen
				console.log('No data found');
				document.body.style.backgroundColor = "#333";
				
				splitBoard.style.display = 'none';
				arrivals.style.display = 'block';
				offline.style.display = 'none';
			}
			else 
			{
				const data = JSON.parse(result);

				arrivals.style.display = 'none';
				offline.style.display = 'none';

				sendMessageToSplit(data);
			}
		});
	}
	else 
	{
		// System is offline
		console.log('Cannot fetch data, you are offline');
		
		document.getElementById('splitBoard').style.display = 'none';
		document.getElementById('arrivals').style.display = 'none';
		document.getElementById('offline').style.display = 'flex';
	}
}

function sendMessageToIc(result) 
{
	console.log('Sending message to iframe...', result);
	const iframe = document.getElementById('ic_board');
	
	const message = result;
	iframe.contentWindow.postMessage(message, '*');
}

function sendMessageToBi(result) 
{
	console.log('Sending message to iframe...');
	const iframe = document.getElementById('bi_board');
	
	const message = result;
	iframe.contentWindow.postMessage(message, '*');
}

function sendMessageToSplit(result) 
{
	console.log('Sending message to iframe...');
	const iframe = document.getElementById('split_board');
	
	const message = result;
	iframe.contentWindow.postMessage(message, '*');
}
</script>