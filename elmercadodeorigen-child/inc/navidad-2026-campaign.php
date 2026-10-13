<?php
/**
 * Campaña Navidad 2026. Dos URLs virtuales independientes (sin alterar home,
 * tienda, blog ni landings previas). No requiere crear páginas en la BBDD.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_navidad_2026_route(): string {
	if ( is_admin() ) { return ''; }
	$requested = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = trim( (string) wp_parse_url( $requested, PHP_URL_PATH ), '/' );
	$base = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( '' !== $base && 0 === strpos( $path, $base . '/' ) ) {
		$path = substr( $path, strlen( $base ) + 1 );
	}
	if ( in_array( $path, array( 'cestas-navidad-empresas-2026', 'vista-previa-banners-navidad-2026' ), true ) ) {
		return $path;
	}
	return '';
}

function emdo_navidad_2026_url( string $slug = 'cestas-navidad-empresas-2026' ): string {
	return home_url( '/' . $slug . '/' );
}

add_action( 'template_redirect', static function (): void {
	$route = emdo_navidad_2026_route();
	if ( ! $route ) { return; }
	$filename = 'cestas-navidad-empresas-2026' === $route
		? 'templates/navidad-2026-landing.php'
		: 'templates/navidad-2026-banners.php';
	$file = get_stylesheet_directory() . '/' . $filename;
	if ( ! is_readable( $file ) ) { return; }
	global $wp_query;
	if ( $wp_query instanceof WP_Query ) { $wp_query->is_404 = false; }
	status_header( 200 );
	require $file;
	exit;
}, 0 );

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( ! emdo_navidad_2026_route() ) { return; }
	$path = get_stylesheet_directory() . '/assets/css/navidad-2026.css';
	wp_enqueue_style(
		'emdo-navidad-2026',
		get_stylesheet_directory_uri() . '/assets/css/navidad-2026.css',
		array(),
		is_readable( $path ) ? (string) filemtime( $path ) : '1'
	);
} );

add_filter( 'body_class', static function ( array $classes ): array {
	if ( emdo_navidad_2026_route() ) { $classes[] = 'emdo-navidad-2026-page'; }
	return $classes;
} );

add_filter( 'pre_get_document_title', static function ( $title ) {
	$route = emdo_navidad_2026_route();
	if ( 'cestas-navidad-empresas-2026' === $route ) {
		return 'Cestas de Navidad para empresas 2026 | El Mercado de Origen';
	}
	if ( 'vista-previa-banners-navidad-2026' === $route ) {
		return 'Vista previa de banners de Navidad | El Mercado de Origen';
	}
	return $title;
}, 120 );

add_action( 'wp_head', static function (): void {
	$route = emdo_navidad_2026_route();
	if ( ! $route ) { return; }
	if ( 'cestas-navidad-empresas-2026' === $route ) {
		echo '<meta name="description" content="Cestas de Navidad y regalos de empresa con productos seleccionados de origen. Cuéntanos cuántas necesitas y tu presupuesto: preparamos una propuesta personalizada.">' . "\n";
	} else {
		echo '<meta name="robots" content="noindex,nofollow">' . "\n";
	}
}, 1 );

add_filter( 'wp_robots', static function ( array $robots ): array {
	if ( 'vista-previa-banners-navidad-2026' === emdo_navidad_2026_route() ) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'] );
	}
	return $robots;
} );

/**
 * Formulario de captación. Envia un email al administrador configurado en WP.
 * No suscribe al visitante a campañas de marketing ni guarda sus datos.
 */
