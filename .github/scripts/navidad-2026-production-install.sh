#!/bin/sh
# Instalacion atomica y acotada a 4 ficheros de la landing de Navidad.
set -eu
raw="${1%/}"
incoming="$2"
dev=""
case "$raw" in
  */wp-content/*) dev="${raw%%/wp-content/*}" ;;
  */wp-content) dev="$(dirname "$raw")" ;;
  *) dev="$raw" ;;
esac
if [ ! -f "$dev/wp-config.php" ]; then
  candidate="$(find /var/www/vhosts "$HOME" -maxdepth 9 -type f -path '*/dev.elmercadodeorigen.com/wp-config.php' 2>/dev/null | head -n 1 || true)"
  [ -n "$candidate" ] || { echo 'No se encontro sitio WordPress de referencia' >&2; exit 11; }
  dev="$(dirname "$candidate")"
fi
prod="$(dirname "$dev")/httpdocs"
[ -f "$prod/wp-config.php" ] || { echo "Ruta de produccion no encontrada" >&2; exit 12; }
php_bin="$(command -v php || true)"
[ -n "$php_bin" ] || php_bin="$(find /opt/plesk/php -maxdepth 3 -type f -path '*/bin/php' 2>/dev/null | sort -Vr | head -n 1)"
wp_bin="$(command -v wp || true)"
[ -n "$wp_bin" ] && [ -x "$php_bin" ] || { echo 'PHP/WP-CLI no disponible' >&2; exit 13; }
siteurl="$("$php_bin" -d memory_limit=512M "$wp_bin" option get siteurl --path="$prod" --skip-plugins --skip-themes --allow-root)"
case "$siteurl" in
  https://www.elmercadodeorigen.com|https://elmercadodeorigen.com|http://www.elmercadodeorigen.com|http://elmercadodeorigen.com) ;;
  *) echo "No es el sitio de produccion esperado: $siteurl" >&2; exit 14 ;;
esac
stylesheet="$("$php_bin" -d memory_limit=512M "$wp_bin" option get stylesheet --path="$prod" --skip-plugins --skip-themes --allow-root)"
[ "$stylesheet" = 'elmercadodeorigen-child' ] || { echo 'Tema inesperado' >&2; exit 15; }
theme="$prod/wp-content/themes/elmercadodeorigen-child"
[ -f "$theme/functions.php" ] || exit 16
for php in navidad-2026-campaign.php navidad-2026-landing.php navidad-2026-mu-loader.php navidad-2026-promo.php; do
  "$php_bin" -l "$incoming/$php" >/dev/null || exit 17
done
test -s "$incoming/navidad-2026.css"
test -s "$incoming/navidad-2026-promo.css"

d1="$theme/inc/navidad-2026-campaign.php"
d2="$theme/templates/navidad-2026-landing.php"
d3="$theme/assets/css/navidad-2026.css"
d4="$prod/wp-content/mu-plugins/emdo-navidad-2026.php"
d5="$theme/inc/navidad-2026-promo.php"
d6="$theme/assets/css/navidad-2026-promo.css"
mkdir -p "$theme/inc" "$theme/templates" "$theme/assets/css" "$prod/wp-content/mu-plugins" "$incoming/backup"
for i in 1 2 3 4 5 6; do
  case "$i" in
    1) dest="$d1" ;;
    2) dest="$d2" ;;
    3) dest="$d3" ;;
    4) dest="$d4" ;;
    5) dest="$d5" ;;
    6) dest="$d6" ;;
  esac
  if [ -f "$dest" ]; then
    cp -p "$dest" "$incoming/backup/$i"
  fi
done

installed=0
rollback() {
  code="$?"
  trap - EXIT HUP INT TERM
  if [ "$code" -ne 0 ] && [ "$installed" -eq 1 ]; then
    echo 'Despliegue incorrecto: restaurando SOLO los archivos Navidad' >&2
    for i in 1 2 3 4 5 6; do
      case "$i" in
    1) dest="$d1" ;;
    2) dest="$d2" ;;
    3) dest="$d3" ;;
    4) dest="$d4" ;;
    5) dest="$d5" ;;
    6) dest="$d6" ;;
  esac
      if [ -f "$incoming/backup/$i" ]; then
        cp -p "$incoming/backup/$i" "$dest"
      else
        rm -f "$dest"
      fi
    done
  fi
  rm -rf "$incoming"
  exit "$code"
}
trap rollback EXIT HUP INT TERM
installed=1
install -m 0644 "$incoming/navidad-2026-campaign.php" "$d1"
install -m 0644 "$incoming/navidad-2026-landing.php" "$d2"
install -m 0644 "$incoming/navidad-2026.css" "$d3"
install -m 0644 "$incoming/navidad-2026-mu-loader.php" "$d4"
install -m 0644 "$incoming/navidad-2026-promo.php" "$d5"
install -m 0644 "$incoming/navidad-2026-promo.css" "$d6"

loaded="$("$php_bin" -d memory_limit=512M "$wp_bin" eval 'echo function_exists("emdo_navidad_2026_route") ? "NAVIDAD_ACTIVE" : "NAVIDAD_MISSING";' --path="$prod" --allow-root 2>/dev/null || true)"
printf 'WP_CLI_MODULE_CHECK=%s\n' "$loaded"
printf '%s' "$loaded" | grep -Fq 'NAVIDAD_ACTIVE'
banners="$("$php_bin" -d memory_limit=512M "$wp_bin" eval 'echo function_exists("emdo_navidad_promo_markup_2026") ? "BANNERS_ACTIVE" : "BANNERS_MISSING";' --path="$prod" --allow-root 2>/dev/null || true)"
printf 'WP_CLI_BANNERS_CHECK=%s\n' "$banners"
printf '%s' "$banners" | grep -Fq 'BANNERS_ACTIVE'
# Purgar la caché PROPIA de portada: transitorio en BD + HTML/CSS estáticos.
# La caché continúa habilitada y se recalienta con la siguiente visita.
"$php_bin" -d memory_limit=512M "$wp_bin" eval '
  if ( ! function_exists("elmercado_flush_home_cache") ) {
    throw new RuntimeException("Falta la función de invalidación de portada");
  }
  elmercado_flush_home_cache();
  echo "EMDO_HOME_CACHE_INVALIDATED\\n";
' --path="$prod" --allow-root
"$php_bin" -d memory_limit=512M "$wp_bin" cache flush --path="$prod" --skip-plugins --allow-root >/dev/null 2>&1 || true
"$php_bin" -d memory_limit=512M "$wp_bin" rocket clean --confirm --path="$prod" --allow-root >/dev/null 2>&1 || true
# Regenerar versiones cacheadas de portada y blog sin tocar contenido/SEO.
rm -f "$prod/wp-content/uploads/elmercado-home-static/index.html" "$prod/wp-content/cache/elmercado-home-static/index.html"
blog_cache="$prod/wp-content/uploads/elmercado-blog-static-v2"
if [ -d "$blog_cache" ]; then
  find "$blog_cache" -maxdepth 1 -type f -name '*.html' -delete
fi
echo 'NAVIDAD_PRODUCTION_FILES_OK (no functions.php or SEO files changed)'
