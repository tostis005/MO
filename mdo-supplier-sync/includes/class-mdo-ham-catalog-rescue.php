<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rescata productos reales de jamón/paleta que la heurística histórica tomó
 * por accesorios por contener palabras como "cuchillo" o "jamonero".
 */
final class MDO_Ham_Catalog_Rescue {
	private const VERSION = '2026-08-12.3';
	private const REPORT_OPTION = 'mdo_ham_catalog_rescue_last_report';
	private static bool $running = false;
	private static bool $writing = false;
	private static array $queue = array();

	public static function init(): void {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'queue_product' ), 92, 2 );
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
				self::rescue_product( (int) $id );
			} catch ( Throwable $error ) {
				error_log( '[EMDO ham rescue] Producto ' . (int) $id . ': ' . $error->getMessage() );
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
				'candidates' => 0,
				'rescued' => 0,
				'cortado_cuchillo' => 0,
				'con_accesorio_regalo' => 0,
				'errors' => array(),
				'finished_at' => current_time( 'mysql' ),
			);
			foreach ( array_map( 'intval', $ids ) as $id ) {
				++$report['scanned'];
				try {
					$product = wc_get_product( $id );
					if ( ! $product || $product->is_type( 'variation' ) || ! self::is_rescue_candidate( $product ) ) {
						continue;
					}
					++$report['candidates'];
					$result = self::rescue_product( $id );
					if ( ! empty( $result['rescued'] ) ) {
						++$report['rescued'];
						if ( 'cortado_cuchillo' === $result['kind'] ) {
							++$report['cortado_cuchillo'];
						} else {
							++$report['con_accesorio_regalo'];
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

	public static function rescue_product( int $product_id ): array {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array( 'rescued' => false, 'kind' => '' );
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
			if ( ! $product ) {
				return array( 'rescued' => false, 'kind' => '' );
			}
		}
		if ( ! self::is_rescue_candidate( $product ) ) {
			return array( 'rescued' => false, 'kind' => '' );
		}

		$original_name = $product->get_name( 'edit' );
		$normalized = self::normalize( $original_name );
		$kind = str_contains( $normalized, 'cortado a cuchillo' ) || str_contains( $normalized, 'cortada a cuchillo' )
			? 'cortado_cuchillo'
			: 'con_accesorio_regalo';

		// El auditor principal sólo necesita no ver las palabras que su heurística
		// histórica asocia a accesorios. El nombre real nunca se guarda modificado.
		$filter = static function ( $name, $filtered_product ) use ( $product_id ): string {
			if ( ! $filtered_product instanceof WC_Product || (int) $filtered_product->get_id() !== $product_id ) {
				return (string) $name;
			}
			$name = preg_replace( '/\bcuchillo\b/iu', 'corte artesanal', (string) $name );
			$name = preg_replace( '/\bjamonero\b/iu', 'accesorio', (string) $name );
			return (string) $name;
		};

		add_filter( 'woocommerce_product_get_name', $filter, 999, 2 );
		try {
			MDO_Ham_Catalog_Audit::audit_product( (int) $product->get_id() );
		} finally {
			remove_filter( 'woocommerce_product_get_name', $filter, 999 );
		}

		$product = wc_get_product( $product->get_id() );
		if ( ! $product ) {
			throw new RuntimeException( 'No se pudo recargar el producto rescatado.' );
		}

		if ( 'cortado_cuchillo' === $kind ) {
			self::set_preparation( $product, array( 'Cortado a cuchillo' ) );
			wp_set_object_terms( $product->get_id(), array( 'Cortado a cuchillo' ), 'product_tag', true );
		}
		update_post_meta( $product->get_id(), '_emdo_ham_rescue_version', self::VERSION );
		update_post_meta( $product->get_id(), '_emdo_ham_rescue_kind', $kind );
		update_post_meta( $product->get_id(), '_emdo_ham_rescue_original_title', $original_name );
		wc_delete_product_transients( $product->get_id() );

		return array( 'rescued' => true, 'kind' => $kind );
	}

	private static function is_rescue_candidate( WC_Product $product ): bool {
		$title = self::normalize( $product->get_name( 'edit' ) );
		if ( ! preg_match( '/\b(?:jamon(?:es)?|paleta(?:s)?)\b/u', $title ) ) {
			return false;
		}
		return (bool) preg_match( '/\b(?:cuchillo|jamonero)\b/u', $title );
	}

	private static function set_preparation( WC_Product $product, array $values ): void {
		$taxonomy = 'pa_preparacion';
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}
		$term_ids = array();
		foreach ( $values as $value ) {
			$term = term_exists( $value, $taxonomy );
			if ( ! $term ) {
				$term = wp_insert_term( $value, $taxonomy );
			}
			if ( is_wp_error( $term ) ) {
				throw new RuntimeException( $term->get_error_message() );
			}
			$term_ids[] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
		}
		wp_set_object_terms( $product->get_id(), $term_ids, $taxonomy, false );
		$attributes = $product->get_attributes();
		$attribute_id = (int) wc_attribute_taxonomy_id_by_name( $taxonomy );
		if ( $attribute_id <= 0 ) {
			return;
		}
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( $attribute_id );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( $term_ids );
		$attribute->set_position( isset( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] instanceof WC_Product_Attribute ? $attributes[ $taxonomy ]->get_position() : count( $attributes ) );
		$attribute->set_visible( false );
		$attribute->set_variation( false );
		$attributes[ $taxonomy ] = $attribute;
		self::$writing = true;
		try {
			$product->set_attributes( array_values( $attributes ) );
			$product->save();
		} finally {
			self::$writing = false;
		}
	}

	private static function normalize( string $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = remove_accents( strtolower( $text ) );
		$text = preg_replace( '/[^a-z0-9%+\-\.\s]/u', ' ', $text );
		return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	}
}
