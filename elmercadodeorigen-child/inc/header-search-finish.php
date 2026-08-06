<?php
/**
 * Acabado estable de cabecera y búsqueda.
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
		<style id="elmercado-header-search-finish">
			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header .site-tools {
					gap: .5rem !important;
				}
				body.elmercado-child-theme .site-header .site-tools > .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools > .my-account > a.tools-icon {
					position: relative !important;
					margin: 0 !important;
					border: 1px solid transparent !important;
					border-radius: 999px !important;
					background: transparent !important;
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
			body.elmercado-child-theme .search-form-wrapper,
			body.elmercado-child-theme .header-search-form,
			body.elmercado-child-theme .site-search-form,
			body.elmercado-child-theme .dgwt-wcas-overlay-mobile {
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
				box-shadow: none !important;
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
			}
			body.elmercado-child-theme .dgwt-wcas-suggestion:hover,
			body.elmercado-child-theme .dgwt-wcas-suggestion-selected {
				background: #edf4ef !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
