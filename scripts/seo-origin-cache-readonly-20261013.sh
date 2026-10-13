#!/usr/bin/env bash
# SEO origin/cache diagnostics. No WordPress, theme, plugin or cache writes.
set -euo pipefail
: "${STAGING_HOST:?missing SSH host}"
: "${STAGING_USER:?missing SSH user}"
: "${STAGING_PASSWORD:?missing SSH password}"
: "${STAGING_REMOTE_PATH:?missing SSH reference path}"
[[ "${STAGING_PORT:-22}" =~ ^[0-9]{1,5}$ ]]
[[ "$STAGING_REMOTE_PATH" =~ ^/[A-Za-z0-9._/-]+/?$ ]]
[[ "${GITHUB_RUN_ID:-}" =~ ^[0-9]+$ ]]
sudo apt-get update -qq
sudo apt-get install -y --no-install-recommends sshpass >/dev/null
port="${STAGING_PORT:-22}"
mkdir -p ~/.ssh && chmod 700 ~/.ssh
ssh-keyscan -T 15 -p "$port" -H "$STAGING_HOST" > ~/.ssh/known_hosts
test -s ~/.ssh/known_hosts
chmod 600 ~/.ssh/known_hosts
export SSHPASS="$STAGING_PASSWORD"
sshpass -e ssh -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -p "$port" "$STAGING_USER@$STAGING_HOST" "sh -s -- '$STAGING_REMOTE_PATH' '$GITHUB_RUN_ID'" <<'REMOTE'
set -eu
raw="${1%/}"
rid="$2"
dev="$raw"
case "$raw" in
  */wp-content/*) dev="${raw%%/wp-content/*}" ;;
  */wp-content) dev="$(dirname "$raw")" ;;
esac
if [ ! -f "$dev/wp-config.php" ]; then
  config="$(find "$HOME" /var/www /home -maxdepth 9 -type f -path '*/dev.elmercadodeorigen.com/wp-config.php' 2>/dev/null | head -n 1 || true)"
  [ -n "$config" ] || { echo 'dev_root_found=no'; exit 1; }
  dev="$(dirname "$config")"
fi
prod="$(dirname "$dev")/httpdocs"
[ -f "$prod/wp-config.php" ] || { echo 'prod_root_found=no'; exit 1; }
php_bin="$(command -v php || true)"
[ -n "$php_bin" ] || php_bin="$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n 1)"
wp_bin="$(command -v wp || true)"
[ -n "$wp_bin" ] && [ -n "$php_bin" ] || { echo 'wpcli_available=no'; exit 1; }
siteurl="$("$php_bin" -d memory_limit=512M "$wp_bin" option get siteurl --path="$prod" --skip-plugins --skip-themes --allow-root 2>/dev/null || true)"
case "$siteurl" in
  https://www.elmercadodeorigen.com|https://elmercadodeorigen.com|http://www.elmercadodeorigen.com|http://elmercadodeorigen.com) ;;
  *) echo 'prod_verified=no'; exit 1 ;;
esac
echo 'prod_verified=yes'
theme="$prod/wp-content/themes/elmercadodeorigen-child"
if [ -f "$theme/inc/seo-gsc-batch-20-20261012.php" ]; then
  echo 'batch1_module_present=yes'
else
  echo 'batch1_module_present=no'
fi
dropin="$prod/wp-content/advanced-cache.php"
if [ -f "$dropin" ]; then
  echo 'advanced_cache_present=yes'
else
  echo 'advanced_cache_present=no'
fi
if [ -f "$dropin" ]; then
  for pair in 'custom:elmercado|emo[-_]|blog-static' 'uri:REQUEST_URI|SCRIPT_NAME' 'query:QUERY_STRING|_GET|parse_str' 'exit:(exit|die)[[:space:]]*[;(]' 'english:/en/|lang[_-]?en|english'; do
    flag="${pair%%:*}"; regex="${pair#*:}"
    if grep -Eqi "$regex" "$dropin"; then state=yes; else state=no; fi
    echo "advanced_cache_$flag=$state"
  done
