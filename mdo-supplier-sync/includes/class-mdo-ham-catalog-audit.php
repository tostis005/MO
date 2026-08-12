<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auditoría autoritativa de Jamones y Paletas.
 *
 * A diferencia de MDO_Ham_Taxonomy, esta capa no clasifica raza/calidad/DOP
 * leyendo indiscriminadamente toda la descripción. Prioriza título y datos
 * estructurados del producto, y sólo usa la descripción para hechos que sí
 * necesitan texto contextual (p. ej. curación). De este modo, los textos
 * educativos sobre bridas o DOP no contaminan los atributos del producto.
 */
final class MDO_Ham_Catalog_Audit {
	private const AUDIT_VERSION = '2026-08-12.1';
	private const REPORT_OPTION = 'mdo_ham_catalog_audit_last_report';
	private const SNAPSHOT_META = '_emdo_ham_taxonomy_snapshot';
	private const AUDIT_META = '_emdo_ham_audit';

	private static bool $writing = false;
	private static bool $running = false;
	private static array $queue = array();
	private static array $attribute_ids = array();

	private const ATTRIBUTES = array(
		'tipo-pieza' => array( 'label' => 'Tipo de pieza', 'terms' => array( 'Jamón', 'Paleta' ) ),
		'calidad' => array( 'label' => 'Calidad', 'terms' => array( 'Bellota 100% ibérico', 'Bellota ibérico', 'Cebo de campo ibérico', 'Cebo ibérico', 'Serrano', 'Selección gourmet' ) ),
		'raza-iberica' => array( 'label' => 'Raza ibérica', 'terms' => array( '100% ibérico', '75% ibérico', '50% ibérico' ) ),
		'alimentacion' => array( 'label' => 'Alimentación', 'terms' => array( 'Bellota', 'Cebo de campo', 'Cebo' ) ),
		'con-dop' => array( 'label' => 'Con DOP', 'terms' => array( 'Sí', 'No' ) ),
		'dop' => array( 'label' => 'Denominación de origen', 'terms' => array( 'Los Pedroches', 'Guijuelo', 'Jabugo', 'Dehesa de Extremadura', 'Jamón de Teruel' ) ),
		'origen' => array( 'label' => 'Origen', 'terms' => array( 'Córdoba', 'Salamanca', 'Huelva', 'Extremadura', 'Teruel', 'Granada', 'Arribes del Duero', 'Zamora' ) ),
		'preparacion' => array( 'label' => 'Preparación', 'terms' => array( 'Pieza entera', 'Deshuesado', 'Loncheado', 'Cortado a cuchillo', 'Taco' ) ),
		'rango-peso' => array( 'label' => 'Peso', 'terms' => array( '3,5–4,5 kg', '4,5–5,5 kg', '5,5–6,5 kg', '6,5–7,5 kg', '7,5–8,5 kg', '8,5–9,5 kg', '9,5–10,5 kg', '+10,5 kg' ) ),
		'curacion' => array( 'label' => 'Curación', 'terms' => array( 'Menos de 24 meses', '24–36 meses', '36–48 meses', '+48 meses' ) ),
		'productor' => array( 'label' => 'Productor', 'terms' => array( 'Puente Robles', 'El Catedrático', 'Hidalgo de la Jara' ) ),
	);

