<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };

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
	body, html {
		margin: 0;
		padding: 0;
		height: 100%;
		font-family: Arial, sans-serif;
		background: url('https://www.intercape.co.za/wp-content/uploads/2024/11/Background-Image.jpg') no-repeat center center;
		background-size: cover; /* Cover the entire area */
		overflow: hidden; /* Prevent scrolling */
	}

	.pattern {
		width: 100%;
		position: relative;
		z-index: 1; /* Ensure it is above the gradient */
		display: flex;
		padding-top:10vh;
		flex-direction: column;
		justify-content: center; /* Center vertically */
		align-items: center; /* Center horizontally */
	}

	h1, h2, h3 {
		color: #fff;
		text-transform: uppercase;
		margin: 0px 0px 6px 0px;
		z-index: 3; /* Ensure text is above the overlay */
		
	}

	h1 {
		font-size: 3.6em;
		line-height:1em;
		align-items: center;
		text-shadow: 3px 3px 3px rgba(0, 0, 0, 0.75);
		font-style: italic;
		font-weight: 900;
		padding-bottom: 20px;

	}

	h2 {
		font-size: 7em;
		/* line-height:1em; */
		align-items: center; 
		justify-content: center;
		color:#000;
		font-weight: 900;
	}
	
	h3 {
		font-size: 7em;
		line-height:1em;
		align-items: center; 
		color:#000;
		font-weight: 900;
	
	}

	.logo-bar {
		min-height: 12vh;
		position: absolute;
		bottom: 0;
		background: white;
		text-align: center;
		padding: 2vh 0;
		z-index: 1000;
		box-shadow: 3px -3px 5px rgba(0, 0, 0, 0.75); /* Drop shadow */
		border-radius: 8vh 8vh 0px 0px;
		width: 60%;
		margin-left: 20%;
	}

	.logo-bar img {
		width: 40vw; /* 60% width */
		max-width: 40vw; /* Optional max width */
		height: auto;
	}

	.header{
		position: sticky;
		text-align:center;
		width: 60%;
		margin-left: 20%;
		border-radius: 0px 0px 8vh 8vh;
		padding: 2vh 0;
		z-index: 1000;
		box-shadow: 3px 3px 5px rgba(0, 0, 0, 0.75); 
		background:#fff;
		min-height: 16vh;
	}

	.header img {
		width: 40vw; /* 60% width */
		max-width: 40vw; /* Optional max width */
		height: auto;
	}
</style>
</head>
<body onload="init();">
	<!-- Placeholder for IC/BI content -->
    <div id="ic-container"></div>
    <div id="bi-container"></div>
	
	<div id="offline" style="display: none; align-items: center; justify-content: center; height: 100%;">
		<div>
			<div style="font-size: 7em; font-style: italic; font-weight: 900; color: #FFF; text-shadow: 3px 3px 3px rgba(0, 0, 0, 0.75);">Please check back later</div>
			<input type="text" id="screen_id" value="<?php echo $screen_id; ?>" style="display: none">
		</div>
	</div>

	<div id="arrivals" style="display: none; height: 100%; width: 100%;">
		<div style="width: 100%">
			<iframe src="https://secure.intercape.co.za/ignite/index.php?c=no_auth&m=vdeparture_boards&type=1&stop=<?php echo $stop_serial; ?>" width="100%" height="100%" style="border: 0px;"></iframe>
		</div>
	</div>
</body>
</html>
<script>
baseUrl = window.location.protocol + "//" + window.location.hostname + "/noauth/";

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
	loadICContent();
	loadBIContent();
}

function loadICContent() 
{
	fetch('inc_ic.html')
	.then(response => response.text())
	.then(data => 
	{
		console.log('IC content loaded:', data);
		document.getElementById('ic-container').innerHTML = data;
	})
	.catch(error => console.error('Error loading IC content:', error));
}

function loadBIContent() 
{
	fetch('inc_bi.html')
	.then(response => response.text())
	.then(data => 
	{
		console.log('BI content loaded:', data);
		document.getElementById('bi-container').innerHTML = data;
	})
	.catch(error => console.error('Error loading IC content:', error));
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
				document.body.style.backgroundColor = "#333";
				document.getElementById('ic').style.display = 'none';
				document.getElementById('bi').style.display = 'none';
				document.getElementById('offline').style.display = 'none';
				document.getElementById('arrivals').style.display = 'flex';
			}
			else 
			{
				const data = JSON.parse(result);

				document.getElementById('arrivals').style.display = 'none';
				document.getElementById('offline').style.display = 'none';

				switch(data.brand)
				{
					case 'IC':
					case 'IS':
					case 'IM':
					case 'IB':
					case 'AR':
					case 'ZZ':
						document.body.style.background = "url('https://www.intercape.co.za/wp-content/uploads/2024/11/Background-Image.jpg') no-repeat center center";
						document.body.style.backgroundSize = "cover";
						document.getElementById('bi').style.display = 'none';
						document.getElementById('offline').style.display = 'none';
						document.getElementById('arrivals').style.display = 'none';
						document.getElementById('ic').style.display = 'block';
						if (data.route_desc.trim().length > 35)
						{
							document.getElementById('ic_route_desc').style.fontSize = '3rem';
						}
						else
						{
							document.getElementById('ic_route_desc').style.fontSize = '4rem';
						}
						document.getElementById('ic_route_desc').innerText = data.route_desc;
						document.getElementById('ic_route_number').innerText = data.route_no;
					break;
					case 'BI':
						console.log('111');
						document.body.style.background = "url('https://www.bigskyintercity.co.za/wp-content/uploads/2024/11/BSI-pattern.jpg') no-repeat center center";
						document.body.style.backgroundSize = "cover";
						document.getElementById('ic').style.display = 'none';
						document.getElementById('offline').style.display = 'none';
						document.getElementById('arrivals').style.display = 'none';
						document.getElementById('bi').style.display = 'block';
						if (data.route_desc.trim().length > 35)
						{
							document.getElementById('bi_route_desc').style.fontSize = '3rem';
						}
						else
						{
							document.getElementById('bi_route_desc').style.fontSize = '4rem';
						}
						document.getElementById('bi_route_desc').innerText = data.route_desc;
						document.getElementById('bi_route_number').innerText = data.route_no;
					break;
					default:
						document.body.style.background = "url('https://www.intercape.co.za/wp-content/uploads/2024/11/Background-Image.jpg') no-repeat center center";
						document.body.style.backgroundSize = "cover";
						document.getElementById('bi').style.display = 'none';
						document.getElementById('offline').style.display = 'none';
						document.getElementById('arrivals').style.display = 'none';
						document.getElementById('ic').style.display = 'block';
						if (data.route_desc.trim().length > 35)
						{
							document.getElementById('ic_route_desc').style.fontSize = '3rem';
						}
						else
						{
							document.getElementById('ic_route_desc').style.fontSize = '4rem';
						}
						document.getElementById('ic_route_desc').innerText = data.route_desc;
						document.getElementById('ic_route_number').innerText = data.route_no;
				}
			}
		});
	}
	else 
	{
		console.log('Cannot fetch data, you are offline');
		
		document.getElementById('ic').style.display = 'none';
		document.getElementById('bi').style.display = 'none';
		document.getElementById('arrivals').style.display = 'none';
		document.getElementById('offline').style.display = 'flex';
	}
}

// init();
</script>
