const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const WIDTHS = [360, 375, 390, 768, 900, 991, 992, 1024, 1100];
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];
const checks = {};

const cleanText = (value) => String(value || '').replace(/\s+/g, ' ').trim();
const norm = (value) => cleanText(value).toLocaleLowerCase('es');
const comparableUrl = (value) => {
  try {
    const url = new URL(value, BASE);
    url.searchParams.delete('filter-flow');
    return `${url.pathname.replace(/\/+$/, '') || '/'}?${[...url.searchParams.entries()].sort().map(([k,v]) => `${k}=${v}`).join('&')}`;
  } catch (_) {
    return String(value || '');
  }
};

async function hideTransientUI(page) {
  await page.addStyleTag({ content: '#cookie-law-info-bar,#cookie-law-info-again,#ht-ctc-chat{display:none!important}' }).catch(() => {});
}

async function settle(page, delay = 700) {
  await page.waitForFunction(() => document.readyState !== 'loading', { timeout: 12000 }).catch(() => {});
  await page.waitForSelector('.woostify-sorting', { timeout: 12000 }).catch(() => {});
  await hideTransientUI(page);
  await sleep(delay);
}

async function go(page, path) {
  const separator = path.includes('?') ? '&' : '?';
  const response = await page.goto(`${BASE}${path}${separator}filter-flow=${Date.now()}`, {
    waitUntil: 'domcontentloaded',
    timeout: 45000,
  });
  await settle(page);
  if (!response || response.status() >= 400) failures.push(`${path}: HTTP ${response?.status() || 'none'}`);
}

async function catalogSnapshot(page) {
  try {
    return await page.evaluate(() => ({
      url: location.href,
      count: (document.querySelector('.woocommerce-result-count')?.textContent || '').replace(/\s+/g, ' ').trim(),
      products: [...document.querySelectorAll('ul.products li.product a.woocommerce-LoopProduct-link,ul.products li.product a[href*="/producto/"]')]
        .slice(0, 8)
        .map((a) => a.href)
        .join('|'),
      title: document.title,
      body: document.body.className,
    }));
  } catch (_) {
    return { url: page.url(), count: '', products: '', title: '', body: '' };
  }
}

function snapshotChanged(before, after, expectedHref = '') {
  if (comparableUrl(after.url) !== comparableUrl(before.url)) return true;
  if (expectedHref && comparableUrl(after.url) === comparableUrl(expectedHref) && comparableUrl(before.url) !== comparableUrl(expectedHref)) return true;
  if (after.count && before.count && after.count !== before.count) return true;
  if (after.products && before.products && after.products !== before.products) return true;
  if (after.body && before.body && after.body !== before.body) return true;
  return false;
}

async function triggerCatalogChange(page, action, label, expectedHref = '') {
  const before = await catalogSnapshot(page);
  try {
    await action();
  } catch (error) {
    failures.push(`${label}: click/submit failed (${error.message})`);
    return null;
  }

  let after = before;
  let changed = false;
  for (let i = 0; i < 80; i += 1) {
    await sleep(150);
    after = await catalogSnapshot(page);
    if (snapshotChanged(before, after, expectedHref)) {
      changed = true;
      break;
    }
  }

  await settle(page, 800);
  after = await catalogSnapshot(page);
  if (!changed) changed = snapshotChanged(before, after, expectedHref);
  if (!changed) {
    failures.push(`${label}: click produced no URL/catalog change (before=${JSON.stringify(before)}, after=${JSON.stringify(after)}, expected=${expectedHref})`);
    return null;
  }
  return { before, after };
}

async function shellIsOpen(page) {
  return page.evaluate(() => {
    const shell = document.querySelector('#emo-premium-filter-shell');
    const panel = shell?.querySelector('.emo-mobile-filter-panel');
    if (!shell || shell.hidden || !panel) return false;
    const rect = panel.getBoundingClientRect();
    const style = getComputedStyle(panel);
    return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden';
  }).catch(() => false);
}

async function openDrawer(page, label) {
  if (!(await shellIsOpen(page))) {
    const toggle = await page.$('#emo-premium-filter-toggle');
    if (!toggle) {
      failures.push(`${label}: filter toggle missing`);
      return false;
    }
    await toggle.click().catch((error) => failures.push(`${label}: filter toggle click failed (${error.message})`));
    await sleep(300);
  }
  const open = await shellIsOpen(page);
  if (!open) failures.push(`${label}: filter drawer did not open`);
  return open;
}

