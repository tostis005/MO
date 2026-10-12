<?php
/**
 * Full 60-URL integration smoke test, with all three separate GSC modules
 * loaded together as they would be when the child theme is deployed.
 */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ );
$GLOBALS['seo_test_path'] = '/';
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
function is_admin(): bool { return false; }
function is_singular( $type ): bool { return 'post' === $type; }
function wp_unslash( $value ) { return stripslashes( (string) $value ); }
function wp_parse_url( $uri, $component ) { return parse_url( $uri, $component ); }
function elmercado_gsc_current_path_010313(): string { return $GLOBALS['seo_test_path']; }

$root = __DIR__ . '/../elmercadodeorigen-child/inc/';
require $root . 'seo-gsc-batch-20-20261012.php';
require $root . 'seo-gsc-english-batch2-20261012.php';
require $root . 'seo-gsc-english-batch3-20261012.php';

function ensure_integration( bool $valid, string $message ): void {
	if ( ! $valid ) { fwrite( STDERR, 'FAIL: ' . $message . "\n" ); exit( 1 ); }
}
function utf8_len( string $value ): int {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : ( preg_match_all( '/./us', $value ) ?: 0 );
}

$first = elmercado_gsc_batch_20_20261012();
$second = elmercado_gsc_en_second_batch_20261012();
$third = elmercado_gsc_en_third_batch_20261012();

ensure_integration( count( $first ) === 20 && count( $second ) === 20 && count( $third ) === 20, 'Three batches of 20 required' );
$combined = array_merge( $first, $second, $third );
ensure_integration( count( $combined ) === 60, 'Overlap between cohort paths' );
$en = 0;
$es = 0;

foreach ( $combined as $path => $entry ) {
	$is_en = str_starts_with( $path, '/en/' );
	if ( $is_en ) { ++$en; } else { ++$es; }
	ensure_integration( preg_match( '~^/(?:en/)?[a-z0-9-]+/$~', $path ) === 1, 'Bad path ' . $path );
	ensure_integration( utf8_len( $entry['title'] . ' | El Mercado de Origen' ) <= 70, 'Title too long ' . $path );
	ensure_integration( utf8_len( $entry['description'] ) >= 100 && utf8_len( $entry['description'] ) <= 160, 'Description length ' . $path );

	$GLOBALS['seo_test_path'] = $path;
	$_SERVER['REQUEST_URI'] = $path;

	$title = elmercado_gsc_batch_20_filter_title_20261012(
		elmercado_gsc_en_second_title_20261012(
			elmercado_gsc_en_third_title_20261012( 'Unchanged title' )
		)
	);
	$description = elmercado_gsc_batch_20_filter_description_20261012(
		elmercado_gsc_en_second_description_20261012(
			elmercado_gsc_en_third_description_20261012( 'Unchanged description' )
		)
	);
	ensure_integration( $title === $entry['title'] . ' | El Mercado de Origen', 'Title conflict ' . $path );
	ensure_integration( $description === $entry['description'], 'Description conflict ' . $path );
	$fallback = array_filter( array(
		elmercado_gsc_batch_20_fallback_description_20261012(),
		elmercado_gsc_en_second_fallback_description_20261012(),
		elmercado_gsc_en_third_fallback_description_20261012(),
	) );
	ensure_integration( count( $fallback ) === 1 && reset( $fallback ) === $entry['description'], 'Native fallback conflict ' . $path );
}

ensure_integration( $en === 56 && $es === 4, 'Expected 56 English and 4 Spanish URLs' );
foreach ( array( '/', '/en/journal/', '/en/not-a-target/', '/en/category/meat/', '/productos/', '/en/product/lean-diced-beef-beef-ragout/' ) as $path ) {
	$GLOBALS['seo_test_path'] = $path;
	$_SERVER['REQUEST_URI'] = $path;
	ensure_integration(
		elmercado_gsc_batch_20_filter_title_20261012( elmercado_gsc_en_second_title_20261012( elmercado_gsc_en_third_title_20261012( 'Unchanged' ) ) ) === 'Unchanged',
		'Unrelated title overwritten ' . $path
	);
	ensure_integration(
		elmercado_gsc_batch_20_filter_description_20261012( elmercado_gsc_en_second_description_20261012( elmercado_gsc_en_third_description_20261012( 'Unchanged' ) ) ) === 'Unchanged',
		'Unrelated description overwritten ' . $path
	);
}

echo "OK: 60 independent SEO URLs (56 EN/4 ES), 3 title and description hooks plus native fallbacks, no overlap.\n";
