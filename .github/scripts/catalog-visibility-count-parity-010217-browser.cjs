const puppeteer = require('puppeteer-core');

const base = (process.env.STAGING_URL || 'https://dev.elmercadodeorigen.com').replace(/\/$/, '');
const attributePayload = JSON.parse(process.env.ATTRIBUTE_PARITY_JSON || '{}');
const adminCookieName = process.env.ADMIN_COOKIE_NAME || '';
const adminCookieValue = process.env.ADMIN_COOKIE_VALUE || '';

function addBust(url, label) {
  const parsed = new URL(url, base);
  parsed.searchParams.set('count-parity-010218', `${Date.now()}-${label}`);
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

async function resultState(page, label) {
  return page.evaluate((context) => {
    const node = document.querySelector('.woocommerce-result-count');
    const text = node?.textContent?.replace(/\s+/g, ' ').trim() || '';
    const match = text.match(/^(\d[\d.,]*)\s+resultado(?:s)?$/i);
    if (!match) {
      throw new Error(`${context}: texto de resultados no simplificado: "${text}"`);
    }
    const total = Number.parseInt(match[1].replace(/[^0-9]/g, ''), 10);
    const ordering = document.querySelector('.woocommerce-ordering');
    const orderingVisible = Boolean(ordering && getComputedStyle(ordering).display !== 'none' && ordering.getClientRects().length);
    return { text, total, orderingVisible };
  }, label);
}

async function assertResultState(page, expected, label) {
  const state = await resultState(page, label);
  if (state.total !== Number(expected)) {
    throw new Error(`${label}: total visible ${state.total}; esperado ${expected}`);
  }
  if (state.orderingVisible) {
    throw new Error(`${label}: el selector/texto de ordenación sigue visible`);
  }
  return state;
}

async function collectShopFilters(page, label) {
  await goto(page, `${base}/tienda/`, label);
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

    const categories = [...document.querySelectorAll('.widget_product_categories li.cat-item')]
      .filter((item) => Boolean(item.querySelector(':scope > .count')))
      .map((item) => ({
        kind: 'category',
        name: item.querySelector(':scope > a')?.textContent?.trim() || item.querySelector('a')?.textContent?.trim() || '',
        href: item.querySelector(':scope > a')?.href || item.querySelector('a')?.href || '',
        count: countFrom(item),
      }));

    return { vendors, categories };
  });
}

async function collectAttributeFilters(page, label) {
  const categoryUrl = attributePayload?.category?.url || `${base}/categoria-producto/embutidos-y-curados/`;
  await goto(page, categoryUrl, label);
  await page.waitForSelector('#emo-category-attribute-filters', { timeout: 30000 });

  return page.evaluate(() => [...document.querySelectorAll('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item')].map((item) => {
    const link = item.querySelector('a');
    const countText = item.querySelector('.count')?.textContent || '';
    const digits = countText.replace(/[^0-9]/g, '');
    let attribute = '';
    let slug = '';

    if (link?.href) {
      const parsed = new URL(link.href);
      const key = [...parsed.searchParams.keys()].find((candidate) => candidate.startsWith('filter_')) || '';
      if (key) {
        attribute = key.slice('filter_'.length);
        const values = (parsed.searchParams.get(key) || '').split(',').filter(Boolean);
        slug = values[values.length - 1] || '';
      }
    }

    const group = item.closest('.emo-category-filter-group')?.querySelector('.emo-category-filter-title')?.textContent?.trim() || '';
    return {
      kind: `attribute:${group}`,
      attribute,
      slug,
      name: link?.textContent?.trim() || '',
      href: link?.href || '',
      count: digits ? Number.parseInt(digits, 10) : 0,
    };
  }));
}

async function validateLinkedEntries(browser, entries, label, cookie = null) {
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
    if (cookie) await page.setCookie(cookie);
    try {
      await goto(page, entry.href, `${label}-${index}`);
      await assertResultState(page, entry.count, `${label}:${entry.kind}:${entry.name}`);
    } finally {
      await page.close();
    }
  }
}

