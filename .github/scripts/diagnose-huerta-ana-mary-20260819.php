<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$supplier = null;
foreach ( MDO_Supplier_Repository::all() as $candidate ) {
	if ( 'la-huerta-ana-mary' === (string) ( $candidate['connector'] ?? '' ) ) {
		$supplier = $candidate;
		break;
	}
}

if ( ! $supplier ) {
	echo wp_json_encode( array( 'error' => 'supplier_not_found' ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	exit( 2 );
}

global $wpdb;
$runs_table     = MDO_Database::table( 'sync_runs' );
$events_table   = MDO_Database::table( 'sync_events' );
$products_table = MDO_Database::table( 'source_products' );
$supplier_id    = (int) $supplier['id'];

$latest_run = $wpdb->get_row(
	$wpdb->prepare( "SELECT * FROM {$runs_table} WHERE supplier_id = %d ORDER BY id DESC LIMIT 1", $supplier_id ),
	ARRAY_A
);
$events = array();
if ( $latest_run ) {
	$events = $wpdb->get_results(
		$wpdb->prepare( "SELECT id,event_type,severity,message,payload,created_at FROM {$events_table} WHERE run_id = %d ORDER BY id ASC", (int) $latest_run['id'] ),
		ARRAY_A
	) ?: array();
}

$status_counts = $wpdb->get_results(
	$wpdb->prepare( "SELECT status,COUNT(*) AS total FROM {$products_table} WHERE supplier_id = %d GROUP BY status ORDER BY status", $supplier_id ),
	ARRAY_A
) ?: array();

$source_rows = $wpdb->get_results(
	$wpdb->prepare( "SELECT id,title,source_url,status,wc_product_id,last_error,last_seen_at FROM {$products_table} WHERE supplier_id = %d ORDER BY id ASC", $supplier_id ),
	ARRAY_A
) ?: array();

$vendor = ! empty( $supplier['vendor_user_id'] ) ? get_user_by( 'id', (int) $supplier['vendor_user_id'] ) : false;
$actions_table = $wpdb->prefix . 'actionscheduler_actions';
$action_rows = array();
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $actions_table ) ) === $actions_table ) {
	$action_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT action_id,hook,status,args,scheduled_date_gmt,last_attempt_gmt,claim_id FROM {$actions_table} WHERE hook = %s ORDER BY action_id DESC LIMIT 100",
			'mdo_supplier_sync_import_product'
		),
		ARRAY_A
	) ?: array();
}

$out = array(
	'plugin_version' => defined( 'MDO_SUPPLIER_SYNC_VERSION' ) ? MDO_SUPPLIER_SYNC_VERSION : 'unknown',
	'supplier'       => array(
		'id' => $supplier_id,
		'name' => $supplier['name'] ?? '',
		'source_url' => $supplier['source_url'] ?? '',
		'exclusion_url_fragments' => $supplier['exclusion_url_fragments'] ?? '',
		'vendor_user_id' => $supplier['vendor_user_id'] ?? null,
		'active' => $supplier['active'] ?? null,
	),
	'vendor' => $vendor ? array(
		'id' => $vendor->ID,
		'user_login' => $vendor->user_login,
		'display_name' => $vendor->display_name,
		'roles' => array_values( (array) $vendor->roles ),
	) : null,
	'latest_run'     => $latest_run,
	'latest_events'  => $events,
	'status_counts'  => $status_counts,
	'source_rows'    => $source_rows,
	'import_actions' => $action_rows,
);

try {
	$discovery = MDO_Connector_Huerta_Ana_Mary::discover( $supplier );
	$out['discovery'] = $discovery;
	$out['discovery_counts'] = array(
		'products' => count( $discovery['products'] ?? array() ),
		'excluded' => count( $discovery['excluded'] ?? array() ),
		'pages' => count( $discovery['pages'] ?? array() ),
	);
	$out['scrape'] = array();
	foreach ( (array) ( $discovery['products'] ?? array() ) as $url ) {
		try {
			$product = MDO_Connector_Huerta_Ana_Mary::scrape_product( (string) $url );
			$out['scrape'][] = array(
				'url' => $url,
				'ok' => true,
				'title' => $product['title'] ?? '',
				'price' => $product['price'] ?? null,
				'image_count' => $product['image_count'] ?? null,
				'stock_status' => $product['stock_status'] ?? null,
			);
		} catch ( Throwable $error ) {
			$out['scrape'][] = array( 'url' => $url, 'ok' => false, 'error' => $error->getMessage() );
		}
	}
} catch ( Throwable $error ) {
	$out['discovery_error'] = $error->getMessage();
}

echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
