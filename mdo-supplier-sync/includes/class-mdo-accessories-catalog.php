<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clasifica jamoneros y cuchillos independientes dentro de Accesorios y reutiliza
 * el atributo global Tipo de producto. Las menciones promocionales o de corte en
 * productos de jamón/paleta no se consideran accesorios.
 */
final class MDO_Accessories_Catalog {
	private const VERSION        = '1.0.3';
	private const VERSION_OPTION = 'mdo_accessories_catalog_version';
	private const REPORT_OPTION  = 'mdo_accessories_catalog_last_report';
	private const SNAPSHOT_META  = '_emdo_accessories_catalog_snapshot';

	private const CATEGORY_NAME  = 'Accesorios';
	private const CATEGORY_SLUG  = 'accesorios';
	private const ATTRIBUTE_SLUG = 'tipo-producto';
	private const ATTRIBUTE_LABEL = 'Tipo de producto';
	private const TYPE_TERMS      = array( 'Jamonero', 'Cuchillo' );

	private static bool $writing = false;
	private static bool $running = false;
	private static array $queue = array();
	private static int $category_id = 0;
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
				error_log( '[EMDO accessories catalog] Producto ' . (int) $id . ': ' . $error->getMessage() );
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
				'status'      => 'completed',
				'version'     => self::VERSION,
				'scanned'     => 0,
				'accessories' => 0,
				'jamoneros'   => 0,
				'cuchillos'   => 0,
				'cleaned'     => 0,
				'updated'     => 0,
				'errors'      => array(),
				'finished_at' => current_time( 'mysql' ),
			);

			foreach ( array_map( 'intval', $ids ) as $id ) {
				++$report['scanned'];
				try {
					$result = self::classify_product( $id );
					if ( ! empty( $result['cleaned'] ) ) {
						++$report['cleaned'];
						++$report['updated'];
					}
					if ( empty( $result['target'] ) ) {
						continue;
					}
					++$report['accessories'];
					++$report['updated'];
					if ( 'Jamonero' === $result['type'] ) {
						++$report['jamoneros'];
					} elseif ( 'Cuchillo' === $result['type'] ) {
						++$report['cuchillos'];
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
			return array( 'target' => false, 'cleaned' => false );
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
			if ( ! $product instanceof WC_Product ) {
				return array( 'target' => false, 'cleaned' => false );
			}
		}

		$type = self::detect_type( self::normalize( $product->get_name() ) );
		if ( '' === $type ) {
			return array(
				'target'  => false,
				'cleaned' => self::cleanup_managed_product( $product ),
			);
		}

		self::ensure_structures();
		$category_ids = array_values( array_unique( array_map( 'intval', $product->get_category_ids() ) ) );
		$category_ids[] = self::$category_id;

		$uncategorized_id = (int) get_option( 'default_product_cat', 0 );
		if ( $uncategorized_id > 0 ) {
			$category_ids = array_values( array_diff( $category_ids, array( $uncategorized_id ) ) );
		}
		$category_ids = array_values( array_unique( array_filter( array_map( 'intval', $category_ids ) ) ) );

		self::$writing = true;
		try {
			$product->set_category_ids( $category_ids );
			self::apply_type_attribute( $product, $type );
			$product->save();
			update_post_meta(
				$product->get_id(),
				self::SNAPSHOT_META,
				wp_json_encode(
					array( 'type' => $type ),
					JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
				)
			);
		} finally {
			self::$writing = false;
		}

		return array( 'target' => true, 'cleaned' => false, 'type' => $type );
	}

	private static function detect_type( string $title ): string {
		// Los productos alimentarios cuyo título empieza por Jamón o Paleta pueden
		// mencionar “cortado a cuchillo” o regalos de jamonero/cuchillo; no son accesorios.
		if ( preg_match( '/^(?:jamon|paleta)\b/u', $title ) ) {
			return '';
		}

		// En los accesorios reales el término puede aparecer después de “blíster”,
		// “set”, “soporte”, etc. Cuchillo prevalece sobre jamonero (p. ej. cuchillo jamonero).
		if ( preg_match( '/\bcuchillo(?:s)?\b/u', $title ) ) {
			return 'Cuchillo';
		}
		if ( preg_match( '/\bjamonero(?:s)?\b/u', $title ) ) {
			return 'Jamonero';
		}
		return '';
	}

	private static function cleanup_managed_product( WC_Product $product ): bool {
		$snapshot = (string) get_post_meta( $product->get_id(), self::SNAPSHOT_META, true );
		if ( '' === $snapshot ) {
			return false;
		}

		$category_ids = array_values( array_unique( array_map( 'intval', $product->get_category_ids() ) ) );
		$category = get_term_by( 'slug', self::CATEGORY_SLUG, 'product_cat' );
		if ( $category instanceof WP_Term ) {
			$category_ids = array_values( array_diff( $category_ids, array( (int) $category->term_id ) ) );
		}

		$attributes = $product->get_attributes();
		$taxonomy = wc_attribute_taxonomy_name( self::ATTRIBUTE_SLUG );
		if ( isset( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] instanceof WC_Product_Attribute ) {
			$managed_term_ids = array();
			foreach ( self::TYPE_TERMS as $term_name ) {
				$term = get_term_by( 'name', $term_name, $taxonomy );
				if ( $term instanceof WP_Term ) {
					$managed_term_ids[] = (int) $term->term_id;
				}
			}
			$options = array_values( array_map( 'intval', $attributes[ $taxonomy ]->get_options() ) );
			if ( $options && ! array_diff( $options, $managed_term_ids ) ) {
				unset( $attributes[ $taxonomy ] );
			}
		}

		self::$writing = true;
		try {
			$product->set_category_ids( $category_ids );
			$product->set_attributes( $attributes );
			$product->save();
			delete_post_meta( $product->get_id(), self::SNAPSHOT_META );
		} finally {
			self::$writing = false;
		}
		return true;
	}

	private static function ensure_structures(): void {
		self::ensure_category();
		self::ensure_attribute();
	}

	private static function ensure_category(): void {
		if ( self::$category_id > 0 ) {
			return;
		}
		$term = get_term_by( 'slug', self::CATEGORY_SLUG, 'product_cat' );
		if ( ! $term ) {
			$created = wp_insert_term( self::CATEGORY_NAME, 'product_cat', array( 'slug' => self::CATEGORY_SLUG ) );
			if ( is_wp_error( $created ) ) {
				throw new RuntimeException( 'No se pudo crear Accesorios: ' . $created->get_error_message() );
			}
			$term = get_term( (int) $created['term_id'], 'product_cat' );
		}
		self::$category_id = $term instanceof WP_Term ? (int) $term->term_id : 0;
		if ( self::$category_id <= 0 ) {
			throw new RuntimeException( 'No se pudo resolver la categoría Accesorios.' );
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
		$value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = remove_accents( $value );
		$value = strtolower( $value );
		$value = preg_replace( '/[^a-z0-9]+/u', ' ', $value );
		return trim( preg_replace( '/\s+/u', ' ', (string) $value ) );
	}
}