	public static function init(): void {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'queue_saved_product' ), 90, 2 );
		add_action( 'shutdown', array( __CLASS__, 'flush_queue' ), PHP_INT_MAX );
	}

	public static function queue_saved_product( $product, $data_store = null ): void {
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
				self::audit_product( (int) $id );
			} catch ( Throwable $error ) {
				error_log( '[EMDO ham audit] Producto ' . (int) $id . ': ' . $error->getMessage() );
			}
		}
	}

	/** Ejecuta la auditoría completa del catálogo y devuelve cobertura verificable. */
	public static function apply_catalog(): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			throw new RuntimeException( 'WooCommerce no está disponible.' );
		}

		self::$running = true;
		try {
			self::ensure_attributes();
			$ids = wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'status' => array( 'publish', 'private', 'draft', 'pending' ) ) );
			$report = array(
				'status' => 'completed',
				'version' => self::AUDIT_VERSION,
				'scanned' => 0,
				'audited' => 0,
				'excluded' => 0,
				'not_target' => 0,
				'errors' => array(),
				'coverage' => array_fill_keys( array_keys( self::ATTRIBUTES ), 0 ),
				'finished_at' => current_time( 'mysql' ),
			);

			foreach ( array_map( 'intval', $ids ) as $id ) {
				++$report['scanned'];
				try {
					$result = self::audit_product( $id );
					if ( 'audited' === $result['status'] ) {
						++$report['audited'];
						foreach ( self::ATTRIBUTES as $slug => $definition ) {
							unset( $definition );
							if ( ! empty( $result['classification'][ $slug ] ) ) {
								++$report['coverage'][ $slug ];
							}
						}
					} elseif ( 'excluded' === $result['status'] ) {
						++$report['excluded'];
					} else {
						++$report['not_target'];
					}
				} catch ( Throwable $error ) {
					if ( count( $report['errors'] ) < 50 ) {
						$report['errors'][] = array( 'product_id' => $id, 'message' => $error->getMessage() );
					}
				}
			}

			update_option( self::REPORT_OPTION, $report, false );
			return $report;
		} finally {
			self::$running = false;
			self::$queue = array();
		}
	}

	/**
	 * Audita un producto. Devuelve audited, excluded o not_target.
	 */
	public static function audit_product( int $product_id ): array {
		$product = $product_id > 0 ? wc_get_product( $product_id ) : false;
		if ( ! $product ) {
			return array( 'status' => 'not_target', 'classification' => array() );
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
			if ( ! $product ) {
				return array( 'status' => 'not_target', 'classification' => array() );
			}
		}

		$title = self::normalize( $product->get_name() );
		if ( preg_match( '/\b(?:jamonero|cuchillo|funda|soporte)\b/u', $title ) ) {
			if ( preg_match( '/\bjamonero\b/u', $title ) ) {
				self::clear_ham_metadata( $product );
				return array( 'status' => 'excluded', 'classification' => array() );
			}
			return array( 'status' => 'not_target', 'classification' => array() );
		}

		if ( ! preg_match( '/\b(?:jamon(?:es)?|paleta(?:s)?)\b/u', $title ) ) {
			return array( 'status' => 'not_target', 'classification' => array() );
		}

		self::ensure_attributes();
		$payload = self::source_payload( (int) $product->get_id() );
		$classification = self::classify_audited( $product, $payload );
		self::apply_classification( $product, $classification );
		self::apply_catalog_terms( $product, $classification );
		self::store_audit_metadata( $product, $classification, $payload );

		return array( 'status' => 'audited', 'classification' => $classification );
	}

	private static function classify_audited( WC_Product $product, array $payload ): array {
		$title = self::normalize( $product->get_name() );
		$result = array_fill_keys( array_keys( self::ATTRIBUTES ), array() );

		if ( preg_match( '/\bjamon(?:es)?\b/u', $title ) ) {
			$result['tipo-pieza'][] = 'Jamón';
		}
		if ( preg_match( '/\bpaleta(?:s)?\b/u', $title ) ) {
			$result['tipo-pieza'][] = 'Paleta';
		}

		$race = self::race_from_title( $title );
		$feeding = self::feeding_from_title( $title );
		$quality = self::quality_from_core( $race, $feeding, $title );

		// Excepciones auditadas una a una: el título abreviado no contiene toda la ficha.
		if ( str_contains( $title, 'lote paleta bellota' ) ) {
			$race = '50'; $feeding = 'Bellota'; $quality = 'Bellota ibérico';
		} elseif ( str_contains( $title, 'lote paleta campo' ) ) {
			$race = '50'; $feeding = 'Cebo de campo'; $quality = 'Cebo de campo ibérico';
		} elseif ( str_contains( $title, 'lote paleta iberica' ) ) {
			$race = '75'; $feeding = 'Bellota'; $quality = 'Bellota ibérico';
		} elseif ( str_contains( $title, 'pack gourmet jamon y queso' ) ) {
			$race = '50'; $feeding = 'Cebo'; $quality = 'Cebo ibérico';
		} elseif ( preg_match( '/\bpack paleta bellota\b/u', $title ) ) {
			$race = '75'; $feeding = 'Bellota'; $quality = 'Bellota ibérico';
		} elseif ( preg_match( '/\bpack paleta cebo\b/u', $title ) ) {
			$race = '50'; $feeding = 'Cebo de campo'; $quality = 'Cebo de campo ibérico';
		} elseif ( str_contains( $title, 'paleta cesareo seleccion gourmet' ) ) {
			$race = ''; $feeding = ''; $quality = 'Selección gourmet';
		}

		if ( 'pack de cata de bellota 100% iberico' === substr( $title, 0, 36 ) || str_contains( $title, 'pack de cata de bellota 100% iberico' ) ) {
			$result['tipo-pieza'] = array( 'Jamón', 'Paleta' );
			$race = '100'; $feeding = 'Bellota'; $quality = 'Bellota 100% ibérico';
		}

		if ( $quality ) {
			$result['calidad'][] = $quality;
		}
		if ( $race ) {
			$result['raza-iberica'][] = $race . '% ibérico';
		}
		if ( $feeding ) {
			$result['alimentacion'][] = $feeding;
		}

		list( $dop_status, $dop_names ) = self::dop_from_product( $product, $title );
		$result['con-dop'] = $dop_status;
		$result['dop'] = $dop_names;
		$result['origen'] = self::origin_from_vendor( $product, $title );
		$result['preparacion'] = self::preparation_from_structured_data( $product, $payload, $title );
		$result['rango-peso'] = self::weight_bands( $product, $payload, $title );
		$result['curacion'] = self::curing_bands( $product, $payload, $title, $quality, $race, $feeding );
		$result['productor'] = array( self::producer_name( $product ) );

		foreach ( $result as $key => $values ) {
			$result[ $key ] = array_values( array_unique( array_filter( array_map( 'strval', (array) $values ) ) ) );
		}
		return $result;
	}

	private static function race_from_title( string $title ): string {
		return preg_match( '/\b(100|75|50)\s*%/u', $title, $match ) ? (string) $match[1] : '';
	}

	private static function feeding_from_title( string $title ): string {
		if ( preg_match( '/\bcebo\s+de\s+campo\b/u', $title ) ) {
			return 'Cebo de campo';
		}
		if ( preg_match( '/\bbellota\b/u', $title ) ) {
			return 'Bellota';
		}
		if ( preg_match( '/\bcebo\b/u', $title ) ) {
			return 'Cebo';
		}
		return '';
	}

	private static function quality_from_core( string $race, string $feeding, string $title ): string {
		if ( 'Bellota' === $feeding && '100' === $race ) {
			return 'Bellota 100% ibérico';
		}
		if ( 'Bellota' === $feeding && in_array( $race, array( '75', '50' ), true ) ) {
			return 'Bellota ibérico';
		}
		if ( 'Cebo de campo' === $feeding && $race ) {
			return 'Cebo de campo ibérico';
		}
		if ( 'Cebo' === $feeding && $race ) {
			return 'Cebo ibérico';
		}
		if ( preg_match( '/\bserrano\b/u', $title ) ) {
			return 'Serrano';
		}
		return '';
	}

	private static function dop_from_product( WC_Product $product, string $title ): array {
		if ( preg_match( '/\b(?:dop|d\.?\s*o\.?\s*p\.?)\s+los\s+pedroches\b/u', $title ) ) {
			return array( array( 'Sí' ), array( 'Los Pedroches' ) );
		}

		$has_dop = false;
		$has_non_dop = false;
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( ! $variation instanceof WC_Product_Variation ) {
					continue;
				}
				$text = self::normalize( implode( ' ', array_values( $variation->get_attributes() ) ) );
				if ( preg_match( '/\bdop[\s-]*los[\s-]*pedroches\b/u', $text ) ) {
					$has_dop = true;
				} else {
					$has_non_dop = true;
				}
			}
		}
		if ( $has_dop ) {
			return array( $has_non_dop ? array( 'Sí', 'No' ) : array( 'Sí' ), array( 'Los Pedroches' ) );
		}
		return array( array( 'No' ), array() );
	}

	private static function producer_name( WC_Product $product ): string {
		$user = get_user_by( 'id', (int) get_post_field( 'post_author', $product->get_id() ) );
		$name = $user ? trim( (string) $user->display_name ) : '';
		$norm = self::normalize( $name );
		if ( str_contains( $norm, 'puenterobles' ) || str_contains( $norm, 'puente robles' ) ) {
			return 'Puente Robles';
		}
		if ( str_contains( $norm, 'elcatedratico' ) || str_contains( $norm, 'el catedratico' ) ) {
			return 'El Catedrático';
		}
		if ( str_contains( $norm, 'hidalgo de la jara' ) ) {
			return 'Hidalgo de la Jara';
		}
		return $name ?: 'El Mercado de Origen';
	}

	private static function origin_from_vendor( WC_Product $product, string $title ): array {
		$producer = self::producer_name( $product );
		if ( 'Hidalgo de la Jara' === $producer ) {
			return array( 'Córdoba' );
		}
		if ( 'El Catedrático' === $producer ) {
			return array( 'Salamanca' );
		}
		if ( 'Puente Robles' === $producer ) {
			$origin = array( 'Arribes del Duero' );
			if ( str_contains( $title, 'cesareo seleccion gourmet' ) ) {
				$origin[] = 'Zamora';
			}
			return $origin;
		}
		return array();
	}

	private static function preparation_from_structured_data( WC_Product $product, array $payload, string $title ): array {
		$values = array();
		foreach ( (array) ( $payload['extra_groups'] ?? array() ) as $group ) {
			$label = self::normalize( (string) ( $group['key'] ?? '' ) . ' ' . (string) ( $group['label'] ?? '' ) );
			if ( ! str_contains( $label, 'prepar' ) ) {
				continue;
			}
			foreach ( (array) ( $group['options'] ?? array() ) as $option ) {
				self::append_preparation_from_text( $values, self::normalize( (string) ( $option['label'] ?? '' ) ) );
			}
		}

		foreach ( self::yith_labels( (int) $product->get_id() ) as $label ) {
			self::append_preparation_from_text( $values, self::normalize( $label ) );
		}
		self::append_preparation_from_text( $values, $title );

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( $variation instanceof WC_Product_Variation ) {
					self::append_preparation_from_text( $values, self::normalize( implode( ' ', array_values( $variation->get_attributes() ) ) ) );
				}
			}
		}

		if ( str_contains( $title, 'pack de cata de bellota 100% iberico' ) ) {
			return array( 'Loncheado', 'Cortado a cuchillo' );
		}
		if ( preg_match( '/\b(?:lote paleta|pack gourmet jamon y queso|2 paletas)\b/u', $title ) ) {
			$values[] = 'Pieza entera';
		}
		if ( ! $values && preg_match( '/\b(?:jamon|paleta)\b/u', $title ) ) {
			$values[] = 'Pieza entera';
		}
		return array_values( array_unique( $values ) );
	}

	private static function append_preparation_from_text( array &$values, string $text ): void {
		if ( str_contains( $text, 'pieza entera' ) ) {
			$values[] = 'Pieza entera';
		}
		if ( str_contains( $text, 'deshues' ) ) {
			$values[] = 'Deshuesado';
		}
		if ( str_contains( $text, 'cuchillo' ) ) {
			$values[] = 'Cortado a cuchillo';
		}
		if ( str_contains( $text, 'maquina' ) || str_contains( $text, 'lonche' ) || str_contains( $text, 'tapas' ) || str_contains( $text, 'sobres' ) ) {
			$values[] = 'Loncheado';
		}
		if ( preg_match( '/\btaco\b/u', $text ) ) {
			$values[] = 'Taco';
		}
	}

	private static function weight_bands( WC_Product $product, array $payload, string $title ): array {
		$intervals = array();

		// En lotes, el selector de la fuente pertenece a productos acompañantes;
		// el peso útil para este filtro es el de la pieza principal indicado en la ficha.
		if ( preg_match( '/\blote paleta (?:bellota|campo|iberica)\b/u', $title ) ) {
			$intervals[] = array( 5.0, 5.0 );
		} elseif ( str_contains( $title, 'pack gourmet jamon y queso' ) ) {
			$intervals[] = array( 8.0, 8.0 );
		} else {
			foreach ( (array) ( $payload['option_groups'] ?? array() ) as $group ) {
				$group_name = self::normalize( (string) ( $group['key'] ?? '' ) . ' ' . (string) ( $group['label'] ?? '' ) );
				if ( ! str_contains( $group_name, 'peso' ) && ! str_contains( $group_name, 'tamano' ) ) {
					continue;
				}
				foreach ( (array) ( $group['options'] ?? array() ) as $option ) {
					$interval = self::parse_weight_interval( (string) ( $option['label'] ?? '' ) );
					if ( $interval && max( $interval ) >= 3.5 ) {
						$intervals[] = $interval;
					}
				}
			}

			if ( ! $intervals && $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $child_id ) {
					$variation = wc_get_product( $child_id );
					if ( ! $variation instanceof WC_Product_Variation ) {
						continue;
					}
					foreach ( $variation->get_attributes() as $value ) {
						$interval = self::parse_slug_weight_interval( (string) $value );
						if ( $interval && max( $interval ) >= 3.5 ) {
							$intervals[] = $interval;
						}
					}
				}
			}
		}

		$bands = array();
		foreach ( $intervals as $interval ) {
			$bands = array_merge( $bands, self::bands_for_interval( (float) $interval[0], (float) $interval[1] ) );
		}
		return array_values( array_unique( $bands ) );
	}

	private static function parse_weight_interval( string $label ): ?array {
		$text = html_entity_decode( $label, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( ! preg_match_all( '/(\d{1,2}(?:[\.,]\d{1,3})?)\s*(?:kg|kilos?)/iu', $text, $matches ) ) {
			return null;
		}
		$values = array_map( static fn( string $value ): float => (float) str_replace( ',', '.', $value ), $matches[1] );
		if ( count( $values ) >= 2 && $values[0] > 0 && $values[0] <= $values[1] && $values[1] <= 20 ) {
			return array( $values[0], $values[1] );
		}
		if ( 1 === count( $values ) && $values[0] > 0 && $values[0] <= 20 ) {
			return array( $values[0], $values[0] );
		}
		return null;
	}

	private static function parse_slug_weight_interval( string $value ): ?array {
		$text = self::normalize( $value );
		if ( ! str_contains( $text, 'kg' ) || str_contains( $text, 'uds' ) ) {
			return null;
		}
		$before = preg_replace( '/kg.*$/u', '', $text );
		preg_match_all( '/\d+/u', (string) $before, $matches );
		if ( count( $matches[0] ) < 2 ) {
			return null;
		}
		$convert = static function ( string $number ): float {
			return 2 === strlen( $number ) && str_ends_with( $number, '5' ) ? (float) ( $number[0] . '.5' ) : (float) $number;
		};
		$low = $convert( $matches[0][0] );
		$high = $convert( $matches[0][1] );
		return $low > 0 && $low <= $high && $high <= 20 ? array( $low, $high ) : null;
	}

	private static function bands_for_interval( float $low, float $high ): array {
		$definitions = array(
			array( 3.5, 4.5, '3,5–4,5 kg' ), array( 4.5, 5.5, '4,5–5,5 kg' ),
			array( 5.5, 6.5, '5,5–6,5 kg' ), array( 6.5, 7.5, '6,5–7,5 kg' ),
			array( 7.5, 8.5, '7,5–8,5 kg' ), array( 8.5, 9.5, '8,5–9,5 kg' ),
			array( 9.5, 10.5, '9,5–10,5 kg' ), array( 10.5, PHP_FLOAT_MAX, '+10,5 kg' ),
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

	private static function curing_bands( WC_Product $product, array $payload, string $title, string $quality, string $race, string $feeding ): array {
		unset( $race );
		if ( str_contains( $title, 'pack de cata de bellota 100% iberico' ) ) {
			return array();
		}
		$description = (string) ( $payload['description'] ?? $product->get_description() );
		$detail = self::curing_detail( $description );
		if ( $detail ) {
			return self::curing_detail_to_bands( $detail );
		}

		$producer = self::producer_name( $product );
		if ( 'Puente Robles' === $producer ) {
			if ( 'Selección gourmet' === $quality ) {
				return array( '36–48 meses' );
			}
			if ( in_array( 'Paleta', self::types_from_title( $title ), true ) ) {
				return 'Cebo' === $feeding ? array( 'Menos de 24 meses' ) : array( '24–36 meses' );
			}
			if ( 'Cebo de campo' === $feeding || ( 'Bellota' === $feeding && 'Bellota ibérico' !== $quality ) || str_contains( $title, '75% raza iberica' ) ) {
				return array( '36–48 meses' );
			}
			return array( '24–36 meses' );
		}
		if ( 'El Catedrático' === $producer ) {
			if ( in_array( 'Paleta', self::types_from_title( $title ), true ) ) {
				return 'Cebo' === $feeding ? array( 'Menos de 24 meses' ) : array( '24–36 meses' );
			}
			if ( 'Cebo de campo' === $feeding ) {
				// La ficha oficial actual no publica un número de meses: no inventamos uno.
				return array();
			}
			if ( 'Cebo' === $feeding || ( 'Bellota' === $feeding && 'Bellota ibérico' === $quality && str_contains( $title, '50% raza iberica' ) ) ) {
				return array( '24–36 meses' );
			}
			return array( '36–48 meses' );
		}
		if ( 'Hidalgo de la Jara' === $producer ) {
			return array( '24–36 meses' );
		}
		return array();
	}

	private static function types_from_title( string $title ): array {
		$out = array();
		if ( preg_match( '/\bjamon(?:es)?\b/u', $title ) ) { $out[] = 'Jamón'; }
		if ( preg_match( '/\bpaleta(?:s)?\b/u', $title ) ) { $out[] = 'Paleta'; }
		return $out;
	}

	private static function curing_detail( string $description ): string {
		$text = preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( $description, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
		$patterns = array(
			'/curaci[oó]n total entre\s+(\d+)\s+y\s+(\d+)\s+meses/iu',
			'/curaci[oó]n total de\s+(\d+)\s+meses/iu',
			'/proceso de\s+(\d+)\s+a\s+(\d+)\s+meses de curaci[oó]n/iu',
			'/se curan? durante\s+(\d+)\s+meses/iu',
			'/secado y maduraci[oó]n de\s+(\d+)\s+a\s+(\d+)\s+meses/iu',
			'/(\d+)\s+meses de curaci[oó]n/iu',
			'/curaci[oó]n artesanal de\s+(\d+)\s+meses/iu',
			'/proceso de curaci[oó]n natural suele ser de\s+(\d+)\s+meses/iu',
			'/curaci[oó]n de\s+(\d+)\s+meses/iu',
			'/curaci[oó]n lenta[^.]{0,150}superior a los\s+(\d+)\s+meses/iu',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, (string) $text, $match ) ) {
				return implode( '-', array_slice( $match, 1 ) ) . ( str_contains( $match[0], 'superior' ) ? '+' : '' );
			}
		}
		return '';
	}

	private static function curing_detail_to_bands( string $detail ): array {
		$more_than = str_ends_with( $detail, '+' );
		preg_match_all( '/\d+/u', $detail, $matches );
		$numbers = array_map( 'intval', $matches[0] );
		if ( ! $numbers ) {
			return array();
		}
		if ( $more_than ) {
			return array( '24–36 meses' );
		}
		$low = min( $numbers );
		$high = max( $numbers );
		if ( $low === $high ) {
			if ( $low < 24 ) { return array( 'Menos de 24 meses' ); }
			if ( $low < 36 ) { return array( '24–36 meses' ); }
			if ( $low < 48 ) { return array( '36–48 meses' ); }
			return array( '+48 meses' );
		}
		$out = array();
		foreach ( array( array( 0, 24, 'Menos de 24 meses' ), array( 24, 36, '24–36 meses' ), array( 36, 48, '36–48 meses' ), array( 48, PHP_INT_MAX, '+48 meses' ) ) as $band ) {
			if ( max( $low, $band[0] ) < min( $high, $band[1] ) ) {
				$out[] = $band[2];
			}
		}
		return $out;
	}

	private static function ensure_attributes(): void {
		if ( self::$attribute_ids ) {
			return;
		}
		$existing = array();
		foreach ( (array) wc_get_attribute_taxonomies() as $attribute ) {
			$existing[ (string) $attribute->attribute_name ] = (int) $attribute->attribute_id;
		}
		foreach ( self::ATTRIBUTES as $slug => $definition ) {
			$id = $existing[ $slug ] ?? 0;
			if ( $id <= 0 ) {
				$id = wc_create_attribute( array( 'name' => $definition['label'], 'slug' => $slug, 'type' => 'select', 'order_by' => 'name', 'has_archives' => false ) );
				if ( is_wp_error( $id ) ) {
					throw new RuntimeException( 'No se pudo crear el atributo ' . $slug . ': ' . $id->get_error_message() );
				}
				$id = (int) $id;
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
	}

	private static function ensure_term( string $taxonomy, string $name ): int {
		$term = term_exists( $name, $taxonomy );
		if ( is_array( $term ) ) { return (int) $term['term_id']; }
		if ( is_int( $term ) ) { return $term; }
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
			unset( $definition );
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			$values = array_values( array_unique( array_filter( (array) ( $classification[ $slug ] ?? array() ) ) ) );
			if ( ! $values ) {
				wp_set_object_terms( $id, array(), $taxonomy, false );
				unset( $attributes[ $taxonomy ] );
				continue;
			}
			$term_ids = array_map( fn( $value ) => self::ensure_term( $taxonomy, (string) $value ), $values );
			wp_set_object_terms( $id, $term_ids, $taxonomy, false );
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( (int) self::$attribute_ids[ $slug ] );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( array_values( array_unique( array_map( 'intval', $term_ids ) ) ) );
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

	private static function apply_catalog_terms( WC_Product $product, array $classification ): void {
		$id = (int) $product->get_id();
		$category = get_term_by( 'slug', 'jamones-paletas', 'product_cat' );
		if ( ! $category instanceof WP_Term ) {
			$category = get_term_by( 'name', 'Jamones y paletas', 'product_cat' );
		}
		if ( ! $category instanceof WP_Term ) {
			$created = wp_insert_term( 'Jamones y paletas', 'product_cat', array( 'slug' => 'jamones-paletas' ) );
			if ( ! is_wp_error( $created ) ) {
				$category = get_term( (int) $created['term_id'], 'product_cat' );
			}
		}
		if ( $category instanceof WP_Term ) {
			wp_set_object_terms( $id, array( (int) $category->term_id ), 'product_cat', true );
		}
		$uncategorized = get_term_by( 'slug', 'sin-categorizar', 'product_cat' );
		if ( ! $uncategorized instanceof WP_Term ) {
			$uncategorized = get_term_by( 'name', 'Sin categorizar', 'product_cat' );
		}
		if ( $uncategorized instanceof WP_Term ) {
			wp_remove_object_terms( $id, (int) $uncategorized->term_id, 'product_cat' );
		}

		$tags = array_merge( $classification['tipo-pieza'], $classification['raza-iberica'], $classification['alimentacion'] );
		if ( $classification['raza-iberica'] ) { $tags[] = 'Ibérico'; }
		if ( in_array( 'Selección gourmet', $classification['calidad'], true ) ) { $tags[] = 'Selección gourmet'; }
		if ( in_array( 'Los Pedroches', $classification['dop'], true ) ) { $tags[] = 'DOP Los Pedroches'; }
		$title = self::normalize( $product->get_name() );
		if ( str_contains( $title, 'sobres' ) || str_contains( $title, 'cortado a maquina' ) || str_contains( $title, 'cortada a maquina' ) ) { $tags[] = 'Loncheado'; }
		if ( str_contains( $title, 'deshues' ) ) { $tags[] = 'Deshuesado'; }
		if ( preg_match( '/\btaco\b/u', $title ) ) { $tags[] = 'Taco'; }
		if ( str_contains( $title, 'cuchillo' ) || str_contains( $title, 'pack de cata' ) ) { $tags[] = 'Cortado a cuchillo'; }
		if ( str_contains( $title, 'pack' ) || str_contains( $title, 'lote' ) ) { $tags[] = 'Pack'; }
		if ( $tags ) {
			wp_set_object_terms( $id, array_values( array_unique( $tags ) ), 'product_tag', true );
		}
	}

	private static function store_audit_metadata( WC_Product $product, array $classification, array $payload ): void {
		$id = (int) $product->get_id();
		$feeding = $classification['alimentacion'][0] ?? '';
		$race = $classification['raza-iberica'][0] ?? '';
		$brida = '';
		if ( 'Bellota' === $feeding ) {
			$brida = '100% ibérico' === $race ? 'Negra' : ( $race ? 'Roja' : '' );
		} elseif ( 'Cebo de campo' === $feeding && $race ) {
			$brida = 'Verde';
		} elseif ( 'Cebo' === $feeding && $race ) {
			$brida = 'Blanca';
		}
		$dop_status = in_array( 'Sí', $classification['con-dop'], true ) && in_array( 'No', $classification['con-dop'], true ) ? 'Mixto' : ( $classification['con-dop'][0] ?? '' );
		$source_url = (string) ( $payload['source_url'] ?? '' );
		if ( ! $source_url ) {
			$source_url = (string) get_post_meta( $id, '_emdo_source_url', true );
		}
		$meta = array(
			'audit_version' => self::AUDIT_VERSION,
			'audited_at' => current_time( 'mysql' ),
			'title_evidence' => html_entity_decode( $product->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'source_url' => $source_url,
			'iberico' => $race ? 'Sí' : 'No especificado',
			'raza_pct' => str_replace( '% ibérico', '', $race ),
			'brida' => $brida,
			'dop_status' => $dop_status,
			'dop' => $classification['dop'],
			'origen' => $classification['origen'],
			'classification' => $classification,
		);
		update_post_meta( $id, self::AUDIT_META, wp_json_encode( $meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		update_post_meta( $id, '_emdo_ham_audit_version', self::AUDIT_VERSION );
		update_post_meta( $id, '_emdo_ham_brida', $brida );
		update_post_meta( $id, '_emdo_ham_dop_status', $dop_status );
		update_post_meta( $id, '_emdo_ham_raza_pct', str_replace( '% ibérico', '', $race ) );
	}

	private static function clear_ham_metadata( WC_Product $product ): void {
		self::ensure_attributes();
		$empty = array_fill_keys( array_keys( self::ATTRIBUTES ), array() );
		self::apply_classification( $product, $empty );
		$id = (int) $product->get_id();
		$category = get_term_by( 'slug', 'jamones-paletas', 'product_cat' );
		if ( $category instanceof WP_Term ) {
			wp_remove_object_terms( $id, (int) $category->term_id, 'product_cat' );
		}
		delete_post_meta( $id, self::SNAPSHOT_META );
		delete_post_meta( $id, self::AUDIT_META );
		delete_post_meta( $id, '_emdo_ham_audit_version' );
		delete_post_meta( $id, '_emdo_ham_brida' );
		delete_post_meta( $id, '_emdo_ham_dop_status' );
		delete_post_meta( $id, '_emdo_ham_raza_pct' );
		wc_delete_product_transients( $id );
	}

	private static function yith_labels( int $product_id ): array {
		global $wpdb;
		$addons = $wpdb->prefix . 'yith_wapo_addons';
		$assoc = $wpdb->prefix . 'yith_wapo_blocks_assoc';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $addons ) ) !== $addons || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $assoc ) ) !== $assoc ) {
			return array();
		}
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT a.options FROM {$addons} a INNER JOIN {$assoc} x ON x.rule_id = a.block_id WHERE x.type = 'product' AND x.object = %s", (string) $product_id ) );
		$labels = array();
		foreach ( (array) $rows as $serialized ) {
			$options = maybe_unserialize( $serialized );
			if ( is_array( $options ) && isset( $options['label'] ) ) {
				$labels = array_merge( $labels, array_map( 'strval', (array) $options['label'] ) );
			}
		}
		return $labels;
	}

	private static function source_payload( int $product_id ): array {
		global $wpdb;
		$source_id = (int) get_post_meta( $product_id, '_emdo_source_product_id', true );
		if ( $source_id <= 0 || ! class_exists( 'MDO_Database' ) ) {
			return array();
		}
		$table = MDO_Database::table( 'source_products' );
		$json = $wpdb->get_var( $wpdb->prepare( "SELECT source_payload FROM {$table} WHERE id = %d", $source_id ) );
		if ( ! is_string( $json ) || '' === $json ) {
			return array();
		}
		$payload = json_decode( $json, true );
		return is_array( $payload ) ? $payload : array();
	}

	private static function normalize( string $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = remove_accents( strtolower( $text ) );
		$text = preg_replace( '/[^a-z0-9%+\-\.\s]/u', ' ', $text );
		return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	}
}
