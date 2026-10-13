<?php
/**
 * Campaña Navidad 2026: franjas discretas enlazadas a la landing.
 * Módulo autónomo, desplegable sin modificar functions.php ni el trabajo SEO.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_navidad_promo_spanish_2026(): bool {
	if ( is_admin() || is_feed() || wp_doing_ajax() ) { return false; }
	$path = '/' . ltrim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
	return ! preg_match( '~^/en(?:/|$)~i', $path );
}

function emdo_navidad_promo_surface_2026(): string {
	if ( ! emdo_navidad_promo_spanish_2026() ) { return ''; }
	if ( is_front_page() ) { return 'home'; }
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
		return 'shop';
	}
	if ( is_home() ) { return 'journal'; }
	if ( is_singular( 'post' ) ) { return 'post'; }
	if ( is_page( array(
		'cestas-lotes-navidad-empresas',
		'regalos-gourmet-empresas',
		'jamon-iberico-empresas-regalos-corporativos',
		'cestas-de-navidad',
		'lotes-de-navidad',
	) ) ) {
		return 'related';
	}
	return '';
}

function emdo_navidad_promo_markup_2026( string $surface ): string {
	$link = esc_url( function_exists( 'emdo_navidad_2026_url' )
		? emdo_navidad_2026_url()
		: home_url( '/cestas-navidad-empresas-2026/' ) );

	if ( 'home' === $surface ) {
		$title = 'Cestas de Navidad para empresas';
		$description = 'Para empleados y clientes. Presupuestos personalizados.';
		$cta = 'Solicitar presupuesto';
		$variant = 'home';
	} elseif ( 'shop' === $surface ) {
		$title = '¿Necesitas cestas de Navidad para tu empresa?';
		$description = 'Dinos cuántas necesitas y tu presupuesto por cesta.';
		$cta = 'Pedir presupuesto';
		$variant = 'shop';
	} elseif ( 'journal' === $surface ) {
		$title = 'Cestas de Navidad para empleados y clientes';
		$description = 'Solicita una propuesta personalizada para tu empresa.';
		$cta = 'Solicitar presupuesto';
		$variant = 'journal';
	} elseif ( 'related' === $surface ) {
		$title = '¿Necesitas varias cestas o regalos para tu empresa?';
		$description = 'Preparamos presupuestos según cantidad y precio por unidad.';
		$cta = 'Ver cestas y solicitar presupuesto';
		$variant = 'related';
	} else {
		$title = 'Cestas de Navidad para empresas';
		$description = 'Regalos para empleados y clientes. Presupuestos a medida.';
		$cta = 'Solicitar presupuesto';
		$variant = 'post';
	}

	return '<aside class="emdo-navidad-promo emdo-navidad-promo--' . esc_attr( $variant )
		. '" data-emdo-navidad-promo="2026" aria-label="Cestas de Navidad para empresas">'
		. '<div class="emdo-navidad-promo__inner"><div class="emdo-navidad-promo__copy">'
		. '<strong class="emdo-navidad-promo__title">' . esc_html( $title ) . '</strong>'
		. '<span class="emdo-navidad-promo__description">' . esc_html( $description ) . '</span>'
		. '</div><a class="emdo-navidad-promo__link" href="' . $link . '">'
		. esc_html( $cta ) . '<span aria-hidden="true"> →</span></a></div></aside>';
}

/**
 * Inserción de la franja Home como elemento entre secciones, sin tapar el hero.
 * Se ejecuta después de las personalizaciones que reemplazan the_content.
 */
add_filter( 'the_content', static function ( $content ) {
	if ( ! is_string( $content ) || '' === $content || is_admin() || is_feed() ) { return $content; }
	$surface = emdo_navidad_promo_surface_2026();
	if ( false !== strpos( $content, 'data-emdo-navidad-promo' ) ) { return $content; }

	if ( 'home' === $surface && in_the_loop() && is_main_query() ) {
		// La primera sección de la portada es el hero.
		if ( preg_match( '~</section\s*>~i', $content, $m, PREG_OFFSET_CAPTURE ) ) {
			$end = $m[0][1] + strlen( $m[0][0] );
			return substr( $content, 0, $end )
				. emdo_navidad_promo_markup_2026( 'home' )
				. substr( $content, $end );
		}
		return emdo_navidad_promo_markup_2026( 'home' ) . $content;
	}

	if ( 'post' === $surface && (int) get_the_ID() === (int) get_queried_object_id() ) {
		// Reemplazar, no duplicar, el CTA antiguo de tres entradas empresariales.
		$content = preg_replace(
			'~<aside\b[^>]*class="[^"]*\bemdo-blog-b2b-context\b[^"]*"[^>]*>.*?</aside\s*>~is',
			'',
			$content
		);
		if ( ! is_string( $content ) ) { return ''; }
		$cta = emdo_navidad_promo_markup_2026( 'post' );
		// El CTA aparece dentro de la lectura pero no interrumpe la introducción.
		if ( preg_match_all( '~</p\s*>~i', $content, $matches, PREG_OFFSET_CAPTURE ) && count( $matches[0] ) >= 4 ) {
			$target = $matches[0][3];
			$offset = $target[1] + strlen( $target[0] );
			return substr( $content, 0, $offset ) . $cta . substr( $content, $offset );
		}
		return $content . $cta;
	}

	if ( 'related' === $surface && in_the_loop() && is_main_query() ) {
		return $content . emdo_navidad_promo_markup_2026( 'related' );
	}
	return $content;
}, 89 );

