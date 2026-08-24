'use strict';

const puppeteer = require('puppeteer-core');
const fs = require('fs');
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const fail = (message, data) => { throw new Error(`${message} ${JSON.stringify(data || {})}`); };

(async () => {
  const base = process.env.BASE_URL || 'https://www.elmercadodeorigen.com';
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  const orderSel = '.emo-catalog-toolbar-shared-010229 .woocommerce-ordering > select[name="orderby"]';
  const toolbarSel = '.emo-catalog-toolbar-shared-010229';

  const surfaces = [
    {
      key: 'shop',
      path: '/tienda/',
      destination: '[data-mdo-destination-open]',
      modal: '[data-mdo-destination-modal]',
      modalSelect: '[data-mdo-destination-country]',
      close: '[data-mdo-destination-close]',
    },
    {
      key: '1957',
      path: '/tienda/1957/',
      destination: '[data-mdo-ps-destination-open]',
      modal: '#mdo-ps-destination-dialog',
      modalSelect: '[data-mdo-ps-country]',
      close: '[data-mdo-ps-destination-close]',
    },
    {
      key: 'hidalgo',
      path: '/tienda/hidalgo-de-la-jara/',
      destination: '[data-mdo-ps-destination-open]',
      modal: '#mdo-ps-destination-dialog',
      modalSelect: '[data-mdo-ps-country]',
      close: '[data-mdo-ps-destination-close]',
    },
  ];

  const style = async (page, cfg) => page.evaluate(({ toolbarSel, orderSel, destination }) => {
    const toolbar = document.querySelector(toolbarSel);
    const order = document.querySelector(orderSel);
    const form = order?.closest('.woocommerce-ordering');
    const dest = document.querySelector(destination);
    if (!toolbar || !order || !form || !dest) return null;

    const snap = el => {
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return {
        rect: { left:r.left, top:r.top, width:r.width, height:r.height, right:r.right, bottom:r.bottom },
        display:s.display,
        visibility:s.visibility,
        opacity:s.opacity,
        pointerEvents:s.pointerEvents,
        borderTop:s.borderTopWidth,
        borderRight:s.borderRightWidth,
        borderBottom:s.borderBottomWidth,
        borderLeft:s.borderLeftWidth,
        borderRadius:s.borderRadius,
        backgroundColor:s.backgroundColor,
        backgroundImage:s.backgroundImage,
        boxShadow:s.boxShadow,
        color:s.color,
        fontFamily:s.fontFamily,
        fontSize:s.fontSize,
        fontWeight:s.fontWeight,
        paddingLeft:s.paddingLeft,
        paddingRight:s.paddingRight,
      };
    };

    const destRect = dest.getBoundingClientRect();
    const orderRect = order.getBoundingClientRect();
    const destHit = document.elementFromPoint(destRect.left + destRect.width / 2, destRect.top + destRect.height / 2);
    const orderHit = document.elementFromPoint(orderRect.left + orderRect.width / 2, orderRect.top + orderRect.height / 2);
    const svgs = [...dest.querySelectorAll('svg')].map(svg => {
      const s = getComputedStyle(svg);
      const r = svg.getBoundingClientRect();
      return { display:s.display, visibility:s.visibility, width:r.width, height:r.height };
    });

    return {
      url: location.href,
      toolbar: snap(toolbar),
      destination: snap(dest),
      order: snap(order),
      form: snap(form),
      destHit: destHit === dest || dest.contains(destHit),
      orderHit: orderHit === order || order.contains(orderHit),
      disabled: order.disabled,
      ariaHidden: order.getAttribute('aria-hidden'),
      options: [...order.options].map(o => ({value:o.value, text:o.textContent.trim()})),
      value: order.value,
      destinationSvg: svgs,
      destinationText: dest.textContent.replace(/\s+/g, ' ').trim(),
      orderBackgroundHasArrow: getComputedStyle(order).backgroundImage !== 'none',
      toolbarMarker: !!document.querySelector('#mdo-catalog-top-controls-parity-20260824'),
      duplicateOrderingEnhancers: form.querySelectorAll('.select2,.select2-container,.chosen-container,.nice-select,.selectize-control').length,
    };
  }, { toolbarSel, orderSel, destination: cfg.destination });

  const assertPill = (label, snap) => {
    if (!snap) fail(`${label}: controls missing`);
    if (!snap.toolbarMarker) fail(`${label}: final parity layer missing`, snap);
    if (snap.destination.visibility !== 'visible' || snap.destination.opacity !== '1' || snap.destination.pointerEvents === 'none') {
      fail(`${label}: destination is not interactive`, snap.destination);
    }
    if (snap.order.visibility !== 'visible' || snap.order.opacity !== '1' || snap.order.pointerEvents !== 'auto' || snap.disabled || snap.ariaHidden !== null) {
      fail(`${label}: native ordering is not directly interactive`, snap.order);
    }
    if (!snap.destHit) fail(`${label}: destination click target is obstructed`, snap);
    if (!snap.orderHit) fail(`${label}: ordering click target is obstructed`, snap);
    if (snap.duplicateOrderingEnhancers !== 0) fail(`${label}: ordering enhancer/duplicate remains`, snap);
    if (snap.options.length < 2) fail(`${label}: ordering has fewer than two options`, snap.options);
    if (!snap.orderBackgroundHasArrow) fail(`${label}: ordering down arrow is missing`, snap.order);

    const visibleDestinationSvgs = snap.destinationSvg.filter(x => x.display !== 'none' && x.visibility !== 'hidden' && x.width > 1 && x.height > 1);
    if (visibleDestinationSvgs.length !== 1) {
      fail(`${label}: destination must show exactly one visible chevron`, { svgs:snap.destinationSvg, visibleDestinationSvgs });
    }

    const diffs = {
      height: Math.abs(snap.destination.rect.height - snap.order.rect.height),
      width: Math.abs(snap.destination.rect.width - snap.order.rect.width),
    };
    if (diffs.height > 1 || diffs.width > 2) fail(`${label}: destination and ordering geometry differs`, { diffs, snap });

    const sameKeys = ['borderTop','borderRight','borderBottom','borderLeft','borderRadius','backgroundColor','boxShadow','color','fontSize','fontWeight'];
    const styleDiff = Object.fromEntries(sameKeys.filter(k => snap.destination[k] !== snap.order[k]).map(k => [k, { destination:snap.destination[k], order:snap.order[k] }]));
    if (Object.keys(styleDiff).length) fail(`${label}: destination and ordering pills differ visually`, { styleDiff, snap });

    if ([snap.order.borderTop,snap.order.borderRight,snap.order.borderBottom,snap.order.borderLeft].some(v => Math.abs(parseFloat(v) - 1) > 0.1)) {
      fail(`${label}: ordering does not have exactly one 1px border`, snap.order);
    }
    if ([snap.form.borderTop,snap.form.borderRight,snap.form.borderBottom,snap.form.borderLeft].some(v => parseFloat(v) > 0.1)) {
      fail(`${label}: ordering form adds an extra border`, snap.form);
    }
  };

  const compareSurface = (label, shop, producer) => {
    const keys = ['borderTop','borderRight','borderBottom','borderLeft','borderRadius','backgroundColor','boxShadow','color','fontSize','fontWeight','paddingLeft','paddingRight'];
    for (const part of ['destination','order']) {
      const diff = Object.fromEntries(keys.filter(k => shop[part][k] !== producer[part][k]).map(k => [k, {shop:shop[part][k], producer:producer[part][k]}]));
      if (Object.keys(diff).length) fail(`${label}: ${part} style differs from global shop`, diff);
      if (Math.abs(shop[part].rect.height - producer[part].rect.height) > 1) {
        fail(`${label}: ${part} height differs from global shop`, {shop:shop[part].rect, producer:producer[part].rect});
      }
    }
    if (JSON.stringify(shop.options) !== JSON.stringify(producer.options)) {
      fail(`${label}: ordering options differ from global shop`, {shop:shop.options, producer:producer.options});
    }
  };

  const clickDestinationAndClose = async (page, label, cfg) => {
    await page.$eval(cfg.destination, el => el.scrollIntoView({block:'center', inline:'center'}));
    await sleep(100);
    await page.click(cfg.destination);
    await page.waitForFunction(modalSel => {
      const modal = document.querySelector(modalSel);
      if (!modal) return false;
      const r = modal.getBoundingClientRect();
      const s = getComputedStyle(modal);
      return !modal.hidden && modal.getAttribute('aria-hidden') === 'false' && s.display !== 'none' && r.width > 0 && r.height > 0;
    }, {timeout:5000}, cfg.modal);

    const modalState = await page.evaluate(({modal, modalSelect}) => {
      const root = document.querySelector(modal);
      const select = root?.querySelector(modalSelect);
      return {
        hidden: root?.hidden,
        ariaHidden: root?.getAttribute('aria-hidden'),
        options: select ? [...select.options].map(o => ({value:o.value,text:o.textContent.trim()})) : [],
      };
    }, {modal:cfg.modal, modalSelect:cfg.modalSelect});
    if (modalState.options.length < 2) fail(`${label}: destination popup has no usable country selector`, modalState);

    await page.keyboard.press('Escape');
    await page.waitForFunction(modalSel => {
      const modal = document.querySelector(modalSel);
      return !!modal && (modal.hidden || modal.getAttribute('aria-hidden') === 'true' || getComputedStyle(modal).display === 'none');
    }, {timeout:5000}, cfg.modal);
    return modalState;
  };

  const realOrderingInteraction = async (page, label, snap, hasTouch) => {
    await page.$eval(orderSel, el => el.scrollIntoView({block:'center', inline:'center'}));
    await page.evaluate(sel => {
      const select = document.querySelector(sel);
      window.__mdoOrderClicks = 0;
      window.__mdoOrderPointer = 0;
      select.addEventListener('click', () => { window.__mdoOrderClicks += 1; }, {capture:true});
      select.addEventListener('pointerdown', () => { window.__mdoOrderPointer += 1; }, {capture:true});
    }, orderSel);
    const rect = await page.$eval(orderSel, el => {
      const r = el.getBoundingClientRect();
      return {x:r.left + r.width/2, y:r.top + r.height/2};
    });
    if (hasTouch) await page.touchscreen.tap(rect.x, rect.y);
    else await page.mouse.click(rect.x, rect.y);
    await sleep(250);
    const clickState = await page.evaluate(sel => ({
      clicks:window.__mdoOrderClicks || 0,
      pointer:window.__mdoOrderPointer || 0,
      active:document.activeElement === document.querySelector(sel),
    }), orderSel);
    if (clickState.clicks < 1 || clickState.pointer < 1) fail(`${label}: real click/tap did not reach native ordering`, clickState);
    await page.keyboard.press('Escape').catch(() => {});

    const target = snap.options.find(o => o.value && o.value !== snap.value);
    if (!target) fail(`${label}: no alternate ordering option available`, snap.options);
    const oldUrl = page.url();
    await page.select(orderSel, target.value);
    await page.waitForFunction((old, value) => {
      return location.href !== old && new URL(location.href).searchParams.get('orderby') === value;
    }, {timeout:12000}, oldUrl, target.value).catch(() => {});
    await sleep(500);
    const newUrl = page.url();
    if (newUrl === oldUrl || new URL(newUrl).searchParams.get('orderby') !== target.value) {
      fail(`${label}: ordering selection did not navigate`, {oldUrl,newUrl,target,clickState});
    }
    return {clickState,target,oldUrl,newUrl};
  };

  try {
    const page = await browser.newPage();
    await page.setCacheEnabled(false);
    const results = {};
    const viewports = [
      {name:'mobile', viewport:{width:390,height:844,isMobile:true,hasTouch:true,deviceScaleFactor:3}, ua:'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1'},
      {name:'desktop', viewport:{width:1440,height:1000,isMobile:false,hasTouch:false,deviceScaleFactor:1}, ua:'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'},
    ];

    for (const viewport of viewports) {
      await page.setViewport(viewport.viewport);
      await page.setUserAgent(viewport.ua);
      results[viewport.name] = {};
      let shopSnap = null;

      for (const cfg of surfaces) {
        const url = `${base}${cfg.path}?_mdo_top_controls=${Date.now()}`;
        await page.goto(url, {waitUntil:'networkidle2', timeout:60000});
        await page.waitForSelector(toolbarSel, {visible:true, timeout:30000});
        await page.waitForSelector(cfg.destination, {visible:true, timeout:30000});
        await page.waitForSelector(orderSel, {visible:true, timeout:30000});
        await sleep(900);

        const snap = await style(page, cfg);
        assertPill(`${viewport.name}/${cfg.key}`, snap);
        if (cfg.key === 'shop') shopSnap = snap;
        else compareSurface(`${viewport.name}/${cfg.key}`, shopSnap, snap);

        await page.screenshot({path:`/tmp/catalog-top-controls-${viewport.name}-${cfg.key}.png`, fullPage:false});
        const modal = await clickDestinationAndClose(page, `${viewport.name}/${cfg.key}`, cfg);

        let ordering = null;
        if (cfg.key === 'shop' || cfg.key === '1957') {
          ordering = await realOrderingInteraction(page, `${viewport.name}/${cfg.key}`, snap, viewport.viewport.hasTouch);
        }

        results[viewport.name][cfg.key] = {snap,modal,ordering};
      }
    }

    const payload = {ok:true, revision:'20260824-1.1.0', results};
    fs.writeFileSync('/tmp/catalog-top-controls-parity-20260824.json', JSON.stringify(payload, null, 2));
    console.log(JSON.stringify(payload));
  } finally {
    await browser.close();
  }
})().catch(error => {
  const payload = {ok:false,error:String(error.stack || error)};
  try { fs.writeFileSync('/tmp/catalog-top-controls-parity-20260824.json', JSON.stringify(payload, null, 2)); } catch (_) {}
  console.error(JSON.stringify(payload));
  process.exit(1);
});
