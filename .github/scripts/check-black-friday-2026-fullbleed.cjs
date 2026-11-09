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
        for(const s of ['.bf26','.bf26-hero','.bf26-band','.bf26-offers','.bf26-coupon-section','.bf26-club']) {
          parts[s]=box(sel(s));
        }
        const footer=sel('.site-footer, #colophon, footer.site-footer');
        const header=sel('.site-header');
        const cart=sel('.site-tools .shopping-cart, .site-tools .cart-contents, .site-tools .shopping-bag-button, .site-tools [class*="cart"]');
        return {
          width:window.innerWidth,scrollWidth:document.documentElement.scrollWidth,
          header:!!header,cart:!!cart,footer:box(footer),
          wallet:!!sel('.bf26-club__wallet'),
          headerVisible:header ? getComputedStyle(header).display!=='none' : false,
          parts
        };
      });
      console.log('BF2026_GEOMETRY_'+label.toUpperCase()+'='+JSON.stringify(measurement));
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
