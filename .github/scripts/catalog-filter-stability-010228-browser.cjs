const puppeteer = require('puppeteer-core');
const base = process.env.BASE_URL || 'https://dev.elmercadodeorigen.com';
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const check = (ok, message) => { if (!ok) throw new Error(message); };

async function openPage(browser, path) {
  const page = await browser.newPage();
  await page.setViewport({width:1440,height:1000,deviceScaleFactor:1});
  const separator = path.includes('?') ? '&' : '?';
  await page.goto(base + path + separator + 'qa-stability-010228=' + Date.now(), {waitUntil:'domcontentloaded',timeout:70000});
  await page.waitForSelector('#elmercado-catalog-filter-stability-010228', {timeout:45000});
  await sleep(2400);
  return page;
}

async function readState(page, kind) {
  return page.evaluate(kind => {
    const rail = kind === 'shop' ? document.querySelector('#secondary.widget-area,.shop-widget-area') : document.querySelector('.emo-vendor-filter-rail-010225');
    const toolbar = kind === 'shop' ? document.querySelector('.emo-catalog-toolbar-parity-010227,.woostify-sorting') : document.querySelector('#wcfmmp-store .emo-catalog-toolbar-parity-010227,#wcfmmp-store .woostify-sorting');
    const handle = rail?.querySelector('.widget_price_filter .ui-slider-handle');
    const slider = rail?.querySelector('.widget_price_filter .price_slider');
    const railStyle = rail ? getComputedStyle(rail) : null;
    const handleStyle = handle ? getComputedStyle(handle) : null;
    const sliderStyle = slider ? getComputedStyle(slider) : null;
    return {
      rail: rail ? rail.getBoundingClientRect().toJSON() : null,
      toolbar: toolbar ? toolbar.getBoundingClientRect().toJSON() : null,
      position: railStyle?.position || '',
      top: railStyle?.top || '',
      overflowY: railStyle?.overflowY || '',
      handle: handleStyle ? [handleStyle.width,handleStyle.height,handleStyle.top,handleStyle.marginTop,handleStyle.marginLeft,handleStyle.borderWidth,handleStyle.borderColor,handleStyle.backgroundColor,handleStyle.transform] : null,
      slider: sliderStyle ? [sliderStyle.height,sliderStyle.marginTop,sliderStyle.marginRight,sliderStyle.marginBottom,sliderStyle.marginLeft,sliderStyle.backgroundColor] : null,
    };
  }, kind);
}

async function sampleTops(page, kind, count) {
  const values = [];
  for (let i = 0; i < count; i += 1) {
    values.push((await readState(page, kind)).rail?.top ?? null);
    await sleep(350);
  }
  return values;
}

(async () => {
  const browser = await puppeteer.launch({headless:true,executablePath:'/usr/bin/google-chrome',protocolTimeout:120000,args:['--no-sandbox','--disable-dev-shm-usage','--disable-gpu']});
  try {
    const shop = await openPage(browser, '/tienda/');
    const vendor = await openPage(browser, '/tienda/hidalgo-de-la-jara/');
    const shopState = await readState(shop, 'shop');
    const vendorState = await readState(vendor, 'vendor');

    check(shopState.rail && shopState.toolbar && vendorState.rail && vendorState.toolbar, 'desktop rail or toolbar missing');
    check(Math.abs(shopState.rail.top - shopState.toolbar.top) <= 3, 'shop initial alignment failed');
    check(Math.abs(vendorState.rail.top - vendorState.toolbar.top) <= 3, 'vendor initial alignment failed');
    check(shopState.position === 'sticky' && vendorState.position === 'sticky', 'desktop rails are not sticky');
    check(shopState.top === '94px' && vendorState.top === '94px', 'sticky top mismatch');
    check(shopState.overflowY === 'auto' && vendorState.overflowY === 'auto', 'desktop overflow mismatch');

    const idleShop = await sampleTops(shop, 'shop', 4);
    const idleVendor = await sampleTops(vendor, 'vendor', 4);
    const spread = values => Math.max(...values) - Math.min(...values);
    check(spread(idleShop) <= 1.5, 'shop moves while idle ' + JSON.stringify(idleShop));
    check(spread(idleVendor) <= 1.5, 'vendor moves while idle ' + JSON.stringify(idleVendor));

    check(shopState.handle && vendorState.handle && shopState.slider && vendorState.slider, 'price slider missing');
    check(JSON.stringify(shopState.handle) === JSON.stringify(vendorState.handle), 'price handle mismatch ' + JSON.stringify([shopState.handle,vendorState.handle]));
    check(JSON.stringify(shopState.slider) === JSON.stringify(vendorState.slider), 'price rail mismatch ' + JSON.stringify([shopState.slider,vendorState.slider]));

    await shop.evaluate(() => window.scrollTo(0, 1200));
    await vendor.evaluate(() => window.scrollTo(0, 1200));
    await sleep(500);
    const stickyShop = await sampleTops(shop, 'shop', 3);
    const stickyVendor = await sampleTops(vendor, 'vendor', 3);
    stickyShop.forEach(value => check(Math.abs(value - 94) <= 3, 'shop sticky failed ' + JSON.stringify(stickyShop)));
    stickyVendor.forEach(value => check(Math.abs(value - 94) <= 3, 'vendor sticky failed ' + JSON.stringify(stickyVendor)));

    await shop.screenshot({path:'qa/shop-desktop-010228.png'});
    await vendor.screenshot({path:'qa/vendor-desktop-010228.png'});

    await vendor.setViewport({width:390,height:844,deviceScaleFactor:1});
    await vendor.goto(base + '/tienda/hidalgo-de-la-jara/?qa-mobile-010228=' + Date.now(), {waitUntil:'domcontentloaded',timeout:70000});
    await vendor.waitForSelector('.emo-vendor-mobile-filter-toggle-010227', {visible:true,timeout:45000});
    await vendor.evaluate(() => document.querySelector('.emo-vendor-mobile-filter-toggle-010227')?.click());
    await sleep(350);
    const mobile = await vendor.evaluate(() => {
      const shell = document.querySelector('.emo-vendor-mobile-filter-shell-010227');
      const rail = shell?.querySelector('.emo-vendor-filter-rail-010225');
      const style = rail ? getComputedStyle(rail) : null;
      return [Boolean(shell && !shell.hidden), style?.position || '', style?.transform || ''];
    });
    check(mobile[0] && mobile[1] === 'static' && mobile[2] === 'none', 'mobile vendor rail mismatch ' + JSON.stringify(mobile));
    await vendor.screenshot({path:'qa/vendor-mobile-open-010228.png'});

    console.log('CATALOG_FILTER_STABILITY_010228_OK', JSON.stringify({shopState,vendorState,idleShop,idleVendor,stickyShop,stickyVendor,mobile}));
  } finally {
    await browser.close();
  }
})().catch(error => {
  console.error('CATALOG_FILTER_STABILITY_010228_ERROR', error);
  process.exit(1);
});
