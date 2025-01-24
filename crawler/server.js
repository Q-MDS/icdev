const express = require('express');
const puppeteer = require('puppeteer');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
app.use(bodyParser.json());
app.use(cors());

app.post('/run-capture', async (req, res) => 
{
    const { from, to, date } = req.body;
    const url = `https://computicket.com/travel/busses/search?from=${from}&to=${to}&date=${date}&adult=1&senior=0&child=0&student=0&sapsandf=0`;

    try 
	{
        // const browser = await puppeteer.launch({ headless: true });
		const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
        const page = await browser.newPage();

        await page.goto(url);

        const client = await page.target().createCDPSession();
        await client.send('Network.enable');
        await client.send('Page.enable');

        let messages = [];
		let responseSent = false;
		const targetString = '{"type":"avalibilityResponse","data":{"metadata":';

        client.on('Network.webSocketFrameReceived', ({ requestId, timestamp, response }) => 
		{
			// console.log('WebSocket Frame Received:', response.payloadData);

			// Check if the response contains the target string
            if (response.payloadData.includes(targetString) && !responseSent) 
			{
                console.log('Target string detected, closing browser.');
                responseSent = true; 
                clearTimeout(timeout);
                client.removeAllListeners('Network.webSocketFrameReceived'); // Stop listening to further messages

                // Extract the specific JSON data
                const jsonData = extractJsonData(response.payloadData, targetString);
                browser.close().then(() => 
				{
                    res.json({ jsonData });
					messages.push({ type: 'received', jsonData });
                });
            }
        });

		// Function to extract the specific JSON data
        function extractJsonData(data, targetString) 
		{
            const startIndex = data.indexOf(targetString);
            if (startIndex === -1) return null;

            let endIndex = startIndex;
            let braceCount = 0;
            let inString = false;

            for (let i = startIndex; i < data.length; i++) 
			{
                const char = data[i];
                if (char === '"' && data[i - 1] !== '\\') 
				{
                    inString = !inString;
                }
                if (!inString) 
				{
                    if (char === '{') braceCount++;
                    if (char === '}') braceCount--;
                }
                if (braceCount === 0) 
				{
                    endIndex = i + 1;
                    break;
                }
            }

            return JSON.parse(data.substring(startIndex, endIndex));
        }

		const timeout = setTimeout(() => 
		{
            if (!responseSent) 
			{
                console.log('Timeout reached, closing browser.');
                responseSent = true; // Set the flag to true
                browser.close().then(() => 
				{
                    res.json({ messages });
                });
            }
        }, 30000);
        
    } 
	catch (error) 
	{
        console.error(error);
        if (!res.headersSent) 
		{
            res.status(500).send('Error running capture');
        }
    }
});

app.listen(3000, () => 
{
    console.log('Server is running on port 3000');
});