function emdo_navidad_2026_submit(): void {
	if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
		wp_die( esc_html__( 'Método no permitido.', 'elmercadodeorigen' ), '', array( 'response' => 405 ) );
	}
	$nonce = isset( $_POST['emdo_navidad_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['emdo_navidad_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'emdo_navidad_presupuesto' ) ) {
		wp_die( 'Sesión caducada. Recarga la página e inténtalo de nuevo.', '', array( 'response' => 403 ) );
	}
	$redirect = emdo_navidad_2026_url();
	if ( ! empty( $_POST['emdo_web_field'] ) ) {
		wp_safe_redirect( add_query_arg( 'estado', 'enviada', $redirect ) );
		exit;
	}
	$name = isset( $_POST['emdo_nombre'] ) ? sanitize_text_field( wp_unslash( $_POST['emdo_nombre'] ) ) : '';
	$email = isset( $_POST['emdo_email'] ) ? sanitize_email( wp_unslash( $_POST['emdo_email'] ) ) : '';
	$business = isset( $_POST['emdo_empresa'] ) ? sanitize_text_field( wp_unslash( $_POST['emdo_empresa'] ) ) : '';
	$phone = isset( $_POST['emdo_telefono'] ) ? sanitize_text_field( wp_unslash( $_POST['emdo_telefono'] ) ) : '';
	$quantity = isset( $_POST['emdo_cantidad'] ) ? absint( $_POST['emdo_cantidad'] ) : 0;
	$budget = isset( $_POST['emdo_presupuesto'] ) ? sanitize_text_field( wp_unslash( $_POST['emdo_presupuesto'] ) ) : '';
	$date = isset( $_POST['emdo_fecha'] ) ? sanitize_text_field( wp_unslash( $_POST['emdo_fecha'] ) ) : '';
	$message = isset( $_POST['emdo_detalles'] ) ? sanitize_textarea_field( wp_unslash( $_POST['emdo_detalles'] ) ) : '';
	$consent = isset( $_POST['emdo_privacidad'] ) && '1' === (string) $_POST['emdo_privacidad'];
	$budgets = array( 'sin-definir', 'menos-30', '30-50', '50-80', '80-120', '120-200', 'mas-200' );
	if ( strlen( $name ) < 2 || strlen( $name ) > 120 || ! is_email( $email )
		|| strlen( $email ) > 190 || $quantity < 1 || $quantity > 100000
		|| ! in_array( $budget, $budgets, true ) || ! $consent
		|| strlen( $message ) > 2000 || strlen( $business ) > 160 || strlen( $phone ) > 40 || strlen( $date ) > 100 ) {
		wp_safe_redirect( add_query_arg( 'estado', 'revisar', $redirect ) );
		exit;
	}
	$ip = sanitize_text_field( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$key = 'emdo_navidad_quote_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 36 );
	if ( get_transient( $key ) ) {
		wp_safe_redirect( add_query_arg( 'estado', 'limite', $redirect ) );
		exit;
	}
	set_transient( $key, 1, 3 * MINUTE_IN_SECONDS );
	$admin = sanitize_email( (string) get_option( 'admin_email' ) );
	if ( ! is_email( $admin ) ) {
		wp_safe_redirect( add_query_arg( 'estado', 'error', $redirect ) );
		exit;
	}
	$body = implode( "\n", array(
		'Nueva solicitud de presupuesto — Cestas Navidad 2026',
		'Nombre: ' . $name,
		'Empresa: ' . $business,
		'Correo: ' . $email,
		'Teléfono: ' . $phone,
		'Cantidad: ' . $quantity,
		'Presupuesto por cesta: ' . $budget,
		'Fecha aproximada de entrega: ' . $date,
		'Detalles: ' . $message,
		'Origen: landing de Navidad 2026',
	) );
	$sent = wp_mail(
		$admin,
		'Presupuesto cestas Navidad 2026 — ' . $name,
		$body,
		array( 'Reply-To: ' . $email, 'Content-Type: text/plain; charset=UTF-8' )
	);
	if ( ! $sent ) { delete_transient( $key ); }
	wp_safe_redirect( add_query_arg( 'estado', $sent ? 'enviada' : 'error', $redirect ) . '#presupuesto' );
	exit;
}
add_action( 'admin_post_emdo_navidad_2026_quote', 'emdo_navidad_2026_submit' );
add_action( 'admin_post_nopriv_emdo_navidad_2026_quote', 'emdo_navidad_2026_submit' );
