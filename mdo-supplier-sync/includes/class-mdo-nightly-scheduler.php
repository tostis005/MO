<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Planificador nocturno para las sincronizaciones automáticas de EMDO.
 *
 * - El dispatcher automático se ancla a las 03:00 de la zona horaria de WP.
 * - Las fichas se ordenan de forma estable por ID de producto origen existente.
 * - Las fichas nuevas van al final, ordenadas por URL.
 * - Cada ficha automática se separa 30 minutos de la siguiente, globalmente,
 *   para evitar trabajos pesados concurrentes.
 * - Las importaciones manuales conservan el comportamiento inmediato existente.
 */
final class MDO_Nightly_Scheduler {
	private const DISPATCH_HOOK = 'mdo_supplier_sync_dispatch';
	private const PRODUCT_HOOK  = 'mdo_supplier_sync_scrape_product';
	private const GROUP         = 'mdo-supplier-sync';
	private const SLOT_SECONDS  = 30 * MINUTE_IN_SECONDS;
	private const SCHEDULE_VERSION = '1';

	public static function init(): void {
		remove_action( self::DISPATCH_HOOK, array( 'MDO_Scheduler', 'dispatch' ) );
		add_action( self::DISPATCH_HOOK, array( __CLASS__, 'dispatch' ), 10, 0 );
		self::ensure_nightly_dispatcher();
	}

	public static function dispatch(): void {
		$slot = 0;
		$base = time() + 5;

		foreach ( MDO_Supplier_Repository::active() as $supplier ) {
			if ( ! self::is_due( $supplier ) || self::has_recent_running_run( (int) $supplier['id'] ) ) {
				continue;
			}

			$connector = self::connector_class( $supplier );
			if ( ! $connector ) {
				continue;
			}

			$run_id = self::create_run( (int) $supplier['id'] );
			try {
				$discovery = $connector::discover( $supplier );
				$products  = isset( $discovery['products'] ) && is_array( $discovery['products'] ) ? $discovery['products'] : array();
				$excluded  = isset( $discovery['excluded'] ) && is_array( $discovery['excluded'] ) ? $discovery['excluded'] : array();
				self::prepare_run( $run_id, (int) $supplier['id'], $products, $excluded );

				if ( ! $products ) {
					self::finish_empty_run( $run_id, (int) $supplier['id'], count( $excluded ) );
					continue;
				}

				foreach ( self::stable_product_order( (int) $supplier['id'], $products ) as $url ) {
					self::schedule_product(
						$base + ( $slot * self::SLOT_SECONDS ),
						array( (int) $supplier['id'], $run_id, $url, 'scheduled' )
					);
					$slot++;
				}
			} catch ( Throwable $error ) {
				self::log_event( $run_id, (int) $supplier['id'], 'catalog_error', 'error', $error->getMessage() );
				self::finish_error_run( $run_id, (int) $supplier['id'], $error->getMessage() );
			}
		}
	}

