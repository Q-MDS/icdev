const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({ headless: false });
  const page = await browser.newPage();

//   page.on('console', msg => console.log('PAGE LOG:', msg.text()));

  await page.goto('https://computicket.com/travel/busses/search?from=ZAZABUTTERWORTH&to=ZAZAJOHANNESBURG&date=2025-01-17&adult=1&senior=0&child=0&student=0&sapsandf=0');

  const client = await page.target().createCDPSession();
  await client.send('Network.enable');
  await client.send('Page.enable');

  client.on('Network.webSocketFrameReceived', ({ requestId, timestamp, response }) => {
    console.log('WebSocket Frame Received:', response.payloadData);
  });

//   client.on('Network.webSocketFrameSent', ({ requestId, timestamp, response }) => {
//     console.log('WebSocket Frame Sent:', response.payloadData);
//   });

  // Keep the browser open
  await new Promise(resolve => setTimeout(resolve, 60000));

  await browser.close();
})();