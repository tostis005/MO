<?php
/**
 * Read-only WordPress CLI inspection. Never echo metadata content, SQL
 * connection details, filesystem paths or private options.
 *
 * Run: wp eval-file tests/seo-aioseo-falang-metadata-readonly-20261013.php
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

global $wpdb, $wp_filter;
$plugin_ids = array( 'all-in-one-seo-pack/all_in_one_seo_pack.php', 'falang/falang.php' );
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
foreach ( $plugin_ids as $plugin ) {
	$slug = explode( '/', $plugin )[0];
	echo 'SEO_DIAG_PLUGIN_' . strtoupper( str_replace( '-', '_', $slug ) ) . '=' .
		( is_plugin_active( $plugin ) ? 'active' : 'other' ) . "\n";
}

foreach ( array( 'aioseo_description', 'localization', 'get_post_metadata' ) as $hook ) {
	$callbacks = isset( $wp_filter[ $hook ] ) ? (array) $wp_filter[ $hook ]->callbacks : array();
	$total = 0;
	$falang = 0;
	$aioseo = 0;
	foreach ( $callbacks as $entries ) {
		foreach ( $entries as $entry ) {
			++$total;
			$fn = $entry['function'] ?? '';
			if ( is_array( $fn ) ) {
				$name = ( is_object( $fn[0] ) ? get_class( $fn[0] ) : (string) $fn[0] ) . '::' . (string) $fn[1];
			} elseif ( is_string( $fn ) ) {
				$name = $fn;
			} else {
				$name = 'anonymous';
			}
			if ( false !== stripos( $name, 'falang' ) ) {
				++$falang;
			}
			if ( false !== stripos( $name, 'aioseo' ) ) {
				++$aioseo;
			}
		}
	}
	printf( "SEO_DIAG_HOOK_%s=total:%d falang:%d aioseo:%d\n", $hook, $total, $falang, $aioseo );
}

$seo_table = $wpdb->prefix . 'aioseo_posts';
$seo_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $seo_table ) ) === $seo_table;
echo 'SEO_DIAG_AIOSEO_TABLE=' . ( $seo_exists ? 'exists' : 'missing' ) . "\n";

$meta_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta}
	WHERE meta_key LIKE '%falang%' OR meta_key LIKE '%aioseo%' LIMIT 35" );
foreach ( (array) $meta_keys as $key ) {
	if ( preg_match( '/^[A-Za-z0-9_-]{1,85}$/', (string) $key ) ) {
		echo 'SEO_DIAG_META_KEY=' . $key . "\n";
	}
}

$targets = function_exists( 'elmercado_gsc_batch_20_20261012' )
	? elmercado_gsc_batch_20_20261012()
	: array();
echo 'SEO_DIAG_BATCH_MODULE_LOADED=' . ( count( $targets ) === 20 ? 'yes' : 'no' ) . "\n";
$paths = array(
	'/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/',
	'/en/white-foam-when-cooking-meat-what-it-is-why-it-appears/',
	'/cuanta-carne-calcular-por-persona-corte-receta/',
);
foreach ( $paths as $path ) {
	$id = url_to_postid( 'https://www.elmercadodeorigen.com' . $path );
	echo 'SEO_DIAG_URL_TO_POSTID_' . ( str_starts_with( $path, '/en/' ) ? 'en' : 'es' ) . '=' . ( $id > 0 ? 'resolved' : 'unresolved' ) . "\n";
	if ( $id < 1 || ! $seo_exists || ! function_exists( 'aioseo' ) ) {
		continue;
	}
	try {
		$post = get_post( $id );
		if ( ! $post ) {
			continue;
		}
		$original = aioseo()->meta->metaData->getMetaData( $post );
		$expected = $targets[ $path ]['description'] ?? '';
		$source = isset( $original->description ) ? (string) $original->description : '';
		echo 'SEO_DIAG_AIOSEO_STORED_DESCRIPTION_' .
			( str_starts_with( $path, '/en/' ) ? 'en' : 'es' ) . '=' .
			( '' === $source ? 'empty' : ( $source === $expected ? 'target' : 'different' ) ) . "\n";
	} catch ( Throwable $ex ) {
		echo 'SEO_DIAG_AIOSEO_META_READ=unavailable' . "\n";
	}
}
