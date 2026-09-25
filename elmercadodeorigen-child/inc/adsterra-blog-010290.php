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
			.emo-adsterra-slot--rectangle {
				margin-top: 30px;
				margin-bottom: 30px;
			}
			.emo-adsterra-slot--tall-rectangle {
				display: none !important;
			}
		}
		@media (max-width: 519px) {
			.emo-adsterra-slot--footer-banner {
				display: none !important;
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

	function hasCreative() {
		if (document.body.querySelector('iframe,object,embed,img,video,canvas')) {
			return true;
		}
		var nodes = Array.prototype.slice.call(document.body.children);
		return nodes.some(function (node) {
			if (!node || node.tagName === 'SCRIPT' || node.tagName === 'STYLE') {
				return false;
			}
			return node.children.length > 0 || (node.textContent || '').trim() !== '';
		});
	}

	function announceWhenReady() {
		if (sent || !hasCreative()) {
			return;
		}
		sent = true;
		window.parent.postMessage({
			type: 'emo-adsterra-rendered',
			token: token
		}, window.location.origin);
	}

	new MutationObserver(announceWhenReady).observe(document.body, {
		childList: true,
		subtree: true,
		attributes: true
	});
	window.addEventListener('load', announceWhenReady);
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
<script src="https://www.highrevenueformat.com/<?php echo esc_attr( $unit['key'] ); ?>/invoke.js"></script>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'elmercado_adsterra_frame_response_010291', 0 );

function elmercado_adsterra_enqueue_controller_010290(): void {
	if ( ! elmercado_adsterra_is_blog_post_request_010290() ) {
		return;
	}

	$handle = 'elmercado-adsterra-geo-010290';
	$src    = ELMERCADO_THEME_URL . '/assets/js/adsterra-geo-010290.js';
	$path   = ELMERCADO_THEME_PATH . '/assets/js/adsterra-geo-010290.js';
	$ver    = is_readable( $path ) ? (string) filemtime( $path ) : ELMERCADO_THEME_VERSION;

	wp_enqueue_script( $handle, $src, array(), $ver, false );
	wp_localize_script(
		$handle,
		'ElMercadoAdsterraGeo',
		array(
			'endpoint'      => esc_url_raw( rest_url( 'elmercado/v1/blog-ad-eligibility' ) ),
			'frameEndpoint' => esc_url_raw( home_url( '/' ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'elmercado_adsterra_enqueue_controller_010290', 41 );
