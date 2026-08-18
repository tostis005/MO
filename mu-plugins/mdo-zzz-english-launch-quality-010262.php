<?php
/**
 * Plugin Name: MDO English Launch Quality
 * Description: Final server-side English storefront fixes for taxonomy labels, filter chips and count grammar.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdoelq_is_english_010262(): bool {
	if ( function_exists( 'mdoes_english_request_010258' ) ) {
		return mdoes_english_request_010258();
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	return 1 === preg_match( '#^/en(?:/|$)#i', $path );
}

add_filter(
	'gettext',
	static function ( string $translated, string $text, string $domain ): string {
		if ( ! mdoelq_is_english_010262() ) {
			return $translated;
		}
		$map = array(
			'Quitar'   => 'Remove',
			'Variedad' => 'Variety',
		);
		return $map[ $text ] ?? $translated;
	},
	PHP_INT_MAX,
	3
);

add_filter(
	'get_the_archive_title',
	static function ( string $title ): string {
		if ( ! mdoelq_is_english_010262() ) {
			return $title;
		}
		return (string) preg_replace( '/^\s*Variedad\s*:/iu', 'Variety:', $title, 1 );
	},
	PHP_INT_MAX
);

/**
 * A few legacy catalogue controls are generated after gettext/Falang. Fix only
 * exact UI residues in the already-English server response; product/body copy
 * is otherwise left untouched.
 */
function mdoelq_render_english_010262( string $html ): string {
	if ( '' === $html || ! mdoelq_is_english_010262() ) {
		return $html;
	}

	$html = str_replace( array( '×Quitar', '>Quitar<' ), array( '×Remove', '>Remove<' ), $html );
	$html = (string) preg_replace( '/(>\s*)Variedad\s*:/iu', '$1Variety:', $html );
	$html = (string) preg_replace_callback(
		'/\b([0-9]+)\s+result\b/iu',
		static function ( array $m ): string {
			return '1' === $m[1] ? $m[0] : $m[1] . ' results';
		},
		$html
	);

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || wp_doing_ajax() || ! mdoelq_is_english_010262() ) {
			return;
		}
		ob_start( 'mdoelq_render_english_010262' );
	},
	-2400
);
