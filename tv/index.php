<?php
$stop_serial = 0;
$screen_id = 0;

if (isset($_GET['s']))
{
	$stop_serial = $_GET['s'];
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

function get_screen_id()
{
	global $stop_serial, $screen_id;

	$conn = oci_conn();
	
	$sql = "SELECT SCREEN_ID FROM DEPARTURE_TVS WHERE STOP_SERIAL = :stop_serial";

	$stid = oci_parse($conn, $sql);

	oci_bind_by_name($stid, ':stop_serial', $stop_serial);

	oci_execute($stid);

	$row = oci_fetch_array($stid, OCI_ASSOC);

	$screen_id = $row['SCREEN_ID'];

	oci_free_statement($stid);

	oci_close($conn);
}

get_screen_id();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Boarding Boards</title>
<style>
	/* Ensure the body takes up the full viewport height and width */
	/* 1300 x 731 */
	html, body {
	  height: 100%;
	  margin: 0;
	  padding: 0;
	  font-family: Arial, Helvetica, sans-serif;
	  color: #000;
	}
  
	/* Container to maintain 16:9 aspect ratio */
	.aspect-ratio-16-9 {
	  position: relative;
	  width: 100%;
	  height: 0;
	  padding-bottom: 56.25%; /* 16:9 aspect ratio */
	}
  
	/* Content inside the container */
	.aspect-ratio-16-9 > .content {
	  position: absolute;
	  display: flex;
	  flex-direction: column;
	  justify-content: center;
	  align-items: center;
	  top: 0;
	  left: 0;
	  width: 100%;
	  height: 100%;
	}
</style>
</head>
<body>
	<!-- <div style="color: black;">sdfsdfsdfsdf</div> -->
	<div id="ic" class="aspect-ratio-16-9" style="display: block">
		<div class="content">
			<div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; background-color: #ffffff;">
					<img src="ic_logo.svg" style="height: 80%;">	
				</div>
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; background-color: #F37721; font-size: 5.5vw; font-weight: bold; color: white">ARE YOU ON THE RIGHT COACH?</div>
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 20%; background-color: #ffffff;">
					<input type="text" id="screen_id" value="<?php echo $screen_id; ?>" style="display: none">
				</div>
			</div>
			<div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
				<div id="ic_route_number" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #3A3A3B; font-size: 5.5vw; font-weight: bold" onclick="fetchData()">1154</div>
				<div style="display: flex; flex-direction: row; width: 100%; height: 20%; background-color: #ffffff;">&nbsp;</div>
				<div id="ic_route_desc" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #3A3A3B; font-size: 4.0vw; font-weight: bold">Express Mthatha to Cape Town Via Queenstown</div>
			</div>
		</div>
	</div>
	
	<div id="bs" class="aspect-ratio-16-9" style="display: none">
		<div class="content">
			<div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; background-color: #ffffff;">
					<img src="bs_logo.svg" style="height: 80%;">	
				</div>
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #0A78BB; font-size: 5.5vw; font-weight: bold">ARE YOU ON THE RIGHT COACH?</div>
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 20%; background-color: #ffffff;">&nbsp;</div>
			</div>
			<div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
				<div id="bs_route_number" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #3A3A3B; font-size: 5.5vw; font-weight: bold">1154</div>
				<div style="display: flex; flex-direction: row; width: 100%; height: 20%; background-color: #ffffff;">&nbsp;</div>
				<div id="bs_route_desc" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40; color: white; background-color: #3A3A3B; font-size: 4.0vw; font-weight: bold">Express Mthatha to Cape Town Via Queenstown</div>
			</div>
		</div>
	</div>
	
	
	<div id="offline" class="aspect-ratio-16-9" style="display: none">
		<div class="content">
			<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; color: black;" onclick="isOffline()">Please check back later</div>
		</div>
	</div>

	<div id="arrivals" class="aspect-ratio-16-9" style="display: none">
		<div class="content">
			<iframe src="https://secure.intercape.co.za/ignite/index.php?c=no_auth&m=vdeparture_boards&type=1&stop=56" width="100%" height="100%" style="border: 0px;"></iframe>
		</div>
	</div>

</body>
</html>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/icdev/tv/service-worker.js').then(function(registration) {
      console.log('ServiceWorker registration successful with scope: ', registration.scope);
    }, function(err) {
      console.log('ServiceWorker registration failed: ', err);
    });
  });
}

function init()
{
	console.log('You are 2: ' + navigator.onLine);
	setInterval(fetchData, 5000);
}

function isOffline()
{
	console.log('Offline check...');
	if (!navigator.onLine)
	{
		console.log('You are offline');
		document.getElementById('ic').style.display = 'none';
		document.getElementById('bs').style.display = 'none';
		document.getElementById('offline').style.display = 'block';
	}
	else 
	{
		console.log('You are online');
		document.getElementById('ic').style.display = 'block';
		document.getElementById('bs').style.display = 'none';
		document.getElementById('offline').style.display = 'none';
	}
}

function fetchDataOld() 
{
	const screenId = document.getElementById('screen_id').value;

	console.log('Fetching board data for screen ID:', screenId);

	if (navigator.onLine) 
	{
		document.getElementById('offline').style.display = 'none';
		
    	console.log('Fetching board daa');
		
		fetch('http://localhost/icdev/tv/api//g.php?217')
		.then(response => response.json())
		.then(data => 
		{
			console.log('Data fetched:', data);
			
			if (data.brand == 'IC')
			{
				document.getElementById('bs').style.display = 'none';
				document.getElementById('ic').style.display = 'block';
				document.getElementById('ic_route_number').innerText = data.route;
				document.getElementById('ic_route_desc').innerText = data.description;

			}
			else
			{
				document.getElementById('ic').style.display = 'none';
				document.getElementById('bs').style.display = 'block';
				document.getElementById('bs_route_number').innerText = data.route;
				document.getElementById('bs_route_desc').innerText = data.description;
			}
		})
		.catch(error => 
		{
			console.error('Error fetching data:', error);
		});
	} 
	else 
	{
		console.log('Cannot fetch data, you are offline');
		
		document.getElementById('ic').style.display = 'none';
		document.getElementById('bs').style.display = 'none';
		document.getElementById('offline').style.display = 'block';
	}
}

async function fetchData() 
{
	const screenId = document.getElementById('screen_id').value;

	const phpUrl = 'http://localhost/icdev/tv/api/g.php';

	const formData = { "screen_id": screenId };
	
	const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
	const result = await response.text()
	.then(result => 
	{
		if (result == 0)
		{
			// No data: show arrival screen
			document.getElementById('ic').style.display = 'none';
			document.getElementById('bs').style.display = 'none';
			document.getElementById('arrivals').style.display = 'block';
		}
		else 
		{
			const data = JSON.parse(result);
			console.log('Data fetched 1:', result);
			console.log('Data fetched:', data.route_no);
			
			if (data.brand == 'IC')
			{
				document.getElementById('bs').style.display = 'none';
				document.getElementById('ic').style.display = 'block';
				document.getElementById('arrivals').style.display = 'none';
				document.getElementById('ic_route_number').innerText = data.route_no;
				document.getElementById('ic_route_desc').innerText = data.route_desc;

			}
			else
			{
				document.getElementById('ic').style.display = 'none';
				document.getElementById('bs').style.display = 'block';
				document.getElementById('arrivals').style.display = 'none';
				document.getElementById('bs_route_number').innerText = data.route_no;
				document.getElementById('bs_route_desc').innerText = data.route_desc;
			}
		}
		
	});
}

init();
</script>