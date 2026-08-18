<?php
/**
 * Plugin Name: MDO English Storefront Stability
 * Description: Keeps the English catalog server-rendered and prevents the custom continuous loader from expanding the DOM in the browser.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Detect the public English route even when another MU plugin rewrites it internally. */
function mdoes_english_request_010258(): bool {
	if ( function_exists( 'mdoer_en' ) ) {
		return mdoer_en();
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	return 1 === preg_match( '#^/en(?:/|$)#i', $path );
}

/**
 * English catalog pages must stay as normal server-rendered paginated HTML.
 * The child theme's continuous loader can recursively fetch and append pages;
 * on translated routes that can grow the DOM until Chrome becomes unstable.
 */
function mdoes_stabilize_english_catalog_html_010258( string $html ): string {
	if ( '' === $html || ! mdoes_english_request_010258() ) {
		return $html;
	}

	/* Only act when the custom catalog enhancement is actually present. */
	if (
		false === strpos( $html, 'elmercado-continuous-catalog-loader-010181' ) &&
		false === strpos( $html, 'elmercado-continuous-catalog-history-010181' )
	) {
		return $html;
	}

	$html = (string) preg_replace(
		'#<script\b[^>]*\bid=("|\')elmercado-continuous-catalog-history-010181\1[^>]*>.*?</script>\s*#isu',
		'',
		$html
	);
	$html = (string) preg_replace(
		'#<script\b[^>]*\bid=("|\')elmercado-continuous-catalog-loader-010181\1[^>]*>.*?</script>\s*#isu',
		'',
		$html
	);

	/* Stop the loader-only CSS from hiding the ordinary HTML pagination. */
	$html = (string) preg_replace_callback(
		'#<body\b([^>]*)\bclass=("|\')([^"\']*)\2([^>]*)>#isu',
		static function ( array $matches ): string {
			$classes = preg_replace( '/(?:^|\s)emo-continuous-catalog(?:\s|$)/u', ' ', (string) $matches[3] );
			$classes = trim( (string) preg_replace( '/\s+/u', ' ', (string) $classes ) );
			$classes = trim( $classes . ' emo-static-english-catalog' );
			return '<body' . $matches[1] . 'class=' . $matches[2] . esc_attr( $classes ) . $matches[2] . $matches[4] . '>';
		},
		$html,
		1
	);

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || wp_doing_ajax() || ! mdoes_english_request_010258() ) {
			return;
		}
		ob_start( 'mdoes_stabilize_english_catalog_html_010258' );
	},
	-2500
);
