<?php
/** Safe fallback for two EN category hubs, without overwriting Falang. */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ . '/' );

function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function wp_unslash( $value ) { return stripslashes( (string) $value ); }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
$GLOBALS['term_flags'] = array();
function get_term_meta( $id, $key, $single ) {
	return $GLOBALS['term_flags'][ (int) $id ][ $key ] ?? '';
}
class WP_Term {
	public int $term_id;
	public string $slug;
	public string $taxonomy;
	public string $name;
	public function __construct( int $id, string $slug, string $name, string $taxonomy = 'category' ) {
		$this->term_id = $id;
		$this->slug = $slug;
		$this->name = $name;
		$this->taxonomy = $taxonomy;
	}
}
require __DIR__ . '/../elmercadodeorigen-child/inc/blog-category-english-copy-20261012.php';

function must( $condition, string $message ): void {
	if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $message . "\n" ); exit( 1 ); }
}
$duck = new WP_Term( 1, 'pato', 'Pato' );
$foie = new WP_Term( 2, 'foie-pates-untables', 'Foie, patés y untables' );
$other = new WP_Term( 3, 'carnes', 'Carnes' );
$_SERVER['REQUEST_URI'] = '/en/category/carnes/pato/';
must( elmercado_blog_category_label_20261012( $duck ) === 'Duck', 'Duck label in EN' );
must( elmercado_blog_category_english_fallback_20261012( $duck )['description'] !== '', 'Duck EN description' );
must( elmercado_blog_category_label_20261012( $foie ) === 'Foie Gras, Pâtés and Spreads', 'Foie EN label' );
must( null === elmercado_blog_category_english_fallback_20261012( $other ), 'Other categories unmodified' );

$_SERVER['REQUEST_URI'] = '/category/carnes/pato/';
must( elmercado_blog_category_label_20261012( $duck ) === 'Pato', 'Spanish label untouched' );
must( null === elmercado_blog_category_english_fallback_20261012( $foie ), 'Spanish copy untouched' );

$_SERVER['REQUEST_URI'] = '/en/category/carnes/pato/';
$GLOBALS['term_flags'][1] = array( '_en_US_published' => '1' );
must( null === elmercado_blog_category_english_fallback_20261012( $duck ), 'Published Falang translation wins' );
$GLOBALS['term_flags'][1] = array();
$translated = new WP_Term( 1, 'pato', 'Duck' );
must( null === elmercado_blog_category_english_fallback_20261012( $translated ), 'Already translated label wins' );
$product = new WP_Term( 1, 'pato', 'Pato', 'product_cat' );
must( null === elmercado_blog_category_english_fallback_20261012( $product ), 'Product taxonomy untouched' );

$_SERVER['REQUEST_URI'] = '/es/category/carnes/pato/';
must( ! elmercado_blog_en_category_request_20261012(), 'Other language untouched' );
$_SERVER['REQUEST_URI'] = '/enquiries/';
must( ! elmercado_blog_en_category_request_20261012(), 'Do not confuse en prefix' );
$_SERVER['REQUEST_URI'] = '/en/category/carnes/pato/';
$original = '<p>Enlaces originales <a href="/en/article-one/">primer artículo</a> y <a href="/en/article-two/">segundo artículo</a>.</p>';
$new_intro = elmercado_blog_category_intro_20261012( $duck, $original );
must( str_contains( $new_intro, 'Explore guides to duck meat' ), 'English summary shown' );
must( str_contains( $new_intro, 'lang="es"' ), 'Spanish original directory labelled as such' );
must( str_contains( $new_intro, 'href="/en/article-one/"' ) && str_contains( $new_intro, 'href="/en/article-two/"' ), 'All original internal links must survive' );
must( elmercado_blog_category_intro_20261012( $duck, 'No linked directory') !== 'No linked directory', 'Plain text gets a concise English summary' );

$_SERVER['REQUEST_URI'] = '/category/carnes/pato/';
must( elmercado_blog_category_intro_20261012( $duck, $original ) === $original, 'Spanish original directory never modified' );
$_SERVER['REQUEST_URI'] = '/en/category/carnes/pato/';
$GLOBALS['term_flags'][1] = array( '_en_US_published' => '1' );
must( elmercado_blog_category_intro_20261012( $duck, $original ) === $original, 'Published Falang intro remains authoritative' );

echo "OK: EN category fallback labels and copy scoped, Spanish/Falang/product cats untouched.\n";
