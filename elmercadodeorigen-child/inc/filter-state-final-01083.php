<?php
/**
 * Persistencia visible de filtros y limpieza final del toolbar móvil 0.10.84.
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
		<style id="elmercado-filter-state-final-01084">
			@media (max-width: 1100px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting > .elmercado-filter-toolbar-extra {
					display: none !important;
				}

				body.elmercado-child-theme #emo-premium-filter-shell .emo-active-filters {
					margin: 0 0 20px !important;
					padding: 14px !important;
					border: 1px solid rgba(23,63,50,.11) !important;
					border-radius: 14px !important;
					background: #f4f7f3 !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell .emo-active-filters__head {
					display: flex !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 12px !important;
					margin-bottom: 10px !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell .emo-active-filters__title {
					margin: 0 !important;
					color: #173f32 !important;
					font-size: 12px !important;
					font-weight: 800 !important;
					letter-spacing: .08em !important;
					text-transform: uppercase !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell .emo-active-filters__clear {
					color: #496158 !important;
					font-size: 12px !important;
					font-weight: 700 !important;
					text-decoration: underline !important;
					text-underline-offset: 3px !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell .emo-active-filters__chips {
					display: flex !important;
					flex-wrap: wrap !important;
					gap: 7px !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell .emo-active-filter-chip {
					display: inline-flex !important;
					min-height: 32px !important;
					align-items: center !important;
					padding: 6px 10px !important;
					border-radius: 999px !important;
					background: #173f32 !important;
					color: #fff !important;
					font-size: 12px !important;
					font-weight: 700 !important;
					line-height: 1.2 !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell .emo-filter-is-active > a,
				body.elmercado-child-theme #emo-premium-filter-shell a.emo-filter-is-active {
					color: #173f32 !important;
					font-weight: 800 !important;
				}
				body.elmercado-child-theme #emo-premium-filter-shell .emo-filter-is-active > a::after,
				body.elmercado-child-theme #emo-premium-filter-shell a.emo-filter-is-active::after {
					content: "✓" !important;
					margin-left: .45rem !important;
					color: #2f7d5d !important;
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

		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
		?>
		<script id="elmercado-filter-state-final-controller-01084">
		(() => {
			'use strict';
			const body = document.body;
			if (!body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || body.classList.contains('wcfmmp-store-page')) return;
			const shopUrl = <?php echo wp_json_encode( $shop_url ); ?>;
			const compact = () => matchMedia('(max-width:1100px)').matches;
			const normalizePath = (value) => {
				try {
					const path = new URL(value, location.href).pathname.replace(/\/+$/, '');
					return path || '/';
				} catch (_) {
					return '';
				}
			};
			const humanize = (value) => decodeURIComponent(String(value || ''))
				.replace(/^pa_/, '')
				.replace(/[-_]+/g, ' ')
				.replace(/\b\p{L}/gu, (letter) => letter.toUpperCase());

			const cleanToolbar = () => {
				if (!compact()) return;
				const toolbar = document.querySelector('.woostify-sorting');
				if (!toolbar) return;
				[...toolbar.children].forEach((child) => {
					const keepsCount = child.matches?.('.woocommerce-result-count') || child.querySelector?.('.woocommerce-result-count');
					const keepsOrdering = child.matches?.('.woocommerce-ordering') || child.querySelector?.('.woocommerce-ordering');
					if (keepsCount || keepsOrdering) return;
					child.classList.add('elmercado-filter-toolbar-extra');
					child.hidden = true;
					child.setAttribute('aria-hidden', 'true');
				});
			};

			const collectState = (content) => {
				const items = [];
				const seenKeys = new Set();
				const seenLabels = new Set();
				const add = (key, label) => {
					const clean = String(label || '').replace(/\s+/g, ' ').trim();
					const labelKey = clean.toLocaleLowerCase('es');
					if (!clean || seenKeys.has(key) || seenLabels.has(labelKey)) return;
					seenKeys.add(key);
					seenLabels.add(labelKey);
					items.push({ key, label: clean });
				};
				const currentPath = normalizePath(location.href);

				content.querySelectorAll('.widget_layered_nav_filters a,.woocommerce-widget-layered-nav-list__item--chosen a,.widget_product_categories .current-cat > a').forEach((link, index) => {
					add(`dom-${index}-${normalizePath(link.href)}`, link.textContent);
					link.classList.add('emo-filter-is-active');
					link.closest('li')?.classList.add('emo-filter-is-active');
				});

				content.querySelectorAll('.widget_product_categories a,.widget_product_tag_cloud a,.tagcloud a').forEach((link) => {
					if (!link.href || normalizePath(link.href) !== currentPath) return;
					add(`path-${currentPath}`, link.textContent);
					link.classList.add('emo-filter-is-active');
					link.closest('li')?.classList.add('emo-filter-is-active');
				});

				const params = new URLSearchParams(location.search);
				const min = params.get('min_price');
				const max = params.get('max_price');
				if (min !== null || max !== null) {
					const lower = min !== null && min !== '' ? min : '0';
					const upper = max !== null && max !== '' ? max : '—';
					add('price', `Precio: ${lower}€ — ${upper}€`);
					const priceWidget = content.querySelector('.widget_price_filter');
					if (priceWidget) priceWidget.classList.add('emo-filter-is-active');
				}

				for (const [key, value] of params.entries()) {
					if (!/^filter_/i.test(key) || !value) continue;
					const group = humanize(key.replace(/^filter_/i, ''));
					value.split(',').filter(Boolean).forEach((part) => add(`${key}-${part}`, `${group}: ${humanize(part)}`));
				}
				return items;
			};

			const renderState = () => {
				cleanToolbar();
				const content = document.querySelector('#emo-premium-filter-shell .emo-mobile-filter-content');
				const toggle = document.querySelector('#emo-premium-filter-toggle');
				if (!content || !toggle) return;
				content.querySelector('.emo-active-filters')?.remove();
				content.querySelectorAll('.emo-filter-is-active').forEach((node) => node.classList.remove('emo-filter-is-active'));
				const items = collectState(content);
				const label = toggle.querySelector('.emo-filter-label');
				if (label) label.textContent = items.length ? `Filtros (${items.length})` : 'Filtros';
				if (!items.length) return;

				const section = document.createElement('section');
				section.className = 'emo-active-filters';
				section.setAttribute('aria-label', 'Filtros activos');
				const head = document.createElement('div');
				head.className = 'emo-active-filters__head';
				const title = document.createElement('h3');
				title.className = 'emo-active-filters__title';
				title.textContent = 'Filtros activos';
				const clear = document.createElement('a');
				clear.className = 'emo-active-filters__clear';
				clear.href = shopUrl;
				clear.textContent = 'Limpiar';
				head.append(title, clear);
				const chips = document.createElement('div');
				chips.className = 'emo-active-filters__chips';
				items.forEach((item) => {
					const chip = document.createElement('span');
					chip.className = 'emo-active-filter-chip';
					chip.textContent = item.label;
					chips.append(chip);
				});
				section.append(head, chips);
				content.prepend(section);
			};

			const refresh = () => {
				cleanToolbar();
				renderState();
			};
			setTimeout(refresh, 0);
			setTimeout(refresh, 160);
			setTimeout(refresh, 650);
			window.addEventListener('pageshow', refresh, { passive: true });
			window.addEventListener('popstate', refresh, { passive: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
