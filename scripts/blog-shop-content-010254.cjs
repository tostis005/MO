const puppeteer = require('puppeteer-core');

const base = (process.env.PRODUCTION_SITEURL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');
const phrase = 'Una selección de productos con procedencia clara para acercar el origen a tu mesa de una forma más directa.';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const bust = (path) => `${base}${path}${path.includes('?') ? '&' : '?'}mdoqa=${Date.now()}`;

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1100, deviceScaleFactor: 1 });

    await page.goto(bust('/blog/'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(750);
    const blogCards = await page.evaluate(() => {
      const normalize = (text) => (text || '')
        .trim()
        .toLocaleLowerCase('es')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
      return [...document.querySelectorAll('.emo-journal-grid .emo-article-card')].map((card) => {
        const title = card.querySelector('h2')?.textContent?.trim() || '';
        const excerpt = card.querySelector('.emo-article-card__body > p')?.textContent?.trim() || '';
        return {
          title,
          excerpt,
          startsWithIntroduction: normalize(excerpt).startsWith('introduccion'),
        };
      });
    });
    if (blogCards.length < 2 || blogCards.some((card) => !card.excerpt || card.startsWithIntroduction)) {
      throw new Error(`Blog card excerpt QA failed ${JSON.stringify(blogCards)}`);
    }

    for (const path of ['/tienda/', '/product-category/embutidos-y-curados/']) {
      await page.goto(bust(path), { waitUntil: 'networkidle2', timeout: 60000 });
      await sleep(650);
      const catalog = await page.evaluate((unwanted) => {
        const leads = [...document.querySelectorAll('.emo-shop-lead')];
        const visible = leads.filter((el) => {
          const r = el.getBoundingClientRect();
          const s = getComputedStyle(el);
          return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
        });
        return {
          path: location.pathname,
          leadCount: leads.length,
          visibleLeadCount: visible.length,
          hasPhraseInVisibleText: (document.body.innerText || '').includes(unwanted),
          markerCss: Boolean(document.querySelector('#mdo-hide-catalog-lead-010254')),
          markerJs: Boolean(document.querySelector('#mdo-remove-catalog-lead-010254')),
        };
      }, phrase);
      if (catalog.visibleLeadCount !== 0 || catalog.hasPhraseInVisibleText) {
        throw new Error(`Catalog intro still visible ${JSON.stringify(catalog)}`);
      }
      if (path === '/tienda/' && (!catalog.markerCss || !catalog.markerJs)) {
        throw new Error(`Catalog hotfix markers missing on shop ${JSON.stringify(catalog)}`);
      }
    }

    await page.goto(bust('/jamon-iberico/'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(1000);
    const jamon = await page.evaluate(() => {
      const normalize = (text) => (text || '').trim().toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      const content = document.querySelector('.emo-article-content');
      if (!content) return null;
      const headings = [...content.querySelectorAll('h1,h2,h3,h4,h5,h6')];
      const intro = headings.filter((h) => normalize(h.textContent) === 'introduccion');
      const embutidosHeading = headings.find((h) => normalize(h.textContent).includes('embutidos')) || null;
      let grid = null;
      if (embutidosHeading) {
        const headingBottom = embutidosHeading.getBoundingClientRect().bottom;
        const grids = [...content.querySelectorAll('.woocommerce ul.products, .wc-block-grid__products, .wc-block-product-template')];
        grid = grids.find((candidate) => candidate.getBoundingClientRect().top >= headingBottom - 2) || null;
      }
      const cards = grid ? [...grid.querySelectorAll(':scope > li.product, :scope > .wc-block-grid__product, :scope > .wc-block-product')].filter((card) => {
        const r = card.getBoundingClientRect();
        return r.width > 0 && r.height > 0;
      }) : [];
      return {
        introHeadings: intro.length,
        hasEmbutidosHeading: Boolean(embutidosHeading),
        productCountAfterEmbutidos: cards.length,
        headingText: embutidosHeading?.textContent?.trim() || '',
        gridTop: grid ? Math.round(grid.getBoundingClientRect().top) : null,
      };
    });
    if (!jamon || jamon.introHeadings !== 0 || !jamon.hasEmbutidosHeading || jamon.productCountAfterEmbutidos < 4) {
      throw new Error(`Jamón article QA failed ${JSON.stringify(jamon)}`);
    }

    await page.goto(bust('/aceite-de-oliva-virgen-extra/'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(750);
    const aceite = await page.evaluate(() => {
      const normalize = (text) => (text || '').trim().toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      const content = document.querySelector('.emo-article-content');
      const headings = content ? [...content.querySelectorAll('h1,h2,h3,h4,h5,h6')] : [];
      return {
        hasContent: Boolean(content),
        introHeadings: headings.filter((h) => normalize(h.textContent) === 'introduccion').length,
      };
    });
    if (!aceite.hasContent || aceite.introHeadings !== 0) {
      throw new Error(`Aceite article QA failed ${JSON.stringify(aceite)}`);
    }

    console.log(JSON.stringify({ ok: true, blogCards, jamon, aceite }, null, 2));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error.stack || error.message || String(error));
  process.exit(1);
});
