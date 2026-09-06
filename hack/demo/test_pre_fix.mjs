import puppeteer from 'puppeteer';
const browser = await puppeteer.launch(process.getuid?.() === 0 ? { args: ['--no-sandbox'] } : {});
const page = await browser.newPage();
await page.setViewport({ width: 900, height: 700 });
await page.goto('http://localhost:8080/index.php/Main_Page', { waitUntil: 'networkidle0' });
await page.addStyleTag({ url: 'https://fonts.googleapis.com/css2?family=Noto+Sans+KR&family=Noto+Serif+KR&display=swap' });
await page.addStyleTag({ content: `
  body { font-family: 'Noto Sans KR', sans-serif !important; }
  mjx-utext { font-family: 'Noto Serif KR', serif !important; }
  pre, code { font-family: monospace, 'Noto Sans KR' !important; }
` });
await page.evaluate(() => document.fonts.ready);
await page.waitForNetworkIdle({ idleTime: 500 }).catch(() => {});
await page.screenshot({ path: '/tmp/pre_fix.png', fullPage: true });
await browser.close();
