<?php
@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
@header("Cache-Control: no-cache, must-revalidate");
@header("Pragma: no-cache");

// Must be passed in as parameter
$stop_serial = 0;
$screen_id = 0;
$screen_layout = 0; // 0 = fullscreen, 1 = split screen

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
	
	$sql = "SELECT STOP_SERIAL, SCREEN_LAYOUT FROM DEPARTURE_TVS WHERE SCREEN_ID = :screen_id";

	$stid = oci_parse($conn, $sql);

	oci_bind_by_name($stid, ':screen_id', $screen_id);

	oci_execute($stid);

	$row = oci_fetch_array($stid, OCI_ASSOC);

	$stop_serial = $row['STOP_SERIAL'];
	$screen_layout = $row['SCREEN_LAYOUT'];

	oci_free_statement($stid);

	oci_close($conn);
}

get_stop_serial();
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
	<?php
	if ($screen_layout == 0)
	{
	?>
	<div id="fullscreen" style="display: block; width: 100%; height: 100%; padding: 0px; margin: 0px; border: 0px;">
		<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; height: 100%;">
			<div style="display: flex; align-items: center; justify-content: center; flex: 1; height: 100%; box-sizing: border-box;">
				<!-- IC Board -->
				<iframe id="ic_board" src="ic.html" style="display: none; width: 100%; height: 100%; border: 0px;"></iframe>
				<!-- BI Board -->
				<iframe id="bi_board" src="bi.html" style="display: none; width: 100%; height: 100%; border: 0px;"></iframe>
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
	<?php
	}
	else if ($screen_layout == 1)
	{
	?>
	<div id="splitscreen" style="display: block; width: 100%; height: 100%; padding: 0px; margin: 0px; border: 0px;">
		<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; height: 100%;">
			<div style="display: flex; align-items: center; justify-content: center; flex: 1; height: 100%; box-sizing: border-box;">
				<iframe id="board_left" src="ic.html" style="width: 100%; height: 100%; border: 0px;"></iframe>
			</div>
			<div style="display: flex; align-items: center; justify-content: center; flex: 1; height: 100%; box-sizing: border-box;">
				<iframe id="board_right" src="bi.html" style="width: 100%; height: 100%; border: 0px;"></iframe>
			</div>
		</div>
	</div>
	<?php
	}
	?>
</body>
</html>
<script>
baseUrl = window.location.protocol + "//" + window.location.hostname + "/icdev/";

function init()
{
	console.log('You are: ' + navigator.onLine);
	fetchData();
	refresh();
}

function refresh()
{
	setInterval(fetchData, 5000);
}

async function fetchData() 
{
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

function sendMessageToIc(result) 
{
	console.log('Sending message to iframe...');
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
</script>