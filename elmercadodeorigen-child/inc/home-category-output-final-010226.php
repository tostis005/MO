<?php
/**
 * Garantiza que la Home termine con las mismas categorías visibles que Tienda.
 *
 * Se aplica sobre el HTML final para que ningún corrector histórico del contenido
 * vuelva a limitar la sección después de calcular la visibilidad real.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_front_page' ) || ! is_front_page() ) {
			return;
		}

		ob_start(
			static function ( string $html ): string {
				if ( ! function_exists( 'elmercado_home_categories_visible_html_010226' ) ) {
					return $html;
				}

				$replacement = elmercado_home_categories_visible_html_010226();
				$start       = strpos( $html, '<section class="emo-section emo-categories"' );
				if ( '' === $replacement || false === $start ) {
					return $html;
				}

				$end = strpos( $html, '</section>', $start );
				if ( false === $end ) {
					return $html;
				}
				$end += strlen( '</section>' );

				return substr_replace( $html, $replacement, $start, $end - $start );
			}
		);
	},
	PHP_INT_MAX
);
