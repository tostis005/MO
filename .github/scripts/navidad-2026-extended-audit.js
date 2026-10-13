const {chromium}=require('playwright-core');
const chrome=process.env.CHROME_PATH;
if(!chrome)throw Error('Chrome no disponible');
const base='https://www.elmercadodeorigen.com';
const pages=[
 {path:'/',short:'home'},
 {path:'/tienda/',short:'shop'},
 {path:'/blog/',short:'blog'},
 {path:'/cestas-navidad-empresas-2026/',short:'landing'},
 {path:'/confit-de-pato-que-es-como-se-prepara-y-con-que-acompanarlo/',short:'post'},
 {path:'/cestas-lotes-navidad-empresas/',short:'old-cestas'},
];
const browserRun=async()=>{
 const browser=await chromium.launch({executablePath:chrome,args:['--no-sandbox','--disable-dev-shm-usage'],headless:true});
 try {
 for(const width of [1366,1024,768,390,360]){
  for(const test of pages){
   const page=await browser.newPage({viewport:{width,height:900},deviceScaleFactor:1});
   try{
    const response=await page.goto(base+test.path,{waitUntil:'domcontentloaded',timeout:50000});
    await page.waitForTimeout(test.short==='shop'?9000:2000);
    await page.evaluate(()=>document.fonts.ready);
    const stats=await page.evaluate(()=>{
      const rect=e=>{if(!e)return null;const r=e.getBoundingClientRect();const c=getComputedStyle(e);return {tag:e.tagName,cl:e.className?.toString()?.slice(0,100),w:Math.round(r.width),x:Math.round(r.left),h:Math.round(r.height),display:c.display,grid:c.gridTemplateColumns,position:c.position,overflowX:c.overflowX,fontSize:c.fontSize}};
      const sels=[
      'body','main','.site-content','.site-content .container','.woostify-container',
      '.woocommerce','.woocommerce-page','.woocommerce-products-header',
      '.woocommerce-products-header__title','.woocommerce-result-count',
      '.woocommerce ul.products','.woocommerce ul.products li.product',
      'main h1','.site-main','.woostify-shop-filter',
      '.emdo-navidad-promo','.emdo-navidad-promo__inner',
      '.emdo-xmas__form-card','.emdo-xmas__fields','.emo-article-content'
      ];
      const result={};
      for(const s of sels){let e=document.querySelector(s);if(e)result[s]=rect(e)}
      const banner=[...document.querySelectorAll('.emdo-navidad-promo[data-emdo-navidad-promo="2026"]')];
      const form=document.querySelector('.emdo-xmas--landing form');
      const inputs=form?[...form.querySelectorAll('input:not([type=hidden]), textarea, select')].map(el=>({name:el.name,...rect(el)})):[];
      return {viewport:innerWidth,scrollWidth:document.documentElement.scrollWidth,href:location.href,css:[...document.styleSheets].map(s=>s.href).filter(Boolean).filter(x=>/woostify|catalog|shop|navidad|woocommerce/.test(x)).slice(0,25),productCount:document.querySelectorAll('li.product').length,banners:banner.length,formFields:inputs,els:result};
    });
    console.log('AUDIT_'+JSON.stringify({path:test.path,width,status:response?.status(),...stats}));
    if(width===1366||width===390) await page.screenshot({path:'/tmp/emdo-audit-'+test.short+'-'+width+'.png',fullPage:true});
    if(test.short==='shop'&&width===1366){
      const earlier=stats.els['main h1'] || stats.els['.woocommerce-products-header__title'];
      // Test whether the banner stylesheet alone is affecting the shop layout.
      await page.evaluate(()=>{for(let e of document.querySelectorAll('link[rel=stylesheet]')){if(/navidad-2026-promo\.css/.test(e.href))e.disabled=true;}});
      await page.waitForTimeout(500);
      const disabled=await page.evaluate(()=>{
       let target=document.querySelector('main h1, .woocommerce-products-header__title,ul.products');
       let r=target?.getBoundingClientRect();
       return {width:r?.width, left:r?.left,shopGridWidth:document.querySelector('.woocommerce ul.products')?.getBoundingClientRect().width,docWidth:document.documentElement.scrollWidth};
      });
      console.log('SHOP_WITHOUT_PROMO_CSS_'+JSON.stringify({before:earlier,after:disabled}));
      await page.screenshot({path:'/tmp/emdo-audit-shop-without-promo-css-1366.png',fullPage:true});
    }
   }finally{await page.close();}
  }
 }
 }finally{await browser.close();}
};
browserRun().catch(e=>{console.error(e);process.exitCode=1});
