const puppeteer = require('puppeteer-core');

const base = (process.env.PRODUCTION_SITEURL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const bust = (path, key = 'mdoqa') => `${base}${path}${path.includes('?') ? '&' : '?'}${key}=${Date.now()}`;

const getHomeState = async (page) => page.evaluate(() => {
  const visible = (el) => {
    if (!el) return false;
    const r = el.getBoundingClientRect();
    const s = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
  };
  const section = document.querySelector('.emo-categories[data-emo-category-truth="010226"]');
  const cta = section?.querySelector('[data-emo-categories-link="010257"]') || null;
  const cards = section ? [...section.querySelectorAll('a.emo-category-card')].filter(visible) : [];
  return {
    viewport: innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    section: Boolean(section),
    ctaVisible: visible(cta),
    ctaHref: cta?.href || '',
    cards: cards.map((card) => ({
      href: card.href,
      title: card.querySelector('strong')?.textContent?.trim() || '',
      count: card.querySelector('small')?.textContent?.trim() || '',
      image: card.style.getPropertyValue('--emo-category-image').trim(),
    })),
  };
});

const getHubState = async (page) => page.evaluate(() => {
  const visible = (el) => {
    if (!el) return false;
    const r = el.getBoundingClientRect();
    const s = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
  };
  const main = document.querySelector('main[data-emo-categories-hub="010257"]');
  const cards = main ? [...main.querySelectorAll('.emo-category-hub-card')].filter(visible) : [];
  const rect = (el) => {
    if (!el) return null;
    const r = el.getBoundingClientRect();
    return { x: Math.round(r.x), w: Math.round(r.width), right: Math.round(r.right), h: Math.round(r.height) };
  };
  return {
    path: location.pathname,
    viewport: innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    hasMain: Boolean(main),
    hero: rect(main?.querySelector('.emo-categories-hub__hero')),
    grid: rect(main?.querySelector('.emo-categories-hub__grid')),
    cards: cards.map((card) => ({
      rect: rect(card),
      slug: card.dataset.categorySlug || '',
      title: card.querySelector('h2')?.textContent?.trim() || '',
      summary: card.querySelector('.emo-category-hub-card__summary')?.textContent?.trim() || '',
      count: Number(card.querySelector('[data-category-count]')?.dataset.categoryCount || 0),
      href: card.querySelector('h2 a')?.href || '',
      image: card.style.getPropertyValue('--emo-category-image').trim(),
      parent: card.querySelector('.emo-category-hub-card__parent')?.textContent?.trim() || '',
    })),
  };
});

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1100, deviceScaleFactor: 1 });

    await page.goto(bust('/'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(900);
    const home = await getHomeState(page);
    if (!home.section || !home.ctaVisible || !home.ctaHref) {
      throw new Error(`Home categories CTA missing ${JSON.stringify(home)}`);
    }
    const ctaPath = new URL(home.ctaHref).pathname.replace(/\/+$/, '');
    if (ctaPath !== '/categorias') {
      throw new Error(`Home categories CTA points to ${home.ctaHref}`);
    }
    if (!home.cards.length || home.cards.some((card) => /\/mentta\/?$/i.test(new URL(card.href).pathname))) {
      throw new Error(`Home public category set invalid ${JSON.stringify(home)}`);
    }
    if (home.scrollWidth > home.viewport + 2) {
      throw new Error(`Home horizontal overflow ${JSON.stringify(home)}`);
    }

    await page.goto(bust('/categorias/'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(900);
    const hub = await getHubState(page);
    if (!hub.hasMain || hub.path.replace(/\/+$/, '') !== '/categorias' || !hub.cards.length) {
      throw new Error(`Categories hub structure invalid ${JSON.stringify(hub)}`);
    }
    if (hub.scrollWidth > hub.viewport + 2) {
      throw new Error(`Categories hub desktop overflow ${JSON.stringify(hub)}`);
    }
    if (hub.cards.length < home.cards.length) {
      throw new Error(`Categories hub has fewer categories than home ${JSON.stringify({ home: home.cards.length, hub: hub.cards.length })}`);
    }

    const badCards = hub.cards.filter((card) => (
      !card.title || card.summary.length < 38 || card.count < 1 || !card.href ||
      card.rect.w < 250 || card.rect.w > 420 || card.rect.x < 80 || card.rect.right > 1360
    ));
    if (badCards.length) {
      throw new Error(`Categories hub card content/geometry invalid ${JSON.stringify(badCards.slice(0, 5))}`);
    }
    if (hub.cards.some((card) => card.slug.toLowerCase() === 'mentta' || /\/mentta\/?$/i.test(new URL(card.href).pathname))) {
      throw new Error(`MENTTA is visible publicly on categories hub ${JSON.stringify(hub.cards)}`);
    }

    const hubByPath = new Map(hub.cards.map((card) => [new URL(card.href).pathname.replace(/\/+$/, ''), card]));
    for (const homeCard of home.cards) {
      const path = new URL(homeCard.href).pathname.replace(/\/+$/, '');
      const match = hubByPath.get(path);
      if (!match) {
        throw new Error(`Home category missing from hub: ${path}`);
      }
      if (homeCard.image && match.image !== homeCard.image) {
        throw new Error(`Category image mismatch for ${path}: ${JSON.stringify({ home: homeCard.image, hub: match.image })}`);
      }
    }

    await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1 });
    await page.goto(bust('/categorias/', 'mobileqa'), { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(800);
    const mobile = await getHubState(page);
    if (!mobile.hasMain || mobile.scrollWidth > mobile.viewport + 2 || !mobile.cards.length) {
      throw new Error(`Categories hub mobile base invalid ${JSON.stringify(mobile)}`);
    }
    const mobileBad = mobile.cards.filter((card) => card.rect.x < 8 || card.rect.right > 382 || card.rect.w < 340 || card.rect.w > 372);
    if (mobileBad.length) {
      throw new Error(`Categories hub mobile cards invalid ${JSON.stringify(mobileBad.slice(0, 5))}`);
    }

    console.log('PRODUCTION_CATEGORIES_HUB_010257_BROWSER_OK', JSON.stringify({
      homeCards: home.cards.length,
      hubCards: hub.cards.length,
      desktop: { viewport: hub.viewport, scrollWidth: hub.scrollWidth, hero: hub.hero, grid: hub.grid },
      mobile: { viewport: mobile.viewport, scrollWidth: mobile.scrollWidth, firstCard: mobile.cards[0]?.rect || null },
      categories: hub.cards.map((card) => ({ slug: card.slug, count: card.count, parent: card.parent })),
    }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error.stack || error.message || String(error));
  process.exit(1);
});
