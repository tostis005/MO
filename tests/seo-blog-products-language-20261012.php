<?php
/** Test that Falang /en/ routes receive English blog product copy. */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ . '/' );

function add_filter( $name, $callback, $priority = 10, $args = 1 ) {}
function add_action( $name, $callback, $priority = 10, $args = 1 ) {}
function add_shortcode( $name, $callback ) {}
function wp_unslash( $value ) { return stripslashes( (string) $value ); }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }

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

echo "OK: public route determines blog language; Falang term labels and fallbacks preserved.\n";
