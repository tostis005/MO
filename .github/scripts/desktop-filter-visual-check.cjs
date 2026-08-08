const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const widths = [1101, 1200, 1440];
const cases = [
  { key: 'shop', path: '/tienda/', sticky: true },
  { key: 'category', path: '/categoria-producto/aceites/', sticky: false },
  { key: 'tag', path: '/etiqueta-producto/aceite/', sticky: false },
];
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path) {
  const separator = path.includes('?') ? '&' : '?';
  await page.goto(`${BASE}${path}${separator}desktop-filter-qa=${Date.now()}`, {
    waitUntil: 'domcontentloaded',
    timeout: 60000,
  });
  await page.addStyleTag({
    content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}',
  }).catch(() => {});
  await sleep(1000);
}

async function snapshot(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      if (!node) return false;
      const rect = node.getBoundingClientRect();
      const style = getComputedStyle(node);
      return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
    };
    const rect = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      return { left:r.left, right:r.right, top:r.top, bottom:r.bottom, width:r.width, height:r.height };
    };
    const overlaps = (a, b) => !!a && !!b && !(a.right <= b.left || b.right <= a.left || a.bottom <= b.top || b.bottom <= a.top);

    const layout = document.querySelector('#content.site-content > .woostify-container');
    const primary = document.querySelector('#primary.content-area');
    const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
    const toolbar = document.querySelector('.woostify-sorting');
    const toggle = document.querySelector('#emo-premium-filter-toggle,.emo-mobile-filter-toggle');
    const sidebarRect = rect(sidebar);
    const toolbarRect = rect(toolbar);
    const sidebarStyle = sidebar ? getComputedStyle(sidebar) : null;
    const layoutStyle = layout ? getComputedStyle(layout) : null;
    const primaryStyle = primary ? getComputedStyle(primary) : null;
    const headings = sidebar ? [...sidebar.querySelectorAll('.widget-title,.widgettitle,.sidebar-heading,.widget-heading,.wp-block-heading')].filter(visible).map((node) => {
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return {
        text:(node.textContent || '').replace(/\s+/g,' ').trim(),
        top:r.top,
        width:r.width,
        height:r.height,
        background:s.backgroundColor,
        color:s.color,
        align:s.textAlign,
        radius:s.borderRadius,
        size:s.fontSize,
      };
    }) : [];

    const slider = sidebar?.querySelector('.price_slider');
    const sliderRect = rect(slider);
    const handles = slider ? [...slider.querySelectorAll('.ui-slider-handle')].filter(visible).map((node) => {
      const r = node.getBoundingClientRect();
      return { left:r.left, right:r.right, top:r.top, bottom:r.bottom, centerX:r.left + r.width / 2, centerY:r.top + r.height / 2, width:r.width, height:r.height };
    }) : [];
    const sliderCenterY = sliderRect ? sliderRect.top + sliderRect.height / 2 : null;

    const filterButton = sidebar?.querySelector('.price_slider_amount .button');
    const priceLabel = sidebar?.querySelector('.price_slider_amount .price_label');
    const filterButtonRect = rect(filterButton);
    const priceLabelRect = rect(priceLabel);
    const tagWidget = sidebar?.querySelector('.widget_product_tag_cloud,.widget_tag_cloud');
    const tagLinks = tagWidget ? [...tagWidget.querySelectorAll('.tagcloud a')].filter(visible) : [];
    const tagOverflow = tagLinks.some((node) => {
      const r = node.getBoundingClientRect();
      return sidebarRect && (r.left < sidebarRect.left - 1 || r.right > sidebarRect.right + 1);
    });
    const firstHeadingTop = headings[0]?.top ?? null;

    return {
      viewport: window.innerWidth,
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
      layoutDisplay: layoutStyle?.display || '',
      layoutOverflowX: layoutStyle?.overflowX || '',
      layoutOverflowY: layoutStyle?.overflowY || '',
      primaryFloat: primaryStyle?.float || '',
      sidebarVisible: visible(sidebar),
      toggleVisible: visible(toggle),
      sidebarInSiteContent: !!sidebar?.closest('.site-content'),
      sidebarRect,
      toolbarRect,
      headingToolbarDelta: firstHeadingTop !== null && toolbarRect ? Math.round(firstHeadingTop - toolbarRect.top) : null,
      position: sidebarStyle?.position || '',
      top: sidebarStyle?.top || '',
      padding: sidebarStyle?.padding || '',
      headings,
      sliderRect,
      handles,
      sliderYDelta: sliderCenterY === null ? [] : handles.map((handle) => Math.round((handle.centerY - sliderCenterY) * 10) / 10),
      filterButtonRect,
      priceLabelRect,
      priceRowOverlap: overlaps(filterButtonRect, priceLabelRect),
      tagsPresent: !!tagWidget,
      tagCount: tagLinks.length,
      tagOverflow,
    };
  });
}

