<?php
@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
@header("Cache-Control: no-cache, must-revalidate");
@header("Pragma: no-cache");

// Must be passed in as parameter
$stop_serial = 0;
$screen_id = 0;
$screen_layout = 0; 

if (isset($_GET['s']))
{
	$screen_id = $_GET['s'];
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

function get_stop_serial()
{
	global $stop_serial, $screen_id, $screen_layout;

	$conn = oci_conn();
	
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
	global $screen_id, $screen_layout;

	$conn = oci_conn();
	
	$sql = "SELECT * FROM DEPARTURE_TV_SETTINGS WHERE SCREEN_ID = :screen_id";

	$stid = oci_parse($conn, $sql);

	oci_bind_by_name($stid, ':screen_id', $screen_id);

	oci_execute($stid);
	
	$row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
	
	$screen_layout = 0;

	if ($row != null)
	{
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
<body onload="init();">

<div style="display: block; width: 100%; height: 100%; padding: 0px; margin: 0px; border: 0px; background: #333">
	<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; height: 100%;">
		<div style="display: flex; align-items: center; justify-content: center; flex: 1; height: 100%; box-sizing: border-box;">
		
			<div id="fullscreen" style="width: 100%; height: 100%; display: none">
				<!-- IC Board -->
				<iframe id="ic_board" src="ic.html" style="display: none; width: 100%; height: 100%; border: 0px;"></iframe>
				<!-- BI Board -->
				<iframe id="bi_board" src="bi.html" style="display: none; width: 100%; height: 100%; border: 0px;"></iframe>
			</div>

			<div id="ic_bi" style="display: none; width: 100%; height: 100%;">
				<iframe id="icbi_board" src="ic_bi.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
			</div>

			<div id="bi_ic" style="display: none; width: 100%; height: 100%">
				<iframe id="biic_board" src="bi_ic.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
			</div>

			<div id="ic_ic" style="display: none; width: 100%; height: 100%">
				<iframe id="icic_board" src="ic_ic.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
			</div>

			<div id="bi_bi" style="display: none; width: 100%; height: 100%">
				<iframe id="bibi_board" src="bi_bi.html" style="display: block; width: 100%; height: 100%; border: 0px;"></iframe>
			</div>
		
			<!-- Arrivals -->
			<iframe id="arrivals" src="https://secure.intercape.co.za/ignite/index.php?c=no_auth&m=vdeparture_boards&type=1&stop=<?php echo $stop_serial; ?>" style="display: none; width: 100%; height: 100%; border: 0px;"></iframe>
			
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
baseUrl = window.location.protocol + "//" + window.location.hostname + "/icdev/";
screenLayout = <?php echo $screen_layout; ?>;
let fetchInterval;
let fetchSplitInterval;

function init()
{
	console.log('INIT Start: ', screenLayout);
	// setLayout();

	stopFetchInterval();
	stopFetchSplitInterval();

	if (screenLayout == 0)
	{
		// Fullscreen
		console.log('Running fetchData()...');
		fetchData();
		// setLayout();
		refresh();
	}
	else 
	{
		console.log('Running fetchSplitData()...');
		fetchSplitData();
		// setLayout();
		refreshSplit();
	}
}

function setLayout()
{
	console.log('Setting layout...', screenLayout);
	const fullscreen = document.getElementById('fullscreen');
	const ic_board = document.getElementById('ic_board');
	const bi_board = document.getElementById('bi_board');
	const icBi = document.getElementById('ic_bi');
	const biIc = document.getElementById('bi_ic');
	const icIc = document.getElementById('ic_ic');
	const biBi = document.getElementById('bi_bi');

	switch(screenLayout)
	{
		case 0:
			fullscreen.style.display = 'block';
			ic_board.style.display = 'block';
			bi_board.style.display = 'block';
			icBi.style.display = 'none';
			biIc.style.display = 'none';
			icIc.style.display = 'none';
			biBi.style.display = 'none';
		break;
		case 1:
			fullscreen.style.display = 'none';
			ic_board.style.display = 'none';
			bi_board.style.display = 'none';
			icBi.style.display = 'block';
			biIc.style.display = 'none';
			icIc.style.display = 'none';
			biBi.style.display = 'none';
		break;
		case 2:
			fullscreen.style.display = 'none';
			ic_board.style.display = 'none';
			bi_board.style.display = 'none';
			icBi.style.display = 'none';
			biIc.style.display = 'block';
			icIc.style.display = 'none';
			biBi.style.display = 'none';
		break;
		case 3:
			fullscreen.style.display = 'none';
			ic_board.style.display = 'none';
			bi_board.style.display = 'none';
			icBi.style.display = 'none';
			biIc.style.display = 'none';
			icIc.style.display = 'block';
			biBi.style.display = 'none';
		break;
		case 4:
			fullscreen.style.display = 'none';
			ic_board.style.display = 'none';
			bi_board.style.display = 'none';
			icBi.style.display = 'none';
			biIc.style.display = 'none';
			icIc.style.display = 'none';
			biBi.style.display = 'block';
		break;
	}
}

function refresh()
{
	if (fetchInterval) 
	{
		clearInterval(fetchInterval);
		fetchInterval = null;
	}

	fetchInterval = setInterval(() => { fetchData(); }, 5000);
}

function stopFetchInterval()
{
	if (fetchInterval) {
		clearInterval(fetchInterval);
		fetchInterval = null;
	}
}

function refreshSplit()
{
	if (fetchSplitInterval) 
	{
		clearInterval(fetchSplitInterval);
	}
	fetchSplitInterval = setInterval(() => { fetchSplitData(); }, 5000);
}

function stopFetchSplitInterval()
{
	if (fetchSplitInterval) {
		clearInterval(fetchSplitInterval);
		fetchSplitInterval = null;
	}
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
			console.log('Received data for fullscreen:', result);

			const arrivals = document.getElementById('arrivals');
			const offline = document.getElementById('offline');
			const icBoard = document.getElementById('ic_board');
			const biBoard = document.getElementById('bi_board');
			const fullscreen = document.getElementById('fullscreen');

			if (result == 0)
			{
				// No data: show arrival screen
				console.log('No single data found');
				stopFetchSplitInterval();
				screenLayout = 0;
				setLayout();

				document.body.style.backgroundColor = "#333";
				
				fullscreen.style.display = 'none';
				icBoard.style.display = 'none';
				biBoard.style.display = 'none';
				arrivals.style.display = 'block';
				offline.style.display = 'none';
			}
			else 
			{
				const data = JSON.parse(result);

				const getLayout = data.screen_layout;

				if (getLayout != screenLayout)
				{
					screenLayout = getLayout;
					init();
				}

				arrivals.style.display = 'none';
				offline.style.display = 'none';
				fullscreen.style.display = 'block';
				
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
	// Splitscreen
	const screenId = document.getElementById('screen_id').value;

	if (navigator.onLine) 
	{
		const phpUrl = baseUrl + 'departure_boards/api/s.php';
		const formData = { "screen_id": screenId };
		
		const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
		const result = await response.text()
		.then(result => 
		{
			console.log('Received data for splitscreen: ' + result);

			const arrivals = document.getElementById('arrivals');
			const offline = document.getElementById('offline');
			
			if (result == 0)
			{
				// No data: show arrival screen
				console.log('No split data found');
				document.body.style.backgroundColor = "#333";
				
				screenLayout = 0;
				init();

				arrivals.style.display = 'block';
				offline.style.display = 'none';
				document.getElementById('fullscreen').style.display = "none";
				document.getElementById('ic_board').style.display = "none";
				document.getElementById('bi_board').style.display = "none";
				document.getElementById('icbi_board').style.display = "none";
				document.getElementById('biic_board').style.display = "none";
				document.getElementById('icic_board').style.display = "none";
				document.getElementById('bibi_board').style.display = "none";
			}
			else 
			{
				const data = JSON.parse(result);

				const getLayout = data.screen_layout;

				if (getLayout != screenLayout)
				{
					screenLayout = getLayout;
					init();
				}

				document.getElementById('fullscreen').style.display = "none";
				console.log('Split:', data.screen_layout);
				screenLayout = data.screen_layout;
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
		
		// document.getElementById('splitBoard').style.display = 'none';
		document.getElementById('arrivals').style.display = 'none';
		document.getElementById('offline').style.display = 'flex';
	}
}

function sendMessageToIc(result) 
{
	console.log('Sending message to IC iframe...');
	const iframe = document.getElementById('ic_board');
	
	const message = result;
	iframe.contentWindow.postMessage(message, '*');
}

function sendMessageToBi(result) 
{
	console.log('Sending message to BI iframe...');
	const iframe = document.getElementById('bi_board');
	
	const message = result;
	iframe.contentWindow.postMessage(message, '*');
}

function sendMessageToSplit(result) 
{
	console.log('Sending message to split iframe...', screenLayout);

	document.getElementById('ic_bi').style.display = 'none';
	document.getElementById('bi_ic').style.display = 'none';
	document.getElementById('ic_ic').style.display = 'none';
	document.getElementById('bi_bi').style.display = 'none';

	let iframe;

	if (screenLayout == 1)
	{
		console.log('AAA');
		document.getElementById('ic_bi').style.display = 'block';
		iframe = document.getElementById('icbi_board');
	} 
	else if (screenLayout == 2)
	{
		console.log('BBB');
		document.getElementById('bi_ic').style.display = 'block';
		iframe = document.getElementById('biic_board');
	} 
	else if (screenLayout == 3)
	{
		console.log('CCC');
		document.getElementById('ic_ic').style.display = 'block';
		iframe = document.getElementById('icic_board');
	}
	else if (screenLayout == 4)
	{
		console.log('DDD');
		document.getElementById('bi_bi').style.display = 'block';
		iframe = document.getElementById('bibi_board');
	}

	iframe.style.display = 'block';
	const message = result;
	iframe.contentWindow.postMessage(message, '*');
}
</script>