const fs = require('fs');
const puppeteer = require('puppeteer-core');
const base = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const report = { errors: [], warnings: [], checks: {}, runtime: {} };
const ignoredFailure = (u) => /google-analytics|googletagmanager|doubleclick|notification\.mp3|facebook|clarity/i.test(u);

async function isVisible(el) {
  return el.evaluate((e) => {
    const r = e.getBoundingClientRect();
    const s = getComputedStyle(e);
    return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
  });
}
async function visibleHandle(page, selector) {
  for (const el of await page.$$(selector)) if (await isVisible(el)) return el;
  return null;
}
async function clickVisible(page, selector) {
  const el = await visibleHandle(page, selector);
  if (!el) return false;
  await el.click();
  return true;
}
async function go(page, path, label) {
  const u = path.startsWith('http') ? path : base + path;
  const url = u + (u.includes('?') ? '&' : '?') + 'qa=' + Date.now();
  const response = await page.goto(url, { waitUntil: 'networkidle2', timeout: 90000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
  await sleep(450);
  if (!response || response.status() >= 400) report.errors.push(`${label}: HTTP ${response?.status() || 'none'}`);
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 2);
  if (overflow) report.errors.push(`${label}: horizontal overflow`);
  return response;
}
async function cartCount(page) {
  return page.evaluate(() => {
    for (const n of document.querySelectorAll('.site-header .shop-cart-count,.site-header .cart-count,.site-header .count,.site-header .elmercado-cart-direct-count')) {
      const r = n.getBoundingClientRect();
      const s = getComputedStyle(n);
      if (r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0) {
        const m = (n.textContent || '').match(/\d+/);
        return m ? Number(m[0]) : 0;
      }
    }
    return 0;
  });
}
function hookRuntime(page, key) {
  const consoleErrors = [], failed = [];
  page.on('console', (m) => { if (m.type() === 'error') consoleErrors.push(m.text()); });
  page.on('requestfailed', (r) => { if (!ignoredFailure(r.url())) failed.push(`${r.url()} :: ${r.failure()?.errorText || 'failed'}`); });
  report.runtime[key] = { consoleErrors, failed };
}
const screenshot = (page, name) => page.screenshot({ path: `qa/${name}.png`, fullPage: true });
async function clickMayNavigate(page, el) {
  const nav = page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 7000 }).catch(() => null);
  await el.click();
  await nav;
  await sleep(650);
}
async function runSafe(label, fn) {
  try { await fn(); } catch (e) { report.errors.push(`${label}: ${e.message}`); }
}

