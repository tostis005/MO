<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migración puntual de saneamiento del sistema de reseñas.
 *
 * Conserva los registros legacy para auditoría, pero retira de deduplicación
 * seis filas Google que se han verificado como copias 1:1 de la fuente
 * Trustindex pública. También recompone el espejo local de WooCommerce si
 * quedó incompleto durante la transición entre importadores.
 */
final class MDO_Reviews_Migration_1038 {
	private const OPTION = 'mdo_reviews_migration_1038';
	private const TARGET_IDS = array( 242, 243, 245, 247, 248, 250 );

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
				$wpdb->query( 'ROLLBACK' );
				return;
			}

			$is_legacy_google = 'google' === (string) $row->source && ! str_starts_with( (string) $row->source_review_id, 'trustindex-' );
			$is_safe_state = 'pending' === (string) $row->status && 'manual' !== (string) $row->validation_method && 'rejected' !== (string) $row->status;
			$fingerprint = (string) $row->content_fingerprint;
			if ( ! $is_legacy_google || ! $is_safe_state || '' === $fingerprint ) {
				$wpdb->query( 'ROLLBACK' );
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
			if ( 1 !== $matching ) {
				$wpdb->query( 'ROLLBACK' );
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
				array( 'id' => $id ),
				array( '%s', '%s', '%s', null, '%s' ),
				array( '%d' )
			);
			if ( false === $result ) {
				$wpdb->query( 'ROLLBACK' );
				return;
			}
			++$updated;
		}

		$duplicates = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM (SELECT content_fingerprint FROM {$table} WHERE content_fingerprint IS NOT NULL AND content_fingerprint<>'' GROUP BY content_fingerprint HAVING COUNT(*)>1) d"
		);
		if ( count( self::TARGET_IDS ) !== $updated || 0 !== $duplicates ) {
			$wpdb->query( 'ROLLBACK' );
			return;
		}
		$wpdb->query( 'COMMIT' );

		$woo_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE source=%s", 'woocommerce_product' ) );
		if ( $woo_count < 241 ) {
			try {
				$method = new ReflectionMethod( 'MDO_Reviews', 'import_woocommerce_reviews' );
				$method->setAccessible( true );
				$method->invoke( null );
			} catch ( Throwable $error ) {
				error_log( '[EMDO reviews migration 1.0.38] Woo restore: ' . $error->getMessage() );
				return;
			}
		}

		$woo_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE source=%s", 'woocommerce_product' ) );
		if ( $woo_count < 241 ) {
			return;
		}

		update_option( self::OPTION, 'done', false );
	}
}
