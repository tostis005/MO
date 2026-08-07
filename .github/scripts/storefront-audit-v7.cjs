const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const report = { errors: [], warnings: [], checks: {}, runtime: {} };

function ignorableFailure(req) {
  const url = req.url();
  const err = req.failure()?.errorText || '';
  if (/google-analytics|googletagmanager|doubleclick|clarity|facebook|notification\.mp3|fonts\.googleapis|fonts\.gstatic/i.test(url)) return true;
  if (/net::ERR_ABORTED/i.test(err) && /wp-admin\/admin-ajax\.php|wc-ajax=get_refreshed_fragments/i.test(url)) return true;
  return false;
}

function attachRuntime(page, key) {
  const consoleErrors = [], failed = [];
  page.on('console', msg => {
    if (msg.type() === 'error' && !/favicon|third-party cookie|permissions policy/i.test(msg.text())) consoleErrors.push(msg.text());
  });
  page.on('requestfailed', req => {
    if (!ignorableFailure(req)) failed.push(`${req.url()} :: ${req.failure()?.errorText || 'failed'}`);
  });
  report.runtime[key] = { consoleErrors, failed };
}

async function rendered(el) {
  if (!el) return false;
  return el.evaluate(node => {
    const r = node.getBoundingClientRect(), s = getComputedStyle(node);
    return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
  }).catch(() => false);
}

async function onscreen(el) {
  if (!(await rendered(el))) return false;
  return el.evaluate(node => {
    const r = node.getBoundingClientRect();
    return r.right > 0 && r.bottom > 0 && r.left < innerWidth && r.top < innerHeight;
  }).catch(() => false);
}

async function firstRendered(page, selector) {
  for (const el of await page.$$(selector)) if (await rendered(el)) return el;
  return null;
}

async function firstOnscreen(page, selector) {
  for (const el of await page.$$(selector)) if (await onscreen(el)) return el;
  return null;
}

async function clickElement(page, el) {
  if (!el) return false;
  await el.evaluate(node => node.scrollIntoView({ block: 'center', inline: 'nearest' })).catch(() => {});
  await sleep(180);
  try { await el.click({ delay: 25 }); }
  catch { await el.evaluate(node => node.click()); }
  return true;
}

async function clickOnscreen(page, selector) {
  const el = await firstOnscreen(page, selector);
  if (!el) return false;
  return clickElement(page, el);
}

async function clickAndSettle(page, el) {
  const nav = page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 5000 }).catch(() => null);
  await clickElement(page, el);
  await nav;
  await sleep(1000);
}

async function go(page, path, label) {
  const raw = path.startsWith('http') ? path : BASE + path;
  const url = raw + (raw.includes('?') ? '&' : '?') + 'qa=' + Date.now();
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
  await sleep(1000);
  if (!response || response.status() >= 400) report.errors.push(`${label}: HTTP ${response?.status() || 'none'}`);
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 3).catch(() => false);
  if (overflow) report.errors.push(`${label}: horizontal overflow`);
  return response;
}

async function shot(page, name) { await page.screenshot({ path: `qa/${name}.png`, fullPage: true }); }
async function safe(label, fn) { try { await fn(); } catch (e) { report.errors.push(`${label}: ${e.message}`); } }

async function badge(page) {
  return page.evaluate(() => {
    const q = '.site-header .shop-cart-count,.site-header .shopping-cart-count,.site-header .cart-count,.site-header .count,.site-header .mini-cart-count,.site-header .elmercado-cart-direct-count';
    for (const node of document.querySelectorAll(q)) {
      const r = node.getBoundingClientRect(), s = getComputedStyle(node);
      if (r.width <= 0 || r.height <= 0 || s.display === 'none' || s.visibility === 'hidden' || Number(s.opacity) <= 0) continue;
      const m = (node.textContent || '').match(/\d+/);
      return { visible: true, value: m ? Number(m[0]) : 0 };
    }
    return { visible: false, value: 0 };
  }).catch(() => ({ visible: false, value: 0 }));
}

