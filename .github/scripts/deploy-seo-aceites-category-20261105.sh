#!/usr/bin/env bash
set -Eeuo pipefail
php -l .github/scripts/seo-aceites-category-copy-20261105.php
test -n "${STAGING_HOST:-}" && test -n "${STAGING_USER:-}" && test -n "${SSHPASS:-}"
sudo apt-get update -qq
sudo apt-get install -y --no-install-recommends sshpass >/dev/null
PORT="${STAGING_PORT:-22}"
mkdir -p ~/.ssh && chmod 700 ~/.ssh
ssh-keyscan -T 15 -p "$PORT" -H "$STAGING_HOST" > ~/.ssh/known_hosts
chmod 600 ~/.ssh/known_hosts
SSH="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -p $PORT"
SCP="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -P $PORT"
INFO="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" '
  set -e
  wp_bin=$(command -v wp)
  php_bin=$(command -v php || true)
  [ -n "$php_bin" ] || php_bin=$(find /opt/plesk/php -maxdepth 3 -type f -path "*/bin/php" 2>/dev/null | sort -Vr | head -n1)
  for cfg in $(find /var/www/vhosts "$HOME" -maxdepth 7 -type f -path "*/httpdocs/wp-config.php" 2>/dev/null); do
    p=$(dirname "$cfg")
    url=$("$php_bin" "$wp_bin" option get siteurl --path="$p" --skip-plugins --skip-themes --allow-root 2>/dev/null || true)
    case "$url" in
      https://www.elmercadodeorigen.com|http://www.elmercadodeorigen.com|https://elmercadodeorigen.com|http://elmercadodeorigen.com)
        printf "%s|%s|%s" "$p" "$php_bin" "$wp_bin"; break;;
    esac
  done
')"
test -n "$INFO"
PROD="${INFO%%|*}"; REST="${INFO#*|}"; PHP="${REST%%|*}"; WP="${REST#*|}"
REMOTE="/tmp/seo-aceites-category-${GITHUB_RUN_ID}.php"
BACKUP="/tmp/seo-aceites-20261105-${GITHUB_RUN_ID}.bak"
sshpass -e scp $SCP .github/scripts/seo-aceites-category-copy-20261105.php "$STAGING_USER@$STAGING_HOST:$REMOTE"
wp_mode() {
  local mode="$1"
  sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" \
    "EMDO_SEO_MODE='$mode' EMDO_SEO_BACKUP='$BACKUP' '$PHP' -d memory_limit=768M '$WP' eval-file '$REMOTE' --path='$PROD' --allow-root"
}
APPLIED=0
rollback_on_failure() {
  local code="$?"
  trap - ERR
  if [ "$APPLIED" = 1 ]; then
    echo "::error::Aceites QA failed. Restoring backup of the original term description"
    wp_mode rollback || echo "::error::Rollback failed; manual recovery from $BACKUP required"
  fi
  exit "$code"
}
cleanup() {
  sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "rm -f '$REMOTE'" >/dev/null 2>&1 || true
}
trap rollback_on_failure ERR
trap cleanup EXIT
wp_mode dryrun
wp_mode apply
APPLIED=1
wp_mode verify
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" \
  "'$PHP' '$WP' cache flush --path='$PROD' --allow-root >/dev/null 2>&1 || true"
URL="https://www.elmercadodeorigen.com/categoria-producto/aceites/?seo_aceites_check=${GITHUB_RUN_ID}"
curl -fLsS --retry 3 --retry-delay 3 --max-time 75 -H 'Cache-Control: no-cache' "$URL" -o /tmp/seo-aceites-after.html
python3 - <<'PY'
from pathlib import Path
from html import unescape
import re
src=Path('/tmp/seo-aceites-after.html').read_text(errors='replace')
plain=re.sub(r'\s+',' ',re.sub(r'<[^>]+>',' ',unescape(src)))
assert len(src)>12000, 'Aceites response too short'
assert 'Nueva cosecha 2026/27 de 1957.' in plain, 'commercial introduction absent'
assert src.count('Compra <strong>aceite de oliva virgen extra online</strong>') == 1, 'category copy absent/duplicated'
assert 'El primer paso es el molido.' not in plain, 'obsolete manufacturing wall remains'
assert 'https://www.elmercadodeorigen.com/tienda/1957/' in src, 'producer link missing'
for slug in ('cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio',
             'sin-filtrar-nueva-campana-26-27-aceite-de-oliva-virgen-extra-15x1l',
             'aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27'):
    assert slug in src, 'Missing AOVE harvest product '+slug
assert re.search(r'<link[^>]+rel=["\']canonical["\'][^>]+/categoria-producto/aceites/',src), 'canonical changed'
assert len(re.findall(r'<h1\b',src,re.I))==1, 'H1 duplication'
print('ACEITES_COMMERCIAL_CATEGORY_PUBLIC_OK')
PY
echo ACEITES_CATEGORY_DEPLOY_VERIFIED
