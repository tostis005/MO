import fs from 'node:fs';
import process from 'node:process';
import { createHash } from 'node:crypto';
import { chromium } from 'playwright';

const GOOGLE_PROFILE_URL = 'https://www.google.com/maps/place/?q=place_id:ChIJbbIJi58nQg0RgJroXR8DG_U&hl=es';
const GOOGLE_PUBLIC_REVIEWS_URL = 'https://www.trustindex.io/reviews/www.elmercadodeorigen.com';
const TRUSTPILOT_PROFILE_URL = 'https://es.trustpilot.com/review/elmercadodeorigen.com';
const DESKTOP_UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36';

function arg(name, fallback = '') {
  const prefix = `--${name}=`;
  const direct = process.argv.find((value) => value.startsWith(prefix));
  if (direct) return direct.slice(prefix.length);
  const index = process.argv.indexOf(`--${name}`);
  if (index >= 0 && process.argv[index + 1]) return process.argv[index + 1];
  return fallback;
}

const source = arg('source').toLowerCase();
const mode = arg('mode', 'incremental').toLowerCase();
const output = arg('output', `reviews-${source || 'external'}.json`);
if (!['google', 'trustpilot'].includes(source)) throw new Error('Use --source google|trustpilot');
if (!['full', 'incremental'].includes(mode)) throw new Error('Use --mode full|incremental');

const chromePath = ['/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium', '/usr/bin/chromium-browser'].find(fs.existsSync);
const launchOptions = chromePath
  ? { headless: true, executablePath: chromePath, args: ['--no-sandbox', '--disable-dev-shm-usage'] }
  : { headless: true };

function sha(value) {
  return createHash('sha256').update(String(value || '')).digest('hex');
}

function toIsoDate(value) {
  const raw = String(value || '').trim();
  if (!raw) return null;
  const dotted = raw.match(/\b(20\d{2})[.\/-](\d{1,2})[.\/-](\d{1,2})\b/);
  if (dotted) {
    const [, year, month, day] = dotted;
    return new Date(Date.UTC(Number(year), Number(month) - 1, Number(day), 12, 0, 0)).toISOString();
  }
  const direct = Date.parse(raw);
  return Number.isNaN(direct) ? null : new Date(direct).toISOString();
}

async function clickConsent(page) {
  const patterns = [/Aceptar todo/i, /Accept all/i, /Estoy de acuerdo/i, /I agree/i, /Continuar/i, /Continue/i];
  for (const pattern of patterns) {
    const button = page.getByRole('button', { name: pattern }).first();
    if (!await button.count()) continue;
    try {
      if (await button.isVisible({ timeout: 500 })) {
        await button.click({ timeout: 2500 });
        await page.waitForTimeout(350);
      }
    } catch {}
  }
}

async function trustindexGoogleSummary(page) {
  return page.evaluate(() => {
    const text = document.body?.innerText || '';
    let reportedCount = 0;
    let rating = 0;
    const countPatterns = [
      /([0-9][0-9.,\s]*)\s+total\b/i,
      /([0-9][0-9.,\s]*)\s+reviews\b/i,
      /Google\s+([0-9][0-9.,\s]*)\s+(?:pcs|reviews|reseñas)/i,
    ];
    for (const pattern of countPatterns) {
      const match = text.match(pattern);
      if (!match) continue;
      const value = Number.parseInt(match[1].replace(/\D/g, ''), 10);
      if (value > 0 && value < 1000000) { reportedCount = value; break; }
    }
    const ratingMatch = text.match(/\b([0-5][,.]\d)\s*\|\s*[0-9][0-9.,\s]*\s+reviews\b/i)
      || text.match(/(?:rating|rating score)\D{0,20}([0-5][,.]\d)/i);
    if (ratingMatch) rating = Number.parseFloat(ratingMatch[1].replace(',', '.'));
    return { reportedCount, rating };
  });
}

