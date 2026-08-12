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

/**
 * Normaliza la copia "Enviará a" a "Enviar a" en contexto de carrito.
 */
function elmercado_cart_shipping_destination_copy( string $translation, string $text, string $domain = '' ): string {
	$is_cart = function_exists( 'is_cart' ) && is_cart();
	$is_ajax = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();

	if ( ! $is_cart && ! $is_ajax ) {
		return $translation;
	}

	if ( 'Shipping to %s.' === $text ) {
		return 'Enviar a %s.';
	}

	if ( 'Shipping to %s' === $text ) {
		return 'Enviar a %s';
	}

	$corrected = preg_replace( '/\bEnviará\s+a\b/iu', 'Enviar a', $translation );
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

		ob_start(
			static function ( string $html ): string {
				$corrected = preg_replace( '/\bEnviará(?:\s|&nbsp;)+a\b/iu', 'Enviar a', $html );
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

			const pattern = /Enviará\s+a/giu;

			const normalizeElement = (element) => {
				if (!(element instanceof Element)) return;
				const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
				let node = walker.nextNode();
				while (node) {
					const value = node.nodeValue || '';
					const corrected = value.replace(pattern, 'Enviar a');
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
