const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 550) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010206', Date.now().toString());
  const res = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!res || res.status() >= 400) failures.push(`${url.pathname}: HTTP ${res?.status() || 'none'}`);
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let i = 0; i < 30; i += 1) {
    await go(page, '/categoria-producto/jamones-paletas/', 160);
    const ready = await page.evaluate(() =>
      !!document.getElementById('elmercado-catalog-filter-system-final-010206') &&
      !!document.getElementById('elmercado-catalog-filter-controller-final-010206') &&
      !document.getElementById('elmercado-catalog-filter-system-010203') &&
      !document.getElementById('elmercado-catalog-filter-controller-010203')
    );
    if (ready) return;
    await sleep(5000);
  }
  throw new Error('0.10.206 not visible on staging');
}

async function state(page) {
  return page.evaluate(() => {
    const css = (node, pseudo = null) => node ? getComputedStyle(node, pseudo) : null;
    const sidebar = document.querySelector(
      '.emo-mobile-filter-content #secondary.widget-area,' +
      '.emo-mobile-filter-content .shop-widget-area,' +
      '.emo-mobile-filter-content .widget-area,' +
      '#secondary.widget-area,.shop-widget-area,.content-area + .widget-area,.site-content .widget-area'
    );
    const direct = (selectors) => sidebar ? [...sidebar.children].find((n) => selectors.some((sel) => n.matches?.(sel) || n.querySelector?.(sel))) || null : null;
    const metric = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      const s = css(node);
      return {
        top:Number(r.top.toFixed(1)), bottom:Number(r.bottom.toFixed(1)), height:Number(r.height.toFixed(1)),
        display:s.display, visibility:s.visibility, background:s.backgroundColor, borderBottomWidth:s.borderBottomWidth,
        boxShadow:s.boxShadow, fontWeight:s.fontWeight, color:s.color,
        hidden:node.hasAttribute('hidden'), mounted:node.classList.contains('emo-catalog-filter-mounted'),
        parentId:node.parentElement?.id || '', parentClass:node.parentElement?.className || ''
      };
    };
    const visible = (node) => !!node && css(node).display !== 'none' && css(node).visibility !== 'hidden';
    const context = document.getElementById('emo-category-context');
    const active = document.querySelector('.emo-active-filter-chips[data-emo-global-active-filters="true"]');
    const price = direct(['.widget_price_filter','.wc-block-price-filter','.wp-block-woocommerce-price-filter']);
    const categories = direct(['.widget_product_categories','.wc-block-product-categories','.wp-block-woocommerce-product-categories']);
    const vendor = document.getElementById('emo-global-vendor-filter');
    const specific = document.getElementById('emo-category-attribute-filters');
    const groups = [...document.querySelectorAll('#emo-category-attribute-filters .emo-category-filter-group')];
    const activeRows = [...document.querySelectorAll('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen')];
    const clearLinks = [...document.querySelectorAll('a')].filter((a) => /limpiar/i.test((a.textContent || '').trim()) && visible(a));
    const legacyIds = [
      'elmercado-category-specific-filters-controller-010185','elmercado-filter-state-final-controller-01085',
      'elmercado-filter-postapply-sync-controller-01086','elmercado-active-filter-chips-controller-010193',
      'elmercado-catalog-core-filters-controller-010193','elmercado-catalog-filter-unification-controller-010196',
      'elmercado-catalog-filter-final-controller-010197','elmercado-catalog-filter-layout-lock-controller-010198',
      'elmercado-catalog-filter-visual-refinement-controller-010199','elmercado-catalog-filter-visual-lock-controller-010200',
      'elmercado-catalog-filter-system-controller-010202','elmercado-catalog-filter-controller-010203',
      'elmercado-filter-state-final-01085','elmercado-filter-postapply-sync-01086','elmercado-filter-toolbar-final-01087',
      'elmercado-desktop-filter-visual-final-01089','elmercado-category-filter-design-010188',
      'elmercado-vendor-filter-visual-fix-010194','elmercado-catalog-filter-system-010203'
    ].filter((id) => document.getElementById(id));
    const arrows = (selector) => [...document.querySelectorAll(selector)].map((link) => ({
      text:(link.textContent || '').replace(/\s+/g,' ').trim(),
      after:css(link,'::after')?.content || '',
      icons:link.querySelectorAll(':scope > svg,:scope > i,:scope > .arrow,:scope > .caret,:scope > .chevron,:scope > .woostify-svg-icon').length
    }));
    return {
      sidebar:metric(sidebar), context:metric(context), active:metric(active), price:metric(price), categories:metric(categories),
      vendor:metric(vendor), specific:metric(specific),
      groupBorders:groups.map((g) => css(g).borderBottomWidth),
      activeRows:activeRows.map(metric),
      clearTexts:clearLinks.map((a) => (a.textContent || '').replace(/\s+/g,' ').trim()),
      vendorCounts:[...document.querySelectorAll('#emo-global-vendor-filter > ul > li > .count')].map((n) => (n.textContent || '').trim()),
      vendorArrows:arrows('#emo-global-vendor-filter .emo-global-vendor-filter__item > a'),
      categoryArrows:arrows('.widget_product_categories li > a'),
      legacyIds,
      oldActiveSections:document.querySelectorAll('.emo-active-filters').length,
      globalActiveSections:document.querySelectorAll('.emo-active-filter-chips[data-emo-global-active-filters="true"]').length,
      filterTemplate:!!document.getElementById('emo-active-filter-chips-template'),
      mobileShell:!!document.querySelector('.emo-mobile-filter-shell'),
      mobileShellHidden:document.querySelector('.emo-mobile-filter-shell')?.hidden ?? null,
      finalStyle:!!document.getElementById('elmercado-catalog-filter-system-final-010206'),
      finalController:!!document.getElementById('elmercado-catalog-filter-controller-final-010206')
    };
  });
}

