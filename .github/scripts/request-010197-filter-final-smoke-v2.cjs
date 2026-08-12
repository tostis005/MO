const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 1700) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010197-v2', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!response || response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let i = 0; i < 24; i += 1) {
    await go(page, '/categoria-producto/jamones-paletas/', 450);
    if (await page.evaluate(() => !!document.getElementById('elmercado-catalog-filter-final-010197'))) return;
    await sleep(5000);
  }
  throw new Error('0.10.197 not visible on staging');
}

async function snapshot(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      if (!node) return false;
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
    };
    const pos = (node) => {
      if (!visible(node)) return null;
      const r = node.getBoundingClientRect();
      return { top: Math.round(r.top * 10) / 10, bottom: Math.round(r.bottom * 10) / 10, order: getComputedStyle(node).order };
    };
    const heading = (node) => {
      if (!node) return null;
      const s = getComputedStyle(node);
      return {
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
    };
    const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
    const direct = sidebar ? [...sidebar.children] : [];
    const widget = (selectors) => direct.find((child) => selectors.some((selector) => child.matches?.(selector) || child.querySelector?.(selector))) || null;
    const context = document.getElementById('emo-category-context');
    const price = widget(['.widget_price_filter', '.wc-block-price-filter', '.wp-block-woocommerce-price-filter']);
    const categories = widget(['.widget_product_categories', '.wc-block-product-categories', '.wp-block-woocommerce-product-categories']);
    const vendor = document.getElementById('emo-global-vendor-filter');
    const specific = document.getElementById('emo-category-attribute-filters');
    const active = document.querySelector('.emo-active-filter-chips[data-emo-global-active-filters="true"]');
    const vendorItems = vendor ? [...vendor.querySelectorAll('.emo-global-vendor-filter__item')].filter(visible) : [];
    const countValues = (root) => root ? [...root.querySelectorAll('.count')].filter(visible).map((n) => (n.textContent || '').replace(/[()]/g, '').trim()) : [];

    return {
      positions: { context: pos(context), price: pos(price), categories: pos(categories), vendor: pos(vendor), specific: pos(specific) },
      headings: {
        price: heading(price?.querySelector('.widget-title,.widgettitle,.wp-block-heading')),
        categories: heading(categories?.querySelector('.widget-title,.widgettitle,.wp-block-heading')),
        vendor: heading(vendor?.querySelector('.emo-global-vendor-filter__title')),
        specific: heading(specific?.querySelector('h3.emo-category-filter-title')),
      },
      context: context ? {
        visible: visible(context),
        name: (context.querySelector('strong')?.textContent || '').trim(),
        removeText: (context.querySelector('a')?.textContent || '').trim(),
        removeHref: context.querySelector('a')?.href || '',
      } : null,
      vendor: {
        rows: vendorItems.length,
        ids: vendorItems.map((n) => Number(n.dataset.vendorId || 0)),
        counts: countValues(vendor),
        inlineClear: visible(vendor?.querySelector('.emo-global-vendor-filter__clear')),
      },
      categories: {
        rows: categories ? [...categories.querySelectorAll('li')].filter(visible).length : 0,
        counts: countValues(categories),
      },
      specific: {
        groups: specific ? [...specific.querySelectorAll('.emo-category-filter-group')].filter(visible).length : 0,
        titles: specific ? [...specific.querySelectorAll('h3.emo-category-filter-title')].filter(visible).map((n) => (n.textContent || '').trim()) : [],
        counts: countValues(specific),
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
  });
}

function sameHeading(a, b, label) {
  if (!a || !b) return failures.push(`${label}: missing heading`);
  for (const key of ['display', 'background', 'color', 'fontSize', 'fontWeight', 'borderLeftWidth', 'borderRadius', 'paddingTop', 'paddingBottom', 'textAlign']) {
    if (a[key] !== b[key]) failures.push(`${label}: ${key} differs (${a[key]} vs ${b[key]})`);
  }
}

