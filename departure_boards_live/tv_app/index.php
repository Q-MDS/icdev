<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

// Must be passed in as parameter
$stop_serial = 0;
$screen_id = 0;

if (isset($_GET['s']))
{
	$screen_id = $_GET['s'];
}

function get_stop_serial()
{
	global $conn, $stop_serial, $screen_id;

	$cursor = ora_open($conn);
	
	$sql = "SELECT STOP_SERIAL FROM DEPARTURE_TVS WHERE SCREEN_ID = $screen_id";

	ora_parse($cursor, $sql);
	ora_exec($cursor);

	ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC);

	$stop_serial = $row['STOP_SERIAL'];

	ora_close($cursor);
}

get_stop_serial();
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
<div id="dpb" class="aspect-ratio-16-9" style="display: block">
		<div class="content">
			<div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; background-color: #ffffff;">
					<!-- *** Logo -->
					<img id="dpb_logo" src="ic_logo.svg" style="height: 80%;">	
				</div>
				<!-- *** bgColor -->
				<div id="dpb_banner" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; background-color: #F37721; font-size: 5.5vw; font-weight: bold; color: white">ARE YOU ON THE RIGHT COACH?</div>
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 20%; background-color: #ffffff;">
					<input type="text" id="screen_id" value="<?php echo $screen_id; ?>" style="display: none">
				</div>
			</div>
			<div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
				<div id="dpb_route_number" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #3A3A3B; font-size: 5.5vw; font-weight: bold" onclick="fetchData()">----</div>
				<div style="display: flex; flex-direction: row; width: 100%; height: 20%; background-color: #ffffff;">&nbsp;</div>
				<div id="dpb_route_desc" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #3A3A3B; font-size: 4.0vw; font-weight: bold">.... .... ....</div>
			</div>
		</div>
	</div>

	<!-- <div id="ic" class="aspect-ratio-16-9" style="display: block">
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
				<div id="ic_route_number" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #3A3A3B; font-size: 5.5vw; font-weight: bold" onclick="fetchData()">----</div>
				<div style="display: flex; flex-direction: row; width: 100%; height: 20%; background-color: #ffffff;">&nbsp;</div>
				<div id="ic_route_desc" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #3A3A3B; font-size: 4.0vw; font-weight: bold">.... .... ....</div>
			</div>
		</div>
	</div> -->

	<!-- <div id="bs" class="aspect-ratio-16-9" style="display: none">
		<div class="content">
			<div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; background-color: #ffffff;">
					<img src="bs_logo.svg" style="height: 80%;">	
				</div>
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #0A78BB; font-size: 5.5vw; font-weight: bold">ARE YOU ON THE RIGHT COACH?</div>
				<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 20%; background-color: #ffffff;">&nbsp;</div>
			</div>
			<div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
				<div id="bs_route_number" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #3A3A3B; font-size: 5.5vw; font-weight: bold">----</div>
				<div style="display: flex; flex-direction: row; width: 100%; height: 20%; background-color: #ffffff;">&nbsp;</div>
				<div id="bs_route_desc" style="display: flex; flex-direction: row; align-items: center; justify-content: center; width: 100%; height: 40%; color: white; background-color: #3A3A3B; font-size: 4.0vw; font-weight: bold">.... .... ....</div>
			</div>
		</div>
	</div> -->
	
	
	<div id="offline" class="aspect-ratio-16-9" style="display: none">
		<div class="content">
			<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; color: black;" onclick="isOffline()">Please check back later</div>
		</div>
	</div>

	<div id="arrivals" class="aspect-ratio-16-9" style="display: none">
		<div class="content">
			<iframe src="https://secure.intercape.co.za/ignite/index.php?c=no_auth&m=vdeparture_boards&type=1&stop=<?php echo $stop_serial; ?>" width="100%" height="100%" style="border: 0px;"></iframe>
		</div>
	</div>

</body>
</html>
<script>
baseUrl = window.location.protocol + "//" + window.location.hostname + "/move/";

if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register(baseUrl + 'departure_boards/tv_app/service-worker.js').then(function(registration) {
      console.log('ServiceWorker registration successful with scope: ', registration.scope);
    }, function(err) {
      console.log('ServiceWorker registration failed: ', err);
    });
  });
}

function init()
{
	console.log('You are: ' + navigator.onLine);
	setInterval(fetchData, 5000);
}

function isOffline()
{
	console.log('Offline check...');
	if (!navigator.onLine)
	{
		console.log('You are offline');
		document.getElementById('dps').style.display = 'none';
		// document.getElementById('bs').style.display = 'none';
		document.getElementById('offline').style.display = 'block';
	}
	else 
	{
		console.log('You are online');
		document.getElementById('dps').style.display = 'block';
		// document.getElementById('bs').style.display = 'none';
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
		
		fetch(baseUrl + '/tv/api//g.php?217')
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

	if (navigator.onLine) 
	{
		const phpUrl = baseUrl + 'departure_boards/api/g.php';

		const formData = { "screen_id": screenId };
		
		const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
		const result = await response.text()
		.then(result => 
		{
			if (result == 0)
			{
				// No data: show arrival screen
				// document.getElementById('ic').style.display = 'none';
				document.getElementById('dpb').style.display = 'none';
				document.getElementById('arrivals').style.display = 'block';
				document.getElementById('offline').style.display = 'none';
			}
			else 
			{
				const data = JSON.parse(result);
				const dpb = document.getElementById('dpb');
				const dpb_logo = document.getElementById('dpb_logo');
				const dpb_banner = document.getElementById('dpb_banner');
				const dpb_route_number = document.getElementById('dpb_route_number');
				const dpb_route_desc = document.getElementById('dpb_route_desc');

				document.getElementById('arrivals').style.display = 'none';

				dpb.style.display = 'block';
				dpb_route_number.innerText = data.route_no;
				dpb_route_desc.innerText = data.route_desc;
				if (data.route_desc.trim().length > 45)
				{
					dpb_route_desc.style.fontSize = '3.0vw';
				}
				else
				{
					dpb_route_desc.style.fontSize = '4.0vw';
				}

				switch(data.brand)
				{
					case 'IC':
					case 'IS':
					case 'IM':
					case 'IB':
					case 'AR':
					case 'ZZ':
						dpb_logo.src = 'ic_logo.svg';
						dpb_banner.style.backgroundColor = '#F37721';
					break;
					case 'BI':
						dpb_logo.src = 'bs_logo.svg';
						dpb_banner.style.backgroundColor = '#0A78BB';
					break;
				}
			}
		});
	}
	else 
	{
		console.log('Cannot fetch data, you are offline');
		
		document.getElementById('dpb').style.display = 'none';
		// document.getElementById('bs').style.display = 'none';
		document.getElementById('offline').style.display = 'block';
	}
}

init();
</script>