<?php
/**
 * Acabado móvil de cabecera, filtros de catálogo y separación de tienda WCFM.
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
		<style id="elmercado-mobile-catalog-interactions-final">
			@media (max-width: 991px) {
				/* Cabecera compacta: menú + marca juntos y tres herramientas en una sola línea. */
				body.elmercado-child-theme .site-header-inner > .woostify-container {
					grid-template-columns: 28px minmax(0, 1fr) 102px !important;
					column-gap: 4px !important;
					padding-inline: 10px !important;
				}
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn {
					width: 28px !important;
					min-width: 28px !important;
					height: 40px !important;
					justify-content: flex-start !important;
				}
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn > span,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn > span::before,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn > span::after,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn .hamburger-inner,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn .hamburger-inner::before,
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn .hamburger-inner::after {
					width: 18px !important;
					max-width: 18px !important;
				}
				body.elmercado-child-theme .site-header .site-branding {
					justify-self: start !important;
					align-self: center !important;
					text-align: left !important;
				}
				body.elmercado-child-theme .site-header .site-branding .site-title,
				body.elmercado-child-theme .site-header .site-branding .site-title > a {
					text-align: left !important;
				}
				body.elmercado-child-theme .site-header .site-tools {
					display: grid !important;
					grid-template-columns: repeat(3, 34px) !important;
					grid-auto-flow: column !important;
					grid-auto-columns: 34px !important;
					width: 102px !important;
					height: 40px !important;
					min-width: 102px !important;
					gap: 0 !important;
					align-items: center !important;
					justify-items: center !important;
					align-self: center !important;
				}
				body.elmercado-child-theme .site-header .site-tools > *,
				body.elmercado-child-theme .site-header .site-tools > * > a,
				body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,.my-account,.shopping-cart,.shopping-bag-button,.tools-icon) {
					display: grid !important;
					width: 34px !important;
					height: 40px !important;
					min-width: 34px !important;
					max-width: 34px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					align-self: center !important;
					line-height: 1 !important;
					transform: none !important;
				}
				body.elmercado-child-theme .site-header .site-tools :is(svg,i) {
					margin: 0 !important;
					vertical-align: middle !important;
					transform: none !important;
				}

				/* Woostify ya imprime una X; mantenemos únicamente el cierre accesible del child theme. */
				body.elmercado-child-theme .sidebar-menu .close-sidebar-menu-btn,
				body.elmercado-child-theme .sidebar-menu .close-sidebar-menu,
				body.elmercado-child-theme .sidebar-menu [class*="close-sidebar"] {
					display: none !important;
				}
				body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					display: grid !important;
				}
			}

			/* Separación normal entre las pestañas del productor y su toolbar. */
			@media (max-width: 767px) {
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .elmercado-vendor-toolbar {
					margin-top: 16px !important;
				}
			}

			/* Disparador discreto de filtros bajo el toolbar del catálogo general. */
			.emo-mobile-filter-toggle,
			.emo-mobile-filter-shell {
				display: none;
			}
			@media (max-width: 991px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting {
					margin-bottom: 10px !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle {
					display: inline-flex !important;
					width: 100% !important;
					height: 44px !important;
					min-height: 44px !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 10px !important;
					margin: 0 0 20px !important;
					padding: 0 14px !important;
					border: 1px solid rgba(23,63,50,.13) !important;
					border-radius: 12px !important;
					background: #f7f9f6 !important;
					color: #173f32 !important;
					font-size: 12px !important;
					font-weight: 750 !important;
					letter-spacing: .01em !important;
					box-shadow: none !important;
					cursor: pointer !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle::before {
					content: "" !important;
					width: 16px !important;
					height: 16px !important;
					flex: 0 0 16px !important;
					background:
						linear-gradient(#2f7d5d,#2f7d5d) 0 3px/16px 1px no-repeat,
						linear-gradient(#2f7d5d,#2f7d5d) 0 8px/16px 1px no-repeat,
						linear-gradient(#2f7d5d,#2f7d5d) 0 13px/16px 1px no-repeat !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle .emo-filter-label {
					margin-right: auto !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle .emo-filter-chevron {
					font-size: 17px !important;
					line-height: 1 !important;
					transition: transform .18s ease !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle[aria-expanded="true"] .emo-filter-chevron {
					transform: rotate(180deg) !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-shell {
					position: fixed !important;
					inset: 0 !important;
					display: block !important;
					background: rgba(8,27,22,.42) !important;
					z-index: 10020 !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-shell[hidden] {
					display: none !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-panel {
					position: absolute !important;
					inset: 0 auto 0 0 !important;
					width: min(88vw, 350px) !important;
					max-width: 350px !important;
					height: 100% !important;
					padding: 18px 16px calc(24px + env(safe-area-inset-bottom,0px)) !important;
					overflow-y: auto !important;
					background: #fff !important;
					box-shadow: 16px 0 46px rgba(8,27,22,.18) !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-head {
					display: flex !important;
					min-height: 48px !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 12px !important;
					margin: 0 0 16px !important;
					padding-bottom: 12px !important;
					border-bottom: 1px solid rgba(23,63,50,.12) !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-title {
					margin: 0 !important;
					color: #173f32 !important;
					font-size: 18px !important;
					font-weight: 800 !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-close {
					display: grid !important;
					width: 40px !important;
					height: 40px !important;
					min-width: 40px !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 50% !important;
					background: #173f32 !important;
					color: #fff !important;
					font-size: 22px !important;
					line-height: 1 !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-panel .widget-area {
					display: block !important;
					position: static !important;
					width: 100% !important;
					max-width: none !important;
					height: auto !important;
					margin: 0 !important;
					padding: 0 !important;
					visibility: visible !important;
					opacity: 1 !important;
					transform: none !important;
				}
				html.emo-shop-filter-open,
				body.emo-shop-filter-open {
					overflow: hidden !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Render the existing mobile catalogue filter trigger in the initial HTML.
 *
 * Previously the footer JavaScript inserted it after first paint, displacing
 * the entire product grid. Keep exactly the same label and position; the
 * original drawer script below only hydrates this pre-rendered button.
 */
/**
 * Prioritize only the first visible catalogue photo (mobile LCP) and correct
 * legacy Smush sizes hints that still assume a two-column narrow-phone grid.
 * Changes HTML attributes only, not image URLs, product selection or CSS.
 */
function elmercado_shop_catalog_photo_priority_20261014( string $html ): string {
	if ( ! preg_match( '~<ul\b[^>]*\bclass=(["\'])[^"\']*\bproducts\b[^"\']*\1[^>]*>~i', $html, $loop, PREG_OFFSET_CAPTURE ) ) {
		return $html;
	}
	$start  = (int) $loop[0][1];
	$before = substr( $html, 0, $start );
	$after  = substr( $html, $start );
	$first  = true;
	$edited = preg_replace_callback(
		'~<img\b(?=[^>]*\bproduct-loop-image\b)[^>]*>~i',
		static function ( array $match ) use ( &$first ): string {
			$tag = $match[0];
			if ( $first ) {
				$first = false;
				if ( preg_match( '~\sfetchpriority=(["\'])[^"\']*\1~i', $tag ) ) {
					$tag = preg_replace( '~\sfetchpriority=(["\'])[^"\']*\1~i', ' fetchpriority="high"', $tag, 1 ) ?? $tag;
				} else {
					$tag = preg_replace( '~<img\b~i', '<img fetchpriority="high"', $tag, 1 ) ?? $tag;
				}
				$tag = preg_replace( '~\sloading=(["\'])lazy\1~i', ' loading="eager"', $tag, 1 ) ?? $tag;
			}
			// Existing WooCommerce CSS renders one product per row at <=599px.
			// Retain the original sources/format; only correct the browser hint.
			return preg_replace_callback(
				'~\b(data-sizes|sizes)=(["\'])([^"\']*)\2~i',
				static function ( array $attr ): string {
					$value = str_replace( '(max-width: 599px) 46vw', '(max-width: 599px) calc(100vw - 32px)', $attr[3] );
					return $attr[1] . '=' . $attr[2] . $value . $attr[2];
				},
				$tag
			) ?? $tag;
		},
		$after
	);
	return is_string( $edited ) ? $before . $edited : $html;
}

function elmercado_shop_early_filter_markup_20261013( string $html ): string {
	if ( '' === $html || false === stripos( $html, 'woostify-sorting' ) ) {
		return $html;
	}

	// The public shop has the exact same 285,255-byte SVG configuration as Home.
	// Reuse its content-addressed file without changing script order or icon code.
	if ( function_exists( 'elmercado_externalize_home_woostify_icons_20261013' ) ) {
		$html = elmercado_externalize_home_woostify_icons_20261013( $html );
	}

	// Deliver the first catalogue image early and keep narrow-phone srcset sharp.
	$html = elmercado_shop_catalog_photo_priority_20261014( $html );

	// Do not create a second trigger if another template already renders it.
	if ( preg_match( '~<button\b[^>]*class=(["\'])[^"\']*\bemo-mobile-filter-toggle\b[^"\']*\1~i', $html ) ) {
		return $html;
	}

	if ( ! preg_match(
		'~<div\b[^>]*class=(["\'])[^"\']*\bwoostify-sorting\b[^"\']*\1[^>]*>~i',
		$html,
		$match,
		PREG_OFFSET_CAPTURE
	) ) {
		return $html;
	}

	$open        = $match[0][0];
	$open_offset = (int) $match[0][1];
	if ( false === strpos( $open, 'emo-catalog-toolbar-shared-010229' ) ) {
		$updated = preg_replace_callback(
			'~\bclass=(["\'])([^"\']*)\1~i',
			static fn ( array $m ): string => 'class=' . $m[1] . $m[2] . ' emo-catalog-toolbar-shared-010229' . $m[1],
			$open,
			1
		);
		if ( is_string( $updated ) ) {
			$html = substr_replace( $html, $updated, $open_offset, strlen( $open ) );
			$open = $updated;
		}
	}

	// Find the actual closing DIV, preserving Woostify's nested controls.
	$position = $open_offset + strlen( $open );
	$depth    = 1;
	$limit    = 0;
	while ( $depth > 0 && ++$limit < 500 ) {
		if ( ! preg_match( '~</?div\b[^>]*>~i', $html, $tag, PREG_OFFSET_CAPTURE, $position ) ) {
			return $html;
		}
		$depth   += str_starts_with( $tag[0][0], '</' ) ? -1 : 1;
		$position = (int) $tag[0][1] + strlen( $tag[0][0] );
	}
	if ( 0 !== $depth ) {
		return $html;
	}

	$button = '<button type="button" class="emo-mobile-filter-toggle" data-emo-early-filter="1" aria-expanded="false" aria-controls="emo-mobile-filter-panel">'
		. '<span class="emo-filter-label">Filtrar productos</span>'
		. '<span class="emo-filter-chevron" aria-hidden="true">⌄</span></button>';

	return substr_replace( $html, $button, $position, 0 );
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || is_feed() || wp_doing_ajax() || ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) ) {
			return;
		}
		if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
			return;
		}
		if ( get_query_var( 'store' )
			|| ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() )
			|| ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) ) {
			return;
		}
		ob_start( 'elmercado_shop_early_filter_markup_20261013' );
	},
	-900
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-mobile-filter-drawer-final">
		(() => {
			'use strict';
			const body = document.body;
			if (!body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || body.classList.contains('wcfmmp-store-page')) return;

			const sorting = document.querySelector('.woostify-sorting');
			const sidebar = document.querySelector('#secondary.widget-area,.site-content .widget-area,.content-area + .widget-area,.shop-widget-area');
			const earlyToggle = document.querySelector('.emo-mobile-filter-toggle[data-emo-early-filter="1"]');
			const otherToggle = document.querySelector('.emo-mobile-filter-toggle');
			if (!sorting || !sidebar || (otherToggle && !earlyToggle)) return;

			const marker = document.createComment('emo-filter-sidebar-home');
			sidebar.parentNode?.insertBefore(marker, sidebar);

			const toggle = earlyToggle || document.createElement('button');
			if (!earlyToggle) {
				toggle.type = 'button';
				toggle.className = 'emo-mobile-filter-toggle';
				toggle.setAttribute('aria-expanded', 'false');
				toggle.setAttribute('aria-controls', 'emo-mobile-filter-panel');
				toggle.innerHTML = '<span class="emo-filter-label">Filtrar productos</span><span class="emo-filter-chevron" aria-hidden="true">⌄</span>';
				sorting.insertAdjacentElement('afterend', toggle);
			}

			const shell = document.createElement('div');
			shell.className = 'emo-mobile-filter-shell';
			shell.hidden = true;
			shell.innerHTML = '<aside class="emo-mobile-filter-panel" id="emo-mobile-filter-panel" aria-label="Filtros de productos"><div class="emo-mobile-filter-head"><h2 class="emo-mobile-filter-title">Filtrar productos</h2><button type="button" class="emo-mobile-filter-close" aria-label="Cerrar filtros">×</button></div><div class="emo-mobile-filter-content"></div></aside>';
			body.append(shell);

			const panel = shell.querySelector('.emo-mobile-filter-panel');
			const content = shell.querySelector('.emo-mobile-filter-content');
			const close = shell.querySelector('.emo-mobile-filter-close');
			const mobile = () => window.matchMedia('(max-width: 991px)').matches;

			const moveSidebar = () => {
				if (mobile()) {
					if (sidebar.parentElement !== content) content.append(sidebar);
				} else if (marker.parentNode && sidebar.parentElement === content) {
					marker.parentNode.insertBefore(sidebar, marker.nextSibling);
				}
			};
			const closeDrawer = (restoreFocus = true) => {
				document.documentElement.classList.remove('emo-shop-filter-open');
				body.classList.remove('emo-shop-filter-open');
				shell.hidden = true;
				toggle.setAttribute('aria-expanded', 'false');
				if (restoreFocus && mobile()) toggle.focus();
			};
			const openDrawer = () => {
				moveSidebar();
				shell.hidden = false;
				document.documentElement.classList.add('emo-shop-filter-open');
				body.classList.add('emo-shop-filter-open');
				toggle.setAttribute('aria-expanded', 'true');
				requestAnimationFrame(() => close.focus());
			};

			toggle.addEventListener('click', () => toggle.getAttribute('aria-expanded') === 'true' ? closeDrawer() : openDrawer());
			close.addEventListener('click', () => closeDrawer());
			shell.addEventListener('click', (event) => { if (event.target === shell) closeDrawer(); });
			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && !shell.hidden) {
					event.preventDefault();
					closeDrawer();
				}
			});
			window.addEventListener('resize', () => {
				if (!mobile()) closeDrawer(false);
				moveSidebar();
			}, { passive: true });
			moveSidebar();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
