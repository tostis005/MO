<?php
/**
 * Sistema definitivo de filtros de catálogo 0.10.207.
 *
 * Un único módulo controla contexto de categoría, vendedor, filtros aplicados,
 * orden y presentación. Recupera el acabado histórico elegante del filtro de
 * precio y elimina Etiquetas de Tienda y categorías desde la primera pintura.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_core_filters_is_catalog(): bool {
	return function_exists( 'is_shop' ) && function_exists( 'is_product_category' ) && ( is_shop() || is_product_category() );
}

function elmercado_core_filter_is_vendor( int $user_id ): bool {
	if ( $user_id <= 1 ) {
		return false;
	}
	$user = get_userdata( $user_id );
	if ( ! $user instanceof WP_User ) {
		return false;
	}
	$roles = array_map( 'sanitize_key', (array) $user->roles );
	if ( in_array( 'administrator', $roles, true ) ) {
		return false;
	}
	$settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
	if ( is_array( $settings ) && ! empty( $settings['store_name'] ) ) {
		return true;
	}
	$store_name = get_user_meta( $user_id, 'store_name', true );
	if ( is_string( $store_name ) && '' !== trim( $store_name ) ) {
		return true;
	}
	return (bool) array_intersect( array( 'wcfm_vendor', 'dc_vendor', 'vendor', 'seller' ), $roles );
}

function elmercado_core_filter_vendor_name( int $user_id ): string {
	if ( ! elmercado_core_filter_is_vendor( $user_id ) ) {
		return '';
	}
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

/** @return int[] */
function elmercado_catalog_context_term_ids_010207(): array {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return array();
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
		return array();
	}
	$children = get_term_children( (int) $term->term_id, 'product_cat' );
	if ( is_wp_error( $children ) ) {
		$children = array();
	}
	return array_values( array_unique( array_filter( array_merge( array( (int) $term->term_id ), array_map( 'intval', (array) $children ) ) ) ) );
}

/** @return array<int,int> */
function elmercado_catalog_vendor_counts_010207(): array {
	global $wpdb;
	$term_ids = elmercado_catalog_context_term_ids_010207();
	if ( $term_ids ) {
		$placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
		$sql = "SELECT p.post_author, COUNT(DISTINCT p.ID) AS product_count
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'
			AND tt.taxonomy = 'product_cat'
			AND tt.term_id IN ({$placeholders})
			GROUP BY p.post_author";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$term_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
	} else {
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT post_author, COUNT(ID) AS product_count FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s GROUP BY post_author",
				'product',
				'publish'
			)
		);
	}
	$counts = array();
	foreach ( (array) $rows as $row ) {
		$author_id = isset( $row->post_author ) ? (int) $row->post_author : 0;
		$count     = isset( $row->product_count ) ? (int) $row->product_count : 0;
		if ( $author_id > 0 && $count > 0 && elmercado_core_filter_is_vendor( $author_id ) ) {
			$counts[ $author_id ] = $count;
		}
	}
	return $counts;
}

/** @return array<int,string> */
function elmercado_core_filter_vendors(): array {
	$vendors = array();
	foreach ( array_keys( elmercado_catalog_vendor_counts_010207() ) as $vendor_id ) {
		$name = elmercado_core_filter_vendor_name( (int) $vendor_id );
		if ( '' !== $name ) {
			$vendors[ (int) $vendor_id ] = $name;
		}
	}
	natcasesort( $vendors );
	return $vendors;
}

function elmercado_core_filter_vendor_url( int $vendor_id = 0 ): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/tienda/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$url         = remove_query_arg( array( 'paged', 'product-page', 'vendor_id' ), home_url( $request_uri ) );
	return $vendor_id > 0 ? add_query_arg( 'vendor_id', $vendor_id, $url ) : $url;
}

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
		if ( $vendor_id > 0 && elmercado_core_filter_is_vendor( $vendor_id ) ) {
			$query->set( 'author', $vendor_id );
		}
	},
	25
);

function elmercado_active_filters_current_url(): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	return remove_query_arg( array( 'product-page', 'paged' ), home_url( $request_uri ) );
}

