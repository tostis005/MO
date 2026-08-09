const fs=require('fs');
const puppeteer=require('puppeteer-core');
const BASE='https://dev.elmercadodeorigen.com';
const sleep=(ms)=>new Promise(r=>setTimeout(r,ms));
const failures=[];const checks={};
const EXPECT={categories:[240,216,160],products:[239,208,195],story:[226,228,220]};

async function go(page,path,delay=650){
  const u=new URL(path,BASE);u.searchParams.set('qa-010113',Date.now());
  const res=await page.goto(u.href,{waitUntil:'domcontentloaded',timeout:60000});
  await page.addStyleTag({content:'#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}'}).catch(()=>{});
  await page.evaluate(()=>{document.documentElement.style.scrollBehavior='auto'});await sleep(delay);
  if(!res||res.status()>=400) failures.push(`${u.pathname}: HTTP ${res?.status()||'none'}`);
}
const rgb=v=>{const m=(v||'').match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);return m?[+m[1],+m[2],+m[3]]:null};
const matches=(v,e)=>{const c=rgb(v);return !!c&&c.every((n,i)=>Math.abs(n-e[i])<=1)};
const distance=(a,b)=>{const x=rgb(a),y=rgb(b);return x&&y?Math.hypot(x[0]-y[0],x[1]-y[1],x[2]-y[2]):0};
const clear=v=>v==='transparent'||/^rgba\([^)]*,\s*0(?:\.0+)?\s*\)$/i.test(v||'');

async function homeCheck(page,w,h){
  await page.setViewport({width:w,height:h,deviceScaleFactor:1,isMobile:w<=767,hasTouch:w<=1100});await go(page,'/');
  const m=await page.evaluate(()=>{
    const categories=document.querySelector('.emo-categories'),sec=document.querySelector('.emo-featured-products'),track=sec?.querySelector('ul.products'),card=track?.querySelector('li.product'),story=document.querySelector('.emo-story');
    const get=n=>{if(!n)return null;const s=getComputedStyle(n),r=n.getBoundingClientRect();return{bg:s.backgroundColor,img:s.backgroundImage,shadow:s.boxShadow,top:r.top+scrollY,bottom:r.bottom+scrollY,left:r.left,right:r.right}};
    const headingPatterns=[...document.querySelectorAll(':is(.emo-kicker,.emo-eyebrow) + :is(h1,h2,h3) + p')].map(p=>{const title=p.previousElementSibling,kicker=title?.previousElementSibling,hr=title?.getBoundingClientRect(),pr=p.getBoundingClientRect(),hs=title?getComputedStyle(title):null,ps=getComputedStyle(p);return{title:(title?.textContent||'').replace(/\s+/g,' ').trim(),kicker:(kicker?.textContent||'').replace(/\s+/g,' ').trim(),gap:hr&&pr?pr.top-hr.bottom:null,titleMarginBottom:hs?.marginBottom||'',descriptionMarginTop:ps.marginTop||''}});
    const categoryTitle=categories?.querySelector('.emo-section-heading h2'),categoryText=categories?.querySelector('.emo-section-heading > p'),chr=categoryTitle?.getBoundingClientRect(),cpr=categoryText?.getBoundingClientRect();
    return{categories:get(categories),sec:get(sec),shell:get(sec?.querySelector('.emo-shell')),woo:get(sec?.querySelector('.woocommerce')),track:get(track),card:get(card),story:get(story),headingPatterns,categoryGap:chr&&cpr?cpr.top-chr.bottom:null,overflow:Math.max(0,document.documentElement.scrollWidth-innerWidth)};
  });
  let gap=null;
  if(m.sec&&m.card){
    const x=Math.max(2,Math.min(w-2,m.card.left+18)),yDoc=Math.min(m.sec.bottom-2,m.card.bottom+7);
    await page.evaluate(y=>window.scrollTo(0,Math.max(0,y-innerHeight/2)),yDoc);await sleep(80);
    gap=await page.evaluate(({x,yDoc})=>{
      const effective=n=>{for(let p=n;p&&p!==document.documentElement;p=p.parentElement){const c=getComputedStyle(p).backgroundColor;if(c&&c!=='transparent'&&!/^rgba\([^)]*,\s*0(?:\.0+)?\s*\)$/i.test(c))return c}return getComputedStyle(document.body).backgroundColor};
      const y=yDoc-scrollY,n=document.elementFromPoint(x,y);return{x,yDoc,viewportY:y,bg:n?effective(n):'',tag:n?.tagName||'',cls:typeof n?.className==='string'?n.className:''};
    },{x,yDoc});
    await page.evaluate(()=>window.scrollTo(0,0));await sleep(50);
  }
  m.gap=gap;checks[`home-${w}`]=m;
  if(!m.categories||!m.sec||!m.track||!m.card||!m.story) failures.push(`home ${w}: central structure missing`);
  else{
    if(!matches(m.categories.bg,EXPECT.categories)) failures.push(`home ${w}: categories background ${m.categories.bg}`);
    if(!matches(m.sec.bg,EXPECT.products)) failures.push(`home ${w}: products background ${m.sec.bg}`);
    if(!matches(m.story.bg,EXPECT.story)) failures.push(`home ${w}: story background ${m.story.bg}`);
    if(distance(m.categories.bg,m.sec.bg)<30||distance(m.sec.bg,m.story.bg)<30) failures.push(`home ${w}: central section contrast too weak ${m.categories.bg}/${m.sec.bg}/${m.story.bg}`);
    for(const [n,x] of [['shell',m.shell],['woocommerce',m.woo],['track',m.track]]) if(x){if(!clear(x.bg)) failures.push(`home ${w}: ${n} background ${x.bg}`);if(x.img!=='none') failures.push(`home ${w}: ${n} background image`);if(x.shadow!=='none') failures.push(`home ${w}: ${n} shadow ${x.shadow}`)}
    if(m.card.shadow!=='none') failures.push(`home ${w}: product shadow still creates band ${m.card.shadow}`);
    if(!matches(m.gap?.bg||'',EXPECT.products)) failures.push(`home ${w}: below-card band ${JSON.stringify(m.gap)}`);
    if(m.headingPatterns.length<3) failures.push(`home ${w}: editorial heading patterns missing ${JSON.stringify(m.headingPatterns)}`);
    else{
      const gaps=m.headingPatterns.map(x=>x.gap).filter(Number.isFinite);
      if(gaps.some(x=>x<14||x>24)) failures.push(`home ${w}: title-description gap outside rhythm ${JSON.stringify(m.headingPatterns)}`);
      if(gaps.length&&Math.max(...gaps)-Math.min(...gaps)>2.5) failures.push(`home ${w}: title-description gaps inconsistent ${JSON.stringify(m.headingPatterns)}`);
    }
    if(w<=991&&Number.isFinite(m.categoryGap)&&m.categoryGap>0&&(m.categoryGap<14||m.categoryGap>24)) failures.push(`home ${w}: category reference gap ${m.categoryGap}`);
    if(m.overflow>1) failures.push(`home ${w}: overflow ${m.overflow}px`);
  }
  await page.screenshot({path:`qa/user-request-010113-home-${w}.png`,fullPage:true});
}

