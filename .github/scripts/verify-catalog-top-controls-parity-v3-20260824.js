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

  const toolbarSel = '.emo-catalog-toolbar-shared-010229';
  const orderSel = `${toolbarSel} .woocommerce-ordering > select[name="orderby"]`;
  const surfaces = [
    {key:'shop', path:'/tienda/', destination:'[data-mdo-destination-open]', modal:'[data-mdo-destination-modal]', modalSelect:'[data-mdo-destination-country]', closeButton:'.mdo-destination-modal__close'},
    {key:'1957', path:'/tienda/1957/', destination:'[data-mdo-ps-destination-open]', modal:'#mdo-ps-destination-dialog', modalSelect:'[data-mdo-ps-country]', closeButton:'.mdo-ps-modal__close'},
    {key:'hidalgo', path:'/tienda/hidalgo-de-la-jara/', destination:'[data-mdo-ps-destination-open]', modal:'#mdo-ps-destination-dialog', modalSelect:'[data-mdo-ps-country]', closeButton:'.mdo-ps-modal__close'},
  ];

  const snapshot = async (page, cfg) => page.evaluate(({toolbarSel, orderSel, destination}) => {
    const toolbar = document.querySelector(toolbarSel);
    const order = document.querySelector(orderSel);
    const form = order?.closest('.woocommerce-ordering');
    const dest = document.querySelector(destination);
    if (!toolbar || !order || !form || !dest) return null;

    const snap = el => {
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return {
        rect:{left:r.left,top:r.top,width:r.width,height:r.height,right:r.right,bottom:r.bottom},
        display:s.display, visibility:s.visibility, opacity:s.opacity, pointerEvents:s.pointerEvents,
        position:s.position,
        borderTop:s.borderTopWidth,borderRight:s.borderRightWidth,borderBottom:s.borderBottomWidth,borderLeft:s.borderLeftWidth,
        borderRadius:s.borderRadius,backgroundColor:s.backgroundColor,backgroundImage:s.backgroundImage,
        boxShadow:s.boxShadow,color:s.color,fontFamily:s.fontFamily,fontSize:s.fontSize,fontWeight:s.fontWeight,
        paddingLeft:s.paddingLeft,paddingRight:s.paddingRight,
      };
    };
    const pseudo = (el, which) => {
      const s = getComputedStyle(el, which);
      return {
        content:s.content,display:s.display,visibility:s.visibility,opacity:s.opacity,pointerEvents:s.pointerEvents,
        position:s.position,width:s.width,height:s.height,right:s.right,top:s.top,
        borderRight:s.borderRightWidth,borderBottom:s.borderBottomWidth,borderRightColor:s.borderRightColor,borderBottomColor:s.borderBottomColor,
        transform:s.transform,
      };
    };

    const dr = dest.getBoundingClientRect();
    const or = order.getBoundingClientRect();
    const destHit = document.elementFromPoint(dr.left + dr.width/2, dr.top + dr.height/2);
    const orderHit = document.elementFromPoint(or.left + or.width/2, or.top + or.height/2);
    const svgs = [...dest.querySelectorAll('svg')].map(svg => {
      const s = getComputedStyle(svg); const r = svg.getBoundingClientRect();
      return {display:s.display,visibility:s.visibility,width:r.width,height:r.height,opacity:s.opacity};
    });

    return {
      url:location.href,
      toolbar:snap(toolbar),destination:snap(dest),order:snap(order),form:snap(form),
      orderBefore:pseudo(form,'::before'),orderAfter:pseudo(form,'::after'),
      destHit:destHit === dest || dest.contains(destHit),
      orderHit:orderHit === order || order.contains(orderHit),
      disabled:order.disabled,ariaHidden:order.getAttribute('aria-hidden'),
      options:[...order.options].map(o => ({value:o.value,text:o.textContent.trim()})),value:order.value,
      destinationSvg:svgs,
      destinationText:dest.textContent.replace(/\s+/g,' ').trim(),
      toolbarFinal:toolbar.dataset.mdoCatalogParityFinal || '',
      arrowFinal:toolbar.dataset.mdoCatalogArrowFinal || '',
      producerMobileWidthParity:toolbar.dataset.mdoProducerMobileWidthParity || '',
      originalMarker:!!document.querySelector('#mdo-catalog-top-controls-parity-20260824'),
      finalMarker:!!document.querySelector('#mdo-catalog-top-controls-parity-final-20260824'),
      arrowMarker:!!document.querySelector('#mdo-catalog-top-controls-arrow-final-20260824'),
      duplicateOrderingEnhancers:form.querySelectorAll('.select2,.select2-container,.chosen-container,.nice-select,.selectize-control').length,
      inlineOrderStyle:order.getAttribute('style') || '',
    };
  }, {toolbarSel,orderSel,destination:cfg.destination});

  const assertSurface = (label, s) => {
    if (!s) fail(`${label}: controls missing`);
    if (!s.originalMarker || !s.finalMarker || !s.arrowMarker || s.toolbarFinal !== '20260824-v2' || s.arrowFinal !== '20260824-v3') {
      fail(`${label}: final owners did not run`, s);
    }
    if (!s.destHit || s.destination.visibility !== 'visible' || s.destination.opacity !== '1' || s.destination.pointerEvents === 'none') {
      fail(`${label}: destination control is obstructed`, s);
    }
    if (!s.orderHit || s.order.visibility !== 'visible' || s.order.opacity !== '1' || s.order.pointerEvents !== 'auto' || s.disabled || s.ariaHidden !== null) {
      fail(`${label}: native ordering is not directly interactive`, s);
    }
    if (s.duplicateOrderingEnhancers !== 0) fail(`${label}: duplicate ordering enhancer remains`, s);
    if (s.options.length < 2) fail(`${label}: ordering options missing`, s.options);

    const visibleDestArrows = s.destinationSvg.filter(x => x.display !== 'none' && x.visibility !== 'hidden' && x.width > 1 && x.height > 1 && x.opacity !== '0');
    if (visibleDestArrows.length !== 1) fail(`${label}: destination must have exactly one visible down arrow`, {all:s.destinationSvg,visibleDestArrows});

    const pseudoArrowVisible = s.orderAfter.display !== 'none' && s.orderAfter.visibility !== 'hidden' && parseFloat(s.orderAfter.width) >= 6 && parseFloat(s.orderAfter.height) >= 6 && parseFloat(s.orderAfter.borderRight) > 0 && parseFloat(s.orderAfter.borderBottom) > 0;
    if (!pseudoArrowVisible || s.orderAfter.pointerEvents !== 'none') fail(`${label}: ordering CSS arrow is missing or blocks clicks`, s.orderAfter);
    if (s.orderBefore.display !== 'none' && s.orderBefore.content !== 'none') fail(`${label}: unexpected second ordering pseudo-arrow`, s.orderBefore);
    if (s.order.backgroundImage !== 'none') fail(`${label}: ordering still has a second background arrow`, s.order);

    const geometry = {height:Math.abs(s.destination.rect.height-s.order.rect.height),width:Math.abs(s.destination.rect.width-s.order.rect.width)};
    if (geometry.height > 1 || geometry.width > 2) fail(`${label}: destination and ordering geometry differs`, {geometry,s});
    const keys = ['borderTop','borderRight','borderBottom','borderLeft','borderRadius','backgroundColor','boxShadow','color','fontSize','fontWeight'];
    const diff = Object.fromEntries(keys.filter(k => s.destination[k] !== s.order[k]).map(k => [k,{destination:s.destination[k],order:s.order[k]}]));
    if (Object.keys(diff).length) fail(`${label}: destination and ordering visual style differs`, diff);
    if ([s.order.borderTop,s.order.borderRight,s.order.borderBottom,s.order.borderLeft].some(v => Math.abs(parseFloat(v)-1) > .1)) fail(`${label}: ordering does not have one 1px border`, s.order);
    if ([s.form.borderTop,s.form.borderRight,s.form.borderBottom,s.form.borderLeft].some(v => parseFloat(v) > .1)) fail(`${label}: ordering wrapper adds a second border`, s.form);
  };

  const compareToShop = (label, shop, producer) => {
    const keys = ['borderTop','borderRight','borderBottom','borderLeft','borderRadius','backgroundColor','boxShadow','color','fontSize','fontWeight','paddingLeft','paddingRight'];
    for (const part of ['destination','order']) {
      const diff = Object.fromEntries(keys.filter(k => shop[part][k] !== producer[part][k]).map(k => [k,{shop:shop[part][k],producer:producer[part][k]}]));
      if (Object.keys(diff).length) fail(`${label}: ${part} differs from global shop`, diff);
      if (Math.abs(shop[part].rect.height-producer[part].rect.height) > 1) fail(`${label}: ${part} height differs from shop`, {shop:shop[part].rect,producer:producer[part].rect});
    }
    if (label.startsWith('mobile/')) {
      if (producer.producerMobileWidthParity !== '20260824-v4') fail(`${label}: producer mobile width parity owner did not run`, producer);
      const toolbarGeometry = {
        left:Math.abs(shop.toolbar.rect.left-producer.toolbar.rect.left),
        width:Math.abs(shop.toolbar.rect.width-producer.toolbar.rect.width),
      };
      if (toolbarGeometry.left > 1 || toolbarGeometry.width > 1) fail(`${label}: producer toolbar does not match global shop mobile width`, {toolbarGeometry,shop:shop.toolbar.rect,producer:producer.toolbar.rect});
      for (const part of ['destination','order']) {
        const geometry = {
          left:Math.abs(shop[part].rect.left-producer[part].rect.left),
          width:Math.abs(shop[part].rect.width-producer[part].rect.width),
        };
        if (geometry.left > 1 || geometry.width > 1) fail(`${label}: ${part} mobile width does not match global shop`, {geometry,shop:shop[part].rect,producer:producer[part].rect});
      }
    }
    if (JSON.stringify(shop.options) !== JSON.stringify(producer.options)) fail(`${label}: producer ordering options differ from shop`, {shop:shop.options,producer:producer.options});
  };

  const verifyDestinationPopup = async (page, label, cfg) => {
    await page.$eval(cfg.destination, el => el.scrollIntoView({block:'center',inline:'center'}));
    await sleep(100);
    await page.click(cfg.destination);
    await page.waitForFunction(modalSel => {
      const modal=document.querySelector(modalSel); if(!modal) return false;
      const r=modal.getBoundingClientRect(), s=getComputedStyle(modal);
      return !modal.hidden && modal.getAttribute('aria-hidden') === 'false' && s.display !== 'none' && r.width>0 && r.height>0;
    }, {timeout:5000}, cfg.modal);
    const state = await page.evaluate(({modal,modalSelect,closeButton}) => {
      const root=document.querySelector(modal), select=root?.querySelector(modalSelect), close=root?.querySelector(closeButton);
      const cr=close?.getBoundingClientRect();
      return {hidden:root?.hidden,ariaHidden:root?.getAttribute('aria-hidden'),options:select?[...select.options].map(o=>({value:o.value,text:o.textContent.trim()})):[],closeVisible:!!close && cr.width>0 && cr.height>0 && getComputedStyle(close).display!=='none'};
    }, {modal:cfg.modal,modalSelect:cfg.modalSelect,closeButton:cfg.closeButton});
    if (state.options.length < 2) fail(`${label}: destination popup has no usable country selector`, state);
    if (!state.closeVisible) fail(`${label}: destination popup close button is not visible`, state);
    await page.click(`${cfg.modal} ${cfg.closeButton}`);
    await page.waitForFunction(modalSel => {
      const modal=document.querySelector(modalSel); return !!modal && (modal.hidden || modal.getAttribute('aria-hidden') === 'true' || getComputedStyle(modal).display === 'none');
    }, {timeout:5000}, cfg.modal).catch(async () => {
      await page.keyboard.press('Escape');
      await page.waitForFunction(modalSel => { const m=document.querySelector(modalSel); return !!m && (m.hidden || m.getAttribute('aria-hidden') === 'true' || getComputedStyle(m).display === 'none'); }, {timeout:5000}, cfg.modal);
    });
    return state;
  };

  const verifyOrdering = async (page, label, s, hasTouch) => {
    await page.$eval(orderSel, el => el.scrollIntoView({block:'center',inline:'center'}));
    await page.evaluate(sel => {
      const el=document.querySelector(sel); window.__mdoClick=0; window.__mdoPointer=0;
      el.addEventListener('click',()=>window.__mdoClick++,{capture:true});
      el.addEventListener('pointerdown',()=>window.__mdoPointer++,{capture:true});
    }, orderSel);
    const p=await page.$eval(orderSel, el => {const r=el.getBoundingClientRect();return{x:r.left+r.width/2,y:r.top+r.height/2};});
    if (hasTouch) await page.touchscreen.tap(p.x,p.y); else await page.mouse.click(p.x,p.y);
    await sleep(200);
    const hit=await page.evaluate(sel=>({clicks:window.__mdoClick||0,pointer:window.__mdoPointer||0,active:document.activeElement===document.querySelector(sel)}),orderSel);
    if (hit.pointer < 1 || (!hasTouch && hit.clicks < 1)) fail(`${label}: real click/tap did not reach native select`, hit);
    await page.keyboard.press('Escape').catch(()=>{});
    const target=s.options.find(o=>o.value && o.value!==s.value); if(!target) fail(`${label}: no alternate ordering option`,s.options);
    const oldUrl=page.url();
    await page.select(orderSel,target.value);
    await page.waitForFunction((old,value)=>location.href!==old && new URL(location.href).searchParams.get('orderby')===value,{timeout:12000},oldUrl,target.value).catch(()=>{});
    await sleep(400);
    const newUrl=page.url();
    if(newUrl===oldUrl || new URL(newUrl).searchParams.get('orderby')!==target.value) fail(`${label}: ordering selection did not navigate`,{oldUrl,newUrl,target,hit});
    return {hit,target,oldUrl,newUrl};
  };

  try {
    const page=await browser.newPage();
    await page.setCacheEnabled(false);
    const viewports=[
      {name:'mobile',viewport:{width:390,height:844,isMobile:true,hasTouch:true,deviceScaleFactor:3},ua:'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1'},
      {name:'desktop',viewport:{width:1440,height:1000,isMobile:false,hasTouch:false,deviceScaleFactor:1},ua:'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'},
    ];
    const results={};
    for(const vp of viewports){
      await page.setViewport(vp.viewport); await page.setUserAgent(vp.ua); results[vp.name]={}; let shop=null;
      for(const cfg of surfaces){
        await page.goto(`${base}${cfg.path}?_mdo_top_controls_v3=${Date.now()}`,{waitUntil:'networkidle2',timeout:60000});
        await page.waitForSelector(toolbarSel,{visible:true,timeout:30000});
        await page.waitForSelector(cfg.destination,{visible:true,timeout:30000});
        await page.waitForSelector(orderSel,{visible:true,timeout:30000});
        await sleep(1500);
        const s=await snapshot(page,cfg);
        await page.screenshot({path:`/tmp/catalog-top-controls-v3-${vp.name}-${cfg.key}.png`,fullPage:false});
        assertSurface(`${vp.name}/${cfg.key}`,s);
        if(cfg.key==='shop') shop=s; else compareToShop(`${vp.name}/${cfg.key}`,shop,s);
        const modal=await verifyDestinationPopup(page,`${vp.name}/${cfg.key}`,cfg);
        let ordering=null;
        if(cfg.key==='shop'||cfg.key==='1957') ordering=await verifyOrdering(page,`${vp.name}/${cfg.key}`,s,vp.viewport.hasTouch);
        results[vp.name][cfg.key]={snapshot:s,modal,ordering};
      }
    }
    const out={ok:true,revision:'20260824-v4-width-parity',results};
    fs.writeFileSync('/tmp/catalog-top-controls-parity-v3-20260824.json',JSON.stringify(out,null,2));
    console.log(JSON.stringify({ok:true,revision:'20260824-v4-width-parity',surfaces:Object.keys(results.mobile).length,viewports:Object.keys(results).length}));
  } finally { await browser.close(); }
})().catch(error=>{
  const out={ok:false,revision:'20260824-v4-width-parity',error:String(error.stack||error)};
  try{fs.writeFileSync('/tmp/catalog-top-controls-parity-v3-20260824.json',JSON.stringify(out,null,2));}catch(_){}
  console.error(JSON.stringify(out)); process.exit(1);
});