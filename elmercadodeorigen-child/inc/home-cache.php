<?php
/**
 * Caché breve de la portada pública ya optimizada.
 *
 * Se utiliza únicamente para peticiones GET anónimas, sin carrito, sesión de
 * WooCommerce, parámetros ni vista previa. La tienda, producto, checkout,
 * cuentas y usuarios identificados nunca pasan por este caché.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clave versionada: cada despliegue del tema invalida el HTML anterior.
 */
function elmercado_home_cache_key(): string {
	return 'elmercado_home_' . md5( ELMERCADO_THEME_VERSION . '|' . home_url( '/' ) );
}

/**
 * Comprueba si la petición admite una respuesta pública compartida.
 */
function elmercado_can_cache_home_request(): bool {
	if ( ! elmercado_is_optimized_home() || is_user_logged_in() || is_preview() || is_customize_preview() ) {
		return false;
	}

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';

	if ( 'GET' !== $method || wp_doing_ajax() || defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	if ( ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo decide si se omite el caché.
		return false;
	}

	foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
		$name = (string) $cookie_name;

		if (
			str_starts_with( $name, 'wordpress_logged_in_' )
			|| str_starts_with( $name, 'wp_woocommerce_session_' )
			|| in_array( $name, array( 'woocommerce_items_in_cart', 'woocommerce_cart_hash' ), true )
		) {
			return false;
		}
	}

	return true;
}

/**
 * Cabeceras transparentes para comprobar el estado sin modificar el contenido.
 */
function elmercado_home_cache_header( string $status ): void {
	if ( ! headers_sent() ) {
		header( 'X-El-Mercado-Cache: ' . $status );
		header( 'Vary: Cookie', false );
	}
}

/**
 * Sirve una copia o inicia la captura exterior. El buffer de optimización de
 * output-optimization.php se abre después y, por tanto, el caché almacena el
 * documento final ya depurado.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( ! elmercado_can_cache_home_request() ) {
			return;
		}

		$key    = elmercado_home_cache_key();
		$cached = get_transient( $key );

		if ( is_string( $cached ) && '' !== $cached ) {
			elmercado_home_cache_header( 'HIT' );
			header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
			echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Documento HTML previamente generado por WordPress.
			exit;
		}

		elmercado_home_cache_header( 'MISS' );
		ob_start(
			static function ( string $html ) use ( $key ): string {
				if ( '' !== $html && false !== stripos( $html, '</html>' ) && false === stripos( $html, 'wp-die-message' ) ) {
					set_transient( $key, $html, 10 * MINUTE_IN_SECONDS );
				}

				return $html;
			}
		);
	},
	-2000
);

/**
 * Invalida cuando cambian productos, stock, pedidos o datos que alimentan la
 * selección comercial de la portada.
 */
function elmercado_flush_home_cache(): void {
	delete_transient( elmercado_home_cache_key() );
}

add_action( 'save_post_product', 'elmercado_flush_home_cache' );
add_action( 'before_delete_post', 'elmercado_flush_home_cache' );
add_action( 'woocommerce_product_set_stock', 'elmercado_flush_home_cache' );
add_action( 'woocommerce_variation_set_stock', 'elmercado_flush_home_cache' );
add_action( 'woocommerce_order_status_changed', 'elmercado_flush_home_cache' );
add_action( 'created_product_cat', 'elmercado_flush_home_cache' );
add_action( 'edited_product_cat', 'elmercado_flush_home_cache' );
add_action( 'delete_product_cat', 'elmercado_flush_home_cache' );
