const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 1400) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010201', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!response || response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let i = 0; i < 24; i += 1) {
    await go(page, '/categoria-producto/jamones-paletas/', 450);
    const ready = await page.evaluate(() => !!document.getElementById('elmercado-catalog-filter-visual-lock-010201'));
    if (ready) return;
    await sleep(5000);
  }
  throw new Error('0.10.201 not visible on staging');
}

async function snapshot(page) {
  return page.evaluate(() => {
    const css = (node, pseudo = null) => node ? getComputedStyle(node, pseudo) : null;
    const rect = (node) => node ? node.getBoundingClientRect() : null;
    const side = document.querySelector('#secondary.widget-area,.shop-widget-area');
    const context = document.getElementById('emo-category-context');
    const contextRow = context?.querySelector('.emo-category-context__row');
    const vendorRows = [...document.querySelectorAll('#emo-global-vendor-filter .emo-global-vendor-filter__item')];
    const categoryRows = [...document.querySelectorAll('.widget_product_categories li')];
    const specificRows = [...document.querySelectorAll('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item')];
    const vendorLinks = vendorRows.map((row) => row.querySelector(':scope > a')).filter(Boolean);
    const categoryLinks = categoryRows.map((row) => row.querySelector(':scope > a')).filter(Boolean);
    const activeVendor = document.querySelector('#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active');

    const rowMetrics = (rows) => rows.slice(0, 5).map((row) => {
      const r = rect(row);
      const s = css(row);
      const link = row.querySelector(':scope > a');
      const ls = css(link);
      return {
        height: r ? Number(r.height.toFixed(1)) : 0,
        minHeight: s?.minHeight || '',
        paddingTop: s?.paddingTop || '',
        paddingBottom: s?.paddingBottom || '',
        linkPaddingTop: ls?.paddingTop || '',
        linkPaddingBottom: ls?.paddingBottom || '',
      };
    });

    const arrowState = (links) => links.slice(0, 8).map((link) => ({
      text: (link.textContent || '').trim(),
      after: css(link, '::after')?.content || '',
      icons: link.querySelectorAll('svg,i,.arrow,.caret,.chevron,.woostify-svg-icon').length,
    }));

    const sidebarStyle = css(side);
    const contextStyle = css(context);
    const contextRowStyle = css(contextRow);
    const activeStyle = css(activeVendor);

    return {
      sidebar: side ? {
        background: sidebarStyle.backgroundColor,
        borderTopWidth: sidebarStyle.borderTopWidth,
        borderRadius: sidebarStyle.borderRadius,
        boxShadow: sidebarStyle.boxShadow,
      } : null,
      context: context ? {
        background: contextStyle.backgroundColor,
        borderTopWidth: contextStyle.borderTopWidth,
        borderRadius: contextStyle.borderRadius,
        boxShadow: contextStyle.boxShadow,
        innerBackground: contextRowStyle?.backgroundColor || '',
        innerBorderTopWidth: contextRowStyle?.borderTopWidth || '',
        innerBorderRadius: contextRowStyle?.borderRadius || '',
      } : null,
      vendorRows: rowMetrics(vendorRows),
      categoryRows: rowMetrics(categoryRows),
      specificRows: rowMetrics(specificRows),
      vendorArrows: arrowState(vendorLinks),
      categoryArrows: arrowState(categoryLinks),
      activeVendor: activeVendor ? {
        background: activeStyle.backgroundColor,
        borderLeftWidth: activeStyle.borderLeftWidth,
        boxShadow: activeStyle.boxShadow,
        borderRadius: activeStyle.borderRadius,
        text: (activeVendor.textContent || '').trim(),
      } : null,
      firstVendorHref: vendorLinks[0]?.href || '',
    };
  });
}

function noArrows(label, items) {
  for (const item of items || []) {
    const after = String(item.after || '').trim();
    if (!['none', 'normal', '""', "''", ''].includes(after)) failures.push(`${label}: pseudo arrow remains on ${item.text}: ${after}`);
    if (item.icons !== 0) failures.push(`${label}: icon arrow remains on ${item.text}`);
  }
}

