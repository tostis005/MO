<?php
/**
 * Diagnóstico temporal de peso inline de Home 0.10.150.
 * Inserta únicamente un resumen oculto de tamaños para identificar el lastre
 * del documento inicial. Se retirará en la siguiente release.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		ob_start(
			static function ( string $html ): string {
				$styles = array();
				if ( preg_match_all( '~<style\\b([^>]*)>(.*?)</style>~is', $html, $matches, PREG_SET_ORDER ) ) {
					foreach ( $matches as $index => $match ) {
						$id = '';
						if ( preg_match( '~\\bid=["\\\']([^"\\\']+)["\\\']~i', $match[1], $id_match ) ) {
							$id = $id_match[1];
						}
						$styles[] = array( 'index' => $index, 'id' => $id, 'bytes' => strlen( $match[2] ) );
					}
				}

				$scripts = array();
				if ( preg_match_all( '~<script\\b([^>]*)>(.*?)</script>~is', $html, $matches, PREG_SET_ORDER ) ) {
					foreach ( $matches as $index => $match ) {
						$src = '';
						if ( preg_match( '~\\bsrc=["\\\']([^"\\\']+)["\\\']~i', $match[1], $src_match ) ) {
							$src = $src_match[1];
						}
						$id = '';
						if ( preg_match( '~\\bid=["\\\']([^"\\\']+)["\\\']~i', $match[1], $id_match ) ) {
							$id = $id_match[1];
						}
						$scripts[] = array( 'index' => $index, 'id' => $id, 'src' => $src, 'inline_bytes' => strlen( $match[2] ) );
					}
				}

				usort( $styles, static fn( array $a, array $b ): int => $b['bytes'] <=> $a['bytes'] );
				usort( $scripts, static fn( array $a, array $b ): int => $b['inline_bytes'] <=> $a['inline_bytes'] );

				$hero_offset = strpos( $html, '<section class="emo-hero"' );
				$head_end    = strpos( $html, '</head>' );
				$result      = array(
					'total'   => strlen( $html ),
					'head'    => false === $head_end ? null : $head_end,
					'hero'    => false === $hero_offset ? null : $hero_offset,
					'styles'  => array_sum( array_column( $styles, 'bytes' ) ),
					'scripts' => array_sum( array_column( $scripts, 'inline_bytes' ) ),
					'top_styles' => array_slice( $styles, 0, 10 ),
					'top_scripts' => array_slice( $scripts, 0, 10 ),
				);

				$summary = 'INLINE_AUDIT ' . (string) wp_json_encode( $result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				$marker  = '<div id="emo-inline-audit-output" aria-hidden="true" style="display:none!important">' . esc_html( $summary ) . '</div>';

				return str_contains( $html, '</body>' ) ? str_replace( '</body>', $marker . '</body>', $html ) : $html . $marker;
			}
		);
	},
	-10000
);
