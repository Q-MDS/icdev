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
		let combinedData = '';
		let responseSent = false;
		// let lastMessageTime = Date.now();
        // const inactivityTimeout = 3000;

        client.on('Network.webSocketFrameReceived', ({ requestId, timestamp, response }) => {
            console.log('WebSocket Frame Received:', response.payloadData);
            messages.push({ type: 'received', data: response.payloadData });
			combinedData += response.payloadData;

			// Check if the combined data is valid JSON
            if (testJSON(combinedData) && !responseSent) {
                console.log('Complete JSON data received, closing browser.');
                responseSent = true; // Set the flag to true
                clearTimeout(timeout); // Clear the timeout
                client.removeAllListeners('Network.webSocketFrameReceived'); // Stop listening to further messages
                browser.close().then(() => {
                    res.json({ messages });
                });
            }
        });

		// Function to test if a string is valid JSON
        function testJSON(text) {
            if (typeof text !== "string") {
                return false;
            }
            try {
                JSON.parse(text);
                return true;
            } catch (error) {
                return false;
            }
        }

		// Keep the browser open until the complete JSON data is received
        // or a timeout occurs to prevent infinite waiting
        const timeout = setTimeout(() => {
            if (!responseSent) { // Check if the response has already been sent
                console.log('Timeout reached, closing browser.');
                responseSent = true; // Set the flag to true
                browser.close().then(() => {
                    res.json({ messages });
                });
            }
        }, 30000); // 30 seconds timeout
        
    } catch (error) {
        console.error(error);
        res.status(500).send('Error running capture');
    }
});

app.listen(3000, () => {
    console.log('Server is running on port 3000');
});