<?php
/**
 * Diagnóstico temporal de peso inline de Home 0.10.150.
 * Solo responde al query privado emo-inline-audit=1 y no altera visitas normales.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || ! isset( $_GET['emo-inline-audit'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['emo-inline-audit'] ) ) ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );

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
					'total_bytes'       => strlen( $html ),
					'head_bytes'        => false === $head_end ? null : $head_end,
					'hero_offset_bytes' => false === $hero_offset ? null : $hero_offset,
					'style_total_bytes' => array_sum( array_column( $styles, 'bytes' ) ),
					'script_inline_total_bytes' => array_sum( array_column( $scripts, 'inline_bytes' ) ),
					'top_styles'        => array_slice( $styles, 0, 20 ),
					'top_inline_scripts' => array_slice( $scripts, 0, 20 ),
				);

				return (string) wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			}
		);
	},
	-10000
);
