<?php
/**
 * Fixture del breadcrumb de producto (sin cargar WordPress).
 * Ejecutar: php tests/product-vendor-breadcrumb-20261021.php
 */
declare(strict_types=1);

define( 'ABSPATH', __DIR__ );
function add_action( ...$args ): void {}
function absint( $value ): int { return abs( (int) $value ); }
function wp_strip_all_tags( string $text ): string { return strip_tags( $text ); }
function wp_unslash( string $text ): string { return stripslashes( $text ); }
function wp_parse_url( string $url, int $component ): string|false|null { return parse_url( $url, $component ); }
function is_admin(): bool { return false; }
function is_product(): bool { return true; }
function get_queried_object_id(): int { return 120; }
function get_post_type( int $product_id ): string { return 120 === $product_id ? 'product' : 'post'; }
function get_the_title( int $product_id ): string { return 'Paleta de bellota 100% Ibérica (brida negra)'; }
function wc_get_page_permalink( string $page ): string { return 'https://www.elmercadodeorigen.com/tienda/'; }
function wcfm_get_vendor_id_by_post( int $product_id ): int { return $GLOBALS['test_vendor_id']; }
function get_post_field( string $field, int $product_id ): int { return $GLOBALS['test_author_id']; }
function wcfm_is_vendor( int $user_id ): bool { return 42 === $user_id; }
function wcfmmp_get_store_url( int $vendor_id ): string {
	return $vendor_id > 0 ? 'https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara/' : '';
}
function wcfm_get_vendor_store_name( int $vendor_id ): string { return 'Hidalgo de la Jara'; }
class WP_User { public string $display_name = 'Hidalgo de la Jara'; }
function get_userdata( int $vendor_id ): WP_User|false { return new WP_User(); }
function get_locale(): string { return 'es_ES'; }
function esc_attr( string $value ): string { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( string $value ): string { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( string $value ): string { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }

require dirname( __DIR__ ) . '/mu-plugins/mdo-product-vendor-breadcrumb-20261021.php';

function assert_breadcrumb( bool $ok, string $label ): void {
	if ( ! $ok ) {
		fwrite( STDERR, 'FAIL: ' . $label . PHP_EOL );
		exit( 1 );
	}
}
$GLOBALS['test_vendor_id'] = 23;
$GLOBALS['test_author_id'] = 100;
$_SERVER['REQUEST_URI'] = '/producto/paleta-de-bellota-100-iberico/';

$data = emdo_product_breadcrumb_data_20261021( 120 );
$html = emdo_product_breadcrumb_html_20261021( $data );
assert_breadcrumb( $data['shop_name'] === 'Tienda', 'nombre tienda en español' );
assert_breadcrumb( $data['vendor_name'] === 'Hidalgo de la Jara', 'nombre WCFM correcto' );
assert_breadcrumb( substr_count( $html, '<li>' ) === 3, 'tres niveles' );
assert_breadcrumb( substr_count( $html, '<a href=' ) === 2, 'solo tienda y productor enlazados' );
assert_breadcrumb( str_contains( $html, 'href="https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara/"' ), 'enlace real del vendedor' );
assert_breadcrumb( str_contains( $html, '<span aria-current="page">Paleta de bellota' ), 'producto actual no enlazado' );
assert_breadcrumb( strpos( $html, 'Tienda' ) < strpos( $html, 'Hidalgo de la Jara' ) && strpos( $html, 'Hidalgo de la Jara' ) < strpos( $html, 'Paleta de bellota' ), 'orden correcto' );

$_SERVER['REQUEST_URI'] = '/en/product/iberian-shoulder/';
$en = emdo_product_breadcrumb_data_20261021( 120 );
assert_breadcrumb( $en['shop_name'] === 'Shop' && $en['aria_label'] === 'Breadcrumb', 'texto inglés' );

$GLOBALS['test_vendor_id'] = 0;
$GLOBALS['test_author_id'] = 42;
$fallback = emdo_product_breadcrumb_data_20261021( 120 );
assert_breadcrumb( $fallback['vendor_name'] === 'Hidalgo de la Jara', 'productor propietario de WCFM como respaldo' );

$GLOBALS['test_author_id'] = 100;
$unknown = emdo_product_breadcrumb_html_20261021( emdo_product_breadcrumb_data_20261021( 120 ) );
assert_breadcrumb( substr_count( $unknown, '<li>' ) === 2, 'sin inventar tienda para usuario no productor' );
assert_breadcrumb( emdo_product_breadcrumb_data_20261021( 1 ) === array(), 'no se muestra en artículos' );

$source = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/mdo-product-vendor-breadcrumb-20261021.php' );
assert_breadcrumb( str_contains( $source, 'max-width: 1280px;' ), 'desktop centrado y contenido acotado' );
assert_breadcrumb( str_contains( $source, 'margin: 0 auto clamp(14px, 1.8vw, 22px);' ), 'márgenes laterales automáticos en escritorio' );
assert_breadcrumb( str_contains( $source, 'padding: clamp(16px, 1.6vw, 22px) clamp(24px, 2.8vw, 42px);' ), 'espacios interiores escritorio vertical y horizontal' );
assert_breadcrumb( str_contains( $source, 'padding: 15px 24px 16px;' ), 'móvil: al menos 24px a cada lado y espacio arriba' );

echo "PRODUCT_VENDOR_BREADCRUMB_OK 15 assertions\n";
