const puppeteer = require('puppeteer-core');

const base = (process.env.PRODUCTION_SITEURL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const withQuery = (url, key) => `${url}${url.includes('?') ? '&' : '?'}${key}=${Date.now()}`;
const sameSignature = (a, b, keys) => keys.every((key) => (a?.[key] || '') === (b?.[key] || ''));

const visualKeys = ['backgroundColor', 'borderRadius', 'boxShadow', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft'];
const imageKeys = ['borderRadius', 'objectFit', 'display'];
const titleKeys = ['fontSize', 'lineHeight', 'fontWeight', 'color', 'marginTop', 'marginBottom'];
const sellerKeys = ['display', 'alignItems', 'fontSize', 'lineHeight', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft'];
const sellerImageKeys = ['display', 'width', 'height', 'borderRadius', 'objectFit'];

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1100, deviceScaleFactor: 1 });

    /* Reference: the real storefront card at the same desktop viewport. */
    await page.goto(withQuery(`${base}/tienda/`, 'shopqa'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(1000);

    const shopCard = await page.evaluate(() => {
      const visible = (el) => el && el.getBoundingClientRect().width > 0 && el.getBoundingClientRect().height > 0;
      const cards = [...document.querySelectorAll('ul.products > li.product')].filter(visible);
      const card = cards.find((item) => item.querySelector('.wcfmmp_sold_by_container, .wcfmmp_sold_by_container_advanced')) || cards[0] || null;
      if (!card) return null;

      const visual = card.querySelector('.product-loop-wrapper') || card;
      const image = card.querySelector('img.product-loop-image, .woocommerce-loop-product__link > img, img.attachment-woocommerce_thumbnail');
      const title = card.querySelector('.woocommerce-loop-product__title, .product-title, h2, h3');
      const seller = card.querySelector('.wcfmmp_sold_by_container, .wcfmmp_sold_by_container_advanced');
      const sellerImage = seller?.querySelector('img') || null;
      const style = (el, keys) => {
        if (!el) return null;
        const s = getComputedStyle(el);
        return Object.fromEntries(keys.map((key) => [key, s[key]]));
      };
      const rect = (el) => {
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height), right: Math.round(r.right) };
      };

      return {
        cardCount: cards.length,
        cardClass: card.className,
        visual: style(visual, ['backgroundColor', 'borderRadius', 'boxShadow', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft']),
        image: style(image, ['borderRadius', 'objectFit', 'display']),
        title: style(title, ['fontSize', 'lineHeight', 'fontWeight', 'color', 'marginTop', 'marginBottom']),
        seller: style(seller, ['display', 'alignItems', 'fontSize', 'lineHeight', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft']),
        sellerImage: style(sellerImage, ['display', 'width', 'height', 'borderRadius', 'objectFit']),
        sellerImageRect: rect(sellerImage),
        hasSeller: Boolean(seller),
        sellerImageCount: seller ? seller.querySelectorAll('img').length : 0,
      };
    });

    if (!shopCard || !shopCard.cardCount) throw new Error(`Storefront product reference unavailable ${JSON.stringify(shopCard)}`);
    if (!shopCard.hasSeller || shopCard.sellerImageCount < 1) {
      throw new Error(`Storefront seller image reference unavailable ${JSON.stringify(shopCard)}`);
    }

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
    await sleep(1000);

    const article = await page.evaluate(() => {
      const rect = (el) => {
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height), right: Math.round(r.right) };
      };
      const style = (el, keys) => {
        if (!el) return null;
        const s = getComputedStyle(el);
        return Object.fromEntries(keys.map((key) => [key, s[key]]));
      };
      const main = document.querySelector('main#primary.emo-article-page');
      const hero = main?.querySelector('.emo-article-hero');
      const content = main?.querySelector('.emo-article-content');
      const grid = content?.querySelector('.woocommerce ul.products, .wc-block-grid__products, .wc-block-product-template');
      const wide = grid?.closest('.woocommerce, .wc-block-grid, .wp-block-woocommerce-product-collection') || grid;
      const cards = grid ? [...grid.children].filter((el) => el.getBoundingClientRect().width > 0) : [];
      const card = cards.find((item) => item.querySelector('.wcfmmp_sold_by_container, .wcfmmp_sold_by_container_advanced')) || cards[0] || null;
      const visual = card?.querySelector('.product-loop-wrapper') || card;
      const image = card?.querySelector('img.product-loop-image, .woocommerce-loop-product__link > img, img.attachment-woocommerce_thumbnail') || null;
      const title = card?.querySelector('.woocommerce-loop-product__title, .product-title, h2, h3') || null;
      const seller = card?.querySelector('.wcfmmp_sold_by_container, .wcfmmp_sold_by_container_advanced') || null;
      const sellerImage = seller?.querySelector('img') || null;

      return {
        viewport: innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        main: main ? rect(main) : null,
        hero: hero ? rect(hero) : null,
        content: content ? rect(content) : null,
        wide: wide ? rect(wide) : null,
        cards: cards.slice(0, 8).map(rect),
        hasLead: Boolean(main?.querySelector('.emo-article-hero__lead')),
        product: card ? {
          visual: style(visual, ['backgroundColor', 'borderRadius', 'boxShadow', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft']),
          image: style(image, ['borderRadius', 'objectFit', 'display']),
          title: style(title, ['fontSize', 'lineHeight', 'fontWeight', 'color', 'marginTop', 'marginBottom']),
          seller: style(seller, ['display', 'alignItems', 'fontSize', 'lineHeight', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft']),
          sellerImage: style(sellerImage, ['display', 'width', 'height', 'borderRadius', 'objectFit']),
          sellerImageRect: rect(sellerImage),
          hasSeller: Boolean(seller),
          sellerImageCount: seller ? seller.querySelectorAll('img').length : 0,
        } : null,
      };
    });

    if (!article.main || !article.hero || !article.content || !article.wide || !article.cards.length || !article.product) {
      throw new Error(`Article structure ${JSON.stringify(article)}`);
    }
    if (article.hasLead) throw new Error(`Repeated article hero introduction still present ${JSON.stringify(article)}`);
    if (article.scrollWidth > article.viewport + 2) throw new Error(`Article overflow ${JSON.stringify(article)}`);
    if (article.main.w < 1100 || article.main.w > 1182 || Math.abs(article.main.w - article.hero.w) > 3) {
      throw new Error(`Article shell ${JSON.stringify(article)}`);
    }
    if (article.content.w < 898 || article.content.w > 902) throw new Error(`Reading width ${JSON.stringify(article)}`);
    if (article.wide.w > 1042 || article.wide.x < 120 || article.wide.right > 1320) {
      throw new Error(`Product block ${JSON.stringify(article)}`);
    }
    if (article.cards.some((card) => card.w < 220 || card.w > 360 || card.x < 100 || card.right > 1340)) {
      throw new Error(`Product cards ${JSON.stringify(article)}`);
    }
    if (!article.product.hasSeller || article.product.sellerImageCount < 1) {
      throw new Error(`Blog product seller image missing ${JSON.stringify({ shopCard, article: article.product })}`);
    }

    if (!sameSignature(shopCard.visual, article.product.visual, visualKeys)) {
      throw new Error(`Store/blog card visual mismatch ${JSON.stringify({ shop: shopCard.visual, blog: article.product.visual })}`);
    }
    if (!sameSignature(shopCard.image, article.product.image, imageKeys)) {
      throw new Error(`Store/blog product image mismatch ${JSON.stringify({ shop: shopCard.image, blog: article.product.image })}`);
    }
    if (!sameSignature(shopCard.title, article.product.title, titleKeys)) {
      throw new Error(`Store/blog product title mismatch ${JSON.stringify({ shop: shopCard.title, blog: article.product.title })}`);
    }
    if (!sameSignature(shopCard.seller, article.product.seller, sellerKeys)) {
      throw new Error(`Store/blog seller block mismatch ${JSON.stringify({ shop: shopCard.seller, blog: article.product.seller })}`);
    }
    if (!sameSignature(shopCard.sellerImage, article.product.sellerImage, sellerImageKeys)) {
      throw new Error(`Store/blog seller image mismatch ${JSON.stringify({ shop: shopCard.sellerImage, blog: article.product.sellerImage })}`);
    }

    /* Mobile parity is checked against the mobile shop itself, including any responsive hiding. */
    await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1 });
    await page.goto(withQuery(`${base}/tienda/`, 'shopmobileqa'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(900);

    const shopMobile = await page.evaluate(() => {
      const visible = (el) => el && el.getBoundingClientRect().width > 0 && el.getBoundingClientRect().height > 0;
      const cards = [...document.querySelectorAll('ul.products > li.product')].filter(visible);
      const card = cards.find((item) => item.querySelector('.wcfmmp_sold_by_container, .wcfmmp_sold_by_container_advanced')) || cards[0] || null;
      if (!card) return null;
      const visual = card.querySelector('.product-loop-wrapper') || card;
      const image = card.querySelector('img.product-loop-image, .woocommerce-loop-product__link > img, img.attachment-woocommerce_thumbnail');
      const title = card.querySelector('.woocommerce-loop-product__title, .product-title, h2, h3');
      const seller = card.querySelector('.wcfmmp_sold_by_container, .wcfmmp_sold_by_container_advanced');
      const sellerImage = seller?.querySelector('img') || null;
      const style = (el, keys) => {
        if (!el) return null;
        const s = getComputedStyle(el);
        return Object.fromEntries(keys.map((key) => [key, s[key]]));
      };
      const rect = (el) => {
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return { x: Math.round(r.x), w: Math.round(r.width), h: Math.round(r.height), right: Math.round(r.right) };
      };
      return {
        viewport: innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        card: rect(card),
        visual: style(visual, ['backgroundColor', 'borderRadius', 'boxShadow', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft']),
        image: style(image, ['borderRadius', 'objectFit', 'display']),
        title: style(title, ['fontSize', 'lineHeight', 'fontWeight', 'color', 'marginTop', 'marginBottom']),
        seller: style(seller, ['display', 'alignItems', 'fontSize', 'lineHeight', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft']),
        sellerImage: style(sellerImage, ['display', 'width', 'height', 'borderRadius', 'objectFit']),
        sellerImageRect: rect(sellerImage),
        hasSeller: Boolean(seller),
        sellerImageCount: seller ? seller.querySelectorAll('img').length : 0,
      };
    });

    if (!shopMobile?.card || !shopMobile.hasSeller || shopMobile.sellerImageCount < 1) {
      throw new Error(`Mobile storefront reference unavailable ${JSON.stringify(shopMobile)}`);
    }

    await page.goto(withQuery(articleUrl, 'mobileqa'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(800);

    const mobile = await page.evaluate(() => {
      const rect = (el) => {
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return { x: Math.round(r.x), w: Math.round(r.width), h: Math.round(r.height), right: Math.round(r.right) };
      };
      const style = (el, keys) => {
        if (!el) return null;
        const s = getComputedStyle(el);
        return Object.fromEntries(keys.map((key) => [key, s[key]]));
      };
      const main = document.querySelector('main#primary.emo-article-page');
      const cards = main ? [...main.querySelectorAll(
        '.emo-article-content .woocommerce ul.products > li.product, .emo-article-content .wc-block-grid__product, .emo-article-content .wc-block-product'
      )].filter((el) => el.getBoundingClientRect().width > 0) : [];
      const card = cards.find((item) => item.querySelector('.wcfmmp_sold_by_container, .wcfmmp_sold_by_container_advanced')) || cards[0] || null;
      const visual = card?.querySelector('.product-loop-wrapper') || card;
      const image = card?.querySelector('img.product-loop-image, .woocommerce-loop-product__link > img, img.attachment-woocommerce_thumbnail') || null;
      const title = card?.querySelector('.woocommerce-loop-product__title, .product-title, h2, h3') || null;
      const seller = card?.querySelector('.wcfmmp_sold_by_container, .wcfmmp_sold_by_container_advanced') || null;
      const sellerImage = seller?.querySelector('img') || null;
      return {
        viewport: innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        main: main ? rect(main) : null,
        cards: cards.slice(0, 5).map(rect),
        hasLead: Boolean(main?.querySelector('.emo-article-hero__lead')),
        product: card ? {
          visual: style(visual, ['backgroundColor', 'borderRadius', 'boxShadow', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft']),
          image: style(image, ['borderRadius', 'objectFit', 'display']),
          title: style(title, ['fontSize', 'lineHeight', 'fontWeight', 'color', 'marginTop', 'marginBottom']),
          seller: style(seller, ['display', 'alignItems', 'fontSize', 'lineHeight', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft']),
          sellerImage: style(sellerImage, ['display', 'width', 'height', 'borderRadius', 'objectFit']),
          sellerImageRect: rect(sellerImage),
          hasSeller: Boolean(seller),
          sellerImageCount: seller ? seller.querySelectorAll('img').length : 0,
        } : null,
      };
    });

    if (!mobile.main || mobile.scrollWidth > mobile.viewport + 2) throw new Error(`Mobile overflow ${JSON.stringify(mobile)}`);
    if (mobile.hasLead) throw new Error(`Mobile hero introduction still present ${JSON.stringify(mobile)}`);
    if (mobile.main.x < 8 || mobile.main.right > 382) throw new Error(`Mobile shell ${JSON.stringify(mobile)}`);
    if (!mobile.cards.length || mobile.cards.some((card) => card.x < 10 || card.right > 380 || card.w > 360)) {
      throw new Error(`Mobile product cards ${JSON.stringify(mobile)}`);
    }
    if (!mobile.product?.hasSeller || mobile.product.sellerImageCount < 1) {
      throw new Error(`Mobile blog seller block missing ${JSON.stringify(mobile)}`);
    }

    if (!sameSignature(shopMobile.visual, mobile.product.visual, visualKeys)) {
      throw new Error(`Mobile store/blog card visual mismatch ${JSON.stringify({ shop: shopMobile.visual, blog: mobile.product.visual })}`);
    }
    if (!sameSignature(shopMobile.image, mobile.product.image, imageKeys)) {
      throw new Error(`Mobile store/blog product image mismatch ${JSON.stringify({ shop: shopMobile.image, blog: mobile.product.image })}`);
    }
    if (!sameSignature(shopMobile.title, mobile.product.title, titleKeys)) {
      throw new Error(`Mobile store/blog product title mismatch ${JSON.stringify({ shop: shopMobile.title, blog: mobile.product.title })}`);
    }
    if (!sameSignature(shopMobile.seller, mobile.product.seller, sellerKeys)) {
      throw new Error(`Mobile store/blog seller block mismatch ${JSON.stringify({ shop: shopMobile.seller, blog: mobile.product.seller })}`);
    }
    if (!sameSignature(shopMobile.sellerImage, mobile.product.sellerImage, sellerImageKeys)) {
      throw new Error(`Mobile store/blog seller image mismatch ${JSON.stringify({ shop: shopMobile.sellerImage, blog: mobile.product.sellerImage })}`);
    }

    const shopSellerVisible = Boolean(shopMobile.sellerImageRect && shopMobile.sellerImageRect.w > 0 && shopMobile.sellerImageRect.h > 0);
    const blogSellerVisible = Boolean(mobile.product.sellerImageRect && mobile.product.sellerImageRect.w > 0 && mobile.product.sellerImageRect.h > 0);
    if (shopSellerVisible !== blogSellerVisible) {
      throw new Error(`Mobile seller visibility mismatch ${JSON.stringify({ shop: shopMobile.sellerImageRect, blog: mobile.product.sellerImageRect })}`);
    }
    if (shopSellerVisible && (
      Math.abs(shopMobile.sellerImageRect.w - mobile.product.sellerImageRect.w) > 1 ||
      Math.abs(shopMobile.sellerImageRect.h - mobile.product.sellerImageRect.h) > 1
    )) {
      throw new Error(`Mobile seller image size mismatch ${JSON.stringify({ shop: shopMobile.sellerImageRect, blog: mobile.product.sellerImageRect })}`);
    }

    console.log('PRODUCTION_BLOG_DESIGN_010251_BROWSER_OK', JSON.stringify({ shopCard, blog, articleUrl, article, shopMobile, mobile }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});