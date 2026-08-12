const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 1200) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010195-qa', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!response || response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let i = 0; i < 24; i += 1) {
    await go(page, '/tienda/', 400);
    const ready = await page.evaluate(() => !!document.getElementById('elmercado-catalog-filter-unification-010195'));
    if (ready) return;
    await sleep(5000);
  }
  throw new Error('0.10.195 did not become visible on staging');
}

const catalogSnapshot = () => {
  const visible = (node) => {
    if (!node) return false;
    const r = node.getBoundingClientRect();
    const s = getComputedStyle(node);
    return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
  };
  const style = (node) => {
    if (!node) return null;
    const s = getComputedStyle(node);
    const r = node.getBoundingClientRect();
    return {
      height: Math.round(r.height * 10) / 10,
      background: s.backgroundColor,
      color: s.color,
      fontSize: s.fontSize,
      fontWeight: s.fontWeight,
      borderLeftWidth: s.borderLeftWidth,
      borderRadius: s.borderRadius,
      paddingTop: s.paddingTop,
      paddingBottom: s.paddingBottom,
    };
  };
  const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
  const direct = sidebar ? [...sidebar.children].filter(visible) : [];
  const price = direct.find((n) => n.matches?.('.widget_price_filter') || n.querySelector?.('.widget_price_filter,.wc-block-price-filter'));
  const categories = direct.find((n) => n.matches?.('.widget_product_categories') || n.querySelector?.('.widget_product_categories,.wc-block-product-categories'));
  const vendor = document.getElementById('emo-global-vendor-filter');
  const context = document.getElementById('emo-category-context');
  const specific = document.getElementById('emo-category-attribute-filters');
  const titleOf = (node, extra = '') => node?.querySelector(extra || '.widget-title,.widgettitle,.wp-block-heading');
  const rows = (root, selector) => root ? [...root.querySelectorAll(selector)].filter(visible) : [];
  const counts = (root) => root ? [...root.querySelectorAll('.count')].filter(visible).map((n) => (n.textContent || '').trim()) : [];
  return {
    order: direct.slice(0, 8).map((n) => n.id || n.className || n.tagName),
    indexes: {
      context: context ? direct.indexOf(context) : -1,
      price: price ? direct.indexOf(price) : -1,
      categories: categories ? direct.indexOf(categories) : -1,
      vendor: vendor ? direct.indexOf(vendor) : -1,
      specific: specific ? direct.indexOf(specific) : -1,
    },
    titles: {
      price: style(titleOf(price)),
      categories: style(titleOf(categories)),
      vendor: style(vendor?.querySelector('.emo-global-vendor-filter__title')),
      firstSpecific: style(specific?.querySelector('.emo-category-filter-group__title')),
    },
    vendor: {
      ids: rows(vendor, '.emo-global-vendor-filter__item').map((n) => Number(n.dataset.vendorId || 0)),
      counts: counts(vendor),
      rows: rows(vendor, '.emo-global-vendor-filter__item').length,
    },
    categories: {
      counts: counts(categories),
      rows: rows(categories, 'li').length,
    },
    specific: {
      groups: rows(specific, '.emo-category-filter-group').length,
      counts: counts(specific),
    },
    context: context ? {
      visible: visible(context),
      name: (context.querySelector('strong')?.textContent || '').trim(),
      removeText: (context.querySelector('a')?.textContent || '').trim(),
      removeHref: context.querySelector('a')?.href || '',
    } : null,
    activeLocation: (() => {
      const active = document.querySelector('.emo-active-filter-chips[data-emo-global-active-filters="true"]');
      if (!active) return null;
      return { parentId: active.parentElement?.id || '', visible: visible(active) };
    })(),
  };
};

