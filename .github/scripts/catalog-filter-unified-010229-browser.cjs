const puppeteer = require('puppeteer-core');

const base = process.env.BASE_URL || 'https://dev.elmercadodeorigen.com';
const chrome = process.env.CHROME_BIN || '/usr/bin/google-chrome';
const fail = (message, data) => { throw new Error(`${message}${data === undefined ? '' : ` ${JSON.stringify(data)}`}`); };
const near = (a,b,t=1) => Math.abs(Number(a)-Number(b)) <= t;
const wait = ms => new Promise(r => setTimeout(r,ms));

(async () => {
  const browser = await puppeteer.launch({executablePath:chrome, headless:true, args:['--no-sandbox','--disable-dev-shm-usage'], protocolTimeout:90000});
  try {
    const page = await browser.newPage();
    await page.setViewport({width:1440,height:1000,deviceScaleFactor:1});

    async function dismissOverlays() {
      await page.evaluate(() => {
        document.querySelectorAll('.hustle-ui,.hustle-popup,.hustle-overlay,.hustle-module-content').forEach(node => node.remove());
        document.documentElement.classList.remove('hustle-scroll-forbidden','hustle-no-scroll');
        document.body?.classList.remove('hustle-scroll-forbidden','hustle-no-scroll');
        if (document.documentElement.style.overflow === 'hidden') document.documentElement.style.removeProperty('overflow');
        if (document.body?.style.overflow === 'hidden') document.body.style.removeProperty('overflow');
      });
    }

    async function open(path) {
      await page.goto(`${base}${path}${path.includes('?') ? '&' : '?'}qa-unified=${Date.now()}`, {waitUntil:'domcontentloaded', timeout:60000});
      await page.waitForSelector('#elmercado-catalog-filter-unified-010229', {timeout:20000});
      await page.waitForSelector('#elmercado-catalog-filter-shared-interaction-010233', {timeout:20000});
      await wait(1200);
      await dismissOverlays();
    }

    async function style(selector) {
      return page.$eval(selector, el => {
        const s = getComputedStyle(el), r = el.getBoundingClientRect();
        return {
          display:s.display, position:s.position, top:s.top, width:r.width, height:r.height,
          marginTop:s.marginTop, marginRight:s.marginRight, marginBottom:s.marginBottom, marginLeft:s.marginLeft,
          paddingTop:s.paddingTop, paddingRight:s.paddingRight, paddingBottom:s.paddingBottom, paddingLeft:s.paddingLeft,
          fontSize:s.fontSize, fontWeight:s.fontWeight, lineHeight:s.lineHeight, letterSpacing:s.letterSpacing,
          textTransform:s.textTransform, textDecorationLine:s.textDecorationLine, color:s.color,
          background:s.backgroundColor, borderRadius:s.borderRadius, gap:s.gap, rowGap:s.rowGap, columnGap:s.columnGap,
          boxShadow:s.boxShadow, borderTopWidth:s.borderTopWidth, borderTopColor:s.borderTopColor
        };
      });
    }

    async function idleVariance(selector) {
      return page.$eval(selector, async el => {
        const values=[];
        for (let i=0;i<18;i++) { values.push(el.getBoundingClientRect().top); await new Promise(r=>setTimeout(r,100)); }
        return {min:Math.min(...values),max:Math.max(...values),values};
      });
    }

    async function hoverState(rowSelector) {
      return page.$eval(rowSelector, el => {
        const link=el.querySelector('.emo-filter-link-shared-010229');
        return {
          row:getComputedStyle(el).backgroundColor,
          decoration:getComputedStyle(link).textDecorationLine,
          rowHover:el.matches(':hover'),
          linkHover:link.matches(':hover')
        };
      });
    }

    async function priceStyle(prefix='') {
      return page.evaluate((p) => {
        const slider=document.querySelector(`${p}.widget_price_filter .price_slider`);
        const range=document.querySelector(`${p}.widget_price_filter .price_slider .ui-slider-range`);
        const handle=document.querySelector(`${p}.widget_price_filter .price_slider .ui-slider-handle`);
        if (!slider || !range || !handle) return null;
        const ss=getComputedStyle(slider), rs=getComputedStyle(range), hs=getComputedStyle(handle), hr=handle.getBoundingClientRect();
        return {
          slider:[slider.getBoundingClientRect().height,ss.marginTop,ss.marginRight,ss.marginBottom,ss.marginLeft,ss.backgroundColor,ss.borderRadius],
          range:[range.getBoundingClientRect().height,rs.backgroundColor,rs.borderRadius],
          handle:[hr.width,hr.height,hs.marginTop,hs.marginLeft,hs.borderTopWidth,hs.borderTopColor,hs.backgroundColor,hs.borderRadius]
        };
      }, prefix);
    }

    await open('/tienda/');
    const shopLegacy = await page.evaluate(() => [
      'elmercado-vendor-store-layout-lock-js-010225',
      'elmercado-vendor-shop-parity-script-010226',
      'elmercado-catalog-store-visual-parity-script-010227',
      'elmercado-catalog-filter-stability-script-010228'
    ].filter(id => document.getElementById(id)));
    if (shopLegacy.length) fail('legacy observer layers still loaded in Shop', shopLegacy);
    await page.waitForSelector('.emo-filter-rail-shared-010229');
    await page.waitForSelector('.emo-global-category-filter-010229 .emo-filter-row-shared-010229');
    const shopRail = await style('.emo-filter-rail-shared-010229');
    const shopTitle = await style('.widget_price_filter .emo-filter-title-shared-010229');
    const shopRow = await style('.emo-global-category-filter-010229 .emo-filter-row-shared-010229');
    const shopLink = await style('.emo-global-category-filter-010229 .emo-filter-link-shared-010229');
    const shopCount = await style('.emo-global-category-filter-010229 .emo-filter-count-shared-010229');
    const shopPrice = await priceStyle('');
    const shopIdle = await idleVariance('.emo-filter-rail-shared-010229');
    if (shopIdle.max-shopIdle.min > 1) fail('Shop rail moves while idle', shopIdle);
    await dismissOverlays();
    await page.hover('.emo-global-category-filter-010229 .emo-filter-link-shared-010229');
    await wait(220);
    const shopHover = await hoverState('.emo-global-category-filter-010229 .emo-filter-row-shared-010229');
    if (!shopHover.decoration.includes('underline')) fail('Shop hover is not underlined', shopHover);
    if (shopHover.row !== 'rgb(217, 237, 224)') fail('Shop hover background is not the shared green', shopHover);
    await page.evaluate(() => window.scrollTo(0,1200));
    await wait(350);
    const shopSticky = await page.$eval('.emo-filter-rail-shared-010229', el => el.getBoundingClientRect().top);
    if (!near(shopSticky,94,2)) fail('Shop sticky position failed', shopSticky);

    await open('/tienda/hidalgo-de-la-jara/');
    const vendorRuntime = await page.evaluate(() => ({
      lean:!!document.getElementById('elmercado-vendor-filter-lean-runtime-010233'),
      old:!!document.getElementById('elmercado-vendor-store-catalog-010225'),
      oldCss:!!document.getElementById('elmercado-vendor-store-catalog-010225'),
      oldOverlay:!!document.querySelector('.emo-vendor-filter-overlay-010225'),
      oldToggle:!!document.querySelector('.emo-vendor-filter-toggle-010225')
    }));
    if (!vendorRuntime.lean || vendorRuntime.old || vendorRuntime.oldCss || vendorRuntime.oldOverlay || vendorRuntime.oldToggle) fail('producer legacy runtime was not retired', vendorRuntime);
    const vendorLegacy = await page.evaluate(() => [
      'elmercado-vendor-store-layout-lock-js-010225',
      'elmercado-vendor-shop-parity-script-010226',
      'elmercado-catalog-store-visual-parity-script-010227',
      'elmercado-catalog-filter-stability-script-010228'
    ].filter(id => document.getElementById(id)));
    if (vendorLegacy.length) fail('legacy observer layers still loaded in producer', vendorLegacy);
    await page.waitForSelector('#wcfmmp-store .emo-filter-rail-shared-010229');
    await page.waitForSelector('#emo-vendor-category-filter .emo-filter-row-shared-010229');
    const vendorRail = await style('#wcfmmp-store .emo-filter-rail-shared-010229');
    const vendorTitle = await style('#wcfmmp-store .widget_price_filter .emo-filter-title-shared-010229');
    const vendorRow = await style('#emo-vendor-category-filter .emo-filter-row-shared-010229');
    const vendorLink = await style('#emo-vendor-category-filter .emo-filter-link-shared-010229');
    const vendorCount = await style('#emo-vendor-category-filter .emo-filter-count-shared-010229');
    const vendorPrice = await priceStyle('#wcfmmp-store ');

    const pick = (o,keys) => Object.fromEntries(keys.map(k=>[k,o[k]]));
    const railKeys=['display','position','top','width','paddingTop','paddingRight','paddingBottom','paddingLeft','background','borderRadius','boxShadow','borderTopWidth','borderTopColor'];
    const titleKeys=['display','marginBottom','paddingTop','paddingBottom','fontSize','fontWeight','lineHeight','letterSpacing','textTransform','color'];
    const rowKeys=['display','height','paddingTop','paddingRight','paddingBottom','paddingLeft','columnGap','borderRadius'];
    const linkKeys=['paddingTop','paddingRight','paddingBottom','paddingLeft','fontSize','fontWeight','lineHeight','color'];
    const countKeys=['fontSize','fontWeight','lineHeight','color'];
    if (JSON.stringify(pick(shopRail,railKeys)) !== JSON.stringify(pick(vendorRail,railKeys))) fail('rail styles differ', {shop:pick(shopRail,railKeys),vendor:pick(vendorRail,railKeys)});
    if (JSON.stringify(pick(shopTitle,titleKeys)) !== JSON.stringify(pick(vendorTitle,titleKeys))) fail('title styles differ', {shop:pick(shopTitle,titleKeys),vendor:pick(vendorTitle,titleKeys)});
    if (JSON.stringify(pick(shopRow,rowKeys)) !== JSON.stringify(pick(vendorRow,rowKeys))) fail('row styles differ', {shop:pick(shopRow,rowKeys),vendor:pick(vendorRow,rowKeys)});
    if (JSON.stringify(pick(shopLink,linkKeys)) !== JSON.stringify(pick(vendorLink,linkKeys))) fail('link styles differ', {shop:pick(shopLink,linkKeys),vendor:pick(vendorLink,linkKeys)});
    if (JSON.stringify(pick(shopCount,countKeys)) !== JSON.stringify(pick(vendorCount,countKeys))) fail('count styles differ', {shop:pick(shopCount,countKeys),vendor:pick(vendorCount,countKeys)});
    if (JSON.stringify(shopPrice) !== JSON.stringify(vendorPrice)) fail('price filter styles differ', {shopPrice,vendorPrice});

    const vendorIdle = await idleVariance('#wcfmmp-store .emo-filter-rail-shared-010229');
    if (vendorIdle.max-vendorIdle.min > 1) fail('producer rail moves while idle', vendorIdle);
    await dismissOverlays();
    await page.hover('#emo-vendor-category-filter .emo-filter-link-shared-010229');
    await wait(220);
    const vendorHover = await hoverState('#emo-vendor-category-filter .emo-filter-row-shared-010229');
    if (!vendorHover.decoration.includes('underline')) fail('producer hover is not underlined', vendorHover);
    if (vendorHover.row !== shopHover.row) fail('hover backgrounds differ', {shopHover,vendorHover});

    await dismissOverlays();
    await page.evaluate(() => window.scrollTo(0,1200));
    await wait(350);
    const sticky = await page.$eval('#wcfmmp-store .emo-filter-rail-shared-010229', el => el.getBoundingClientRect().top);
    if (!near(sticky,94,2)) fail('producer sticky position failed', sticky);

    await open('/tienda/hidalgo-de-la-jara/?emo_vendor_cat=embutidos-y-curados');
    await page.waitForSelector('.emo-category-context__remove');
    const remove = await page.$eval('.emo-category-context__remove', el => {
      const spans=[...el.querySelectorAll(':scope > span')], s=getComputedStyle(el);
      const ys=spans.map(n=>Math.round(n.getBoundingClientRect().top));
      return {text:(el.textContent||'').replace(/\s+/g,''), whiteSpace:s.whiteSpace, ys, before:getComputedStyle(el,'::before').content, after:getComputedStyle(el,'::after').content, rects:el.getClientRects().length};
    });
    if (remove.text !== '×Quitar') fail('remove copy is not exact', remove);
    if (remove.whiteSpace !== 'nowrap' || new Set(remove.ys).size !== 1) fail('remove wraps to multiple lines', remove);
    if (!["none","normal",'""'].includes(remove.before) || !["none","normal",'""'].includes(remove.after)) fail('remove has stray pseudo content', remove);

    await page.setViewport({width:390,height:844,deviceScaleFactor:1});
    await open('/tienda/hidalgo-de-la-jara/?emo_vendor_cat=embutidos-y-curados');
    await page.waitForSelector('.emo-filter-toggle-shared-010229');
    const toggle = await page.$eval('.emo-filter-toggle-shared-010229', el => { const r=el.getBoundingClientRect(),s=getComputedStyle(el); return {w:r.width,h:r.height,radius:s.borderRadius,display:s.display}; });
    if (!near(toggle.h,44,1)) fail('mobile toggle height differs from contract', toggle);
    await page.click('.emo-filter-toggle-shared-010229');
    await wait(250);
    const mobile = await page.$eval('.emo-filter-shell-shared-010229', shell => {
      const panel=shell.querySelector('.emo-mobile-filter-panel'), close=shell.querySelector('.emo-mobile-filter-close'), rail=shell.querySelector('.emo-filter-rail-shared-010229');
      const pr=panel.getBoundingClientRect(), cr=close.getBoundingClientRect();
      return {hidden:shell.hidden,panelWidth:pr.width,closeW:cr.width,closeH:cr.height,railInside:!!rail};
    });
    if (mobile.hidden || !mobile.railInside || !near(mobile.closeW,40,1) || !near(mobile.closeH,40,1)) fail('mobile producer drawer contract failed', mobile);

    console.log('CATALOG_FILTER_UNIFIED_010233_OK', JSON.stringify({shopRail,vendorRail,shopTitle,vendorTitle,shopRow,vendorRow,shopPrice,vendorPrice,shopIdle:{min:shopIdle.min,max:shopIdle.max},vendorIdle:{min:vendorIdle.min,max:vendorIdle.max},shopHover,vendorHover,shopSticky,sticky,remove,toggle,mobile,vendorRuntime}));
  } finally {
    await browser.close();
  }
})().catch(err => { console.error('CATALOG_FILTER_UNIFIED_010233_ERROR', err); process.exit(1); });