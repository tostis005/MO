#!/usr/bin/env bash
set -euo pipefail

php -l .github/scripts/publish-nutrition-priority-26-30-production.php
python3 - <<'PY'
import base64,gzip,json
from pathlib import Path
root=Path('elmercadodeorigen-child/inc/content-seeds')
enc=(root/'nutrition-priority-26-30-010272.b64').read_text().strip()
data=json.loads(gzip.decompress(base64.b64decode(enc,validate=True)).decode('utf-8'))
if len(data)!=5: raise SystemExit('Expected five articles')
for a in data:
    if len(a['content'])<5500 or len(a['en_content'])<4800:
        raise SystemExit('Article too short: '+a['slug'])
    if '<!-- EMDO_RELATED_PRODUCTS -->' not in a['content'] or '<!-- EMDO_RELATED_PRODUCTS -->' not in a['en_content']:
        raise SystemExit('Related placeholder missing: '+a['slug'])
Path('/tmp/nutrition-2630.json').write_text(json.dumps(data,ensure_ascii=False),encoding='utf-8')
print('Editorial payload OK:', ', '.join(a['slug'] for a in data))
PY

sudo apt-get update -qq
sudo apt-get install -y --no-install-recommends sshpass jq >/dev/null
PORT="${STAGING_PORT:-22}"
SSH="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=20 -p $PORT"
SCP="-P $PORT -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=20"
UA='Mozilla/5.0 EMDO safe nutrition publication verification'
mapfile -t TARGET_SLUGS < <(jq -r '.[].slug' /tmp/nutrition-2630.json)
[ "${#TARGET_SLUGS[@]}" -eq 5 ]

CRITICAL_URLS=(
 'https://www.elmercadodeorigen.com/'
 'https://www.elmercadodeorigen.com/tienda/'
 'https://www.elmercadodeorigen.com/blog/'
 'https://www.elmercadodeorigen.com/category/carnes/'
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
REMOTE="/tmp/emdo-nutrition-2630-${GITHUB_RUN_ID}"

# Delete only an exact stale residue from a prior attempt of this batch.
for slug in "${TARGET_SLUGS[@]}"; do
  expected_title="$(jq -r --arg s "$slug" '.[]|select(.slug==$s)|.title' /tmp/nutrition-2630.json)"
  expected_excerpt="$(jq -r --arg s "$slug" '.[]|select(.slug==$s)|.excerpt' /tmp/nutrition-2630.json)"
  ids="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post list --path='$PROD' --allow-root --post_type=post --post_status=any --name='$slug' --format=ids 2>/dev/null || true")"
  for id in $ids; do
    actual_title="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post get '$id' --field=post_title --path='$PROD' --allow-root 2>/dev/null || true")"
    actual_excerpt="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post get '$id' --field=post_excerpt --path='$PROD' --allow-root 2>/dev/null || true")"
    [ "$actual_title" = "$expected_title" ] && [ "$actual_excerpt" = "$expected_excerpt" ] || {
      echo "Safety stop: existing slug is not this batch: $slug id=$id title=$actual_title" >&2; exit 10;
    }
    echo "cleanup_stale_batch_post slug=$slug id=$id"
    sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; '$PHP' '$WP' post delete '$id' --force --path='$PROD' --allow-root >/dev/null"
  done
done

sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "mkdir -p '$REMOTE/content-seeds'"
sshpass -e scp $SCP .github/scripts/publish-nutrition-priority-26-30-production.php "$STAGING_USER@$STAGING_HOST:$REMOTE/"
sshpass -e scp $SCP elmercadodeorigen-child/inc/content-seeds/nutrition-priority-26-30-010272.b64 "$STAGING_USER@$STAGING_HOST:$REMOTE/content-seeds/"

ROLLBACK_ACTIVE=1
rollback_only_new_posts(){
  rc=$?; set +e
  if [ "${ROLLBACK_ACTIVE:-0}" = 1 ]; then
    echo 'Verification failed: rolling back only batch 26-30 posts.' >&2
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
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export EMDO_NUTRITION_2630_SEED_DIR='$REMOTE'; export PATH='$(dirname "$PHP")':\$PATH; '$PHP' -d memory_limit=768M '$WP' eval-file '$REMOTE/publish-nutrition-priority-26-30-production.php' --path='$PROD' --allow-root" >/tmp/nutrition-2630.raw 2>/tmp/nutrition-2630.err
PUBLISH_RC=$?; set -e; trap rollback_only_new_posts ERR
[ ! -s /tmp/nutrition-2630.err ] || cat /tmp/nutrition-2630.err >&2
[ ! -s /tmp/nutrition-2630.raw ] || cat /tmp/nutrition-2630.raw
[ "$PUBLISH_RC" -eq 0 ]
JSON_LINE="$(grep -E '^\{.*\}$' /tmp/nutrition-2630.raw|tail -n1 || true)"; [ -n "$JSON_LINE" ]
printf '%s\n' "$JSON_LINE" >/tmp/nutrition-2630-result.json
jq -e '.verified==true and .count==5 and (.errors|length)==0 and (.posts|length)==5' /tmp/nutrition-2630-result.json >/dev/null

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
done < <(jq -r '.posts[]|[.slug,.en_slug,.title,.en_title,.permalink,.en_permalink,.category_slug]|@tsv' /tmp/nutrition-2630-result.json)

curl -L -sS --retry 3 -A "$UA" "https://www.elmercadodeorigen.com/blog/?emdo_verify=${GITHUB_RUN_ID}" -o /tmp/blog.html
while read -r slug; do grep -Fq "$slug" /tmp/blog.html; done < <(jq -r '.posts[].slug' /tmp/nutrition-2630-result.json)
health_check post "${CRITICAL_URLS[@]}"
ROLLBACK_ACTIVE=0; trap - ERR
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "rm -rf '$REMOTE'"
echo 'SAFE_PUBLICATION_OK: batch 26-30 published and verified.'
