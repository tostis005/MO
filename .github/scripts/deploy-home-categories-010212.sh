#!/usr/bin/env bash
set -Eeuo pipefail

: "${STAGING_HOST:?Missing STAGING_HOST}"
: "${STAGING_USER:?Missing STAGING_USER}"
: "${STAGING_PASSWORD:?Missing STAGING_PASSWORD}"
: "${STAGING_REMOTE_PATH:?Missing STAGING_REMOTE_PATH}"
: "${GITHUB_ENV:?Missing GITHUB_ENV}"

EXPECTED_VERSION="${EXPECTED_VERSION:-0.10.212}"
PORT="${STAGING_PORT:-22}"
SSH_OPTIONS="-o BatchMode=no -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o StrictHostKeyChecking=yes -p $PORT"
REMOTE_TMP="/tmp/elmercado-prod-home-010212-${GITHUB_RUN_ID:-manual}"
BACKUP_NAME="$(date -u +%Y%m%dT%H%M%SZ)-before-home-010212-${GITHUB_SHA:0:10}"

export SSHPASS="$STAGING_PASSWORD"

test -f elmercadodeorigen-child/functions.php
test -f elmercadodeorigen-child/style.css
test -f elmercadodeorigen-child/dropins/advanced-cache.php
test -f elmercadodeorigen-child/inc/home-category-visibility-010212.php
grep -q "ELMERCADO_THEME_VERSION', '$EXPECTED_VERSION'" elmercadodeorigen-child/functions.php
grep -Eq '^Version:[[:space:]]*0\.10\.212$' elmercadodeorigen-child/style.css
find elmercadodeorigen-child -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null

sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "rm -rf '$REMOTE_TMP' && mkdir -p '$REMOTE_TMP/theme'"
sshpass -e rsync -az --delete --exclude='.DS_Store' --exclude='*.map' -e "ssh $SSH_OPTIONS" elmercadodeorigen-child/ "$STAGING_USER@$STAGING_HOST:$REMOTE_TMP/theme/"

OUT="$(sshpass -e ssh $SSH_OPTIONS "$STAGING_USER@$STAGING_HOST" "sh -s -- '$STAGING_REMOTE_PATH' '$REMOTE_TMP/theme' '${GITHUB_SHA:-manual}' '$BACKUP_NAME' '$EXPECTED_VERSION'" <<'REMOTE'
set -eu
raw="${1%/}"
incoming="$2"
commit="$3"
backup_name="$4"
expected="$5"

case "$raw" in
  */wp-content/*) dev="${raw%%/wp-content/*}" ;;
  */wp-content) dev="$(dirname "$raw")" ;;
  *) dev="$raw" ;;
esac

if [ ! -f "$dev/wp-config.php" ]; then
  cfg="$(find "$HOME" /var/www /home -maxdepth 9 -type f -path '*/dev.elmercadodeorigen.com/wp-config.php' 2>/dev/null | head -n1 || true)"
  [ -n "$cfg" ] || { echo 'Cannot resolve development root.' >&2; exit 1; }
  dev="$(dirname "$cfg")"
fi

prod="$(dirname "$dev")/httpdocs"
[ -f "$prod/wp-config.php" ] && [ -d "$prod/wp-content/themes" ] || { echo 'Production WordPress path invalid.' >&2; exit 1; }

php_bin="$(command -v php || true)"
[ -n "$php_bin" ] || php_bin="$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n1)"
wp_bin="$(command -v wp || true)"
[ -x "$php_bin" ] && [ -n "$wp_bin" ] || { echo 'PHP/WP-CLI unavailable.' >&2; exit 1; }

siteurl="$("$php_bin" -d memory_limit=512M "$wp_bin" option get siteurl --path="$prod" --allow-root 2>/dev/null || true)"
case "$siteurl" in
  https://elmercadodeorigen.com*|http://elmercadodeorigen.com*|https://www.elmercadodeorigen.com*|http://www.elmercadodeorigen.com*) ;;
  *) echo "Unexpected production siteurl=$siteurl" >&2; exit 1 ;;
esac

find "$incoming" -type f -name '*.php' -print0 | xargs -0 -n1 "$php_bin" -l >/dev/null
grep -q "ELMERCADO_THEME_VERSION', '$expected'" "$incoming/functions.php"
grep -Eq '^Version:[[:space:]]*0\.10\.212$' "$incoming/style.css"

parent="$prod/wp-content/themes"
target="$parent/elmercadodeorigen-child"
dropin="$prod/wp-content/advanced-cache.php"
[ -d "$target" ] || { echo 'Existing production child theme missing.' >&2; exit 1; }
if [ -f "$dropin" ] && ! grep -q 'ELMERCADO_EARLY_HOME_CACHE' "$dropin"; then
  echo 'Foreign advanced-cache.php found; refusing to overwrite.' >&2
  exit 1
fi

