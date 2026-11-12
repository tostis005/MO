// Black Friday production regression: global curtain, daily frequency,
// cross-page persistence, overnight reset and canonical campaign navigation.
const {chromium} = require('playwright-core');

(async () => {
  const executablePath=process.env.CHROME;
  if (!executablePath) throw Error('Chromium binary not configured');
  const browser=await chromium.launch({executablePath,headless:true,
    args:['--no-sandbox','--disable-dev-shm-usage']});
  try {
    for (const [label,width,height] of [
      ['mobile-small',320,720],['mobile-medium',360,780],
      ['mobile',390,844],['tablet',768,1024],['desktop',1440,900]
    ]) {
      const context=await browser.newContext({viewport:{width,height}});
      const page=await context.newPage();
      const base='https://www.elmercadodeorigen.com';
      const home=await page.goto(base+'/?bf_global_check='+label+'_'+(process.env.GITHUB_RUN_ID||'local'),
        {waitUntil:'domcontentloaded',timeout:90000});
      if(!home || home.status()!==200) throw Error('Home not 200 at '+label);
      await page.waitForSelector('#bf26-overlay',{timeout:20000});
      await page.waitForFunction(() => {
        const x=document.getElementById('bf26-overlay');
        return x && !x.hidden && document.body.classList.contains('bf26-modal-open');
      },{timeout:20000});
      const state=await page.evaluate(() => {
        const el=document.getElementById('bf26-overlay');
        const panel=el.querySelector('.bf26-overlay__panel');
        const rect=panel.getBoundingClientRect();
        const style=getComputedStyle(el);
        return {
          overlays:document.querySelectorAll('#bf26-overlay').length,
          css:!!document.querySelector('link[href*="curtain.css"]'),
          js:!!document.querySelector('script[src*="preview.js"]'),
          seen:localStorage.getItem('emdo_bf2026_curtain_last_seen'),
          daily:new Date().toLocaleDateString('en-CA'),
          visible:!el.hidden,zIndex:Number(style.zIndex),
          panelLeft:rect.left,panelRight:rect.right,viewport:innerWidth,
          destination:el.querySelector('.bf26-overlay__cta')?.getAttribute('href')
        };
      });
      if(state.overlays!==1 || !state.css || !state.js || !state.visible ||
        !state.seen || state.zIndex<10000 || state.panelLeft < -3 ||
        state.panelRight > width + 3 ||
        !state.destination?.includes('/black-friday-2026/')) {
        throw Error('Broken global BF curtain at '+label+': '+JSON.stringify(state));
      }
      await page.locator('#bf26-overlay [data-bf26-close]').first().click();
      if(await page.locator('#bf26-overlay').evaluate(el=>!el.hidden)) {
        throw Error('Close button failed at '+label);
      }
      await page.reload({waitUntil:'domcontentloaded',timeout:90000});
      await page.waitForTimeout(700);
      if(await page.locator('#bf26-overlay').evaluate(el=>!el.hidden)) {
        throw Error('Curtain repeated on same local day after reload at '+label);
      }
      // Shared daily key also prevents repeats when visiting product-category pages.
      const category=await page.goto(base+'/tienda/hidalgo-de-la-jara/?bf_global_check='+label,
        {waitUntil:'domcontentloaded',timeout:90000});
      if(category && category.status()===200 && await page.locator('#bf26-overlay').count()){
        await page.waitForTimeout(400);
        if(await page.locator('#bf26-overlay').evaluate(el=>!el.hidden)) {
          throw Error('Curtain repeated on another page at '+label);
        }
      }
      // Simulate a new day without depending on the test runner clock.
      await page.goto(base+'/?bf_new_day_check='+label,{waitUntil:'domcontentloaded',timeout:90000});
      await page.evaluate(() => {
        localStorage.setItem('emdo_bf2026_curtain_last_seen','2000-01-01');
        sessionStorage.setItem('emdo_bf2026_curtain_session_seen','2000-01-01');
        document.cookie='emdo_bf2026_curtain_last_seen=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;SameSite=Lax';
      });
      await page.reload({waitUntil:'domcontentloaded',timeout:90000});
      await page.waitForFunction(() => {
        const el=document.getElementById('bf26-overlay');return el&&!el.hidden;
      },{timeout:20000});
      // The global CTA opens the canonical offer page and does not loop the modal.
      await Promise.all([
        page.waitForURL(/\/black-friday-2026\//,{timeout:45000}),
        page.locator('#bf26-overlay .bf26-overlay__cta').click()
      ]);
      await page.waitForSelector('[data-bf26-visible-sellers="hidalgo"]',{timeout:20000});
      if(await page.locator('[data-bf26-seller="montjam"]').count()) {
        throw Error('Montjam unexpectedly visible on campaign landing');
      }
      if(await page.locator('#bf26-overlay').evaluate(el=>!el.hidden)) {
        throw Error('Landing repeated already-seen daily curtain at '+label);
      }
      console.log('BF2026_DAILY_GLOBAL_CURTAIN_OK '+label+' '+width+'px');
      await context.close();
    }
  }finally{await browser.close();}
})().catch(e=>{console.error(e.stack||e);process.exit(1)});
