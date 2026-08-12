const puppeteer = require('puppeteer-core');

const base = (process.env.PRODUCTION_SITEURL || 'https://www.elmercadodeorigen.com').replace(/\/$/, '');
const rows = JSON.parse(process.env.CATEGORY_PARITY_JSON || process.env.COUNTS_JSON || '[]');
const adminCookieName = process.env.ADMIN_COOKIE_NAME || '';
const adminCookieValue = process.env.ADMIN_COOKIE_VALUE || '';

const norm = (url) => {
  const parsed = new URL(url);
  return `${parsed.origin}${parsed.pathname.replace(/\/$/, '')}/`;
};

async function snapshot(page, url) {
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (!response || response.status() >= 400) throw new Error(`Home HTTP ${response?.status()}`);
  await page.waitForSelector('.emo-category-card__content small', { timeout: 30000 });
  await new Promise((resolve) => setTimeout(resolve, 800));
  return page.evaluate(() => ({
    authenticated: document.body.classList.contains('logged-in') || Boolean(document.querySelector('#wpadminbar')),
    cards: [...document.querySelectorAll('.emo-category-card')].map((card) => {
      const content = card.querySelector('.emo-category-card__content');
      const strong = content?.querySelector('strong');
      const small = content?.querySelector('small');
      const contentRect = content?.getBoundingClientRect();
      const smallRect = small?.getBoundingClientRect();
      const style = small ? getComputedStyle(small) : null;
      const contentStyle = content ? getComputedStyle(content) : null;
      return {
        href: card.href,
        name: strong?.textContent?.trim() || '',
        countText: small?.textContent?.trim() || '',
        textAlign: style?.textAlign || '',
        direction: contentStyle?.flexDirection || '',
        rightGap: contentRect && smallRect ? Math.abs(contentRect.right - smallRect.right) : null,
      };
    }),
  }));
}

function validate(result, field, label, authExpected) {
  if (result.authenticated !== authExpected) throw new Error(`${label}: authentication=${result.authenticated}`);
  const positive = rows.filter((row) => Number(row[field]) > 0);
  const expected = new Map(positive.map((row) => [norm(row.url), row]));
  const actual = new Map(result.cards.map((card) => [norm(card.href), card]));

  for (const row of positive) {
    const card = actual.get(norm(row.url));
    if (!card) throw new Error(`${label}: missing positive category ${row.name}`);
    const shown = parseInt(card.countText.replace(/[^0-9]/g, ''), 10);
    if (shown !== Number(row[field])) throw new Error(`${label}: ${row.name} shows ${shown}; expected ${row[field]}`);
    if (card.textAlign !== 'right') throw new Error(`${label}: ${row.name} is not right aligned`);
    if (card.direction !== 'column') throw new Error(`${label}: ${row.name} is not stacked vertically`);
    if (card.rightGap === null || card.rightGap > 1.5) throw new Error(`${label}: ${row.name} right gap=${card.rightGap}px`);
  }

  for (const row of rows.filter((row) => Number(row[field]) <= 0)) {
    if (actual.has(norm(row.url))) throw new Error(`${label}: zero-visible category rendered: ${row.name}`);
  }

  for (const card of result.cards) {
    if (!expected.has(norm(card.href))) throw new Error(`${label}: unexpected rendered category ${card.name}`);
  }
}

(async () => {
  if (!rows.length) throw new Error('Missing category parity rows');
  if (!adminCookieName || !adminCookieValue) throw new Error('Missing administrator cookie');

  const browser = await puppeteer.launch({ headless: true, executablePath: '/usr/bin/google-chrome', args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  try {
    const anonymousPage = await browser.newPage();
    await anonymousPage.setViewport({ width: 1440, height: 1000 });
    const anonymous = await snapshot(anonymousPage, `${base}/`);
    validate(anonymous, 'public', 'anonymous', false);

    const mobilePage = await browser.newPage();
    await mobilePage.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
    const mobile = await snapshot(mobilePage, `${base}/`);
    validate(mobile, 'public', 'mobile anonymous', false);

    const adminPage = await browser.newPage();
    await adminPage.setViewport({ width: 1440, height: 1000 });
    await adminPage.setCookie({ name: adminCookieName, value: adminCookieValue, url: base, secure: true, httpOnly: true });
    const admin = await snapshot(adminPage, `${base}/?home-admin-parity-010215=${Date.now()}`);
    validate(admin, 'admin', 'administrator', true);

    console.log('PRODUCTION_HOME_CATEGORY_PARITY_010215_BROWSER_OK', JSON.stringify({ rows, anonymous, mobile, admin }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
