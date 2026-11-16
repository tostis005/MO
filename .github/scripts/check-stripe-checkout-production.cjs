// Visual-only regression of the live WooCommerce checkout; never submits an order.
// Uses a fresh anonymous browser session and a purchasable simple product.
const { chromium } = require('playwright-core');

const BASE = 'https://www.elmercadodeorigen.com';
const TESTS = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'desktop', width: 1440, height: 900 },
];

async function closePromotion(page) {
  await page.evaluate(() => {
    const close = document.querySelector('#bf26-overlay [data-bf26-close]');
    if (close) close.click();
  });
}

(async () => {
  const chrome = process.env.CHROME;
  if (!chrome) throw Error('Chrome executable is required');
  const browser = await chromium.launch({
    executablePath: chrome,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  try {
    const context = await browser.newContext({
      viewport: { width: 390, height: 844 },
      deviceScaleFactor: 1,
      locale: 'es-ES',
    });
    const page = await context.newPage();
    // Live storefront publicly exposes a purchasable simple product:
    // Lote barbacoa, whose Add to cart link resolves to ?add-to-cart=11139.
    // Avoid the unrelated /wp-json/wc/store/v1/products endpoint (HTTP 500).
    const candidates = [{id: 11139}]; // Two units clear Tolecarnes' EUR 60 minimum.

    let picked = null;
    const observations = [];
    for (const p of candidates.slice(0, 12)) {
      const add = await page.goto(BASE + '/tienda/?add-to-cart=' + encodeURIComponent(p.id) + '&quantity=2', {
        waitUntil: 'domcontentloaded', timeout: 90000,
      });
      const addState = {
        addHttp: add?.status(), addUrl: page.url(),
        cartText: (await page.locator('body').innerText()).includes('se ha añadido a tu carrito'),
      };
      // A fresh GitHub runner is outside Spain. Set an actual Spanish delivery
      // destination in the native WooCommerce cart calculator before checkout.
      await page.goto(BASE + '/carrito/', {
        waitUntil: 'domcontentloaded', timeout: 90000,
      });
      const shippingToggle = page.locator('.shipping-calculator-button').first();
      if (await shippingToggle.count() && await shippingToggle.isVisible()) {
        await shippingToggle.click({force:true}).catch(()=>{});
      }
      const country = page.locator('select#calc_shipping_country');
      if (await country.count()) {
        await country.selectOption('ES').catch(()=>{});
      }
      const postcode = page.locator('#calc_shipping_postcode');
      if (await postcode.count()) {
        await postcode.fill('28001').catch(()=>{});
      }
      const calc = page.locator('button[name="calc_shipping"]');
      if (await calc.count() && await calc.isVisible()) {
        await calc.click({force:true}).catch(()=>{});
        await page.waitForTimeout(1600);
      }
      console.log('STRIPE_CART_BEFORE_CHECKOUT=' + JSON.stringify({
        country: await country.count() ? await country.inputValue().catch(()=>null) : null,
        postal: await postcode.count() ? await postcode.inputValue().catch(()=>null) : null,
        summary: (await page.locator('body').innerText()).slice(0,420),
      }));
      const checkout = await page.goto(BASE + '/finalizar-compra/?stripe_visual_audit=1', {
        waitUntil: 'domcontentloaded', timeout: 90000,
      });
      await page.waitForTimeout(4000);
      await page.waitForSelector('#payment, form.checkout, .wc-block-checkout', {timeout:8000}).catch(() => {});
      const bodyExtract = (await page.locator('body').innerText()).slice(0, 1200).replace(/\\s+/g, ' ');
      const state = {
        product: p.id, ...addState, checkoutHttp: checkout?.status(),
        bodyExtract, title: await page.title(),
        checkoutUrl: page.url(),
        hasClassicPayment: await page.locator('#payment').count(),
        hasCheckoutForm: await page.locator('form.checkout').count(),
        hasBlockCheckout: await page.locator('.wc-block-checkout').count(),
        paymentMethods: await page.locator('[name="payment_method"]').count(),
        cookieNames: (await context.cookies(BASE)).map(x => x.name),
      };
      observations.push(state);
      console.log('STRIPE_CHECKOUT_SESSION=' + JSON.stringify(state));
      if (!page.url().includes('/carrito/') && state.hasClassicPayment) {
        picked = p.id;
        break;
      }
    }
    if (!picked) {
      await page.screenshot({path:'/tmp/emdo-stripe-checkout-landing.png',fullPage:true});
      throw Error('Unable to reach classic checkout: ' + JSON.stringify(observations));
    }

    for (const test of TESTS) {
      await page.setViewportSize({width: test.width, height: test.height});
      await closePromotion(page);
      const selector = '#payment .payment_box.payment_method_stripe';
      const radio = page.locator('#payment input[name="payment_method"][value="stripe"]');
      if (await radio.count()) {
        await radio.check({force: true, timeout: 10000});
      }
      const box = page.locator(selector).first();
      await box.waitFor({state: 'visible', timeout: 30000});
      await page.waitForTimeout(3500);
      const metrics = await box.evaluate(el => {
        const r = el.getBoundingClientRect();
        const css = getComputedStyle(el);
        const iframes = [...el.querySelectorAll('iframe')].map(frame => {
          const b = frame.getBoundingClientRect();
          return {width:b.width,height:b.height,left:b.left,right:b.right};
        });
        const badChildren = [...el.querySelectorAll('input,select,.wc-stripe-upe-element,.StripeElement')].filter(node => {
          const b = node.getBoundingClientRect();
          return b.width > 0 && (b.right > window.innerWidth + 4 || b.left < -4);
        }).length;
        return {
          left:r.left,right:r.right,width:r.width,height:r.height,
          background:css.backgroundColor,color:css.color,
          bodyClass:document.body.className,
          stripeClass:el.className,
          injectedStripeStyles:[...document.querySelectorAll('style[id*="stripe" i]')].map(x=>({id:x.id,len:x.textContent.length})),
          stripeRuleMatched:[...document.styleSheets].flatMap(sheet=>{
            try {
              return [...sheet.cssRules].filter(rule=>{
                if(!rule.selectorText || !rule.style?.backgroundColor) return false;
                try { return el.matches(rule.selectorText); } catch { return false; }
              }).map(rule=>({selector:rule.selectorText.slice(0,180),bg:rule.style.backgroundColor,
                important:rule.style.getPropertyPriority('background-color')}));
            } catch { return []; }
          }).slice(-15),
          iframes,badChildren, viewport:window.innerWidth,
        };
      });
      const screenshot = '/tmp/emdo-stripe-checkout-' + test.name + '.png';
      await page.screenshot({path:screenshot, fullPage:true, timeout:60000});
      console.log('STRIPE_CHECKOUT_' + test.name.toUpperCase() + '=' + JSON.stringify(metrics));
      if (metrics.width < 200 || metrics.height < 40 ||
          metrics.left < -4 || metrics.right > test.width + 4 || metrics.badChildren) {
        throw Error('Stripe form layout exceeds ' + test.name + ' viewport');
      }
      if (!/rgb\(255, (253|255), (248|255)\)/.test(metrics.background)) {
        throw Error('Stripe payment box is not using the light surface on ' + test.name);
      }
      if (!metrics.iframes.some(f => f.width > 160 && f.height > 12)) {
        throw Error('Secure Stripe payment iframe not visible on ' + test.name);
      }
    }
    console.log('STRIPE_CHECKOUT_VISUAL_AUDIT_OK product=' + picked);
    await context.close();
  } finally {
    await browser.close();
  }
})().catch(e => { console.error('STRIPE_CHECKOUT_VISUAL_AUDIT_FAILED:', e.stack || e); process.exitCode = 1; });
