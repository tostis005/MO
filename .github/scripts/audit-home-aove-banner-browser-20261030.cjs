const {chromium}=require('playwright-core');
(async()=>{
 const exe=process.env.CHROME;
 if(!exe)throw Error('Chrome not installed on runner');
 const browser=await chromium.launch({executablePath:exe,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 try{
  for(const [label,width,height] of [['desktop',1440,900],['mobile',390,844]]){
   const page=await browser.newPage({viewport:{width,height},deviceScaleFactor:1});
   await page.goto('https://www.elmercadodeorigen.com/?aove_browser_probe='+label,{waitUntil:'domcontentloaded',timeout:85000});
   await page.waitForSelector('aside.emdo-aove-harvest',{timeout:15000});
   await page.waitForTimeout(750);
   const m=await page.evaluate(()=>{
     const get=s=>document.querySelector(s);
     const rect=e=>{let r=e.getBoundingClientRect();return{x:r.x,y:r.y,w:r.width,h:r.height,bottom:r.bottom,right:r.right}};
     const a=get('aside.emdo-aove-harvest'),c=get('aside.emdo-navidad-promo--home'),hero=get('.emo-hero');
     const ct=get('.emdo-aove-harvest__link'),inner=get('.emdo-aove-harvest__inner'),copy=get('.emdo-aove-harvest__copy');
     const hs=get('.emdo-aove-harvest__title'),desc=get('.emdo-aove-harvest__description');
     return {count:document.querySelectorAll('aside.emdo-aove-harvest').length,
      hero:hero?rect(hero):null,aove:rect(a),christmas:rect(c),inner:rect(inner),copy:rect(copy),
      title:rect(hs),desc:rect(desc),cta:rect(ct),
      bg:getComputedStyle(a).backgroundColor,christmasBg:getComputedStyle(c).backgroundColor,
      ctaBg:getComputedStyle(ct).backgroundColor,link:ct.href,
      docWidth:document.documentElement.scrollWidth,vw:innerWidth
     };
   });
   console.log('AOVE_BROWSER_'+label.toUpperCase()+'='+JSON.stringify(m));
   if(m.count!==1||!m.hero||m.aove.y+2<m.hero.bottom||m.aove.bottom>m.christmas.y+3||
      m.bg===m.christmasBg||m.inner.w>m.vw||m.cta.right>m.vw||!m.link.endsWith('/tienda/1957/'))throw Error('AOVE browser layout check failed '+label);
   if(label==='mobile'&&(m.cta.y+2<m.desc.bottom||m.title.w>m.vw))throw Error('AOVE mobile overlaps');
   await page.locator('aside.emdo-aove-harvest').screenshot({path:'/tmp/aove-'+label+'.png'});
   await page.close();
  }
 }finally{await browser.close();}
 console.log('AOVE_DESKTOP_AND_MOBILE_BROWSER_OK');
})().catch(err=>{console.error(err.stack);process.exit(1)});