function compareRows(labelA, a, labelB, b) {
  if (!a?.length || !b?.length) return;
  const aa = a[0];
  const bb = b[0];
  for (const key of ['minHeight','paddingTop','paddingBottom','linkPaddingTop','linkPaddingBottom']) {
    if (aa[key] !== bb[key]) failures.push(`${labelA}/${labelB}: ${key} differs (${aa[key]} vs ${bb[key]})`);
  }
  if (Math.abs(aa.height - bb.height) > 1.5) failures.push(`${labelA}/${labelB}: rendered row height differs (${aa.height} vs ${bb.height})`);
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({ headless: true, executablePath: '/usr/bin/google-chrome', args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 1500, deviceScaleFactor: 1 });

  try {
    await waitForRelease(page);

    await go(page, '/tienda/');
    report.shop = await snapshot(page);
    await page.screenshot({ path: 'qa/request-010201-shop.png', fullPage: true });
    if (!report.shop.sidebar) failures.push('shop: sidebar missing');
    if (report.shop.sidebar?.background !== 'rgb(255, 255, 255)') failures.push(`shop: sidebar background is ${report.shop.sidebar?.background}`);
    if (report.shop.sidebar?.borderTopWidth !== '1px') failures.push(`shop: sidebar border is ${report.shop.sidebar?.borderTopWidth}`);
    noArrows('shop vendor', report.shop.vendorArrows);
    noArrows('shop categories', report.shop.categoryArrows);
    compareRows('shop vendor', report.shop.vendorRows, 'shop categories', report.shop.categoryRows);

    await go(page, '/categoria-producto/jamones-paletas/');
    report.jamones = await snapshot(page);
    await page.screenshot({ path: 'qa/request-010201-jamones.png', fullPage: true });
    if (report.jamones.sidebar?.background !== 'rgb(255, 255, 255)') failures.push(`jamones: sidebar background is ${report.jamones.sidebar?.background}`);
    if (report.jamones.sidebar?.borderTopWidth !== '1px') failures.push(`jamones: sidebar border is ${report.jamones.sidebar?.borderTopWidth}`);
    if (!report.jamones.context) failures.push('jamones: category context missing');
    if (report.jamones.context?.background !== 'rgba(0, 0, 0, 0)') failures.push(`jamones: outer context background is ${report.jamones.context?.background}`);
    if (report.jamones.context?.borderTopWidth !== '0px') failures.push(`jamones: outer context border is ${report.jamones.context?.borderTopWidth}`);
    if (report.jamones.context?.innerBackground === 'rgba(0, 0, 0, 0)') failures.push('jamones: inner category card lost its background');
    if (report.jamones.context?.innerBorderTopWidth === '0px') failures.push('jamones: inner category card lost its border');
    noArrows('jamones vendor', report.jamones.vendorArrows);
    compareRows('jamones vendor', report.jamones.vendorRows, 'jamones specific', report.jamones.specificRows);

    const vendorHref = report.jamones.firstVendorHref;
    if (!vendorHref) {
      failures.push('jamones: no vendor link available for active-state check');
    } else {
      await page.goto(vendorHref + (vendorHref.includes('?') ? '&' : '?') + 'request-010201-active=' + Date.now(), { waitUntil: 'domcontentloaded', timeout: 60000 });
      await sleep(1300);
      report.selectedVendor = await snapshot(page);
      await page.screenshot({ path: 'qa/request-010201-selected-vendor.png', fullPage: true });
      const active = report.selectedVendor.activeVendor;
      if (!active) failures.push('selected vendor: active row missing');
      if (active?.background !== 'rgb(226, 239, 231)') failures.push(`selected vendor: highlight is ${active?.background}`);
      if (active?.borderLeftWidth !== '0px') failures.push(`selected vendor: left border remains ${active?.borderLeftWidth}`);
      if (active?.boxShadow !== 'none') failures.push(`selected vendor: box shadow/bar remains ${active?.boxShadow}`);
      noArrows('selected vendor', report.selectedVendor.vendorArrows);
    }

    fs.writeFileSync('qa/request-010201-report.json', JSON.stringify({ failures, report }, null, 2));
    if (failures.length) {
      console.error('REQUEST_010201_FAIL', JSON.stringify(failures));
      process.exitCode = 2;
    } else {
      console.log('REQUEST_010201_OK', JSON.stringify(report));
    }
  } finally {
    await browser.close();
  }
})();
