<?php
/**
 * Pulido visual global aplicado después de Woostify y de los plugins.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-global-finish">
			/* Pie más compacto, legible y coherente en todas las superficies. */
			body.elmercado-child-theme .site-footer {
				margin-top: clamp(2.5rem, 5vw, 4.75rem) !important;
				padding-top: clamp(1.75rem, 3.5vw, 3rem) !important;
			}

			body.elmercado-premium-home .site-footer {
				margin-top: 0 !important;
			}

			body.elmercado-child-theme .site-footer-widget,
			body.elmercado-child-theme .site-footer .footer-widget-area,
			body.elmercado-child-theme .site-footer .footer-widgets {
				padding-top: 0.75rem !important;
				padding-bottom: clamp(1.5rem, 3vw, 2.25rem) !important;
			}

			body.elmercado-child-theme .site-footer .widget {
				margin-bottom: 1.25rem !important;
			}

			body.elmercado-child-theme .site-footer .widget-title,
			body.elmercado-child-theme .site-footer h4 {
				margin-bottom: 0.75rem !important;
			}

			body.elmercado-child-theme .site-footer ul,
			body.elmercado-child-theme .site-footer p {
				margin-top: 0 !important;
				margin-bottom: 0.65rem !important;
			}

			body.elmercado-child-theme .site-footer li {
				margin-bottom: 0.3rem !important;
				line-height: 1.45 !important;
			}

			body.elmercado-child-theme .site-footer .site-info,
			body.elmercado-child-theme .site-footer .footer-bottom,
			body.elmercado-child-theme .site-footer .site-footer-inner {
				padding-top: 0.9rem !important;
				padding-bottom: 0.9rem !important;
			}

			/* Ritmo vertical compartido para páginas internas. */
			body.elmercado-child-theme:not(.elmercado-premium-home) .site-content {
				padding-top: clamp(1.75rem, 4vw, 3.5rem);
			}

			body.elmercado-child-theme .page-header,
			body.elmercado-child-theme .woocommerce-products-header {
				margin-bottom: clamp(1.5rem, 3vw, 2.5rem) !important;
			}

			body.elmercado-child-theme .woocommerce-breadcrumb,
			body.elmercado-child-theme .breadcrumbs,
			body.elmercado-child-theme .yoast-breadcrumb {
				margin-bottom: 1.25rem !important;
				color: #68736d !important;
				font-size: 0.78rem !important;
				font-weight: 650 !important;
				line-height: 1.45 !important;
			}

			body.elmercado-child-theme .woocommerce-breadcrumb a,
			body.elmercado-child-theme .breadcrumbs a,
			body.elmercado-child-theme .yoast-breadcrumb a {
				color: #21483a !important;
				text-decoration: none !important;
			}

			/* Formularios consistentes en contacto, cuenta, checkout y búsqueda. */
			body.elmercado-child-theme input[type="text"],
			body.elmercado-child-theme input[type="email"],
			body.elmercado-child-theme input[type="tel"],
			body.elmercado-child-theme input[type="password"],
			body.elmercado-child-theme input[type="search"],
			body.elmercado-child-theme input[type="number"],
			body.elmercado-child-theme select,
			body.elmercado-child-theme textarea {
				max-width: 100% !important;
				border-color: rgba(23, 63, 50, 0.18) !important;
				border-radius: 12px !important;
				background-color: #fff !important;
				color: #18221e !important;
			}

			body.elmercado-child-theme input:not([type="checkbox"]):not([type="radio"]):focus,
			body.elmercado-child-theme select:focus,
			body.elmercado-child-theme textarea:focus {
				border-color: #2f6650 !important;
				box-shadow: 0 0 0 3px rgba(47, 102, 80, 0.14) !important;
				outline: 0 !important;
			}

			body.elmercado-child-theme textarea {
				min-height: 140px;
				resize: vertical;
			}

			body.elmercado-child-theme label {
				color: #173f32;
				font-weight: 700;
			}

			body.elmercado-child-theme .required {
				color: #a74f2d !important;
			}

			/* Avisos y estados vacíos dejan de parecer contenido sin terminar. */
			body.elmercado-child-theme .woocommerce-info,
			body.elmercado-child-theme .woocommerce-message,
			body.elmercado-child-theme .woocommerce-error,
			body.elmercado-child-theme .woocommerce-noreviews,
			body.elmercado-child-theme .no-results,
			body.elmercado-child-theme .not-found {
				border: 1px solid rgba(23, 63, 50, 0.13) !important;
				border-radius: 16px !important;
				background: #fffdf8 !important;
				box-shadow: none !important;
				color: #18221e !important;
			}

			body.elmercado-child-theme .woocommerce-info,
			body.elmercado-child-theme .woocommerce-message,
			body.elmercado-child-theme .woocommerce-error {
				margin-bottom: 1.25rem !important;
				padding: 1rem 1.1rem 1rem 3rem !important;
			}

			/* Paginación uniforme en catálogo, blog y vendedores. */
			body.elmercado-child-theme .pagination,
			body.elmercado-child-theme .woocommerce-pagination,
			body.elmercado-child-theme .nav-links {
				margin-top: clamp(1.75rem, 4vw, 3rem) !important;
			}

			body.elmercado-child-theme .page-numbers {
				display: inline-flex !important;
				align-items: center;
				justify-content: center;
				min-width: 42px;
				min-height: 42px;
				margin: 0.2rem !important;
				padding: 0.45rem 0.7rem !important;
				border: 1px solid rgba(23, 63, 50, 0.14) !important;
				border-radius: 999px !important;
				background: #fff !important;
				color: #173f32 !important;
				font-weight: 800 !important;
				text-decoration: none !important;
			}

			body.elmercado-child-theme .page-numbers.current,
			body.elmercado-child-theme a.page-numbers:hover,
			body.elmercado-child-theme a.page-numbers:focus-visible {
				border-color: #173f32 !important;
				background: #173f32 !important;
				color: #fff !important;
			}

			/* Tablas y contenido legal seguros en pantallas estrechas. */
			body.elmercado-child-theme .entry-content table,
			body.elmercado-child-theme .woocommerce table {
				border-collapse: separate !important;
				border-spacing: 0 !important;
				border: 1px solid rgba(23, 63, 50, 0.13) !important;
				border-radius: 14px !important;
				background: #fff !important;
				overflow: hidden;
			}

			body.elmercado-child-theme .entry-content th,
			body.elmercado-child-theme .woocommerce table th {
				background: #eef3ef !important;
				color: #173f32 !important;
			}

			body.elmercado-child-theme .entry-content hr {
				margin-block: clamp(2rem, 5vw, 3.5rem);
				border-color: rgba(23, 63, 50, 0.12);
			}

			body.elmercado-child-theme .entry-content > :last-child,
			body.elmercado-child-theme .page-content > :last-child {
				margin-bottom: 0 !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme .site-footer {
					margin-top: 2.25rem !important;
					padding-top: 1.5rem !important;
				}

				body.elmercado-child-theme .site-footer-widget,
				body.elmercado-child-theme .site-footer .footer-widget-area,
				body.elmercado-child-theme .site-footer .footer-widgets {
					padding-bottom: 1.25rem !important;
				}

				body.elmercado-child-theme .site-footer .widget {
					margin-bottom: 0.85rem !important;
				}

				body.elmercado-child-theme:not(.elmercado-premium-home) .site-content {
					padding-top: 1.35rem;
				}

				body.elmercado-child-theme .entry-content table,
				body.elmercado-child-theme .woocommerce table {
					display: block;
					max-width: 100%;
					overflow-x: auto;
					-webkit-overflow-scrolling: touch;
				}

				body.elmercado-child-theme .woocommerce-info,
				body.elmercado-child-theme .woocommerce-message,
				body.elmercado-child-theme .woocommerce-error {
					padding-right: 0.9rem !important;
					padding-left: 2.65rem !important;
				}
			}

			@media (prefers-reduced-motion: reduce) {
				body.elmercado-child-theme *,
				body.elmercado-child-theme *::before,
				body.elmercado-child-theme *::after {
					scroll-behavior: auto !important;
					transition-duration: 0.01ms !important;
					animation-duration: 0.01ms !important;
					animation-iteration-count: 1 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