async function auditHome(page, mode) {
  await go(page, '/', `home ${mode}`);
  await shot(page, `home-${mode}`);
  const b = await badge(page);
  if (b.visible && b.value < 1) report.errors.push(`home ${mode}: zero cart badge visible`);

  const header = await page.evaluate(() => {
    const shell = document.querySelector('.site-header-inner > .woostify-container');
    const brand = document.querySelector('.site-header .site-branding');
    const tools = document.querySelector('.site-header .site-tools');
    if (!shell || !brand || !tools) return null;
    const a = shell.getBoundingClientRect(), b = brand.getBoundingClientRect(), c = tools.getBoundingClientRect();
    return { brand: Math.abs(a.top + a.height / 2 - (b.top + b.height / 2)), tools: Math.abs(a.top + a.height / 2 - (c.top + c.height / 2)) };
  });
  if (!header) report.errors.push(`home ${mode}: header structure missing`);
  else if (header.brand > 7 || header.tools > 7) report.errors.push(`home ${mode}: header alignment outside tolerance`);

  if (mode === 'mobile' && await page.$('.emo-featured-products ul.products')) {
    await page.$eval('.emo-featured-products', el => el.scrollIntoView({ block: 'center' }));
    await sleep(650);
    const prev = await firstOnscreen(page, '.emo-carousel-control--previous');
    const next = await firstOnscreen(page, '.emo-carousel-control--next');
    if (!prev || !next) report.errors.push('home mobile: carousel arrows missing when carousel is visible');
    if (next) {
      const before = await page.$eval('.emo-featured-products ul.products', el => el.scrollLeft);
      await clickElement(page, next); await sleep(650);
      const after = await page.$eval('.emo-featured-products ul.products', el => el.scrollLeft);
      if (after <= before + 2) report.errors.push('home mobile: carousel next arrow did not move track');
    }
  }

  await page.evaluate(() => window.scrollTo(0, 0));
  await page.keyboard.press('Tab'); await page.keyboard.press('Tab');
  if (!(await page.evaluate(() => document.activeElement && document.activeElement !== document.body))) report.errors.push(`home ${mode}: keyboard focus not reachable`);
}

async function auditMobileMenu(page) {
  await go(page, '/', 'mobile menu');
  if (!(await clickOnscreen(page, '.site-header .toggle-sidebar-menu-btn'))) return report.errors.push('mobile menu: toggle missing');
  await sleep(500);
  if (!(await page.evaluate(() => document.documentElement.classList.contains('sidebar-menu-open')))) report.errors.push('mobile menu: did not open');

  const close = await firstRendered(page, '.sidebar-menu .close-sidebar-menu-btn,.sidebar-menu .close-sidebar-menu,.sidebar-menu [class*="close-sidebar"]');
  if (!close) report.errors.push('mobile menu: close control missing');
  else {
    await clickElement(page, close); await sleep(1000);
    const closed = await page.evaluate(() => !document.documentElement.classList.contains('sidebar-menu-open'));
    if (!closed) report.errors.push('mobile menu: close control did not close');
  }

  await clickOnscreen(page, '.site-header .toggle-sidebar-menu-btn'); await sleep(350);
  await page.keyboard.press('Escape'); await sleep(500);
  if (await page.evaluate(() => document.documentElement.classList.contains('sidebar-menu-open'))) report.errors.push('mobile menu: Escape did not close');
}

async function auditSearch(page, mode) {
  await go(page, '/', `search ${mode}`);
  if (!(await clickOnscreen(page, '.site-header .header-search-icon,.site-header .search-icon,.site-header .site-search-toggle,.site-header .js-dgwt-wcas-search-icon-handler'))) return report.errors.push(`search ${mode}: toggle missing`);
  await sleep(650);
  const input = await firstOnscreen(page, 'input[type="search"]');
  if (!input) report.errors.push(`search ${mode}: search input missing`);
  else { await input.type('aceite'); report.checks[`searchOpen-${mode}`] = true; }
  await go(page, '/?s=aceite&post_type=product', `search results ${mode}`);
  if (!(await page.evaluate(() => document.querySelectorAll('li.product').length > 0))) report.errors.push(`search ${mode}: known query returned no products`);
  await go(page, '/?s=zzzz-no-existe-qa-987654&post_type=product', `search no results ${mode}`);
  if (await page.evaluate(() => document.querySelectorAll('li.product').length > 0)) report.errors.push(`search ${mode}: impossible query returned products`);
}

