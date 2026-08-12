const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, pathOrUrl, delay = 900) {
  const url = /^https?:/i.test(pathOrUrl) ? new URL(pathOrUrl) : new URL(pathOrUrl, BASE);
  url.searchParams.set('request-010193-qa', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!response || response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let attempt = 1; attempt <= 24; attempt += 1) {
    await go(page, '/tienda/', 500);
    const state = await page.evaluate(() => ({
      core: !!document.getElementById('elmercado-catalog-core-filters-010193'),
      vendor: !!document.getElementById('emo-global-vendor-filter'),
    }));
    if (state.core && state.vendor) return;
    await sleep(5000);
  }
  throw new Error('The 0.10.193 vendor filter release did not become visible on staging');
}

async function shopSnapshot(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      if (!node) return false;
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
    };
    const style = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return {
        height: Math.round(r.height * 10) / 10,
        fontSize: s.fontSize,
        fontWeight: s.fontWeight,
        lineHeight: s.lineHeight,
        background: s.backgroundColor,
        color: s.color,
        borderRadius: s.borderRadius,
        textAlign: s.textAlign,
        paddingTop: s.paddingTop,
        paddingBottom: s.paddingBottom,
      };
    };

    const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
    const vendor = document.getElementById('emo-global-vendor-filter');
    const categories = sidebar?.querySelector('.widget_product_categories,.wc-block-product-categories,.wp-block-woocommerce-product-categories');
    const vendorTitle = vendor?.querySelector('.emo-global-vendor-filter__title');
    const categoryTitle = categories?.querySelector('.widget-title,.widgettitle,.wp-block-heading');
    const vendorLinks = vendor ? [...vendor.querySelectorAll('.emo-global-vendor-filter__item > a')].filter(visible) : [];
    const categoryLinks = categories ? [...categories.querySelectorAll('li > a,a.wc-block-product-categories-list-item__name')].filter(visible) : [];
    const clearInsideVendor = vendor?.querySelector('.emo-global-vendor-filter__clear,[class*="clear"]');

    return {
      vendorTitle: style(vendorTitle),
      categoryTitle: style(categoryTitle),
      firstVendorRow: style(vendorLinks[0]),
      firstCategoryRow: style(categoryLinks[0]),
      clearInsideVendor: visible(clearInsideVendor),
      vendors: vendorLinks.map((link) => {
        const item = link.closest('.emo-global-vendor-filter__item');
        const url = new URL(link.href, location.href);
        return {
          id: Number(item?.dataset.vendorId || url.searchParams.get('vendor_id') || 0),
          name: (link.textContent || '').replace(/\s+/g, ' ').trim(),
          href: link.href,
        };
      }),
      directOrder: sidebar ? [...sidebar.children].filter(visible).slice(0, 6).map((node) => ({
        id: node.id || '',
        classes: node.className || '',
        text: (node.querySelector('.widget-title,.widgettitle,.wp-block-heading')?.textContent || node.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 70),
      })) : [],
    };
  });
}

function assertShop(metric) {
  if (!metric.vendors.length) failures.push('shop: no real vendors visible');
  if (metric.vendors.some((vendor) => vendor.id === 1)) failures.push(`shop: administrative vendor ID 1 is still visible (${JSON.stringify(metric.vendors)})`);
  if (metric.vendors.some((vendor) => vendor.id <= 0)) failures.push(`shop: invalid vendor id (${JSON.stringify(metric.vendors)})`);
  if (metric.clearInsideVendor) failures.push('shop: vendor filter still contains an inline clear/remove action');

  const titleKeys = ['height', 'fontSize', 'fontWeight', 'background', 'color', 'borderRadius', 'textAlign'];
  for (const key of titleKeys) {
    if (metric.vendorTitle?.[key] !== metric.categoryTitle?.[key]) {
      failures.push(`shop: vendor/category title ${key} differs (${metric.vendorTitle?.[key]} vs ${metric.categoryTitle?.[key]})`);
    }
  }

  if (!metric.firstVendorRow || !metric.firstCategoryRow) {
    failures.push('shop: cannot compare vendor/category rows');
  } else {
    if (Math.abs(metric.firstVendorRow.height - metric.firstCategoryRow.height) > 1) {
      failures.push(`shop: vendor/category row heights differ (${metric.firstVendorRow.height} vs ${metric.firstCategoryRow.height})`);
    }
    if (metric.firstVendorRow.fontSize !== metric.firstCategoryRow.fontSize) {
      failures.push(`shop: vendor/category row font-size differs (${metric.firstVendorRow.fontSize} vs ${metric.firstCategoryRow.fontSize})`);
    }
    if (metric.firstVendorRow.fontWeight !== metric.firstCategoryRow.fontWeight) {
      failures.push(`shop: vendor/category row font-weight differs (${metric.firstVendorRow.fontWeight} vs ${metric.firstCategoryRow.fontWeight})`);
    }
  }
}

