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

  const surfaces = [
    {key:'shop', path:'/tienda/', destination:'[data-mdo-destination-open]', country:'[data-mdo-destination-country]'},
    {key:'1957', path:'/tienda/1957/', destination:'[data-mdo-ps-destination-open]', country:'[data-mdo-ps-country]'},
    {key:'hidalgo', path:'/tienda/hidalgo-de-la-jara/', destination:'[data-mdo-ps-destination-open]', country:'[data-mdo-ps-country]'},
  ];
  const viewports = [390, 709];
  const expectedSpanish = {
    ES:'España', DE:'Alemania', AT:'Austria', BE:'Bélgica', BG:'Bulgaria', FR:'Francia',
    GR:'Grecia', HU:'Hungría', IT:'Italia', LU:'Luxemburgo', NL:'Países Bajos', PL:'Polonia',
    PT:'Portugal', CZ:'Chequia', SE:'Suecia', CH:'Suiza',
  };

  const snap = async (page, cfg) => page.evaluate(({destination, country}) => {
    const toolbar = document.querySelector('.emo-catalog-toolbar-shared-010229');
    const trigger = document.querySelector(destination);
    const wrap = trigger?.closest('.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination') || trigger?.parentElement;
    const form = toolbar?.querySelector('.woocommerce-ordering');
    const order = form?.querySelector('select[name="orderby"]');
    const countrySelect = document.querySelector(country);
    if (!toolbar || !trigger || !wrap || !form || !order || !countrySelect) return null;
    const rect = el => { const r=el.getBoundingClientRect(); return {left:r.left,right:r.right,width:r.width,height:r.height,top:r.top,bottom:r.bottom}; };
    const orderStyle = getComputedStyle(order);
    const triggerStyle = getComputedStyle(trigger);
    return {
      viewportWidth: window.innerWidth,
      url:location.href,
      marker:toolbar.dataset.mdoCatalogRuntimeGuard || '',
      toolbar:rect(toolbar), destination:rect(wrap), form:rect(form), order:rect(order),
      orderTextAlign:orderStyle.textAlign,
      orderTextAlignLast:orderStyle.textAlignLast,
      destinationTextAlign:triggerStyle.textAlign,
      destinationStrong:trigger.querySelector('strong')?.textContent.trim() || '',
      countryValue:countrySelect.value,
      countries:[...countrySelect.options].map(o=>({value:String(o.value||'').toUpperCase(),text:o.textContent.trim()})),
      formStyle:form.getAttribute('style') || '',
    };
  }, cfg);

  const assertState = (label, state) => {
    if (!state) fail(`${label}: runtime-guard controls missing`);
    if (state.marker !== '20260824-v2') fail(`${label}: runtime guard did not run`, state);
    const geometry = {
      left:Math.abs(state.destination.left-state.form.left),
      width:Math.abs(state.destination.width-state.form.width),
      selectWidth:Math.abs(state.destination.width-state.order.width),
    };
    if (geometry.left > 1 || geometry.width > 1 || geometry.selectWidth > 1) {
      fail(`${label}: ordering is not the same length and position as destination`, {geometry,state});
    }
    if (state.orderTextAlign !== 'left' || state.orderTextAlignLast !== 'left' || state.destinationTextAlign !== 'left') {
      fail(`${label}: mobile control text is not left aligned`, state);
    }
    const byCode = Object.fromEntries(state.countries.map(x=>[x.value,x.text]));
    for (const [code, spanish] of Object.entries(expectedSpanish)) {
      if (code in byCode && byCode[code] !== spanish) fail(`${label}: country ${code} is not Spanish`, {actual:byCode[code],expected:spanish,countries:state.countries});
    }
    if (state.countryValue === 'ES' && state.destinationStrong !== 'España') {
      fail(`${label}: visible Spanish destination must say España`, state);
    }
  };

  try {
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Linux; Android 15; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36');
    await page.setCacheEnabled(false);
    const results = {};

    for (const width of viewports) {
      await page.setViewport({width,height:1000,isMobile:true,hasTouch:true,deviceScaleFactor:1});
      results[width] = {};

      for (const cfg of surfaces) {
        await page.goto(`${base}${cfg.path}?_mdo_runtime_guard=${width}-${Date.now()}`, {waitUntil:'networkidle2', timeout:60000});
        await page.waitForSelector('.emo-catalog-toolbar-shared-010229', {visible:true, timeout:30000});
        await page.waitForSelector(cfg.destination, {visible:true, timeout:30000});
        await page.waitForSelector('.emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"]', {visible:true, timeout:30000});
        await sleep(4500);
        await page.$eval('.emo-catalog-toolbar-shared-010229', el => el.scrollIntoView({block:'center',inline:'center'}));
        await sleep(300);

        const stable = await snap(page, cfg);
        assertState(`${width}px/${cfg.key}/stable`, stable);

        if (cfg.key !== 'shop') {
          await page.$eval('.emo-catalog-toolbar-shared-010229 .woocommerce-ordering', form => {
            form.style.setProperty('width','48%','important');
            form.style.setProperty('min-width','48%','important');
            form.style.setProperty('max-width','48%','important');
            form.style.setProperty('margin-left','auto','important');
            form.style.setProperty('left','auto','important');
          });
          await sleep(700);
          const recovered = await snap(page, cfg);
          assertState(`${width}px/${cfg.key}/after-late-override`, recovered);
          results[width][cfg.key] = {stable,recovered};
        } else {
          results[width][cfg.key] = {stable};
        }

        await sleep(1500);
        const late = await snap(page, cfg);
        assertState(`${width}px/${cfg.key}/late`, late);
        results[width][cfg.key].late = late;
        await page.screenshot({path:`/tmp/catalog-mobile-runtime-guard-${width}-${cfg.key}.png`, fullPage:false});
      }
    }

    const out = {ok:true,revision:'20260824-runtime-guard-v2-767',viewports,results};
    fs.writeFileSync('/tmp/catalog-mobile-runtime-guard-20260824.json', JSON.stringify(out,null,2));
    console.log(JSON.stringify({ok:true,revision:'20260824-runtime-guard-v2-767',viewports,surfaces:surfaces.length}));
  } finally {
    await browser.close();
  }
})().catch(error => {
  const out={ok:false,revision:'20260824-runtime-guard-v2-767',error:String(error.stack||error)};
  try { fs.writeFileSync('/tmp/catalog-mobile-runtime-guard-20260824.json',JSON.stringify(out,null,2)); } catch (_) {}
  console.error(JSON.stringify(out));
  process.exit(1);
});