function assertInitial(initial, label) {
  if (initial.overflow) failures.push(`${label}: horizontal overflow`);
  if (initial.layoutDisplay !== 'flex') failures.push(`${label}: catalog layout is not flex (${initial.layoutDisplay})`);
  if (initial.layoutOverflowX !== 'visible' || initial.layoutOverflowY !== 'visible') failures.push(`${label}: sticky ancestor clips overflow (${initial.layoutOverflowX}/${initial.layoutOverflowY})`);
  if (initial.primaryFloat !== 'none') failures.push(`${label}: primary still floated (${initial.primaryFloat})`);
  if (!initial.sidebarVisible || !initial.sidebarInSiteContent) failures.push(`${label}: desktop sidebar missing from site content`);
  if (initial.toggleVisible) failures.push(`${label}: compact filter toggle visible on desktop`);
  if (initial.position !== 'sticky') failures.push(`${label}: sidebar not sticky (${initial.position})`);
  if (!initial.sidebarRect || initial.sidebarRect.width < 240 || initial.sidebarRect.width > 270) failures.push(`${label}: sidebar width out of range (${initial.sidebarRect?.width})`);
  if (initial.headingToolbarDelta === null || Math.abs(initial.headingToolbarDelta) > 30) failures.push(`${label}: first filter heading not aligned with catalog toolbar (${initial.headingToolbarDelta}px)`);
  if (initial.headings.length < 3) failures.push(`${label}: missing filter section headings (${initial.headings.length})`);
  initial.headings.forEach((heading, index) => {
    if (heading.height < 37 || heading.height > 44) failures.push(`${label}: heading ${index + 1} height ${heading.height}`);
    if (heading.background !== 'rgb(23, 63, 50)') failures.push(`${label}: heading ${index + 1} background ${heading.background}`);
    if (heading.color !== 'rgb(255, 255, 255)') failures.push(`${label}: heading ${index + 1} color ${heading.color}`);
    if (heading.align !== 'center') failures.push(`${label}: heading ${index + 1} not centered`);
  });
  if (!initial.sliderRect || initial.handles.length !== 2) failures.push(`${label}: price slider incomplete`);
  if (initial.sliderYDelta.some((value) => Math.abs(value) > 1)) failures.push(`${label}: slider handles not centered ${JSON.stringify(initial.sliderYDelta)}`);
  if (initial.priceRowOverlap) failures.push(`${label}: price button and label overlap`);
  if (!initial.tagsPresent || initial.tagCount < 1) failures.push(`${label}: product tag filters missing`);
  if (initial.tagOverflow) failures.push(`${label}: tag chips overflow sidebar`);
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const page = await browser.newPage();

  try {
    for (const width of widths) {
      await page.setViewport({ width, height: 1000, deviceScaleFactor: 1 });
      for (const current of cases) {
        await go(page, current.path);
        const initial = await snapshot(page);
        const label = `${current.key}-${width}px`;
        await page.screenshot({ path: `qa/desktop-filter-${current.key}-${width}.png`, fullPage: true });
        assertInitial(initial, label);

        let afterScroll = null;
        if (current.sticky) {
          await page.evaluate(() => window.scrollTo(0, 700));
          await sleep(220);
          afterScroll = await page.evaluate(() => {
            const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
            const r = sidebar?.getBoundingClientRect();
            return r ? { top:r.top, bottom:r.bottom, height:r.height, scrollY:window.scrollY } : null;
          });
          if (!afterScroll || afterScroll.scrollY < 500) failures.push(`${label}: page did not scroll far enough (${afterScroll?.scrollY})`);
          if (afterScroll && Math.abs(afterScroll.top - 94) > 4) failures.push(`${label}: sticky top unstable after scroll (${afterScroll.top}px)`);
        }
        report[`${current.key}-${width}`] = { initial, afterScroll };
      }
    }
  } finally {
    await browser.close();
  }

  require('fs').writeFileSync('qa/desktop-filter-visual-check.json', JSON.stringify({ report, failures }, null, 2));
  if (failures.length) {
    console.error(`DESKTOP_FILTER_VISUAL_FAIL ${JSON.stringify(failures)}`);
    process.exitCode = 2;
  } else {
    console.log(`DESKTOP_FILTER_VISUAL_OK ${JSON.stringify(report)}`);
  }
})();