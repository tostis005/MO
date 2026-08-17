const puppeteer = require('puppeteer-core');

const base = (process.env.PRODUCTION_SITEURL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1100, deviceScaleFactor: 1 });
    await page.goto(`${base}/blog/?qa=${Date.now()}`, { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(800);

    const blog = await page.evaluate(() => {
      const rect = (element) => {
        const value = element.getBoundingClientRect();
        return { x: value.x, w: value.width, right: value.right };
      };
      const main = document.querySelector('main#primary.emo-journal');
      const hero = main?.querySelector('.emo-journal-hero') || null;
      const grid = main?.querySelector('.emo-journal-grid') || null;
      return {
        scrollWidth: document.documentElement.scrollWidth,
        viewport: innerWidth,
        bodyClass: document.body.className,
        journalMatches: [...document.querySelectorAll('.emo-journal')].map((element) => ({
          tag: element.tagName,
          id: element.id,
          className: element.className?.toString() || '',
          rect: rect(element),
        })),
        main: main ? rect(main) : null,
        hero: hero ? rect(hero) : null,
        grid: grid ? rect(grid) : null,
        marker248: Boolean(document.querySelector('#elmercado-blog-design-system-010248')),
        marker250: Boolean(document.querySelector('#elmercado-blog-design-force-010250')),
        links: main
          ? [...main.querySelectorAll('.emo-article-card__link')]
              .map((link) => link.href)
              .filter(Boolean)
              .slice(0, 20)
          : [],
      };
    });

    if (!blog.marker248 || !blog.marker250 || !blog.main || !blog.hero || !blog.grid) {
      throw new Error(`Blog structure ${JSON.stringify(blog)}`);
    }
    if (blog.scrollWidth > blog.viewport + 2) {
      throw new Error(`Blog horizontal overflow ${JSON.stringify(blog)}`);
    }
    if (blog.main.w > 1182 || blog.main.w < 1100) {
      throw new Error(`Blog shell width ${JSON.stringify(blog)}`);
    }
    if (Math.abs(blog.hero.w - blog.grid.w) > 3 || Math.abs(blog.main.w - blog.grid.w) > 3) {
      throw new Error(`Blog width mismatch ${JSON.stringify(blog)}`);
    }

    let articleUrl = blog.links[0];
    if (!articleUrl) throw new Error('No blog article link found');
    let productArticle = null;

    for (const url of blog.links) {
      await page.goto(`${url}${url.includes('?') ? '&' : '?'}qa=${Date.now()}`, {
        waitUntil: 'networkidle2',
        timeout: 60000,
      });
      await sleep(350);
      const hasProducts = await page.evaluate(() => Boolean(document.querySelector(
        'main#primary.emo-article-page .emo-article-content .woocommerce ul.products, main#primary.emo-article-page .emo-article-content .wc-block-grid__products, main#primary.emo-article-page .emo-article-content .wc-block-product-template'
      )));
      if (hasProducts) {
        productArticle = url;
        break;
      }
    }

    articleUrl = productArticle || articleUrl;
    await page.goto(`${articleUrl}${articleUrl.includes('?') ? '&' : '?'}qa=${Date.now()}`, {
      waitUntil: 'networkidle2',
      timeout: 60000,
    });
    await sleep(800);

    const article = await page.evaluate(() => {
      const rect = (element) => {
        const value = element.getBoundingClientRect();
        return { x: value.x, w: value.width, right: value.right };
      };
      const main = document.querySelector('main#primary.emo-article-page');
      const hero = main?.querySelector('.emo-article-hero') || null;
      const content = main?.querySelector('.emo-article-content') || null;
      const grid = content?.querySelector(
        '.woocommerce ul.products, .wc-block-grid__products, .wc-block-product-template'
      ) || null;
      const wide = grid?.closest('.woocommerce,.wc-block-grid,.wp-block-woocommerce-product-collection') || grid;
      const cards = grid
        ? [...grid.children]
            .filter((element) => element.getBoundingClientRect().width > 0)
            .slice(0, 8)
            .map(rect)
        : [];
      return {
        scrollWidth: document.documentElement.scrollWidth,
        viewport: innerWidth,
        articlePageMatches: [...document.querySelectorAll('.emo-article-page')].map((element) => ({
          tag: element.tagName,
          id: element.id,
          className: element.className?.toString() || '',
          rect: rect(element),
        })),
        marker248: Boolean(document.querySelector('#elmercado-blog-design-system-010248')),
        marker250: Boolean(document.querySelector('#elmercado-blog-design-force-010250')),
        main: main ? rect(main) : null,
        hero: hero ? rect(hero) : null,
        content: content ? rect(content) : null,
        wide: wide ? rect(wide) : null,
        cards,
      };
    });

    if (!article.marker248 || !article.marker250 || !article.main || !article.hero || !article.content) {
      throw new Error(`Article structure ${JSON.stringify(article)}`);
    }
    if (article.scrollWidth > article.viewport + 2) {
      throw new Error(`Article horizontal overflow ${JSON.stringify(article)}`);
    }
    if (Math.abs(article.main.w - article.hero.w) > 3) {
      throw new Error(`Article hero mismatch ${JSON.stringify(article)}`);
    }
    if (article.content.w > 802 || article.content.w < 650) {
      throw new Error(`Reading width ${JSON.stringify(article.content)}`);
    }
    if (article.wide) {
      if (article.wide.w > 1042 || article.wide.x < 120 || article.wide.right > 1320) {
        throw new Error(`Product block overflow ${JSON.stringify(article.wide)}`);
      }
      if (article.cards.some((card) => card.x < 100 || card.right > 1340 || card.w > 360 || card.w < 220)) {
        throw new Error(`Product card geometry ${JSON.stringify(article.cards)}`);
      }
    }

    await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1 });
    await page.goto(`${articleUrl}${articleUrl.includes('?') ? '&' : '?'}mobileqa=${Date.now()}`, {
      waitUntil: 'networkidle2',
      timeout: 60000,
    });
    await sleep(700);

    const mobile = await page.evaluate(() => {
      const rect = (element) => {
        const value = element.getBoundingClientRect();
        return { x: value.x, w: value.width, right: value.right };
      };
      const main = document.querySelector('main#primary.emo-article-page');
      const content = main?.querySelector('.emo-article-content') || null;
      const cards = main
        ? [...main.querySelectorAll(
            '.emo-article-content .woocommerce ul.products > li.product, .emo-article-content .wc-block-grid__product, .emo-article-content .wc-block-product'
          )]
            .filter((element) => element.getBoundingClientRect().width > 0)
            .slice(0, 5)
            .map(rect)
        : [];
      return {
        scrollWidth: document.documentElement.scrollWidth,
        viewport: innerWidth,
        main: main ? rect(main) : null,
        content: content ? rect(content) : null,
        cards,
      };
    });

    if (mobile.scrollWidth > mobile.viewport + 2) {
      throw new Error(`Mobile horizontal overflow ${JSON.stringify(mobile)}`);
    }
    if (!mobile.main || mobile.main.x < 8 || mobile.main.right > 382) {
      throw new Error(`Mobile shell ${JSON.stringify(mobile)}`);
    }
    if (mobile.cards.some((card) => card.x < 10 || card.right > 380 || card.w > 360)) {
      throw new Error(`Mobile products ${JSON.stringify(mobile.cards)}`);
    }

    console.log('PRODUCTION_BLOG_DESIGN_010250_BROWSER_OK', JSON.stringify({
      blog,
      articleUrl,
      productArticle: Boolean(productArticle),
      article,
      mobile,
    }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
