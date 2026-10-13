<?php
/**
 * Plugin Name: MDO Shop First Frame Stability
 * Description: Add the final catalogue toolbar class before the first HTML paint, instead of waiting for the footer normaliser.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The established catalogue script adds this class in wp_footer. On slower
 * mobile networks that happens after the first product grid is painted and
 * produces a large layout shift. Give the existing toolbar its *same* final
 * CSS class in HTML before the browser receives it. No content, products,
 * ordering, layout rules or WooCommerce state is changed.
 */
add_action(
	'template_redirect',
	static function (): void {
		if (
			is_admin() ||
			( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			! function_exists( 'is_shop' ) ||
			! is_shop() ||
			'GET' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) )
		) {
			return;
		}

		ob_start(
			static function ( string $html ): string {
				if ( false === strpos( $html, 'woostify-sorting' ) ) {
					return $html;
				}

				$fixed = preg_replace_callback(
					'~<div\\b[^>]*\\bclass=(["\\\'])([^"\\\']*\\bwoostify-sorting\\b[^"\\\']*)\\1[^>]*>~i',
					static function ( array $matches ): string {
						if ( false !== strpos( $matches[2], 'emo-catalog-toolbar-shared-010229' ) ) {
							return $matches[0];
						}
						$classes = $matches[2] . ' emo-catalog-toolbar-shared-010229';
						return str_replace(
							'class=' . $matches[1] . $matches[2] . $matches[1],
							'class=' . $matches[1] . $classes . $matches[1],
							$matches[0]
						);
					},
					$html,
					1
				);

				return is_string( $fixed ) ? $fixed : $html;
			}
		);
	},
	-2100
);