async function closeDrawerIfOpen(page) {
  if (!(await shellIsOpen(page))) return false;
  const close = await page.$('#emo-premium-filter-shell .emo-mobile-filter-close');
  if (close) await close.click().catch(() => {});
  await sleep(260);
  return true;
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
    const rect = (node) => {
      if (!visible(node)) return null;
      const r = node.getBoundingClientRect();
      return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width, height: r.height };
    };
    if (!toolbar) return { exists: false };

    const count = [...toolbar.querySelectorAll('.woocommerce-result-count')].find(visible) || null;
    const ordering = [...toolbar.querySelectorAll('.woocommerce-ordering')].find(visible) || null;
    const countRect = rect(count);
    const orderingRect = rect(ordering);
    const rogueChildren = [...toolbar.children].filter((child) => {
      const keepsCount = child.matches?.('.woocommerce-result-count') || child.querySelector?.('.woocommerce-result-count');
      const keepsOrdering = child.matches?.('.woocommerce-ordering') || child.querySelector?.('.woocommerce-ordering');
      return !keepsCount && !keepsOrdering && visible(child);
    });
    const rogueControls = [...toolbar.querySelectorAll('input,select,button')].filter((control) => {
      if (control.matches('select[name="orderby"]') || control.closest('.woocommerce-ordering')) return false;
      return visible(control);
    });
    const overlap = !!(countRect && orderingRect &&
      Math.max(countRect.left, orderingRect.left) < Math.min(countRect.right, orderingRect.right) &&
      Math.max(countRect.top, orderingRect.top) < Math.min(countRect.bottom, orderingRect.bottom));

    return {
      exists: true,
      rogueCount: rogueChildren.length,
      rogueControls: rogueControls.map((node) => ({ tag: node.tagName, name: node.getAttribute('name') || '', cls: node.className || '', text: (node.textContent || '').trim(), rect: rect(node) })),
      countRect,
      orderingRect,
      overlap,
      text: (toolbar.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 220),
    };
  });
}

function assertToolbar(metric, label) {
  if (!metric.exists) failures.push(`${label}: sorting toolbar missing`);
  if ((metric.rogueCount || 0) > 0) failures.push(`${label}: ${metric.rogueCount} visible extra toolbar child`);
  if ((metric.rogueControls || []).length > 0) failures.push(`${label}: rogue toolbar controls ${JSON.stringify(metric.rogueControls)}`);
  if (!metric.countRect) failures.push(`${label}: result count missing`);
  if (!metric.orderingRect) failures.push(`${label}: ordering missing`);
  if (metric.overlap) failures.push(`${label}: result count overlaps ordering`);
}

async function drawerState(page) {
  return page.evaluate(() => {
    const shell = document.querySelector('#emo-premium-filter-shell');
    const content = shell?.querySelector('.emo-mobile-filter-content');
    const panel = shell?.querySelector('.emo-mobile-filter-panel');
    const visible = (node) => {
      if (!node) return false;
      const rect = node.getBoundingClientRect();
      const style = getComputedStyle(node);
      return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
    };
    const active = content ? [...new Set([...content.querySelectorAll('a.emo-filter-is-active,.emo-filter-is-active > a')].filter(visible).map((a) => (a.textContent || '').replace(/\s+/g, ' ').trim()))] : [];
    const chips = content ? [...content.querySelectorAll('.emo-active-filter-chip')].filter(visible).map((n) => (n.textContent || '').replace(/\s+/g, ' ').trim()) : [];
    const min = content?.querySelector('.widget_price_filter input[name="min_price"]')?.value ?? null;
    const max = content?.querySelector('.widget_price_filter input[name="max_price"]')?.value ?? null;
    const headings = content ? [...content.querySelectorAll('.widget > .widget-title,.widget > .sidebar-heading,.widget > .widget-heading,.widget > .wp-block-heading')].filter(visible).map((n) => {
      const r = n.getBoundingClientRect(); const s = getComputedStyle(n);
      return { text: (n.textContent || '').replace(/\s+/g, ' ').trim(), height: r.height, bg: s.backgroundColor, radius: s.borderRadius };
    }) : [];
    return {
      open: !!shell && !shell.hidden && !!panel && visible(panel),
      overflow: content ? Math.max(0, content.scrollWidth - content.clientWidth) : null,
      panelWidth: panel?.getBoundingClientRect().width || 0,
      active,
      chips,
      min,
      max,
      headings,
      toggleText: (document.querySelector('#emo-premium-filter-toggle')?.textContent || '').replace(/\s+/g, ' ').trim(),
      url: location.href,
    };
  });
}

