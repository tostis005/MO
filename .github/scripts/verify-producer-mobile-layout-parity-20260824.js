'use strict';

const puppeteer = require('puppeteer-core');
const fs = require('fs');
const sleep = ms => new Promise(r => setTimeout(r, ms));
const fail = (message, data) => { throw new Error(`${message} ${JSON.stringify(data || {})}`); };

(async () => {
  const base = process.env.BASE_URL || 'https://www.elmercadodeorigen.com';
  const browser = await puppeteer.launch({executablePath:'/usr/bin/google-chrome',headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
  const page = await browser.newPage();
  await page.setViewport({width:390,height:844,isMobile:true,hasTouch:true,deviceScaleFactor:3});
  await page.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1');
  await page.setCacheEnabled(false);

  const measure = async (path,key) => {
    await page.goto(`${base}${path}?_mdo_layout=${Date.now()}`,{waitUntil:'networkidle2',timeout:60000});
    await page.waitForSelector('.emo-catalog-toolbar-shared-010229',{visible:true,timeout:30000});
    await page.waitForSelector('ul.products',{visible:true,timeout:30000});
    await sleep(3500);
    const result = await page.evaluate(() => {
      const rect = el => { const r=el?.getBoundingClientRect(); return r ? {left:r.left,width:r.width,right:r.right,height:r.height,top:r.top} : null; };
      const toolbar=document.querySelector('.emo-catalog-toolbar-shared-010229');
      const destination=toolbar?.querySelector('[data-mdo-destination-open],[data-mdo-ps-destination-open]');
      const ordering=toolbar?.querySelector('select[name="orderby"]');
      const orderForm=ordering?.closest('.woocommerce-ordering');
      const filter=document.querySelector('#emo-premium-filter-toggle,#wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229');
      const products=document.querySelector('ul.products');
      const firstProduct=products?.querySelector(':scope > li.product');
      const destSvg=[...(destination?.querySelectorAll('svg')||[])].filter(svg=>{const s=getComputedStyle(svg),r=svg.getBoundingClientRect();return s.display!=='none'&&s.visibility!=='hidden'&&s.opacity!=='0'&&r.width>1&&r.height>1;}).map(rect);
      const pseudo=getComputedStyle(orderForm,'::after');
      return {
        toolbar:rect(toolbar),destination:rect(destination),ordering:rect(ordering),filter:rect(filter),products:rect(products),firstProduct:rect(firstProduct),
        destSvg,
        orderArrow:{width:parseFloat(pseudo.width),height:parseFloat(pseudo.height),right:parseFloat(pseudo.right),top:pseudo.top,transform:pseudo.transform,pointerEvents:pseudo.pointerEvents},
        orderPaddingRight:getComputedStyle(ordering).paddingRight,
        href:location.href,
      };
    });
    await page.$eval('ul.products',el=>el.scrollIntoView({block:'start',inline:'nearest'}));
    await sleep(250);
    await page.screenshot({path:`/tmp/producer-mobile-layout-${key}.png`,fullPage:false});
    return result;
  };

  try {
    const shop=await measure('/tienda/','shop');
    const producer=await measure('/tienda/1957/','1957');
    const hidalgo=await measure('/tienda/hidalgo-de-la-jara/','hidalgo');
    const surfaces=[['shop',shop],['1957',producer],['hidalgo',hidalgo]];

    for(const [label,s] of surfaces){
      if(!s.toolbar) fail(`${label}: missing toolbar`,s);
      const expectedLeft=s.toolbar.left+12;
      const expectedWidth=s.toolbar.width-24;
      for(const part of ['destination','ordering']){
        if(!s[part]) fail(`${label}: missing ${part}`,s);
        if(Math.abs(s[part].left-expectedLeft)>1||Math.abs(s[part].width-expectedWidth)>1){
          fail(`${label}: ${part} does not respect the 12px inner card gutter`,{expected:{left:expectedLeft,width:expectedWidth},actual:s[part],toolbar:s.toolbar});
        }
      }
      if(Math.abs(s.destination.left-s.ordering.left)>1||Math.abs(s.destination.width-s.ordering.width)>1){
        fail(`${label}: destination and ordering are not equal width`,{destination:s.destination,ordering:s.ordering});
      }
    }

    const producers=[['1957',producer],['hidalgo',hidalgo]];
    for(const [label,p] of producers){
      for(const part of ['toolbar','destination','ordering','filter','products']){
        if(!shop[part]||!p[part]) fail(`${label}: missing ${part}`,{shop:shop[part],producer:p[part]});
        if(Math.abs(shop[part].left-p[part].left)>1||Math.abs(shop[part].width-p[part].width)>1) fail(`${label}: ${part} does not match shop width`,{shop:shop[part],producer:p[part]});
      }
      if(!shop.firstProduct||!p.firstProduct) fail(`${label}: first product missing`,{shop:shop.firstProduct,producer:p.firstProduct});
      const shopInset=shop.firstProduct.left-shop.products.left;
      const producerInset=p.firstProduct.left-p.products.left;
      if(Math.abs(shopInset-producerInset)>1||Math.abs(shop.firstProduct.width-p.firstProduct.width)>1) {
        fail(`${label}: product card geometry differs from shop`,{shop:{products:shop.products,firstProduct:shop.firstProduct,inset:shopInset},producer:{products:p.products,firstProduct:p.firstProduct,inset:producerInset}});
      }
      if(p.destSvg.length!==1) fail(`${label}: destination must have one visible arrow`,p.destSvg);
      const d=p.destSvg[0];
      if(d.width<16||d.width>20||d.height<16||d.height>20) fail(`${label}: destination arrow box size is off`,d);
      if(p.orderArrow.width<6||p.orderArrow.width>8||p.orderArrow.height<6||p.orderArrow.height>8||p.orderArrow.pointerEvents!=='none') fail(`${label}: ordering arrow geometry is off`,p.orderArrow);
      const destCenter=d.top+d.height/2;
      const controlCenter=p.destination.top+p.destination.height/2;
      if(Math.abs(destCenter-controlCenter)>2) fail(`${label}: destination arrow is not vertically centered`,{destCenter,controlCenter,destination:p.destination,arrow:d});
      if(p.orderPaddingRight!=='36px') fail(`${label}: ordering padding drifted`,p.orderPaddingRight);
    }
    fs.writeFileSync('/tmp/producer-mobile-layout-parity-20260824.json',JSON.stringify({ok:true,shop,producer,hidalgo},null,2));
    console.log(JSON.stringify({ok:true,revision:'20260824-layout-v4-inner-width'}));
  } finally { await browser.close(); }
})().catch(error=>{try{fs.writeFileSync('/tmp/producer-mobile-layout-parity-20260824.json',JSON.stringify({ok:false,error:String(error.stack||error)},null,2));}catch(_){};console.error(error.stack||error);process.exit(1);});