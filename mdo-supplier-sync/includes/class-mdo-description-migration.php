<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reparación puntual de descripciones importadas antes de EMDO 0.6.2.
 *
 * Solo actúa sobre productos que tienen wc_product_id en la tabla de productos
 * origen de EMDO. Para minimizar efectos secundarios actualiza directamente:
 * - wp_posts.post_content del producto WooCommerce.
 * - source_payload.description de la fila EMDO correspondiente.
 *
 * No ejecuta wp_update_post(), save() de WooCommerce ni hooks de producto, por lo
 * que no modifica precio, stock, imágenes, título, autor/vendedor ni estado.
 */
final class MDO_Description_Migration {
	private const OPTION_VERSION = 'mdo_description_repair_version';
	private const OPTION_STATS   = 'mdo_description_repair_stats';
	private const VERSION        = '1';

	public static function run_once(): void {
		if ( self::VERSION === (string) get_option( self::OPTION_VERSION, '' ) ) {
			return;
		}
		if ( ! class_exists( 'MDO_Database' ) || ! class_exists( 'MDO_Text' ) ) {
			return;
		}

		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$rows  = $wpdb->get_results(
			"SELECT id, wc_product_id, source_payload FROM {$table} WHERE wc_product_id IS NOT NULL AND wc_product_id > 0 AND source_payload IS NOT NULL ORDER BY id ASC",
			ARRAY_A
		) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$stats = array(
			'scanned'         => 0,
			'woo_changed'     => 0,
			'payload_changed' => 0,
			'unchanged'       => 0,
			'missing_posts'   => 0,
			'errors'          => 0,
			'completed_at'    => current_time( 'mysql', true ),
		);

		foreach ( $rows as $row ) {
			$stats['scanned']++;
			$source_id  = absint( $row['id'] ?? 0 );
			$product_id = absint( $row['wc_product_id'] ?? 0 );
			$payload    = json_decode( (string) ( $row['source_payload'] ?? '' ), true );
			$payload    = is_array( $payload ) ? $payload : array();
			$changed    = false;

			try {
				if ( array_key_exists( 'description', $payload ) ) {
					$original_payload_description = (string) $payload['description'];
					$clean_payload_description    = MDO_Text::normalize_description( $original_payload_description );
					if ( $clean_payload_description !== $original_payload_description ) {
						$payload['description'] = $clean_payload_description;
						$updated = $wpdb->update(
							$table,
							array( 'source_payload' => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
							array( 'id' => $source_id ),
							array( '%s' ),
							array( '%d' )
						);
						if ( false === $updated ) {
							throw new RuntimeException( 'No se pudo actualizar source_payload.' );
						}
						$stats['payload_changed']++;
						$changed = true;
					}
				}

				$post = $wpdb->get_row(
					$wpdb->prepare( "SELECT ID, post_content FROM {$wpdb->posts} WHERE ID = %d LIMIT 1", $product_id ),
					ARRAY_A
				);
				if ( ! $post ) {
					$stats['missing_posts']++;
				} else {
					$original_content = (string) $post['post_content'];
					$clean_content    = MDO_Text::normalize_description( $original_content );
					if ( $clean_content !== $original_content ) {
						$updated = $wpdb->update(
							$wpdb->posts,
							array( 'post_content' => $clean_content ),
							array( 'ID' => $product_id ),
							array( '%s' ),
							array( '%d' )
						);
						if ( false === $updated ) {
							throw new RuntimeException( 'No se pudo actualizar post_content.' );
						}
						clean_post_cache( $product_id );
						$stats['woo_changed']++;
						$changed = true;
					}
				}

				if ( ! $changed ) {
					$stats['unchanged']++;
				}
			} catch ( Throwable $error ) {
				$stats['errors']++;
			}
		}

		$stats['completed_at'] = current_time( 'mysql', true );
		update_option( self::OPTION_STATS, $stats, false );

		// Solo marcamos la migración como completada si no hubo errores.
		if ( 0 === (int) $stats['errors'] ) {
			update_option( self::OPTION_VERSION, self::VERSION, false );
		}
	}
}
