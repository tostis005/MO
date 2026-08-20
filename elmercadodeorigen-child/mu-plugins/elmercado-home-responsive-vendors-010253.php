<?php
/**
 * Responsive-image pass for the Home producer collage.
 *
 * Adds small, card-specific crops for the two oversized producer images called
 * out by Lighthouse, while keeping the original media untouched everywhere else.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	static function (): void {
		add_image_size( 'elmercado_vendor_square_400', 400, 400, true );
		add_image_size( 'elmercado_vendor_landscape_450', 450, 300, true );
	},
	PHP_INT_MAX
);

function elmercado_home_responsive_vendor_images_010253( string $html ): string {
	if ( '' === $html || false === strpos( $html, 'emo-hero__visual--vendors' ) ) {
		return $html;
	}

	$start = strpos( $html, '<div class="emo-hero__visual emo-hero__visual--vendors' );
	if ( false === $start ) {
		return $html;
	}

	$end = strpos( $html, '</div>', $start );
	if ( false === $end ) {
		return $html;
	}
	$end += strlen( '</div>' );

	$visual = substr( $html, $start, $end - $start );
	if ( '' === $visual ) {
		return $html;
	}

	$visual = preg_replace_callback(
		'~<img\b[^>]*>~i',
		static function ( array $match ): string {
			$tag = $match[0];
			if ( ! preg_match( '~\bsrc=([' . "\"'" . '])([^' . "\"'" . ']+)\1~i', $tag, $src_match ) ) {
				return $tag;
			}

			$src           = html_entity_decode( $src_match[2], ENT_QUOTES, 'UTF-8' );
			$attachment_id = attachment_url_to_postid( $src );
			if ( ! $attachment_id ) {
				return $tag;
			}

			$alt = '';
			if ( preg_match( '~\balt=([' . "\"'" . '])([^' . "\"'" . ']*)\1~i', $tag, $alt_match ) ) {
				$alt = html_entity_decode( $alt_match[2], ENT_QUOTES, 'UTF-8' );
			}

			$loading = 'lazy';
			if ( preg_match( '~\bloading=([' . "\"'" . '])(eager|lazy)\1~i', $tag, $loading_match ) ) {
				$loading = strtolower( $loading_match[2] );
			}

			$size = 'medium_large';
			if ( 11052 === $attachment_id ) {
				$size = 'elmercado_vendor_square_400';
			} elseif ( 12667 === $attachment_id ) {
				$size = 'elmercado_vendor_landscape_450';
			}

			$responsive = wp_get_attachment_image(
				$attachment_id,
				$size,
				false,
				array(
					'class'    => 'emo-home-vendor-responsive-image',
					'alt'      => $alt,
					'loading'  => $loading,
					'decoding' => 'async',
					'sizes'    => '(max-width: 767px) calc(100vw - 32px), 375px',
				)
			);

			if ( ! is_string( $responsive ) || '' === $responsive ) {
				return $tag;
			}

			/*
			 * Este callback es el buffer más exterior de la Home. Por eso las
			 * imágenes responsive que genera aquí deben pasar por WebP después de
			 * crearse; si no, vuelven a insertar JPEG tras el pase global WebP.
			 */
			if ( function_exists( 'mdo_home_webp_transform_html' ) ) {
				$responsive = mdo_home_webp_transform_html( $responsive );
			}

			return $responsive;
		},
		$visual
	);

	if ( ! is_string( $visual ) || '' === $visual ) {
		return $html;
	}

	return substr_replace( $html, $visual, $start, $end - $start );
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! is_front_page() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		ob_start( 'elmercado_home_responsive_vendor_images_010253' );
	},
	-99999
);
