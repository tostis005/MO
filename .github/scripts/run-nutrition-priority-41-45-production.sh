#!/usr/bin/env bash
set -euo pipefail

php -l .github/scripts/publish-nutrition-priority-41-45-production.php
python3 - <<'PY'
import base64,gzip,json,hashlib
from pathlib import Path
parts=sorted(Path('elmercadodeorigen-child/inc/content-seeds').glob('nutrition-priority-41-45-010274-v1.part*'))
if len(parts)!=3: raise SystemExit('Expected three payload parts')
enc=''.join(p.read_text().strip() for p in parts)
expected_sha='2dfecb9ff19c2e939afb96d8a931f1f9a7b02c7f0c1334b10104689d66749e0a'
if hashlib.sha256(enc.encode()).hexdigest()!=expected_sha: raise SystemExit('Payload integrity check failed')
data=json.loads(gzip.decompress(base64.b64decode(enc,validate=True)).decode('utf-8'))
if len(data)!=5: raise SystemExit('Expected five articles')
expected=['aceites']*5
expected_products=[['aceites']]*5
for i,a in enumerate(data):
    if len(a['content'])<4500 or len(a['en_content'])<4200:
        raise SystemExit('Article too short: '+a['slug'])
    if '<!-- EMDO_RELATED_PRODUCTS -->' not in a['content'] or '<!-- EMDO_RELATED_PRODUCTS -->' not in a['en_content']:
        raise SystemExit('Related placeholder missing: '+a['slug'])
    if a['category_slug']!=expected[i]:
        raise SystemExit('Unexpected category/order: '+a['slug']+' -> '+a['category_slug'])
    if a['product_cat_slugs']!=expected_products[i]:
        raise SystemExit('Unexpected product categories: '+a['slug'])
Path('/tmp/nutrition-4145.json').write_text(json.dumps(data,ensure_ascii=False),encoding='utf-8')
print('Editorial payload OK:', ', '.join(a['slug'] for a in data))
PY

sudo apt-get update -qq
sudo apt-get install -y --no-install-recommends sshpass jq >/dev/null
PORT="${STAGING_PORT:-22}"
SSH="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=20 -p $PORT"
SCP="-P $PORT -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=20"
UA='Mozilla/5.0 EMDO safe nutrition publication verification'
mapfile -t TARGET_SLUGS < <(jq -r '.[].slug' /tmp/nutrition-4145.json)
[ "${#TARGET_SLUGS[@]}" -eq 5 ]

CRITICAL_URLS=(
 'https://www.elmercadodeorigen.com/'
 'https://www.elmercadodeorigen.com/tienda/'
 'https://www.elmercadodeorigen.com/blog/'
 'https://www.elmercadodeorigen.com/category/aceites/'
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
REMOTE="/tmp/emdo-nutrition-4145-aove-${GITHUB_RUN_ID}"

for slug in "${TARGET_SLUGS[@]}"; do
  ids="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post list --path='$PROD' --allow-root --post_type=post --post_status=any --name='$slug' --format=ids 2>/dev/null || true")"
  [ -z "${ids// /}" ] || { echo "Safety stop: target slug already exists: $slug ids=$ids" >&2; exit 10; }
done

sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "mkdir -p '$REMOTE/content-seeds'"
sshpass -e scp $SCP .github/scripts/publish-nutrition-priority-41-45-production.php "$STAGING_USER@$STAGING_HOST:$REMOTE/"
sshpass -e scp $SCP elmercadodeorigen-child/inc/content-seeds/nutrition-priority-41-45-010274-v1.part* "$STAGING_USER@$STAGING_HOST:$REMOTE/content-seeds/"

ROLLBACK_ACTIVE=1
rollback_only_new_posts(){
  rc=$?; set +e
  if [ "${ROLLBACK_ACTIVE:-0}" = 1 ]; then
    echo 'Verification failed: rolling back only new batch 41-45 posts.' >&2
    for slug in "${TARGET_SLUGS[@]}"; do
      ids="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post list --path='$PROD' --allow-root --post_type=post --post_status=any --name='$slug' --format=ids 2>/dev/null || true")"
      [ -z "${ids// /}" ] || sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post delete $ids --force --path='$PROD' --allow-root >/dev/null 2>&1 || true"
    done
    sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' rewrite flush --path='$PROD' --allow-root >/dev/null 2>&1 || true; '$PHP' '$WP' cache flush --path='$PROD' --allow-root >/dev/null 2>&1 || true; '$PHP' '$WP' rocket clean --confirm --path='$PROD' --allow-root >/dev/null 2>&1 || true; rm -rf '$REMOTE'" >/dev/null 2>&1
    health_check rollback "${CRITICAL_URLS[@]}" || true
  fi
  exit "$rc"
}
trap rollback_only_new_posts ERR

trap - ERR; set +e
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export EMDO_NUTRITION_4145_SEED_DIR='$REMOTE'; export PATH='$(dirname "$PHP")':\$PATH; '$PHP' -d memory_limit=768M '$WP' eval-file '$REMOTE/publish-nutrition-priority-41-45-production.php' --path='$PROD' --allow-root" >/tmp/nutrition-4145.raw 2>/tmp/nutrition-4145.err
PUBLISH_RC=$?; set -e; trap rollback_only_new_posts ERR
[ ! -s /tmp/nutrition-4145.err ] || cat /tmp/nutrition-4145.err >&2
[ ! -s /tmp/nutrition-4145.raw ] || cat /tmp/nutrition-4145.raw
[ "$PUBLISH_RC" -eq 0 ]
JSON_LINE="$(grep -E '^\{.*\}$' /tmp/nutrition-4145.raw|tail -n1 || true)"; [ -n "$JSON_LINE" ]
printf '%s\n' "$JSON_LINE" >/tmp/nutrition-4145-result.json
jq -e '.verified==true and .count==5 and (.errors|length)==0 and (.posts|length)==5' /tmp/nutrition-4145-result.json >/dev/null

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
done < <(jq -r '.posts[]|[.slug,.en_slug,.title,.en_title,.permalink,.en_permalink,.category_slug]|@tsv' /tmp/nutrition-4145-result.json)

curl -L -sS --retry 3 -A "$UA" "https://www.elmercadodeorigen.com/blog/?emdo_verify=${GITHUB_RUN_ID}" -o /tmp/blog.html
while read -r slug; do grep -Fq "$slug" /tmp/blog.html; done < <(jq -r '.posts[].slug' /tmp/nutrition-4145-result.json)
health_check post "${CRITICAL_URLS[@]}"
ROLLBACK_ACTIVE=0; trap - ERR
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "rm -rf '$REMOTE'"
echo 'SAFE_PUBLICATION_OK: batch 41-45 nutrition articles published and verified.'
