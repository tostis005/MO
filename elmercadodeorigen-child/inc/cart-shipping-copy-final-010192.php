<?php
/**
 * Copia definitiva del destino de envío del carrito 0.10.192.
 *
 * WooCommerce y algunas capas de traducción pueden volver a aplicar el dominio
 * `woocommerce` después del filtro gettext genérico. Esta capa corrige el texto
 * en el filtro de dominio, en la salida HTML y también tras refrescos AJAX.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'elmercado_request_uses_english' ) ) {
	function elmercado_request_uses_english(): bool {
		global $TRP_LANGUAGE;

		if ( isset( $TRP_LANGUAGE ) && is_string( $TRP_LANGUAGE ) && '' !== $TRP_LANGUAGE ) {
			return 0 === strpos( strtolower( $TRP_LANGUAGE ), 'en' );
		}

		if ( function_exists( 'trp_get_current_language' ) ) {
			$language = trp_get_current_language();
			if ( is_string( $language ) && '' !== $language ) {
				return 0 === strpos( strtolower( $language ), 'en' );
			}
		}

		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		return 1 === preg_match( '#^/en(?:/|$)#i', $path );
	}
}

/**
 * Normaliza el destino de envío respetando el idioma de la petición.
 */
function elmercado_cart_shipping_destination_copy( string $translation, string $text, string $domain = '' ): string {
	$is_cart = function_exists( 'is_cart' ) && is_cart();
	$is_ajax = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();

	if ( ! $is_cart && ! $is_ajax ) {
		return $translation;
	}

	$is_english = elmercado_request_uses_english();

	if ( 'Shipping to %s.' === $text ) {
		return $is_english ? 'Shipping to %s.' : 'Enviar a %s.';
	}

	if ( 'Shipping to %s' === $text ) {
		return $is_english ? 'Shipping to %s' : 'Enviar a %s';
	}

	if ( $is_english ) {
		$corrected = preg_replace( '/\b(?:Enviará|Enviar)(?:\s|&nbsp;)+a\b/iu', 'Shipping to', $translation );
	} else {
		$corrected = preg_replace( '/\bEnviará(?:\s|&nbsp;)+a\b/iu', 'Enviar a', $translation );
	}

	return is_string( $corrected ) ? $corrected : $translation;
}

add_filter( 'gettext', 'elmercado_cart_shipping_destination_copy', PHP_INT_MAX, 3 );
add_filter( 'gettext_woocommerce', 'elmercado_cart_shipping_destination_copy', PHP_INT_MAX, 3 );

/**
 * Última red de seguridad para HTML renderizado en el carrito.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}

		$is_english = elmercado_request_uses_english();
		ob_start(
			static function ( string $html ) use ( $is_english ): string {
				if ( $is_english ) {
					$corrected = preg_replace( '/\b(?:Enviará|Enviar)(?:\s|&nbsp;)+a\b/iu', 'Shipping to', $html );
				} else {
					$corrected = preg_replace( '/\bEnviará(?:\s|&nbsp;)+a\b/iu', 'Enviar a', $html );
				}
				return is_string( $corrected ) ? $corrected : $html;
			}
		);
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}
		?>
		<script id="elmercado-cart-shipping-copy-final-010192">
		(() => {
			'use strict';

			const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en') || /^\/en(?:\/|$)/i.test(window.location.pathname || '');
			const pattern = isEnglish ? /(?:Enviará|Enviar)\s+a/giu : /Enviará\s+a/giu;
			const replacement = isEnglish ? 'Shipping to' : 'Enviar a';

			const normalizeElement = (element) => {
				if (!(element instanceof Element)) return;
				const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
				let node = walker.nextNode();
				while (node) {
					const value = node.nodeValue || '';
					const corrected = value.replace(pattern, replacement);
					if (corrected !== value) node.nodeValue = corrected;
					node = walker.nextNode();
				}
			};

			const normalize = (root = document) => {
				if (root instanceof Element && root.matches('.woocommerce-shipping-destination')) {
					normalizeElement(root);
				}
				root.querySelectorAll?.('.woocommerce-shipping-destination').forEach(normalizeElement);
			};

			const start = () => {
				normalize();
				requestAnimationFrame(() => normalize());
				setTimeout(() => normalize(), 120);
				setTimeout(() => normalize(), 650);

				const observer = new MutationObserver((mutations) => {
					for (const mutation of mutations) {
						if (mutation.type === 'characterData') {
							const parent = mutation.target.parentElement;
							const destination = parent?.closest?.('.woocommerce-shipping-destination');
							if (destination) normalizeElement(destination);
							continue;
						}

						for (const node of mutation.addedNodes) {
							if (node instanceof Element) normalize(node);
							else if (node.parentElement) {
								const destination = node.parentElement.closest?.('.woocommerce-shipping-destination');
								if (destination) normalizeElement(destination);
							}
						}
					}
				});

				observer.observe(document.body, { childList: true, characterData: true, subtree: true });
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', start, { once: true });
			} else {
				start();
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