async function auditShop(page, mode) {
  await go(page, '/tienda/', `shop ${mode}`); await shot(page, `shop-${mode}`);
  const producerFilter = await page.evaluate(() => [...document.querySelectorAll('select')].some(select => {
    if (![...select.options].some(o => /(todos los productores|todos los vendedores)/i.test(o.textContent || ''))) return false;
    const r = select.getBoundingClientRect(), s = getComputedStyle(select);
    return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
  }));
  if (producerFilter) report.errors.push(`shop ${mode}: producer filter visible`);

  const ordering = await page.$('.woocommerce-ordering select');
  if (!ordering) report.errors.push(`shop ${mode}: ordering dropdown missing`);
  else {
    const borders = await ordering.evaluate(el => [getComputedStyle(el).borderTopWidth, getComputedStyle(el).borderBottomWidth]);
    if (borders.includes('0px')) report.errors.push(`shop ${mode}: ordering border incomplete`);
  }

  const text = await page.$eval('.woocommerce-result-count', el => (el.textContent || '').replace(/\s+/g, ' ').trim()).catch(() => '');
  if (/mostrando/i.test(text) && !/\d\s*de\s*\d/i.test(text)) report.errors.push(`shop ${mode}: malformed result count`);

  const titles = await page.$$eval('ul.products li.product .woocommerce-loop-product__title,ul.products li.product .elmercado-product-title-link', els => els.slice(0, 10).map(el => {
    const s = getComputedStyle(el); return { h: el.getBoundingClientRect().height, lh: parseFloat(s.lineHeight), clamp: String(s.webkitLineClamp || s.lineClamp || '') };
  }));
  if (!titles.length) report.errors.push(`shop ${mode}: product titles missing`);
  else if (titles.some(x => x.lh && x.h + 1 < x.lh * 1.9 && x.clamp !== '2')) report.errors.push(`shop ${mode}: titles do not reserve two lines`);

  if (mode === 'desktop') {
    const widgets = await page.$$eval('.widget-area .widget', els => els.filter(el => { const r = el.getBoundingClientRect(), s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== 'none'; }).length).catch(() => 0);
    if (!widgets) report.errors.push('shop desktop: filter sections missing');
  }
  if (!(await page.$('.widget_price_filter .price_slider,.price_slider_wrapper .ui-slider-horizontal'))) report.warnings.push(`shop ${mode}: price slider not detected`);

  const category = await page.$('.widget_product_categories a,.product-categories a');
  if (category) { const href = await category.evaluate(a => a.href); await go(page, href, `category ${mode}`); }
  else report.warnings.push(`shop ${mode}: category link not detected`);

  await go(page, '/tienda/', `shop restore ${mode}`);
  const pagination = await page.$('.woocommerce-pagination a.page-numbers');
  if (pagination) { const href = await pagination.evaluate(a => a.href); await go(page, href, `pagination ${mode}`); }
  else report.warnings.push(`shop ${mode}: pagination not present`);

  await go(page, '/tienda/', `product links ${mode}`);
  return page.$$eval('li.product a.woocommerce-loop-product__link', links => [...new Set(links.map(a => a.href))].slice(0, 10)).catch(() => []);
}

async function chooseVariations(page) {
  const selects = await page.$$('form.variations_form select');
  if (!selects.length) return { variable: false, enabled: true };
  const success = await page.evaluate(() => {
    const all = [...document.querySelectorAll('form.variations_form select')];
    for (const select of all) {
      const option = [...select.options].find(o => o.value && !o.disabled);
      if (!option) return false;
      select.value = option.value;
      select.dispatchEvent(new Event('input', { bubbles: true }));
      select.dispatchEvent(new Event('change', { bubbles: true }));
      if (window.jQuery) window.jQuery(select).trigger('change');
    }
    return true;
  });
  await sleep(1700);
  const enabled = success && await page.evaluate(() => {
    const b = document.querySelector('.single_add_to_cart_button');
    return !!b && !b.disabled && !b.classList.contains('disabled');
  });
  return { variable: true, enabled };
}

