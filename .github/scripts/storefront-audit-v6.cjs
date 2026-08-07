const fs = require('fs');
const puppeteer = require('puppeteer-core');

const base = 'https://dev.elmercadodeorigen.com';
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const report = { errors: [], warnings: [], checks: {}, runtime: {} };
const ignoreUrl = url => /google-analytics|googletagmanager|doubleclick|clarity|facebook|notification\.mp3|fonts\.googleapis|fonts\.gstatic/i.test(url);

async function rendered(el) {
  if (!el) return false;
  return el.evaluate(node => {
    const rect = node.getBoundingClientRect();
    const style = getComputedStyle(node);
    return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
  }).catch(() => false);
}

async function onscreen(el) {
  if (!el || !(await rendered(el))) return false;
  return el.evaluate(node => {
    const rect = node.getBoundingClientRect();
    return rect.right > 0 && rect.bottom > 0 && rect.left < innerWidth && rect.top < innerHeight;
  }).catch(() => false);
}

async function firstOnscreen(page, selector) {
  for (const el of await page.$$(selector)) if (await onscreen(el)) return el;
  return null;
}

async function clickOnscreen(page, selector) {
  const el = await firstOnscreen(page, selector);
  if (!el) return false;
  try { await el.click({ delay: 25 }); }
  catch { await el.evaluate(node => node.click()); }
  return true;
}

async function go(page, path, label) {
  const raw = path.startsWith('http') ? path : base + path;
  const url = raw + (raw.includes('?') ? '&' : '?') + 'qa=' + Date.now();
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
  await sleep(1100);
  if (!response || response.status() >= 400) report.errors.push(`${label}: HTTP ${response?.status() || 'none'}`);
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 3);
  if (overflow) report.errors.push(`${label}: horizontal overflow`);
  return response;
}

async function shot(page, name) {
  await page.screenshot({ path: `qa/${name}.png`, fullPage: true });
}

async function safe(label, fn) {
  try { await fn(); }
  catch (error) { report.errors.push(`${label}: ${error.message}`); }
}

function runtime(page, key) {
  const consoleErrors = [];
  const failed = [];
  page.on('console', msg => {
    if (msg.type() === 'error' && !/favicon|third-party cookie|permissions policy/i.test(msg.text())) consoleErrors.push(msg.text());
  });
  page.on('requestfailed', req => {
    if (!ignoreUrl(req.url())) failed.push(`${req.url()} :: ${req.failure()?.errorText || 'failed'}`);
  });
  report.runtime[key] = { consoleErrors, failed };
}

async function cartBadge(page) {
  return page.evaluate(() => {
    const selectors = '.site-header .shop-cart-count,.site-header .shopping-cart-count,.site-header .cart-count,.site-header .count,.site-header .mini-cart-count,.site-header .elmercado-cart-direct-count';
    for (const node of document.querySelectorAll(selectors)) {
      const rect = node.getBoundingClientRect();
      const style = getComputedStyle(node);
      const isVisible = rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
      if (!isVisible) continue;
      const match = (node.textContent || '').match(/\d+/);
      return { visible: true, value: match ? Number(match[0]) : 0 };
    }
    return { visible: false, value: 0 };
  });
}

async function selectVariations(page) {
  const count = await page.$$eval('form.variations_form select', els => els.length).catch(() => 0);
  if (!count) return { variable: false, enabled: false };
  const ok = await page.evaluate(() => {
    const selects = [...document.querySelectorAll('form.variations_form select')];
    for (const select of selects) {
      const option = [...select.options].find(o => o.value && !o.disabled);
      if (!option) return false;
      select.value = option.value;
      select.dispatchEvent(new Event('input', { bubbles: true }));
      select.dispatchEvent(new Event('change', { bubbles: true }));
      if (window.jQuery) window.jQuery(select).trigger('change');
    }
    return true;
  });
  if (!ok) return { variable: true, enabled: false };
  await sleep(1600);
  const enabled = await page.evaluate(() => {
    const button = document.querySelector('.single_add_to_cart_button');
    return !!button && !button.disabled && !button.classList.contains('disabled') && getComputedStyle(button).display !== 'none';
  });
  return { variable: true, enabled };
}

