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
	PHP_INT_MAX
);
