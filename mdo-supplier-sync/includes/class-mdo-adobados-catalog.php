<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normaliza la familia Adobados y mantiene el chorizo para asar en Embutidos y curados.
 */
final class MDO_Adobados_Catalog {
	private const VERSION        = '1.0.0';
	private const VERSION_OPTION = 'mdo_adobados_catalog_version';
	private const REPORT_OPTION  = 'mdo_adobados_catalog_last_report';
	private const SNAPSHOT_META  = '_emdo_adobados_catalog_snapshot';

	private const ADOBADOS_CATEGORY_NAME = 'Adobados';
	private const ADOBADOS_CATEGORY_SLUG = 'adobados';
	private const CURED_CATEGORY_NAME    = 'Embutidos y curados';
	private const CURED_CATEGORY_SLUG    = 'embutidos-y-curados';
	private const ATTRIBUTE_SLUG         = 'tipo-producto';
	private const ATTRIBUTE_LABEL        = 'Tipo de producto';

	private const TYPE_TERMS = array( 'Costilla', 'Lomo', 'Panceta', 'Chorizo' );

	private static bool $writing = false;
	private static bool $running = false;
	private static array $queue = array();
	private static int $adobados_category_id = 0;
	private static int $cured_category_id = 0;
	private static int $attribute_id = 0;