async function cartCheck(page,id,w=390,h=844){
  await page.setViewport({width:w,height:h,deviceScaleFactor:1,isMobile:true,hasTouch:true});await go(page,`/tienda/?add-to-cart=${id}`,900);await go(page,'/carrito/',850);
  const m=await page.evaluate(()=>{
    const card=document.querySelector('.cart_totals'),row=card?.querySelector('tr.order-total'),td=row?.querySelector('td'),line=td?.querySelector('.emo-cart-total-inline'),strong=line?.querySelector(':scope>strong'),total=strong?.querySelector('.amount')||strong,tax=line?.querySelector(':scope>.includes_tax');
    const R=n=>{if(!n)return null;const r=n.getBoundingClientRect();return{left:r.left,right:r.right,top:r.top,bottom:r.bottom,width:r.width,height:r.height,mid:(r.top+r.bottom)/2}};
    const cr=R(card),cs=card?getComputedStyle(card):null,ts=tax?getComputedStyle(tax):null;
    return{card:cr,td:R(td),line:R(line),total:R(total),tax:R(tax),expectedRight:cr&&cs?cr.right-parseFloat(cs.paddingRight||0):null,display:line?getComputedStyle(line).display:'',direction:line?getComputedStyle(line).flexDirection:'',taxFont:ts?.fontSize||'',taxText:(tax?.textContent||'').replace(/\s+/g,' ').trim(),overflow:Math.max(0,document.documentElement.scrollWidth-innerWidth)};
  });
  checks[`cart-${w}`]=m;
  if(!m.line||!m.total||!m.tax) failures.push(`cart ${w}: inline total missing ${JSON.stringify(m)}`);
  else{if(Math.abs(m.total.mid-m.tax.mid)>5) failures.push(`cart ${w}: VAT not inline`);if(Math.abs(m.tax.right-m.expectedRight)>5) failures.push(`cart ${w}: VAT detail not at right margin ${JSON.stringify(m)}`);if(m.total.left>=m.tax.left) failures.push(`cart ${w}: total is not before VAT ${JSON.stringify(m)}`);if(m.line.width>m.td.width+1) failures.push(`cart ${w}: total line overflow`);if(!/iva/i.test(m.taxText)) failures.push(`cart ${w}: VAT text missing`);if(parseFloat(m.taxFont)<10) failures.push(`cart ${w}: VAT text too small ${m.taxFont}`);if(!/flex/.test(m.display)||m.direction!=='row') failures.push(`cart ${w}: inline presentation wrong ${m.display}/${m.direction}`)}
  if(m.overflow>1) failures.push(`cart ${w}: overflow ${m.overflow}px`);
  await page.screenshot({path:`qa/user-request-010113-cart-${w}.png`,fullPage:true});
}