async function addCurrentProduct(page) {
  const variation = await selectVariations(page);
  const button = await firstOnscreen(page, '.single_add_to_cart_button:not(.disabled):not([disabled]), form.cart button[type="submit"]:not(.disabled):not([disabled])');
  if (!button) return { added: false, variation };
  const before = await cartBadge(page);
  try { await button.click({ delay: 30 }); }
  catch { await button.evaluate(node => node.click()); }
  await sleep(1800);
  const after = await cartBadge(page);
  return { added: after.value > before.value || after.value > 0, before, after, variation };
}

async function auditHome(page, mode) {
  await go(page, '/', `home ${mode}`);
  await shot(page, `home-${mode}`);
  const badge = await cartBadge(page);
  if (badge.visible && badge.value < 1) report.errors.push(`home ${mode}: zero cart badge visible`);

  const header = await page.evaluate(() => {
    const shell = document.querySelector('.site-header-inner > .woostify-container');
    const tools = document.querySelector('.site-header .site-tools');
    const branding = document.querySelector('.site-header .site-branding');
    if (!shell || !tools || !branding) return null;
    const s = shell.getBoundingClientRect(), t = tools.getBoundingClientRect(), b = branding.getBoundingClientRect();
    return { toolsDelta: Math.abs((s.top + s.height / 2) - (t.top + t.height / 2)), brandDelta: Math.abs((s.top + s.height / 2) - (b.top + b.height / 2)) };
  });
  if (!header) report.errors.push(`home ${mode}: header structure missing`);
  else if (header.toolsDelta > 7 || header.brandDelta > 7) report.errors.push(`home ${mode}: header elements off-center`);

  if (mode === 'mobile') {
    const hasTrack = await page.$('.emo-featured-products ul.products');
    if (hasTrack) {
      await page.$eval('.emo-featured-products', el => el.scrollIntoView({ block: 'center', inline: 'nearest' }));
      await sleep(650);
      const arrows = await page.evaluate(() => {
        const prev = document.querySelector('.emo-carousel-control--previous');
        const next = document.querySelector('.emo-carousel-control--next');
        const check = el => {
          if (!el) return false;
          const r = el.getBoundingClientRect(), s = getComputedStyle(el);
          return r.width >= 20 && r.height >= 20 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0 && r.right > 0 && r.left < innerWidth;
        };
        return { prev: check(prev), next: check(next) };
      });
      if (!arrows.prev || !arrows.next) report.errors.push('home mobile: carousel arrows missing when section is visible');
      const before = await page.$eval('.emo-featured-products ul.products', el => el.scrollLeft).catch(() => 0);
      if (arrows.next) {
        await clickOnscreen(page, '.emo-carousel-control--next');
        await sleep(650);
        const after = await page.$eval('.emo-featured-products ul.products', el => el.scrollLeft).catch(() => before);
        if (after <= before + 2) report.errors.push('home mobile: carousel next arrow did not move products');
      }
    }
  }

  await page.evaluate(() => window.scrollTo(0, 0));
  await page.keyboard.press('Tab');
  await page.keyboard.press('Tab');
  const focus = await page.evaluate(() => document.activeElement && document.activeElement !== document.body);
  if (!focus) report.errors.push(`home ${mode}: keyboard focus not reachable`);
}

async function auditMobileMenu(page) {
  await go(page, '/', 'mobile menu home');
  if (!(await clickOnscreen(page, '.site-header .toggle-sidebar-menu-btn'))) return report.errors.push('mobile menu: toggle missing');
  await sleep(500);
  const open = await page.evaluate(() => document.documentElement.classList.contains('sidebar-menu-open'));
  if (!open) report.errors.push('mobile menu: did not open');

  let closedByButton = false;
  const close = await firstOnscreen(page, '.sidebar-menu .close-sidebar-menu-btn,.sidebar-menu .close-sidebar-menu,.sidebar-menu [class*="close-sidebar"]');
  if (close) {
    try { await close.click({ delay: 25 }); } catch { await close.evaluate(node => node.click()); }
    await sleep(550);
    closedByButton = await page.evaluate(() => !document.documentElement.classList.contains('sidebar-menu-open'));
  }
  if (!closedByButton) report.errors.push('mobile menu: close button did not close');

  await clickOnscreen(page, '.site-header .toggle-sidebar-menu-btn');
  await sleep(350);
  await page.keyboard.press('Escape');
  await sleep(350);
  const escaped = await page.evaluate(() => !document.documentElement.classList.contains('sidebar-menu-open'));
  if (!escaped) report.errors.push('mobile menu: Escape did not close');
}

