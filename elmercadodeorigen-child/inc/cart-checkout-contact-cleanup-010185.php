<?php
/**
 * Ajustes solicitados de carrito, checkout y formularios de contacto 0.10.185.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Corrige el texto de destino de envío del carrito sin depender del catálogo
 * de traducciones instalado en cada entorno.
 */
add_filter(
	'gettext',
	static function ( string $translation, string $text, string $domain ): string {
		if ( 'woocommerce' !== $domain ) {
			return $translation;
		}

		if ( 'Shipping to %s.' === $text ) {
			return 'Enviar a %s.';
		}

		if ( 'Shipping to %s' === $text ) {
			return 'Enviar a %s';
		}

		return $translation;
	},
	20,
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
		<style id="elmercado-contact-checkout-cleanup-010185">
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
		?>
		<script id="elmercado-contact-empty-fieldset-guard-010185">
		(() => {
			'use strict';

			const path = (window.location.pathname || '').toLowerCase();
			if (!path.includes('contact')) return;

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

			const scan = (root = document) => {
				if (root instanceof HTMLFieldSetElement) normalizeFieldset(root);
				root.querySelectorAll?.('form fieldset').forEach(normalizeFieldset);
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
