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
      ['mobile-small', 320, 720],
      ['mobile-medium', 360, 780],
      ['mobile', 390, 844],
      ['tablet', 768, 1024],
      ['desktop', 1440, 900],
    ]) {
      const page = await browser.newPage({viewport:{width, height}, deviceScaleFactor:1});
      const url='https://www.elmercadodeorigen.com/black-friday-2026/?bf_visual_check='
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
        const sellerCards=Array.from(document.querySelectorAll('.bf26-offer'));
        const enabledSellers=(promo?.getAttribute('data-bf26-visible-sellers')||'').split(',').filter(Boolean);
        const actualSellers=sellerCards.map(el=>el.getAttribute('data-bf26-seller'));
        const sellerLabels=sellerCards.map(el=>el.querySelector('.bf26-offer__number')?.textContent.trim());
        const hidalgoCoupon=sel('.bf26-offer--hidalgo [data-bf26-copy]');
        const montjamCoupon=sel('.bf26-offer--montjam [data-bf26-copy]');
        const intro=Array.from(document.querySelectorAll(
          '.bf26-hero__copy, .bf26-band__inner, .bf26-heading, #bf26-overlay-desc, .bf26-overlay__discount'
        )).map(el=>el.textContent).join(' ');
        const campaignText=promo?.textContent || '';
        const clubText=sel('.bf26-club__copy')?.textContent || '';
        const clubWalletText=sel('.bf26-club__wallet')?.textContent || '';
        const clubFooterText=sel('.bf26-club__footer')?.textContent || '';
        const overlayText=sel('#bf26-overlay-desc')?.textContent || '';
        const campaignStamps=Array.from(document.querySelectorAll(
          '.bf26-hero__seal.bf26-campaign-stamp, .bf26-overlay__discount.bf26-campaign-stamp'
        ));
        const campaignStampTypography=campaignStamps.map(el=>({
          heading:el.querySelector('strong')?.textContent.trim(),
          edition:el.querySelector('small')?.textContent.trim(),
          benefit:el.querySelector(':scope > span')?.textContent.trim(),
          centerFontSize:parseFloat(getComputedStyle(el.querySelector('strong')).fontSize),
          labelFontSize:parseFloat(getComputedStyle(el.querySelector(':scope > span')).fontSize),
          circleWidth:parseFloat(getComputedStyle(el).width),
          ring:getComputedStyle(el,'::before').borderTopWidth
        }));
        const hidalgo=sel('.bf26-offer--hidalgo');
        const hidalgoText=hidalgo?.textContent || '';
        const hidalgoPremium=sel('.bf26-hidalgo-rewards__tier--premium')?.textContent || '';
        const hidalgoGiftTiers=Array.from(document.querySelectorAll('.bf26-hidalgo-rewards__tier'));
        const montjam=sel('.bf26-offer--montjam');
        const montjamText=montjam?.textContent || '';
        const cashbackLines=Array.from(document.querySelectorAll('.bf26-offer__cashback'));
        const selectedTermsLinks=Array.from(document.querySelectorAll('a[href="#hidalgo-condiciones"],a[href="#montjam-letra-pequena"]'));
        const offerSelectors=[
          '.bf26-offer', '.bf26-offer__content', '.bf26-offer__description',
          '.bf26-offer__discount', '.bf26-offer__discount > strong',
          '.bf26-offer__discount-details', '.bf26-offer__coupon-row',
          '.bf26-offer__coupon-row .bf26-coupon', '.bf26-gift',
          '.bf26-gift__text', '.bf26-gift__text > strong',
          '.bf26-offer__content > a.bf26-button',
          '.bf26-hidalgo-benefits','.bf26-hidalgo-upgrade',
          '.bf26-hidalgo-upgrade__emblem','.bf26-hidalgo-upgrade__copy',
          '.bf26-hidalgo-upgrade__copy h4','.bf26-hidalgo-rewards',
          '.bf26-hidalgo-rewards__grid','.bf26-hidalgo-rewards__tier',
          '.bf26-hidalgo-rewards__note',
          '.bf26-offer__cashback','.bf26-montjam-extras',
          '.bf26-montjam-extra','.bf26-montjam-extra__badge',
          '.bf26-montjam-extra__copy','.bf26-montjam-extra__copy h4',
          '.bf26-montjam-extras__conditions',
          '.bf26-montjam-gift','.bf26-montjam-gift__emblem'
        ];
        const offerRectangles=offerSelectors.flatMap(selector=>
          Array.from(document.querySelectorAll(selector)).map(el=>{
            const r=el.getBoundingClientRect();
            return {selector,left:r.left,right:r.right,width:r.width,
              scrollWidth:el.scrollWidth,clientWidth:el.clientWidth};
          })
        );
        const percentGlyphs=Array.from(document.querySelectorAll(
          '.bf26-hero__seal strong span, .bf26-overlay__discount strong span, '+
          '.bf26-offer__discount > strong span, .bf26-club__wallet-main strong span, '+
          '.bf26-montjam-extra__badge strong span'
        )).map((el)=>({
          symbol:el.textContent.trim(),
          marginLeft:parseFloat(getComputedStyle(el).marginLeft),
          fontRatio:parseFloat(getComputedStyle(el).fontSize)/
            parseFloat(getComputedStyle(el.parentElement).fontSize)
        }));
        return {
          couponPresent:enabledSellers.length>0 && 
            (!enabledSellers.includes('hidalgo') || !!hidalgoCoupon) &&
            (!enabledSellers.includes('montjam') || !!montjamCoupon),
          sellerVisibilityConsistent:enabledSellers.length>0 &&
            actualSellers.length===enabledSellers.length &&
            actualSellers.every((seller,i)=>seller===enabledSellers[i]) &&
            sellerLabels.every((label,i)=>label===String(i+1).padStart(2,'0')) &&
            (!enabledSellers.includes('hidalgo') || hidalgo) &&
            (!enabledSellers.includes('montjam') || montjam) &&
            (!enabledSellers.includes('hidalgo') ? !hidalgo && !sel('#hidalgo-condiciones') : !!hidalgo) &&
            (!enabledSellers.includes('montjam') ? !montjam && !sel('#montjam-condiciones') : !!montjam),
          generalCampaignIntro:!/(dos\s+(productores|cupones)|Hidalgo|Montjam|BFEMDO|BFMONTJAM|15\s*%)/i.test(intro) &&
            intro.includes('BLACK FRIDAY') &&
            intro.includes('DESCUENTOS Y REGALOS ESPECIALES') &&
            !!sel('.bf26-hero__seal strong') &&
            sel('.bf26-hero__seal strong').textContent.trim()==='BLACKFRIDAY' &&
            sel('.bf26-overlay__discount strong')?.textContent.trim()==='BLACKFRIDAY',
          clubCashbackWithoutQualifier:!/(pedidos válidos|compras válidas)/i.test(campaignText) &&
            clubText.includes('1 %') && clubText.includes('2 %') &&
            clubText.includes('en todas tus compras') && clubText.includes('monedero'),
          campaignStampDesign:campaignStamps.length===2 &&
            campaignStampTypography.every(st=>
              st.heading==='BLACKFRIDAY' &&
              st.edition==='EDICIÓN 2026' &&
              st.benefit.includes('OFERTAS') && st.benefit.includes('REGALOS') &&
              st.centerFontSize>=18 && st.labelFontSize>=6 && st.circleWidth>=100 &&
              st.ring!=='0px'),
          clubLaunchBlackFridayBoost:
            clubText.includes('Lanzamos el') &&
            clubText.includes('recargar tu monedero') &&
            clubWalletText.includes('2%') &&
            clubWalletText.includes('1 % DESPUÉS DEL BLACK FRIDAY') &&
            clubFooterText.includes('un 2 % de cashback') &&
            clubFooterText.includes('acumularán el 1 %') &&
            clubFooterText.includes('todavía no se ha anunciado oficialmente') &&
            overlayText.includes('2 % de cashback') &&
            overlayText.includes('Club EMDO') &&
            !overlayText.includes('1 % en tu monedero'),
          distinctSellerCoupons:couponButtons.length===enabledSellers.length &&
            (!enabledSellers.includes('hidalgo') || hidalgoCoupon?.getAttribute('data-bf26-copy')==='BFEMDO') &&
            (!enabledSellers.includes('montjam') || montjamCoupon?.getAttribute('data-bf26-copy')==='BFMONTJAM') &&
            (!hidalgoCoupon || !montjamCoupon || hidalgoCoupon!==montjamCoupon),
          montjamTerms:!enabledSellers.includes('montjam') || (!!montjam &&
            montjamText.includes('15 % de descuento en todos los productos Montjam') &&
            montjamText.includes('EN TODOS LOS PRODUCTOS MONTJAM') &&
            montjamText.includes('2,5 % adicional') &&
            montjamText.includes('automáticamente en el carrito') &&
            montjamText.includes('7 kg o más') &&
            montjamText.includes('igual o superior a 7 kg') &&
            !montjamText.includes('incluye los de 7 kg exactos') &&
            montjamText.includes('menos de 7 kg') &&
            !montjamText.includes('7 kg o menos') &&
            montjamText.includes('bellota 100 % ibérico') &&
            montjamText.includes('fuet de regalo') &&
            !montjamText.includes('sobre de chorizo') &&
            !montjamText.includes('sobre de salchichón')),
          selectedConditionsAccessible:
            selectedTermsLinks.length===enabledSellers.length &&
            (!enabledSellers.includes('hidalgo') || !!sel('#hidalgo-condiciones')) &&
            (!enabledSellers.includes('montjam') || !!sel('#montjam-letra-pequena')) &&
            selectedTermsLinks.every(el=>!!document.querySelector(el.getAttribute('href'))),
          bothCashbacks:cashbackLines.length===enabledSellers.length &&
            sellerCards.every(el=>!!el.querySelector('.bf26-offer__cashback')) &&
            cashbackLines.every(el=>el.textContent.includes('2 % de cashback') &&
              el.textContent.includes('Después de la campaña, seguirás acumulando el 1 %') && el.textContent.includes('monedero') &&
              !!el.querySelector('a[href="#club-emdo"]')),
          finalHidalgoTerms:!enabledSellers.includes('hidalgo') || (!!hidalgo &&
            hidalgoText.includes('15 % de descuento en todos los productos Hidalgo') &&
            hidalgoText.includes('7,5–8 kg') &&
            hidalgoText.includes('8–8,5 kg') &&
            hidalgoText.includes('brida negra') &&
            hidalgoText.includes('sin DOP') &&
            hidalgoText.includes('150 €') &&
            hidalgoText.includes('250 €') &&
            hidalgoText.includes('sobre de salchichón') &&
            hidalgoText.includes('miniqueso Pajarete de 90 g') &&
            hidalgoPremium.includes('sobre de salchichón') &&
            hidalgoPremium.includes('miniqueso Pajarete de 90 g') &&
            hidalgoGiftTiers.length===2),
          noOldHidalgoGift:!hidalgoText.includes('100 g') &&
            !hidalgoText.includes('Con la compra de cualquier jamón o paleta de Hidalgo'),
          legacyCouponPresent:!!promo && /BFM2|BFEMDOM/.test(promo.textContent),
          allCopyButtonsUpdated:couponButtons.length===enabledSellers.length &&
            couponButtons.every(el=>/^[A-Z0-9]{4,16}$/.test(el.getAttribute('data-bf26-copy')||'')),
          integratedCoupons:discountDetails.length===enabledSellers.length &&
            couponRows.length===enabledSellers.length &&
            couponRows.length===couponButtons.length &&
            couponRows.every(el=>!!el.querySelector('.bf26-coupon')),
          noOldCouponSentence:!!promo && !promo.textContent.includes('Introduce el código'),
          heroTitleTopGap:hero && headline ? box(headline).top-box(hero).top : null,
          removedInstructionSection:!sel('.bf26-coupon-section, #como-funciona'),
          removedHeroEyebrow:!sel('.bf26-hero__copy .bf26-eyebrow'),
          removedHowItWorksLink:!sel('a[href="#como-funciona"]'),
          percentGlyphs,
          campaignStampTypography,
          offerRectangles,
          width:window.innerWidth,scrollWidth:document.documentElement.scrollWidth,
          header:!!header,cart:!!cart,footer:box(footer),
          wallet:!!sel('.bf26-club__wallet'),
          headerVisible:header ? getComputedStyle(header).display!=='none' : false,
          parts
        };
      });
      console.log('BF2026_GEOMETRY_'+label.toUpperCase()+'='+JSON.stringify(measurement));
      if (!measurement.campaignStampDesign) {
        throw Error('Black Friday campaign seals are not readable or consistent at '+label+': '+
          JSON.stringify(measurement.campaignStampTypography));
      }
      if (!measurement.generalCampaignIntro || !measurement.clubCashbackWithoutQualifier ||
          !measurement.clubLaunchBlackFridayBoost) {
        throw Error('Campaign intro must stay seller-agnostic and Club EMDO terms must be uncluttered: '+
          label+': '+JSON.stringify(measurement));
      }
      if (!measurement.montjamTerms || !measurement.distinctSellerCoupons ||
          !measurement.sellerVisibilityConsistent ||
          !measurement.selectedConditionsAccessible || !measurement.bothCashbacks) {
        throw Error('Montjam/Club EMDO coupon or conditional benefit mismatch at '+label+': '+JSON.stringify(measurement));
      }
      if (!measurement.finalHidalgoTerms || !measurement.noOldHidalgoGift) {
        throw Error('Missing definitive Hidalgo BF benefits at '+label+': '+JSON.stringify(measurement));
      }
      if (!measurement.couponPresent || measurement.legacyCouponPresent || !measurement.allCopyButtonsUpdated
          || !measurement.integratedCoupons || !measurement.noOldCouponSentence
          || measurement.heroTitleTopGap===null || measurement.heroTitleTopGap<0 || measurement.heroTitleTopGap>105
          || !measurement.removedInstructionSection || !measurement.removedHeroEyebrow
          || !measurement.removedHowItWorksLink) {
        throw Error('Incorrect coupon or textual guidance at '+label+': '+JSON.stringify(measurement));
      }
      if (measurement.percentGlyphs.length < 2 ||
          measurement.percentGlyphs.some(g=>g.symbol !== '%' || g.marginLeft < 2 || g.fontRatio < .4 || g.fontRatio > .55)) {
        throw Error('Poor percent symbol spacing at '+label+': '+JSON.stringify(measurement.percentGlyphs));
      }
      if (measurement.offerRectangles.length < 12) {
        throw Error('Product cards not fully rendered at '+label);
      }
      for (const el of measurement.offerRectangles) {
        if (el.left < -3 || el.right > measurement.width+3 ||
            el.clientWidth + 3 < el.scrollWidth) {
          throw Error('Clipped or overflowing offer element at '+label+': '+JSON.stringify(el));
        }
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
