import fs from 'node:fs';
import process from 'node:process';
import { createHash } from 'node:crypto';
import { chromium } from 'playwright';

const GOOGLE_PLACE_ID = 'ChIJbbIJi58nQg0RgJroXR8DG_U';
const GOOGLE_PROFILE_URL = `https://www.google.com/maps/search/?api=1&query=El%20Mercado%20de%20Origen&query_place_id=${GOOGLE_PLACE_ID}&hl=es`;
const TRUSTPILOT_PROFILE_URL = 'https://es.trustpilot.com/review/elmercadodeorigen.com';
const GOOGLE_MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1';
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

function stableGoogleReviewId(review) {
  if (review.id) return String(review.id);
  const basis = [
    String(review.reviewer_url || '').trim(),
    String(review.author_name || '').trim().toLowerCase(),
    String(review.rating || ''),
    String(review.text || '').trim(),
  ].join('|');
  return `mobile-${createHash('sha256').update(basis).digest('hex')}`;
}

function toIsoRelativeDate(value, now = new Date()) {
  const raw = String(value || '').trim();
  if (!raw) return null;
  const text = raw.toLowerCase().replace(/\s+/g, ' ').trim();
  const d = new Date(now.getTime());
  const direct = Date.parse(raw);
  if (!Number.isNaN(direct) && !/^hace\b/i.test(raw)) return new Date(direct).toISOString();
  if (/^(ayer|yesterday)$/.test(text)) {
    d.setUTCDate(d.getUTCDate() - 1);
    return d.toISOString();
  }
  const match = text.match(/(?:hace\s+|^)(un|una|one|\d+)\s+(minuto|minutos|minute|minutes|hora|horas|hour|hours|día|dias|días|day|days|semana|semanas|week|weeks|mes|meses|month|months|año|años|year|years)/i);
  if (!match) return null;
  const amount = /^(un|una|one)$/i.test(match[1]) ? 1 : Number.parseInt(match[1], 10);
  if (!Number.isFinite(amount)) return null;
  const unit = match[2].toLowerCase();
  if (/minut/.test(unit)) d.setUTCMinutes(d.getUTCMinutes() - amount);
  else if (/hora|hour/.test(unit)) d.setUTCHours(d.getUTCHours() - amount);
  else if (/día|dias|días|day/.test(unit)) d.setUTCDate(d.getUTCDate() - amount);
  else if (/semana|week/.test(unit)) d.setUTCDate(d.getUTCDate() - amount * 7);
  else if (/mes|month/.test(unit)) d.setUTCMonth(d.getUTCMonth() - amount);
  else if (/año|year/.test(unit)) d.setUTCFullYear(d.getUTCFullYear() - amount);
  return d.toISOString();
}

async function clickConsent(page) {
  const patterns = [
    /Aceptar todo/i,
    /Accept all/i,
    /Estoy de acuerdo/i,
    /I agree/i,
    /Volver a la web/i,
    /Go back to web/i,
    /Continuar/i,
    /Continue/i,
  ];
  for (const pattern of patterns) {
    const button = page.getByRole('button', { name: pattern }).first();
    if (!await button.count()) continue;
    try {
      if (await button.isVisible({ timeout: 700 })) {
        await button.click({ timeout: 2500 });
        await page.waitForTimeout(450);
      }
    } catch {}
  }
}

async function googleReportedCount(page) {
  return page.evaluate(() => {
    const candidates = [document.title || '', document.body?.innerText || ''];
    document.querySelectorAll('[aria-label]').forEach((el) => candidates.push(el.getAttribute('aria-label') || ''));
    for (const text of candidates) {
      const match = text.match(/(\d[\d.,\s]*)\s+(?:reseñas|reviews)\b/i);
      if (!match) continue;
      const value = Number.parseInt(match[1].replace(/\D/g, ''), 10);
      if (Number.isInteger(value) && value > 0 && value < 1000000) return value;
    }
    return 0;
  });
}

