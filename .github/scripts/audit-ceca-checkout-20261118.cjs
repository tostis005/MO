// Read-only checkout rendering test; does not submit orders or enter card data.
const { chromium } = require('playwright-core');
const BASE = 'https://www.elmercadodeorigen.com';
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
(async () => {
  const exe = process.env.CHROME;
  if (!exe) throw new Error('Headless Chrome required');
  const browser = await chromium.launch({executablePath: exe,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
  const context = await browser.newContext({viewport:{width:390,height:844},locale:'es-ES'});
  const page = await context.newPage();
  try {
    const add = await page.goto(BASE+'/tienda/?add-to-cart=11139&quantity=2',{waitUntil:'domcontentloaded',timeout:90000});
    console.log('CECA_CART_ADD_HTTP='+(add?.status()||'none'));
    await page.goto(BASE+'/carrito/',{waitUntil:'domcontentloaded',timeout:90000});
    const open = page.locator('.shipping-calculator-button').first();
    if (await open.count() && await open.isVisible()) await open.click({force:true}).catch(()=>{});
    const country=page.locator('#calc_shipping_country');
    if (await country.count()) await country.selectOption('ES').catch(()=>{});
    const zip=page.locator('#calc_shipping_postcode');
    if (await zip.count()) await zip.fill('28001').catch(()=>{});
    const update=page.locator('button[name="calc_shipping"]');
    if (await update.count() && await update.isVisible()) {
      await update.click({force:true}).catch(()=>{});
      await sleep(1000);
    }
    const checked=await page.goto(BASE+'/finalizar-compra/?ceca_visual_audit=20261118',{waitUntil:'domcontentloaded',timeout:90000});
    console.log('CECA_CHECKOUT_HTTP='+(checked?.status()||'none'));
    await page.locator('#payment').waitFor({state:'visible',timeout:30000}).catch(()=>{});
    const payments=await page.locator('#payment input[name="payment_method"]').evaluateAll(xs=>xs.map(x=>({id:x.value,checked:x.checked,label:x.closest('li')?.querySelector('label')?.textContent?.trim().slice(0,160)})));
    console.log('CECA_PAYMENT_OPTIONS='+JSON.stringify(payments));
    const radio=page.locator('#payment input[name="payment_method"][value="cecabank_gateway"]');
    if (!(await radio.count())) {
      throw new Error('CECA payment option missing; body='+(await page.locator('body').innerText()).slice(0,300));
    }
    await radio.check({force:true});
    await sleep(2000);
    for(const {label,width,height} of [
      {label:'mobile',width:390,height:844},
      {label:'desktop',width:1440,height:900}
    ]) {
      await page.setViewportSize({width,height});
      await sleep(1200);
      const metric=await page.evaluate(() => {
        const pay=document.querySelector('#payment');
        const ceca=document.querySelector('#payment .payment_method_cecabank_gateway');
        const box=document.querySelector('#payment .payment_box.payment_method_cecabank_gateway');
        const label=ceca?.querySelector('label');
        const rect=(e)=>{if(!e)return null;const b=e.getBoundingClientRect();return {x:Math.round(b.x),width:Math.round(b.width),height:Math.round(b.height),right:Math.round(b.right),scrollWidth:e.scrollWidth,clientWidth:e.clientWidth}};
        const style=e=>e?{bg:getComputedStyle(e).backgroundColor,fg:getComputedStyle(e).color,font:getComputedStyle(e).fontSize}:null;
        return {
          viewport:window.innerWidth,
          pay:rect(pay),ceca:rect(ceca),box:rect(box),label:rect(label),
          colors:{pay:style(pay),ceca:style(ceca),box:style(box),label:style(label)},
          checked:document.querySelector('#payment input[name="payment_method"]:checked')?.value,
          oldStripeStylePresent:!!document.querySelector('#elmercado-stripe-checkout-visual-20261116'),
          ceCaText:(ceca?.textContent||'').trim().slice(0,350),
          ceCaInputs:[...ceca?.querySelectorAll('input,select,iframe')||[]].map(e=>({tag:e.tagName,cls:e.className,box:rect(e)})),
          documentHorizontalOverflow:document.documentElement.scrollWidth>window.innerWidth+4
        };
      });
      console.log('CECA_VISUAL_'+label.toUpperCase()+'='+JSON.stringify(metric));
      await page.screenshot({path:'/tmp/ceca-checkout-'+label+'.png',fullPage:true,timeout:60000});
      if (metric.oldStripeStylePresent) throw new Error('Temporary Stripe style still loaded');
      if (metric.checked!=='cecabank_gateway') throw new Error('CECA payment method not selected');
      if (!metric.ceca || metric.ceca.right > width+4 || metric.ceca.x< -4) throw new Error('CECA option overflows '+label);
      if (metric.box && (metric.box.right>width+4 || metric.box.x < -4)) throw new Error('CECA payment detail overflows '+label);
      if (!metric.label || metric.label.width<50 || metric.label.height<12) throw new Error('CECA label missing/too small on '+label);
    }
    console.log('CECA_CHECKOUT_VISUAL_AUDIT_OK');
  } finally {
    await context.close();
    await browser.close();
  }
})().catch(e=>{console.error('CECA_CHECKOUT_VISUAL_AUDIT_FAILED:',e.stack||e);process.exitCode=1});
