<?php
/**
 * Smoke test de la tanda SEO GSC de octubre de 2026.
 * Ejecutar sin WordPress: php tests/seo-gsc-batch-20-20261012.php
 * Protege el alcance por URL, idioma, los metadatos y los H1 duplicados.
 */
declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['seo_test_path'] = '/';
$GLOBALS['seo_legacy_target'] = false;

function add_filter( $tag, $callback, $priority = 10, $args = 1 ) {}
function add_action( $tag, $callback, $priority = 10, $args = 1 ) {}
function is_admin(): bool { return false; }
function is_feed(): bool { return false; }
function is_singular( $type ): bool { return 'post' === $type; }
function in_the_loop(): bool { return true; }
function is_main_query(): bool { return true; }
function get_queried_object_id(): int { return 123; }
function get_the_ID(): int { return 123; }
function get_the_title( $id ): string { return 'How to Store Food Safely'; }
function elmercado_gsc_current_path_010313(): string { return $GLOBALS['seo_test_path']; }
function elmercado_gsc_targeted_title_010313(): string { return $GLOBALS['seo_legacy_target'] ? 'Original title target' : ''; }
function elmercado_gsc_normalize_heading_010313( $text ): string {
	return strtolower( trim( preg_replace( '/\\s+/', ' ', strip_tags( (string) $text ) ) ) );
}

require __DIR__ . '/../elmercadodeorigen-child/inc/seo-gsc-batch-20-20261012.php';

function check( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

$batch = elmercado_gsc_batch_20_20261012();
check( count( $batch ) === 20, 'Exactly 20 URLs required' );

$en = 0;
$es = 0;
foreach ( $batch as $path => $item ) {
	check( preg_match( '~^/(?:en/)?[a-z0-9-]+/$~', $path ) === 1, 'Invalid URL path: ' . $path );
	check( isset( $item['title'], $item['description'] ), 'Missing metadata: ' . $path );
	check( strlen( $item['title'] ) > 15, 'Title too short: ' . $path );
	check( mb_safe_length( $item['title'] . ' | El Mercado de Origen' ) <= 70, 'Title too long: ' . $path );
	check( mb_safe_length( $item['description'] ) >= 100, 'Description too short: ' . $path );
	check( mb_safe_length( $item['description'] ) <= 160, 'Description too long: ' . $path );
	if ( str_starts_with( $path, '/en/' ) ) { ++$en; } else { ++$es; }

	$GLOBALS['seo_test_path'] = $path;
	check( elmercado_gsc_batch_20_filter_title_20261012( 'Original' ) === $item['title'] . ' | El Mercado de Origen', 'Wrong title scope: ' . $path );
	check( elmercado_gsc_batch_20_filter_description_20261012( 'Original' ) === $item['description'], 'Wrong description scope: ' . $path );
}
check( $en === 16 && $es === 4, 'Must contain 16 English and 4 Spanish articles' );

$GLOBALS['seo_test_path'] = '/blog/';
check( elmercado_gsc_batch_20_filter_title_20261012( 'Original' ) === 'Original', 'Must preserve other titles' );
check( elmercado_gsc_batch_20_filter_description_20261012( 'Original' ) === 'Original', 'Must preserve other descriptions' );

$all_links = elmercado_gsc_batch_20_links_20261012();
foreach ( $all_links as $source => $links ) {
	check( isset( $batch[ $source ] ), 'Link added to URL outside batch: ' . $source );
	foreach ( $links as $link ) {
		check( $link[0] !== $source, 'Self link: ' . $source );
		check( str_starts_with( $source, '/en/' ) === str_starts_with( $link[0], '/en/' ), 'Mixed language in internal links: ' . $source );
		check( trim( $link[1] ) !== '', 'Missing link label: ' . $source );
	}
}

$GLOBALS['seo_test_path'] = array_key_first( $batch );
$body = '<h1>How to Store Food Safely</h1><p>Relevant article.</p><h1>Another heading</h1>';
$clean = elmercado_gsc_remove_repeated_heading_at_render_20261012( $body );
check( ! str_contains( $clean, '<h1>How to Store Food Safely</h1>' ), 'Duplicate H1 was not removed' );
check( str_contains( $clean, '<h1>Another heading</h1>' ), 'Unrelated H1 must remain' );

$GLOBALS['seo_test_path'] = '/legacy-test/';
$GLOBALS['seo_legacy_target'] = true;
check( elmercado_gsc_remove_repeated_heading_at_render_20261012( $body ) === $clean, 'Legacy batch H1 regression' );

$GLOBALS['seo_legacy_target'] = false;
check( elmercado_gsc_remove_repeated_heading_at_render_20261012( $body ) === $body, 'Unrelated article must remain unchanged' );

echo "OK: 20 URL, 16 EN, 4 ES, scoped metadata, internal links, duplicate H1 and legacy safety checks.\n";

function mb_safe_length( string $value ): int {
	if ( function_exists( 'mb_strlen' ) ) { return mb_strlen( $value, 'UTF-8' ); }
	return preg_match_all( '/./us', $value ) ?: 0;
}
