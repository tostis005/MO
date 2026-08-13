const puppeteer = require('puppeteer-core');

const base = (process.env.STAGING_URL || 'https://dev.elmercadodeorigen.com').replace(/\/$/, '');
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function assert(condition, message) {
  if (!condition) failures.push(message);
}

async function open(browser, slug, params = '', viewport = { width: 1440, height: 1000 }) {
  const page = await browser.newPage();
  await page.setViewport({ ...viewport, deviceScaleFactor: 1 });
  const separator = params ? '&' : '?';
  const url = `${base}/tienda/${slug}/${params ? `?${params}` : ''}${separator}qa-vendor-010225=${Date.now()}`;
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (!response || response.status() >= 400) throw new Error(`${slug}: HTTP ${response?.status()} ${url}`);
  await page.waitForSelector('#wcfmmp-store', { timeout: 30000 });
  await page.waitForFunction(() => document.querySelector('#emo-vendor-filters') && /^\d+\s+resultados?$/.test((document.querySelector('#wcfmmp-store .woocommerce-result-count')?.textContent || '').trim()), { timeout: 30000 });
  return page;
}

async function state(page) {
  return page.evaluate(() => {
    const store = document.querySelector('#wcfmmp-store');
    const sidebar = store.querySelector('.left_sidebar');
    const products = store.querySelector('.right_side, .right_side_full, .products-wrapper, .wcfmmp-store-product, .product_area');
    const resultText = (store.querySelector('.woocommerce-result-count')?.textContent || '').replace(/\s+/g, ' ').trim();
    const total = Number.parseInt(resultText.replace(/[^0-9]/g, ''), 10) || 0;
    const productIds = [...store.querySelectorAll('ul.products li.product')].map((item) => {
      const cls = [...item.classList].find((name) => /^post-\d+$/.test(name));
      return cls ? Number(cls.slice(5)) : 0;
    }).filter(Boolean);
    const categories = [...store.querySelectorAll('#emo-vendor-category-filter li a')].map((a) => ({
      name: (a.querySelector('span')?.textContent || '').trim(),
      count: Number.parseInt((a.querySelector('small')?.textContent || '').replace(/[^0-9]/g, ''), 10) || 0,
      href: a.href,
    }));
    const attributes = [...store.querySelectorAll('.emo-vendor-attribute-filter')].map((group) => ({
      attribute: group.getAttribute('data-attribute') || '',
      title: (group.querySelector('.widget-title')?.textContent || '').trim(),
      options: [...group.querySelectorAll('li a')].map((a) => ({
        name: (a.querySelector('span')?.textContent || '').trim(),
        count: Number.parseInt((a.querySelector('small')?.textContent || '').replace(/[^0-9]/g, ''), 10) || 0,
        href: a.href,
        chosen: a.closest('li')?.classList.contains('chosen') || false,
      })),
    }));
    const sidebarBox = sidebar?.getBoundingClientRect();
    const productBox = products?.getBoundingClientRect();
    return {
      pathname: location.pathname,
      search: location.search,
      resultText,
      total,
      productIds,
      categories,
      attributes,
      price: Boolean(store.querySelector('.emo-vendor-price-filter .price_slider_wrapper')),
      context: (store.querySelector('#emo-vendor-category-context strong')?.textContent || '').trim(),
      clearCategory: store.querySelector('#emo-vendor-category-context a')?.href || '',
      oldCategoryWidgets: store.querySelectorAll('.left_sidebar > .widget_product_categories:not(#emo-vendor-category-filter), .left_sidebar .widget_product_tag_cloud').length,
      vendorFilter: Boolean(store.querySelector('#emo-global-vendor-filter, [data-attribute="productor"], .emo-vendor-attribute-filter[data-attribute="productor"]')),
      ordering: Boolean(store.querySelector('.woocommerce-ordering')),
      rightRail: Boolean(sidebarBox && productBox && sidebarBox.left > productBox.right - 2),
      sidebarWidth: sidebarBox ? Math.round(sidebarBox.width) : 0,
      bodyOrder: [...(store.querySelector('.body_area')?.children || [])].map((node) => node.className || node.id || node.tagName),
      toggleVisible: (() => {
        const toggle = store.querySelector('.emo-vendor-filter-toggle-010225');
        if (!toggle) return false;
        const style = getComputedStyle(toggle);
        return style.display !== 'none' && toggle.getBoundingClientRect().width > 0;
      })(),
    };
  });
}

