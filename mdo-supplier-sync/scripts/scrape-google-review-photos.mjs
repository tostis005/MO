/**
 * Optional, read-only discovery of review-attached public Google Maps photographs.
 * Matches photos back to Trustindex review records ONLY when author and review
 * text both agree unambiguously. Does not treat business gallery photos as reviews.
 * Usage: node scrape-google-review-photos.mjs --input FILE --output FILE
 */
import fs from 'node:fs';
import process from 'node:process';
import { chromium } from 'playwright';

function arg(flag, fallback='') {
  const i=process.argv.indexOf('--'+flag);
  return i>=0 && process.argv[i+1] ? process.argv[i+1] : fallback;
}
const input=arg('input');
const output=arg('output');
const audit=arg('audit');
if (!input || !fs.existsSync(input)) throw new Error('--input existing Google review JSON required');
const payload=JSON.parse(fs.readFileSync(input,'utf8'));
if(payload.source!=='google'||!Array.isArray(payload.reviews)) throw new Error('Expected Google review payload');
const norm=v=>String(v||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,' ').replace(/\s+/g,' ').trim();
const scoreText=(a,b)=>{
  const x=norm(a), y=norm(b);
  if (x.length<24||y.length<24) return false;
  if (x===y) return true;
  const shorter=x.length<y.length?x:y;
  const longer=x.length<y.length?y:x;
  return shorter.length>=45 && longer.startsWith(shorter.slice(0,Math.min(100,shorter.length)));
};
const reviewByAuthor=new Map();
for (const review of payload.reviews) {
  const name=norm(review.author_name);
  if (!name) continue;
  if (!reviewByAuthor.has(name)) reviewByAuthor.set(name,[]);
  reviewByAuthor.get(name).push(review);
}
const stats={
  source:'google_maps_public_review_cards',read_only:true,trustindex_total:payload.reviews.length,
  maps_loaded:false,cards_inspected:0,cards_with_customer_photos:0,photos_detected:0,
  uniquely_matched_reviews:0,matched_photo_urls:0,author_missing_from_trustindex:0,
  author_ambiguous:0,review_text_not_confirmed:0,not_assigned_or_guessed:0,google_review_panel_opened:false,
  image_hosts:{},notes:[],maps_reported_count:0,maps_count_verified:false
};
const photosByTrustindexId=new Map();
const discoveredCardIds=new Set();
const chromiumPath=['/usr/bin/google-chrome','/usr/bin/google-chrome-stable','/usr/bin/chromium'].find(fs.existsSync);
const browser=await chromium.launch(chromiumPath?{headless:true,executablePath:chromiumPath,args:['--no-sandbox','--disable-dev-shm-usage']}:{headless:true});
try {
  const context=await browser.newContext({locale:'es-ES',viewport:{width:1440,height:1100},userAgent:'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'});
  const page=await context.newPage();
  page.setDefaultTimeout(6000);
  const url='https://www.google.com/maps/place/?q=place_id:ChIJbbIJi58nQg0RgJroXR8DG_U&hl=es';
  const response=await page.goto(url,{waitUntil:'domcontentloaded',timeout:65000});
  if(response&&response.status()>=400) throw new Error('Google Maps HTTP '+response.status());
  stats.maps_loaded=true;
  await page.waitForTimeout(2400);
  // Extract the public Google Maps count independently of Trustindex. The exact
  // label ("305 reseñas") is distinct from review-keyword chips ("jamón 67").
  const mapsCount = await page.evaluate(() => {
    const labels=Array.from(document.querySelectorAll('button,[role="button"]'))
      .map(el=>(el.getAttribute('aria-label')||el.textContent||'').replace(/\s+/g,' ').trim());
    for (const label of labels) {
      const match=label.match(/^([\d.,\s]+)\s+(?:reseñas|opiniones|reviews?)$/i);
      if(!match)continue;
      const number=Number.parseInt(match[1].replace(/[^0-9]/g,''),10);
      if(Number.isInteger(number)&&number>0&&number<100000) return number;
    }
    return 0;
  });
  stats.maps_reported_count=mapsCount;
  stats.maps_count_verified=mapsCount>0;
  async function collectCards(){
    const cards=await page.evaluate(()=>{
      const result=[];
      for (const card of document.querySelectorAll('.jftiEf[data-review-id],.jftiEf')) {
        const id=card.getAttribute('data-review-id')||'';
        const author=(card.querySelector('.d4r55')?.textContent||'').trim();
        const text=(card.querySelector('.wiI7pd,.MyEned')?.textContent||'').trim();
        const ratingLabel=card.querySelector('.kvMYJc')?.getAttribute('aria-label')||'';
        const rating=Number.parseInt(ratingLabel.match(/[1-5]/)?.[0]||'0',10)||0;
        const photos=[];
        for(const btn of card.querySelectorAll('button.Tya61d,button[aria-label*="Foto"][style]')) {
          const label=btn.getAttribute('aria-label')||'';
          if(!/foto\s*\d+\s+de\s+la\s+reseña/i.test(label)) continue;
          const style=getComputedStyle(btn).backgroundImage||'';
          const uri=style.split('url(')[1]?.split(')')[0]?.replaceAll('"','').replaceAll("'","").trim()||'';
          if(!uri) continue;
          try {
            const image=new URL(uri);
            if(image.protocol!=='https:' || !/(^|\.)googleusercontent\.com$/.test(image.hostname)) continue;
            if(!photos.includes(image.href)) photos.push(image.href);
          }catch{}
        }
        if(id||author) result.push({id,author,text,rating,photos:photos.slice(0,10)});
      }
      return result;
    });
    for(const card of cards){
      const identity=card.id||norm(card.author)+'|'+norm(card.text).slice(0,120);
      if(discoveredCardIds.has(identity))continue;
      discoveredCardIds.add(identity);
      stats.cards_inspected++;
      if(!card.photos.length)continue;
      stats.cards_with_customer_photos++;
      stats.photos_detected+=card.photos.length;
      for(const u of card.photos){
        const host=new URL(u).hostname;
        stats.image_hosts[host]=(stats.image_hosts[host]||0)+1;
      }
      const sameAuthor=reviewByAuthor.get(norm(card.author))||[];
      if(!sameAuthor.length){stats.author_missing_from_trustindex++;continue;}
      const exactText=sameAuthor.filter(r=>scoreText(r.text,card.text)&&(!card.rating||Number(r.rating)===card.rating));
      if(exactText.length!==1) {
        if(sameAuthor.length>1) stats.author_ambiguous++;
        else stats.review_text_not_confirmed++;
        continue;
      }
      const matching=exactText[0];
      const prev=photosByTrustindexId.get(matching.id)||[];
      photosByTrustindexId.set(matching.id,[...new Set([...prev,...card.photos])].slice(0,10));
    }
  }
  await collectCards();
  for(const option of [
    ()=>page.getByRole('button',{name:/\d+\s+reseñas/i}).first(),
    ()=>page.locator('[role="button"]').filter({hasText:/\d+\s+reseñas/i}).first(),
    ()=>page.getByText(/^\d+\s+reseñas$/i).first(),
    ()=>page.getByRole('button',{name:/Todas las reseñas/i}).first(),
  ]){
    try{
      const locator=option();
      if(await locator.count()&&await locator.isVisible()){
        await locator.click({timeout:3500});
        stats.google_review_panel_opened=true;
        await page.waitForTimeout(1600);
        break;
      }
    }catch{}
  }
  for(let index=0;index<14;index++){
    await collectCards();
    const scrollResult=await page.evaluate(()=>{
      const card=document.querySelector('.jftiEf');
      let node=card?.parentElement;
      for(let i=0;node&&i<15;node=node.parentElement,i++){
        if(node.scrollHeight>node.clientHeight+100 && node.clientHeight>200){
          const before=node.scrollTop;
          node.scrollTop=node.scrollHeight;
          return {found:true,moved:node.scrollTop>before,clientHeight:node.clientHeight};
        }
      }
      return {found:false,moved:false};
    });
    if(!scrollResult.found)await page.mouse.wheel(0,1200).catch(()=>{});
    await page.waitForTimeout(550);
    if(index>=5&&stats.cards_inspected<4)break;
  }
  await collectCards();
  await context.close();
}catch(error){
  stats.notes.push('Google Maps browser access limited: '+String(error.message||error).slice(0,130));
}finally{await browser.close();}
stats.uniquely_matched_reviews=photosByTrustindexId.size;
stats.matched_photo_urls=[...photosByTrustindexId.values()].reduce((n,urls)=>n+urls.length,0);
stats.not_assigned_or_guessed=stats.cards_with_customer_photos-stats.uniquely_matched_reviews;
if(output){
  for(const review of payload.reviews){
    const newPhotos=photosByTrustindexId.get(review.id)||[];
    if(!newPhotos.length)continue;
    review.review_images=[...new Set([...(Array.isArray(review.review_images)?review.review_images:[]),...newPhotos])].slice(0,10);
    review.review_images_source='google_maps_public_review_card';
  }
  if (stats.maps_count_verified && stats.maps_reported_count>=Number(payload.reported_count||0)) {
    // Public source total may move ahead of Trustindex's cached review rows.
    payload.maps_reported_count=stats.maps_reported_count;
    payload.maps_count_checked_at=new Date().toISOString();
  }
  fs.writeFileSync(output,JSON.stringify(payload,null,2)+'\n','utf8');
}
if(audit)fs.writeFileSync(audit,JSON.stringify(stats,null,2)+'\n','utf8');
console.log('GOOGLE_MAPS_REVIEW_PHOTO_MATCH_AUDIT '+JSON.stringify(stats));
