const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];
const checks = {};

async function go(page, path, delay = 650) {
  const url = `${BASE}${path}${path.includes('?') ? '&' : '?'}real-mobile=${Date.now()}`;
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
  await sleep(delay);
  if (!response || response.status() >= 400) failures.push(`${path}: HTTP ${response?.status() || 'none'}`);
}

async function metric(page, path, selector) {
  await go(page, path);
  const before = await page.evaluate((sel) => {
    const node = [...document.querySelectorAll(sel)].find((el) => {
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
    });
    const header = document.querySelector('.site-header');
    if (!node || !header) return null;
    const r = node.getBoundingClientRect();
    const h = header.getBoundingClientRect();
    return {
      top: Math.round((r.top + scrollY) * 10) / 10,
      gap: Math.round((r.top - h.bottom) * 10) / 10,
    };
  }, selector);
  if (!before) return null;
  await page.evaluate(() => scrollTo(0, 170));
  await sleep(280);
  const after = await page.evaluate((sel) => {
    const node = [...document.querySelectorAll(sel)].find((el) => {
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
    });
    if (!node) return null;
    const r = node.getBoundingClientRect();
    return Math.round((r.top + scrollY) * 10) / 10;
  }, selector);
  await page.evaluate(() => scrollTo(0, 0));
  return { ...before, after, delta: after === null ? null : Math.round(Math.abs(after - before.top) * 10) / 10 };
}

async function group(page, label, surfaces, tolerance = 2.5, expectedGap = null) {
  const values = [];
  for (const [path, selector] of surfaces) {
    const item = await metric(page, path, selector);
    checks[`${label}:${path}`] = item;
    if (!item) {
      failures.push(`${label} ${path}: surface missing (${selector})`);
      continue;
    }
    values.push([path, item.top, item.gap]);
    if (item.delta === null || item.delta > 2) failures.push(`${label} ${path}: moves on first scroll ${JSON.stringify(item)}`);
    if (expectedGap !== null && Math.abs(item.gap - expectedGap) > 2.5) failures.push(`${label} ${path}: header gap ${item.gap}px, expected ~${expectedGap}px`);
  }
  if (values.length > 1) {
    const tops = values.map(([, top]) => top);
    const spread = Math.round((Math.max(...tops) - Math.min(...tops)) * 10) / 10;
    checks[`${label}:spread`] = { spread, values };
    if (spread > tolerance) failures.push(`${label}: starts differ ${spread}px ${JSON.stringify(values)}`);
  }
}

async function checkPageStarts(page, width) {
  const paper = [
    ['/tienda/', '.emo-shop-lead .emo-kicker'],
    ['/quienes-somos/', '.emo-about-intro .emo-kicker'],
    ['/carrito/', '.emo-cart-intro .emo-kicker'],
    ['/finalizar-compra/', '.emo-checkout-intro .emo-kicker'],
  ];
  const green = [
    ['/contacto/', '.emo-contact-aside'],
    ['/contacto-productores/', '.emo-contact-aside'],
    ['/productores/', '.emo-producers-intro'],
    ['/blog/', '.emo-journal-hero__inner'],
  ];
  await group(page, `paper-${width}`, paper, 2.5, null);
  await group(page, `green-${width}`, green, 2.5, width <= 767 ? 18 : null);
}