async function scrollCheck(page,path,label,w,h,capture=false){
  await page.setViewport({width:w,height:h,deviceScaleFactor:1,isMobile:w<=767,hasTouch:w<=1100});await go(page,path,700);const rows=[];
  for(const y of [0,4,8,12,16,24,40]){await page.evaluate(v=>window.scrollTo(0,v),y);await sleep(90);rows.push(await page.evaluate(()=>{const h=document.querySelector('.site-header'),i=document.querySelector('.site-header-inner'),c=document.querySelector('#content,.site-content'),s=h?getComputedStyle(h):null,is=i?getComputedStyle(i):null,cs=c?getComputedStyle(c):null,hr=h?.getBoundingClientRect(),cr=c?.getBoundingClientRect();const bump=[...document.querySelectorAll('.site-header .bumper,.site-header + .bumper,.site-header-inner + .bumper,.site-header-inner ~ .bumper')].some(n=>{const r=n.getBoundingClientRect(),x=getComputedStyle(n);return r.height>0&&r.width>0&&x.display!=='none'&&x.visibility!=='hidden'});return{y:scrollY,hh:hr?.height??null,docTop:cr?cr.top+scrollY:null,bg:s?.backgroundColor||'',shadow:s?.boxShadow||'',position:s?.position||'',innerPosition:is?.position||'',fija:!!i?.classList.contains('fija'),contentMarginTop:cs?.marginTop||'',bumper:bump}}));if(capture&&(y===0||y===16)) await page.screenshot({path:`qa/user-request-010113-scroll-${label}-${w}-${y}.png`,fullPage:false})}
  checks[`scroll-${label}-${w}`]=rows;const hs=rows.map(x=>x.hh).filter(Number.isFinite),tops=rows.map(x=>x.docTop).filter(Number.isFinite);
  if(!hs.length||!tops.length) failures.push(`${label} ${w}: missing scroll geometry`);else{if(Math.max(...hs)-Math.min(...hs)>1) failures.push(`${label} ${w}: header height jumps`);if(Math.max(...tops)-Math.min(...tops)>1.5) failures.push(`${label} ${w}: content document position jumps ${JSON.stringify(rows)}`)}
  if(new Set(rows.map(x=>x.bg)).size>1) failures.push(`${label} ${w}: header background changes at tiny scroll`);if(new Set(rows.map(x=>x.shadow)).size>1) failures.push(`${label} ${w}: header shadow changes at tiny scroll`);
  if(rows.some(x=>x.innerPosition==='fixed'||x.bumper||Math.abs(parseFloat(x.contentMarginTop)||0)>1)) failures.push(`${label} ${w}: legacy fixed-header layout effect active ${JSON.stringify(rows)}`);
}

(async()=>{
  fs.mkdirSync('qa',{recursive:true});const products=await fetch(`${BASE}/wp-json/wc/store/v1/products?per_page=100`).then(r=>r.json()),p=products.find(x=>x.is_purchasable&&x.is_in_stock&&x.type==='simple');if(!p)throw new Error('No purchasable simple product');
  const browser=await puppeteer.launch({executablePath:'/usr/bin/google-chrome',headless:'new',protocolTimeout:120000,args:['--no-sandbox','--disable-dev-shm-usage']});const page=await browser.newPage();page.setDefaultNavigationTimeout(60000);
  try{for(const [w,h] of [[390,844],[768,1024],[1440,1000]])await homeCheck(page,w,h);await cartCheck(page,p.id,390,844);await cartCheck(page,p.id,360,800);
    for(const [path,label] of [['/','home'],['/tienda/','shop'],['/quienes-somos/','about'],['/contacto/','contact'],['/contacto-productores/','producer-contact'],['/productores/','producers'],['/blog/','blog'],['/carrito/','cart'],['/finalizar-compra/','checkout']])await scrollCheck(page,path,label,390,844,label==='home'||label==='shop');
    for(const [path,label] of [['/','home'],['/tienda/','shop'],['/blog/','blog'],['/carrito/','cart']])await scrollCheck(page,path,label,1440,1000);
  }finally{await browser.close()}
  fs.writeFileSync('qa/user-request-010113-check.json',JSON.stringify({failures,checks},null,2));if(failures.length){console.error('USER_REQUEST_010113_FAIL '+JSON.stringify(failures));process.exitCode=2}else console.log('USER_REQUEST_010113_OK');
})();
