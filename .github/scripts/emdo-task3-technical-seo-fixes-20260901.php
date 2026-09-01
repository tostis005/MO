<?php
/**
 * Plugin Name: EMDO Task 3 Technical SEO Fixes 2026-09-01
 * Description: Final technical SEO guard for blog language alternates and thin archives.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This MU plugin is deployed with a filename beginning in "0-" so its output
 * buffer is opened before the legacy English SEO buffers. As an outer buffer,
 * this callback receives the final HTML after those inner buffers have run.
 */
function emdo_task3_fix_link_href( string $tag, string $href ): string {
	$escaped = esc_url( $href );
	if ( preg_match( '/\bhref\s*=\s*(["\']).*?\1/i', $tag ) ) {
		return (string) preg_replace( '/\bhref\s*=\s*(["\']).*?\1/i', 'href="' . $escaped . '"', $tag, 1 );
	}
	return rtrim( $tag, '>' ) . ' href="' . $escaped . '">';
}

function emdo_task3_spanish_category_url_from_english_path(): string {
	global $wpdb;

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	if ( ! preg_match( '#^/en/category/([^/]+)(?:/page/(\d+))?/?$#i', $path, $m ) ) {
		return '';
	}

	$english_slug = sanitize_title( rawurldecode( $m[1] ) );
	if ( '' === $english_slug ) {
		return '';
	}

	$term_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT tm.term_id
			 FROM {$wpdb->termmeta} tm
			 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id
			 WHERE tt.taxonomy = 'category'
			   AND tm.meta_key = '_en_US_slug'
			   AND tm.meta_value = %s
			 LIMIT 1",
			$english_slug
		)
	);
	if ( $term_id < 1 ) {
		return '';
	}

	$term = get_term( $term_id, 'category' );
	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	$base = untrailingslashit( (string) get_option( 'home' ) );
	$url  = $base . '/category/' . rawurlencode( $term->slug ) . '/';
	if ( ! empty( $m[2] ) && (int) $m[2] > 1 ) {
		$url .= 'page/' . (int) $m[2] . '/';
	}
	return $url;
}

function emdo_task3_final_html( string $html ): string {
	if ( '' === $html || false === stripos( $html, '</head>' ) ) {
		return $html;
	}

	// Repair reciprocal hreflang for custom English blog-category routes.
	$spanish_category_url = emdo_task3_spanish_category_url_from_english_path();
	if ( '' !== $spanish_category_url ) {
		$html = (string) preg_replace_callback(
			'#<link\b(?=[^>]*\bhreflang\s*=\s*(["\'])(?:es(?:-ES)?|x-default)\1)[^>]*>#iu',
			static function ( array $match ) use ( $spanish_category_url ): string {
				return emdo_task3_fix_link_href( $match[0], $spanish_category_url );
			},
			$html
		);
	}

	// Make the HTML signal agree with the X-Robots-Tag guard below.
	if ( is_author() || is_date() || ( is_category() && is_paged() ) ) {
		$html = (string) preg_replace( '#<meta\b[^>]*\bname\s*=\s*(["\'])robots\1[^>]*>\s*#iu', '', $html );
		$robots = '<meta name="robots" content="noindex, follow, max-image-preview:large" data-emdo-task3="1">' . "\n";
		$html = (string) preg_replace( '#</head>#i', $robots . '</head>', $html, 1 );
	}

	return $html;
}

if ( PHP_SAPI !== 'cli' && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	ob_start( 'emdo_task3_final_html' );
}

add_action( 'template_redirect', static function (): void {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}
	if ( is_author() || is_date() || ( is_category() && is_paged() ) ) {
		header( 'X-Robots-Tag: noindex, follow', true );
	}
}, -PHP_INT_MAX );
