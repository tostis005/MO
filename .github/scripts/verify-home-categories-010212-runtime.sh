#!/usr/bin/env bash
set -Eeuo pipefail

: "${STAGING_HOST:?}"
: "${STAGING_USER:?}"
: "${STAGING_PASSWORD:?}"
: "${PRODUCTION_WP_PATH:?}"
: "${PRODUCTION_SITEURL:?}"

PORT="${STAGING_PORT:-22}"
SSH_OPTIONS="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -p $PORT"
SCP_OPTIONS="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -P $PORT"
export SSHPASS="$STAGING_PASSWORD"
BASE="${PRODUCTION_SITEURL%/}"

OPCACHE_PROBE="elmercado-opcache-${GITHUB_RUN_ID:-manual}-$(openssl rand -hex 8).php"
sshpass -e scp $SCP_OPTIONS .github/scripts/elmercado-opcache-reset.php "$STAGING_USER@$STAGING_HOST:$PRODUCTION_WP_PATH/$OPCACHE_PROBE"
sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "test -s '$PRODUCTION_WP_PATH/$OPCACHE_PROBE'"
opcache_body="$(curl -sS -L -H 'Cache-Control: no-store' -H 'Pragma: no-cache' --connect-timeout 15 --max-time 30 "$BASE/$OPCACHE_PROBE" || true)"
printf '%s\n' "$opcache_body"
sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "rm -f '$PRODUCTION_WP_PATH/$OPCACHE_PROBE'"
grep -q '^OPCACHE_RESET_OK$' <<< "$opcache_body"
echo 'PHP_FPM_OPCACHE_RESET_OK'

RUNTIME_PROBE="elmercado-runtime-${GITHUB_RUN_ID:-manual}-$(openssl rand -hex 8).php"
sshpass -e scp $SCP_OPTIONS .github/scripts/elmercado-runtime-probe.php "$STAGING_USER@$STAGING_HOST:$PRODUCTION_WP_PATH/$RUNTIME_PROBE"
sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "test -s '$PRODUCTION_WP_PATH/$RUNTIME_PROBE'"
runtime_body="$(curl -sS -L -H 'Cache-Control: no-store' -H 'Pragma: no-cache' --connect-timeout 15 --max-time 60 "$BASE/$RUNTIME_PROBE" || true)"
printf '%s\n' "$runtime_body"
sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "rm -f '$PRODUCTION_WP_PATH/$RUNTIME_PROBE'"
grep -q '^theme=0\.10\.212$' <<< "$runtime_body"
grep -q '^helper=1$' <<< "$runtime_body"
echo 'PHP_FPM_RUNTIME_010212_OK'

# Force one uncached Home render through the verified PHP-FPM runtime.
code="$(curl -sS -L -H 'Cache-Control: no-cache' -H 'Pragma: no-cache' --connect-timeout 15 --max-time 90 -D /tmp/home-010212-warm.headers -o /tmp/home-010212-warm.html -w '%{http_code}' "$BASE/" || true)"
[ "$code" = '200' ] || { echo "Uncached Home HTTP $code" >&2; exit 1; }
grep -q 'emo-category-card' /tmp/home-010212-warm.html

echo '=== Home warm headers ==='
tr -d '\r' < /tmp/home-010212-warm.headers | grep -Eiv '^(set-cookie:|date:)' || true

# Read the anonymous root again. The browser step performs the definitive DOM,
# count and layout assertions, so here we only require a healthy Home response.
code="$(curl -sS -L --connect-timeout 15 --max-time 90 -D /tmp/home-010212-root.headers -o /tmp/home-010212-root.html -w '%{http_code}' "$BASE/" || true)"
[ "$code" = '200' ] || { echo "Anonymous Home HTTP $code" >&2; exit 1; }
grep -q 'emo-category-card' /tmp/home-010212-root.html

echo '=== Home anonymous headers ==='
tr -d '\r' < /tmp/home-010212-root.headers | grep -Eiv '^(set-cookie:|date:)' || true

echo 'PRODUCTION_HOME_RUNTIME_010212_OK'
