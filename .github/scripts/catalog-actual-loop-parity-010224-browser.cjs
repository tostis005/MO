const puppeteer = require('puppeteer-core');

const base = (process.env.STAGING_URL || 'https://dev.elmercadodeorigen.com').replace(/\/$/, '');
const payload = JSON.parse(process.env.CATALOG_AUDIT_JSON || '{}');
const adminCookieName = process.env.ADMIN_COOKIE_NAME || '';
const adminCookieValue = process.env.ADMIN_COOKIE_VALUE || '';
const failures = [];
const report = { public: {}, admin: {} };
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function norm(url) {
  const parsed = new URL(url, base);
  parsed.hash = '';
  return `${parsed.origin}${parsed.pathname.replace(/\/+$/, '')}/`;
}

function sameIds(actual, expected) {
  const a = [...new Set(actual.map(Number))].sort((x, y) => x - y);
  const e = [...new Set(expected.map(Number))].sort((x, y) => x - y);
  return a.length === e.length && a.every((id, index) => id === e[index]);
}

function diffIds(actual, expected) {
  const a = new Set(actual.map(Number));
  const e = new Set(expected.map(Number));
  return {
    extra: [...a].filter((id) => !e.has(id)).sort((x, y) => x - y),
    missing: [...e].filter((id) => !a.has(id)).sort((x, y) => x - y),
  };
}

async function setupPage(browser, cookie, javascriptEnabled = true) {
  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900, deviceScaleFactor: 1 });
  await page.setJavaScriptEnabled(javascriptEnabled);
  await page.setRequestInterception(true);
  page.on('request', (request) => {
    if (['image', 'media', 'font'].includes(request.resourceType())) request.abort();
    else request.continue();
  });
  if (cookie) await page.setCookie(cookie);
  return page;
}

