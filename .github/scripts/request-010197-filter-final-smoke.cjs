const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 1400) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010197-qa', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!response || response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let attempt = 0; attempt < 24; attempt += 1) {
    await go(page, '/categoria-producto/jamones-paletas/', 450);
    const ready = await page.evaluate(() => !!document.getElementById('elmercado-catalog-filter-final-010197'));
    if (ready) return;
    await sleep(5000);
  }
  throw new Error('0.10.197 did not become visible on staging');
}

function headingStyle(node) {
  if (!node) return null;
  const s = getComputedStyle(node);
  const r = node.getBoundingClientRect();
  return {
    height: Math.round(r.height * 10) / 10,
    display: s.display,
    background: s.backgroundColor,
    color: s.color,
    fontSize: s.fontSize,
    fontWeight: s.fontWeight,
    borderLeftWidth: s.borderLeftWidth,
    borderRadius: s.borderRadius,
    paddingTop: s.paddingTop,
    paddingBottom: s.paddingBottom,
    textAlign: s.textAlign,
  };
}

function visible(node) {
  if (!node) return false;
  const r = node.getBoundingClientRect();
  const s = getComputedStyle(node);
  return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
}

function visualPosition(node) {
  if (!visible(node)) return null;
  const r = node.getBoundingClientRect();
  return { top: Math.round(r.top * 10) / 10, bottom: Math.round(r.bottom * 10) / 10, order: getComputedStyle(node).order };
}

function snapshotCatalog() {
  const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
  const direct = sidebar ? [...sidebar.children] : [];
  const directWidget = (selectors) => direct.find((child) => selectors.some((selector) => child.matches?.(selector) || child.querySelector?.(selector))) || null;
  const context = document.getElementById('emo-category-context');
  const price = directWidget(['.widget_price_filter', '.wc-block-price-filter', '.wp-block-woocommerce-price-filter']);
  const categories = directWidget(['.widget_product_categories', '.wc-block-product-categories', '.wp-block-woocommerce-product-categories']);
  const vendor = document.getElementById('emo-global-vendor-filter');
  const specific = document.getElementById('emo-category-attribute-filters');
  const active = document.querySelector('.emo-active-filter-chips[data-emo-global-active-filters="true"]');
  const countText = (root) => root ? [...root.querySelectorAll('.count')].filter(visible).map((n) => (n.textContent || '').replace(/[()]/g, '').trim()) : [];
  const vendorItems = vendor ? [...vendor.querySelectorAll('.emo-global-vendor-filter__item')].filter(visible) : [];
  const categoryItems = categories ? [...categories.querySelectorAll('li')].filter(visible) : [];

  return {
    positions: {
      context: visualPosition(context),
      price: visualPosition(price),
      categories: visualPosition(categories),
      vendor: visualPosition(vendor),
      specific: visualPosition(specific),
    },
    headings: {
      price: headingStyle(price?.querySelector('.widget-title,.widgettitle,.wp-block-heading')),
      categories: headingStyle(categories?.querySelector('.widget-title,.widgettitle,.wp-block-heading')),
      vendor: headingStyle(vendor?.querySelector('.emo-global-vendor-filter__title')),
      specific: headingStyle(specific?.querySelector('h3.emo-category-filter-title')),
    },
    context: context ? {
      visible: visible(context),
      name: (context.querySelector('strong')?.textContent || '').trim(),
      removeText: (context.querySelector('a')?.textContent || '').trim(),
      removeHref: context.querySelector('a')?.href || '',
    } : null,
    vendor: {
      ids: vendorItems.map((item) => Number(item.dataset.vendorId || 0)),
      names: vendorItems.map((item) => (item.querySelector('a')?.textContent || '').trim()),
      counts: countText(vendor),
      rows: vendorItems.length,
      inlineClear: visible(vendor?.querySelector('.emo-global-vendor-filter__clear')),
    },
    categories: {
      rows: categoryItems.length,
      counts: countText(categories),
    },
    specific: {
      groups: specific ? [...specific.querySelectorAll('.emo-category-filter-group')].filter(visible).length : 0,
      counts: countText(specific),
      titles: specific ? [...specific.querySelectorAll('h3.emo-category-filter-title')].filter(visible).map((n) => (n.textContent || '').trim()) : [],
    },
    active: active ? {
      visible: visible(active),
      parentId: active.parentElement?.id || '',
      chips: [...active.querySelectorAll('.emo-active-filter-chip')].filter(visible).map((chip) => ({
        group: (chip.querySelector('.emo-active-filter-chip__group')?.textContent || '').trim(),
        name: (chip.querySelector('.emo-active-filter-chip__name')?.textContent || '').trim(),
      })),
    } : null,
  };
}

