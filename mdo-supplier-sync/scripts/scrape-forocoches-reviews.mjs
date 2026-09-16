import fs from 'node:fs';
import process from 'node:process';
import { chromium } from 'playwright';

const THREADS = [
  { id: '9308187', kind: 'ibericos', vendor_hint: 'Hidalgo de la Jara', url: 'https://forocoches.com/foro/showthread.php?t=9308187' },
  { id: '8107492', kind: 'aceite', vendor_hint: '1957', url: 'https://forocoches.com/foro/showthread.php?t=8107492' },
];
const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36';

function arg(name, fallback = '') {
  const direct = process.argv.find((value) => value.startsWith(`--${name}=`));
  if (direct) return direct.slice(name.length + 3);
  const index = process.argv.indexOf(`--${name}`);
  return index >= 0 && process.argv[index + 1] ? process.argv[index + 1] : fallback;
}

const mode = arg('mode', 'incremental').toLowerCase();
const output = arg('output', 'reviews-forocoches.json');
if (!['full', 'incremental'].includes(mode)) throw new Error('Use --mode full|incremental');

const monthMap = { ene: 0, feb: 1, mar: 2, abr: 3, may: 4, jun: 5, jul: 6, ago: 7, sep: 8, oct: 9, nov: 10, dic: 11 };
function spanishDateToIso(value) {
  const text = String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
  const match = text.match(/(\d{1,2})[-\s](ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic)[-\s](20\d{2})(?:\s+(\d{1,2}):(\d{2}))?/i);
  if (!match) return '';
  const date = new Date(Date.UTC(Number(match[3]), monthMap[match[2]], Number(match[1]), Number(match[4] || 12), Number(match[5] || 0)));
  return date.toISOString();
}

function compact(value) {
  return String(value || '').replace(/\u00a0/g, ' ').replace(/[ \t]+/g, ' ').replace(/\n{3,}/g, '\n\n').trim();
}

function reviewDecision(text) {
  const normalized = compact(text).toLowerCase();
  if (normalized.length < 20) return null;
  const strongNegative = /(no\s+recomiendo|p[eé]simo|fatal|rancio|decepci[oó]n|muy\s+malo|estafa)/i;
  if (strongNegative.test(normalized)) return null;

  const experience = /(recibid|recib[ií]|me\s+ha\s+llegado|me\s+lleg[oó]|nos\s+lleg[oó]|lleg[oó]\s+el|pedido\s+(?:hecho|recibido)|he\s+pedido|hemos\s+pedido|compr[eé]|hemos\s+comprado|he\s+comprado|repito|repetir[eé]|volver[eé]\s+a\s+comprar|he\s+probado|hemos\s+probado|despu[eé]s\s+de\s+haberme\s+comido|ya\s+puedo\s+opinar)/i;
  const strongPraise = /(100%\s+recomend|muy\s+content|encantad|excelente|incre[ií]ble|espectacular|brutal|saborazo|cojonud|delicia|buen[ií]sim|riqu[ií]sim|de\s+los\s+buenos|mejor\s+relaci[oó]n\s+calidad|trato\s+genial|trato\s+excelente|volver[eé]\s+a\s+comprar|repetir[eé]\s+seguro)/i;
  if (!experience.test(normalized) && !strongPraise.test(normalized)) return null;

  const positivePatterns = [
    /muy\s+bueno/gi, /buen[ií]sim/gi, /excelente/gi, /incre[ií]ble/gi, /espectacular/gi,
    /brutal/gi, /saborazo/gi, /repetir/gi, /volver[eé]\s+a\s+comprar/gi, /100%\s+recomend/gi,
    /recomiendo/gi, /muy\s+content/gi, /encantad/gi, /trato\s+genial/gi, /trato\s+excelente/gi,
    /r[aá]pid/gi, /calidad/gi, /delicia/gi, /cojonud/gi, /perfect/gi, /muy\s+rico/gi,
    /riqu[ií]sim/gi, /de\s+los\s+buenos/gi, /un\s+placer/gi, /sin\s+duda/gi,
  ];
  let positive = 0;
  for (const pattern of positivePatterns) positive += (normalized.match(pattern) || []).length;
  if (positive < 1 && !strongPraise.test(normalized)) return null;

  const mildNegatives = (normalized.match(/\b(problema|incidencia|retraso|tarde|demora|faltaba|equivocad|error|pero)\b/gi) || []).length;
  return { rating: mildNegatives > 0 ? 4 : 5, confidence: mildNegatives > 0 ? 0.88 : 0.96, rating_method: mildNegatives > 0 ? 'positive_with_minor_issue' : 'clear_positive' };
}

