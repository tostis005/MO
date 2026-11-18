/**
 * Carrito real con producto, cupón y totales: Chromium, Firefox y WebKit (Safari).
 * Ejecución: SITE_URL=... TOLE_PRODUCT_ID=... node tests/cart-cross-browser-20261118.cjs
 */
'use strict';

const fs = require('node:fs');
const path = require('node:path');
const { chromium, firefox, webkit } = require('playwright');

const base = (process.env.SITE_URL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');
const widths = [320, 360, 375, 390, 430, 600, 768, 834, 991, 1024, 1280, 1440, 1920];
const results = [];
const out = path.resolve(process.cwd(), 'cart-browser-artifacts');
fs.mkdirSync(out, { recursive: true });

async function findProductId(page) {
  const configured = String(process.env.TOLE_PRODUCT_ID || '').trim();
  if (/^\d+$/.test(configured)) return configured;
  await page.goto(base + '/tienda/?cart_browser_probe=1', { waitUntil: 'domcontentloaded', timeout: 90000 });
  const id = await page.evaluate(() => {
    const links = Array.from(document.querySelectorAll(
      'a.add_to_cart_button[data-product_id], a.ajax_add_to_cart[data-product_id], button.add_to_cart_button[data-product_id]'
    ));
    const first = links.find(link => /^\d+$/.test(link.dataset.product_id || ''));
    return first?.dataset.product_id || '';
  });
  if (!/^\d+$/.test(id)) throw new Error('No simple purchasable product for cross-browser cart QA');
  return id;
}

const getGeometry = () => {
  const box = (selector) => {
    const el = document.querySelector(selector);
    if (!el) return null;
    const s = getComputedStyle(el);
    const r = el.getBoundingClientRect();
    return { x: r.x, right: r.right, y: r.y, bottom: r.bottom, width: r.width, height: r.height,
      display: s.display, visibility: s.visibility, opacity: Number(s.opacity) };
  };
  const form = box('.emo-cart-layout > form.woocommerce-cart-form');
  const coupon = box('.woocommerce-cart-form td.actions .coupon');
  const couponInput = box('.woocommerce-cart-form #coupon_code');
  const couponButton = box('.woocommerce-cart-form .coupon button[name="apply_coupon"], .woocommerce-cart-form .coupon input[name="apply_coupon"]');
  const totals = box('.emo-cart-layout .cart_totals');
  const cross = box('.emo-cart-layout > .cross-sells');
  const layout = box('.emo-cart-layout');
  const cartTable = box('.woocommerce-cart-form table.shop_table');
  const rows = document.querySelectorAll('.woocommerce-cart-form tr.cart_item, .woocommerce-cart-form tr.woocommerce-cart-form__cart-item').length;
  return { viewport: innerWidth, docWidth: document.documentElement.scrollWidth, bodyWidth: document.body.scrollWidth,
    form, coupon, couponInput, couponButton, totals, cross, layout, cartTable, rows };
};

function assertGeometry(g, engine, width) {
  const label = `${engine}@${width}px`;
  const must = ['form', 'coupon', 'couponInput', 'couponButton', 'totals', 'layout', 'cartTable'];
  for (const key of must) {
    if (!g[key] || g[key].width < 1 || g[key].height < 1) throw new Error(`${label} missing/invisible ${key}: ${JSON.stringify(g)}`);
    if (g[key].display === 'none' || g[key].visibility === 'hidden' || g[key].opacity === 0) {
      throw new Error(`${label} hidden ${key}: ${JSON.stringify(g)}`);
    }
  }
  if (g.rows < 1) throw new Error(`${label} no cart item; test must exercise real nonempty cart`);
  if (g.docWidth > width + 5 || g.bodyWidth > width + 5) {
    throw new Error(`${label} horizontal page overflow: ${JSON.stringify(g)}`);
  }
  for (const key of ['form', 'coupon', 'couponInput', 'couponButton', 'totals', 'layout', 'cartTable', 'cross']) {
    const b = g[key];
    if (b && (b.x < -4 || b.right > width + 4 || b.width > width + 4)) {
      throw new Error(`${label} clipped/overflowing ${key}: ${JSON.stringify(g)}`);
    }
  }
  if (width <= 991 && g.totals.y < g.form.bottom - 5) {
    throw new Error(`${label} totals overlap mobile/tablet form: ${JSON.stringify(g)}`);
  }
  if (width >= 992 && g.totals.x < g.form.right - 4) {
    throw new Error(`${label} totals overlap desktop form: ${JSON.stringify(g)}`);
  }
  if (width <= 767 && g.couponInput.width < Math.min(g.form.width - 60, 170)) {
    throw new Error(`${label} coupon input too narrow: ${JSON.stringify(g)}`);
  }
}

(async () => {
  for (const [engine, launcher] of Object.entries({ chromium, firefox, webkit })) {
    const browser = await launcher.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 390, height: 900 }, locale: 'es-ES' });
    const page = await context.newPage();
    let lastWidth = 390;
    try {
      const product = await findProductId(page);
      await page.goto(base + '/?add-to-cart=' + encodeURIComponent(product) + '&quantity=1&cart_browser_probe=' + Date.now(), {
        waitUntil: 'domcontentloaded', timeout: 90000,
      });
      await page.goto(base + '/carrito/?cart_browser_probe=' + Date.now(), {
        waitUntil: 'domcontentloaded', timeout: 90000,
      });
      await page.locator('.emo-cart-layout form.woocommerce-cart-form').waitFor({ timeout: 30000 });
      for (const width of widths) {
        lastWidth = width;
        await page.setViewportSize({ width, height: width >= 992 ? 1000 : 900 });
        await page.waitForTimeout(160);
        const g = await page.evaluate(getGeometry);
        assertGeometry(g, engine, width);
        results.push({ engine, width, rowCount: g.rows, couponWidth: Math.round(g.couponInput.width),
          formWidth: Math.round(g.form.width), totalsWidth: Math.round(g.totals.width) });
      }
      process.stdout.write(`OK ${engine}: ${widths.length} sizes, cart with product and coupon\n`);
    } catch (error) {
      const file = path.join(out, `${engine}-${lastWidth}.png`);
      try { await page.screenshot({ path: file, fullPage: true, timeout: 12000 }); } catch (_) {}
      throw new Error(`${engine}: ${error.stack || error}\nScreenshot: ${file}`);
    } finally {
      await context.close();
      await browser.close();
    }
  }
  fs.writeFileSync(path.join(out, 'results.json'), JSON.stringify(results, null, 2));
  process.stdout.write(`CART_CROSS_BROWSER_OK: ${results.length} checks\n`);
})().catch(err => {
  console.error(err);
  process.exitCode = 1;
});