/** @return array<int,array{group:string,name:string,url:string}> */
function elmercado_active_filter_chips(): array {
	if ( ! elmercado_core_filters_is_catalog() || ! class_exists( 'WC_Query' ) ) {
		return array();
	}
	$chips   = array();
	$current = elmercado_active_filters_current_url();
	$has_min = isset( $_GET['min_price'] ) && '' !== (string) $_GET['min_price']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$has_max = isset( $_GET['max_price'] ) && '' !== (string) $_GET['max_price']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $has_min || $has_max ) {
		$min = $has_min ? (float) wc_clean( wp_unslash( $_GET['min_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max = $has_max ? (float) wc_clean( wp_unslash( $_GET['max_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( null !== $min && null !== $max ) {
			$name = wc_format_localized_price( $min ) . '–' . wc_format_localized_price( $max ) . ' €';
		} elseif ( null !== $min ) {
			$name = 'Desde ' . wc_format_localized_price( $min ) . ' €';
		} else {
			$name = 'Hasta ' . wc_format_localized_price( (float) $max ) . ' €';
		}
		$chips[] = array( 'group' => 'Precio', 'name' => $name, 'url' => remove_query_arg( array( 'min_price', 'max_price' ), $current ) );
	}
	$vendor_id = isset( $_GET['vendor_id'] ) ? absint( wp_unslash( $_GET['vendor_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $vendor_id > 0 && elmercado_core_filter_is_vendor( $vendor_id ) ) {
		$name = elmercado_core_filter_vendor_name( $vendor_id );
		if ( '' !== $name ) {
			$chips[] = array( 'group' => 'Vendedor', 'name' => $name, 'url' => remove_query_arg( 'vendor_id', $current ) );
		}
	}
	$profile = function_exists( 'elmercado_catalog_filter_profile' ) ? elmercado_catalog_filter_profile() : null;
	$chosen  = WC_Query::get_layered_nav_chosen_attributes();
	if ( $profile ) {
		foreach ( (array) ( $profile['attributes'] ?? array() ) as $slug => $label ) {
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			$terms    = isset( $chosen[ $taxonomy ]['terms'] ) ? (array) $chosen[ $taxonomy ]['terms'] : array();
			if ( ! $terms ) {
				continue;
			}
			$filter_param = 'filter_' . $slug;
			$query_param  = 'query_type_' . $slug;
			$raw_value    = isset( $_GET[ $filter_param ] ) ? wc_clean( wp_unslash( $_GET[ $filter_param ] ) ) : implode( ',', $terms ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected     = array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) $raw_value ) ) ) );
			foreach ( $terms as $term_slug ) {
				$term_slug = sanitize_title( $term_slug );
				$term      = get_term_by( 'slug', $term_slug, $taxonomy );
				$remaining = array_values( array_diff( $selected, array( $term_slug ) ) );
				$remove    = $remaining ? add_query_arg( $filter_param, implode( ',', $remaining ), $current ) : remove_query_arg( array( $filter_param, $query_param ), $current );
				$chips[]   = array( 'group' => (string) $label, 'name' => $term instanceof WP_Term ? (string) $term->name : $term_slug, 'url' => $remove );
			}
		}
	}
	return $chips;
}

function elmercado_active_filters_clear_url(): string {
	if ( function_exists( 'is_product_category' ) && is_product_category() && function_exists( 'elmercado_catalog_filter_clear_url' ) ) {
		return elmercado_catalog_filter_clear_url();
	}
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
}

