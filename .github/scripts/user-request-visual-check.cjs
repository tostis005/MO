const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];
const checks = {};

async function go(page, path, delay = 700) {
  const url = `${BASE}${path}${path.includes('?') ? '&' : '?'}user-visual=${Date.now()}`;
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
  await sleep(delay);
  if (!response || response.status() >= 400) failures.push(`${path}: HTTP ${response?.status() || 'none'}`);
}

async function clickVisible(page, selector) {
  for (const el of await page.$$(selector)) {
    const visible = await el.evaluate((node) => {
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
    }).catch(() => false);
    if (!visible) continue;
    await el.evaluate((node) => (node.closest('a,button,[role="button"]') || node).click());
    return true;
  }
  return false;
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const products = await fetch(`${BASE}/wp-json/wc/store/v1/products?per_page=100`).then((r) => r.json());
  const product = products.find((item) => item.is_purchasable && item.is_in_stock && item.type === 'simple');
  if (!product) throw new Error('No purchasable simple product available for visual cart test');
  const titleToken = (product.name || '').replace(/<[^>]*>/g, '').trim().split(/\s+/)[0].toLowerCase();

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
    await go(page, '/blog/');
    const blog = await page.evaluate(() => {
      const hero = document.querySelector('.emo-journal-hero__inner');
      const listing = document.querySelector('.emo-journal-listing > .emo-shell');
      if (!hero || !listing) return null;
      const a = hero.getBoundingClientRect();
      const b = listing.getBoundingClientRect();
      return {
        heroLeft: Math.round(a.left),
        heroRight: Math.round(innerWidth - a.right),
        listLeft: Math.round(b.left),
        listRight: Math.round(innerWidth - b.right),
        leftDelta: Math.round(Math.abs(a.left - b.left)),
        rightDelta: Math.round(Math.abs(a.right - b.right))
      };
    });
    checks.blog = blog;
    if (!blog || blog.leftDelta > 4 || blog.rightDelta > 4) failures.push(`blog listing not aligned with hero: ${JSON.stringify(blog)}`);
    await page.screenshot({ path: 'qa/user-blog-mobile.png', fullPage: true });

    await go(page, `/contacto/?add-to-cart=${product.id}`, 900);
    const opened = await clickVisible(page, '.site-header .shopping-cart,.site-header .shopping-bag-button,.site-header a.cart-contents');
    if (!opened) failures.push('mobile minicart trigger not found');
    await sleep(650);

    const minicart = await page.evaluate((token) => {
      const item = document.querySelector('#shop-cart-sidebar .woocommerce-mini-cart-item,#shop-cart-sidebar .mini_cart_item');
      if (!item) return null;
      const image = item.querySelector('img');
      if (!image) return { item: true, title: '', image: false };

      const walker = document.createTreeWalker(item, NodeFilter.SHOW_TEXT);
      let textNode = null;
      while (walker.nextNode()) {
        const value = (walker.currentNode.nodeValue || '').replace(/\s+/g, ' ').trim();
        if (!value || /<img\b/i.test(value)) continue;
        if (token && value.toLowerCase().includes(token)) { textNode = walker.currentNode; break; }
      }
      if (!textNode) {
        const fallback = document.createTreeWalker(item, NodeFilter.SHOW_TEXT);
        while (fallback.nextNode()) {
          const value = (fallback.currentNode.nodeValue || '').replace(/\s+/g, ' ').trim();
          if (value && !/<img\b/i.test(value) && !/vendido por|subtotal|€|^[+−\-]?\d+$/i.test(value)) { textNode = fallback.currentNode; break; }
        }
      }

      const ir = image.getBoundingClientRect();
      let tr = null;
      let title = '';
      if (textNode) {
        title = (textNode.nodeValue || '').replace(/\s+/g, ' ').trim();
        const range = document.createRange();
        range.selectNodeContents(textNode);
        tr = range.getBoundingClientRect();
      }
      const drawer = document.querySelector('#shop-cart-sidebar')?.getBoundingClientRect();
      return {
        title,
        image: { left: Math.round(ir.left), right: Math.round(ir.right), top: Math.round(ir.top), bottom: Math.round(ir.bottom) },
        titleRect: tr ? { left: Math.round(tr.left), right: Math.round(tr.right), top: Math.round(tr.top), bottom: Math.round(tr.bottom) } : null,
        drawer: drawer ? { left: Math.round(drawer.left), right: Math.round(drawer.right), width: Math.round(drawer.width) } : null
      };
    }, titleToken);
    checks.minicart = minicart;
    if (!minicart?.title || !minicart.titleRect || minicart.titleRect.width === 0 || minicart.titleRect.height === 0) {
      failures.push(`mobile minicart product title missing: ${JSON.stringify(minicart)}`);
    } else {
      const beside = minicart.titleRect.left >= minicart.image.right - 4 && minicart.titleRect.top < minicart.image.bottom - 10;
      if (!beside) failures.push(`mobile minicart product title is not beside image: ${JSON.stringify(minicart)}`);
    }
    await page.screenshot({ path: 'qa/user-minicart-mobile.png', fullPage: false });

    await go(page, '/carrito/', 800);
    const assurance = await page.evaluate(() => {
      const box = document.querySelector('.cart_totals .emo-cart-assurance');
      const spans = [...(box?.querySelectorAll(':scope > span') || [])];
      if (!box || spans.length !== 3) return null;
      const rect = box.getBoundingClientRect();
      const style = getComputedStyle(box);
      const rows = spans.map((el) => {
        const r = el.getBoundingClientRect();
        return { top: Math.round(r.top), bottom: Math.round(r.bottom), height: Math.round(r.height) };
      });
      const gaps = rows.slice(1).map((row, i) => Math.round(row.top - rows[i].bottom));
      return {
        height: Math.round(rect.height),
        rows,
        gaps,
        maxGap: Math.max(...gaps),
        display: style.display,
        gap: style.gap,
        justifyContent: style.justifyContent,
        minHeight: style.minHeight,
        paddingTop: style.paddingTop,
        paddingBottom: style.paddingBottom
      };
    });
    checks.assurance = assurance;
    if (!assurance) failures.push('cart assurance block missing');
    else if (assurance.maxGap > 16 || assurance.height > 105) failures.push(`cart assurance spacing too large: ${JSON.stringify(assurance)}`);
    await page.screenshot({ path: 'qa/user-cart-mobile.png', fullPage: true });

    await go(page, '/finalizar-compra/', 250);
    await sleep(150);
    const checkout = await page.evaluate(() => {
      const column = document.querySelector('.emo-checkout-summary-column');
      const review = document.querySelector('#order_review');
      const card = column?.querySelector(':scope > .emo-checkout-status-card');
      const visibleText = (node) => {
        if (!node) return '';
        const r = node.getBoundingClientRect();
        const s = getComputedStyle(node);
        if (r.width <= 0 || r.height <= 0 || s.display === 'none' || s.visibility === 'hidden') return '';
        return (node.innerText || '').replace(/\s+/g, ' ').trim();
      };
      const cr = column?.getBoundingClientRect();
      const pseudo = column ? getComputedStyle(column, '::before').content : 'none';
      return {
        columnHeight: cr ? Math.round(cr.height) : 0,
        loading: !!review?.classList.contains('emo-order-review-loading'),
        pending: !!review?.classList.contains('emo-order-review-pending'),
        cartRows: review?.querySelectorAll('tr.cart_item').length || 0,
        cardExists: !!card,
        cardText: visibleText(card),
        reviewText: visibleText(review),
        pseudoContent: pseudo && pseudo !== 'none' ? pseudo : ''
      };
    });
    checks.checkout = checkout;
    const checkoutReadable = `${checkout?.cardText || ''} ${checkout?.reviewText || ''} ${checkout?.pseudoContent || ''}`.replace(/\s+/g, ' ').trim();
    if (checkout?.columnHeight > 100 && checkoutReadable.length < 18) {
      failures.push(`checkout summary surface is blank: ${JSON.stringify(checkout)}`);
    }
    await page.screenshot({ path: 'qa/user-checkout-mobile.png', fullPage: true });
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/user-request-visual-check.json', JSON.stringify({ failures, checks }, null, 2));
  if (failures.length) {
    console.error(failures.join('\n'));
    process.exitCode = 2;
  } else {
    console.log(`USER_REQUEST_VISUAL_OK ${JSON.stringify(checks)}`);
  }
})();