async function getThreadMeta(page, thread) {
  const response = await page.goto(thread.url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (response && response.status() >= 400) throw new Error(`Foro Coches hilo ${thread.id}: HTTP ${response.status()}`);
  await page.waitForTimeout(500);
  const meta = await page.evaluate((threadId) => {
    const hrefs = Array.from(document.querySelectorAll('a[href]')).map((a) => a.getAttribute('href') || '');
    let lastPage = 1;
    for (const href of hrefs) {
      if (!href.includes(`t=${threadId}`)) continue;
      const match = href.match(/[?&]page=(\d+)/);
      if (match) lastPage = Math.max(lastPage, Number(match[1]));
    }
    const body = document.body?.innerText || '';
    const title = document.title || '';
    return { lastPage, title, bodyStart: body.slice(0, 700), postBodies: document.querySelectorAll('[id^="post_message_"]').length };
  }, thread.id);
  if (!meta.postBodies) throw new Error(`Foro Coches hilo ${thread.id}: no se han encontrado mensajes; title=${meta.title}; body=${meta.bodyStart.replace(/\s+/g, ' ')}`);
  return meta;
}

async function collectPage(page, thread, pageNumber) {
  const url = `${thread.url}&page=${pageNumber}`;
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (response && response.status() >= 400) throw new Error(`Foro Coches ${thread.id} página ${pageNumber}: HTTP ${response.status()}`);
  await page.waitForTimeout(250);
  return page.evaluate(({ threadId, threadKind, vendorHint }) => {
    const clean = (value) => String(value || '').replace(/\u00a0/g, ' ').replace(/[ \t]+/g, ' ').replace(/\n{3,}/g, '\n\n').trim();
    const out = [];
    for (const body of Array.from(document.querySelectorAll('[id^="post_message_"]'))) {
      const id = (body.id.match(/post_message_(\d+)/) || [])[1] || '';
      if (!id) continue;
      const container = body.closest(`[id="post${id}"]`) || body.closest('table') || body.parentElement?.parentElement || body.parentElement;
      const clone = body.cloneNode(true);
      clone.querySelectorAll('blockquote,.quote,.bbcode_quote,[class*="quote"],[id*="quote"]').forEach((node) => node.remove());
      let text = clean(clone.innerText || clone.textContent || '');
      text = text.replace(/^Cita de .*?(?=\n\n|$)/gis, '').replace(/^Originalmente escrito por .*?(?=\n\n|$)/gis, '').trim();
      const userCandidates = container ? Array.from(container.querySelectorAll('a.bigusername,a[href*="member.php?u="],.username a,.username strong')) : [];
      const username = clean(userCandidates.map((node) => node.textContent).find(Boolean) || '');
      const containerText = clean(container?.innerText || container?.textContent || '');
      const dateMatch = containerText.match(/\b\d{1,2}-(?:ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic)-20\d{2}\s+\d{1,2}:\d{2}\b/i);
      const dateText = dateMatch ? dateMatch[0] : '';
      let sourceUrl = '';
      if (container) {
        const permalink = Array.from(container.querySelectorAll('a[href]')).find((a) => /showpost\.php\?p=|#post\d+/.test(a.getAttribute('href') || ''));
        if (permalink) sourceUrl = permalink.href;
      }
      if (!sourceUrl) sourceUrl = `https://forocoches.com/foro/showthread.php?p=${id}#post${id}`;
      out.push({ id, username, dateText, text, sourceUrl, thread_id: threadId, thread_kind: threadKind, vendor_hint: vendorHint });
    }
    return out;
  }, { threadId: thread.id, threadKind: thread.kind, vendorHint: thread.vendor_hint });
}

const chromePath = ['/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium', '/usr/bin/chromium-browser'].find(fs.existsSync);
const browser = await chromium.launch(chromePath ? { headless: true, executablePath: chromePath, args: ['--no-sandbox', '--disable-dev-shm-usage'] } : { headless: true });
try {
  const context = await browser.newContext({ locale: 'es-ES', viewport: { width: 1440, height: 1100 }, userAgent: UA });
  const page = await context.newPage();
  page.setDefaultTimeout(10000);
  const map = new Map();
  const threadStats = [];

  for (const thread of THREADS) {
    const meta = await getThreadMeta(page, thread);
    const startPage = mode === 'full' ? 1 : Math.max(1, meta.lastPage - 2);
    let scanned = 0;
    let accepted = 0;
    for (let pageNumber = startPage; pageNumber <= meta.lastPage; pageNumber += 1) {
      const posts = await collectPage(page, thread, pageNumber);
      scanned += posts.length;
      for (const post of posts) {
        if (!post.username || /mercado\s+origen/i.test(post.username)) continue;
        const decision = reviewDecision(post.text);
        if (!decision) continue;
        const date = spanishDateToIso(post.dateText);
        if (!date) continue;
        const id = `fc-${thread.id}-${post.id}`;
        map.set(id, {
          id,
          author_name: post.username,
          author_avatar_url: '',
          rating: decision.rating,
          title: '',
          text: post.text.slice(0, 2000),
          date,
          source_url: post.sourceUrl,
          thread_id: thread.id,
          thread_kind: thread.kind,
          vendor_hint: thread.vendor_hint,
          rating_method: decision.rating_method,
          sentiment_confidence: decision.confidence,
          transport: 'forocoches_public_playwright',
        });
        accepted += 1;
      }
      console.error(`FOROCOCHES_PROGRESS thread=${thread.id} page=${pageNumber}/${meta.lastPage} scanned=${scanned} accepted=${accepted}`);
      await page.waitForTimeout(120);
    }
    threadStats.push({ thread_id: thread.id, kind: thread.kind, pages: meta.lastPage, start_page: startPage, scanned, accepted });
  }

  const reviews = Array.from(map.values()).sort((a, b) => String(b.date).localeCompare(String(a.date)) || String(b.id).localeCompare(String(a.id)));
  if (mode === 'full' && !reviews.length) throw new Error('Foro Coches: no se detectó ninguna experiencia positiva en los hilos configurados.');
  const payload = {
    source: 'forocoches',
    provider: 'forocoches_public_playwright',
    mode,
    scraped_at: new Date().toISOString(),
    reported_count: reviews.length,
    available_count: reviews.length,
    historical_count: reviews.length,
    rating: 0,
    profile_url: THREADS[0].url,
    sorted_newest: true,
    thread_stats: threadStats,
    reviews,
  };
  fs.writeFileSync(output, `${JSON.stringify(payload, null, 2)}\n`, 'utf8');
  console.log(JSON.stringify({ source: payload.source, provider: payload.provider, mode, found: reviews.length, threads: threadStats, sorted_newest: true, output }));
  await context.close();
} finally {
  await browser.close();
}
