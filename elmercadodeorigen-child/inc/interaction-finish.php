<?php
/**
 * Acabado de interacciones globales: cabecera, búsqueda y controles flotantes.
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
		<style id="elmercado-interaction-finish">
			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header .site-tools {
					gap: 0.5rem !important;
				}

				body.elmercado-child-theme .site-header .site-tools > .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon {
					position: relative !important;
					display: grid !important;
					width: 44px !important;
					height: 44px !important;
					min-width: 44px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 1px solid transparent !important;
					border-radius: 999px !important;
					background: transparent !important;
					color: #173f32 !important;
					line-height: 1 !important;
					text-decoration: none !important;
					transition: background-color 160ms ease,border-color 160ms ease,color 160ms ease !important;
				}

				body.elmercado-child-theme .site-header .site-tools > .header-search-icon:hover,
				body.elmercado-child-theme .site-header .site-tools > .header-search-icon:focus-visible,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon:hover,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon:focus-visible,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon:hover,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon:focus-visible {
					background: #e8f1eb !important;
					border-color: rgba(23,63,50,.12) !important;
					color: #173f32 !important;
				}

				body.elmercado-child-theme .site-header .site-tools > .header-search-icon::after,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon::after,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon::after {
					content: "" !important;
					position: absolute !important;
					right: 9px !important;
					bottom: 2px !important;
					left: 9px !important;
					height: 2px !important;
					border-radius: 999px !important;
					background: #2f7d5d !important;
					transform: scaleX(0) !important;
					transition: transform 160ms ease !important;
				}

				body.elmercado-child-theme .site-header .site-tools > .header-search-icon:hover::after,
				body.elmercado-child-theme .site-header .site-tools > .header-search-icon:focus-visible::after,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon:hover::after,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon:focus-visible::after,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon:hover::after,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon:focus-visible::after {
					transform: scaleX(1) !important;
				}
			}

			/* Buscador emergente de Woostify/FiboSearch. */
			body.elmercado-child-theme .search-form-wrapper,
			body.elmercado-child-theme .header-search-form,
			body.elmercado-child-theme .site-search-form,
			body.elmercado-child-theme .dgwt-wcas-overlay-mobile,
			body.elmercado-child-theme .dgwt-wcas-search-wrapp.dgwt-wcas-layout-icon-open {
				background: rgba(255,253,248,.98) !important;
				color: #18221e !important;
			}

			body.elmercado-child-theme .search-form-wrapper,
			body.elmercado-child-theme .header-search-form,
			body.elmercado-child-theme .site-search-form {
				padding: clamp(1rem,3vw,2rem) !important;
				border: 1px solid rgba(23,63,50,.12) !important;
				box-shadow: 0 24px 70px rgba(13,33,27,.18) !important;
			}

			body.elmercado-child-theme .dgwt-wcas-search-input,
			body.elmercado-child-theme .search-form-wrapper input[type="search"],
			body.elmercado-child-theme .header-search-form input[type="search"],
			body.elmercado-child-theme .site-search-form input[type="search"] {
				height: 52px !important;
				min-height: 52px !important;
				padding: 0 3rem 0 1rem !important;
				border: 1px solid rgba(23,63,50,.18) !important;
				border-radius: 999px !important;
				background: #fff !important;
				color: #18221e !important;
				font-size: 1rem !important;
				box-shadow: none !important;
			}

			body.elmercado-child-theme .dgwt-wcas-search-input:focus,
			body.elmercado-child-theme .search-form-wrapper input[type="search"]:focus,
			body.elmercado-child-theme .header-search-form input[type="search"]:focus,
			body.elmercado-child-theme .site-search-form input[type="search"]:focus {
				border-color: #2f6650 !important;
				box-shadow: 0 0 0 4px rgba(47,102,80,.14) !important;
				outline: 0 !important;
			}

			body.elmercado-child-theme .dgwt-wcas-suggestions-wrapp,
			body.elmercado-child-theme .dgwt-wcas-details-wrapp {
				border: 1px solid rgba(23,63,50,.12) !important;
				border-radius: 16px !important;
				background: #fffdf8 !important;
				box-shadow: 0 24px 70px rgba(13,33,27,.16) !important;
				overflow: hidden !important;
			}

			body.elmercado-child-theme .dgwt-wcas-suggestion {
				padding: .8rem 1rem !important;
				border-bottom: 1px solid rgba(23,63,50,.08) !important;
				color: #18221e !important;
			}

			body.elmercado-child-theme .dgwt-wcas-suggestion:hover,
			body.elmercado-child-theme .dgwt-wcas-suggestion-selected {
				background: #edf4ef !important;
			}

			body.elmercado-child-theme .dgwt-wcas-si img {
				width: 54px !important;
				height: 68px !important;
				padding: .2rem !important;
				border-radius: 10px !important;
				background: #f5f1e8 !important;
				object-fit: contain !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme .dgwt-wcas-overlay-mobile {
					padding: 1rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
