const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const failures = [];
const report = {};
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function go(page, path, delay = 650) {
  const url = new URL(path, BASE);
  url.searchParams.set('request-010202', Date.now().toString());
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 60000 });
  if (!response || response.status() >= 400) failures.push(`${url.pathname}: HTTP ${response?.status() || 'none'}`);
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important;visibility:hidden!important}' }).catch(() => {});
  await sleep(delay);
}

async function waitForRelease(page) {
  for (let i = 0; i < 30; i += 1) {
    await go(page, '/categoria-producto/jamones-paletas/', 250);
    const ready = await page.evaluate(() => !!document.getElementById('elmercado-catalog-filter-system-010202'));
    if (ready) return;
    await sleep(5000);
  }
  throw new Error('0.10.202 not visible on staging');
}

async function state(page) {
  return page.evaluate(() => {
    const css = (node, pseudo = null) => node ? getComputedStyle(node, pseudo) : null;
    const rect = (node) => node ? node.getBoundingClientRect() : null;
    const visible = (node) => !!node && css(node).display !== 'none' && css(node).visibility !== 'hidden';
    const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
    const context = document.getElementById('emo-category-context');
    const active = document.querySelector('.emo-active-filter-chips[data-emo-global-active-filters="true"]');
    const price = sidebar ? [...sidebar.children].find((node) => node.matches?.('.widget_price_filter,.wc-block-price-filter,.wp-block-woocommerce-price-filter')) : null;
    const vendor = document.getElementById('emo-global-vendor-filter');
    const specific = document.getElementById('emo-category-attribute-filters');
    const groups = [...document.querySelectorAll('#emo-category-attribute-filters .emo-category-filter-group')];
    const firstSpecific = document.querySelector('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item');
    const firstVendor = document.querySelector('#emo-global-vendor-filter .emo-global-vendor-filter__item');
    const heading = document.querySelector('#emo-category-attribute-filters .emo-category-filter-title');
    const activeRows = [...document.querySelectorAll('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen')];
    const clearLinks = [...document.querySelectorAll('a')].filter((a) => /limpiar/i.test((a.textContent || '').trim()) && visible(a));
    const oldLayerIds = [
      'elmercado-active-filter-clear-guard-010191',
      'elmercado-vendor-filter-visual-fix-010194',
      'elmercado-catalog-filter-unification-010196',
      'elmercado-catalog-filter-final-010197',
      'elmercado-catalog-filter-layout-lock-010198',
      'elmercado-catalog-filter-visual-refinement-010199',
      'elmercado-catalog-filter-visual-lock-010200'
    ].filter((id) => document.getElementById(id));

    const metric = (node) => {
      const r = rect(node);
      const s = css(node);
      return node ? {
        top: Number(r.top.toFixed(1)),
        bottom: Number(r.bottom.toFixed(1)),
        height: Number(r.height.toFixed(1)),
        display: s.display,
        background: s.backgroundColor,
        borderBottomWidth: s.borderBottomWidth,
        boxShadow: s.boxShadow,
        fontWeight: s.fontWeight,
        color: s.color,
        marginTop: s.marginTop,
        marginBottom: s.marginBottom,
        inlineStyle: node.getAttribute('style') || ''
      } : null;
    };

    return {
      sidebar: metric(sidebar),
      context: metric(context),
      active: metric(active),
      price: metric(price),
      vendor: metric(vendor),
      specific: metric(specific),
      heading: metric(heading),
      firstSpecific: metric(firstSpecific),
      firstVendor: metric(firstVendor),
      groupBorders: groups.map((g) => css(g).borderBottomWidth),
      groupMargins: groups.map((g) => css(g).marginBottom),
      activeRows: activeRows.map((row) => metric(row)),
      activeRowTexts: activeRows.map((row) => (row.textContent || '').replace(/\s+/g, ' ').trim()),
      clearTexts: clearLinks.map((a) => (a.textContent || '').replace(/\s+/g, ' ').trim()),
      activeParentId: active?.parentElement?.id || '',
      oldLayerIds,
      vendorCountTexts: [...document.querySelectorAll('#emo-global-vendor-filter .emo-global-vendor-filter__item > .count')].map((n) => (n.textContent || '').trim()),
      loadedFinalStyle: !!document.getElementById('elmercado-catalog-filter-system-010202'),
    };
  });
}

