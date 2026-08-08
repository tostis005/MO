const puppeteer = require('puppeteer-core');
const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const failures = [];

async function go(page, path, delay = 650) {
  await page.goto(`${BASE}${path}${path.includes('?') ? '&' : '?'}premium-visual=${Date.now()}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await sleep(delay);
}

const rgb = value => (value.match(/[\d.]+/g) || []).slice(0, 3).map(Number);
const luminance = ([r=0,g=0,b=0]) => {
  const c = [r,g,b].map(v => { v /= 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); });
  return .2126*c[0] + .7152*c[1] + .0722*c[2];
};
const contrast = (a,b) => { const l1=luminance(a), l2=luminance(b); return (Math.max(l1,l2)+.05)/(Math.min(l1,l2)+.05); };

(async () => {
  const browser = await puppeteer.launch({ executablePath:'/usr/bin/google-chrome', headless:'new', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  try {
    await page.setViewport({ width:390, height:844, deviceScaleFactor:1, isMobile:true, hasTouch:true });
    await go(page, '/');
    const home = await page.evaluate(() => {
      const panel = document.querySelector('.emo-story__panel');
      const p = panel?.querySelector('p');
      const list = document.querySelector('.emo-featured-products ul.products');
      const cards = [...(list?.querySelectorAll(':scope > li.product') || [])];
      const r0 = cards[0]?.getBoundingClientRect();
      const r1 = cards[1]?.getBoundingClientRect();
      const lr = list?.getBoundingClientRect();
      const arrows = [...document.querySelectorAll('.emo-featured-products .slick-arrow,.emo-featured-products .swiper-button-prev,.emo-featured-products .swiper-button-next,.emo-featured-products .owl-nav,.emo-featured-products .tns-controls')].filter(el => {
        const r=el.getBoundingClientRect(), s=getComputedStyle(el); return r.width>0&&r.height>0&&s.display!=='none'&&s.visibility!=='hidden';
      });
      return {
        panelBg: panel ? getComputedStyle(panel).backgroundColor : '',
        textColor: p ? getComputedStyle(p).color : '',
        cardRatio: r0 && lr ? r0.width / lr.width : 0,
        nextPeek: r1 && lr ? Math.max(0, Math.min(r1.right, lr.right) - Math.max(r1.left, lr.left)) : 0,
        arrows: arrows.length,
        horizontal: list ? list.scrollWidth > list.clientWidth + 10 : false,
      };
    });
    const ratio = contrast(rgb(home.panelBg), rgb(home.textColor));
    if (ratio < 4.5) failures.push(`home dark block contrast too low (${ratio.toFixed(2)} ${JSON.stringify(home)})`);
    if (home.arrows) failures.push(`home product carousel arrows still visible (${home.arrows})`);
    if (!home.horizontal || home.cardRatio < .72 || home.cardRatio > .9 || home.nextPeek < 20) failures.push(`home mobile carousel affordance invalid ${JSON.stringify(home)}`);

    const gutterPaths = [
      ['/tienda/', '#content > .woostify-container,.site-content > .woostify-container'],
      ['/productores/', '#content > .woostify-container,.site-content > .woostify-container'],
      ['/contacto/', '#content > .woostify-container,.site-content > .woostify-container'],
      ['/quienes-somos/', '#content > .woostify-container,.site-content > .woostify-container'],
      ['/blog/', '.emo-journal-hero__inner'],
      ['/tienda/hidalgo-de-la-jara/', '#content > .woostify-container,.site-content > .woostify-container'],
    ];
    const gutters = [];
    for (const [path, selector] of gutterPaths) {
      await go(page, path);
      const g = await page.evaluate(sel => {
        const el = document.querySelector(sel); if (!el) return null;
        const r=el.getBoundingClientRect(); return { left:Math.round(r.left), right:Math.round(innerWidth-r.right), width:Math.round(r.width) };
      }, selector);
      if (!g) failures.push(`${path}: gutter surface missing`); else gutters.push([path,g]);
    }
    if (gutters.length) {
      const sides = gutters.flatMap(([,g]) => [g.left,g.right]);
      if (Math.max(...sides) - Math.min(...sides) > 4) failures.push(`page gutters inconsistent ${JSON.stringify(gutters)}`);
    }

    /* Páginas que no dependen del estado de una sesión de compra. */
    const starts = [
      ['/tienda/', 'main.site-main .emo-kicker'],
      ['/quienes-somos/', '.emo-about-layout'],
      ['/contacto/', '.emo-contact-aside'],
      ['/productores/', '.emo-producers-intro'],
      ['/blog/', '.emo-journal-hero__inner'],
    ];
    const startTops = [];
    for (const [path, selector] of starts) {
      await go(page, path);
      const top = await page.evaluate(sel => {
        const el=document.querySelector(sel); if(!el)return null;
        const r=el.getBoundingClientRect(); return Math.round((r.top + scrollY) * 10) / 10;
      }, selector);
      if (top === null) failures.push(`${path}: content-start surface missing`);
      else startTops.push([path, top]);
    }
    if (startTops.length) {
      const values = startTops.map(([,top]) => top);
      if (Math.max(...values) - Math.min(...values) > 8) failures.push(`content starts not aligned ${JSON.stringify(startTops)}`);
    }

    /* El top absoluto del contenido debe ser inmutable al activar is-scrolled. */
    const headers = [
      ['/productores/', '.emo-producers-intro'],
      ['/contacto/', '.emo-contact-aside'],
      ['/quienes-somos/', '.emo-about-layout'],
      ['/blog/', '.emo-journal-hero__inner'],
      ['/tienda/', '#content'],
      ['/carrito/', '#content'],
      ['/finalizar-compra/', '#content'],
      ['/mi-cuenta/', '#content'],
    ];
    for (const [path, selector] of headers) {
      await go(page, path);
      const before = await page.evaluate(sel => { const e=document.querySelector(sel); if(!e)return null; const r=e.getBoundingClientRect(); return r.top + scrollY; }, selector);
      await page.evaluate(() => scrollTo(0, 180));
      await sleep(350);
      const after = await page.evaluate(sel => { const e=document.querySelector(sel); if(!e)return null; const r=e.getBoundingClientRect(); return r.top + scrollY; }, selector);
      if (before === null || after === null) failures.push(`${path}: scroll-stability surface missing`);
      else if (Math.abs(after-before) > 3) failures.push(`${path}: content jumps on scroll (${before} -> ${after})`);
    }
  } finally {
    await browser.close();
  }
  if (failures.length) { console.error(failures.join('\n')); process.exitCode=2; }
  else console.log('PREMIUM_VISUAL_SYSTEM_OK');
})();
