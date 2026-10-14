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

$GLOBALS['seo_registered_filters'] = array();
$GLOBALS['seo_registered_actions'] = array();
$GLOBALS['seo_removed_actions'] = array();
function add_filter( $tag, $callback, $priority = 10, $args = 1 ) { $GLOBALS['seo_registered_filters'][ $tag ][] = array( $callback, $priority ); }
function add_action( $tag, $callback, $priority = 10, $args = 1 ) { $GLOBALS['seo_registered_actions'][ $tag ][] = array( $callback, $priority ); }
function remove_action( $tag, $callback, $priority = 10 ) { $GLOBALS['seo_removed_actions'][] = array( $tag, $callback, $priority ); return true; }
function is_admin(): bool { return false; }
function esc_attr( $s ): string { return htmlspecialchars( (string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
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

$second_candidates = elmercado_gsc_candidate_h1_paths_20261012();
check( count( $second_candidates ) === 20, 'Exactly 20 H1-only English candidate URLs' );
check( count( array_unique( $second_candidates ) ) === 20, 'No duplicate EN candidate paths' );
foreach ( $second_candidates as $candidate ) {
	check( str_starts_with( $candidate, '/en/' ), 'H1-only candidate must be English' );
	check( ! isset( $batch[ $candidate ] ), 'H1-only candidate must not override first batch' );
}
$GLOBALS['seo_test_path'] = $second_candidates[0];
check( elmercado_gsc_remove_repeated_heading_at_render_20261012( $body ) === $clean, 'Second cohort identical H1 clean-up' );
check( elmercado_gsc_batch_20_filter_title_20261012( 'Original' ) === 'Original', 'Second cohort metadata must remain unchanged' );

$GLOBALS['seo_test_path'] = '/legacy-test/';
$GLOBALS['seo_legacy_target'] = true;
check( elmercado_gsc_remove_repeated_heading_at_render_20261012( $body ) === $clean, 'Legacy batch H1 regression' );

$GLOBALS['seo_legacy_target'] = false;
check( elmercado_gsc_remove_repeated_heading_at_render_20261012( $body ) === $body, 'Unrelated article must remain unchanged' );


/* AIOSEO overrides must run AFTER its default 99999 title priority. */
foreach ( array( 'pre_get_document_title', 'aioseo_title', 'wp_title' ) as $hook ) {
	$matches = array_filter(
		$GLOBALS['seo_registered_filters'][ $hook ] ?? array(),
		static fn( $entry ) => 'elmercado_gsc_batch_20_filter_title_20261012' === $entry[0] && $entry[1] > 99999
	);
	check( ! empty( $matches ), 'Missing late SEO title hook: ' . $hook );
}
$meta_hooks = array_filter(
	$GLOBALS['seo_registered_filters']['aioseo_description'] ?? array(),
	static fn( $entry ) => 'elmercado_gsc_batch_20_filter_description_20261012' === $entry[0] && PHP_INT_MAX === $entry[1]
);
check( ! empty( $meta_hooks ), 'AIOSEO description filter must be highest priority' );

$page_header_removers = array_filter(
	$GLOBALS['seo_registered_actions']['wp_head'] ?? array(),
	static fn( $entry ) => -5000 === $entry[1]
);
check( 1 === count( $page_header_removers ), 'Expected exactly one early WP head H1 protection' );
$remove_header = reset( $page_header_removers )[0];
$GLOBALS['seo_removed_actions'] = array();
$GLOBALS['seo_test_path'] = array_key_first( $batch );
$remove_header();
check(
	in_array( array( 'woostify_after_header', 'woostify_page_header', 10 ), $GLOBALS['seo_removed_actions'], true ),
	'Remove only the hidden Woostify H1 on selected article'
);
$GLOBALS['seo_removed_actions'] = array();
$GLOBALS['seo_test_path'] = '/blog/';
$remove_header();
check( empty( $GLOBALS['seo_removed_actions'] ), 'Do not remove Woostify title outside the cohort' );


$approved = $batch['/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/']['description'];
$fixture = '<!doctype html><html lang="en"><head><title>Existing title</title><meta name="description" content="Old translated description"><meta name="robots" content="index,follow"></head><body><h1>Keep article heading</h1><p>Body intact.</p></body></html>';
$updated = elmercado_gsc_meta_outer_replace_20261014( $fixture, $approved );
check( $updated !== $fixture, 'Outer buffer must update one meta tag' );
check( substr_count( $updated, 'name="description"' ) === 1, 'Exactly one meta description' );
check( str_contains( $updated, esc_attr( $approved ) ), 'EN meta must match target' );
check( str_contains( $updated, '<title>Existing title</title>' ), 'Must preserve title' );
check( str_contains( $updated, '<h1>Keep article heading</h1><p>Body intact.</p>' ), 'Must preserve body H1/content' );
check( str_contains( $updated, '<meta name="robots" content="index,follow">' ), 'Must preserve robots' );
check( $updated === elmercado_gsc_meta_outer_replace_20261014( $updated, $approved ), 'Idempotent EN metadata' );
check( $fixture === elmercado_gsc_meta_outer_replace_20261014( $fixture, '' ), 'Empty target must be no-op' );
check( $fixture === elmercado_gsc_meta_outer_replace_20261014( $fixture, null ), 'Non-string target no-op' );

$two = str_replace( '</head>', '<meta name="description" content="Second"></head>', $fixture );
check( $two === elmercado_gsc_meta_outer_replace_20261014( $two, $approved ), 'Duplicate descriptions must not be altered' );
$bad = str_replace( '</head>', '', $fixture );
check( $bad === elmercado_gsc_meta_outer_replace_20261014( $bad, $approved ), 'Malformed head preserved' );

$init_callbacks = array_filter(
	$GLOBALS['seo_registered_actions']['init'] ?? array(),
	static fn( $entry ) => -1000 === $entry[1]
);
check( count( $init_callbacks ) === 1, 'Exactly one early outer-buffer callback' );
$starter = reset( $init_callbacks )[0];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/en/unrelated-article/';
$depth_before = ob_get_level();
$starter();
check( ob_get_level() === $depth_before, 'Do not start buffer on unrelated EN page' );
$_SERVER['REQUEST_URI'] = '/cuanta-carne-calcular-por-persona-corte-receta/';
$starter();
check( ob_get_level() === $depth_before, 'Do not start buffer on Spanish article' );
$_SERVER['REQUEST_URI'] = '/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/';
$_SERVER['REQUEST_METHOD'] = 'POST';
$starter();
check( ob_get_level() === $depth_before, 'Do not buffer POST' );
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start(); // Capture the output of the outer-buffer handler.
$starter();
check( ob_get_level() === $depth_before + 2, 'Start one buffer on sole approved EN page' );
echo $fixture;
ob_end_flush(); // Apply the SEO callback into the test capture buffer.
$captured = ob_get_clean();
check( str_contains( $captured, esc_attr( $approved ) ), 'Output buffer must transform real printed HTML' );
check( ob_get_level() === $depth_before, 'Buffer cleanup should restore stack depth' );


echo "OK: 20 URL, 16 EN, 4 ES, title priorities, article H1 only, link and metadata scope.\n";


function mb_safe_length( string $value ): int {
	if ( function_exists( 'mb_strlen' ) ) { return mb_strlen( $value, 'UTF-8' ); }
	return preg_match_all( '/./us', $value ) ?: 0;
}
