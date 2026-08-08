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

  const hero = metrics.sections.find((s) => s.cls.includes('emo-hero'));
  const editorial = metrics.sections.filter((s) => s.cls.includes('emo-section'));
  const editorialMax = width <= 767 ? 34 : width <= 991 ? 46 : 62;
  const editorialMin = width <= 767 ? 28 : width <= 991 ? 38 : 50;
  const heroMax = width <= 767 ? 38 : width <= 991 ? 45 : 60;
  const heroMin = width <= 767 ? 30 : width <= 991 ? 34 : 46;

  if (metrics.overflow) failures.push(`${width}px: horizontal overflow on home`);
  if (!hero) failures.push(`${width}px: hero missing`);
  if (editorial.length < 4) failures.push(`${width}px: expected at least four editorial sections, got ${editorial.length}`);

  if (hero) {
    if (hero.paddingTop > heroMax + 1) failures.push(`${width}px: excessive hero top air (${hero.paddingTop}px)`);
    if (hero.paddingTop < heroMin - 1) failures.push(`${width}px: hero too compressed (${hero.paddingTop}px)`);
  }

  for (const section of editorial) {
    if (section.paddingTop > editorialMax + 1) failures.push(`${width}px: excessive top air in ${section.cls} (${section.paddingTop}px)`);
    if (section.paddingTop < editorialMin - 1) failures.push(`${width}px: section too compressed in ${section.cls} (${section.paddingTop}px)`);
  }

  if (editorial.length > 1) {
    const values = editorial.map((s) => s.paddingTop);
    const spread = Math.max(...values) - Math.min(...values);
    if (spread > 3) failures.push(`${width}px: inconsistent editorial top rhythm (${spread}px spread)`);
  }

  results[`${width}`] = metrics;
  await page.screenshot({ path: `qa/home-rhythm-01099-${width}.png`, fullPage: true });
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
    console.error(`HOME_RHYTHM_01099_FAIL ${JSON.stringify(failures)}`);
    process.exitCode = 2;
  } else {
    console.log(`HOME_RHYTHM_01099_OK ${JSON.stringify(results)}`);
  }
})();