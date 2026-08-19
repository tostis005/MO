from pathlib import Path
import re

root = Path('.')
connector = root / 'mdo-supplier-sync/connectors/class-mdo-connector-huerta-ana-mary-v2.php'
plugin = root / 'mdo-supplier-sync/mdo-supplier-sync.php'
deploy = root / '.github/workflows/deploy-mdo-supplier-sync-production-20260819.yml'
marker = root / 'mdo-supplier-sync/deploy-version.txt'
helper = root / 'mdo-supplier-sync/includes/class-mdo-huerta-defaults.php'

text = connector.read_text(encoding='utf-8')

# La tienda usa data/... e img/... como rutas relativas a la raíz del dominio.
text = text.replace(
    "~^(?:inicio|noticias|recetas|hortalizas-y-conservas)/~i",
    "~^(?:data|img|inicio|noticias|recetas|hortalizas-y-conservas)/~i",
)

new_description = r'''\tprivate static function description( DOMXPath $xpath, array $json, string $title ): string {
\t\tif ( ! empty( $json['description'] ) ) {
\t\t\t$clean = self::clean_description_text( self::repair_text( wp_strip_all_tags( (string) $json['description'] ) ), $title );
\t\t\tif ( mb_strlen( $clean ) >= 40 ) {
\t\t\t\treturn wpautop( esc_html( $clean ) );
\t\t\t}
\t\t}

\t\t$title_slug = sanitize_title( $title );
\t\tforeach ( $xpath->query( '//h1|//h2|//h3|//h4|//h5' ) ?: array() as $heading ) {
\t\t\tif ( ! $heading instanceof DOMElement || sanitize_title( self::repair_text( (string) $heading->textContent ) ) !== $title_slug ) {
\t\t\t\tcontinue;
\t\t\t}
\t\t\t$container = $heading;
\t\t\tfor ( $i = 0; $i < 8 && $container->parentNode instanceof DOMElement; $i++ ) {
\t\t\t\t$container = $container->parentNode;
\t\t\t\t$text = self::repair_text( (string) $container->textContent );
\t\t\t\tif ( false !== mb_stripos( $text, 'Te interesa' ) ) {
\t\t\t\t\t$clean = self::clean_description_text( $text, $title );
\t\t\t\t\tif ( mb_strlen( $clean ) >= 40 ) {
\t\t\t\t\t\treturn wpautop( esc_html( $clean ) );
\t\t\t\t\t}
\t\t\t\t}
\t\t\t}
\t\t}

\t\t$body  = self::repair_text( self::xpath_text( $xpath, '//body' ) );
\t\t$clean = self::clean_description_text( $body, $title );
\t\treturn mb_strlen( $clean ) >= 40 ? wpautop( esc_html( $clean ) ) : '';
\t}

\tprivate static function clean_description_text( string $text, string $title ): string {
\t\t$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
\t\t$text = str_replace( array( "\\r\\n", "\\r", "\\xC2\\xA0" ), array( "\\n", "\\n", ' ' ), $text );
\t\t$position = mb_stripos( $text, $title );
\t\tif ( false !== $position ) {
\t\t\t$text = mb_substr( $text, $position + mb_strlen( $title ) );
\t\t}
\t\t$cut = null;
\t\tforeach ( array( 'Te interesa', '€/kg', '€/caja', '€/ud', '€/unidad', 'Consejos de conservación:', 'Recetas relacionadas:', 'Seguir Comprando', 'Carrito de la Compra' ) as $stop ) {
\t\t\t$p = mb_stripos( $text, $stop );
\t\t\tif ( false !== $p && ( null === $cut || $p < $cut ) ) {
\t\t\t\t$cut = $p;
\t\t\t}
\t\t}
\t\tif ( null !== $cut ) {
\t\t\t$text = mb_substr( $text, 0, $cut );
\t\t}
\t\t$text = preg_replace( '/[ \\t]+/u', ' ', $text );
\t\t$text = preg_replace( "/\\n[ \\t]+/u", "\\n", (string) $text );
\t\t$text = preg_replace( "/\\n{3,}/u", "\\n\\n", (string) $text );
\t\treturn trim( (string) $text );
\t}
'''
text, n = re.subn(
    r"\tprivate static function description\( DOMXPath \$xpath, array \$json, string \$title \): string \{.*?(?=\n\tprivate static function images\()",
    lambda m: new_description.rstrip(),
    text,
    flags=re.S,
)
assert n == 1, f'description replacement count={n}'

