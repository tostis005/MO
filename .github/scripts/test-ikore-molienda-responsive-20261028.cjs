/* Browser regression: coffee grind cards occupy the full variation form at responsive widths. */
const { chromium } = require('playwright');

const root = 'https://www.elmercadodeorigen.com/producto/';
const routes = [
  'cafe-ikore-kofi-typica-mejorado',
  'pack-cafe-esencia-de-costa-rica'
];
const widths = [390, 700, 719, 721, 768, 900, 1024, 1280, 1440];
(async () => {
  const browser = await chromium.launch({ headless: true, channel: 'chrome', args: ['--no-sandbox'] });
  try {
    for (const route of routes) {
      const page = await browser.newPage({ viewport: { width: 1280, height: 900 }, deviceScaleFactor: 1 });
      const response = await page.goto(root + route + '/?grind_layout_check=20261028-2', {
        waitUntil: 'domcontentloaded', timeout: 60000
      });
      if (!response || response.status() !== 200) throw new Error(route + ' page HTTP failure');
      await page.waitForSelector('form.variations_form > .emdo-ikore-grind .emdo-ikore-card', { timeout: 30000 });
      for (const width of widths) {
        await page.setViewportSize({ width, height: 900 });
        const state = await page.evaluate(() => {
          const form = document.querySelector('form.variations_form');
          const wrap = form && form.querySelector(':scope > .emdo-ikore-grind');
          const grid = wrap && wrap.querySelector('.emdo-ikore-grid');
          const native = form && form.querySelector('select[name*="molienda"]');
          const row = form && form.querySelector('tr.emdo-ikore-grind-row');
          if (!form || !wrap || !grid || !native || !row) return { error: 'missing native/form/grid/row' };
          const formRect = form.getBoundingClientRect();
          const wrapRect = wrap.getBoundingClientRect();
          const gridRect = grid.getBoundingClientRect();
          const cardRects = [...grid.querySelectorAll('.emdo-ikore-card')].map(x => x.getBoundingClientRect());
          return {
            formWidth: formRect.width, wrapWidth: wrapRect.width, gridWidth: gridRect.width,
            gapLeft: gridRect.left - formRect.left, gapRight: formRect.right - gridRect.right,
            cardCount: cardRects.length, cardWidth: cardRects[0]?.width || 0,
            hiddenRow: getComputedStyle(row).display === 'none',
            columns: getComputedStyle(grid).gridTemplateColumns,
            scrollOverhang: document.documentElement.scrollWidth - document.documentElement.clientWidth
          };
        });
        if (state.error || state.formWidth < 180 || state.cardCount !== 7 || !state.hiddenRow ||
            Math.abs(state.gridWidth - state.formWidth) > 5 || Math.abs(state.wrapWidth - state.formWidth) > 5 ||
            Math.abs(state.gapLeft) > 6 || Math.abs(state.gapRight) > 6 ||
            state.cardWidth < 70 || state.scrollOverhang > 5) {
          throw new Error(route + ' ' + width + 'px: ' + JSON.stringify(state));
        }
        console.log('GRIND_LAYOUT_OK', route, width + 'px', JSON.stringify(state));
      }
      const italian = page.locator('.emdo-ikore-grid button.emdo-ikore-card', { hasText: 'Italiana' }).first();
      await italian.click();
      await page.waitForFunction(() => {
        const s = document.querySelector('form.variations_form select[name*="molienda"]');
        return s && s.value.toLowerCase().includes('italiana');
      }, { timeout: 12000 });
      const state = await italian.getAttribute('aria-pressed');
      if (state !== 'true') throw new Error('WooCommerce option selection did not update');
      console.log('GRIND_WOOCOMMERCE_SELECTION_OK', route);
      await page.close();
    }
    console.log('COFFEE_GRIND_RESPONSIVE_BROWSER_VERIFIED');
  } finally {
    await browser.close();
  }
})().catch(err => { console.error(err.stack || err.message); process.exitCode = 1; });
