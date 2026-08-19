<?php
/**
 * Final responsive-image pass for the Home producer collage.
 *
 * The legacy Home MU plugin builds producer cards from raw WCFM banner URLs,
 * which bypasses WordPress responsive image markup. This outer output buffer
 * runs before that legacy buffer is opened, so its callback receives the final
 * producer collage and upgrades only its <img> tags to attachment-backed
 * medium_large markup with srcset/sizes.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

			$responsive = wp_get_attachment_image(
				$attachment_id,
				'medium_large',
				false,
				array(
					'alt'      => $alt,
					'loading'  => $loading,
					'decoding' => 'async',
					'sizes'    => '(max-width: 767px) calc(100vw - 32px), 375px',
				)
			);

			return is_string( $responsive ) && '' !== $responsive ? $responsive : $tag;
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
