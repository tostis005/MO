<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normaliza Embutidos y curados y Packs y lotes en WooCommerce.
 * Reutiliza los atributos globales de ibérico creados para Jamones y Paletas.
 */
final class MDO_Cured_Catalog {
	private const VERSION        = '1.0.1';
	private const VERSION_OPTION = 'mdo_cured_catalog_version';
	private const REPORT_OPTION  = 'mdo_cured_catalog_last_report';
	private const SNAPSHOT_META  = '_emdo_cured_catalog_snapshot';

	private const CURED_CATEGORY_NAME = 'Embutidos y curados';
	private const CURED_CATEGORY_SLUG = 'embutidos-y-curados';
	private const PACKS_CATEGORY_NAME = 'Packs y lotes';
	private const PACKS_CATEGORY_SLUG = 'packs-y-lotes';

	private const ATTRIBUTES = array(
		'tipo-producto' => array(
			'label' => 'Tipo de producto',
			'terms' => array( 'Chorizo', 'Salchichón', 'Lomo', 'Lomito', 'Morcón', 'Sobrasada', 'Cecina' ),
		),
		'raza-iberica' => array(
			'label' => 'Raza ibérica',
			'terms' => array( '100% ibérico', '75% ibérico', '50% ibérico' ),
		),
		'alimentacion' => array(
			'label' => 'Alimentación',
			'terms' => array( 'Bellota', 'Cebo de campo', 'Cebo' ),
		),
		'preparacion' => array(
			'label' => 'Preparación',
			'terms' => array( 'Pieza entera', 'Media pieza', 'Loncheado' ),
		),
	);

	private const SPECIAL_BUNDLES = array(
		'catedra iberica',
		'el catedratico gourmet',
		'el catedratico seleccion',
		'la catedra del sabor',
		'la caja del estudiante',
	);

	private static bool $writing = false;
	private static bool $running = false;
	private static array $queue = array();
	private static array $attribute_ids = array();
	private static int $cured_category_id = 0;
	private static int $packs_category_id = 0;

