<?php
/** Independent metadata smoke test: 20 EN posts and no unrelated routes. */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ );
function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_action( $tag, $callback, $priority = 10, $args = 1 ) {}
function remove_action( $tag, $callback, $priority = 10 ) {}
function is_admin(): bool { return false; }
function is_singular( $type ): bool { return 'post' === $type; }
function wp_unslash( $value ) { return stripslashes( (string) $value ); }
function wp_parse_url( $uri, $component ) { return parse_url( $uri, $component ); }
require __DIR__ . '/../elmercadodeorigen-child/inc/seo-gsc-english-batch2-20261012.php';

function ensure( bool $pass, string $message ): void {
	if ( ! $pass ) { fwrite( STDERR, 'FAIL: ' . $message . "\n" ); exit( 1 ); }
}
function unicode_length( string $text ): int {
	return function_exists( 'mb_strlen' )
		? mb_strlen( $text, 'UTF-8' )
		: ( preg_match_all( '/./us', $text ) ?: 0 );
}
$batch = elmercado_gsc_en_second_batch_20261012();
ensure( count( $batch ) === 20, 'Exactly 20 pages' );
foreach ( $batch as $path => $item ) {
	ensure( preg_match( '~^/en/[a-z0-9-]+/$~', $path ) === 1, 'Must be real English path: ' . $path );
	ensure( isset( $item['title'], $item['description'] ), 'Missing meta: ' . $path );
	ensure( unicode_length( $item['title'] . ' | El Mercado de Origen' ) <= 70, 'Title length: ' . $path );
	ensure( unicode_length( $item['description'] ) >= 100 && unicode_length( $item['description'] ) <= 160, 'Description length: ' . $path );
	$_SERVER['REQUEST_URI'] = $path . '?utm_source=qa';
	ensure( elmercado_gsc_en_second_title_20261012( 'Original' ) === $item['title'] . ' | El Mercado de Origen', 'Wrong title: ' . $path );
	ensure( elmercado_gsc_en_second_description_20261012( 'Original' ) === $item['description'], 'Wrong description: ' . $path );
	ensure( elmercado_gsc_en_second_fallback_description_20261012() === $item['description'], 'Native fallback: ' . $path );
}
foreach ( array( '/', '/en/journal/', '/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/', '/hay-que-poner-lentejas-en-remojo-cuanto-tiempo/', '/enquiries/' ) as $path ) {
	$_SERVER['REQUEST_URI'] = $path;
	ensure( elmercado_gsc_en_second_title_20261012( 'Original' ) === 'Original', 'Unrelated title changed: ' . $path );
	ensure( elmercado_gsc_en_second_description_20261012( 'Original' ) === 'Original', 'Unrelated description changed: ' . $path );
	ensure( elmercado_gsc_en_second_fallback_description_20261012() === '', 'Unrelated fallback changed: ' . $path );
}
echo "OK: exactly 20 EN pages, safe metadata, native fallback and no unrelated overrides.\n";
