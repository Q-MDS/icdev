const express = require('express');
const puppeteer = require('puppeteer');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
app.use(bodyParser.json());
app.use(cors());

app.post('/run-capture', async (req, res) => {
    const { from, to, date } = req.body;
    const url = `https://computicket.com/travel/busses/search?from=${from}&to=${to}&date=${date}&adult=1&senior=0&child=0&student=0&sapsandf=0`;

    try {
        const browser = await puppeteer.launch({ headless: true });
        const page = await browser.newPage();

        await page.goto(url);

        const client = await page.target().createCDPSession();
        await client.send('Network.enable');
        await client.send('Page.enable');

        let messages = [];
		let lastMessageTime = Date.now();
        const inactivityTimeout = 3000;

        client.on('Network.webSocketFrameReceived', ({ requestId, timestamp, response }) => {
            console.log('WebSocket Frame Received:', response.payloadData);
            messages.push({ type: 'received', data: response.payloadData });
        });

       // Function to check for inactivity
	   async function checkInactivity() 
	   {
			while (true) 
			{
				await new Promise(resolve => setTimeout(resolve, 500));
				if (Date.now() - lastMessageTime > inactivityTimeout) 
				{
					break;
				}
			}
		}

        // Keep the browser open for a while to capture messages
        // await new Promise(resolvse => setTimeout(resolve, 10000));
		await checkInactivity();

        await browser.close();
        res.json({ messages });
    } catch (error) {
        console.error(error);
        res.status(500).send('Error running capture');
    }
});

app.listen(3000, () => {
    console.log('Server is running on port 3000');
});