async function firstFilterLink(page, selector) {
  return page.evaluate((sel) => {
    const current = new URL(location.href);
    current.searchParams.delete('filter-flow');
    const key = (value) => {
      try {
        const u = new URL(value, location.href); u.searchParams.delete('filter-flow');
        return `${u.pathname.replace(/\/+$/, '') || '/'}?${[...u.searchParams.entries()].sort().map(([k,v]) => `${k}=${v}`).join('&')}`;
      } catch (_) { return value; }
    };
    const visible = (node) => {
      const r = node.getBoundingClientRect(); const s = getComputedStyle(node);
      return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
    };
    const links = [...document.querySelectorAll(sel)].filter((link) => visible(link) && link.href && !/^javascript:/i.test(link.href) && key(link.href) !== key(current.href));
    const link = links[0];
    return link ? { href: link.href, text: (link.textContent || '').replace(/\s+/g, ' ').trim() } : null;
  }, selector);
}

async function clickFilterLink(page, link, label) {
  const handle = await page.evaluateHandle((href) => [...document.querySelectorAll('#emo-premium-filter-shell a')].find((a) => a.href === href) || null, link.href);
  const element = handle.asElement();
  if (!element) {
    await handle.dispose();
    failures.push(`${label}: target link disappeared (${link.href})`);
    return null;
  }
  const result = await triggerCatalogChange(page, () => element.click(), label, link.href);
  await handle.dispose().catch(() => {});
  return result;
}

async function applyPrice(page, label) {
  const geometry = await page.evaluate(() => {
    const track = document.querySelector('#emo-premium-filter-shell .widget_price_filter .price_slider.ui-slider,#emo-premium-filter-shell .widget_price_filter .ui-slider-horizontal');
    const handle = document.querySelector('#emo-premium-filter-shell .widget_price_filter .ui-slider-handle');
    if (!track || !handle) return null;
    const tr = track.getBoundingClientRect(); const hr = handle.getBoundingClientRect();
    return { track: { x: tr.x, y: tr.y, width: tr.width, height: tr.height }, handle: { x: hr.x, width: hr.width } };
  });
  if (!geometry) { failures.push(`${label}: price slider missing`); return null; }

  const y = geometry.track.y + geometry.track.height / 2;
  const from = geometry.handle.x + geometry.handle.width / 2;
  const to = geometry.track.x + Math.max(30, geometry.track.width * 0.24);
  await page.mouse.move(from, y);
  await page.mouse.down();
  await page.mouse.move(to, y, { steps: 14 });
  await page.mouse.up();
  await sleep(180);

  const values = await page.evaluate(() => {
    const root = document.querySelector('#emo-premium-filter-shell .widget_price_filter');
    return { min: root?.querySelector('input[name="min_price"]')?.value ?? null, max: root?.querySelector('input[name="max_price"]')?.value ?? null };
  });
  if (!values || values.min === null || Number(values.min) <= 0) {
    failures.push(`${label}: dragging minimum price did not change value (${JSON.stringify(values)})`);
    return null;
  }

  const button = await page.$('#emo-premium-filter-shell .widget_price_filter .price_slider_amount .button');
  if (!button) { failures.push(`${label}: price apply button missing`); return null; }
  return triggerCatalogChange(page, () => button.click(), `${label}: apply`);
}

async function clearFilters(page, label) {
  const clear = await page.$('#emo-premium-filter-shell .emo-active-filters__clear');
  if (!clear) { failures.push(`${label}: clear link missing`); return null; }
  return triggerCatalogChange(page, () => clear.click(), `${label}: clear`, `${BASE}/tienda/`);
}

async function validateTagFlow(page) {
  await go(page, '/tienda/');
  if (!(await openDrawer(page, '390px tag initial'))) return;
  const tag = await firstFilterLink(page, '#emo-premium-filter-shell .widget_product_tag_cloud a,#emo-premium-filter-shell .tagcloud a');
  if (!tag) { failures.push('390px tag: no tag link available'); return; }
  const transition = await clickFilterLink(page, tag, '390px tag');
  if (!transition) return;
  const remainedOpen = await closeDrawerIfOpen(page);
  const toolbar = await toolbarState(page); assertToolbar(toolbar, '390px tag');
  if (!(await openDrawer(page, '390px tag reopen'))) return;
  const state = await drawerState(page);
  if (![...state.active, ...state.chips].some((text) => norm(text).includes(norm(tag.text)))) failures.push(`390px tag: applied tag not visually selected (${tag.text})`);
  if (state.overflow > 1) failures.push(`390px tag: drawer overflow ${state.overflow}px`);
  await page.screenshot({ path: 'qa/filter-flow-390-tag.png', fullPage: false });
  checks['tag-390'] = { tag, transition, remainedOpen, toolbar, state };
}

