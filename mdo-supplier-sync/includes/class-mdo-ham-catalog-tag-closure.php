<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cierra la consistencia entre preparación explícita y etiquetas de producto.
 *
 * Sólo actúa sobre productos que ya pasaron MDO_Ham_Catalog_Precision. No
 * recalcula clasificación ni toca raza, calidad, DOP, origen, peso o curación.
 */
final class MDO_Ham_Catalog_Tag_Closure {
	private const VERSION = '2026-08-12.5';
	private const OPTION  = 'mdo_ham_catalog_tag_closure_version';
	private static bool $writing = false;

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'maybe_apply_once' ), 99 );
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'sync_saved_product' ), 100, 2 );
	}

	public static function maybe_apply_once(): void {
		if ( self::VERSION === (string) get_option( self::OPTION, '' ) || ! function_exists( 'wc_get_products' ) ) {
			return;
		}

		$ids = wc_get_products(
			array(
				'limit'  => -1,
				'return' => 'ids',
				'status' => array( 'publish', 'private', 'draft', 'pending' ),
			)
		);

		foreach ( array_map( 'intval', $ids ) as $id ) {
			$product = wc_get_product( $id );
			if ( $product instanceof WC_Product && ! $product->is_type( 'variation' ) ) {
				self::sync_product( $product );
			}
		}

		update_option( self::OPTION, self::VERSION, false );
	}

	public static function sync_saved_product( $product, $data_store = null ): void {
		unset( $data_store );
		if ( self::$writing || ! $product instanceof WC_Product ) {
			return;
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
		}
		if ( $product instanceof WC_Product ) {
			self::sync_product( $product );
		}
	}

	private static function sync_product( WC_Product $product ): void {
		$id = (int) $product->get_id();
		if ( ! get_post_meta( $id, '_emdo_ham_precision_version', true ) || ! taxonomy_exists( 'pa_preparacion' ) ) {
			return;
		}

		$preparations = wc_get_product_terms( $id, 'pa_preparacion', array( 'fields' => 'names' ) );
		if ( is_wp_error( $preparations ) || ! $preparations ) {
			return;
		}

		$tags = array_values(
			array_filter(
				array_map( 'strval', (array) $preparations ),
				static fn( string $value ): bool => '' !== $value && 'Pieza entera' !== $value
			)
		);
		if ( ! $tags ) {
			return;
		}

		self::$writing = true;
		try {
			wp_set_object_terms( $id, array_values( array_unique( $tags ) ), 'product_tag', true );
			update_post_meta( $id, '_emdo_ham_tag_closure_version', self::VERSION );
		} finally {
			self::$writing = false;
		}
	}
}
