const fs = require('fs');
const puppeteer = require('puppeteer-core');
const BASE = 'https://dev.elmercadodeorigen.com';
const widths = [1440, 1200, 1024, 992, 991, 900, 821, 820, 768, 600, 390];
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    protocolTimeout: 120000,
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  const page = await browser.newPage();
  const results = [];
  const errors = [];
  try {
    await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
    await page.goto(`${BASE}/tienda/hidalgo-de-la-jara/?qa=resize-${Date.now()}`, { waitUntil: 'networkidle2', timeout: 90000 });
    await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});

    for (const width of widths) {
      await page.setViewport({ width, height: width <= 600 ? 844 : 1000, deviceScaleFactor: 1 });
      await sleep(420);
      const first = await page.evaluate(() => {
        const tabs = document.querySelector('#wcfmmp-store .tab_links');
        const toolbar = document.querySelector('#wcfmmp-store .elmercado-vendor-toolbar');
        const product = document.querySelector('#wcfmmp-store ul.products li.product');
        if (!tabs || !toolbar || !product) return null;
        const a = tabs.getBoundingClientRect();
        const b = toolbar.getBoundingClientRect();
        const c = product.getBoundingClientRect();
        return {
          tabsToolbar: Math.round(b.top - a.bottom),
          toolbarProducts: Math.round(c.top - b.bottom),
          toolbarY: Math.round(b.top * 10) / 10,
          productY: Math.round(c.top * 10) / 10,
          toolbarTransform: getComputedStyle(toolbar).transform,
          productsTransform: getComputedStyle(product.closest('ul.products')).transform,
          overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 3
        };
      });
      await sleep(180);
      const second = await page.evaluate(() => {
        const toolbar = document.querySelector('#wcfmmp-store .elmercado-vendor-toolbar');
        const product = document.querySelector('#wcfmmp-store ul.products li.product');
        if (!toolbar || !product) return null;
        return {
          toolbarY: Math.round(toolbar.getBoundingClientRect().top * 10) / 10,
          productY: Math.round(product.getBoundingClientRect().top * 10) / 10
        };
      });
      const row = { width, first, second };
      results.push(row);
      if (!first || !second) { errors.push(`${width}px: vendor elements missing`); continue; }
      if (first.overflow) errors.push(`${width}px: horizontal overflow`);
      if (first.toolbarTransform !== 'none') errors.push(`${width}px: toolbar transform ${first.toolbarTransform}`);
      if (first.productsTransform !== 'none') errors.push(`${width}px: products transform ${first.productsTransform}`);
      if (Math.abs(first.toolbarY - second.toolbarY) > 1 || Math.abs(first.productY - second.productY) > 1) errors.push(`${width}px: layout still moving after resize`);
      if (first.tabsToolbar < 12 || first.tabsToolbar > 40) errors.push(`${width}px: tabs→toolbar gap ${first.tabsToolbar}px`);
      if (first.toolbarProducts < 10 || first.toolbarProducts > 32) errors.push(`${width}px: toolbar→products gap ${first.toolbarProducts}px`);
    }
  } finally {
    await browser.close();
  }
  fs.writeFileSync('qa/vendor-resize-stability.json', JSON.stringify({ errors, results }, null, 2));
  if (errors.length) {
    console.error(errors.join('\n'));
    process.exitCode = 2;
  } else {
    console.log('VENDOR_RESIZE_STABLE');
  }
})();
