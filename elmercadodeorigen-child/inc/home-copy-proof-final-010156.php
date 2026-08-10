<?php
/**
 * Corrección final de la franja de conceptos de Home 0.10.156.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fija los tres conceptos del hero sin modificar su estructura.
 *
 * @param string $html Documento HTML.
 * @return string
 */
function elmercado_home_proof_copy_final_010156( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$result = preg_replace_callback(
		'~<div class="emo-hero__proof">.*?</div>~s',
		static function ( array $matches ): string {
			$items = array(
				array( 'Calidad', 'Como punto de partida.' ),
				array( 'Identidad', 'Porque buscamos algo propio.' ),
				array( 'Confianza', 'Para elegir con tranquilidad.' ),
			);
			$index = 0;

			$proof = preg_replace_callback(
				'~<span><strong>.*?</strong>.*?</span>~s',
				static function ( array $item_match ) use ( &$index, $items ): string {
					if ( ! isset( $items[ $index ] ) ) {
						return $item_match[0];
					}

					$item = $items[ $index ];
					$index++;

					return '<span><strong>' . $item[0] . '</strong>' . $item[1] . '</span>';
				},
				$matches[0],
				3
			);

			return is_string( $proof ) ? $proof : $matches[0];
		},
		$html,
		1
	);

	return is_string( $result ) ? $result : $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		/* Capa exterior: recibe el copy final 0.10.155 y corrige solo esta franja. */
		ob_start( 'elmercado_home_proof_copy_final_010156' );
	},
	-900
);