async function collectTrustindexGoogle(page) {
  return page.evaluate(() => {
    const reportLinks = Array.from(document.querySelectorAll('a[href*="/review/report/"]'));
    const reviewNodes = Array.from(document.querySelectorAll('.ti-review-item.source-Google, .ti-review-item'));
    const candidates = [];
    const seen = new Set();

    function add(card, link = null) {
      if (!card || seen.has(card)) return;
      seen.add(card);
      candidates.push({ card, link });
    }

    for (const link of reportLinks) {
      let card = link.closest('.ti-review-item, article, [class*="review-item"], [class*="review-card"], li');
      if (!card) {
        let node = link.parentElement;
        for (let depth = 0; node && depth < 8; depth += 1, node = node.parentElement) {
          const text = node.textContent || '';
          const reports = node.querySelectorAll?.('a[href*="/review/report/"]').length || 0;
          if (reports === 1 && text.length >= 10 && text.length < 8000) card = node;
        }
      }
      add(card || link.parentElement, link);
    }
    for (const card of reviewNodes) add(card, card.querySelector('a[href*="/review/report/"]'));

    const reviews = [];
    for (const { card, link } of candidates) {
      const href = link?.href || card.querySelector?.('a[href*="/review/report/"]')?.href || '';
      const reportId = href.match(/\/id\/(\d+)/)?.[1] || href.match(/\/report\/(\d+)/)?.[1] || '';
      const cardId = card.getAttribute?.('data-id') || '';
      const author = card.querySelector?.('.ti-name, [class*="reviewer-name"], [class*="review-name"]')?.textContent?.trim() || '';
      const dateText = card.querySelector?.('.ti-date, time, [class*="review-date"]')?.textContent?.trim() || '';
      const textNode = card.querySelector?.('.ti-review-text-container, .ti-review-content, [class*="review-text"], [class*="review-content"]');
      const text = textNode?.textContent?.replace(/\s+/g, ' ').trim() || '';
      const avatar = card.querySelector?.('.ti-profile-img img[src], .ti-profile-img [data-imgurl], img[alt*="profile"]');
      const avatarUrl = avatar?.getAttribute('src') || avatar?.getAttribute('data-imgurl') || '';

      let rating = card.querySelectorAll?.('.ti-stars .ti-star.f, .ti-stars img[src*="/star/f.svg"], .ti-stars [data-imgurl*="/star/f.svg"]').length || 0;
      if (rating < 1 || rating > 5) {
        const labels = Array.from(card.querySelectorAll?.('[aria-label], img[alt], [alt]') || []).map((el) => `${el.getAttribute('aria-label') || ''} ${el.getAttribute('alt') || ''}`).join(' ');
        const match = labels.match(/([1-5])\s*(?:stars?|estrellas?)/i);
        rating = match ? Number.parseInt(match[1], 10) : 0;
      }
      if (!rating) {
        const stars = ((card.textContent || '').match(/★/g) || []).length;
        if (stars >= 1 && stars <= 5) rating = stars;
      }

      if (!rating) continue;
      const fallback = [author.toLowerCase(), dateText, String(rating), text].join('|');
      reviews.push({
        id: reportId ? `trustindex-${reportId}` : (cardId ? `trustindex-card-${cardId}` : `trustindex-fallback-${fallback}`),
        author_name: author,
        author_avatar_url: avatarUrl,
        rating,
        title: '',
        text,
        date: dateText,
        source_url: 'https://www.google.com/maps/place/?q=place_id:ChIJbbIJi58nQg0RgJroXR8DG_U&hl=es',
        transport: 'trustindex_public',
        transport_review_id: reportId || cardId,
      });
    }
    return reviews;
  });
}

async function revealMoreTrustindex(page) {
  const selectors = [
    '.ti-load-more-reviews-button',
    'button:has-text("More")',
    '[role="button"]:has-text("More")',
    'a:has-text("More")',
    'button:has-text("Load more")',
    '[role="button"]:has-text("Load more")',
  ];
  for (const selector of selectors) {
    const nodes = page.locator(selector);
    const count = Math.min(await nodes.count(), 10);
    for (let i = 0; i < count; i += 1) {
      const node = nodes.nth(i);
      try {
        if (!(await node.isVisible({ timeout: 300 }))) continue;
        const text = (await node.innerText().catch(() => '')).trim();
        if (text && !/^(more|load more|más|cargar más)$/i.test(text)) continue;
        await node.scrollIntoViewIfNeeded({ timeout: 1200 }).catch(() => {});
        await node.click({ timeout: 3000 });
        await page.waitForTimeout(750);
        return true;
      } catch {}
    }
  }
  return false;
}

