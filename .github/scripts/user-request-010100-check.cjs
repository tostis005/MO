const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];
const checks = {};

async function go(page, path, delay = 700) {
  const separator = path.includes('?') ? '&' : '?';
  const response = await page.goto(`${BASE}${path}${separator}qa-010100=${Date.now()}`, {
    waitUntil: 'domcontentloaded', timeout: 60000,
  });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
  await sleep(delay);
  if (!response || response.status() >= 400) failures.push(`${path}: HTTP ${response?.status() || 'none'}`);
}

function transparent(value) {
  return value === 'transparent' || /rgba\([^)]*,\s*0(?:\.0+)?\s*\)$/i.test(value || '');
}

async function homeCheck(page, width, height) {
  await page.setViewport({ width, height, deviceScaleFactor: 1, isMobile: width <= 767, hasTouch: width <= 1100 });
  await go(page, '/');
  const metric = await page.evaluate(() => {
    const featured = document.querySelector('.emo-featured-products');
    const story = document.querySelector('.emo-story');
    const card = featured?.querySelector('ul.products li.product');
    const image = card?.querySelector('a img');
    const css = (node) => node ? getComputedStyle(node) : null;
    const f = css(featured); const s = css(story); const c = css(card); const i = css(image);
    return {
      overflow: Math.max(0, document.documentElement.scrollWidth - innerWidth),
      featuredBg: f?.backgroundColor || '',
      storyBg: s?.backgroundColor || '',
      cardBg: c?.backgroundColor || '',
      cardBorderColor: c?.borderTopColor || '',
      cardBorderWidth: c?.borderTopWidth || '',
      cardShadow: c?.boxShadow || '',
      cardOverflow: c?.overflow || '',
      imageRadius: i?.borderRadius || '',
      productCount: featured?.querySelectorAll('ul.products li.product').length || 0,
    };
  });
  checks[`home-${width}`] = metric;
  if (!metric.productCount) failures.push(`${width}px home: no featured products`);
  if (metric.overflow > 1) failures.push(`${width}px home: horizontal overflow ${metric.overflow}px`);
  if (!transparent(metric.cardBg)) failures.push(`${width}px home: product card background is not transparent (${metric.cardBg})`);
  if (!transparent(metric.cardBorderColor)) failures.push(`${width}px home: visible product card border remains (${metric.cardBorderWidth} ${metric.cardBorderColor})`);
  if (metric.cardShadow !== 'none') failures.push(`${width}px home: product card shadow remains (${metric.cardShadow})`);
  if (metric.featuredBg === metric.storyBg || !metric.featuredBg || !metric.storyBg) failures.push(`${width}px home: featured/story surfaces are not distinct (${metric.featuredBg} / ${metric.storyBg})`);
  await page.screenshot({ path: `qa/user-request-010100-home-${width}.png`, fullPage: true });
}

async function filterFeedbackCheck(page) {
  await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  await go(page, '/tienda/');
  const toggle = await page.$('#emo-premium-filter-toggle');
  if (!toggle) {
    failures.push('390px filter feedback: premium filter toggle missing');
    return;
  }
  await toggle.click();
  await sleep(250);

  await page.evaluate(() => {
    window.__emoQaPreventFilterNav = (event) => {
      if (event.target.closest?.('#emo-premium-filter-shell .emo-mobile-filter-content a[href]')) event.preventDefault();
    };
    document.addEventListener('click', window.__emoQaPreventFilterNav, true);
  });

  const link = await page.evaluateHandle(() => {
    const visible = (node) => {
      const r = node.getBoundingClientRect(); const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
    };
    return [...document.querySelectorAll('#emo-premium-filter-shell .emo-mobile-filter-content a[href]')]
      .find((node) => visible(node) && node.getAttribute('href') && node.getAttribute('href').charAt(0) !== '#') || null;
  });
  const element = link.asElement();
  if (!element) {
    failures.push('390px filter feedback: no visible filter link');
    await link.dispose();
    return;
  }
  await element.click();
  await sleep(90);
  const state = await page.evaluate(() => {
    const overlay = document.querySelector('#emo-catalog-filter-progress');
    if (!overlay) return null;
    const r = overlay.getBoundingClientRect(); const s = getComputedStyle(overlay);
    return {
      hidden: overlay.hidden,
      visible: !overlay.hidden && r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden',
      text: (overlay.textContent || '').replace(/\s+/g, ' ').trim(),
      ariaBusy: document.body.getAttribute('aria-busy'),
      width: Math.round(r.width),
      height: Math.round(r.height),
    };
  });
  checks.filterFeedback = state;
  if (!state?.visible) failures.push(`390px filter feedback: progress layer not visible (${JSON.stringify(state)})`);
  if (!/actualizando productos/i.test(state?.text || '')) failures.push(`390px filter feedback: progress copy missing (${JSON.stringify(state)})`);
  if (state?.ariaBusy !== 'true') failures.push(`390px filter feedback: aria-busy not set (${JSON.stringify(state)})`);
  await page.screenshot({ path: 'qa/user-request-010100-filter-feedback-390.png', fullPage: false });
  await link.dispose();
}

