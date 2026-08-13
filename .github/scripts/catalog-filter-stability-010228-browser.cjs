const puppeteer = require('puppeteer-core');
const base = process.env.BASE_URL || 'https://dev.elmercadodeorigen.com';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const assert = (ok, msg) => { if (!ok) throw new Error(msg); };

async function open(browser, path) {
  const page = await browser.newPage();
  await page.setViewport({width:1440,height:1000,deviceScaleFactor:1});
  const sep = path.includes('?') ? '&' : '?';
  await page.goto(base + path + sep + 'qa-stability-010228=' + Date.now(), {waitUntil:'domcontentloaded',timeout:70000});
  await page.waitForSelector('#elmercado-catalog-filter-stability-010228', {timeout:45000});
  await sleep(2400);
  return page;
}

async function state(page, kind) {
  return page.evaluate(kind => {
    const rail = kind === 'shop' ? document.querySelector('#secondary.widget-area,.shop-widget-area') : document.querySelector('.emo-vendor-filter-rail-010225');
    const toolbar = kind === 'shop' ? document.querySelector('.emo-catalog-toolbar-parity-010227,.woostify-sorting') : document.querySelector('#wcfmmp-store .emo-catalog-toolbar-parity-010227,#wcfmmp-store .woostify-sorting');
    const handle = rail?.querySelector('.widget_price_filter .ui-slider-handle');
    const slider = rail?.querySelector('.widget_price_filter .price_slider');
    const rs = rail ? getComputedStyle(rail) : null;
    const hs = handle ? getComputedStyle(handle) : null;
    const ss = slider ? getComputedStyle(slider) : null;
    return {
      rail: rail ? rail.getBoundingClientRect().toJSON() : null,
      toolbar: toolbar ? toolbar.getBoundingClientRect().toJSON() : null,
      position: rs?.position || '', top: rs?.top || '', overflowY: rs?.overflowY || '',
      handle: hs ? [hs.width,hs.height,hs.top,hs.marginLeft,hs.borderWidth,hs.borderColor,hs.backgroundColor,hs.transform] : null,
      slider: ss ? [ss.height,ss.marginTop,ss.marginRight,ss.marginBottom,ss.marginLeft,ss.backgroundColor] : null,
    };
  }, kind);
}

async function tops(page, kind, count=4) {
  const out=[];
  for (let i=0;i<count;i++) { out.push((await state(page,kind)).rail?.top ?? null); await sleep(350); }
  return out;
}

async function hover(page, kind) {
  const selector = kind === 'shop' ? '#secondary .emo-filter-row-parity-010227 > a,.shop-widget-area .emo-filter-row-parity-010227 > a' : '.emo-vendor-filter-rail-010225 .emo-filter-row-parity-010227 > a';
  const el = await page.$(selector);
  if (!el) return null;
  await el.hover(); await sleep(120);
  return el.evaluate(node => { const s=getComputedStyle(node); return [s.textDecorationLine,s.textUnderlineOffset,s.color]; });
}

(async () => {
  const browser = await puppeteer.launch({headless:true,executablePath:'/usr/bin/google-chrome',protocolTimeout:120000,args:['--no-sandbox','--disable-dev-shm-usage','--disable-gpu']});
  try {
    const shop = await open(browser,'/tienda/');
    const vendor = await open(browser,'/tienda/hidalgo-de-la-jara/');
    const a = await state(shop,'shop');
    const b = await state(vendor,'vendor');
    assert(a.rail && a.toolbar && b.rail && b.toolbar,'desktop rail/toolbar missing');
    assert(Math.abs(a.rail.top-a.toolbar.top)<=3,`shop align ${a.rail.top}/${a.toolbar.top}`);
    assert(Math.abs(b.rail.top-b.toolbar.top)<=3,`vendor align ${b.rail.top}/${b.toolbar.top}`);
    assert(a.position==='sticky' && b.position==='sticky',`sticky ${a.position}/${b.position}`);
    assert(a.top==='94px' && b.top==='94px',`top ${a.top}/${b.top}`);
    assert(a.overflowY==='auto' && b.overflowY==='auto',`overflow ${a.overflowY}/${b.overflowY}`);
    const idleShop=await tops(shop,'shop'), idleVendor=await tops(vendor,'vendor');
    const spread=v=>Math.max(...v)-Math.min(...v);
    assert(spread(idleShop)<=1.5,`shop idle ${JSON.stringify(idleShop)}`);
    assert(spread(idleVendor)<=1.5,`vendor idle ${JSON.stringify(idleVendor)}`);
    assert(JSON.stringify(a.handle)===JSON.stringify(b.handle),`handle mismatch ${JSON.stringify(a.handle)} / ${JSON.stringify(b.handle)}`);
    assert(JSON.stringify(a.slider)===JSON.stringify(b.slider),`slider mismatch ${JSON.stringify(a.slider)} / ${JSON.stringify(b.slider)}`);
    const ha=await hover(shop,'shop'), hb=await hover(vendor,'vendor');
    assert(ha?.[0].includes('underline'),`shop hover ${JSON.stringify(ha)}`);
    assert(hb?.[0].includes('underline'),`vendor hover ${JSON.stringify(hb)}`);
    assert(ha[1]===hb[1],`underline offset ${ha[1]}/${hb[1]}`);
    await shop.evaluate(()=>scrollTo(0,1200)); await vendor.evaluate(()=>scrollTo(0,1200)); await sleep(500);
    const stickyShop=await tops(shop,'shop',3), stickyVendor=await tops(vendor,'vendor',3);
    stickyShop.forEach(v=>assert(Math.abs(v-94)<=3,`shop sticky ${JSON.stringify(stickyShop)}`));
    stickyVendor.forEach(v=>assert(Math.abs(v-94)<=3,`vendor sticky ${JSON.stringify(stickyVendor)}`));
    await shop.screenshot({path:'qa/shop-desktop-010228.png'}); await vendor.screenshot({path:'qa/vendor-desktop-010228.png'});
    await vendor.setViewport({width:390,height:844,deviceScaleFactor:1});
    await vendor.goto(base+'/tienda/hidalgo-de-la-jara/?qa-mobile-010228='+Date.now(),{waitUntil:'domcontentloaded',timeout:70000});
    await vendor.waitForSelector('.emo-vendor-mobile-filter-toggle-010227',{visible:true,timeout:45000});
    await vendor.evaluate(()=>document.querySelector('.emo-vendor-mobile-filter-toggle-010227')?.click()); await sleep(350);
    const mobile=await vendor.evaluate(()=>{const shell=document.querySelector('.emo-vendor-mobile-filter-shell-010227');const rail=shell?.querySelector('.emo-vendor-filter-rail-010225');const s=rail?getComputedStyle(rail):null;return [!!shell&&!shell.hidden,s?.position||'',s?.transform||''];});
    assert(mobile[0] && mobile[1]==='static' && mobile[2]==='none',`mobile ${JSON.stringify(mobile)}`);
    await vendor.screenshot({path:'qa/vendor-mobile-open-010228.png'});
    console.log('CATALOG_FILTER_STABILITY_010228_OK',JSON.stringify({a,b,idleShop,idleVendor,stickyShop,stickyVendor,ha,hb,mobile}));
  } finally { await browser.close(); }
})().catch(err=>{console.error('CATALOG_FILTER_STABILITY_010228_ERROR',err);process.exit(1);});
