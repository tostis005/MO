const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const WIDTHS = [360, 375, 390, 768, 900, 991, 992, 1024, 1100];
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];
const checks = {};

function normalizeText(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

async function hideTransientUI(page) {
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
}

async function settle(page, delay = 850) {
  await hideTransientUI(page);
  await sleep(delay);
}

async function go(page, path, delay = 850) {
  const separator = path.includes('?') ? '&' : '?';
  const response = await page.goto(`${BASE}${path}${separator}filter-flow=${Date.now()}`, {
    waitUntil: 'domcontentloaded',
    timeout: 60000,
  });
  await settle(page, delay);
  if (!response || response.status() >= 400) failures.push(`${path}: HTTP ${response?.status() || 'none'}`);
}

async function waitNavigation(page, action, label) {
  try {
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }),
      action(),
    ]);
    await settle(page, 950);
    return true;
  } catch (error) {
    failures.push(`${label}: navigation failed (${error.message})`);
    return false;
  }
}

async function openDrawer(page, label) {
  const toggle = await page.$('#emo-premium-filter-toggle');
  if (!toggle) {
    failures.push(`${label}: filter toggle missing`);
    return false;
  }
  const expanded = await page.evaluate(() => {
    const shell = document.querySelector('#emo-premium-filter-shell');
    return !!shell && !shell.hidden && shell.getAttribute('aria-hidden') !== 'true';
  });
  if (!expanded) {
    await toggle.click();
    await sleep(300);
  }
  const open = await page.evaluate(() => {
    const shell = document.querySelector('#emo-premium-filter-shell');
    if (!shell) return false;
    const panel = shell.querySelector('.emo-mobile-filter-panel');
    if (!panel || shell.hidden) return false;
    const rect = panel.getBoundingClientRect();
    const style = getComputedStyle(panel);
    return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden';
  });
  if (!open) failures.push(`${label}: filter drawer did not open`);
  return open;
}

async function toolbarState(page) {
  return page.evaluate(() => {
    const toolbar = document.querySelector('.woostify-sorting');
    const visible = (node) => {
      if (!node) return false;
      const rect = node.getBoundingClientRect();
      const style = getComputedStyle(node);
      return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
    };
    const compactRect = (node) => {
      if (!node || !visible(node)) return null;
      const rect = node.getBoundingClientRect();
      return {
        left: Math.round(rect.left * 10) / 10,
        right: Math.round(rect.right * 10) / 10,
        top: Math.round(rect.top * 10) / 10,
        bottom: Math.round(rect.bottom * 10) / 10,
        width: Math.round(rect.width * 10) / 10,
        height: Math.round(rect.height * 10) / 10,
      };
    };
    if (!toolbar) return { exists: false };
    const children = [...toolbar.children];
    const rogue = children.filter((child) => {
      const keepsCount = child.matches?.('.woocommerce-result-count') || child.querySelector?.('.woocommerce-result-count');
      const keepsOrdering = child.matches?.('.woocommerce-ordering') || child.querySelector?.('.woocommerce-ordering');
      return !keepsCount && !keepsOrdering && visible(child);
    });
    const count = [...toolbar.querySelectorAll('.woocommerce-result-count')].find(visible) || null;
    const ordering = [...toolbar.querySelectorAll('.woocommerce-ordering')].find(visible) || null;
    const countRect = compactRect(count);
    const orderingRect = compactRect(ordering);
    const overlap = !!(countRect && orderingRect && Math.max(countRect.left, orderingRect.left) < Math.min(countRect.right, orderingRect.right) && Math.max(countRect.top, orderingRect.top) < Math.min(countRect.bottom, orderingRect.bottom));
    const extraSelects = [...toolbar.querySelectorAll('select')].filter((select) => {
      if ((select.name || '').toLowerCase() === 'orderby' || select.closest('.woocommerce-ordering')) return false;
      return visible(select);
    });
    return {
      exists: true,
      rogueCount: rogue.length,
      rogue: rogue.map((node) => ({ tag: node.tagName, cls: node.className || '', text: (node.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 120), rect: compactRect(node) })),
      extraSelectCount: extraSelects.length,
      countRect,
      orderingRect,
      overlap,
      text: (toolbar.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 220),
    };
  });
}