async function selectedVendorSnapshot(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      if (!node) return false;
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
    };
    const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
    const summary = sidebar?.querySelector(':scope > .emo-active-filter-chips[data-emo-global-active-filters="true"]');
    const chips = summary ? [...summary.querySelectorAll('.emo-active-filter-chip')].filter(visible).map((chip) => ({
      group: (chip.querySelector('.emo-active-filter-chip__group')?.textContent || '').trim(),
      name: (chip.querySelector('.emo-active-filter-chip__name')?.textContent || '').trim(),
      href: chip.href,
    })) : [];
    const vendor = document.getElementById('emo-global-vendor-filter');
    return {
      summaryVisible: visible(summary),
      summaryIsFirst: !!sidebar && sidebar.firstElementChild === summary,
      chips,
      inlineClearVisible: visible(vendor?.querySelector('.emo-global-vendor-filter__clear,[class*="clear"]')),
      selectedName: (vendor?.querySelector('.emo-global-vendor-filter__item.is-active > a')?.textContent || '').replace(/\s+/g, ' ').trim(),
    };
  });
}

function assertSelected(metric, expectedName) {
  if (!metric.summaryVisible) failures.push('selected vendor: applied-filter summary is missing');
  if (!metric.summaryIsFirst) failures.push('selected vendor: applied-filter summary is not first in sidebar');
  const vendorChip = metric.chips.find((chip) => /^Vendedor$/i.test(chip.group));
  if (!vendorChip) failures.push(`selected vendor: vendor chip missing (${JSON.stringify(metric.chips)})`);
  if (vendorChip && vendorChip.name !== expectedName) failures.push(`selected vendor: chip name mismatch (${vendorChip.name} vs ${expectedName})`);
  if (metric.inlineClearVisible) failures.push('selected vendor: inline remove action is still visible');
  if (metric.selectedName !== expectedName) failures.push(`selected vendor: active row mismatch (${metric.selectedName} vs ${expectedName})`);
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    protocolTimeout: 120000,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const page = await browser.newPage();
  page.setDefaultNavigationTimeout(60000);

  try {
    await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
    await waitForRelease(page);

    await go(page, '/tienda/', 1500);
    report.shop = await shopSnapshot(page);
    assertShop(report.shop);
    await page.screenshot({ path: 'qa/request-010193-shop.png', fullPage: true });

    const chosen = report.shop.vendors[0];
    if (chosen) {
      await go(page, chosen.href, 1600);
      report.selectedVendor = await selectedVendorSnapshot(page);
      assertSelected(report.selectedVendor, chosen.name);
      await page.screenshot({ path: 'qa/request-010193-selected-vendor.png', fullPage: true });
    }

    await go(page, '/categoria-producto/aceites/', 1500);
    report.aceites = await page.evaluate(() => {
      const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
      const visible = (node) => {
        if (!node) return false;
        const r = node.getBoundingClientRect();
        const s = getComputedStyle(node);
        return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
      };
      const vendor = document.getElementById('emo-global-vendor-filter');
      const links = vendor ? [...vendor.querySelectorAll('.emo-global-vendor-filter__item > a')].filter(visible) : [];
      const price = sidebar?.querySelector('.widget_price_filter,.wc-block-price-filter,.wp-block-woocommerce-price-filter');
      return {
        priceIndex: price ? [...sidebar.children].indexOf(price) : -1,
        vendorIndex: vendor ? [...sidebar.children].indexOf(vendor) : -1,
        vendorIds: links.map((link) => Number(link.closest('.emo-global-vendor-filter__item')?.dataset.vendorId || 0)),
        vendorTitle: (vendor?.querySelector('.emo-global-vendor-filter__title')?.textContent || '').trim(),
      };
    });
    if (report.aceites.priceIndex !== 0 || report.aceites.vendorIndex !== 1) failures.push(`aceites: expected price → vendor order (${JSON.stringify(report.aceites)})`);
    if (report.aceites.vendorIds.includes(1)) failures.push(`aceites: administrative vendor ID 1 is visible (${JSON.stringify(report.aceites.vendorIds)})`);
    await page.screenshot({ path: 'qa/request-010193-aceites.png', fullPage: true });
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/request-010193-vendor-filter-smoke.json', JSON.stringify({ failures, report }, null, 2));
  if (failures.length) {
    console.error(`REQUEST_010193_FAIL ${JSON.stringify(failures)}`);
    process.exitCode = 2;
  } else {
    console.log(`REQUEST_010193_OK ${JSON.stringify(report)}`);
  }
})();
