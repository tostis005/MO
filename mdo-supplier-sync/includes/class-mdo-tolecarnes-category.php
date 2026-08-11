<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps every Tole Carnes product inside the Carnes WooCommerce category.
 *
 * The migration is deliberately additive: existing product categories are kept.
 */
final class MDO_Tolecarnes_Category {
	private const CATEGORY_NAME = 'Carnes';
	private const MIGRATION_OPTION = 'mdo_tolecarnes_category_migration_v1';

	public static function run_once(): void {
		if ( get_option( self::MIGRATION_OPTION, false ) ) {
			return;
		}
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}

		global $wpdb;
		$suppliers_table = MDO_Database::table( 'suppliers' );
		$products_table  = MDO_Database::table( 'source_products' );
		$suppliers       = $wpdb->get_results(
			"SELECT id, vendor_user_id FROM {$suppliers_table} WHERE connector = 'tolecarnes'",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $suppliers ) {
			return;
		}

		$category_id = self::ensure_category_id();
		if ( ! $category_id ) {
			return;
		}

		$product_ids = array();
		foreach ( $suppliers as $supplier ) {
			$supplier_id = (int) $supplier['id'];
			$linked_ids  = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT wc_product_id FROM {$products_table} WHERE supplier_id = %d AND wc_product_id IS NOT NULL AND wc_product_id > 0",
					$supplier_id
				)
			); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( $linked_ids as $product_id ) {
				$product_ids[ (int) $product_id ] = (int) $product_id;
			}

			$vendor_user_id = (int) $supplier['vendor_user_id'];
			if ( $vendor_user_id > 0 ) {
				$vendor_product_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_author = %d AND post_status NOT IN ('trash', 'auto-draft')",
						$vendor_user_id
					)
				);
				foreach ( $vendor_product_ids as $product_id ) {
					$product_ids[ (int) $product_id ] = (int) $product_id;
				}
			}
		}

		$assigned = 0;
		foreach ( $product_ids as $product_id ) {
			if ( self::assign_product( $product_id, $category_id ) ) {
				$assigned++;
			}
		}

		update_option(
			self::MIGRATION_OPTION,
			array(
				'completed_at' => current_time( 'mysql' ),
				'category_id'  => $category_id,
				'products'     => $assigned,
			),
			false
		);
	}

	public static function assign_product( int $product_id, int $category_id = 0 ): bool {
		if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
			return false;
		}
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return false;
		}

		$category_id = $category_id ?: self::ensure_category_id();
		if ( ! $category_id ) {
			return false;
		}

		$result = wp_set_object_terms( $product_id, array( $category_id ), 'product_cat', true );
		return ! is_wp_error( $result );
	}

	private static function ensure_category_id(): int {
		$existing = term_exists( self::CATEGORY_NAME, 'product_cat' );
		if ( is_array( $existing ) && ! empty( $existing['term_id'] ) ) {
			return (int) $existing['term_id'];
		}
		if ( is_int( $existing ) && $existing > 0 ) {
			return $existing;
		}

		$created = wp_insert_term( self::CATEGORY_NAME, 'product_cat', array( 'slug' => 'carnes' ) );
		if ( is_wp_error( $created ) || empty( $created['term_id'] ) ) {
			return 0;
		}
		return (int) $created['term_id'];
	}
}
