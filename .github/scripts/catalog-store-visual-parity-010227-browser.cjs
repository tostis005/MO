const puppeteer = require('puppeteer-core');

const BASE = (process.env.BASE_URL || process.env.STAGING_URL || 'https://dev.elmercadodeorigen.com').replace(/\/$/, '');
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function assert(condition, message) {
  if (!condition) failures.push(message);
}

async function go(page, path, viewport) {
  if (viewport) await page.setViewport({ ...viewport, deviceScaleFactor: 1 });
  const url = new URL(path, BASE);
  url.searchParams.set('qa-visual-parity-010227', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (!response || response.status() >= 400) throw new Error(`${url.pathname}: HTTP ${response?.status()}`);
  await page.waitForSelector('body', { timeout: 15000 });
  await page.waitForFunction(() => Boolean(document.getElementById('elmercado-catalog-store-visual-parity-010227')), { timeout: 45000 });
  await sleep(1200);
}

async function waitShop(page) {
  await page.waitForFunction(() => {
    const toolbar = document.querySelector('.woostify-sorting.emo-catalog-toolbar-parity-010227');
    const rail = document.querySelector('#secondary.widget-area.emo-filter-rail-parity-010227,.shop-widget-area.emo-filter-rail-parity-010227');
    const category = document.querySelector('.emo-global-category-filter-010226');
    return Boolean(toolbar && rail && category);
  }, { timeout: 45000 });
  await sleep(450);
}

async function waitVendor(page) {
  await page.waitForSelector('#wcfmmp-store', { timeout: 30000 });
  await page.waitForFunction(() => {
    const store = document.querySelector('#wcfmmp-store');
    const toolbar = store?.querySelector('.emo-catalog-toolbar-parity-010227');
    const panel = document.querySelector('#emo-vendor-filters');
    return Boolean(toolbar && panel);
  }, { timeout: 45000 });
  await sleep(450);
}

async function commonState(page, kind) {
  return page.evaluate((kind) => {
    const visible = (node) => {
      if (!node) return false;
      const s = getComputedStyle(node);
      const r = node.getBoundingClientRect();
      return s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity || 1) !== 0 && r.width > 0 && r.height > 0;
    };
    const box = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      return { left:r.left, right:r.right, top:r.top, bottom:r.bottom, width:r.width, height:r.height };
    };
    const style = (node, props) => {
      if (!node) return null;
      const s = getComputedStyle(node);
      return Object.fromEntries(props.map((prop) => [prop, s[prop]]));
    };
    const normalize = (value) => (value || '').replace(/\s+/g, ' ').trim().replace(/[.!?…]+$/u, '');
    const sentence = 'Una selección de productos con procedencia clara para acercar el origen a tu mesa de una forma más directa';

    const isVendor = kind === 'vendor';
    const root = isVendor ? document.querySelector('#wcfmmp-store') : document;
    const toolbar = isVendor
      ? root?.querySelector('.emo-catalog-toolbar-parity-010227')
      : document.querySelector('.woostify-sorting.emo-catalog-toolbar-parity-010227');
    const rail = isVendor
      ? document.querySelector('.left_sidebar.emo-vendor-filter-rail-010225,.emo-vendor-mobile-filter-shell-010227 .emo-vendor-filter-rail-010225')
      : document.querySelector('#secondary.widget-area,.shop-widget-area');
    const title = isVendor
      ? document.querySelector('#emo-vendor-category-filter .widget-title,#emo-vendor-filters .emo-vendor-price-filter .widget-title')
      : document.querySelector('.emo-global-category-filter-010226 .widget-title,.widget_price_filter .widget-title');
    const row = isVendor
      ? document.querySelector('#emo-vendor-category-filter li,.emo-vendor-attribute-filter li')
      : document.querySelector('.emo-global-category-filter-010226 li,.widget_product_categories li');

    return {
      marker: Boolean(document.getElementById('elmercado-catalog-store-visual-parity-010227')),
      taglinePresent: [...document.querySelectorAll('p')].some((node) => normalize(node.textContent) === sentence),
      visibleShopLeadCount: [...document.querySelectorAll('.emo-shop-lead')].filter(visible).length,
      toolbarVisible: visible(toolbar),
      toolbarBox: box(toolbar),
      toolbarStyle: style(toolbar, ['display','padding','borderWidth','borderStyle','borderRadius','backgroundColor','boxShadow','marginBottom']),
      orderingVisible: visible(toolbar?.querySelector('.woocommerce-ordering')),
      resultVisible: visible(toolbar?.querySelector('.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225')),
      railVisible: visible(rail),
      railBox: box(rail),
      railStyle: style(rail, ['padding','borderWidth','borderStyle','borderRadius','backgroundColor','boxShadow','marginTop']),
      titleText: (title?.textContent || '').replace(/\s+/g, ' ').trim(),
      titleStyle: style(title, ['fontSize','fontWeight','letterSpacing','lineHeight','textTransform','color','marginBottom','paddingTop','paddingRight','paddingBottom','paddingLeft']),
      rowText: (row?.textContent || '').replace(/\s+/g, ' ').trim(),
      rowStyle: style(row, ['minHeight','paddingTop','paddingRight','paddingBottom','paddingLeft','borderRadius','backgroundColor','boxShadow']),
      rowLinkStyle: style(row?.querySelector(':scope > a') || row?.querySelector('a'), ['fontSize','fontWeight','lineHeight','color','paddingTop','paddingRight','paddingBottom','paddingLeft']),
      oldVendorToggleVisible: isVendor ? visible(document.querySelector('.emo-vendor-filter-toggle-010225')) : false,
      newVendorToggleVisible: isVendor ? visible(document.querySelector('.emo-vendor-mobile-filter-toggle-010227')) : false,
    };
  }, kind);
}

