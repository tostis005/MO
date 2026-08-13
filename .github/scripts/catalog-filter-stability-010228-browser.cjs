const puppeteer = require('puppeteer-core');
const base = process.env.BASE_URL || 'https://dev.elmercadodeorigen.com';
const check = (ok, message) => { if (!ok) throw new Error(message); };

async function open(browser, path) {
  const page = await browser.newPage();
  await page.setViewport({width:1440,height:1000,deviceScaleFactor:1});
  const sep = path.includes('?') ? '&' : '?';
  await page.goto(base + path + sep + 'qa-stability-010228=' + Date.now(), {waitUntil:'domcontentloaded',timeout:70000});
  await page.waitForSelector('#elmercado-catalog-filter-stability-010228', {timeout:45000});
  return page;
}

async function probe(page, kind) {
  return page.evaluate(async kind => {
    const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
    const get = () => {
      const rail = kind === 'shop' ? document.querySelector('#secondary.widget-area,.shop-widget-area') : document.querySelector('.emo-vendor-filter-rail-010225');
      const toolbar = kind === 'shop' ? document.querySelector('.emo-catalog-toolbar-parity-010227,.woostify-sorting') : document.querySelector('#wcfmmp-store .emo-catalog-toolbar-parity-010227,#wcfmmp-store .woostify-sorting');
      const handle = rail?.querySelector('.widget_price_filter .ui-slider-handle');
      const slider = rail?.querySelector('.widget_price_filter .price_slider');
      const rs = rail ? getComputedStyle(rail) : null;
      const hs = handle ? getComputedStyle(handle) : null;
      const ss = slider ? getComputedStyle(slider) : null;
      return {
        railTop: rail?.getBoundingClientRect().top ?? null,
        toolbarTop: toolbar?.getBoundingClientRect().top ?? null,
        position: rs?.position || '',
        stickyTop: rs?.top || '',
        overflowY: rs?.overflowY || '',
        handle: hs ? [hs.width,hs.height,hs.top,hs.marginTop,hs.marginLeft,hs.borderWidth,hs.borderColor,hs.backgroundColor,hs.transform] : null,
        slider: ss ? [ss.height,ss.marginTop,ss.marginRight,ss.marginBottom,ss.marginLeft,ss.backgroundColor] : null,
      };
    };
    await wait(2200);
    const initial = get();
    await wait(900);
    const idle = get();
    window.scrollTo(0, 1200);
    await wait(500);
    const scrolled = get();
    return {initial,idle,scrolled};
  }, kind);
}

(async () => {
  const browser = await puppeteer.launch({headless:true,executablePath:'/usr/bin/google-chrome',protocolTimeout:60000,args:['--no-sandbox','--disable-dev-shm-usage','--disable-gpu']});
  try {
    const shop = await open(browser, '/tienda/');
    console.log('QA_010228_SHOP_OPEN');
    const shopProbe = await probe(shop, 'shop');
    console.log('QA_010228_SHOP_PROBED', JSON.stringify(shopProbe));
    await shop.close();

    const vendor = await open(browser, '/tienda/hidalgo-de-la-jara/');
    console.log('QA_010228_VENDOR_OPEN');
    const vendorProbe = await probe(vendor, 'vendor');
    console.log('QA_010228_VENDOR_PROBED', JSON.stringify(vendorProbe));
    await vendor.close();

    const a = shopProbe.initial;
    const b = vendorProbe.initial;
    check(a.railTop !== null && a.toolbarTop !== null && b.railTop !== null && b.toolbarTop !== null, 'rail or toolbar missing');
    check(Math.abs(a.railTop-a.toolbarTop)<=3, 'shop initial alignment failed');
    check(Math.abs(b.railTop-b.toolbarTop)<=3, 'vendor initial alignment failed');
    check(a.position==='sticky' && b.position==='sticky', 'desktop rails are not sticky');
    check(a.stickyTop==='94px' && b.stickyTop==='94px', 'sticky top mismatch');
    check(a.overflowY==='auto' && b.overflowY==='auto', 'overflow mismatch');
    check(Math.abs(shopProbe.idle.railTop-a.railTop)<=1.5, 'shop moves while idle');
    check(Math.abs(vendorProbe.idle.railTop-b.railTop)<=1.5, 'vendor moves while idle');
    check(JSON.stringify(a.handle)===JSON.stringify(b.handle), 'price handle mismatch ' + JSON.stringify([a.handle,b.handle]));
    check(JSON.stringify(a.slider)===JSON.stringify(b.slider), 'price slider mismatch ' + JSON.stringify([a.slider,b.slider]));
    check(Math.abs(shopProbe.scrolled.railTop-94)<=3, 'shop sticky after scroll failed ' + shopProbe.scrolled.railTop);
    check(Math.abs(vendorProbe.scrolled.railTop-94)<=3, 'vendor sticky after scroll failed ' + vendorProbe.scrolled.railTop);

    console.log('CATALOG_FILTER_STABILITY_010228_OK', JSON.stringify({shopProbe,vendorProbe}));
  } finally {
    await browser.close();
  }
})().catch(error => {
  console.error('CATALOG_FILTER_STABILITY_010228_ERROR', error);
  process.exit(1);
});
