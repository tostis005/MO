#!/usr/bin/env bash
set -Eeuo pipefail

: "${STAGING_HOST:?Missing STAGING_HOST}"
: "${STAGING_USER:?Missing STAGING_USER}"
: "${STAGING_PASSWORD:?Missing STAGING_PASSWORD}"
export SSHPASS="${STAGING_PASSWORD}"
PORT="${STAGING_PORT:-22}"

sudo apt-get update -qq
sudo apt-get install -y --no-install-recommends sshpass >/dev/null
mkdir -p ~/.ssh && chmod 700 ~/.ssh
ssh-keyscan -T 15 -p "$PORT" -H "$STAGING_HOST" > ~/.ssh/known_hosts
chmod 600 ~/.ssh/known_hosts
SSH="-o ServerAliveInterval=20 -o ServerAliveCountMax=20 -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -p $PORT"
SCP="-o ServerAliveInterval=20 -o ServerAliveCountMax=20 -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -P $PORT"

PROD_PATH="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" '
  wp_bin=$(command -v wp || true)
  php_bin=$(command -v php || true)
  [ -n "$php_bin" ] || php_bin=$(find /opt/plesk/php -maxdepth 3 -type f -path "*/bin/php" 2>/dev/null | sort -Vr | head -n1)
  for cfg in $(find /var/www/vhosts "$HOME" -maxdepth 7 -type f -path "*/httpdocs/wp-config.php" 2>/dev/null); do
    p=$(dirname "$cfg")
    url=$("$php_bin" "$wp_bin" option get siteurl --path="$p" --skip-plugins --skip-themes --allow-root 2>/dev/null || true)
    case "$url" in
      https://www.elmercadodeorigen.com|http://www.elmercadodeorigen.com|https://elmercadodeorigen.com|http://elmercadodeorigen.com)
        printf "%s" "$p"
        break
        ;;
    esac
  done
')"
test -n "$PROD_PATH"
THEME="$PROD_PATH/wp-content/themes/elmercadodeorigen-child"
stamp=$(date +%Y%m%d%H%M%S)

sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "set -e; test -d '$THEME'; mkdir -p '$THEME/inc' '$THEME/mu-plugins'; cp '$THEME/inc/product-card-density-final-010162.php' '$THEME/inc/product-card-density-final-010162.php.bak-010246-$stamp' 2>/dev/null || true; cp '$THEME/mu-plugins/elmercado-home-fresh.php' '$THEME/mu-plugins/elmercado-home-fresh.php.bak-010246-$stamp' 2>/dev/null || true"
sshpass -e scp $SCP elmercadodeorigen-child/inc/product-card-density-final-010162.php "$STAGING_USER@$STAGING_HOST:$THEME/inc/product-card-density-final-010162.php"
sshpass -e scp $SCP elmercadodeorigen-child/mu-plugins/elmercado-home-fresh.php "$STAGING_USER@$STAGING_HOST:$THEME/mu-plugins/elmercado-home-fresh.php"

# Some production generations have also loaded this file directly from wp-content/mu-plugins.
if sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "test -f '$PROD_PATH/wp-content/mu-plugins/elmercado-home-fresh.php'"; then
  sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "cp '$PROD_PATH/wp-content/mu-plugins/elmercado-home-fresh.php' '$PROD_PATH/wp-content/mu-plugins/elmercado-home-fresh.php.bak-010246-$stamp'"
  sshpass -e scp $SCP elmercadodeorigen-child/mu-plugins/elmercado-home-fresh.php "$STAGING_USER@$STAGING_HOST:$PROD_PATH/wp-content/mu-plugins/elmercado-home-fresh.php"
fi

sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "
  set -e
  php_bin=\$(command -v php || true)
  [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)
  \"\$php_bin\" -l '$THEME/inc/product-card-density-final-010162.php'
  \"\$php_bin\" -l '$THEME/mu-plugins/elmercado-home-fresh.php'
  wp_bin=\$(command -v wp || true)
  [ -z \"\$wp_bin\" ] || \"\$php_bin\" \"\$wp_bin\" cache flush --path='$PROD_PATH' --allow-root >/dev/null 2>&1 || true
  rm -f '$PROD_PATH/wp-content/uploads/elmercado-home-static/index.html' 2>/dev/null || true
"

echo "PRODUCTION_FILES_DEPLOYED"

mkdir -p /tmp/emo-qa-010246
cd /tmp/emo-qa-010246
npm init -y >/dev/null 2>&1
npm install playwright@1.55.0 >/dev/null 2>&1
npx playwright install chromium --with-deps >/dev/null 2>&1
cat > verify.js <<'NODE'
const { chromium } = require('playwright');
(async()=>{
  const browser=await chromium.launch({headless:true});
  for(const width of [1440,390]){
    const page=await browser.newPage({viewport:{width,height:1400}});
    await page.goto('https://www.elmercadodeorigen.com/tienda/?emoqa=010246-'+Date.now(),{waitUntil:'networkidle',timeout:120000});
    const c=await page.locator('ul.products li.product').first().evaluate(el=>{
      const title=el.querySelector('.product-loop-content > .woocommerce-loop-product__title');
      const price=el.querySelector('.product-loop-meta .price');
      const sold=el.querySelector('.product-loop-wrapper > .wcfmmp_sold_by_container');
      const content=el.querySelector('.product-loop-content');
      const image=el.querySelector('.product-loop-image');
      const s=n=>n?getComputedStyle(n):null, r=n=>n?n.getBoundingClientRect():null;
      const tr=r(title),pr=r(price),sr=r(sold),ir=r(image);
      return {
        titlePriceGap:tr&&pr?+(pr.top-tr.bottom).toFixed(2):null,
        titleSoldGap:tr&&sr?+(sr.top-tr.bottom).toFixed(2):null,
        titleMargin:s(title)?.marginBottom,
        priceMargin:s(price)?.marginTop,
        pricePadding:s(price)?.paddingTop,
        soldMargin:s(sold)?.marginTop,
        soldPadding:s(sold)?.paddingTop,
        contentPaddingBottom:s(content)?.paddingBottom,
        imageWidth:ir?.width,
        imageHeight:ir?.height
      };
    });
    console.log('CARD_QA_'+width+'='+JSON.stringify(c));
    if(!(c.titlePriceGap<=5.5 && c.titleSoldGap<=58 && parseFloat(c.pricePadding||'99')<=1 && parseFloat(c.soldPadding||'99')<=1 && c.imageWidth>150 && c.imageHeight>180)) throw new Error('Card density QA failed '+width);
    await page.close();
  }
  for(const width of [1440,390]){
    const page=await browser.newPage({viewport:{width,height:1400}});
    await page.goto('https://www.elmercadodeorigen.com/?emoqa=010246-'+Date.now(),{waitUntil:'networkidle',timeout:120000});
    const h=await page.evaluate(()=>{
      const q=s=>document.querySelector(s);
      const info=n=>{if(!n)return null;const s=getComputedStyle(n),r=n.getBoundingClientRect();return{top:r.top,bottom:r.bottom,height:r.height,font:parseFloat(s.fontSize),line:parseFloat(s.lineHeight),gap:s.gap,paddingTop:s.paddingTop,paddingBottom:s.paddingBottom}};
      const hero=info(q('.emo-hero')), title=info(q('.emo-hero h1')), p=info(q('.emo-hero__copy > p')), proof=info(q('.emo-hero__proof')), visual=info(q('.emo-hero__visual--vendors'));
      return {hero,title,p,proof,visual,visualOffset:+(visual.top-hero.top).toFixed(2),proofVisualGap:+(visual.top-proof.bottom).toFixed(2)};
    });
    console.log('HERO_QA_'+width+'='+JSON.stringify(h));
    if(width===1440){
      if(!(h.title.font<=80 && h.p.line<=27 && h.hero.height<730 && h.visualOffset<185)) throw new Error('Desktop hero QA failed');
    } else {
      if(!(h.title.font<=43 && h.p.line<=23 && h.hero.height<1120 && h.proofVisualGap<=25)) throw new Error('Mobile hero QA failed');
    }
    await page.close();
  }
  await browser.close();
  console.log('PRODUCTION_CARD_HERO_DENSITY_010246_OK');
})().catch(e=>{console.error(e);process.exit(1)});
NODE
node verify.js
