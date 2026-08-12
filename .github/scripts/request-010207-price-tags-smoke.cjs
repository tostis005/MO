const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 500) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010207', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!response || response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let i = 0; i < 30; i += 1) {
    await go(page, '/tienda/', 200);
    const ready = await page.evaluate(() => !!document.getElementById('elmercado-catalog-filter-system-final-010207'));
    if (ready) return;
    await sleep(5000);
  }
  throw new Error('0.10.207 not visible on staging');
}

async function inspect(page) {
  return page.evaluate(() => {
    const s = (node) => node ? getComputedStyle(node) : null;
    const box = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      const c = s(node);
      return { width:r.width, height:r.height, display:c.display, visibility:c.visibility, background:c.backgroundColor, color:c.color, fontSize:c.fontSize, fontWeight:c.fontWeight, fontFamily:c.fontFamily, borderColor:c.borderColor, borderRadius:c.borderRadius, minHeight:c.minHeight };
    };
    const root = document.querySelector('.widget_price_filter');
    const slider = root?.querySelector('.price_slider');
    const range = root?.querySelector('.ui-slider-range');
    const handle = root?.querySelector('.ui-slider-handle');
    const amount = root?.querySelector('.price_slider_amount');
    const button = root?.querySelector('.price_slider_amount .button');
    const label = root?.querySelector('.price_slider_amount .price_label');
    const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area');
    const tagSelectors = '.widget_product_tag_cloud,.widget_tag_cloud,.wc-block-product-tag-cloud,.wp-block-woocommerce-product-tag-cloud,.wp-block-tag-cloud,.tagcloud';
    const tagNodes = [...document.querySelectorAll(tagSelectors)].map((node) => ({ text:(node.textContent || '').replace(/\s+/g,' ').trim(), display:s(node).display, visibility:s(node).visibility }));
    const visibleTagNodes = tagNodes.filter((n) => n.display !== 'none' && n.visibility !== 'hidden');
    const visibleEtiquetas = sidebar ? [...sidebar.querySelectorAll('h1,h2,h3,h4,h5,h6,.widget-title,.widgettitle,.wp-block-heading')].filter((node) => /etiquetas/i.test((node.textContent || '').trim()) && s(node).display !== 'none' && s(node).visibility !== 'hidden').map((n) => n.textContent.trim()) : [];
    return {
      final207:!!document.getElementById('elmercado-catalog-filter-system-final-010207'),
      old206:!!document.getElementById('elmercado-catalog-filter-system-final-010206'),
      slider:box(slider), range:box(range), handle:box(handle), amount:box(amount), button:box(button), label:box(label),
      labelText:(label?.textContent || '').replace(/\s+/g,' ').trim(),
      visibleTagNodes, visibleEtiquetas
    };
  });
}

function assertPrice(name, x) {
  if (!x.final207 || x.old206) failures.push(`${name}: wrong filter system release`);
  if (!x.slider) failures.push(`${name}: price slider missing`);
  if (x.slider && x.slider.background !== 'rgb(223, 233, 227)') failures.push(`${name}: slider bg ${x.slider.background}`);
  if (x.range && x.range.background !== 'rgb(47, 125, 93)') failures.push(`${name}: active range bg ${x.range.background}`);
  if (x.handle && (Math.round(x.handle.width) !== 18 || Math.round(x.handle.height) !== 18)) failures.push(`${name}: handle ${x.handle.width}x${x.handle.height}`);
  if (x.handle && x.handle.borderColor !== 'rgb(47, 125, 93)') failures.push(`${name}: handle border ${x.handle.borderColor}`);
  if (x.label && x.label.fontSize !== '11.5px') failures.push(`${name}: price label size ${x.label.fontSize}`);
  if (x.label && x.label.fontWeight !== '700') failures.push(`${name}: price label weight ${x.label.fontWeight}`);
  if (x.label && x.label.color !== 'rgb(66, 86, 78)') failures.push(`${name}: price label color ${x.label.color}`);
  if (x.button && x.button.borderRadius !== '999px') failures.push(`${name}: price button radius ${x.button.borderRadius}`);
  if (x.visibleTagNodes.length || x.visibleEtiquetas.length) failures.push(`${name}: Etiquetas visible ${JSON.stringify({nodes:x.visibleTagNodes, headings:x.visibleEtiquetas})}`);
}

(async () => {
  fs.mkdirSync('qa', { recursive:true });
  const browser = await puppeteer.launch({ headless:true, executablePath:'/usr/bin/google-chrome', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  await page.setViewport({ width:1440, height:1200, deviceScaleFactor:1 });
  try {
    await waitForRelease(page);

    await go(page, '/tienda/?min_price=25&max_price=150', 650);
    report.shopInitial = await inspect(page);
    await sleep(2300);
    report.shopStable = await inspect(page);
    assertPrice('shop', report.shopStable);
    if (JSON.stringify(report.shopInitial) !== JSON.stringify(report.shopStable)) failures.push('shop price/tags presentation changed after load');
    await page.screenshot({ path:'qa/request-010207-shop-price.png', fullPage:true });

    await go(page, '/categoria-producto/jamones-paletas/?min_price=80&max_price=350', 650);
    report.categoryInitial = await inspect(page);
    await sleep(2300);
    report.categoryStable = await inspect(page);
    assertPrice('category', report.categoryStable);
    if (JSON.stringify(report.categoryInitial) !== JSON.stringify(report.categoryStable)) failures.push('category price/tags presentation changed after load');
    await page.screenshot({ path:'qa/request-010207-category-price.png', fullPage:true });

    await page.setViewport({ width:390, height:844, deviceScaleFactor:1 });
    await go(page, '/categoria-producto/jamones-paletas/?min_price=80&max_price=350', 500);
    const toggle = await page.$('.emo-mobile-filter-toggle,#emo-premium-filter-toggle');
    if (toggle) { await toggle.click(); await sleep(250); }
    report.mobile = await inspect(page);
    assertPrice('mobile', report.mobile);
    await page.screenshot({ path:'qa/request-010207-mobile-price.png', fullPage:true });

    fs.writeFileSync('qa/request-010207-report.json', JSON.stringify({ failures, report }, null, 2));
    if (failures.length) {
      console.error('REQUEST_010207_FAIL', JSON.stringify(failures));
      process.exitCode = 2;
    } else {
      console.log('REQUEST_010207_OK', JSON.stringify({ shop:report.shopStable, category:report.categoryStable, mobile:report.mobile }));
    }
  } finally {
    await browser.close();
  }
})();