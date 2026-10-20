<?php
/**
 * Cierre visual y estructural de la versión editorial.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Añade los últimos ajustes sin crear peticiones CSS adicionales. En la portada
 * se insertan junto a la hoja base; en el resto del sitio, junto a editorial.css.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$stylesheets = array(
			ELMERCADO_THEME_PATH . '/assets/css/editorial-finish.css',
			ELMERCADO_THEME_PATH . '/assets/css/ux-polish.css',
		);
		$contents = array();

		foreach ( $stylesheets as $stylesheet ) {
			if ( ! is_readable( $stylesheet ) ) {
				continue;
			}

			$content = file_get_contents( $stylesheet );
			if ( false !== $content && '' !== trim( $content ) ) {
				$contents[] = $content;
			}
		}

		if ( empty( $contents ) ) {
			return;
		}

		$handle = 'elmercado-editorial';

		if ( function_exists( 'elmercado_is_optimized_home' ) && elmercado_is_optimized_home() ) {
			$handle = wp_style_is( 'woostify-parent-style', 'registered' )
				? 'woostify-parent-style'
				: ( wp_style_is( 'woostify-parent', 'registered' ) ? 'woostify-parent' : $handle );
		}

		wp_add_inline_style( $handle, (string) preg_replace( '!/\*.*?\*/!s', '', implode( "\n", $contents ) ) );
	},
	10100
);

/**
 * Woostify no imprime siempre el título de la tienda. Se construye un encabezado
 * propio para que la página tenga jerarquía visual y un H1 semántico estable.
 */
add_action(
	'woocommerce_before_shop_loop',
	static function (): void {
		if ( ! is_shop() ) {
			return;
		}
		?>
		<header class="emo-shop-title-block">
			<span class="emo-kicker"><?php esc_html_e( 'El mercado', 'elmercadodeorigen' ); ?></span>
			<h1><?php esc_html_e( 'Productos', 'elmercadodeorigen' ); ?></h1>
		</header>
		<?php
	},
	2
);

/**
 * El directorio de productores ya se compone mediante CSS en HEAD.
 * Se retira el antiguo normalizador con MutationObserver, temporizadores
 * y estilos inline: provocaba cambios de columnas tras el primer pintado.
 * La interacción y los enlaces de WCFM permanecen nativos y accesibles.
 */
