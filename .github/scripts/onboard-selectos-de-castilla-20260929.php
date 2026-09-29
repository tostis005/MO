<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

global $wpdb;

$phase = getenv( 'SELECTOS_PHASE' ) ?: 'setup';
$supplier_table = MDO_Database::table( 'suppliers' );
$source_table   = MDO_Database::table( 'source_products' );
$runs_table     = MDO_Database::table( 'sync_runs' );

$find_supplier = static function () use ( $wpdb, $supplier_table ): ?array {
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$supplier_table}
			 WHERE LOWER(name) = LOWER(%s)
			    OR code = %s
			    OR source_url LIKE %s
			 ORDER BY id DESC
			 LIMIT 1",
			'Selectos de Castilla',
			'selectos-de-castilla',
			'%selectosdecastilla.com%'
		),
		ARRAY_A
	);
	return $row ?: null;
};

$supplier = $find_supplier();
if ( ! $supplier ) {
	fwrite( STDERR, "SUPPLIER_NOT_FOUND\n" );
	exit( 21 );
}

$supplier_id = (int) $supplier['id'];
$catalog_url = 'https://tienda.selectosdecastilla.com/productos-2?order=product.position.asc&resultsPerPage=9999999';

if ( 'setup' === $phase ) {
	$wpdb->update(
		$supplier_table,
		array(
			'source_url'     => $catalog_url,
			'connector'      => 'selectos-de-castilla',
			'sync_frequency' => 'daily',
			'active'         => 1,
			'updated_at'     => current_time( 'mysql' ),
		),
		array( 'id' => $supplier_id )
	);
	$supplier = MDO_Supplier_Repository::find( $supplier_id );
	if ( ! $supplier || empty( $supplier['vendor_user_id'] ) ) {
		fwrite( STDERR, "SUPPLIER_VENDOR_MISSING id={$supplier_id}\n" );
		exit( 22 );
	}

	MDO_Nightly_Scheduler::ensure_nightly_dispatcher();
	$discovery = MDO_Connector_Iberico_Family::discover( $supplier );
	$products  = array_values( (array) ( $discovery['products'] ?? array() ) );
	if ( count( $products ) < 150 ) {
		fwrite( STDERR, 'DISCOVERY_TOO_SMALL count=' . count( $products ) . "\n" );
		exit( 23 );
	}

	$sample = MDO_Text::normalize_product(
		MDO_Connector_Iberico_Family::scrape_product( (string) $products[0], $supplier )
	);
	if ( empty( $sample['title'] ) || ! array_key_exists( 'price', $sample ) ) {
		fwrite( STDERR, "SAMPLE_SCRAPE_INVALID\n" );
		exit( 24 );
	}

	$next = function_exists( 'as_next_scheduled_action' )
		? as_next_scheduled_action( 'mdo_supplier_sync_dispatch', array(), 'mdo-supplier-sync' )
		: wp_next_scheduled( 'mdo_supplier_sync_dispatch' );

	echo 'SETUP=' . wp_json_encode(
		array(
			'supplier_id'   => $supplier_id,
			'vendor_user_id'=> (int) $supplier['vendor_user_id'],
			'connector'     => $supplier['connector'],
			'frequency'     => $supplier['sync_frequency'],
			'active'        => (int) $supplier['active'],
			'discovered'    => count( $products ),
			'sample'        => array(
				'url'    => (string) $products[0],
				'title'  => $sample['title'],
				'price'  => $sample['price'],
				'stock'  => $sample['stock_status'] ?? null,
				'images' => $sample['image_count'] ?? null,
			),
			'next_dispatch' => $next ? wp_date( 'c', (int) $next ) : null,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	) . "\n";

	MDO_Scheduler::run_supplier( $supplier_id, 'manual' );
	echo "SUPPLIER_ID={$supplier_id}\n";
	exit( 0 );
}

if ( 'scrape-report' === $phase ) {
	$run = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id,status,products_found,products_new,products_updated,products_excluded,errors_count,message,started_at,finished_at
			 FROM {$runs_table}
			 WHERE supplier_id = %d
			 ORDER BY id DESC
			 LIMIT 1",
			$supplier_id
		),
		ARRAY_A
	);
	$counts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT status, COUNT(*) n FROM {$source_table} WHERE supplier_id = %d GROUP BY status ORDER BY status",
			$supplier_id
		),
		ARRAY_A
	);
	echo 'SCRAPE_REPORT=' . wp_json_encode(
		array( 'run' => $run, 'source_counts' => $counts ),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	) . "\n";
	if ( ! $run || empty( $run['finished_at'] ) || ! in_array( $run['status'], array( 'success', 'warning' ), true ) || (int) $run['products_found'] < 150 ) {
		exit( 31 );
	}
	exit( 0 );
}