async function auditMode(browser, mode, viewport) {
  const ctx = await browser.createBrowserContext();
  const page = await ctx.newPage();
  hookRuntime(page, mode);
  await page.setViewport(viewport);

  await runSafe(`home ${mode}`, async () => {
    await go(page, '/', `home ${mode}`);
    await screenshot(page, `home-${mode}`);
    if ((await cartCount(page)) !== 0) report.errors.push(`home ${mode}: zero cart badge visible`);
    const geometry = await page.evaluate(() => {
      const h = document.querySelector('.site-header');
      const t = document.querySelector('.site-tools');
      if (!h || !t) return null;
      const a = h.getBoundingClientRect(), b = t.getBoundingClientRect();
      return { delta: Math.abs((a.y + a.height / 2) - (b.y + b.height / 2)) };
    });
    if (mode === 'mobile' && geometry && geometry.delta > 6) report.errors.push(`home mobile: utility icons off-center by ${Math.round(geometry.delta)}px`);
    const carousel = await page.$('.emo-featured-products ul.products');
    if (mode === 'mobile' && carousel) {
      if (!(await visibleHandle(page, '.emo-featured-products .emo-carousel-control--previous')) || !(await visibleHandle(page, '.emo-featured-products .emo-carousel-control--next'))) report.errors.push('home mobile: carousel arrows missing');
    }
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    const focused = await page.evaluate(() => document.activeElement && document.activeElement !== document.body && ['A','BUTTON','INPUT','SPAN','DIV'].includes(document.activeElement.tagName));
    if (!focused) report.warnings.push(`home ${mode}: keyboard focus not detected in header`);
  });

  if (mode === 'mobile') await runSafe('mobile menu', async () => {
    const toggleSel = '.site-header .toggle-sidebar-menu-btn';
    if (!(await clickVisible(page, toggleSel))) { report.errors.push('mobile menu: toggle missing'); return; }
    await sleep(350);
    const open = await page.evaluate(() => {
      const e = document.querySelector('.sidebar-menu');
      if (!e) return false;
      const r = e.getBoundingClientRect();
      return r.width > 0 && r.height > 0 && e.getAttribute('aria-hidden') !== 'true';
    });
    if (!open) report.errors.push('mobile menu: did not open');
    const closeSel = '.sidebar-menu .close-sidebar-menu-btn,.sidebar-menu .close-sidebar-menu,.sidebar-menu [class*="close-sidebar"]';
    if (!(await clickVisible(page, closeSel))) await clickVisible(page, toggleSel);
    await sleep(350);
    const stillOpen = await page.evaluate(() => {
      const e = document.querySelector('.sidebar-menu');
      if (!e) return false;
      const r = e.getBoundingClientRect();
      return r.width > 0 && r.height > 0 && !e.hasAttribute('inert') && e.getAttribute('aria-hidden') !== 'true';
    });
    if (stillOpen) report.errors.push('mobile menu: did not close');
  });

  await runSafe(`search ${mode}`, async () => {
    await go(page, '/', `search home ${mode}`);
    const toggle = '.site-header .header-search-icon,.site-header .search-icon,.site-header .site-search-toggle,.site-header .js-dgwt-wcas-search-icon-handler';
    if (!(await clickVisible(page, toggle))) { report.errors.push(`search ${mode}: toggle missing`); return; }
    await sleep(650);
    const input = await visibleHandle(page, 'input[type="search"]');
    if (!input) report.errors.push(`search ${mode}: visible search input missing`);
    else { await input.click(); await input.type('aceite'); report.checks[`searchOpen-${mode}`] = true; }
    await go(page, '/?s=aceite&post_type=product', `search results ${mode}`);
    if (!(await page.evaluate(() => document.querySelectorAll('li.product').length > 0))) report.errors.push(`search ${mode}: known query has no products`);
    await go(page, '/?s=zzzz-no-existe-qa-987654&post_type=product', `search no-results ${mode}`);
    if (await page.evaluate(() => document.querySelectorAll('li.product').length > 0)) report.errors.push(`search ${mode}: impossible query returned products`);
  });

  let firstProduct = null;
  await runSafe(`shop ${mode}`, async () => {
    await go(page, '/tienda/', `shop ${mode}`);
    await screenshot(page, `shop-${mode}`);
    const producerVisible = await page.evaluate(() => [...document.querySelectorAll('select')].some((s) => {
      if (![...s.options].some((o) => /(todos los productores|todos los vendedores)/i.test(o.textContent || ''))) return false;
      const r = s.getBoundingClientRect(), cs = getComputedStyle(s);
      return r.width > 0 && r.height > 0 && cs.display !== 'none' && cs.visibility !== 'hidden';
    }));
    if (producerVisible) report.errors.push(`shop ${mode}: producer filter visible`);
    const borders = await page.evaluate(() => {
      const e = document.querySelector('.woocommerce-ordering select'); if (!e) return null;
      const s = getComputedStyle(e); return [s.borderTopWidth, s.borderBottomWidth];
    });
    if (borders && borders.includes('0px')) report.errors.push(`shop ${mode}: ordering border incomplete`);
    const result = await page.$eval('.woocommerce-result-count', (e) => (e.textContent || '').replace(/\s+/g, ' ').trim()).catch(() => null);
    if (result && /\d(de)\d/i.test(result)) report.errors.push(`shop ${mode}: result count spacing malformed`);
    const titleHeights = await page.$$eval('ul.products li.product .woocommerce-loop-product__title,ul.products li.product .elmercado-product-title-link', (els) => els.slice(0,8).map((e) => [e.getBoundingClientRect().height, parseFloat(getComputedStyle(e).lineHeight)]));
    if (titleHeights.some(([h, lh]) => h + 1 < lh * 1.9)) report.errors.push(`shop ${mode}: product title does not reserve two lines`);
    if (!(await page.$('.widget_price_filter .price_slider,.price_slider_wrapper .ui-slider-horizontal'))) report.warnings.push(`shop ${mode}: price slider not detected`);
    const category = await page.$('.widget_product_categories a,.product-categories a');
    if (category) { const href = await category.evaluate((a) => a.href); const res = await go(page, href, `category ${mode}`); if (res?.status() >= 400) report.errors.push(`category ${mode}: failed`); }
    await go(page, '/tienda/', `shop restore ${mode}`);
    const pagination = await page.$('.woocommerce-pagination a.page-numbers');
    if (pagination) { const href = await pagination.evaluate((a) => a.href); await go(page, href, `pagination ${mode}`); }
    await go(page, '/tienda/', `shop product discovery ${mode}`);
    firstProduct = await page.$eval('li.product a.woocommerce-loop-product__link', (a) => a.href).catch(() => null);
    if (!firstProduct) report.errors.push(`shop ${mode}: product link missing`);
    const out = await page.$('li.product.outofstock a.woocommerce-loop-product__link');
    if (out) { const href = await out.evaluate((a) => a.href); await go(page, href, `out-of-stock product ${mode}`); if (!(await page.evaluate(() => /agotado|out of stock/i.test(document.body.innerText)))) report.warnings.push(`out-of-stock ${mode}: status text not detected`); }
  });

  await runSafe(`product/cart ${mode}`, async () => {
    if (!firstProduct) return;
    await go(page, firstProduct, `product ${mode}`);
    await screenshot(page, `product-${mode}`);
    for (const select of await page.$$('form.variations_form select')) {
      const v = await select.evaluate((e) => [...e.options].find((o) => o.value && !o.disabled)?.value || '');
      if (v) await select.select(v);
    }
    await sleep(450);
    const add = await visibleHandle(page, '.single_add_to_cart_button:not(.disabled),form.cart button[type="submit"]');
    if (add) {
      const before = await cartCount(page);
      await clickMayNavigate(page, add);
      const after = await cartCount(page).catch(() => 0);
      report.checks[`cartCountAfterAdd-${mode}`] = { before, after };
      if (after < 1) {
        await go(page, '/carrito/', `cart add verification ${mode}`);
        if (!(await page.$('input.qty'))) report.errors.push(`cart ${mode}: product was not added`);
      } else {
        await clickVisible(page, '.site-header .shopping-cart,.site-header .shopping-bag-button,.site-header a.cart-contents');
        await sleep(350);
        if (!(await page.evaluate(() => !!document.querySelector('.mini_cart_item,.woocommerce-mini-cart-item')))) report.warnings.push(`minicart ${mode}: added item not detected`);
      }
    } else if (!(await page.evaluate(() => /agotado|out of stock/i.test(document.body.innerText)))) report.warnings.push(`product ${mode}: add-to-cart control unavailable`);

    await go(page, '/carrito/', `cart ${mode}`);
    await screenshot(page, `cart-${mode}`);
    const qty = await page.$('input.qty');
    if (!qty) { report.warnings.push(`cart ${mode}: remained empty; non-empty cart tests skipped`); return; }
    await qty.click({ clickCount: 3 }); await qty.type('2');
    const update = await visibleHandle(page, 'button[name="update_cart"],input[name="update_cart"]');
    if (update) await clickMayNavigate(page, update);
    const coupon = await visibleHandle(page, 'input#coupon_code,input[name="coupon_code"]');
    if (coupon) {
      await coupon.type('QA-CUPON-INVALIDO-987654');
      const apply = await visibleHandle(page, 'button[name="apply_coupon"],input[name="apply_coupon"]');
      if (apply) { await clickMayNavigate(page, apply); report.checks[`invalidCoupon-${mode}`] = await page.evaluate(() => !!document.querySelector('.woocommerce-error,.woocommerce-notices-wrapper,.woocommerce-message')); }
    }
    await go(page, '/finalizar-compra/', `checkout ${mode}`);
    await screenshot(page, `checkout-${mode}`);
    if (!(await page.evaluate(() => !!document.querySelector('form.checkout,#customer_details,.woocommerce-checkout')))) report.errors.push(`checkout ${mode}: checkout form missing`);
  });

  await runSafe(`contact ${mode}`, async () => {
    await go(page, '/contacto/', `contact ${mode}`); await screenshot(page, `contact-${mode}`);
    const form = await page.$('form.wpcf7-form,.elementor-form,form[id*="contact"]');
    if (!form) { report.warnings.push(`contact ${mode}: form not found`); return; }
    const submit = await form.$('button[type="submit"],input[type="submit"]');
    if (submit) { await submit.click(); await sleep(600); report.checks[`contactInvalid-${mode}`] = await page.evaluate(() => !!document.querySelector('[aria-invalid="true"],.wpcf7-not-valid-tip,.elementor-message-danger,.error')); }
  });

  await runSafe(`content ${mode}`, async () => {
    await go(page, '/blog/', `blog ${mode}`);
    const article = await page.$eval('article a[href],h2 a[href]', (a) => a.href).catch(() => null);
    if (article) await go(page, article, `blog article ${mode}`); else report.warnings.push(`blog ${mode}: article link not found`);
    await go(page, '/', `footer ${mode}`);
    const legal = await page.evaluate(() => [...document.querySelectorAll('footer a[href],.site-footer a[href]')].filter((a) => /privacidad|cookies|aviso legal|t[eé]rminos|condiciones/i.test((a.textContent || '') + ' ' + a.href)).map((a) => a.href).filter((v,i,a) => a.indexOf(v) === i).slice(0,6));
    for (let i=0; i<legal.length; i++) await go(page, legal[i], `legal ${mode} ${i+1}`);
    if (!legal.length) report.warnings.push(`footer ${mode}: legal links not found`);
  });

  await runSafe(`vendor ${mode}`, async () => {
    await go(page, '/tienda/hidalgo-de-la-jara/', `vendor ${mode}`); await screenshot(page, `vendor-${mode}`);
    const gap = await page.evaluate(() => {
      const t = document.querySelector('.wcfmmp-store-tabs,.wcfm_store_tabs,.store-tabs,.wcfmmp-store-tab');
      if (!t || !t.nextElementSibling) return null;
      return t.nextElementSibling.getBoundingClientRect().top - t.getBoundingClientRect().bottom;
    });
    if (gap !== null && gap < 16) report.errors.push(`vendor ${mode}: tabs/content spacing ${Math.round(gap)}px`);
  });

  await ctx.close();
}

