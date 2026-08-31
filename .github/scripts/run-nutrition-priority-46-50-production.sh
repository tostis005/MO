#!/usr/bin/env bash
set -euo pipefail

php -l .github/scripts/publish-nutrition-priority-46-50-production.php
python3 - <<'PY'
import base64,gzip,json,hashlib
from pathlib import Path
parts=sorted(Path('elmercadodeorigen-child/inc/content-seeds').glob('nutrition-priority-46-50-010275-v1.part*'))
if len(parts)!=3: raise SystemExit('Expected three payload parts')
enc=''.join(p.read_text().strip() for p in parts)
expected_sha='73b0fa8a76092a2dc01827705dd623f626472686dc99286477fb4866c08f396a'
if hashlib.sha256(enc.encode()).hexdigest()!=expected_sha: raise SystemExit('Payload integrity check failed')
data=json.loads(gzip.decompress(base64.b64decode(enc,validate=True)).decode('utf-8'))
if len(data)!=5: raise SystemExit('Expected five articles')
expected_slugs=[
 'nutrientes-lomo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
 'jamon-iberico-vs-lomo-iberico-diferencias-nutricionales',
 'cuanta-proteina-tiene-lomo-iberico',
 'cuanto-hierro-tiene-lomo-iberico',
 'grasa-lomo-iberico-saturada-monoinsaturada-poliinsaturada',
]
expected_products=[['embutidos'],['embutidos','jamones-paletas'],['embutidos'],['embutidos'],['embutidos']]
for i,a in enumerate(data):
    if a['slug']!=expected_slugs[i]: raise SystemExit('Unexpected slug/order: '+a['slug'])
    if len(a['content'])<4500 or len(a['en_content'])<4200: raise SystemExit('Article too short: '+a['slug'])
    if '<!-- EMDO_RELATED_PRODUCTS -->' not in a['content'] or '<!-- EMDO_RELATED_PRODUCTS -->' not in a['en_content']: raise SystemExit('Related placeholder missing: '+a['slug'])
    if a['category_slug']!='embutidos-y-curados': raise SystemExit('Unexpected category: '+a['slug']+' -> '+a['category_slug'])
    if a['product_cat_slugs']!=expected_products[i]: raise SystemExit('Unexpected product categories: '+a['slug'])
Path('/tmp/nutrition-4650.json').write_text(json.dumps(data,ensure_ascii=False),encoding='utf-8')
print('Editorial payload OK:', ', '.join(a['slug'] for a in data))
PY

sudo apt-get update -qq
sudo apt-get install -y --no-install-recommends sshpass jq >/dev/null
PORT="${STAGING_PORT:-22}"
SSH="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=20 -p $PORT"
SCP="-P $PORT -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=20"
UA='Mozilla/5.0 EMDO safe nutrition publication verification'
BATCH_TOKEN="nutrition-4650-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT:-1}"

CRITICAL_URLS=(
 'https://www.elmercadodeorigen.com/'
 'https://www.elmercadodeorigen.com/tienda/'
 'https://www.elmercadodeorigen.com/blog/'
 'https://www.elmercadodeorigen.com/category/embutidos-y-curados/'
)
health_check(){
  local phase="$1"; shift
  for url in "$@"; do
    local file code bytes
    file="/tmp/health-${phase}-$(echo "$url"|tr '/:?&=' '_').html"
    code="$(curl -L -sS --retry 3 --retry-delay 2 -A "$UA" -o "$file" -w '%{http_code}' "${url}?emdo_health=${GITHUB_RUN_ID}-${phase}")"
    [ "$code" = 200 ] || { echo "Health ${phase}: HTTP ${code} ${url}" >&2; return 1; }
    bytes="$(wc -c < "$file")"; [ "$bytes" -gt 5000 ] || return 1
    grep -qi '</html>' "$file" || return 1
    echo "health_ok phase=${phase} http=${code} bytes=${bytes} url=${url}"
  done
}
health_check pre "${CRITICAL_URLS[@]}"

INFO="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" '
  wp=$(command -v wp || true); php=$(command -v php || true)
  [ -n "$php" ] || php=$(find /opt/plesk/php -maxdepth 3 -type f -path "*/bin/php" 2>/dev/null|sort -Vr|head -n1)
  [ -n "$wp" ] || exit 2; [ -n "$php" ] || exit 3
  prod=""
  for cfg in $(find /var/www/vhosts "$HOME" -maxdepth 7 -type f -path "*/httpdocs/wp-config.php" 2>/dev/null); do
    p=$(dirname "$cfg"); u=$("$php" "$wp" option get siteurl --path="$p" --skip-plugins --skip-themes --allow-root 2>/dev/null || true)
    case "$u" in https://www.elmercadodeorigen.com|https://elmercadodeorigen.com|http://www.elmercadodeorigen.com|http://elmercadodeorigen.com) prod="$p"; break;; esac
  done
  [ -n "$prod" ] || exit 4
  printf "%s|%s|%s" "$prod" "$php" "$wp"
')"
PROD="${INFO%%|*}"; REST="${INFO#*|}"; PHP="${REST%%|*}"; WP="${REST#*|}"
REMOTE="/tmp/emdo-nutrition-4650-lomo-${GITHUB_RUN_ID}"
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "mkdir -p '$REMOTE/content-seeds'"
sshpass -e scp $SCP .github/scripts/publish-nutrition-priority-46-50-production.php "$STAGING_USER@$STAGING_HOST:$REMOTE/"
sshpass -e scp $SCP elmercadodeorigen-child/inc/content-seeds/nutrition-priority-46-50-010275-v1.part* "$STAGING_USER@$STAGING_HOST:$REMOTE/content-seeds/"

