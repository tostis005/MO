<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normaliza Jamones y Paletas en atributos globales de WooCommerce pensados
 * para filtros. No altera las variaciones ni los extras originales del proveedor.
 */
final class MDO_Ham_Taxonomy {
	private const SCHEMA_VERSION = '1.0.0';
	private const VERSION_OPTION = 'mdo_ham_taxonomy_version';
	private const REPORT_OPTION  = 'mdo_ham_taxonomy_last_report';
	private const SNAPSHOT_META  = '_emdo_ham_taxonomy_snapshot';

	private static bool $writing = false;
	private static bool $migration = false;
	private static array $queue = array();
	private static array $attribute_ids = array();

	private const ATTRIBUTES = array(
		'tipo-pieza' => array( 'label' => 'Tipo de pieza', 'terms' => array( 'Jamón', 'Paleta' ) ),
		'calidad' => array( 'label' => 'Calidad', 'terms' => array( 'Bellota 100% ibérico', 'Bellota ibérico', 'Cebo de campo ibérico', 'Cebo ibérico', 'Serrano' ) ),
		'raza-iberica' => array( 'label' => 'Raza ibérica', 'terms' => array( '100% ibérico', '75% ibérico', '50% ibérico' ) ),
		'alimentacion' => array( 'label' => 'Alimentación', 'terms' => array( 'Bellota', 'Cebo de campo', 'Cebo' ) ),
		'con-dop' => array( 'label' => 'Con DOP', 'terms' => array( 'Sí', 'No' ) ),
		'dop' => array( 'label' => 'Denominación de origen', 'terms' => array( 'Los Pedroches', 'Guijuelo', 'Jabugo', 'Dehesa de Extremadura', 'Jamón de Teruel' ) ),
		'origen' => array( 'label' => 'Origen', 'terms' => array( 'Córdoba', 'Salamanca', 'Huelva', 'Extremadura', 'Teruel', 'Granada' ) ),
		'preparacion' => array( 'label' => 'Preparación', 'terms' => array( 'Pieza entera', 'Deshuesado', 'Loncheado', 'Cortado a cuchillo' ) ),
		'rango-peso' => array( 'label' => 'Peso', 'terms' => array( '3,5–4,5 kg', '4,5–5,5 kg', '5,5–6,5 kg', '6,5–7,5 kg', '7,5–8,5 kg', '8,5–9,5 kg', '9,5–10,5 kg', '+10,5 kg' ) ),
		'curacion' => array( 'label' => 'Curación', 'terms' => array( 'Menos de 24 meses', '24–36 meses', '36–48 meses', '+48 meses' ) ),
		'productor' => array( 'label' => 'Productor', 'terms' => array() ),
	);

