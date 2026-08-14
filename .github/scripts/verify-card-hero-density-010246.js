const { chromium } = require('playwright');

(async()=>{
  const browser = await chromium.launch({headless:true});
  for (const width of [1440,390]) {
    const page = await browser.newPage({viewport:{width,height:1400}});
    await page.goto('https://www.elmercadodeorigen.com/tienda/?emoqa=010246-'+Date.now(), {waitUntil:'networkidle', timeout:120000});
    const c = await page.locator('ul.products li.product').first().evaluate(el => {
      const title=el.querySelector('.product-loop-content > .woocommerce-loop-product__title');
      const price=el.querySelector('.product-loop-meta .price');
      const sold=el.querySelector('.product-loop-wrapper > .wcfmmp_sold_by_container');
      const content=el.querySelector('.product-loop-content');
      const image=el.querySelector('.product-loop-image');
      const s=n=>n?getComputedStyle(n):null, r=n=>n?n.getBoundingClientRect():null;
      const tr=r(title),pr=r(price),sr=r(sold),ir=r(image);
      return {
        titlePriceGap: tr&&pr ? +(pr.top-tr.bottom).toFixed(2) : null,
        titleSoldGap: tr&&sr ? +(sr.top-tr.bottom).toFixed(2) : null,
        titleMargin:s(title)?.marginBottom,
        priceMargin:s(price)?.marginTop,
        pricePadding:s(price)?.paddingTop,
        soldMargin:s(sold)?.marginTop,
        soldPadding:s(sold)?.paddingTop,
        contentPaddingBottom:s(content)?.paddingBottom,
        imageWidth:ir?.width,
        imageHeight:ir?.height
      };
    });
    console.log('CARD_FINAL_'+width+'='+JSON.stringify(c));
    if (!(c.titlePriceGap<=8 && c.titleSoldGap<=58 && parseFloat(c.pricePadding||'99')<=1 && parseFloat(c.soldPadding||'99')<=1 && c.imageWidth>150 && c.imageHeight>180)) {
      throw new Error('Card final QA failed '+width);
    }
    await page.close();
  }

  for (const width of [1440,390]) {
    const page = await browser.newPage({viewport:{width,height:1400}});
    await page.goto('https://www.elmercadodeorigen.com/?emoqa=010246-'+Date.now(), {waitUntil:'networkidle', timeout:120000});
    const h = await page.evaluate(()=>{
      const q=s=>document.querySelector(s);
      const info=n=>{if(!n)return null;const s=getComputedStyle(n),r=n.getBoundingClientRect();return{top:r.top,bottom:r.bottom,height:r.height,font:parseFloat(s.fontSize),line:parseFloat(s.lineHeight),gap:s.gap,paddingTop:s.paddingTop,paddingBottom:s.paddingBottom}};
      const hero=info(q('.emo-hero'));
      const title=info(q('.emo-hero h1'));
      const p=info(q('.emo-hero__copy > p'));
      const proof=info(q('.emo-hero__proof'));
      const visual=info(q('.emo-hero__visual--vendors'));
      return {hero,title,p,proof,visual,visualOffset:+(visual.top-hero.top).toFixed(2),proofVisualGap:+(visual.top-proof.bottom).toFixed(2)};
    });
    console.log('HERO_FINAL_'+width+'='+JSON.stringify(h));
    if (width===1440) {
      if (!(h.title.font<=80 && h.p.line<=27 && h.hero.height<730 && h.visualOffset<185)) throw new Error('Desktop hero final QA failed');
    } else {
      if (!(h.title.font<=43 && h.p.line<=23 && h.hero.height<1120 && h.proofVisualGap<=25)) throw new Error('Mobile hero final QA failed');
    }
    await page.close();
  }
  await browser.close();
  console.log('PRODUCTION_CARD_HERO_DENSITY_010246_FINAL_OK');
})().catch(e=>{console.error(e);process.exit(1)});
