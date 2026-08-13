const puppeteer = require('puppeteer-core');

const base = (process.env.STAGING_URL || 'https://dev.elmercadodeorigen.com').replace(/\/$/, '');
const payload = JSON.parse(process.env.CATALOG_AUDIT_JSON || '{}');
const adminCookieName = process.env.ADMIN_COOKIE_NAME || '';
const adminCookieValue = process.env.ADMIN_COOKIE_VALUE || '';
const failures = [];
const report = { public: {}, admin: {} };
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function normalizeUrl(value) {
  const url = new URL(value, base);
  url.searchParams.delete('catalog-query-parity-010224');
  url.hash = '';
  url.pathname = url.pathname.replace(/\/page\/\d+\/?$/i, '/').replace(/\/+$/, '/');
  return url.href;
}

function canonicalPageUrl(surfaceUrl, pageNumber) {
  const url = new URL(surfaceUrl, base);
  url.pathname = url.pathname.replace(/\/page\/\d+\/?$/i, '/').replace(/\/+$/, '/');
  if (pageNumber > 1) url.pathname = `${url.pathname}page/${pageNumber}/`;
  return url.href;
}

function expectedIdsFor(surface, mode) {
  return [...new Set((surface?.[mode] || []).map(Number).filter(Boolean))].sort((a, b) => a - b);
}

function sameIds(a, b) {
  const aa = [...new Set(a.map(Number).filter(Boolean))].sort((x, y) => x - y);
  const bb = [...new Set(b.map(Number).filter(Boolean))].sort((x, y) => x - y);
  return aa.length === bb.length && aa.every((id, index) => id === bb[index]);
}

function diffIds(actual, expected) {
  const a = new Set(actual.map(Number));
  const e = new Set(expected.map(Number));
  return {
    extra: [...a].filter((id) => !e.has(id)).sort((x, y) => x - y),
    missing: [...e].filter((id) => !a.has(id)).sort((x, y) => x - y),
  };
}