async function googleRating(page) {
  return page.evaluate(() => {
    const candidates = [document.title || '', document.body?.innerText || ''];
    document.querySelectorAll('[aria-label]').forEach((el) => candidates.push(el.getAttribute('aria-label') || ''));
    for (const text of candidates) {
      const explicit = text.match(/([0-5](?:[,.]\d)?)\s*(?:estrellas?|stars?)/i);
      if (explicit) {
        const value = Number.parseFloat(explicit[1].replace(',', '.'));
        if (value > 0 && value <= 5) return value;
      }
    }
    const body = candidates[1];
    const loose = body.match(/\b([0-5][,.]\d)\b/);
    return loose ? Number.parseFloat(loose[1].replace(',', '.')) : 0;
  });
}

async function googleCardCount(page) {
  return page.locator('.jftiEf, .hjmQqc, [data-review-id]').count();
}

async function openGoogleReviews(page) {
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    await clickConsent(page);
    await page.waitForTimeout(attempt === 1 ? 1800 : 2500);
    if (await googleCardCount(page)) return true;

    const selectors = [
      'button[data-tab-index="1"]',
      'button[jsaction*="pane.reviewChart.moreReviews"]',
      'button[aria-label*="reseñas"]',
      'button[aria-label*="reviews"]',
    ];
    for (const selector of selectors) {
      const buttons = page.locator(selector);
      const count = Math.min(await buttons.count(), 15);
      for (let i = 0; i < count; i += 1) {
        const button = buttons.nth(i);
        try {
          if (!(await button.isVisible({ timeout: 700 }))) continue;
          const label = `${await button.getAttribute('aria-label') || ''} ${await button.innerText().catch(() => '')}`.trim();
          if (/escribir|write a review|tu reseña|your review/i.test(label)) continue;
          if (!/reseñas|reviews/i.test(label) && !/data-tab-index/i.test(selector)) continue;
          await button.click({ timeout: 5000 });
          await page.waitForTimeout(1800);
          if (await googleCardCount(page)) return true;
        } catch {}
      }
    }

    const textButton = page.getByRole('button', { name: /\d[\d.,\s]*\s*(?:reseñas|reviews)|^(?:Reseñas|Reviews)$/i }).first();
    try {
      if (await textButton.count() && await textButton.isVisible({ timeout: 700 })) {
        await textButton.click({ timeout: 5000 });
        await page.waitForTimeout(1800);
        if (await googleCardCount(page)) return true;
      }
    } catch {}

    console.error(`GOOGLE_OPEN_RETRY attempt=${attempt} url=${page.url()} cards=${await googleCardCount(page)}`);
    if (attempt < 4) {
      await page.reload({ waitUntil: 'domcontentloaded', timeout: 90000 });
    }
  }
  return false;
}

async function sortGoogleNewest(page) {
  const sorters = page.locator('button[aria-label*="Ordenar"], button[aria-label*="Sort"], button[data-value*="Ordenar"], button:has-text("Ordenar")');
  const count = Math.min(await sorters.count(), 10);
  for (let i = 0; i < count; i += 1) {
    try {
      const sorter = sorters.nth(i);
      if (!(await sorter.isVisible({ timeout: 700 }))) continue;
      await sorter.click({ timeout: 3500 });
      await page.waitForTimeout(400);
      const newest = page.locator('[role="menuitemradio"], [role="menuitem"], button').filter({ hasText: /Más recientes|Más nuevas|Newest|Most recent/i }).first();
      if (await newest.count() && await newest.isVisible({ timeout: 1200 })) {
        await newest.click({ timeout: 3500 });
        await page.waitForTimeout(1500);
        return true;
      }
    } catch {}
  }
  return false;
}