async function auditSearch(page, mode) {
  await go(page, '/', `search home ${mode}`);
  const toggled = await clickOnscreen(page, '.site-header .header-search-icon,.site-header .search-icon,.site-header .site-search-toggle,.site-header .js-dgwt-wcas-search-icon-handler');
  if (!toggled) return report.errors.push(`search ${mode}: toggle missing`);
  await sleep(700);
  const input = await firstOnscreen(page, 'input[type="search"]');
  if (!input) report.errors.push(`search ${mode}: visible input missing`);
  else { await input.type('aceite'); report.checks[`searchOpen-${mode}`] = true; }

  await go(page, '/?s=aceite&post_type=product', `search results ${mode}`);
  if (!(await page.evaluate(() => document.querySelectorAll('li.product').length > 0))) report.errors.push(`search ${mode}: known query returned no products`);
  await go(page, '/?s=zzzz-no-existe-qa-987654&post_type=product', `search no-results ${mode}`);
  if (await page.evaluate(() => document.querySelectorAll('li.product').length > 0)) report.errors.push(`search ${mode}: impossible query returned products`);
}

async function auditShop(page, mode) {
  await go(page, '/tienda/', `shop ${mode}`);
  await shot(page, `shop-${mode}`);

  const sellerVisible = await page.evaluate(() => [...document.querySelectorAll('select')].some(select => {
    const producer = [...select.options].some(o => /(todos los productores|todos los vendedores)/i.test(o.textContent || ''));
    if (!producer) return false;
    const r = select.getBoundingClientRect(), s = getComputedStyle(select);
    return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
  }));
  if (sellerVisible) report.errors.push(`shop ${mode}: producer filter visible`);

  const orderBorder = await page.$eval('.woocommerce-ordering select', el => [getComputedStyle(el).borderTopWidth, getComputedStyle(el).borderBottomWidth]).catch(() => null);
  if (!orderBorder) report.errors.push(`shop ${mode}: ordering dropdown missing`);
  else if (orderBorder.includes('0px')) report.errors.push(`shop ${mode}: ordering border incomplete`);

  const resultText = await page.$eval('.woocommerce-result-count', el => (el.textContent || '').replace(/\s+/g, ' ').trim()).catch(() => '');
  if (/mostrando/i.test(resultText) && /\d\s*de\s*\d/i.test(resultText) === false) report.errors.push(`shop ${mode}: malformed result count`);

  const titleMetrics = await page.$$eval('ul.products li.product .woocommerce-loop-product__title,ul.products li.product .elmercado-product-title-link', els => els.slice(0, 10).map(el => {
    const s = getComputedStyle(el); return [el.getBoundingClientRect().height, parseFloat(s.lineHeight), s.webkitLineClamp || s.lineClamp || ''];
  }));
  if (!titleMetrics.length) report.errors.push(`shop ${mode}: product titles missing`);
  else if (titleMetrics.some(([height, lineHeight, clamp]) => lineHeight && height + 1 < lineHeight * 1.9 && String(clamp) !== '2')) report.errors.push(`shop ${mode}: product title does not reserve two lines`);

  const filterSections = await page.$$eval('.widget-area .widget', els => els.filter(el => { const r = el.getBoundingClientRect(), s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== 'none'; }).length).catch(() => 0);
  if (mode === 'desktop' && filterSections < 1) report.errors.push('shop desktop: filter sections missing');
  if (!(await page.$('.widget_price_filter .price_slider,.price_slider_wrapper .ui-slider-horizontal'))) report.warnings.push(`shop ${mode}: price slider not detected`);

  const category = await page.$('.widget_product_categories a,.product-categories a');
  if (category) { const href = await category.evaluate(a => a.href); await go(page, href, `category ${mode}`); }
  else report.warnings.push(`shop ${mode}: category filter link not detected`);

  await go(page, '/tienda/', `shop restore ${mode}`);
  const pagination = await page.$('.woocommerce-pagination a.page-numbers');
  if (pagination) { const href = await pagination.evaluate(a => a.href); await go(page, href, `pagination ${mode}`); }
  else report.warnings.push(`shop ${mode}: pagination not present for current catalogue size`);

  await go(page, '/tienda/', `shop links ${mode}`);
  return page.$$eval('li.product a.woocommerce-loop-product__link', links => [...new Set(links.map(a => a.href))].slice(0, 8)).catch(() => []);
}