async function loadAll(page, expected) {
  const started = Date.now();
  let stable = 0;
  let previous = -1;
  while (Date.now() - started < 45000) {
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await sleep(350);
    const info = await page.evaluate(() => ({
      count: document.querySelectorAll('#wcfmmp-store ul.products li.product').length,
      loader: window.__emoCatalogLoaderState ? {
        loading: Boolean(window.__emoCatalogLoaderState.loading),
        nextUrl: window.__emoCatalogLoaderState.nextUrl || '',
      } : null,
    }));
    if (info.count === previous) stable += 1; else stable = 0;
    previous = info.count;
    if (info.count >= expected) break;
    if (stable > 18 && (!info.loader || (!info.loader.loading && !info.loader.nextUrl))) break;
  }
  return page.evaluate(() => [...new Set([...document.querySelectorAll('#wcfmmp-store ul.products li.product')].map((item) => {
    const cls = [...item.classList].find((name) => /^post-\d+$/.test(name));
    return cls ? Number(cls.slice(5)) : 0;
  }).filter(Boolean))]);
}

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    protocolTimeout: 180000,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  try {
    // Single-category producer: category is implicit, so no category chooser.
    const oilPage = await open(browser, '1957');
    const oil = await state(oilPage);
    report.oil = oil;
    assert(oil.resultText === '4 resultados', `1957: total text ${JSON.stringify(oil.resultText)} != 4 resultados`);
    assert(oil.total === 4, `1957: total ${oil.total} != 4`);
    assert(oil.context.toLowerCase().includes('aceite'), `1957: implicit Aceites context missing (${oil.context})`);
    assert(oil.categories.length === 0, `1957: category chooser should be hidden, got ${JSON.stringify(oil.categories)}`);
    assert(oil.price, '1957: price filter missing');
    assert(!oil.vendorFilter, '1957: vendor/productor filter must not exist');
    assert(oil.oldCategoryWidgets === 0, `1957: stale WCFM widgets remain (${oil.oldCategoryWidgets})`);
    assert(oil.rightRail, '1957: filters are not geometrically to the right of products');
    assert(oil.sidebarWidth >= 230 && oil.sidebarWidth <= 270, `1957: sidebar width ${oil.sidebarWidth}`);
    const oilAll = await loadAll(oilPage, oil.total);
    assert(oilAll.length === 4, `1957: loaded ${oilAll.length} unique products, expected 4`);
    await oilPage.close();

    // Multi-category producer: initial state is price + categories only.
    const hidalgoPage = await open(browser, 'hidalgo-de-la-jara');
    const hidalgo = await state(hidalgoPage);
    report.hidalgo = hidalgo;
    assert(/^\d+ resultados$/.test(hidalgo.resultText), `Hidalgo: bad result text ${JSON.stringify(hidalgo.resultText)}`);
    assert(hidalgo.total > 4, `Hidalgo: implausible total ${hidalgo.total}`);
    assert(hidalgo.context === '', `Hidalgo: should start without category context, got ${hidalgo.context}`);
    assert(hidalgo.categories.length >= 2, `Hidalgo: expected multiple categories, got ${JSON.stringify(hidalgo.categories)}`);
    assert(hidalgo.attributes.length === 0, `Hidalgo: attributes should wait for a category, got ${JSON.stringify(hidalgo.attributes)}`);
    assert(hidalgo.price, 'Hidalgo: price filter missing');
    assert(!hidalgo.vendorFilter, 'Hidalgo: vendor/productor filter must not exist');
    assert(hidalgo.oldCategoryWidgets === 0, `Hidalgo: stale WCFM widgets remain (${hidalgo.oldCategoryWidgets})`);
    assert(hidalgo.rightRail, 'Hidalgo: filters are not geometrically to the right of products');
    assert(hidalgo.categories.every((item) => item.count > 0), `Hidalgo: zero category visible ${JSON.stringify(hidalgo.categories)}`);
    assert(hidalgo.categories.every((item) => new URL(item.href).pathname.includes('/tienda/hidalgo-de-la-jara/')), 'Hidalgo: a category link escapes producer store');

    const jamones = hidalgo.categories.find((item) => /jamon|paleta/i.test(item.name));
    assert(Boolean(jamones), `Hidalgo: Jamones y paletas category not found ${JSON.stringify(hidalgo.categories)}`);
    if (jamones) {
      await hidalgoPage.goto(`${jamones.href}&qa-vendor-cat-010225=${Date.now()}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
      await hidalgoPage.waitForSelector('#emo-vendor-category-context', { timeout: 30000 });
      const cat = await state(hidalgoPage);
      report.jamones = cat;
      assert(cat.context.toLowerCase().includes('jamon') || cat.context.toLowerCase().includes('paleta'), `Jamones: wrong context ${cat.context}`);
      assert(cat.clearCategory, 'Jamones: Quitar category link missing');
      assert(cat.total === jamones.count, `Jamones: total ${cat.total} != category count ${jamones.count}`);
      assert(cat.attributes.length > 0, 'Jamones: category-specific attributes missing');
      assert(!cat.vendorFilter, 'Jamones: redundant productor filter appeared');
      assert(cat.attributes.every((group) => group.options.every((item) => item.count > 0 || item.chosen)), `Jamones: zero attribute option visible ${JSON.stringify(cat.attributes)}`);
      assert(cat.attributes.every((group) => group.options.every((item) => new URL(item.href).pathname.includes('/tienda/hidalgo-de-la-jara/'))), 'Jamones: an attribute link escapes producer store');

      const firstGroup = cat.attributes.find((group) => group.options.length);
      const firstOption = firstGroup?.options?.[0];
      if (firstOption) {
        await hidalgoPage.goto(`${firstOption.href}&qa-vendor-attr-010225=${Date.now()}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
        await hidalgoPage.waitForSelector('#emo-vendor-category-context', { timeout: 30000 });
        const filtered = await state(hidalgoPage);
        report.filtered = filtered;
        assert(filtered.total > 0 && filtered.total <= cat.total, `Attribute filter total ${filtered.total} invalid against ${cat.total}`);
        assert(filtered.context === cat.context, `Attribute filter lost category context (${filtered.context})`);
        assert(filtered.attributes.some((group) => group.options.some((item) => item.chosen)), 'Attribute filter did not remain selected');
        assert(!filtered.vendorFilter, 'Attribute state exposes producer filter');
      }
    }
    await hidalgoPage.close();

    // Mobile: same filters via a drawer, not a left/right inline sidebar.
    const mobilePage = await open(browser, 'hidalgo-de-la-jara', '', { width: 390, height: 844 });
    const mobile = await state(mobilePage);
    report.mobile = mobile;
    assert(mobile.toggleVisible, 'Mobile: Filtrar button is not visible');
    await mobilePage.click('.emo-vendor-filter-toggle-010225');
    await mobilePage.waitForFunction(() => document.documentElement.classList.contains('emo-vendor-filters-open-010225'), { timeout: 5000 });
    const drawerOpen = await mobilePage.evaluate(() => {
      const sidebar = document.querySelector('#wcfmmp-store .left_sidebar');
      const box = sidebar?.getBoundingClientRect();
      return Boolean(box && box.right <= innerWidth + 2 && box.left < innerWidth);
    });
    assert(drawerOpen, 'Mobile: filter drawer did not open');
    await mobilePage.close();
  } finally {
    await browser.close();
  }

  console.log('VENDOR_STORE_FILTERS_010225_REPORT', JSON.stringify({ failures, report }));
  if (failures.length) {
    console.error('VENDOR_STORE_FILTERS_010225_FAIL', JSON.stringify(failures));
    process.exit(2);
  }
  console.log('VENDOR_STORE_FILTERS_010225_OK');
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
