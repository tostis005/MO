<?php
/**
 * Corrección puntual de honeypot visible en contacto y capitalización legal 0.10.247.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * El copy inglés del enlace legal debe conservar siempre su capitalización.
 * No dependemos del detector de idioma: el reemplazo sólo actúa sobre la
 * etiqueta inglesa exacta, por lo que la versión española queda intacta.
 */
add_filter(
	'nav_menu_item_title',
	static function ( string $title ): string {
		$plain = html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' );
		$plain = preg_replace( '/\s+/u', ' ', trim( $plain ) );

		if ( is_string( $plain ) && in_array( strtolower( $plain ), array( 'terms and conditions', 'terms & conditions' ), true ) ) {
			return 'Terms and Conditions';
		}

		return $title;
	},
	PHP_INT_MAX,
	1
);

add_filter(
	'gettext',
	static function ( string $translation ): string {
		$plain = html_entity_decode( wp_strip_all_tags( $translation ), ENT_QUOTES, 'UTF-8' );
		$plain = preg_replace( '/\s+/u', ' ', trim( $plain ) );

		if ( is_string( $plain ) && in_array( strtolower( $plain ), array( 'terms and conditions', 'terms & conditions' ), true ) ) {
			return 'Terms and Conditions';
		}

		return $translation;
	},
	PHP_INT_MAX,
	1
);

/**
 * Capa visual temprana para honeypots que sí exponen una clase/atributo
 * reconocible. El guard de JS de pie cubre además los honeypots con nombres
 * deliberadamente neutros (por ejemplo "website").
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-contact-footer-hotfix-010247">
			.wpcf7 [class*="honeypot" i],
			.wpcf7 [data-name*="honeypot" i],
			.wpcf7 input[name*="honeypot" i],
			.wpcf7 input[id*="honeypot" i],
			.wpcf7 textarea[name*="honeypot" i],
			.wpcf7 textarea[id*="honeypot" i],
			.wpcf7 [data-emo-contact-honeypot="true"] {
				display: none !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				min-width: 0 !important;
				min-height: 0 !important;
				height: 0 !important;
				overflow: hidden !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Salvaguarda de runtime para:
 *  - ocultar el campo trampa de CF7 incluso si el plugin le da un nombre neutro;
 *  - corregir el enlace legal aunque Falang/TranslatePress lo reescriban tras
 *    el render PHP.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-contact-footer-runtime-hotfix-010247">
		(() => {
			'use strict';

			const normalizeText = (value) => (value || '')
				.replace(/\s+/g, ' ')
				.trim()
				.toLocaleLowerCase();

			const honeypotPrompts = [
				'no completar este campo',
				'no rellenar este campo',
				'leave this field empty',
				'please leave this field empty',
				'do not fill in this field',
				'do not fill this field'
			];

			const markHidden = (element) => {
				if (!(element instanceof Element)) return;
				element.setAttribute('data-emo-contact-honeypot', 'true');
				element.setAttribute('aria-hidden', 'true');
			};

			const closestHoneypotRow = (element, form) => {
				if (!(element instanceof Element)) return null;

				const row = element.closest('fieldset, p, label, .form-group, .form-row, .wpcf7-form-control-wrap, [class*="honeypot" i]');
				if (row && row !== form) return row;

				return element;
			};

			const scanContactHoneypots = (root = document) => {
				const forms = [];

				if (root instanceof Element && root.matches('.wpcf7-form')) forms.push(root);
				root.querySelectorAll?.('.wpcf7-form').forEach((form) => forms.push(form));

				forms.forEach((form) => {
					form.querySelectorAll('[class*="honeypot" i], [data-name*="honeypot" i], input[name*="honeypot" i], input[id*="honeypot" i], textarea[name*="honeypot" i], textarea[id*="honeypot" i]').forEach((element) => {
						markHidden(closestHoneypotRow(element, form));
					});

					form.querySelectorAll('label, p, fieldset, legend').forEach((element) => {
						const label = normalizeText(element.textContent);
						const isPrompt = honeypotPrompts.some((prompt) => label === prompt || label.startsWith(`${prompt} `));
						if (!isPrompt) return;

						markHidden(closestHoneypotRow(element, form));
					});
				});
			};

			const fixFooterTerms = (root = document) => {
				const footer = root instanceof Element && root.matches('footer, #colophon, .site-footer')
					? root
					: document.querySelector('footer, #colophon, .site-footer');
				if (!footer) return;

				footer.querySelectorAll('a').forEach((link) => {
					const label = normalizeText(link.textContent);
					if (label === 'terms and conditions' || label === 'terms & conditions') {
						link.textContent = 'Terms and Conditions';
					}
				});
			};

			const scan = (root = document) => {
				scanContactHoneypots(root);
				fixFooterTerms(root);
			};

			const start = () => {
				scan();
				window.setTimeout(() => scan(), 250);
				window.setTimeout(() => scan(), 1000);

				const observer = new MutationObserver((mutations) => {
					let shouldScan = false;
					for (const mutation of mutations) {
						if (mutation.type === 'characterData' || mutation.addedNodes.length) {
							shouldScan = true;
							break;
						}
					}
					if (shouldScan) scan();
				});

				observer.observe(document.body, {
					childList: true,
					subtree: true,
					characterData: true
				});
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
