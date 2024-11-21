<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dparture Board</title>
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
<body>
	<div style="width: 100%; height: 100%; padding: 0px; margin: 0px; border: 0px;">
		<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; height: 100%;">
			<div style="display: flex; align-items: center; justify-content: center; flex: 1; height: 100%; box-sizing: border-box; border: 1px solid #ff0000;">
				<iframe id="board_left" src="ic.html" style="width: 100%; height: 100%; border: 0px;"></iframe>
			</div>
			<div style="display: flex; align-items: center; justify-content: center; flex: 1; height: 100%; box-sizing: border-box; border: 1px solid #ff0000;">
				<iframe id="board_right" src="bi.html" style="width: 100%; height: 100%; border: 0px;"></iframe>
			</div>
		</div>
	</div>
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
	// setInterval(fetchData, 5000);
}

function sendMessageToIframe(result) {
	console.log('Sending message to iframe...');
            const iframe = document.getElementById('board_left');
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

function sendMessageToRight(result) 
{
	console.log('Sending message to iframe...');
	const iframe = document.getElementById('board_right');
	// const title = "Hello, iframe!";
	// const title = result;
	// const routeNumber = "9855";
	// const message = {
	// 	title: title,
	// 	route_number: routeNumber
	// };
	const message = result;
	iframe.contentWindow.postMessage(message, '*');
}

        // Call the function to send a message to the iframe
		// sendMessageToIframe();

async function fetchData() 
{
	// const screenId = document.getElementById('screen_id').value;

	if (navigator.onLine) 
	{
		const phpUrl = baseUrl + 'departure_boards/api/t.php';
		// const formData = { "screen_id": screenId };
		const formData = { "screen_id": 123 };
		
		const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
		const result = await response.text()
		.then(result => 
		{
			console.log('Result:', result);
			const response = JSON.parse(result);
			const leftData = response.left;
			const title = "Booyaa";
			console.log('Response:', leftData);
			const rightData = response.right;
			sendMessageToIframe(leftData);
			sendMessageToRight(rightData);
		})
	}
}

init();
</script>