fi
# Inspect only bounded counts and indicators. Never print cache filenames or PHP source.
cache_dir="$prod/wp-content/uploads/elmercado-blog-static-v2"
if [ -d "$cache_dir" ]; then
  echo 'blog_static_v2_dir_present=yes'
  root_html="$(find "$cache_dir" -mindepth 1 -maxdepth 1 -type f -name '*.html' 2>/dev/null | wc -l | tr -d ' ')"
  nested_html="$(find "$cache_dir" -mindepth 2 -maxdepth 6 -type f -name '*.html' 2>/dev/null | wc -l | tr -d ' ')"
  english_paths="$(find "$cache_dir" -mindepth 1 -maxdepth 6 -type f -path '*/en/*' 2>/dev/null | wc -l | tr -d ' ')"
  potato_paths="$(find "$cache_dir" -mindepth 1 -maxdepth 6 -type f -iname '*why-cut-potatoes*' 2>/dev/null | wc -l | tr -d ' ')"
  echo "blog_static_v2_root_html=$root_html"
  echo "blog_static_v2_nested_html=$nested_html"
  echo "blog_static_v2_en_paths=$english_paths"
  echo "blog_static_v2_potato_paths=$potato_paths"
else
  echo 'blog_static_v2_dir_present=no'
fi
if [ -f "$dropin" ]; then
  for pair in 'static_v2:elmercado-blog-static-v2' 'static_en:/en/|english' 'static_serving:readfile|file_get_contents|fpassthru' 'static_key:sha1|md5|hash|crc32'; do
    flag="${pair%%:*}"; regex="${pair#*:}"
    if grep -Eqi "$regex" "$dropin"; then state=yes; else state=no; fi
    echo "advanced_cache_$flag=$state"
  done
fi
wp_cache="$("$php_bin" -d memory_limit=256M "$wp_bin" eval 'echo defined("WP_CACHE") && WP_CACHE ? "yes" : "no";' --path="$prod" --skip-plugins --skip-themes --allow-root 2>/dev/null || true)"
case "$wp_cache" in yes|no) ;; *) wp_cache=unknown ;; esac
echo "wp_cache_enabled=$wp_cache"
# Print only HTTP status, known cache-header status, expected-title flag and H1 count.
ip="$(hostname -I 2>/dev/null | tr ' ' '\n' | grep -E '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$' | head -n 1 || true)"
if [ -z "$ip" ]; then echo 'origin_ip_available=no'; exit 0; fi
url='https://www.elmercadodeorigen.com/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/'
for mode in normal nocache_nonce; do
  out="$(mktemp)"; hdr="$(mktemp)"
  request="$url"
  if [ "$mode" = nocache_nonce ]; then request="${url}?seo_origin_live_${rid}=1"; fi
  code="$(curl -ksS --connect-timeout 6 --max-time 18 --max-redirs 0 --resolve "www.elmercadodeorigen.com:443:$ip" -H 'Cache-Control: no-cache, no-store' -H 'Pragma: no-cache' -D "$hdr" -o "$out" -w '%{http_code}' "$request" || true)"
  [ -n "$code" ] || code=000
  count="$(grep -oEi '<h1([[:space:]>])' "$out" | wc -l | tr -d ' ' || true)"
  [ -n "$count" ] || count=0
  expected=no
  grep -Fq 'Why Cut Potatoes Turn Black: Causes' "$out" && expected=yes || true
  label=none
  if grep -Eiq '^(x-cache|cf-cache-status|x-litespeed-cache|x-rocket-cache|x-proxy-cache|x-el-mercado-cache):' "$hdr"; then
    if grep -Eiq '^(x-cache|cf-cache-status|x-litespeed-cache|x-rocket-cache|x-proxy-cache|x-el-mercado-cache):[[:space:]]*(hit|cached|stale)([[:space:];]|$)' "$hdr"; then label=hit
    elif grep -Eiq '^(x-cache|cf-cache-status|x-litespeed-cache|x-rocket-cache|x-proxy-cache|x-el-mercado-cache):[[:space:]]*(miss|bypass|dynamic|expired)([[:space:];]|$)' "$hdr"; then label=miss
    else label=other; fi
  fi
  echo "origin_${mode}=http:${code} h1:${count} expected_title:${expected} cache_header:${label}"
  rm -f "$out" "$hdr"
done
REMOTE
