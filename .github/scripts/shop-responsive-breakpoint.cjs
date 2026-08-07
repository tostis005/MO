const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];

async function go(page, path) {
  const url = `${BASE}${path}${path.includes('?') ? '&' : '?'}qa=${Date.now()}`;
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await sleep(650);
}

async function metrics(page) {
  return page.evaluate(() => {
    const visible = (el) => {
      if (!el) return false;
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
    };
    const primary = document.querySelector('#primary,.content-area');
    const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area');
    const toggle = document.querySelector('.emo-mobile-filter-toggle');
    const shell = document.querySelector('.emo-mobile-filter-shell');
    const product = document.querySelector('ul.products li.product');
    const title = product?.querySelector('.woocommerce-loop-product__title,.product-title,h2,h3');
    const price = product?.querySelector('.price');
    const pr = primary?.getBoundingClientRect();
    const tr = title?.getBoundingClientRect();
    const rr = price?.getBoundingClientRect();
    const ts = title ? getComputedStyle(title) : null;
    return {
      viewport: window.innerWidth,
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 3,
      primaryRatio: pr ? pr.width / document.documentElement.clientWidth : 0,
      sidebarVisible: visible(sidebar),
      sidebarInDrawer: !!sidebar?.closest('.emo-mobile-filter-content'),
      sidebarInSiteContent: !!sidebar?.closest('.site-content'),
      toggleVisible: visible(toggle),
      shellHidden: shell ? shell.hidden : true,
      titleHeight: tr?.height || 0,
      titleLineHeight: ts ? parseFloat(ts.lineHeight) || 0 : 0,
      titlePriceGap: tr && rr ? Math.round(rr.top - tr.bottom) : null,
    };
  });
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const page = await browser.newPage();
  try {
    for (const width of [1440, 1200, 1101, 1100, 1099, 1024, 992, 991, 900, 768, 390]) {
      await page.setViewport({ width, height: 900, deviceScaleFactor: 1 });
      await go(page, '/tienda/');
      const m = await metrics(page);
      console.log(`${width}px ${JSON.stringify(m)}`);
      if (m.overflow) failures.push(`${width}px: horizontal overflow`);
      if (width >= 1101) {
        if (!m.sidebarVisible) failures.push(`${width}px: desktop sidebar not visible`);
        if (m.toggleVisible) failures.push(`${width}px: compact filter toggle visible on desktop`);
      } else {
        if (!m.toggleVisible) failures.push(`${width}px: filter toggle not visible`);
        if (!m.sidebarInDrawer) failures.push(`${width}px: sidebar not moved into drawer`);
        if (m.sidebarInSiteContent) failures.push(`${width}px: sidebar still reserves site-content space`);
        if (m.primaryRatio < 0.94) failures.push(`${width}px: catalog not full width (${m.primaryRatio.toFixed(2)})`);
      }
      if (m.titleLineHeight && m.titleHeight < m.titleLineHeight * 1.95) {
        failures.push(`${width}px: product title box too short (${m.titleHeight}/${m.titleLineHeight})`);
      }
      if (m.titlePriceGap !== null && m.titlePriceGap > 12) {
        failures.push(`${width}px: excessive title/price gap (${m.titlePriceGap}px)`);
      }
    }

    await page.setViewport({ width: 1024, height: 900, deviceScaleFactor: 1 });
    await go(page, '/tienda/');
    const toggle = await page.$('.emo-mobile-filter-toggle');
    if (!toggle) failures.push('1024px: filter toggle missing for interaction');
    else {
      await toggle.click();
      await sleep(250);
      const drawer = await page.evaluate(() => {
        const shell = document.querySelector('.emo-mobile-filter-shell');
        const sidebar = shell?.querySelector('.widget-area,#secondary,.shop-widget-area');
        const close = shell?.querySelector('.emo-mobile-filter-close');
        const vis = (el) => {
          if (!el) return false;
          const r = el.getBoundingClientRect();
          const s = getComputedStyle(el);
          return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
        };
        return { shell: vis(shell), sidebar: vis(sidebar), close: vis(close), title: shell?.querySelector('.emo-mobile-filter-title')?.textContent?.trim() || '' };
      });
      if (!drawer.shell || !drawer.sidebar || !drawer.close) failures.push(`1024px: filter drawer incomplete ${JSON.stringify(drawer)}`);
      if (drawer.title.toLowerCase() !== 'filtros') failures.push(`1024px: drawer title not compact (${drawer.title})`);
    }

    for (const path of ['/tienda/', '/contacto/', '/productores/']) {
      await go(page, path);
      const headerVisible = await page.evaluate(() => {
        const selectors = '.page-header,.entry-header,.woocommerce-products-header,.woocommerce-breadcrumb,.breadcrumbs,.woostify-breadcrumb,.page-title-wrap';
        return [...document.querySelectorAll(selectors)].some((el) => {
          const r = el.getBoundingClientRect();
          const s = getComputedStyle(el);
          return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
        });
      });
      if (headerVisible) failures.push(`${path}: redundant title/breadcrumb header still visible`);
    }

    await go(page, '/blog/');
    const postHref = await page.evaluate(() => document.querySelector('article a[href*="/blog/"]:not([href$="/blog/"]), article .entry-title a, .post-card a')?.href || '');
    if (postHref) {
      await page.goto(`${postHref}${postHref.includes('?') ? '&' : '?'}qa=${Date.now()}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
      await sleep(500);
      const blog = await page.evaluate(() => {
        const p = document.querySelector('#primary,.content-area')?.getBoundingClientRect();
        const secondary = document.querySelector('#secondary');
        const visible = secondary ? (() => { const r = secondary.getBoundingClientRect(); const s = getComputedStyle(secondary); return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden'; })() : false;
        return { ratio: p ? p.width / document.documentElement.clientWidth : 0, secondaryVisible: visible };
      });
      if (blog.secondaryVisible || blog.ratio < 0.9) failures.push(`single blog not full width ${JSON.stringify(blog)}`);
    }
  } finally {
    await browser.close();
  }

  if (failures.length) {
    console.error(failures.join('\n'));
    process.exitCode = 2;
  } else {
    console.log('SHOP_RESPONSIVE_OK');
  }
})();
