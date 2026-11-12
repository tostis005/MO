<?php
/**
 * Plugin Name: EMDO — Black Friday 2026 (campaña preparada)
 * Description: Landing de Black Friday y cortinilla pública diaria. No modifica precios, cupones, monedero ni carrito.
 * Version: 1.0.0
 * Author: El Mercado de Origen
 *
 * Copia independiente del tema para no sincronizar ni sustituir files de producción.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * La landing ya utiliza la URL definitiva /black-friday-2026/.
 * La landing mantiene su URL definitiva y su estructura separada.
 * La cortinilla global usa el mismo JS con frecuencia máxima diaria.
 */
function emdo_bf2026_requested_path(): string {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return '';
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$base = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $base && 0 === strpos( $path, $base . '/' ) ) {
		$path = substr( $path, strlen( $base ) + 1 );
	}
	return $path;
}

function emdo_bf2026_production_preview_route(): bool {
	return 'black-friday-2026' === emdo_bf2026_requested_path();
}

// Conservar los enlaces compartidos de la antigua vista previa, sin contenido duplicado.
add_action( 'template_redirect', static function (): void {
	if ( 'vista-previa-black-friday-2026' !== emdo_bf2026_requested_path() ) {
		return;
	}
	wp_safe_redirect( home_url( '/black-friday-2026/' ), 301, 'EMDO Black Friday' );
	exit;
}, -101 );

add_action( 'template_redirect', static function (): void {
	if ( ! emdo_bf2026_production_preview_route() ) {
		return;
	}
	$file = __DIR__ . '/emdo-black-friday-2026-preview/landing.php';
	if ( ! is_readable( $file ) ) {
		status_header( 503 );
		wp_die( esc_html__( 'Vista previa temporalmente no disponible.', 'elmercadodeorigen' ), '', array( 'response' => 503 ) );
	}
	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404 = false;
	}
	status_header( 200 );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
	require $file;
	exit;
}, -100 );

/**
 * Mostrar en el escaparate una sola vez al día por navegador.
 * Disponible desde cualquier página pública, sin excepciones en checkout
 * ni carrito: el visitante solo la ve la primera vez del día.
 */
function emdo_bf2026_global_curtain_eligible(): bool {
	if ( emdo_bf2026_production_preview_route()
		|| is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ( defined( 'DOING_CRON' ) && DOING_CRON )
		|| is_feed()
		|| 'yes' !== get_option( 'emdo_bf2026_global_curtain_enabled', 'yes' ) ) {
		return false;
	}
	return true;
}

add_action( 'wp_enqueue_scripts', static function (): void {
	$is_landing = emdo_bf2026_production_preview_route();
	if ( ! $is_landing && ! emdo_bf2026_global_curtain_eligible() ) {
		return;
	}
	$base_url = content_url( '/mu-plugins/emdo-black-friday-2026-preview/' );
	$base_dir = __DIR__ . '/emdo-black-friday-2026-preview/';
	// La hoja global solo contiene estilos de la cortinilla: no afecta al
	// tema ni al rendimiento de las páginas de producto/tienda.
	$css_file = $is_landing ? 'preview.css' : 'curtain.css';
	wp_dequeue_style( 'emdo-bf2026-preview' );
	wp_deregister_style( 'emdo-bf2026-preview' );
	wp_enqueue_style(
		'emdo-bf2026-preview',
		$base_url . $css_file,
		array(),
		is_readable( $base_dir . $css_file ) ? (string) filemtime( $base_dir . $css_file ) : '1.0'
	);
	wp_dequeue_script( 'emdo-bf2026-preview' );
	wp_deregister_script( 'emdo-bf2026-preview' );
	wp_enqueue_script(
		'emdo-bf2026-preview',
		$base_url . 'preview.js',
		array(),
		is_readable( $base_dir . 'preview.js' ) ? (string) filemtime( $base_dir . 'preview.js' ) : '1.0',
		true
	);
}, 999 );

add_filter( 'body_class', static function ( array $classes ): array {
	if ( emdo_bf2026_production_preview_route() ) {
		$classes[] = 'emdo-bf2026-page';
	}
	return array_unique( $classes );
}, 999 );

