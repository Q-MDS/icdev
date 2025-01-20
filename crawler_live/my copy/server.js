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

        // page.on('console', msg => console.log('PAGE LOG:', msg.text()));

        await page.goto(url);

        const client = await page.target().createCDPSession();
        await client.send('Network.enable');
        await client.send('Page.enable');

        let messages = [];

        client.on('Network.webSocketFrameReceived', ({ requestId, timestamp, response }) => {
            console.log('WebSocket Frame Received:', response.payloadData);
            messages.push({ type: 'received', data: response.payloadData });
        });

        // client.on('Network.webSocketFrameSent', ({ requestId, timestamp, response }) => {
        //     console.log('WebSocket Frame Sent:', response.payloadData);
        //     messages.push({ type: 'sent', data: response.payloadData });
        // });

        // Keep the browser open for a while to capture messages
        await new Promise(resolve => setTimeout(resolve, 10000));

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