function assertToolbar(metric, label) {
  if (!metric.exists) failures.push(`${label}: sorting toolbar missing`);
  if ((metric.rogueCount || 0) > 0) failures.push(`${label}: visible extra toolbar control ${JSON.stringify(metric.rogue)}`);
  if ((metric.extraSelectCount || 0) > 0) failures.push(`${label}: non-ordering select visible in toolbar`);
  if (!metric.countRect) failures.push(`${label}: result count missing`);
  if (!metric.orderingRect) failures.push(`${label}: ordering missing`);
  if (metric.overlap) failures.push(`${label}: result count overlaps ordering`);
}

async function drawerState(page) {
  return page.evaluate(() => {
    const shell = document.querySelector('#emo-premium-filter-shell');
    const panel = shell?.querySelector('.emo-mobile-filter-panel');
    const content = shell?.querySelector('.emo-mobile-filter-content');
    const visible = (node) => {
      if (!node) return false;
      const rect = node.getBoundingClientRect();
      const style = getComputedStyle(node);
      return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
    };
    const panelRect = panel?.getBoundingClientRect();
    const activeLinks = content ? [...content.querySelectorAll('a.emo-filter-is-active,.emo-filter-is-active > a')].filter(visible).map((link) => (link.textContent || '').replace(/\s+/g, ' ').trim()) : [];
    const chips = content ? [...content.querySelectorAll('.emo-active-filter-chip')].filter(visible).map((chip) => (chip.textContent || '').replace(/\s+/g, ' ').trim()) : [];
    const minInput = content?.querySelector('.widget_price_filter input[name="min_price"]');
    const maxInput = content?.querySelector('.widget_price_filter input[name="max_price"]');
    const handles = content ? [...content.querySelectorAll('.widget_price_filter .ui-slider-handle')].slice(0, 2).map((handle) => {
      const r = handle.getBoundingClientRect();
      return { left: r.left, right: r.right, top: r.top, bottom: r.bottom };
    }) : [];
    return {
      open: !!shell && !shell.hidden && !!panel && visible(panel),
      panelWidth: panelRect ? Math.round(panelRect.width * 10) / 10 : 0,
      overflow: content ? Math.max(0, Math.round((content.scrollWidth - content.clientWidth) * 10) / 10) : null,
      activeLinks,
      chips,
      minInput: minInput?.value ?? null,
      maxInput: maxInput?.value ?? null,
      handles,
      url: location.href,
      toggleText: (document.querySelector('#emo-premium-filter-toggle')?.textContent || '').replace(/\s+/g, ' ').trim(),
    };
  });
}

async function firstCategory(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
    };
    const links = [...document.querySelectorAll('#emo-premium-filter-shell .widget_product_categories a')].filter((link) => visible(link) && link.href && !link.closest('.current-cat'));
    const link = links[0];
    return link ? { href: link.href, text: (link.textContent || '').replace(/\s+/g, ' ').trim() } : null;
  });
}

async function clickLinkByHref(page, href, label) {
  const handle = await page.evaluateHandle((targetHref) => [...document.querySelectorAll('#emo-premium-filter-shell a')].find((link) => link.href === targetHref) || null, href);
  const element = handle.asElement();
  if (!element) {
    await handle.dispose();
    failures.push(`${label}: target link disappeared (${href})`);
    return false;
  }
  const ok = await waitNavigation(page, () => element.click(), label);
  await handle.dispose();
  return ok;
}