async function goto(page, url, label) {
  const parsed = new URL(url, base);
  parsed.searchParams.set('catalog-loop-audit-010224', `${Date.now()}-${label}`);
  const response = await page.goto(parsed.href, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (!response || response.status() >= 400) throw new Error(`${label}: HTTP ${response?.status()} ${parsed.href}`);
  await page.waitForSelector('body', { timeout: 20000 });
  return response;
}

async function readServerPage(page) {
  return page.evaluate(() => {
    const productIds = [...document.querySelectorAll('ul.products > li.product, .products > li.product')].map((item) => {
      const postClass = [...item.classList].find((name) => /^post-\d+$/.test(name));
      if (postClass) return Number(postClass.slice(5));
      const attr = item.getAttribute('data-product_id') || item.querySelector('[data-product_id]')?.getAttribute('data-product_id') || '';
      return Number(attr) || 0;
    }).filter(Boolean);
    const exact = document.querySelector('.emo-catalog-result-count-010220');
    const text = (exact?.textContent || '').replace(/\s+/g, ' ').trim();
    const match = text.match(/^(\d[\d.,]*)\s+resultados?$/i);
    const next = document.querySelector('.woocommerce-pagination a.next[href], a.page-numbers.next[href], .woostify-pagination a.next[href], a[rel~="next"][href]');
    return {
      authenticated: document.body.classList.contains('logged-in') || Boolean(document.querySelector('#wpadminbar')),
      productIds,
      totalText: text,
      total: match ? Number.parseInt(match[1].replace(/[^0-9]/g, ''), 10) : null,
      next: next?.href || '',
      pathname: location.pathname,
    };
  });
}

async function collectServerPagination(browser, url, cookie, label) {
  const page = await setupPage(browser, cookie, false);
  const all = [];
  const seenUrls = new Set();
  const pages = [];
  let next = url;
  let first = null;
  try {
    for (let index = 1; next && index <= 50; index += 1) {
      const current = new URL(next, base);
      current.searchParams.delete('catalog-loop-audit-010224');
      const key = current.href;
      if (seenUrls.has(key)) throw new Error(`${label}: pagination cycle at ${key}`);
      seenUrls.add(key);
      await goto(page, key, `${label}-page-${index}`);
      const state = await readServerPage(page);
      if (!first) first = state;
      pages.push({ index, url: page.url(), count: state.productIds.length, next: state.next, total: state.total, ids: state.productIds });
      all.push(...state.productIds);
      next = state.next;
    }
  } finally {
    await page.close();
  }
  return { first, pages, ids: [...new Set(all)].sort((a, b) => a - b), duplicates: all.length - new Set(all).size };
}

async function readHome(browser, cookie, label) {
  const page = await setupPage(browser, cookie, false);
  try {
    await goto(page, `${base}/`, `${label}-home`);
    return await page.evaluate(() => ({
      authenticated: document.body.classList.contains('logged-in') || Boolean(document.querySelector('#wpadminbar')),
      cards: [...document.querySelectorAll('.emo-category-card')].map((card) => ({
        href: card.href,
        name: card.querySelector('.emo-category-card__content strong')?.textContent?.trim() || '',
        count: Number.parseInt((card.querySelector('.emo-category-card__content small')?.textContent || '').replace(/[^0-9]/g, ''), 10) || 0,
      })),
    }));
  } finally {
    await page.close();
  }
}

async function collectContinuous(browser, url, cookie, label) {
  const page = await setupPage(browser, cookie, true);
  try {
    await goto(page, url, `${label}-continuous`);
    await page.waitForSelector('ul.products > li.product, .products > li.product', { timeout: 30000 });
    let previousSignature = '';
    let stable = 0;
    const started = Date.now();
    while (Date.now() - started < 90000) {
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await sleep(350);
      const state = await page.evaluate(() => {
        const ids = [...document.querySelectorAll('ul.products > li.product, .products > li.product')].map((item) => {
          const postClass = [...item.classList].find((name) => /^post-\d+$/.test(name));
          if (postClass) return Number(postClass.slice(5));
          const attr = item.getAttribute('data-product_id') || item.querySelector('[data-product_id]')?.getAttribute('data-product_id') || '';
          return Number(attr) || 0;
        }).filter(Boolean);
        const loader = window.__emoCatalogLoaderState || null;
        const exact = document.querySelector('.emo-catalog-result-count-010220');
        return {
          ids,
          loading: Boolean(loader?.loading),
          nextUrl: loader?.nextUrl || '',
          shown: Number(loader?.shown || ids.length),
          totalText: (exact?.textContent || '').replace(/\s+/g, ' ').trim(),
        };
      });
      const signature = `${state.ids.length}:${state.loading}:${state.nextUrl}`;
      if (!state.loading && !state.nextUrl) return { ...state, ids: [...new Set(state.ids)].sort((a, b) => a - b) };
      if (signature === previousSignature) stable += 1;
      else stable = 0;
      previousSignature = signature;
      if (stable > 30) return { ...state, timeoutStable: true, ids: [...new Set(state.ids)].sort((a, b) => a - b) };
    }
    const final = await page.evaluate(() => ({
      ids: [...document.querySelectorAll('ul.products > li.product, .products > li.product')].map((item) => {
        const postClass = [...item.classList].find((name) => /^post-\d+$/.test(name));
        return postClass ? Number(postClass.slice(5)) : 0;
      }).filter(Boolean),
      loading: Boolean(window.__emoCatalogLoaderState?.loading),
      nextUrl: window.__emoCatalogLoaderState?.nextUrl || '',
      shown: Number(window.__emoCatalogLoaderState?.shown || 0),
      totalText: (document.querySelector('.emo-catalog-result-count-010220')?.textContent || '').replace(/\s+/g, ' ').trim(),
    }));
    return { ...final, timedOut: true, ids: [...new Set(final.ids)].sort((a, b) => a - b) };
  } finally {
    await page.close();
  }
}

async function validateMode(browser, mode, cookie) {
  const expectedShop = payload?.shop?.[mode] || [];
  const home = await readHome(browser, cookie, mode);
  report[mode].home = home;
  const shouldAuth = mode === 'admin';
  if (home.authenticated !== shouldAuth) failures.push(`${mode}: unexpected authentication state on Home`);

  const homeByUrl = new Map(home.cards.map((card) => [norm(card.href), card]));
  const categories = Array.isArray(payload.categories) ? payload.categories : [];
  for (const category of categories) {
    const expected = category?.[mode] || [];
    const card = homeByUrl.get(norm(category.url));
    if (expected.length > 0) {
      if (!card) failures.push(`${mode}: Home missing category ${category.name} (${expected.length})`);
      else if (card.count !== expected.length) failures.push(`${mode}: Home ${category.name} shows ${card.count}; expected ${expected.length}`);
    } else if (card) {
      failures.push(`${mode}: Home shows zero category ${category.name}`);
    }
  }

  const shop = await collectServerPagination(browser, payload.shop.url, cookie, `${mode}-shop`);
  report[mode].shop = shop;
  if (shop.first?.total !== expectedShop.length) failures.push(`${mode}: shop result total ${shop.first?.total}; expected ${expectedShop.length}`);
  if (shop.duplicates) failures.push(`${mode}: shop pagination has ${shop.duplicates} duplicate cards`);
  if (!sameIds(shop.ids, expectedShop)) failures.push(`${mode}: shop server loop mismatch ${JSON.stringify(diffIds(shop.ids, expectedShop))}`);

  report[mode].categories = {};
  for (const category of categories) {
    const expected = category?.[mode] || [];
    if (!expected.length) continue;
    const surface = await collectServerPagination(browser, category.url, cookie, `${mode}-cat-${category.slug}`);
    report[mode].categories[category.slug] = surface;
    if (surface.first?.total !== expected.length) failures.push(`${mode}: ${category.name} result total ${surface.first?.total}; expected ${expected.length}`);
    if (surface.duplicates) failures.push(`${mode}: ${category.name} pagination has ${surface.duplicates} duplicate cards`);
    if (!sameIds(surface.ids, expected)) failures.push(`${mode}: ${category.name} server loop mismatch ${JSON.stringify(diffIds(surface.ids, expected))}`);
  }

  // La carga continua es la experiencia real del usuario: auditamos Tienda y Jamones y paletas.
  const jamones = categories.find((category) => /jamones/i.test(category.name || ''));
  report[mode].continuous = {};
  const shopContinuous = await collectContinuous(browser, payload.shop.url, cookie, `${mode}-shop`);
  report[mode].continuous.shop = shopContinuous;
  if (!sameIds(shopContinuous.ids, expectedShop)) failures.push(`${mode}: shop continuous mismatch ${JSON.stringify(diffIds(shopContinuous.ids, expectedShop))}`);
  if (jamones && (jamones?.[mode] || []).length) {
    const jamonesExpected = jamones[mode];
    const jamonesContinuous = await collectContinuous(browser, jamones.url, cookie, `${mode}-jamones`);
    report[mode].continuous.jamones = jamonesContinuous;
    if (!sameIds(jamonesContinuous.ids, jamonesExpected)) failures.push(`${mode}: Jamones continuous mismatch ${JSON.stringify(diffIds(jamonesContinuous.ids, jamonesExpected))}`);
  }
}

(async () => {
  if (!payload?.shop?.url || !Array.isArray(payload?.shop?.public) || !Array.isArray(payload?.shop?.admin)) throw new Error('Missing CATALOG_AUDIT_JSON');
  if (!adminCookieName || !adminCookieValue) throw new Error('Missing administrator cookie');

  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const adminCookie = { name: adminCookieName, value: adminCookieValue, url: base, secure: true, httpOnly: true };

  try {
    await validateMode(browser, 'public', null);
    await validateMode(browser, 'admin', adminCookie);
  } finally {
    await browser.close();
  }

  console.log('CATALOG_ACTUAL_LOOP_PARITY_010224_REPORT', JSON.stringify({
    disabledVendorIds: payload.disabled_vendor_ids || [],
    expected: {
      publicShop: payload.shop.public.length,
      adminShop: payload.shop.admin.length,
      categories: (payload.categories || []).map((c) => ({ name: c.name, public: c.public.length, admin: c.admin.length })),
    },
    failures,
    report,
  }));

  if (failures.length) {
    console.error('CATALOG_ACTUAL_LOOP_PARITY_010224_FAIL', JSON.stringify(failures));
    process.exit(2);
  }
  console.log('CATALOG_ACTUAL_LOOP_PARITY_010224_OK');
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
