import fs from 'node:fs';
import process from 'node:process';
import { createHash } from 'node:crypto';
import { chromium } from 'playwright';

const PUBLIC_URL = 'https://www.trustindex.io/reviews/www.elmercadodeorigen.com';
const PROFILE_URL = 'https://www.google.com/maps/place/?q=place_id:ChIJbbIJi58nQg0RgJroXR8DG_U&hl=es';
const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36';

function arg(name, fallback = '') {
  const prefix = `--${name}=`;
  const direct = process.argv.find((value) => value.startsWith(prefix));
  if (direct) return direct.slice(prefix.length);
  const index = process.argv.indexOf(`--${name}`);
  return index >= 0 && process.argv[index + 1] ? process.argv[index + 1] : fallback;
}

const mode = arg('mode', 'incremental').toLowerCase();
const output = arg('output', 'reviews-google.json');
if (!['full', 'incremental'].includes(mode)) throw new Error('Use --mode full|incremental');

function sha(value) {
  return createHash('sha256').update(String(value || '')).digest('hex');
}

function toIsoDate(value) {
  const raw = String(value || '').trim();
  if (!raw) return null;
  const match = raw.match(/\b(20\d{2})[.\/-](\d{1,2})[.\/-](\d{1,2})\b/);
  if (match) return new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12)).toISOString();
  const parsed = Date.parse(raw);
  return Number.isNaN(parsed) ? null : new Date(parsed).toISOString();
}

