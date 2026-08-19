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

			$loading = str_contains( $tag, 'loading="eager"' ) || str_contains( $tag, "loading='eager'" ) ? 'eager' : 'lazy';
			$attrs   = array(
				'alt'      => $alt,
				'loading'  => $loading,
				'decoding' => 'async',
				'sizes'    => '(max-width: 767px) calc(100vw - 32px), 375px',
				'class'    => 'emo-home-vendor-responsive-image',
			);

			$image = wp_get_attachment_image( $id, 'medium_large', false, $attrs );
			return is_string( $image ) && '' !== $image ? $image : $tag;
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
