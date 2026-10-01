<?php
/**
 * Responsive producer images for the public Home vendor collage.
 *
 * The existing Home MU plugin builds vendor cards from WCFM list-banner URLs
 * using raw <img src="..."> markup. This outer output buffer runs after that
 * renderer has finished and replaces only those producer-card images with
 * WordPress attachment markup, preserving the existing layout and links while
 * restoring srcset/sizes and an appropriately sized base src.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public front page only.
 */
function elmercado_home_vendor_responsive_is_front_010252(): bool {
	return ! is_admin() && is_front_page() && ! is_feed() && ! is_trackback() && ! wp_doing_ajax();
}

/**
 * Map known production banners to their WordPress attachments.
 * Unknown future vendors fall back to attachment_url_to_postid().
 */
function elmercado_home_vendor_attachment_id_010252( string $src ): int {
	$known = array(
		'Tolecarnes-fondo'      => 11052,
		'JAMON_ACTO_ECOLOGICO1' => 12667,
	);

	foreach ( $known as $needle => $attachment_id ) {
		if ( str_contains( $src, $needle ) ) {
			return $attachment_id;
		}
	}

	return (int) attachment_url_to_postid( $src );
}


/**
 * Very wide producer banners need a larger width candidate than their visible
 * card width suggests. With object-fit: cover the banner is scaled from its
 * height, so a 480x200 source can be visibly upscaled on HiDPI screens.
 */
function elmercado_home_vendor_is_panorama_010252( int $attachment_id ): bool {
	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( ! is_array( $meta ) ) {
		return false;
	}

	$width  = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
	$height = isset( $meta['height'] ) ? (int) $meta['height'] : 0;

	return $width > 0 && $height > 0 && ( $width / $height ) >= 2.0;
}

/**
 * Rewrite only images inside the active-producer hero visual.
 */
function elmercado_home_vendor_responsive_output_010252( string $html ): string {
	if ( '' === $html || ! str_contains( $html, 'emo-hero__visual--vendors' ) ) {
		return $html;
	}

	$start = strpos( $html, '<div class="emo-hero__visual emo-hero__visual--vendors' );
	if ( false === $start ) {
		return $html;
	}

	/* The vendor visual ends before the hero grid closes; all target images are nearby. */
	$end = strpos( $html, '</section>', $start );
	if ( false === $end ) {
		return $html;
	}

	$segment = substr( $html, $start, $end - $start );
	$segment = preg_replace_callback(
		'~<img\b[^>]*>~i',
		static function ( array $matches ): string {
			$tag = $matches[0];
			if ( ! preg_match( '~\bsrc=(["\'])(.*?)\1~i', $tag, $src_match ) ) {
				return $tag;
			}

			$src = html_entity_decode( (string) $src_match[2], ENT_QUOTES );
			$id  = elmercado_home_vendor_attachment_id_010252( $src );
			if ( ! $id ) {
				return $tag;
			}

			$alt = '';
			if ( preg_match( '~\balt=(["\'])(.*?)\1~i', $tag, $alt_match ) ) {
				$alt = html_entity_decode( (string) $alt_match[2], ENT_QUOTES );
			}

			$loading     = str_contains( $tag, 'loading="eager"' ) || str_contains( $tag, "loading='eager'" ) ? 'eager' : 'lazy';
			$is_panorama = elmercado_home_vendor_is_panorama_010252( $id );
			$image_size  = $is_panorama ? 'large' : 'medium_large';
			$attrs       = array(
				'alt'      => $alt,
				'loading'  => $loading,
				'decoding' => 'async',
				'sizes'    => $is_panorama
					? '(max-width: 599px) 380px, (max-width: 1180px) 480px, 500px'
					: '(max-width: 767px) calc(100vw - 32px), 375px',
				'class'    => $is_panorama
					? 'emo-home-vendor-responsive-image emo-home-vendor-panorama'
					: 'emo-home-vendor-responsive-image',
			);

			$image = wp_get_attachment_image( $id, $image_size, false, $attrs );
			if ( ! is_string( $image ) || '' === $image ) {
				return $tag;
			}

			/*
			 * WordPress prepends "auto," to sizes on lazy images. That is normally
			 * useful, but for a panoramic image cropped with object-fit: cover it
			 * makes the browser choose by the narrow card width and ignore the much
			 * larger source width needed to provide enough vertical pixels.
			 */
			if ( $is_panorama ) {
				$image = preg_replace(
					'~\bsizes=(["\'])auto,\s*~i',
					'sizes=$1',
					$image,
					1
				) ?: $image;
			}

			return $image;
		},
		$segment
	);

	if ( ! is_string( $segment ) ) {
		return $html;
	}

	return substr_replace( $html, $segment, $start, $end - $start );
}

/*
 * Start before the legacy Home vendor buffer. Output buffers unwind in reverse
 * order, so this callback receives the final vendor markup and is the last word
 * on producer image delivery.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( ! elmercado_home_vendor_responsive_is_front_010252() ) {
			return;
		}

		ob_start( 'elmercado_home_vendor_responsive_output_010252' );
	},
	-10000
);
