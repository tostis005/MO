// Browser regression for the production Black Friday 2026 campaign.
// Measures actual viewport edges, not only HTML markup or CSS presence.
const {chromium} = require('playwright-core');

(async () => {
  const exe = process.env.CHROME;
  if (!exe) throw Error('Chrome is required to verify full-bleed production layout');
  const browser = await chromium.launch({
    executablePath: exe, headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  try {
    for (const [label, width, height] of [
      ['mobile', 390, 844],
      ['tablet', 768, 1024],
      ['desktop', 1440, 900],
    ]) {
      const page = await browser.newPage({viewport:{width, height}, deviceScaleFactor:1});
      const url='https://www.elmercadodeorigen.com/vista-previa-black-friday-2026/?bf_visual_check='
        + encodeURIComponent(process.env.GITHUB_RUN_ID || 'manual') + '_' + label;
      const response = await page.goto(url, {waitUntil:'domcontentloaded',timeout:90000});
      if (!response || response.status() !== 200) {
        throw Error('Live campaign status not 200 on '+label+': '+(response && response.status()));
      }
      await page.waitForSelector('.bf26-club__wallet', {timeout:30000});
      await page.waitForTimeout(650);
      await page.evaluate(() => {
        const close = document.querySelector('#bf26-overlay [data-bf26-close]');
        if (close) close.click();
      });
      const measurement=await page.evaluate(() => {
        const sel=(s)=>document.querySelector(s);
        const box=(e)=>{ if (!e) return null; const r=e.getBoundingClientRect();
          return {left:r.left, right:r.right,top:r.top,bottom:r.bottom,width:r.width,height:r.height}; };
        const parts={};
        for(const s of ['.bf26','.bf26-hero','.bf26-band','.bf26-offers','.bf26-club']) {
          parts[s]=box(sel(s));
        }
        const footer=sel('.site-footer, #colophon, footer.site-footer');
        const header=sel('.site-header');
        const cart=sel('.site-tools .shopping-cart, .site-tools .cart-contents, .site-tools .shopping-bag-button, .site-tools [class*="cart"]');
        const promo=sel('.bf26');
        const hero=sel('.bf26-hero');
        const headline=sel('.bf26-hero h1');
        const discountDetails=Array.from(document.querySelectorAll('.bf26-offer__discount-details'));
        const couponRows=Array.from(document.querySelectorAll('.bf26-offer__coupon-row'));
        const couponButtons=Array.from(document.querySelectorAll('[data-bf26-copy]'));
        const percentGlyphs=Array.from(document.querySelectorAll(
          '.bf26-hero__seal strong span, .bf26-overlay__discount strong span, '+
          '.bf26-offer__discount > strong span, .bf26-club__wallet-main strong span'
        )).map((el)=>({
          symbol:el.textContent.trim(),
          marginLeft:parseFloat(getComputedStyle(el).marginLeft),
          fontRatio:parseFloat(getComputedStyle(el).fontSize)/
            parseFloat(getComputedStyle(el.parentElement).fontSize)
        }));
        return {
          couponPresent:!!promo && promo.textContent.includes('BFEMDO'),
          legacyCouponPresent:!!promo && promo.textContent.includes('BFM2'),
          allCopyButtonsUpdated:couponButtons.length===2 && couponButtons.every(el=>el.getAttribute('data-bf26-copy')==='BFEMDO'),
          integratedCoupons:discountDetails.length===2 && couponRows.length===2
            && couponRows.every(el=>!!el.querySelector('.bf26-coupon') && el.textContent.includes('BFEMDO')),
          noOldCouponSentence:!!promo && !promo.textContent.includes('Introduce el código'),
          heroTitleTopGap:hero && headline ? box(headline).top-box(hero).top : null,
          removedInstructionSection:!sel('.bf26-coupon-section, #como-funciona'),
          removedHeroEyebrow:!sel('.bf26-hero__copy .bf26-eyebrow'),
          removedHowItWorksLink:!sel('a[href="#como-funciona"]'),
          percentGlyphs,
          width:window.innerWidth,scrollWidth:document.documentElement.scrollWidth,
          header:!!header,cart:!!cart,footer:box(footer),
          wallet:!!sel('.bf26-club__wallet'),
          headerVisible:header ? getComputedStyle(header).display!=='none' : false,
          parts
        };
      });
      console.log('BF2026_GEOMETRY_'+label.toUpperCase()+'='+JSON.stringify(measurement));
      if (!measurement.couponPresent || measurement.legacyCouponPresent || !measurement.allCopyButtonsUpdated
          || !measurement.integratedCoupons || !measurement.noOldCouponSentence
          || measurement.heroTitleTopGap===null || measurement.heroTitleTopGap<0 || measurement.heroTitleTopGap>105
          || !measurement.removedInstructionSection || !measurement.removedHeroEyebrow
          || !measurement.removedHowItWorksLink) {
        throw Error('Incorrect coupon or textual guidance at '+label+': '+JSON.stringify(measurement));
      }
      if (measurement.percentGlyphs.length !== 5 ||
          measurement.percentGlyphs.some(g=>g.symbol !== '%' || g.marginLeft < 2 || g.fontRatio < .4 || g.fontRatio > .55)) {
        throw Error('Poor percent symbol spacing at '+label+': '+JSON.stringify(measurement.percentGlyphs));
      }
      if (!measurement.header || !measurement.headerVisible || !measurement.cart || !measurement.footer || !measurement.wallet) {
        throw Error('Missing native shop shell/header/cart/footer at '+label);
      }
      for (const [selector,r] of Object.entries(measurement.parts)) {
        if (!r || Math.abs(r.left)>3 || Math.abs(r.right-measurement.width)>3) {
          throw Error('Section is not full-bleed at '+label+' '+selector+' '+JSON.stringify(r));
        }
      }
      if (measurement.scrollWidth > measurement.width+3) {
        throw Error('Unwanted horizontal page overflow at '+label+': '+measurement.scrollWidth);
      }
      const gap=measurement.footer.top-measurement.parts['.bf26-club'].bottom;
      if (Math.abs(gap)>5) {
        throw Error('Gap before native shop footer at '+label+': '+gap+'px');
      }
      await page.screenshot({path:'/tmp/black-friday-2026-'+label+'.png',fullPage:true});
      await page.close();
    }
  } finally {
    await browser.close();
  }
  console.log('BF2026_VIEWPORT_EDGES_AND_NATIVE_FOOTER_OK');
})().catch(e=>{console.error(e.stack||e);process.exit(1);});
