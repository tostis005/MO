<?php
/**
 * Plugin Name: EMDO Navidad 2026 Landing y Banners
 * Description: Carga la campaña de Navidad y sus CTAs aislados sin modificar otros módulos.
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action(
	'after_setup_theme',
	static function (): void {
		$module = get_stylesheet_directory() . '/inc/navidad-2026-campaign.php';
		if ( is_readable( $module ) ) {
			require_once $module;
		}
		$promo = get_stylesheet_directory() . '/inc/navidad-2026-promo.php';
		if ( is_readable( $promo ) ) {
			require_once $promo;
		}
	},
	1
);
