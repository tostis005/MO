<?php
/**
 * Experiencia de compra: avisos, carrito, checkout y confirmación accesible.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indica si la vista necesita la capa visual transaccional completa.
 */
function elmercado_is_commerce_surface(): bool {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return is_front_page();
	}

	return is_front_page()
		|| is_woocommerce()
		|| is_cart()
		|| is_checkout()
		|| is_account_page();
}

/**
 * Minificación conservadora para CSS insertado en la portada.
 */
function elmercado_compact_css( string $css ): string {
	$css = (string) preg_replace( '!/\*.*?\*/!s', '', $css );
	$css = (string) preg_replace( '/\s+/', ' ', $css );
	$css = str_replace( array( ' {', '{ ', ' }', '; ', ': ', ', ' ), array( '{', '{', '}', ';', ':', ',' ), $css );

	return trim( $css );
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$css_relative = '/assets/css/commerce.css';
		$css_file     = ELMERCADO_THEME_PATH . $css_relative;

		if ( elmercado_is_commerce_surface() && is_readable( $css_file ) ) {
			if ( function_exists( 'elmercado_is_optimized_home' ) && elmercado_is_optimized_home() ) {
				$parent_handle = wp_style_is( 'woostify-parent-style', 'registered' )
					? 'woostify-parent-style'
					: ( wp_style_is( 'woostify-parent', 'registered' ) ? 'woostify-parent' : '' );
				$content       = file_get_contents( $css_file );

				if ( '' !== $parent_handle && false !== $content ) {
					wp_add_inline_style( $parent_handle, elmercado_compact_css( $content ) );
				}
			} else {
				wp_enqueue_style(
					'elmercado-commerce',
					ELMERCADO_THEME_URL . $css_relative,
					array( 'elmercado-editorial' ),
					elmercado_asset_version( $css_relative )
				);
			}
		}

		wp_enqueue_script(
			'elmercado-commerce',
			ELMERCADO_THEME_URL . '/assets/js/commerce.js',
			array(),
			elmercado_asset_version( '/assets/js/commerce.js' ),
			true
		);
		wp_script_add_data( 'elmercado-commerce', 'strategy', 'defer' );

		wp_localize_script(
			'elmercado-commerce',
			'elMercadoCommerce',
			array(
				'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/carrito/' ),
				'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/finalizar-compra/' ),
			)
		);
	},
	10120
);

/**
 * Introducción del carrito y contenedor de dos columnas.
 */
add_action(
	'woocommerce_before_cart',
	static function (): void {
		?>
		<section class="emo-cart-intro" aria-labelledby="emo-cart-heading">
			<div>
				<span class="emo-kicker"><?php esc_html_e( 'Tu selección', 'elmercadodeorigen' ); ?></span>
				<h2 id="emo-cart-heading"><?php esc_html_e( 'Revisa tu carrito', 'elmercadodeorigen' ); ?></h2>
			</div>
			<p><?php esc_html_e( 'Comprueba cantidades y productos antes de continuar. Verás el coste final y las opciones disponibles en el siguiente paso.', 'elmercadodeorigen' ); ?></p>
		</section>
		<div class="emo-cart-layout">
		<?php
	},
	5
);

add_action(
	'woocommerce_after_cart',
	static function (): void {
		echo '</div>';
	},
	100
);

add_action(
	'woocommerce_after_cart_totals',
	static function (): void {
		?>
		<div class="emo-cart-assurance" aria-label="Información de compra">
			<span><?php esc_html_e( 'Pago protegido durante todo el proceso', 'elmercadodeorigen' ); ?></span>
			<span><?php esc_html_e( 'Información clara antes de confirmar', 'elmercadodeorigen' ); ?></span>
			<span><?php esc_html_e( 'Atención cercana si necesitas ayuda', 'elmercadodeorigen' ); ?></span>
		</div>
		<?php
	}
);

/**
 * Introducción al checkout. No altera campos, pagos ni validaciones.
 */
add_action(
	'woocommerce_before_checkout_form',
	static function (): void {
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		?>
		<section class="emo-checkout-intro" aria-labelledby="emo-checkout-heading">
			<div>
				<span class="emo-kicker"><?php esc_html_e( 'Último paso', 'elmercadodeorigen' ); ?></span>
				<h2 id="emo-checkout-heading"><?php esc_html_e( 'Datos y pago', 'elmercadodeorigen' ); ?></h2>
			</div>
			<p><?php esc_html_e( 'Completa tus datos, revisa el resumen y confirma únicamente cuando toda la información sea correcta.', 'elmercadodeorigen' ); ?></p>
		</section>
		<?php
	},
	4
);
