<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Processing</title>
    
</head>
<body>
    <h1>Batch Processing</h1>
    <p>Processing batches, please wait...</p>
	<div id="output" style="display: block;"></div>
</body>
</html>
<script>
        let currentBatch = 0;
        const output = document.getElementById('output');
        let totalBatches = 9999;

        function processBatch() {
            if (currentBatch < totalBatches) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'process_batch.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) 
					{
						let res = xhr.responseText;
						console.log('Response: ' + res);
						totalBatches = res;
                        console.log('Batch ' + currentBatch + ' processed out of ' + totalBatches);
						output.innerHTML = 'Batch ' + currentBatch + ' processed out of ' + (totalBatches - 1);
						if (res != "0")
						{
							currentBatch++;
							processBatch(); // Process the next batch
						}
						else 
						{
							console.log('Batch ' + currentBatch + ' failed.');
						}
                    }
                };
                xhr.send('batch=' + currentBatch);
            } else {
                console.log('All batches processed.');
            }
        }

        window.onload = function () {
            processBatch(); // Start processing batches
        };
    </script>