function sameHeading(a, b, label) {
  if (!a || !b) { failures.push(`${label}: missing heading`); return; }
  for (const key of ['background', 'color', 'fontSize', 'fontWeight', 'borderLeftWidth', 'borderRadius', 'paddingTop', 'paddingBottom']) {
    if (a[key] !== b[key]) failures.push(`${label}: ${key} differs (${a[key]} vs ${b[key]})`);
  }
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', protocolTimeout: 120000, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  page.setDefaultNavigationTimeout(60000);
  await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });

  try {
    await waitForRelease(page);

    await go(page, '/tienda/', 1700);
    report.shop = await page.evaluate(catalogSnapshot);
    if (report.shop.indexes.price !== 0 || report.shop.indexes.categories !== 1 || report.shop.indexes.vendor !== 2) failures.push(`shop order incorrect: ${JSON.stringify(report.shop.indexes)}`);
    if (report.shop.vendor.ids.includes(1)) failures.push('shop still shows vendor ID 1');
    if (!report.shop.vendor.rows || report.shop.vendor.counts.length !== report.shop.vendor.rows) failures.push(`shop vendor counts missing: ${JSON.stringify(report.shop.vendor)}`);
    if (!report.shop.categories.rows || !report.shop.categories.counts.length) failures.push(`shop category counts missing: ${JSON.stringify(report.shop.categories)}`);
    sameHeading(report.shop.titles.categories, report.shop.titles.vendor, 'shop categories/vendor headings');
    sameHeading(report.shop.titles.price, report.shop.titles.vendor, 'shop price/vendor headings');
    if (report.shop.titles.vendor?.background === 'rgb(23, 63, 50)') failures.push('shop vendor heading still uses old dark background');
    await page.screenshot({ path: 'qa/request-010195-shop.png', fullPage: true });

    await go(page, '/categoria-producto/jamones-paletas/', 1900);
    report.jamones = await page.evaluate(catalogSnapshot);
    if (report.jamones.indexes.context !== 0 || report.jamones.indexes.price !== 1 || report.jamones.indexes.vendor !== 2 || report.jamones.indexes.specific !== 3) failures.push(`jamones order incorrect: ${JSON.stringify(report.jamones.indexes)}`);
    if (!report.jamones.context?.visible || !/jamones/i.test(report.jamones.context.name)) failures.push(`jamones context missing: ${JSON.stringify(report.jamones.context)}`);
    if (!/quitar/i.test(report.jamones.context?.removeText || '') || !/tienda\/?$/i.test(new URL(report.jamones.context?.removeHref || BASE).pathname)) failures.push(`jamones context remove action incorrect: ${JSON.stringify(report.jamones.context)}`);
    if (report.jamones.vendor.ids.includes(1)) failures.push('jamones still shows vendor ID 1');
    if (!report.jamones.vendor.rows || report.jamones.vendor.counts.length !== report.jamones.vendor.rows) failures.push(`jamones vendor counts missing: ${JSON.stringify(report.jamones.vendor)}`);
    if (!report.jamones.specific.groups || !report.jamones.specific.counts.length) failures.push(`jamones specific filters/counts missing: ${JSON.stringify(report.jamones.specific)}`);
    sameHeading(report.jamones.titles.firstSpecific, report.jamones.titles.vendor, 'jamones specific/vendor headings');
    sameHeading(report.jamones.titles.price, report.jamones.titles.vendor, 'jamones price/vendor headings');
    await page.screenshot({ path: 'qa/request-010195-jamones.png', fullPage: true });

    const chosenHref = await page.evaluate(() => document.querySelector('#emo-global-vendor-filter .emo-global-vendor-filter__item > a')?.href || '');
    if (chosenHref) {
      await go(page, chosenHref, 1800);
      report.jamonesVendor = await page.evaluate(catalogSnapshot);
      if (!report.jamonesVendor.activeLocation?.visible) failures.push(`selected vendor applied filters missing: ${JSON.stringify(report.jamonesVendor.activeLocation)}`);
      if (report.jamonesVendor.activeLocation?.parentId !== 'emo-category-attribute-filters' && report.jamonesVendor.activeLocation?.parentId !== 'emo-category-applied-filters-slot-010195') failures.push(`selected vendor applied filters in wrong place: ${JSON.stringify(report.jamonesVendor.activeLocation)}`);
      await page.screenshot({ path: 'qa/request-010195-jamones-vendor.png', fullPage: true });
    }

    await go(page, '/', 1600);
    report.home = await page.evaluate(() => {
      const card = document.querySelector('.emo-category-card');
      const content = card?.querySelector('.emo-category-card__content');
      const count = content?.querySelector('small');
      const arrow = card?.querySelector(':scope > svg');
      const cs = content ? getComputedStyle(content) : null;
      return {
        card: !!card,
        countText: (count?.textContent || '').trim(),
        arrowDisplay: arrow ? getComputedStyle(arrow).display : 'missing',
        contentDisplay: cs?.display || '',
        paddingTop: cs?.paddingTop || '',
        paddingBottom: cs?.paddingBottom || '',
      };
    });
    if (!report.home.card || !/productos?/i.test(report.home.countText)) failures.push(`home category count missing: ${JSON.stringify(report.home)}`);
    if (report.home.arrowDisplay !== 'none') failures.push(`home category arrow still visible: ${JSON.stringify(report.home)}`);
    if (report.home.contentDisplay !== 'grid') failures.push(`home category content not compact grid: ${JSON.stringify(report.home)}`);
    await page.screenshot({ path: 'qa/request-010195-home.png', fullPage: true });
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/request-010195-filter-unification.json', JSON.stringify({ failures, report }, null, 2));
  if (failures.length) {
    console.error('REQUEST_010195_FAIL ' + JSON.stringify(failures));
    process.exitCode = 2;
  } else {
    console.log('REQUEST_010195_OK ' + JSON.stringify(report));
  }
})().catch((error) => {
  console.error(error);
  process.exit(2);
});
