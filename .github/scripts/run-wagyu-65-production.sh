#!/usr/bin/env bash
set -Eeuo pipefail

php -l .github/scripts/publish-wagyu-65-production.php >/dev/null
mapfile -t PAYLOADS < <(find .github/data -maxdepth 2 -type f -path '*/editorial-wagyu-batch*-20260910/*.php' | sort -V)
[ "${#PAYLOADS[@]}" -eq 65 ] || { echo "Expected 65 Wagyu payloads, found ${#PAYLOADS[@]}" >&2; exit 2; }
for file in "${PAYLOADS[@]}"; do php -l "$file" >/dev/null; done

php <<'PHP' > /tmp/wagyu-slugs.tsv
<?php
$files = glob('.github/data/editorial-wagyu-batch*-20260910/*.php') ?: array();
natsort($files);
$seenEs = $seenEn = array();
foreach ($files as $file) {
    $a = include $file;
    foreach (array('slug','en_slug','title','en_title','content','en_content','seo_title','en_seo_title','meta_description','en_meta_description','focus_keyword','en_focus_keyword') as $k) {
        if (!isset($a[$k]) || (is_string($a[$k]) && trim($a[$k]) === '')) { fwrite(STDERR, "Missing $k in $file\n"); exit(3); }
    }
    if (isset($seenEs[$a['slug']]) || isset($seenEn[$a['en_slug']])) { fwrite(STDERR, "Duplicate slug in $file\n"); exit(4); }
    $seenEs[$a['slug']] = true; $seenEn[$a['en_slug']] = true;
    echo $a['slug'], "\t", $a['en_slug'], "\n";
}
if (count($files) !== 65) { fwrite(STDERR, "Expected 65 files\n"); exit(5); }
PHP
[ "$(wc -l < /tmp/wagyu-slugs.tsv)" -eq 65 ]

: "${STAGING_HOST:?}"; : "${STAGING_USER:?}"; : "${STAGING_PASSWORD:?}"
export SSHPASS="${STAGING_PASSWORD}"
sudo apt-get update -qq
sudo apt-get install -y --no-install-recommends sshpass jq >/dev/null
PORT="${STAGING_PORT:-22}"
SSH="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=20 -p $PORT"
SCP="-P $PORT -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=20"
UA='Mozilla/5.0 EMDO safe Wagyu publication verification'
BASE='https://www.elmercadodeorigen.com'
CRITICAL_URLS=("$BASE/" "$BASE/tienda/" "$BASE/blog/")

health_check(){
  local phase="$1"; shift
  for url in "$@"; do
    local file code bytes
    file="/tmp/wagyu-health-${phase}-$(echo "$url" | tr '/:?&=' '_').html"
    code="$(curl -L -sS --retry 3 --retry-delay 2 -A "$UA" -o "$file" -w '%{http_code}' "${url}?emdo_wagyu_health=${GITHUB_RUN_ID}-${phase}" || true)"
    [ "$code" = 200 ] || { echo "Health ${phase}: HTTP ${code} ${url}" >&2; return 1; }
    bytes="$(wc -c < "$file")"; [ "$bytes" -gt 5000 ] || { echo "Health ${phase}: small response ${url}" >&2; return 1; }
    grep -qi '</html>' "$file" || { echo "Health ${phase}: incomplete HTML ${url}" >&2; return 1; }
    echo "health_ok phase=${phase} http=${code} bytes=${bytes} url=${url}"
  done
}
health_check pre "${CRITICAL_URLS[@]}"