async function summary(page) {
  return page.evaluate(() => {
    const text = document.body?.innerText || '';
    let reportedCount = 0;
    let rating = 0;
    for (const pattern of [
      /([0-9][0-9.,\s]*)\s+total\b/i,
      /([0-9][0-9.,\s]*)\s+reviews\b/i,
      /Google\s+([0-9][0-9.,\s]*)\s+(?:pcs|reviews|reseñas)/i,
    ]) {
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

async function collect(page) {
  return page.evaluate(() => {
    const compact = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const links = Array.from(document.querySelectorAll('a[href*="/review/report/"]'));
    const reviews = [];
    const seenIds = new Set();

    function findCard(link) {
      let card = link.closest('.ti-review-item, article, [class*="review-item"], [class*="review-card"], li');
      if (card) return card;
      let node = link.parentElement;
      let best = node;
      for (let depth = 0; node && depth < 9; depth += 1, node = node.parentElement) {
        const reports = node.querySelectorAll?.('a[href*="/review/report/"]').length || 0;
        const text = node.textContent || '';
        if (reports === 1 && text.length >= 10 && text.length < 8000) best = node;
      }
      return best;
    }

    function identity(card) {
      const cardText = compact(card.innerText || card.textContent || '');
      let author = '';
      let dateText = '';
      for (const selector of [
        '.ti-name', '.ti-review-author', '.ti-review-author-name',
        '.ti-profile-details .ti-name', '.ti-profile-details [class*="name"]',
        '[class*="reviewer-name"]', '[class*="review-name"]',
        '[class*="author-name"]', '[class*="user-name"]', '[class*="username"]',
      ]) {
        const value = compact(card.querySelector?.(selector)?.textContent);
        if (value && value.length <= 180) { author = value; break; }
      }
      for (const selector of ['.ti-date', 'time[datetime]', 'time', '[class*="review-date"]', '[class*="date"]']) {
        const node = card.querySelector?.(selector);
        const value = compact(node?.getAttribute?.('datetime') || node?.textContent);
        if (/20\d{2}[.\/-]\d{1,2}[.\/-]\d{1,2}/.test(value)) { dateText = value; break; }
      }
      const dateMatch = cardText.match(/\b(20\d{2}[.\/-]\d{1,2}[.\/-]\d{1,2})\b/);
      if (!dateText && dateMatch) dateText = dateMatch[1];
      if (!author && dateMatch && Number.isInteger(dateMatch.index)) {
        let prefix = compact(cardText.slice(0, dateMatch.index));
        prefix = prefix
          .replace(/^[★☆\s]+/, '')
          .replace(/^(?:Google|Trustindex)\s+/i, '')
          .replace(/\b(?:Google|Trustindex)\s*$/i, '')
          .replace(/^[0-5](?:[.,]\d)?\s*(?:\/\s*5|stars?|estrellas?)?\s*/i, '')
          .trim();
        if (prefix && prefix.length <= 180) author = prefix;
      }
      return { author, dateText };
    }

    for (const link of links) {
      const href = link.href || link.getAttribute('href') || '';
      const reportId = href.match(/\/id\/(\d+)/)?.[1] || href.match(/\/report\/(\d+)/)?.[1] || '';
      const card = findCard(link);
      if (!card) continue;
      const cardId = card.getAttribute?.('data-id') || '';
      const { author, dateText } = identity(card);
      const textNode = card.querySelector?.('.ti-review-text-container, .ti-review-content, [class*="review-text"], [class*="review-content"]');
      const text = compact(textNode?.textContent);
      const avatar = card.querySelector?.('.ti-profile-img img[src], .ti-profile-img [data-imgurl], img[alt*="profile"]');
      const avatarUrl = avatar?.getAttribute('src') || avatar?.getAttribute('data-imgurl') || '';

      let rating = card.querySelectorAll?.('.ti-stars .ti-star.f, .ti-stars img[src*="/star/f.svg"], .ti-stars [data-imgurl*="/star/f.svg"]').length || 0;
      if (rating < 1 || rating > 5) {
        const labels = Array.from(card.querySelectorAll?.('[aria-label], img[alt], [alt]') || [])
          .map((el) => `${el.getAttribute('aria-label') || ''} ${el.getAttribute('alt') || ''}`).join(' ');
        const match = labels.match(/([1-5])\s*(?:stars?|estrellas?)/i);
        rating = match ? Number.parseInt(match[1], 10) : 0;
      }
      if (!rating) {
        const stars = ((card.textContent || '').match(/★/g) || []).length;
        if (stars >= 1 && stars <= 5) rating = stars;
      }
      if (!rating) continue;

      const fallbackSeed = [author.toLowerCase(), dateText, String(rating), text].join('|');
      const id = reportId ? `trustindex-${reportId}` : (cardId ? `trustindex-card-${cardId}` : `trustindex-fallback-${fallbackSeed}`);
      if (seenIds.has(id)) continue;
      seenIds.add(id);
      reviews.push({
        id,
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

async function loadMore(page) {
  for (const selector of [
    '.ti-load-more-reviews-button',
    'button:has-text("More")', '[role="button"]:has-text("More")', 'a:has-text("More")',
    'button:has-text("Load more")', '[role="button"]:has-text("Load more")',
  ]) {
    const nodes = page.locator(selector);
    for (let index = 0; index < Math.min(await nodes.count(), 10); index += 1) {
      const node = nodes.nth(index);
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

const chromePath = ['/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium', '/usr/bin/chromium-browser'].find(fs.existsSync);
const browser = await chromium.launch(chromePath ? { headless:true, executablePath:chromePath, args:['--no-sandbox','--disable-dev-shm-usage'] } : { headless:true });
try {
  const context = await browser.newContext({ locale:'es-ES', viewport:{ width:1440, height:1100 }, userAgent:UA });
  const page = await context.newPage();
  page.setDefaultTimeout(10000);
  const response = await page.goto(PUBLIC_URL, { waitUntil:'domcontentloaded', timeout:90000 });
  if (response && response.status() >= 400) throw new Error(`Trustindex Google HTTP ${response.status()}`);
  await page.waitForTimeout(900);

  const meta = await summary(page);
  const target = mode === 'full' ? (meta.reportedCount || 303) : Math.min(meta.reportedCount || 20, 25);
  const map = new Map();
  let stable = 0;
  for (let loop = 0; loop < (mode === 'full' ? 40 : 1); loop += 1) {
    const batch = await collect(page);
    const before = map.size;
    for (const review of batch) {
      const id = review.id.startsWith('trustindex-fallback-') ? `trustindex-fallback-${sha(review.id)}` : review.id;
      map.set(id, { ...review, id, date: toIsoDate(review.date) });
    }
    if (map.size >= target) break;
    stable = map.size === before ? stable + 1 : 0;
    console.error(`GOOGLE_TRUSTINDEX_PROGRESS loop=${loop + 1} reviews=${map.size}/${target}`);
    const clicked = await loadMore(page);
    if (!clicked) {
      await page.keyboard.press('End').catch(() => {});
      await page.waitForTimeout(650);
    }
    if (stable >= 4 && !clicked) break;
  }

  const reviews = Array.from(map.values());
  if (!reviews.length) throw new Error('Trustindex no devolvió reseñas Google.');
  const missingAuthors = reviews.filter((review) => !String(review.author_name || '').trim());
  const missingDates = reviews.filter((review) => !String(review.date || '').trim());
  console.error(`GOOGLE_TRUSTINDEX_IDENTITY authors=${reviews.length - missingAuthors.length}/${reviews.length} dates=${reviews.length - missingDates.length}/${reviews.length}`);
  if (missingAuthors.length) throw new Error(`Trustindex devolvió ${missingAuthors.length} reseñas Google sin autor.`);

  const payload = {
    source:'google',
    provider:'trustindex_public',
    mode,
    scraped_at:new Date().toISOString(),
    reported_count:meta.reportedCount,
    rating:meta.rating,
    profile_url:PROFILE_URL,
    sorted_newest:true,
    reviews,
  };
  fs.writeFileSync(output, `${JSON.stringify(payload, null, 2)}\n`, 'utf8');
  console.log(JSON.stringify({ source:'google', provider:payload.provider, mode, found:reviews.length, reported_count:meta.reportedCount, rating:meta.rating, sorted_newest:true, output }));
  await context.close();
} finally {
  await browser.close();
}
