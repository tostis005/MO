'use strict';

const puppeteer = require('puppeteer-core');
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const fail = (message, data) => {
  throw new Error(`${message} ${JSON.stringify(data || {})}`);
};

(async () => {
  const base = process.env.BASE_URL || 'https://www.elmercadodeorigen.com';
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 3 });
    await page.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1');
    await page.goto(`${base}/tienda/1957/`, { waitUntil: 'networkidle2', timeout: 60000 });
    await page.waitForSelector('.mdo-vendor-order-button', { visible: true, timeout: 30000 });
    await sleep(1600);

    const before = await page.evaluate(() => {
      const button = document.querySelector('.mdo-vendor-order-button');
      const select = document.querySelector('.emo-catalog-toolbar-shared-010229 .woocommerce-ordering select');
      if (!button || !select) return null;
      const br = button.getBoundingClientRect();
      const sr = select.getBoundingClientRect();
      const ss = getComputedStyle(select);
      const hit = document.elementFromPoint(br.left + br.width / 2, br.top + br.height / 2);
      return {
        button: { left: br.left, top: br.top, width: br.width, height: br.height, text: button.textContent.trim() },
        native: {
          rectWidth: sr.width,
          rectHeight: sr.height,
          cssWidth: ss.width,
          cssHeight: ss.height,
          position: ss.position,
          opacity: ss.opacity,
          visibility: ss.visibility,
          pointer: ss.pointerEvents,
          aria: select.getAttribute('aria-hidden'),
          marker: select.dataset.mdoSheet010276 || '',
        },
        hitButton: hit === button || button.contains(hit),
        oldMenuCount: document.querySelectorAll('.mdo-vendor-order-menu').length,
      };
    });

    if (!before) fail('ordering controls missing');
    if (before.native.marker !== '1') fail('0.10.276 ordering owner is not active', before);
    if (before.native.opacity !== '0' || before.native.visibility !== 'hidden' || before.native.pointer !== 'none' || before.native.aria !== 'true') {
      fail('native select can still interfere with touch', before);
    }
    if (!before.hitButton) fail('ordering button is not the real tap target', before);
    if (before.oldMenuCount !== 0) fail('legacy floating menu is still present', before);

    await page.touchscreen.tap(
      before.button.left + before.button.width / 2,
      before.button.top + before.button.height / 2
    );
    await sleep(350);

    const opened = await page.evaluate(() => {
      const sheet = document.querySelector('.mdo-vendor-order-sheet:not([hidden])');
      if (!sheet) return null;
      const panel = sheet.querySelector('.mdo-vendor-order-sheet__panel');
      const select = document.querySelector('.woocommerce-ordering select');
      if (!panel || !select) return null;
      const sr = sheet.getBoundingClientRect();
      const pr = panel.getBoundingClientRect();
      const cs = getComputedStyle(sheet);
      const target = [...sheet.querySelectorAll('.mdo-vendor-order-option')].find(item => item.dataset.value !== select.value);
      if (!target) return null;
      const tr = target.getBoundingClientRect();
      return {
        sheet: {
          left: sr.left,
          top: sr.top,
          right: sr.right,
          bottom: sr.bottom,
          width: sr.width,
          height: sr.height,
          display: cs.display,
          visibility: cs.visibility,
          pointer: cs.pointerEvents,
        },
        panel: { top: pr.top, bottom: pr.bottom, height: pr.height },
        target: { value: target.dataset.value, left: tr.left, top: tr.top, width: tr.width, height: tr.height },
        viewport: {
          width: window.innerWidth,
          height: window.innerHeight,
          visualHeight: window.visualViewport ? window.visualViewport.height : null,
        },
      };
    });

    if (!opened) fail('bottom sheet did not open after a real touchscreen tap', before);
    if (opened.sheet.display === 'none' || opened.sheet.visibility !== 'visible' || opened.sheet.pointer === 'none') fail('bottom sheet is not visible', opened);
    if (opened.panel.top < -1 || opened.panel.bottom > opened.viewport.height + 2) fail('bottom sheet panel is outside viewport', opened);
    if (opened.target.top < -1 || opened.target.top + opened.target.height > opened.viewport.height + 2) fail('ordering option is outside viewport', opened);

    const oldUrl = page.url();
    await page.touchscreen.tap(
      opened.target.left + opened.target.width / 2,
      opened.target.top + opened.target.height / 2
    );
    await page.waitForFunction(
      (old, value) => location.href !== old && new URL(location.href).searchParams.get('orderby') === value,
      { timeout: 12000 },
      oldUrl,
      opened.target.value
    ).catch(() => {});
    await sleep(500);

    const newUrl = page.url();
    if (newUrl === oldUrl || new URL(newUrl).searchParams.get('orderby') !== opened.target.value) {
      fail('ordering option did not navigate', { oldUrl, newUrl, opened });
    }

    console.log(JSON.stringify({ ok: true, before, opened, oldUrl, newUrl }));
  } finally {
    await browser.close();
  }
})().catch(error => {
  console.error(JSON.stringify({ ok: false, error: String(error.stack || error) }));
  process.exit(1);
});