(async () => {
  fs.mkdirSync('qa', { recursive: true });
  const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', protocolTimeout: 120000, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  try {
    for (const width of WIDTHS) {
      const page = await browser.newPage();
      await page.setViewport({ width, height: width <= 390 ? 844 : 900, deviceScaleFactor: 1, isMobile: width <= 390, hasTouch: true });
      page.setDefaultTimeout(20000);
      page.setDefaultNavigationTimeout(45000);
      const browserErrors = [];
      page.on('console', (message) => {
        if (message.type() === 'error' && /EMO_QA_FILTER_|uncaught|typeerror|referenceerror/i.test(message.text())) browserErrors.push(message.text());
      });

      try {
        const label = `${width}px`;
        await go(page, '/tienda/');
        if (!(await openDrawer(page, `${label} initial`))) continue;

        const category = await firstFilterLink(page, '#emo-premium-filter-shell .widget_product_categories a');
        if (!category) { failures.push(`${label}: no category link available`); continue; }
        const categoryTransition = await clickFilterLink(page, category, `${label} category`);
        if (!categoryTransition) continue;

        const categoryRemainedOpen = await closeDrawerIfOpen(page);
        const categoryToolbar = await toolbarState(page); assertToolbar(categoryToolbar, `${label} category`);
        await page.screenshot({ path: `qa/filter-flow-${width}-toolbar.png`, fullPage: false });

        if (!(await openDrawer(page, `${label} category reopen`))) continue;
        const categoryState = await drawerState(page);
        const categoryMarked = [...categoryState.active, ...categoryState.chips].some((text) => norm(text).includes(norm(category.text)));
        if (!categoryMarked) failures.push(`${label}: applied category not visually selected (${category.text}) ${JSON.stringify(categoryState)}`);
        if (categoryState.overflow > 1) failures.push(`${label}: category drawer overflow ${categoryState.overflow}px`);

        const priceTransition = await applyPrice(page, `${label} price`);
        if (!priceTransition) continue;
        const priceRemainedOpen = await closeDrawerIfOpen(page);
        const priceToolbar = await toolbarState(page); assertToolbar(priceToolbar, `${label} price`);

        if (!(await openDrawer(page, `${label} price reopen`))) continue;
        const priceState = await drawerState(page);
        const params = new URL(priceState.url).searchParams;
        if (!params.has('min_price') && !params.has('max_price')) failures.push(`${label}: price parameters missing after apply (${priceState.url})`);
        if (params.has('min_price') && priceState.min !== null && Number(params.get('min_price')) !== Number(priceState.min)) failures.push(`${label}: minimum price state mismatch url=${params.get('min_price')} input=${priceState.min}`);
        if (params.has('max_price') && priceState.max !== null && Number(params.get('max_price')) !== Number(priceState.max)) failures.push(`${label}: maximum price state mismatch url=${params.get('max_price')} input=${priceState.max}`);
        if (!priceState.chips.some((text) => /^Precio:/i.test(text))) failures.push(`${label}: price chip missing after reopen ${JSON.stringify(priceState.chips)}`);
        if (![...priceState.active, ...priceState.chips].some((text) => norm(text).includes(norm(category.text)))) failures.push(`${label}: category state lost after price apply (${category.text})`);
        if (priceState.overflow > 1) failures.push(`${label}: price drawer overflow ${priceState.overflow}px`);
        await page.screenshot({ path: `qa/filter-flow-${width}-postapply.png`, fullPage: false });

        checks[`flow-${width}`] = { category, categoryTransition, categoryRemainedOpen, categoryToolbar, categoryState, priceTransition, priceRemainedOpen, priceToolbar, priceState, browserErrors };
        if (browserErrors.length) failures.push(`${label}: filter console errors ${browserErrors.join(' | ')}`);

        const clearTransition = await clearFilters(page, label);
        if (clearTransition) {
          await closeDrawerIfOpen(page);
          if (await openDrawer(page, `${label} cleared`)) {
            const cleared = await drawerState(page);
            const clearedParams = new URL(cleared.url).searchParams;
            if (cleared.chips.length || cleared.active.length) failures.push(`${label}: active state remains after clear ${JSON.stringify(cleared)}`);
            if (clearedParams.has('min_price') || clearedParams.has('max_price')) failures.push(`${label}: price params remain after clear (${cleared.url})`);
            checks[`cleared-${width}`] = { transition: clearTransition, state: cleared };
          }
        }

        if (width === 390) await validateTagFlow(page);
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
