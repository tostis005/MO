const puppeteer = require('puppeteer-core');

const base = (process.env.STAGING_URL || 'https://dev.elmercadodeorigen.com').replace(/\/$/, '');

function addBust(url, label) {
  const parsed = new URL(url, base);
  parsed.searchParams.set('count-parity-010217', `${Date.now()}-${label}`);
  return parsed.toString();
}

async function goto(page, url, label) {
  const response = await page.goto(addBust(url, label), {
    waitUntil: 'domcontentloaded',
    timeout: 90000,
  });
  if (!response || response.status() >= 400) {
    throw new Error(`${label}: HTTP ${response?.status()} ${url}`);
  }
  await new Promise((resolve) => setTimeout(resolve, 500));
}

async function resultTotal(page, label) {
  return page.evaluate((context) => {
    const text = document.querySelector('.woocommerce-result-count')?.textContent?.trim() || '';
    if (/único resultado|un resultado/i.test(text)) return 1;
    const numbers = [...text.matchAll(/[0-9][0-9.,]*/g)].map((m) => Number.parseInt(m[0].replace(/[^0-9]/g, ''), 10));
    if (numbers.length) return numbers[numbers.length - 1];

    const products = document.querySelectorAll('ul.products li.product, .products .product');
    const next = document.querySelector('.woocommerce-pagination .next, a.next.page-numbers');
    if (!next) return products.length;
    throw new Error(`${context}: no se pudo obtener el total de resultados`);
  }, label);
}

async function collectShopFilters(page) {
  await goto(page, `${base}/tienda/`, 'shop');
  await page.waitForSelector('body.woocommerce-shop, body.post-type-archive-product', { timeout: 30000 });

  return page.evaluate(() => {
    const countFrom = (node) => {
      const count = node?.querySelector('.count')?.textContent || '';
      const digits = count.replace(/[^0-9]/g, '');
      return digits ? Number.parseInt(digits, 10) : 0;
    };

    const vendors = [...document.querySelectorAll('#emo-global-vendor-filter li[data-vendor-id]')].map((item) => ({
      kind: 'vendor',
      name: item.querySelector('a')?.textContent?.trim() || '',
      href: item.querySelector('a')?.href || '',
      count: countFrom(item),
    }));

    const categories = [...document.querySelectorAll('.widget_product_categories li.cat-item')].map((item) => ({
      kind: 'category',
      name: item.querySelector(':scope > a')?.textContent?.trim() || item.querySelector('a')?.textContent?.trim() || '',
      href: item.querySelector(':scope > a')?.href || item.querySelector('a')?.href || '',
      count: countFrom(item),
    }));

    return { vendors, categories };
  });
}

async function collectAttributeFilters(page) {
  await goto(page, `${base}/categoria-producto/embutidos-y-curados/`, 'attributes');
  await page.waitForSelector('#emo-category-attribute-filters', { timeout: 30000 });

  return page.evaluate(() => [...document.querySelectorAll('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item')].map((item) => {
    const link = item.querySelector('a');
    const countText = item.querySelector('.count')?.textContent || '';
    const digits = countText.replace(/[^0-9]/g, '');
    const group = item.closest('.emo-category-filter-group')?.querySelector('.emo-category-filter-title')?.textContent?.trim() || '';
    return {
      kind: `attribute:${group}`,
      name: link?.textContent?.trim() || '',
      href: link?.href || '',
      count: digits ? Number.parseInt(digits, 10) : 0,
    };
  }));
}

async function validateEntries(browser, entries, label) {
  if (!entries.length) {
    throw new Error(`${label}: no se encontraron opciones para validar`);
  }

  for (const entry of entries) {
    if (!entry.href) throw new Error(`${label}: ${entry.kind} ${entry.name} no tiene enlace`);
    if (entry.count <= 0) throw new Error(`${label}: ${entry.kind} ${entry.name} se muestra con count ${entry.count}`);
  }

  for (const [index, entry] of entries.entries()) {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000 });
    try {
      await goto(page, entry.href, `${label}-${index}`);
      const total = await resultTotal(page, `${label}:${entry.kind}:${entry.name}`);
      if (total !== entry.count) {
        throw new Error(`${label}: ${entry.kind} ${entry.name} muestra ${entry.count} en el filtro, pero su listado devuelve ${total}`);
      }
    } finally {
      await page.close();
    }
  }
}

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000 });

    const shop = await collectShopFilters(page);
    await validateEntries(browser, shop.vendors, 'shop-vendors');
    await validateEntries(browser, shop.categories, 'shop-categories');

    const attributes = await collectAttributeFilters(page);
    await validateEntries(browser, attributes, 'category-attributes');

    console.log('CATALOG_VISIBILITY_COUNT_PARITY_010217_OK', JSON.stringify({
      vendors: shop.vendors,
      categories: shop.categories,
      attributes,
    }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});