<?php
/**
 * Configuración general, recursos y pequeñas mejoras de accesibilidad.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve una versión basada en la fecha del archivo durante desarrollo.
 */
function elmercado_asset_version( string $relative_path ): string {
	$absolute_path = ELMERCADO_THEME_PATH . $relative_path;

	return file_exists( $absolute_path )
		? (string) filemtime( $absolute_path )
		: ELMERCADO_THEME_VERSION;
}

add_action(
	'after_setup_theme',
	function (): void {
		load_child_theme_textdomain( 'elmercadodeorigen', ELMERCADO_THEME_PATH . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/theme.css' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function (): void {
		$parent = wp_get_theme( 'woostify' );

		wp_enqueue_style(
			'woostify-parent',
			get_template_directory_uri() . '/style.css',
			array(),
			$parent->exists() ? $parent->get( 'Version' ) : null
		);

		wp_enqueue_style(
			'elmercado-theme',
			ELMERCADO_THEME_URL . '/assets/css/theme.css',
			array( 'woostify-parent' ),
			elmercado_asset_version( '/assets/css/theme.css' )
		);

		wp_enqueue_style(
			'elmercado-integrations',
			ELMERCADO_THEME_URL . '/assets/css/integrations.css',
			array( 'elmercado-theme' ),
			elmercado_asset_version( '/assets/css/integrations.css' )
		);

		wp_enqueue_script(
			'elmercado-theme',
			ELMERCADO_THEME_URL . '/assets/js/theme.js',
			array(),
			elmercado_asset_version( '/assets/js/theme.js' ),
			true
		);
	},
	20
);

add_filter(
	'body_class',
	function ( array $classes ): array {
		$classes[] = 'elmercado-child-theme';

		if ( class_exists( 'WooCommerce' ) ) {
			$classes[] = 'elmercado-has-woocommerce';
		}

		return $classes;
	}
);
