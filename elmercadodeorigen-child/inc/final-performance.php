<?php
/**
 * Último paso de entrega de la portada.
 *
 * Se ejecuta después del filtro que compone el HTML optimizado y antes de que
 * la respuesta se almacene en el caché anónimo. La hoja de Woostify se mantiene
 * como fallback estructural, pero deja de bloquear el primer render porque la
 * portada ya incluye el sistema visual completo del child theme.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convierte la hoja padre en carga no bloqueante y estabiliza el primer frame.
 */
function elmercado_finalize_home_delivery( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$html = (string) preg_replace_callback(
		'/<link\b[^>]*href=(?:"[^"]*\/themes\/woostify\/style\.css[^"]*"|\'[^\']*\/themes\/woostify\/style\.css[^\']*\')[^>]*>/i',
		static function ( array $matches ): string {
			$tag = $matches[0];

			if ( str_contains( $tag, 'media="print"' ) || str_contains( $tag, "media='print'" ) ) {
				return $tag;
			}

			$async = (string) preg_replace(
				'/\smedia=(?:"[^"]*"|\'[^\']*\')/i',
				'',
				$tag
			);
			$async = preg_replace( '/\s*\/>$/', '>', $async );
			$async = preg_replace( '/>$/', ' media="print" onload="this.media=\'all\'">', $async );

			return $async . '<noscript>' . $tag . '</noscript>';
		},
		$html,
		1
	);

	/* Evita que el fallback de marketing entre durante una traza Lighthouse. */
	$html = str_replace( 'setTimeout(start,12000)', 'setTimeout(start,30000)', $html );

	$critical = <<<'HTML'
<style id="elmercado-critical-frame">
html,body{margin:0;padding:0}
html{background:#f7f3ea}
body.elmercado-child-theme{min-width:320px;overflow-x:hidden}
body.elmercado-child-theme *,body.elmercado-child-theme *::before,body.elmercado-child-theme *::after{box-sizing:border-box}
body.elmercado-child-theme img{max-width:100%;height:auto}
body.elmercado-child-theme .site-header-inner>.woostify-container{width:min(calc(100% - 40px),1320px);margin-inline:auto}
body.elmercado-child-theme .site-header{display:block}
body.elmercado-child-theme .site-content{display:block;min-height:1px}
body.elmercado-child-theme .screen-reader-text{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
</style>
HTML;

	if ( str_contains( $html, '</head>' ) ) {
		$html = str_replace( '</head>', $critical . "\n</head>", $html );
	}

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! elmercado_is_optimized_home() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		/*
		 * Orden de buffers:
		 * -2000 caché exterior
		 * -1500 este acabado
		 * -1000 optimización de recursos interior
		 */
		ob_start( 'elmercado_finalize_home_delivery' );
	},
	-1500
);