	public static function init(): void {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'queue_saved_product' ), 50, 2 );
		add_action( 'shutdown', array( __CLASS__, 'flush_queue' ), PHP_INT_MAX );
	}

	public static function queue_saved_product( $product, $data_store = null ): void {
		unset( $data_store );
		if ( self::$writing || self::$migration || ! $product instanceof WC_Product ) {
			return;
		}
		$id = $product->is_type( 'variation' ) ? (int) $product->get_parent_id() : (int) $product->get_id();
		if ( $id > 0 ) {
			self::$queue[ $id ] = true;
		}
	}

	public static function flush_queue(): void {
		if ( self::$writing || self::$migration || ! self::$queue ) {
			return;
		}
		$ids = array_keys( self::$queue );
		self::$queue = array();
		foreach ( $ids as $id ) {
			try {
				self::classify_product( (int) $id );
			} catch ( Throwable $error ) {
				error_log( '[EMDO taxonomy] Producto ' . (int) $id . ': ' . $error->getMessage() );
			}
		}
	}

	/** Ejecuta la pasada inicial del catálogo. El workflow de Dev la invoca tras desplegar. */
	public static function migrate_catalog( bool $force = false ): array {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_products' ) ) {
			throw new RuntimeException( 'WooCommerce no está disponible.' );
		}
		if ( ! $force && self::SCHEMA_VERSION === (string) get_option( self::VERSION_OPTION, '' ) ) {
			$old = get_option( self::REPORT_OPTION, array() );
			return is_array( $old ) ? array_merge( $old, array( 'status' => 'up-to-date' ) ) : array( 'status' => 'up-to-date' );
		}

		self::$migration = true;
		try {
			self::ensure_attributes();
			$ids = wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'status' => array( 'publish', 'private', 'draft', 'pending' ) ) );
			$report = array(
				'status' => 'completed', 'version' => self::SCHEMA_VERSION, 'scanned' => 0,
				'classified' => 0, 'skipped' => 0, 'errors' => array(), 'finished_at' => current_time( 'mysql' ),
			);
			foreach ( array_map( 'intval', $ids ) as $id ) {
				++$report['scanned'];
				try {
					if ( self::classify_product( $id ) ) {
						++$report['classified'];
					} else {
						++$report['skipped'];
					}
				} catch ( Throwable $error ) {
					if ( count( $report['errors'] ) < 25 ) {
						$report['errors'][] = array( 'product_id' => $id, 'message' => $error->getMessage() );
					}
				}
			}
			update_option( self::VERSION_OPTION, self::SCHEMA_VERSION, false );
			update_option( self::REPORT_OPTION, $report, false );
			return $report;
		} finally {
			self::$migration = false;
			self::$queue = array();
		}
	}

	public static function classify_product( int $product_id ): bool {
		$product = $product_id > 0 && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
		if ( ! $product ) {
			return false;
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
			if ( ! $product ) {
				return false;
			}
		}

		$payload = self::source_payload( (int) $product->get_id() );
		$context = self::build_context( $product, $payload );
		if ( ! self::is_target_product( $product, $context['text'] ) ) {
			return false;
		}
		self::ensure_attributes();
		self::apply_classification( $product, self::classify( $product, $context ) );
		return true;
	}

	private static function ensure_attributes(): void {
		if ( self::$attribute_ids ) {
			return;
		}
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) || ! function_exists( 'wc_create_attribute' ) ) {
			throw new RuntimeException( 'La API de atributos de WooCommerce no está disponible.' );
		}
		$existing = array();
		foreach ( (array) wc_get_attribute_taxonomies() as $attribute ) {
			$existing[ (string) $attribute->attribute_name ] = (int) $attribute->attribute_id;
		}
		foreach ( self::ATTRIBUTES as $slug => $definition ) {
			$id = $existing[ $slug ] ?? 0;
			if ( $id <= 0 ) {
				$result = wc_create_attribute( array( 'name' => $definition['label'], 'slug' => $slug, 'type' => 'select', 'order_by' => 'name', 'has_archives' => false ) );
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

	private static function apply_classification( WC_Product $product, array $classification ): void {
		$id = (int) $product->get_id();
		$attributes = $product->get_attributes();
		$position = count( $attributes );
		foreach ( self::ATTRIBUTES as $slug => $definition ) {
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			$values = array_values( array_unique( array_filter( (array) ( $classification[ $slug ] ?? array() ) ) ) );
			if ( ! $values ) {
				wp_set_object_terms( $id, array(), $taxonomy, false );
				unset( $attributes[ $taxonomy ] );
				continue;
			}
			$term_ids = array();
			foreach ( $values as $value ) {
				$term_ids[] = self::ensure_term( $taxonomy, (string) $value );
			}
			$term_ids = array_values( array_unique( array_map( 'intval', $term_ids ) ) );
			wp_set_object_terms( $id, $term_ids, $taxonomy, false );
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( (int) self::$attribute_ids[ $slug ] );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( $term_ids );
			$attribute->set_position( $position++ );
			$attribute->set_visible( false );
			$attribute->set_variation( false );
			$attributes[ $taxonomy ] = $attribute;
		}
		self::$writing = true;
		try {
			$product->set_attributes( array_values( $attributes ) );
			$product->save();
			update_post_meta( $id, self::SNAPSHOT_META, wp_json_encode( $classification, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		} finally {
			self::$writing = false;
		}
	}

	private static function classify( WC_Product $product, array $context ): array {
		$text = $context['text'];
		$title = self::normalize_text( $product->get_name() );
		$result = array_fill_keys( array_keys( self::ATTRIBUTES ), array() );

		if ( preg_match( '/\bpaleta\b/u', $title ) ) {
			$result['tipo-pieza'][] = 'Paleta';
		} elseif ( preg_match( '/\bjamon\b/u', $title ) ) {
			$result['tipo-pieza'][] = 'Jamón';
		}

		$race = self::detect_race( $text );
		if ( $race ) {
			$result['raza-iberica'][] = $race . '% ibérico';
		}
		if ( preg_match( '/\bcebo\s+de\s+campo\b/u', $text ) ) {
			$result['alimentacion'][] = 'Cebo de campo';
		} elseif ( preg_match( '/\bbellota\b/u', $text ) ) {
			$result['alimentacion'][] = 'Bellota';
		} elseif ( preg_match( '/\bcebo\b/u', $text ) ) {
			$result['alimentacion'][] = 'Cebo';
		}

		if ( preg_match( '/\bbellota\b/u', $text ) && '100' === $race ) {
			$result['calidad'][] = 'Bellota 100% ibérico';
		} elseif ( preg_match( '/\bbellota\b/u', $text ) && preg_match( '/\biberic/u', $text ) ) {
			$result['calidad'][] = 'Bellota ibérico';
		} elseif ( preg_match( '/\bcebo\s+de\s+campo\b/u', $text ) && preg_match( '/\biberic/u', $text ) ) {
			$result['calidad'][] = 'Cebo de campo ibérico';
		} elseif ( preg_match( '/\bcebo\b/u', $text ) && preg_match( '/\biberic/u', $text ) ) {
			$result['calidad'][] = 'Cebo ibérico';
		} elseif ( preg_match( '/\bserrano\b/u', $text ) ) {
			$result['calidad'][] = 'Serrano';
		}

		$dop = self::detect_dop( $text );
		$has_dop = (bool) preg_match( '/(?:\bdop\b|\bd[.\s]*o[.\s]*p\.?\b|\bdenominacion\s+de\s+origen\s+protegida\b)/u', $text );
		if ( $dop ) {
			$has_dop = true;
			$result['dop'][] = $dop;
		}
		$result['con-dop'][] = $has_dop ? 'Sí' : 'No';
		$result['origen'] = self::detect_origins( $text, $dop );
		$result['preparacion'] = self::detect_preparations( $title, $text );
		$result['rango-peso'] = self::detect_weight_bands( $context['weight_texts'] );
		$result['curacion'] = self::detect_curing( $text );

		$author = get_user_by( 'id', (int) get_post_field( 'post_author', $product->get_id() ) );
		if ( $author && trim( (string) $author->display_name ) ) {
			$result['productor'][] = trim( (string) $author->display_name );
		}
		foreach ( $result as $key => $values ) {
			$result[ $key ] = array_values( array_unique( array_filter( $values ) ) );
		}
		return $result;
	}

	private static function detect_race( string $text ): string {
		foreach ( array( '100', '75', '50' ) as $race ) {
			if ( preg_match( '/\b' . $race . '\s*%\s*(?:de\s+raza\s+)?iberic/u', $text ) || preg_match( '/\biberic[^.]{0,30}\b' . $race . '\s*%/u', $text ) ) {
				return $race;
			}
		}
		return '';
	}

	private static function detect_dop( string $text ): string {
		$names = array(
			'Los Pedroches' => 'los\s+pedroches', 'Guijuelo' => 'guijuelo', 'Jabugo' => 'jabugo',
			'Dehesa de Extremadura' => 'dehesa\s+de\s+extremadura', 'Jamón de Teruel' => 'jamon\s+de\s+teruel|teruel',
		);
		$marker = '(?:dop|d[.\s]*o[.\s]*p\.?|denominacion\s+de\s+origen(?:\s+protegida)?)';
		foreach ( $names as $label => $pattern ) {
			if ( preg_match( '/(?:' . $marker . ')[^.]{0,80}(?:' . $pattern . ')|(?:' . $pattern . ')[^.]{0,80}(?:' . $marker . ')/u', $text ) ) {
				return $label;
			}
		}
		return '';
	}

	private static function detect_origins( string $text, string $dop ): array {
		$values = array();
		$from_dop = array( 'Los Pedroches' => 'Córdoba', 'Guijuelo' => 'Salamanca', 'Jabugo' => 'Huelva', 'Dehesa de Extremadura' => 'Extremadura', 'Jamón de Teruel' => 'Teruel' );
		if ( $dop && isset( $from_dop[ $dop ] ) ) {
			$values[] = $from_dop[ $dop ];
		}
		$patterns = array( 'Córdoba' => '\bcordoba\b|\blos\s+pedroches\b', 'Salamanca' => '\bsalamanca\b|\bguijuelo\b', 'Huelva' => '\bhuelva\b|\bjabugo\b', 'Extremadura' => '\bextremadura\b', 'Teruel' => '\bteruel\b', 'Granada' => '\bgranada\b|\btrevelez\b' );
		foreach ( $patterns as $origin => $pattern ) {
			if ( preg_match( '/' . $pattern . '/u', $text ) ) {
				$values[] = $origin;
			}
		}
		return array_values( array_unique( $values ) );
	}

	private static function detect_preparations( string $title, string $text ): array {
		$values = array();
		if ( preg_match( '/\b(?:pieza\s+entera|pieza\s+completa|sin\s+preparar|entero|entera)\b/u', $text ) ) $values[] = 'Pieza entera';
		if ( preg_match( '/\b(?:deshuesad[oa]?|deshuesar|sin\s+hueso)\b/u', $text ) ) $values[] = 'Deshuesado';
		if ( preg_match( '/\b(?:lonchead[oa]?|lonchas?|sobres?\s+(?:al\s+vacio|de\s+\d+\s*g))\b/u', $text ) ) $values[] = 'Loncheado';
		if ( preg_match( '/\b(?:cortad[oa]?\s+a\s+cuchillo|corte\s+a\s+cuchillo|a\s+cuchillo)\b/u', $text ) ) $values[] = 'Cortado a cuchillo';
		if ( ! preg_match( '/\b(?:lonchead|sobres?|virutas?|tacos?|deshuesad)\b/u', $title ) ) $values[] = 'Pieza entera';
		return array_values( array_unique( $values ) );
	}

	private static function detect_weight_bands( array $texts ): array {
		$intervals = array();
		foreach ( $texts as $raw ) {
			$text = self::normalize_text( (string) $raw );
			$range_found = false;
			if ( preg_match_all( '/([0-9]{1,2}(?:[.,][0-9]{1,3})?)\s*(?:kg|kilos?)?\s*(?:-|–|a|hasta)\s*([0-9]{1,2}(?:[.,][0-9]{1,3})?)\s*(?:kg|kilos?)/u', $text, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $match ) {
					$min = self::decimal( $match[1] ); $max = self::decimal( $match[2] );
					if ( null !== $min && null !== $max && $min > 0 && $max >= $min && $max <= 20 ) {
						$intervals[] = array( $min, $max ); $range_found = true;
					}
				}
			}
			if ( ! $range_found && preg_match_all( '/\b([0-9]{1,2}(?:[.,][0-9]{1,3})?)\s*(?:kg|kilos?)\b/u', $text, $matches ) ) {
				foreach ( $matches[1] as $raw_value ) {
					$value = self::decimal( $raw_value );
					if ( null !== $value && $value > 0 && $value <= 20 ) $intervals[] = array( $value, $value );
				}
			}
		}
		$bands = array(
			array( 3.5, 4.5, '3,5–4,5 kg' ), array( 4.5, 5.5, '4,5–5,5 kg' ), array( 5.5, 6.5, '5,5–6,5 kg' ),
			array( 6.5, 7.5, '6,5–7,5 kg' ), array( 7.5, 8.5, '7,5–8,5 kg' ), array( 8.5, 9.5, '8,5–9,5 kg' ), array( 9.5, 10.5, '9,5–10,5 kg' ),
		);
		$result = array();
		foreach ( $intervals as $interval ) {
			list( $min, $max ) = $interval;
			foreach ( $bands as $band ) {
				list( $low, $high, $label ) = $band;
				if ( ( $min === $max && $min >= $low && $min < $high ) || ( $min < $max && max( $min, $low ) < min( $max, $high ) ) ) $result[] = $label;
			}
			if ( ( $min === $max && $min >= 10.5 ) || ( $min < $max && $max > 10.5 ) ) $result[] = '+10,5 kg';
		}
		return array_values( array_unique( $result ) );
	}

	private static function detect_curing( string $text ): array {
		$months = array();
		if ( preg_match_all( '/\b([0-9]{1,3})\s*(?:mes|meses)\b/u', $text, $matches ) ) {
			foreach ( $matches[1] as $raw ) {
				$value = (int) $raw;
				if ( $value >= 6 && $value <= 120 ) $months[] = $value;
			}
		}
		if ( ! $months ) return array();
		$value = max( $months );
		if ( $value < 24 ) return array( 'Menos de 24 meses' );
		if ( $value < 36 ) return array( '24–36 meses' );
		if ( $value < 48 ) return array( '36–48 meses' );
		return array( '+48 meses' );
	}

	private static function build_context( WC_Product $product, array $payload ): array {
		$texts = array( $product->get_name(), $product->get_description(), $product->get_short_description() );
		$weights = array();
		$categories = wp_get_post_terms( $product->get_id(), 'product_cat' );
		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) { $texts[] = $category->name; $texts[] = $category->slug; }
		}
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof WC_Product_Attribute ) continue;
			$name = $attribute->get_name(); $texts[] = $name;
			$values = $attribute->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'names' ) ) : $attribute->get_options();
			foreach ( (array) $values as $value ) { $texts[] = (string) $value; if ( preg_match( '/peso|tamano|tamaño|weight/iu', $name ) ) $weights[] = (string) $value; }
		}
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( ! $variation instanceof WC_Product_Variation ) continue;
				foreach ( $variation->get_attributes() as $key => $value ) {
					$texts[] = (string) $key; $texts[] = (string) $value;
					if ( preg_match( '/peso|tamano|tamaño|weight/iu', (string) $key ) || preg_match( '/kg|kilos?/iu', (string) $value ) ) $weights[] = (string) $value;
				}
			}
		}
		if ( $payload ) {
			self::flatten_strings( $payload, $texts );
			foreach ( array( 'variations', 'option_groups' ) as $key ) if ( isset( $payload[ $key ] ) ) self::flatten_strings( $payload[ $key ], $weights );
		}
		self::append_yith_text( (int) $product->get_id(), $texts );
		return array( 'text' => self::normalize_text( implode( ' | ', array_filter( $texts ) ) ), 'weight_texts' => array_values( array_unique( array_filter( array_map( 'strval', $weights ) ) ) ) );
	}

	private static function append_yith_text( int $product_id, array &$texts ): void {
		global $wpdb;
		$addons = $wpdb->prefix . 'yith_wapo_addons'; $assoc = $wpdb->prefix . 'yith_wapo_blocks_assoc';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $addons ) ) !== $addons || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $assoc ) ) !== $assoc ) return;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT a.settings, a.options FROM {$addons} a INNER JOIN {$assoc} x ON x.rule_id = a.block_id WHERE x.type = 'product' AND x.object = %s", (string) $product_id ), ARRAY_A );
		foreach ( (array) $rows as $row ) { self::flatten_strings( maybe_unserialize( $row['settings'] ?? '' ), $texts ); self::flatten_strings( maybe_unserialize( $row['options'] ?? '' ), $texts ); }
	}

	private static function source_payload( int $product_id ): array {
		$source_id = (int) get_post_meta( $product_id, '_emdo_source_product_id', true );
		if ( $source_id <= 0 || ! class_exists( 'MDO_Database' ) ) return array();
		global $wpdb;
		$raw = $wpdb->get_var( $wpdb->prepare( 'SELECT source_payload FROM ' . MDO_Database::table( 'source_products' ) . ' WHERE id = %d', $source_id ) );
		$payload = json_decode( (string) $raw, true );
		return is_array( $payload ) ? $payload : array();
	}

	private static function is_target_product( WC_Product $product, string $text ): bool {
		$categories = wp_get_post_terms( $product->get_id(), 'product_cat' );
		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) {
				if ( preg_match( '/\b(?:jamon(?:es)?|paleta(?:s)?)\b/u', self::normalize_text( $category->name . ' ' . $category->slug ) ) ) return true;
			}
		}
		$title = self::normalize_text( $product->get_name() );
		if ( preg_match( '/\b(?:jamonero|cuchillo|funda|soporte)\b/u', $title ) ) return false;
		if ( preg_match( '/\bjamon\b/u', $title ) ) return (bool) preg_match( '/\b(?:iberic|bellota|cebo|serrano|curad|dop|guijuelo|jabugo|pedroches|teruel)\b/u', $text );
		if ( preg_match( '/\bpaleta\b/u', $title ) ) return (bool) preg_match( '/\b(?:iberic|bellota|cebo|serrano|curad|dop|guijuelo|jabugo|pedroches)\b/u', $text );
		return false;
	}

	private static function flatten_strings( $value, array &$output ): void {
		if ( is_string( $value ) || is_numeric( $value ) ) {
			$string = trim( (string) $value ); if ( '' !== $string && strlen( $string ) <= 5000 ) $output[] = $string; return;
		}
		if ( is_array( $value ) ) foreach ( $value as $child ) self::flatten_strings( $child, $output );
	}

	private static function normalize_text( string $value ): string {
		$value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = remove_accents( $value );
		$value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
		$value = str_replace( array( "\r", "\n", "\t", ';', ':', '(', ')', '[', ']' ), ' ', $value );
		return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
	}

	private static function decimal( string $value ): ?float {
		$value = str_replace( ',', '.', trim( $value ) );
		return is_numeric( $value ) ? (float) $value : null;
	}
}