async function cartCheck(page, productId) {
  await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  await go(page, `/tienda/?add-to-cart=${productId}`, 850);
  await go(page, '/carrito/', 800);
  const metric = await page.evaluate(() => {
    const subtotal = document.querySelector('.cart_totals .cart-subtotal td .amount');
    const orderAmount = document.querySelector('.cart_totals .order-total td strong .amount,.cart_totals .order-total td > strong .amount');
    const tax = document.querySelector('.cart_totals .order-total td .includes_tax');
    const td = document.querySelector('.cart_totals .order-total td');
    const row = document.querySelector('.cart_totals .order-total');
    const rect = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width, height: r.height };
    };
    return {
      subtotal: rect(subtotal),
      total: rect(orderAmount),
      tax: rect(tax),
      cell: rect(td),
      row: rect(row),
      strongDisplay: orderAmount?.closest('strong') ? getComputedStyle(orderAmount.closest('strong')).display : '',
      taxDisplay: tax ? getComputedStyle(tax).display : '',
      textAlign: td ? getComputedStyle(td).textAlign : '',
      overflow: Math.max(0, document.documentElement.scrollWidth - innerWidth),
    };
  });
  checks.cart = metric;
  if (!metric.subtotal || !metric.total || !metric.tax || !metric.cell) failures.push(`cart total geometry missing (${JSON.stringify(metric)})`);
  else {
    if (Math.abs(metric.subtotal.right - metric.total.right) > 5) failures.push(`cart total amount not aligned with subtotal (${JSON.stringify(metric)})`);
    if (metric.tax.top < metric.total.bottom - 1) failures.push(`cart tax note did not move below total (${JSON.stringify(metric)})`);
    if (metric.textAlign !== 'right') failures.push(`cart total cell not right aligned (${metric.textAlign})`);
  }
  if (metric.overflow > 1) failures.push(`cart 390px horizontal overflow ${metric.overflow}px`);
  await page.screenshot({ path: 'qa/user-request-010100-cart-390.png', fullPage: true });
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const products = await fetch(`${BASE}/wp-json/wc/store/v1/products?per_page=100`).then((r) => r.json());
  const product = products.find((item) => item.is_purchasable && item.is_in_stock && item.type === 'simple');
  if (!product) throw new Error('No purchasable simple product available for cart QA');

  const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', protocolTimeout: 120000, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  page.setDefaultNavigationTimeout(60000);
  try {
    for (const [width, height] of [[390, 844], [768, 1024], [1100, 900], [1440, 1000]]) await homeCheck(page, width, height);
    await filterFeedbackCheck(page);
    await cartCheck(page, product.id);
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/user-request-010100-check.json', JSON.stringify({ failures, checks }, null, 2));
  if (failures.length) {
    console.error(`USER_REQUEST_010100_FAIL ${JSON.stringify(failures)}`);
    process.exitCode = 2;
  } else {
    console.log(`USER_REQUEST_010100_OK ${JSON.stringify(checks)}`);
  }
})();
