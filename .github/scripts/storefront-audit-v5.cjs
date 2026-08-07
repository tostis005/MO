const fs = require('fs');
const puppeteer = require('puppeteer-core');
const base = 'https://dev.elmercadodeorigen.com';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const report = { errors: [], warnings: [], checks: {}, runtime: {} };
const ignoreUrl = u => /google-analytics|googletagmanager|doubleclick|clarity|facebook|notification\.mp3|fonts\.googleapis|fonts\.gstatic/i.test(u);

async function visible(el) {
  if (!el) return false;
  return el.evaluate(e => {
    const r = e.getBoundingClientRect();
    const s = getComputedStyle(e);
    return r.width > 0 && r.height > 0 && r.right > 0 && r.bottom > 0 && r.left < innerWidth && r.top < innerHeight && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
  }).catch(() => false);
}
async function firstVisible(page, selector) {
  for (const el of await page.$$(selector)) if (await visible(el)) return el;
  return null;
}
async function clickVisible(page, selector) {
  const el = await firstVisible(page, selector);
  if (!el) return false;
  try { await el.click({delay: 30}); }
  catch { await el.evaluate(e => e.click()); }
  return true;
}
async function go(page, path, label) {
  const raw = path.startsWith('http') ? path : base + path;
  const url = raw + (raw.includes('?') ? '&' : '?') + 'qa=' + Date.now();
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.addStyleTag({content:'#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}'}).catch(()=>{});
  await sleep(900);
  if (!response || response.status() >= 400) report.errors.push(`${label}: HTTP ${response?.status() || 'none'}`);
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 2);
  if (overflow) report.errors.push(`${label}: horizontal overflow`);
  return response;
}
async function cartCount(page) {
  return page.evaluate(() => {
    const nodes = document.querySelectorAll('.site-header .shop-cart-count,.site-header .shopping-cart-count,.site-header .cart-count,.site-header .count,.site-header .mini-cart-count,.site-header .elmercado-cart-direct-count');
    for (const n of nodes) {
      const r=n.getBoundingClientRect(), s=getComputedStyle(n);
      if (r.width>0 && r.height>0 && r.right>0 && r.left<innerWidth && s.display!=='none' && s.visibility!=='hidden' && Number(s.opacity)>0) {
        const m=(n.textContent||'').match(/\d+/); return m ? Number(m[0]) : 0;
      }
    }
    return 0;
  });
}
function runtime(page, key) {
  const consoleErrors=[], failed=[];
  page.on('console', m => { if (m.type()==='error' && !/favicon|third-party cookie/i.test(m.text())) consoleErrors.push(m.text()); });
  page.on('requestfailed', r => { if (!ignoreUrl(r.url())) failed.push(`${r.url()} :: ${r.failure()?.errorText||'failed'}`); });
  report.runtime[key]={consoleErrors,failed};
}
async function shot(page,name){ await page.screenshot({path:`qa/${name}.png`,fullPage:true}); }
async function safe(label, fn){ try{ await fn(); }catch(e){ report.errors.push(`${label}: ${e.message}`); } }
async function clickMayNavigate(page, el){
  const nav=page.waitForNavigation({waitUntil:'domcontentloaded',timeout:5000}).catch(()=>null);
  try{await el.click({delay:30});}catch{await el.evaluate(e=>e.click());}
  await nav; await sleep(700);
}

