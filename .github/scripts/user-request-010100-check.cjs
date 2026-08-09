const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];
const checks = {};

async function go(page, pathOrUrl, delay = 700) {
  const url = /^https?:/i.test(pathOrUrl) ? new URL(pathOrUrl) : new URL(pathOrUrl, BASE);
  url.searchParams.set('qa-010103', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
  await sleep(delay);
  if (!response || response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
}

function isWhite(value) {
  return /^rgb\(255,\s*255,\s*255\)$/i.test(value || '') || /^rgba\(255,\s*255,\s*255,\s*1(?:\.0+)?\)$/i.test(value || '');
}

async function productCardMetric(page, scope) {
  return page.evaluate((scopeSelector) => {
    const card = document.querySelector(`${scopeSelector} ul.products li.product`);
    if (!card) return null;
    const title = card.querySelector('.woocommerce-loop-product__title,.product-title,h2');
    const price = card.querySelector('.price');
    const content = card.querySelector('.product-loop-content,.product-content');
    const image = card.querySelector('img');
    const effectiveBackground = (node) => {
      let current = node;
      while (current && current !== document.documentElement) {
        const color = getComputedStyle(current).backgroundColor;
        if (color && color !== 'transparent' && !/^rgba\([^)]*,\s*0(?:\.0+)?\s*\)$/i.test(color)) return color;
        current = current.parentElement;
      }
      return '';
    };
    const style = (node) => {
      if (!node) return null;
      const s = getComputedStyle(node);
      const r = node.getBoundingClientRect();
      return {
        backgroundColor: s.backgroundColor,
        effectiveBackground: effectiveBackground(node),
        borderRadius: s.borderRadius,
        overflow: s.overflow,
        paddingLeft: s.paddingLeft,
        paddingRight: s.paddingRight,
        lineHeight: s.lineHeight,
        webkitLineClamp: s.webkitLineClamp,
        textOverflow: s.textOverflow,
        width: r.width,
        height: r.height,
      };
    };
    return {
      overflow: Math.max(0, document.documentElement.scrollWidth - innerWidth),
      card: style(card),
      content: style(content),
      title: style(title),
      price: style(price),
      image: style(image),
    };
  }, scope);
}

async function cardAndTitleCheck(page, path, scope, label, width, height) {
  await page.setViewport({ width, height, deviceScaleFactor: 1, isMobile: width <= 767, hasTouch: width <= 1100 });
  await go(page, path);
  const metric = await productCardMetric(page, scope);
  checks[`${label}-${width}`] = metric;
  if (!metric) {
    failures.push(`${label}-${width}: product card missing`);
    return null;
  }
  if (!isWhite(metric.card.backgroundColor)) failures.push(`${label}-${width}: card is not white (${metric.card.backgroundColor})`);
  if (!isWhite(metric.title.effectiveBackground)) failures.push(`${label}-${width}: title area is not on white (${metric.title.effectiveBackground})`);
  if (metric.price && !isWhite(metric.price.effectiveBackground)) failures.push(`${label}-${width}: price area is not on white (${metric.price.effectiveBackground})`);
  if (metric.card.overflow !== 'hidden') failures.push(`${label}-${width}: outer card does not clip rounded corners (${metric.card.overflow})`);
  if (parseFloat(metric.card.borderRadius || '0') < 12) failures.push(`${label}-${width}: outer card radius too small (${metric.card.borderRadius})`);
  if (metric.overflow > 1) failures.push(`${label}-${width}: horizontal overflow ${metric.overflow}px`);

  const titles = await page.evaluate((scopeSelector) => {
    const cards = [...document.querySelectorAll(`${scopeSelector} ul.products li.product`)].slice(0, 10);
    return cards.map((card) => {
      const title = card.querySelector('.woocommerce-loop-product__title,.product-title,h2');
      if (!title) return null;
      const s = getComputedStyle(title);
      const r = title.getBoundingClientRect();
      const lineHeight = parseFloat(s.lineHeight || '0');
      return {
        text: (title.textContent || '').replace(/\s+/g, ' ').trim(),
        height: r.height,
        clientHeight: title.clientHeight,
        scrollHeight: title.scrollHeight,
        lineHeight,
        approxLines: lineHeight ? r.height / lineHeight : 0,
        webkitLineClamp: s.webkitLineClamp,
        overflow: s.overflow,
        textOverflow: s.textOverflow,
        whiteSpace: s.whiteSpace,
      };
    }).filter(Boolean);
  }, scope);
  checks[`${label}-${width}-titles`] = titles;
  if (!titles.length) failures.push(`${label}-${width}: titles missing`);
  for (const title of titles) {
    if (title.webkitLineClamp !== '2') failures.push(`${label}-${width}: title clamp is ${title.webkitLineClamp} (${title.text})`);
    if (title.overflow !== 'hidden') failures.push(`${label}-${width}: title overflow is ${title.overflow} (${title.text})`);
    if (title.textOverflow !== 'ellipsis') failures.push(`${label}-${width}: title ellipsis is ${title.textOverflow} (${title.text})`);
    if (title.approxLines < 1.9 || title.approxLines > 2.1) failures.push(`${label}-${width}: title box is not two lines (${title.approxLines.toFixed(2)} / ${title.text})`);
  }
  const longTitle = titles.find((title) => title.text.length >= 45 && title.scrollHeight > title.clientHeight + 1);
  checks[`${label}-${width}-truncatedExample`] = longTitle || null;

  await page.screenshot({ path: `qa/user-request-010103-${label}-${width}.png`, fullPage: true });
  return metric;
}

async function compareHomeShop(page, width, height) {
  const home = await cardAndTitleCheck(page, '/', '.emo-featured-products', 'home-products', width, height);
  const shop = await cardAndTitleCheck(page, '/tienda/', '.woocommerce', 'shop-products', width, height);
  if (!home || !shop) return;
  const comparison = {
    radiusDelta: Math.abs(parseFloat(home.card.borderRadius || '0') - parseFloat(shop.card.borderRadius || '0')),
    homeRadius: home.card.borderRadius,
    shopRadius: shop.card.borderRadius,
    homeTitleLineHeight: home.title.lineHeight,
    shopTitleLineHeight: shop.title.lineHeight,
  };
  checks[`home-shop-comparison-${width}`] = comparison;
  if (comparison.radiusDelta > 1) failures.push(`${width}px: home/shop card radius differs (${JSON.stringify(comparison)})`);
  if (home.title.webkitLineClamp !== shop.title.webkitLineClamp) failures.push(`${width}px: home/shop title clamp differs`);
}

async function landscapeCheck(page) {
  await page.setViewport({ width: 844, height: 390, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  await go(page, '/');
  const metric = await page.evaluate(() => {
    const rowCount = (selector) => {
      const items = [...document.querySelectorAll(selector)].filter((node) => {
        const r = node.getBoundingClientRect();
        const s = getComputedStyle(node);
        return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
      });
      if (!items.length) return 0;
      const top = items[0].getBoundingClientRect().top;
      return items.filter((node) => Math.abs(node.getBoundingClientRect().top - top) < 3).length;
    };
    const track = document.querySelector('.emo-featured-products ul.products');
    const card = track?.querySelector('li.product');
    const cs = track ? getComputedStyle(track) : null;
    const cardStyle = card ? getComputedStyle(card) : null;
    return {
      productFirstRow: rowCount('.emo-featured-products ul.products li.product'),
      categoryFirstRow: rowCount('.emo-category-grid .emo-category-card'),
      cardWidth: card?.getBoundingClientRect().width || 0,
      cardBackground: cardStyle?.backgroundColor || '',
      cardRadius: cardStyle?.borderRadius || '',
      display: cs?.display || '',
      columns: cs?.gridTemplateColumns || '',
      overflow: Math.max(0, document.documentElement.scrollWidth - innerWidth),
    };
  });
  checks.landscape = metric;
  if (metric.productFirstRow < 3) failures.push(`landscape: fewer than 3 products in first row (${JSON.stringify(metric)})`);
  if (metric.categoryFirstRow >= 3 && metric.productFirstRow !== metric.categoryFirstRow) failures.push(`landscape: product/category row density differs (${JSON.stringify(metric)})`);
  if (metric.cardWidth > 280) failures.push(`landscape: product cards remain too large (${metric.cardWidth}px)`);
  if (metric.display !== 'grid') failures.push(`landscape: product track is not grid (${metric.display})`);
  if (!isWhite(metric.cardBackground)) failures.push(`landscape: product card is not white (${metric.cardBackground})`);
  if (metric.overflow > 1) failures.push(`landscape: horizontal overflow ${metric.overflow}px`);
  await page.screenshot({ path: 'qa/user-request-010103-home-landscape-844x390.png', fullPage: true });
}

async function filterFeedbackCheck(page) {
  await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  await go(page, '/tienda/');
  const toggle = await page.$('#emo-premium-filter-toggle');
  if (!toggle) {
    failures.push('filter feedback: premium filter toggle missing');
    return;
  }
  await toggle.click();
  await sleep(220);
  await page.evaluate(() => {
    window.__emoQaPreventFilterNav = (event) => {
      if (event.target.closest?.('#emo-premium-filter-shell .emo-mobile-filter-content a[href]')) event.preventDefault();
    };
    document.addEventListener('click', window.__emoQaPreventFilterNav, true);
  });
  const handle = await page.evaluateHandle(() => {
    const visible = (node) => {
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
    };
    return [...document.querySelectorAll('#emo-premium-filter-shell .emo-mobile-filter-content a[href]')]
      .find((node) => visible(node) && node.getAttribute('href') && node.getAttribute('href').charAt(0) !== '#') || null;
  });
  const link = handle.asElement();
  if (!link) {
    failures.push('filter feedback: no visible filter link');
    await handle.dispose();
    return;
  }
  await link.click();
  await sleep(90);
  const state = await page.evaluate(() => {
    const overlay = document.querySelector('#emo-catalog-filter-progress');
    if (!overlay) return null;
    const r = overlay.getBoundingClientRect();
    const s = getComputedStyle(overlay);
    return {
      visible: !overlay.hidden && r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden',
      text: (overlay.textContent || '').replace(/\s+/g, ' ').trim(),
      ariaBusy: document.body.getAttribute('aria-busy'),
    };
  });
  checks.filterFeedback = state;
  if (!state?.visible) failures.push(`filter feedback: progress layer not visible (${JSON.stringify(state)})`);
  if (!/actualizando productos/i.test(state?.text || '')) failures.push(`filter feedback: progress copy missing (${JSON.stringify(state)})`);
  if (state?.ariaBusy !== 'true') failures.push(`filter feedback: aria-busy not set (${JSON.stringify(state)})`);
  await page.screenshot({ path: 'qa/user-request-010103-filter-feedback-390.png', fullPage: false });
  await handle.dispose();
}

async function filteredLeadCheck(page) {
  await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  await go(page, '/categoria-producto/aceites/', 800);
  const metric = await page.evaluate(() => {
    const lead = document.querySelector('.emo-shop-lead');
    const toolbar = document.querySelector('.woostify-sorting');
    const rect = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      return { top: r.top, bottom: r.bottom, left: r.left, right: r.right, width: r.width, height: r.height };
    };
    const visible = (node) => {
      if (!node) return false;
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
    };
    return {
      leadVisible: visible(lead),
      leadText: (lead?.textContent || '').replace(/\s+/g, ' ').trim(),
      lead: rect(lead),
      toolbar: rect(toolbar),
      gap: lead && toolbar ? toolbar.getBoundingClientRect().top - lead.getBoundingClientRect().bottom : null,
      overflow: Math.max(0, document.documentElement.scrollWidth - innerWidth),
    };
  });
  checks.filteredLead = metric;
  if (!metric.leadVisible) failures.push(`filtered lead: lead missing/hidden (${JSON.stringify(metric)})`);
  if (!/procedencia clara/i.test(metric.leadText)) failures.push(`filtered lead: context copy missing (${metric.leadText})`);
  if (!metric.toolbar) failures.push('filtered lead: result toolbar missing');
  if (metric.lead && metric.toolbar && metric.toolbar.top <= metric.lead.top) failures.push(`filtered lead: toolbar appears before/over lead (${JSON.stringify(metric)})`);
  if (metric.overflow > 1) failures.push(`filtered lead: horizontal overflow ${metric.overflow}px`);
  await page.screenshot({ path: 'qa/user-request-010103-filtered-shop-390.png', fullPage: true });
}

async function cartAlignmentCheck(page, productId) {
  await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  await go(page, `/tienda/?add-to-cart=${productId}`, 850);
  await go(page, '/carrito/', 800);
  const metric = await page.evaluate(() => {
    const card = document.querySelector('.cart_totals');
    const table = card?.querySelector('table.shop_table');
    const subtotal = card?.querySelector('.cart-subtotal td .amount');
    const total = card?.querySelector('.order-total td .amount');
    const tax = card?.querySelector('.order-total td .includes_tax');
    const rect = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width, height: r.height };
    };
    const style = card ? getComputedStyle(card) : null;
    const cardRect = rect(card);
    const expectedLeft = cardRect && style ? cardRect.left + parseFloat(style.paddingLeft || '0') : null;
    const expectedRight = cardRect && style ? cardRect.right - parseFloat(style.paddingRight || '0') : null;
    return {
      card: cardRect,
      table: rect(table),
      subtotal: rect(subtotal),
      total: rect(total),
      tax: rect(tax),
      expectedLeft,
      expectedRight,
      overflow: Math.max(0, document.documentElement.scrollWidth - innerWidth),
    };
  });
  checks.cartAlignment = metric;
  if (!metric.card || !metric.table || !metric.subtotal || !metric.total || !metric.tax) {
    failures.push(`cart alignment: geometry missing (${JSON.stringify(metric)})`);
  } else {
    if (Math.abs(metric.table.left - metric.expectedLeft) > 3) failures.push(`cart alignment: table left inset incorrect (${JSON.stringify(metric)})`);
    if (Math.abs(metric.table.right - metric.expectedRight) > 3) failures.push(`cart alignment: table right inset incorrect (${JSON.stringify(metric)})`);
    if (Math.abs(metric.subtotal.right - metric.expectedRight) > 4) failures.push(`cart alignment: subtotal too far left (${JSON.stringify(metric)})`);
    if (Math.abs(metric.total.right - metric.expectedRight) > 4) failures.push(`cart alignment: total too far left (${JSON.stringify(metric)})`);
    if (metric.tax.top < metric.total.bottom - 1) failures.push(`cart alignment: tax note is not below total (${JSON.stringify(metric)})`);
  }
  if (metric.overflow > 1) failures.push(`cart alignment: horizontal overflow ${metric.overflow}px`);
  await page.screenshot({ path: 'qa/user-request-010103-cart-390.png', fullPage: true });
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const products = await fetch(`${BASE}/wp-json/wc/store/v1/products?per_page=100`).then((r) => r.json());
  const product = products.find((item) => item.is_purchasable && item.is_in_stock && item.type === 'simple');
  if (!product) throw new Error('No purchasable simple product available for cart QA');

  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    protocolTimeout: 120000,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const page = await browser.newPage();
  page.setDefaultNavigationTimeout(60000);
  try {
    await compareHomeShop(page, 390, 844);
    await compareHomeShop(page, 768, 1024);
    await compareHomeShop(page, 1100, 900);
    await compareHomeShop(page, 1440, 1000);
    await landscapeCheck(page);
    await filterFeedbackCheck(page);
    await filteredLeadCheck(page);
    await cartAlignmentCheck(page, product.id);
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/user-request-010103-check.json', JSON.stringify({ failures, checks }, null, 2));
  if (failures.length) {
    console.error(`USER_REQUEST_010103_FAIL ${JSON.stringify(failures)}`);
    process.exitCode = 2;
  } else {
    console.log(`USER_REQUEST_010103_OK ${JSON.stringify(checks)}`);
  }
})();