INFO="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" '
  wp=$(command -v wp || true); php=$(command -v php || true)
  [ -n "$php" ] || php=$(find /opt/plesk/php -maxdepth 3 -type f -path "*/bin/php" 2>/dev/null | sort -Vr | head -n1)
  [ -n "$wp" ] || exit 2; [ -n "$php" ] || exit 3
  prod=""
  for cfg in $(find /var/www/vhosts "$HOME" -maxdepth 7 -type f -path "*/httpdocs/wp-config.php" 2>/dev/null); do
    p=$(dirname "$cfg"); u=$("$php" "$wp" option get siteurl --path="$p" --skip-plugins --skip-themes --allow-root 2>/dev/null || true)
    case "$u" in https://www.elmercadodeorigen.com|https://elmercadodeorigen.com|http://www.elmercadodeorigen.com|http://elmercadodeorigen.com) prod="$p"; break;; esac
  done
  [ -n "$prod" ] || exit 4
  printf "%s|%s|%s" "$prod" "$php" "$wp"
')"
PROD="${INFO%%|*}"; REST="${INFO#*|}"; PHPBIN="${REST%%|*}"; WPBIN="${REST#*|}"
echo "production_path_resolved"

# First-run safety: target slugs must not already exist. This prevents the outer
# rollback from ever deleting a pre-existing editorial post.
while IFS=$'\t' read -r es_slug en_slug; do
  ids="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' '$WPBIN' post list --path='$PROD' --allow-root --post_type=post --name='$es_slug' --format=ids 2>/dev/null || true")"
  [ -z "${ids// /}" ] || { echo "Safety stop: target ES slug already exists: $es_slug ($ids)" >&2; exit 10; }
done < /tmp/wagyu-slugs.tsv

# Also ensure the related-products fallback exists before any post is created.
CARNES_TERM="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' '$WPBIN' term get product_cat carnes --by=slug --field=term_id --path='$PROD' --allow-root 2>/dev/null || true")"
[ -n "${CARNES_TERM// /}" ] || { echo 'Safety stop: WooCommerce product category carnes not found.' >&2; exit 11; }

REMOTE="/tmp/emdo-wagyu-65-${GITHUB_RUN_ID}"
ARCHIVE="/tmp/wagyu-65-${GITHUB_RUN_ID}.tgz"
tar -czf "$ARCHIVE" \
  .github/scripts/publish-wagyu-65-production.php \
  .github/data/editorial-wagyu-batch01-20260910 \
  .github/data/editorial-wagyu-batch02-20260910 \
  .github/data/editorial-wagyu-batch03-20260910 \
  .github/data/editorial-wagyu-batch04-20260910 \
  .github/data/editorial-wagyu-batch05-20260910 \
  .github/data/editorial-wagyu-batch06-20260910 \
  .github/data/editorial-wagyu-batch07-20260910
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "rm -rf '$REMOTE' && mkdir -p '$REMOTE'"
sshpass -e scp $SCP "$ARCHIVE" "$STAGING_USER@$STAGING_HOST:$REMOTE/payload.tgz"
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "tar -xzf '$REMOTE/payload.tgz' -C '$REMOTE'"

ROLLBACK_ACTIVE=1
rollback_only_wagyu_posts(){
  rc=$?; set +e
  if [ "${ROLLBACK_ACTIVE:-0}" = 1 ]; then
    echo 'Wagyu verification failed: rolling back only the 65 target post slugs.' >&2
    while IFS=$'\t' read -r es_slug en_slug; do
      ids="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' '$WPBIN' post list --path='$PROD' --allow-root --post_type=post --name='$es_slug' --format=ids 2>/dev/null || true")"
      [ -z "${ids// /}" ] || sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' '$WPBIN' post delete $ids --force --path='$PROD' --allow-root >/dev/null 2>&1 || true"
    done < /tmp/wagyu-slugs.tsv
    sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' '$WPBIN' rewrite flush --path='$PROD' --allow-root >/dev/null 2>&1 || true; '$PHPBIN' '$WPBIN' cache flush --path='$PROD' --allow-root >/dev/null 2>&1 || true; '$PHPBIN' '$WPBIN' rocket clean --confirm --path='$PROD' --allow-root >/dev/null 2>&1 || true; rm -rf '$REMOTE'" >/dev/null 2>&1 || true
    health_check rollback "${CRITICAL_URLS[@]}" || true
  fi
  exit "$rc"
}
trap rollback_only_wagyu_posts ERR

