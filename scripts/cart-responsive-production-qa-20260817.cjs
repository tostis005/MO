const puppeteer = require('puppeteer-core');

const base = (process.env.PRODUCTION_SITEURL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');
const productId = String(process.env.TOLE_PRODUCT_ID || '').trim();
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

if (!/^\d+$/.test(productId)) {
  throw new Error(`Invalid TOLE_PRODUCT_ID: ${productId}`);
}

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1024, height: 1000, deviceScaleFactor: 1 });

    const stamp = Date.now();
    await page.goto(`${base}/?add-to-cart=${encodeURIComponent(productId)}&quantity=1&cartqa=${stamp}`, {
      waitUntil: 'networkidle2',
      timeout: 60000,
    });
    await sleep(700);

    await page.goto(`${base}/carrito/?cartqa=${stamp}`, { waitUntil: 'networkidle2', timeout: 60000 });
    if (page.url().includes('/carrito/') === false && page.url().includes('/cart/') === false) {
      await page.goto(`${base}/cart/?cartqa=${stamp}`, { waitUntil: 'networkidle2', timeout: 60000 });
    }
    await sleep(900);

    const initial = await page.evaluate(() => ({
      url: location.href,
      bodyClass: document.body.className,
      hasCartForm: Boolean(document.querySelector('.woocommerce-cart-form')),
      itemCount: document.querySelectorAll('.woocommerce-cart-form__cart-item, tr.cart_item').length,
      notices: [...document.querySelectorAll('.woocommerce-message,.woocommerce-info,.woocommerce-error')]
        .filter((el) => el.getBoundingClientRect().width > 0 && el.getBoundingClientRect().height > 0)
        .map((el) => (el.textContent || '').replace(/\s+/g, ' ').trim()),
    }));

    if (!initial.hasCartForm || initial.itemCount < 1) {
      throw new Error(`Tolecarnes product was not added to cart ${JSON.stringify(initial)}`);
    }

    const noticeSignal = initial.notices.some((text) => /tole|mínim|minim|pedido|order/i.test(text));
    if (!noticeSignal) {
      throw new Error(`Expected Tolecarnes/minimum-order cart notice not found ${JSON.stringify(initial)}`);
    }

    const widths = [390, 740, 760, 767, 768, 800, 900, 1024];
    const results = [];

    for (const width of widths) {
      await page.setViewport({ width, height: 1000, deviceScaleFactor: 1 });
      await sleep(220);

      const geometry = await page.evaluate(() => {
        const rect = (el) => {
          if (!el) return null;
          const r = el.getBoundingClientRect();
          return {
            x: Math.round(r.x * 10) / 10,
            width: Math.round(r.width * 10) / 10,
            right: Math.round(r.right * 10) / 10,
            height: Math.round(r.height * 10) / 10,
          };
        };
        const visible = (el) => el && el.getBoundingClientRect().width > 0 && el.getBoundingClientRect().height > 0;
        const notices = [...document.querySelectorAll('.woocommerce-message,.woocommerce-info,.woocommerce-error')].filter(visible);
        const signal = notices.find((el) => /tole|mínim|minim|pedido|order/i.test(el.textContent || '')) || notices[0] || null;
        const button = signal?.querySelector('.button') || null;
        const cartForm = document.querySelector('.woocommerce-cart-form');
        const layout = document.querySelector('.emo-cart-layout');
        const totals = document.querySelector('.cart_totals');
        return {
          viewport: innerWidth,
          scrollWidth: document.documentElement.scrollWidth,
          bodyScrollWidth: document.body.scrollWidth,
          notice: rect(signal),
          noticeText: signal ? (signal.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 260) : '',
          button: rect(button),
          buttonFloat: button ? getComputedStyle(button).float : null,
          buttonDisplay: button ? getComputedStyle(button).display : null,
          cartForm: rect(cartForm),
          layout: rect(layout),
          totals: rect(totals),
        };
      });

      if (geometry.scrollWidth > width + 2 || geometry.bodyScrollWidth > width + 2) {
        throw new Error(`Horizontal overflow at ${width}px ${JSON.stringify(geometry)}`);
      }
      for (const [name, box] of Object.entries({ notice: geometry.notice, cartForm: geometry.cartForm, layout: geometry.layout, totals: geometry.totals })) {
        if (!box) continue;
        if (box.x < -2 || box.right > width + 2 || box.width > width + 2) {
          throw new Error(`${name} outside viewport at ${width}px ${JSON.stringify(geometry)}`);
        }
      }
      if (!geometry.notice) {
        throw new Error(`Notice disappeared at ${width}px ${JSON.stringify(geometry)}`);
      }
      if (width <= 900 && geometry.button) {
        if (geometry.buttonFloat !== 'none') {
          throw new Error(`Notice button still floated at ${width}px ${JSON.stringify(geometry)}`);
        }
        if (geometry.button.width < geometry.notice.width * 0.82) {
          throw new Error(`Notice button not stacked/full width at ${width}px ${JSON.stringify(geometry)}`);
        }
      }
      results.push(geometry);
    }

    console.log('PRODUCTION_TOLECARNES_CART_RESPONSIVE_OK', JSON.stringify({ productId, initial, results }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
