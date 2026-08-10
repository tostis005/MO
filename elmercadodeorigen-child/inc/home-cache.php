<?php
/**
 * Caché breve de la portada pública ya optimizada.
 *
 * Se utiliza únicamente para peticiones GET anónimas, sin carrito, sesión de
 * WooCommerce, parámetros ni vista previa. La tienda, producto, checkout,
 * cuentas y usuarios identificados nunca pasan por este caché.
 *
 * Además del transient, mantiene una copia estática que el drop-in
 * advanced-cache.php puede servir antes de cargar plugins y tema.
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
 * Ruta de la copia estática que consume el drop-in temprano.
 */
function elmercado_home_static_cache_file(): string {
	return WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html';
}

/**
 * Guarda de forma atómica el documento final de Home para el cache temprano.
 */
function elmercado_write_home_static_cache( string $html ): void {
	if ( '' === $html || false === stripos( $html, '</html>' ) || false !== stripos( $html, 'wp-die-message' ) ) {
		return;
	}

	$file      = elmercado_home_static_cache_file();
	$directory = dirname( $file );

	if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
		return;
	}

	$tmp = tempnam( $directory, 'home-' );
	if ( false === $tmp ) {
		return;
	}

	$written = file_put_contents( $tmp, $html, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	if ( false === $written || $written < strlen( $html ) ) {
		@unlink( $tmp );
		return;
	}

	@chmod( $tmp, 0644 );
	if ( ! @rename( $tmp, $file ) ) {
		@unlink( $tmp );
	}
}

/**
 * Comprueba si la petición admite una respuesta pública compartida.
 */
function elmercado_can_cache_home_request(): bool {
	if ( ! elmercado_is_optimized_home() || is_user_logged_in() || is_preview() || is_customize_preview() ) {
		return false;
	}

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';

	if ( 'GET' !== $method || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
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
 * Sirve una copia o inicia la captura exterior. El buffer almacena el documento
 * final ya depurado y lo replica también al fichero usado por advanced-cache.
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
			$file  = elmercado_home_static_cache_file();
			$mtime = is_file( $file ) ? @filemtime( $file ) : false;
			if ( ! is_readable( $file ) || false === $mtime || ( time() - (int) $mtime ) > 10 * MINUTE_IN_SECONDS ) {
				elmercado_write_home_static_cache( $cached );
			}

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
					elmercado_write_home_static_cache( $html );
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
	$file = elmercado_home_static_cache_file();
	if ( is_file( $file ) ) {
		@unlink( $file );
	}
}

add_action( 'save_post_product', 'elmercado_flush_home_cache' );
add_action( 'before_delete_post', 'elmercado_flush_home_cache' );
add_action( 'woocommerce_product_set_stock', 'elmercado_flush_home_cache' );
add_action( 'woocommerce_variation_set_stock', 'elmercado_flush_home_cache' );
add_action( 'woocommerce_order_status_changed', 'elmercado_flush_home_cache' );
add_action( 'created_product_cat', 'elmercado_flush_home_cache' );
add_action( 'edited_product_cat', 'elmercado_flush_home_cache' );
add_action( 'delete_product_cat', 'elmercado_flush_home_cache' );
