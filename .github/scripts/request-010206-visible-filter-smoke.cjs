const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function go(page, path, delay = 500) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010206-visible', Date.now().toString());
  const res = await page.goto(url.href, { waitUntil:'domcontentloaded', timeout:60000 });
  if (!res || res.status() >= 400) failures.push(`${url.pathname}: HTTP ${res?.status() || 'none'}`);
  await page.addStyleTag({content:'#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}'}).catch(()=>{});
  await sleep(delay);
}

async function waitRelease(page) {
  for (let i=0;i<25;i++) {
    await go(page,'/categoria-producto/jamones-paletas/',180);
    const ok = await page.evaluate(() => !!document.getElementById('elmercado-catalog-filter-system-final-010206') && !!document.getElementById('elmercado-catalog-filter-controller-final-010206') && !document.getElementById('elmercado-catalog-filter-system-010203'));
    if (ok) return;
    await sleep(4000);
  }
  throw new Error('0.10.206 not visible');
}

async function read(page) {
  return page.evaluate(() => {
    const cs=(n,p=null)=>n?getComputedStyle(n,p):null;
    const vis=n=>!!n&&cs(n).display!=='none'&&cs(n).visibility!=='hidden'&&n.getBoundingClientRect().height>0;
    const box=n=>n?(()=>{const r=n.getBoundingClientRect(),s=cs(n);return{top:+r.top.toFixed(1),bottom:+r.bottom.toFixed(1),height:+r.height.toFixed(1),display:s.display,visibility:s.visibility,background:s.backgroundColor,borderBottom:s.borderBottomWidth,fontWeight:s.fontWeight,color:s.color}})():null;
    const sidebar=document.querySelector('.emo-mobile-filter-content #secondary.widget-area,.emo-mobile-filter-content .shop-widget-area,.emo-mobile-filter-content .widget-area,#secondary.widget-area,.shop-widget-area');
    const direct=(sels)=>sidebar?[...sidebar.children].find(n=>sels.some(sel=>n.matches?.(sel)||n.querySelector?.(sel)))||null:null;
    const context=document.getElementById('emo-category-context');
    const active=document.querySelector('.emo-active-filter-chips[data-emo-global-active-filters="true"]');
    const price=direct(['.widget_price_filter','.wc-block-price-filter','.wp-block-woocommerce-price-filter']);
    const categories=direct(['.widget_product_categories','.wc-block-product-categories','.wp-block-woocommerce-product-categories']);
    const vendor=document.getElementById('emo-global-vendor-filter');
    const specific=document.getElementById('emo-category-attribute-filters');
    const chosen=[...document.querySelectorAll('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen')];
    const clear=[...document.querySelectorAll('a')].filter(a=>/limpiar/i.test((a.textContent||'').trim())&&vis(a)).map(a=>(a.textContent||'').replace(/\s+/g,' ').trim());
    const oldIds=['elmercado-category-specific-filters-controller-010185','elmercado-filter-state-final-controller-01085','elmercado-filter-postapply-sync-controller-01086','elmercado-active-filter-chips-controller-010193','elmercado-catalog-core-filters-controller-010193','elmercado-catalog-filter-unification-controller-010196','elmercado-catalog-filter-final-controller-010197','elmercado-catalog-filter-layout-lock-controller-010198','elmercado-catalog-filter-visual-refinement-controller-010199','elmercado-catalog-filter-visual-lock-controller-010200','elmercado-catalog-filter-controller-010203','elmercado-filter-state-final-01085','elmercado-filter-postapply-sync-01086','elmercado-filter-toolbar-final-01087','elmercado-desktop-filter-visual-final-01089','elmercado-catalog-filter-system-010203'].filter(id=>document.getElementById(id));
    const arrows=sel=>[...document.querySelectorAll(sel)].map(a=>({after:cs(a,'::after')?.content||'',icons:a.querySelectorAll(':scope > svg,:scope > i,:scope > .arrow,:scope > .caret,:scope > .chevron,:scope > .woostify-svg-icon').length}));
    return {
      sidebar:box(sidebar),context:box(context),active:box(active),price:box(price),categories:box(categories),vendor:box(vendor),specific:box(specific),
      vendorVisible:vis(vendor),specificVisible:vis(specific),activeVisible:vis(active),
      separators:[...document.querySelectorAll('#emo-category-attribute-filters .emo-category-filter-group')].map(g=>cs(g).borderBottomWidth),
      chosen:chosen.map(box),clear,
      vendorArrows:arrows('#emo-global-vendor-filter .emo-global-vendor-filter__item > a'),categoryArrows:arrows('.widget_product_categories li > a'),
      vendorCounts:[...document.querySelectorAll('#emo-global-vendor-filter .count')].map(n=>(n.textContent||'').trim()),
      oldIds,oldActive:document.querySelectorAll('.emo-active-filters').length,globalActive:document.querySelectorAll('.emo-active-filter-chips[data-emo-global-active-filters="true"]').length,
      mobileShellHidden:document.querySelector('.emo-mobile-filter-shell')?.hidden??null
    };
  });
}

