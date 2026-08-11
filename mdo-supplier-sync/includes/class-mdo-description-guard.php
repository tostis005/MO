<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Garantiza que una importación EMDO no deje entidades HTML visibles aunque el
 * producto proceda de un payload analizado con una versión anterior del plugin.
 */
final class MDO_Description_Guard {
	private static bool $repairing = false;

	public static function init(): void {
		add_action( 'woocommerce_new_product', array( __CLASS__, 'repair_product' ), 30, 1 );
		add_action( 'woocommerce_update_product', array( __CLASS__, 'repair_product' ), 30, 1 );
		add_action( 'added_post_meta', array( __CLASS__, 'after_meta_added' ), 30, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'after_meta_updated' ), 30, 4 );
	}

	public static function after_meta_added( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( '_emdo_source_product_id' === $meta_key ) {
			self::repair_product( $object_id );
		}
	}

	public static function after_meta_updated( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( '_emdo_source_product_id' === $meta_key ) {
			self::repair_product( $object_id );
		}
	}

	public static function repair_product( int $product_id ): void {
		if ( self::$repairing || $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
			return;
		}

		// En actualizaciones solo actuamos sobre productos vinculados a EMDO.
		// En la primera creación el meta se añade después del save y este método
		// vuelve a ejecutarse desde added_post_meta.
		if ( ! metadata_exists( 'post', $product_id, '_emdo_source_product_id' ) ) {
			return;
		}

		$current = (string) get_post_field( 'post_content', $product_id, 'raw' );
		$clean   = MDO_Text::normalize_description( $current );
		if ( $clean === $current ) {
			return;
		}

		self::$repairing = true;
		wp_update_post(
			array(
				'ID'           => $product_id,
				'post_content' => $clean,
			)
		);
		self::$repairing = false;
	}
}
