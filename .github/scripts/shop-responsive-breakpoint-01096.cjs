const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];

async function go(page, path) {
  const url = `${BASE}${path}${path.includes('?') ? '&' : '?'}qa96=${Date.now()}`;
  await page.goto(url, { waitUntil:'domcontentloaded', timeout:60000 });
  await sleep(750);
}

async function metrics(page) {
  return page.evaluate(() => {
    const visible = (el) => {
      if (!el) return false;
      const r=el.getBoundingClientRect(), s=getComputedStyle(el);
      return r.width>0 && r.height>0 && s.display!=='none' && s.visibility!=='hidden' && Number(s.opacity)>0;
    };
    const primary=document.querySelector('#primary,.content-area');
    const container=primary?.closest('.woostify-container') || document.querySelector('#content > .woostify-container,.site-content > .woostify-container');
    const siteSidebar=document.querySelector('.site-content #secondary.widget-area,.site-content .shop-widget-area,.site-content > .woostify-container > .widget-area');
    const toggle=document.querySelector('#emo-premium-filter-toggle,.emo-mobile-filter-toggle');
    const product=document.querySelector('ul.products li.product');
    const title=product?.querySelector('.woocommerce-loop-product__title,.product-title,h2,h3');
    const price=product?.querySelector('.price');
    const pr=primary?.getBoundingClientRect(), cr=container?.getBoundingClientRect(), tr=title?.getBoundingClientRect(), rr=price?.getBoundingClientRect();
    const ts=title?getComputedStyle(title):null;
    const native=[...document.querySelectorAll('.woostify-sorting button.filter,.woostify-sorting a.filter,.woostify-sorting .filter.show')].filter(visible);
    return {
      viewport:innerWidth,
      overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth+3,
      primaryContainerRatio:pr&&cr?pr.width/cr.width:0,
      siteSidebarVisible:visible(siteSidebar),
      siteSidebarPresent:!!siteSidebar,
      toggleVisible:visible(toggle),
      nativeFilterVisible:native.length,
      titleHeight:tr?.height||0,
      titleLineHeight:ts?parseFloat(ts.lineHeight)||0:0,
      titlePriceOverlap:tr&&rr ? rr.top < tr.bottom-1 : false,
      pricePresent:!!price,
    };
  });
}

(async()=>{
  const browser=await puppeteer.launch({ executablePath:'/usr/bin/google-chrome', headless:'new', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const page=await browser.newPage();
  try {
    for (const width of [1440,1200,1101,1100,1099,1024,992,991,900,768,390]) {
      await page.setViewport({width,height:900,deviceScaleFactor:1});
      await go(page,'/tienda/');
      const m=await metrics(page);
      console.log(`${width}px ${JSON.stringify(m)}`);
      if (m.overflow) failures.push(`${width}px: horizontal overflow`);
      if (m.nativeFilterVisible) failures.push(`${width}px: native toolbar filter visible (${m.nativeFilterVisible})`);
      if (width>=1101) {
        if (!m.siteSidebarVisible) failures.push(`${width}px: desktop sidebar not visible`);
        if (m.toggleVisible) failures.push(`${width}px: compact filter toggle visible on desktop`);
      } else {
        if (!m.toggleVisible) failures.push(`${width}px: canonical filter toggle not visible`);
        if (m.siteSidebarVisible) failures.push(`${width}px: sidebar still visibly reserves catalog space`);
        if (m.primaryContainerRatio<0.98) failures.push(`${width}px: catalog does not fill gutter container (${m.primaryContainerRatio.toFixed(2)})`);
      }
      if (m.titleLineHeight && m.titleHeight < m.titleLineHeight*1.95) failures.push(`${width}px: product title box too short (${m.titleHeight}/${m.titleLineHeight})`);
      if (m.titlePriceOverlap) failures.push(`${width}px: price overlaps product title`);
      if (!m.pricePresent) failures.push(`${width}px: first product price missing`);
    }

    await page.setViewport({width:1024,height:900,deviceScaleFactor:1});
    await go(page,'/tienda/');
    const toggle=await page.$('#emo-premium-filter-toggle,.emo-mobile-filter-toggle');
    if (!toggle) failures.push('1024px: canonical filter toggle missing for interaction');
    else {
      await toggle.click();
      await sleep(300);
      const drawer=await page.evaluate(()=>{
        const shell=document.querySelector('#emo-premium-filter-shell,.emo-mobile-filter-shell');
        const sidebar=shell?.querySelector('.widget-area,#secondary,.shop-widget-area,.widget_product_categories')?.closest('.widget-area,#secondary,.shop-widget-area') || shell?.querySelector('.widget_product_categories');
        const close=shell?.querySelector('.emo-mobile-filter-close');
        const vis=(el)=>{ if(!el)return false; const r=el.getBoundingClientRect(),s=getComputedStyle(el); return r.width>0&&r.height>0&&s.display!=='none'&&s.visibility!=='hidden'; };
        const category=shell?.querySelector('.widget_product_categories a');
        return { shell:vis(shell), sidebar:vis(sidebar), close:vis(close), category:vis(category), expanded:document.querySelector('#emo-premium-filter-toggle,.emo-mobile-filter-toggle')?.getAttribute('aria-expanded')||'' };
      });
      console.log(`FILTER_DRAWER_01096 ${JSON.stringify(drawer)}`);
      if (!drawer.shell || !drawer.sidebar || !drawer.close || !drawer.category || drawer.expanded!=='true') failures.push(`1024px: filter drawer incomplete ${JSON.stringify(drawer)}`);
    }

    for (const path of ['/tienda/','/contacto/','/productores/']) {
      await go(page,path);
      const headerVisible=await page.evaluate(()=>{
        const selectors='.page-header,.entry-header,.woocommerce-products-header,.woocommerce-breadcrumb,.breadcrumbs,.woostify-breadcrumb,.page-title-wrap';
        return [...document.querySelectorAll(selectors)].some((el)=>{ const r=el.getBoundingClientRect(),s=getComputedStyle(el); return r.width>0&&r.height>0&&s.display!=='none'&&s.visibility!=='hidden'; });
      });
      if (headerVisible) failures.push(`${path}: redundant title/breadcrumb header visible`);
    }

    await go(page,'/blog/');
    const postHref=await page.evaluate(()=>document.querySelector('article a[href*="/blog/"]:not([href$="/blog/"]), article .entry-title a, .post-card a')?.href||'');
    if (postHref) {
      await page.goto(`${postHref}${postHref.includes('?')?'&':'?'}qa96=${Date.now()}`,{waitUntil:'domcontentloaded',timeout:60000});
      await sleep(550);
      const blog=await page.evaluate(()=>{
        const p=document.querySelector('#primary,.content-area')?.getBoundingClientRect();
        const secondary=document.querySelector('#secondary');
        const visible=secondary?(()=>{const r=secondary.getBoundingClientRect(),s=getComputedStyle(secondary);return r.width>0&&r.height>0&&s.display!=='none'&&s.visibility!=='hidden';})():false;
        return {ratio:p?p.width/document.documentElement.clientWidth:0,secondaryVisible:visible};
      });
      if (blog.secondaryVisible || blog.ratio<0.9) failures.push(`single blog not full width ${JSON.stringify(blog)}`);
    }
  } finally { await browser.close(); }

  if (failures.length) { console.error(failures.join('\n')); process.exitCode=2; }
  else console.log('SHOP_RESPONSIVE_01096_OK');
})();