async function modeAudit(browser, mode, viewport){
  const ctx=await browser.createBrowserContext();
  const page=await ctx.newPage();
  await page.setViewport(viewport); page.setDefaultNavigationTimeout(45000); runtime(page,mode);

  await safe(`home ${mode}`, async()=>{
    await go(page,'/',`home ${mode}`); await shot(page,`home-${mode}`);
    if ((await cartCount(page)) !== 0) report.errors.push(`home ${mode}: zero cart badge visible`);
    const g=await page.evaluate(()=>{const h=document.querySelector('.site-header'),t=document.querySelector('.site-tools');if(!h||!t)return null;const a=h.getBoundingClientRect(),b=t.getBoundingClientRect();return Math.abs((a.top+a.height/2)-(b.top+b.height/2));});
    if(mode==='mobile' && g!==null && g>6) report.errors.push(`home mobile: utility icons off-center by ${Math.round(g)}px`);
    if(mode==='mobile' && await page.$('.emo-featured-products ul.products')){
      if(!(await firstVisible(page,'.emo-carousel-control--previous')) || !(await firstVisible(page,'.emo-carousel-control--next'))) report.errors.push('home mobile: carousel arrows missing');
    }
    await page.keyboard.press('Tab'); await page.keyboard.press('Tab');
    const focus=await page.evaluate(()=>document.activeElement && document.activeElement!==document.body);
    if(!focus) report.errors.push(`home ${mode}: keyboard focus not reachable`);
  });

  if(mode==='mobile') await safe('mobile menu', async()=>{
    await go(page,'/','mobile menu home');
    if(!(await clickVisible(page,'.site-header .toggle-sidebar-menu-btn'))) return report.errors.push('mobile menu: toggle missing');
    await sleep(450);
    const open=await page.evaluate(()=>{const e=document.querySelector('.sidebar-menu');if(!e)return false;const r=e.getBoundingClientRect(),s=getComputedStyle(e);return r.width>0&&r.height>0&&r.right>0&&r.left<innerWidth&&s.visibility!=='hidden'&&s.display!=='none';});
    if(!open) report.errors.push('mobile menu: did not open');
    if(!(await clickVisible(page,'.sidebar-menu .close-sidebar-menu-btn,.sidebar-menu .close-sidebar-menu,.sidebar-menu [class*="close-sidebar"]'))) await clickVisible(page,'.site-header .toggle-sidebar-menu-btn');
    await sleep(500);
    const closed=await page.evaluate(()=>{const e=document.querySelector('.sidebar-menu');if(!e)return true;const r=e.getBoundingClientRect(),s=getComputedStyle(e);return r.right<=0||r.left>=innerWidth||s.visibility==='hidden'||s.display==='none'||e.getAttribute('aria-hidden')==='true'||e.hasAttribute('inert');});
    if(!closed) report.errors.push('mobile menu: did not close');
  });

  await safe(`search ${mode}`, async()=>{
    await go(page,'/',`search home ${mode}`);
    const toggled=await clickVisible(page,'.site-header .header-search-icon,.site-header .search-icon,.site-header .site-search-toggle,.site-header .js-dgwt-wcas-search-icon-handler');
    if(!toggled) return report.errors.push(`search ${mode}: toggle missing`);
    await sleep(650);
    const input=await firstVisible(page,'input[type="search"]');
    if(!input) report.errors.push(`search ${mode}: visible input missing`); else { await input.type('aceite'); report.checks[`searchOpen-${mode}`]=true; }
    await go(page,'/?s=aceite&post_type=product',`search results ${mode}`);
    if(!(await page.evaluate(()=>document.querySelectorAll('li.product').length>0))) report.errors.push(`search ${mode}: known query returned no products`);
    await go(page,'/?s=zzzz-no-existe-qa-987654&post_type=product',`search no-results ${mode}`);
    if(await page.evaluate(()=>document.querySelectorAll('li.product').length>0)) report.errors.push(`search ${mode}: impossible query returned products`);
  });

  let firstProduct=null;
  await safe(`shop ${mode}`, async()=>{
    await go(page,'/tienda/',`shop ${mode}`); await shot(page,`shop-${mode}`);
    const seller=await page.evaluate(()=>[...document.querySelectorAll('select')].some(s=>[...s.options].some(o=>/(todos los productores|todos los vendedores)/i.test(o.textContent||''))&&(()=>{const r=s.getBoundingClientRect(),c=getComputedStyle(s);return r.width>0&&r.height>0&&c.display!=='none'&&c.visibility!=='hidden'})()));
    if(seller) report.errors.push(`shop ${mode}: producer filter visible`);
    const border=await page.$eval('.woocommerce-ordering select',e=>[getComputedStyle(e).borderTopWidth,getComputedStyle(e).borderBottomWidth]).catch(()=>null);
    if(border && border.includes('0px')) report.errors.push(`shop ${mode}: ordering border incomplete`);
    const result=await page.$eval('.woocommerce-result-count',e=>(e.textContent||'').replace(/\s+/g,' ').trim()).catch(()=>null);
    if(result && /\d\s*de\s*\d/i.test(result)===false && /mostrando/i.test(result)) report.errors.push(`shop ${mode}: malformed result count`);
    const heights=await page.$$eval('ul.products li.product .woocommerce-loop-product__title,ul.products li.product .elmercado-product-title-link',els=>els.slice(0,8).map(e=>[e.getBoundingClientRect().height,parseFloat(getComputedStyle(e).lineHeight)]));
    if(heights.some(([h,lh])=>lh&&h+1<lh*1.9)) report.errors.push(`shop ${mode}: product title does not reserve two lines`);
    if(!(await page.$('.widget_price_filter .price_slider,.price_slider_wrapper .ui-slider-horizontal'))) report.warnings.push(`shop ${mode}: price slider not detected`);
    const cat=await page.$('.widget_product_categories a,.product-categories a'); if(cat){const href=await cat.evaluate(a=>a.href);await go(page,href,`category ${mode}`);}
    await go(page,'/tienda/',`shop restore ${mode}`);
    const pag=await page.$('.woocommerce-pagination a.page-numbers'); if(pag){const href=await pag.evaluate(a=>a.href);await go(page,href,`pagination ${mode}`);}
    await go(page,'/tienda/',`product discovery ${mode}`);
    firstProduct=await page.$eval('li.product a.woocommerce-loop-product__link',a=>a.href).catch(()=>null);
    if(!firstProduct) report.errors.push(`shop ${mode}: product link missing`);
    const out=await page.$('li.product.outofstock a.woocommerce-loop-product__link');
    if(out){const href=await out.evaluate(a=>a.href);await go(page,href,`out-of-stock ${mode}`);if(!(await page.evaluate(()=>/agotado|out of stock/i.test(document.body.innerText)))) report.warnings.push(`out-of-stock ${mode}: explicit status not found`);}
  });

  await safe(`product/cart ${mode}`, async()=>{
    if(!firstProduct) return;
    await go(page,firstProduct,`product ${mode}`); await shot(page,`product-${mode}`);
    for(const sel of await page.$$('form.variations_form select')){const v=await sel.evaluate(e=>[...e.options].find(o=>o.value&&!o.disabled)?.value||'');if(v)await sel.select(v);}
    await sleep(450);
    const add=await firstVisible(page,'.single_add_to_cart_button:not(.disabled),form.cart button[type="submit"]');
    if(add){const before=await cartCount(page);await clickMayNavigate(page,add);const after=await cartCount(page);report.checks[`cartCountAfterAdd-${mode}`]={before,after};if(after<1)report.errors.push(`cart ${mode}: badge did not update after add`);await clickVisible(page,'.site-header .shopping-cart,.site-header .shopping-bag-button,.site-header a.cart-contents');await sleep(350);if(!(await page.evaluate(()=>!!document.querySelector('.mini_cart_item,.woocommerce-mini-cart-item'))))report.warnings.push(`minicart ${mode}: item not detected`);}
    await go(page,'/carrito/',`cart ${mode}`); await shot(page,`cart-${mode}`);
    const qty=await page.$('input.qty'); if(!qty){report.errors.push(`cart ${mode}: cart unexpectedly empty`);return;}
    await qty.click({clickCount:3}); await qty.type('2'); const update=await firstVisible(page,'button[name="update_cart"],input[name="update_cart"]'); if(update) await clickMayNavigate(page,update);
    const coupon=await firstVisible(page,'input#coupon_code,input[name="coupon_code"]'); if(coupon){await coupon.type('QA-CUPON-INVALIDO-987654');const apply=await firstVisible(page,'button[name="apply_coupon"],input[name="apply_coupon"]');if(apply){await clickMayNavigate(page,apply);const notice=await page.evaluate(()=>!!document.querySelector('.woocommerce-error,.woocommerce-notices-wrapper,.woocommerce-message'));if(!notice)report.errors.push(`cart ${mode}: invalid coupon produced no notice`);}}
    await go(page,'/finalizar-compra/',`checkout ${mode}`); await shot(page,`checkout-${mode}`); if(!(await page.evaluate(()=>!!document.querySelector('form.checkout,#customer_details,.woocommerce-checkout')))) report.errors.push(`checkout ${mode}: checkout form missing`);
  });

  await safe(`contact ${mode}`, async()=>{
    await go(page,'/contacto/',`contact ${mode}`); await shot(page,`contact-${mode}`);
    const form=await page.$('form.wpcf7-form,.elementor-form,form[id*="contact"]'); if(!form){report.errors.push(`contact ${mode}: form missing`);return;}
    const invalid=await form.evaluate(f=>!f.checkValidity()); if(!invalid) report.warnings.push(`contact ${mode}: empty form reports valid`);
  });

  await safe(`content ${mode}`, async()=>{
    await go(page,'/blog/',`blog ${mode}`); const article=await page.$eval('article a[href],h2 a[href]',a=>a.href).catch(()=>null); if(article) await go(page,article,`blog article ${mode}`); else report.warnings.push(`blog ${mode}: article link not found`);
    await go(page,'/',`footer ${mode}`); const legal=await page.evaluate(()=>[...document.querySelectorAll('footer a[href],.site-footer a[href]')].filter(a=>/privacidad|cookies|aviso legal|t[eé]rminos|condiciones/i.test((a.textContent||'')+' '+a.href)).map(a=>a.href).filter((v,i,a)=>a.indexOf(v)===i).slice(0,6)); if(!legal.length) report.errors.push(`footer ${mode}: legal links missing`); for(let i=0;i<legal.length;i++) await go(page,legal[i],`legal ${mode} ${i+1}`);
  });

  await safe(`vendor ${mode}`, async()=>{
    await go(page,'/tienda/hidalgo-de-la-jara/',`vendor ${mode}`); await shot(page,`vendor-${mode}`);
    const gap=await page.evaluate(()=>{const t=document.querySelector('.wcfmmp-store-tabs,.wcfm_store_tabs,.store-tabs,.wcfmmp-store-tab');if(!t)return null;const next=t.nextElementSibling;if(!next)return null;return next.getBoundingClientRect().top-t.getBoundingClientRect().bottom;}); if(gap!==null&&gap<16)report.errors.push(`vendor ${mode}: tabs/content spacing ${Math.round(gap)}px`);
  });

  const r=report.runtime[mode]; if([...new Set(r.consoleErrors)].length) report.errors.push(`${mode}: console error(s) detected`); if([...new Set(r.failed)].length) report.errors.push(`${mode}: request failure(s) detected`);
  await ctx.close();
}

