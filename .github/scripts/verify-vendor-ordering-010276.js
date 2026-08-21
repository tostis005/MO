'use strict';

const puppeteer = require('puppeteer-core');
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const fail = (message, data) => { throw new Error(`${message} ${JSON.stringify(data || {})}`); };

(async () => {
  const base = process.env.BASE_URL || 'https://www.elmercadodeorigen.com';
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setCacheEnabled(false);
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 3 });
    await page.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1');

    const selector = '.emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select';
    const snapshot = async () => page.evaluate(sel => {
      const select = document.querySelector(sel);
      const form = select?.closest('.woocommerce-ordering');
      if (!select || !form) return null;
      const r = select.getBoundingClientRect();
      const s = getComputedStyle(select);
      const f = getComputedStyle(form);
      const before = getComputedStyle(form, '::before');
      const after = getComputedStyle(form, '::after');
      const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
      return {
        url: location.href,
        rect: { left:r.left, top:r.top, width:r.width, height:r.height },
        select: {
          display:s.display, position:s.position, visibility:s.visibility, opacity:s.opacity,
          pointer:s.pointerEvents, width:s.width, height:s.height, borderTop:s.borderTopWidth,
          borderRight:s.borderRightWidth, borderBottom:s.borderBottomWidth, borderLeft:s.borderLeftWidth,
          borderRadius:s.borderRadius, background:s.backgroundColor, boxShadow:s.boxShadow,
          aria:select.getAttribute('aria-hidden'), disabled:select.disabled,
          nativeMarker:select.dataset.mdoNative010277 || '',
        },
        form: {
          borderTop:f.borderTopWidth, borderRight:f.borderRightWidth,
          borderBottom:f.borderBottomWidth, borderLeft:f.borderLeftWidth,
          background:f.backgroundColor, boxShadow:f.boxShadow,
          beforeDisplay:before.display, afterDisplay:after.display,
        },
        hit: { tag:hit?.tagName || '', isSelect:hit === select || select.contains(hit) },
        customCount: document.querySelectorAll('.mdo-vendor-order-button,.mdo-vendor-order-menu,.mdo-vendor-order-sheet').length,
        value: select.value,
        options: [...select.options].map(o => ({ value:o.value, text:o.textContent.trim() })),
      };
    }, selector);

    await page.goto(`${base}/tienda/`, { waitUntil:'networkidle2', timeout:60000 });
    await page.waitForSelector(selector, { visible:true, timeout:30000 });
    await sleep(800);
    const shop = await snapshot();
    if (!shop) fail('global shop ordering missing');

    await page.goto(`${base}/tienda/1957/`, { waitUntil:'networkidle2', timeout:60000 });
    await page.waitForSelector(selector, { visible:true, timeout:30000 });
    await sleep(1600);
    const vendor = await snapshot();
    if (!vendor) fail('vendor native ordering missing');

    if (vendor.customCount !== 0) fail('custom vendor ordering UI still exists', { shop, vendor });
    if (vendor.select.nativeMarker !== '1') fail('native vendor ordering owner is not active', vendor);
    if (vendor.select.visibility !== 'visible' || vendor.select.opacity !== '1' || vendor.select.pointer !== 'auto' || vendor.select.aria !== null || vendor.select.disabled) {
      fail('native vendor select is not directly interactive', vendor);
    }
    if (!vendor.hit.isSelect) fail('touch target is not the native select', vendor);
    if ([vendor.form.borderTop,vendor.form.borderRight,vendor.form.borderBottom,vendor.form.borderLeft].some(v => parseFloat(v) > 0)) {
      fail('ordering form has an extra border', vendor);
    }
    if ([vendor.select.borderTop,vendor.select.borderRight,vendor.select.borderBottom,vendor.select.borderLeft].some(v => Math.abs(parseFloat(v) - 1) > 0.1)) {
      fail('native select does not have exactly one 1px border', vendor);
    }
    if (Math.abs(vendor.rect.width - shop.rect.width) > 1 || Math.abs(vendor.rect.height - shop.rect.height) > 1 || vendor.select.borderRadius !== shop.select.borderRadius || vendor.select.background !== shop.select.background) {
      fail('vendor ordering visual geometry differs from global shop', { shop, vendor });
    }

    await page.evaluate(sel => {
      const select = document.querySelector(sel);
      window.__mdoNativeTap010277 = 0;
      select.addEventListener('click', () => { window.__mdoNativeTap010277 += 1; }, { capture:true });
    }, selector);

    await page.touchscreen.tap(vendor.rect.left + vendor.rect.width / 2, vendor.rect.top + vendor.rect.height / 2);
    await sleep(300);
    const tap = await page.evaluate(sel => ({
      clicks: window.__mdoNativeTap010277 || 0,
      activeIsSelect: document.activeElement === document.querySelector(sel),
    }), selector);
    if (tap.clicks < 1) fail('real touchscreen tap did not reach native select', { vendor, tap });

    const target = vendor.options.find(o => o.value && o.value !== vendor.value);
    if (!target) fail('no alternate ordering option available', vendor);
    const oldUrl = page.url();
    await page.select(selector, target.value).catch(() => {});
    await page.waitForFunction((old, value) => location.href !== old && new URL(location.href).searchParams.get('orderby') === value, { timeout:12000 }, oldUrl, target.value).catch(() => {});
    await sleep(500);
    const newUrl = page.url();
    if (newUrl === oldUrl || new URL(newUrl).searchParams.get('orderby') !== target.value) {
      fail('native ordering change did not navigate', { oldUrl, newUrl, target, vendor, tap });
    }

    console.log(JSON.stringify({ ok:true, shop, vendor, tap, target, oldUrl, newUrl }));
  } finally {
    await browser.close();
  }
})().catch(error => {
  console.error(JSON.stringify({ ok:false, error:String(error.stack || error) }));
  process.exit(1);
});
