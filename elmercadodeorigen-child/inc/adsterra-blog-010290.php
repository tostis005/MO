<?php
/**
 * Adsterra geolocalizado para entradas editoriales.
 *
 * Comparte exactamente la misma elegibilidad geográfica que AdSense:
 * solo se hidratan anuncios cuando WooCommerce confirma que la visita
 * procede de un país sin capacidad de envío activa.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Caché estática breve para la primera visita anónima a artículos.
 *
 * Solo genera HTML compartido para GET canónicos en español, sin cookies ni
 * query string. El drop-in advanced-cache.php puede servir ese archivo antes de
 * arrancar WordPress. La geografía publicitaria continúa resolviéndose en el
 * navegador, por lo que el HTML cacheado no fija un país ni un proveedor.
 */
function elmercado_blog_static_cache_dir_010299(): string {
	return WP_CONTENT_DIR . '/uploads/elmercado-blog-static-v1';
}

function elmercado_blog_static_cache_normalized_path_010299(): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
	$path        = is_string( $path ) && '' !== $path ? '/' . ltrim( $path, '/' ) : '/';

	return '/' !== $path ? rtrim( $path, '/' ) . '/' : '/';
}

function elmercado_blog_static_cache_file_010299( string $path ): string {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : 'www.elmercadodeorigen.com';
	$host = (string) preg_replace( '/:\\d+$/', '', $host );
	$key  = hash( 'sha256', $host . '|' . $path );

	return elmercado_blog_static_cache_dir_010299() . '/' . $key . '.html';
}

/**
 * Permite cachear cookies que no cambian el HTML editorial ni el estado de compra.
 *
 * Cualquier cookie no reconocida sigue forzando WordPress completo. Esto evita
 * compartir HTML de sesiones, login, carrito, wishlist o integraciones futuras.
 */
function elmercado_blog_static_cache_has_stateful_cookie_010300(): bool {
	if ( empty( $_COOKIE ) ) {
		return false;
	}

	$harmless_prefixes = array(
		'_ga',
		'_gid',
		'_gat',
		'_gcl_',
		'_fbp',
		'_fbc',
		'_pin_unauth',
		'cookielawinfo-',
		'sbjs_',
		'tk_',
	);
	$harmless_exact = array(
		'CookieLawInfoConsent',
		'viewed_cookie_policy',
		'total_page',
	);

	foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
		$cookie_name = (string) $cookie_name;

		if ( in_array( $cookie_name, $harmless_exact, true ) ) {
			continue;
		}

		$harmless = false;
		foreach ( $harmless_prefixes as $prefix ) {
			if ( 0 === strpos( $cookie_name, $prefix ) ) {
				$harmless = true;
				break;
			}
		}

		if ( ! $harmless ) {
			return true;
		}
	}

	return false;
}

function elmercado_blog_static_cache_request_010299(): bool {
	if ( is_admin() || ! is_singular( 'post' ) || is_preview() || is_customize_preview() || is_user_logged_in() ) {
		return false;
	}

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
	$query  = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
	if ( 'GET' !== $method || '' !== $query || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	// Solo las cookies analíticas/consentimiento conocidas pueden reutilizar HTML.
	// Cualquier cookie de estado real o desconocida mantiene el bypass seguro.
	if ( elmercado_blog_static_cache_has_stateful_cookie_010300() ) {
		return false;
	}

	$path = elmercado_blog_static_cache_normalized_path_010299();

	// Fase inicial: solo español canónico. Inglés sigue dinámico.
	if ( str_starts_with( $path, '/en/' ) ) {
		return false;
	}

	return '/' !== $path;
}

function elmercado_blog_static_cache_write_010299( string $file, string $html ): void {
	if (
		'' === $html
		|| false === stripos( $html, '<html' )
		|| false === stripos( $html, '</html>' )
		|| false === strpos( $html, 'data-emo-adsterra-slot=' )
		|| false !== stripos( $html, 'wp-die-message' )
		|| false !== stripos( $html, 'WordPress database error' )
	) {
		return;
	}

	$directory = dirname( $file );
	if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
		return;
	}

	$tmp = tempnam( $directory, 'blog-' );
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

function elmercado_blog_static_cache_purge_010299(): void {
	$directory = elmercado_blog_static_cache_dir_010299();
	if ( ! is_dir( $directory ) ) {
		return;
	}

	foreach ( glob( $directory . '/*.html' ) ?: array() as $file ) {
		if ( is_file( $file ) ) {
			@unlink( $file );
		}
	}
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! elmercado_blog_static_cache_request_010299() ) {
			return;
		}

		$path = elmercado_blog_static_cache_normalized_path_010299();
		$file = elmercado_blog_static_cache_file_010299( $path );

		if ( ! headers_sent() ) {
			header( 'X-El-Mercado-Blog-Early-Cache: MISS' );
			header( 'Vary: Cookie', false );
		}

		ob_start(
			static function ( string $html ) use ( $file ): string {
				elmercado_blog_static_cache_write_010299( $file, $html );
				return $html;
			}
		);
	},
	-2500
);

