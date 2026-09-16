import fs from 'node:fs';
import process from 'node:process';
import { chromium } from 'playwright';

const PROFILE_URL = 'https://es.trustpilot.com/review/elmercadodeorigen.com';
const DESKTOP_UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36';

function arg(name, fallback = '') {
  const prefix = `--${name}=`;
  const direct = process.argv.find((value) => value.startsWith(prefix));
  if (direct) return direct.slice(prefix.length);
  const index = process.argv.indexOf(`--${name}`);
  if (index >= 0 && process.argv[index + 1]) return process.argv[index + 1];
  return fallback;
}

const mode = arg('mode', 'incremental').toLowerCase();
const output = arg('output', 'reviews-trustpilot.json');
if (!['full', 'incremental'].includes(mode)) throw new Error('Use --mode full|incremental');

const chromePath = ['/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium', '/usr/bin/chromium-browser'].find(fs.existsSync);
const launchOptions = chromePath
  ? { headless: true, executablePath: chromePath, args: ['--no-sandbox', '--disable-dev-shm-usage'] }
  : { headless: true };

async function clickConsent(page) {
  for (const pattern of [/Aceptar todo/i, /Accept all/i, /Estoy de acuerdo/i, /I agree/i, /Continuar/i, /Continue/i]) {
    const button = page.getByRole('button', { name: pattern }).first();
    if (!await button.count()) continue;
    try {
      if (await button.isVisible({ timeout: 400 })) {
        await button.click({ timeout: 2000 });
        await page.waitForTimeout(250);
      }
    } catch {}
  }
}

function normalizeReview(raw) {
  if (!raw || typeof raw !== 'object') return null;
  const id = String(raw.id || '').trim();
  const rating = Math.round(Number(raw.rating || raw.stars || 0));
  if (!id || rating < 1 || rating > 5) return null;
  const consumer = raw.consumer && typeof raw.consumer === 'object' ? raw.consumer : {};
  const dates = raw.dates && typeof raw.dates === 'object' ? raw.dates : {};
  const labels = raw.labels && typeof raw.labels === 'object' ? raw.labels : {};
  const verification = labels.verification && typeof labels.verification === 'object' ? labels.verification : {};
  return {
    id,
    author_name: String(consumer.displayName || consumer.name || '').trim(),
    author_avatar_url: String(consumer.imageUrl || consumer.image || '').trim(),
    rating,
    title: String(raw.title || '').trim(),
    text: String(raw.text || '').trim(),
    date: String(dates.publishedDate || dates.experiencedDate || raw.createdAt || raw.updatedAt || '').trim(),
    source_url: `https://es.trustpilot.com/reviews/${encodeURIComponent(id)}`,
    verified: Boolean(verification.isVerified || raw.isVerified),
    transport: 'trustpilot_next_data_playwright',
  };
}

async function readNextData(page) {
  return page.evaluate(() => {
    const node = document.querySelector('script#__NEXT_DATA__');
    if (!node?.textContent) return { error: '__NEXT_DATA__ missing' };
    let data;
    try {
      data = JSON.parse(node.textContent);
    } catch (error) {
      return { error: `__NEXT_DATA__ invalid: ${String(error)}` };
    }
    const pp = data?.props?.pageProps || {};
    const pagination = pp?.filters?.pagination || {};
    return {
      reviews: Array.isArray(pp.reviews) ? pp.reviews : [],
      activeCount: Number(pagination.totalCount ?? pp?.filters?.totalNumberOfFilteredReviews ?? pp?.filters?.totalNumberOfReviews ?? 0) || 0,
      historicalCount: Number(pp?.businessUnit?.numberOfReviews ?? pp?.filters?.reviewStatistics?.ratings?.total ?? 0) || 0,
      totalPages: Number(pagination.totalPages ?? 0) || 0,
      currentPage: Number(pagination.currentPage ?? 0) || 0,
      rating: Number(pp?.businessUnit?.trustScore ?? 0) || 0,
      pageUrl: String(pp?.pageUrl || location.href),
      title: document.title,
      href: location.href,
      bodyStart: String(document.body?.innerText || '').slice(0, 500),
    };
  });
}