async function createPage(browser, cookie, javascriptEnabled) {
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

async function go(page, url, label) {
  const parsed = new URL(url, base);
  parsed.searchParams.set('catalog-query-parity-010224', `${Date.now()}-${label}`);
  const response = await page.goto(parsed.href, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (!response || response.status() >= 400) {
    throw new Error(`${label}: HTTP ${response?.status()} ${parsed.href}`);
  }
  await page.waitForSelector('body', { timeout: 20000 });
  return response;
}

async function pageState(page) {
  return page.evaluate(() => {
    const cards = [...document.querySelectorAll('ul.products > li.product, .products > li.product')];
    const productIds = cards.map((item) => {
      const postClass = [...item.classList].find((name) => /^post-\d+$/.test(name));
      if (postClass) return Number(postClass.slice(5));
      const raw = item.getAttribute('data-product_id') || item.querySelector('[data-product_id]')?.getAttribute('data-product_id') || '';
      return Number(raw) || 0;
    }).filter(Boolean);
    const countNode = document.querySelector('.emo-catalog-result-count-010220');
    const countText = (countNode?.textContent || '').replace(/\s+/g, ' ').trim();
    const countMatch = countText.match(/^(\d[\d.,]*)\s+resultados?$/i);
    const pagination = [...document.querySelectorAll('.woocommerce-pagination a[href], .woostify-pagination a[href], a.page-numbers[href]')].map((a) => ({
      href: a.href,
      text: (a.textContent || '').trim(),
      classes: a.className || '',
    }));
    const authenticated = document.body.classList.contains('logged-in') || Boolean(document.querySelector('#wpadminbar'));
    return {
      authenticated,
      productIds,
      countText,
      total: countMatch ? Number.parseInt(countMatch[1].replace(/[^0-9]/g, ''), 10) : null,
      pagination,
      pathname: location.pathname,
    };
  });
}

function paginationPageNumber(href) {
  try {
    const url = new URL(href, base);
    const pathMatch = url.pathname.match(/\/page\/(\d+)(?:\/|$)/i);
    if (pathMatch) return Number.parseInt(pathMatch[1], 10) || 1;
    for (const key of ['paged', 'product-page', 'product_page', 'page']) {
      const value = Number.parseInt(url.searchParams.get(key) || '', 10);
      if (value > 0) return value;
    }
  } catch (_) {}
  return 1;
}

async function validateServerSurface(browser, surface, mode, cookie, label) {
  const expected = expectedIdsFor(surface, mode);
  if (!expected.length) return { skipped: true, expected: 0 };

  const page = await createPage(browser, cookie, false);
  const collected = [];
  const pages = [];
  try {
    await go(page, canonicalPageUrl(surface.url, 1), `${label}-1`);
    const first = await pageState(page);
    if (first.authenticated !== (mode === 'admin')) failures.push(`${label}: authentication=${first.authenticated}`);
    if (first.total !== expected.length) failures.push(`${label}: total ${first.total}; expected ${expected.length}`);
    if (!/^\d+\s+resultados?$/.test(first.countText)) failures.push(`${label}: count text ${JSON.stringify(first.countText)}`);

    const perPage = Math.max(1, first.productIds.length);
    const maxPages = Math.max(1, Math.ceil(expected.length / perPage));
    const leakedPageLinks = first.pagination.map((link) => ({ ...link, page: paginationPageNumber(link.href) })).filter((link) => link.page > maxPages);
    if (leakedPageLinks.length) failures.push(`${label}: pagination exposes pages beyond ${maxPages}: ${JSON.stringify(leakedPageLinks)}`);

    collected.push(...first.productIds);
    pages.push({ page: 1, ids: first.productIds, total: first.total, pagination: first.pagination });

    for (let number = 2; number <= maxPages; number += 1) {
      await go(page, canonicalPageUrl(surface.url, number), `${label}-${number}`);
      const state = await pageState(page);
      if (state.total !== expected.length) failures.push(`${label} page ${number}: total ${state.total}; expected ${expected.length}`);
      collected.push(...state.productIds);
      pages.push({ page: number, ids: state.productIds, total: state.total, pagination: state.pagination });
      const bad = state.pagination.map((link) => ({ ...link, page: paginationPageNumber(link.href) })).filter((link) => link.page > maxPages);
      if (bad.length) failures.push(`${label} page ${number}: pagination exposes pages beyond ${maxPages}: ${JSON.stringify(bad)}`);
    }

    const unique = [...new Set(collected)].sort((a, b) => a - b);
    const duplicates = collected.length - unique.length;
    if (duplicates) failures.push(`${label}: ${duplicates} duplicate product cards across server pages`);
    if (!sameIds(unique, expected)) failures.push(`${label}: server product set mismatch ${JSON.stringify(diffIds(unique, expected))}`);

    // The next canonical page must not exist once the exact set is exhausted.
    const beyond = canonicalPageUrl(surface.url, maxPages + 1);
    const response = await page.goto(beyond, { waitUntil: 'domcontentloaded', timeout: 90000 }).catch(() => null);
    if (response && response.status() < 400) {
      const beyondState = await pageState(page).catch(() => null);
      if (beyondState?.productIds?.length) failures.push(`${label}: page ${maxPages + 1} still returns ${beyondState.productIds.length} products`);
    }

    return { expected: expected.length, perPage, maxPages, uniqueIds: unique, pages };
  } finally {
    await page.close();
  }
}

async function validateContinuousSurface(browser, surface, mode, cookie, label) {
  const expected = expectedIdsFor(surface, mode);
  if (!expected.length) return { skipped: true, expected: 0 };

  const page = await createPage(browser, cookie, true);
  try {
    await go(page, surface.url, `${label}-continuous`);
    await page.waitForSelector('ul.products > li.product, .products > li.product', { timeout: 30000 });

    const expectedLabel = `${expected.length.toLocaleString('es-ES')} ${expected.length === 1 ? 'resultado' : 'resultados'}`;
    let stable = 0;
    let previous = '';
    let finalState = null;
    const started = Date.now();

    while (Date.now() - started < 90000) {
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await sleep(300);
      finalState = await page.evaluate(() => {
        const cards = [...document.querySelectorAll('ul.products > li.product, .products > li.product')];
        const ids = cards.map((item) => {
          const postClass = [...item.classList].find((name) => /^post-\d+$/.test(name));
          if (postClass) return Number(postClass.slice(5));
          const raw = item.getAttribute('data-product_id') || item.querySelector('[data-product_id]')?.getAttribute('data-product_id') || '';
          return Number(raw) || 0;
        }).filter(Boolean);
        const loader = window.__emoCatalogLoaderState || null;
        return {
          ids,
          countText: (document.querySelector('.emo-catalog-result-count-010220')?.textContent || '').replace(/\s+/g, ' ').trim(),
          loading: Boolean(loader?.loading),
          nextUrl: loader?.nextUrl || '',
          shown: Number(loader?.shown || ids.length),
          highestPage: Number(loader?.highestPage || 0),
        };
      });

      if (finalState.countText !== expectedLabel) failures.push(`${label}: continuous count changed to ${JSON.stringify(finalState.countText)}; expected ${JSON.stringify(expectedLabel)}`);
      const signature = `${finalState.ids.length}:${finalState.loading}:${finalState.nextUrl}`;
      if (signature === previous) stable += 1; else stable = 0;
      previous = signature;

      if (!finalState.loading && !finalState.nextUrl) break;
      if (stable > 30) {
        failures.push(`${label}: continuous loader stalled with next=${finalState.nextUrl}`);
        break;
      }
    }

    const unique = [...new Set((finalState?.ids || []).map(Number))].sort((a, b) => a - b);
    if (!sameIds(unique, expected)) failures.push(`${label}: continuous product set mismatch ${JSON.stringify(diffIds(unique, expected))}`);
    if ((finalState?.ids || []).length !== unique.length) failures.push(`${label}: continuous loader duplicated product cards`);
    return { expected: expected.length, ...finalState, uniqueIds: unique };
  } finally {
    await page.close();
  }
}

async function validateHome(browser, mode, cookie) {
  const page = await createPage(browser, cookie, false);
  try {
    await go(page, `${base}/`, `${mode}-home`);
    const state = await page.evaluate(() => ({
      authenticated: document.body.classList.contains('logged-in') || Boolean(document.querySelector('#wpadminbar')),
      cards: [...document.querySelectorAll('.emo-category-card')].map((card) => ({
        href: card.href || '',
        name: card.querySelector('.emo-category-card__content strong')?.textContent?.trim() || '',
        count: Number.parseInt((card.querySelector('.emo-category-card__content small')?.textContent || '').replace(/[^0-9]/g, ''), 10) || 0,
      })),
    }));
    if (state.authenticated !== (mode === 'admin')) failures.push(`${mode} Home: authentication=${state.authenticated}`);

    const displayed = new Map(state.cards.map((card) => [normalizeUrl(card.href), card]));
    for (const category of payload.categories || []) {
      const expected = expectedIdsFor(category, mode).length;
      const card = displayed.get(normalizeUrl(category.url));
      if (expected > 0) {
        if (!card) failures.push(`${mode} Home: missing ${category.name} (${expected})`);
        else if (card.count !== expected) failures.push(`${mode} Home: ${category.name} shows ${card.count}; expected ${expected}`);
      } else if (card) {
        failures.push(`${mode} Home: zero category ${category.name} is visible`);
      }
    }
    return state;
  } finally {
    await page.close();
  }
}

(async () => {
  if (!payload?.shop?.url || !Array.isArray(payload?.shop?.public) || !Array.isArray(payload?.shop?.admin)) throw new Error('Missing CATALOG_AUDIT_JSON');
  if (!adminCookieName || !adminCookieValue) throw new Error('Missing administrator cookie');

  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    protocolTimeout: 180000,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const adminCookie = { name: adminCookieName, value: adminCookieValue, url: base, secure: true, httpOnly: true };

  try {
    for (const [mode, cookie] of [['public', null], ['admin', adminCookie]]) {
      report[mode].home = await validateHome(browser, mode, cookie);
      report[mode].shop = await validateServerSurface(browser, payload.shop, mode, cookie, `${mode}-shop`);
      report[mode].categories = {};
      for (const category of payload.categories || []) {
        if (!expectedIdsFor(category, mode).length) continue;
        report[mode].categories[category.slug] = await validateServerSurface(browser, category, mode, cookie, `${mode}-${category.slug}`);
      }
      report[mode].continuous = {};
      report[mode].continuous.shop = await validateContinuousSurface(browser, payload.shop, mode, cookie, `${mode}-shop`);
      const jamones = (payload.categories || []).find((category) => /jamones/i.test(category.name || ''));
      if (jamones && expectedIdsFor(jamones, mode).length) {
        report[mode].continuous.jamones = await validateContinuousSurface(browser, jamones, mode, cookie, `${mode}-jamones`);
      }
    }
  } finally {
    await browser.close();
  }

  console.log('CATALOG_QUERY_PARITY_010224_REPORT', JSON.stringify({
    disabledVendorIds: payload.disabled_vendor_ids || [],
    expected: {
      publicShop: expectedIdsFor(payload.shop, 'public').length,
      adminShop: expectedIdsFor(payload.shop, 'admin').length,
      categories: (payload.categories || []).map((category) => ({
        name: category.name,
        public: expectedIdsFor(category, 'public').length,
        admin: expectedIdsFor(category, 'admin').length,
      })),
    },
    failures,
    report,
  }));

  if (failures.length) {
    console.error('CATALOG_QUERY_PARITY_010224_FAIL', JSON.stringify(failures));
    process.exit(2);
  }
  console.log('CATALOG_QUERY_PARITY_010224_OK');
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
