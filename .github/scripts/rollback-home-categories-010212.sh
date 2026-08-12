#!/usr/bin/env bash
set -Eeuo pipefail

: "${STAGING_HOST:?}"
: "${STAGING_USER:?}"
: "${STAGING_PASSWORD:?}"
: "${PRODUCTION_WP_PATH:?}"
: "${PRODUCTION_SITEURL:?}"
: "${PREDEPLOY_OLD_PATH:?}"
: "${ROLLBACK_BACKUP_DIR:?}"

PORT="${STAGING_PORT:-22}"
SSH_OPTIONS="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -p $PORT"
SCP_OPTIONS="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -P $PORT"
export SSHPASS="$STAGING_PASSWORD"

sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "sh -s -- '$PRODUCTION_WP_PATH' '$PREDEPLOY_OLD_PATH' '$ROLLBACK_BACKUP_DIR'" <<'REMOTE'
set -eu
prod="$1"
old="$2"
backup_dir="$3"
target="$prod/wp-content/themes/elmercadodeorigen-child"
dropin="$prod/wp-content/advanced-cache.php"

if [ -d "$old" ]; then
  rm -rf "$target"
  mv "$old" "$target"
fi

if [ "$(cat "$backup_dir/advanced-cache-state.txt" 2>/dev/null || echo absent)" = 'present' ] && [ -f "$backup_dir/advanced-cache.php" ]; then
  cp "$backup_dir/advanced-cache.php" "$dropin"
else
  rm -f "$dropin"
fi

rm -f "$prod/wp-content/uploads/elmercado-home-static/index.html" "$prod/wp-content/cache/elmercado-home-static/index.html"
rm -f "$prod/wp-content/uploads/elmercado-home-static"/home-deferred-*.css

php_bin="$(command -v php || true)"
[ -n "$php_bin" ] || php_bin="$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)"
wp_bin="$(command -v wp || true)"
"$php_bin" -d memory_limit=512M "$wp_bin" theme activate elmercadodeorigen-child --path="$prod" --skip-plugins --allow-root >/dev/null 2>&1 || true
"$php_bin" -d memory_limit=512M "$wp_bin" cache flush --path="$prod" --skip-plugins --allow-root >/dev/null 2>&1 || true
REMOTE

BASE="${PRODUCTION_SITEURL%/}"
OPCACHE_PROBE="elmercado-opcache-rollback-${GITHUB_RUN_ID:-manual}-$(openssl rand -hex 8).php"
sshpass -e scp $SCP_OPTIONS .github/scripts/elmercado-opcache-reset.php "$STAGING_USER@$STAGING_HOST:$PRODUCTION_WP_PATH/$OPCACHE_PROBE"
opcache_body="$(curl -sS -L -H 'Cache-Control: no-store' -H 'Pragma: no-cache' --connect-timeout 15 --max-time 30 "$BASE/$OPCACHE_PROBE" || true)"
printf '%s\n' "$opcache_body"
sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "rm -f '$PRODUCTION_WP_PATH/$OPCACHE_PROBE'"

RUNTIME_PROBE="elmercado-runtime-rollback-${GITHUB_RUN_ID:-manual}-$(openssl rand -hex 8).php"
sshpass -e scp $SCP_OPTIONS .github/scripts/elmercado-runtime-probe.php "$STAGING_USER@$STAGING_HOST:$PRODUCTION_WP_PATH/$RUNTIME_PROBE"
runtime_body="$(curl -sS -L -H 'Cache-Control: no-store' -H 'Pragma: no-cache' --connect-timeout 15 --max-time 60 "$BASE/$RUNTIME_PROBE" || true)"
printf '%s\n' "$runtime_body"
sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "rm -f '$PRODUCTION_WP_PATH/$RUNTIME_PROBE' '${REMOTE_TMP:-/tmp/never}'"

if [ -n "${PREVIOUS_VERSION:-}" ]; then
  grep -q "^theme=${PREVIOUS_VERSION//./\\.}$" <<< "$runtime_body" || true
fi

echo 'ROLLBACK_HOME_010212_OK'