async function applyPriceByDrag(page, label) {
  const geometry = await page.evaluate(() => {
    const track = document.querySelector('#emo-premium-filter-shell .widget_price_filter .price_slider.ui-slider,#emo-premium-filter-shell .widget_price_filter .ui-slider-horizontal');
    const handle = document.querySelector('#emo-premium-filter-shell .widget_price_filter .ui-slider-handle');
    if (!track || !handle) return null;
    const tr = track.getBoundingClientRect();
    const hr = handle.getBoundingClientRect();
    return {
      track: { x: tr.x, y: tr.y, width: tr.width, height: tr.height },
      handle: { x: hr.x, y: hr.y, width: hr.width, height: hr.height },
    };
  });
  if (!geometry) {
    failures.push(`${label}: price slider geometry missing`);
    return false;
  }

  const startX = geometry.handle.x + geometry.handle.width / 2;
  const centerY = geometry.track.y + geometry.track.height / 2;
  const targetX = geometry.track.x + Math.max(26, geometry.track.width * 0.23);
  await page.mouse.move(startX, centerY);
  await page.mouse.down();
  await page.mouse.move(targetX, centerY, { steps: 12 });
  await page.mouse.up();
  await sleep(180);

  const values = await page.evaluate(() => {
    const root = document.querySelector('#emo-premium-filter-shell .widget_price_filter');
    return {
      min: root?.querySelector('input[name="min_price"]')?.value ?? null,
      max: root?.querySelector('input[name="max_price"]')?.value ?? null,
      label: (root?.querySelector('.price_label')?.textContent || '').replace(/\s+/g, ' ').trim(),
    };
  });
  if (!values || values.min === null || Number(values.min) <= 0) {
    failures.push(`${label}: dragging minimum price did not change the filter (${JSON.stringify(values)})`);
    return false;
  }

  const button = await page.$('#emo-premium-filter-shell .widget_price_filter .price_slider_amount .button');
  if (!button) {
    failures.push(`${label}: price filter button missing`);
    return false;
  }
  const ok = await waitNavigation(page, () => button.click(), `${label}: apply price`);
  return ok;
}

async function clearFilters(page, label) {
  const clear = await page.$('#emo-premium-filter-shell .emo-active-filters__clear');
  if (!clear) {
    failures.push(`${label}: clear active filters link missing`);
    return false;
  }
  return waitNavigation(page, () => clear.click(), `${label}: clear filters`);
}

