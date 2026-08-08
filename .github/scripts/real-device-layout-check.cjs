const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];
const checks = {};

async function go(page, path, delay = 650) {
  const url = `${BASE}${path}${path.includes('?') ? '&' : '?'}real-mobile=${Date.now()}`;
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
  await sleep(delay);
  if (!response || response.status() >= 400) failures.push(`${path}: HTTP ${response?.status() || 'none'}`);
}

async function surfaceMetric(page, path, selector) {
  await go(page, path);
  const before = await page.evaluate((sel) => {
    const node = [...document.querySelectorAll(sel)].find((el) => {
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
    });
    const header = document.querySelector('.site-header');
    if (!node || !header) return null;
    const r = node.getBoundingClientRect();
    const h = header.getBoundingClientRect();
    return {
      top: Math.round((r.top + scrollY) * 10) / 10,
      headerBottom: Math.round((h.bottom + scrollY) * 10) / 10,
      gap: Math.round((r.top - h.bottom) * 10) / 10,
    };
  }, selector);
  if (!before) return null;
  await page.evaluate(() => scrollTo(0, 170));
  await sleep(280);
  const after = await page.evaluate((sel) => {
    const node = [...document.querySelectorAll(sel)].find((el) => {
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
    });
    if (!node) return null;
    const r = node.getBoundingClientRect();
    return Math.round((r.top + scrollY) * 10) / 10;
  }, selector);
  await page.evaluate(() => scrollTo(0, 0));
  return { ...before, after, delta: after === null ? null : Math.round(Math.abs(after - before.top) * 10) / 10 };
}

async function checkStarts(page, label, expectedGap) {
  const surfaces = [
    ['/tienda/', '.emo-shop-lead'],
    ['/quienes-somos/', '.emo-about-layout'],
    ['/carrito/', '.emo-cart-intro'],
    ['/finalizar-compra/', '.emo-checkout-intro'],
    ['/contacto/', '.emo-contact-aside'],
    ['/contacto-productores/', '.emo-contact-aside'],
    ['/productores/', '.emo-producers-intro'],
    ['/blog/', '.emo-journal-hero__inner'],
  ];
  const values = [];
  for (const [path, selector] of surfaces) {
    const metric = await surfaceMetric(page, path, selector);
    checks[`start:${label}:${path}`] = metric;
    if (!metric) {
      failures.push(`${label} ${path}: start surface missing (${selector})`);
      continue;
    }
    values.push([path, metric.top, metric.gap]);
    if (metric.delta === null || metric.delta > 2) failures.push(`${label} ${path}: moves on first scroll ${JSON.stringify(metric)}`);
    if (expectedGap !== null && Math.abs(metric.gap - expectedGap) > 2.5) failures.push(`${label} ${path}: header/content gap ${metric.gap}px, expected ~${expectedGap}px`);
  }
  if (values.length > 1) {
    const tops = values.map(([, top]) => top);
    const gaps = values.map(([, , gap]) => gap);
    const topSpread = Math.round((Math.max(...tops) - Math.min(...tops)) * 10) / 10;
    const gapSpread = Math.round((Math.max(...gaps) - Math.min(...gaps)) * 10) / 10;
    checks[`startSpread:${label}`] = { topSpread, gapSpread, values };
    if (topSpread > 2.5 || gapSpread > 2.5) failures.push(`${label}: page starts are not aligned ${JSON.stringify(values)}`);
  }
}