ROLLBACK_ACTIVE=1
rollback_only_this_run(){
  rc=$?; set +e
  if [ "${ROLLBACK_ACTIVE:-0}" = 1 ]; then
    echo "Verification failed: rolling back only posts created by token ${BATCH_TOKEN}." >&2
    ids="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post list --path='$PROD' --allow-root --post_type=post --post_status=any --meta_key='_emdo_batch_token' --meta_value='$BATCH_TOKEN' --format=ids 2>/dev/null || true")"
    if [ -n "${ids// /}" ]; then
      sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post delete $ids --force --path='$PROD' --allow-root >/dev/null 2>&1 || true"
    fi
    sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' rewrite flush --path='$PROD' --allow-root >/dev/null 2>&1 || true; '$PHP' '$WP' cache flush --path='$PROD' --allow-root >/dev/null 2>&1 || true; '$PHP' '$WP' rocket clean --confirm --path='$PROD' --allow-root >/dev/null 2>&1 || true; rm -rf '$REMOTE'" >/dev/null 2>&1
    health_check rollback "${CRITICAL_URLS[@]}" || true
  fi
  exit "$rc"
}
trap rollback_only_this_run ERR

trap - ERR; set +e
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export EMDO_NUTRITION_4650_SEED_DIR='$REMOTE'; export EMDO_BATCH_TOKEN='$BATCH_TOKEN'; export PATH='$(dirname "$PHP")':\$PATH; '$PHP' -d memory_limit=768M '$WP' eval-file '$REMOTE/publish-nutrition-priority-46-50-production.php' --path='$PROD' --allow-root" >/tmp/nutrition-4650.raw 2>/tmp/nutrition-4650.err
PUBLISH_RC=$?; set -e; trap rollback_only_this_run ERR
[ ! -s /tmp/nutrition-4650.err ] || cat /tmp/nutrition-4650.err >&2
[ ! -s /tmp/nutrition-4650.raw ] || cat /tmp/nutrition-4650.raw
[ "$PUBLISH_RC" -eq 0 ]
JSON_LINE="$(grep -E '^\{.*\}$' /tmp/nutrition-4650.raw|tail -n1 || true)"; [ -n "$JSON_LINE" ]
printf '%s\n' "$JSON_LINE" >/tmp/nutrition-4650-result.json
jq -e '.verified==true and .count==5 and (.errors|length)==0 and (.posts|length)==5' /tmp/nutrition-4650-result.json >/dev/null

sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' cache flush --path='$PROD' --allow-root >/dev/null 2>&1 || true; '$PHP' '$WP' rocket clean --confirm --path='$PROD' --allow-root >/dev/null 2>&1 || true"
while IFS=$'\t' read -r slug en_slug title en_title permalink en_permalink category_slug; do
  es_file="/tmp/post-${slug}.html"; en_file="/tmp/post-${en_slug}.html"
  es_code="$(curl -L -sS --retry 3 --retry-delay 2 -A "$UA" -o "$es_file" -w '%{http_code}' "${permalink}?emdo_verify=${GITHUB_RUN_ID}")"
  [ "$es_code" = 200 ]; [ "$(wc -c < "$es_file")" -gt 5000 ]; grep -qi '</html>' "$es_file"; grep -Fq "$title" "$es_file"
  en_code="$(curl -L -sS --retry 3 --retry-delay 2 -A "$UA" -o "$en_file" -w '%{http_code}' "${en_permalink}?emdo_verify=${GITHUB_RUN_ID}")"
  [ "$en_code" = 200 ]; [ "$(wc -c < "$en_file")" -gt 5000 ]; grep -qi '</html>' "$en_file"; grep -Fq "$en_title" "$en_file"
  cat_file="/tmp/category-${category_slug}-${slug}.html"
  curl -L -sS --retry 3 -A "$UA" "https://www.elmercadodeorigen.com/category/${category_slug}/?emdo_verify=${GITHUB_RUN_ID}" -o "$cat_file"
  grep -Fq "$slug" "$cat_file"
  echo "post_ok es=${permalink} en=${en_permalink} category=${category_slug}"
done < <(jq -r '.posts[]|[.slug,.en_slug,.title,.en_title,.permalink,.en_permalink,.category_slug]|@tsv' /tmp/nutrition-4650-result.json)

curl -L -sS --retry 3 -A "$UA" "https://www.elmercadodeorigen.com/blog/?emdo_verify=${GITHUB_RUN_ID}" -o /tmp/blog-4650.html
while read -r slug; do grep -Fq "$slug" /tmp/blog-4650.html; done < <(jq -r '.posts[].slug' /tmp/nutrition-4650-result.json)
health_check post "${CRITICAL_URLS[@]}"
ROLLBACK_ACTIVE=0; trap - ERR
while read -r id; do
  [ -n "$id" ] || continue
  sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post meta delete '$id' '_emdo_batch_token' --path='$PROD' --allow-root >/dev/null 2>&1 || true"
done < <(jq -r '.posts[]|select(.adopted==false)|.id' /tmp/nutrition-4650-result.json)
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "rm -rf '$REMOTE'"
echo 'SAFE_PUBLICATION_OK: batch 46-50 nutrition articles published and verified.'