function sameHeading(a, b, label) {
  if (!a || !b) {
    failures.push(`${label}: heading missing`);
    return;
  }
  const keys = ['display', 'background', 'color', 'fontSize', 'fontWeight', 'borderLeftWidth', 'borderRadius', 'paddingTop', 'paddingBottom', 'textAlign'];
  for (const key of keys) {
    if (a[key] !== b[key]) failures.push(`${label}: ${key} differs (${a[key]} vs ${b[key]})`);
  }
}

function assertAscending(entries, label) {
  const present = entries.filter(([, value]) => value && typeof value.top === 'number');
  for (let i = 1; i < present.length; i += 1) {
    if (!(present[i - 1][1].top < present[i][1].top)) {
      failures.push(`${label}: visual order is wrong (${JSON.stringify(Object.fromEntries(present))})`);
      return;
    }
  }
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', protocolTimeout: 120000, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  page.setDefaultNavigationTimeout(60000);
  await page.setViewport({ width: 1440, height: 1100, deviceScaleFactor: 1 });

  try {
    await waitForRelease(page);

    await go(page, '/tienda/', 1900);
    report.shop = await page.evaluate(snapshotCatalog);
    assertAscending([
      ['price', report.shop.positions.price],
      ['categories', report.shop.positions.categories],
      ['vendor', report.shop.positions.vendor],
    ], 'shop');
    sameHeading(report.shop.headings.price, report.shop.headings.vendor, 'shop price/vendor');
    sameHeading(report.shop.headings.categories, report.shop.headings.vendor, 'shop categories/vendor');
    if (report.shop.vendor.ids.includes(1)) failures.push('shop: vendor ID 1 visible');
    if (!report.shop.vendor.rows || report.shop.vendor.counts.length !== report.shop.vendor.rows) failures.push(`shop: vendor counts missing (${JSON.stringify(report.shop.vendor)})`);
    if (!report.shop.categories.rows || !report.shop.categories.counts.length) failures.push(`shop: category counts missing (${JSON.stringify(report.shop.categories)})`);
    if (report.shop.headings.vendor?.background === 'rgb(23, 63, 50)') failures.push('shop: old dark vendor heading remains');
    await page.screenshot({ path: 'qa/request-010197-shop.png', fullPage: true });

    await go(page, '/categoria-producto/jamones-paletas/', 2200);
    report.jamones = await page.evaluate(snapshotCatalog);
    assertAscending([
      ['context', report.jamones.positions.context],
      ['price', report.jamones.positions.price],
      ['vendor', report.jamones.positions.vendor],
      ['specific', report.jamones.positions.specific],
    ], 'jamones');
    if (!report.jamones.context?.visible || !/jamones/i.test(report.jamones.context.name)) failures.push(`jamones: category context missing (${JSON.stringify(report.jamones.context)})`);
    if (!/quitar/i.test(report.jamones.context?.removeText || '')) failures.push(`jamones: remove category action missing (${JSON.stringify(report.jamones.context)})`);
    if (new URL(report.jamones.context?.removeHref || BASE).pathname.replace(/\/$/, '') !== '/tienda') failures.push(`jamones: category remove does not return to shop (${report.jamones.context?.removeHref})`);
    sameHeading(report.jamones.headings.price, report.jamones.headings.vendor, 'jamones price/vendor');
    sameHeading(report.jamones.headings.specific, report.jamones.headings.vendor, 'jamones specific/vendor');
    if (!report.jamones.specific.titles.some((title) => /peso/i.test(title))) failures.push(`jamones: Por peso title missing (${JSON.stringify(report.jamones.specific.titles)})`);
    if (!report.jamones.specific.groups || !report.jamones.specific.counts.length) failures.push(`jamones: specific filters/counts missing (${JSON.stringify(report.jamones.specific)})`);
    if (!report.jamones.vendor.rows || report.jamones.vendor.counts.length !== report.jamones.vendor.rows) failures.push(`jamones: vendor counts missing (${JSON.stringify(report.jamones.vendor)})`);
    if (report.jamones.vendor.ids.includes(1)) failures.push('jamones: vendor ID 1 visible');
    await page.screenshot({ path: 'qa/request-010197-jamones.png', fullPage: true });

    const vendorHref = await page.evaluate(() => document.querySelector('#emo-global-vendor-filter .emo-global-vendor-filter__item > a')?.href || '');
    if (vendorHref) {
      await go(page, vendorHref, 2200);
      report.selectedVendor = await page.evaluate(snapshotCatalog);
      assertAscending([
        ['context', report.selectedVendor.positions.context],
        ['price', report.selectedVendor.positions.price],
        ['vendor', report.selectedVendor.positions.vendor],
        ['specific', report.selectedVendor.positions.specific],
      ], 'selected vendor');
      if (!report.selectedVendor.active?.visible) failures.push(`selected vendor: applied-filter group missing (${JSON.stringify(report.selectedVendor.active)})`);
      const vendorChip = report.selectedVendor.active?.chips?.find((chip) => /^Vendedor$/i.test(chip.group));
      if (!vendorChip) failures.push(`selected vendor: vendor chip missing (${JSON.stringify(report.selectedVendor.active)})`);
      if (report.selectedVendor.vendor.inlineClear) failures.push('selected vendor: inline Quitar vendedor is visible');
      if (report.selectedVendor.active?.parentId !== 'emo-category-attribute-filters' && report.selectedVendor.active?.parentId !== 'emo-category-applied-filters-slot-010196') failures.push(`selected vendor: applied filters in wrong container (${JSON.stringify(report.selectedVendor.active)})`);
      await page.screenshot({ path: 'qa/request-010197-selected-vendor.png', fullPage: true });
    }

    await go(page, '/', 1900);
    report.home = await page.evaluate(() => {
      const cards = [...document.querySelectorAll('.emo-category-card')];
      const first = cards[0];
      const content = first?.querySelector('.emo-category-card__content');
      const count = content?.querySelector('small');
      const arrow = first?.querySelector(':scope > svg');
      const cs = content ? getComputedStyle(content) : null;
      return {
        cards: cards.length,
        countText: (count?.textContent || '').trim(),
        arrowDisplay: arrow ? getComputedStyle(arrow).display : 'missing',
        contentDisplay: cs?.display || '',
        paddingTop: cs?.paddingTop || '',
        paddingBottom: cs?.paddingBottom || '',
      };
    });
    if (!report.home.cards || !/productos?/i.test(report.home.countText)) failures.push(`home: category count missing (${JSON.stringify(report.home)})`);
    if (report.home.arrowDisplay !== 'none') failures.push(`home: category arrow visible (${JSON.stringify(report.home)})`);
    if (report.home.contentDisplay !== 'grid') failures.push(`home: category content is not compact grid (${JSON.stringify(report.home)})`);
    if (report.home.paddingTop !== report.home.paddingBottom) failures.push(`home: category vertical padding is not balanced (${JSON.stringify(report.home)})`);
    await page.screenshot({ path: 'qa/request-010197-home.png', fullPage: true });
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/request-010197-filter-final.json', JSON.stringify({ failures, report }, null, 2));
  if (failures.length) {
    console.error('REQUEST_010197_FAIL ' + JSON.stringify(failures));
    process.exitCode = 2;
  } else {
    console.log('REQUEST_010197_OK ' + JSON.stringify(report));
  }
})().catch((error) => {
  console.error(error);
  process.exit(2);
});
