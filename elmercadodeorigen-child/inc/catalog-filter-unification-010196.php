<?php
/**
 * Sistema visual unificado de filtros 0.10.196.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_filter_vendor_count_010196( int $vendor_id ): int {
	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'author'                 => $vendor_id,
		'posts_per_page'         => 1,
		'fields'                 => 'ids',
		'no_found_rows'          => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && 'product_cat' === $term->taxonomy ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => array( (int) $term->term_id ),
					'include_children' => true,
				),
			);
		}
	}

	$query = new WP_Query( $args );
	return (int) $query->found_posts;
}

function elmercado_filter_counts_010196(): array {
	$data = array( 'vendors' => array(), 'categories' => array() );

	if ( function_exists( 'elmercado_core_filter_vendors' ) ) {
		foreach ( elmercado_core_filter_vendors() as $vendor_id => $vendor_name ) {
			$count = elmercado_filter_vendor_count_010196( (int) $vendor_id );
			if ( $count > 0 ) {
				$data['vendors'][ (string) $vendor_id ] = $count;
			}
		}
	}

	if ( function_exists( 'is_shop' ) && is_shop() && taxonomy_exists( 'product_cat' ) ) {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( ! $term instanceof WP_Term ) {
					continue;
				}
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					$data['categories'][ untrailingslashit( (string) $link ) ] = (int) $term->count;
				}
			}
		}
	}

	return $data;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		$is_catalog = function_exists( 'is_shop' ) && function_exists( 'is_product_category' ) && ( is_shop() || is_product_category() );
		$is_home    = function_exists( 'is_front_page' ) && is_front_page();
		if ( ! $is_catalog && ! $is_home ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-unification-010196">
			html body.home.elmercado-child-theme .emo-home .emo-categories .emo-category-grid .emo-category-card > .emo-category-card__content {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) auto !important;
				align-items:center !important;
				gap:10px !important;
				padding-top:12px !important;
				padding-bottom:12px !important;
			}
			html body.home.elmercado-child-theme .emo-home .emo-categories .emo-category-card__content small {
				margin:0 !important;
				white-space:nowrap !important;
				text-align:right !important;
			}
			html body.home.elmercado-child-theme .emo-home .emo-categories .emo-category-card > svg { display:none !important; }

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) > :is(.widget_price_filter,.widget_product_categories,#emo-global-vendor-filter,#emo-category-attribute-filters) {
				box-sizing:border-box !important;
				width:100% !important;
				margin:0 !important;
				padding:10px 0 12px !important;
				border:0 !important;
				border-bottom:1px solid rgba(23,63,50,.09) !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(.widget_price_filter > .widget-title,.widget_price_filter > .widgettitle,.widget_product_categories > .widget-title,.widget_product_categories > .widgettitle,#emo-global-vendor-filter > .emo-global-vendor-filter__title,#emo-category-attribute-filters .emo-category-filter-title) {
				display:flex !important;
				width:100% !important;
				min-height:32px !important;
				align-items:center !important;
				margin:0 0 7px !important;
				padding:6px 9px !important;
				border:1px solid rgba(47,125,93,.10) !important;
				border-left:3px solid #2f7d5d !important;
				border-radius:8px !important;
				background:#f2f6f3 !important;
				background-image:none !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:800 !important;
				letter-spacing:.015em !important;
				line-height:1.25 !important;
				text-align:left !important;
				text-transform:none !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(.widget_product_categories li,#emo-global-vendor-filter .emo-global-vendor-filter__item) {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) auto !important;
				align-items:center !important;
				column-gap:8px !important;
				min-height:30px !important;
				margin:0 !important;
				padding:0 4px !important;
				border:0 !important;
				border-radius:7px !important;
				background:transparent !important;
				box-shadow:none !important;
				list-style:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) .widget_product_categories ul.children {
				grid-column:1 / -1 !important;
				width:100% !important;
				margin:1px 0 2px !important;
				padding-left:12px !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(.widget_product_categories li > a,#emo-global-vendor-filter .emo-global-vendor-filter__item > a) {
				display:block !important;
				min-width:0 !important;
				min-height:0 !important;
				margin:0 !important;
				padding:6px 3px !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
				color:#42584f !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:600 !important;
				line-height:1.3 !important;
				text-align:left !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(.widget_product_categories .count,#emo-global-vendor-filter .count,#emo-category-attribute-filters .count) {
				display:inline-flex !important;
				align-items:center !important;
				justify-content:flex-end !important;
				min-width:22px !important;
				margin:0 1px 0 auto !important;
				padding:0 !important;
				border:0 !important;
				background:transparent !important;
				color:#809088 !important;
				font-size:10.5px !important;
				font-weight:650 !important;
				line-height:1 !important;
				text-align:right !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(.widget_product_categories li,#emo-global-vendor-filter .emo-global-vendor-filter__item,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item):hover { background:#f6f8f6 !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(.widget_product_categories .current-cat,#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen) {
				background:#edf4ef !important;
				box-shadow:inset 3px 0 0 #2f7d5d !important;
			}

			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters { padding:0 !important; border-bottom:0 !important; }
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-group {
				margin:0 !important;
				padding:10px 0 12px !important;
				border:0 !important;
				border-bottom:1px solid rgba(23,63,50,.09) !important;
				background:transparent !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-group:last-child { padding-bottom:2px !important; border-bottom:0 !important; }

			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-category-context {
				display:block !important;
				width:100% !important;
				margin:0 0 4px !important;
				padding:10px 11px !important;
				border:1px solid rgba(47,125,93,.14) !important;
				border-radius:10px !important;
				background:#edf4ef !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__label {
				display:block !important;
				margin:0 0 5px !important;
				color:#71827a !important;
				font-size:9.5px !important;
				font-weight:800 !important;
				letter-spacing:.06em !important;
				line-height:1.1 !important;
				text-transform:uppercase !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__row {
				display:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:10px !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__row strong { color:#173f32 !important; font-size:12.5px !important; font-weight:800 !important; line-height:1.25 !important; }
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__row a { color:#496158 !important; font-size:10.5px !important; font-weight:750 !important; text-decoration:underline !important; text-underline-offset:3px !important; }
			body.elmercado-child-theme.tax-product_cat #emo-category-applied-filters-slot-010196 { width:100% !important; margin:0 !important; padding:6px 0 0 !important; }
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_category' ) || ( ! is_shop() && ! is_product_category() ) ) {
			return;
		}
		$counts = elmercado_filter_counts_010196();
		?>
		<script id="elmercado-catalog-filter-unification-controller-010196">
		(() => {
			'use strict';
			const counts = <?php echo wp_json_encode( $counts ); ?>;
			const isCategory = document.body.classList.contains('tax-product_cat');
			const findSidebar = () => document.querySelector('.emo-mobile-filter-content #secondary.widget-area,.emo-mobile-filter-content .shop-widget-area,.emo-mobile-filter-content .widget-area,#secondary.widget-area,.shop-widget-area,.content-area + .widget-area');
			const directWidget = (sidebar, selectors) => Array.from(sidebar.children).find((child) => selectors.some((selector) => child.matches?.(selector) || child.querySelector?.(selector))) || null;
			const cleanCount = (badge, value = null) => {
				if (!(badge instanceof Element)) return;
				const next = value === null ? (badge.textContent || '').replace(/[()]/g, '').trim() : String(value);
				if ((badge.textContent || '').trim() !== next) badge.textContent = next;
				badge.classList.add('count');
			};
			const syncCounts = () => {
				document.querySelectorAll('#emo-global-vendor-filter .emo-global-vendor-filter__item').forEach((item) => {
					const value = counts.vendors?.[String(item.dataset.vendorId || '')];
					if (value === undefined) return;
					let badge = item.querySelector(':scope > .count');
					if (!badge) { badge = document.createElement('span'); badge.className = 'count'; item.append(badge); }
					badge.setAttribute('aria-label', `${value} productos`);
					cleanCount(badge, value);
				});
				document.querySelectorAll('.widget_product_categories li').forEach((item) => {
					const link = item.querySelector(':scope > a');
					if (!link) return;
					let key = '';
					try { key = new URL(link.href, location.href).href.replace(/\/$/, ''); } catch (error) { return; }
					const value = counts.categories?.[key];
					if (value === undefined) return;
					let badge = item.querySelector(':scope > .count');
					if (!badge) { badge = document.createElement('span'); badge.className = 'count'; link.insertAdjacentElement('afterend', badge); }
					badge.setAttribute('aria-label', `${value} productos`);
					cleanCount(badge, value);
				});
				document.querySelectorAll('#emo-category-attribute-filters .count').forEach((badge) => cleanCount(badge));
			};
			const place = () => {
				const sidebar = findSidebar();
				if (!sidebar) return;
				const price = directWidget(sidebar, ['.widget_price_filter', '.wc-block-price-filter', '.wp-block-woocommerce-price-filter']);
				const categories = directWidget(sidebar, ['.widget_product_categories', '.wc-block-product-categories', '.wp-block-woocommerce-product-categories']);
				const vendor = document.getElementById('emo-global-vendor-filter');
				const context = document.getElementById('emo-category-context');
				const specific = document.getElementById('emo-category-attribute-filters');
				const active = document.querySelector('.emo-active-filter-chips[data-emo-global-active-filters="true"]');
				if (vendor && vendor.parentElement !== sidebar) sidebar.append(vendor);
				if (context && context.parentElement !== sidebar) sidebar.prepend(context);
				if (specific && specific.parentElement !== sidebar) sidebar.append(specific);
				if (isCategory) {
					if (active) {
						if (specific) {
							const groups = specific.querySelector('.emo-category-attribute-filters__groups');
							if (groups) specific.insertBefore(active, groups); else specific.prepend(active);
						} else {
							let slot = document.getElementById('emo-category-applied-filters-slot-010196');
							if (!slot) { slot = document.createElement('div'); slot.id = 'emo-category-applied-filters-slot-010196'; }
							if (slot.parentElement !== sidebar) sidebar.append(slot);
							if (active.parentElement !== slot) slot.append(active);
						}
					}
					const afterVendor = specific || document.getElementById('emo-category-applied-filters-slot-010196');
					const desired = [context, price, vendor, afterVendor].filter(Boolean);
					desired.slice().reverse().forEach((node) => sidebar.prepend(node));
					desired.forEach((node) => { node.hidden = false; node.removeAttribute('aria-hidden'); });
				} else {
					const activeDirect = active && active.parentElement === sidebar ? active : null;
					const desired = [activeDirect, price, categories, vendor].filter(Boolean);
					desired.slice().reverse().forEach((node) => sidebar.prepend(node));
				}
				syncCounts();
			};
			place();
			requestAnimationFrame(place);
			setTimeout(place, 260);
			setTimeout(place, 860);
			setTimeout(place, 1700);
			window.addEventListener('pageshow', place, { passive:true });
			window.addEventListener('resize', place, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
