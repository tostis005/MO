<?php
/**
 * Plugin Name: MDO - Blog discovery loader 0.10.263
 * Description: Loads the blog discovery module for both wp-admin and public requests.
 * Version: 0.10.263
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	static function (): void {
		$module = get_stylesheet_directory() . '/inc/blog-discovery-010263.php';
		if ( ! function_exists( 'elmercado_blog_filter_state_010263' ) && is_readable( $module ) ) {
			require_once $module;
		}
	},
	1
);