add_action( 'save_post_post', 'elmercado_blog_static_cache_purge_010299' );
add_action( 'save_post_product', 'elmercado_blog_static_cache_purge_010299' );
add_action( 'before_delete_post', 'elmercado_blog_static_cache_purge_010299' );
add_action( 'woocommerce_product_set_stock', 'elmercado_blog_static_cache_purge_010299' );
add_action( 'woocommerce_variation_set_stock', 'elmercado_blog_static_cache_purge_010299' );
add_action( 'created_category', 'elmercado_blog_static_cache_purge_010299' );
add_action( 'edited_category', 'elmercado_blog_static_cache_purge_010299' );
add_action( 'delete_category', 'elmercado_blog_static_cache_purge_010299' );

function elmercado_blog_ad_provider_010290(): string {
	$provider = defined( 'ELMERCADO_BLOG_AD_PROVIDER' ) ? strtolower( trim( (string) ELMERCADO_BLOG_AD_PROVIDER ) ) : 'adsense';
	return in_array( $provider, array( 'adsense', 'adsterra' ), true ) ? $provider : 'adsense';
}

function elmercado_adsterra_is_blog_post_request_010290(): bool {
	return 'adsterra' === elmercado_blog_ad_provider_010290()
		&& ! is_admin()
		&& is_singular( 'post' )
		&& ! is_feed()
		&& ! is_preview();
}

function elmercado_adsterra_slot_010290( string $type, string $class = '' ): string {
	if ( ! elmercado_adsterra_is_blog_post_request_010290() ) {
		return '';
	}

	$classes = 'emo-adsterra-slot emo-adsterra-slot--' . sanitize_html_class( $type );
	if ( '' !== $class ) {
		$classes .= ' ' . sanitize_html_class( $class );
	}

	return sprintf(
		'<div class="%1$s" data-emo-adsterra-slot="%2$s" aria-hidden="true"><span class="emo-adsterra-label">Publicidad</span><div class="emo-adsterra-mount"></div></div>',
		esc_attr( $classes ),
		esc_attr( $type )
	);
}

