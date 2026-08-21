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

  const selector = '.emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select[name="orderby"]';

  try {
    const page = await browser.newPage();
    await page.setCacheEnabled(false);

    const snapshot = async () => page.evaluate(sel => {
      const select = document.querySelector(sel);
      const form = select?.closest('.woocommerce-ordering');
      if (!select || !form) return null;
      const r = select.getBoundingClientRect();
      const fr = form.getBoundingClientRect();
      const s = getComputedStyle(select);
      const f = getComputedStyle(form);
      const before = getComputedStyle(form, '::before');
      const after = getComputedStyle(form, '::after');
      const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
      return {
        url: location.href,
        rect: { left:r.left, top:r.top, width:r.width, height:r.height },
        formRect: { left:fr.left, top:fr.top, width:fr.width, height:fr.height },
        select: {
          display:s.display, position:s.position, visibility:s.visibility, opacity:s.opacity,
          pointer:s.pointerEvents, width:s.width, height:s.height,
          borderTop:s.borderTopWidth, borderRight:s.borderRightWidth,
          borderBottom:s.borderBottomWidth, borderLeft:s.borderLeftWidth,
          borderRadius:s.borderRadius, background:s.backgroundColor, boxShadow:s.boxShadow,
          color:s.color, fontSize:s.fontSize, fontWeight:s.fontWeight,
          paddingLeft:s.paddingLeft, paddingRight:s.paddingRight,
          aria:select.getAttribute('aria-hidden'), disabled:select.disabled,
          nativeMarker:select.dataset.mdoNative010277 || '',
          parityMarker:select.dataset.mdoNativeParity || '',
          className:select.className,
        },
        form: {
          borderTop:f.borderTopWidth, borderRight:f.borderRightWidth,
          borderBottom:f.borderBottomWidth, borderLeft:f.borderLeftWidth,
          background:f.backgroundColor, boxShadow:f.boxShadow,
          beforeDisplay:before.display, afterDisplay:after.display,
        },
        hit: { tag:hit?.tagName || '', isSelect:hit === select || select.contains(hit) },
        customCount: document.querySelectorAll('.mdo-vendor-order-button,.mdo-vendor-order-menu,.mdo-vendor-order-sheet').length,
        enhancerCount: form.querySelectorAll('.select2,.select2-container,.chosen-container,.nice-select,.selectize-control').length,
        value: select.value,
        options: [...select.options].map(o => ({ value:o.value, text:o.textContent.trim() })),
      };
    }, selector);

    const gotoAndSnapshot = async url => {
      await page.goto(url, { waitUntil:'networkidle2', timeout:60000 });
      await page.waitForSelector(selector, { visible:true, timeout:30000 });
      await sleep(1400);
      return snapshot();
    };

    const compareParity = (label, shop, vendor) => {
      if (!vendor) fail(`${label}: vendor ordering missing`);
      if (vendor.customCount !== 0 || vendor.enhancerCount !== 0) fail(`${label}: duplicate/custom ordering UI remains`, vendor);
      if (vendor.select.nativeMarker !== '1' || vendor.select.parityMarker !== '010279') fail(`${label}: 0.10.279 native owner is not active`, vendor);
      if (vendor.select.visibility !== 'visible' || vendor.select.opacity !== '1' || vendor.select.pointer !== 'auto' || vendor.select.aria !== null || vendor.select.disabled) {
        fail(`${label}: native vendor select is not directly interactive`, vendor);
      }
      if (!vendor.hit.isSelect) fail(`${label}: pointer/touch target is not the native select`, vendor);
      if ([vendor.form.borderTop,vendor.form.borderRight,vendor.form.borderBottom,vendor.form.borderLeft].some(v => parseFloat(v) > 0)) {
        fail(`${label}: ordering form has an extra border`, vendor);
      }
      if ([vendor.select.borderTop,vendor.select.borderRight,vendor.select.borderBottom,vendor.select.borderLeft].some(v => Math.abs(parseFloat(v) - 1) > 0.1)) {
        fail(`${label}: native select does not have exactly one 1px border`, vendor);
      }

      const geometryDiff = {
        width: Math.abs(vendor.rect.width - shop.rect.width),
        height: Math.abs(vendor.rect.height - shop.rect.height),
      };
      const styleKeys = ['borderRadius','background','boxShadow','color','fontSize','fontWeight','paddingLeft','paddingRight'];
      const styleDiff = Object.fromEntries(styleKeys.filter(key => vendor.select[key] !== shop.select[key]).map(key => [key, { shop:shop.select[key], vendor:vendor.select[key] }]));
      if (geometryDiff.width > 1 || geometryDiff.height > 1 || Object.keys(styleDiff).length) {
        fail(`${label}: producer ordering is not visually identical to global shop`, { geometryDiff, styleDiff, shop, vendor });
      }
      if (JSON.stringify(vendor.options) !== JSON.stringify(shop.options)) {
        fail(`${label}: producer ordering options differ from global shop`, { shop:shop.options, vendor:vendor.options });
      }
    };

    const results = {};
    const viewports = [
      { name:'mobile', viewport:{ width:390, height:844, isMobile:true, hasTouch:true, deviceScaleFactor:3 }, ua:'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1' },
      { name:'desktop', viewport:{ width:1440, height:1000, isMobile:false, hasTouch:false, deviceScaleFactor:1 }, ua:'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36' },
    ];

    for (const config of viewports) {
      await page.setViewport(config.viewport);
      await page.setUserAgent(config.ua);

      const shop = await gotoAndSnapshot(`${base}/tienda/?_mdo_verify=${Date.now()}`);
      if (!shop) fail(`${config.name}: global shop ordering missing`);

      const vendor1957 = await gotoAndSnapshot(`${base}/tienda/1957/?_mdo_verify=${Date.now()}`);
      compareParity(`${config.name}/1957`, shop, vendor1957);

      await page.evaluate(sel => {
        const select = document.querySelector(sel);
        window.__mdoNativeClick010279 = 0;
        select.addEventListener('click', () => { window.__mdoNativeClick010279 += 1; }, { capture:true });
      }, selector);

      if (config.viewport.hasTouch) {
        await page.touchscreen.tap(vendor1957.rect.left + vendor1957.rect.width / 2, vendor1957.rect.top + vendor1957.rect.height / 2);
      } else {
        await page.mouse.click(vendor1957.rect.left + vendor1957.rect.width / 2, vendor1957.rect.top + vendor1957.rect.height / 2);
      }
      await sleep(300);
      const clickState = await page.evaluate(sel => ({
        clicks: window.__mdoNativeClick010279 || 0,
        activeIsSelect: document.activeElement === document.querySelector(sel),
      }), selector);
      if (clickState.clicks < 1) fail(`${config.name}: real click/tap did not reach native select`, { vendor1957, clickState });

      const target = vendor1957.options.find(o => o.value && o.value !== vendor1957.value);
      if (!target) fail(`${config.name}: no alternate ordering option available`, vendor1957);
      const oldUrl = page.url();
      await page.select(selector, target.value).catch(() => {});
      await page.waitForFunction((old, value) => location.href !== old && new URL(location.href).searchParams.get('orderby') === value, { timeout:12000 }, oldUrl, target.value).catch(() => {});
      await sleep(700);
      const newUrl = page.url();
      if (newUrl === oldUrl || new URL(newUrl).searchParams.get('orderby') !== target.value) {
        fail(`${config.name}: ordering change did not navigate`, { oldUrl, newUrl, target, vendor1957, clickState });
      }

      const vendorHidalgo = await gotoAndSnapshot(`${base}/tienda/hidalgo-de-la-jara/?_mdo_verify=${Date.now()}`);
      compareParity(`${config.name}/hidalgo`, shop, vendorHidalgo);

      results[config.name] = { shop, vendor1957, vendorHidalgo, clickState, target, oldUrl, newUrl };
    }

    console.log(JSON.stringify({ ok:true, revision:'010279', results }));
  } finally {
    await browser.close();
  }
})().catch(error => {
  console.error(JSON.stringify({ ok:false, error:String(error.stack || error) }));
  process.exit(1);
});