async function runTagFlow(page, width) {
  const label = `${width}px tag`;
  await go(page, '/tienda/', 750);
  if (!(await openDrawer(page, label))) return;
  const tag = await page.evaluate(() => {
    const visible = (node) => {
      const r = node.getBoundingClientRect();
      const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
    };
    const links = [...document.querySelectorAll('#emo-premium-filter-shell .widget_product_tag_cloud a,#emo-premium-filter-shell .tagcloud a')].filter((link) => visible(link) && link.href);
    const link = links[0];
    return link ? { href: link.href, text: (link.textContent || '').replace(/\s+/g, ' ').trim() } : null;
  });
  if (!tag) {
    failures.push(`${label}: no tag link available`);
    return;
  }
  if (!(await clickLinkByHref(page, tag.href, label))) return;
  const toolbar = await toolbarState(page);
  assertToolbar(toolbar, label);
  if (!(await openDrawer(page, label))) return;
  const state = await drawerState(page);
  const needle = normalizeText(tag.text).toLowerCase();
  const marked = [...state.activeLinks, ...state.chips].some((text) => normalizeText(text).toLowerCase().includes(needle));
  if (!marked) failures.push(`${label}: applied tag is not visually selected (${tag.text}) ${JSON.stringify(state)}`);
  if (state.overflow > 1) failures.push(`${label}: drawer horizontal overflow ${state.overflow}px`);
  checks[`tag-${width}`] = { tag, toolbar, state };
  await page.screenshot({ path: `qa/filter-flow-${width}-tag.png`, fullPage: false });
  await clearFilters(page, label);
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: 'new',
    protocolTimeout: 120000,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    for (const width of WIDTHS) {
      const page = await browser.newPage();
      const height = width <= 390 ? 844 : 900;
      await page.setViewport({ width, height, deviceScaleFactor: 1, isMobile: width <= 390, hasTouch: true });
      page.setDefaultNavigationTimeout(60000);
      const browserErrors = [];
      page.on('console', (message) => {
        if (message.type() === 'error' && /EMO_QA_FILTER_|uncaught|typeerror|referenceerror/i.test(message.text())) browserErrors.push(message.text());
      });

      try {
        const label = `${width}px`;
        await go(page, '/tienda/', 750);
        if (!(await openDrawer(page, `${label} initial`))) continue;

        const category = await firstCategory(page);
        if (!category) {
          failures.push(`${label}: no category link available`);
          continue;
        }
        if (!(await clickLinkByHref(page, category.href, `${label} category`))) continue;

        const categoryToolbar = await toolbarState(page);
        assertToolbar(categoryToolbar, `${label} category`);
        await page.screenshot({ path: `qa/filter-flow-${width}-toolbar.png`, fullPage: false });

        if (!(await openDrawer(page, `${label} category reopen`))) continue;
        const categoryState = await drawerState(page);
        const categoryNeedle = normalizeText(category.text).toLowerCase();
        const categoryMarked = [...categoryState.activeLinks, ...categoryState.chips].some((text) => normalizeText(text).toLowerCase().includes(categoryNeedle));
        if (!categoryMarked) failures.push(`${label}: applied category is not visually selected (${category.text}) ${JSON.stringify(categoryState)}`);
        if (categoryState.overflow > 1) failures.push(`${label}: category drawer horizontal overflow ${categoryState.overflow}px`);

        if (!(await applyPriceByDrag(page, `${label} price`))) continue;
        const priceToolbar = await toolbarState(page);
        assertToolbar(priceToolbar, `${label} price`);
        if (!(await openDrawer(page, `${label} price reopen`))) continue;
        const priceState = await drawerState(page);
        const params = new URL(priceState.url).searchParams;
        const min = params.get('min_price');
        const max = params.get('max_price');
        if (min === null && max === null) failures.push(`${label}: price parameters missing after applying slider (${priceState.url})`);
        if (min !== null && priceState.minInput !== null && String(Number(priceState.minInput)) !== String(Number(min))) failures.push(`${label}: minimum price input lost state (url=${min}, input=${priceState.minInput})`);
        if (max !== null && priceState.maxInput !== null && String(Number(priceState.maxInput)) !== String(Number(max))) failures.push(`${label}: maximum price input lost state (url=${max}, input=${priceState.maxInput})`);
        if (!priceState.chips.some((text) => /^Precio:/i.test(text))) failures.push(`${label}: price filter chip missing after reopening ${JSON.stringify(priceState.chips)}`);
        const stillCategory = [...priceState.activeLinks, ...priceState.chips].some((text) => normalizeText(text).toLowerCase().includes(categoryNeedle));
        if (!stillCategory) failures.push(`${label}: category state disappeared after applying price (${category.text})`);
        if (priceState.overflow > 1) failures.push(`${label}: price drawer horizontal overflow ${priceState.overflow}px`);
        await page.screenshot({ path: `qa/filter-flow-${width}-postapply.png`, fullPage: false });

        checks[`flow-${width}`] = {
          category,
          categoryToolbar,
          categoryState,
          priceToolbar,
          priceState,
          browserErrors: [...browserErrors],
        };

        if (browserErrors.length) failures.push(`${label}: filter QA console errors ${browserErrors.join(' | ')}`);

        if (await clearFilters(page, label)) {
          if (!(await openDrawer(page, `${label} cleared`))) continue;
          const cleared = await drawerState(page);
          const clearedParams = new URL(cleared.url).searchParams;
          if (cleared.chips.length || cleared.activeLinks.length) failures.push(`${label}: drawer still shows active state after clearing ${JSON.stringify(cleared)}`);
          if (clearedParams.has('min_price') || clearedParams.has('max_price')) failures.push(`${label}: price params remain after clearing (${cleared.url})`);
          checks[`cleared-${width}`] = cleared;
        }

        if (width === 390) await runTagFlow(page, width);
      } finally {
        await page.close();
      }
    }
  } finally {
    await browser.close();
  }

  fs.writeFileSync('qa/filter-postapply-flow-check.json', JSON.stringify({ failures, checks }, null, 2));
  if (failures.length) {
    console.error(failures.join('\n'));
    process.exitCode = 2;
  } else {
    console.log(`FILTER_POSTAPPLY_FLOW_OK ${JSON.stringify(checks)}`);
  }
})();