function ascending(items, label) {
  const present = items.filter(([, p]) => p && typeof p.top === 'number');
  for (let i = 1; i < present.length; i += 1) {
    if (!(present[i - 1][1].top < present[i][1].top)) {
      failures.push(`${label}: wrong visual order ${JSON.stringify(Object.fromEntries(present))}`);
      break;
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

    await go(page, '/tienda/', 2100);
    report.shop = await snapshot(page);
    ascending([['price', report.shop.positions.price], ['categories', report.shop.positions.categories], ['vendor', report.shop.positions.vendor]], 'shop');
    sameHeading(report.shop.headings.price, report.shop.headings.vendor, 'shop price/vendor');
    sameHeading(report.shop.headings.categories, report.shop.headings.vendor, 'shop categories/vendor');
    if (report.shop.vendor.ids.includes(1)) failures.push('shop: vendor ID 1 visible');
    if (!report.shop.vendor.rows || report.shop.vendor.counts.length !== report.shop.vendor.rows) failures.push(`shop: vendor counts missing ${JSON.stringify(report.shop.vendor)}`);
    if (!report.shop.categories.rows || !report.shop.categories.counts.length) failures.push(`shop: category counts missing ${JSON.stringify(report.shop.categories)}`);
    if (report.shop.headings.vendor?.background === 'rgb(23, 63, 50)') failures.push('shop: old vendor heading background visible');
    await page.screenshot({ path: 'qa/request-010197-v2-shop.png', fullPage: true });

    await go(page, '/categoria-producto/jamones-paletas/', 2400);
    report.jamones = await snapshot(page);
    ascending([['context', report.jamones.positions.context], ['price', report.jamones.positions.price], ['vendor', report.jamones.positions.vendor], ['specific', report.jamones.positions.specific]], 'jamones');
    if (!report.jamones.context?.visible || !/jamones/i.test(report.jamones.context.name)) failures.push(`jamones: context missing ${JSON.stringify(report.jamones.context)}`);
    if (!/quitar/i.test(report.jamones.context?.removeText || '')) failures.push(`jamones: category remove missing ${JSON.stringify(report.jamones.context)}`);
    if (new URL(report.jamones.context?.removeHref || BASE).pathname.replace(/\/$/, '') !== '/tienda') failures.push(`jamones: category remove target wrong ${report.jamones.context?.removeHref}`);
    sameHeading(report.jamones.headings.price, report.jamones.headings.vendor, 'jamones price/vendor');
    sameHeading(report.jamones.headings.specific, report.jamones.headings.vendor, 'jamones specific/vendor');
    if (!report.jamones.specific.titles.some((t) => /peso/i.test(t))) failures.push(`jamones: Por peso missing ${JSON.stringify(report.jamones.specific.titles)}`);
    if (!report.jamones.specific.groups || !report.jamones.specific.counts.length) failures.push(`jamones: specific counts missing ${JSON.stringify(report.jamones.specific)}`);
    if (!report.jamones.vendor.rows || report.jamones.vendor.counts.length !== report.jamones.vendor.rows) failures.push(`jamones: vendor counts missing ${JSON.stringify(report.jamones.vendor)}`);
    if (report.jamones.vendor.ids.includes(1)) failures.push('jamones: vendor ID 1 visible');
    await page.screenshot({ path: 'qa/request-010197-v2-jamones.png', fullPage: true });

    const href = await page.evaluate(() => document.querySelector('#emo-global-vendor-filter .emo-global-vendor-filter__item > a')?.href || '');
    if (href) {
      await go(page, href, 2400);
      report.selectedVendor = await snapshot(page);
      ascending([['context', report.selectedVendor.positions.context], ['price', report.selectedVendor.positions.price], ['vendor', report.selectedVendor.positions.vendor], ['specific', report.selectedVendor.positions.specific]], 'selected vendor');
      if (!report.selectedVendor.active?.visible) failures.push(`selected vendor: applied filters missing ${JSON.stringify(report.selectedVendor.active)}`);
      if (!report.selectedVendor.active?.chips?.some((chip) => /^Vendedor$/i.test(chip.group))) failures.push(`selected vendor: vendor chip missing ${JSON.stringify(report.selectedVendor.active)}`);
      if (report.selectedVendor.vendor.inlineClear) failures.push('selected vendor: inline clear visible');
      if (!['emo-category-attribute-filters', 'emo-category-applied-filters-slot-010196'].includes(report.selectedVendor.active?.parentId || '')) failures.push(`selected vendor: applied filters in wrong container ${JSON.stringify(report.selectedVendor.active)}`);
      await page.screenshot({ path: 'qa/request-010197-v2-selected-vendor.png', fullPage: true });
    }

    await go(page, '/', 2100);
    report.home = await page.evaluate(() => {
      const card = document.querySelector('.emo-category-card');
      const content = card?.querySelector('.emo-category-card__content');
      const count = content?.querySelector('small');
      const arrow = card?.querySelector(':scope > svg');
      const style = content ? getComputedStyle(content) : null;
      return {
        countText: (count?.textContent || '').trim(),
        arrowDisplay: arrow ? getComputedStyle(arrow).display : 'missing',
        display: style?.display || '',
        paddingTop: style?.paddingTop || '',
        paddingBottom: style?.paddingBottom || '',
      };
    });
    if (!/productos?/i.test(report.home.countText)) failures.push(`home: product count missing ${JSON.stringify(report.home)}`);
    if (report.home.arrowDisplay !== 'none') failures.push(`home: arrow visible ${JSON.stringify(report.home)}`);
    if (report.home.display !== 'grid') failures.push(`home: category content not grid ${JSON.stringify(report.home)}`);
    if (report.home.paddingTop !== report.home.paddingBottom) failures.push(`home: category padding unbalanced ${JSON.stringify(report.home)}`);
    await page.screenshot({ path: 'qa/request-010197-v2-home.png', fullPage: true });
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/request-010197-v2.json', JSON.stringify({ failures, report }, null, 2));
  if (failures.length) {
    console.error('REQUEST_010197_V2_FAIL ' + JSON.stringify(failures));
    process.exitCode = 2;
  } else {
    console.log('REQUEST_010197_V2_OK ' + JSON.stringify(report));
  }
})().catch((error) => {
  console.error(error);
  process.exit(2);
});