	public static function init(): void {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'queue_product' ), 98, 2 );
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
				error_log( '[EMDO cured catalog] Producto ' . (int) $id . ': ' . $error->getMessage() );
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
			$ids = wc_get_products( array(
				'limit'  => -1,
				'return' => 'ids',
				'status' => array( 'publish', 'private', 'draft', 'pending' ),
			) );

			$report = array(
				'status'           => 'completed',
				'version'          => self::VERSION,
				'scanned'          => 0,
				'individual_cured' => 0,
				'packs'            => 0,
				'packs_with_cured' => 0,
				'packs_with_ham'   => 0,
				'updated'          => 0,
				'skipped'          => 0,
				'errors'           => array(),
				'finished_at'      => current_time( 'mysql' ),
			);

			foreach ( array_map( 'intval', $ids ) as $id ) {
				++$report['scanned'];
				try {
					$result = self::classify_product( $id );
					if ( empty( $result['target'] ) ) {
						++$report['skipped'];
						continue;
					}
					++$report['updated'];
					if ( ! empty( $result['individual_cured'] ) ) {
						++$report['individual_cured'];
					}
					if ( ! empty( $result['pack'] ) ) {
						++$report['packs'];
					}
					if ( ! empty( $result['pack_with_cured'] ) ) {
						++$report['packs_with_cured'];
					}
					if ( ! empty( $result['pack_with_ham'] ) ) {
						++$report['packs_with_ham'];
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

		self::ensure_structures();

		$title   = self::normalize( $product->get_name() );
		$context = self::build_context( $product );
		$is_pack = self::is_pack( $title );
		$type_values = self::detect_types( $title );
		$is_individual = ! $is_pack && ! empty( $type_values ) && self::is_individual_cured( $product, $title );

		if ( ! $is_pack && ! $is_individual ) {
			return array( 'target' => false );
		}

		$families = $is_pack ? self::detect_bundle_families( $context ) : array();
		$family_count = count( array_filter( $families ) );
		$pack_with_cured = $is_pack && ! empty( $families['cured'] ) && $family_count <= 2;
		$pack_with_ham   = $is_pack && ! empty( $families['ham'] ) && $family_count <= 2;

		$category_ids = array_map( 'intval', $product->get_category_ids() );
		$category_ids = array_values( array_unique( $category_ids ) );

		// Los lotes muy transversales viven únicamente en Packs y lotes. Así no
		// contaminan categorías de familia cuando mezclan tres o más familias.
		if ( $is_pack && $family_count > 2 ) {
			$family_category_ids = array( self::$cured_category_id );
			foreach ( array( 'jamones-paletas', 'jamones-y-paletas', 'quesos', 'carnes', 'aceites', 'naranjas' ) as $family_slug ) {
				$family_id = self::term_id_by_slug( $family_slug );
				if ( $family_id > 0 ) {
					$family_category_ids[] = $family_id;
				}
			}
			$category_ids = array_values( array_diff( $category_ids, $family_category_ids ) );
		}

		if ( $is_individual || $pack_with_cured ) {
			$category_ids[] = self::$cured_category_id;
		}
		if ( $is_pack ) {
			$category_ids[] = self::$packs_category_id;
		}
		if ( $pack_with_ham ) {
			$ham_id = self::ham_category_id();
			if ( $ham_id > 0 ) {
				$category_ids[] = $ham_id;
			}
		}
		$uncategorized_id = (int) get_option( 'default_product_cat', 0 );
		if ( $uncategorized_id > 0 ) {
			$category_ids = array_values( array_diff( $category_ids, array( $uncategorized_id ) ) );
		}
		$category_ids = array_values( array_unique( array_filter( array_map( 'intval', $category_ids ) ) ) );

		$classification = array(
			'tipo-producto' => array(),
			'raza-iberica'   => array(),
			'alimentacion'   => array(),
			'preparacion'    => array(),
		);

		if ( $is_individual || $pack_with_cured ) {
			$classification['tipo-producto'] = $is_individual ? $type_values : self::detect_types( $context );
			$classification['raza-iberica']   = self::detect_race( $context, $is_pack );
			$classification['alimentacion']   = self::detect_feed( $context, $is_pack );
			$classification['preparacion']    = self::detect_preparation( $product, $context, $is_pack );
		}

		self::$writing = true;
		try {
			$product->set_category_ids( $category_ids );
			if ( $is_individual || $pack_with_cured ) {
				self::apply_attributes( $product, $classification );
			}
			$product->save();
			update_post_meta(
				$product->get_id(),
				self::SNAPSHOT_META,
				wp_json_encode(
					array(
						'individual_cured' => $is_individual,
						'pack'              => $is_pack,
						'pack_with_cured'   => $pack_with_cured,
						'pack_with_ham'     => $pack_with_ham,
						'families'          => $families,
						'attributes'        => $classification,
					),
					JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
				)
			);
		} finally {
			self::$writing = false;
		}

		return array(
			'target'           => true,
			'individual_cured' => $is_individual,
			'pack'             => $is_pack,
			'pack_with_cured'  => $pack_with_cured,
			'pack_with_ham'    => $pack_with_ham,
		);
	}

	private static function ensure_structures(): void {
		self::ensure_categories();
		self::ensure_attributes();
	}

	private static function ensure_categories(): void {
		if ( self::$cured_category_id > 0 && self::$packs_category_id > 0 ) {
			return;
		}

		$cured = get_term_by( 'slug', self::CURED_CATEGORY_SLUG, 'product_cat' );
		if ( ! $cured ) {
			$legacy = get_term_by( 'slug', 'embutidos', 'product_cat' );
			if ( $legacy instanceof WP_Term ) {
				$updated = wp_update_term(
					(int) $legacy->term_id,
					'product_cat',
					array( 'name' => self::CURED_CATEGORY_NAME, 'slug' => self::CURED_CATEGORY_SLUG )
				);
				if ( is_wp_error( $updated ) ) {
					throw new RuntimeException( 'No se pudo renombrar la categoría Embutidos: ' . $updated->get_error_message() );
				}
				$cured = get_term( (int) $legacy->term_id, 'product_cat' );
			} else {
				$created = wp_insert_term( self::CURED_CATEGORY_NAME, 'product_cat', array( 'slug' => self::CURED_CATEGORY_SLUG ) );
				if ( is_wp_error( $created ) ) {
					throw new RuntimeException( 'No se pudo crear Embutidos y curados: ' . $created->get_error_message() );
				}
				$cured = get_term( (int) $created['term_id'], 'product_cat' );
			}
		}
		self::$cured_category_id = $cured instanceof WP_Term ? (int) $cured->term_id : 0;

		$packs = get_term_by( 'slug', self::PACKS_CATEGORY_SLUG, 'product_cat' );
		if ( ! $packs ) {
			$created = wp_insert_term( self::PACKS_CATEGORY_NAME, 'product_cat', array( 'slug' => self::PACKS_CATEGORY_SLUG ) );
			if ( is_wp_error( $created ) ) {
				throw new RuntimeException( 'No se pudo crear Packs y lotes: ' . $created->get_error_message() );
			}
			$packs = get_term( (int) $created['term_id'], 'product_cat' );
		}
		self::$packs_category_id = $packs instanceof WP_Term ? (int) $packs->term_id : 0;

		if ( self::$cured_category_id <= 0 || self::$packs_category_id <= 0 ) {
			throw new RuntimeException( 'No se pudieron resolver las categorías de catálogo.' );
		}
	}

	private static function ensure_attributes(): void {
		if ( count( self::$attribute_ids ) === count( self::ATTRIBUTES ) ) {
			return;
		}

		$existing = array();
		foreach ( (array) wc_get_attribute_taxonomies() as $attribute ) {
			$existing[ (string) $attribute->attribute_name ] = (int) $attribute->attribute_id;
		}

		foreach ( self::ATTRIBUTES as $slug => $definition ) {
			$id = $existing[ $slug ] ?? 0;
			if ( $id <= 0 ) {
				$result = wc_create_attribute( array(
					'name'         => $definition['label'],
					'slug'         => $slug,
					'type'         => 'select',
					'order_by'     => 'name',
					'has_archives' => false,
				) );
				if ( is_wp_error( $result ) ) {
					throw new RuntimeException( 'No se pudo crear el atributo ' . $slug . ': ' . $result->get_error_message() );
				}
				$id = (int) $result;
			}
			self::$attribute_ids[ $slug ] = $id;
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
			}
			foreach ( $definition['terms'] as $term ) {
				self::ensure_term( $taxonomy, $term );
			}
		}

		delete_transient( 'wc_attribute_taxonomies' );
		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		}
	}

	private static function ensure_term( string $taxonomy, string $name ): int {
		$term = term_exists( $name, $taxonomy );
		if ( is_array( $term ) ) {
			return (int) $term['term_id'];
		}
		if ( is_int( $term ) ) {
			return $term;
		}
		$created = wp_insert_term( $name, $taxonomy );
		if ( is_wp_error( $created ) ) {
			throw new RuntimeException( 'No se pudo crear el término «' . $name . '»: ' . $created->get_error_message() );
		}
		return (int) $created['term_id'];
	}

	private static function apply_attributes( WC_Product $product, array $classification ): void {
		$attributes = $product->get_attributes();
		$position = count( $attributes );

		foreach ( self::ATTRIBUTES as $slug => $definition ) {
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			$values = array_values( array_unique( array_filter( (array) ( $classification[ $slug ] ?? array() ) ) ) );
			if ( ! $values ) {
				wp_set_object_terms( $product->get_id(), array(), $taxonomy, false );
				unset( $attributes[ $taxonomy ] );
				continue;
			}
			$term_ids = array();
			foreach ( $values as $value ) {
				$term_ids[] = self::ensure_term( $taxonomy, (string) $value );
			}
			$term_ids = array_values( array_unique( array_map( 'intval', $term_ids ) ) );
			wp_set_object_terms( $product->get_id(), $term_ids, $taxonomy, false );
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( (int) self::$attribute_ids[ $slug ] );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( $term_ids );
			$attribute->set_position( $position++ );
			$attribute->set_visible( false );
			$attribute->set_variation( false );
			$attributes[ $taxonomy ] = $attribute;
		}

		$product->set_attributes( array_values( $attributes ) );
	}

	private static function is_pack( string $title ): bool {
		if ( preg_match( '/\b(?:pack|megapack|lote|surtido|degustacion)\b/u', $title ) ) {
			return true;
		}
		return in_array( $title, self::SPECIAL_BUNDLES, true );
	}

	private static function is_individual_cured( WC_Product $product, string $title ): bool {
		if ( preg_match( '/\b(?:adobad[oa]s?|adobo|marinad[oa]s?)\b/u', $title ) ) {
			return false;
		}
		if ( preg_match( '/\bchorizo\s+para\s+asar\b/u', $title ) ) {
			return false;
		}
		foreach ( $product->get_category_ids() as $category_id ) {
			$term = get_term( (int) $category_id, 'product_cat' );
			if ( $term instanceof WP_Term && in_array( $term->slug, array( 'carnes', 'pack-gourmet' ), true ) ) {
				return false;
			}
		}
		return true;
	}

	private static function detect_types( string $text ): array {
		$types = array();
		$map = array(
			'Lomito'     => '/\blomito\b/u',
			'Chorizo'    => '/\bchorizos?\b/u',
			'Salchichón' => '/\bsalchichon(?:es)?\b/u',
			'Morcón'     => '/\bmorcon(?:es)?\b/u',
			'Sobrasada'  => '/\bsobrasadas?\b/u',
			'Cecina'     => '/\bcecinas?\b/u',
			'Lomo'       => '/\blomo\b/u',
		);
		foreach ( $map as $name => $pattern ) {
			if ( preg_match( $pattern, $text ) ) {
				$types[] = $name;
			}
		}
		if ( in_array( 'Lomito', $types, true ) ) {
			$types = array_values( array_diff( $types, array( 'Lomo' ) ) );
		}
		return array_values( array_unique( $types ) );
	}

	private static function detect_race( string $text, bool $is_pack ): array {
		$values = array();
		foreach ( array( 100, 75, 50 ) as $pct ) {
			$patterns = array(
				'/\b' . $pct . '\s*%\s*(?:raza\s+)?iberic[oa]s?\b/u',
				'/\biberic[oa]s?\s*(?:de\s+)?(?:raza\s+)?' . $pct . '\s*%\b/u',
			);
			foreach ( $patterns as $pattern ) {
				if ( preg_match( $pattern, $text ) ) {
					$values[] = $pct . '% ibérico';
					break;
				}
			}
		}
		$values = array_values( array_unique( $values ) );
		if ( $is_pack && count( $values ) > 1 ) {
			return array();
		}
		return $values;
	}

	private static function detect_feed( string $text, bool $is_pack ): array {
		$values = array();
		if ( preg_match( '/\bcebo\s+de\s+campo\b/u', $text ) || preg_match( '/\bcebo\s+campo\b/u', $text ) ) {
			$values[] = 'Cebo de campo';
		}
		if ( preg_match( '/\bbellota\b/u', $text ) ) {
			$values[] = 'Bellota';
		}
		$text_without_field = preg_replace( '/\bcebo\s+(?:de\s+)?campo\b/u', ' ', $text );
		if ( is_string( $text_without_field ) && preg_match( '/\bcebo\b/u', $text_without_field ) ) {
			$values[] = 'Cebo';
		}
		$values = array_values( array_unique( $values ) );
		if ( $is_pack && count( $values ) > 1 ) {
			return array();
		}
		return $values;
	}

	private static function detect_preparation( WC_Product $product, string $context, bool $is_pack ): array {
		$title = self::normalize( $product->get_name() );
		$values = array();

		if ( preg_match( '/\b(?:sobres?|lonchead[oa]s?|cortad[oa]s?\s+a\s+(?:maquina|cuchillo))\b/u', $title ) ) {
			$values[] = 'Loncheado';
		}
		if ( preg_match( '/\bmedias?\s+piezas?\b/u', $context ) ) {
			$values[] = 'Media pieza';
		}
		if ( preg_match( '/\bpiezas?\s+enteras?\b/u', $context ) ) {
			$values[] = 'Pieza entera';
		}

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof WC_Product_Attribute ) {
				continue;
			}
			$name = $attribute->get_name();
			$options = $attribute->is_taxonomy()
				? wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'names' ) )
				: $attribute->get_options();
			$option_text = self::normalize( implode( ' ', array_map( 'strval', (array) $options ) ) );
			if ( preg_match( '/\b0[,.]5\s*kg\b/u', $option_text ) ) {
				$values[] = 'Media pieza';
			}
			if ( preg_match( '/\b1(?:[,.]0)?\s*kg\b/u', $option_text ) ) {
				$values[] = 'Pieza entera';
			}
		}

		if ( $is_pack && preg_match( '/\bsobres?\b/u', $context ) ) {
			$values[] = 'Loncheado';
		}
		if ( ! $values && ! preg_match( '/\b(?:sobres?|lonchead[oa]s?)\b/u', $title ) ) {
			$values[] = 'Pieza entera';
		}
		return array_values( array_unique( $values ) );
	}

	private static function detect_bundle_families( string $context ): array {
		$families = array(
			'cured'  => false,
			'ham'    => false,
			'cheese' => false,
			'wine'   => false,
			'oil'    => false,
			'honey'  => false,
			'meat'   => false,
			'fruit'  => false,
		);
		$title = strstr( $context, ' || ', true );
		$title = false === $title ? $context : $title;

		$families['cured']  = (bool) preg_match( '/\b(?:chorizos?|salchichon(?:es)?|lomos?|lomitos?|morcon(?:es)?|sobrasadas?|cecinas?|longanizas?)\b/u', $context );
		$families['ham']    = (bool) preg_match( '/\b(?:jamon(?:es)?|paletas?)\b/u', $context );
		$families['cheese'] = (bool) preg_match( '/\bquesos?\b/u', $context );
		$families['wine']   = (bool) preg_match( '/\b(?:botellas?\s+de\s+vino|vinos?)\b/u', $context );
		$families['oil']    = (bool) ( preg_match( '/\baceites?\b/u', $title ) || preg_match( '/\b(?:botellas?|botellitas?|latas?|garrafas?)\s+de\s+aceite\b/u', $context ) );
		$families['honey']  = (bool) preg_match( '/\b(?:tarros?|botes?)\s+de\s+miel\b/u', $context );
		$families['fruit']  = (bool) preg_match( '/\b(?:naranjas?|frutas?)\b/u', $title );

		// Para lotes de carne se usa el título como señal principal, evitando que
		// "carne" en ingredientes o "vaca" en un queso creen una familia falsa.
		$families['meat'] = ! preg_match( '/\bquesos?\b/u', $title )
			&& (bool) preg_match( '/\b(?:ternera|vaca|buey|tomahawk|chuleton|entrecot|filetes?|magro|barbacoa)\b/u', $title );
		return $families;
	}

	private static function build_context( WC_Product $product ): string {
		$parts = array(
			self::normalize( $product->get_name() ),
			self::normalize( wp_strip_all_tags( $product->get_short_description(), true ) ),
			self::normalize( wp_strip_all_tags( $product->get_description(), true ) ),
		);
		return implode( ' || ', array_filter( $parts ) );
	}

	private static function normalize( string $text ): string {
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = remove_accents( strtolower( $text ) );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( (string) $text );
	}

	private static function ham_category_id(): int {
		foreach ( array( 'jamones-paletas', 'jamones-y-paletas' ) as $slug ) {
			$id = self::term_id_by_slug( $slug );
			if ( $id > 0 ) {
				return $id;
			}
		}
		return 0;
	}

	private static function term_id_by_slug( string $slug ): int {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		return $term instanceof WP_Term ? (int) $term->term_id : 0;
	}
}
