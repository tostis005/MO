/**
 * QA visual de la landing de Navidad, sin enviar datos ni modificar WordPress.
 * Requiere playwright-core y un Chrome instalado en el runner.
 */
const { chromium } = require('playwright-core');

const url = process.env.NAVIDAD_URL || 'https://www.elmercadodeorigen.com/cestas-navidad-empresas-2026/';
const chrome = process.env.CHROME_PATH;
if (!chrome) throw Error('Chrome no disponible para verificación visual');

(async () => {
  const browser = await chromium.launch({
    executablePath: chrome,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  try {
    for (const width of [1440, 1024, 768, 600, 390, 360]) {
      const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
      const response = await page.goto(url + (url.includes('?') ? '&' : '?') + 'visual_navidad=' + Date.now(), {
        waitUntil: 'domcontentloaded',
        timeout: 55000
      });
      if (!response || response.status() !== 200) throw Error(width + ': la landing no devolvió HTTP 200');
      await page.locator('.emdo-xmas--landing .emdo-xmas__form-card').scrollIntoViewIfNeeded();
      await page.waitForTimeout(400);
      const metrics = await page.evaluate(() => {
        const get = sel => {
          const node = document.querySelector(sel);
          if (!node) throw Error('No encontrado: ' + sel);
          const rect = node.getBoundingClientRect();
          return { x:rect.x, y:rect.y, left:rect.left, right:rect.right, top:rect.top, bottom:rect.bottom,
            width:rect.width, height:rect.height };
        };
        const section = get('.emdo-xmas--landing .emdo-xmas__section--form-last');
        const layout = get('.emdo-xmas--landing .emdo-xmas__form-layout');
        const heading = get('.emdo-xmas--landing .emdo-xmas__form-intro h2');
        const card = get('.emdo-xmas--landing .emdo-xmas__form-card');
        const button = get('.emdo-xmas--landing .emdo-xmas__submit');
        const how = get('.emdo-xmas--landing .emdo-xmas__how');
        const inputs = [...document.querySelectorAll('.emdo-xmas--landing .emdo-xmas__fields input, .emdo-xmas--landing .emdo-xmas__fields textarea')]
          .map(el => {const r = el.getBoundingClientRect();return {name:el.name,left:r.left,right:r.right,top:r.top,bottom:r.bottom};});
        const computed = getComputedStyle(document.querySelector('.emdo-xmas--landing .emdo-xmas__form-layout'));
        const copy = document.querySelector('.emdo-xmas--landing .emdo-xmas__note').innerText;
        const intro = document.querySelector('.emdo-xmas--landing .emdo-xmas__form-intro').innerText;
        return { viewport: innerWidth, documentScrollWidth: document.documentElement.scrollWidth,
          section, layout, heading, card, button, how, inputs,
          flexDirection:computed.flexDirection, copy, intro };
      });

      const close = (a, b, eps=2) => a <= b + eps;
      const problems = [];
      if (metrics.flexDirection !== 'column') problems.push('El contenedor del formulario no está en columna');
      if (!close(metrics.how.bottom, metrics.section.top, 4)) problems.push('Sección anterior se superpone');
      if (!close(metrics.section.top, metrics.heading.top)) problems.push('El título aparece fuera de la sección');
      if (!close(metrics.heading.bottom + 8, metrics.card.top)) problems.push('El formulario se superpone al título');
      if (!close(metrics.card.bottom, metrics.section.bottom)) problems.push('El formulario sobresale sobre el siguiente contenedor');
      if (metrics.card.left < -2 || metrics.card.right > width + 2) problems.push('El formulario sobresale horizontalmente');
      if (!close(metrics.card.left, metrics.button.left) || !close(metrics.button.right, metrics.card.right)) {
        problems.push('El botón sobresale horizontalmente del formulario');
      }
      for (const input of metrics.inputs) {
        if (input.left < metrics.card.left - 2 || input.right > metrics.card.right + 2)
          problems.push('Campo fuera del formulario: ' + input.name);
      }
      if (/condiciones de transporte|conservaci[oó]n de cada producto|tipo de env[ií]o/i.test(metrics.copy))
        problems.push('Texto logístico anterior sigue visible');
      if (/n[uú]mero aproximado|presupuesto.*por unidad|fecha de entrega prevista/i.test(metrics.intro))
        problems.push('Texto redundante bajo el título sigue visible');
      console.log(JSON.stringify({
        viewport:width,
        column:metrics.flexDirection,
        card:{x:Math.round(metrics.card.left),width:Math.round(metrics.card.width),bottom:Math.round(metrics.card.bottom)},
        sectionBottom:Math.round(metrics.section.bottom),
        headingBottom:Math.round(metrics.heading.bottom),
        cardTop:Math.round(metrics.card.top),
        globalScrollWidth:metrics.documentScrollWidth,
        inputs:metrics.inputs.length,
        problems
      }));
      await page.screenshot({ path: '/tmp/navidad-2026-' + width + '.png', fullPage:true });
      await page.close();
      if (problems.length) throw Error(width + ': ' + problems.join('; '));
    }
    console.log('NAVIDAD_LAYOUT_RESPONSIVE_OK');
  } finally {
    await browser.close();
  }
})().catch(err => { console.error(err); process.exit(1); });
