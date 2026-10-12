<?php
/** Test that Falang /en/ routes receive English blog product copy. */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ . '/' );

function add_filter( $name, $callback, $priority = 10, $args = 1 ) {}
function add_action( $name, $callback, $priority = 10, $args = 1 ) {}
function add_shortcode( $name, $callback ) {}
function wp_unslash( $value ) { return stripslashes( (string) $value ); }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }

function trailingslashit( $value ) { return rtrim( (string) $value, '/' ) . '/'; }
$GLOBALS['sku_to_product'] = array(
	'/filetes-magro-awr'     => 501,
	'/magro-ragu-ternera-az' => 502,
	'29325'                 => 503,
);
$GLOBALS['product_status'] = array( 501 => 'publish', 502 => 'publish', 503 => 'publish' );
$GLOBALS['product_visible'] = array( 501 => true, 502 => true, 503 => false );
$GLOBALS['product_stock'] = array( 501 => true, 502 => true, 503 => true );
function wc_get_product_id_by_sku( $sku ) { return $GLOBALS['sku_to_product'][ $sku ] ?? 0; }
function get_post_status( $id ) { return $GLOBALS['product_status'][ $id ] ?? ''; }
class MockWCProduct {
	private int $id;
	public function __construct( int $id ) { $this->id = $id; }
	public function is_visible(): bool { return (bool) ( $GLOBALS['product_visible'][ $this->id ] ?? false ); }
	public function is_in_stock(): bool { return (bool) ( $GLOBALS['product_stock'][ $this->id ] ?? false ); }
}
function wc_get_product( $id ) { return isset( $GLOBALS['product_status'][ $id ] ) ? new MockWCProduct( (int) $id ) : null; }


$GLOBALS['mock_locale'] = 'es_ES';
$GLOBALS['mock_falang_names'] = array();
function determine_locale() { return $GLOBALS['mock_locale']; }
function get_locale() { return $GLOBALS['mock_locale']; }
function get_term_meta( $id, $key, $single ) {
	return $GLOBALS['mock_falang_names'][ $id ][ $key ] ?? '';
}
class WP_Term {
	public int $term_id;
	public string $name;
	public function __construct( int $id, string $name ) {
		$this->term_id = $id;
		$this->name = $name;
	}
}

require __DIR__ . '/../elmercadodeorigen-child/inc/blog-product-category-more-010267.php';

function check_english( string $url, bool $expected ): void {
	$_SERVER['REQUEST_URI'] = $url;
	$actual = elmercado_blog_products_is_english_010277();
	if ( $actual !== $expected ) {
		fwrite( STDERR, 'FAIL: ' . $url . ', expected ' . (int) $expected . ', got ' . (int) $actual . "\n" );
		exit( 1 );
	}
}

check_english( '/en/why-meat-releases-water-in-pan-how-to-stop-it/', true );
check_english( '/en/', true );
check_english( '/en?preview=1', true );
check_english( '/producto/garbanzo-pedrosillano/', false );
check_english( '/', false );
check_english( '/enquiries/', false );
check_english( '/es/legumbres/', false );
check_english( '/pt/legumes/', false );

// Never switch a Spanish URL to EN because WordPress/Falang current locale differs.
$GLOBALS['mock_locale'] = 'en_US';
check_english( '/hay-que-poner-lentejas-en-remojo-cuanto-tiempo/', false );

// Legacy non-HTTP rendering falls back to the locale.
unset( $_SERVER['REQUEST_URI'] );
if ( ! elmercado_blog_products_is_english_010277() ) {
	fwrite( STDERR, "FAIL: missing URL must fall back to English locale.\n" );
	exit( 1 );
}

$term = new WP_Term( 13, 'carnes' );
$GLOBALS['mock_locale'] = 'es_ES';
$_SERVER['REQUEST_URI'] = '/en/why-meat-releases-water-in-pan-how-to-stop-it/';
if ( 'carnes' !== elmercado_blog_product_term_name_010277( $term ) ) {
	fwrite( STDERR, "FAIL: untranslated category fallback.\n" );
	exit( 1 );
}
$GLOBALS['mock_falang_names'][13] = array( '_en_US_published' => '1', '_en_US_name' => 'Meat' );
if ( 'Meat' !== elmercado_blog_product_term_name_010277( $term ) ) {
	fwrite( STDERR, "FAIL: Falang product category name.\n" );
	exit( 1 );
}
$_SERVER['REQUEST_URI'] = '/garbanzos-tiempo-remojo-cuanto-tardan-cocerse/';
if ( 'carnes' !== elmercado_blog_product_term_name_010277( $term ) ) {
	fwrite( STDERR, "FAIL: Spanish category name must stay Spanish.\n" );
	exit( 1 );
}


// Targeted merchandising must never fall back to popular duck products.
$_SERVER['REQUEST_URI'] = '/en/how-much-iron-in-beef/?utm_source=test';
$beef_ids = elmercado_blog_iron_beef_product_ids_010277();
if ( $beef_ids !== array( 501, 502 ) ) {
	fwrite( STDERR, "FAIL: expected two currently visible and in-stock beef SKUs.\n" );
	exit( 1 );
}
$GLOBALS['product_stock'][502] = false;
$beef_ids = elmercado_blog_iron_beef_product_ids_010277();
if ( $beef_ids !== array( 501 ) ) {
	fwrite( STDERR, "FAIL: out-of-stock product was included.\n" );
	exit( 1 );
}
$GLOBALS['product_visible'][501] = false;
if ( elmercado_blog_iron_beef_product_ids_010277() !== array() ) {
	fwrite( STDERR, "FAIL: no eligible beef products should return an empty list.\n" );
	exit( 1 );
}
$_SERVER['REQUEST_URI'] = '/en/lean-beef-what-it-is-how-to-cook-tender/';
if ( null !== elmercado_blog_iron_beef_product_ids_010277() ) {
	fwrite( STDERR, "FAIL: unrelated beef guide must keep its original product selection.\n" );
	exit( 1 );
}
$_SERVER['REQUEST_URI'] = '/how-much-iron-in-beef/';
if ( null !== elmercado_blog_iron_beef_product_ids_010277() ) {
	fwrite( STDERR, "FAIL: Spanish/unprefixed page must remain unchanged.\n" );
	exit( 1 );
}

echo "OK: public route determines blog language; Falang term labels preserved; curated beef SKUs and stock visibility verified.\n";
