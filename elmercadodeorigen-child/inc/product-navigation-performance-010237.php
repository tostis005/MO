<?php
/**
 * Optimiza la navegación anterior/siguiente de Woostify en fichas de producto.
 *
 * Woostify 2.0.6 recorre candidatos uno a uno y carga cada WC_Product completo
 * hasta encontrar uno visible. En un marketplace esto puede multiplicar las
 * consultas cuando hay vendedores desactivados o productos ocultos. Esta capa
 * descarta esos candidatos directamente en la consulta SQL de WordPress, sin
 * modificar el HTML ni el comportamiento visible de las flechas.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Añade exclusiones baratas a la consulta de posts adyacentes de productos.
 */
function elmercado_product_navigation_adjacent_where_010237( string $where ): string {
	global $wpdb;

	/* No intervenir en navegación de entradas/páginas ni en la vista completa del admin. */
	if ( false === strpos( $where, "p.post_type = 'product'" ) ) {
		return $where;
	}

	if (
		function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' )
		&& elmercado_wcfm_disabled_visibility_can_view_010210()
	) {
		return $where;
	}

	$clauses = array();

	/* Evita que Woostify cargue productos de vendedores que igualmente serán invisibles. */
	if ( function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' ) ) {
		$disabled_vendors = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', elmercado_wcfm_disabled_vendor_ids_010210() )
				)
			)
		);

		if ( ! empty( $disabled_vendors ) ) {
			$clauses[] = 'p.post_author NOT IN (' . implode( ',', $disabled_vendors ) . ')';
		}
	}

	/*
	 * WooCommerce marcaría estos productos como no visibles después de cargar el
	 * objeto completo. Excluirlos aquí evita ese trabajo y conserva la semántica
	 * del catálogo. Si la tienda muestra agotados, no se excluyen.
	 */
	if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		$visibility = wc_get_product_visibility_term_ids();
		$excluded   = array_filter(
			array(
				absint( $visibility['exclude-from-catalog'] ?? 0 ),
				'yes' === get_option( 'woocommerce_hide_out_of_stock_items' )
					? absint( $visibility['outofstock'] ?? 0 )
					: 0,
			)
		);

		if ( ! empty( $excluded ) ) {
			$clauses[] = sprintf(
				'NOT EXISTS (SELECT 1 FROM %1$s AS emdo_nav_tr WHERE emdo_nav_tr.object_id = p.ID AND emdo_nav_tr.term_taxonomy_id IN (%2$s))',
				$wpdb->term_relationships,
				implode( ',', array_map( 'absint', $excluded ) )
			);
		}
	}

	if ( empty( $clauses ) ) {
		return $where;
	}

	return $where . ' AND ' . implode( ' AND ', $clauses );
}

add_filter( 'get_previous_post_where', 'elmercado_product_navigation_adjacent_where_010237', 20 );
add_filter( 'get_next_post_where', 'elmercado_product_navigation_adjacent_where_010237', 20 );