active_before="$("$php_bin" -d memory_limit=512M "$wp_bin" option get stylesheet --path="$prod" --skip-plugins --skip-themes --allow-root)"
version_before="$(grep -o "ELMERCADO_THEME_VERSION', '[^']*" "$target/functions.php" | head -n1 | sed "s/.*'//" || true)"

backup_dir="$HOME/.elmercado-rollbacks/elmercadodeorigen.com/$backup_name"
mkdir -p "$backup_dir"
tar -czf "$backup_dir/theme.tar.gz" -C "$parent" elmercadodeorigen-child
(cd "$backup_dir" && sha256sum theme.tar.gz > theme.tar.gz.sha256 && sha256sum -c theme.tar.gz.sha256)
if [ -f "$dropin" ]; then
  cp "$dropin" "$backup_dir/advanced-cache.php"
  printf 'present\n' > "$backup_dir/advanced-cache-state.txt"
else
  printf 'absent\n' > "$backup_dir/advanced-cache-state.txt"
fi
printf '%s\n' "$active_before" > "$backup_dir/active-stylesheet.txt"
printf '%s\n' "${version_before:-unknown}" > "$backup_dir/theme-version.txt"
printf '%s\n' "$siteurl" > "$backup_dir/siteurl.txt"
printf '%s\n' "$commit" > "$backup_dir/release-sha.txt"

old="$parent/.elmercadodeorigen-child.predeploy-$commit"
rm -rf "$old"
swapped=0
rollback_server() {
  code=$?
  if [ "$swapped" -eq 1 ] && [ "$code" -ne 0 ]; then
    echo 'Server-side validation failed; restoring previous production release.' >&2
    rm -rf "$target"
    [ ! -d "$old" ] || mv "$old" "$target"
    if [ "$(cat "$backup_dir/advanced-cache-state.txt" 2>/dev/null || echo absent)" = 'present' ] && [ -f "$backup_dir/advanced-cache.php" ]; then
      cp "$backup_dir/advanced-cache.php" "$dropin"
    else
      rm -f "$dropin"
    fi
    rm -f "$prod/wp-content/uploads/elmercado-home-static/index.html" "$prod/wp-content/cache/elmercado-home-static/index.html"
    rm -f "$prod/wp-content/uploads/elmercado-home-static"/home-deferred-*.css
    "$php_bin" -d memory_limit=512M "$wp_bin" theme activate "$active_before" --path="$prod" --skip-plugins --allow-root >/dev/null 2>&1 || true
    "$php_bin" -d memory_limit=512M "$wp_bin" cache flush --path="$prod" --skip-plugins --allow-root >/dev/null 2>&1 || true
  fi
  rm -rf "$incoming"
  exit "$code"
}
trap rollback_server EXIT HUP INT TERM

mv "$target" "$old"
mv "$incoming" "$target"
swapped=1
find "$target" -type f -name '*.php' -print0 | xargs -0 -n1 "$php_bin" -l >/dev/null
cp "$target/dropins/advanced-cache.php" "$dropin"
chmod 0644 "$dropin"
grep -q 'ELMERCADO_EARLY_HOME_CACHE' "$dropin"
rm -f "$prod/wp-content/uploads/elmercado-home-static/index.html" "$prod/wp-content/cache/elmercado-home-static/index.html"
rm -f "$prod/wp-content/uploads/elmercado-home-static"/home-deferred-*.css

"$php_bin" -d memory_limit=512M "$wp_bin" theme activate elmercadodeorigen-child --path="$prod" --skip-plugins --allow-root >/dev/null
active_after="$("$php_bin" -d memory_limit=512M "$wp_bin" option get stylesheet --path="$prod" --skip-plugins --skip-themes --allow-root)"
[ "$active_after" = 'elmercadodeorigen-child' ] || { echo "Unexpected active stylesheet $active_after" >&2; exit 1; }
runtime="$("$php_bin" -d memory_limit=512M "$wp_bin" eval 'echo defined("ELMERCADO_THEME_VERSION") ? ELMERCADO_THEME_VERSION : "missing";' --path="$prod" --skip-plugins --allow-root)"
[ "$runtime" = "$expected" ] || { echo "CLI runtime $runtime != $expected" >&2; exit 1; }
style_version="$(grep -o '^Version:[[:space:]]*[^[:space:]]*' "$target/style.css" | awk '{print $2}')"
[ "$style_version" = "$expected" ] || { echo "style.css $style_version != $expected" >&2; exit 1; }
"$php_bin" -d memory_limit=512M "$wp_bin" cache flush --path="$prod" --skip-plugins --allow-root >/dev/null 2>&1 || true
"$php_bin" -d memory_limit=512M "$wp_bin" eval 'if(function_exists("elmercado_flush_home_cache")) elmercado_flush_home_cache();' --path="$prod" --skip-plugins --allow-root >/dev/null 2>&1 || true
rm -f "$prod/wp-content/uploads/elmercado-home-static/index.html"
rm -f "$prod/wp-content/uploads/elmercado-home-static"/home-deferred-*.css

COUNTS_OUT="$("$php_bin" -d memory_limit=512M "$wp_bin" eval '
if(!function_exists("elmercado_home_public_category_count_010212")){fwrite(STDERR,"Missing Home public-count helper.\n");exit(2);}
$admins=get_users(array("role"=>"administrator","number"=>1,"fields"=>"ID")); if(!$admins){fwrite(STDERR,"No administrator.\n");exit(3);} wp_set_current_user((int)$admins[0]);
$disabled=function_exists("elmercado_wcfm_disabled_vendor_ids_010210") ? array_values(array_filter(array_map("absint",elmercado_wcfm_disabled_vendor_ids_010210()))) : array();
$exclude=array_filter(array((int)get_option("default_product_cat")));
$terms=get_terms(array("taxonomy"=>"product_cat","hide_empty"=>true,"number"=>6,"orderby"=>"count","order"=>"DESC","exclude"=>$exclude));
$rows=array();
foreach((array)$terms as $term){
  if(!($term instanceof WP_Term)) continue;
  $link=get_term_link($term); if(is_wp_error($link)) continue;
  $base=array("post_type"=>"product","post_status"=>"publish","fields"=>"ids","posts_per_page"=>1,"no_found_rows"=>false,"ignore_sticky_posts"=>true,"tax_query"=>array(array("taxonomy"=>"product_cat","field"=>"term_id","terms"=>array((int)$term->term_id),"include_children"=>true)));
  $all=new WP_Query($base); $published=(int)$all->found_posts;
  $disabled_count=0;
  if($disabled){$d=$base;$d["author__in"]=$disabled;$dq=new WP_Query($d);$disabled_count=(int)$dq->found_posts;}
  $public=(int)elmercado_home_public_category_count_010212((int)$term->term_id);
  if($public!==max(0,$published-$disabled_count)){fwrite(STDERR,"Count identity failed for {$term->name}: published={$published} disabled={$disabled_count} public={$public}\n");exit(4);}
  $rows[]=array("id"=>(int)$term->term_id,"name"=>$term->name,"url"=>$link,"legacy"=>(int)$term->count,"published"=>$published,"disabled"=>$disabled_count,"public"=>$public);
}
echo "__COUNTS__=".base64_encode(wp_json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))."\n";
' --path="$prod" --allow-root)"
counts_b64="$(printf '%s\n' "$COUNTS_OUT" | sed -n 's/^__COUNTS__=//p' | tail -n1)"
[ -n "$counts_b64" ] || { echo 'Production count payload missing.' >&2; exit 1; }

