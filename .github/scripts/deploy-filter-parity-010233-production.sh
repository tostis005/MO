#!/usr/bin/env bash
set -euo pipefail

SOURCE_DIR="${1:?source checkout required}"
BASE_URL="${BASE_URL:-https://www.elmercadodeorigen.com}"
PORT="${STAGING_PORT:-22}"
SSH_OPTIONS="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -p $PORT"
SCP_OPTIONS="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -P $PORT"

: "${STAGING_HOST:?}"
: "${STAGING_USER:?}"
: "${STAGING_PASSWORD:?}"
: "${SSHPASS:?}"

FILTER_FILES=(
  catalog-result-total-cleanup-010221.php
  vendor-store-catalog-filters-010225.php
  vendor-filter-spacing-010226.php
  vendor-sticky-root-fix-010228.php
  catalog-filter-unified-010229.php
  catalog-filter-mobile-hitarea-010233.php
)

for f in "${FILTER_FILES[@]}"; do
  test -f "$SOURCE_DIR/elmercadodeorigen-child/inc/$f"
  php -l "$SOURCE_DIR/elmercadodeorigen-child/inc/$f" >/dev/null
done
node --check "$SOURCE_DIR/.github/scripts/catalog-filter-unified-010229-browser.cjs"
grep -Fq 'emo_vendor_cat' "$SOURCE_DIR/elmercadodeorigen-child/inc/vendor-store-catalog-filters-010225.php"
grep -Fq 'elmercado-vendor-filter-lean-runtime-010233' "$SOURCE_DIR/elmercadodeorigen-child/inc/catalog-result-total-cleanup-010221.php"
grep -Fq 'elmercado-catalog-filter-shared-interaction-010233' "$SOURCE_DIR/elmercadodeorigen-child/inc/catalog-result-total-cleanup-010221.php"
grep -Fq 'elmercado-catalog-filter-mobile-hitarea-010233-v2' "$SOURCE_DIR/elmercadodeorigen-child/inc/catalog-filter-mobile-hitarea-010233.php"
echo FILTER_PAYLOAD_010233_OK

PROD_PATH="$(sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" '
  set -e
  wp_bin=$(command -v wp || true)
  php_bin=$(command -v php || true)
  [ -n "$php_bin" ] || php_bin=$(find /opt/plesk/php -maxdepth 3 -type f -path "*/bin/php" 2>/dev/null | sort -Vr | head -n1)
  [ -n "$wp_bin" ] || exit 1
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
: "${PROD_PATH:?production path not found}"
THEME_PATH="$PROD_PATH/wp-content/themes/elmercadodeorigen-child"
BACKUP_DIR="/tmp/elmercado-filter-010233-${GITHUB_RUN_ID:-manual}"
REMOTE_PAYLOAD="/tmp/elmercado-filter-payload-010233-${GITHUB_RUN_ID:-manual}"
export PROD_PATH THEME_PATH BACKUP_DIR REMOTE_PAYLOAD

rollback() {
  set +e
  echo 'ROLLBACK_FILTER_PARITY_010233_START'
  sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "set +e; if [ -f '$BACKUP_DIR/functions.php' ]; then cp -p '$BACKUP_DIR/functions.php' '$THEME_PATH/functions.php'; fi; for f in ${FILTER_FILES[*]}; do if [ -f '$BACKUP_DIR/inc/.missing-'\"\$f\" ]; then rm -f '$THEME_PATH/inc/'\"\$f\"; elif [ -f '$BACKUP_DIR/inc/'\"\$f\" ]; then cp -p '$BACKUP_DIR/inc/'\"\$f\" '$THEME_PATH/inc/'\"\$f\"; fi; done; rm -rf '$REMOTE_PAYLOAD' '$BACKUP_DIR'" || true
  echo PRODUCTION_FILTER_PARITY_ROLLED_BACK
}
trap rollback ERR

sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "set -e; test -d '$THEME_PATH/inc'; mkdir -p '$BACKUP_DIR/inc' '$REMOTE_PAYLOAD/inc'; cp -p '$THEME_PATH/functions.php' '$BACKUP_DIR/functions.php'; for f in ${FILTER_FILES[*]}; do if [ -f '$THEME_PATH/inc/'\"\$f\" ]; then cp -p '$THEME_PATH/inc/'\"\$f\" '$BACKUP_DIR/inc/'\"\$f\"; else : > '$BACKUP_DIR/inc/.missing-'\"\$f\"; fi; done"

for f in "${FILTER_FILES[@]}"; do
  sshpass -e scp $SCP_OPTIONS "$SOURCE_DIR/elmercadodeorigen-child/inc/$f" "$STAGING_USER@$STAGING_HOST:$REMOTE_PAYLOAD/inc/$f"
done

cat > /tmp/patch-filter-functions-010233.py <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
s = path.read_text()
anchor = "\t'inc/catalog-result-total-cleanup-010221.php',\n"
if anchor not in s:
    raise SystemExit('catalog cleanup anchor missing')