async function tryAddProduct(page, link, mode) {
  await go(page, link, `product ${mode}`);
  const variation = await chooseVariations(page);
  if (variation.variable) report.checks[`variation-${mode}`] = variation;
  if (variation.variable && !variation.enabled) return false;
  const add = await firstRendered(page, '.single_add_to_cart_button:not(.disabled):not([disabled]),form.cart button[type="submit"]:not(.disabled):not([disabled])');
  if (!add) return false;
  await clickAndSettle(page, add);
  await sleep(700);
  if (await page.$('input.qty')) return true;
  const current = page.url();
  if (!/\/carrito\/?/i.test(current)) await go(page, '/carrito/', `cart confirm ${mode}`);
  return !!(await page.$('input.qty'));
}

async function auditProductCart(page, mode, links) {
  let added = false;
  for (const link of links) {
    if (await tryAddProduct(page, link, mode)) { added = true; break; }
  }
  if (!added) return report.errors.push(`cart ${mode}: could not add any available product`);

  const b = await badge(page);
  if (!b.visible || b.value < 1) report.errors.push(`cart ${mode}: positive badge missing with item in cart`);
  await shot(page, `cart-${mode}`);

  const miniItems = await page.evaluate(() => document.querySelectorAll('.mini_cart_item,.woocommerce-mini-cart-item').length).catch(() => 0);
  if (!miniItems) report.warnings.push(`minicart ${mode}: mini-cart item markup not detected after add`);

  const qty = await page.$('input.qty');
  if (!qty) return report.errors.push(`cart ${mode}: quantity field missing`);
  await qty.click({ clickCount: 3 }); await qty.type('2');
  const update = await firstRendered(page, 'button[name="update_cart"],input[name="update_cart"]');
  if (update) await clickAndSettle(page, update);

  const coupon = await firstRendered(page, 'input#coupon_code,input[name="coupon_code"]');
  if (coupon) {
    await coupon.type('QA-CUPON-INVALIDO-987654');
    const apply = await firstRendered(page, 'button[name="apply_coupon"],input[name="apply_coupon"]');
    if (apply) { await clickAndSettle(page, apply); const error = await page.$('.woocommerce-error,.woocommerce-notice--error,[role="alert"]'); if (!error) report.errors.push(`cart ${mode}: invalid coupon produced no error notice`); }
  }

  await go(page, '/finalizar-compra/', `checkout ${mode}`); await shot(page, `checkout-${mode}`);
  if (!(await page.$('form.checkout,#customer_details,.woocommerce-checkout'))) report.errors.push(`checkout ${mode}: checkout form missing`);
}

