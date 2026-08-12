<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Planificador nocturno para las sincronizaciones automáticas de EMDO.
 *
 * El dispatcher se ancla a las 03:00 de la zona horaria de WordPress y cada
 * productor se separa 30 minutos. Un watchdog independiente evita que una
 * ejecución rota permanezca indefinidamente en estado running.
 */
final class MDO_Nightly_Scheduler {
	private const DISPATCH_HOOK    = 'mdo_supplier_sync_dispatch';
	private const RUN_HOOK         = 'mdo_supplier_sync_run_supplier';
	private const WATCHDOG_HOOK    = 'mdo_supplier_sync_watchdog';
	private const GROUP            = 'mdo-supplier-sync';
	private const SLOT_SECONDS     = 30 * MINUTE_IN_SECONDS;
	private const WATCHDOG_SECONDS = 15 * MINUTE_IN_SECONDS;
	private const STALE_SECONDS    = 2 * HOUR_IN_SECONDS;
	private const SCHEDULE_VERSION = '5';

	public static function init(): void {
		remove_action( self::DISPATCH_HOOK, array( 'MDO_Scheduler', 'dispatch' ) );
		add_action( self::DISPATCH_HOOK, array( __CLASS__, 'dispatch' ), 10, 0 );
		add_action( self::WATCHDOG_HOOK, array( __CLASS__, 'recover_stale_runs' ), 10, 0 );

		/*
		 * Action Scheduler puede exponer sus funciones antes de inicializar el
		 * almacén. Esperamos a action_scheduler_init para inspeccionar y recrear
		 * realmente las acciones recurrentes.
		 */
		if ( did_action( 'action_scheduler_init' ) ) {
			self::ensure_nightly_dispatcher();
		} elseif ( function_exists( 'as_schedule_recurring_action' ) ) {
			add_action( 'action_scheduler_init', array( __CLASS__, 'ensure_nightly_dispatcher' ), 20 );
		} else {
			add_action( 'init', array( __CLASS__, 'ensure_nightly_dispatcher' ), 100 );
		}
	}

	public static function dispatch(): void {
		self::recover_stale_runs();

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

	public static function ensure_nightly_dispatcher(): void {
		$version       = (string) get_option( 'mdo_nightly_schedule_version', '' );
		$existing_next = self::existing_dispatch_timestamp();
		$wrong_slot    = $existing_next > 0 && '03:00' !== wp_date( 'H:i', $existing_next );
		$needs_reset   = self::SCHEDULE_VERSION !== $version || $wrong_slot;

		if ( $needs_reset ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::DISPATCH_HOOK, array(), self::GROUP );
				as_unschedule_all_actions( self::WATCHDOG_HOOK, array(), self::GROUP );
			}
			wp_clear_scheduled_hook( self::DISPATCH_HOOK );
			wp_clear_scheduled_hook( self::WATCHDOG_HOOK );
			$existing_next = 0;
		}

		$next = self::next_three_am_timestamp();
		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( $existing_next <= 0 && ! as_has_scheduled_action( self::DISPATCH_HOOK, array(), self::GROUP ) ) {
				as_schedule_recurring_action( $next, DAY_IN_SECONDS, self::DISPATCH_HOOK, array(), self::GROUP );
			}
			if ( ! as_has_scheduled_action( self::WATCHDOG_HOOK, array(), self::GROUP ) ) {
				as_schedule_recurring_action( time() + 60, self::WATCHDOG_SECONDS, self::WATCHDOG_HOOK, array(), self::GROUP );
			}
		} else {
			if ( $existing_next <= 0 && ! wp_next_scheduled( self::DISPATCH_HOOK ) ) {
				wp_schedule_event( $next, 'daily', self::DISPATCH_HOOK );
			}
			if ( ! wp_next_scheduled( self::WATCHDOG_HOOK ) ) {
				wp_schedule_event( time() + 60, 'hourly', self::WATCHDOG_HOOK );
			}
		}

		if ( $needs_reset || self::SCHEDULE_VERSION !== $version ) {
			update_option( 'mdo_nightly_schedule_version', self::SCHEDULE_VERSION, false );
		}
	}

	/**
	 * Cierra ejecuciones que llevan demasiado tiempo abiertas. Con el runner de
	 * servidor una sincronización normal avanza continuamente; dos horas sin
	 * terminar se consideran una ejecución interrumpida y no debe bloquear el
	 * proveedor durante 36 horas.
	 */
	public static function recover_stale_runs(): void {
		global $wpdb;
		$runs   = MDO_Database::table( 'sync_runs' );
		$events = MDO_Database::table( 'sync_events' );
		$cutoff = wp_date( 'Y-m-d H:i:s', time() - self::STALE_SECONDS );
		$stale  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, supplier_id, errors_count FROM {$runs} WHERE status = 'running' AND started_at < %s ORDER BY id ASC",
				$cutoff
			),
			ARRAY_A
		);
		foreach ( $stale ?: array() as $run ) {
			$run_id      = (int) $run['id'];
			$supplier_id = (int) $run['supplier_id'];
			$message     = 'Ejecución cerrada automáticamente por el watchdog tras superar 2 horas en estado running. Puede volver a lanzarse con seguridad.';
			$updated = $wpdb->update(
				$runs,
				array(
					'status'       => 'warning',
					'finished_at'  => current_time( 'mysql' ),
					'errors_count' => max( 1, (int) $run['errors_count'] ),
					'message'      => $message,
				),
				array( 'id' => $run_id, 'status' => 'running' )
			);
			if ( $updated ) {
				$wpdb->insert(
					$events,
					array(
						'run_id'      => $run_id,
						'supplier_id' => $supplier_id,
						'event_type'  => 'run_watchdog_timeout',
						'severity'    => 'error',
						'message'     => $message,
						'payload'     => null,
						'created_at'  => current_time( 'mysql' ),
					)
				);
			}
		}
	}

	private static function existing_dispatch_timestamp(): int {
		if ( function_exists( 'as_next_scheduled_action' ) ) {
			$next = as_next_scheduled_action( self::DISPATCH_HOOK, array(), self::GROUP );
			if ( is_numeric( $next ) && (int) $next > 0 ) {
				return (int) $next;
			}
		}
		$next = wp_next_scheduled( self::DISPATCH_HOOK );
		return $next ? (int) $next : 0;
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
		$table  = MDO_Database::table( 'sync_runs' );
		$cutoff = wp_date( 'Y-m-d H:i:s', time() - self::STALE_SECONDS );
		$count  = (int) $wpdb->get_var(
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
