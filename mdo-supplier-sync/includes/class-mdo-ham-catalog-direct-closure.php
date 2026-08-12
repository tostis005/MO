<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Última escritura canónica de Jamones y Paletas.
 *
 * A diferencia de las capas de clasificación, esta clase no llama a
 * WC_Product::save(): sustituye únicamente las relaciones de términos ya
 * existentes y actualiza el snapshot. Así evita iniciar un nuevo ciclo de
 * clasificación que pudiera volver a sobrescribir el valor canónico.
 */
final class MDO_Ham_Catalog_Direct_Closure {
	private const VERSION = '2026-08-12.1';
	private const REPORT_OPTION = 'mdo_ham_catalog_direct_closure_last_report';
	private static bool $running = false;
	private static array $queue = array();

	public static function init(): void {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'queue_product' ), 200, 2 );
		add_action( 'shutdown', array( __CLASS__, 'flush_queue' ), PHP_INT_MAX );
	}

	public static function queue_product( $product, $data_store = null ): void {
		unset( $data_store );
		if ( self::$running || ! $product instanceof WC_Product ) {
			return;
		}
		$id = $product->is_type( 'variation' ) ? (int) $product->get_parent_id() : (int) $product->get_id();
		if ( $id > 0 ) {
			self::$queue[ $id ] = true;
		}
	}

	public static function flush_queue(): void {
		if ( self::$running || ! self::$queue ) {
			return;
		}
		$ids = array_keys( self::$queue );
		self::$queue = array();
		foreach ( $ids as $id ) {
			try {
				self::close_product( (int) $id );
			} catch ( Throwable $error ) {
				error_log( '[EMDO ham direct closure] Producto ' . (int) $id . ': ' . $error->getMessage() );
			}
		}
	}

	public static function apply_catalog(): array {
		self::$running = true;
		try {
			$ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'future' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);
			$report = array(
				'status' => 'completed',
				'version' => self::VERSION,
				'scanned' => 0,
				'eligible' => 0,
				'changed' => 0,
				'producer_overrides' => 0,
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
					$result = self::close_product( $id );
					if ( ! empty( $result['changed'] ) ) {
						++$report['changed'];
					}
					if ( ! empty( $result['producer'] ) ) {
						++$report['producer_overrides'];
					}
					if ( ! empty( $result['weight'] ) ) {
						++$report['weight_overrides'];
					}
				} catch ( Throwable $error ) {
					$report['errors'][] = array( 'product_id' => $id, 'message' => $error->getMessage() );
				}
			}
			if ( $report['errors'] ) {
				$report['status'] = 'completed_with_errors';
			}
			update_option( self::REPORT_OPTION, $report, false );
			return $report;
		} finally {
			self::$running = false;
			self::$queue = array();
		}
	}

	public static function close_product( int $product_id ): array {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array( 'changed' => false, 'producer' => false, 'weight' => false );
		}
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
			if ( ! $product ) {
				return array( 'changed' => false, 'producer' => false, 'weight' => false );
			}
		}
		if ( ! self::is_eligible( $product ) ) {
			return array( 'changed' => false, 'producer' => false, 'weight' => false );
		}

		$id = (int) $product->get_id();
		$producer = self::canonical_producer( $product );
		$producer_changed = false;
		$weight_changed = false;

		if ( $producer ) {
			$producer_changed = self::replace_terms( $id, 'productor', array( $producer ) );
		}

		$source_path = self::source_path( self::source_url( $id ) );
		if ( 'El Catedrático' === $producer && 'paleta-de-cebo-de-campo-iberica-50-raza-iberica' === $source_path ) {
			$weight_changed = self::replace_terms( $id, 'rango-peso', array( '4,5–5,5 kg', '5,5–6,5 kg' ) );
		}

		$changed = $producer_changed || $weight_changed;
		if ( $changed ) {
			self::refresh_snapshot( $id );
			update_post_meta( $id, '_emdo_ham_direct_closure_version', self::VERSION );
			clean_post_cache( $id );
			wc_delete_product_transients( $id );
		}

		return array(
			'changed' => $changed,
			'producer' => $producer_changed,
			'weight' => $weight_changed,
		);
	}

	private static function replace_terms( int $product_id, string $attribute_slug, array $values ): bool {
		$taxonomy = wc_attribute_taxonomy_name( $attribute_slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}
		$values = array_values( array_unique( array_filter( array_map( 'strval', $values ) ) ) );
		$current = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
		$current = is_wp_error( $current ) ? array() : array_values( array_map( 'strval', $current ) );
		$compare_current = $current;
		$compare_wanted = $values;
		sort( $compare_current, SORT_NATURAL | SORT_FLAG_CASE );
		sort( $compare_wanted, SORT_NATURAL | SORT_FLAG_CASE );
		if ( $compare_current === $compare_wanted ) {
			return false;
		}

		$term_ids = array();
		foreach ( $values as $value ) {
			$term = term_exists( $value, $taxonomy );
			if ( ! $term ) {
				$term = wp_insert_term( $value, $taxonomy );
			}
			if ( is_wp_error( $term ) ) {
				throw new RuntimeException( 'No se pudo crear el término «' . $value . '»: ' . $term->get_error_message() );
			}
			$term_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		}

		$result = wp_set_object_terms( $product_id, $term_ids, $taxonomy, false );
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( 'No se pudo fijar ' . $taxonomy . ': ' . $result->get_error_message() );
		}
		return true;
	}

	private static function is_eligible( WC_Product $product ): bool {
		$id = (int) $product->get_id();
		if ( get_post_meta( $id, '_emdo_ham_audit', true ) || get_post_meta( $id, '_emdo_ham_rescue_version', true ) || get_post_meta( $id, '_emdo_ham_precision_version', true ) ) {
			return true;
		}
		$terms = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) );
		return ! is_wp_error( $terms ) && in_array( 'jamones-paletas', (array) $terms, true );
	}

	private static function canonical_producer( WC_Product $product ): string {
		$candidates = array();
		if ( taxonomy_exists( 'pa_productor' ) ) {
			$terms = wp_get_post_terms( $product->get_id(), 'pa_productor', array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $terms ) ) {
				$candidates = array_merge( $candidates, array_map( 'strval', $terms ) );
			}
		}
		$user = get_user_by( 'id', (int) get_post_field( 'post_author', $product->get_id() ) );
		if ( $user ) {
			$candidates[] = (string) $user->display_name;
			$candidates[] = (string) $user->user_login;
			$candidates[] = (string) get_user_meta( $user->ID, 'nickname', true );
			$settings = get_user_meta( $user->ID, 'wcfmmp_profile_settings', true );
			if ( is_array( $settings ) && ! empty( $settings['store_name'] ) ) {
				$candidates[] = (string) $settings['store_name'];
			}
		}
		foreach ( $candidates as $candidate ) {
			$norm = self::normalize_name( $candidate );
			if ( str_contains( $norm, 'puente robles' ) || str_contains( $norm, 'puenterobles' ) ) {
				return 'Puente Robles';
			}
			if ( str_contains( $norm, 'el catedratico' ) || str_contains( $norm, 'elcatedratico' ) ) {
				return 'El Catedrático';
			}
			if ( str_contains( $norm, 'hidalgo de la jara' ) || str_contains( $norm, 'hidalgodelajara' ) ) {
				return 'Hidalgo de la Jara';
			}
		}
		return '';
	}

	private static function normalize_name( string $value ): string {
		$value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = strtolower( remove_accents( trim( $value ) ) );
		$value = preg_replace( '/[^a-z0-9]+/u', ' ', $value );
		return trim( preg_replace( '/\s+/u', ' ', (string) $value ) );
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
		$table = $wpdb->prefix . 'mdo_source_products';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			return (string) $wpdb->get_var( $wpdb->prepare( "SELECT source_url FROM {$table} WHERE wc_product_id = %d ORDER BY id DESC LIMIT 1", $product_id ) );
		}
		return '';
	}

	private static function source_path( string $url ): string {
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		if ( str_starts_with( $path, 'es/' ) ) {
			$path = substr( $path, 3 );
		}
		return sanitize_title( $path );
	}

	private static function refresh_snapshot( int $product_id ): void {
		$classification = array();
		foreach ( array( 'tipo-pieza', 'calidad', 'raza-iberica', 'alimentacion', 'con-dop', 'dop', 'origen', 'preparacion', 'rango-peso', 'curacion', 'productor' ) as $slug ) {
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			$terms = taxonomy_exists( $taxonomy ) ? wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) ) : array();
			$classification[ $slug ] = is_wp_error( $terms ) ? array() : array_values( array_map( 'strval', $terms ) );
		}
		update_post_meta( $product_id, '_emdo_ham_taxonomy_snapshot', wp_json_encode( $classification, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}
}
