<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Segunda barrera de seguridad de stock para productos gestionados por EMDO.
 *
 * Los importadores ya sincronizan el stock de WooCommerce. Esta clase evita que
 * un producto pueda comprarse si la última lectura del proveedor está marcada
 * explícitamente como agotada, incluso si el estado interno de WooCommerce
 * hubiera quedado temporalmente desfasado.
 */
final class MDO_Stock_Guard {
	private static array $source_status_cache = array();

	public static function init(): void {
		add_filter( 'woocommerce_product_get_stock_status', array( __CLASS__, 'filter_stock_status' ), 20, 2 );
		add_filter( 'woocommerce_product_variation_get_stock_status', array( __CLASS__, 'filter_stock_status' ), 20, 2 );
		add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'filter_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( __CLASS__, 'filter_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_product_backorders_allowed', array( __CLASS__, 'filter_backorders' ), 20, 3 );
	}

	public static function filter_stock_status( string $stock_status, $product ): string {
		return self::is_source_out_of_stock( $product ) ? 'outofstock' : $stock_status;
	}

	public static function filter_purchasable( bool $purchasable, $product ): bool {
		if ( self::is_source_out_of_stock( $product ) ) {
			return false;
		}
		return $purchasable;
	}

	public static function filter_backorders( bool $allowed, int $product_id, $product ): bool {
		if ( self::is_source_out_of_stock( $product ) ) {
			return false;
		}
		return $allowed;
	}

	private static function is_source_out_of_stock( $product ): bool {
		return 'outofstock' === self::source_stock_status( $product );
	}

	private static function source_stock_status( $product ): ?string {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return null;
		}

		$product_id = (int) $product->get_id();
		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) && method_exists( $product, 'get_parent_id' ) ) {
			$product_id = (int) $product->get_parent_id();
		}
		if ( $product_id <= 0 ) {
			return null;
		}

		if ( array_key_exists( $product_id, self::$source_status_cache ) ) {
			return self::$source_status_cache[ $product_id ];
		}

		$source_product_id = absint( get_post_meta( $product_id, '_emdo_source_product_id', true ) );
		if ( ! $source_product_id ) {
			self::$source_status_cache[ $product_id ] = null;
			return null;
		}

		global $wpdb;
		$table  = MDO_Database::table( 'source_products' );
		$status = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT source_stock_status FROM {$table} WHERE id = %d LIMIT 1",
				$source_product_id
			)
		);

		$status = is_string( $status ) ? sanitize_key( $status ) : null;
		self::$source_status_cache[ $product_id ] = $status;
		return $status;
	}
}