async function hoverState(page, selector) {
  const row = await page.$(selector);
  if (!row) return null;
  const before = await page.evaluate((node) => {
    const s = getComputedStyle(node);
    const a = node.querySelector(':scope > a');
    const as = a ? getComputedStyle(a) : null;
    return { background: s.backgroundColor, boxShadow: s.boxShadow, fontWeight: as?.fontWeight || '', color: as?.color || '' };
  }, row);
  await row.hover();
  await sleep(120);
  const after = await page.evaluate((node) => {
    const s = getComputedStyle(node);
    const a = node.querySelector(':scope > a');
    const as = a ? getComputedStyle(a) : null;
    return { background: s.backgroundColor, boxShadow: s.boxShadow, fontWeight: as?.fontWeight || '', color: as?.color || '' };
  }, row);
  return { before, after };
}

function sameStable(label, a, b) {
  const pick = (s) => ({
    sidebar: s.sidebar && { background:s.sidebar.background, borderBottomWidth:s.sidebar.borderBottomWidth },
    context: s.context && { top:s.context.top, bottom:s.context.bottom, background:s.context.background },
    active: s.active && { top:s.active.top, bottom:s.active.bottom, background:s.active.background },
    price: s.price && { top:s.price.top, bottom:s.price.bottom },
    vendor: s.vendor && { top:s.vendor.top, bottom:s.vendor.bottom },
    specific: s.specific && { top:s.specific.top, bottom:s.specific.bottom },
    heading: s.heading && { display:s.heading.display, background:s.heading.background, fontWeight:s.heading.fontWeight },
    firstSpecific: s.firstSpecific && { height:s.firstSpecific.height, background:s.firstSpecific.background, fontWeight:s.firstSpecific.fontWeight },
    groupBorders:s.groupBorders,
    activeParentId:s.activeParentId,
  });
  if (JSON.stringify(pick(a)) !== JSON.stringify(pick(b))) failures.push(`${label}: computed filter layout/styles changed after initial render`);
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({ headless:true, executablePath:'/usr/bin/google-chrome', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  await page.setViewport({ width:1440, height:1500, deviceScaleFactor:1 });

  try {
    await waitForRelease(page);
    await go(page, '/categoria-producto/jamones-paletas/', 120);
    report.initial = await state(page);
    await sleep(2200);
    report.stable = await state(page);
    sameStable('jamones', report.initial, report.stable);

    if (!report.stable.loadedFinalStyle) failures.push('final 010202 stylesheet missing');
    if (report.stable.oldLayerIds.length) failures.push(`legacy runtime visual layers still loaded: ${report.stable.oldLayerIds.join(', ')}`);
    if (!report.stable.context) failures.push('category context missing');
    if (report.stable.context?.background !== 'rgba(0, 0, 0, 0)') failures.push(`category outer context still boxed: ${report.stable.context?.background}`);
    if (report.stable.sidebar?.background !== 'rgb(255, 255, 255)') failures.push(`sidebar card background lost: ${report.stable.sidebar?.background}`);
    if (report.stable.groupBorders.some((v) => v !== '0px')) failures.push(`specific filter separator remains: ${report.stable.groupBorders.join(',')}`);
    if (report.stable.clearTexts.filter((t) => /limpiar filtros/i.test(t)).length) failures.push(`redundant Limpiar filtros still visible: ${report.stable.clearTexts.join(' | ')}`);
    if (report.stable.active && !report.stable.clearTexts.some((t) => /limpiar todo/i.test(t))) failures.push('Limpiar todo missing while active filters exist');
    if (report.stable.vendorCountTexts.length === 0) failures.push('vendor counts missing');

    report.hover = await hoverState(page, '#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:not(.chosen):not(.woocommerce-widget-layered-nav-list__item--chosen)');
    if (!report.hover) failures.push('no specific filter row available for hover check');
    if (report.hover?.after.background !== 'rgb(217, 237, 224)') failures.push(`hover highlight differs: ${report.hover?.after.background}`);
    if (report.hover?.before.fontWeight !== report.hover?.after.fontWeight) failures.push(`hover changes font weight (${report.hover?.before.fontWeight} -> ${report.hover?.after.fontWeight})`);

    const firstPesoHref = await page.evaluate(() => document.querySelector('.emo-category-filter-rango-peso .woocommerce-widget-layered-nav-list__item:not(.chosen):not(.woocommerce-widget-layered-nav-list__item--chosen) > a')?.href || '');
    if (!firstPesoHref) {
      failures.push('no Peso option available');
    } else {
      await page.goto(firstPesoHref, { waitUntil:'domcontentloaded', timeout:60000 });
      await sleep(650);
      const secondPesoHref = await page.evaluate(() => {
        const rows = [...document.querySelectorAll('.emo-category-filter-rango-peso .woocommerce-widget-layered-nav-list__item')];
        const chosenIndex = rows.findIndex((row) => row.matches('.chosen,.woocommerce-widget-layered-nav-list__item--chosen'));
        const next = rows.find((row, index) => index !== chosenIndex && !row.matches('.chosen,.woocommerce-widget-layered-nav-list__item--chosen'));
        return next?.querySelector(':scope > a')?.href || '';
      });
      if (!secondPesoHref) {
        failures.push('no second Peso option available');
      } else {
        await page.goto(secondPesoHref, { waitUntil:'domcontentloaded', timeout:60000 });
        await sleep(700);
        report.twoPeso = await state(page);
        await page.screenshot({ path:'qa/request-010202-two-peso.png', fullPage:true });
        if (report.twoPeso.activeRows.length < 2) failures.push(`expected two selected Peso rows, got ${report.twoPeso.activeRows.length}`);
        const rows = report.twoPeso.activeRows.slice(0,2);
        if (rows.length === 2) {
          const gap = Number((rows[1].top - rows[0].bottom).toFixed(1));
          report.twoPeso.selectedGap = gap;
          if (gap < 2) failures.push(`adjacent selected rows visually merge; gap=${gap}px`);
          for (const row of rows) {
            if (row.background !== 'rgb(217, 237, 224)') failures.push(`selected highlight differs from hover: ${row.background}`);
          }
        }
        const p = report.twoPeso;
        if (!p.active) failures.push('Filtros aplicados missing after selecting Peso');
        if (p.activeParentId !== 'secondary' && p.activeParentId !== '') failures.push(`Filtros aplicados is nested in ${p.activeParentId}`);
        if (p.context && p.active && p.price && !(p.context.top < p.active.top && p.active.top < p.price.top)) failures.push(`wrong category order: context=${p.context.top}, active=${p.active.top}, price=${p.price.top}`);
        if (p.clearTexts.filter((t) => /limpiar todo/i.test(t)).length !== 1) failures.push(`expected one Limpiar todo, got ${p.clearTexts.join(' | ')}`);
        if (p.clearTexts.some((t) => /limpiar filtros/i.test(t))) failures.push(`Limpiar filtros returned after selection: ${p.clearTexts.join(' | ')}`);
      }
    }

    await go(page, '/tienda/', 700);
    report.shop = await state(page);
    await page.screenshot({ path:'qa/request-010202-shop.png', fullPage:true });
    if (report.shop.groupBorders.some((v) => v !== '0px')) failures.push('shop: unexpected specific separators');
    if (report.shop.oldLayerIds.length) failures.push(`shop: legacy visual layers loaded: ${report.shop.oldLayerIds.join(', ')}`);

    fs.writeFileSync('qa/request-010202-report.json', JSON.stringify({ failures, report }, null, 2));
    if (failures.length) {
      console.error('REQUEST_010202_FAIL', JSON.stringify(failures));
      process.exitCode = 2;
    } else {
      console.log('REQUEST_010202_OK', JSON.stringify(report));
    }
  } finally {
    await browser.close();
  }
})();
