const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path) {
  const sep = path.includes('?') ? '&' : '?';
  await page.goto(`${BASE}${path}${sep}feedback-01096=${Date.now()}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(900);
}

function isShade(value) {
  const match = String(value || '').match(/rgba?\((\d+)[, ]+\s*(\d+)[, ]+\s*(\d+)(?:[, /]+\s*([\d.]+))?\)/i);
  if (!match) return false;
  const [, r, g, b, a] = match;
  return Math.abs(Number(r) - 234) <= 2 && Math.abs(Number(g) - 242) <= 2 && Math.abs(Number(b) - 237) <= 2 && (a === undefined || Number(a) >= 0.9);
}

async function visible(page, selector) {
  return page.evaluate((sel) => {
    const node = document.querySelector(sel);
    if (!node) return false;
    const r = node.getBoundingClientRect();
    const s = getComputedStyle(node);
    return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
  }, selector);
}

async function nativeFilterCount(page) {
  return page.evaluate(() => [...document.querySelectorAll('.woostify-sorting button.filter,.woostify-sorting a.filter,.woostify-sorting .filter.show')].filter((node) => {
    const r = node.getBoundingClientRect();
    const s = getComputedStyle(node);
    return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
  }).length);
}

(async () => {
  const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  const page = await browser.newPage();

  try {
    await page.setViewport({ width: 1440, height: 720, deviceScaleFactor: 1 });
    await go(page, '/tienda/');

    const desktop = await page.evaluate(() => {
      const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
      const ss = sidebar ? getComputedStyle(sidebar) : null;
      const navItem = document.querySelector('.primary-navigation > .emo-current-section,.primary-navigation > .current-menu-item,.primary-navigation > .current_page_item,.primary-navigation > .current-menu-ancestor,.primary-navigation > .current_page_parent');
      const navLink = navItem?.querySelector(':scope > a');
      const after = navLink ? getComputedStyle(navLink, '::after') : null;
      return {
        sidebar: sidebar ? { clientHeight:sidebar.clientHeight, scrollHeight:sidebar.scrollHeight, overflowY:ss?.overflowY, maxHeight:ss?.maxHeight, position:ss?.position } : null,
        nav: navItem ? { className:navItem.className, afterTransform:after?.transform, afterWidth:after?.width } : null,
      };
    });
    report.desktop = desktop;
    report.desktopNativeFilters = await nativeFilterCount(page);
    if (report.desktopNativeFilters !== 0) failures.push(`desktop: native toolbar filter visible (${report.desktopNativeFilters})`);
    if (!desktop.sidebar) failures.push('desktop: sidebar missing');
    else {
      if (!['auto', 'scroll'].includes(desktop.sidebar.overflowY)) failures.push(`desktop: sidebar overflow-y is ${desktop.sidebar.overflowY}`);
      if (!desktop.sidebar.maxHeight || desktop.sidebar.maxHeight === 'none') failures.push('desktop: sidebar has no viewport max-height');
      if (desktop.sidebar.scrollHeight <= desktop.sidebar.clientHeight) failures.push(`desktop: sidebar does not overflow at 720px (${desktop.sidebar.scrollHeight}/${desktop.sidebar.clientHeight})`);
      report.desktopRailScroll = await page.evaluate(() => {
        const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
        if (!sidebar) return null;
        sidebar.scrollTop = 160;
        return { scrollTop:sidebar.scrollTop, clientHeight:sidebar.clientHeight, scrollHeight:sidebar.scrollHeight };
      });
      if (!report.desktopRailScroll || report.desktopRailScroll.scrollTop < 40) failures.push(`desktop: sticky rail cannot be internally scrolled (${JSON.stringify(report.desktopRailScroll)})`);
    }
    if (!desktop.nav) failures.push('desktop: active navigation section not detected');
    else if (!desktop.nav.afterTransform || desktop.nav.afterTransform === 'none' || desktop.nav.afterTransform.startsWith('matrix(0')) failures.push(`desktop: current section underline missing (${desktop.nav.afterTransform})`);

    const desktopFilterLink = await page.$('#secondary .widget_product_categories a,.shop-widget-area .widget_product_categories a');
    if (!desktopFilterLink) failures.push('desktop: category filter link missing');
    else {
      await desktopFilterLink.hover();
      await sleep(120);
      report.desktopFilterHover = await desktopFilterLink.evaluate((node) => { const s = getComputedStyle(node); return { decoration:s.textDecorationLine, background:s.backgroundColor, color:s.color }; });
      if (report.desktopFilterHover.decoration !== 'none') failures.push(`desktop: filter hover underlined (${report.desktopFilterHover.decoration})`);
      if (!isShade(report.desktopFilterHover.background)) failures.push(`desktop: filter hover lacks selected shading (${report.desktopFilterHover.background})`);
    }
    await page.screenshot({ path:'qa/user-feedback-01096-shop-desktop.png', fullPage:true });

    await page.setViewport({ width:390, height:844, deviceScaleFactor:1 });
    await go(page, '/tienda/');
    report.mobileNativeFilters = await nativeFilterCount(page);
    if (report.mobileNativeFilters !== 0) failures.push(`mobile: native toolbar filter visible (${report.mobileNativeFilters})`);
    if (!(await visible(page, '#emo-premium-filter-toggle'))) failures.push('mobile: established filter toggle missing');
    else {
      await page.click('#emo-premium-filter-toggle');
      await sleep(250);
      if (!(await visible(page, '#emo-premium-filter-shell .emo-mobile-filter-panel'))) failures.push('mobile: filter drawer did not open');
      const mobileFilterLink = await page.$('#emo-premium-filter-shell .widget_product_categories a');
      if (!mobileFilterLink) failures.push('mobile: category filter link missing');
      else {
        await mobileFilterLink.hover();
        await sleep(120);
        report.mobileFilterHover = await mobileFilterLink.evaluate((node) => { const s = getComputedStyle(node); return { decoration:s.textDecorationLine, background:s.backgroundColor, color:s.color }; });
        if (report.mobileFilterHover.decoration !== 'none') failures.push(`mobile: filter hover underlined (${report.mobileFilterHover.decoration})`);
        if (!isShade(report.mobileFilterHover.background)) failures.push(`mobile: filter hover lacks selected shading (${report.mobileFilterHover.background})`);
      }
      await page.screenshot({ path:'qa/user-feedback-01096-filter-mobile.png' });
    }

    await go(page, '/');
    report.mobileHome = await page.evaluate(() => {
      const title = document.querySelector('.emo-featured-products .emo-section-heading h2');
      const story = document.querySelector('.emo-story__panel');
      const vendor = document.querySelector('.emo-vendor-cta');
      const footer = document.querySelector('.site-footer');
      const ts = title ? getComputedStyle(title) : null;
      const tr = title?.getBoundingClientRect();
      const ss = story ? getComputedStyle(story) : null;
      const sh = story?.querySelector('h2');
      const shs = sh ? getComputedStyle(sh) : null;
      const vr = vendor?.getBoundingClientRect();
      const fr = footer?.getBoundingClientRect();
      const before = footer ? getComputedStyle(footer, '::before') : null;
      const lh = ts ? parseFloat(ts.lineHeight) : 0;
      return {
        title:title ? { text:(title.textContent||'').replace(/\s+/g,' ').trim(), height:tr?.height||0, lineHeight:lh, lines:lh ? Math.round((tr?.height||0)/lh) : null, fontSize:ts?.fontSize } : null,
        story:story ? { backgroundColor:ss?.backgroundColor, backgroundImage:ss?.backgroundImage, borderRadius:ss?.borderRadius, headingColor:shs?.color, minHeight:ss?.minHeight } : null,
        footerGap:vr && fr ? Math.round(fr.top-vr.bottom) : null,
        footerBeforeDisplay:before?.display,
      };
    });
    if (!report.mobileHome.title) failures.push('mobile home: featured title missing');
    else if (report.mobileHome.title.lines > 3) failures.push(`mobile home: featured title too fragmented (${report.mobileHome.title.lines} lines)`);
    if (!report.mobileHome.story) failures.push('mobile home: story panel missing');
    else {
      if (report.mobileHome.story.backgroundImage !== 'none') failures.push(`mobile home: dark story background remains (${report.mobileHome.story.backgroundImage})`);
      if (['rgb(255, 253, 248)','rgb(255, 255, 255)'].includes(report.mobileHome.story.headingColor)) failures.push(`mobile home: story still uses light text (${report.mobileHome.story.headingColor})`);
    }
    if (report.mobileHome.footerGap !== null && Math.abs(report.mobileHome.footerGap) > 2) failures.push(`mobile home: residual strip before footer (${report.mobileHome.footerGap}px)`);
    if (report.mobileHome.footerBeforeDisplay && report.mobileHome.footerBeforeDisplay !== 'none') failures.push(`mobile home: footer separator remains (${report.mobileHome.footerBeforeDisplay})`);
    await page.screenshot({ path:'qa/user-feedback-01096-home-mobile.png', fullPage:true });

    await page.setViewport({ width:1440, height:1000, deviceScaleFactor:1 });
    await go(page, '/');
    report.desktopHomeTitle = await page.evaluate(() => {
      const title = document.querySelector('.emo-featured-products .emo-section-heading h2');
      if (!title) return null;
      const r=title.getBoundingClientRect(), s=getComputedStyle(title), lh=parseFloat(s.lineHeight);
      return { text:(title.textContent||'').replace(/\s+/g,' ').trim(), height:r.height, lineHeight:lh, lines:lh ? Math.round(r.height/lh) : null, fontSize:s.fontSize };
    });
    if (!report.desktopHomeTitle) failures.push('desktop home: featured title missing');
    else if (report.desktopHomeTitle.lines > 2) failures.push(`desktop home: featured title too fragmented (${report.desktopHomeTitle.lines} lines)`);
    await page.screenshot({ path:'qa/user-feedback-01096-home-desktop.png', fullPage:true });
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/user-feedback-01096-check.json', JSON.stringify({ report, failures }, null, 2));
  if (failures.length) {
    console.error(`USER_FEEDBACK_01096_FAIL ${JSON.stringify(failures)}`);
    process.exitCode = 2;
  } else {
    console.log(`USER_FEEDBACK_01096_OK ${JSON.stringify(report)}`);
  }
})();