async function collectGoogleCards(page) {
  return page.evaluate(() => {
    document.querySelectorAll('.jftiEf button.w8nwRe, [data-review-id] button.w8nwRe, .hjmQqc button').forEach((button) => {
      const label = `${button.getAttribute('aria-label') || ''} ${button.textContent || ''}`;
      if (/más|more|ver más|see more/i.test(label)) {
        try { button.click(); } catch {}
      }
    });

    const nodes = [];
    const seenNodes = new Set();
    for (const selector of ['.jftiEf', '.hjmQqc', '[data-review-id]']) {
      for (const node of document.querySelectorAll(selector)) {
        const card = node.closest('.jftiEf, .hjmQqc, [role="article"]') || node;
        if (!seenNodes.has(card)) {
          seenNodes.add(card);
          nodes.push(card);
        }
      }
    }

    const reviews = [];
    for (const card of nodes) {
      const idNode = card.matches?.('[data-review-id]') ? card : card.querySelector?.('[data-review-id]');
      const id = idNode?.getAttribute('data-review-id') || card.getAttribute?.('data-review-id') || '';
      const author = card.querySelector?.('.d4r55')?.textContent?.trim()
        || card.querySelector?.('[class*="author"]')?.textContent?.trim()
        || card.querySelector?.('a[href*="/contrib/"]')?.textContent?.trim()
        || '';
      const text = card.querySelector?.('.wiI7pd')?.textContent?.trim()
        || card.querySelector?.('.MyEned')?.textContent?.trim()
        || '';
      const dateText = card.querySelector?.('.rsqaWe')?.textContent?.trim()
        || (card.textContent || '').match(/(?:hace\s+)?(?:un|una|\d+)\s+(?:minutos?|horas?|d[ií]as?|semanas?|meses?|a[ñn]os?)|(?:one|\d+)\s+(?:minutes?|hours?|days?|weeks?|months?|years?)\s+ago/i)?.[0]
        || '';
      const ratingNode = card.querySelector?.('.kvMYJc[aria-label], span[role="img"][aria-label], [aria-label*="estrella"], [aria-label*="star"]');
      const ratingLabel = ratingNode?.getAttribute('aria-label') || '';
      const ratingMatch = ratingLabel.match(/([1-5](?:[.,]\d)?)/);
      let rating = ratingMatch ? Number.parseFloat(ratingMatch[1].replace(',', '.')) : 0;
      if (!rating) {
        const stars = ((card.textContent || '').match(/★/g) || []).length;
        if (stars >= 1 && stars <= 5) rating = stars;
      }
      const image = card.querySelector?.('img[src]');
      const reviewerLink = card.querySelector?.('a[href*="/contrib/"], a[href*="/maps/contrib/"]');
      reviews.push({
        id,
        author_name: author,
        author_avatar_url: image?.src || '',
        reviewer_url: reviewerLink?.href || '',
        rating,
        text,
        date_text: dateText,
      });
    }
    return reviews;
  });
}

async function scrollGoogleReviews(page) {
  const last = page.locator('.jftiEf, .hjmQqc, [data-review-id]').last();
  try {
    if (await last.count()) await last.scrollIntoViewIfNeeded({ timeout: 2500 });
  } catch {}

  const paneScroll = await page.evaluate(() => {
    const candidates = [];
    const seen = new Set();
    for (const selector of ['div.m6QErb.DxyBCb.kA9KIf.dS8AEf', 'div.m6QErb.DxyBCb', 'div[role="main"] div.m6QErb', 'div.m6QErb']) {
      for (const node of document.querySelectorAll(selector)) {
        if (seen.has(node)) continue;
        seen.add(node);
        if (!node.querySelector('.jftiEf, .hjmQqc, [data-review-id]')) continue;
        if (node.scrollHeight <= node.clientHeight + 80) continue;
        candidates.push(node);
      }
    }
    candidates.sort((a, b) => (b.scrollHeight - b.clientHeight) - (a.scrollHeight - a.clientHeight));
    const pane = candidates[0] || null;
    if (!pane) return { found: false, before: 0, after: 0, height: 0 };
    const before = pane.scrollTop;
    pane.scrollTop = Math.min(pane.scrollHeight, pane.scrollTop + Math.max(3500, pane.clientHeight * 4));
    pane.dispatchEvent(new Event('scroll', { bubbles: true }));
    return { found: true, before, after: pane.scrollTop, height: pane.scrollHeight };
  });

  await page.mouse.wheel(0, 1800).catch(() => {});
  if (!paneScroll.found) await page.keyboard.press('End').catch(() => {});
  return paneScroll;
}