async function auditProductCart(page, mode, links) {
  let added = false;
  let testedVariation = false;
  for (const link of links) {
    await go(page, link, `product candidate ${mode}`);
    const variationCount = await page.$$eval('form.variations_form select', els => els.length).catch(() => 0);
    if (variationCount) testedVariation = true;
    const result = await addCurrentProduct(page);
    if (result.variation.variable) report.checks[`variation-${mode}`] = result.variation;
    if (result.added) { added = true; report.checks[`cartAdd-${mode}`] = result; await shot(page, `product-${mode}`); break; }
  }
  if (!testedVariation) report.warnings.push(`product ${mode}: variable product not encountered in first catalogue links`);
  if (!added) return report.errors.push(`cart ${mode}: could not add any available product`);

  const badge = await cartBadge(page);
  if (!badge.visible || badge.value < 1) report.errors.push(`cart ${mode}: positive cart badge not visible after add`);

  await go(page, '/carrito/', `cart ${mode}`);
  await shot(page, `cart-${mode}`);
  const qty = await page.$('input.qty');
  if (!qty) return report.errors.push(`cart ${mode}: cart unexpectedly empty after confirmed add`);
  await qty.click({ clickCount: 3 });
  await qty.type('2');
  const update = await firstOnscreen(page, 'button[name="update_cart"],input[name="update_cart"]');
  if (update) { try { await update.click(); } catch { await update.evaluate(node => node.click()); } await sleep(1400); }

  const coupon = await firstOnscreen(page, 'input#coupon_code,input[name="coupon_code"]');
  if (coupon) {
    await coupon.type('QA-CUPON-INVALIDO-987654');
    const apply = await firstOnscreen(page, 'button[name="apply_coupon"],input[name="apply_coupon"]');
    if (apply) {
      try { await apply.click(); } catch { await apply.evaluate(node => node.click()); }
      await sleep(1300);
      const notice = await page.evaluate(() => !!document.querySelector('.woocommerce-error,.woocommerce-notices-wrapper .woocommerce-error,.woocommerce-notice--error'));
      if (!notice) report.errors.push(`cart ${mode}: invalid coupon produced no error notice`);
    }
  }

  await go(page, '/finalizar-compra/', `checkout ${mode}`);
  await shot(page, `checkout-${mode}`);
  if (!(await page.evaluate(() => !!document.querySelector('form.checkout,#customer_details,.woocommerce-checkout')))) report.errors.push(`checkout ${mode}: checkout form missing`);
}

async function auditContactContentVendor(page, mode) {
  await go(page, '/contacto/', `contact ${mode}`);
  await shot(page, `contact-${mode}`);
  const form = await page.$('form.wpcf7-form,.elementor-form,form[id*="contact"]');
  if (!form) report.errors.push(`contact ${mode}: form missing`);
  else {
    const invalid = await form.evaluate(node => !node.checkValidity());
    if (!invalid) report.warnings.push(`contact ${mode}: empty form does not expose native invalid state`);
    const submit = await firstOnscreen(page, 'form.wpcf7-form input[type="submit"],form.wpcf7-form button[type="submit"],.elementor-form button[type="submit"],form[id*="contact"] button[type="submit"]');
    if (submit) {
      try { await submit.click(); } catch { await submit.evaluate(node => node.click()); }
      await sleep(650);
      const errors = await page.evaluate(() => document.querySelectorAll('.wpcf7-not-valid-tip,.elementor-message-danger,.error,[aria-invalid="true"]').length);
      if (!invalid && !errors) report.warnings.push(`contact ${mode}: empty submit did not surface a validation error`);
    }
  }

  await go(page, '/blog/', `blog ${mode}`);
  const article = await page.$eval('article a[href],h2 a[href]', a => a.href).catch(() => null);
  if (article) await go(page, article, `blog article ${mode}`);
  else report.warnings.push(`blog ${mode}: article link not found`);

  await go(page, '/', `footer ${mode}`);
  const legal = await page.evaluate(() => [...document.querySelectorAll('footer a[href],.site-footer a[href]')]
    .filter(a => /privacidad|cookies|aviso legal|t[eé]rminos|condiciones/i.test((a.textContent || '') + ' ' + a.href))
    .map(a => a.href).filter((value, index, all) => all.indexOf(value) === index).slice(0, 6));
  if (!legal.length) report.errors.push(`footer ${mode}: legal links missing`);
  for (let i = 0; i < legal.length; i++) await go(page, legal[i], `legal ${mode} ${i + 1}`);

  await go(page, '/tienda/hidalgo-de-la-jara/', `vendor ${mode}`);
  await shot(page, `vendor-${mode}`);
  const vendorProducts = await page.evaluate(() => document.querySelectorAll('li.product').length);
  if (!vendorProducts) report.errors.push(`vendor ${mode}: product catalogue missing`);
  const gap = await page.evaluate(() => {
    const tabs = document.querySelector('.wcfmmp-store-tabs,.wcfm_store_tabs,.store-tabs,.wcfmmp-store-tab');
    if (!tabs) return null;
    const next = tabs.nextElementSibling;
    if (!next) return null;
    return next.getBoundingClientRect().top - tabs.getBoundingClientRect().bottom;
  });
  if (gap !== null && gap < 16) report.errors.push(`vendor ${mode}: tabs/content spacing ${Math.round(gap)}px`);
}

