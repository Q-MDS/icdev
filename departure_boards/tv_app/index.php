<?php
// Must be passed in as parameter
$stop_serial = 0;
$screen_id = 0;

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
	global $stop_serial, $screen_id;

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
	<!-- Placeholder for IC/BI content -->
    <!-- <div id="ic-container" style="display: none"></div>
    <div id="bi-container" style="display: none"></div> -->
	<div style="width: 100%; height: 100%; padding: 0px; margin: 0px; border: 0px;">
		<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; height: 100%;">
			<div style="display: flex; align-items: center; justify-content: center; flex: 1; height: 100%; box-sizing: border-box;">
				<iframe id="board_main" src="ic.html" style="display: none; width: 100%; height: 100%; border: 0px;"></iframe>
				<iframe id="arrivals" src="https://secure.intercape.co.za/ignite/index.php?c=no_auth&m=vdeparture_boards&type=1&stop=<?php echo $stop_serial; ?>"  style="display: none; width: 100%; height: 100%; border: 0px;"></iframe>
			</div>
		</div>
	</div>
	

	<div id="offline" style="display: none; align-items: center; justify-content: center; height: 100%;">
		<div>
			<div style="font-size: 7em; font-style: italic; font-weight: 900; color: #FFF; text-shadow: 3px 3px 3px rgba(0, 0, 0, 0.75);">Please check back later</div>
			<input type="text" id="screen_id" value="<?php echo $screen_id; ?>" style="display: none">
		</div>
	</div>

	<!-- <div id="arrivals" style="display: block; height: 100%; width: 100%; z-index:999">
		<div style="width: 100%">
			
		</div>
	</div> -->
</body>
</html>
<script>
baseUrl = window.location.protocol + "//" + window.location.hostname + "/icdev/";

//if ('serviceWorker' in navigator) {
//  window.addEventListener('load', function() {
//    navigator.serviceWorker.register(baseUrl + 'departure_boards/tv_app/service-worker.js').then(function(registration) {
//      console.log('ServiceWorker registration successful with scope: ', registration.scope);
//    }, function(err) {
//      console.log('ServiceWorker registration failed: ', err);
//    });
//  });
//}

function init()
{
	console.log('You are: ' + navigator.onLine);
	// setInterval(fetchData, 5000);
	// loadICContent();
	// loadBIContent();
	fetchData();
	refresh();
}

function refresh()
{
	setInterval(fetchData, 30000);
}

function loadICContent() 
{
	fetch('ic.html')
	.then(response => response.text())
	.then(data => 
	{
		// console.log('IC content loaded:', data);
		document.getElementById('ic-container').innerHTML = data;
	})
	.catch(error => console.error('Error loading IC content:', error));
}

function loadBIContent() 
{
	fetch('bi.html')
	.then(response => response.text())
	.then(data => 
	{
		// console.log('BI content loaded:', data);
		document.getElementById('bi-container').innerHTML = data;
	})
	.catch(error => console.error('Error loading BI content:', error));
}

function isOffline()
{
	console.log('Offline check...');
	if (!navigator.onLine)
	{
		console.log('You are offline');
		document.getElementById('ic-container').style.display = 'none';
		document.getElementById('bi-container').style.display = 'none';
		document.getElementById('offline').style.display = 'block';
	}
	else 
	{
		console.log('You are online');
		document.getElementById('ic-container').style.display = 'block';
		document.getElementById('bi-container').style.display = 'none';
		document.getElementById('offline').style.display = 'none';
	}
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
			console.log('Result:', result);
			const arrivals = document.getElementById('arrivals');
			const offline = document.getElementById('offline');
			const board_main = document.getElementById('board_main');

			if (result == 0)
			{
				// No data: show arrival screen
				console.log('No data found aaa');
				document.body.style.backgroundColor = "#333";
				
				board_main.style.display = 'none';
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
						board_main.style.display = 'block';
						if (board_main.src != baseUrl + 'departure_boards/tv_app/ic.html')
						{
							board_main.src = 'ic.html';
						}
						sendMessageToIframe(data);
					break;
					case 'BI':
						board_main.style.display = 'block';
						if (board_main.src != baseUrl + 'departure_boards/tv_app/bi.html')
						{
							board_main.src = 'bi.html';
						}
					break;
					default:
						board_main.style.display = 'block';
						if (board_main.src != baseUrl + 'departure_boards/tv_app/ic.html')
						{
							board_main.src = 'ic.html';
						}
				}
			}
		});
	}
	else 
	{
		console.log('Cannot fetch data, you are offline');
		
		document.getElementById('ic-container').style.display = 'none';
		document.getElementById('bi-container').style.display = 'none';
		document.getElementById('arrivals').style.display = 'none';
		document.getElementById('offline').style.display = 'flex';
	}
}

function sendMessageToIframe(result) 
{
	console.log('Sending message to iframe...');
	const iframe = document.getElementById('board_main');
	// const title = "Hello, iframe!";
	// const title = result;
	// const routeNumber = "7230";
	// const message = {
	// 	title: title,
	// 	route_number: routeNumber
	// };
	const message = result;
	iframe.contentWindow.postMessage(message, '*');
}

// init();
</script>