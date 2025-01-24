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
		let responseSent = false;
		// const targetString = '}}]}}"}]}';
		const targetString = '}}]}}';

        client.on('Network.webSocketFrameReceived', ({ requestId, timestamp, response }) => {
            console.log('WebSocket Frame Received:', response.payloadData);
            messages.push({ type: 'received', data: response.payloadData });

			// Check if the response contains the target string
            if (response.payloadData.includes(targetString) && !responseSent) {
                console.log('Target string detected, closing browser.');
                responseSent = true; // Set the flag to true
                clearTimeout(timeout); // Clear the timeout
                client.removeAllListeners('Network.webSocketFrameReceived'); // Stop listening to further messages
                browser.close().then(() => {
                    res.json({ messages });
                });
            }
        });

		const timeout = setTimeout(() => {
            if (!responseSent) { // Check if the response has already been sent
                console.log('Timeout reached, closing browser.');
                responseSent = true; // Set the flag to true
                browser.close().then(() => {
                    res.json({ messages });
                });
            }
        }, 10000);
        
    } catch (error) {
        console.error(error);
        if (!res.headersSent) {
            res.status(500).send('Error running capture');
        }
    }
});

app.listen(3000, () => {
    console.log('Server is running on port 3000');
});