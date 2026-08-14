#!/usr/bin/env bash
set -Eeuo pipefail

: "${STAGING_HOST:?Missing STAGING_HOST}"
: "${STAGING_USER:?Missing STAGING_USER}"
: "${STAGING_PASSWORD:?Missing STAGING_PASSWORD}"
export SSHPASS="${STAGING_PASSWORD}"
BASE_URL="${BASE_URL:-https://www.elmercadodeorigen.com}"
PORT="${STAGING_PORT:-22}"

sudo apt-get update -qq
sudo apt-get install -y --no-install-recommends sshpass >/dev/null
mkdir -p ~/.ssh && chmod 700 ~/.ssh
ssh-keyscan -T 15 -p "$PORT" -H "$STAGING_HOST" > ~/.ssh/known_hosts
chmod 600 ~/.ssh/known_hosts
SSH="-o ServerAliveInterval=20 -o ServerAliveCountMax=40 -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -p $PORT"
SCP="-o ServerAliveInterval=20 -o ServerAliveCountMax=40 -o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -P $PORT"

PROD_PATH="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" '
  set -e
  wp_bin=$(command -v wp || true)
  php_bin=$(command -v php || true)
  [ -n "$php_bin" ] || php_bin=$(find /opt/plesk/php -maxdepth 3 -type f -path "*/bin/php" 2>/dev/null | sort -Vr | head -n1)
  [ -n "$wp_bin" ] && [ -n "$php_bin" ] || exit 1
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
test -n "$PROD_PATH"
echo PRODUCTION_PATH_OK

PLUGIN="$PROD_PATH/wp-content/plugins/mdo-supplier-sync"
RUN_TAG="${GITHUB_RUN_ID:-manual}"
files=(
  "mdo-supplier-sync.php"
  "includes/class-mdo-pricing.php"
  "includes/class-mdo-woo-importer.php"
  "includes/class-mdo-scheduler.php"
)

for f in "${files[@]}"; do
  base="$(basename "$f")"
  sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "test -f '$PLUGIN/$f'; cp -p '$PLUGIN/$f' '/tmp/emdo-010245-${RUN_TAG}-${base}.bak'"
  sshpass -e scp $SCP "mdo-supplier-sync/$f" "$STAGING_USER@$STAGING_HOST:$PLUGIN/$f"
done

rollback(){
  code=$?
  set +e
  echo CODE_ROLLBACK_START
  for f in "${files[@]}"; do
    base="$(basename "$f")"
    b="/tmp/emdo-010245-${RUN_TAG}-${base}.bak"
    sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "[ -f '$b' ] && cp -p '$b' '$PLUGIN/$f'" || true
  done
  echo CODE_ROLLED_BACK
  exit "$code"
}
trap rollback ERR

sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "
  set -e
  php_bin=\$(command -v php || true)
  [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)
  \"\$php_bin\" -l '$PLUGIN/mdo-supplier-sync.php' >/dev/null
  \"\$php_bin\" -l '$PLUGIN/includes/class-mdo-pricing.php' >/dev/null
  \"\$php_bin\" -l '$PLUGIN/includes/class-mdo-woo-importer.php' >/dev/null
  \"\$php_bin\" -l '$PLUGIN/includes/class-mdo-scheduler.php' >/dev/null
  grep -Fq 'Version: 1.0.13' '$PLUGIN/mdo-supplier-sync.php'
  grep -Fq 'mark_source_unavailable' '$PLUGIN/includes/class-mdo-woo-importer.php'
  grep -Fq 'reconcile_missing_products' '$PLUGIN/includes/class-mdo-scheduler.php'
"

echo CODE_DEPLOYED_1_0_13