if ( 'queue-import' === $phase ) {
	$ids = array_map(
		'intval',
		$wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$source_table} WHERE supplier_id = %d AND status = 'pending' ORDER BY id",
				$supplier_id
			)
		)
	);
	$queued = 0;
	foreach ( $ids as $id ) {
		if ( MDO_Scheduler::queue_import( $id ) ) {
			$queued++;
		}
	}
	echo 'IMPORT_QUEUE=' . wp_json_encode( array( 'pending' => count( $ids ), 'queued' => $queued ) ) . "\n";
	exit( 0 );
}

if ( 'final-report' === $phase ) {
	$supplier = MDO_Supplier_Repository::find( $supplier_id );
	$counts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT status, COUNT(*) n FROM {$source_table} WHERE supplier_id = %d GROUP BY status ORDER BY status",
			$supplier_id
		),
		ARRAY_A
	);
	$total = (int) $wpdb->get_var(
		$wpdb->prepare( "SELECT COUNT(*) FROM {$source_table} WHERE supplier_id = %d", $supplier_id )
	);
	$active = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$source_table}
			 WHERE supplier_id = %d AND status = 'active' AND wc_product_id IS NOT NULL AND wc_product_id > 0",
			$supplier_id
		)
	);
	$pending = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$source_table} WHERE supplier_id = %d AND status IN ('pending','importing')",
			$supplier_id
		)
	);
	$bad_author = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			 FROM {$source_table} sp
			 JOIN {$wpdb->posts} p ON p.ID = sp.wc_product_id
			 WHERE sp.supplier_id = %d
			   AND sp.status = 'active'
			   AND p.post_author <> %d",
			$supplier_id,
			(int) $supplier['vendor_user_id']
		)
	);
	$errors = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id,title,last_error FROM {$source_table}
			 WHERE supplier_id = %d AND last_error IS NOT NULL AND last_error <> ''
			 ORDER BY id LIMIT 20",
			$supplier_id
		),
		ARRAY_A
	);
	echo 'FINAL_REPORT=' . wp_json_encode(
		array(
			'supplier'            => array(
				'id'             => (int) $supplier['id'],
				'name'           => $supplier['name'],
				'source_url'     => $supplier['source_url'],
				'vendor_user_id' => (int) $supplier['vendor_user_id'],
				'connector'      => $supplier['connector'],
				'sync_frequency' => $supplier['sync_frequency'],
				'active'         => (int) $supplier['active'],
				'last_sync_at'   => $supplier['last_sync_at'],
			),
			'total_source'        => $total,
			'active_imported'     => $active,
			'pending_or_importing'=> $pending,
			'wrong_vendor_author' => $bad_author,
			'counts'              => $counts,
			'errors'              => $errors,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	) . "\n";
	if (
		'selectos-de-castilla' !== $supplier['connector']
		|| 'daily' !== $supplier['sync_frequency']
		|| 1 !== (int) $supplier['active']
		|| $total < 150
		|| $active < 150
		|| 0 !== $pending
		|| 0 !== $bad_author
		|| ! empty( $errors )
	) {
		exit( 41 );
	}
	exit( 0 );
}

fwrite( STDERR, "UNKNOWN_PHASE={$phase}\n" );
exit( 50 );