async function modeAudit(browser, mode, viewport) {
  const ctx = await browser.createBrowserContext();
  const page = await ctx.newPage();
  await page.setViewport(viewport);
  page.setDefaultNavigationTimeout(60000);
  runtime(page, mode);

  await safe(`home ${mode}`, () => auditHome(page, mode));
  if (mode === 'mobile') await safe('mobile menu', () => auditMobileMenu(page));
  await safe(`search ${mode}`, () => auditSearch(page, mode));
  let links = [];
  await safe(`shop ${mode}`, async () => { links = await auditShop(page, mode); });
  await safe(`product/cart ${mode}`, () => auditProductCart(page, mode, links));
  await safe(`content/vendor ${mode}`, () => auditContactContentVendor(page, mode));

  const runtimeResult = report.runtime[mode];
  const consoleErrors = [...new Set(runtimeResult.consoleErrors)];
  const failed = [...new Set(runtimeResult.failed)];
  if (consoleErrors.length) report.errors.push(`${mode}: console error(s) detected: ${consoleErrors.slice(0, 3).join(' | ')}`);
  if (failed.length) report.errors.push(`${mode}: request failure(s) detected: ${failed.slice(0, 3).join(' | ')}`);
  await ctx.close();
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', protocolTimeout: 120000, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  try {
    const clean = await browser.createBrowserContext();
    const page = await clean.newPage();
    await page.setViewport({ width: 1440, height: 1000 });
    runtime(page, 'clean');
    await go(page, '/carrito/', 'empty cart');
    if (!(await page.evaluate(() => /carrito.*vac[ií]o|tu carrito est[aá] vac[ií]o|volver a la tienda|return to shop/i.test(document.body.innerText)))) report.errors.push('empty cart state missing');
    const cleanBadge = await cartBadge(page);
    if (cleanBadge.visible && cleanBadge.value < 1) report.errors.push('empty cart: zero badge visible');
    await go(page, '/mi-cuenta/', 'account logged out');
    if (!(await page.evaluate(() => !!document.querySelector('form.woocommerce-form-login,input[name="username"],input[name="password"]')))) report.warnings.push('account logged-out form not detected');
    const cleanRuntime = report.runtime.clean;
    if ([...new Set(cleanRuntime.consoleErrors)].length) report.errors.push('clean: console error(s) detected');
    if ([...new Set(cleanRuntime.failed)].length) report.errors.push('clean: request failure(s) detected');
    await clean.close();

    await modeAudit(browser, 'desktop', { width: 1440, height: 1000 });
    await modeAudit(browser, 'mobile', { width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 1 });
  } finally {
    await browser.close();
  }

  report.errors = [...new Set(report.errors)];
  report.warnings = [...new Set(report.warnings)];
  fs.writeFileSync('qa/report-v6.json', JSON.stringify(report, null, 2));
  if (report.errors.length) {
    console.error(report.errors.join('\n'));
    process.exitCode = 2;
  } else {
    console.log(`AUDIT_OK warnings=${report.warnings.length}`);
  }
})();