swapped=0
trap - EXIT HUP INT TERM
printf '__SITEURL__=%s\n' "$siteurl"
printf '__PROD__=%s\n' "$prod"
printf '__BEFORE__=%s\n' "${version_before:-unknown}"
printf '__AFTER__=%s\n' "$runtime"
printf '__COUNTS_B64__=%s\n' "$counts_b64"
printf '__OLD__=%s\n' "$old"
printf '__BACKUP_DIR__=%s\n' "$backup_dir"
REMOTE
)"

printf '%s\n' "$OUT"
SITEURL="$(printf '%s\n' "$OUT" | sed -n 's/^__SITEURL__=//p' | tail -n1)"
PROD="$(printf '%s\n' "$OUT" | sed -n 's/^__PROD__=//p' | tail -n1)"
BEFORE="$(printf '%s\n' "$OUT" | sed -n 's/^__BEFORE__=//p' | tail -n1)"
AFTER="$(printf '%s\n' "$OUT" | sed -n 's/^__AFTER__=//p' | tail -n1)"
COUNTS_B64="$(printf '%s\n' "$OUT" | sed -n 's/^__COUNTS_B64__=//p' | tail -n1)"
OLD="$(printf '%s\n' "$OUT" | sed -n 's/^__OLD__=//p' | tail -n1)"
BACKUP_DIR="$(printf '%s\n' "$OUT" | sed -n 's/^__BACKUP_DIR__=//p' | tail -n1)"
: "${SITEURL:?Missing production site URL}"
: "${PROD:?Missing production path}"
: "${COUNTS_B64:?Missing count payload}"
: "${OLD:?Missing predeploy path}"
: "${BACKUP_DIR:?Missing rollback backup path}"
COUNTS_JSON="$(printf '%s' "$COUNTS_B64" | base64 -d)"

{
  echo "PRODUCTION_SITEURL=$SITEURL"
  echo "PRODUCTION_WP_PATH=$PROD"
  echo "PREVIOUS_VERSION=$BEFORE"
  echo "CURRENT_VERSION=$AFTER"
  echo "COUNTS_JSON=$COUNTS_JSON"
  echo "PREDEPLOY_OLD_PATH=$OLD"
  echo "ROLLBACK_BACKUP_DIR=$BACKUP_DIR"
  echo "REMOTE_TMP=$REMOTE_TMP"
  echo "BACKUP_NAME=$BACKUP_NAME"
} >> "$GITHUB_ENV"

printf '%s\n' "$COUNTS_JSON" | jq .
echo "SERVER_HOME_010212_OK before=$BEFORE after=$AFTER"
