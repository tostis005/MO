<?php
/**
 * Reconciliación final con estilos heredados y plugins.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retira la hoja de CSS personalizada antigua ya migrada al child theme.
 */
function elmercado_remove_legacy_custom_css(): void {
	global $wp_styles;

	$legacy_handles = array( '6585', 'custom-css-js-6585', 'custom-css-6585' );

	foreach ( $legacy_handles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}

	if ( ! $wp_styles instanceof WP_Styles ) {
		return;
	}

	foreach ( $wp_styles->registered as $handle => $style ) {
		$source = isset( $style->src ) ? (string) $style->src : '';

		if ( str_contains( $source, '/custom-css-js/6585.css' ) ) {
			wp_dequeue_style( (string) $handle );
			wp_deregister_style( (string) $handle );
		}
	}
}

add_action(
	'wp_enqueue_scripts',
	function (): void {
		elmercado_remove_legacy_custom_css();

		wp_enqueue_style(
			'elmercado-polish',
			ELMERCADO_THEME_URL . '/assets/css/polish.css',
			array( 'elmercado-integrations' ),
			elmercado_asset_version( '/assets/css/polish.css' )
		);

		wp_enqueue_style(
			'elmercado-final',
			ELMERCADO_THEME_URL . '/assets/css/final.css',
			array( 'elmercado-polish' ),
			elmercado_asset_version( '/assets/css/final.css' )
		);
	},
	9999
);

add_action( 'wp_print_styles', 'elmercado_remove_legacy_custom_css', 9999 );

/**
 * Algunas herramientas de CSS personalizado imprimen su etiqueta después del
 * encolado normal. Este filtro evita que la hoja migrada llegue al HTML.
 */
add_filter(
	'style_loader_tag',
	static function ( string $html, string $handle, string $href ): string {
		unset( $handle );

		return str_contains( $href, '/custom-css-js/6585.css' ) ? '' : $html;
	},
	PHP_INT_MAX,
	3
);

/**
 * Evita categorías vacías en la selección de portada aunque otro plugin
 * altere el comportamiento de hide_empty.
 *
 * @param WP_Term[]|int[]|WP_Error $terms Términos obtenidos.
 * @param string[]                  $taxonomies Taxonomías consultadas.
 * @return WP_Term[]|int[]|WP_Error
 */
add_filter(
	'get_terms',
	function ( $terms, array $taxonomies ) {
		if ( is_admin() || ! is_front_page() || ! in_array( 'product_cat', $taxonomies, true ) || ! is_array( $terms ) ) {
			return $terms;
		}

		return array_values(
			array_filter(
				$terms,
				static function ( $term ): bool {
					return ! $term instanceof WP_Term || (int) $term->count > 0;
				}
			)
		);
	},
	20,
	2
);
