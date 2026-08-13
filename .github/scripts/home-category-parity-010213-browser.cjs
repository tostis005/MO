const puppeteer = require('puppeteer-core');

const base = (process.env.STAGING_URL || 'https://dev.elmercadodeorigen.com').replace(/\/$/, '');
const rows = JSON.parse(process.env.CATEGORY_PARITY_JSON || '[]');
const adminCookieName = process.env.ADMIN_COOKIE_NAME || '';
const adminCookieValue = process.env.ADMIN_COOKIE_VALUE || '';

const norm = (url) => {
  const parsed = new URL(url);
  return `${parsed.origin}${parsed.pathname.replace(/\/$/, '')}/`;
};

async function readHome(page, label) {
  const response = await page.goto(`${base}/?home-parity-010219=${Date.now()}-${label}`, {
    waitUntil: 'domcontentloaded',
    timeout: 90000,
  });
  if (!response || response.status() >= 400) {
    throw new Error(`${label}: Home HTTP ${response?.status()}`);
  }
  await page.waitForSelector('.emo-category-card__content small', { timeout: 30000 });
  await new Promise((resolve) => setTimeout(resolve, 700));

  return page.evaluate(() => ({
    authenticated: document.body.classList.contains('logged-in') || Boolean(document.querySelector('#wpadminbar')),
    cards: [...document.querySelectorAll('.emo-category-card')].map((card) => {
      const content = card.querySelector('.emo-category-card__content');
      const strong = content?.querySelector('strong');
      const small = content?.querySelector('small');
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
      };
    }),
  }));
}

function validate(result, field, label, shouldBeAuthenticated) {
  if (result.authenticated !== shouldBeAuthenticated) {
    throw new Error(`${label}: authentication state is ${result.authenticated}`);
  }
  if (!result.cards.length) {
    throw new Error(`${label}: no category cards rendered`);
  }

  const expected = new Map(rows.map((row) => [norm(row.url), row]));
  for (const card of result.cards) {
    const row = expected.get(norm(card.href));
    if (!row) throw new Error(`${label}: unexpected category ${card.name} (${card.href})`);
    if (Number(row[field]) <= 0) {
      throw new Error(`${label}: category with zero visible products is rendered: ${card.name}`);
    }
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
    const anonymous = await readHome(anonymousPage, 'anonymous');
    validate(anonymous, 'public', 'anonymous', false);

    const adminPage = await browser.newPage();
    await adminPage.setViewport({ width: 1440, height: 1000 });
    await adminPage.setCookie({
      name: adminCookieName,
      value: adminCookieValue,
      url: base,
      secure: true,
      httpOnly: true,
    });
    const admin = await readHome(adminPage, 'admin');
    validate(admin, 'admin', 'admin', true);

    console.log('HOME_CATEGORY_PARITY_010219_BROWSER_OK', JSON.stringify({ rows, anonymous, admin }));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});