module_lines = [
    "\t'inc/vendor-store-catalog-filters-010225.php',\n",
    "\t'inc/vendor-filter-spacing-010226.php',\n",
    "\t'inc/catalog-filter-mobile-hitarea-010233.php',\n",
]
insertion = ''.join(line for line in module_lines if line not in s)
if insertion:
    s = s.replace(anchor, anchor + insertion, 1)

marker = "/* FILTER_PARITY_010233_PRODUCTION */"
if marker not in s:
    loop_end = "}\n\nremove_action( 'wp_print_styles', 'elmercado_optimize_home_assets', 0 );"
    if loop_end not in s:
        raise SystemExit('module loop end anchor missing')
    hook = """}

/* FILTER_PARITY_010233_PRODUCTION */
add_action(
    'after_setup_theme',
    static function (): void {
        $module = ELMERCADO_THEME_PATH . '/inc/catalog-filter-unified-010229.php';
        if ( is_readable( $module ) ) {
            require_once $module;
        }
    },
    PHP_INT_MAX
);

remove_action( 'wp_print_styles', 'elmercado_optimize_home_assets', 0 );"""
    s = s.replace(loop_end, hook, 1)

path.write_text(s)
PY
python3 -m py_compile /tmp/patch-filter-functions-010233.py
sshpass -e scp $SCP_OPTIONS /tmp/patch-filter-functions-010233.py "$STAGING_USER@$STAGING_HOST:$REMOTE_PAYLOAD/patch-functions.py"

sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "set -euo pipefail; cp -p '$THEME_PATH/functions.php' '$REMOTE_PAYLOAD/functions.php'; python3 '$REMOTE_PAYLOAD/patch-functions.py' '$REMOTE_PAYLOAD/functions.php'; php_bin=\$(command -v php || true); [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1); \"\$php_bin\" -l '$REMOTE_PAYLOAD/functions.php' >/dev/null; for f in '$REMOTE_PAYLOAD'/inc/*.php; do \"\$php_bin\" -l \"\$f\" >/dev/null; done; install -m 0644 '$REMOTE_PAYLOAD/functions.php' '$THEME_PATH/functions.php'; for f in '$REMOTE_PAYLOAD'/inc/*.php; do install -m 0644 \"\$f\" '$THEME_PATH/inc/'\"\$(basename \"\$f\")\"; done; grep -Fq 'FILTER_PARITY_010233_PRODUCTION' '$THEME_PATH/functions.php'; grep -Fq 'emo_vendor_cat' '$THEME_PATH/inc/vendor-store-catalog-filters-010225.php'; grep -Fq 'elmercado-catalog-filter-mobile-hitarea-010233-v2' '$THEME_PATH/inc/catalog-filter-mobile-hitarea-010233.php'"
echo FILTER_FILES_DEPLOYED_010233_OK

sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "wp_bin=\$(command -v wp || true); php_bin=\$(command -v php || true); [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1); \"\$php_bin\" \"\$wp_bin\" cache flush --path='$PROD_PATH' --allow-root >/dev/null 2>&1 || true"

PROBE="elmercado-opcache-filter-${GITHUB_RUN_ID:-manual}.php"
sshpass -e scp $SCP_OPTIONS "$SOURCE_DIR/.github/scripts/elmercado-opcache-reset.php" "$STAGING_USER@$STAGING_HOST:$PROD_PATH/$PROBE"
BODY="$(curl -fsSL -H 'Cache-Control: no-store' -H 'Pragma: no-cache' --max-time 30 "$BASE_URL/$PROBE?run=${GITHUB_RUN_ID:-manual}")"
sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "rm -f '$PROD_PATH/$PROBE'"
printf '%s\n' "$BODY" | grep -q '^OPCACHE_RESET_OK$'
echo FILTER_CACHE_RESET_010233_OK

runtime_ok=0
for i in $(seq 1 45); do
  shop="$(curl -fsSL --max-time 30 -H 'Cache-Control: no-store' "${BASE_URL}/tienda/?filter-prod=${GITHUB_RUN_ID:-manual}-${i}" || true)"
  vendor="$(curl -fsSL --max-time 30 -H 'Cache-Control: no-store' "${BASE_URL}/tienda/hidalgo-de-la-jara/?filter-prod=${GITHUB_RUN_ID:-manual}-${i}" || true)"
  if grep -Fq 'elmercado-catalog-filter-shared-interaction-010233' <<<"$shop" \
    && grep -Fq 'elmercado-catalog-filter-shared-interaction-010233' <<<"$vendor" \
    && grep -Fq 'elmercado-vendor-filter-lean-runtime-010233' <<<"$vendor" \
    && grep -Fq 'elmercado-catalog-filter-mobile-hitarea-010233-v2' <<<"$vendor" \
    && ! grep -Fq 'elmercado-vendor-store-catalog-010225' <<<"$vendor"; then
    runtime_ok=1
    break
  fi
  sleep 3
done
test "$runtime_ok" -eq 1
echo PRODUCTION_FILTER_RUNTIME_010233_OK

BASE_URL="$BASE_URL" node "$SOURCE_DIR/.github/scripts/catalog-filter-unified-010229-browser.cjs"

esshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "rm -rf '$BACKUP_DIR' '$REMOTE_PAYLOAD'"
trap - ERR
echo PRODUCTION_FILTER_PARITY_010233_OK