new_images = r'''\tprivate static function images( DOMXPath $xpath, array $json, string $base_url, string $title ): array {
\t\t$images = array();

\t\t// Las fotos reales de producto de esta tienda viven en /data/productos/imagenes/.
\t\t// Las imágenes de recetas/noticias usan /data/documentos/imagenes/ y no deben importarse.
\t\tforeach ( $xpath->query( '//img[@src or @data-src or @data-original or @data-lazy-src or @srcset]' ) ?: array() as $img ) {
\t\t\tif ( ! $img instanceof DOMElement ) {
\t\t\t\tcontinue;
\t\t\t}
\t\t\t$raw_url = (string) ( $img->getAttribute( 'data-original' ) ?: $img->getAttribute( 'data-lazy-src' ) ?: $img->getAttribute( 'data-src' ) ?: $img->getAttribute( 'src' ) );
\t\t\tif ( ! $raw_url && $img->hasAttribute( 'srcset' ) ) {
\t\t\t\t$first_srcset = trim( explode( ',', (string) $img->getAttribute( 'srcset' ) )[0] );
\t\t\t\t$raw_url      = trim( explode( ' ', $first_srcset )[0] );
\t\t\t}
\t\t\t$decoded = html_entity_decode( trim( $raw_url ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
\t\t\tif ( preg_match( '~(?:^|/)data/productos/imagenes/[^/?]+\\.(?:jpe?g|png|webp|gif)(?:\\?|$)~i', $decoded ) ) {
\t\t\t\tself::add_image( $images, $decoded, $base_url );
\t\t\t}
\t\t}

\t\t// Fallbacks estructurados solo si también pertenecen al directorio real de producto.
\t\t$raw = isset( $json['image'] ) ? ( is_array( $json['image'] ) ? $json['image'] : array( $json['image'] ) ) : array();
\t\t$raw[] = self::meta( $xpath, 'property', 'og:image' );
\t\tforeach ( $raw as $image ) {
\t\t\t$image = is_array( $image ) ? ( $image['url'] ?? $image['contentUrl'] ?? '' ) : $image;
\t\t\tif ( preg_match( '~(?:^|/)data/productos/imagenes/~i', (string) $image ) ) {
\t\t\t\tself::add_image( $images, (string) $image, $base_url );
\t\t\t}
\t\t}
\t\treturn array_values( $images );
\t}
'''
text, n = re.subn(
    r"\tprivate static function images\( DOMXPath \$xpath, array \$json, string \$base_url, string \$title \): array \{.*?(?=\n\tprivate static function add_image\()",
    lambda m: new_images.rstrip(),
    text,
    flags=re.S,
)
assert n == 1, f'images replacement count={n}'

new_repair = r'''\tprivate static function repair_text( string $text ): string {
\t\t$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
\t\tfor ( $i = 0; $i < 3 && preg_match( '/[ÃÂâã]/u', $text ); $i++ ) {
\t\t\t$candidate = @mb_convert_encoding( $text, 'Windows-1252', 'UTF-8' );
\t\t\tif ( ! is_string( $candidate ) || '' === $candidate || ( function_exists( 'mb_check_encoding' ) && ! mb_check_encoding( $candidate, 'UTF-8' ) ) || $candidate === $text ) {
\t\t\t\tbreak;
\t\t\t}
\t\t\t$text = $candidate;
\t\t}
\t\treturn strtr(
\t\t\t$text,
\t\t\tarray(
\t\t\t\t'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú', 'Ã±' => 'ñ',
\t\t\t\t'Ã' => 'Á', 'Ã‰' => 'É', 'Ã' => 'Í', 'Ã“' => 'Ó', 'Ãš' => 'Ú', 'Ã‘' => 'Ñ',
\t\t\t\t'Â¿' => '¿', 'Â¡' => '¡', 'Âº' => 'º', 'Âª' => 'ª', 'Â€' => '€', 'Â' => '',
\t\t\t)
\t\t);
\t}
'''
text, n = re.subn(
    r"\tprivate static function repair_text\( string \$text \): string \{.*?(?=\n\})",
    lambda m: new_repair.rstrip(),
    text,
    flags=re.S,
)
assert n == 1, f'repair replacement count={n}'
connector.write_text(text, encoding='utf-8')