trap - ERR; set +e
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export EMDO_WAGYU_SEED_DIR='$REMOTE'; export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' -d memory_limit=768M '$WPBIN' eval-file '$REMOTE/.github/scripts/publish-wagyu-65-production.php' --path='$PROD' --allow-root" >/tmp/wagyu-65.raw 2>/tmp/wagyu-65.err
PUBLISH_RC=$?; set -e; trap rollback_only_wagyu_posts ERR
[ ! -s /tmp/wagyu-65.err ] || cat /tmp/wagyu-65.err >&2
[ ! -s /tmp/wagyu-65.raw ] || cat /tmp/wagyu-65.raw
[ "$PUBLISH_RC" -eq 0 ]
grep -q 'EMDO_WAGYU_65_BEGIN' /tmp/wagyu-65.raw
grep -q 'EMDO_WAGYU_65_END' /tmp/wagyu-65.raw
JSON_LINE="$(grep -E '^\{.*\}$' /tmp/wagyu-65.raw | tail -n1 || true)"; [ -n "$JSON_LINE" ]
printf '%s\n' "$JSON_LINE" >/tmp/wagyu-65-result.json
jq -e '.verified==true and .count==65 and .blog_category_slug=="wagyu" and .shop_wagyu_category_slug=="wagyu" and .related_product_category=="carnes" and (.posts|length)==65' /tmp/wagyu-65-result.json >/dev/null

# Runtime verification from WordPress before public HTTP checks.
POST_COUNT="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' '$WPBIN' post list --path='$PROD' --allow-root --post_type=post --post_status=publish --meta_key=_emdo_editorial_wagyu_65 --meta_value='2026-09-10.wagyu-65.v1' --format=count 2>/dev/null")"
[ "$POST_COUNT" = 65 ] || { echo "Expected 65 marked published posts, got $POST_COUNT" >&2; false; }
BLOG_TERM="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' '$WPBIN' term get category wagyu --by=slug --field=term_id --path='$PROD' --allow-root 2>/dev/null")"
SHOP_TERM="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' '$WPBIN' term get product_cat wagyu --by=slug --field=term_id --path='$PROD' --allow-root 2>/dev/null")"
[ -n "${BLOG_TERM// /}" ] && [ -n "${SHOP_TERM// /}" ]

sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHPBIN")':\$PATH; '$PHPBIN' '$WPBIN' rewrite flush --path='$PROD' --allow-root >/dev/null 2>&1 || true; '$PHPBIN' '$WPBIN' cache flush --path='$PROD' --allow-root >/dev/null 2>&1 || true; '$PHPBIN' '$WPBIN' rocket clean --confirm --path='$PROD' --allow-root >/dev/null 2>&1 || true"

check_url(){
  local url="$1" code='' file='/tmp/wagyu-page.html'
  for attempt in 1 2 3 4; do
    code="$(curl -L -sS -A "$UA" -o "$file" -w '%{http_code}' --connect-timeout 15 --max-time 45 "${url}?emdo_wagyu=${GITHUB_RUN_ID}-${attempt}" || true)"
    if [ "$code" = 200 ] && [ "$(wc -c < "$file")" -gt 5000 ] && grep -qi '</html>' "$file"; then
      echo "public_ok $url"
      return 0
    fi
    sleep 4
  done
  echo "Public verification failed: HTTP $code $url" >&2
  return 1
}

while IFS=$'\t' read -r es_slug en_slug; do
  check_url "$BASE/${es_slug}/"
  check_url "$BASE/en/${en_slug}/"
done < /tmp/wagyu-slugs.tsv
check_url "$BASE/category/wagyu/"
health_check post "${CRITICAL_URLS[@]}"

ROLLBACK_ACTIVE=0
trap - ERR
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "rm -rf '$REMOTE'"
echo "SAFE_WAGYU_PUBLICATION_OK: 65 ES + 65 EN posts published; blog category Wagyu and WooCommerce product category Wagyu exist; related products use Carnes."