async function scrapeGoogle(page) {
  await page.goto(GOOGLE_PROFILE_URL, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await clickConsent(page);
  await page.waitForTimeout(1800);
  let reportedCount = await googleReportedCount(page);
  let overallRating = await googleRating(page);
  const opened = await openGoogleReviews(page);
  if (!opened) throw new Error(`Google Maps no abrió el panel de reseñas (reported=${reportedCount}).`);
  reportedCount = reportedCount || await googleReportedCount(page);
  overallRating = overallRating || await googleRating(page);
  const sortedNewest = await sortGoogleNewest(page);
  if (mode === 'incremental' && !sortedNewest) throw new Error('Google Maps no permitió ordenar las reseñas por más recientes.');

  const map = new Map();
  const target = mode === 'full' ? (reportedCount > 0 ? reportedCount : 500) : Math.min(reportedCount || 80, 80);
  const maxLoops = mode === 'full' ? 280 : 55;
  let stable = 0;
  let previous = 0;

  for (let loop = 0; loop < maxLoops; loop += 1) {
    const batch = await collectGoogleCards(page);
    for (const item of batch) {
      if (!item.rating) continue;
      const id = stableGoogleReviewId(item);
      map.set(id, {
        id,
        author_name: item.author_name || '',
        author_avatar_url: item.author_avatar_url || '',
        reviewer_url: item.reviewer_url || '',
        rating: Math.max(1, Math.min(5, Math.round(item.rating))),
        title: '',
        text: item.text || '',
        date: toIsoRelativeDate(item.date_text),
        date_text: item.date_text || '',
        source_url: GOOGLE_PROFILE_URL,
      });
    }
    if (map.size >= target) break;
    stable = map.size === previous ? stable + 1 : 0;
    previous = map.size;
    if (stable >= (mode === 'full' ? 18 : 9)) break;
    const scroll = await scrollGoogleReviews(page);
    if (loop === 0 || (loop + 1) % 10 === 0) {
      console.error(`GOOGLE_PROGRESS loop=${loop + 1} reviews=${map.size}/${target} pane=${scroll.found ? 1 : 0} scroll=${scroll.before}->${scroll.after}/${scroll.height}`);
    }
    await page.waitForTimeout(mode === 'full' ? 950 : 800);
  }

  if (!map.size) throw new Error('Google Maps no devolvió reseñas.');
  return {
    source: 'google',
    mode,
    scraped_at: new Date().toISOString(),
    reported_count: reportedCount,
    rating: overallRating,
    profile_url: GOOGLE_PROFILE_URL,
    sorted_newest: sortedNewest,
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
      const text = card.querySelector('[data-service-review-text-typography]')?.textContent?.trim() || '';
      const author = card.querySelector('[data-consumer-name-typography]')?.textContent?.trim() || card.querySelector('a[href*="/users/"]')?.textContent?.trim() || '';
      const time = card.querySelector('time[datetime]');
      const date = time?.getAttribute('datetime') || '';
      reviews.push({
        id: idMatch[1],
        author_name: author,
        author_avatar_url: '',
        rating,
        title,
        text,
        date,
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
      map.set(item.id, item);
      newItems += 1;
    }
    if (!newItems) emptyPages += 1;
    else emptyPages = 0;
    if (mode === 'full' && reportedCount > 0 && map.size >= reportedCount) break;
    if (emptyPages >= 2 || !batch.length) break;
  }

  if (!map.size) throw new Error('Trustpilot no devolvió reseñas.');
  return {
    source: 'trustpilot',
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
  const contextOptions = source === 'google'
    ? { locale: 'es-ES', viewport: { width: 390, height: 844 }, isMobile: true, userAgent: GOOGLE_MOBILE_UA }
    : { locale: 'es-ES', viewport: { width: 1440, height: 1100 }, userAgent: DESKTOP_UA };
  const context = await browser.newContext(contextOptions);
  const page = await context.newPage();
  page.setDefaultTimeout(10000);
  const payload = source === 'google' ? await scrapeGoogle(page) : await scrapeTrustpilot(page);
  fs.writeFileSync(output, `${JSON.stringify(payload, null, 2)}\n`, 'utf8');
  console.log(JSON.stringify({
    source: payload.source,
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
