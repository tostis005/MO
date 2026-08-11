<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Planificador nocturno para las sincronizaciones automáticas de EMDO.
 *
 * - El dispatcher automático se ancla a las 03:00 de la zona horaria de WP.
 * - Los productores/proveedores se ordenan de forma estable por su ID de EMDO.
 * - Cada productor comienza 30 minutos después del anterior: 03:00, 03:30, 04:00…
 * - Una vez iniciado un productor, MDO_Scheduler procesa su catálogo completo
 *   con el comportamiento normal; no se espacian las fichas individuales.
 * - Las importaciones manuales conservan el comportamiento inmediato existente.
 */
final class MDO_Nightly_Scheduler {
	private const DISPATCH_HOOK = 'mdo_supplier_sync_dispatch';
	private const RUN_HOOK      = 'mdo_supplier_sync_run_supplier';
	private const GROUP         = 'mdo-supplier-sync';
	private const SLOT_SECONDS  = 30 * MINUTE_IN_SECONDS;
	private const SCHEDULE_VERSION = '3';

	public static function init(): void {
		remove_action( self::DISPATCH_HOOK, array( 'MDO_Scheduler', 'dispatch' ) );
		add_action( self::DISPATCH_HOOK, array( __CLASS__, 'dispatch' ), 10, 0 );
		self::ensure_nightly_dispatcher();
	}

	public static function dispatch(): void {
		$suppliers = MDO_Supplier_Repository::active();
		usort(
			$suppliers,
			static fn( array $a, array $b ): int => (int) ( $a['id'] ?? 0 ) <=> (int) ( $b['id'] ?? 0 )
		);

		$slot = 0;
		$base = time() + 5;

		foreach ( $suppliers as $supplier ) {
			$supplier_id = (int) ( $supplier['id'] ?? 0 );
			if ( $supplier_id <= 0 || ! self::is_due( $supplier ) || self::has_recent_running_run( $supplier_id ) ) {
				continue;
			}

			self::schedule_supplier(
				$base + ( $slot * self::SLOT_SECONDS ),
				array( $supplier_id, 'scheduled' )
			);
			$slot++;
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

	private static function schedule_supplier( int $timestamp, array $args ): void {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( $timestamp, self::RUN_HOOK, $args, self::GROUP );
			return;
		}
		wp_schedule_single_event( $timestamp, self::RUN_HOOK, $args );
	}
}