fresh="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "
  php_bin=\$(command -v php || true)
  [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)
  wp_bin=\$(command -v wp)
  \"\$php_bin\" -d memory_limit=768M \"\$wp_bin\" eval '
    \$urls=[
      \"https://tolecarnes.com/producto/filetes-primera-de-ternera-af/\",
      \"https://tolecarnes.com/producto/carne-picada-de-ternera/\",
      \"https://tolecarnes.com/producto/magro-ragu-ternera-az/\",
      \"https://tolecarnes.com/producto/roast-beef-de-ternera-adq/\",
      \"https://tolecarnes.com/producto/pecho-en-filetes-de-ternera/\"
    ];
    \$sales=0;
    foreach(\$urls as \$u){
      try{
        \$p=MDO_Text::normalize_product(MDO_Connector_Tolecarnes::scrape_product(\$u));
        \$o=[\"url\"=>\$u,\"title\"=>\$p[\"title\"]??null,\"price\"=>\$p[\"price\"]??null,\"regular\"=>\$p[\"regular_price\"]??null,\"sale\"=>\$p[\"sale_price\"]??null];
        echo \"FRESH_PRICE=\".wp_json_encode(\$o,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).\"\\n\";
        if(null!==\$o[\"sale\"] && (float)\$o[\"sale\"]<(float)\$o[\"regular\"]) \$sales++;
        if(str_contains(\$u,\"filetes-primera\") && (abs((float)\$o[\"price\"]-18.99)>0.005 || null!==\$o[\"sale\"])) exit(21);
      }catch(Throwable \$e){
        echo \"FRESH_ERROR=\".\$u.\" :: \".\$e->getMessage().\"\\n\";
      }
    }
    echo \"FRESH_REAL_SALES=\".\$sales.\"\\n\";
    if(\$sales<1) exit(22);
  ' --path='$PROD_PATH' --allow-root
")"
printf '%s\n' "$fresh"
printf '%s\n' "$fresh" | grep -Fq 'FRESH_REAL_SALES='

sync_start="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "
  php_bin=\$(command -v php || true)
  [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)
  wp_bin=\$(command -v wp)
  \"\$php_bin\" -d memory_limit=768M \"\$wp_bin\" eval 'global \$wpdb; \$su=MDO_Database::table(\"suppliers\"); \$sid=(int)\$wpdb->get_var(\"SELECT id FROM {\$su} WHERE connector=\\\"tolecarnes\\\" OR LOWER(name) LIKE \\\"%tolecarnes%\\\" ORDER BY id LIMIT 1\"); if(!\$sid) exit(30); MDO_Scheduler::run_supplier(\$sid,\"manual\"); \$t=MDO_Database::table(\"sync_runs\"); echo \$sid.\":\".(int)\$wpdb->get_var(\$wpdb->prepare(\"SELECT MAX(id) FROM {\$t} WHERE supplier_id=%d\",\$sid));' --path='$PROD_PATH' --allow-root
")"
SID="$(printf '%s' "$sync_start" | grep -oE '[0-9]+:[0-9]+' | tail -n1 | cut -d: -f1)"
RUN_ID="$(printf '%s' "$sync_start" | grep -oE '[0-9]+:[0-9]+' | tail -n1 | cut -d: -f2)"
test -n "$SID" && test -n "$RUN_ID"
echo "TOLECARNES_FULL_SYNC_RUN_ID=$RUN_ID SUPPLIER_ID=$SID"

sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "
  php_bin=\$(command -v php || true)
  [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)
  wp_bin=\$(command -v wp)
  \"\$php_bin\" -d memory_limit=1024M \"\$wp_bin\" action-scheduler run --group=mdo-supplier-sync --batch-size=20 --batches=0 --force --path='$PROD_PATH' --allow-root
"

report="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "
  php_bin=\$(command -v php || true)
  [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)
  wp_bin=\$(command -v wp)
  \"\$php_bin\" -d memory_limit=512M \"\$wp_bin\" eval 'global \$wpdb; \$t=MDO_Database::table(\"sync_runs\"); \$r=\$wpdb->get_row(\$wpdb->prepare(\"SELECT id,status,products_found,products_new,products_updated,products_excluded,errors_count,message,started_at,finished_at FROM {\$t} WHERE id=%d\",$RUN_ID),ARRAY_A); echo wp_json_encode(\$r,JSON_UNESCAPED_UNICODE); if(!\$r || !in_array(\$r[\"status\"],[\"success\",\"warning\"],true) || empty(\$r[\"finished_at\"])) exit(31);' --path='$PROD_PATH' --allow-root
")"
echo "FULL_SYNC_REPORT=$report"

reimport="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "
  php_bin=\$(command -v php || true)
  [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)
  wp_bin=\$(command -v wp)
  \"\$php_bin\" -d memory_limit=1536M \"\$wp_bin\" eval '
    global \$wpdb;
    \$t=MDO_Database::table(\"source_products\");
    \$ids=array_map(\"intval\",\$wpdb->get_col(\$wpdb->prepare(\"SELECT id FROM {\$t} WHERE supplier_id=%d AND status=\\\"active\\\" AND wc_product_id IS NOT NULL AND wc_product_id>0 ORDER BY id\",$SID)));
    \$ok=0; \$bad=[];
    foreach(\$ids as \$id){ try{ MDO_Woo_Importer::import_source_product(\$id); \$ok++; }catch(Throwable \$e){ \$bad[]=[\"id\"=>\$id,\"error\"=>\$e->getMessage()]; } }
    echo \"FULL_REIMPORT_COUNT=\".count(\$ids).\" OK=\".\$ok.\" ERRORS=\".count(\$bad).\"\\n\";
    foreach(\$bad as \$b) echo \"REIMPORT_ERROR=\".wp_json_encode(\$b,JSON_UNESCAPED_UNICODE).\"\\n\";
    if(\$bad) exit(41);
  ' --path='$PROD_PATH' --allow-root
")"
printf '%s\n' "$reimport"
printf '%s\n' "$reimport" | grep -Fq 'ERRORS=0'

audit="$(sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "
  php_bin=\$(command -v php || true)
  [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)
  wp_bin=\$(command -v wp)
  \"\$php_bin\" -d memory_limit=1024M \"\$wp_bin\" eval '
    global \$wpdb;
    echo \"PLUGIN_VERSION=\".MDO_SUPPLIER_SYNC_VERSION.\"\\n\";
    if(\"1.0.13\"!==MDO_SUPPLIER_SYNC_VERSION) exit(50);
    \$st=MDO_Database::table(\"source_products\");
    \$rows=\$wpdb->get_results(\$wpdb->prepare(\"SELECT id,title,status,wc_product_id,source_payload FROM {\$st} WHERE supplier_id=%d ORDER BY id\",$SID),ARRAY_A)?:[];
    \$bad=[]; \$sale_count=0; \$active=0; \$unavailable=0;
    foreach(\$rows as \$row){
      if(\"unavailable\"===\$row[\"status\"]){
        \$unavailable++;
        if(!empty(\$row[\"wc_product_id\"])){
          \$wp=wc_get_product((int)\$row[\"wc_product_id\"]);
          if(\$wp && (\"draft\"!==\$wp->get_status() || \"outofstock\"!==\$wp->get_stock_status())) \$bad[]=[\"title\"=>\$row[\"title\"],\"reason\"=>\"unavailable_not_blocked\"];
        }
        continue;
      }
      if(\"active\"!==\$row[\"status\"] || empty(\$row[\"wc_product_id\"])) continue;
      \$active++;
      \$p=json_decode((string)\$row[\"source_payload\"],true); \$p=is_array(\$p)?\$p:[];
      if(!empty(\$p[\"variations\"])) continue;
      \$cur=isset(\$p[\"price\"])&&is_numeric(\$p[\"price\"])?(float)\$p[\"price\"]:null;
      \$reg=isset(\$p[\"regular_price\"])&&is_numeric(\$p[\"regular_price\"])?(float)\$p[\"regular_price\"]:\$cur;
      \$sale=isset(\$p[\"sale_price\"])&&is_numeric(\$p[\"sale_price\"])?(float)\$p[\"sale_price\"]:null;
      \$valid=null!==\$sale&&null!==\$reg&&\$sale<\$reg&&null!==\$cur&&abs(\$cur-\$sale)<0.005;
      if(\$valid) \$sale_count++;
      \$pid=(int)\$row[\"wc_product_id\"];
      \$wp=get_post_meta(\$pid,\"_price\",true); \$wr=get_post_meta(\$pid,\"_regular_price\",true); \$ws=get_post_meta(\$pid,\"_sale_price\",true);
      \$ep=\$valid?\$sale:\$cur; \$er=\$valid?\$reg:\$cur; \$es=\$valid?\$sale:null;
      if((null!==\$ep&&abs((float)\$wp-\$ep)>0.005)||(null!==\$er&&abs((float)\$wr-\$er)>0.005)||((null===\$es&&\$ws!==\"\")||(null!==\$es&&abs((float)\$ws-\$es)>0.005))) \$bad[]=[\"title\"=>\$row[\"title\"],\"payload\"=>[\$cur,\$reg,\$sale],\"woo\"=>[\$wp,\$wr,\$ws]];
    }
    echo \"FINAL_AUDIT_ACTIVE=\".\$active.\" SALE_COUNT=\".\$sale_count.\" UNAVAILABLE=\".\$unavailable.\" MISMATCH_COUNT=\".count(\$bad).\"\\n\";
    foreach(\$bad as \$b) echo \"MISMATCH=\".wp_json_encode(\$b,JSON_UNESCAPED_UNICODE).\"\\n\";
    if(\$sale_count<1 || \$bad) exit(51);
    \$needle=\"%\".\$wpdb->esc_like(\"Filetes primera\").\"%\";
    \$f=\$wpdb->get_row(\$wpdb->prepare(\"SELECT title,wc_product_id,source_payload FROM {\$st} WHERE supplier_id=%d AND title LIKE %s ORDER BY id DESC LIMIT 1\",$SID,\$needle),ARRAY_A);
    if(!\$f) exit(52);
    \$fp=json_decode((string)\$f[\"source_payload\"],true); \$fid=(int)\$f[\"wc_product_id\"];
    echo \"FILETES_FINAL=\".wp_json_encode([\"payload\"=>[\"price\"=>\$fp[\"price\"]??null,\"regular\"=>\$fp[\"regular_price\"]??null,\"sale\"=>\$fp[\"sale_price\"]??null],\"woo\"=>[\"price\"=>get_post_meta(\$fid,\"_price\",true),\"regular\"=>get_post_meta(\$fid,\"_regular_price\",true),\"sale\"=>get_post_meta(\$fid,\"_sale_price\",true)]],JSON_UNESCAPED_UNICODE).\"\\n\";
  ' --path='$PROD_PATH' --allow-root
")"
printf '%s\n' "$audit"
printf '%s\n' "$audit" | grep -Fq 'MISMATCH_COUNT=0'
printf '%s\n' "$audit" | grep -Fq 'SALE_COUNT='
printf '%s\n' "$audit" | grep -Fq 'FILETES_FINAL='

sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "
  php_bin=\$(command -v php || true)
  [ -n \"\$php_bin\" ] || php_bin=\$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)
  wp_bin=\$(command -v wp)
  \"\$php_bin\" \"\$wp_bin\" cache flush --path='$PROD_PATH' --allow-root >/dev/null 2>&1 || true
"

for f in "${files[@]}"; do
  base="$(basename "$f")"
  sshpass -e ssh $SSH "$STAGING_USER@$STAGING_HOST" "rm -f '/tmp/emdo-010245-${RUN_TAG}-${base}.bak'" || true
done
trap - ERR
echo PRODUCTION_EMDO_PRICE_MISSING_010245_OK
