'use strict';
/**
 * Auditoría de geometría real del breadcrumb y la galería en Chrome.
 * Read-only: no escribe nada en WordPress.
 */
const { chromium } = require('playwright-core');
const fs = require('fs');
const plugin = fs.readFileSync('mu-plugins/mdo-product-vendor-breadcrumb-20261021.php', 'utf8');
const cssMatch = plugin.match(/<style id="emo-product-breadcrumb-20261021">([\\s\\S]*?)<\\/style>/);
if (!cssMatch) throw Error('Candidate breadcrumb CSS missing');
const candidateCss = cssMatch[1];

const URLS = [
  ['aceite-1957', 'https://www.elmercadodeorigen.com/producto/aceite-de-oliva-virgen-extra-5l/'],
  ['paleta-hidalgo', 'https://www.elmercadodeorigen.com/producto/paleta-de-bellota-100-iberico/']
];
const SIZES = [320, 360, 390, 430, 600, 768, 1024, 1280, 1440, 1920];
const CHROME = process.env.CHROME_PATH;
if (!CHROME) throw Error('Chrome is required for geometry checks');
const round = x => Math.round(x * 10) / 10;

(async () => {
  const browser = await chromium.launch({ executablePath: CHROME, headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  try {
    for (const [name, url] of URLS) {
      for (const width of SIZES) {
        const mobile = width < 601;
        const page = await browser.newPage({ viewport: { width, height: mobile ? 844 : 900 }, isMobile: mobile, hasTouch: mobile, deviceScaleFactor: 1 });
        try {
          const response = await page.goto(url + '?breadcrumb_geometry=' + Date.now(), { waitUntil: 'domcontentloaded', timeout: 60000 });
          if (!response || response.status() >= 400) throw new Error('HTTP ' + response?.status());
          await page.locator('.emo-product-breadcrumb-20261021').waitFor({ timeout: 20000 });
          await page.waitForTimeout(500);
          const probe = () => page.evaluate(() => {
            const round = x => Math.round(x * 10) / 10;
            const sel = s => document.querySelector(s);
            const nav = sel('.emo-product-breadcrumb-20261021');
            const list = sel('.emo-product-breadcrumb__items');
            const first = sel('.emo-product-breadcrumb__items li:first-child');
            const last = sel('.emo-product-breadcrumb__items li:last-child');
            const prod = sel('.woocommerce div.product') || sel('div.product');
            const gallery = sel('.woocommerce-product-gallery');
            const galleryImg = sel('.woocommerce-product-gallery img') || prod?.querySelector('img');
            const inspect = el => {
              if (!el) return null;
              const r = el.getBoundingClientRect(), s = getComputedStyle(el);
              return { el:el.tagName.toLowerCase(),className:String(el.className||'').slice(0,130),x:r.x,y:r.y,w:r.width,h:r.height,right:r.right,bottom:r.bottom,mt:s.marginTop,mb:s.marginBottom,pt:s.paddingTop,pb:s.paddingBottom,pl:s.paddingLeft,pr:s.paddingRight,background:s.backgroundColor,display:s.display };
            };
            const parents = [];
            let curr = nav;
            for (let i = 0; i < 5 && curr; i++,curr=curr.parentElement) parents.push(inspect(curr));
            const n=nav.getBoundingClientRect(), f=first.getBoundingClientRect(), l=last.getBoundingClientRect(), g=gallery?.getBoundingClientRect(), p=prod?.getBoundingClientRect();
            return { href:location.href,window:innerWidth, scrollWidth:document.documentElement.scrollWidth,nav:inspect(nav),list:inspect(list),first:inspect(first),last:inspect(last),prod:inspect(prod),prodParent:inspect(prod?.parentElement),gallery:inspect(gallery),galleryImg:inspect(galleryImg),next:inspect(nav.nextElementSibling), parent:parents.slice(1,4),
              navTopInside:round(f.top-n.top), navBottomInside:round(n.bottom-l.bottom),
              navToGallery: g ? round(g.top-n.bottom) : null, navToProduct: p?round(p.top-n.bottom):null };
          });
          const before = await probe();
          await page.addStyleTag({ content: candidateCss });
          const after = await probe();
          const problems = [];
          if (Math.abs(after.navTopInside - after.navBottomInside) > 1.1) problems.push('El padding interior no es simétrico');
          if (parseFloat(after.nav.mb) > 0.5) problems.push('Queda margen exterior inferior bajo el breadcrumb');
          if (!after.prod || parseFloat(after.prod.pt) < 21) problems.push('No hay separación interna blanca antes del producto');
          if (!after.prod || after.prod.background !== 'rgb(255, 255, 255)') problems.push('El espacio antes de la galería no es blanco');
          if (after.first.x < 20 || after.last.right > width - 15) problems.push('El texto desborda los márgenes laterales');
          if (after.scrollWidth > width + 2) problems.push('Scroll horizontal tras corrección');
          console.log('BREADCRUMB_CANDIDATE '+name+' '+width+' '+JSON.stringify({
            before:{ nav:before.nav, inside:[before.navTopInside,before.navBottomInside],prod:before.prod, productImg:before.galleryImg },
            after:{ nav:after.nav, inside:[after.navTopInside,after.navBottomInside],prod:after.prod,productImg:after.galleryImg, prodParent:after.prodParent, next:after.next },
            problems
          }));
          if (problems.length) throw Error(name + ' ' + width + ': ' + problems.join('; '));
        } catch (error) {
          console.log('BREADCRUMB_ERROR '+name+' '+width+' '+String(error.message||error).slice(0,420));
          process.exitCode = 1;
        } finally {
          await page.close();
        }
      }
    }
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exit(1); });
