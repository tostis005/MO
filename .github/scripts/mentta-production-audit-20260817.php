<?php
/**
 * Read-only production audit for the MENTTA integration.
 *
 * Intentionally prints plugin/configuration identifiers only. It never prints
 * option values, credentials, tokens, passwords, or customer/order data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

global $wpdb;

function mdo_audit_line_20260817( string $label, $value = null ): void {
	if ( null === $value ) {
		echo $label . PHP_EOL;
		return;
	}

	if ( is_bool( $value ) ) {
		$value = $value ? 'yes' : 'no';
	} elseif ( is_array( $value ) || is_object( $value ) ) {
		$value = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	echo $label . ': ' . (string) $value . PHP_EOL;
}

mdo_audit_line_20260817( 'siteurl', get_option( 'siteurl' ) );
mdo_audit_line_20260817( 'wordpress', get_bloginfo( 'version' ) );
mdo_audit_line_20260817( 'woocommerce_active', class_exists( 'WooCommerce' ) );
if ( defined( 'WC_VERSION' ) ) {
	mdo_audit_line_20260817( 'woocommerce_version', WC_VERSION );
}

mdo_audit_line_20260817( '--- plugins ---' );
$plugins = get_plugins();
ksort( $plugins );
foreach ( $plugins as $file => $data ) {
	$name = isset( $data['Name'] ) ? (string) $data['Name'] : '';
	$version = isset( $data['Version'] ) ? (string) $data['Version'] : '';
	$active = is_plugin_active( $file ) ? 'active' : 'inactive';
	mdo_audit_line_20260817( 'plugin', $file . ' | ' . $name . ' | ' . $version . ' | ' . $active );
}

mdo_audit_line_20260817( '--- MENTTA-like product categories ---' );
$terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	)
);
if ( is_wp_error( $terms ) ) {
	mdo_audit_line_20260817( 'term_error', $terms->get_error_message() );
} else {
	$found = 0;
	foreach ( $terms as $term ) {
		$haystack = strtolower( $term->slug . ' ' . $term->name );
		if ( false !== strpos( $haystack, 'mentta' ) || false !== strpos( $haystack, 'menta' ) ) {
			$found++;
			mdo_audit_line_20260817( 'category', array(
				'id'    => (int) $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => (int) $term->count,
			) );
		}
	}
	if ( ! $found ) {
		mdo_audit_line_20260817( 'category', 'none' );
	}
}

mdo_audit_line_20260817( '--- option names containing mentta/menta (values deliberately omitted) ---' );
$option_names = $wpdb->get_col(
	"SELECT option_name
	 FROM {$wpdb->options}
	 WHERE LOWER(option_name) LIKE '%mentta%'
	    OR LOWER(option_name) LIKE '%menta%'
	 ORDER BY option_name ASC
	 LIMIT 200"
);
if ( $option_names ) {
	foreach ( $option_names as $option_name ) {
		mdo_audit_line_20260817( 'option_name', $option_name );
	}
} else {
	mdo_audit_line_20260817( 'option_name', 'none' );
}

mdo_audit_line_20260817( '--- database tables containing mentta/menta ---' );
$like_tables = $wpdb->get_col( "SHOW TABLES LIKE '%ment%a%'" );
if ( $like_tables ) {
	foreach ( $like_tables as $table ) {
		mdo_audit_line_20260817( 'table', $table );
	}
} else {
	mdo_audit_line_20260817( 'table', 'none' );
}

mdo_audit_line_20260817( '--- cron hooks containing mentta/menta ---' );
$cron = _get_cron_array();
$cron_hooks = array();
if ( is_array( $cron ) ) {
	foreach ( $cron as $timestamp => $hooks ) {
		foreach ( array_keys( (array) $hooks ) as $hook ) {
			$lower = strtolower( (string) $hook );
			if ( false !== strpos( $lower, 'mentta' ) || false !== strpos( $lower, 'menta' ) ) {
				$cron_hooks[ $hook ] = true;
			}
		}
	}
}
if ( $cron_hooks ) {
	foreach ( array_keys( $cron_hooks ) as $hook ) {
		mdo_audit_line_20260817( 'cron_hook', $hook );
	}
} else {
	mdo_audit_line_20260817( 'cron_hook', 'none' );
}

mdo_audit_line_20260817( '--- mentta plugin selection-related source lines ---' );
$plugin_root = WP_PLUGIN_DIR . '/mentta-marketplace';
$matches = 0;
if ( is_dir( $plugin_root ) ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $plugin_root, FilesystemIterator::SKIP_DOTS )
	);
	foreach ( $iterator as $file ) {
		if ( $matches >= 300 || ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
			continue;
		}
		$relative = ltrim( str_replace( $plugin_root, '', $file->getPathname() ), '/\\' );
		$lines = @file( $file->getPathname(), FILE_IGNORE_NEW_LINES );
		if ( ! is_array( $lines ) ) {
			continue;
		}
		foreach ( $lines as $index => $line ) {
			if ( $matches >= 300 ) {
				break;
			}
			if ( preg_match( '/product_cat|categor(?:y|ies)|tax_query|wc_get_products|WC_Product_Query|WP_Query|get_posts|register_rest_route|rest_api_init|woocommerce_rest|posts_where|pre_get_posts/i', $line ) ) {
				$clean = trim( preg_replace( '/\s+/', ' ', $line ) );
				if ( strlen( $clean ) > 500 ) {
					$clean = substr( $clean, 0, 500 ) . '…';
				}
				mdo_audit_line_20260817( 'source', $relative . ':' . ( $index + 1 ) . ' | ' . $clean );
				$matches++;
			}
		}
	}
} else {
	mdo_audit_line_20260817( 'source', 'mentta-marketplace directory not found' );
}
mdo_audit_line_20260817( 'source_matches', $matches );

mdo_audit_line_20260817( 'audit_complete', true );
