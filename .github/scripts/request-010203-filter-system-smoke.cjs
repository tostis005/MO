const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 600) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010205', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!response || response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let i = 0; i < 30; i += 1) {
    await go(page, '/categoria-producto/jamones-paletas/', 200);
    const ready = await page.evaluate(() => {
      const finalSystem = !!document.getElementById('elmercado-catalog-filter-system-010203');
      const removedLegacy = !document.getElementById('elmercado-category-specific-filters-controller-010185')
        && !document.getElementById('elmercado-filter-state-final-controller-01085')
        && !document.getElementById('elmercado-filter-postapply-sync-controller-01086')
        && !document.getElementById('elmercado-desktop-filter-visual-final-01089');
      return finalSystem && removedLegacy;
    });
    if (ready) return;
    await sleep(5000);
  }
  throw new Error('0.10.205 consolidated filter release not visible on staging');
}

async function state(page) {
  return page.evaluate(() => {
    const style = (node, pseudo = null) => node ? getComputedStyle(node, pseudo) : null;
    const metric = (node) => {
      if (!node) return null;
      const r = node.getBoundingClientRect();
      const s = style(node);
      return {
        top:Number(r.top.toFixed(1)), bottom:Number(r.bottom.toFixed(1)), height:Number(r.height.toFixed(1)),
        display:s.display, visibility:s.visibility, background:s.backgroundColor, borderBottomWidth:s.borderBottomWidth,
        boxShadow:s.boxShadow, fontWeight:s.fontWeight, color:s.color,
        marginTop:s.marginTop, marginBottom:s.marginBottom, inlineStyle:node.getAttribute('style') || ''
      };
    };
    const visible = (node) => !!node && style(node).display !== 'none' && style(node).visibility !== 'hidden';
    const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
    const context = document.getElementById('emo-category-context');
    const active = document.querySelector('.emo-active-filter-chips[data-emo-global-active-filters="true"]');
    const price = sidebar ? [...sidebar.children].find((n) => n.matches?.('.widget_price_filter,.wc-block-price-filter,.wp-block-woocommerce-price-filter') || n.querySelector?.('.wc-block-price-filter,.wp-block-woocommerce-price-filter')) : null;
    const categories = sidebar ? [...sidebar.children].find((n) => n.matches?.('.widget_product_categories,.wc-block-product-categories,.wp-block-woocommerce-product-categories') || n.querySelector?.('.wc-block-product-categories,.wp-block-woocommerce-product-categories')) : null;
    const vendor = document.getElementById('emo-global-vendor-filter');
    const specific = document.getElementById('emo-category-attribute-filters');
    const heading = document.querySelector('#emo-category-attribute-filters .emo-category-filter-title');
    const groups = [...document.querySelectorAll('#emo-category-attribute-filters .emo-category-filter-group')];
    const activeRows = [...document.querySelectorAll('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen')];
    const clearLinks = [...document.querySelectorAll('a')].filter((a) => /limpiar/i.test((a.textContent || '').trim()) && visible(a));
    const legacyRuntimeIds = [
      'elmercado-category-specific-filters-controller-010185',
      'elmercado-filter-state-final-controller-01085',
      'elmercado-filter-postapply-sync-controller-01086',
      'elmercado-active-filter-chips-controller-010193',
      'elmercado-catalog-core-filters-controller-010193',
      'elmercado-active-filter-clear-guard-010191',
      'elmercado-catalog-filter-unification-controller-010196',
      'elmercado-catalog-filter-final-controller-010197',
      'elmercado-catalog-filter-layout-lock-controller-010198',
      'elmercado-catalog-filter-visual-refinement-controller-010199',
      'elmercado-catalog-filter-visual-lock-controller-010200',
      'elmercado-catalog-filter-system-controller-010202'
    ].filter((id) => document.getElementById(id));
    const legacyStyleIds = [
      'elmercado-category-specific-filters-010185',
      'elmercado-filter-state-final-01085',
      'elmercado-filter-postapply-sync-01086',
      'elmercado-filter-toolbar-final-01087',
      'elmercado-desktop-filter-visual-final-01089',
      'elmercado-active-filter-chips-010193',
      'elmercado-catalog-core-filters-010193',
      'elmercado-category-filter-design-010188',
      'elmercado-vendor-filter-visual-fix-010194',
      'elmercado-catalog-filter-unification-010196',
      'elmercado-catalog-filter-final-010197',
      'elmercado-catalog-filter-layout-lock-010198',
      'elmercado-catalog-filter-visual-refinement-010199',
      'elmercado-catalog-filter-visual-lock-010200',
      'elmercado-catalog-filter-system-010202'
    ].filter((id) => document.getElementById(id));

    const arrows = (selector) => [...document.querySelectorAll(selector)].map((link) => ({
      text:(link.textContent || '').replace(/\s+/g,' ').trim(),
      after:style(link, '::after')?.content || '',
      icons:link.querySelectorAll(':scope > svg,:scope > i,:scope > .arrow,:scope > .caret,:scope > .chevron,:scope > .woostify-svg-icon').length
    }));

    return {
      sidebar:metric(sidebar), context:metric(context), active:metric(active), price:metric(price), categories:metric(categories),
      vendor:metric(vendor), specific:metric(specific), heading:metric(heading),
      groupBorders:groups.map((g) => style(g).borderBottomWidth),
      activeRows:activeRows.map(metric),
      clearTexts:clearLinks.map((a) => (a.textContent || '').replace(/\s+/g,' ').trim()),
      activeParentId:active?.parentElement?.id || '',
      vendorCounts:[...document.querySelectorAll('#emo-global-vendor-filter .emo-global-vendor-filter__item > .count')].map((n) => (n.textContent || '').trim()),
      vendorArrows:arrows('#emo-global-vendor-filter .emo-global-vendor-filter__item > a'),
      categoryArrows:arrows('.widget_product_categories li > a'),
      legacyRuntimeIds, legacyStyleIds,
      oldActiveSections:document.querySelectorAll('.emo-active-filters').length,
      globalActiveSections:document.querySelectorAll('.emo-active-filter-chips[data-emo-global-active-filters="true"]').length,
      finalStyle:!!document.getElementById('elmercado-catalog-filter-system-010203'),
      finalController:!!document.getElementById('elmercado-catalog-filter-controller-010203')
    };
  });
}

