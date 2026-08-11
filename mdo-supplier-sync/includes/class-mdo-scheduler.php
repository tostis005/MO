<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Scheduler {
	private const DISPATCH_HOOK = 'mdo_supplier_sync_dispatch';
	private const RUN_HOOK      = 'mdo_supplier_sync_run_supplier';
	private const PRODUCT_HOOK  = 'mdo_supplier_sync_scrape_product';
	private const IMPORT_HOOK   = 'mdo_supplier_sync_import_product';

	public static function init(): void {
		add_action( self::DISPATCH_HOOK, array( __CLASS__, 'dispatch' ) );
		add_action( self::RUN_HOOK, array( __CLASS__, 'run_supplier' ), 10, 2 );
		add_action( self::PRODUCT_HOOK, array( __CLASS__, 'process_product' ), 10, 4 );
		add_action( self::IMPORT_HOOK, array( __CLASS__, 'process_import' ), 10, 1 );
		self::ensure_dispatcher();
	}

	public static function activate(): void {
		self::ensure_dispatcher();
	}

	public static function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::DISPATCH_HOOK, array(), 'mdo-supplier-sync' );
			as_unschedule_all_actions( self::RUN_HOOK, array(), 'mdo-supplier-sync' );
			as_unschedule_all_actions( self::PRODUCT_HOOK, array(), 'mdo-supplier-sync' );
			as_unschedule_all_actions( self::IMPORT_HOOK, array(), 'mdo-supplier-sync' );
		}
		wp_clear_scheduled_hook( self::DISPATCH_HOOK );
	}

	public static function dispatch(): void {
		foreach ( MDO_Supplier_Repository::active() as $supplier ) {
			if ( ! self::is_due( $supplier ) ) {
				continue;
			}
			self::enqueue_action( self::RUN_HOOK, array( (int) $supplier['id'], 'scheduled' ) );
		}
	}

	public static function queue_manual( int $supplier_id ): void {
		self::enqueue_action( self::RUN_HOOK, array( $supplier_id, 'manual' ) );
	}

	public static function queue_import( int $source_product_id ): bool {
		if ( ! MDO_Woo_Importer::mark_importing( $source_product_id ) ) {
			return false;
		}
		self::enqueue_action( self::IMPORT_HOOK, array( $source_product_id ) );
		return true;
	}

	public static function process_import( int $source_product_id ): void {
		try {
			MDO_Woo_Importer::import_source_product( $source_product_id );
		} catch ( Throwable $error ) {
			MDO_Woo_Importer::mark_import_error( $source_product_id, $error->getMessage() );
		}
	}

	public static function run_supplier( int $supplier_id, string $trigger_type = 'scheduled' ): void {
		$supplier = MDO_Supplier_Repository::find( $supplier_id );
		if ( ! $supplier ) {
			return;
		}

		$run_id = self::create_run( $supplier_id, $trigger_type );
		try {
			$connector = self::connector_class( $supplier );
			if ( ! $connector ) {
				self::finish_run( $run_id, 'warning', 'El conector seleccionado todavía no está implementado.' );
				return;
			}

			$discovery = $connector::discover( $supplier );
			$products  = $discovery['products'] ?? array();
			$excluded  = $discovery['excluded'] ?? array();
			$total     = count( $products ) + count( $excluded );

			global $wpdb;
			$wpdb->update(
				MDO_Database::table( 'sync_runs' ),
				array(
					'products_found'    => $total,
					'products_excluded' => count( $excluded ),
					'message'           => sprintf( 'Catálogo descubierto: %d productos; procesando fichas.', $total ),
				),
				array( 'id' => $run_id )
			);

			foreach ( $excluded as $url ) {
				self::log_event( $run_id, $supplier_id, 'product_excluded', 'info', 'Excluido por regla URL: ' . $url, array( 'url' => $url ) );
			}

			if ( ! $products ) {
				self::finish_if_complete( $run_id, $supplier );
				return;
			}

			foreach ( $products as $url ) {
				self::enqueue_action( self::PRODUCT_HOOK, array( $supplier_id, $run_id, $url, $trigger_type ) );
			}
		} catch ( Throwable $error ) {
			self::log_event( $run_id, $supplier_id, 'catalog_error', 'error', $error->getMessage() );
			self::finish_run( $run_id, 'error', 'No se pudo analizar el catálogo: ' . $error->getMessage(), 1 );
		}
	}

	public static function process_product( int $supplier_id, int $run_id, string $url, string $trigger_type = 'scheduled' ): void {
		$supplier = MDO_Supplier_Repository::find( $supplier_id );
		if ( ! $supplier ) {
			return;
		}
		try {
			$connector = self::connector_class( $supplier );
			if ( ! $connector ) {
				throw new RuntimeException( 'El conector seleccionado todavía no está implementado.' );
			}
			$product = 'tolecarnes' === (string) $supplier['connector']
				? MDO_Connector_Tolecarnes::scrape_product( $url )
				: MDO_Connector_Iberico_Family::scrape_product( $url, $supplier );
			$product = MDO_Text::normalize_product( $product );
			$result  = $connector::upsert_product( $supplier_id, $product );
			self::increment_run( $run_id, $result );

			if ( 'updated' === $result ) {
				try {
					MDO_Woo_Importer::sync_if_active( $supplier_id, (string) $product['source_url'] );
				} catch ( Throwable $sync_error ) {
					self::increment_run( $run_id, 'error' );
					self::log_event( $run_id, $supplier_id, 'woocommerce_sync_error', 'error', $sync_error->getMessage(), array( 'url' => $url ) );
				}
			}

			self::log_event(
				$run_id,
				$supplier_id,
				'product_' . $result,
				'excluded' === $result ? 'info' : 'success',
				sprintf( '%s: %s', ucfirst( $result ), $product['title'] ),
				array(
					'url'             => $url,
					'price'           => $product['price'],
					'stock_status'    => $product['stock_status'],
					'image_count'     => $product['image_count'],
					'variation_count' => $product['variation_count'],
				)
			);
		} catch ( Throwable $error ) {
			self::increment_run( $run_id, 'error' );
			self::log_event( $run_id, $supplier_id, 'product_error', 'error', $error->getMessage(), array( 'url' => $url ) );
		}
		self::finish_if_complete( $run_id, $supplier, $trigger_type );
	}

	private static function connector_class( array $supplier ): ?string {
		return match ( (string) ( $supplier['connector'] ?? 'none' ) ) {
			'tolecarnes'      => MDO_Connector_Tolecarnes::class,
			'el-catedratico',
			'puente-robles'   => MDO_Connector_Iberico_Family::class,
			default           => null,
		};
	}

	private static function create_run( int $supplier_id, string $trigger_type ): int {
		global $wpdb;
		$wpdb->insert(
			MDO_Database::table( 'sync_runs' ),
			array(
				'supplier_id'  => $supplier_id,
				'trigger_type' => sanitize_key( $trigger_type ),
				'status'       => 'running',
				'started_at'   => current_time( 'mysql' ),
				'message'      => 'Descubriendo catálogo…',
			)
		);
		return (int) $wpdb->insert_id;
	}

	private static function increment_run( int $run_id, string $result ): void {
		global $wpdb;
		$table  = MDO_Database::table( 'sync_runs' );
		$column = match ( $result ) {
			'new'     => 'products_new',
			'updated' => 'products_updated',
			'error'   => 'errors_count',
			default   => '',
		};
		if ( $column ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET {$column} = {$column} + 1 WHERE id = %d", $run_id ) );
		}
	}

	private static function finish_if_complete( int $run_id, array $supplier, string $trigger_type = 'scheduled' ): void {
		global $wpdb;
		$runs   = MDO_Database::table( 'sync_runs' );
		$events = MDO_Database::table( 'sync_events' );
		$run    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$runs} WHERE id = %d", $run_id ), ARRAY_A );
		if ( ! $run || 'running' !== $run['status'] ) {
			return;
		}
		$processed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$events} WHERE run_id = %d AND event_type IN ('product_new','product_updated','product_unchanged','product_excluded','product_error')",
				$run_id
			)
		);
		if ( $processed < (int) $run['products_found'] ) {
			return;
		}

		$status  = (int) $run['errors_count'] > 0 ? 'warning' : 'success';
		$message = sprintf(
			'Análisis completado: %d encontrados, %d nuevos, %d modificados, %d excluidos y %d errores.',
			(int) $run['products_found'],
			(int) $run['products_new'],
			(int) $run['products_updated'],
			(int) $run['products_excluded'],
			(int) $run['errors_count']
		);
		self::finish_run( $run_id, $status, $message );

		if ( 'scheduled' === $trigger_type && (int) $run['products_new'] > 0 && ! empty( $supplier['notification_email'] ) ) {
			wp_mail(
				(string) $supplier['notification_email'],
				sprintf( '[EMDO] %d productos nuevos en %s', (int) $run['products_new'], $supplier['name'] ),
				$message . "\n\nRevisa EMDO > Productos origen en WordPress."
			);
		}
	}

	private static function finish_run( int $run_id, string $status, string $message, int $errors = 0 ): void {
		global $wpdb;
		$runs = MDO_Database::table( 'sync_runs' );
		$run  = $wpdb->get_row( $wpdb->prepare( "SELECT supplier_id, errors_count FROM {$runs} WHERE id = %d", $run_id ), ARRAY_A );
		if ( ! $run ) {
			return;
		}
		$wpdb->update(
			$runs,
			array(
				'status'       => sanitize_key( $status ),
				'finished_at'  => current_time( 'mysql' ),
				'errors_count' => max( (int) $run['errors_count'], $errors ),
				'message'      => $message,
			),
			array( 'id' => $run_id, 'status' => 'running' )
		);
		$wpdb->update(
			MDO_Database::table( 'suppliers' ),
			array( 'last_sync_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $run['supplier_id'] )
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

	private static function enqueue_action( string $hook, array $args ): void {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( $hook, $args, 'mdo-supplier-sync' );
			return;
		}
		wp_schedule_single_event( time() + 5, $hook, $args );
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
