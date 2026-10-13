const { chromium } = require('playwright-core');
const chrome = process.env.CHROME_PATH;
if (!chrome) throw Error('Chrome/Chromium no disponible');
const base = 'https://www.elmercadodeorigen.com';
const pages = [
  {path:'/', surface:'home', desired:1},
  {path:'/tienda/', surface:'shop', desired:1},
  {path:'/blog/', surface:'journal', desired:1},
  {path:'/confit-de-pato-que-es-como-se-prepara-y-con-que-acompanarlo/',surface:'post',desired:1},
  {path:'/como-conservar-foie-gras-antes-y-despues-de-abrirlo/',surface:'post',desired:1},
  {path:'/cestas-navidad-empresas-2026/',surface:'landing',desired:0},
];
(async()=>{
 const browser = await chromium.launch({executablePath:chrome,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 try {
   for (const width of [1366,390]) {
     for (const item of pages) {
       const page=await browser.newPage({viewport:{width,height:900},deviceScaleFactor:1});
       const u=base+item.path+'?navidad_banner_check='+Date.now();
       const response=await page.goto(u,{waitUntil:'domcontentloaded',timeout:60000});
       if(!response||response.status()!==200)throw Error(item.path+': HTTP '+(response && response.status()));
       await page.waitForTimeout(750);
       const v=await page.evaluate(()=>{
         const els=[...document.querySelectorAll('.emdo-navidad-promo[data-emdo-navidad-promo="2026"]')];
         const b=els[0];const r=b?.getBoundingClientRect();
         // Woostify incluye H1 ocultos con bounding box 0x0.
         // Evaluamos solo el encabezado que realmente se pinta en pantalla.
         const headers=[...document.querySelectorAll('main h1, main h2, .woocommerce-products-header h1, h1.page-title')];
         const visibleHeading=headers.find(el=>{
           const rect=el.getBoundingClientRect(),style=getComputedStyle(el);
           return rect.width>0&&rect.height>0&&style.visibility!=='hidden'&&style.display!=='none';
         });
         const h=visibleHeading?.getBoundingClientRect();
         const target=b?.querySelector('a.emdo-navidad-promo__link');
         return {
           banners:els.length,
           href:target?.getAttribute('href'),
           label:target?.innerText.trim(),
           rect:r?{left:r.left,right:r.right,top:r.top,height:r.height}:null,
           h1:h?{top:h.top,bottom:h.bottom}:null,
           docWidth:document.documentElement.scrollWidth,
           width:innerWidth,
           blogContent:!!b?.closest('.emo-article-content'),
           shopBeforeHeading:r&&h? r.top <= h.top+1:null,
         };
       });
       const issues=[];
       if(v.banners!==item.desired)issues.push('Esperados '+item.desired+' banners; hay '+v.banners);
       if(v.banners&&(!v.href||!v.href.includes('/cestas-navidad-empresas-2026/')))issues.push('CTA sin destino landing');
       if(v.banners&&(!v.label||!/(presupuesto)/i.test(v.label)))issues.push('CTA incorrecta');
       if(v.docWidth>width+5)issues.push('Desbordamiento horizontal');
       if(v.rect&&(v.rect.left<-5||v.rect.right>width+5))issues.push('Banner supera viewport');
       if(item.surface==='shop'&&v.banners&&v.h1&&!v.shopBeforeHeading)issues.push('Banner no esta antes del primer encabezado visible de tienda');
       if(item.surface==='post'&&v.banners&&!v.blogContent)issues.push('Banner fuera de tarjeta de articulo');
       console.log(JSON.stringify({path:item.path,width,...v,issues}));
       await page.screenshot({path:'/tmp/navidad-banner-'+item.surface+'-'+width+'.png',fullPage:true});
       await page.close();
       if(issues.length)throw Error(item.path+' width '+width+': '+issues.join(' | '));
     }
   }
   console.log('NAVIDAD_BANNERS_SITEWIDE_OK');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
