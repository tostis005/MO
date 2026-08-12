const puppeteer = require('puppeteer-core');

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
  await page.goto('https://dev.elmercadodeorigen.com/?request-010195-home-inspect=1', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await new Promise((resolve) => setTimeout(resolve, 1600));

  const report = await page.evaluate(() => {
    const interesting = [...document.querySelectorAll('main section, #primary section, .emo-home section')].map((section) => ({
      className: section.className || '',
      text: (section.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 240),
      links: [...section.querySelectorAll('a')].slice(0, 12).map((a) => ({
        className: a.className || '',
        text: (a.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 120),
        href: a.href,
        childClasses: [...a.children].map((child) => child.className || child.tagName).slice(0, 8),
      })),
    }));

    const candidates = [...document.querySelectorAll('a')]
      .filter((a) => /store|vendor|producer|productor|category|categoria/i.test(`${a.className} ${a.href} ${a.parentElement?.className || ''}`))
      .slice(0, 50)
      .map((a) => ({
        className: a.className || '',
        parentClass: a.parentElement?.className || '',
        text: (a.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 160),
        href: a.href,
        html: a.outerHTML.slice(0, 700),
      }));

    return { interesting, candidates };
  });

  console.log('HOME_010195_INSPECT ' + JSON.stringify(report));
  await browser.close();
})().catch((error) => {
  console.error(error);
  process.exit(2);
});
