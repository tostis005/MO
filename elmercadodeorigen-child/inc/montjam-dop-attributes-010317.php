<?php
/**
 * One-time repair of WooCommerce D.O.P. attributes on the two Montjam Jabugo products.
 * Does not modify pricing, shipping, variations, descriptions or Mentta integration.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks the exact two products and corrects only their D.O.P. taxonomy attributes.
 *
 * @return true|WP_Error True when both products have been verified and repaired.
 */
function elmercado_repair_montjam_dop_attributes_010317() {
	if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
		return new WP_Error( 'montjam_no_woocommerce', 'WooCommerce no está disponible.' );
	}

	$taxonomies = array(
		'pa_con-dop' => array( 'name' => 'con-dop', 'slug' => 'si' ),
		'pa_dop'     => array( 'name' => 'dop', 'slug' => 'jabugo' ),
	);
	foreach ( $taxonomies as $taxonomy => $definition ) {
		if ( ! taxonomy_exists( $taxonomy ) || ! wc_attribute_taxonomy_id_by_name( $definition['name'] ) ) {
			return new WP_Error( 'montjam_missing_attribute', 'No existe el atributo global ' . $taxonomy . '.' );
		}
	}

	$slugs = array(
		'jamon-bellota-100-iberico-dop-jabugo-montjam',
		'paleta-bellota-100-iberica-dop-jabugo-montjam',
	);
	$products = array();
	// Preflight both records before making any change.
	foreach ( $slugs as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
			return new WP_Error( 'montjam_product_not_found', 'No se ha encontrado el producto publicado: ' . $slug );
		}
		$product = wc_get_product( $post->ID );
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return new WP_Error( 'montjam_unexpected_product_type', 'Tipo de producto inesperado: ' . $slug );
		}
		$name = strtolower( remove_accents( $product->get_name() ) );
		if ( false === strpos( $name, 'montjam' ) || false === strpos( $name, 'jabugo' ) || false === strpos( $name, 'd.o.p.' ) ) {
			return new WP_Error( 'montjam_unexpected_product_name', 'Nombre de producto no verificado: ' . $slug );
		}
		$vendor_name = function_exists( 'elmercado_core_filter_vendor_name' )
			? elmercado_core_filter_vendor_name( (int) $post->post_author ) : '';
		if ( 'montjam' !== sanitize_title( $vendor_name ) ) {
			return new WP_Error( 'montjam_vendor_mismatch', 'El vendedor no es Montjam: ' . $slug );
		}
		$attributes = $product->get_attributes();
		foreach ( array_keys( $taxonomies ) as $taxonomy ) {
			if ( isset( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] instanceof WC_Product_Attribute && $attributes[ $taxonomy ]->get_variation() ) {
				return new WP_Error( 'montjam_variation_attribute', 'El atributo D.O.P. se usa como variación: ' . $taxonomy );
			}
		}
		$products[] = $product;
	}

	$terms = array();
	foreach ( $taxonomies as $taxonomy => $definition ) {
		$term = get_term_by( 'slug', $definition['slug'], $taxonomy );
		if ( ! $term && 'pa_dop' === $taxonomy ) {
			$created = wp_insert_term( 'Jabugo', $taxonomy, array( 'slug' => 'jabugo' ) );
			if ( is_wp_error( $created ) ) {
				return $created;
			}
			$term = get_term( (int) $created['term_id'], $taxonomy );
		}
		if ( ! $term instanceof WP_Term ) {
			return new WP_Error( 'montjam_missing_term', 'No existe el valor requerido en ' . $taxonomy . '.' );
		}
		$terms[ $taxonomy ] = (int) $term->term_id;
	}

	foreach ( $products as $product ) {
		$attributes = $product->get_attributes();
		foreach ( $taxonomies as $taxonomy => $definition ) {
			$attribute = isset( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] instanceof WC_Product_Attribute
				? clone $attributes[ $taxonomy ] : new WC_Product_Attribute();
			$attribute->set_id( (int) wc_attribute_taxonomy_id_by_name( $definition['name'] ) );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( array( $terms[ $taxonomy ] ) );
			$attribute->set_visible( true );
			$attribute->set_variation( false );
			if ( ! isset( $attributes[ $taxonomy ] ) ) {
				$attribute->set_position( count( $attributes ) );
			}
			$attributes[ $taxonomy ] = $attribute;
		}
		$product->set_attributes( $attributes );
		$product->save();
		// Verify the terms used by layered navigation, not only the product meta.
		foreach ( $terms as $taxonomy => $term_id ) {
			$assigned = wp_get_object_terms( $product->get_id(), $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $assigned ) || array_map( 'intval', (array) $assigned ) !== array( $term_id ) ) {
				return new WP_Error( 'montjam_repair_not_verified', 'La taxonomía no se ha guardado: ' . $taxonomy . ' en producto ' . $product->get_id() );
			}
		}
		wc_delete_product_transients( $product->get_id() );
	}

	return true;
}

// Repair only on the first admin visit: never on shop, orders, API or Mentta feed.
add_action(
	'admin_init',
	static function (): void {
		if ( ! current_user_can( 'manage_options' ) || get_option( 'elmercado_montjam_dop_repaired_010317', false ) ) {
			return;
		}
		$result = elmercado_repair_montjam_dop_attributes_010317();
		if ( is_wp_error( $result ) ) {
			error_log( '[EMDO] Montjam DOP repair pending: ' . $result->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}
		update_option( 'elmercado_montjam_dop_repaired_010317', gmdate( 'c' ), false );
	},
	30
);