(async () => {
  const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', protocolTimeout: 120000, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  try {
    const clean = await browser.createBrowserContext();
    const p = await clean.newPage(); hookRuntime(p, 'clean'); await p.setViewport({width:1440,height:1000});
    await go(p, '/carrito/', 'empty cart');
    if (!(await p.evaluate(() => /carrito.*vac[ií]o|tu carrito est[aá] vac[ií]o|volver a la tienda|return to shop/i.test(document.body.innerText)))) report.errors.push('empty cart state missing');
    await go(p, '/mi-cuenta/', 'account logged out');
    if (!(await p.evaluate(() => !!document.querySelector('form.woocommerce-form-login,input[name="username"],input[name="password"]')))) report.warnings.push('account logged-out form not detected');
    await clean.close();
    await auditMode(browser, 'desktop', {width:1440,height:1000});
    await auditMode(browser, 'mobile', {width:390,height:844,isMobile:true,hasTouch:true});
    for (const [k,v] of Object.entries(report.runtime)) {
      if ([...new Set(v.consoleErrors)].length) report.warnings.push(`${k}: console error(s) detected`);
      if ([...new Set(v.failed)].length) report.warnings.push(`${k}: request failure(s) detected`);
    }
  } finally {
    fs.writeFileSync('qa/report.json', JSON.stringify(report, null, 2));
    await browser.close();
  }
  if (report.errors.length) { console.error(report.errors.join('\n')); process.exit(2); }
})().catch((e) => { report.errors.push(`fatal: ${e.message}`); fs.mkdirSync('qa',{recursive:true}); fs.writeFileSync('qa/report.json',JSON.stringify(report,null,2)); console.error(e); process.exit(1); });
