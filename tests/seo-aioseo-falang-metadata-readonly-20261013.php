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


$active = (array) get_option( 'active_plugins', array() );
foreach ( $active as $plugin_file ) {
	if ( preg_match( '/(?:falang|translate|polylang|wpml|multi-language)/i', (string) $plugin_file ) ) {
		$name = basename( dirname( (string) $plugin_file ) );
		if ( preg_match( '/^[A-Za-z0-9_-]{2,70}$/', $name ) ) {
			echo 'SEO_DIAG_TRANSLATION_PLUGIN_DIR=' . $name . "\n";
		}
	}
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
	if ( 'aioseo_description' === $hook ) {
		foreach ( $callbacks as $priority => $items ) {
			foreach ( $items as $entry ) {
				$fn = $entry['function'] ?? '';
				if ( is_string( $fn ) ) {
					$name = $fn;
				} elseif ( is_array( $fn ) ) {
					$name = ( is_object( $fn[0] ) ? get_class( $fn[0] ) : (string) $fn[0] ) . '::' . (string) $fn[1];
				} else {
					$name = 'anonymous';
				}
				$name = preg_replace( '/[^A-Za-z0-9_:\\\\-]/', '', $name );
				echo "SEO_DIAG_AIOSEO_DESC_CALLBACK=priority:" . (int) $priority .
					" name:" . substr( $name, 0, 100 ) . "\n";
			}
		}
	}

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


/* Diagnose TranslatePress translation storage, without exposing strings. */
$settings = get_option( 'trp_settings', array() );
$default_lang = is_array( $settings ) ? (string) ( $settings['default-language'] ?? '' ) : '';
if ( preg_match( '/^[a-z]{2}_[A-Z]{2}$/', $default_lang ) ) {
	echo 'SEO_DIAG_TRP_DEFAULT_LANGUAGE=' . $default_lang . "\n";
}
if ( is_array( $settings ) && isset( $settings['translation-languages'] ) ) {
	foreach ( (array) $settings['translation-languages'] as $lang ) {
		if ( preg_match( '/^[a-z]{2}_[A-Z]{2}$/', (string) $lang ) ) {
			echo 'SEO_DIAG_TRP_TRANSLATION_LANGUAGE=' . $lang . "\n";
		}
	}
}
$table_prefix = $wpdb->prefix . 'trp_dictionary_';
$tables = $wpdb->get_col(
	$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_prefix ) . '%' )
);
foreach ( (array) $tables as $table ) {
	if ( ! preg_match( '/^[A-Za-z0-9_]+$/', (string) $table ) ) {
		continue;
	}
	$label = substr( $table, strlen( $table_prefix ) );
	$quoted = chr(96) . $table . chr(96);
	$columns = $wpdb->get_col( "SHOW COLUMNS FROM " . $quoted );
	if ( ! in_array( 'original', (array) $columns, true ) || ! in_array( 'translated', (array) $columns, true ) ) {
		echo 'SEO_DIAG_TRP_TABLE_' . $label . '=schema_unexpected' . "\n";
		continue;
	}
	$old_prefix = 'A peeled or cut potato can darken on contact with air';
	$n = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM " . $quoted . " WHERE translated LIKE %s",
		$wpdb->esc_like( $old_prefix ) . '%'
	) );
	echo 'SEO_DIAG_TRP_TABLE_' . $label . '=old_potato_description_matches:' . $n . "\n";
	$desired = $targets['/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/']['description'] ?? '';
	if ( $desired ) {
		$origin_hits = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM " . $quoted . " WHERE original = %s", $desired
		) );
		$target_hits = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM " . $quoted . " WHERE translated = %s", $desired
		) );
		echo 'SEO_DIAG_TRP_TARGET_POTATO_' . $label . '=original:' . $origin_hits . ' translated:' . $target_hits . "\n";
	}
}
$original_meta = $wpdb->prefix . 'trp_original_meta';
$original_meta_exists = $wpdb->get_var(
	$wpdb->prepare( 'SHOW TABLES LIKE %s', $original_meta )
) === $original_meta;
echo 'SEO_DIAG_TRP_ORIGINAL_META=' . ( $original_meta_exists ? 'exists' : 'missing' ) . "\n";
if ( $original_meta_exists ) {
	$fields = $wpdb->get_col( 'SHOW COLUMNS FROM ' . chr(96) . $original_meta . chr(96) );
	foreach ( (array) $fields as $field ) {
		if ( preg_match( '/^[A-Za-z0-9_]{1,65}$/', (string) $field ) ) {
			echo 'SEO_DIAG_TRP_ORIGINAL_META_COLUMN=' . $field . "\n";
		}
	}
}
