<?php
/**
 * Plugin Name: MDO English Storefront Stability
 * Description: Keeps English pages server-rendered from persisted translations and removes browser-side translation/catalog expansion.
 * Version: 1.3.2
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

/** Read only persisted English UI copy from wp_options. */
function mdoes_persisted_english_ui_map_010258(): array {
	$stored = get_option( 'elmercado_en_ui_copy_010245', array() );
	return is_array( $stored ) ? $stored : array();
}

/** Return a persisted translation, otherwise leave the source untouched. */
function mdoes_persisted_english_ui_copy_010258( string $source ): string {
	$map = mdoes_persisted_english_ui_map_010258();
	return isset( $map[ $source ] ) && '' !== trim( (string) $map[ $source ] )
		? (string) $map[ $source ]
		: $source;
}

/** TranslatePress must not scan/rewrite the English DOM in the browser. */
function mdoes_dequeue_live_translation_scripts_010258(): void {
	if ( ! mdoes_english_request_010258() ) {
		return;
	}

	$handles = array(
		'trp-translate-dom-changes',
		'trp-frontend-language-switcher',
		'trp-frontend-compatibility',
		'trp-language-switcher',
	);
	foreach ( $handles as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'mdoes_dequeue_live_translation_scripts_010258', PHP_INT_MAX );
add_action( 'wp_footer', 'mdoes_dequeue_live_translation_scripts_010258', 1 );

/** Remove one inline script by its exact WordPress id. */
function mdoes_remove_inline_script_by_id_010258( string $html, string $id ): string {
	$id = preg_quote( $id, '#' );
	return (string) preg_replace(
		'#<script\b[^>]*\bid=("|\')' . $id . '\1[^>]*>.*?</script>\s*#isu',
		'',
		$html
	);
}

/**
 * Replace hard-coded storefront UI literals before HTML reaches the browser.
 * Every target translation is read from the persisted wp_options map.
 */
function mdoes_render_persisted_english_ui_010258( string $html ): string {
	/* Whole UI phrases are safe to replace globally, including their inline JS literals. */
	$source_strings = array(
		'Recomendados',
		'Más populares',
		'Mejor valorados',
		'Más recientes',
		'Menor precio',
		'Mayor precio',
		'Filtros',
		'Cerrar menú',
		'Buscar productos',
		'Buscar',
		'Ordenar por',
	);

	$replace = array();
	foreach ( $source_strings as $source ) {
		$translated = mdoes_persisted_english_ui_copy_010258( $source );
		if ( $translated !== $source ) {
			$replace[ $source ] = $translated;
		}
	}
	if ( $replace ) {
		$html = strtr( $html, $replace );
	}

	/* Singular/plural nouns are replaced only as quoted JS literals or visible count text. */
	foreach ( array( 'resultado', 'resultados', 'producto', 'productos' ) as $source ) {
		$translated = mdoes_persisted_english_ui_copy_010258( $source );
		if ( $translated === $source ) {
			continue;
		}
		$html = str_replace( "'" . $source . "'", "'" . $translated . "'", $html );
		$html = str_replace( '"' . $source . '"', '"' . $translated . '"', $html );
		$html = (string) preg_replace(
			'#(>\s*\(?\s*[0-9][0-9.,]*\s+)' . preg_quote( $source, '#' ) . '(\s*\)?\s*<)#iu',
			'$1' . $translated . '$2',
			$html
		);
	}

	return $html;
}

/**
 * Final safety net for English HTML.
 *
 * English is rendered from persisted WordPress/Falang data. Browser-side text
 * translation and automatic catalogue expansion are intentionally disabled.
 */
function mdoes_stabilize_english_catalog_html_010258( string $html ): string {
	if ( '' === $html || ! mdoes_english_request_010258() ) {
		return $html;
	}

	$html = (string) preg_replace(
		'#<script\b[^>]*\bsrc=("|\')[^"\']*/translatepress-multilingual/assets/js/(?:trp-translate-dom-changes|trp-frontend-language-switcher|trp-frontend-compatibility)\.js[^"\']*\1[^>]*>.*?</script>\s*#isu',
		'',
		$html
	);

	/* TranslatePress dynamic-translation config is unnecessary when its client translator is disabled. */
	$html = mdoes_remove_inline_script_by_id_010258( $html, 'trp-dynamic-translator-js-extra' );

	/* Remove a residual inline whole-DOM translator regardless of where it was registered. */
	$html = (string) preg_replace_callback(
		'#<script\b([^>]*)>(.*?)</script>#isu',
		static function ( array $matches ): string {
			$code = (string) $matches[2];
			if ( false !== strpos( $code, 'createTreeWalker' ) && false !== strpos( $code, 'MutationObserver' ) ) {
				return '';
			}
			return (string) $matches[0];
		},
		$html
	);

	/* Legacy continuous loader. */
	$html = mdoes_remove_inline_script_by_id_010258( $html, 'elmercado-continuous-catalog-history-010181' );
	$html = mdoes_remove_inline_script_by_id_010258( $html, 'elmercado-continuous-catalog-loader-010181' );

	/* Current catalogue scroller: it fetches and appends pages automatically. */
	$html = mdoes_remove_inline_script_by_id_010258( $html, 'elmercado-catalog-scroll-final-010234' );

	/* Render remaining hard-coded interface literals from the database before delivery. */
	$html = mdoes_render_persisted_english_ui_010258( $html );

	/* Stop loader-only CSS from hiding the ordinary HTML pagination. */
	$html = (string) preg_replace_callback(
		'#<body\b([^>]*)\bclass=("|\')([^"\']*)\2([^>]*)>#isu',
		static function ( array $matches ): string {
			$classes = preg_replace( '/(?:^|\s)emo-continuous-catalog(?:\s|$)/u', ' ', (string) $matches[3] );
			$classes = trim( (string) preg_replace( '/\s+/u', ' ', (string) $classes ) );
			if ( ! str_contains( ' ' . $classes . ' ', ' emo-static-english-catalog ' ) ) {
				$classes = trim( $classes . ' emo-static-english-catalog' );
			}
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