function elmercado_adsterra_render_responsive_top_010290(): void {
	echo elmercado_adsterra_slot_010290( 'responsive-top' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function elmercado_adsterra_render_skyscraper_010290(): void {
	echo elmercado_adsterra_slot_010290( 'skyscraper' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function elmercado_adsterra_render_footer_banner_010290(): void {
	echo elmercado_adsterra_slot_010290( 'footer-banner' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function elmercado_adsterra_render_native_010290(): void {
	if ( ! elmercado_adsterra_is_blog_post_request_010290() ) {
		return;
	}
	?>
	<div class="emo-shell emo-adsterra-native-shell">
		<?php echo elmercado_adsterra_slot_010290( 'native' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
}

function elmercado_adsterra_insert_content_slots_010290( string $content ): string {
	if ( ! elmercado_adsterra_is_blog_post_request_010290() || '' === trim( $content ) ) {
		return $content;
	}

	$commercial_marker = '<section class="emo-related-products-dynamic"';
	$commercial_offset = strpos( $content, $commercial_marker );

	if ( false === $commercial_offset ) {
		$editorial = $content;
		$suffix    = '';
	} else {
		$editorial = substr( $content, 0, $commercial_offset );
		$suffix    = substr( $content, $commercial_offset );
	}

	$matches = array();
	if ( ! preg_match_all( '/<p\b[^>]*>.*?<\/p>/isu', $editorial, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}

	$paragraphs = array();
	foreach ( $matches[0] as $match ) {
		$html = (string) $match[0];
		$text = trim(
			preg_replace(
				'/\s+/u',
				' ',
				html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' )
			)
		);

		if ( strlen( $text ) < 80 ) {
			continue;
		}

		$paragraphs[] = array(
			'end' => (int) $match[1] + strlen( $html ),
		);
	}

	$count = count( $paragraphs );
	if ( $count < 3 ) {
		return $content;
	}

	$targets = array(
		array(
			'index' => min( 2, $count - 1 ),
			'type'  => 'rectangle',
		),
	);

	if ( $count >= 10 ) {
		$index = min( $count - 2, max( 7, (int) round( ( $count - 1 ) * 0.72 ) ) );
		$targets[] = array(
			'index' => $index,
			'type'  => 'tall-rectangle',
		);
	}

	$insertions = array();
	foreach ( $targets as $target ) {
		$index = (int) $target['index'];
		if ( ! isset( $paragraphs[ $index ] ) ) {
			continue;
		}

		$insertions[] = array(
			'offset' => (int) $paragraphs[ $index ]['end'],
			'html'   => "\n" . elmercado_adsterra_slot_010290( (string) $target['type'] ),
		);
	}

	usort(
		$insertions,
		static function ( array $a, array $b ): int {
			return $b['offset'] <=> $a['offset'];
		}
	);

	foreach ( $insertions as $insertion ) {
		$editorial = substr( $editorial, 0, $insertion['offset'] )
			. $insertion['html']
			. substr( $editorial, $insertion['offset'] );
	}

	return $editorial . $suffix;
}

function elmercado_adsterra_content_filter_010290( $content ) {
	if ( ! elmercado_adsterra_is_blog_post_request_010290() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	return elmercado_adsterra_insert_content_slots_010290( (string) $content );
}
add_filter( 'the_content', 'elmercado_adsterra_content_filter_010290', 13 );

function elmercado_adsterra_styles_010290(): void {
	if ( ! elmercado_adsterra_is_blog_post_request_010290() ) {
		return;
	}
	?>
	<style id="elmercado-adsterra-blog-010290">
		.emo-adsterra-slot {
			display: none;
			box-sizing: border-box;
			max-width: 100%;
			text-align: center;
		}
		.emo-adsterra-slot.is-eligible {
			display: block;
		}
		html.emo-adsterra-adsense-fallback .emo-adsterra-slot.is-adsense-fallback {
			display: block !important;
			position: static !important;
			width: 100% !important;
			max-width: 100% !important;
			height: auto !important;
			min-height: 0 !important;
			margin: clamp(28px, 5vw, 46px) auto !important;
			padding: 0 !important;
			overflow: visible !important;
		}
		html.emo-adsterra-adsense-fallback .emo-adsterra-native-shell.is-adsense-fallback {
			display: block !important;
			margin-top: clamp(34px, 5vw, 58px);
			margin-bottom: clamp(34px, 5vw, 58px);
		}
		html.emo-adsterra-adsense-fallback .emo-adsterra-slot.is-adsense-fallback > .emo-adsterra-mount {
			display: block;
			overflow: visible;
		}
		html.emo-adsterra-adsense-fallback .emo-adsterra-slot.is-adsense-fallback ins.adsbygoogle[data-ad-status="unfilled"] {
			display: none !important;
			height: 0 !important;
			min-height: 0 !important;
			margin: 0 !important;
			padding: 0 !important;
		}
		html.emo-adblock-detected .emo-adsterra-slot,
		html.emo-adblock-detected .emo-adsterra-native-shell {
			display: none !important;
			height: 0 !important;
			min-height: 0 !important;
			max-height: 0 !important;
			margin: 0 !important;
			padding: 0 !important;
			overflow: hidden !important;
		}
		.emo-adsterra-label {
			display: block;
			margin: 0 0 7px;
			color: #807a70;
			font: 700 10px/1.2 Arial, sans-serif;
			letter-spacing: .12em;
			text-transform: uppercase;
		}
		.emo-adsterra-mount {
			display: flex;
			justify-content: center;
			align-items: flex-start;
			max-width: 100%;
			overflow: hidden;
		}
		.emo-adsterra-slot--responsive-top {
			margin: 30px auto 0;
		}
		.emo-adsterra-slot--rectangle,
		.emo-adsterra-slot--tall-rectangle {
			margin: clamp(30px, 5vw, 46px) auto;
		}
		.emo-adsterra-slot--rectangle {
			max-width: 320px;
		}
		.emo-adsterra-slot--tall-rectangle {
			max-width: 180px;
		}
		.emo-adsterra-slot--footer-banner {
			max-width: 488px;
			margin: 30px auto 0;
		}
		.emo-adsterra-native-shell {
			display: none;
			margin: 0;
		}
		.emo-adsterra-native-shell.is-eligible {
			display: block;
			margin-top: clamp(34px, 5vw, 58px);
			margin-bottom: clamp(34px, 5vw, 58px);
		}
		.emo-adsterra-slot--native .emo-adsterra-mount {
			display: block;
			overflow: visible;
		}
		html body.single-post main#primary.emo-article-page .emo-article-main-shell {
			position: relative;
		}
		.emo-adsterra-slot--skyscraper {
			position: absolute;
			right: 0;
			top: clamp(30px, 4.5vw, 52px);
			width: 160px;
			min-height: 620px;
			margin: 0;
		}
		.emo-adsterra-slot--skyscraper.is-eligible {
			display: block;
		}
		@media (max-width: 1160px) {
			.emo-adsterra-slot--skyscraper {
				display: none !important;
			}
		}
		@media (max-width: 767px) {
			.emo-adsterra-slot--responsive-top {
				margin-top: 22px;
			}
			.emo-adsterra-slot--rectangle,
			.emo-adsterra-slot--tall-rectangle,
			.emo-adsterra-slot--footer-banner {
				width: 100%;
				max-width: 100%;
				margin: clamp(24px, 7vw, 36px) auto;
			}
		}
	</style>
	<?php
}
add_action( 'wp_head', 'elmercado_adsterra_styles_010290', 21 );

/**
 * Devuelve un documento publicitario mínimo del propio dominio.
 *
 * Los banners estándar de Adsterra funcionan con document.write durante la
 * carga. Servirlos desde un iframe con URL real evita las diferencias de
 * compatibilidad de about:srcdoc observadas en algunos navegadores móviles.
 */
function elmercado_adsterra_frame_response_010291(): void {
	if ( ! isset( $_GET['emo_adsterra_frame'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( 'adsterra' !== elmercado_blog_ad_provider_010290() ) {
		status_header( 404 );
		exit;
	}

	$type  = sanitize_key( wp_unslash( (string) $_GET['emo_adsterra_frame'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$token = isset( $_GET['emo_adsterra_token'] ) ? sanitize_key( wp_unslash( (string) $_GET['emo_adsterra_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$units = array(
		'responsive-desktop' => array( 'key' => '4e02d145fdb84842713b24a4bade6244', 'width' => 728, 'height' => 90 ),
		'responsive-mobile'  => array( 'key' => '3de18866861c081d6eb282ad00fe2bee', 'width' => 320, 'height' => 50 ),
		'rectangle'          => array( 'key' => 'c1f547d5b9552a71fae2de32c69f2d66', 'width' => 300, 'height' => 250 ),
		'tall-rectangle'     => array( 'key' => 'bd307b1985a219b2694f34d17ceebe8e', 'width' => 160, 'height' => 300 ),
		'skyscraper'         => array( 'key' => '040427a877ae83cac71362a8c92eb779', 'width' => 160, 'height' => 600 ),
		'footer-banner'      => array( 'key' => '985360811e3cd3c3d7d50e3a9ea81484', 'width' => 468, 'height' => 60 ),
	);

	if ( ! isset( $units[ $type ] ) ) {
		status_header( 404 );
		exit;
	}

	$country = function_exists( 'elmercado_adsense_get_visitor_country' ) ? elmercado_adsense_get_visitor_country() : '';
	$eligible = '' !== $country
		&& function_exists( 'elmercado_adsense_country_is_shippable' )
		&& ! elmercado_adsense_country_is_shippable( $country );

	if ( ! $eligible ) {
		status_header( 204 );
		nocache_headers();
		exit;
	}

	$unit = $units[ $type ];
	nocache_headers();
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
	?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<style>html,body{margin:0;padding:0;overflow:hidden;background:transparent}body{display:flex;justify-content:center;align-items:flex-start}</style>
</head>
<body>
<script>
(function () {
	var token = <?php echo wp_json_encode( $token ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	var sent = false;

	function notify(type) {
		if (sent) return;
		sent = true;
		window.parent.postMessage({ type: type, token: token }, window.location.origin);
	}

	function watchMedia(node) {
		if (!node || node.getAttribute('data-emo-adsterra-watch') === '1') return;
		node.setAttribute('data-emo-adsterra-watch', '1');
		node.addEventListener('load', function () { notify('emo-adsterra-rendered'); }, { once: true });

		if (node.tagName === 'IFRAME' && (node.getAttribute('src') || '').trim() !== '') {
			notify('emo-adsterra-rendered');
			return;
		}

		if (node.tagName === 'IMG' && node.complete && node.naturalWidth > 0) {
			notify('emo-adsterra-rendered');
		}
	}

	function inspect() {
		if (sent) return;

		Array.prototype.slice.call(document.body.querySelectorAll('iframe,img,video,object,embed')).forEach(watchMedia);

		var visibleText = Array.prototype.slice.call(document.body.children).some(function (node) {
			if (!node || node.tagName === 'SCRIPT' || node.tagName === 'STYLE') return false;
			if (node.querySelector && node.querySelector('iframe,img,video,object,embed')) return false;
			return (node.textContent || '').trim() !== '';
		});
		if (visibleText) notify('emo-adsterra-rendered');
	}

	new MutationObserver(inspect).observe(document.body, {
		childList: true,
		subtree: true
	});
	inspect();
	window.setTimeout(function () {
		if (!sent) notify('emo-adsterra-blocked');
	}, 6000);
})();
</script>
<script>
window.atOptions = <?php echo wp_json_encode( array(
	'key'    => $unit['key'],
	'format' => 'iframe',
	'height' => (int) $unit['height'],
	'width'  => (int) $unit['width'],
	'params' => (object) array(),
) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
</script>
<script src="https://www.highrevenueformat.com/<?php echo esc_attr( $unit['key'] ); ?>/invoke.js" onerror="window.parent.postMessage({type:'emo-adsterra-blocked',token:<?php echo esc_attr( wp_json_encode( $token ) ); ?>},window.location.origin)"></script>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'elmercado_adsterra_frame_response_010291', 0 );

/**
 * Descubre el controlador publicitario al principio del head para evitar que
 * compita con scripts de analítica y plugins antes de iniciar su descarga.
 */
function elmercado_adsterra_preload_controller_010301(): void {
	if ( ! elmercado_adsterra_is_blog_post_request_010290() ) {
		return;
	}

	$path      = ELMERCADO_THEME_PATH . '/assets/js/adsterra-geo-010290.js';
	$ver       = is_readable( $path ) ? (string) filemtime( $path ) : ELMERCADO_THEME_VERSION;
	$src       = add_query_arg( 'ver', $ver, ELMERCADO_THEME_URL . '/assets/js/adsterra-geo-010290.js' );
	$fast_geo  = ELMERCADO_THEME_URL . '/assets/ad-geo-fast.php';

	printf(
		'<link rel="preconnect" href="https://www.highrevenueformat.com" crossorigin>' . "\n" .
		'<link rel="preconnect" href="https://pl31502847.profitableratecpmnetwork.com" crossorigin>' . "\n" .
		'<link rel="preload" as="script" href="%1$s" fetchpriority="high">' . "\n",
		esc_url( $src )
	);

	?>
	<script id="elmercado-ad-geo-bootstrap-010300">
	(function () {
		'use strict';
		try {
			if (window.sessionStorage && window.sessionStorage.getItem('emo-blog-ad-eligibility-v3')) {
				window.ElMercadoAdGeoBootstrap = Promise.resolve(null);
				return;
			}
		} catch (error) {}

		if (typeof window.fetch !== 'function') return;
		window.ElMercadoAdGeoBootstrap = fetch(
			<?php echo wp_json_encode( esc_url_raw( $fast_geo ) ); ?> + '?_early=' + Date.now(),
			{
				method: 'GET',
				credentials: 'same-origin',
				cache: 'no-store',
				headers: { 'Accept': 'application/json' }
			}
		).then(function (response) {
			if (!response.ok) throw new Error('early_geo_http_' + response.status);
			return response.json();
		});
	}());
	</script>
	<?php
}
add_action( 'wp_head', 'elmercado_adsterra_preload_controller_010301', 1 );

function elmercado_adsterra_enqueue_controller_010290(): void {
	if ( ! elmercado_adsterra_is_blog_post_request_010290() ) {
		return;
	}

	$handle = 'elmercado-adsterra-geo-010290';
	$src    = ELMERCADO_THEME_URL . '/assets/js/adsterra-geo-010290.js';
	$path   = ELMERCADO_THEME_PATH . '/assets/js/adsterra-geo-010290.js';
	$ver    = is_readable( $path ) ? (string) filemtime( $path ) : ELMERCADO_THEME_VERSION;

	wp_enqueue_script( $handle, $src, array(), $ver, false );
	wp_script_add_data( $handle, 'strategy', 'async' );
	wp_localize_script(
		$handle,
		'ElMercadoAdsterraGeo',
		array(
			'endpoint'             => esc_url_raw( rest_url( 'elmercado/v1/blog-ad-eligibility' ) ),
			'fastGeoEndpoint'      => esc_url_raw( ELMERCADO_THEME_URL . '/assets/ad-geo-fast.php' ),
			'frameEndpoint'        => esc_url_raw( ELMERCADO_THEME_URL . '/assets/adsterra-frame.html' ),
			'shippableCountries'   => function_exists( 'elmercado_adsense_get_shippable_countries' ) ? elmercado_adsense_get_shippable_countries() : array(),
			'adsensePublisher'     => defined( 'ELMERCADO_ADSENSE_PUBLISHER' ) ? ELMERCADO_ADSENSE_PUBLISHER : '',
			'adsenseInArticleSlot' => defined( 'ELMERCADO_ADSENSE_INARTICLE_SLOT' ) ? ELMERCADO_ADSENSE_INARTICLE_SLOT : '',
			'slotTimeout'          => 2600,
			'fallbackTimeout'      => 0,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'elmercado_adsterra_enqueue_controller_010290', 1 );

add_filter(
	'script_loader_tag',
	static function ( string $tag, string $handle ): string {
		if ( 'elmercado-adsterra-geo-010290' !== $handle || str_contains( $tag, ' fetchpriority=' ) ) {
			return $tag;
		}

		return str_replace( '<script ', '<script fetchpriority="high" ', $tag );
	},
	5,
	2
);
