<?php
/**
 * Plugin Name: MDO - MENTTA frontend admin visibility 0.10.257
 * Description: Lets authenticated administrators audit the internal MENTTA category on the frontend while keeping it hidden from everyone else.
 * Version: 0.10.257
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function (): void {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/* The base MENTTA MU-plugin stays authoritative for public requests. */
		remove_filter( 'get_terms_args', 'mdo_mentta_hide_from_public_term_queries', 20 );
		remove_filter( 'get_terms', 'mdo_mentta_hide_from_public_term_results', 20 );
		remove_filter( 'get_the_terms', 'mdo_mentta_hide_from_public_product_terms', 20 );
		remove_filter( 'wp_get_nav_menu_items', 'mdo_mentta_hide_public_menu_items', 20 );
		remove_action( 'template_redirect', 'mdo_mentta_start_home_output_filter', -10000 );
		remove_action( 'template_redirect', 'mdo_mentta_block_public_archive', 1 );
	},
	1
);
