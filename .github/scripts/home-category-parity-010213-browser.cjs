const puppeteer = require('puppeteer-core');

const base = (process.env.STAGING_URL || 'https://dev.elmercadodeorigen.com').replace(/\/$/, '');
const rows = JSON.parse(process.env.CATEGORY_PARITY_JSON || '[]');
const adminCookieName = process.env.ADMIN_COOKIE_NAME || '';
const adminCookieValue = process.env.ADMIN_COOKIE_VALUE || '';

const norm = (url) => {
  const parsed = new URL(url);
  return `${parsed.origin}${parsed.pathname.replace(/\/$/, '')}/`;
};

async function readCards(page, label) {
  const response = await page.goto(`${base}/?home-parity-010213=${Date.now()}-${label}`, {
    waitUntil: 'domcontentloaded',
    timeout: 90000,
  });
  if (!response || response.status() >= 400) {
    throw new Error(`${label}: Home HTTP ${response?.status()}`);
  }
  await page.waitForSelector('.emo-category-card__content small', { timeout: 30000 });
  await new Promise((resolve) => setTimeout(resolve, 700));

  return page.evaluate(() => [...document.querySelectorAll('.emo-category-card')].map((card) => {
    const content = card.querySelector('.emo-category-card__content');
    const strong = content?.querySelector('strong');
    const small = content?.querySelector('small');
    const cardRect = card.getBoundingClientRect();
    const contentRect = content?.getBoundingClientRect();
    const smallRect = small?.getBoundingClientRect();
    const smallStyle = small ? getComputedStyle(small) : null;
    const contentStyle = content ? getComputedStyle(content) : null;
    return {
      href: card.href,
      name: strong?.textContent?.trim() || '',
      countText: small?.textContent?.trim() || '',
      textAlign: smallStyle?.textAlign || '',
      contentDisplay: contentStyle?.display || '',
      contentDirection: contentStyle?.flexDirection || '',
      contentRightGap: contentRect && smallRect ? Math.abs(contentRect.right - smallRect.right) : null,
      cardRightGap: smallRect ? cardRect.right - smallRect.right : null,
    };
  }));
}

function validate(cards, field, label) {
  const positive = rows.filter((row) => Number(row[field]) > 0);
  const expected = new Map(positive.map((row) => [norm(row.url), row]));
  if (cards.length !== positive.length) {
    throw new Error(`${label}: ${cards.length} cards; expected ${positive.length}`);
  }
  for (const card of cards) {
    const row = expected.get(norm(card.href));
    if (!row) throw new Error(`${label}: unexpected category ${card.name} (${card.href})`);
    const displayed = parseInt(card.countText.replace(/[^0-9]/g, ''), 10);
    if (displayed !== Number(row[field])) {
      throw new Error(`${label}: ${card.name} shows ${displayed}; expected ${row[field]}`);
    }
    if (card.textAlign !== 'right') {
      throw new Error(`${label}: ${card.name} count alignment is ${card.textAlign}`);
    }
    if (card.contentDisplay !== 'flex' || card.contentDirection !== 'column') {
      throw new Error(`${label}: ${card.name} content layout is ${card.contentDisplay}/${card.contentDirection}`);
    }
    if (card.contentRightGap === null || card.contentRightGap > 1.5) {
      throw new Error(`${label}: ${card.name} count does not reach content right edge (${card.contentRightGap}px)`);
    }
  }
}

(async () => {
  if (!rows.length) throw new Error('Missing CATEGORY_PARITY_JSON');
  if (!adminCookieName || !adminCookieValue) throw new Error('Missing generated administrator cookie');

  const browser = await puppeteer.launch({
    headless: true,
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const anonymousPage = await browser.newPage();
    await anonymousPage.setViewport({ width: 1440, height: 1000 });
    const anonymous = await readCards(anonymousPage, 'anonymous');
    validate(anonymous, 'public', 'anonymous');

    const adminPage = await browser.newPage();
    await adminPage.setViewport({ width: 1440, height: 1000 });
    await adminPage.setCookie({
      name: adminCookieName,
      value: adminCookieValue,
      url: base,
      secure: true,
      httpOnly: true,
    });
    const admin = await readCards(adminPage, 'admin');
    validate(admin, 'admin', 'admin');

    console.log('HOME_CATEGORY_PARITY_010213_BROWSER_OK', JSON.stringify({ rows, anonymous, admin }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
