const puppeteer = require('puppeteer-core');

const base = (process.env.BASE_URL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');
const adminCookieName = process.env.ADMIN_COOKIE_NAME || '';
const adminCookieValue = process.env.ADMIN_COOKIE_VALUE || '';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function makePage(browser, cookie = null) {
  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900, deviceScaleFactor: 1 });
  await page.setRequestInterception(true);
  page.on('request', (request) => {
    if (['image', 'media', 'font'].includes(request.resourceType())) request.abort();
    else request.continue();
  });
  if (cookie) await page.setCookie(cookie);
  return page;
}

async function go(page, path, label) {
  const url = new URL(path, base);
  url.searchParams.set('catalog-admin-diag-010224', `${Date.now()}-${label}`);
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (!response || response.status() >= 400) throw new Error(`${label}: HTTP ${response?.status()} ${url.href}`);
  await page.waitForSelector('body', { timeout: 20000 });
  await sleep(500);
}

async function homeState(page, label) {
  await go(page, '/', `${label}-home`);
  return page.evaluate(() => ({
    authenticated: document.body.classList.contains('logged-in') || Boolean(document.querySelector('#wpadminbar')),
    cards: [...document.querySelectorAll('.emo-category-card')].map((card) => ({
      name: card.querySelector('.emo-category-card__content strong')?.textContent?.trim() || '',
      count: Number.parseInt((card.querySelector('.emo-category-card__content small')?.textContent || '').replace(/[^0-9]/g, ''), 10) || 0,
      href: card.href || '',
    })),
  }));
}

async function surfaceState(page, path, label) {
  await go(page, path, label);
  await page.waitForSelector('ul.products > li.product, .products > li.product', { timeout: 30000 });

  const initial = await page.evaluate(() => {
    const ids = [...document.querySelectorAll('ul.products > li.product, .products > li.product')].map((item) => {
      const cls = [...item.classList].find((name) => /^post-\d+$/.test(name));
      return cls ? Number(cls.slice(5)) : 0;
    }).filter(Boolean);
    const count = (document.querySelector('.emo-catalog-result-count-010220')?.textContent || '').replace(/\s+/g, ' ').trim();
    const pagination = [...document.querySelectorAll('.woocommerce-pagination a[href], a.page-numbers[href], .woostify-pagination a[href]')].map((a) => a.href);
    return {
      authenticated: document.body.classList.contains('logged-in') || Boolean(document.querySelector('#wpadminbar')),
      count,
      ids,
      pagination,
      loader: window.__emoCatalogLoaderState ? {
        nextUrl: window.__emoCatalogLoaderState.nextUrl || '',
        shown: Number(window.__emoCatalogLoaderState.shown || 0),
        loading: Boolean(window.__emoCatalogLoaderState.loading),
      } : null,
    };
  });

  let stable = 0;
  let last = '';
  let final = null;
  const started = Date.now();
  while (Date.now() - started < 90000) {
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await sleep(400);
    final = await page.evaluate(() => {
      const ids = [...document.querySelectorAll('ul.products > li.product, .products > li.product')].map((item) => {
        const cls = [...item.classList].find((name) => /^post-\d+$/.test(name));
        return cls ? Number(cls.slice(5)) : 0;
      }).filter(Boolean);
      const loader = window.__emoCatalogLoaderState || null;
      return {
        count: (document.querySelector('.emo-catalog-result-count-010220')?.textContent || '').replace(/\s+/g, ' ').trim(),
        ids,
        uniqueIds: [...new Set(ids)].sort((a, b) => a - b),
        loader: loader ? { nextUrl: loader.nextUrl || '', shown: Number(loader.shown || 0), loading: Boolean(loader.loading), highestPage: Number(loader.highestPage || 0) } : null,
        pathname: location.pathname,
      };
    });
    const sig = `${final.uniqueIds.length}:${final.loader?.loading}:${final.loader?.nextUrl}`;
    if (sig === last) stable += 1; else stable = 0;
    last = sig;
    if (final.loader && !final.loader.loading && !final.loader.nextUrl) break;
    if (stable >= 25) break;
  }

  return { initial, final, elapsedMs: Date.now() - started };
}

(async () => {
  if (!adminCookieName || !adminCookieValue) throw new Error('Missing administrator cookie');
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const cookie = { name: adminCookieName, value: adminCookieValue, url: base, secure: true, httpOnly: true };
  try {
    const publicPage = await makePage(browser);
    const adminPage = await makePage(browser, cookie);
    const report = {
      publicHome: await homeState(publicPage, 'public'),
      adminHome: await homeState(adminPage, 'admin'),
      publicShop: await surfaceState(publicPage, '/tienda/', 'public-shop'),
      adminShop: await surfaceState(adminPage, '/tienda/', 'admin-shop'),
      publicJamones: await surfaceState(publicPage, '/categoria-producto/jamones-paletas/', 'public-jamones'),
      adminJamones: await surfaceState(adminPage, '/categoria-producto/jamones-paletas/', 'admin-jamones'),
    };
    console.log('CATALOG_ADMIN_TOTAL_DIAGNOSTIC_010224', JSON.stringify(report));
    await publicPage.close();
    await adminPage.close();
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