add_action(
	'woocommerce_before_main_content',
	static function (): void {
		if ( is_admin() || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term && 'product_cat' === $term->taxonomy ) {
				$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
				?>
				<aside id="emo-category-context" class="emo-category-context emo-catalog-filter-pending" aria-label="Categoría activa">
					<div class="emo-category-context__row">
						<strong class="emo-category-context__name"><?php echo esc_html( $term->name ); ?></strong>
						<a class="emo-category-context__remove" href="<?php echo esc_url( $shop_url ); ?>" aria-label="<?php echo esc_attr( 'Quitar categoría ' . $term->name ); ?>"><span aria-hidden="true">×</span><span>Quitar</span></a>
					</div>
				</aside>
				<?php
			}
		}
		$chips = elmercado_active_filter_chips();
		if ( $chips ) {
			?>
			<template id="emo-active-filter-chips-template">
				<section class="emo-active-filter-chips" data-emo-global-active-filters="true" aria-label="Filtros aplicados">
					<div class="emo-active-filter-chips__head"><strong>Filtros aplicados</strong><a class="emo-active-filter-chips__clear" href="<?php echo esc_url( elmercado_active_filters_clear_url() ); ?>">Limpiar todo</a></div>
					<div class="emo-active-filter-chips__list">
						<?php foreach ( $chips as $chip ) : ?>
							<a class="emo-active-filter-chip" href="<?php echo esc_url( $chip['url'] ); ?>" aria-label="<?php echo esc_attr( 'Quitar ' . $chip['group'] . ': ' . $chip['name'] ); ?>"><span class="emo-active-filter-chip__group"><?php echo esc_html( $chip['group'] ); ?></span><span class="emo-active-filter-chip__name"><?php echo esc_html( $chip['name'] ); ?></span><span class="emo-active-filter-chip__remove" aria-hidden="true">×</span></a>
						<?php endforeach; ?>
					</div>
				</section>
			</template>
			<?php
		}
		$counts    = elmercado_catalog_vendor_counts_010207();
		$vendors   = elmercado_core_filter_vendors();
		$active_id = isset( $_GET['vendor_id'] ) ? absint( wp_unslash( $_GET['vendor_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<aside id="emo-global-vendor-filter" class="widget woocommerce emo-global-vendor-filter emo-catalog-filter-pending" aria-label="Filtrar por vendedor">
			<h3 class="widget-title emo-global-vendor-filter__title">Vendedor</h3>
			<ul class="emo-global-vendor-filter__list">
				<?php foreach ( $vendors as $vendor_id => $vendor_name ) : ?>
					<li class="emo-global-vendor-filter__item<?php echo $active_id === (int) $vendor_id ? ' is-active' : ''; ?>" data-vendor-id="<?php echo esc_attr( (string) $vendor_id ); ?>">
						<a href="<?php echo esc_url( elmercado_core_filter_vendor_url( (int) $vendor_id ) ); ?>"<?php echo $active_id === (int) $vendor_id ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $vendor_name ); ?></a>
						<span class="count" aria-label="<?php echo esc_attr( (string) ( $counts[ $vendor_id ] ?? 0 ) . ' productos' ); ?>"><?php echo esc_html( (string) ( $counts[ $vendor_id ] ?? 0 ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</aside>
		<?php
	},
	38
);

add_action(
	'wp_head',
	static function (): void {
		$is_catalog = elmercado_core_filters_is_catalog();
		$is_home    = function_exists( 'is_front_page' ) && is_front_page();
		if ( is_admin() || ( ! $is_catalog && ! $is_home ) ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-system-final-010207">
			body.home.elmercado-child-theme .emo-category-card > svg,
			body.home.elmercado-child-theme .emo-category-card::after,
			body.home.elmercado-child-theme .emo-category-card__content::after { display:none !important; content:none !important; }
			body.home.elmercado-child-theme .emo-category-card > .emo-category-card__content { display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; gap:10px !important; padding-top:12px !important; padding-bottom:12px !important; }
			body.home.elmercado-child-theme .emo-category-card__content small { margin:0 !important; white-space:nowrap !important; text-align:right !important; }

			/* Nada pendiente aparece antes del montaje final. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-catalog-filter-pending { display:none !important; }
			html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > :is(#emo-category-context,#emo-global-vendor-filter,#emo-category-attribute-filters).emo-catalog-filter-mounted { display:block !important; visibility:visible !important; opacity:1 !important; }

			/* Etiquetas no forman parte del catálogo: se ocultan desde la primera pintura. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > :is(.widget_product_tag_cloud,.widget_tag_cloud,.wc-block-product-tag-cloud,.wp-block-woocommerce-product-tag-cloud,.wp-block-tag-cloud),
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > :is(.widget,.widget_block):has(.tagcloud),
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_product_tag_cloud,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_tag_cloud { display:none !important; visibility:hidden !important; }

			@media (min-width:1101px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) { box-sizing:border-box !important; width:250px !important; min-width:250px !important; max-width:250px !important; padding:18px !important; border:1px solid rgba(23,63,50,.11) !important; border-radius:18px !important; background:#fff !important; box-shadow:0 12px 32px rgba(17,42,34,.07) !important; }
			}

			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > :is(.widget_product_categories,.wc-block-product-categories,.wp-block-woocommerce-product-categories),
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > .widget_block:has(:is(.wc-block-product-categories,.wp-block-woocommerce-product-categories)) { display:none !important; visibility:hidden !important; }

			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > :is(.widget,.widget_block,[class*="wp-block-woocommerce-"]) { display:none !important; visibility:hidden !important; }
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > :is(.widget_price_filter,.widget_product_categories,#emo-global-vendor-filter,.wc-block-price-filter,.wp-block-woocommerce-price-filter,.wc-block-product-categories,.wp-block-woocommerce-product-categories),
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > .widget_block:has(:is(.wc-block-price-filter,.wp-block-woocommerce-price-filter,.wc-block-product-categories,.wp-block-woocommerce-product-categories)) { display:block !important; visibility:visible !important; }

			body.elmercado-child-theme.tax-product_cat #emo-category-context { box-sizing:border-box !important; width:100% !important; margin:0 0 14px !important; padding:0 !important; border:0 !important; background:transparent !important; box-shadow:none !important; }
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__row { display:flex !important; align-items:center !important; justify-content:space-between !important; gap:10px !important; min-height:38px !important; padding:8px 10px !important; border:1px solid rgba(23,63,50,.10) !important; border-radius:10px !important; background:#f3f7f4 !important; box-shadow:none !important; }
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__name { min-width:0 !important; color:#173f32 !important; font-size:13px !important; font-weight:750 !important; line-height:1.25 !important; }
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove { display:inline-flex !important; align-items:center !important; gap:3px !important; padding:3px 2px !important; border:0 !important; background:transparent !important; color:#687b72 !important; font-size:10.5px !important; font-weight:700 !important; line-height:1 !important; text-decoration:none !important; }

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > :is(.widget_price_filter,.widget_product_categories,#emo-global-vendor-filter,#emo-category-attribute-filters) { box-sizing:border-box !important; width:100% !important; margin:0 0 12px !important; padding:0 !important; border:0 !important; border-bottom:0 !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important; }
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-group { margin:0 0 5px !important; padding:7px 0 8px !important; border:0 !important; border-bottom:0 !important; background:transparent !important; box-shadow:none !important; }
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters :is(.emo-category-attribute-filters__head,.emo-category-filter-actions,.emo-category-attribute-filters__clear,.emo-category-filter-actions__clear) { display:none !important; }

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(.widget_price_filter > .widget-title,.widget_price_filter > .widgettitle,.widget_product_categories > .widget-title,.widget_product_categories > .widgettitle,#emo-global-vendor-filter > .emo-global-vendor-filter__title,#emo-category-attribute-filters .emo-category-filter-title) { display:grid !important; grid-template-columns:max-content minmax(24px,1fr) !important; align-items:center !important; column-gap:10px !important; width:100% !important; min-height:0 !important; margin:0 0 8px !important; padding:1px 1px 7px !important; border:0 !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important; color:#173f32 !important; font-size:10.5px !important; font-weight:800 !important; letter-spacing:.085em !important; line-height:1.25 !important; text-align:left !important; text-transform:uppercase !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(.widget_price_filter > .widget-title,.widget_price_filter > .widgettitle,.widget_product_categories > .widget-title,.widget_product_categories > .widgettitle,#emo-global-vendor-filter > .emo-global-vendor-filter__title,#emo-category-attribute-filters .emo-category-filter-title)::after { content:"" !important; display:block !important; width:100% !important; height:1px !important; background:rgba(23,63,50,.16) !important; }

			/* Filtro de precio: recuperación exacta del acabado elegante anterior (0.10.89). */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter form,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter .price_slider_wrapper { margin:0 !important; padding:0 !important; font-family:inherit !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter .price_slider { position:relative !important; height:4px !important; min-height:4px !important; margin:12px 9px 20px !important; padding:0 !important; border:0 !important; border-radius:999px !important; background:#dfe9e3 !important; box-shadow:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter .price_slider .ui-slider-range { top:0 !important; height:4px !important; border:0 !important; border-radius:999px !important; background:#2f7d5d !important; box-shadow:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter .price_slider .ui-slider-handle { top:50% !important; width:18px !important; height:18px !important; min-width:18px !important; min-height:18px !important; margin-top:-9px !important; margin-left:-9px !important; padding:0 !important; border:3px solid #2f7d5d !important; border-radius:50% !important; background:#fff !important; box-shadow:0 1px 5px rgba(17,42,34,.12) !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter .price_slider_amount { display:flex !important; align-items:center !important; justify-content:space-between !important; gap:10px !important; width:100% !important; min-height:40px !important; margin:0 !important; padding:0 !important; font-family:inherit !important; text-align:left !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter .price_slider_amount::before,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter .price_slider_amount::after { display:none !important; content:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter .price_slider_amount .button { flex:0 0 auto !important; min-height:38px !important; margin:0 !important; padding:0 14px !important; border-radius:999px !important; font-family:inherit !important; font-size:12px !important; font-weight:750 !important; line-height:1 !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) .widget_price_filter .price_slider_amount .price_label { min-width:0 !important; margin:0 0 0 auto !important; color:#42564e !important; font-family:inherit !important; font-size:11.5px !important; font-weight:700 !important; line-height:1.25 !important; letter-spacing:0 !important; text-align:right !important; text-transform:none !important; white-space:nowrap !important; }

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(.widget_product_categories > ul,.widget_product_categories ul.product-categories,#emo-global-vendor-filter .emo-global-vendor-filter__list,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list) { display:grid !important; gap:3px !important; margin:0 !important; padding:0 !important; list-style:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(.widget_product_categories li,#emo-global-vendor-filter .emo-global-vendor-filter__item,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item) { display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; column-gap:8px !important; box-sizing:border-box !important; min-height:32px !important; margin:0 !important; padding:1px 4px !important; border:0 !important; border-radius:8px !important; background:transparent !important; box-shadow:none !important; list-style:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(.widget_product_categories li > a,#emo-global-vendor-filter .emo-global-vendor-filter__item > a,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a) { display:block !important; min-width:0 !important; min-height:0 !important; margin:0 !important; padding:6px 4px !important; border:0 !important; background:transparent !important; color:#42584f !important; font-size:12px !important; font-weight:650 !important; line-height:1.3 !important; text-align:left !important; text-decoration:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(.widget_product_categories li:hover,.widget_product_categories .current-cat,#emo-global-vendor-filter .emo-global-vendor-filter__item:hover,#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:hover,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen) { background:#d9ede0 !important; box-shadow:inset 0 0 0 1px rgba(47,125,93,.18) !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(.widget_product_categories li:hover > a,.widget_product_categories .current-cat > a,#emo-global-vendor-filter .emo-global-vendor-filter__item:hover > a,#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active > a,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:hover > a,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen > a,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen > a) { color:#155b42 !important; font-weight:650 !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(.widget_product_categories .count,#emo-global-vendor-filter .count,#emo-category-attribute-filters .count) { display:inline-flex !important; align-items:center !important; justify-content:flex-end !important; min-width:22px !important; margin:0 1px 0 auto !important; padding:0 !important; border:0 !important; background:transparent !important; color:#809088 !important; font-size:10.5px !important; font-weight:650 !important; line-height:1 !important; text-align:right !important; white-space:nowrap !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item::before,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item::after,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a::before,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a::after,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(.widget_product_categories li > a,#emo-global-vendor-filter .emo-global-vendor-filter__item > a)::after { display:none !important; content:none !important; }

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] { box-sizing:border-box !important; width:100% !important; margin:0 0 14px !important; padding:0 !important; border:0 !important; background:transparent !important; box-shadow:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips__head { display:flex !important; align-items:center !important; justify-content:space-between !important; gap:8px !important; margin:0 0 8px !important; padding:0 1px !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips__head strong { color:#173f32 !important; font-size:11px !important; font-weight:800 !important; letter-spacing:.04em !important; text-transform:uppercase !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips__clear { display:inline-flex !important; align-items:center !important; justify-content:center !important; min-height:28px !important; padding:6px 10px !important; border:1px solid #173f32 !important; border-radius:999px !important; background:#173f32 !important; color:#fff !important; font-size:10.5px !important; font-weight:800 !important; line-height:1 !important; text-decoration:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips__list { display:flex !important; flex-wrap:wrap !important; gap:6px !important; margin:0 !important; padding:0 !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chip { display:inline-flex !important; max-width:100% !important; align-items:center !important; gap:4px !important; min-height:27px !important; padding:5px 8px !important; border:1px solid rgba(47,125,93,.18) !important; border-radius:999px !important; background:#edf4ef !important; color:#294c3f !important; font-size:10.5px !important; font-weight:650 !important; line-height:1.15 !important; text-decoration:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chip__group { color:#71827a !important; font-size:9.5px !important; font-weight:700 !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chip__group::after { content:"·" !important; margin-left:4px !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chip__name { min-width:0 !important; overflow:hidden !important; text-overflow:ellipsis !important; white-space:nowrap !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chip__remove { flex:0 0 auto !important; margin-left:1px !important; color:#496158 !important; font-size:14px !important; font-weight:500 !important; line-height:.8 !important; }
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
		<script id="elmercado-catalog-filter-controller-final-010207">
		(() => {
			'use strict';
			const sidebar = document.querySelector('.emo-mobile-filter-content #secondary.widget-area,.emo-mobile-filter-content .shop-widget-area,.emo-mobile-filter-content .widget-area,#secondary.widget-area,.shop-widget-area,.content-area + .widget-area,.site-content .widget-area');
			if (!sidebar) return;
			const directWidget = (selectors) => Array.from(sidebar.children).find((child) => selectors.some((selector) => child.matches?.(selector) || child.querySelector?.(selector))) || null;
			const context = document.getElementById('emo-category-context');
			const vendor = document.getElementById('emo-global-vendor-filter');
			const specific = document.getElementById('emo-category-attribute-filters');
			const template = document.getElementById('emo-active-filter-chips-template');
			let active = sidebar.querySelector(':scope > .emo-active-filter-chips[data-emo-global-active-filters="true"]');
			if (!active && template) active = template.content.firstElementChild?.cloneNode(true) || null;
			[context,vendor,specific].filter(Boolean).forEach((node) => {
				if (node.parentElement !== sidebar) sidebar.append(node);
				node.classList.remove('emo-catalog-filter-pending');
				node.classList.add('emo-catalog-filter-mounted');
				node.removeAttribute('hidden');
				node.removeAttribute('aria-hidden');
			});
			if (active && active.parentElement !== sidebar) sidebar.append(active);
			sidebar.querySelectorAll(':scope > .widget_product_tag_cloud,:scope > .widget_tag_cloud,:scope > .widget:has(.tagcloud),:scope > .widget_block:has(.tagcloud)').forEach((node) => node.remove());
			document.querySelectorAll('.widget_product_categories .count,#emo-category-attribute-filters .count').forEach((badge) => {
				const clean = (badge.textContent || '').replace(/[()]/g, '').trim();
				if (clean) badge.textContent = clean;
			});
			const price = directWidget(['.widget_price_filter','.wc-block-price-filter','.wp-block-woocommerce-price-filter']);
			const categories = directWidget(['.widget_product_categories','.wc-block-product-categories','.wp-block-woocommerce-product-categories']);
			const isCategory = document.body.classList.contains('tax-product_cat');
			const desired = (isCategory ? [context,active,price,vendor,specific] : [active,price,categories,vendor]).filter(Boolean);
			desired.slice().reverse().forEach((node) => sidebar.prepend(node));
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
