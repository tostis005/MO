<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Última capa de precisión para Jamones y Paletas.
 *
 * Sólo corrige datos inequívocos del SKU/ficha concreta: preparación explícita
 * y pesos de pieza que el título o la ficha oficial publican de forma verificable.
 */
final class MDO_Ham_Catalog_Precision {
	private const VERSION = '2026-08-12.4';
	private const REPORT_OPTION = 'mdo_ham_catalog_precision_last_report';
	private static bool $running = false;
	private static bool $writing = false;
	private static array $queue = array();

	public static function init(): void {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'queue_product' ), 99, 2 );
		add_action( 'shutdown', array( __CLASS__, 'flush_queue' ), PHP_INT_MAX );
	}

	public static function queue_product( $product, $data_store = null ): void {
		unset( $data_store );
		if ( self::$running || self::$writing || ! $product instanceof WC_Product ) {
			return;
		}
		$id = $product->is_type( 'variation' ) ? (int) $product->get_parent_id() : (int) $product->get_id();
		if ( $id > 0 ) {
			self::$queue[ $id ] = true;
		}
	}

	public static function flush_queue(): void {
		if ( self::$running || self::$writing || ! self::$queue ) {
			return;
		}
		$ids = array_keys( self::$queue );
		self::$queue = array();
		foreach ( $ids as $id ) {
			try {
				self::precision_product( (int) $id );
			} catch ( Throwable $error ) {
				error_log( '[EMDO ham precision] Producto ' . (int) $id . ': ' . $error->getMessage() );
			}
		}
	}

	public static function apply_catalog(): array {
		self::$running = true;
		try {
			$ids = wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'status' => array( 'publish', 'private', 'draft', 'pending' ) ) );
			$report = array(
				'status' => 'completed',
				'version' => self::VERSION,
				'scanned' => 0,
				'eligible' => 0,
				'changed' => 0,
				'preparation_overrides' => 0,
				'weight_overrides' => 0,
				'errors' => array(),
				'finished_at' => current_time( 'mysql' ),
			);

			foreach ( array_map( 'intval', $ids ) as $id ) {
				++$report['scanned'];
				try {
					$product = wc_get_product( $id );
					if ( ! $product || $product->is_type( 'variation' ) || ! self::is_eligible( $product ) ) {
						continue;
					}
					++$report['eligible'];
					$result = self::precision_product( $id );
					if ( ! empty( $result['changed'] ) ) {
						++$report['changed'];
					}
					if ( ! empty( $result['preparation'] ) ) {
						++$report['preparation_overrides'];
					}
					if ( ! empty( $result['weight'] ) ) {
						++$report['weight_overrides'];
					}
				} catch ( Throwable $error ) {
					$report['errors'][] = array( 'product_id' => $id, 'message' => $error->getMessage() );
				}
			}

			update_option( self::REPORT_OPTION, $report, false );
			return $report;
		} finally {
			self::$running = false;
			self::$queue = array();
		}
	}

	public static function precision_product( int $product_id ): array {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array( 'changed' => false, 'preparation' => false, 'weight' => false );
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
			if ( ! $product ) {
				return array( 'changed' => false, 'preparation' => false, 'weight' => false );
			}
		}
		if ( ! self::is_eligible( $product ) ) {
			return array( 'changed' => false, 'preparation' => false, 'weight' => false );
		}

		$title = self::normalize( $product->get_name( 'edit' ) );
		$source_url = self::source_url( (int) $product->get_id() );
		$source_path = self::source_path( $source_url );
		$producer = self::producer( $product );
		$overrides = array();
		$evidence = array();

		$preparation = self::explicit_preparation( $title, $source_path );
		if ( $preparation ) {
			$overrides['preparacion'] = $preparation;
			$evidence['preparacion'] = array( 'source' => 'explicit_product_format', 'value' => $preparation );
		}

		$weight = self::explicit_weight_bands( $title, $source_path, $producer );
		if ( $weight ) {
			$overrides['rango-peso'] = $weight;
			$evidence['rango-peso'] = array( 'source' => self::weight_evidence_source( $title, $source_path ), 'value' => $weight );
		}

		if ( ! $overrides ) {
			return array( 'changed' => false, 'preparation' => false, 'weight' => false );
		}

		self::apply_overrides( $product, $overrides );
		self::refresh_snapshot( $product );
		update_post_meta( $product->get_id(), '_emdo_ham_precision_version', self::VERSION );
		update_post_meta( $product->get_id(), '_emdo_ham_precision_evidence', wp_json_encode( $evidence, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		wc_delete_product_transients( $product->get_id() );

		return array(
			'changed' => true,
			'preparation' => isset( $overrides['preparacion'] ),
			'weight' => isset( $overrides['rango-peso'] ),
		);
	}

	private static function is_eligible( WC_Product $product ): bool {
		$id = (int) $product->get_id();
		if ( get_post_meta( $id, '_emdo_ham_audit', true ) || get_post_meta( $id, '_emdo_ham_rescue_version', true ) ) {
			return true;
		}
		$terms = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) );
		return ! is_wp_error( $terms ) && in_array( 'jamones-paletas', (array) $terms, true );
	}

	private static function explicit_preparation( string $title, string $source_path ): array {
		$haystack = $title . ' ' . str_replace( '-', ' ', $source_path );

		if ( preg_match( '/\b(?:cortado|cortada)\s+a\s+cuchillo\b/u', $haystack ) ) {
			return array( 'Cortado a cuchillo' );
		}
		if ( preg_match( '/\b(?:cortado|cortada)\s+a\s+maquina\b/u', $haystack ) ) {
			return array( 'Loncheado' );
		}
		if ( preg_match( '/\bdeshuesad[oa]\b/u', $haystack ) ) {
			return array( 'Deshuesado' );
		}
		if ( preg_match( '/\bvirutas\b/u', $haystack ) ) {
			return array( 'Virutas' );
		}
		if ( preg_match( '/\bcodillo\b/u', $haystack ) ) {
			return array( 'Codillo' );
		}
		if ( preg_match( '/\btaco\b/u', $haystack ) ) {
			return array( 'Taco' );
		}

		// Estos dos productos son packs de sobres según la ficha oficial; no son
		// una paleta entera aunque el nombre comercial sea abreviado.
		if ( in_array( $source_path, array( 'pack-paleta-bellota', 'pack-paleta-cebo' ), true ) ) {
			return array( 'Loncheado' );
		}

		return array();
	}

	private static function explicit_weight_bands( string $title, string $source_path, string $producer ): array {
		// Promociones de pieza + accesorio: el peso de la pieza está en el título.
		if ( ( str_contains( $title, 'jamonero' ) || str_contains( $title, 'cuchillo gratis' ) ) && str_contains( $title, 'kg' ) ) {
			$interval = self::title_weight_interval( $title );
			if ( $interval ) {
				return self::bands_for_interval( $interval[0], $interval[1] );
			}
		}

		// Evidencia de las fichas oficiales actuales, expresada por URL de origen
		// para que la regla sea portable a producción aunque cambie el product_id.
		if ( '2-paletas-de-cebo-de-campo-iberica-50-raza-iberica' === $source_path ) {
			return self::bands_for_interval( 5.0, 5.0 );
		}
		if ( 'paleta-cesareo-seleccion-gourmet' === $source_path ) {
			return self::bands_for_interval( 4.2, 6.6 );
		}
		if ( 'paleta-de-cebo-de-campo-iberica-50-raza-iberica' === $source_path ) {
			return 'El Catedrático' === $producer
				? self::bands_for_interval( 5.2, 5.6 )
				: self::bands_for_interval( 4.8, 6.6 );
		}
		if ( 'paleta-de-cebo-iberica-50-raza-iberica' === $source_path && 'Puente Robles' === $producer ) {
			return self::bands_for_interval( 5.0, 5.6 );
		}

		return array();
	}

	private static function weight_evidence_source( string $title, string $source_path ): string {
		if ( ( str_contains( $title, 'jamonero' ) || str_contains( $title, 'cuchillo gratis' ) ) && str_contains( $title, 'kg' ) ) {
			return 'product_title';
		}
		return $source_path ? 'official_source_page' : 'structured_product_data';
	}

	private static function title_weight_interval( string $title ): ?array {
		if ( preg_match( '/\b(\d{1,2}(?:[\.,]\d+)?)\s*kg\s+a\s+(\d{1,2}(?:[\.,]\d+)?)\s*kg\b/u', $title, $match ) ) {
			return array( self::decimal( $match[1] ), self::decimal( $match[2] ) );
		}
		if ( preg_match( '/\bde\s+(\d{1,2}(?:[\.,]\d+)?)\s*kg\s+a\s+(\d{1,2}(?:[\.,]\d+)?)\s*kg\b/u', $title, $match ) ) {
			return array( self::decimal( $match[1] ), self::decimal( $match[2] ) );
		}
		if ( preg_match( '/\b(\d{1,2}(?:[\.,]\d+)?)\s*kg\b/u', $title, $match ) ) {
			$value = self::decimal( $match[1] );
			return array( $value, $value );
		}
		return null;
	}

	private static function decimal( string $value ): float {
		return (float) str_replace( ',', '.', $value );
	}

	private static function bands_for_interval( float $low, float $high ): array {
		$definitions = array(
			array( 3.5, 4.5, '3,5–4,5 kg' ),
			array( 4.5, 5.5, '4,5–5,5 kg' ),
			array( 5.5, 6.5, '5,5–6,5 kg' ),
			array( 6.5, 7.5, '6,5–7,5 kg' ),
			array( 7.5, 8.5, '7,5–8,5 kg' ),
			array( 8.5, 9.5, '8,5–9,5 kg' ),
			array( 9.5, 10.5, '9,5–10,5 kg' ),
			array( 10.5, PHP_FLOAT_MAX, '+10,5 kg' ),
		);
		$out = array();
		foreach ( $definitions as $definition ) {
			list( $band_low, $band_high, $label ) = $definition;
			if ( abs( $low - $high ) < 0.0001 ) {
				if ( $low >= $band_low && $low < $band_high ) {
					$out[] = $label;
				}
			} elseif ( max( $low, $band_low ) < min( $high, $band_high ) ) {
				$out[] = $label;
			}
		}
		return $out;
	}

	private static function apply_overrides( WC_Product $product, array $overrides ): void {
		$attributes = $product->get_attributes();
		$position = count( $attributes );

		foreach ( $overrides as $slug => $values ) {
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$values = array_values( array_unique( array_filter( array_map( 'strval', (array) $values ) ) ) );
			$term_ids = array();
			foreach ( $values as $value ) {
				$term = term_exists( $value, $taxonomy );
				if ( ! $term ) {
					$term = wp_insert_term( $value, $taxonomy );
				}
				if ( is_wp_error( $term ) ) {
					throw new RuntimeException( 'No se pudo crear ' . $taxonomy . ':' . $value . ': ' . $term->get_error_message() );
				}
				$term_ids[] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
			}
			wp_set_object_terms( $product->get_id(), $term_ids, $taxonomy, false );

			$attribute_id = (int) wc_attribute_taxonomy_id_by_name( $taxonomy );
			if ( $attribute_id <= 0 ) {
				continue;
			}
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( $attribute_id );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( $term_ids );
			$attribute->set_position( isset( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] instanceof WC_Product_Attribute ? $attributes[ $taxonomy ]->get_position() : $position++ );
			$attribute->set_visible( false );
			$attribute->set_variation( false );
			$attributes[ $taxonomy ] = $attribute;
		}

		self::$writing = true;
		try {
			$product->set_attributes( array_values( $attributes ) );
			$product->save();
		} finally {
			self::$writing = false;
		}
	}

	private static function refresh_snapshot( WC_Product $product ): void {
		$classification = array();
		foreach ( array( 'tipo-pieza', 'calidad', 'raza-iberica', 'alimentacion', 'con-dop', 'dop', 'origen', 'preparacion', 'rango-peso', 'curacion', 'productor' ) as $slug ) {
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			$classification[ $slug ] = taxonomy_exists( $taxonomy )
				? array_values( array_map( 'strval', (array) wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) ) ) )
				: array();
		}
		update_post_meta( $product->get_id(), '_emdo_ham_taxonomy_snapshot', wp_json_encode( $classification, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}

	private static function source_url( int $product_id ): string {
		$audit = json_decode( (string) get_post_meta( $product_id, '_emdo_ham_audit', true ), true );
		if ( is_array( $audit ) && ! empty( $audit['source_url'] ) ) {
			return (string) $audit['source_url'];
		}
		$url = (string) get_post_meta( $product_id, '_emdo_source_url', true );
		if ( $url ) {
			return $url;
		}
		global $wpdb;
		$source_id = (int) get_post_meta( $product_id, '_emdo_source_product_id', true );
		if ( $source_id > 0 && class_exists( 'MDO_Database' ) ) {
			$table = MDO_Database::table( 'source_products' );
			$url = (string) $wpdb->get_var( $wpdb->prepare( "SELECT source_url FROM {$table} WHERE id = %d", $source_id ) );
		}
		return $url;
	}

	private static function source_path( string $url ): string {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$path = trim( $path, '/' );
		if ( str_starts_with( $path, 'es/' ) ) {
			$path = substr( $path, 3 );
		}
		return sanitize_title( $path );
	}

	private static function producer( WC_Product $product ): string {
		if ( taxonomy_exists( 'pa_productor' ) ) {
			$values = wc_get_product_terms( $product->get_id(), 'pa_productor', array( 'fields' => 'names' ) );
			if ( $values ) {
				return (string) reset( $values );
			}
		}
		$user = get_user_by( 'id', (int) get_post_field( 'post_author', $product->get_id() ) );
		return $user ? (string) $user->display_name : '';
	}

	private static function normalize( string $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = remove_accents( strtolower( $text ) );
		$text = preg_replace( '/[^a-z0-9%+\-\.\s]/u', ' ', $text );
		return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	}
}
