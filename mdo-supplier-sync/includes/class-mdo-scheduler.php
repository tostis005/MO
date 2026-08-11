<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Scheduler {
	private const DISPATCH_HOOK = 'mdo_supplier_sync_dispatch';

	public static function init(): void {
		add_action( self::DISPATCH_HOOK, array( __CLASS__, 'dispatch' ) );
		add_action( 'mdo_supplier_sync_run_supplier', array( __CLASS__, 'run_supplier' ), 10, 2 );
		self::ensure_dispatcher();
	}

	public static function activate(): void {
		self::ensure_dispatcher();
	}

	public static function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::DISPATCH_HOOK, array(), 'mdo-supplier-sync' );
			as_unschedule_all_actions( 'mdo_supplier_sync_run_supplier', array(), 'mdo-supplier-sync' );
		}
		wp_clear_scheduled_hook( self::DISPATCH_HOOK );
	}

	public static function dispatch(): void {
		foreach ( MDO_Supplier_Repository::active() as $supplier ) {
			if ( ! self::is_due( $supplier ) ) {
				continue;
			}

			$args = array( (int) $supplier['id'], 'scheduled' );
			if ( function_exists( 'as_enqueue_async_action' ) ) {
				as_enqueue_async_action( 'mdo_supplier_sync_run_supplier', $args, 'mdo-supplier-sync' );
			} else {
				wp_schedule_single_event( time() + 30, 'mdo_supplier_sync_run_supplier', $args );
			}
		}
	}

	public static function run_supplier( int $supplier_id, string $trigger_type = 'scheduled' ): void {
		$supplier = MDO_Supplier_Repository::find( $supplier_id );
		if ( ! $supplier ) {
			return;
		}

		global $wpdb;
		$now  = current_time( 'mysql' );
		$runs = MDO_Database::table( 'sync_runs' );
		$wpdb->insert(
			$runs,
			array(
				'supplier_id' => $supplier_id,
				'trigger_type' => sanitize_key( $trigger_type ),
				'status' => 'warning',
				'started_at' => $now,
				'finished_at' => $now,
				'message' => 'Infraestructura lista. Falta conectar el scraper específico del proveedor antes de modificar productos.',
			)
		);
		$wpdb->update( MDO_Database::table( 'suppliers' ), array( 'last_sync_at' => $now, 'updated_at' => $now ), array( 'id' => $supplier_id ) );
	}

	public static function queue_manual( int $supplier_id ): void {
		$args = array( $supplier_id, 'manual' );
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'mdo_supplier_sync_run_supplier', $args, 'mdo-supplier-sync' );
		} else {
			wp_schedule_single_event( time() + 5, 'mdo_supplier_sync_run_supplier', $args );
		}
	}

	private static function ensure_dispatcher(): void {
		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( ! as_has_scheduled_action( self::DISPATCH_HOOK, array(), 'mdo-supplier-sync' ) ) {
				as_schedule_recurring_action( time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, self::DISPATCH_HOOK, array(), 'mdo-supplier-sync' );
			}
			return;
		}

		if ( ! wp_next_scheduled( self::DISPATCH_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::DISPATCH_HOOK );
		}
	}

	private static function is_due( array $supplier ): bool {
		$frequency = (string) ( $supplier['sync_frequency'] ?? 'weekly' );
		if ( 'manual' === $frequency || 'none' === (string) ( $supplier['connector'] ?? 'none' ) ) {
			return false;
		}

		$last = ! empty( $supplier['last_sync_at'] ) ? strtotime( (string) $supplier['last_sync_at'] ) : 0;
		$age  = $last ? time() - $last : PHP_INT_MAX;
		return 'daily' === $frequency ? $age >= DAY_IN_SECONDS : $age >= WEEK_IN_SECONDS;
	}
}
