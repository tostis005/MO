<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deduplicación persistente entre fuentes de reseñas.
 *
 * Mantiene source_key como identidad de transporte, pero evita mostrar dos
 * veces la misma opinión cuando el mismo contenido llega por varias fuentes.
 * Nunca cambia una decisión manual.
 */
final class MDO_Reviews_External_Dedupe {
	private const VERSION = '1.0.0';
	private const VERSION_OPTION = 'mdo_reviews_external_dedupe_version';
	private const LAST_OPTION = 'mdo_reviews_external_dedupe_last';
	private const EXTERNAL_SOURCES = array( 'google', 'trustpilot' );
	private const NATIVE_SOURCES = array( 'woocommerce_product', 'wcfm' );

	public static function init(): void {
		add_action( 'updated_option', array( __CLASS__, 'on_updated_option' ), 10, 3 );
		add_action( 'added_option', array( __CLASS__, 'on_added_option' ), 10, 2 );

		if ( self::VERSION !== (string) get_option( self::VERSION_OPTION, '' ) ) {
			$stats = self::enforce();
			update_option( self::VERSION_OPTION, self::VERSION, false );
			update_option( self::LAST_OPTION, array( 'at' => time(), 'reason' => 'policy_upgrade', 'stats' => $stats ), false );
		}
	}

	public static function on_updated_option( string $option, $old_value, $value ): void {
		if ( 'mdo_reviews_last_external_import' !== $option ) {
			return;
		}
		self::enforce_after_external_import();
	}

	public static function on_added_option( string $option, $value ): void {
		if ( 'mdo_reviews_last_external_import' !== $option ) {
			return;
		}
		self::enforce_after_external_import();
	}

	private static function enforce_after_external_import(): void {
		$stats = self::enforce();
		update_option( self::LAST_OPTION, array( 'at' => time(), 'reason' => 'external_import', 'stats' => $stats ), false );
	}

	/**
	 * @return array{rejected_fingerprints_cleared:int,groups:int,rejected:int,skipped_manual:int,skipped_native_only:int}
	 */
	public static function enforce(): array {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$now = current_time( 'mysql' );
		$stats = array(
			'rejected_fingerprints_cleared' => 0,
			'groups' => 0,
			'rejected' => 0,
			'skipped_manual' => 0,
			'skipped_native_only' => 0,
		);

		// Una fila ya descartada no debe volver a participar en deduplicación.
		$cleared = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET content_fingerprint=NULL,updated_at=%s WHERE status=%s AND content_fingerprint IS NOT NULL AND content_fingerprint<>''",
				$now,
				'rejected'
			)
		);
		$stats['rejected_fingerprints_cleared'] = false === $cleared ? 0 : (int) $cleared;

		$fingerprints = $wpdb->get_col(
			"SELECT content_fingerprint FROM {$table} WHERE content_fingerprint IS NOT NULL AND content_fingerprint<>'' GROUP BY content_fingerprint HAVING COUNT(*)>1 AND COUNT(DISTINCT source)>1"
		);

		foreach ( (array) $fingerprints as $fingerprint ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE content_fingerprint=%s AND status<>%s ORDER BY id ASC",
					(string) $fingerprint,
					'rejected'
				)
			);
			if ( count( (array) $rows ) < 2 ) {
				continue;
			}

			$sources = array_values( array_unique( array_map( static fn( $row ) => (string) $row->source, (array) $rows ) ) );
			if ( count( $sources ) < 2 ) {
				continue;
			}
			++$stats['groups'];

			$native_rows = array_values( array_filter( (array) $rows, static fn( $row ) => in_array( (string) $row->source, self::NATIVE_SOURCES, true ) ) );
			$external_rows = array_values( array_filter( (array) $rows, static fn( $row ) => in_array( (string) $row->source, self::EXTERNAL_SOURCES, true ) ) );
			if ( ! $external_rows ) {
				++$stats['skipped_native_only'];
				continue;
			}

			// Nunca anulamos automáticamente una decisión manual. Si hay una fila
			// nativa y una externa manual idéntica, el grupo queda para moderación.
			$manual_external = array_values( array_filter( $external_rows, static fn( $row ) => 'manual' === (string) $row->validation_method ) );
			if ( $native_rows && $manual_external ) {
				++$stats['skipped_manual'];
				continue;
			}

			$canonical = null;
			if ( $native_rows ) {
				$canonical = self::best_row( $native_rows );
			} else {
				$manual_rows = array_values( array_filter( $external_rows, static fn( $row ) => 'manual' === (string) $row->validation_method ) );
				$canonical = $manual_rows ? self::best_row( $manual_rows ) : self::best_row( $external_rows );
			}
			if ( ! $canonical ) {
				continue;
			}

			foreach ( $external_rows as $row ) {
				if ( (int) $row->id === (int) $canonical->id ) {
					continue;
				}
				if ( 'manual' === (string) $row->validation_method ) {
					++$stats['skipped_manual'];
					continue;
				}
				$reason = sprintf(
					'Duplicado exacto entre fuentes; se conserva %s #%d como registro canónico.',
					self::source_label( (string) $canonical->source ),
					(int) $canonical->id
				);
				$result = $wpdb->update(
					$table,
					array(
						'status' => 'rejected',
						'validation_method' => 'automatic',
						'assignment_reason' => $reason,
						'content_fingerprint' => null,
						'updated_at' => $now,
					),
					array( 'id' => (int) $row->id )
				);
				if ( false !== $result ) {
					++$stats['rejected'];
				}
			}
		}

		return $stats;
	}

	private static function best_row( array $rows ) {
		usort(
			$rows,
			static function ( $a, $b ): int {
				$score_a = self::source_priority( (string) $a->source );
				$score_b = self::source_priority( (string) $b->source );
				if ( $score_a !== $score_b ) {
					return $score_b <=> $score_a;
				}
				if ( 'validated' === (string) $a->status xor 'validated' === (string) $b->status ) {
					return 'validated' === (string) $a->status ? -1 : 1;
				}
				return (int) $a->id <=> (int) $b->id;
			}
		);
		return $rows[0] ?? null;
	}

	private static function source_priority( string $source ): int {
		$priorities = array(
			'wcfm' => 400,
			'woocommerce_product' => 350,
			'trustpilot' => 200,
			'google' => 100,
		);
		return $priorities[ $source ] ?? 0;
	}

	private static function source_label( string $source ): string {
		$labels = array(
			'wcfm' => 'WCFM',
			'woocommerce_product' => 'WooCommerce',
			'trustpilot' => 'Trustpilot',
			'google' => 'Google',
		);
		return $labels[ $source ] ?? $source;
	}
}