async function auditContent(page, mode) {
  await go(page, '/contacto/', `contact ${mode}`); await shot(page, `contact-${mode}`);
  const form = await page.$('form.wpcf7-form,.elementor-form,form[id*="contact"]');
  if (!form) report.errors.push(`contact ${mode}: form missing`);
  else {
    const submit = await firstRendered(page, 'form.wpcf7-form input[type="submit"],form.wpcf7-form button[type="submit"],.elementor-form button[type="submit"],form[id*="contact"] button[type="submit"]');
    if (submit) {
      await clickElement(page, submit); await sleep(700);
      const invalid = await form.evaluate(node => !node.checkValidity()).catch(() => false);
      const message = await page.$('.wpcf7-not-valid-tip,.elementor-message-danger,[aria-invalid="true"]');
      if (!invalid && !message) report.warnings.push(`contact ${mode}: empty form validation message not detected`);
    }
  }

  await go(page, '/blog/', `blog ${mode}`);
  const article = await page.$eval('article a[href],h2 a[href]', a => a.href).catch(() => null);
  if (article) await go(page, article, `blog article ${mode}`); else report.warnings.push(`blog ${mode}: article link not found`);

  await go(page, '/', `footer ${mode}`);
  const legal = await page.evaluate(() => [...document.querySelectorAll('footer a[href],.site-footer a[href]')].filter(a => /privacidad|cookies|aviso legal|t[eé]rminos|condiciones/i.test((a.textContent || '') + ' ' + a.href)).map(a => a.href).filter((v, i, a) => a.indexOf(v) === i).slice(0, 6));
  if (!legal.length) report.errors.push(`footer ${mode}: legal links missing`);
  for (let i = 0; i < legal.length; i++) await go(page, legal[i], `legal ${mode} ${i + 1}`);

  await go(page, '/tienda/hidalgo-de-la-jara/', `vendor ${mode}`); await shot(page, `vendor-${mode}`);
  if (!(await page.evaluate(() => document.querySelectorAll('li.product').length > 0))) report.errors.push(`vendor ${mode}: products missing`);
  const gap = await page.evaluate(() => {
    const tabs = document.querySelector('.wcfmmp-store-tabs,.wcfm_store_tabs,.store-tabs,.wcfmmp-store-tab');
    if (!tabs || !tabs.nextElementSibling) return null;
    return tabs.nextElementSibling.getBoundingClientRect().top - tabs.getBoundingClientRect().bottom;
  });
  if (gap !== null && gap < 16) report.errors.push(`vendor ${mode}: tabs/content spacing ${Math.round(gap)}px`);
}

async function modeAudit(browser, mode, viewport) {
  const ctx = await browser.createBrowserContext();
  const page = await ctx.newPage();
  await page.setViewport(viewport); page.setDefaultNavigationTimeout(60000); attachRuntime(page, mode);
  await safe(`home ${mode}`, () => auditHome(page, mode));
  if (mode === 'mobile') await safe('mobile menu', () => auditMobileMenu(page));
  await safe(`search ${mode}`, () => auditSearch(page, mode));
  let links = [];
  await safe(`shop ${mode}`, async () => { links = await auditShop(page, mode); });
  await safe(`product/cart ${mode}`, () => auditProductCart(page, mode, links));
  await safe(`content ${mode}`, () => auditContent(page, mode));
  const rt = report.runtime[mode];
  const ce = [...new Set(rt.consoleErrors)], rf = [...new Set(rt.failed)];
  if (ce.length) report.errors.push(`${mode}: console error(s): ${ce.slice(0, 3).join(' | ')}`);
  if (rf.length) report.errors.push(`${mode}: request failure(s): ${rf.slice(0, 3).join(' | ')}`);
  await ctx.close();
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', protocolTimeout: 120000, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  try {
    const clean = await browser.createBrowserContext();
    const page = await clean.newPage(); await page.setViewport({ width: 1440, height: 1000 }); attachRuntime(page, 'clean');
    await go(page, '/carrito/', 'empty cart');
    if (!(await page.evaluate(() => /carrito.*vac[ií]o|tu carrito est[aá] vac[ií]o|volver a la tienda|return to shop/i.test(document.body.innerText)))) report.errors.push('empty cart state missing');
    const b = await badge(page); if (b.visible && b.value < 1) report.errors.push('empty cart: zero badge visible');
    await go(page, '/mi-cuenta/', 'account logged out');
    if (!(await page.$('form.woocommerce-form-login,input[name="username"],input[name="password"]'))) report.warnings.push('account logged-out form not detected');
    const rt = report.runtime.clean; if ([...new Set(rt.consoleErrors)].length) report.errors.push('clean: console error(s)'); if ([...new Set(rt.failed)].length) report.errors.push('clean: request failure(s)');
    await clean.close();
    await modeAudit(browser, 'desktop', { width: 1440, height: 1000 });
    await modeAudit(browser, 'mobile', { width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 1 });
  } finally { await browser.close(); }
  report.errors = [...new Set(report.errors)]; report.warnings = [...new Set(report.warnings)];
  fs.writeFileSync('qa/report-v7.json', JSON.stringify(report, null, 2));
  if (report.errors.length) { console.error(report.errors.join('\n')); process.exitCode = 2; }
  else console.log(`AUDIT_OK warnings=${report.warnings.length}`);
})();
