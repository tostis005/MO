const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const results = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function inspect(page, width, height) {
  await page.setViewport({ width, height, deviceScaleFactor: 1 });
  await page.goto(`${BASE}/?home-rhythm=${Date.now()}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await sleep(900);

  const metrics = await page.evaluate(() => {
    const sections = [...document.querySelectorAll('.emo-home > section')].map((section) => {
      const style = getComputedStyle(section);
      const rect = section.getBoundingClientRect();
      const label = section.querySelector('.emo-kicker,.emo-eyebrow,h2,h3')?.textContent?.trim() || '';
      return {
        cls: section.className,
        label: label.slice(0, 90),
        paddingTop: parseFloat(style.paddingTop) || 0,
        paddingBottom: parseFloat(style.paddingBottom) || 0,
        top: Math.round(rect.top + window.scrollY),
        height: Math.round(rect.height),
      };
    });

    return {
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 3,
      sections,
    };
  });

  const editorial = metrics.sections.filter((s) => !s.cls.includes('emo-hero') && !s.cls.includes('emo-trust'));
  const maxTop = width <= 767 ? 46 : width <= 991 ? 62 : 90;
  const minTop = width <= 767 ? 32 : 40;

  if (metrics.overflow) failures.push(`${width}px: horizontal overflow on home`);
  if (editorial.length < 4) failures.push(`${width}px: expected at least four editorial sections, got ${editorial.length}`);

  for (const section of editorial) {
    if (section.paddingTop > maxTop + 1) failures.push(`${width}px: excessive top air in ${section.cls} (${section.paddingTop}px)`);
    if (section.paddingTop < minTop - 1) failures.push(`${width}px: section too compressed in ${section.cls} (${section.paddingTop}px)`);
  }

  const normal = editorial.filter((s) => !s.cls.includes('emo-story') && !s.cls.includes('emo-vendor-cta'));
  if (normal.length > 1) {
    const values = normal.map((s) => s.paddingTop);
    const spread = Math.max(...values) - Math.min(...values);
    if (spread > 3) failures.push(`${width}px: inconsistent main editorial top rhythm (${spread}px spread)`);
  }

  results[`${width}`] = metrics;
  await page.screenshot({ path: `qa/home-rhythm-01098-${width}.png`, fullPage: true });
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  const page = await browser.newPage();
  try {
    await inspect(page, 390, 844);
    await inspect(page, 768, 900);
    await inspect(page, 1440, 900);
  } finally {
    await browser.close();
  }

  if (failures.length) {
    console.error(`HOME_RHYTHM_01098_FAIL ${JSON.stringify(failures)}`);
    process.exitCode = 2;
  } else {
    console.log(`HOME_RHYTHM_01098_OK ${JSON.stringify(results)}`);
  }
})();