async function hoverState(page, selector) {
  const target = await page.$(selector);
  if (!target) return null;
  await target.hover();
  await sleep(180);
  return page.evaluate((selector) => {
    const node = document.querySelector(selector);
    if (!node) return null;
    const s = getComputedStyle(node);
    const link = node.querySelector(':scope > a') || node.querySelector('a');
    const ls = link ? getComputedStyle(link) : null;
    return { backgroundColor:s.backgroundColor, boxShadow:s.boxShadow, linkColor:ls?.color || '', linkWeight:ls?.fontWeight || '' };
  }, selector);
}

async function mobileDrawerState(page, kind) {
  const isVendor = kind === 'vendor';
  const toggleSelector = isVendor ? '.emo-vendor-mobile-filter-toggle-010227' : '.emo-mobile-filter-toggle:not(.emo-vendor-mobile-filter-toggle-010227)';
  await page.waitForSelector(toggleSelector, { visible:true, timeout:20000 });
  const before = await page.evaluate((toggleSelector, isVendor) => {
    const box = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      return { left:r.left, right:r.right, top:r.top, bottom:r.bottom, width:r.width, height:r.height };
    };
    const style = (node, props) => {
      if (!node) return null;
      const s = getComputedStyle(node);
      return Object.fromEntries(props.map((prop) => [prop, s[prop]]));
    };
    const visible = (node) => {
      if (!node) return false;
      const s = getComputedStyle(node); const r = node.getBoundingClientRect();
      return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0;
    };
    const toggle = document.querySelector(toggleSelector);
    const toolbar = isVendor
      ? document.querySelector('#wcfmmp-store .emo-catalog-toolbar-parity-010227')
      : document.querySelector('.woostify-sorting.emo-catalog-toolbar-parity-010227');
    return {
      toolbarBox:box(toolbar),
      toggleBox:box(toggle),
      toggleStyle:style(toggle, ['height','minHeight','paddingLeft','paddingRight','borderWidth','borderStyle','borderRadius','backgroundColor','color','fontSize','fontWeight','boxShadow','marginBottom']),
      oldVendorToggleVisible:isVendor ? visible(document.querySelector('.emo-vendor-filter-toggle-010225')) : false,
    };
  }, toggleSelector, isVendor);

  await page.click(toggleSelector);
  const shellSelector = isVendor ? '.emo-vendor-mobile-filter-shell-010227' : '.emo-mobile-filter-shell:not(.emo-vendor-mobile-filter-shell-010227)';
  await page.waitForFunction((selector) => {
    const shell = document.querySelector(selector);
    if (!shell || shell.hidden) return false;
    const s = getComputedStyle(shell); const r = shell.getBoundingClientRect();
    return s.display !== 'none' && r.width > 0 && r.height > 0;
  }, { timeout:10000 }, shellSelector);
  await sleep(300);

  const open = await page.evaluate((shellSelector, isVendor) => {
    const shell = document.querySelector(shellSelector);
    const panel = shell?.querySelector('.emo-mobile-filter-panel');
    const close = shell?.querySelector('.emo-mobile-filter-close');
    const title = shell?.querySelector('.emo-mobile-filter-title');
    const filterTitle = isVendor
      ? shell?.querySelector('#emo-vendor-category-filter .widget-title,#emo-vendor-filters .emo-vendor-price-filter .widget-title')
      : shell?.querySelector('.emo-global-category-filter-010226 .widget-title,.widget_product_categories .widget-title,.widget_price_filter .widget-title');
    const priceSlider = isVendor
      ? shell?.querySelector('.emo-vendor-price-filter .price_slider')
      : shell?.querySelector('.widget_price_filter .price_slider');
    const handles = [...(priceSlider?.querySelectorAll('.ui-slider-handle') || [])];
    const trackRect = priceSlider?.getBoundingClientRect();
    const style = (node, props) => {
      if (!node) return null;
      const s = getComputedStyle(node);
      return Object.fromEntries(props.map((prop) => [prop, s[prop]]));
    };
    const box = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      return { left:r.left, right:r.right, top:r.top, bottom:r.bottom, width:r.width, height:r.height };
    };
    return {
      panelBox:box(panel),
      panelStyle:style(panel, ['paddingTop','paddingRight','paddingBottom','paddingLeft','backgroundColor','boxShadow']),
      closeBox:box(close),
      closeStyle:style(close, ['width','height','minWidth','borderWidth','borderRadius','backgroundColor','color','fontSize','lineHeight']),
      titleText:(title?.textContent || '').replace(/\s+/g,' ').trim(),
      titleStyle:style(title, ['fontSize','fontWeight','color','marginTop','marginBottom']),
      filterTitleText:(filterTitle?.textContent || '').replace(/\s+/g,' ').trim(),
      filterTitleStyle:style(filterTitle, ['fontSize','fontWeight','letterSpacing','textTransform','color','paddingBottom','marginBottom']),
      priceVisible:Boolean(priceSlider && trackRect && trackRect.width > 0 && trackRect.height > 0),
      handleCount:handles.length,
      handleAlignment:trackRect ? handles.map((h) => {
        const r = h.getBoundingClientRect();
        return Math.abs((r.top + r.height / 2) - (trackRect.top + trackRect.height / 2));
      }) : [],
      vendorRailStyle:isVendor ? style(shell?.querySelector('.emo-vendor-filter-rail-010225'), ['position','width','margin','padding','borderWidth','backgroundColor','boxShadow','transform']) : null,
    };
  }, shellSelector, isVendor);

  await page.click(`${shellSelector} .emo-mobile-filter-close`);
  await page.waitForFunction((selector) => document.querySelector(selector)?.hidden === true, { timeout:5000 }, shellSelector);
  return { before, open };
}