async function scrapeGoogle(page) {
  const response = await page.goto(GOOGLE_PUBLIC_REVIEWS_URL, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (response && response.status() >= 400) throw new Error(`Trustindex Google HTTP ${response.status()}.`);
  await clickConsent(page);
  await page.waitForTimeout(900);
  const summary = await trustindexGoogleSummary(page);
  const map = new Map();
  const target = mode === 'full' ? (summary.reportedCount || 303) : Math.min(summary.reportedCount || 20, 25);
  const maxLoops = mode === 'full' ? 40 : 1;
  let stable = 0;

  for (let loop = 0; loop < maxLoops; loop += 1) {
    const batch = await collectTrustindexGoogle(page);
    const before = map.size;
    for (const review of batch) {
      const id = review.id.startsWith('trustindex-fallback-') ? `trustindex-fallback-${sha(review.id)}` : review.id;
      map.set(id, { ...review, id, date: toIsoDate(review.date) });
    }
    if (map.size >= target) break;
    stable = map.size === before ? stable + 1 : 0;
    console.error(`GOOGLE_TRUSTINDEX_PROGRESS loop=${loop + 1} reviews=${map.size}/${target} reportLinks=${await page.locator('a[href*="/review/report/"]').count()}`);
    const clicked = await revealMoreTrustindex(page);
    if (!clicked) {
      await page.keyboard.press('End').catch(() => {});
      await page.waitForTimeout(650);
    }
    if (stable >= 4 && !clicked) break;
  }

  if (!map.size) {
    const diagnostics = await page.evaluate(() => ({
      title: document.title,
      reviewItems: document.querySelectorAll('.ti-review-item').length,
      reportLinks: document.querySelectorAll('a[href*="/review/report/"]').length,
      textStart: (document.body?.innerText || '').slice(0, 500).replace(/\s+/g, ' '),
    }));
    throw new Error(`Trustindex no devolvió reseñas Google: ${JSON.stringify(diagnostics)}`);
  }

  return {
    source: 'google',
    provider: 'trustindex_public',
    mode,
    scraped_at: new Date().toISOString(),
    reported_count: summary.reportedCount,
    rating: summary.rating,
    profile_url: GOOGLE_PROFILE_URL,
    sorted_newest: true,
    reviews: Array.from(map.values()),
  };
}

async function trustpilotSummary(page) {
  return page.evaluate(() => {
    let reportedCount = 0;
    let rating = 0;
    for (const script of document.querySelectorAll('script[type="application/ld+json"]')) {
      try {
        const parsed = JSON.parse(script.textContent || 'null');
        for (const item of (Array.isArray(parsed) ? parsed : [parsed])) {
          const aggregate = item?.aggregateRating || item?.mainEntity?.aggregateRating;
          const count = Number.parseInt(aggregate?.reviewCount || aggregate?.ratingCount || '0', 10);
          const score = Number.parseFloat(aggregate?.ratingValue || '0');
          if (count > reportedCount) reportedCount = count;
          if (score > 0 && score <= 5) rating = score;
        }
      } catch {}
    }
    const text = document.body?.innerText || '';
    if (!reportedCount) {
      for (const pattern of [/Opiniones\s+(\d[\d.,\s]*)/i, /(\d[\d.,\s]*)\s+opiniones\b/i, /(\d[\d.,\s]*)\s+reviews\b/i]) {
        const match = text.match(pattern);
        if (!match) continue;
        const value = Number.parseInt(match[1].replace(/\D/g, ''), 10);
        if (value > 0 && value < 1000000) { reportedCount = value; break; }
      }
    }
    if (!rating) {
      const match = text.match(/(?:TrustScore:?\s*)?([0-5][,.]\d)\s*(?:sobre\s*5|out of\s*5|Excelente|Excellent)/i);
      if (match) rating = Number.parseFloat(match[1].replace(',', '.'));
    }
    return { reportedCount, rating };
  });
}

async function collectTrustpilotPage(page) {
  return page.evaluate(() => {
    const cards = Array.from(document.querySelectorAll('article[data-service-review-card-paper], article'));
    const reviews = [];
    const seen = new Set();
    for (const card of cards) {
      const link = card.querySelector('a[href*="/reviews/"]');
      const href = link?.getAttribute('href') || '';
      const idMatch = href.match(/\/reviews\/([A-Za-z0-9_-]+)/);
      if (!idMatch || seen.has(idMatch[1])) continue;
      seen.add(idMatch[1]);
      const ratingNode = card.querySelector('[data-service-review-rating]');
      const ratingRaw = ratingNode?.getAttribute('data-service-review-rating') || ratingNode?.querySelector('img')?.getAttribute('alt') || ratingNode?.getAttribute('aria-label') || '';
      const ratingMatch = String(ratingRaw).match(/([1-5](?:[.,]\d)?)/);
      const rating = ratingMatch ? Number.parseFloat(ratingMatch[1].replace(',', '.')) : 0;
      const title = card.querySelector('[data-service-review-title-typography]')?.textContent?.trim() || '';
      const text = card.querySelector('[data-service-review-text-typography]')?.textContent?.replace(/\s+/g, ' ').trim() || '';
      const author = card.querySelector('[data-consumer-name-typography]')?.textContent?.trim() || card.querySelector('a[href*="/users/"]')?.textContent?.trim() || '';
      const time = card.querySelector('time[datetime]');
      reviews.push({
        id: idMatch[1],
        author_name: author,
        author_avatar_url: '',
        rating,
        title,
        text,
        date: time?.getAttribute('datetime') || '',
        source_url: `https://es.trustpilot.com/reviews/${idMatch[1]}`,
      });
    }
    return reviews;
  });
}

async function scrapeTrustpilot(page) {
  const map = new Map();
  const maxPages = mode === 'full' ? 60 : 2;
  let reportedCount = 0;
  let overallRating = 0;
  let emptyPages = 0;

  for (let pageNumber = 1; pageNumber <= maxPages; pageNumber += 1) {
    const url = `${TRUSTPILOT_PROFILE_URL}?page=${pageNumber}&sort=recency`;
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
    if (response && response.status() >= 400) throw new Error(`Trustpilot HTTP ${response.status()} en página ${pageNumber}.`);
    await clickConsent(page);
    await page.waitForTimeout(650);
    if (pageNumber === 1) {
      const summary = await trustpilotSummary(page);
      reportedCount = summary.reportedCount;
      overallRating = summary.rating;
    }
    const batch = await collectTrustpilotPage(page);
    let newItems = 0;
    for (const item of batch) {
      if (!item.id || !item.rating || map.has(item.id)) continue;
      map.set(item.id, { ...item, date: toIsoDate(item.date) || item.date });
      newItems += 1;
    }
    console.error(`TRUSTPILOT_PROGRESS page=${pageNumber} new=${newItems} total=${map.size}/${reportedCount || '?'}`);
    if (!newItems) emptyPages += 1;
    else emptyPages = 0;
    if (mode === 'full' && reportedCount > 0 && map.size >= reportedCount) break;
    if (emptyPages >= 2 || !batch.length) break;
  }

  if (!map.size) throw new Error('Trustpilot no devolvió reseñas.');
  return {
    source: 'trustpilot',
    provider: 'trustpilot_public',
    mode,
    scraped_at: new Date().toISOString(),
    reported_count: reportedCount,
    rating: overallRating,
    profile_url: TRUSTPILOT_PROFILE_URL,
    sorted_newest: true,
    reviews: Array.from(map.values()),
  };
}

const browser = await chromium.launch(launchOptions);
try {
  const context = await browser.newContext({ locale: 'es-ES', viewport: { width: 1440, height: 1100 }, userAgent: DESKTOP_UA });
  const page = await context.newPage();
  page.setDefaultTimeout(10000);
  const payload = source === 'google' ? await scrapeGoogle(page) : await scrapeTrustpilot(page);
  fs.writeFileSync(output, `${JSON.stringify(payload, null, 2)}\n`, 'utf8');
  console.log(JSON.stringify({
    source: payload.source,
    provider: payload.provider,
    mode: payload.mode,
    found: payload.reviews.length,
    reported_count: payload.reported_count,
    rating: payload.rating,
    sorted_newest: payload.sorted_newest,
    output,
  }));
  await context.close();
} finally {
  await browser.close();
}
