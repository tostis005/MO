<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migración puntual de saneamiento del sistema de reseñas.
 *
 * Conserva los registros legacy para auditoría, pero retira de deduplicación
 * seis filas Google verificadas como copias 1:1 de la fuente Trustindex
 * pública. También recompone el espejo local de WooCommerce si quedó
 * incompleto durante la transición entre importadores.
 */
final class MDO_Reviews_Migration_1038 {
	private const OPTION = 'mdo_reviews_migration_1038';
	private const TARGET_IDS = array( 242, 243, 245, 247, 248, 250 );
	private const STAR_ONLY_ID = 247;
	private const WOO_MISSING_SOURCE_ID = '8352';

	public static function run_once(): void {
		if ( 'done' === (string) get_option( self::OPTION, '' ) ) {
			return;
		}

		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$wpdb->query( 'START TRANSACTION' );

		$updated = 0;
		foreach ( self::TARGET_IDS as $id ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $id ) );
			if ( ! $row ) {
				self::rollback( 'legacy_row_missing_' . $id );
				return;
			}

			$is_legacy_google = 'google' === (string) $row->source && ! str_starts_with( (string) $row->source_review_id, 'trustindex-' );
			$is_safe_state = 'pending' === (string) $row->status && 'manual' !== (string) $row->validation_method;
			if ( ! $is_legacy_google || ! $is_safe_state ) {
				self::rollback( 'legacy_row_not_safe_' . $id );
				return;
			}

			if ( self::STAR_ONLY_ID === $id ) {
				if ( '' !== trim( (string) $row->review_text ) ) {
					self::rollback( 'star_only_row_has_text' );
					return;
				}
				$date = substr( (string) $row->review_date, 0, 10 );
				$matching = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table} WHERE id<>%d AND source=%s AND source_review_id LIKE %s AND author_name=%s AND rating=%d AND DATE(review_date)=%s AND (review_text IS NULL OR TRIM(review_text)='')",
						$id,
						'google',
						'trustindex-%',
						(string) $row->author_name,
						(int) $row->rating,
						$date
					)
				);
			} else {
				$fingerprint = (string) $row->content_fingerprint;
				if ( '' === $fingerprint ) {
					self::rollback( 'legacy_fingerprint_missing_' . $id );
					return;
				}
				$matching = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table} WHERE id<>%d AND source=%s AND source_review_id LIKE %s AND content_fingerprint=%s",
						$id,
						'google',
						'trustindex-%',
						$fingerprint
					)
				);
			}

			if ( 1 !== $matching ) {
				self::rollback( 'canonical_match_count_' . $id . '_' . $matching );
				return;
			}

			$result = $wpdb->update(
				$table,
				array(
					'status' => 'rejected',
					'validation_method' => 'automatic',
					'assignment_reason' => 'Duplicado legacy de la reseña Google canónica importada desde Trustindex.',
					'content_fingerprint' => null,
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $id )
			);
			if ( false === $result ) {
				self::rollback( 'legacy_update_failed_' . $id );
				return;
			}
			++$updated;
		}

		$duplicates = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM (SELECT content_fingerprint FROM {$table} WHERE content_fingerprint IS NOT NULL AND content_fingerprint<>'' GROUP BY content_fingerprint HAVING COUNT(*)>1) d"
		);
		if ( count( self::TARGET_IDS ) !== $updated || 0 !== $duplicates ) {
			self::rollback( 'post_google_cleanup_duplicates_' . $duplicates );
			return;
		}

		$woo_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE source=%s", 'woocommerce_product' ) );
		if ( $woo_count < 241 ) {
			try {
				$method = new ReflectionMethod( 'MDO_Reviews', 'import_woocommerce_reviews' );
				$method->setAccessible( true );
				$method->invoke( null );
			} catch ( Throwable $error ) {
				self::rollback( 'woo_restore_exception_' . $error->getMessage() );
				return;
			}
		}

		$woo_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE source=%s", 'woocommerce_product' ) );
		$woo_missing_restored = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE source=%s AND source_review_id=%s",
				'woocommerce_product',
				self::WOO_MISSING_SOURCE_ID
			)
		);
		if ( $woo_count < 241 || 1 !== $woo_missing_restored ) {
			self::rollback( 'woo_restore_incomplete_' . $woo_count . '_' . $woo_missing_restored );
			return;
		}

		$wpdb->query( 'COMMIT' );
		update_option( self::OPTION, 'done', false );
	}

	private static function rollback( string $reason ): void {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
		error_log( '[EMDO reviews migration 1.0.38] Rollback: ' . $reason );
	}
}