function sameStyle(left, right, keys, label) {
  keys.forEach((key) => {
    if (left?.[key] !== right?.[key]) failures.push(`${label}: ${key} differs ${JSON.stringify(left?.[key])} != ${JSON.stringify(right?.[key])}`);
  });
}

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    protocolTimeout: 180000,
    args: ['--no-sandbox','--disable-dev-shm-usage'],
  });

  try {
    const desktop = { width:1440, height:1000 };

    const shopPage = await browser.newPage();
    await go(shopPage, '/tienda/', desktop);
    await waitShop(shopPage);
    report.shopDesktop = await commonState(shopPage, 'shop');
    report.shopDesktopHover = await hoverState(shopPage, '.emo-global-category-filter-010226 li');

    assert(report.shopDesktop.marker, 'Shop desktop: release marker missing');
    assert(!report.shopDesktop.taglinePresent, 'Shop desktop: redundant Productos sentence is still present');
    assert(report.shopDesktop.visibleShopLeadCount === 0, `Shop desktop: ${report.shopDesktop.visibleShopLeadCount} editorial lead blocks remain visible`);
    assert(report.shopDesktop.toolbarVisible && report.shopDesktop.orderingVisible && report.shopDesktop.resultVisible, 'Shop desktop: results/ordering toolbar incomplete');
    assert(report.shopDesktop.railVisible, 'Shop desktop: filter rail hidden');
    assert(Math.abs((report.shopDesktop.toolbarBox?.top || 0) - (report.shopDesktop.railBox?.top || 0)) <= 3, `Shop desktop: toolbar/rail top mismatch ${JSON.stringify({toolbar:report.shopDesktop.toolbarBox,rail:report.shopDesktop.railBox})}`);
    assert(Math.round(report.shopDesktop.railBox?.width || 0) === 250, `Shop desktop: rail width ${report.shopDesktop.railBox?.width}`);
    assert(report.shopDesktop.railStyle?.padding === '18px', `Shop desktop: rail padding ${report.shopDesktop.railStyle?.padding}`);
    assert(report.shopDesktopHover?.backgroundColor === 'rgb(217, 237, 224)', `Shop desktop: category hover background ${report.shopDesktopHover?.backgroundColor}`);
    assert(report.shopDesktopHover?.boxShadow && report.shopDesktopHover.boxShadow !== 'none', 'Shop desktop: category hover border/shadow missing');

    const vendorPage = await browser.newPage();
    await go(vendorPage, '/tienda/hidalgo-de-la-jara/', desktop);
    await waitVendor(vendorPage);
    report.vendorDesktop = await commonState(vendorPage, 'vendor');
    report.vendorDesktopHover = await hoverState(vendorPage, '#emo-vendor-category-filter li');

    assert(!report.vendorDesktop.taglinePresent, 'Producer desktop: redundant Productos sentence is still present');
    assert(report.vendorDesktop.toolbarVisible && report.vendorDesktop.orderingVisible && report.vendorDesktop.resultVisible, 'Producer desktop: results/ordering toolbar incomplete');
    assert(report.vendorDesktop.railVisible, 'Producer desktop: filter rail hidden');
    assert(Math.abs((report.vendorDesktop.toolbarBox?.top || 0) - (report.vendorDesktop.railBox?.top || 0)) <= 3, `Producer desktop: toolbar/rail top mismatch ${JSON.stringify({toolbar:report.vendorDesktop.toolbarBox,rail:report.vendorDesktop.railBox})}`);
    assert(Math.round(report.vendorDesktop.railBox?.width || 0) === 250, `Producer desktop: rail width ${report.vendorDesktop.railBox?.width}`);
    assert(report.vendorDesktop.railStyle?.padding === '18px', `Producer desktop: rail padding ${report.vendorDesktop.railStyle?.padding}`);
    assert(report.vendorDesktopHover?.backgroundColor === 'rgb(217, 237, 224)', `Producer desktop: category hover background ${report.vendorDesktopHover?.backgroundColor}`);
    assert(report.vendorDesktopHover?.boxShadow && report.vendorDesktopHover.boxShadow !== 'none', 'Producer desktop: category hover border/shadow missing');

    sameStyle(report.shopDesktop.railStyle, report.vendorDesktop.railStyle, ['padding','borderWidth','borderStyle','borderRadius','backgroundColor','boxShadow'], 'Desktop rail');
    sameStyle(report.shopDesktop.toolbarStyle, report.vendorDesktop.toolbarStyle, ['padding','borderWidth','borderStyle','borderRadius','backgroundColor','boxShadow','marginBottom'], 'Desktop toolbar');
    sameStyle(report.shopDesktop.titleStyle, report.vendorDesktop.titleStyle, ['fontSize','fontWeight','letterSpacing','lineHeight','textTransform','color','marginBottom','paddingTop','paddingRight','paddingBottom','paddingLeft'], 'Desktop filter title');
    sameStyle(report.shopDesktop.rowStyle, report.vendorDesktop.rowStyle, ['minHeight','paddingTop','paddingRight','paddingBottom','paddingLeft','borderRadius'], 'Desktop filter row');
    sameStyle(report.shopDesktop.rowLinkStyle, report.vendorDesktop.rowLinkStyle, ['fontSize','fontWeight','lineHeight','color','paddingTop','paddingRight','paddingBottom','paddingLeft'], 'Desktop filter link');

    const activePage = await browser.newPage();
    await go(activePage, '/tienda/hidalgo-de-la-jara/?product_cat=jamones-paletas', desktop);
    await waitVendor(activePage);
    report.activeCategory = await activePage.evaluate(() => {
      const panel = document.querySelector('#emo-vendor-filters');
      const context = panel?.querySelector('#emo-vendor-category-context');
      const price = panel?.querySelector('.emo-vendor-price-filter');
      const children = [...(panel?.children || [])];
      return {
        context:(context?.textContent || '').replace(/\s+/g,' ').trim(),
        remove:(context?.querySelector('a')?.textContent || '').replace(/\s+/g,' ').trim(),
        beforePrice:Boolean(context && price && children.indexOf(context) < children.indexOf(price)),
      };
    });
    assert(report.activeCategory.beforePrice, `Producer category: active category is not above price ${JSON.stringify(report.activeCategory)}`);
    assert(/Quitar/i.test(report.activeCategory.remove), `Producer category: Quitar control missing ${JSON.stringify(report.activeCategory)}`);

    await shopPage.setViewport({ width:390, height:844, deviceScaleFactor:1 });
    await go(shopPage, '/tienda/', { width:390, height:844 });
    await waitShop(shopPage);
    report.shopMobileCommon = await commonState(shopPage, 'shop');
    report.shopMobile = await mobileDrawerState(shopPage, 'shop');

    await vendorPage.setViewport({ width:390, height:844, deviceScaleFactor:1 });
    await go(vendorPage, '/tienda/hidalgo-de-la-jara/', { width:390, height:844 });
    await waitVendor(vendorPage);
    report.vendorMobileCommon = await commonState(vendorPage, 'vendor');
    report.vendorMobile = await mobileDrawerState(vendorPage, 'vendor');

    assert(report.shopMobile.before.toolbarBox && report.shopMobile.before.toggleBox && report.shopMobile.before.toggleBox.top >= report.shopMobile.before.toolbarBox.bottom - 1, 'Shop mobile: filter card is not below results/order card');
    assert(report.vendorMobile.before.toolbarBox && report.vendorMobile.before.toggleBox && report.vendorMobile.before.toggleBox.top >= report.vendorMobile.before.toolbarBox.bottom - 1, 'Producer mobile: filter card is not below results/order card');
    assert(!report.vendorMobile.before.oldVendorToggleVisible, 'Producer mobile: old inline Filtrar control is still visible');
    assert(report.vendorMobileCommon.newVendorToggleVisible, 'Producer mobile: new Shop-style filter control missing');

    sameStyle(report.shopMobileCommon.toolbarStyle, report.vendorMobileCommon.toolbarStyle, ['padding','borderWidth','borderStyle','borderRadius','backgroundColor','boxShadow','marginBottom'], 'Mobile toolbar');
    sameStyle(report.shopMobile.before.toggleStyle, report.vendorMobile.before.toggleStyle, ['height','minHeight','paddingLeft','paddingRight','borderWidth','borderStyle','borderRadius','backgroundColor','color','fontSize','fontWeight','boxShadow','marginBottom'], 'Mobile filter trigger');
    sameStyle(report.shopMobile.open.panelStyle, report.vendorMobile.open.panelStyle, ['paddingTop','paddingRight','paddingBottom','paddingLeft','backgroundColor','boxShadow'], 'Mobile drawer panel');
    sameStyle(report.shopMobile.open.closeStyle, report.vendorMobile.open.closeStyle, ['width','height','minWidth','borderWidth','borderRadius','backgroundColor','color','fontSize','lineHeight'], 'Mobile drawer close');
    sameStyle(report.shopMobile.open.titleStyle, report.vendorMobile.open.titleStyle, ['fontSize','fontWeight','color','marginTop','marginBottom'], 'Mobile drawer title');
    sameStyle(report.shopMobile.open.filterTitleStyle, report.vendorMobile.open.filterTitleStyle, ['fontSize','fontWeight','letterSpacing','textTransform','color','paddingBottom','marginBottom'], 'Mobile filter heading');

    assert(Math.abs(report.shopMobile.open.panelBox?.left || 0) <= 1, `Shop mobile: drawer is not left-aligned ${JSON.stringify(report.shopMobile.open.panelBox)}`);
    assert(Math.abs(report.vendorMobile.open.panelBox?.left || 0) <= 1, `Producer mobile: drawer is not left-aligned ${JSON.stringify(report.vendorMobile.open.panelBox)}`);
    assert(Math.round(report.shopMobile.open.closeBox?.width || 0) === 40 && Math.round(report.shopMobile.open.closeBox?.height || 0) === 40, `Shop mobile: close size ${JSON.stringify(report.shopMobile.open.closeBox)}`);
    assert(Math.round(report.vendorMobile.open.closeBox?.width || 0) === 40 && Math.round(report.vendorMobile.open.closeBox?.height || 0) === 40, `Producer mobile: close size ${JSON.stringify(report.vendorMobile.open.closeBox)}`);
    assert(report.shopMobile.open.titleText === 'Filtrar productos' && report.vendorMobile.open.titleText === 'Filtrar productos', 'Mobile: drawer title mismatch');
    assert(report.shopMobile.open.priceVisible && report.vendorMobile.open.priceVisible, 'Mobile: price slider missing in one drawer');
    report.shopMobile.open.handleAlignment.forEach((delta, i) => assert(delta <= 0.75, `Shop mobile: price handle ${i + 1} off center by ${delta}px`));
    report.vendorMobile.open.handleAlignment.forEach((delta, i) => assert(delta <= 0.75, `Producer mobile: price handle ${i + 1} off center by ${delta}px`));
    assert(report.vendorMobile.open.vendorRailStyle?.position === 'static', `Producer mobile: rail position ${report.vendorMobile.open.vendorRailStyle?.position}`);
    assert(report.vendorMobile.open.vendorRailStyle?.boxShadow === 'none', `Producer mobile: nested old rail shadow remains ${report.vendorMobile.open.vendorRailStyle?.boxShadow}`);
    assert(report.vendorMobile.open.vendorRailStyle?.borderWidth === '0px', `Producer mobile: nested old rail border remains ${report.vendorMobile.open.vendorRailStyle?.borderWidth}`);

    await shopPage.close();
    await vendorPage.close();
    await activePage.close();
  } finally {
    await browser.close();
  }

  console.log('CATALOG_STORE_VISUAL_PARITY_010227_REPORT', JSON.stringify({ failures, report }));
  if (failures.length) {
    console.error('CATALOG_STORE_VISUAL_PARITY_010227_FAIL', JSON.stringify(failures));
    process.exit(2);
  }
  console.log('CATALOG_STORE_VISUAL_PARITY_010227_OK');
})().catch((error) => {
  console.error('CATALOG_STORE_VISUAL_PARITY_010227_ERROR', error);
  process.exit(1);
});
