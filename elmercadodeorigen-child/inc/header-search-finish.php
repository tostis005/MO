<?php
/**
 * Acabado estable de cabecera y búsqueda, refinado en 0.10.182.
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
		<style id="elmercado-header-search-finish-010182">
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

			/*
			 * Overlay nativo de Woostify. Conservamos su apertura/cierre y su formulario,
			 * pero lo llevamos al lenguaje visual editorial del sitio.
			 */
			body.elmercado-child-theme .site-dialog-search.woostify-search-wrap {
				align-items: center !important;
				justify-content: center !important;
				padding: clamp(18px,4vw,56px) !important;
				background: rgba(13,31,25,.78) !important;
				-webkit-backdrop-filter: blur(10px) saturate(.9);
				backdrop-filter: blur(10px) saturate(.9);
			}

			body.elmercado-child-theme .site-dialog-search .dialog-search-content {
				width: min(760px,100%) !important;
				max-width: 760px !important;
				min-width: 0 !important;
				border: 1px solid rgba(23,63,50,.14) !important;
				border-radius: 26px !important;
				background: linear-gradient(145deg,#fffdf8 0%,#faf6ed 100%) !important;
				box-shadow: 0 34px 100px rgba(4,18,13,.30),0 1px 0 rgba(255,255,255,.78) inset !important;
				overflow: hidden !important;
			}

			body.elmercado-child-theme .site-dialog-search .dialog-search-header {
				display: flex !important;
				min-height: 0 !important;
				align-items: flex-start !important;
				padding: 36px 82px 18px 36px !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}

			body.elmercado-child-theme .site-dialog-search .dialog-search-title {
				display: block !important;
				color: #173f32 !important;
				font-size: clamp(28px,4vw,38px) !important;
				font-weight: 720 !important;
				line-height: 1.08 !important;
				letter-spacing: -.035em !important;
				text-transform: none !important;
			}

			body.elmercado-child-theme .site-dialog-search .dialog-search-title::before {
				content: "BUSCAR EN EL MERCADO";
				display: block;
				margin-bottom: 10px;
				color: #2f7d5d;
				font-size: 11px;
				font-weight: 850;
				line-height: 1.2;
				letter-spacing: .14em;
				text-transform: uppercase;
			}

			body.elmercado-child-theme .site-dialog-search .dialog-search-title::after {
				content: "Encuentra productos seleccionados con criterio, directamente desde su origen.";
				display: block;
				max-width: 540px;
				margin-top: 11px;
				color: #617067;
				font-size: 14px;
				font-weight: 500;
				line-height: 1.5;
				letter-spacing: 0;
				text-transform: none;
			}

			body.elmercado-child-theme .site-dialog-search .dialog-search-close-icon {
				position: absolute !important;
				top: 24px !important;
				right: 24px !important;
				width: 46px !important;
				height: 46px !important;
				border: 1px solid rgba(23,63,50,.13) !important;
				border-radius: 999px !important;
				background: rgba(255,255,255,.72) !important;
				color: #173f32 !important;
				box-shadow: 0 8px 22px rgba(13,33,27,.08) !important;
				transition: background-color 160ms ease,border-color 160ms ease,transform 160ms ease !important;
			}

			body.elmercado-child-theme .site-dialog-search .dialog-search-close-icon:hover,
			body.elmercado-child-theme .site-dialog-search .dialog-search-close-icon:focus-visible {
				border-color: rgba(23,63,50,.24) !important;
				background: #edf4ef !important;
				color: #173f32 !important;
				transform: translateY(-1px);
			}

			body.elmercado-child-theme .site-dialog-search .dialog-search-close-icon svg {
				width: 18px !important;
				height: 18px !important;
			}

			body.elmercado-child-theme .site-dialog-search .search-form,
			body.elmercado-child-theme .site-dialog-search .woocommerce-product-search {
				display: flex !important;
				height: auto !important;
				align-items: center !important;
				gap: 10px !important;
				margin: 0 !important;
				padding: 0 36px 36px !important;
				background: transparent !important;
			}

			body.elmercado-child-theme .site-dialog-search .search-form label,
			body.elmercado-child-theme .site-dialog-search .woocommerce-product-search label {
				display: block !important;
				min-width: 0 !important;
				flex: 1 1 auto !important;
				margin: 0 !important;
			}

			body.elmercado-child-theme .site-dialog-search .search-field,
			body.elmercado-child-theme .site-dialog-search input[type="search"] {
				box-sizing: border-box !important;
				width: 100% !important;
				height: 62px !important;
				min-height: 62px !important;
				min-width: 0 !important;
				flex: 1 1 auto !important;
				padding: 0 20px !important;
				border: 1px solid rgba(23,63,50,.19) !important;
				border-radius: 16px !important;
				background: #fff !important;
				color: #18221e !important;
				box-shadow: 0 9px 26px rgba(13,33,27,.07) !important;
				font-size: 16px !important;
				font-style: normal !important;
				line-height: 1.2 !important;
				outline: none !important;
				transition: border-color 160ms ease,box-shadow 160ms ease,background-color 160ms ease !important;
			}

			body.elmercado-child-theme .site-dialog-search .search-field::placeholder,
			body.elmercado-child-theme .site-dialog-search input[type="search"]::placeholder {
				color: #89968e !important;
				opacity: 1 !important;
			}

			body.elmercado-child-theme .site-dialog-search .search-field:focus,
			body.elmercado-child-theme .site-dialog-search input[type="search"]:focus {
				border-color: #2f7d5d !important;
				background: #fff !important;
				box-shadow: 0 0 0 4px rgba(47,125,93,.12),0 10px 30px rgba(13,33,27,.08) !important;
			}

			body.elmercado-child-theme .site-dialog-search button[type="submit"],
			body.elmercado-child-theme .site-dialog-search input[type="submit"],
			body.elmercado-child-theme .site-dialog-search .search-submit,
			body.elmercado-child-theme .site-dialog-search .search-form-icon {
				box-sizing: border-box !important;
				height: 62px !important;
				min-height: 62px !important;
				min-width: 112px !important;
				flex: 0 0 auto !important;
				padding: 0 20px !important;
				border: 1px solid #173f32 !important;
				border-radius: 16px !important;
				background: #173f32 !important;
				color: #fff !important;
				box-shadow: 0 10px 24px rgba(23,63,50,.18) !important;
				font-size: 13px !important;
				font-weight: 800 !important;
				font-style: normal !important;
				letter-spacing: .02em !important;
				text-transform: none !important;
				transition: background-color 160ms ease,border-color 160ms ease,transform 160ms ease,box-shadow 160ms ease !important;
			}

			body.elmercado-child-theme .site-dialog-search button[type="submit"]:hover,
			body.elmercado-child-theme .site-dialog-search button[type="submit"]:focus-visible,
			body.elmercado-child-theme .site-dialog-search input[type="submit"]:hover,
			body.elmercado-child-theme .site-dialog-search input[type="submit"]:focus-visible,
			body.elmercado-child-theme .site-dialog-search .search-submit:hover,
			body.elmercado-child-theme .site-dialog-search .search-submit:focus-visible,
			body.elmercado-child-theme .site-dialog-search .search-form-icon:hover,
			body.elmercado-child-theme .site-dialog-search .search-form-icon:focus-visible {
				border-color: #2f7d5d !important;
				background: #2f7d5d !important;
				box-shadow: 0 12px 28px rgba(47,125,93,.22) !important;
				transform: translateY(-1px);
			}

			/* Mantener coherente cualquier buscador/sugerencia complementaria ya existente. */
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

			@media (max-width: 600px) {
				body.elmercado-child-theme .site-dialog-search.woostify-search-wrap {
					padding: 12px !important;
				}
				body.elmercado-child-theme .site-dialog-search .dialog-search-content {
					width: 100% !important;
					max-width: 100% !important;
					border-radius: 22px !important;
				}
				body.elmercado-child-theme .site-dialog-search .dialog-search-header {
					padding: 28px 66px 16px 24px !important;
				}
				body.elmercado-child-theme .site-dialog-search .dialog-search-title {
					font-size: 28px !important;
				}
				body.elmercado-child-theme .site-dialog-search .dialog-search-title::after {
					font-size: 13px !important;
				}
				body.elmercado-child-theme .site-dialog-search .dialog-search-close-icon {
					top: 18px !important;
					right: 18px !important;
					width: 42px !important;
					height: 42px !important;
				}
				body.elmercado-child-theme .site-dialog-search .search-form,
				body.elmercado-child-theme .site-dialog-search .woocommerce-product-search {
					gap: 8px !important;
					padding: 0 24px 24px !important;
				}
				body.elmercado-child-theme .site-dialog-search .search-field,
				body.elmercado-child-theme .site-dialog-search input[type="search"],
				body.elmercado-child-theme .site-dialog-search button[type="submit"],
				body.elmercado-child-theme .site-dialog-search input[type="submit"],
				body.elmercado-child-theme .site-dialog-search .search-submit,
				body.elmercado-child-theme .site-dialog-search .search-form-icon {
					height: 56px !important;
					min-height: 56px !important;
					border-radius: 14px !important;
				}
				body.elmercado-child-theme .site-dialog-search button[type="submit"],
				body.elmercado-child-theme .site-dialog-search input[type="submit"],
				body.elmercado-child-theme .site-dialog-search .search-submit,
				body.elmercado-child-theme .site-dialog-search .search-form-icon {
					min-width: 96px !important;
					padding: 0 16px !important;
				}
			}

			@media (max-width: 420px) {
				body.elmercado-child-theme .site-dialog-search .search-form,
				body.elmercado-child-theme .site-dialog-search .woocommerce-product-search {
					display: grid !important;
					grid-template-columns: minmax(0,1fr) !important;
				}
				body.elmercado-child-theme .site-dialog-search button[type="submit"],
				body.elmercado-child-theme .site-dialog-search input[type="submit"],
				body.elmercado-child-theme .site-dialog-search .search-submit,
				body.elmercado-child-theme .site-dialog-search .search-form-icon {
					width: 100% !important;
					min-width: 0 !important;
				}
			}

			@media (prefers-reduced-motion: reduce) {
				body.elmercado-child-theme .site-dialog-search,
				body.elmercado-child-theme .site-dialog-search .dialog-search-content,
				body.elmercado-child-theme .site-dialog-search * {
					transition-duration: .01ms !important;
					animation-duration: .01ms !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
