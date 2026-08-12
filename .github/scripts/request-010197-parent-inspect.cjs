const puppeteer = require('puppeteer-core');

(async () => {
  const browser = await puppeteer.launch({ executablePath:'/usr/bin/google-chrome', headless:'new', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  await page.setViewport({ width:1440, height:1100 });
  await page.goto('https://dev.elmercadodeorigen.com/categoria-producto/jamones-paletas/?parent-inspect=1', { waitUntil:'domcontentloaded', timeout:60000 });
  await new Promise((r) => setTimeout(r, 2400));
  const data = await page.evaluate(() => {
    const nodes = {
      context: document.getElementById('emo-category-context'),
      price: document.querySelector('.widget_price_filter'),
      vendor: document.getElementById('emo-global-vendor-filter'),
      specific: document.getElementById('emo-category-attribute-filters'),
    };
    const info = (node) => {
      if (!node) return null;
      const p = node.parentElement;
      const r = node.getBoundingClientRect();
      const ps = p ? getComputedStyle(p) : null;
      const s = getComputedStyle(node);
      return {
        id: node.id || '',
        className: node.className || '',
        top: r.top,
        order: s.order,
        position: s.position,
        parentId: p?.id || '',
        parentClass: p?.className || '',
        parentDisplay: ps?.display || '',
        parentFlexDirection: ps?.flexDirection || '',
        parentChildren: p ? [...p.children].map((x) => ({ id:x.id || '', cls:x.className || '', order:getComputedStyle(x).order, top:x.getBoundingClientRect().top })).slice(0,12) : [],
      };
    };
    return Object.fromEntries(Object.entries(nodes).map(([k,v]) => [k, info(v)]));
  });
  console.log('PARENT_010197 ' + JSON.stringify(data));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(2); });