	public static function init(): void {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'queue_product' ), 99, 2 );
		add_action( 'shutdown', array( __CLASS__, 'flush_queue' ), PHP_INT_MAX );
	}

	public static function queue_product( $product, $data_store = null ): void {
		unset( $data_store );
		if ( self::$writing || self::$running || ! $product instanceof WC_Product ) {
			return;
		}

		$id = $product->is_type( 'variation' ) ? (int) $product->get_parent_id() : (int) $product->get_id();
		if ( $id > 0 ) {
			self::$queue[ $id ] = true;
		}
	}

	public static function flush_queue(): void {
		if ( self::$writing || self::$running || ! self::$queue ) {
			return;
		}

		$ids = array_keys( self::$queue );
		self::$queue = array();
		foreach ( $ids as $id ) {
			try {
				self::classify_product( (int) $id );
			} catch ( Throwable $error ) {
				error_log( '[EMDO adobados catalog] Producto ' . (int) $id . ': ' . $error->getMessage() );
			}
		}
	}

	public static function migrate_catalog( bool $force = false ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			throw new RuntimeException( 'WooCommerce no está disponible.' );
		}

		if ( ! $force && self::VERSION === (string) get_option( self::VERSION_OPTION, '' ) ) {
			$old = get_option( self::REPORT_OPTION, array() );
			return is_array( $old ) ? array_merge( $old, array( 'status' => 'up-to-date' ) ) : array( 'status' => 'up-to-date' );
		}

		self::$running = true;
		try {
			self::ensure_structures();
			$ids = wc_get_products(
				array(
					'limit'  => -1,
					'return' => 'ids',
					'status' => array( 'publish', 'private', 'draft', 'pending' ),
				)
			);

			$report = array(
				'status'          => 'completed',
				'version'         => self::VERSION,
				'scanned'         => 0,
				'adobados'        => 0,
				'costilla'        => 0,
				'lomo'            => 0,
				'panceta'         => 0,
				'chorizo_asar'    => 0,
				'updated'         => 0,
				'errors'          => array(),
				'finished_at'     => current_time( 'mysql' ),
			);

			foreach ( array_map( 'intval', $ids ) as $id ) {
				++$report['scanned'];
				try {
					$result = self::classify_product( $id );
					if ( empty( $result['target'] ) ) {
						continue;
					}
					++$report['updated'];
					if ( ! empty( $result['adobado_type'] ) ) {
						++$report['adobados'];
						$key = strtolower( (string) $result['adobado_type'] );
						if ( isset( $report[ $key ] ) ) {
							++$report[ $key ];
						}
					}
					if ( ! empty( $result['chorizo_asar'] ) ) {
						++$report['chorizo_asar'];
					}
				} catch ( Throwable $error ) {
					if ( count( $report['errors'] ) < 25 ) {
						$report['errors'][] = array( 'product_id' => $id, 'message' => $error->getMessage() );
					}
				}
			}

			update_option( self::VERSION_OPTION, self::VERSION, false );
			update_option( self::REPORT_OPTION, $report, false );
			return $report;
		} finally {
			self::$running = false;
			self::$queue = array();
		}
	}

	public static function classify_product( int $product_id ): array {
		$product = $product_id > 0 ? wc_get_product( $product_id ) : false;
		if ( ! $product instanceof WC_Product ) {
			return array( 'target' => false );
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
			if ( ! $product instanceof WC_Product ) {
				return array( 'target' => false );
			}
		}

		$title = self::normalize( $product->get_name() );
		$adobado_type = self::detect_adobado_type( $title );
		$is_chorizo_asar = self::is_chorizo_para_asar( $title );
		if ( '' === $adobado_type && ! $is_chorizo_asar ) {
			return array( 'target' => false );
		}

		self::ensure_structures();
		$category_ids = array_values( array_unique( array_map( 'intval', $product->get_category_ids() ) ) );

		if ( '' !== $adobado_type ) {
			$category_ids = array_values( array_diff( $category_ids, array( self::$cured_category_id ) ) );
			$category_ids[] = self::$adobados_category_id;
		} elseif ( $is_chorizo_asar ) {
			$category_ids = array_values( array_diff( $category_ids, array( self::$adobados_category_id ) ) );
			$category_ids[] = self::$cured_category_id;
		}

		$uncategorized_id = (int) get_option( 'default_product_cat', 0 );
		if ( $uncategorized_id > 0 ) {
			$category_ids = array_values( array_diff( $category_ids, array( $uncategorized_id ) ) );
		}
		$category_ids = array_values( array_unique( array_filter( array_map( 'intval', $category_ids ) ) ) );

		self::$writing = true;
		try {
			$product->set_category_ids( $category_ids );
			if ( '' !== $adobado_type ) {
				self::apply_type_attribute( $product, $adobado_type );
			}
			$product->save();
			update_post_meta(
				$product->get_id(),
				self::SNAPSHOT_META,
				wp_json_encode(
					array(
						'adobado_type' => $adobado_type,
						'chorizo_asar' => $is_chorizo_asar,
					),
					JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
				)
			);
		} finally {
			self::$writing = false;
		}

		return array(
			'target'       => true,
			'adobado_type' => $adobado_type,
			'chorizo_asar' => $is_chorizo_asar,
		);
	}

	private static function detect_adobado_type( string $title ): string {
		if ( ! str_contains( $title, 'adobad' ) ) {
			return '';
		}
		if ( preg_match( '/\bcostilla(?:s)?\b/u', $title ) ) {
			return 'Costilla';
		}
		if ( preg_match( '/\blomo\b/u', $title ) ) {
			return 'Lomo';
		}
		if ( preg_match( '/\bpanceta\b/u', $title ) ) {
			return 'Panceta';
		}
		return '';
	}

	private static function is_chorizo_para_asar( string $title ): bool {
		return (bool) preg_match( '/\bchorizo(?:s)?\b.*\b(?:para\s+)?asar\b/u', $title );
	}

	private static function ensure_structures(): void {
		self::ensure_categories();
		self::ensure_attribute();
	}

	private static function ensure_categories(): void {
		if ( self::$adobados_category_id <= 0 ) {
			$term = get_term_by( 'slug', self::ADOBADOS_CATEGORY_SLUG, 'product_cat' );
			if ( ! $term ) {
				$created = wp_insert_term( self::ADOBADOS_CATEGORY_NAME, 'product_cat', array( 'slug' => self::ADOBADOS_CATEGORY_SLUG ) );
				if ( is_wp_error( $created ) ) {
					throw new RuntimeException( 'No se pudo crear Adobados: ' . $created->get_error_message() );
				}
				$term = get_term( (int) $created['term_id'], 'product_cat' );
			}
			self::$adobados_category_id = $term instanceof WP_Term ? (int) $term->term_id : 0;
		}

		if ( self::$cured_category_id <= 0 ) {
			$term = get_term_by( 'slug', self::CURED_CATEGORY_SLUG, 'product_cat' );
			if ( ! $term ) {
				$created = wp_insert_term( self::CURED_CATEGORY_NAME, 'product_cat', array( 'slug' => self::CURED_CATEGORY_SLUG ) );
				if ( is_wp_error( $created ) ) {
					throw new RuntimeException( 'No se pudo crear Embutidos y curados: ' . $created->get_error_message() );
				}
				$term = get_term( (int) $created['term_id'], 'product_cat' );
			}
			self::$cured_category_id = $term instanceof WP_Term ? (int) $term->term_id : 0;
		}
	}

	private static function ensure_attribute(): void {
		if ( self::$attribute_id <= 0 ) {
			self::$attribute_id = (int) wc_attribute_taxonomy_id_by_name( self::ATTRIBUTE_SLUG );
			if ( self::$attribute_id <= 0 ) {
				$created = wc_create_attribute(
					array(
						'name'         => self::ATTRIBUTE_LABEL,
						'slug'         => self::ATTRIBUTE_SLUG,
						'type'         => 'select',
						'order_by'     => 'menu_order',
						'has_archives' => false,
					)
				);
				if ( is_wp_error( $created ) ) {
					throw new RuntimeException( 'No se pudo crear Tipo de producto: ' . $created->get_error_message() );
				}
				self::$attribute_id = (int) $created;
				delete_transient( 'wc_attribute_taxonomies' );
			}
		}

		$taxonomy = wc_attribute_taxonomy_name( self::ATTRIBUTE_SLUG );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy(
				$taxonomy,
				apply_filters( "woocommerce_taxonomy_objects_{$taxonomy}", array( 'product' ) ),
				apply_filters(
					"woocommerce_taxonomy_args_{$taxonomy}",
					array(
						'hierarchical' => false,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					),
					$taxonomy
				)
			);
		}

		foreach ( self::TYPE_TERMS as $term_name ) {
			if ( ! term_exists( $term_name, $taxonomy ) ) {
				$created = wp_insert_term( $term_name, $taxonomy );
				if ( is_wp_error( $created ) ) {
					throw new RuntimeException( 'No se pudo crear el término ' . $term_name . ': ' . $created->get_error_message() );
				}
			}
		}
	}

	private static function apply_type_attribute( WC_Product $product, string $type ): void {
		$taxonomy = wc_attribute_taxonomy_name( self::ATTRIBUTE_SLUG );
		$term = get_term_by( 'name', $type, $taxonomy );
		if ( ! $term instanceof WP_Term ) {
			throw new RuntimeException( 'No existe el término de Tipo de producto: ' . $type );
		}

		$attributes = $product->get_attributes();
		$position = count( $attributes );
		if ( isset( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] instanceof WC_Product_Attribute ) {
			$position = (int) $attributes[ $taxonomy ]->get_position();
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( self::$attribute_id );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array( (int) $term->term_id ) );
		$attribute->set_position( $position );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$attributes[ $taxonomy ] = $attribute;
		$product->set_attributes( $attributes );
	}

	private static function normalize( string $value ): string {
		$value = remove_accents( wp_strip_all_tags( $value ) );
		$value = strtolower( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$value = preg_replace( '/[^a-z0-9]+/u', ' ', $value );
		return trim( preg_replace( '/\s+/u', ' ', (string) $value ) );
	}
}
