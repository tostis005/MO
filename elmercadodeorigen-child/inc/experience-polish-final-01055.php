<?php
/**
 * Estados de producto, validación accesible y legibilidad editorial 0.10.55.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Un producto agotado debe comunicar su estado con texto real en el DOM.
 * La capa anterior lo resolvía con un pseudo-elemento, útil visualmente pero
 * invisible para lectores de pantalla, búsquedas internas y pruebas de flujo.
 */
add_filter(
	'woocommerce_get_availability_text',
	static function ( string $availability, $product ): string {
		if ( $product instanceof WC_Product && ! $product->is_in_stock() ) {
			return __( 'Agotado temporalmente', 'elmercadodeorigen' );
		}

		return $availability;
	},
	20,
	2
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-experience-polish-final-01055">
			/* Estado de stock: una sola señal, visible y semántica. */
			body.elmercado-child-theme.single-product div.product.outofstock .summary::before {
				display: none !important;
				content: none !important;
			}

			body.elmercado-child-theme.single-product div.product.outofstock .stock.out-of-stock {
				display: inline-flex !important;
				align-items: center;
				gap: 8px;
				min-height: 34px;
				margin: 0 0 14px !important;
				padding: 7px 12px;
				border: 1px solid rgba(127, 47, 42, .20);
				border-radius: 999px;
				background: #f8ebe7;
				color: #7f2f2a !important;
				font-size: 12px;
				font-weight: 850;
				letter-spacing: .035em;
				line-height: 1.2;
				text-transform: uppercase;
			}

			body.elmercado-child-theme.single-product div.product.outofstock .stock.out-of-stock::before {
				width: 7px;
				height: 7px;
				flex: 0 0 7px;
				border-radius: 50%;
				background: currentColor;
				content: "";
			}

			/* Contacto: feedback inequívoco y una caja de mensaje proporcionada. */
			body.elmercado-child-theme.elmercado-contact-page .emo-contact-form :is(input, textarea, select) {
				transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
			}

			body.elmercado-child-theme.elmercado-contact-page .emo-contact-form textarea {
				height: 220px !important;
				min-height: 180px !important;
				resize: vertical !important;
			}

			body.elmercado-child-theme.elmercado-contact-page .emo-contact-form [aria-invalid="true"] {
				border-color: #9f4138 !important;
				background-color: #fffaf8 !important;
				box-shadow: 0 0 0 3px rgba(159, 65, 56, .10) !important;
			}

			body.elmercado-child-theme.elmercado-contact-page .emo-contact-form .emo-field-error {
				display: block;
				margin-top: 6px;
				color: #8b3029;
				font-size: 12px;
				font-weight: 700;
				line-height: 1.4;
			}

			body.elmercado-child-theme.elmercado-contact-page .emo-contact-form :is(button[type="submit"], input[type="submit"]) {
				min-width: 132px;
				min-height: 46px;
			}

			/* La ficha del artículo mantiene el mismo shell exterior, pero recupera
			 * una medida de lectura cómoda dentro de ese ancho compartido. */
			body.elmercado-child-theme.single-post .emo-article-content > :is(p, h2, h3, h4, ul, ol, blockquote, table) {
				width: 100%;
				max-width: 780px;
				margin-left: auto;
				margin-right: auto;
			}

			body.elmercado-child-theme.single-post .emo-article-content > :is(p, ul, ol, blockquote) {
				font-size: 17px;
				line-height: 1.78;
			}

			body.elmercado-child-theme.single-post .emo-article-content > :is(figure, .wp-block-image, .wp-block-gallery) {
				max-width: 980px;
				margin-left: auto;
				margin-right: auto;
			}

			/* Las descripciones largas de producto necesitan jerarquía de lectura,
			 * no una columna de texto demasiado ancha. */
			body.elmercado-child-theme.single-product .woocommerce-Tabs-panel--description > :is(p, h2, h3, h4, ul, ol, blockquote) {
				max-width: 860px;
				margin-left: auto;
				margin-right: auto;
			}

			body.elmercado-child-theme.single-product .woocommerce-Tabs-panel--description > :is(p, ul, ol) {
				font-size: 15px;
				line-height: 1.75;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme.elmercado-contact-page .emo-contact-form textarea {
					height: 190px !important;
					min-height: 160px !important;
				}

				body.elmercado-child-theme.single-post .emo-article-content > :is(p, ul, ol, blockquote) {
					font-size: 16px;
					line-height: 1.72;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Mejora progresiva para formularios de contacto de distintos plugins.
 * No sustituye su envío: sólo sincroniza required/aria-required con un estado
 * de error visible y accesible antes y después de la validación del plugin.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! is_page( array( 'contacto', 'contacto-productores' ) ) ) {
			return;
		}
		?>
		<script id="elmercado-contact-validation-01055">
		(() => {
			'use strict';

			const requiredByLabel = (control) => {
				const explicit = control.labels?.[0]?.textContent || '';
				const group = control.closest('.wpcf7-form-control-wrap,.elementor-field-group,.form-row,p');
				const nearby = group?.querySelector('label')?.textContent || group?.previousElementSibling?.textContent || '';
				return /obligatorio|required/i.test(`${explicit} ${nearby}`);
			};

			const required = (control) => control.required || control.getAttribute('aria-required') === 'true' || requiredByLabel(control);
			const messageFor = (control) => control.type === 'email' && control.value.trim()
				? 'Revisa que el correo electrónico tenga un formato válido.'
				: 'Este campo es obligatorio.';

			const isInvalid = (control) => {
				if (required(control) && !control.value.trim()) return true;
				if (control.type === 'email' && control.value.trim() && control.validity?.typeMismatch) return true;
				return false;
			};

			const errorAnchor = (control) => control.closest('.wpcf7-form-control-wrap') || control;

			const showError = (control) => {
				control.setAttribute('aria-invalid', 'true');
				let error = errorAnchor(control).parentElement?.querySelector(`.emo-field-error[data-for="${CSS.escape(control.name || control.id || 'field')}"]`);
				if (!error) {
					error = document.createElement('small');
					error.className = 'emo-field-error';
					error.dataset.for = control.name || control.id || 'field';
					error.id = `emo-field-error-${Math.random().toString(36).slice(2, 9)}`;
					error.setAttribute('role', 'alert');
					errorAnchor(control).insertAdjacentElement('afterend', error);
				}
				error.textContent = messageFor(control);
				const describedBy = new Set((control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
				describedBy.add(error.id);
				control.setAttribute('aria-describedby', [...describedBy].join(' '));
			};

			const clearError = (control) => {
				if (isInvalid(control)) return;
				control.setAttribute('aria-invalid', 'false');
				const parent = errorAnchor(control).parentElement;
				parent?.querySelectorAll('.emo-field-error').forEach((error) => {
					const tokens = (control.getAttribute('aria-describedby') || '').split(/\s+/).filter((token) => token && token !== error.id);
					control.setAttribute('aria-describedby', tokens.join(' '));
					error.remove();
				});
			};

			const validateForm = (form) => {
				const controls = [...form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]), textarea, select')];
				controls.forEach((control) => isInvalid(control) ? showError(control) : clearError(control));
			};

			document.addEventListener('DOMContentLoaded', () => {
				document.querySelectorAll('.emo-contact-form form').forEach((form) => {
					form.querySelectorAll('input, textarea, select').forEach((control) => {
						control.addEventListener('invalid', () => showError(control));
						control.addEventListener('input', () => clearError(control));
						control.addEventListener('change', () => clearError(control));
					});

					form.addEventListener('submit', () => {
						requestAnimationFrame(() => validateForm(form));
						setTimeout(() => validateForm(form), 450);
					});

					form.addEventListener('click', (event) => {
						if (!event.target.closest('button[type="submit"],input[type="submit"]')) return;
						requestAnimationFrame(() => validateForm(form));
						setTimeout(() => validateForm(form), 450);
					});
				});

				document.addEventListener('wpcf7invalid', (event) => {
					const form = event.target?.closest?.('.emo-contact-form form') || document.querySelector('.emo-contact-form form');
					if (form) validateForm(form);
				});
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