add_filter( 'pre_get_document_title', static function ( $title ) {
	return emdo_bf2026_production_preview_route()
		? 'Black Friday 2026 | El Mercado de Origen'
		: $title;
}, 999 );

add_filter( 'wp_robots', static function ( array $robots ): array {
	if ( emdo_bf2026_production_preview_route() ) {
		unset( $robots['index'], $robots['follow'] );
		$robots['noindex']   = true;
		$robots['nofollow']  = true;
		$robots['noarchive'] = true;
	}
	return $robots;
}, 999 );

// A published but unlinked page provides the native WordPress page context,
// so Woostify renders its regular desktop/mobile header, menus and cart.
add_filter( 'wp_sitemaps_posts_query_args', static function ( array $args, string $post_type ): array {
	if ( 'page' !== $post_type ) {
		return $args;
	}
	$excluded = isset( $args['post__not_in'] ) && is_array( $args['post__not_in'] ) ? $args['post__not_in'] : array();
	foreach ( array( 'black-friday-2026', 'vista-previa-black-friday-2026' ) as $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page instanceof WP_Post ) {
			$excluded[] = (int) $page->ID;
		}
	}
	$args['post__not_in'] = array_unique( $excluded );
	return $args;
}, 10, 2 );


/**
 * Inserta la cortinilla al final de las páginas públicas (una sola instancia).
 * Se mantiene separada de los contenidos SEO y no duplica la landing.
 */
add_action( 'wp_footer', static function (): void {
	if ( ! emdo_bf2026_global_curtain_eligible() ) {
		return;
	}
	// Es la misma paleta que aparece en la landing, pero el resto de la web
	// nunca depende de la plantilla de Black Friday para renderizarse.
	$paleta = get_page_by_path( 'paleta-de-bellota-100-iberico', OBJECT, 'product' );
	$photo = '';
	if ( $paleta instanceof WP_Post && has_post_thumbnail( $paleta->ID ) ) {
		$photo = get_the_post_thumbnail(
			$paleta->ID,
			'large',
			array(
				'class'    => 'bf26-photo',
				'alt'      => 'Paleta ibérica de Hidalgo de la Jara',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
	}
	?>
	<div class="bf26-overlay" id="bf26-overlay" role="dialog" aria-modal="true" aria-labelledby="bf26-overlay-title" aria-describedby="bf26-overlay-desc" hidden>
		<div class="bf26-overlay__backdrop" aria-hidden="true"></div>
		<div class="bf26-overlay__panel" tabindex="-1">
			<button class="bf26-overlay__close" type="button" data-bf26-close aria-label="Cerrar promoción de Black Friday">✕ <span>CERRAR</span></button>
			<div class="bf26-overlay__layout">
				<div class="bf26-overlay__copy">
					<h2 id="bf26-overlay-title">BLACK<br><em>FRIDAY</em></h2>
					<p id="bf26-overlay-desc">Ya está aquí nuestro Black Friday: <strong>15 % de descuento con BFEMDO en Hidalgo de la Jara</strong>, ventajas en jamones seleccionados, regalos y <strong>2 % de cashback con el Club EMDO</strong> al comprar con tu cuenta registrada. <strong>Próximamente sumaremos más promociones: ¡estate atento!</strong></p>
					<div class="bf26-overlay__buttons">
						<a class="bf26-overlay__cta" href="<?php echo esc_url( home_url( '/black-friday-2026/' ) ); ?>">Descubrir las ofertas <span aria-hidden="true">↗</span></a>
						<button type="button" class="bf26-overlay__skip" data-bf26-close>Ahora no, seguir comprando</button>
					</div>
				</div>
				<div class="bf26-overlay__art" aria-hidden="true">
					<div class="bf26-overlay__circle"></div>
					<div class="bf26-overlay__photo">
						<?php
						if ( $photo ) {
							echo wp_kses_post( $photo );
						} else {
							echo '<div class="bf26-image-fallback">BLACK<br><strong>FRIDAY</strong></div>';
						}
						?>
					</div>
					<div class="bf26-overlay__discount bf26-campaign-stamp"><small>EDICIÓN 2026</small><strong>BLACK<em>FRIDAY</em></strong><span>OFERTAS <b aria-hidden="true">✳</b> REGALOS</span></div>
					<div class="bf26-overlay__asterisk">✳</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}, 99 );
