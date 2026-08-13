const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = process.env.BASE_URL || 'https://dev.elmercadodeorigen.com';
const parityPayload = JSON.parse(process.env.ATTRIBUTE_PARITY_JSON || '{}');
const expectedShopTotal = Number(parityPayload?.totals?.public_shop || 0);
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 500) {
  const url = new URL(path, BASE);
  url.searchParams.set('catalog-toolbar-010223', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => null);
  if (response && response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response.status()}`);
  await page.waitForSelector('body', { timeout: 10000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let i = 0; i < 30; i += 1) {
    await go(page, '/tienda/', 200);
    const ready = await page.evaluate(() => {
      const styleMarker = !!document.getElementById('elmercado-catalog-toolbar-mobile-price-fix-010222');
      const categoryWidget = document.querySelector('.widget_product_categories');
      const categoryCounts = categoryWidget ? categoryWidget.querySelectorAll('li.cat-item > .count').length : 0;
      return styleMarker && categoryCounts > 0;
    });
    if (ready) return;
    await sleep(3000);
  }
  throw new Error('0.10.223 category-count release not visible');
}

async function inspectToolbar(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      if (!node) return false;
      const c = getComputedStyle(node);
      const r = node.getBoundingClientRect();
      return c.display !== 'none' && c.visibility !== 'hidden' && Number(c.opacity || 1) !== 0 && r.width > 0 && r.height > 0;
    };
    const box = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      const c = getComputedStyle(node);
      return { left:r.left, right:r.right, top:r.top, bottom:r.bottom, width:r.width, height:r.height, display:c.display, visibility:c.visibility };
    };
    const exact = document.querySelector('.emo-catalog-result-count-010220');
    const legacy = document.querySelector('.emo-catalog-result-count-010218');
    const ordering = document.querySelector('.woocommerce-ordering');
    const select = ordering?.querySelector('select.orderby,select');
    const exactText = (exact?.textContent || '').replace(/\s+/g, ' ').trim();
    const totalMatch = exactText.match(/^(\d+)\s+resultados?$/i);
    return {
      release: !!document.getElementById('elmercado-catalog-toolbar-mobile-price-fix-010222'),
      exactVisible: visible(exact),
      exactText,
      exactTotal: totalMatch ? Number(totalMatch[1]) : null,
      legacyVisible: visible(legacy),
      orderingVisible: visible(ordering),
      selectVisible: visible(select),
      optionCount: select?.options?.length || 0,
      ordering: box(ordering),
      select: box(select),
      viewport: { width: innerWidth, height: innerHeight }
    };
  });
}

async function inspectMobilePrice(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      if (!node) return false;
      const c = getComputedStyle(node);
      const r = node.getBoundingClientRect();
      return c.display !== 'none' && c.visibility !== 'hidden' && r.width > 0 && r.height > 0;
    };
    const root = document.querySelector('.emo-mobile-filter-content .widget_price_filter');
    const slider = root?.querySelector('.price_slider,.ui-slider-horizontal');
    const handles = [...(root?.querySelectorAll('.ui-slider-handle') || [])];
    const sliderRect = slider?.getBoundingClientRect();
    const handleData = handles.map((handle) => {
      const rect = handle.getBoundingClientRect();
      const c = getComputedStyle(handle);
      return {
        visible: visible(handle),
        centerY: rect.top + rect.height / 2,
        width: rect.width,
        height: rect.height,
        top: c.top,
        marginTop: c.marginTop,
        transform: c.transform,
        boxSizing: c.boxSizing
      };
    });
    const trackCenterY = sliderRect ? sliderRect.top + sliderRect.height / 2 : null;
    return {
      drawerVisible: visible(document.querySelector('#emo-premium-filter-shell,.emo-mobile-filter-shell')),
      sliderVisible: visible(slider),
      trackCenterY,
      trackHeight: sliderRect?.height || 0,
      handles: handleData,
      alignmentDeltas: trackCenterY === null ? [] : handleData.map((h) => Math.abs(h.centerY - trackCenterY))
    };
  });
}

async function inspectCategoryCounts(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      if (!node) return false;
      const c = getComputedStyle(node);
      const r = node.getBoundingClientRect();
      return c.display !== 'none' && c.visibility !== 'hidden' && Number(c.opacity || 1) !== 0 && r.width > 0 && r.height > 0;
    };
    const widget = document.querySelector('.emo-mobile-filter-content .widget_product_categories,.widget_product_categories');
    const items = [...(widget?.querySelectorAll('li.cat-item') || [])].filter((item) => visible(item));
    return {
      widgetVisible: visible(widget),
      items: items.map((item) => {
        const link = item.querySelector(':scope > a') || item.querySelector('a');
        const countNode = item.querySelector(':scope > .count') || item.querySelector('.count');
        const countText = (countNode?.textContent || '').replace(/[()\s]/g, '');
        const parsed = Number.parseInt(countText.replace(/[^0-9]/g, ''), 10);
        return {
          name: (link?.textContent || '').replace(/\s+/g, ' ').trim(),
          href: link?.href || '',
          countText,
          count: Number.isFinite(parsed) ? parsed : 0,
          countVisible: visible(countNode)
        };
      })
    };
  });
}

async function verifyCategoryResultCounts(browser, categories) {
  for (const [index, category] of categories.entries()) {
    const page = await browser.newPage();
    await page.setRequestInterception(true);
    page.on('request', (request) => {
      if (['image', 'media', 'font'].includes(request.resourceType())) request.abort();
      else request.continue();
    });
    try {
      await page.setViewport({ width: 360, height: 800, deviceScaleFactor: 1 });
      await go(page, category.href, 450);
      const state = await page.evaluate(() => {
        const node = document.querySelector('.emo-catalog-result-count-010220');
        const text = (node?.textContent || '').replace(/\s+/g, ' ').trim();
        const match = text.match(/^(\d+)\s+resultados?$/i);
        return { text, total: match ? Number(match[1]) : 0 };
      });
      if (state.total !== category.count) failures.push(`mobile: category ${category.name} count ${category.count} != result total ${state.total}`);
      report[`categoryResult${index + 1}`] = { name: category.name, count: category.count, result: state.total };
    } finally {
      await page.close();
    }
  }
}

function assertToolbar(name, data) {
  if (!data.release) failures.push(`${name}: 0.10.222 release marker missing`);
  if (!data.exactVisible) failures.push(`${name}: exact result count not visible`);
  if (!/^\d+\s+resultados?$/.test(data.exactText)) failures.push(`${name}: unexpected result text ${JSON.stringify(data.exactText)}`);
  if (expectedShopTotal > 0 && data.exactTotal !== expectedShopTotal) failures.push(`${name}: exact total ${data.exactTotal}; expected ${expectedShopTotal}`);
  if (data.legacyVisible) failures.push(`${name}: legacy result count visible`);
  if (!data.orderingVisible) failures.push(`${name}: ordering form hidden`);
  if (!data.selectVisible) failures.push(`${name}: ordering select hidden`);
  if (data.optionCount < 2) failures.push(`${name}: ordering options ${data.optionCount}`);
  if (data.select && (data.select.left < -1 || data.select.right > data.viewport.width + 1)) failures.push(`${name}: ordering select outside viewport ${JSON.stringify(data.select)}`);
}

function assertMobilePrice(data) {
  if (!data.drawerVisible) failures.push('mobile: filter drawer hidden after toggle');
  if (!data.sliderVisible) failures.push('mobile: price slider hidden');
  if (data.handles.length !== 2) failures.push(`mobile: expected 2 slider handles, got ${data.handles.length}`);
  data.handles.forEach((handle, index) => {
    if (!handle.visible) failures.push(`mobile: handle ${index + 1} hidden`);
    if (handle.marginTop !== '0px') failures.push(`mobile: handle ${index + 1} margin-top ${handle.marginTop}`);
    if (handle.boxSizing !== 'border-box') failures.push(`mobile: handle ${index + 1} box-sizing ${handle.boxSizing}`);
  });
  data.alignmentDeltas.forEach((delta, index) => {
    if (delta > 0.75) failures.push(`mobile: handle ${index + 1} center differs from track by ${delta}px`);
  });
}

function assertCategoryCounts(data) {
  if (!data.widgetVisible) failures.push('mobile: category widget hidden after toggle');
  if (!data.items.length) failures.push('mobile: no visible category rows');
  data.items.forEach((item) => {
    if (!item.countVisible) failures.push(`mobile: category ${item.name} count hidden`);
    if (item.count <= 0) failures.push(`mobile: category ${item.name} invalid count ${JSON.stringify(item.countText)}`);
    if (!item.href) failures.push(`mobile: category ${item.name} missing link`);
  });
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  const page = await browser.newPage();
  await page.setRequestInterception(true);
  page.on('request', (request) => {
    if (['image', 'media', 'font'].includes(request.resourceType())) request.abort();
    else request.continue();
  });

  try {
    await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
    await waitForRelease(page);
    await go(page, '/tienda/', 600);
    report.desktopToolbar = await inspectToolbar(page);
    assertToolbar('desktop', report.desktopToolbar);

    await page.setViewport({ width: 360, height: 800, deviceScaleFactor: 1 });
    await go(page, '/tienda/', 700);
    report.mobileToolbar = await inspectToolbar(page);
    assertToolbar('mobile', report.mobileToolbar);

    const toggle = await page.$('.emo-mobile-filter-toggle,#emo-premium-filter-toggle');
    if (!toggle) {
      failures.push('mobile: filter toggle missing');
    } else {
      await toggle.click();
      await sleep(450);
      report.mobilePrice = await inspectMobilePrice(page);
      assertMobilePrice(report.mobilePrice);
      report.mobileCategories = await inspectCategoryCounts(page);
      assertCategoryCounts(report.mobileCategories);
      await verifyCategoryResultCounts(browser, report.mobileCategories.items);
    }

    await page.screenshot({ path: 'qa/catalog-toolbar-mobile-price-010223.png', fullPage: true });
    fs.writeFileSync('qa/catalog-toolbar-mobile-price-010223.json', JSON.stringify({ base: BASE, expectedShopTotal, failures, report }, null, 2));

    if (failures.length) {
      console.error('CATALOG_TOOLBAR_MOBILE_PRICE_010223_FAIL', JSON.stringify(failures));
      process.exitCode = 2;
    } else {
      console.log('CATALOG_TOOLBAR_MOBILE_PRICE_010223_OK', JSON.stringify({ expectedShopTotal, ...report }));
    }
  } finally {
    await browser.close();
  }
})();