const stable=s=>JSON.stringify({context:s.context,active:s.active,price:s.price,vendor:s.vendor,specific:s.specific,separators:s.separators,oldIds:s.oldIds,oldActive:s.oldActive,globalActive:s.globalActive});

async function hover(page){
  const row=await page.$('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:not(.chosen):not(.woocommerce-widget-layered-nav-list__item--chosen)');
  if(!row)return null;
  const read=n=>{const s=getComputedStyle(n),a=getComputedStyle(n.querySelector(':scope > a'));return{background:s.backgroundColor,shadow:s.boxShadow,fontWeight:a.fontWeight,color:a.color}};
  const before=await page.evaluate(read,row); await row.hover(); await sleep(100); const after=await page.evaluate(read,row); return{before,after};
}

(async()=>{
  fs.mkdirSync('qa',{recursive:true});
  const browser=await puppeteer.launch({headless:true,executablePath:'/usr/bin/google-chrome',args:['--no-sandbox','--disable-dev-shm-usage']});
  const page=await browser.newPage();
  try{
    await page.setViewport({width:1440,height:1500,deviceScaleFactor:1});
    await waitRelease(page); await go(page,'/categoria-producto/jamones-paletas/',120);
    report.cat0=await read(page); await sleep(2300); report.cat1=await read(page);
    if(stable(report.cat0)!==stable(report.cat1))failures.push('category changes after 2.3s');
    if(!report.cat1.vendorVisible||!report.cat1.specificVisible)failures.push('Vendor or specific filters not visibly rendered');
    if(report.cat1.separators.some(x=>x!=='0px'))failures.push('separator remains between filter groups');
    if(report.cat1.oldIds.length||report.cat1.oldActive)failures.push(`legacy filter layer remains: ${report.cat1.oldIds.join(',')}`);
    if(report.cat1.vendorCounts.length!==3)failures.push('vendor counts missing in Jamones');
    if(report.cat1.vendorArrows.some(x=>x.after!=='none'||x.icons))failures.push('vendor arrow remains');
    report.hover=await hover(page);
    if(!report.hover)failures.push('hover row unavailable');
    else{
      if(report.hover.after.background!=='rgb(217, 237, 224)')failures.push(`wrong hover background ${report.hover.after.background}`);
      if(report.hover.before.fontWeight!==report.hover.after.fontWeight)failures.push(`hover changes weight ${report.hover.before.fontWeight}->${report.hover.after.fontWeight}`);
    }

    const first=await page.evaluate(()=>document.querySelector('.emo-category-filter-rango-peso .woocommerce-widget-layered-nav-list__item:not(.chosen):not(.woocommerce-widget-layered-nav-list__item--chosen) > a')?.href||'');
    if(!first)failures.push('first Peso missing');
    else{
      await page.goto(first,{waitUntil:'domcontentloaded',timeout:60000});await sleep(450);
      const second=await page.evaluate(()=>[...document.querySelectorAll('.emo-category-filter-rango-peso .woocommerce-widget-layered-nav-list__item')].find(r=>!r.matches('.chosen,.woocommerce-widget-layered-nav-list__item--chosen'))?.querySelector(':scope > a')?.href||'');
      if(!second)failures.push('second Peso missing');
      else{
        await page.goto(second,{waitUntil:'domcontentloaded',timeout:60000});await sleep(500);
        report.peso0=await read(page);await sleep(2300);report.peso1=await read(page);
        if(stable(report.peso0)!==stable(report.peso1))failures.push('two-Peso layout changes after 2.3s');
        if(report.peso1.chosen.length<2)failures.push(`two selected Peso rows expected, got ${report.peso1.chosen.length}`);
        if(report.peso1.chosen.length>=2){report.gap=+(report.peso1.chosen[1].top-report.peso1.chosen[0].bottom).toFixed(1);if(report.gap<2)failures.push(`selected highlights merge: ${report.gap}px`);report.peso1.chosen.slice(0,2).forEach(r=>{if(r.background!=='rgb(217, 237, 224)')failures.push('selected background differs from hover')});}
        if(report.peso1.globalActive!==1||!report.peso1.activeVisible)failures.push(`expected one visible Filtros aplicados, got ${report.peso1.globalActive}`);
        if(report.peso1.clear.filter(t=>/limpiar todo/i.test(t)).length!==1)failures.push(`expected one Limpiar todo: ${report.peso1.clear.join('|')}`);
        if(report.peso1.clear.some(t=>/limpiar filtros/i.test(t)||/^limpiar$/i.test(t)))failures.push(`redundant clear remains: ${report.peso1.clear.join('|')}`);
        const s=report.peso1;if(!(s.context.top<s.active.top&&s.active.top<s.price.top&&s.price.top<s.vendor.top&&s.vendor.top<s.specific.top))failures.push(`wrong category order ${s.context.top},${s.active.top},${s.price.top},${s.vendor.top},${s.specific.top}`);
        report.filteredUrl=page.url();await page.screenshot({path:'qa/request-010206-visible-two-peso.png',fullPage:true});
      }
    }

    if(report.filteredUrl){
      await page.setViewport({width:390,height:844,deviceScaleFactor:1});await page.goto(report.filteredUrl,{waitUntil:'domcontentloaded',timeout:60000});await sleep(450);
      const toggle=await page.$('.emo-mobile-filter-toggle');if(!toggle)failures.push('mobile filter toggle missing');else{await toggle.click();await sleep(160);}
      report.mob0=await read(page);await sleep(2300);report.mob1=await read(page);
      if(stable(report.mob0)!==stable(report.mob1))failures.push('mobile open drawer changes after 2.3s');
      if(report.mob1.mobileShellHidden!==false)failures.push('mobile drawer not open');
      if(!report.mob1.vendorVisible||!report.mob1.specificVisible)failures.push('mobile Vendor or specifics not visible');
      if(report.mob1.globalActive!==1||report.mob1.oldActive)failures.push('mobile active-filter summary duplicated/missing');
      if(report.mob1.oldIds.length)failures.push(`mobile legacy filters loaded ${report.mob1.oldIds.join(',')}`);
      await page.screenshot({path:'qa/request-010206-visible-mobile.png',fullPage:true});
    }

    await page.setViewport({width:1440,height:1500,deviceScaleFactor:1});await go(page,'/tienda/',500);report.shop=await read(page);
    if(!(report.shop.price.top<report.shop.categories.top&&report.shop.categories.top<report.shop.vendor.top))failures.push('wrong shop order');
    if(report.shop.vendorArrows.some(x=>x.after!=='none'||x.icons)||report.shop.categoryArrows.some(x=>x.after!=='none'||x.icons))failures.push('shop arrow remains');
    if(report.shop.oldIds.length)failures.push(`shop legacy filter layer remains ${report.shop.oldIds.join(',')}`);
    await page.screenshot({path:'qa/request-010206-visible-shop.png',fullPage:true});

    fs.writeFileSync('qa/request-010206-visible-report.json',JSON.stringify({failures,report},null,2));
    if(failures.length){console.error('REQUEST_010206_VISIBLE_FAIL',JSON.stringify(failures));process.exitCode=2;}else console.log('REQUEST_010206_VISIBLE_OK',JSON.stringify({gap:report.gap,hover:report.hover,category:report.peso1,mobile:report.mob1,shop:report.shop}));
  }finally{await browser.close();}
})();