(async()=>{
  fs.mkdirSync('qa',{recursive:true});
  const browser=await puppeteer.launch({executablePath:'/usr/bin/google-chrome',headless:'new',protocolTimeout:120000,args:['--no-sandbox','--disable-dev-shm-usage']});
  try{
    const clean=await browser.createBrowserContext(); const p=await clean.newPage(); await p.setViewport({width:1440,height:1000}); runtime(p,'clean');
    await go(p,'/carrito/','empty cart'); if(!(await p.evaluate(()=>/carrito.*vac[ií]o|tu carrito est[aá] vac[ií]o|volver a la tienda|return to shop/i.test(document.body.innerText))))report.errors.push('empty cart state missing');
    await go(p,'/mi-cuenta/','account logged out'); if(!(await p.evaluate(()=>!!document.querySelector('form.woocommerce-form-login,input[name="username"],input[name="password"]'))))report.warnings.push('account logged-out form not detected');
    const cr=report.runtime.clean;if([...new Set(cr.consoleErrors)].length)report.errors.push('clean: console error(s) detected');if([...new Set(cr.failed)].length)report.errors.push('clean: request failure(s) detected'); await clean.close();
    await modeAudit(browser,'desktop',{width:1440,height:1000}); await modeAudit(browser,'mobile',{width:390,height:844,isMobile:true,hasTouch:true});
  }finally{fs.writeFileSync('qa/report-v5.json',JSON.stringify(report,null,2));await browser.close();}
  if(report.errors.length){console.error(report.errors.join('\n'));process.exit(2);} console.log('Storefront audit v5 passed');
})().catch(e=>{report.errors.push(`fatal: ${e.message}`);fs.mkdirSync('qa',{recursive:true});fs.writeFileSync('qa/report-v5.json',JSON.stringify(report,null,2));console.error(e);process.exit(1);});