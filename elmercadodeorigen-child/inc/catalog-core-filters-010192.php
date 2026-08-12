<?php
/**
 * Filtros base coherentes para tienda y categorías 0.10.192.
 *
 * Tienda: Precio → Categorías → Vendedor.
 * Categorías: Precio → Vendedor → filtros específicos de la categoría.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indica si estamos en una vista de catálogo soportada por estos filtros.
 */
function elmercado_core_filters_is_catalog(): bool {
	return function_exists( 'is_shop' ) && function_exists( 'is_product_category' ) && ( is_shop() || is_product_category() );
}

/**
 * Nombre comercial del vendedor, con fallback al nombre público de WordPress.
 */
function elmercado_core_filter_vendor_name( int $user_id ): string {
	$settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
	if ( is_array( $settings ) && ! empty( $settings['store_name'] ) ) {
		return sanitize_text_field( (string) $settings['store_name'] );
	}

	$store_name = get_user_meta( $user_id, 'store_name', true );
	if ( is_string( $store_name ) && '' !== trim( $store_name ) ) {
		return sanitize_text_field( $store_name );
	}

	$user = get_userdata( $user_id );
	return $user instanceof WP_User ? sanitize_text_field( $user->display_name ) : '';
}

/**
 * Obtiene los autores que tienen productos publicados en el contexto actual.
 *
 * @return array<int, string> ID de usuario => nombre comercial.
 */
