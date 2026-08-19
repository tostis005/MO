<?php
/**
 * One-off production operation: assign Conservas to La Huerta de Ana Mary
 * WooCommerce products using the synchronizer source table as canonical link.
 * Existing categories are preserved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}
if ( ! class_exists( 'MDO_Database' ) ) {
	fwrite( STDERR, "ERROR: MDO database layer is not loaded.\n" );
	exit( 2 );
}

$source_hosts = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );

$conservas = get_term_by( 'slug', 'conservas', 'product_cat' );
if ( ! ( $conservas instanceof WP_Term ) ) {
	$conservas = get_term_by( 'name', 'Conservas', 'product_cat' );
}
if ( ! ( $conservas instanceof WP_Term ) ) {
	$created = wp_insert_term( 'Conservas', 'product_cat', array( 'slug' => 'conservas' ) );
	if ( is_wp_error( $created ) ) {
		fwrite( STDERR, 'ERROR creating Conservas category: ' . $created->get_error_message() . "\n" );
		exit( 3 );
	}
	$conservas = get_term( (int) $created['term_id'], 'product_cat' );
}
if ( ! ( $conservas instanceof WP_Term ) ) {
	fwrite( STDERR, "ERROR: Conservas category could not be resolved.\n" );
	exit( 4 );
}

global $wpdb;
$source_table = MDO_Database::table( 'source_products' );
$rows         = $wpdb->get_results(
	"SELECT id, supplier_id, source_url, wc_product_id, title, status
	 FROM {$source_table}
	 WHERE source_url LIKE '%lahuertadeanamary.com%'
	 ORDER BY id ASC",
	ARRAY_A
);

$huerta_rows = array();
foreach ( (array) $rows as $row ) {
	$source_url = trim( (string) ( $row['source_url'] ?? '' ) );
	$host       = strtolower( (string) wp_parse_url( $source_url, PHP_URL_HOST ) );
	if ( in_array( $host, $source_hosts, true ) ) {
		$huerta_rows[] = $row;
	}
}

if ( ! $huerta_rows ) {
	fwrite( STDERR, "ERROR: no La Huerta de Ana Mary source rows were found.\n" );
	exit( 5 );
}

$source_conservas = 0;
$status_counts     = array();
$linked            = array();
$broken_links      = array();

foreach ( $huerta_rows as $row ) {
	$status = (string) ( $row['status'] ?? 'unknown' );
	$status_counts[ $status ] = ( $status_counts[ $status ] ?? 0 ) + 1;
	$source_url = (string) $row['source_url'];
	if ( str_contains( strtolower( $source_url ), '/conservas-3/' ) ) {
		++$source_conservas;
	}

	$product_id = absint( $row['wc_product_id'] ?? 0 );
	if ( ! $product_id ) {
		continue;
	}
	if ( 'product' !== get_post_type( $product_id ) ) {
		$broken_links[] = $product_id;
		continue;
	}
	$linked[ $product_id ] = $row;
}

ksort( $status_counts );
echo sprintf(
	"HUERTA_SOURCE rows=%d source_conservas=%d linked_woo=%d broken_links=%d statuses=%s\n",
	count( $huerta_rows ),
	$source_conservas,
	count( $linked ),
	count( array_unique( $broken_links ) ),
	wp_json_encode( $status_counts )
);

if ( ! $linked ) {
	echo sprintf(
		"huerta_conservas_pending_import source_products=%d source_conservas=%d linked_woo=0\n",
		count( $huerta_rows ),
		$source_conservas
	);
	exit( 0 );
}

$expected = array();
$other    = array();

foreach ( $linked as $product_id => $row ) {
	$source_url = trim( (string) $row['source_url'] );

	// Repair legacy/missing EMDO metadata from the canonical source row.
	update_post_meta( $product_id, '_emdo_source_product_id', (int) $row['id'] );
	update_post_meta( $product_id, '_emdo_supplier_id', (int) $row['supplier_id'] );
	update_post_meta( $product_id, '_emdo_source_url', esc_url_raw( $source_url ) );

	if ( str_contains( strtolower( $source_url ), '/conservas-3/' ) ) {
		$expected[ $product_id ] = $row;
	} else {
		$other[ $product_id ] = $row;
	}
}

$added   = 0;
$removed = 0;
$kept    = 0;

foreach ( $expected as $product_id => $row ) {
	if ( has_term( (int) $conservas->term_id, 'product_cat', $product_id ) ) {
		++$kept;
	} else {
		$result = wp_set_object_terms( $product_id, array( (int) $conservas->term_id ), 'product_cat', true );
		if ( is_wp_error( $result ) ) {
			fwrite( STDERR, sprintf( "ERROR assigning Conservas to product %d: %s\n", $product_id, $result->get_error_message() ) );
			exit( 6 );
		}
		++$added;
	}

	echo sprintf(
		"CONSERVA %d | %s | %s\n",
		$product_id,
		get_the_title( $product_id ),
		(string) $row['source_url']
	);
}

foreach ( $other as $product_id => $row ) {
	if ( ! has_term( (int) $conservas->term_id, 'product_cat', $product_id ) ) {
		continue;
	}
	$result = wp_remove_object_terms( $product_id, (int) $conservas->term_id, 'product_cat' );
	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, sprintf( "ERROR removing Conservas from product %d: %s\n", $product_id, $result->get_error_message() ) );
		exit( 7 );
	}
	++$removed;
}

clean_term_cache( (int) $conservas->term_id, 'product_cat' );

foreach ( $expected as $product_id => $row ) {
	if ( ! has_term( (int) $conservas->term_id, 'product_cat', $product_id ) ) {
		fwrite( STDERR, sprintf( "ERROR verification: product %d should have Conservas.\n", $product_id ) );
		exit( 8 );
	}
}
foreach ( $other as $product_id => $row ) {
	if ( has_term( (int) $conservas->term_id, 'product_cat', $product_id ) ) {
		fwrite( STDERR, sprintf( "ERROR verification: product %d should not have Conservas.\n", $product_id ) );
		exit( 9 );
	}
}

echo sprintf(
	"huerta_conservas_ok products=%d conservas=%d added=%d already_ok=%d removed_wrong=%d source_rows=%d source_conservas=%d\n",
	count( $linked ),
	count( $expected ),
	$added,
	$kept,
	$removed,
	count( $huerta_rows ),
	$source_conservas
);