async function checkFilter(page, width) {
  await page.setViewport({ width, height: 800, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  await go(page, '/tienda/', 800);
  const toggle = await page.$('#emo-premium-filter-toggle');
  if (!toggle) {
    failures.push(`${width}px: filter toggle missing`);
    return;
  }
  await toggle.click();
  await sleep(650);
  const data = await page.evaluate(() => {
    const shell = document.querySelector('#emo-premium-filter-shell');
    const panel = shell?.querySelector('.emo-mobile-filter-panel');
    const content = shell?.querySelector('.emo-mobile-filter-content');
    const close = shell?.querySelector('.emo-mobile-filter-close');
    const headings = [...(shell?.querySelectorAll('.emo-mobile-filter-content .widget > .widget-title,.emo-mobile-filter-content .widget > .sidebar-heading,.emo-mobile-filter-content .widget > .widget-heading,.emo-mobile-filter-content .widget > .wp-block-heading') || [])].slice(0, 3);
    const headingData = headings.map((el) => {
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return {
        text: (el.textContent || '').replace(/\s+/g, ' ').trim(),
        left: Math.round(r.left * 10) / 10,
        right: Math.round(r.right * 10) / 10,
        top: Math.round(r.top * 10) / 10,
        bottom: Math.round(r.bottom * 10) / 10,
        width: Math.round(r.width * 10) / 10,
        height: Math.round(r.height * 10) / 10,
        radius: s.borderRadius,
        background: s.backgroundColor,
        fontSize: s.fontSize,
      };
    });
    const priceWidget = shell?.querySelector('.widget_price_filter');
    const amount = priceWidget?.querySelector('.price_slider_amount');
    const nextWidget = priceWidget?.nextElementSibling;
    const nextHeading = nextWidget?.querySelector(':scope > .widget-title,:scope > .sidebar-heading,:scope > .widget-heading,:scope > .wp-block-heading');
    const pw = priceWidget?.getBoundingClientRect();
    const nw = nextWidget?.getBoundingClientRect();
    const ar = amount?.getBoundingClientRect();
    const nh = nextHeading?.getBoundingClientRect();
    const track = shell?.querySelector('.widget_price_filter .price_slider.ui-slider,.widget_price_filter .ui-slider-horizontal');
    const handles = [...(shell?.querySelectorAll('.widget_price_filter .ui-slider-handle') || [])].slice(0, 2);
    const tr = track?.getBoundingClientRect();
    const hr = handles.map((el) => el.getBoundingClientRect());
    const cr = close?.getBoundingClientRect();
    const slider = tr && hr.length === 2 ? {
      track: { left: tr.left, right: tr.right, top: tr.top, bottom: tr.bottom, centerY: tr.top + tr.height / 2 },
      handles: hr.map((r) => ({ left: r.left, right: r.right, top: r.top, bottom: r.bottom, centerX: r.left + r.width / 2, centerY: r.top + r.height / 2 })),
      yDelta: hr.map((r) => Math.abs((r.top + r.height / 2) - (tr.top + tr.height / 2))),
      xDelta: [Math.abs((hr[0].left + hr[0].width / 2) - tr.left), Math.abs((hr[1].left + hr[1].width / 2) - tr.right)],
    } : null;
    return {
      open: !!shell && !shell.hidden,
      panelWidth: panel ? Math.round(panel.getBoundingClientRect().width * 10) / 10 : 0,
      contentOverflow: content ? Math.round((content.scrollWidth - content.clientWidth) * 10) / 10 : null,
      close: cr ? { width: Math.round(cr.width * 10) / 10, height: Math.round(cr.height * 10) / 10, radius: getComputedStyle(close).borderRadius } : null,
      headings: headingData,
      sectionGap: ar && nh ? Math.round((nh.top - ar.bottom) * 10) / 10 : null,
      widgetGap: pw && nw ? Math.round((nw.top - pw.bottom) * 10) / 10 : null,
      slider,
    };
  });
  checks[`filter:${width}`] = data;
  if (!data.open) failures.push(`${width}px: filter drawer did not open`);
  if (data.contentOverflow !== null && data.contentOverflow > 2) failures.push(`${width}px: filter content overflows horizontally (${data.contentOverflow}px)`);
  if (!data.close || Math.abs(data.close.width - data.close.height) > 1) failures.push(`${width}px: filter close is not circular ${JSON.stringify(data.close)}`);
  if (data.headings.length < 3) failures.push(`${width}px: filter headings missing ${JSON.stringify(data.headings)}`);
  else {
    const heights = data.headings.map((h) => h.height);
    const widths = data.headings.map((h) => h.width);
    const radii = new Set(data.headings.map((h) => h.radius));
    const backgrounds = new Set(data.headings.map((h) => h.background));
    const fontSizes = new Set(data.headings.map((h) => h.fontSize));
    if (Math.max(...heights) - Math.min(...heights) > 1 || Math.max(...widths) - Math.min(...widths) > 1 || radii.size !== 1 || backgrounds.size !== 1 || fontSizes.size !== 1) {
      failures.push(`${width}px: filter headings are not one visual system ${JSON.stringify(data.headings)}`);
    }
  }
  if (data.sectionGap === null || data.sectionGap < 18) failures.push(`${width}px: price/categories content gap too small (${data.sectionGap}px)`);
  if (data.widgetGap === null || data.widgetGap < 12) failures.push(`${width}px: price widget overlaps next section (${data.widgetGap}px)`);
  if (!data.slider) failures.push(`${width}px: slider geometry missing`);
  else if (Math.max(...data.slider.yDelta) > 1.5 || Math.max(...data.slider.xDelta) > 2.5) failures.push(`${width}px: slider handles misaligned ${JSON.stringify(data.slider)}`);
  await page.screenshot({ path: `qa/real-filter-${width}.png`, fullPage: false });
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const products = await fetch(`${BASE}/wp-json/wc/store/v1/products?per_page=100`).then((r) => r.json());
  const product = products.find((item) => item.is_purchasable && item.is_in_stock && item.type === 'simple');
  if (!product) throw new Error('No purchasable simple product available');

  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    protocolTimeout: 120000,
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  const page = await browser.newPage();
  page.setDefaultNavigationTimeout(60000);
  await page.setUserAgent('Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0 Mobile Safari/537.36');

  try {
    await page.setViewport({ width: 360, height: 800, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
    await go(page, `/contacto/?add-to-cart=${product.id}`, 850);
    await checkStarts(page, '360px', 18);
    await page.screenshot({ path: 'qa/real-start-about-360.png', fullPage: false });

    for (const width of [360, 375, 390]) await checkFilter(page, width);

    await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
    await checkStarts(page, '390px', 18);

    await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
    await checkStarts(page, '1440px', 24);
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/real-device-layout-check.json', JSON.stringify({ failures, checks }, null, 2));
  if (failures.length) {
    console.error(failures.join('\n'));
    process.exitCode = 2;
  } else {
    console.log(`REAL_DEVICE_LAYOUT_OK ${JSON.stringify(checks)}`);
  }
})();