function elmercado_core_filter_vendors(): array {
	global $wpdb;

	$author_ids = array();
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && 'product_cat' === $term->taxonomy ) {
			$children = get_term_children( (int) $term->term_id, 'product_cat' );
			if ( is_wp_error( $children ) ) {
				$children = array();
			}

			$term_ids = array_merge(
				array( (int) $term->term_id ),
				array_map( 'intval', (array) $children )
			);
			$term_ids = array_values( array_unique( array_filter( $term_ids ) ) );

			if ( $term_ids ) {
				$placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
				$sql = "SELECT DISTINCT p.post_author
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
					INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					WHERE p.post_type = 'product'
					AND p.post_status = 'publish'
					AND tt.taxonomy = 'product_cat'
					AND tt.term_id IN ({$placeholders})";
				$prepared = $wpdb->prepare( $sql, ...$term_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$author_ids = array_map( 'intval', (array) $wpdb->get_col( $prepared ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			}
		}
	} else {
		$sql = $wpdb->prepare(
			"SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
			'product',
			'publish'
		);
		$author_ids = array_map( 'intval', (array) $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	$vendors = array();
	foreach ( array_values( array_unique( array_filter( $author_ids ) ) ) as $author_id ) {
		$name = elmercado_core_filter_vendor_name( $author_id );
		if ( '' !== $name ) {
			$vendors[ $author_id ] = $name;
		}
	}

	natcasesort( $vendors );
	return $vendors;
}

/**
 * Filtra el loop principal por vendedor cuando existe vendor_id en la URL.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( 'product' ) && ! $query->is_tax( 'product_cat' ) ) {
			return;
		}

		$vendor_id = isset( $_GET['vendor_id'] ) ? absint( wp_unslash( $_GET['vendor_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $vendor_id > 0 && get_userdata( $vendor_id ) instanceof WP_User ) {
			$query->set( 'author', $vendor_id );
		}
	},
	25
);

/**
 * Genera la URL actual preservando el resto de filtros y cambiando vendedor.
 */
function elmercado_core_filter_vendor_url( int $vendor_id = 0 ): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/tienda/';
	$url         = home_url( $request_uri );
	$url         = remove_query_arg( array( 'paged', 'product-page', 'vendor_id' ), $url );

	return $vendor_id > 0 ? add_query_arg( 'vendor_id', $vendor_id, $url ) : $url;
}

/**
 * Render del filtro vendedor en un contenedor que se recoloca en el sidebar.
 */
add_action(
	'woocommerce_before_main_content',
	static function (): void {
		if ( is_admin() || ! elmercado_core_filters_is_catalog() ) {
			return;
		}

		$vendors   = elmercado_core_filter_vendors();
		$active_id = isset( $_GET['vendor_id'] ) ? absint( wp_unslash( $_GET['vendor_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<aside id="emo-global-vendor-filter" class="widget woocommerce emo-global-vendor-filter" hidden aria-label="Filtrar por vendedor">
			<h3 class="widget-title emo-global-vendor-filter__title">Vendedor</h3>
			<?php if ( $active_id > 0 ) : ?>
				<a class="emo-global-vendor-filter__clear" href="<?php echo esc_url( elmercado_core_filter_vendor_url() ); ?>">Quitar vendedor</a>
			<?php endif; ?>
			<ul class="emo-global-vendor-filter__list">
				<?php foreach ( $vendors as $vendor_id => $vendor_name ) : ?>
					<li class="emo-global-vendor-filter__item<?php echo $active_id === (int) $vendor_id ? ' is-active' : ''; ?>">
						<a href="<?php echo esc_url( elmercado_core_filter_vendor_url( (int) $vendor_id ) ); ?>"<?php echo $active_id === (int) $vendor_id ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $vendor_name ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</aside>
		<?php
	},
	42
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-core-filters-010192">
			#emo-global-vendor-filter[hidden] { display:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter {
				box-sizing:border-box !important;
				width:100% !important;
				margin:0 !important;
				padding:12px 0 10px !important;
				border:0 !important;
				border-bottom:1px solid rgba(23,63,50,.085) !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter .emo-global-vendor-filter__title {
				display:block !important;
				width:100% !important;
				min-height:0 !important;
				margin:0 0 7px !important;
				padding:0 2px !important;
				border:0 !important;
				background:transparent !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12.5px !important;
				font-weight:750 !important;
				line-height:1.3 !important;
				text-align:left !important;
				text-transform:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter .emo-global-vendor-filter__clear {
				display:inline-block !important;
				margin:0 2px 7px !important;
				color:#687b72 !important;
				font-size:10.5px !important;
				font-weight:700 !important;
				text-decoration:underline !important;
				text-underline-offset:3px !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter .emo-global-vendor-filter__list {
				max-height:230px !important;
				overflow:auto !important;
				margin:0 !important;
				padding:0 3px 0 0 !important;
				list-style:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter .emo-global-vendor-filter__item {
				margin:0 !important;
				padding:0 !important;
				border:0 !important;
				border-radius:7px !important;
				list-style:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter .emo-global-vendor-filter__item > a {
				display:block !important;
				min-height:29px !important;
				padding:6px 4px !important;
				color:#42584f !important;
				font-size:12px !important;
				font-weight:600 !important;
				line-height:1.3 !important;
				text-align:left !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter .emo-global-vendor-filter__item:hover {
				background:#f6f8f6 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter .emo-global-vendor-filter__item.is-active {
				background:#edf4ef !important;
				box-shadow:inset 3px 0 0 #2f7d5d !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter .emo-global-vendor-filter__item.is-active > a {
				color:#173f32 !important;
				font-weight:750 !important;
			}

			/* En la tienda sólo quedan los tres filtros base solicitados. */
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_product_tag_cloud,
				.widget_tag_cloud,
				.wp-block-tag-cloud,
				.widget_layered_nav:not(#emo-global-vendor-filter)
			) {
				display:none !important;
				visibility:hidden !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		?>
		<script id="elmercado-catalog-core-filters-controller-010192">
		(() => {
			'use strict';

			const vendor = document.getElementById('emo-global-vendor-filter');
			if (!vendor) return;

			const isCategory = document.body.classList.contains('tax-product_cat');
			const findSidebar = () => document.querySelector(
				'.emo-mobile-filter-content #secondary.widget-area,' +
				'.emo-mobile-filter-content .shop-widget-area,' +
				'.emo-mobile-filter-content .widget-area,' +
				'#secondary.widget-area,' +
				'.shop-widget-area,' +
				'.content-area + .widget-area'
			);

			const directWidget = (sidebar, selectors) => Array.from(sidebar.children).find((child) =>
				selectors.some((selector) => child.matches?.(selector) || child.querySelector?.(selector))
			) || null;

			let scheduled = false;
			const place = () => {
				scheduled = false;
				const sidebar = findSidebar();
				if (!sidebar) return;

				if (vendor.parentElement !== sidebar) sidebar.append(vendor);
				vendor.hidden = false;
				vendor.removeAttribute('aria-hidden');

				const price = directWidget(sidebar, ['.widget_price_filter', '.wc-block-price-filter', '.wp-block-woocommerce-price-filter']);
				const categories = directWidget(sidebar, ['.widget_product_categories', '.wc-block-product-categories', '.wp-block-woocommerce-product-categories']);
				const context = document.getElementById('emo-category-context');
				const specific = document.getElementById('emo-category-attribute-filters');

				const desired = (isCategory
					? [price, vendor, context, specific]
					: [price, categories, vendor]
				).filter(Boolean);

				const alreadyOrdered = desired.every((node, index) => sidebar.children[index] === node);
				if (!alreadyOrdered) {
					desired.slice().reverse().forEach((node) => sidebar.prepend(node));
				}

				desired.forEach((node) => {
					node.hidden = false;
					node.removeAttribute('aria-hidden');
				});

				if (!isCategory) {
					const allowed = new Set(desired);
					Array.from(sidebar.children).forEach((child) => {
						if (allowed.has(child)) return;
						if (!child.matches?.('.widget,.widget_block,[class*="widget_"],[class*="wp-block-woocommerce-"]')) return;
						child.hidden = true;
						child.setAttribute('aria-hidden', 'true');
						child.setAttribute('data-emo-shop-core-hidden', 'true');
					});
				}

				if (isCategory) {
					if (context) { context.hidden = false; context.removeAttribute('aria-hidden'); }
					if (specific) { specific.hidden = false; specific.removeAttribute('aria-hidden'); }
				}
			};

			const schedule = () => {
				if (scheduled) return;
				scheduled = true;
				requestAnimationFrame(place);
			};

			place();
			setTimeout(place, 220);
			setTimeout(place, 760);
			setTimeout(place, 1300);

			const observer = new MutationObserver(schedule);
			observer.observe(document.body, { childList:true, subtree:true });
			window.addEventListener('pageshow', place, { passive:true });
			window.addEventListener('resize', schedule, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
