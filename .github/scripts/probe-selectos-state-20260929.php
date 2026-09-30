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

$sid       = (int) $s['id'];
$vendor_id = (int) $s['vendor_user_id'];

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

$source_status_counts = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT status, COUNT(*) n
		 FROM {$pt}
		 WHERE supplier_id = %d
		 GROUP BY status
		 ORDER BY status",
		$sid
	),
	ARRAY_A
);

$source_stock = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT status, source_stock_status, COUNT(*) n
		 FROM {$pt}
		 WHERE supplier_id = %d
		 GROUP BY status, source_stock_status
		 ORDER BY status, source_stock_status",
		$sid
	),
	ARRAY_A
);

$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT sp.id source_id, sp.status source_status, sp.source_stock_status, sp.title source_title,
		        sp.wc_product_id, sp.last_error,
		        p.post_status, p.post_author, p.post_title
		 FROM {$pt} sp
		 LEFT JOIN {$wpdb->posts} p ON p.ID = sp.wc_product_id
		 WHERE sp.supplier_id = %d
		 ORDER BY sp.id ASC",
		$sid
	),
	ARRAY_A
) ?: array();

$bucket = array(
	'active_mapped'               => 0,
	'active_publish'              => 0,
	'active_draft'                => 0,
	'active_other_post_status'    => 0,
	'active_instock'              => 0,
	'active_outofstock'           => 0,
	'active_visibility_visible'   => 0,
	'active_visibility_catalog'   => 0,
	'active_visibility_search'    => 0,
	'active_visibility_hidden'    => 0,
	'active_public_candidate'     => 0,
	'excluded_total'              => 0,
	'excluded_mapped'             => 0,
	'excluded_draft'              => 0,
	'excluded_publish'            => 0,
	'unavailable_total'           => 0,
	'wrong_vendor_author'         => 0,
	'missing_wc_product'          => 0,
);

$hide_outofstock = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' );
$mismatches = array();

foreach ( $rows as $row ) {
	$source_status = (string) $row['source_status'];
	$product_id    = (int) $row['wc_product_id'];
	$post_status   = (string) ( $row['post_status'] ?? '' );

	if ( $source_status === 'excluded' ) {
		$bucket['excluded_total']++;
		if ( $product_id > 0 ) {
			$bucket['excluded_mapped']++;
		}
		if ( 'draft' === $post_status ) {
			$bucket['excluded_draft']++;
		} elseif ( 'publish' === $post_status ) {
			$bucket['excluded_publish']++;
			$mismatches[] = array(
				'reason'        => 'excluded_but_published',
				'source_id'     => (int) $row['source_id'],
				'wc_product_id' => $product_id,
				'title'         => $row['source_title'],
			);
		}
		continue;
	}

	if ( $source_status === 'unavailable' ) {
		$bucket['unavailable_total']++;
	}

	if ( 'active' !== $source_status ) {
		continue;
	}

	if ( $product_id <= 0 ) {
		$bucket['missing_wc_product']++;
		$mismatches[] = array(
			'reason'    => 'active_without_wc_product',
			'source_id' => (int) $row['source_id'],
			'title'     => $row['source_title'],
		);
		continue;
	}

	$bucket['active_mapped']++;
	if ( $vendor_id > 0 && (int) $row['post_author'] !== $vendor_id ) {
		$bucket['wrong_vendor_author']++;
	}

	if ( 'publish' === $post_status ) {
		$bucket['active_publish']++;
	} elseif ( 'draft' === $post_status ) {
		$bucket['active_draft']++;
	} else {
		$bucket['active_other_post_status']++;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		$mismatches[] = array(
			'reason'        => 'wc_product_not_loadable',
			'source_id'     => (int) $row['source_id'],
			'wc_product_id' => $product_id,
			'title'         => $row['source_title'],
		);
		continue;
	}

	$stock = (string) $product->get_stock_status();
	$vis   = (string) $product->get_catalog_visibility();

	if ( 'outofstock' === $stock ) {
		$bucket['active_outofstock']++;
	} else {
		$bucket['active_instock']++;
	}

	$key = 'active_visibility_' . $vis;
	if ( isset( $bucket[ $key ] ) ) {
		$bucket[ $key ]++;
	}

	$is_catalog_visible = in_array( $vis, array( 'visible', 'catalog' ), true );
	$is_public_candidate = 'publish' === $post_status
		&& $is_catalog_visible
		&& ( ! $hide_outofstock || 'outofstock' !== $stock );

	if ( $is_public_candidate ) {
		$bucket['active_public_candidate']++;
	} else {
		$mismatches[] = array(
			'reason'            => 'active_not_public_candidate',
			'source_id'         => (int) $row['source_id'],
			'wc_product_id'     => $product_id,
			'title'             => $row['source_title'],
			'post_status'       => $post_status,
			'stock_status'      => $stock,
			'catalog_visibility'=> $vis,
		);
	}
}