function validateAttributes(entries, field, label) {
  const expectedRows = Array.isArray(attributePayload?.rows) ? attributePayload.rows : [];
  if (!expectedRows.length) throw new Error(`${label}: faltan expectativas de atributos`);

  const displayed = new Map();
  for (const entry of entries) {
    if (!entry.attribute || !entry.slug) {
      throw new Error(`${label}: no se pudo identificar ${entry.kind} ${entry.name}`);
    }
    const key = `${entry.attribute}:${entry.slug}`;
    if (displayed.has(key)) throw new Error(`${label}: atributo duplicado ${key}`);
    displayed.set(key, entry);
  }

  for (const row of expectedRows) {
    const key = `${row.attribute}:${row.slug}`;
    const expected = Number(row[field] || 0);
    const entry = displayed.get(key);

    if (expected <= 0) {
      if (entry) {
        throw new Error(`${label}: ${row.label} / ${row.name} debería estar a 0 y no mostrarse, pero aparece con ${entry.count}`);
      }
      continue;
    }

    if (!entry) {
      throw new Error(`${label}: falta ${row.label} / ${row.name}; se esperaban ${expected} productos`);
    }
    if (entry.count !== expected) {
      throw new Error(`${label}: ${row.label} / ${row.name} muestra ${entry.count}; esperado ${expected}`);
    }
  }

  for (const [key, entry] of displayed) {
    const expected = expectedRows.find((row) => `${row.attribute}:${row.slug}` === key);
    if (!expected) throw new Error(`${label}: opción inesperada ${entry.kind} ${entry.name}`);
    if (entry.count <= 0) throw new Error(`${label}: ${entry.kind} ${entry.name} aparece con count ${entry.count}`);
  }
}

async function validateAttributeResultLinks(browser, entries, field, label, cookie = null) {
  const expectedRows = Array.isArray(attributePayload?.rows) ? attributePayload.rows : [];
  for (const [index, entry] of entries.entries()) {
    const expected = expectedRows.find((row) => row.attribute === entry.attribute && row.slug === entry.slug);
    if (!expected) continue;
    const expectedCount = Number(expected[field] || 0);
    if (expectedCount <= 0) continue;

    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000 });
    if (cookie) await page.setCookie(cookie);
    try {
      await goto(page, entry.href, `${label}-${index}`);
      await assertResultState(page, expectedCount, `${label}:${entry.kind}:${entry.name}`);
    } finally {
      await page.close();
    }
  }
}

(async () => {
  if (!attributePayload?.rows?.length) throw new Error('Missing ATTRIBUTE_PARITY_JSON');
  if (!attributePayload?.totals) throw new Error('Missing catalog totals');
  if (!adminCookieName || !adminCookieValue) throw new Error('Missing administrator cookie');
  if (attributePayload.hide_out_of_stock !== true) throw new Error('WooCommerce is not configured to hide out-of-stock products');

  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  const adminCookie = {
    name: adminCookieName,
    value: adminCookieValue,
    url: base,
    secure: true,
    httpOnly: true,
  };

  try {
    const publicPage = await browser.newPage();
    await publicPage.setViewport({ width: 1440, height: 1000 });

    const publicShop = await collectShopFilters(publicPage, 'shop-public');
    const publicShopState = await assertResultState(publicPage, attributePayload.totals.public_shop, 'shop-public-total');
    await validateLinkedEntries(browser, publicShop.vendors, 'shop-vendors-public');
    if (publicShop.categories.length) {
      await validateLinkedEntries(browser, publicShop.categories, 'shop-categories-public');
    }

    const publicAttributes = await collectAttributeFilters(publicPage, 'attributes-public');
    const publicCategoryState = await assertResultState(publicPage, attributePayload.totals.public_category, 'category-public-total');
    validateAttributes(publicAttributes, 'public', 'category-attributes-public');
    await validateAttributeResultLinks(browser, publicAttributes, 'public', 'category-attribute-results-public');

    const adminPage = await browser.newPage();
    await adminPage.setViewport({ width: 1440, height: 1000 });
    await adminPage.setCookie(adminCookie);
    await goto(adminPage, `${base}/tienda/`, 'admin-auth-check');
    const authenticated = await adminPage.evaluate(() => document.body.classList.contains('logged-in') || Boolean(document.querySelector('#wpadminbar')));
    if (!authenticated) throw new Error('administrator browser session is not authenticated');

    const adminShop = await collectShopFilters(adminPage, 'shop-admin');
    const adminShopState = await assertResultState(adminPage, attributePayload.totals.admin_shop, 'shop-admin-total');
    await validateLinkedEntries(browser, adminShop.vendors, 'shop-vendors-admin', adminCookie);

    const adminAttributes = await collectAttributeFilters(adminPage, 'attributes-admin');
    const adminCategoryState = await assertResultState(adminPage, attributePayload.totals.admin_category, 'category-admin-total');
    validateAttributes(adminAttributes, 'admin', 'category-attributes-admin');
    await validateAttributeResultLinks(browser, adminAttributes, 'admin', 'category-attribute-results-admin', adminCookie);

    const lomo = attributePayload.rows.filter((row) => /lomo/i.test(`${row.slug} ${row.name}`));
    console.log('CATALOG_VISIBILITY_COUNT_PARITY_010218_OK', JSON.stringify({
      hideOutOfStock: attributePayload.hide_out_of_stock,
      disabledVendorIds: attributePayload.disabled_vendor_ids || [],
      totals: attributePayload.totals,
      lomo,
      publicShopState,
      adminShopState,
      publicCategoryState,
      adminCategoryState,
      publicVendors: publicShop.vendors,
      adminVendors: adminShop.vendors,
      publicAttributes,
      adminAttributes,
    }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});