<?php
/**
 * One-off production operation: assign the existing Conservas category to
 * La Huerta de Ana Mary products whose source URL belongs to /conservas-3/.
 * Other product categories are preserved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$source_hosts = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );

$conservas = get_term_by( 'slug', 'conservas', 'product_cat' );
if ( ! ( $conservas instanceof WP_Term ) ) {
	$conservas = get_term_by( 'name', 'Conservas', 'product_cat' );
}
if ( ! ( $conservas instanceof WP_Term ) ) {
	$created = wp_insert_term(
		'Conservas',
		'product_cat',
		array(
			'slug' => 'conservas',
		)
	);
	if ( is_wp_error( $created ) ) {
		fwrite( STDERR, 'ERROR creating Conservas category: ' . $created->get_error_message() . "\n" );
		exit( 2 );
	}
	$conservas = get_term( (int) $created['term_id'], 'product_cat' );
}

if ( ! ( $conservas instanceof WP_Term ) ) {
	fwrite( STDERR, "ERROR: Conservas category could not be resolved.\n" );
	exit( 3 );
}

$candidate_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'future', 'trash' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => '_emdo_source_url',
				'value'   => 'lahuertadeanamary.com',
				'compare' => 'LIKE',
			),
		),
	)
);

$product_ids = array();
foreach ( array_values( array_unique( array_map( 'intval', $candidate_ids ) ) ) as $product_id ) {
	$source_url = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
	$host       = strtolower( (string) wp_parse_url( $source_url, PHP_URL_HOST ) );
	if ( in_array( $host, $source_hosts, true ) ) {
		$product_ids[] = $product_id;
	}
}

if ( ! $product_ids ) {
	global $wpdb;
	$source_meta_count = (int) $wpdb->get_var(
		"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_emdo_source_url'"
	);
	fwrite(
		STDERR,
		sprintf(
			"ERROR: no La Huerta de Ana Mary WooCommerce products were found by source URL. products_with_any_source_url=%d\n",
			$source_meta_count
		)
	);
	exit( 4 );
}

$expected = array();
$other    = array();

foreach ( $product_ids as $product_id ) {
	$source_url = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
	if ( str_contains( strtolower( $source_url ), '/conservas-3/' ) ) {
		$expected[] = $product_id;
	} else {
		$other[] = $product_id;
	}
}

if ( ! $expected ) {
	fwrite( STDERR, "ERROR: no Huerta products matched /conservas-3/. No changes were made.\n" );
	foreach ( $product_ids as $product_id ) {
		fwrite(
			STDERR,
			sprintf(
				"HUERTA_SOURCE %d | %s | %s\n",
				$product_id,
				get_the_title( $product_id ),
				(string) get_post_meta( $product_id, '_emdo_source_url', true )
			)
		);
	}
	exit( 5 );
}

echo sprintf(
	"HUERTA products=%d | expected_conservas=%d | other=%d | conservas_term_id=%d\n",
	count( $product_ids ),
	count( $expected ),
	count( $other ),
	(int) $conservas->term_id
);

$added   = 0;
$removed = 0;
$kept    = 0;

foreach ( $expected as $product_id ) {
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
		(string) get_post_meta( $product_id, '_emdo_source_url', true )
	);
}

foreach ( $other as $product_id ) {
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

foreach ( $expected as $product_id ) {
	if ( ! has_term( (int) $conservas->term_id, 'product_cat', $product_id ) ) {
		fwrite( STDERR, sprintf( "ERROR verification: product %d should have Conservas.\n", $product_id ) );
		exit( 8 );
	}
}
foreach ( $other as $product_id ) {
	if ( has_term( (int) $conservas->term_id, 'product_cat', $product_id ) ) {
		fwrite( STDERR, sprintf( "ERROR verification: product %d should not have Conservas.\n", $product_id ) );
		exit( 9 );
	}
}

echo sprintf(
	"huerta_conservas_ok products=%d conservas=%d added=%d already_ok=%d removed_wrong=%d\n",
	count( $product_ids ),
	count( $expected ),
	$added,
	$kept,
	$removed
);
