<?php
/**
 * Ajustes solicitados de carrito, checkout y formularios de contacto 0.10.186.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'elmercado_request_uses_english' ) ) {
	/**
	 * Detecta de forma robusta si la petición visible corresponde a la versión inglesa.
	 */
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
 * Normaliza el destino de envío sin forzar español dentro de /en/.
 */
add_filter(
	'gettext',
	static function ( string $translation, string $text, string $domain ): string {
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			$is_english = elmercado_request_uses_english();

			if ( 'Shipping to %s.' === $text ) {
				return $is_english ? 'Shipping to %s.' : 'Enviar a %s.';
			}

			if ( 'Shipping to %s' === $text ) {
				return $is_english ? 'Shipping to %s' : 'Enviar a %s';
			}

			if ( $is_english ) {
				$corrected = preg_replace( '/^\s*(?:Enviará|Enviar)\s+a(?=\s|$)/u', 'Shipping to', $translation, 1 );
			} else {
				$corrected = preg_replace( '/^\s*Enviará\s+a(?=\s|$)/u', 'Enviar a', $translation, 1 );
			}

			if ( is_string( $corrected ) && $corrected !== $translation ) {
				return $corrected;
			}
		}

		return $translation;
	},
	PHP_INT_MAX,
	3
);

/**
 * El checkout debe mostrar únicamente el nombre de cada método de pago.
 */
add_filter(
	'woocommerce_gateway_icon',
	static function ( string $icon ): string {
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return '';
		}

		return $icon;
	},
	PHP_INT_MAX
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-contact-checkout-cleanup-010186">
			/* Fallback para gateways que imprimen su logo sin usar woocommerce_gateway_icon. */
			body.elmercado-child-theme.woocommerce-checkout #payment .wc_payment_method > label img,
			body.elmercado-child-theme.woocommerce-checkout #payment .payment_methods > li > label img,
			body.elmercado-child-theme.woocommerce-checkout #payment .payment_method_paypal img {
				display: none !important;
			}

			/* Los fieldset realmente vacíos nunca deben reservar caja ni borde. */
			body.elmercado-child-theme form fieldset:empty,
			body.elmercado-child-theme form fieldset[data-emo-empty-fieldset="true"] {
				display: none !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				min-width: 0 !important;
				min-height: 0 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		/* Este runtime solo tiene trabajo en carrito y páginas de contacto. */
		$uri        = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path       = strtolower( (string) wp_parse_url( $uri, PHP_URL_PATH ) );
		$is_cart    = function_exists( 'is_cart' ) && is_cart();
		$is_contact = str_contains( $path, 'contact' );
		if ( ! $is_cart && ! $is_contact ) {
			return;
		}
		?>
		<script id="elmercado-cart-contact-runtime-guard-010186">
		(() => {
			'use strict';

			const path = (window.location.pathname || '').toLowerCase();
			const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en') || /^\/en(?:\/|$)/i.test(path);
			const isRegularContact = document.body.classList.contains('page-id-1345')
				|| /^\/(?:en\/)?contacto\/?$/i.test(path)
				|| /^\/(?:en\/)?contact\/?$/i.test(path);

			const trimRegularContactAfterForm = () => {
				if (!isRegularContact) return;
				const content = document.querySelector('.entry-content');
				const form = content?.querySelector('.wpcf7');
				if (!content || !form) return;

				let node = form;
				while (node && node !== content) {
					let sibling = node.nextSibling;
					while (sibling) {
						const next = sibling.nextSibling;
						sibling.remove();
						sibling = next;
					}
					node = node.parentNode;
				}
			};

			const normalizeCartDestination = (root = document) => {
				if (!document.body.classList.contains('woocommerce-cart')) return;
				root.querySelectorAll?.('.woocommerce-shipping-destination').forEach((element) => {
					Array.from(element.childNodes).forEach((node) => {
						if (node.nodeType !== Node.TEXT_NODE) return;
						const value = node.nodeValue || '';
						const corrected = isEnglish
							? value.replace(/^\s*(?:Enviará|Enviar)\s+a(?=\s|$)/u, (match) => match.replace(/(?:Enviará|Enviar)\s+a/u, 'Shipping to'))
							: value.replace(/^\s*Enviará\s+a(?=\s|$)/u, (match) => match.replace(/Enviará\s+a/u, 'Enviar a'));
						if (corrected !== value) node.nodeValue = corrected;
					});
				});
			};

			const isRendered = (element) => {
				if (!(element instanceof Element)) return false;
				const style = window.getComputedStyle(element);
				if (style.display === 'none' || style.visibility === 'hidden' || style.visibility === 'collapse') return false;
				return element.getClientRects().length > 0;
			};

			const hasVisibleControl = (fieldset) => Array.from(
				fieldset.querySelectorAll('input:not([type="hidden"]), select, textarea, button, img, svg, canvas, iframe')
			).some(isRendered);

			const hasVisibleText = (fieldset) => {
				const walker = document.createTreeWalker(fieldset, NodeFilter.SHOW_TEXT);
				let node = walker.nextNode();
				while (node) {
					if ((node.textContent || '').trim() !== '') {
						const parent = node.parentElement;
						if (parent && isRendered(parent)) return true;
					}
					node = walker.nextNode();
				}
				return false;
			};

			const normalizeFieldset = (fieldset) => {
				if (!(fieldset instanceof HTMLFieldSetElement)) return;
				const empty = !hasVisibleControl(fieldset) && !hasVisibleText(fieldset);
				if (empty) {
					fieldset.setAttribute('data-emo-empty-fieldset', 'true');
					fieldset.setAttribute('aria-hidden', 'true');
				} else {
					fieldset.removeAttribute('data-emo-empty-fieldset');
					fieldset.removeAttribute('aria-hidden');
				}
			};

			const scanContactFieldsets = (root = document) => {
				if (!path.includes('contact')) return;
				if (root instanceof HTMLFieldSetElement) normalizeFieldset(root);
				root.querySelectorAll?.('form fieldset').forEach(normalizeFieldset);
			};

			const scan = (root = document) => {
				trimRegularContactAfterForm();
				normalizeCartDestination(root);
				scanContactFieldsets(root);
			};

			const start = () => {
				scan();
				const observer = new MutationObserver((mutations) => {
					mutations.forEach((mutation) => {
						mutation.addedNodes.forEach((node) => {
							if (node instanceof Element) scan(node);
						});
					});
				});
				observer.observe(document.body, { childList: true, subtree: true });
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
