const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];
const checks = {};

async function go(page, path, delay = 650) {
  const url = `${BASE}${path}${path.includes('?') ? '&' : '?'}page-filter=${Date.now()}`;
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
  await sleep(delay);
  if (!response || response.status() >= 400) failures.push(`${path}: HTTP ${response?.status() || 'none'}`);
}

async function measureStableTop(page, path, selector) {
  await go(page, path);
  const before = await page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    const r = el.getBoundingClientRect();
    return Math.round((r.top + scrollY) * 10) / 10;
  }, selector);
  if (before === null) return null;
  await page.evaluate(() => scrollTo(0, 180));
  await sleep(300);
  const after = await page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    const r = el.getBoundingClientRect();
    return Math.round((r.top + scrollY) * 10) / 10;
  }, selector);
  await page.evaluate(() => scrollTo(0, 0));
  return { top: before, after, delta: after === null ? null : Math.round(Math.abs(after - before) * 10) / 10 };
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
  await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  page.setDefaultNavigationTimeout(60000);

  try {
    const surfaces = [
      ['/tienda/', 'main.site-main'],
      ['/quienes-somos/', '.emo-about-layout'],
      ['/contacto/', '.emo-contact-layout'],
      ['/productores/', '.emo-producers-intro'],
      ['/blog/', '.emo-journal-hero'],
    ];
    const starts = [];
    for (const [path, selector] of surfaces) {
      const metric = await measureStableTop(page, path, selector);
      checks[`start:${path}`] = metric;
      if (!metric) failures.push(`${path}: start surface missing (${selector})`);
      else {
        starts.push([path, metric.top]);
        if (metric.delta === null || metric.delta > 3) failures.push(`${path}: start moves on first scroll ${JSON.stringify(metric)}`);
      }
      if (path === '/quienes-somos/') await page.screenshot({ path: 'qa/page-start-about-mobile.png', fullPage: false });
    }

    await go(page, `/contacto/?add-to-cart=${product.id}`, 800);
    for (const [path, selector] of [
      ['/carrito/', '.emo-cart-intro'],
      ['/finalizar-compra/', '.emo-checkout-intro'],
    ]) {
      const metric = await measureStableTop(page, path, selector);
      checks[`start:${path}`] = metric;
      if (!metric) failures.push(`${path}: transactional start surface missing (${selector})`);
      else {
        starts.push([path, metric.top]);
        if (metric.delta === null || metric.delta > 3) failures.push(`${path}: start moves on first scroll ${JSON.stringify(metric)}`);
      }
    }

    if (starts.length >= 5) {
      const values = starts.map(([, top]) => top);
      const spread = Math.round((Math.max(...values) - Math.min(...values)) * 10) / 10;
      checks.startSpread = { spread, starts };
      if (spread > 12) failures.push(`page starts differ too much (${spread}px): ${JSON.stringify(starts)}`);
    }

    await go(page, '/tienda/', 750);
    const toggle = await page.$('#emo-premium-filter-toggle');
    if (!toggle) {
      failures.push('filter toggle missing');
    } else {
      await toggle.click();
      await sleep(450);
      const filter = await page.evaluate(() => {
        const shell = document.querySelector('#emo-premium-filter-shell');
        const panel = shell?.querySelector('.emo-mobile-filter-panel');
        const headings = [...(shell?.querySelectorAll('.emo-mobile-filter-content .widget > .widget-title:first-child,.emo-mobile-filter-content .widget > .sidebar-heading:first-child,.emo-mobile-filter-content .widget > .widget-heading:first-child,.emo-mobile-filter-content .widget > .wp-block-heading:first-child') || [])].slice(0, 3);
        const headingData = headings.map((el) => {
          const r = el.getBoundingClientRect();
          const s = getComputedStyle(el);
          return {
            text: (el.textContent || '').replace(/\s+/g, ' ').trim(),
            top: Math.round(r.top * 10) / 10,
            bottom: Math.round(r.bottom * 10) / 10,
            height: Math.round(r.height * 10) / 10,
            radius: s.borderRadius,
            background: s.backgroundColor,
          };
        });
        const priceWidget = shell?.querySelector('.widget_price_filter');
        const nextWidget = priceWidget?.nextElementSibling;
        const nextHeading = nextWidget?.querySelector(':scope > .widget-title,:scope > .sidebar-heading,:scope > .widget-heading,:scope > .wp-block-heading');
        const pw = priceWidget?.getBoundingClientRect();
        const nh = nextHeading?.getBoundingClientRect();
        const track = shell?.querySelector('.widget_price_filter .price_slider.ui-slider,.widget_price_filter .ui-slider-horizontal');
        const handles = [...(shell?.querySelectorAll('.widget_price_filter .ui-slider-handle') || [])].slice(0, 2);
        const tr = track?.getBoundingClientRect();
        const hr = handles.map((el) => el.getBoundingClientRect());
        const slider = tr && hr.length === 2 ? {
          track: { left: tr.left, right: tr.right, top: tr.top, bottom: tr.bottom, centerY: tr.top + tr.height / 2 },
          handles: hr.map((r) => ({ left: r.left, right: r.right, top: r.top, bottom: r.bottom, centerX: r.left + r.width / 2, centerY: r.top + r.height / 2 })),
          yDelta: hr.map((r) => Math.abs((r.top + r.height / 2) - (tr.top + tr.height / 2))),
          xDelta: [Math.abs((hr[0].left + hr[0].width / 2) - tr.left), Math.abs((hr[1].left + hr[1].width / 2) - tr.right)],
        } : null;
        return {
          open: !!shell && !shell.hidden,
          panelWidth: panel ? Math.round(panel.getBoundingClientRect().width) : 0,
          headings: headingData,
          sectionGap: pw && nh ? Math.round((nh.top - pw.bottom) * 10) / 10 : null,
          slider,
        };
      });
      checks.filter = filter;
      if (!filter.open) failures.push('filter drawer did not open');
      if (filter.headings.length < 3) failures.push(`filter section headings missing ${JSON.stringify(filter)}`);
      else {
        const heights = filter.headings.map((h) => h.height);
        const radii = new Set(filter.headings.map((h) => h.radius));
        const backgrounds = new Set(filter.headings.map((h) => h.background));
        if (Math.max(...heights) - Math.min(...heights) > 1.5 || radii.size !== 1 || backgrounds.size !== 1) failures.push(`filter headings inconsistent ${JSON.stringify(filter.headings)}`);
      }
      if (filter.sectionGap === null || filter.sectionGap < 14) failures.push(`price/categories sections collide (${filter.sectionGap}px)`);
      if (!filter.slider) failures.push('price slider geometry missing');
      else if (Math.max(...filter.slider.yDelta) > 1.5 || Math.max(...filter.slider.xDelta) > 2.5) failures.push(`price slider handles misaligned ${JSON.stringify(filter.slider)}`);
      await page.screenshot({ path: 'qa/filter-detail-mobile.png', fullPage: false });
    }
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/page-start-filter-check.json', JSON.stringify({ failures, checks }, null, 2));
  if (failures.length) {
    console.error(failures.join('\n'));
    process.exitCode = 2;
  } else {
    console.log(`PAGE_START_FILTER_OK ${JSON.stringify(checks)}`);
  }
})();