$vendor_post_status = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT post_status, COUNT(*) n
		 FROM {$wpdb->posts}
		 WHERE post_type = 'product'
		   AND post_author = %d
		 GROUP BY post_status
		 ORDER BY post_status",
		$vendor_id
	),
	ARRAY_A
);

$vendor_total = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*)
		 FROM {$wpdb->posts}
		 WHERE post_type = 'product'
		   AND post_author = %d",
		$vendor_id
	)
);

$vendor_published = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*)
		 FROM {$wpdb->posts}
		 WHERE post_type = 'product'
		   AND post_author = %d
		   AND post_status = 'publish'",
		$vendor_id
	)
);

$store_url = function_exists( 'wcfmmp_get_store_url' ) && $vendor_id > 0
	? (string) wcfmmp_get_store_url( $vendor_id )
	: '';

$store_truth_ids = function_exists( 'elmercado_vendor_store_product_ids_010225' )
	? elmercado_vendor_store_product_ids_010225( $vendor_id )
	: array();
$store_categories = function_exists( 'elmercado_vendor_store_categories_010225' )
	? elmercado_vendor_store_categories_010225( $store_truth_ids )
	: array();
$implicit_category = 1 === count( $store_categories ) ? reset( $store_categories ) : null;
$store_filtered_ids = $store_truth_ids;
if ( is_array( $implicit_category ) && isset( $implicit_category['term'] ) && $implicit_category['term'] instanceof WP_Term && function_exists( 'elmercado_vendor_store_product_ids_010225' ) ) {
	$store_filtered_ids = elmercado_vendor_store_product_ids_010225( $vendor_id, $implicit_category['term'] );
}
$store_category_summary = array();
foreach ( $store_categories as $data ) {
	if ( ! isset( $data['term'] ) || ! $data['term'] instanceof WP_Term ) {
		continue;
	}
	$store_category_summary[] = array(
		'id'    => (int) $data['term']->term_id,
		'slug'  => $data['term']->slug,
		'name'  => $data['term']->name,
		'count' => (int) ( $data['count'] ?? 0 ),
	);
}

$run_errors = array();
if ( $run && ! empty( $run['id'] ) ) {
	$events_table = MDO_Database::table( 'sync_events' );
	$run_errors = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT event_type, severity, message, payload
			 FROM {$events_table}
			 WHERE run_id = %d
			   AND severity = 'error'
			 ORDER BY id ASC",
			(int) $run['id']
		),
		ARRAY_A
	) ?: array();
	foreach ( $run_errors as &$event ) {
		$payload = json_decode( (string) ( $event['payload'] ?? '' ), true );
		$event['payload'] = is_array( $payload ) ? $payload : null;
	}
	unset( $event );
}

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
			'name'           => $s['name'],
			'vendor_user_id' => $vendor_id,
			'connector'      => $s['connector'],
			'frequency'      => $s['sync_frequency'],
			'active'         => (int) $s['active'],
			'last_sync_at'   => $s['last_sync_at'],
			'store_url'      => $store_url,
		),
		'latest_run'          => $run,
		'source_status'       => $source_status_counts,
		'source_stock'        => $source_stock,
		'woo'                 => $bucket,
		'vendor_total_products'    => $vendor_total,
		'vendor_published_products'=> $vendor_published,
		'vendor_post_status'       => $vendor_post_status,
		'hide_out_of_stock_items'  => $hide_outofstock,
		'store_truth_count_b64'    => base64_encode( (string) count( $store_truth_ids ) ),
		'store_filtered_count_b64' => base64_encode( (string) count( $store_filtered_ids ) ),
		'store_categories'         => $store_category_summary,
		'pending_group_actions'    => $pending_actions,
		'latest_run_errors'       => $run_errors,
		'mismatches'               => array_slice( $mismatches, 0, 100 ),
	),
	JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) . PHP_EOL;