helper.write_text(r'''<?php

if ( ! defined( 'ABSPATH' ) ) {
\texit;
}

final class MDO_Huerta_Defaults {
\tprivate const CATEGORY_SLUG = 'hortalizas-verduras';

\tpublic static function init(): void {
\t\tadd_action( 'save_post_product', array( __CLASS__, 'on_product_save' ), 30, 3 );
\t\tadd_action( 'added_post_meta', array( __CLASS__, 'on_post_meta' ), 10, 4 );
\t\tadd_action( 'updated_post_meta', array( __CLASS__, 'on_post_meta' ), 10, 4 );
\t\tadd_filter( 'http_request_args', array( __CLASS__, 'image_request_args' ), 10, 2 );
\t}

\tpublic static function on_product_save( int $post_id, WP_Post $post, bool $update ): void {
\t\tif ( wp_is_post_revision( $post_id ) || 'product' !== $post->post_type ) {
\t\t\treturn;
\t\t}
\t\tself::assign_category_if_huerta( $post_id );
\t}

\tpublic static function on_post_meta( int $meta_id, int $object_id, string $meta_key, mixed $meta_value ): void {
\t\tif ( '_emdo_supplier_id' !== $meta_key || 'product' !== get_post_type( $object_id ) ) {
\t\t\treturn;
\t\t}
\t\tself::assign_category_if_huerta( $object_id );
\t}

\tpublic static function image_request_args( array $args, string $url ): array {
\t\t$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
\t\t$path = (string) wp_parse_url( $url, PHP_URL_PATH );
\t\tif ( ! in_array( $host, array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' ), true ) || ! str_starts_with( $path, '/data/productos/imagenes/' ) ) {
\t\t\treturn $args;
\t\t}
\t\t$args['timeout'] = max( 30, (int) ( $args['timeout'] ?? 0 ) );
\t\t$args['user-agent'] = 'Mozilla/5.0 (compatible; EMDO/' . MDO_SUPPLIER_SYNC_VERSION . '; +https://www.elmercadodeorigen.com/)';
\t\t$headers = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : array();
\t\t$headers['Referer'] = 'https://www.lahuertadeanamary.com/';
\t\t$headers['Accept'] = 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8';
\t\t$args['headers'] = $headers;
\t\treturn $args;
\t}

\tprivate static function assign_category_if_huerta( int $product_id ): void {
\t\t$supplier_id = absint( get_post_meta( $product_id, '_emdo_supplier_id', true ) );
\t\tif ( ! $supplier_id ) {
\t\t\treturn;
\t\t}
\t\t$supplier = MDO_Supplier_Repository::find( $supplier_id );
\t\tif ( ! $supplier || 'la-huerta-ana-mary' !== (string) ( $supplier['connector'] ?? '' ) ) {
\t\t\treturn;
\t\t}
\t\t$term = get_term_by( 'slug', self::CATEGORY_SLUG, 'product_cat' );
\t\tif ( ! $term || is_wp_error( $term ) ) {
\t\t\treturn;
\t\t}
\t\twp_set_object_terms( $product_id, array( (int) $term->term_id ), 'product_cat', true );
\t}
}
'''.replace('\\t', '\t'), encoding='utf-8')

ptext = plugin.read_text(encoding='utf-8')
ptext = ptext.replace('Version: 1.0.15', 'Version: 1.0.16')
ptext = ptext.replace("define( 'MDO_SUPPLIER_SYNC_VERSION', '1.0.15' );", "define( 'MDO_SUPPLIER_SYNC_VERSION', '1.0.16' );")
needle = "require_once MDO_SUPPLIER_SYNC_PATH . 'connectors/class-mdo-connector-huerta-ana-mary-v2.php';"
assert needle in ptext
ptext = ptext.replace(needle, needle + "\nrequire_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-huerta-defaults.php';")
needle2 = "\t\tMDO_Minimum_Order::init();"
assert needle2 in ptext
ptext = ptext.replace(needle2, "\t\tMDO_Huerta_Defaults::init();\n" + needle2)
plugin.write_text(ptext, encoding='utf-8')

dtext = deploy.read_text(encoding='utf-8').replace('1.0.15', '1.0.16')
deploy.write_text(dtext, encoding='utf-8')
marker.write_text('MDO Supplier Sync 1.0.16\nProduction deployment trigger: 2026-08-19\nConnector: La Huerta de Ana Mary\n', encoding='utf-8')
