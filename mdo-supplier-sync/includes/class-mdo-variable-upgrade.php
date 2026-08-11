<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permite que un producto EMDO ya importado como simple sea tratado como
 * variable cuando una extracción posterior incorpora una matriz válida de
 * variaciones. WooCommerce persistirá el tipo correcto al guardar el producto.
 */
final class MDO_Variable_Upgrade {
	public static function init(): void {
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'product_class' ), 20, 4 );
	}

	public static function product_class( string $classname, string $product_type, string $post_type, int $product_id ): string {
		if ( $product_id <= 0 || 'product' !== $post_type || 'variable' === $product_type ) {
			return $classname;
		}
		$source_product_id = (int) get_post_meta( $product_id, '_emdo_source_product_id', true );
		if ( $source_product_id <= 0 || ! self::source_has_usable_variations( $source_product_id ) ) {
			return $classname;
		}
		return class_exists( 'WC_Product_Variable' ) ? WC_Product_Variable::class : $classname;
	}

	private static function source_has_usable_variations( int $source_product_id ): bool {
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$payload_json = $wpdb->get_var(
			$wpdb->prepare( "SELECT source_payload FROM {$table} WHERE id = %d LIMIT 1", $source_product_id )
		);
		$payload = json_decode( (string) $payload_json, true );
		if ( ! is_array( $payload ) || empty( $payload['variations'] ) || ! is_array( $payload['variations'] ) ) {
			return false;
		}
		foreach ( $payload['variations'] as $variation ) {
			if ( ! is_array( $variation ) ) {
				continue;
			}
			$price = $variation['display_price'] ?? null;
			$attributes = $variation['attributes'] ?? array();
			if ( is_numeric( $price ) && is_array( $attributes ) && ! empty( array_filter( array_map( 'strval', $attributes ) ) ) ) {
				return true;
			}
		}
		return false;
	}
}
