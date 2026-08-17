const puppeteer = require('puppeteer-core');

const base = (process.env.PRODUCTION_SITEURL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const withQuery = (url, key) => `${url}${url.includes('?') ? '&' : '?'}${key}=${Date.now()}`;

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1100, deviceScaleFactor: 1 });
    await page.goto(withQuery(`${base}/blog/`, 'blogqa'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(900);

    const blog = await page.evaluate(() => {
      const rect = (el) => {
        const r = el.getBoundingClientRect();
        return { x: Math.round(r.x), w: Math.round(r.width), right: Math.round(r.right) };
      };
      const main = document.querySelector('main#primary.emo-journal');
      const hero = main?.querySelector('.emo-journal-hero');
      const grid = main?.querySelector('.emo-journal-grid');
      return {
        viewport: innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        marker248: Boolean(document.querySelector('#elmercado-blog-design-system-010248')),
        marker250: Boolean(document.querySelector('#elmercado-blog-design-force-010250')),
        guard250: Boolean(document.querySelector('#elmercado-blog-geometry-guard-010250')),
        main: main ? rect(main) : null,
        hero: hero ? rect(hero) : null,
        grid: grid ? rect(grid) : null,
        inlineStyle: main?.getAttribute('style') || '',
        links: main ? [...main.querySelectorAll('.emo-article-card__link')].map((a) => a.href).filter(Boolean) : [],
      };
    });

    if (!blog.marker248 || !blog.marker250 || !blog.guard250 || !blog.main || !blog.hero || !blog.grid) {
      throw new Error(`Blog structure ${JSON.stringify(blog)}`);
    }
    if (blog.scrollWidth > blog.viewport + 2) throw new Error(`Blog overflow ${JSON.stringify(blog)}`);
    if (blog.main.w < 1100 || blog.main.w > 1182) throw new Error(`Blog shell ${JSON.stringify(blog)}`);
    if (Math.abs(blog.main.w - blog.hero.w) > 3 || Math.abs(blog.main.w - blog.grid.w) > 3) {
      throw new Error(`Blog alignment ${JSON.stringify(blog)}`);
    }
    if (!blog.links.length) throw new Error('No article links in blog grid');

    let articleUrl = null;
    for (const url of blog.links.slice(0, 12)) {
      await page.goto(withQuery(url, 'productqa'), { waitUntil: 'networkidle2', timeout: 60000 });
      await sleep(450);
      const hasProducts = await page.evaluate(() => Boolean(document.querySelector(
        'main#primary.emo-article-page .emo-article-content .woocommerce ul.products, main#primary.emo-article-page .emo-article-content .wc-block-grid__products, main#primary.emo-article-page .emo-article-content .wc-block-product-template'
      )));
      if (hasProducts) {
        articleUrl = url;
        break;
      }
    }
    if (!articleUrl) throw new Error(`No article with a product grid found among ${blog.links.length} blog links`);

    await page.goto(withQuery(articleUrl, 'articleqa'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(900);

    const article = await page.evaluate(() => {
      const rect = (el) => {
        const r = el.getBoundingClientRect();
        return { x: Math.round(r.x), w: Math.round(r.width), right: Math.round(r.right) };
      };
      const main = document.querySelector('main#primary.emo-article-page');
      const hero = main?.querySelector('.emo-article-hero');
      const content = main?.querySelector('.emo-article-content');
      const grid = content?.querySelector('.woocommerce ul.products, .wc-block-grid__products, .wc-block-product-template');
      const wide = grid?.closest('.woocommerce, .wc-block-grid, .wp-block-woocommerce-product-collection') || grid;
      const cards = grid ? [...grid.children].filter((el) => el.getBoundingClientRect().width > 0).slice(0, 8).map(rect) : [];
      return {
        viewport: innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        main: main ? rect(main) : null,
        hero: hero ? rect(hero) : null,
        content: content ? rect(content) : null,
        wide: wide ? rect(wide) : null,
        cards,
      };
    });

    if (!article.main || !article.hero || !article.content || !article.wide || !article.cards.length) {
      throw new Error(`Article structure ${JSON.stringify(article)}`);
    }
    if (article.scrollWidth > article.viewport + 2) throw new Error(`Article overflow ${JSON.stringify(article)}`);
    if (article.main.w < 1100 || article.main.w > 1182 || Math.abs(article.main.w - article.hero.w) > 3) {
      throw new Error(`Article shell ${JSON.stringify(article)}`);
    }
    if (article.content.w < 650 || article.content.w > 802) throw new Error(`Reading width ${JSON.stringify(article)}`);
    if (article.wide.w > 1042 || article.wide.x < 120 || article.wide.right > 1320) {
      throw new Error(`Product block ${JSON.stringify(article)}`);
    }
    if (article.cards.some((card) => card.w < 220 || card.w > 360 || card.x < 100 || card.right > 1340)) {
      throw new Error(`Product cards ${JSON.stringify(article)}`);
    }

    await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1 });
    await page.goto(withQuery(articleUrl, 'mobileqa'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(800);

    const mobile = await page.evaluate(() => {
      const rect = (el) => {
        const r = el.getBoundingClientRect();
        return { x: Math.round(r.x), w: Math.round(r.width), right: Math.round(r.right) };
      };
      const main = document.querySelector('main#primary.emo-article-page');
      const cards = main ? [...main.querySelectorAll(
        '.emo-article-content .woocommerce ul.products > li.product, .emo-article-content .wc-block-grid__product, .emo-article-content .wc-block-product'
      )].filter((el) => el.getBoundingClientRect().width > 0).slice(0, 5).map(rect) : [];
      return {
        viewport: innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        main: main ? rect(main) : null,
        cards,
      };
    });

    if (!mobile.main || mobile.scrollWidth > mobile.viewport + 2) throw new Error(`Mobile overflow ${JSON.stringify(mobile)}`);
    if (mobile.main.x < 8 || mobile.main.right > 382) throw new Error(`Mobile shell ${JSON.stringify(mobile)}`);
    if (!mobile.cards.length || mobile.cards.some((card) => card.x < 10 || card.right > 380 || card.w > 360)) {
      throw new Error(`Mobile product cards ${JSON.stringify(mobile)}`);
    }

    console.log('PRODUCTION_BLOG_DESIGN_010250_BROWSER_OK', JSON.stringify({ blog, articleUrl, article, mobile }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
