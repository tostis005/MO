const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function waitForRelease(page) {
  for (let i = 0; i < 30; i += 1) {
    const url = `${BASE}/?request-010208=${Date.now()}`;
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    const ready = await page.evaluate(() => !!document.getElementById('elmercado-header-search-copy-neutral-010208'));
    if (ready) return;
    await sleep(5000);
  }
  throw new Error('0.10.208 search copy layer not available on staging');
}

async function inspect(page, name, width, height) {
  await page.setViewport({ width, height, deviceScaleFactor: 1 });
  await page.goto(`${BASE}/?request-010208-${name}=${Date.now()}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(300);

  const trigger = await page.$('.header-search-icon');
  if (!trigger) {
    failures.push(`${name}: search trigger not found`);
    return;
  }
  await trigger.click();
  await sleep(250);

  const data = await page.evaluate(() => {
    const root = document.querySelector('.site-dialog-search');
    const title = root?.querySelector('.dialog-search-title');
    const input = root?.querySelector('input[type="search"], .search-field');
    const submit = root?.querySelector('button[type="submit"], input[type="submit"], .search-submit');
    const style = root ? getComputedStyle(root) : null;
    const rect = root?.getBoundingClientRect();
    const pseudo = title ? {
      before: getComputedStyle(title, '::before').content,
      after: getComputedStyle(title, '::after').content,
      beforeDisplay: getComputedStyle(title, '::before').display,
      afterDisplay: getComputedStyle(title, '::after').display,
    } : null;
    return {
      visible: !!root && style.display !== 'none' && style.visibility !== 'hidden' && rect && rect.width > 0 && rect.height > 0,
      title: title?.textContent?.trim() || '',
      placeholder: input?.getAttribute('placeholder') || '',
      inputAria: input?.getAttribute('aria-label') || '',
      submit: submit ? (submit.tagName === 'INPUT' ? submit.value : submit.textContent.trim()) : '',
      submitAria: submit?.getAttribute('aria-label') || '',
      pseudo,
      dialogText: root?.textContent?.replace(/\s+/g, ' ').trim() || '',
      controller: !!document.getElementById('elmercado-header-search-copy-neutral-controller-010208'),
    };
  });

  report[name] = data;
  if (!data.visible) failures.push(`${name}: search dialog not visible after clicking search`);
  if (data.title !== 'Buscar productos') failures.push(`${name}: title=${JSON.stringify(data.title)}`);
  if (data.placeholder !== 'Buscar productos') failures.push(`${name}: placeholder=${JSON.stringify(data.placeholder)}`);
  if (data.inputAria !== 'Buscar productos') failures.push(`${name}: input aria=${JSON.stringify(data.inputAria)}`);
  if (data.submit !== 'Buscar') failures.push(`${name}: submit=${JSON.stringify(data.submit)}`);
  if (data.submitAria !== 'Buscar') failures.push(`${name}: submit aria=${JSON.stringify(data.submitAria)}`);
  if (!data.controller) failures.push(`${name}: neutral copy controller missing`);
  if (data.pseudo && !['none', 'normal'].includes(data.pseudo.before)) failures.push(`${name}: marketing ::before still has content ${data.pseudo.before}`);
  if (data.pseudo && !['none', 'normal'].includes(data.pseudo.after)) failures.push(`${name}: marketing ::after still has content ${data.pseudo.after}`);
  if (/BUSCAR EN EL MERCADO|seleccionados con criterio|directamente desde su origen/i.test(data.dialogText)) failures.push(`${name}: marketing copy still visible in dialog text`);

  await page.screenshot({ path: `qa/request-010208-search-${name}.png`, fullPage: false });
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  try {
    const page = await browser.newPage();
    await waitForRelease(page);
    await inspect(page, 'desktop', 1440, 1000);
    await inspect(page, 'mobile', 390, 844);
    fs.writeFileSync('qa/request-010208-search-copy.json', JSON.stringify(report, null, 2));
    if (failures.length) {
      console.error('REQUEST_010208_FAIL', JSON.stringify(failures));
      process.exitCode = 2;
    } else {
      console.log('REQUEST_010208_OK', JSON.stringify(report));
    }
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
