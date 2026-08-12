const puppeteer = require('puppeteer-core');

const expected = JSON.parse(process.env.COUNTS_JSON || '[]');
const base = (process.env.PRODUCTION_SITEURL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');

const norm = (url) => {
  const parsed = new URL(url);
  return `${parsed.origin}${parsed.pathname.replace(/\/$/, '')}/`;
};

const positive = expected.filter((row) => Number(row.public) > 0);
const expectedMap = new Map(positive.map((row) => [norm(row.url), row]));
const zeroUrls = new Set(expected.filter((row) => Number(row.public) <= 0).map((row) => norm(row.url)));

async function inspect(page, width, height, label) {
  await page.setViewport({ width, height });
  const response = await page.goto(`${base}/`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (!response || response.status() >= 400) {
    throw new Error(`${label}: Home HTTP ${response?.status()}`);
  }

  await page.waitForSelector('.emo-category-card__content small', { timeout: 30000 });
  await new Promise((resolve) => setTimeout(resolve, 1000));

  return page.evaluate(() => [...document.querySelectorAll('.emo-category-card')].map((card) => {
    const content = card.querySelector('.emo-category-card__content');
    const strong = content?.querySelector('strong');
    const small = content?.querySelector('small');
    const strongRect = strong?.getBoundingClientRect();
    const smallRect = small?.getBoundingClientRect();
    const contentStyle = content ? getComputedStyle(content) : null;
    const strongStyle = strong ? getComputedStyle(strong) : null;

    return {
      href: card.href,
      name: strong?.textContent?.trim() || '',
      countText: small?.textContent?.trim() || '',
      display: contentStyle?.display || '',
      direction: contentStyle?.flexDirection || '',
      gap: strongRect && smallRect ? smallRect.top - strongRect.bottom : null,
      strongHeight: strongRect?.height || 0,
      lineHeight: parseFloat(strongStyle?.lineHeight || '0') || 0,
    };
  }));
}

function validate(cards, label) {
  if (cards.length !== positive.length) {
    throw new Error(`${label}: ${cards.length} category cards, expected ${positive.length}`);
  }

  for (const card of cards) {
    const href = norm(card.href);
    if (zeroUrls.has(href)) {
      throw new Error(`${label}: zero-public category is visible: ${card.name}`);
    }

    const row = expectedMap.get(href);
    if (!row) {
      throw new Error(`${label}: unexpected category card ${card.href}`);
    }

    const displayed = parseInt(card.countText.replace(/[^0-9]/g, ''), 10);
    if (displayed !== Number(row.public)) {
      throw new Error(`${label}: ${card.name} shows ${displayed}; expected ${row.public} (published ${row.published}, disabled ${row.disabled})`);
    }

    if (card.display !== 'flex' || card.direction !== 'column') {
      throw new Error(`${label}: ${card.name} layout is ${card.display}/${card.direction}`);
    }

    if (card.gap === null || card.gap < 5) {
      throw new Error(`${label}: ${card.name} title/count gap is ${card.gap}`);
    }
  }

  const wrapped = cards
    .filter((card) => card.lineHeight > 0 && card.strongHeight > card.lineHeight * 1.55)
    .map((card) => card.name);

  if (label.startsWith('desktop') && wrapped.length) {
    throw new Error(`${label}: wrapped category titles: ${wrapped.join(', ')}`);
  }

  return wrapped;
}

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const desktopPage = await browser.newPage();
    const desktop = await inspect(desktopPage, 1440, 1000, 'desktop-cache');

    const mobilePage = await browser.newPage();
    const mobile = await inspect(mobilePage, 390, 844, 'mobile-cache');

    const freshPage = await browser.newPage();
    await freshPage.setExtraHTTPHeaders({ 'Cache-Control': 'no-cache', Pragma: 'no-cache' });
    const fresh = await inspect(freshPage, 1440, 1000, 'desktop-no-cache');

    const wrapped = validate(desktop, 'desktop-cache');
    validate(mobile, 'mobile-cache');
    validate(fresh, 'desktop-no-cache');

    console.log('PRODUCTION_010212_HOME_BROWSER_OK', JSON.stringify({ expected, desktop, mobile, fresh, wrapped }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
