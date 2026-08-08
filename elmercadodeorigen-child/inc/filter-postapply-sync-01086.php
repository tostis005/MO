<?php
/**
 * Sincronización final del drawer móvil después de aplicar filtros 0.10.86.
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
		<style id="elmercado-filter-postapply-sync-01086">
			@media (max-width: 1100px) {
				/* En compacto la toolbar contiene únicamente resultados y ordenación.
				 * La regla es declarativa para que ningún control tardío pueda reaparecer. */
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .woostify-sorting > :not(.woocommerce-result-count):not(.woocommerce-ordering):not(:has(.woocommerce-result-count)):not(:has(.woocommerce-ordering)) {
					display: none !important;
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
		<script id="elmercado-filter-postapply-sync-controller-01086">
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
					content.querySelector('.widget_price_filter')?.classList.add('emo-filter-is-active');
				}

				for (const [key, value] of params.entries()) {
					if (!/^filter_/i.test(key) || !value) continue;
					const group = humanize(key.replace(/^filter_/i, ''));
					value.split(',').filter(Boolean).forEach((part) => add(`${key}-${part}`, `${group}: ${humanize(part)}`));
				}

				return items;
			};

			const syncState = () => {
				if (!compact()) return;
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

			const toggle = document.querySelector('#emo-premium-filter-toggle');
			if (toggle) toggle.addEventListener('click', syncState, { passive: true });
			window.addEventListener('load', syncState, { once: true, passive: true });
			window.addEventListener('pageshow', syncState, { passive: true });
			syncState();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