async function hoverState(page, selector) {
  const row = await page.$(selector);
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

function stableSnapshot(s) {
  return {
    context:s.context && { top:s.context.top, bottom:s.context.bottom, background:s.context.background },
    active:s.active && { top:s.active.top, bottom:s.active.bottom },
    price:s.price && { top:s.price.top, bottom:s.price.bottom },
    vendor:s.vendor && { top:s.vendor.top, bottom:s.vendor.bottom, display:s.vendor.display },
    specific:s.specific && { top:s.specific.top, bottom:s.specific.bottom, display:s.specific.display },
    heading:s.heading && { display:s.heading.display, background:s.heading.background, fontWeight:s.heading.fontWeight, inlineStyle:s.heading.inlineStyle },
    groupBorders:s.groupBorders,
    legacyRuntimeIds:s.legacyRuntimeIds,
    legacyStyleIds:s.legacyStyleIds,
    oldActiveSections:s.oldActiveSections,
    globalActiveSections:s.globalActiveSections,
  };
}

(async () => {
  fs.mkdirSync('qa', { recursive:true });
  const browser = await puppeteer.launch({ headless:true, executablePath:'/usr/bin/google-chrome', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  await page.setViewport({ width:1440, height:1500, deviceScaleFactor:1 });

  try {
    await waitForRelease(page);
    await go(page, '/categoria-producto/jamones-paletas/', 100);
    report.initial = await state(page);
    await sleep(2300);
    report.stable = await state(page);

    if (JSON.stringify(stableSnapshot(report.initial)) !== JSON.stringify(stableSnapshot(report.stable))) failures.push('catalog layout/styles change after initial consolidated mount');
    if (!report.stable.finalStyle || !report.stable.finalController) failures.push('final 010203 system missing');
    if (report.stable.legacyRuntimeIds.length) failures.push(`legacy filter controllers loaded: ${report.stable.legacyRuntimeIds.join(', ')}`);
    if (report.stable.legacyStyleIds.length) failures.push(`legacy filter visual layers loaded: ${report.stable.legacyStyleIds.join(', ')}`);
    if (report.stable.oldActiveSections) failures.push(`legacy active-filter summary still exists: ${report.stable.oldActiveSections}`);
    if (report.stable.sidebar?.background !== 'rgb(255, 255, 255)') failures.push(`sidebar card lost: ${report.stable.sidebar?.background}`);
    if (report.stable.context?.background !== 'rgba(0, 0, 0, 0)') failures.push(`category outer context still boxed: ${report.stable.context?.background}`);
    if (report.stable.groupBorders.some((v) => v !== '0px')) failures.push(`separator line remains: ${report.stable.groupBorders.join(',')}`);
    if (report.stable.clearTexts.some((t) => /limpiar filtros/i.test(t) || /^limpiar$/i.test(t))) failures.push(`redundant clear action visible: ${report.stable.clearTexts.join(' | ')}`);
    if (report.stable.vendor?.display === 'none' || report.stable.vendor?.height === 0) failures.push(`Vendor hidden in category: ${JSON.stringify(report.stable.vendor)}`);
    if (report.stable.categories && report.stable.categories.display !== 'none') failures.push(`native Categories widget visible in category: ${JSON.stringify(report.stable.categories)}`);
    if (report.stable.vendorCounts.length !== 3) failures.push(`Jamones vendor counts missing: ${report.stable.vendorCounts.join(',')}`);
    if (report.stable.vendorArrows.some((a) => a.after !== 'none' || a.icons)) failures.push(`vendor arrow remains: ${JSON.stringify(report.stable.vendorArrows)}`);

    const p0 = report.stable;
    if (p0.context && p0.price && !(p0.context.top < p0.price.top)) failures.push(`category context is not above price (${p0.context.top} vs ${p0.price.top})`);

    report.hover = await hoverState(page, '#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:not(.chosen):not(.woocommerce-widget-layered-nav-list__item--chosen)');
    if (!report.hover) failures.push('no specific row available for hover');
    if (report.hover?.after.background !== 'rgb(217, 237, 224)') failures.push(`hover highlight differs: ${report.hover?.after.background}`);
    if (report.hover?.before.fontWeight !== report.hover?.after.fontWeight) failures.push(`hover changes font weight (${report.hover?.before.fontWeight} -> ${report.hover?.after.fontWeight})`);

    const firstPeso = await page.evaluate(() => document.querySelector('.emo-category-filter-rango-peso .woocommerce-widget-layered-nav-list__item:not(.chosen):not(.woocommerce-widget-layered-nav-list__item--chosen) > a')?.href || '');
    if (!firstPeso) {
      failures.push('no Peso option available');
    } else {
      await page.goto(firstPeso, { waitUntil:'domcontentloaded', timeout:60000 });
      await sleep(500);
      const secondPeso = await page.evaluate(() => {
        const rows = [...document.querySelectorAll('.emo-category-filter-rango-peso .woocommerce-widget-layered-nav-list__item')];
        return rows.find((row) => !row.matches('.chosen,.woocommerce-widget-layered-nav-list__item--chosen'))?.querySelector(':scope > a')?.href || '';
      });
      if (!secondPeso) {
        failures.push('no second Peso option available');
      } else {
        await page.goto(secondPeso, { waitUntil:'domcontentloaded', timeout:60000 });
        await sleep(550);
        report.twoPesoInitial = await state(page);
        await sleep(2300);
        report.twoPesoStable = await state(page);
        if (JSON.stringify(stableSnapshot(report.twoPesoInitial)) !== JSON.stringify(stableSnapshot(report.twoPesoStable))) failures.push('two-Peso layout/styles change after initial mount');

        const s = report.twoPesoStable;
        if (s.activeRows.length < 2) failures.push(`expected two selected Peso rows, got ${s.activeRows.length}`);
        if (s.activeRows.length >= 2) {
          const rows = s.activeRows.slice(0,2);
          const gap = Number((rows[1].top - rows[0].bottom).toFixed(1));
          report.selectedGap = gap;
          if (gap < 2) failures.push(`selected rows visually merge; gap=${gap}px`);
          rows.forEach((row) => {
            if (row.background !== 'rgb(217, 237, 224)') failures.push(`selected highlight differs from hover: ${row.background}`);
          });
        }
        if (!s.active) failures.push('Filtros aplicados missing');
        if (s.activeParentId !== 'secondary' && s.activeParentId !== '') failures.push(`Filtros aplicados nested in ${s.activeParentId}`);
        if (s.context && s.active && s.price && s.vendor && s.specific && !(s.context.top < s.active.top && s.active.top < s.price.top && s.price.top < s.vendor.top && s.vendor.top < s.specific.top)) failures.push(`wrong category visual order: context=${s.context.top}, active=${s.active.top}, price=${s.price.top}, vendor=${s.vendor.top}, specific=${s.specific.top}`);
        if (s.clearTexts.filter((t) => /limpiar todo/i.test(t)).length !== 1) failures.push(`expected exactly one Limpiar todo: ${s.clearTexts.join(' | ')}`);
        if (s.clearTexts.some((t) => /limpiar filtros/i.test(t) || /^limpiar$/i.test(t))) failures.push(`redundant clear returned: ${s.clearTexts.join(' | ')}`);
        if (s.legacyRuntimeIds.length || s.legacyStyleIds.length) failures.push(`legacy layer returned after filtering: ${[...s.legacyRuntimeIds,...s.legacyStyleIds].join(', ')}`);
        await page.screenshot({ path:'qa/request-010205-two-peso.png', fullPage:true });
      }
    }

    await page.setViewport({ width:390, height:844, deviceScaleFactor:1 });
    const filteredUrl = page.url();
    await page.goto(filteredUrl, { waitUntil:'domcontentloaded', timeout:60000 });
    await sleep(300);
    report.mobileInitial = await state(page);
    await sleep(2300);
    report.mobileStable = await state(page);
    if (report.mobileStable.legacyRuntimeIds.length || report.mobileStable.legacyStyleIds.length) failures.push(`mobile legacy layers loaded: ${[...report.mobileStable.legacyRuntimeIds,...report.mobileStable.legacyStyleIds].join(', ')}`);
    if (report.mobileStable.oldActiveSections) failures.push(`mobile legacy active summary exists: ${report.mobileStable.oldActiveSections}`);
    if (report.mobileStable.globalActiveSections !== 1) failures.push(`mobile expected one global active summary, got ${report.mobileStable.globalActiveSections}`);
    if (JSON.stringify(stableSnapshot(report.mobileInitial)) !== JSON.stringify(stableSnapshot(report.mobileStable))) failures.push('mobile filter structure/styles change after initial mount');

    await page.setViewport({ width:1440, height:1500, deviceScaleFactor:1 });
    await go(page, '/tienda/', 550);
    report.shop = await state(page);
    const sh = report.shop;
    if (sh.price && sh.categories && sh.vendor && !(sh.price.top < sh.categories.top && sh.categories.top < sh.vendor.top)) failures.push(`wrong shop order: price=${sh.price.top}, categories=${sh.categories.top}, vendor=${sh.vendor.top}`);
    if (sh.vendorArrows.some((a) => a.after !== 'none' || a.icons)) failures.push(`shop vendor arrow remains: ${JSON.stringify(sh.vendorArrows)}`);
    if (sh.categoryArrows.some((a) => a.after !== 'none' || a.icons)) failures.push(`shop category arrow remains: ${JSON.stringify(sh.categoryArrows)}`);
    if (sh.legacyRuntimeIds.length || sh.legacyStyleIds.length) failures.push(`shop legacy layers loaded: ${[...sh.legacyRuntimeIds,...sh.legacyStyleIds].join(', ')}`);
    await page.screenshot({ path:'qa/request-010205-shop.png', fullPage:true });

    fs.writeFileSync('qa/request-010205-report.json', JSON.stringify({ failures, report }, null, 2));
    if (failures.length) {
      console.error('REQUEST_010205_FAIL', JSON.stringify(failures));
      process.exitCode = 2;
    } else {
      console.log('REQUEST_010205_OK', JSON.stringify({ selectedGap:report.selectedGap, hover:report.hover, category:report.twoPesoStable, mobile:report.mobileStable, shop:report.shop }));
    }
  } finally {
    await browser.close();
  }
})();