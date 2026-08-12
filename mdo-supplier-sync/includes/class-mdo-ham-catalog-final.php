<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capa final de casos especiales auditados de Jamones y Paletas.
 *
 * Completa familias que no pertenecen a las categorías legales del ibérico
 * (Duroc/Reserva y Cesáreo fuera de norma) sin inventar raza 50/75/100 ni
 * alimentación Bellota/Cebo cuando la ficha no lo declara.
 */
final class MDO_Ham_Catalog_Final {
	private const VERSION = '2026-08-12.2';
	private const REPORT_OPTION = 'mdo_ham_catalog_final_last_report';
	private static bool $writing = false;
	private static bool $running = false;
	private static array $queue = array();

	public static function init(): void {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'queue_product' ), 95, 2 );
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
				self::finalize_product( (int) $id );
			} catch ( Throwable $error ) {
				error_log( '[EMDO ham final] Producto ' . (int) $id . ': ' . $error->getMessage() );
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
				'special_finalized' => 0,
				'duroc' => 0,
				'cesareo' => 0,
				'errors' => array(),
				'finished_at' => current_time( 'mysql' ),
			);
			foreach ( array_map( 'intval', $ids ) as $id ) {
				++$report['scanned'];
				try {
					$result = self::finalize_product( $id );
					if ( ! empty( $result['finalized'] ) ) {
						++$report['special_finalized'];
						if ( isset( $report[ $result['family'] ] ) ) {
							++$report[ $result['family'] ];
						}
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

	public static function finalize_product( int $product_id ): array {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array( 'finalized' => false, 'family' => '' );
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
			if ( ! $product ) {
				return array( 'finalized' => false, 'family' => '' );
			}
		}

		$title = self::normalize( $product->get_name() );
		$family = self::special_family( $title );
		if ( ! $family ) {
			return array( 'finalized' => false, 'family' => '' );
		}

		if ( 'duroc' === $family ) {
			self::finalize_duroc( $product, $title );
		} else {
			self::finalize_cesareo( $product, $title );
		}
		self::update_audit_meta( $product, $family, $title );
		wc_delete_product_transients( $product->get_id() );

		return array( 'finalized' => true, 'family' => $family );
	}

	private static function special_family( string $title ): string {
		if (
			str_contains( $title, 'jamon reserva' ) ||
			str_contains( $title, 'codillo de jamon curado' ) ||
			str_contains( $title, 'virutas de jamon premium' ) ||
			str_contains( $title, 'taco de jamon reserva' )
		) {
			return 'duroc';
		}
		if ( str_contains( $title, 'cesareo seleccion gourmet' ) ) {
			return 'cesareo';
		}
		return '';
	}

	private static function finalize_duroc( WC_Product $product, string $title ): void {
		self::set_attribute( $product, 'calidad', array( 'Duroc' ) );
		self::set_attribute( $product, 'raza-iberica', array() );
		self::set_attribute( $product, 'alimentacion', array() );
		self::set_attribute( $product, 'con-dop', array( 'No' ) );
		self::set_attribute( $product, 'dop', array() );

		if ( str_contains( $title, 'codillo de jamon curado' ) ) {
			self::set_attribute( $product, 'origen', array( 'Arribes del Duero' ) );
			self::set_attribute( $product, 'curacion', array( 'Menos de 24 meses' ) );
			self::ensure_tag( $product, 'Duroc' );
			self::ensure_tag( $product, 'Codillo' );
			update_post_meta( $product->get_id(), '_emdo_ham_alimentacion_descriptiva', 'Cereales y piensos naturales' );
		} elseif ( str_contains( $title, 'virutas de jamon premium' ) ) {
			self::set_attribute( $product, 'origen', array( 'Arribes del Duero', 'Zamora' ) );
			self::set_attribute( $product, 'preparacion', array( 'Virutas' ) );
			self::set_attribute( $product, 'curacion', array( '36–48 meses' ) );
			self::ensure_tag( $product, 'Duroc' );
			self::ensure_tag( $product, 'Virutas' );
			update_post_meta( $product->get_id(), '_emdo_ham_alimentacion_descriptiva', 'Cría en campo' );
		} else {
			self::set_attribute( $product, 'origen', array( 'Arribes del Duero', 'Zamora' ) );
			self::set_attribute( $product, 'curacion', array( '24–36 meses' ) );
			self::ensure_tag( $product, 'Duroc' );
			self::ensure_tag( $product, 'Reserva' );
			update_post_meta( $product->get_id(), '_emdo_ham_alimentacion_descriptiva', 'Cereales y piensos naturales' );

			if ( 'jamon reserva' === $title && ! self::attribute_values( $product, 'rango-peso' ) ) {
				self::set_attribute( $product, 'rango-peso', array( '7,5–8,5 kg', '8,5–9,5 kg', '9,5–10,5 kg', '+10,5 kg' ) );
			}
		}

		update_post_meta( $product->get_id(), '_emdo_ham_raza_general', 'Duroc' );
		update_post_meta( $product->get_id(), '_emdo_ham_iberico', 'No' );
		update_post_meta( $product->get_id(), '_emdo_ham_norma_iberico', 'No aplica' );
		update_post_meta( $product->get_id(), '_emdo_ham_brida', '' );
		update_post_meta( $product->get_id(), '_emdo_ham_raza_pct', '' );
	}

	private static function finalize_cesareo( WC_Product $product, string $title ): void {
		unset( $title );
		self::set_attribute( $product, 'calidad', array( 'Selección gourmet' ) );
		self::set_attribute( $product, 'raza-iberica', array() );
		self::set_attribute( $product, 'alimentacion', array() );
		self::set_attribute( $product, 'con-dop', array( 'No' ) );
		self::set_attribute( $product, 'dop', array() );
		self::set_attribute( $product, 'origen', array( 'Arribes del Duero', 'Zamora' ) );
		self::set_attribute( $product, 'curacion', array( '36–48 meses' ) );
		self::ensure_tag( $product, 'Selección gourmet' );
		self::ensure_tag( $product, 'Cesáreo' );
		update_post_meta( $product->get_id(), '_emdo_ham_raza_general', 'No declarada' );
		update_post_meta( $product->get_id(), '_emdo_ham_iberico', 'No especificado' );
		update_post_meta( $product->get_id(), '_emdo_ham_norma_iberico', 'Fuera de norma' );
		update_post_meta( $product->get_id(), '_emdo_ham_alimentacion_descriptiva', 'Cría en campo' );
		update_post_meta( $product->get_id(), '_emdo_ham_brida', '' );
		update_post_meta( $product->get_id(), '_emdo_ham_raza_pct', '' );
	}

	private static function set_attribute( WC_Product $product, string $slug, array $values ): void {
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}
		$product_id = (int) $product->get_id();
		$attributes = $product->get_attributes();
		$values = array_values( array_unique( array_filter( array_map( 'strval', $values ) ) ) );

		if ( ! $values ) {
			wp_set_object_terms( $product_id, array(), $taxonomy, false );
			unset( $attributes[ $taxonomy ] );
			self::save_attributes( $product, $attributes );
			return;
		}

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
		wp_set_object_terms( $product_id, $term_ids, $taxonomy, false );

		$attribute_id = (int) wc_attribute_taxonomy_id_by_name( $taxonomy );
		if ( $attribute_id <= 0 ) {
			return;
		}
		$position = isset( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] instanceof WC_Product_Attribute
			? $attributes[ $taxonomy ]->get_position()
			: count( $attributes );
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( $attribute_id );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( $term_ids );
		$attribute->set_position( $position );
		$attribute->set_visible( false );
		$attribute->set_variation( false );
		$attributes[ $taxonomy ] = $attribute;
		self::save_attributes( $product, $attributes );
	}

	private static function save_attributes( WC_Product $product, array $attributes ): void {
		self::$writing = true;
		try {
			$product->set_attributes( array_values( $attributes ) );
			$product->save();
		} finally {
			self::$writing = false;
		}
	}

	private static function attribute_values( WC_Product $product, string $slug ): array {
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}
		return array_values( array_map( 'strval', (array) wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) ) ) );
	}

	private static function ensure_tag( WC_Product $product, string $tag ): void {
		wp_set_object_terms( $product->get_id(), array( $tag ), 'product_tag', true );
	}

	private static function update_audit_meta( WC_Product $product, string $family, string $title ): void {
		$id = (int) $product->get_id();
		$classification = array();
		foreach ( array( 'tipo-pieza', 'calidad', 'raza-iberica', 'alimentacion', 'con-dop', 'dop', 'origen', 'preparacion', 'rango-peso', 'curacion', 'productor' ) as $slug ) {
			$classification[ $slug ] = self::attribute_values( $product, $slug );
		}
		update_post_meta( $id, '_emdo_ham_taxonomy_snapshot', wp_json_encode( $classification, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

		$existing = json_decode( (string) get_post_meta( $id, '_emdo_ham_audit', true ), true );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		$existing['final_version'] = self::VERSION;
		$existing['finalized_at'] = current_time( 'mysql' );
		$existing['family'] = $family;
		$existing['title_evidence'] = html_entity_decode( $product->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$existing['quality_evidence'] = 'duroc' === $family ? 'Familia Duroc/Reserva del proveedor' : 'Cesáreo Selección Gourmet fuera de norma';
		$existing['classification'] = $classification;
		$existing['normalized_title'] = $title;
		update_post_meta( $id, '_emdo_ham_audit', wp_json_encode( $existing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		update_post_meta( $id, '_emdo_ham_audit_version', self::VERSION );
	}

	private static function normalize( string $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = remove_accents( strtolower( $text ) );
		$text = preg_replace( '/[^a-z0-9%+\-\.\s]/u', ' ', $text );
		return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	}
}