/**
 * WooCommerce: la franja se inserta dentro del encabezado del catálogo.
 *
 * NO imprimir en woocommerce_before_main_content: ese hook se ejecuta antes
 * de que Woostify abra su wrapper de catálogo y convierte la franja en un
 * hermano de la columna principal. En escritorio, el flexbox comprime todo
 * el catálogo a unos pocos píxeles, aunque en móvil parece correcto.
 * La inserción de primer pintado arriba respeta ese encabezado interno.
 * El fallback de wp_footer sigue disponible si alguna plantilla no lo incluye.
 */

/**
 * Keep the Christmas banner at its existing, verified location, but include it
 * in the original HTML rather than inserting it on DOMContentLoaded.
 *
 * Shop layout: <main.site-main> -> <header.emo-shop-title-block> -> first child.
 * This avoids the 153px mobile banner shifting catalogue products after paint.
 * Other routes and all translated English pages retain their existing behavior.
 */
function emdo_navidad_shop_first_paint_20261013( string $html ): string {
	if ( '' === $html || false === stripos( $html, 'emo-shop-title-block' ) ) {
		return $html;
	}

	// Ignore the fallback <template>: it is not a banner rendered on the page.
	$fallback_start = stripos( $html, '<template id="emdo-navidad-promo-fallback-2026"' );
	$rendered_html  = false === $fallback_start ? $html : substr( $html, 0, $fallback_start );
	if ( preg_match( '~<aside\b[^>]*\bdata-emdo-navidad-promo=["\']2026["\']~i', $rendered_html ) ) {
		return $html;
	}

	if ( ! preg_match(
		'~<header\b[^>]*class=(["\'])[^"\']*\bemo-shop-title-block\b[^"\']*\1[^>]*>~i',
		$html,
		$match,
		PREG_OFFSET_CAPTURE
	) ) {
		return $html;
	}
	$position = (int) $match[0][1] + strlen( $match[0][0] );
	return substr_replace( $html, emdo_navidad_promo_markup_2026( 'shop' ), $position, 0 );
}

add_action( 'template_redirect', static function (): void {
	if ( 'shop' !== emdo_navidad_promo_surface_2026() ) {
		return;
	}
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}
	ob_start( 'emdo_navidad_shop_first_paint_20261013' );
}, -1200 );

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( ! emdo_navidad_promo_surface_2026() ) { return; }
	$filename = get_stylesheet_directory() . '/assets/css/navidad-2026-promo.css';
	wp_enqueue_style(
		'emdo-navidad-promo-2026',
		get_stylesheet_directory_uri() . '/assets/css/navidad-2026-promo.css',
		array(),
		is_readable( $filename ) ? (string) filemtime( $filename ) : '1'
	);
} );

/**
 * Respaldo de posición para archivos que no pasan por the_content/WooCommerce.
 * Se ejecuta una sola vez y no duplica banners renderizados en PHP.
 */
add_action( 'wp_footer', static function (): void {
	$surface = emdo_navidad_promo_surface_2026();
	if ( ! in_array( $surface, array( 'home', 'shop', 'journal', 'post', 'related' ), true ) ) { return; }
	$markup = emdo_navidad_promo_markup_2026( $surface );
	?>
	<template id="emdo-navidad-promo-fallback-2026"><?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup generado y escapado. ?></template>
	<script id="emdo-navidad-promo-fallback-script-2026">
	(function () {
		var surface = <?php echo wp_json_encode( $surface ); ?>;
		function place() {
			if (document.querySelector('[data-emdo-navidad-promo="2026"]')) { return; }
			var source = document.getElementById('emdo-navidad-promo-fallback-2026');
			if (!source || !source.content) { return; }
			var banner = source.content.firstElementChild;
			if (!banner) { return; }
			var target = null, before = false;
			if (surface === 'home') {
				target = document.querySelector('main .emo-hero, .emo-home .emo-hero, main .home-hero, main section');
			} else if (surface === 'shop') {
				var candidates = document.querySelectorAll('main .woocommerce-products-header, .woocommerce .woocommerce-products-header, main .emo-shop-header, main .emo-market-header');
				var header = null;
				for (var j = 0; j < candidates.length; j++) {
					var candidate = candidates[j];
					var rect = candidate.getBoundingClientRect(), style = window.getComputedStyle(candidate);
					if (rect.width > 0 && rect.height > 0 && style.visibility !== 'hidden' && style.display !== 'none') {
						header = candidate;
						break;
					}
				}
				if (!header) {
					var headings = document.querySelectorAll('main h1, h1.page-title, h1.woocommerce-products-header__title');
					for (var i = 0; i < headings.length; i++) {
						var h = headings[i];
						var cs = window.getComputedStyle(h);
						if (h.getBoundingClientRect().width > 0 && h.getBoundingClientRect().height > 0
							&& cs.visibility !== 'hidden' && cs.display !== 'none') {
							header = h.parentElement;
							break;
						}
					}
				}
				// Se coloca COMO HIJO del bloque de título, nunca junto a los
				// contenedores flex de dos columnas del catálogo.
				if (header) {
					header.insertBefore(banner, header.firstChild);
					return;
				}
				return;
			} else if (surface === 'journal') {
				target = document.querySelector('main .emo-journal-hero, main header');
			} else if (surface === 'post') {
				target = document.querySelector('main#primary .emo-article-content');
				if (target) {
					var paragraphs = target.querySelectorAll('p');
					if (paragraphs.length > 3) { target = paragraphs[3]; }
				}
			} else if (surface === 'related') {
				target = document.querySelector('main .entry-content, main article, main');
			}
			if (!target || !target.parentNode) { return; }
			if (surface === 'post' && target.classList && target.classList.contains('emo-article-content')) {
				target.appendChild(banner);
			} else {
				target.parentNode.insertBefore(banner, before ? target : target.nextSibling);
			}
		}
		if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', place, {once:true}); }
		else { place(); }
	})();
	</script>
	<?php
}, 65 );
