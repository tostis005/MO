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
 * Convierte las imágenes grandes de productores de la portada en fuentes
 * responsive. Se inspecciona cada etiqueta img y solo se cambian los adjuntos
 * conocidos de productores, evitando depender de clases que otras capas quitan.
 */
function elmercado_home_responsive_producer_cards_010252( string $html ): string {
	if (
		'' === $html
		|| (
			! str_contains( $html, 'Tolecarnes-fondo' )
			&& ! str_contains( $html, 'JAMON_ACTO_ECOLOGICO1' )
			&& ! str_contains( $html, 'Aceite-sin-filtrar' )
		)
	) {
		return $html;
	}

	$known_attachments = array(
		'Tolecarnes-fondo'      => 11052,
		'JAMON_ACTO_ECOLOGICO1' => 12667,
	);

	$rewritten = preg_replace_callback(
		'~<img\b[^>]*>~i',
		static function ( array $matches ) use ( $known_attachments ): string {
			$tag = $matches[0];
			$id  = 0;

			foreach ( $known_attachments as $needle => $attachment_id ) {
				if ( str_contains( $tag, $needle ) ) {
					$id = $attachment_id;
					break;
				}
			}

			if ( ! $id && str_contains( $tag, 'Aceite-sin-filtrar' ) ) {
				if ( preg_match( '~\bsrc=(["\'])(.*?)\1~i', $tag, $src_match ) ) {
					$id = attachment_url_to_postid( html_entity_decode( $src_match[2], ENT_QUOTES ) );
				}
			}

			if ( ! $id ) {
				return $tag;
			}

			$image  = wp_get_attachment_image_src( $id, 'medium_large' );
			$srcset = wp_get_attachment_image_srcset( $id, 'medium_large' );
			if ( ! is_array( $image ) || empty( $image[0] ) || ! is_string( $srcset ) || '' === trim( $srcset ) ) {
				return $tag;
			}

			$tag = preg_replace( '~\s+srcset=(["\']).*?\1~i', '', $tag ) ?? $tag;
			$tag = preg_replace( '~\s+sizes=(["\']).*?\1~i', '', $tag ) ?? $tag;
			$tag = preg_replace( '~\bsrc=(["\']).*?\1~i', 'src="' . esc_url( $image[0] ) . '"', $tag, 1 ) ?? $tag;

			$self_closing = str_ends_with( rtrim( $tag ), '/>' );
			$tag          = preg_replace( '~\s*/?>$~', '', $tag ) ?? $tag;
			$tag         .= ' srcset="' . esc_attr( $srcset ) . '" sizes="(max-width: 767px) calc(100vw - 32px), 375px" data-emo-responsive="1"';
			$tag         .= $self_closing ? ' />' : '>';

			return $tag;
		},
		$html
	);

	return is_string( $rewritten ) ? $rewritten : $html;
}

/*
 * Mantiene una segunda protección sobre la salida dinámica. La caché de Home
 * también llama directamente a esta función antes de guardar su HTML.
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
