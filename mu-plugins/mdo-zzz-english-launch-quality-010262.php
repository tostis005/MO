<?php
/**
 * Plugin Name: MDO English Launch Quality
 * Description: Final server-side English storefront fixes for launch-critical copy.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdoelq_is_english_010262(): bool {
	if ( function_exists( 'mdoes_english_request_010258' ) ) { return mdoes_english_request_010258(); }
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return 1 === preg_match( '#^/en(?:/|$)#i', (string) wp_parse_url( $uri, PHP_URL_PATH ) );
}

add_filter( 'gettext', static function ( string $translated, string $text, string $domain ): string {
	if ( ! mdoelq_is_english_010262() ) { return $translated; }
	$map = array( 'Quitar' => 'Remove', 'Variedad' => 'Variety' );
	return $map[ $text ] ?? $translated;
}, PHP_INT_MAX, 3 );

add_filter( 'get_the_archive_title', static function ( string $title ): string {
	if ( ! mdoelq_is_english_010262() ) { return $title; }
	return (string) preg_replace( '/^\s*Variedad\s*:/iu', 'Variety:', $title, 1 );
}, PHP_INT_MAX );

/* Journal card template uses get_the_excerpt directly; guarantee its English copy. */
add_filter( 'get_the_excerpt', static function ( string $excerpt, $post ): string {
	if ( ! mdoelq_is_english_010262() || ! $post instanceof WP_Post ) { return $excerpt; }
	$copy = array(
		3327 => 'Iberian ham is a unique product whose history, according to documentary evidence, dates back to Roman times. Its distinctive sensory characteristics and nutritional properties give it an exceptional quality closely linked to breed, feeding, curing and origin.',
		1056 => 'November is harvest time in the Córdoba countryside. Although the timing differs in other parts of Spain, choosing the right moment to pick the olives is essential to the aroma, flavour and quality of the resulting extra virgin olive oil.',
	);
	return $copy[ $post->ID ] ?? $excerpt;
}, PHP_INT_MAX, 2 );

function mdoelq_render_english_010262( string $html ): string {
	if ( '' === $html || ! mdoelq_is_english_010262() ) { return $html; }

	$html = str_replace( array( '×Quitar', '>Quitar<' ), array( '×Remove', '>Remove<' ), $html );
	$html = (string) preg_replace( '/(>\s*)Variedad\s*:/iu', '$1Variety:', $html );
	$html = (string) preg_replace_callback( '/\b([0-9]+)\s+result\b/iu', static function ( array $m ): string {
		return '1' === $m[1] ? $m[0] : $m[1] . ' results';
	}, $html );

	/*
	 * Some Journal/category/related-post templates build their teaser before
	 * the ordinary excerpt filters run. Remove the two known Spanish source
	 * teasers from the final English HTML as a server-side safety net.
	 */
	$evoo = 'November is harvest time in the Córdoba countryside. Although the timing differs in other parts of Spain, choosing the right moment to pick the olives is essential to the aroma, flavour and quality of the resulting extra virgin olive oil.';
	$ham  = 'Iberian ham is a unique product whose history, according to documentary evidence, dates back to Roman times. Its distinctive sensory characteristics and nutritional properties give it an exceptional quality closely linked to breed, feeding, curing and origin.';
	$html = (string) preg_replace(
		'#Noviembre\s+es\s+la\s+[ée]poca\s+de\s+recolecci[oó]n\s+en\s+la\s+campi[nñ]a\s+cordobesa[^<]*#iu',
		esc_html( $evoo ),
		$html
	);
	$html = (string) preg_replace(
		'#El\s+Jam[oó]n\s+Ib[eé]rico\s+es[^<]*#iu',
		esc_html( $ham ),
		$html
	);

	/* Render-time fallbacks for persisted English data while page caches rotate. */
	$html = str_replace( 'First name  | Type', 'Name  | Type', $html );
	$html = strtr( $html, array(
		'https://support.google.com/chrome/answer/95647?hl=es' => 'https://support.google.com/chrome/answer/95647?hl=en',
		'http://windows.microsoft.com/es-es/windows-vista/cookies-frequently-asked-questions' => 'https://support.microsoft.com/en-us/edge/microsoft-edge-browsing-data-and-privacy',
		'http://support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-que-los-sitios-we' => 'https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox',
		'http://www.apple.com/es/privacy/use-of-cookies/' => 'https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac',
		'http://help.opera.com/Windows/11.50/es-ES/cookies.html' => 'https://help.opera.com/en/latest/web-preferences/',
		'http://www.youronlinechoices.com/es/' => 'https://www.youronlinechoices.com/',
		'https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage?hl=es#analyticsjs' => 'https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage?hl=en#analyticsjs',
		'http://www.google.es/policies/privacy/ads/#toc-doubleclick' => 'https://policies.google.com/technologies/ads?hl=en',
		'http://www.google.es/policies/privacy/ads/' => 'https://policies.google.com/technologies/ads?hl=en',
		'NEW HARVEST 23/24' => '2023/24 harvest',
	) );
	return $html;
}

add_action( 'template_redirect', static function (): void {
	if ( is_admin() || wp_doing_ajax() || ! mdoelq_is_english_010262() ) { return; }
	ob_start( 'mdoelq_render_english_010262' );
}, -2400 );
