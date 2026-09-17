<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consolida las fuentes internas históricas en una única fuente EMDO.
 */
final class MDO_Reviews_EMDO_Source {
	private const VERSION = '1.0.0';
	private const OPTION = 'mdo_reviews_emdo_source_version';

	public static function init(): void {
		// Desde este punto ya no se reimportan reseñas de producto/WCFM como
		// fuentes separadas. El formulario público escribe directamente en EMDO.
		remove_action( 'admin_post_mdo_reviews_import', array( 'MDO_Reviews_Scraping', 'handle_import' ) );
		add_action( 'admin_post_mdo_reviews_import', array( __CLASS__, 'handle_import' ) );
		self::run_once();
	}

	public static function run_once(): array {
		if ( self::VERSION === (string) get_option( self::OPTION, '' ) ) {
			return array( 'migrated' => 0, 'forocoches_removed' => 0 );
		}
		$stats = self::consolidate();
		if ( ! empty( $stats['ok'] ) ) {
			update_option( self::OPTION, self::VERSION, false );
		}
		return $stats;
	}

	public static function handle_import(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'mdo-supplier-sync' ) );
		}
		check_admin_referer( 'mdo_reviews_import' );
		$stats = self::consolidate();
		if ( class_exists( 'MDO_Reviews_External_Dedupe' ) ) {
			MDO_Reviews_External_Dedupe::enforce();
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'mdo-reviews',
					'mdo_notice' => 'imported',
					'found' => (int) ( $stats['migrated'] ?? 0 ),
					'saved' => (int) ( $stats['migrated'] ?? 0 ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function consolidate(): array {
		global $wpdb;
		$reviews = MDO_Database::table( 'reviews' );
		$vendors = MDO_Reviews_Vendors::table();
		$rows = $wpdb->get_results( "SELECT id,source,source_review_id FROM {$reviews} WHERE source IN ('woocommerce_product','wcfm','external','forocoches') ORDER BY id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$stats = array( 'ok' => false, 'migrated' => 0, 'forocoches_removed' => 0 );
		$wpdb->query( 'START TRANSACTION' );
		try {
			foreach ( (array) $rows as $row ) {
				$id = (int) $row->id;
				$source = (string) $row->source;
				if ( 'forocoches' === $source ) {
					if ( false === $wpdb->delete( $vendors, array( 'review_id' => $id ), array( '%d' ) ) ) {
						throw new RuntimeException( 'No se pudo limpiar la relación de una reseña de Foro Coches.' );
					}
					if ( false === $wpdb->delete( $reviews, array( 'id' => $id ), array( '%d' ) ) ) {
						throw new RuntimeException( 'No se pudo eliminar una reseña de Foro Coches.' );
					}
					++$stats['forocoches_removed'];
					continue;
				}

				$legacy_id = trim( (string) $row->source_review_id );
				if ( '' === $legacy_id ) {
					$legacy_id = (string) $id;
				}
				$prefix = 'woocommerce_product' === $source ? 'product' : ( 'wcfm' === $source ? 'wcfm' : 'legacy' );
				$new_source_id = $prefix . '-' . $legacy_id . '-' . $id;
				$new_source_key = hash( 'sha256', 'emdo|' . $new_source_id );
				$updated = $wpdb->update(
					$reviews,
					array(
						'source' => 'emdo',
						'source_review_id' => $new_source_id,
						'source_key' => $new_source_key,
						'source_url' => null,
						'updated_at' => current_time( 'mysql' ),
					),
					array( 'id' => $id )
				);
				if ( false === $updated ) {
					throw new RuntimeException( 'No se pudo convertir una reseña interna a EMDO.' );
				}
				++$stats['migrated'];
			}
			$wpdb->query( 'COMMIT' );
			$stats['ok'] = true;
			update_option( 'mdo_reviews_emdo_source_last', array( 'at' => time(), 'stats' => $stats ), false );
			return $stats;
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( '[EMDO reviews source consolidation] ' . $error->getMessage() );
			return $stats;
		}
	}
}