async function checkFilter(page, width) {
  await page.setViewport({ width, height: 800, deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  await go(page, '/tienda/', 800);
  const toggle = await page.$('#emo-premium-filter-toggle');
  if (!toggle) {
    failures.push(`${width}px: filter toggle missing`);
    return;
  }
  await toggle.click();
  await sleep(650);
  const data = await page.evaluate(() => {
    const shell = document.querySelector('#emo-premium-filter-shell');
    const panel = shell?.querySelector('.emo-mobile-filter-panel');
    const content = shell?.querySelector('.emo-mobile-filter-content');
    const close = shell?.querySelector('.emo-mobile-filter-close');
    const headings = [...(shell?.querySelectorAll('.emo-mobile-filter-content .widget > .widget-title,.emo-mobile-filter-content .widget > .sidebar-heading,.emo-mobile-filter-content .widget > .widget-heading,.emo-mobile-filter-content .widget > .wp-block-heading') || [])].slice(0, 3);
    const headingData = headings.map((el) => {
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return { text:(el.textContent||'').replace(/\s+/g,' ').trim(), left:r.left, right:r.right, top:r.top, bottom:r.bottom, width:r.width, height:r.height, radius:s.borderRadius, background:s.backgroundColor, fontSize:s.fontSize };
    });
    const priceWidget = shell?.querySelector('.widget_price_filter');
    const amount = priceWidget?.querySelector('.price_slider_amount');
    const nextWidget = priceWidget?.nextElementSibling;
    const nextHeading = nextWidget?.querySelector(':scope > .widget-title,:scope > .sidebar-heading,:scope > .widget-heading,:scope > .wp-block-heading');
    const pw = priceWidget?.getBoundingClientRect();
    const nw = nextWidget?.getBoundingClientRect();
    const ar = amount?.getBoundingClientRect();
    const nh = nextHeading?.getBoundingClientRect();
    const track = shell?.querySelector('.widget_price_filter .price_slider.ui-slider,.widget_price_filter .ui-slider-horizontal');
    const handles = [...(shell?.querySelectorAll('.widget_price_filter .ui-slider-handle') || [])].slice(0, 2);
    const tr = track?.getBoundingClientRect();
    const hr = handles.map((el) => el.getBoundingClientRect());
    const cr = close?.getBoundingClientRect();
    const slider = tr && hr.length === 2 ? {
      track:{left:tr.left,right:tr.right,top:tr.top,bottom:tr.bottom,centerY:tr.top+tr.height/2},
      handles:hr.map((r)=>({left:r.left,right:r.right,top:r.top,bottom:r.bottom,centerX:r.left+r.width/2,centerY:r.top+r.height/2})),
      yDelta:hr.map((r)=>Math.abs((r.top+r.height/2)-(tr.top+tr.height/2))),
      xDelta:[Math.abs((hr[0].left+hr[0].width/2)-tr.left),Math.abs((hr[1].left+hr[1].width/2)-tr.right)],
    } : null;
    return {
      open:!!shell&&!shell.hidden,
      panelWidth:panel?.getBoundingClientRect().width||0,
      overflow:content ? content.scrollWidth-content.clientWidth : null,
      close:cr ? {width:cr.width,height:cr.height,radius:getComputedStyle(close).borderRadius} : null,
      headings:headingData,
      sectionGap:ar&&nh ? nh.top-ar.bottom : null,
      widgetGap:pw&&nw ? nw.top-pw.bottom : null,
      slider,
    };
  });
  checks[`filter-${width}`] = data;
  if (!data.open) failures.push(`${width}px: filter drawer did not open`);
  if (data.overflow !== null && data.overflow > 2) failures.push(`${width}px: horizontal filter overflow ${data.overflow}px`);
  if (!data.close || Math.abs(data.close.width-data.close.height)>1) failures.push(`${width}px: close control not circular ${JSON.stringify(data.close)}`);
  if (data.headings.length < 3) failures.push(`${width}px: filter headings missing`);
  else {
    const heights=data.headings.map(h=>h.height), widths=data.headings.map(h=>h.width);
    const radii=new Set(data.headings.map(h=>h.radius)), backgrounds=new Set(data.headings.map(h=>h.background)), sizes=new Set(data.headings.map(h=>h.fontSize));
    if (Math.max(...heights)-Math.min(...heights)>1 || Math.max(...widths)-Math.min(...widths)>1 || radii.size!==1 || backgrounds.size!==1 || sizes.size!==1) failures.push(`${width}px: section headings inconsistent ${JSON.stringify(data.headings)}`);
  }
  if (data.sectionGap===null || data.sectionGap<18) failures.push(`${width}px: price/categories content gap ${data.sectionGap}px`);
  if (data.widgetGap===null || data.widgetGap<12) failures.push(`${width}px: price widget overlaps next section ${data.widgetGap}px`);
  if (!data.slider) failures.push(`${width}px: slider missing`);
  else if (Math.max(...data.slider.yDelta)>1.5 || Math.max(...data.slider.xDelta)>2.5) failures.push(`${width}px: slider handles misaligned ${JSON.stringify(data.slider)}`);
  await page.screenshot({ path:`qa/real-filter-${width}.png`, fullPage:false });
}

(async()=>{
  fs.mkdirSync('qa',{recursive:true});
  const products=await fetch(`${BASE}/wp-json/wc/store/v1/products?per_page=100`).then(r=>r.json());
  const product=products.find(item=>item.is_purchasable&&item.is_in_stock&&item.type==='simple');
  if(!product) throw new Error('No purchasable simple product available');
  const browser=await puppeteer.launch({executablePath:'/usr/bin/google-chrome',headless:'new',protocolTimeout:120000,args:['--no-sandbox','--disable-dev-shm-usage']});
  const page=await browser.newPage();
  page.setDefaultNavigationTimeout(60000);
  await page.setUserAgent('Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0 Mobile Safari/537.36');
  try {
    await page.setViewport({width:360,height:800,deviceScaleFactor:1,isMobile:true,hasTouch:true});
    await go(page,`/contacto/?add-to-cart=${product.id}`,850);
    await checkPageStarts(page,360);
    for(const width of [360,375,390]) await checkFilter(page,width);
    await page.setViewport({width:390,height:844,deviceScaleFactor:1,isMobile:true,hasTouch:true});
    await checkPageStarts(page,390);
    await page.setViewport({width:1440,height:1000,deviceScaleFactor:1});
    await checkPageStarts(page,1440);
  } finally { await browser.close(); }
  fs.writeFileSync('qa/real-device-layout-check.json',JSON.stringify({failures,checks},null,2));
  if(failures.length){ console.error(failures.join('\n')); process.exitCode=2; }
  else console.log(`REAL_DEVICE_LAYOUT_OK ${JSON.stringify(checks)}`);
})();
