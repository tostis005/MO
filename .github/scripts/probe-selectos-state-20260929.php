<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

global $wpdb;

$st = MDO_Database::table( 'suppliers' );
$pt = MDO_Database::table( 'source_products' );
$rt = MDO_Database::table( 'sync_runs' );

$s = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT * FROM {$st}
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

if ( ! $s ) {
	echo 'PROBE=' . wp_json_encode( array( 'supplier' => null ) ) . PHP_EOL;
	exit( 0 );
}

$sid = (int) $s['id'];
$run = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT id,status,trigger_type,products_found,products_new,products_updated,products_excluded,errors_count,message,started_at,finished_at
		 FROM {$rt}
		 WHERE supplier_id = %d
		 ORDER BY id DESC
		 LIMIT 1",
		$sid
	),
	ARRAY_A
);
$counts = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT status,COUNT(*) n
		 FROM {$pt}
		 WHERE supplier_id = %d
		 GROUP BY status
		 ORDER BY status",
		$sid
	),
	ARRAY_A
);
$active = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*)
		 FROM {$pt}
		 WHERE supplier_id = %d
		   AND status = 'active'
		   AND wc_product_id IS NOT NULL
		   AND wc_product_id > 0",
		$sid
	)
);
$pending = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*)
		 FROM {$pt}
		 WHERE supplier_id = %d
		   AND status IN ('pending','importing')",
		$sid
	)
);
$errors = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*)
		 FROM {$pt}
		 WHERE supplier_id = %d
		   AND last_error IS NOT NULL
		   AND last_error <> ''",
		$sid
	)
);
$pending_actions = null;
if ( function_exists( 'as_get_scheduled_actions' ) ) {
	$pending_actions = count(
		as_get_scheduled_actions(
			array(
				'group'    => 'mdo-supplier-sync',
				'status'   => ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 1000,
			),
			'ids'
		)
	);
}

echo 'PROBE=' . wp_json_encode(
	array(
		'supplier' => array(
			'id'             => $sid,
			'vendor_user_id' => (int) $s['vendor_user_id'],
			'connector'      => $s['connector'],
			'frequency'      => $s['sync_frequency'],
			'active'         => (int) $s['active'],
			'last_sync_at'   => $s['last_sync_at'],
		),
		'latest_run'          => $run,
		'counts'              => $counts,
		'active_imported'     => $active,
		'pending_or_importing'=> $pending,
		'source_errors'       => $errors,
		'pending_group_actions'=> $pending_actions,
	),
	JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) . PHP_EOL;