async function fetchPage(page, pageNumber) {
  const url = `${PROFILE_URL}?languages=all&page=${pageNumber}&sort=recency`;
  let last = null;
  for (let attempt = 1; attempt <= 3; attempt += 1) {
    try {
      const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
      if (response && response.status() >= 400 && response.status() !== 404) {
        throw new Error(`HTTP ${response.status()}`);
      }
      await clickConsent(page);
      await page.waitForTimeout(900 + (attempt - 1) * 500);
      const data = await readNextData(page);
      if (!data.error && Array.isArray(data.reviews) && (data.reviews.length > 0 || pageNumber > (data.totalPages || 0))) {
        return data;
      }
      last = new Error(`${data.error || 'no reviews'}; title=${data.title || ''}; href=${data.href || ''}; body=${String(data.bodyStart || '').replace(/\s+/g, ' ')}`);
    } catch (error) {
      last = error;
    }
    await page.waitForTimeout(1000 * attempt);
  }
  throw new Error(`Trustpilot página ${pageNumber}: ${String(last || 'sin datos')}`);
}

const browser = await chromium.launch(launchOptions);
try {
  const context = await browser.newContext({ locale: 'es-ES', viewport: { width: 1440, height: 1100 }, userAgent: DESKTOP_UA });
  const page = await context.newPage();
  page.setDefaultTimeout(12000);

  const map = new Map();
  const first = await fetchPage(page, 1);
  const activeCount = first.activeCount;
  const historicalCount = first.historicalCount;
  const totalPages = first.totalPages || Math.max(1, Math.ceil((activeCount || first.reviews.length) / 20));
  const rating = first.rating;
  const pagesToFetch = mode === 'full' ? totalPages : Math.min(2, totalPages);

  for (let pageNumber = 1; pageNumber <= pagesToFetch; pageNumber += 1) {
    const data = pageNumber === 1 ? first : await fetchPage(page, pageNumber);
    let added = 0;
    for (const raw of data.reviews) {
      const review = normalizeReview(raw);
      if (!review || map.has(review.id)) continue;
      map.set(review.id, review);
      added += 1;
    }
    console.error(`TRUSTPILOT_PLAYWRIGHT_PROGRESS page=${pageNumber}/${pagesToFetch} raw=${data.reviews.length} added=${added} total=${map.size}/${activeCount || '?'} historical=${historicalCount || '?'}`);
  }

  if (!map.size) throw new Error('Trustpilot no devolvió reseñas públicas activas.');
  if (mode === 'full' && activeCount > 0 && map.size < activeCount) {
    throw new Error(`Trustpilot full incompleto: ${map.size}/${activeCount} reseñas activas (histórico ${historicalCount || '?'}).`);
  }

  // Trustpilot puede publicar transitoriamente un total filtrado inferior al
  // número de filas que sus propias páginas oficiales exponen (p. ej. 143 vs
  // 145). En full, las filas realmente paginadas constituyen el mínimo
  // verificable; nunca persistimos un objetivo menor que lo capturado.
  const effectiveActiveCount = mode === 'full'
    ? Math.max(activeCount || 0, map.size)
    : (activeCount || map.size);

  const payload = {
    source: 'trustpilot',
    provider: 'trustpilot_public_playwright_next_data',
    mode,
    scraped_at: new Date().toISOString(),
    reported_count: effectiveActiveCount,
    available_count: effectiveActiveCount,
    historical_count: historicalCount,
    pagination_pages: totalPages,
    rating,
    profile_url: PROFILE_URL,
    sorted_newest: true,
    reviews: Array.from(map.values()),
  };
  fs.writeFileSync(output, `${JSON.stringify(payload, null, 2)}\n`, 'utf8');
  console.log(JSON.stringify({
    source: payload.source,
    provider: payload.provider,
    mode: payload.mode,
    found: payload.reviews.length,
    reported_count: payload.reported_count,
    historical_count: payload.historical_count,
    pages: payload.pagination_pages,
    rating: payload.rating,
    sorted_newest: payload.sorted_newest,
    output,
  }));
  await context.close();
} finally {
  await browser.close();
}
