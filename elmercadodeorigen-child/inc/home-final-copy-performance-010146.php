<?php
/**
 * Rendimiento final de la portada 0.10.146.
 *
 * Conserva únicamente las optimizaciones de recursos e imágenes de esta capa.
 * El copy se gestiona desde una sola capa definitiva en 0.10.165.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recursos que no tienen interfaz activa en la portada personalizada.
 * Se filtran también al imprimir porque varios plugins actuales encolan tarde.
 *
 * @return string[]
 */
function elmercado_home_unused_style_sources_010146(): array {
	return array(
		'/plugins/elementor/',
		'/uploads/elementor/css/',
		'/plugins/contact-form-7/',
		'/plugins/contact-form-7-honeypot/',
		'/plugins/slide-anything/',
		'/plugins/fluentform/',
		'/plugins/woo-discount-rules/',
		'/plugins/woo-discount-rules-pro/',
		'/plugins/wc-frontend-manager/',
		'/plugins/all-in-one-seo-pack/dist/Lite/assets/css/table-of-contents/',
		'/p/jetpack/',
		'/assets/client/blocks/wc-blocks.css',
		'fonts.googleapis.com/css?family=Roboto:',
		'fonts.googleapis.com/css?family=Roboto+',
		'fonts.googleapis.com/css?family=Roboto%20Slab',
	);
}

/**
 * @return string[]
 */
function elmercado_home_unused_script_sources_010146(): array {
	return array(
		'/plugins/elementor/',
		'/plugins/contact-form-7/',
		'/plugins/contact-form-7-honeypot/',
		'/plugins/slide-anything/',
		'/plugins/fluentform/',
		'/plugins/woo-discount-rules/',
		'/plugins/woo-discount-rules-pro/',
		'/plugins/wc-frontend-manager/',
		'cdn.trustindex.io/loader.js',
		'/wp-includes/js/jquery/ui/core.min.js',
		'/wp-includes/js/dist/i18n.min.js',
		'/themes/woostify/assets/js/arrive.min.js',
		'/themes/woostify/assets/js/woocommerce/quantity-button.min.js',
		'/themes/woostify/assets/js/woocommerce/woocommerce.min.js',
	);
}

/**
 * Vuelve a limpiar las colas justo antes de imprimirlas.
 */
function elmercado_home_late_asset_cleanup_010146(): void {
	if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
		return;
	}

	global $wp_styles, $wp_scripts;

	if ( $wp_styles instanceof WP_Styles ) {
		foreach ( $wp_styles->registered as $handle => $asset ) {
			$src = isset( $asset->src ) ? (string) $asset->src : '';
			foreach ( elmercado_home_unused_style_sources_010146() as $needle ) {
				if ( '' !== $src && str_contains( $src, $needle ) ) {
					wp_dequeue_style( (string) $handle );
					break;
				}
			}
		}
	}

	if ( $wp_scripts instanceof WP_Scripts ) {
		foreach ( $wp_scripts->registered as $handle => $asset ) {
			$src = isset( $asset->src ) ? (string) $asset->src : '';
			foreach ( elmercado_home_unused_script_sources_010146() as $needle ) {
				if ( '' !== $src && str_contains( $src, $needle ) ) {
					wp_dequeue_script( (string) $handle );
					break;
				}
			}
		}
	}
}

add_action( 'wp_print_styles', 'elmercado_home_late_asset_cleanup_010146', PHP_INT_MAX );
add_action( 'wp_print_footer_scripts', 'elmercado_home_late_asset_cleanup_010146', 0 );

add_filter(
	'style_loader_tag',
	static function ( string $html, string $handle, string $href ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return $html;
		}

		foreach ( elmercado_home_unused_style_sources_010146() as $needle ) {
			if ( str_contains( $href, $needle ) ) {
				return '';
			}

		return $html;
	},
	PHP_INT_MAX,
	3
);

