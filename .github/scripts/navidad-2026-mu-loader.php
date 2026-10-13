<?php
/**
 * Plugin Name: EMDO Navidad 2026 Landing Loader
 * Description: Carga únicamente la landing independiente de Navidad sin modificar el tema o el SEO.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action(
	'after_setup_theme',
	static function (): void {
		$module = get_stylesheet_directory() . '/inc/navidad-2026-campaign.php';
		if ( is_readable( $module ) ) {
			require_once $module;
		}
	},
	1
);
