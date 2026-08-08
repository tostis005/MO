const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path) {
  const sep = path.includes('?') ? '&' : '?';
  await page.goto(`${BASE}${path}${sep}feedback-01095=${Date.now()}`, {
    waitUntil: 'domcontentloaded',
    timeout: 60000,
  });
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(850);
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

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const page = await browser.newPage();

  try {
    await page.setViewport({ width: 1440, height: 720, deviceScaleFactor: 1 });
    await go(page, '/tienda/');

    const desktop = await page.evaluate(() => {
      const isVisible = (node) => {
        if (!node) return false;
        const r = node.getBoundingClientRect();
        const s = getComputedStyle(node);
        return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
      };
      const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
      const rogue = [...document.querySelectorAll('.woostify-sorting button.filter,.woostify-sorting a.filter,.woostify-sorting .filter.show')].filter(isVisible);
      const ss = sidebar ? getComputedStyle(sidebar) : null;
      const navItem = document.querySelector('.primary-navigation > .emo-current-section,.primary-navigation > .current-menu-item,.primary-navigation > .current_page_item,.primary-navigation > .current-menu-ancestor,.primary-navigation > .current_page_parent');
      const navLink = navItem?.querySelector(':scope > a');
      const after = navLink ? getComputedStyle(navLink, '::after') : null;
      return {
        rogueToolbarFilters: rogue.length,
        sidebar: sidebar ? {
          clientHeight: sidebar.clientHeight,
          scrollHeight: sidebar.scrollHeight,
          overflowY: ss?.overflowY,
          maxHeight: ss?.maxHeight,
          position: ss?.position,
        } : null,
        nav: navItem ? {
          className: navItem.className,
          afterTransform: after?.transform,
          afterWidth: after?.width,
        } : null,
      };
    });

    if (desktop.rogueToolbarFilters !== 0) failures.push(`desktop: native toolbar filter visible (${desktop.rogueToolbarFilters})`);
    if (!desktop.sidebar) failures.push('desktop: sidebar missing');
    else {
      if (!['auto', 'scroll'].includes(desktop.sidebar.overflowY)) failures.push(`desktop: sidebar overflow-y is ${desktop.sidebar.overflowY}`);
      if (!desktop.sidebar.maxHeight || desktop.sidebar.maxHeight === 'none') failures.push('desktop: sidebar has no viewport max-height');
      if (desktop.sidebar.scrollHeight <= desktop.sidebar.clientHeight) failures.push(`desktop: sidebar does not overflow at 720px viewport (${desktop.sidebar.scrollHeight}/${desktop.sidebar.clientHeight})`);
      const scrolled = await page.evaluate(() => {
        const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
        if (!sidebar) return null;
        sidebar.scrollTop = 160;
        return { scrollTop: sidebar.scrollTop, clientHeight: sidebar.clientHeight, scrollHeight: sidebar.scrollHeight };
      });
      if (!scrolled || scrolled.scrollTop < 40) failures.push(`desktop: sticky rail cannot be internally scrolled (${JSON.stringify(scrolled)})`);
      report.desktopRailScroll = scrolled;
    }
    if (!desktop.nav) failures.push('desktop: active navigation section not detected on shop');
    else if (!desktop.nav.afterTransform || desktop.nav.afterTransform === 'none' || /matrix\([^,]+,\s*0,\s*0,\s*[^,]+,/.test(desktop.nav.afterTransform) && desktop.nav.afterTransform.startsWith('matrix(0')) {
      failures.push(`desktop: active navigation underline is not persistent (${desktop.nav.afterTransform})`);
    }

    const desktopFilterLink = await page.$('#secondary .widget_product_categories a,.shop-widget-area .widget_product_categories a');
    if (!desktopFilterLink) {
      failures.push('desktop: category filter link missing');
    } else {
      await desktopFilterLink.hover();
      await sleep(120);
      const hover = await desktopFilterLink.evaluate((node) => {
        const s = getComputedStyle(node);
        return { decoration:s.textDecorationLine, background:s.backgroundColor, color:s.color };
      });
      report.desktopFilterHover = hover;
      if (hover.decoration !== 'none') failures.push(`desktop: filter hover underlined (${hover.decoration})`);
      if (hover.background !== 'rgb(234, 242, 237)') failures.push(`desktop: filter hover lacks selected shading (${hover.background})`);
    }
    report.desktop = desktop;
    await page.screenshot({ path: 'qa/user-feedback-01095-shop-desktop.png', fullPage: true });

    await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 1 });
    await go(page, '/tienda/');
    const mobileToolbarRogue = await page.evaluate(() => [...document.querySelectorAll('.woostify-sorting button.filter,.woostify-sorting a.filter,.woostify-sorting .filter.show')].some((node) => {
      const r = node.getBoundingClientRect(); const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
    }));
    if (mobileToolbarRogue) failures.push('mobile: native toolbar filter visible');

    if (!(await visible(page, '#emo-premium-filter-toggle'))) failures.push('mobile: established filter toggle missing');
    else {
      await page.click('#emo-premium-filter-toggle');
      await sleep(220);
      if (!(await visible(page, '#emo-premium-filter-shell .emo-mobile-filter-panel'))) failures.push('mobile: established filter drawer did not open');
      const mobileFilterLink = await page.$('#emo-premium-filter-shell .widget_product_categories a');
      if (!mobileFilterLink) failures.push('mobile: category filter link missing');
      else {
        await mobileFilterLink.hover();
        await sleep(120);
        const hover = await mobileFilterLink.evaluate((node) => {
          const s = getComputedStyle(node);
          return { decoration:s.textDecorationLine, background:s.backgroundColor, color:s.color };
        });
        report.mobileFilterHover = hover;
        if (hover.decoration !== 'none') failures.push(`mobile: filter hover underlined (${hover.decoration})`);
        if (hover.background !== 'rgb(234, 242, 237)') failures.push(`mobile: filter hover lacks selected shading (${hover.background})`);
      }
      await page.screenshot({ path: 'qa/user-feedback-01095-filter-mobile.png', fullPage: true });
    }

    await go(page, '/');
    const mobileHome = await page.evaluate(() => {
      const featuredTitle = document.querySelector('.emo-featured-products .emo-section-heading h2');
      const story = document.querySelector('.emo-story__panel');
      const vendor = document.querySelector('.emo-vendor-cta');
      const footer = document.querySelector('.site-footer');
      const titleStyle = featuredTitle ? getComputedStyle(featuredTitle) : null;
      const titleRect = featuredTitle?.getBoundingClientRect();
      const storyStyle = story ? getComputedStyle(story) : null;
      const storyHeading = story?.querySelector('h2');
      const storyHeadingStyle = storyHeading ? getComputedStyle(storyHeading) : null;
      const vendorRect = vendor?.getBoundingClientRect();
      const footerRect = footer?.getBoundingClientRect();
      const footerBefore = footer ? getComputedStyle(footer, '::before') : null;
      const lineHeight = titleStyle ? parseFloat(titleStyle.lineHeight) : 0;
      return {
        title: featuredTitle ? {
          text:(featuredTitle.textContent || '').replace(/\s+/g,' ').trim(),
          height:titleRect?.height || 0,
          lineHeight,
          lines: lineHeight ? Math.round((titleRect?.height || 0) / lineHeight) : null,
          fontSize:titleStyle?.fontSize,
        } : null,
        story: story ? {
          backgroundColor:storyStyle?.backgroundColor,
          backgroundImage:storyStyle?.backgroundImage,
          borderRadius:storyStyle?.borderRadius,
          headingColor:storyHeadingStyle?.color,
          minHeight:storyStyle?.minHeight,
        } : null,
        footerGap: vendorRect && footerRect ? Math.round(footerRect.top - vendorRect.bottom) : null,
        footerBeforeDisplay: footerBefore?.display,
      };
    });
    report.mobileHome = mobileHome;
    if (!mobileHome.title) failures.push('mobile home: featured title missing');
    else if (mobileHome.title.lines > 3) failures.push(`mobile home: featured title still too fragmented (${mobileHome.title.lines} lines)`);
    if (!mobileHome.story) failures.push('mobile home: story panel missing');
    else {
      if (mobileHome.story.backgroundImage !== 'none') failures.push(`mobile home: dark story background remains (${mobileHome.story.backgroundImage})`);
      if (mobileHome.story.headingColor === 'rgb(255, 253, 248)' || mobileHome.story.headingColor === 'rgb(255, 255, 255)') failures.push(`mobile home: story text still uses dark-card light color (${mobileHome.story.headingColor})`);
    }
    if (mobileHome.footerGap !== null && Math.abs(mobileHome.footerGap) > 2) failures.push(`mobile home: residual strip before footer (${mobileHome.footerGap}px)`);
    if (mobileHome.footerBeforeDisplay && mobileHome.footerBeforeDisplay !== 'none') failures.push(`mobile home: footer separator line remains (${mobileHome.footerBeforeDisplay})`);
    await page.screenshot({ path: 'qa/user-feedback-01095-home-mobile.png', fullPage: true });

    await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
    await go(page, '/');
    const desktopTitle = await page.evaluate(() => {
      const title = document.querySelector('.emo-featured-products .emo-section-heading h2');
      if (!title) return null;
      const r = title.getBoundingClientRect(); const s = getComputedStyle(title); const lh = parseFloat(s.lineHeight);
      return { text:(title.textContent || '').replace(/\s+/g,' ').trim(), height:r.height, lineHeight:lh, lines:lh ? Math.round(r.height/lh) : null, fontSize:s.fontSize };
    });
    report.desktopHomeTitle = desktopTitle;
    if (!desktopTitle) failures.push('desktop home: featured title missing');
    else if (desktopTitle.lines > 2) failures.push(`desktop home: featured title too fragmented (${desktopTitle.lines} lines)`);
    await page.screenshot({ path: 'qa/user-feedback-01095-home-desktop.png', fullPage: true });
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/user-feedback-01095-check.json', JSON.stringify({ report, failures }, null, 2));
  if (failures.length) {
    console.error(`USER_FEEDBACK_01095_FAIL ${JSON.stringify(failures)}`);
    process.exitCode = 2;
  } else {
    console.log(`USER_FEEDBACK_01095_OK ${JSON.stringify(report)}`);
  }
})();