	private static function ensure_nightly_dispatcher(): void {
		$version = (string) get_option( 'mdo_nightly_schedule_version', '' );
		$needs_reset = self::SCHEDULE_VERSION !== $version;

		if ( $needs_reset ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::DISPATCH_HOOK, array(), self::GROUP );
			}
			wp_clear_scheduled_hook( self::DISPATCH_HOOK );
		}

		$next = self::next_three_am_timestamp();
		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( $needs_reset || ! as_has_scheduled_action( self::DISPATCH_HOOK, array(), self::GROUP ) ) {
				as_schedule_recurring_action( $next, DAY_IN_SECONDS, self::DISPATCH_HOOK, array(), self::GROUP );
			}
		} elseif ( $needs_reset || ! wp_next_scheduled( self::DISPATCH_HOOK ) ) {
			wp_schedule_event( $next, 'daily', self::DISPATCH_HOOK );
		}

		if ( $needs_reset ) {
			update_option( 'mdo_nightly_schedule_version', self::SCHEDULE_VERSION, false );
		}
	}

	private static function next_three_am_timestamp(): int {
		$now  = current_datetime();
		$next = $now->setTime( 3, 0, 0 );
		if ( $next <= $now ) {
			$next = $next->modify( '+1 day' );
		}
		return $next->getTimestamp();
	}

	private static function is_due( array $supplier ): bool {
		$frequency = (string) ( $supplier['sync_frequency'] ?? 'weekly' );
		if ( 'manual' === $frequency || 'none' === (string) ( $supplier['connector'] ?? 'none' ) ) {
			return false;
		}
		if ( 'daily' === $frequency ) {
			// El propio dispatcher solo se ejecuta una vez cada noche a las 03:00.
			return true;
		}
		$last = ! empty( $supplier['last_sync_at'] ) ? strtotime( (string) $supplier['last_sync_at'] ) : 0;
		return ! $last || ( time() - $last ) >= WEEK_IN_SECONDS;
	}

	private static function has_recent_running_run( int $supplier_id ): bool {
		global $wpdb;
		$table = MDO_Database::table( 'sync_runs' );
		$cutoff = wp_date( 'Y-m-d H:i:s', time() - ( 36 * HOUR_IN_SECONDS ) );
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE supplier_id = %d AND status = 'running' AND started_at >= %s",
				$supplier_id,
				$cutoff
			)
		);
		return $count > 0;
	}

	private static function stable_product_order( int $supplier_id, array $urls ): array {
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$ids = array();
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, source_url FROM {$table} WHERE supplier_id = %d", $supplier_id ),
			ARRAY_A
		);
		foreach ( $rows ?: array() as $row ) {
			$ids[ (string) $row['source_url'] ] = (int) $row['id'];
		}

		$urls = array_values( array_unique( array_map( 'strval', $urls ) ) );
		usort(
			$urls,
			static function ( string $a, string $b ) use ( $ids ): int {
				$a_id = $ids[ $a ] ?? PHP_INT_MAX;
				$b_id = $ids[ $b ] ?? PHP_INT_MAX;
				if ( $a_id === $b_id ) {
					return strcmp( $a, $b );
				}
				return $a_id <=> $b_id;
			}
		);
		return $urls;
	}

	private static function schedule_product( int $timestamp, array $args ): void {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( $timestamp, self::PRODUCT_HOOK, $args, self::GROUP );
			return;
		}
		wp_schedule_single_event( $timestamp, self::PRODUCT_HOOK, $args );
	}

	private static function connector_class( array $supplier ): ?string {
		return match ( (string) ( $supplier['connector'] ?? 'none' ) ) {
			'tolecarnes'      => MDO_Connector_Tolecarnes::class,
			'el-catedratico',
			'puente-robles'   => MDO_Connector_Iberico_Family::class,
			default           => null,
		};
	}

	private static function create_run( int $supplier_id ): int {
		global $wpdb;
		$wpdb->insert(
			MDO_Database::table( 'sync_runs' ),
			array(
				'supplier_id'  => $supplier_id,
				'trigger_type' => 'scheduled',
				'status'       => 'running',
				'started_at'   => current_time( 'mysql' ),
				'message'      => 'Descubriendo catálogo para la tanda nocturna…',
			)
		);
		return (int) $wpdb->insert_id;
	}

	private static function prepare_run( int $run_id, int $supplier_id, array $products, array $excluded ): void {
		global $wpdb;
		$total = count( $products ) + count( $excluded );
		$wpdb->update(
			MDO_Database::table( 'sync_runs' ),
			array(
				'products_found'    => $total,
				'products_excluded' => count( $excluded ),
				'message'           => sprintf( 'Catálogo descubierto: %d productos; fichas nocturnas separadas 30 minutos.', $total ),
			),
			array( 'id' => $run_id )
		);
		foreach ( $excluded as $url ) {
			self::log_event( $run_id, $supplier_id, 'product_excluded', 'info', 'Excluido por regla URL: ' . $url, array( 'url' => $url ) );
		}
	}

	private static function finish_empty_run( int $run_id, int $supplier_id, int $excluded ): void {
		global $wpdb;
		$message = sprintf( 'Análisis completado: 0 fichas procesables y %d excluidas.', $excluded );
		$wpdb->update(
			MDO_Database::table( 'sync_runs' ),
			array( 'status' => 'success', 'finished_at' => current_time( 'mysql' ), 'message' => $message ),
			array( 'id' => $run_id, 'status' => 'running' )
		);
		$wpdb->update(
			MDO_Database::table( 'suppliers' ),
			array( 'last_sync_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $supplier_id )
		);
	}

	private static function finish_error_run( int $run_id, int $supplier_id, string $error ): void {
		global $wpdb;
		$wpdb->update(
			MDO_Database::table( 'sync_runs' ),
			array(
				'status'       => 'error',
				'finished_at'  => current_time( 'mysql' ),
				'errors_count' => 1,
				'message'      => 'No se pudo preparar la tanda nocturna: ' . sanitize_text_field( $error ),
			),
			array( 'id' => $run_id, 'status' => 'running' )
		);
		$wpdb->update(
			MDO_Database::table( 'suppliers' ),
			array( 'last_sync_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $supplier_id )
		);
	}

	private static function log_event( int $run_id, int $supplier_id, string $type, string $severity, string $message, array $payload = array() ): void {
		global $wpdb;
		$wpdb->insert(
			MDO_Database::table( 'sync_events' ),
			array(
				'run_id'      => $run_id,
				'supplier_id' => $supplier_id,
				'event_type'  => sanitize_key( $type ),
				'severity'    => sanitize_key( $severity ),
				'message'     => $message,
				'payload'     => $payload ? wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : null,
				'created_at'  => current_time( 'mysql' ),
			)
		);
	}
}
