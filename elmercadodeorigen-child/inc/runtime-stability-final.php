<?php
/**
 * Capa final de estabilidad: cabecera consistente, cookies centradas y
 * observación DOM acotada a los componentes que realmente cambian.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* commerce.js no aporta funcionalidad necesaria fuera de superficies de compra. */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$is_commerce = function_exists( 'elmercado_is_commerce_surface' ) && elmercado_is_commerce_surface();
		$is_vendor   = function_exists( 'is_wcfm_store_page' ) && is_wcfm_store_page();

		if ( ! $is_commerce && ! $is_vendor ) {
			wp_dequeue_script( 'elmercado-commerce' );
		}
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
		<style id="elmercado-runtime-stability-final">
			/* El fondo pertenece a la cabecera completa, nunca al contenedor interior. */
			body.elmercado-child-theme .site-header {
				background: rgba(255,255,255,.98) !important;
			}
			body.elmercado-child-theme .site-header-inner,
			body.elmercado-child-theme .site-header-inner > .woostify-container {
				background: transparent !important;
				border-bottom: 0 !important;
				box-shadow: none !important;
			}
			body.elmercado-child-theme .site-header-inner {
				height: auto !important;
				min-height: 0 !important;
				padding-block: 0 !important;
				overflow: visible !important;
			}

			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header-inner > .woostify-container {
					display: grid !important;
					grid-template-columns: minmax(190px,auto) minmax(0,1fr) 148px !important;
					align-items: center !important;
					column-gap: clamp(28px,3.2vw,54px) !important;
					height: 64px !important;
					min-height: 64px !important;
					padding-block: 0 !important;
				}
				body.elmercado-child-theme .site-header .site-branding {
					grid-column: 1 !important;
					justify-self: start !important;
					margin: 0 !important;
				}
				body.elmercado-child-theme .site-header .site-branding img,
				body.elmercado-child-theme .site-header .custom-logo {
					max-height: 44px !important;
				}
				body.elmercado-child-theme .site-header .main-navigation {
					grid-column: 2 !important;
					width: 100% !important;
					margin: 0 !important;
					justify-content: center !important;
				}
				body.elmercado-child-theme .site-header .primary-navigation > li > a {
					min-height: 44px !important;
					align-items: center !important;
				}
				body.elmercado-child-theme .site-header .site-tools {
					grid-column: 3 !important;
					display: grid !important;
					grid-template-columns: repeat(3,44px) !important;
					align-items: center !important;
					justify-content: end !important;
					column-gap: 8px !important;
					width: 148px !important;
					margin: 0 !important;
				}
			}

			@media (max-width: 991px) {
				body.elmercado-child-theme .site-header,
				body.elmercado-child-theme .site-header-inner {
					height: auto !important;
					min-height: 60px !important;
				}
				body.elmercado-child-theme .site-header-inner > .woostify-container {
					height: 60px !important;
					min-height: 60px !important;
					padding-block: 0 !important;
					align-items: center !important;
				}
				body.elmercado-child-theme .site-header .site-branding,
				body.elmercado-child-theme .site-header .site-tools {
					margin-block: 0 !important;
				}
			}

			/* Cualquier contador vacío permanece fuera del layout. */
			body.elmercado-child-theme .site-header :is(.shop-cart-count,.shopping-cart-count,.cart-count,.count,.mini-cart-count,.elmercado-cart-direct-count).is-zero,
			body.elmercado-child-theme .site-header .elmercado-cart-count-empty {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
			}

			/* Cierre móvil explícito, sin observadores sobre todo el documento. */
			@media (max-width: 991px) {
				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					position: absolute !important;
					top: 14px !important;
					right: 14px !important;
					display: grid !important;
					width: 44px !important;
					height: 44px !important;
					min-width: 44px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 50% !important;
					background: #173f32 !important;
					color: transparent !important;
					font-size: 0 !important;
					box-shadow: 0 6px 20px rgba(23,63,50,.18) !important;
					z-index: 10002 !important;
				}
				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::before,
				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::after {
					content: "" !important;
					position: absolute !important;
					width: 19px !important;
					height: 2px !important;
					border-radius: 999px !important;
					background: #fff !important;
				}
				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::before { transform: rotate(45deg) !important; }
				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close::after { transform: rotate(-45deg) !important; }
			}

			/* Botón de consentimiento: el texto queda centrado ópticamente y geométricamente. */
			body.elmercado-child-theme #cookie_action_close_header {
				display: inline-flex !important;
				height: 40px !important;
				min-height: 40px !important;
				align-items: center !important;
				justify-content: center !important;
				margin: 0 0 0 auto !important;
				padding: 0 1.1rem !important;
				line-height: 1 !important;
				vertical-align: middle !important;
			}
			@media (max-width: 640px) {
				body.elmercado-child-theme #cookie_action_close_header {
					height: 38px !important;
					min-height: 38px !important;
					padding-block: 0 !important;
				}
			}

			/* Resultados y ordenación de una tienda de productor comparten una única barra. */
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-sorting-normalized {
				display: block !important;
				width: 100% !important;
				margin: 0 0 18px !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				float: none !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar {
				display: grid !important;
				grid-template-columns: minmax(0,1fr) minmax(180px,260px) !important;
				width: 100% !important;
				align-items: center !important;
				gap: 14px !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
				grid-column: 1 !important;
				grid-row: 1 !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
				grid-column: 2 !important;
				grid-row: 1 !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar :is(.woocommerce-result-count,.woocommerce-ordering) {
				display: flex !important;
				width: 100% !important;
				min-height: 46px !important;
				align-items: center !important;
				margin: 0 !important;
				padding: 0 !important;
				float: none !important;
			}
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
				width: 100% !important;
				height: 46px !important;
				min-height: 46px !important;
				margin: 0 !important;
			}
			@media (max-width: 600px) {
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar {
					grid-template-columns: minmax(0,1fr) minmax(132px,145px) !important;
					gap: 8px !important;
				}
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar :is(.woocommerce-result-count,.woocommerce-ordering),
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
					height: 44px !important;
					min-height: 44px !important;
				}
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
					font-size: 11px !important;
					line-height: 1.25 !important;
				}
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
		<script id="elmercado-runtime-vendor-toolbar">
		(() => {
			'use strict';
			const store = document.querySelector('#wcfmmp-store');
			if (!store) return;

			const commonAncestor = (first, second) => {
				let node = first?.parentElement || null;
				while (node && node !== store && node !== document.body) {
					if (node.contains(second)) return node;
					node = node.parentElement;
				}
				return null;
			};

			const mount = () => {
				const result = store.querySelector('.woocommerce-result-count');
				const ordering = store.querySelector('.woocommerce-ordering');
				if (!result || !ordering) return;
				const host = result.closest('.woostify-sorting')
					|| ordering.closest('.woostify-sorting')
					|| commonAncestor(result, ordering)
					|| result.closest('.right_side,.products-wrapper,.wcfmmp-store-product');
				if (!host || host === store) return;
				host.classList.add('elmercado-vendor-sorting-normalized');
				let toolbar = host.querySelector(':scope > .elmercado-vendor-toolbar');
				if (!toolbar) {
					toolbar = document.createElement('div');
					toolbar.className = 'elmercado-vendor-toolbar';
					toolbar.setAttribute('role', 'group');
					toolbar.setAttribute('aria-label', 'Resultados y ordenación');
					host.prepend(toolbar);
				}
				if (result.parentElement !== toolbar) toolbar.append(result);
				if (ordering.parentElement !== toolbar) toolbar.append(ordering);
			};

			mount();
			let frame = 0;
			new MutationObserver((mutations) => {
				const relevant = mutations.some((mutation) => [...mutation.addedNodes].some((node) => {
					if (!(node instanceof Element)) return false;
					return node.matches('.woocommerce-result-count,.woocommerce-ordering')
						|| Boolean(node.querySelector('.woocommerce-result-count,.woocommerce-ordering'));
				}));
				if (!relevant || frame) return;
				frame = requestAnimationFrame(() => {
					mount();
					frame = 0;
				});
			}).observe(store, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
