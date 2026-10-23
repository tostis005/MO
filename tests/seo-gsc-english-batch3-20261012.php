<?php
/** Third GSC batch PHP smoke: 20 EN URLs, isolated metas and native fallback. */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ );
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
function is_admin(): bool { return false; }
function is_singular( $kind ): bool { return 'post' === $kind; }
function wp_unslash( $value ) { return stripslashes( (string) $value ); }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
require __DIR__ . '/../elmercadodeorigen-child/inc/seo-gsc-english-batch3-20261012.php';

function ensure( bool $ok, string $reason ): void {
	if ( ! $ok ) { fwrite( STDERR, 'FAIL: ' . $reason . "\n" ); exit( 1 ); }
}
function length_utf8( string $text ): int {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : ( preg_match_all( '/./us', $text ) ?: 0 );
}
$batch = elmercado_gsc_en_third_batch_20261012();
ensure( count( $batch ) === 20, 'Expected 20 URL entries' );
foreach ( $batch as $path => $meta ) {
	ensure( preg_match( '~^/en/[a-z0-9-]+/$~', $path ) === 1, 'EN URL required: ' . $path );
	ensure( isset( $meta['title'], $meta['description'] ), 'Missing title/description: ' . $path );
	ensure( length_utf8( $meta['title'] . ' | El Mercado de Origen' ) <= 70, 'Long title with site name: ' . $path );
	ensure( length_utf8( $meta['description'] ) >= 100 && length_utf8( $meta['description'] ) <= 160, 'Description length: ' . $path );
	$_SERVER['REQUEST_URI'] = $path . '?utm_source=qa';
	ensure( elmercado_gsc_en_third_title_20261012( 'Unchanged' ) === $meta['title'] . ' | El Mercado de Origen', 'Title hook: ' . $path );
	ensure( elmercado_gsc_en_third_description_20261012( 'Unchanged' ) === $meta['description'], 'Description hook: ' . $path );
	ensure( elmercado_gsc_en_third_fallback_description_20261012() === $meta['description'], 'Native meta fallback: ' . $path );
}
foreach ( array( '/', '/en/journal/', '/en/how-much-iron-in-beef/', '/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/', '/carne-magra-ternera-que-es-como-cocinar-tierna/', '/enquiries/' ) as $path ) {
	$_SERVER['REQUEST_URI'] = $path;
	ensure( elmercado_gsc_en_third_title_20261012( 'Unchanged' ) === 'Unchanged', 'Unexpected unrelated title: ' . $path );
	ensure( elmercado_gsc_en_third_description_20261012( 'Unchanged' ) === 'Unchanged', 'Unexpected unrelated description: ' . $path );
	ensure( elmercado_gsc_en_third_fallback_description_20261012() === '', 'Unexpected unrelated fallback: ' . $path );
}
echo "OK: 20 EN cohort 3 URLs, scoped metadata and plugin-free descriptions; no other pages altered.\n";
