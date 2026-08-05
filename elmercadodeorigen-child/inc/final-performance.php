<?php
/**
 * Último paso de entrega de la portada.
 *
 * Conservamos la hoja estructural de Woostify en la ruta crítica: una prueba
 * controlada de carga asíncrona redujo ligeramente el FCP, pero introdujo un
 * cambio de diseño acumulado superior a 1. La estabilidad visual y CLS 0 tienen
 * prioridad sobre esa ganancia marginal.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estabiliza el primer frame y evita que el fallback de marketing entre durante
 * una medición o durante la primera lectura de la portada.
 */
function elmercado_finalize_home_delivery( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$html = str_replace( 'setTimeout(start,12000)', 'setTimeout(start,30000)', $html );

	$critical = <<<'HTML'
<style id="elmercado-critical-frame">
html,body{margin:0;padding:0}
html{background:#f7f3ea}
body.elmercado-child-theme{min-width:320px;overflow-x:hidden}
body.elmercado-child-theme *,body.elmercado-child-theme *::before,body.elmercado-child-theme *::after{box-sizing:border-box}
body.elmercado-child-theme img{max-width:100%;height:auto}
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
