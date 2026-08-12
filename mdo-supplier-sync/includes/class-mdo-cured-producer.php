<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Garantiza el atributo global Productor en los productos de Embutidos y curados.
 *
 * El filtro visual usa pa_productor, igual que Jamones y paletas. Esta clase
 * mantiene ese atributo sincronizado con el autor/vendedor del producto y hace
 * una migración única de las referencias existentes.
 */
final class MDO_Cured_Producer {
	private const VERSION        = '1.0.0';
	private const VERSION_OPTION = 'mdo_cured_producer_version';
	private const CATEGORY_SLUG  = 'embutidos-y-curados';
	private const ATTRIBUTE_SLUG = 'productor';
	private const ATTRIBUTE_NAME = 'Productor';

	private static bool $writing = false;
	private static bool $running = false;
	private static int $attribute_id = 0;

	public static function init(): void {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'sync_after_save' ), 99, 2 );
		add_action( 'wp_loaded', array( __CLASS__, 'maybe_migrate' ), 99 );
	}

	public static function maybe_migrate(): void {
		if ( self::$running || self::$writing || ! function_exists( 'wc_get_products' ) ) {
			return;
		}
		if ( self::VERSION === (string) get_option( self::VERSION_OPTION, '' ) ) {
			return;
		}

		try {
			self::migrate_catalog( false );
		} catch ( Throwable $error ) {
			error_log( '[EMDO cured producer] ' . $error->getMessage() );
		}
	}

	public static function migrate_catalog( bool $force = false ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			throw new RuntimeException( 'WooCommerce no está disponible.' );
		}
		if ( ! $force && self::VERSION === (string) get_option( self::VERSION_OPTION, '' ) ) {
			return array( 'status' => 'up-to-date', 'version' => self::VERSION );
		}

		self::$running = true;
		try {
			self::ensure_attribute();
			$ids = wc_get_products(
				array(
					'limit'    => -1,
					'return'   => 'ids',
					'status'   => array( 'publish', 'private', 'draft', 'pending' ),
					'category' => array( self::CATEGORY_SLUG ),
				)
			);

			$report = array(
				'status'     => 'completed',
				'version'    => self::VERSION,
				'scanned'    => 0,
				'updated'    => 0,
				'skipped'    => 0,
				'producers'  => array(),
				'errors'     => array(),
				'finished_at'=> current_time( 'mysql' ),
			);

			foreach ( array_map( 'intval', $ids ) as $product_id ) {
				++$report['scanned'];
				try {
					$result = self::assign_product( $product_id );
					if ( empty( $result['updated'] ) ) {
						++$report['skipped'];
						continue;
					}
					++$report['updated'];
					$producer = (string) ( $result['producer'] ?? '' );
					if ( '' !== $producer ) {
						$report['producers'][ $producer ] = ( $report['producers'][ $producer ] ?? 0 ) + 1;
					}
				} catch ( Throwable $error ) {
					if ( count( $report['errors'] ) < 25 ) {
						$report['errors'][] = array( 'product_id' => $product_id, 'message' => $error->getMessage() );
					}
				}
			}

			ksort( $report['producers'], SORT_NATURAL | SORT_FLAG_CASE );
			update_option( self::VERSION_OPTION, self::VERSION, false );
			return $report;
		} finally {
			self::$running = false;
		}
	}

	public static function sync_after_save( $product, $data_store = null ): void {
		unset( $data_store );
		if ( self::$writing || self::$running || ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = $product->is_type( 'variation' ) ? (int) $product->get_parent_id() : (int) $product->get_id();
		if ( $product_id <= 0 || ! self::is_cured_product( $product_id ) ) {
			return;
		}

		try {
			self::assign_product( $product_id );
		} catch ( Throwable $error ) {
			error_log( '[EMDO cured producer] Producto ' . $product_id . ': ' . $error->getMessage() );
		}
	}

	private static function assign_product( int $product_id ): array {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product || ! self::is_cured_product( $product_id ) ) {
			return array( 'updated' => false );
		}

		self::ensure_attribute();
		$producer = self::producer_name( $product );
		if ( '' === $producer ) {
			return array( 'updated' => false );
		}

		$taxonomy = wc_attribute_taxonomy_name( self::ATTRIBUTE_SLUG );
		$term     = term_exists( $producer, $taxonomy );
		if ( is_array( $term ) ) {
			$term_id = (int) $term['term_id'];
		} elseif ( is_int( $term ) ) {
			$term_id = $term;
		} else {
			$created = wp_insert_term( $producer, $taxonomy );
			if ( is_wp_error( $created ) ) {
				throw new RuntimeException( 'No se pudo crear el productor «' . $producer . '»: ' . $created->get_error_message() );
			}
			$term_id = (int) $created['term_id'];
		}

		wp_set_object_terms( $product_id, array( $term_id ), $taxonomy, false );

		$attributes = $product->get_attributes();
		$position   = count( $attributes );
		if ( isset( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] instanceof WC_Product_Attribute ) {
			$position = (int) $attributes[ $taxonomy ]->get_position();
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( self::$attribute_id );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array( $term_id ) );
		$attribute->set_position( $position );
		$attribute->set_visible( false );
		$attribute->set_variation( false );
		$attributes[ $taxonomy ] = $attribute;
		$product->set_attributes( array_values( $attributes ) );

		self::$writing = true;
		try {
			$product->save();
		} finally {
			self::$writing = false;
		}

		return array( 'updated' => true, 'producer' => $producer );
	}

	private static function is_cured_product( int $product_id ): bool {
		$slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
		return ! is_wp_error( $slugs ) && in_array( self::CATEGORY_SLUG, (array) $slugs, true );
	}

	private static function ensure_attribute(): void {
		if ( self::$attribute_id > 0 ) {
			return;
		}

		foreach ( (array) wc_get_attribute_taxonomies() as $attribute ) {
			if ( self::ATTRIBUTE_SLUG === (string) $attribute->attribute_name ) {
				self::$attribute_id = (int) $attribute->attribute_id;
				break;
			}
		}

		if ( self::$attribute_id <= 0 ) {
			$created = wc_create_attribute(
				array(
					'name'         => self::ATTRIBUTE_NAME,
					'slug'         => self::ATTRIBUTE_SLUG,
					'type'         => 'select',
					'order_by'     => 'name',
					'has_archives' => false,
				)
			);
			if ( is_wp_error( $created ) ) {
				throw new RuntimeException( 'No se pudo crear el atributo Productor: ' . $created->get_error_message() );
			}
			self::$attribute_id = (int) $created;
			delete_transient( 'wc_attribute_taxonomies' );
			if ( class_exists( 'WC_Cache_Helper' ) ) {
				WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
			}
		}

		$taxonomy = wc_attribute_taxonomy_name( self::ATTRIBUTE_SLUG );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy(
				$taxonomy,
				array( 'product' ),
				array(
					'hierarchical' => false,
					'show_ui'      => false,
					'query_var'     => true,
					'rewrite'       => false,
				)
			);
		}
	}

	private static function producer_name( WC_Product $product ): string {
		$user_id = (int) get_post_field( 'post_author', $product->get_id() );
		$user    = $user_id > 0 ? get_userdata( $user_id ) : false;
		$name    = '';

		$settings = $user_id > 0 ? get_user_meta( $user_id, 'wcfmmp_profile_settings', true ) : array();
		if ( is_array( $settings ) && ! empty( $settings['store_name'] ) ) {
			$name = trim( (string) $settings['store_name'] );
		}
		if ( '' === $name && $user_id > 0 ) {
			$store_name = get_user_meta( $user_id, 'store_name', true );
			if ( is_string( $store_name ) ) {
				$name = trim( $store_name );
			}
		}
		if ( '' === $name && $user instanceof WP_User ) {
			$name = trim( (string) $user->display_name );
		}

		$normalized = self::normalize( $name );
		if ( str_contains( $normalized, 'puenterobles' ) || str_contains( $normalized, 'puente robles' ) ) {
			return 'Puente Robles';
		}
		if ( str_contains( $normalized, 'elcatedratico' ) || str_contains( $normalized, 'el catedratico' ) ) {
			return 'El Catedrático';
		}
		if ( str_contains( $normalized, 'hidalgo de la jara' ) ) {
			return 'Hidalgo de la Jara';
		}

		return $name;
	}

	private static function normalize( string $text ): string {
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = remove_accents( strtolower( $text ) );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( (string) $text );
	}
}
