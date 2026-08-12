const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 1100) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010192-qa', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!response || response.status() >= 400) {
    failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
  }
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForCatalogRelease(page) {
  for (let attempt = 1; attempt <= 18; attempt += 1) {
    await go(page, '/tienda/', 500);
    const present = await page.$('#emo-global-vendor-filter');
    if (present) return;
    await sleep(5000);
  }
  throw new Error('The 0.10.192 catalog filter release did not become visible on staging');
}

async function catalogSnapshot(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      if (!node) return false;
      const rect = node.getBoundingClientRect();
      const style = getComputedStyle(node);
      return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
    };
    const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
    if (!sidebar) return null;
    const children = [...sidebar.children];
    const top = (selectors) => children.find((child) => selectors.some((selector) => child.matches?.(selector) || child.querySelector?.(selector))) || null;
    const index = (node) => node ? children.indexOf(node) : -1;
    const heading = (node) => (node?.querySelector('.widget-title,.widgettitle,.sidebar-heading,.widget-heading,.wp-block-heading')?.textContent || '').replace(/\s+/g, ' ').trim();
    const price = top(['.widget_price_filter', '.wc-block-price-filter', '.wp-block-woocommerce-price-filter']);
    const categories = top(['.widget_product_categories', '.wc-block-product-categories', '.wp-block-woocommerce-product-categories']);
    const vendor = document.getElementById('emo-global-vendor-filter');
    const specific = document.getElementById('emo-category-attribute-filters');
    const context = document.getElementById('emo-category-context');
    const allowed = new Set([price, categories, vendor, specific].filter(Boolean));
    const visibleWidgetish = children.filter((child) =>
      visible(child) && child.matches?.('.widget,.widget_block,[class*="widget_"],[class*="wp-block-woocommerce-"]')
    );
    return {
      price: { visible: visible(price), index: index(price), heading: heading(price) },
      categories: { visible: visible(categories), index: index(categories), heading: heading(categories) },
      vendor: {
        visible: visible(vendor),
        index: index(vendor),
        heading: heading(vendor),
        links: vendor ? [...vendor.querySelectorAll('a[href]')].filter(visible).length : 0,
      },
      specific: { present: !!specific, visible: visible(specific), index: index(specific) },
      context: { present: !!context, visible: visible(context), index: index(context) },
      otherVisibleWidgets: visibleWidgetish.filter((node) => !allowed.has(node)).map((node) => ({
        id: node.id || '',
        classes: node.className || '',
        text: (node.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 100),
      })),
      directChildren: children.map((node) => ({
        id: node.id || '',
        classes: node.className || '',
        visible: visible(node),
        heading: heading(node),
      })),
    };
  });
}

function assertShop(metric) {
  if (!metric) return failures.push('shop: sidebar missing');
  if (!metric.price.visible || metric.price.index !== 0 || !/precio/i.test(metric.price.heading)) {
    failures.push(`shop: price must be first (${JSON.stringify(metric.price)})`);
  }
  if (!metric.categories.visible || metric.categories.index !== 1 || !/categor/i.test(metric.categories.heading)) {
    failures.push(`shop: categories must be second (${JSON.stringify(metric.categories)})`);
  }
  if (!metric.vendor.visible || metric.vendor.index !== 2 || !/vendedor/i.test(metric.vendor.heading)) {
    failures.push(`shop: vendor must be third (${JSON.stringify(metric.vendor)})`);
  }
  if (metric.vendor.links < 1) failures.push(`shop: vendor choices missing (${JSON.stringify(metric.vendor)})`);
  if (metric.otherVisibleWidgets.length) failures.push(`shop: unexpected visible filter widgets (${JSON.stringify(metric.otherVisibleWidgets)})`);
}

function assertCategory(metric) {
  if (!metric) return failures.push('aceites: sidebar missing');
  if (!metric.price.visible || metric.price.index !== 0 || !/precio/i.test(metric.price.heading)) {
    failures.push(`aceites: price must be first (${JSON.stringify(metric.price)})`);
  }
  if (!metric.vendor.visible || metric.vendor.index !== 1 || !/vendedor/i.test(metric.vendor.heading)) {
    failures.push(`aceites: vendor must be second (${JSON.stringify(metric.vendor)})`);
  }
  if (metric.vendor.links < 1) failures.push(`aceites: vendor choices missing (${JSON.stringify(metric.vendor)})`);
  if (metric.specific.present && (!metric.specific.visible || metric.specific.index !== 2)) {
    failures.push(`aceites: specific filters must follow vendor (${JSON.stringify(metric.specific)})`);
  }
}

async function cartCopyCheck(page, productId) {
  await go(page, `/tienda/?add-to-cart=${productId}`, 900);
  await go(page, '/carrito/', 1200);
  const metric = await page.evaluate(() => {
    const destination = document.querySelector('.woocommerce-shipping-destination');
    const text = (destination?.textContent || '').replace(/\s+/g, ' ').trim();
    return { present: !!destination, text };
  });
  report.cart = metric;
  if (!metric.present) failures.push('cart: shipping destination line is missing');
  if (metric.present && !/^Enviar a\b/i.test(metric.text)) failures.push(`cart: expected "Enviar a", got "${metric.text}"`);
  if (/Enviará/i.test(metric.text)) failures.push(`cart: forbidden future-tense copy remains: "${metric.text}"`);
  await page.screenshot({ path: 'qa/request-010192-cart.png', fullPage: true });
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const products = await fetch(`${BASE}/wp-json/wc/store/v1/products?per_page=100`).then((response) => {
    if (!response.ok) throw new Error(`Product API HTTP ${response.status}`);
    return response.json();
  });
  const product = products.find((item) => item.is_purchasable && item.is_in_stock && item.type === 'simple' && item.is_virtual !== true);
  if (!product) throw new Error('No purchasable physical simple product available for cart QA');

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
    await waitForCatalogRelease(page);

    await go(page, '/tienda/', 1500);
    report.shop = await catalogSnapshot(page);
    assertShop(report.shop);
    await page.screenshot({ path: 'qa/request-010192-shop.png', fullPage: true });

    await go(page, '/categoria-producto/aceites/', 1500);
    report.aceites = await catalogSnapshot(page);
    assertCategory(report.aceites);
    await page.screenshot({ path: 'qa/request-010192-aceites.png', fullPage: true });

    await cartCopyCheck(page, product.id);
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/request-010192-smoke.json', JSON.stringify({ failures, report }, null, 2));
  if (failures.length) {
    console.error(`REQUEST_010192_FAIL ${JSON.stringify(failures)}`);
    process.exitCode = 2;
  } else {
    console.log(`REQUEST_010192_OK ${JSON.stringify(report)}`);
  }
})();
