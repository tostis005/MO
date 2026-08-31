#!/usr/bin/env bash
set -euo pipefail

PHP_SCRIPT=".github/scripts/publish-nutrition-priority-51-55-production.php"
BASE="elmercadodeorigen-child/inc/content-seeds/nutrition-priority-51-55-010276.part"
EXPECTED_SHA="5382f18ecfb7ff6890d9e5a83db43d1b6661f41cb30e7b1ebbe608c8e8afb869"
UA='Mozilla/5.0 EMDO nutrition publication verification'

php -l "$PHP_SCRIPT"
python3 - <<'PY'
import base64,gzip,json,hashlib
from pathlib import Path
base=Path('elmercadodeorigen-child/inc/content-seeds/nutrition-priority-51-55-010276.part')
parts=[Path(str(base)+str(i)) for i in (1,2,3)]
enc=''.join(p.read_text().strip() for p in parts)
expected='5382f18ecfb7ff6890d9e5a83db43d1b6661f41cb30e7b1ebbe608c8e8afb869'
if hashlib.sha256(enc.encode()).hexdigest()!=expected: raise SystemExit('Payload SHA mismatch')
data=json.loads(gzip.decompress(base64.b64decode(enc,validate=True)).decode('utf-8'))
expected_slugs=[
'nutrientes-chorizo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
'cuanta-proteina-tiene-chorizo-iberico',
'cuanto-hierro-tiene-chorizo-iberico',
'grasa-chorizo-iberico-saturada-monoinsaturada-poliinsaturada',
'chorizo-iberico-vs-salchichon-iberico-diferencias-nutricionales']
if not isinstance(data,list) or [a.get('slug') for a in data]!=expected_slugs: raise SystemExit('Unexpected payload/order')
for a in data:
    if len(a['content'])<5000 or len(a['en_content'])<4500: raise SystemExit('Article too short: '+a['slug'])
    if a['category_slug']!='embutidos-y-curados': raise SystemExit('Wrong category: '+a['slug'])
    if '<!-- EMDO_RELATED_PRODUCTS -->' not in a['content'] or '<!-- EMDO_RELATED_PRODUCTS -->' not in a['en_content']: raise SystemExit('Missing related marker')
print('Editorial payload OK:', ', '.join(a['slug'] for a in data))
PY

if ! command -v sshpass >/dev/null 2>&1; then
  sudo apt-get update -qq
  sudo apt-get install -y --no-install-recommends sshpass >/dev/null
fi

PORT="${STAGING_PORT:-22}"
SSH="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=15 -p $PORT"
SCP="-P $PORT -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o ConnectTimeout=15"

head_ok(){
  local url="$1" code
  code="$(curl -L -I -sS --connect-timeout 8 --max-time 20 --retry 1 --retry-delay 1 -A "$UA" -o /dev/null -w '%{http_code}' "$url")"
  [ "$code" = 200 ] || { echo "HTTP ${code} ${url}" >&2; return 1; }
  echo "http_ok ${code} ${url}"
}
parallel_head_check(){
  local pids=() rc=0 url pid
  for url in "$@"; do ( head_ok "$url" ) & pids+=("$!"); done
  for pid in "${pids[@]}"; do wait "$pid" || rc=1; done
  return "$rc"
}

# One lightweight preflight is enough to make sure the public site is alive.
head_ok 'https://www.elmercadodeorigen.com/'

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
REMOTE="/tmp/emdo-nutrition-5155-${GITHUB_RUN_ID}"
TOKEN="nutrition-5155-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "mkdir -p '$REMOTE/content-seeds'"
sshpass -e scp $SCP "$PHP_SCRIPT" "$STAGING_USER@$STAGING_HOST:$REMOTE/"
for i in 1 2 3; do sshpass -e scp $SCP "${BASE}${i}" "$STAGING_USER@$STAGING_HOST:$REMOTE/content-seeds/"; done

ROLLBACK_ACTIVE=1
rollback(){
  rc=$?; set +e
  if [ "${ROLLBACK_ACTIVE:-0}" = 1 ]; then
    echo "Verification failed: rolling back only posts created by token $TOKEN." >&2
    sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export PATH='$(dirname "$PHP")':\$PATH; ids=\$('$PHP' '$WP' post list --path='$PROD' --allow-root --post_type=post --post_status=any --meta_key=_emdo_nutrition_batch_token --meta_value='$TOKEN' --format=ids 2>/dev/null || true); [ -z \"\$ids\" ] || '$PHP' '$WP' post delete \$ids --force --path='$PROD' --allow-root >/dev/null 2>&1; '$PHP' '$WP' rocket clean --confirm --path='$PROD' --allow-root >/dev/null 2>&1 || true; rm -rf '$REMOTE'" >/dev/null 2>&1
  fi
  exit "$rc"
}
trap rollback ERR

trap - ERR; set +e
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "export EMDO_NUTRITION_5155_SEED_DIR='$REMOTE'; export EMDO_NUTRITION_5155_TOKEN='$TOKEN'; export PATH='$(dirname "$PHP")':\$PATH; '$PHP' -d memory_limit=768M '$WP' eval-file '$REMOTE/$(basename "$PHP_SCRIPT")' --path='$PROD' --allow-root" >/tmp/nutrition-5155.raw 2>/tmp/nutrition-5155.err
RC=$?; set -e; trap rollback ERR
[ ! -s /tmp/nutrition-5155.err ] || cat /tmp/nutrition-5155.err >&2
[ ! -s /tmp/nutrition-5155.raw ] || cat /tmp/nutrition-5155.raw
[ "$RC" -eq 0 ]
JSON_LINE="$(grep -E '^\{.*\}$' /tmp/nutrition-5155.raw|tail -n1 || true)"; [ -n "$JSON_LINE" ]
printf '%s\n' "$JSON_LINE" >/tmp/nutrition-5155-result.json
python3 - <<'PY'
import json
from pathlib import Path
p=Path('/tmp/nutrition-5155-result.json')
d=json.loads(p.read_text())
if not (d.get('verified') is True and d.get('count')==5 and len(d.get('errors',[]))==0 and len(d.get('posts',[]))==5):
    raise SystemExit('WordPress verification JSON is not valid')
urls=[]
for row in d['posts']:
    urls.extend([row['permalink'],row['en_permalink']])
Path('/tmp/nutrition-5155-urls.txt').write_text('\n'.join(urls)+'\n')
print('WordPress verified 5 bilingual articles.')
PY

# Public verification is intentionally lightweight: normal cached URLs, HEAD, all in parallel.
mapfile -t ARTICLE_URLS < /tmp/nutrition-5155-urls.txt
parallel_head_check "${ARTICLE_URLS[@]}" 'https://www.elmercadodeorigen.com/' 'https://www.elmercadodeorigen.com/blog/'

ROLLBACK_ACTIVE=0; trap - ERR
sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "rm -rf '$REMOTE'"
echo 'FAST_PUBLICATION_OK: batch 51-55 nutrition articles published/adopted and verified.'
