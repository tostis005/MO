<?php
/**
 * Garantiza que la Home termine con las mismas categorías visibles que Tienda.
 *
 * Este buffer se abre antes que las capas históricas de caché/optimización para
 * ser el más exterior. Al vaciarse la respuesta, su callback corre el último y
 * deja la sección de categorías con la verdad final del catálogo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		ob_start(
			static function ( string $html ): string {
				$start = strpos( $html, '<section class="emo-section emo-categories"' );
				if ( false === $start || ! function_exists( 'elmercado_home_categories_visible_html_010226' ) ) {
					return $html;
				}

				$replacement = elmercado_home_categories_visible_html_010226();
				$end         = strpos( $html, '</section>', $start );
				if ( '' === $replacement || false === $end ) {
					return $html;
				}
				$end += strlen( '</section>' );

				return substr_replace( $html, $replacement, $start, $end - $start );
			}
		);
	},
	-3000
);