add_filter(
	'script_loader_tag',
	static function ( string $html, string $handle, string $src ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return $html;
		}

		foreach ( elmercado_home_unused_script_sources_010146() as $needle ) {
			if ( str_contains( $src, $needle ) ) {
				return '';
			}

		return $html;
	},
	PHP_INT_MAX,
	3
);

/**
 * wp-hooks es dependencia real de Google Listings. La optimización histórica
 * lo retiraba junto con recursos no usados; se recupera al final de la cola.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return;
		}

		if ( wp_script_is( 'wp-hooks', 'registered' ) ) {
			wp_enqueue_script( 'wp-hooks' );
		}
	},
	PHP_INT_MAX
);

/**
 * Convierte las imágenes de las tarjetas de productores de la portada en
 * imágenes responsive de WordPress. Algunas capas históricas eliminan la clase
 * de la tarjeta en el HTML final, por eso también se reconocen por su adjunto.
 */
function elmercado_home_responsive_producer_cards_010252( string $html ): string {
	if ( '' === $html || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $html;
	}

	if (
		! str_contains( $html, 'emo-hero-card__image' )
		&& ! str_contains( $html, 'Tolecarnes-fondo' )
		&& ! str_contains( $html, 'JAMON_ACTO_ECOLOGICO1' )
		&& ! str_contains( $html, 'Aceite-sin-filtrar' )
	) {
		return $html;
	}

	$processor = new WP_HTML_Tag_Processor( $html );
	$changed   = false;

	while ( $processor->next_tag( 'img' ) ) {
		$class = (string) $processor->get_attribute( 'class' );
		$src   = (string) $processor->get_attribute( 'src' );
		if ( '' === $src ) {
			continue;
		}

		$is_producer_card = str_contains( $class, 'emo-hero-card__image' )
			|| str_contains( $src, 'Tolecarnes-fondo' )
			|| str_contains( $src, 'JAMON_ACTO_ECOLOGICO1' )
			|| str_contains( $src, 'Aceite-sin-filtrar' );
		if ( ! $is_producer_card ) {
			continue;
		}

		$attachment_id = attachment_url_to_postid( $src );
		if ( ! $attachment_id ) {
			continue;
		}

		$image  = wp_get_attachment_image_src( $attachment_id, 'medium_large' );
		$srcset = wp_get_attachment_image_srcset( $attachment_id, 'medium_large' );
		if ( ! is_array( $image ) || empty( $image[0] ) || ! is_string( $srcset ) || '' === $srcset ) {
			continue;
		}

		$processor->set_attribute( 'src', $image[0] );
		$processor->set_attribute( 'srcset', $srcset );
		$processor->set_attribute( 'sizes', '(max-width: 767px) calc(100vw - 32px), 375px' );
		$processor->set_attribute( 'loading', 'lazy' );
		$processor->set_attribute( 'decoding', 'async' );
		$changed = true;
	}

	return $changed ? $processor->get_updated_html() : $html;
}

/*
 * El buffer de home-cache se abre antes (-2000). Este buffer interior procesa
 * primero el documento y entrega la versión responsive a la caché de portada.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return;
		}

		ob_start( 'elmercado_home_responsive_producer_cards_010252' );
	},
	-1900
);

/**
 * performance-release marcaba todas las imágenes del hero como eager/high.
 * Restauramos la intención original: solo la primera compite por prioridad.
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attributes ): array {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return $attributes;
		}

		$class = isset( $attributes['class'] ) ? (string) $attributes['class'] : '';
		if ( ! str_contains( $class, 'emo-hero-product-image' ) ) {
			return $attributes;
		}

		static $hero_image_index = 0;
		++$hero_image_index;

		$attributes['decoding'] = 'async';
		if ( 1 === $hero_image_index ) {
			$attributes['loading']       = 'eager';
			$attributes['fetchpriority'] = 'high';
		} else {
			$attributes['loading']       = 'lazy';
			$attributes['fetchpriority'] = 'low';
		}

		return $attributes;
	},
	PHP_INT_MAX,
	3
);