function stablePick(s) {
  const pick = (m) => m && ({ top:m.top,bottom:m.bottom,height:m.height,display:m.display,background:m.background,hidden:m.hidden,mounted:m.mounted,parentId:m.parentId,parentClass:m.parentClass });
  return {
    sidebar:pick(s.sidebar), context:pick(s.context), active:pick(s.active), price:pick(s.price), categories:pick(s.categories),
    vendor:pick(s.vendor), specific:pick(s.specific), groupBorders:s.groupBorders, legacyIds:s.legacyIds,
    oldActiveSections:s.oldActiveSections, globalActiveSections:s.globalActiveSections
  };
}

async function hoverState(page) {
  const row = await page.$('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:not(.chosen):not(.woocommerce-widget-layered-nav-list__item--chosen)');
  if (!row) return null;
  const read = (node) => {
    const s = getComputedStyle(node);
    const link = node.querySelector(':scope > a');
    const a = link ? getComputedStyle(link) : null;
    return { background:s.backgroundColor, boxShadow:s.boxShadow, fontWeight:a?.fontWeight || '', color:a?.color || '' };
  };
  const before = await page.evaluate(read, row);
  await row.hover();
  await sleep(120);
  const after = await page.evaluate(read, row);
  return { before, after };
}

(async () => {
  fs.mkdirSync('qa', { recursive:true });
  const browser = await puppeteer.launch({ headless:true, executablePath:'/usr/bin/google-chrome', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();

  try {
    await page.setViewport({ width:1440, height:1500, deviceScaleFactor:1 });
    await waitForRelease(page);
    await go(page, '/categoria-producto/jamones-paletas/', 120);
    report.categoryInitial = await state(page);
    await sleep(2300);
    report.categoryStable = await state(page);

    const s0 = report.categoryStable;
    if (JSON.stringify(stablePick(report.categoryInitial)) !== JSON.stringify(stablePick(s0))) failures.push('category layout/styles change after 2.3s');
    if (!s0.finalStyle || !s0.finalController) failures.push('final 010206 system missing');
    if (s0.legacyIds.length) failures.push(`legacy filter layers loaded: ${s0.legacyIds.join(', ')}`);
    if (s0.oldActiveSections) failures.push(`legacy active summary exists: ${s0.oldActiveSections}`);
    if (s0.sidebar?.background !== 'rgb(255, 255, 255)') failures.push(`sidebar card lost: ${s0.sidebar?.background}`);
    if (s0.context?.background !== 'rgba(0, 0, 0, 0)') failures.push(`outer category context boxed: ${s0.context?.background}`);
    if (s0.vendor?.display === 'none' || s0.vendor?.height === 0 || s0.vendor?.hidden || !s0.vendor?.mounted) failures.push(`Vendor not mounted visibly: ${JSON.stringify(s0.vendor)}`);
    if (s0.specific?.display === 'none' || s0.specific?.hidden || !s0.specific?.mounted) failures.push(`specific panel not mounted visibly: ${JSON.stringify(s0.specific)}`);
    if (s0.categories && s0.categories.display !== 'none') failures.push(`native Categories visible in category: ${JSON.stringify(s0.categories)}`);
    if (s0.groupBorders.some((v) => v !== '0px')) failures.push(`separator remains: ${s0.groupBorders.join(',')}`);
    if (s0.vendorCounts.length !== 3) failures.push(`Jamones vendor counts missing: ${s0.vendorCounts.join(',')}`);
    if (s0.vendorArrows.some((a) => a.after !== 'none' || a.icons)) failures.push(`vendor arrow remains: ${JSON.stringify(s0.vendorArrows)}`);

    report.hover = await hoverState(page);
    if (!report.hover) failures.push('no row available for hover');
    if (report.hover?.after.background !== 'rgb(217, 237, 224)') failures.push(`hover highlight wrong: ${report.hover?.after.background}`);
    if (report.hover?.before.fontWeight !== report.hover?.after.fontWeight) failures.push(`hover changes font weight: ${report.hover?.before.fontWeight} -> ${report.hover?.after.fontWeight}`);

    const firstPeso = await page.evaluate(() => document.querySelector('.emo-category-filter-rango-peso .woocommerce-widget-layered-nav-list__item:not(.chosen):not(.woocommerce-widget-layered-nav-list__item--chosen) > a')?.href || '');
    if (!firstPeso) failures.push('first Peso option missing');
    else {
      await page.goto(firstPeso, { waitUntil:'domcontentloaded', timeout:60000 }); await sleep(500);
      const secondPeso = await page.evaluate(() => [...document.querySelectorAll('.emo-category-filter-rango-peso .woocommerce-widget-layered-nav-list__item')].find((r) => !r.matches('.chosen,.woocommerce-widget-layered-nav-list__item--chosen'))?.querySelector(':scope > a')?.href || '');
      if (!secondPeso) failures.push('second Peso option missing');
      else {
        await page.goto(secondPeso, { waitUntil:'domcontentloaded', timeout:60000 }); await sleep(550);
        report.twoPesoInitial = await state(page); await sleep(2300); report.twoPesoStable = await state(page);
        const s = report.twoPesoStable;
        if (JSON.stringify(stablePick(report.twoPesoInitial)) !== JSON.stringify(stablePick(s))) failures.push('two-Peso layout/styles change after 2.3s');
        if (s.activeRows.length < 2) failures.push(`expected two selected rows, got ${s.activeRows.length}`);
        if (s.activeRows.length >= 2) {
          const rows = s.activeRows.slice(0,2);
          report.selectedGap = Number((rows[1].top - rows[0].bottom).toFixed(1));
          if (report.selectedGap < 2) failures.push(`selected rows merge; gap=${report.selectedGap}px`);
          rows.forEach((r) => { if (r.background !== 'rgb(217, 237, 224)') failures.push(`selected highlight differs from hover: ${r.background}`); });
        }
        if (!s.active || s.globalActiveSections !== 1) failures.push(`expected one Filtros aplicados, got ${s.globalActiveSections}`);
        if (s.clearTexts.filter((t) => /limpiar todo/i.test(t)).length !== 1) failures.push(`expected one Limpiar todo: ${s.clearTexts.join(' | ')}`);
        if (s.clearTexts.some((t) => /limpiar filtros/i.test(t) || /^limpiar$/i.test(t))) failures.push(`redundant clear visible: ${s.clearTexts.join(' | ')}`);
        if (s.context && s.active && s.price && s.vendor && s.specific && !(s.context.top < s.active.top && s.active.top < s.price.top && s.price.top < s.vendor.top && s.vendor.top < s.specific.top)) failures.push(`wrong category order: ${s.context.top},${s.active.top},${s.price.top},${s.vendor.top},${s.specific.top}`);
        report.filteredUrl = page.url();
        await page.screenshot({ path:'qa/request-010206-two-peso.png', fullPage:true });
      }
    }

    if (report.filteredUrl) {
      await page.setViewport({ width:390, height:844, deviceScaleFactor:1 });
      await page.goto(report.filteredUrl, { waitUntil:'domcontentloaded', timeout:60000 }); await sleep(500);
      const toggle = await page.$('.emo-mobile-filter-toggle');
      if (!toggle) failures.push('mobile filter toggle missing');
      else { await toggle.click(); await sleep(180); }
      report.mobileInitial = await state(page); await sleep(2300); report.mobileStable = await state(page);
      const m = report.mobileStable;
      if (JSON.stringify(stablePick(report.mobileInitial)) !== JSON.stringify(stablePick(m))) failures.push('mobile open drawer layout/styles change after 2.3s');
      if (!m.mobileShell || m.mobileShellHidden) failures.push('mobile filter drawer did not open');
      if (m.globalActiveSections !== 1) failures.push(`mobile expected one Filtros aplicados, got ${m.globalActiveSections}`);
      if (m.oldActiveSections) failures.push(`mobile legacy active summary exists: ${m.oldActiveSections}`);
      if (m.legacyIds.length) failures.push(`mobile legacy filter layers loaded: ${m.legacyIds.join(', ')}`);
      if (m.vendor?.display === 'none' || m.vendor?.hidden || !m.vendor?.mounted) failures.push(`mobile Vendor not visible/mounted: ${JSON.stringify(m.vendor)}`);
      await page.screenshot({ path:'qa/request-010206-mobile.png', fullPage:true });
    }

    await page.setViewport({ width:1440, height:1500, deviceScaleFactor:1 });
    await go(page, '/tienda/', 550);
    report.shop = await state(page);
    const sh = report.shop;
    if (sh.price && sh.categories && sh.vendor && !(sh.price.top < sh.categories.top && sh.categories.top < sh.vendor.top)) failures.push(`wrong shop order: ${sh.price.top},${sh.categories.top},${sh.vendor.top}`);
    if (sh.vendorArrows.some((a) => a.after !== 'none' || a.icons)) failures.push(`shop vendor arrow remains: ${JSON.stringify(sh.vendorArrows)}`);
    if (sh.categoryArrows.some((a) => a.after !== 'none' || a.icons)) failures.push(`shop category arrow remains: ${JSON.stringify(sh.categoryArrows)}`);
    if (sh.legacyIds.length) failures.push(`shop legacy filter layers loaded: ${sh.legacyIds.join(', ')}`);
    await page.screenshot({ path:'qa/request-010206-shop.png', fullPage:true });

    fs.writeFileSync('qa/request-010206-report.json', JSON.stringify({ failures, report }, null, 2));
    if (failures.length) { console.error('REQUEST_010206_FAIL', JSON.stringify(failures)); process.exitCode = 2; }
    else console.log('REQUEST_010206_OK', JSON.stringify({ gap:report.selectedGap, hover:report.hover, category:report.twoPesoStable, mobile:report.mobileStable, shop:report.shop }));
  } finally {
    await browser.close();
  }
})();