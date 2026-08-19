<?php
/**
 * One-off production operation: assign the existing Conservas category to
 * La Huerta de Ana Mary products whose source URL belongs to /conservas-3/.
 * Other product categories are preserved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! class_exists( 'MDO_Supplier_Repository' ) ) {
	fwrite( STDERR, "ERROR: MDO Supplier Sync is not loaded.\n" );
	exit( 2 );
}

$supplier = null;
foreach ( MDO_Supplier_Repository::all() as $candidate ) {
	if ( 'la-huerta-ana-mary' === (string) ( $candidate['connector'] ?? '' ) ) {
		$supplier = $candidate;
		break;
	}
}

if ( ! $supplier ) {
	fwrite( STDERR, "ERROR: La Huerta de Ana Mary supplier was not found.\n" );
	exit( 3 );
}

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
		exit( 4 );
	}
	$conservas = get_term( (int) $created['term_id'], 'product_cat' );
}

if ( ! ( $conservas instanceof WP_Term ) ) {
	fwrite( STDERR, "ERROR: Conservas category could not be resolved.\n" );
	exit( 5 );
}

$product_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'future', 'trash' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'meta_key'       => '_emdo_supplier_id',
		'meta_value'     => (string) (int) $supplier['id'],
	)
);
$product_ids = array_values( array_unique( array_map( 'intval', $product_ids ) ) );

if ( ! $product_ids ) {
	fwrite( STDERR, "ERROR: no La Huerta de Ana Mary WooCommerce products were found.\n" );
	exit( 6 );
}

$expected = array();
$other    = array();
$missing  = array();

foreach ( $product_ids as $product_id ) {
	$source_url = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
	if ( '' === $source_url ) {
		$missing[] = $product_id;
		continue;
	}

	if ( str_contains( strtolower( $source_url ), '/conservas-3/' ) ) {
		$expected[] = $product_id;
	} else {
		$other[] = $product_id;
	}
}

if ( $missing ) {
	fwrite( STDERR, "ERROR: some Huerta products have no source URL. No changes were made.\n" );
	foreach ( $missing as $product_id ) {
		fwrite( STDERR, sprintf( "MISSING_SOURCE %d | %s\n", $product_id, get_the_title( $product_id ) ) );
	}
	exit( 7 );
}

if ( ! $expected ) {
	fwrite( STDERR, "ERROR: no Huerta products matched /conservas-3/. No changes were made.\n" );
	exit( 8 );
}

echo sprintf(
	"HUERTA supplier_id=%d | products=%d | expected_conservas=%d | other=%d | conservas_term_id=%d\n",
	(int) $supplier['id'],
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
			exit( 9 );
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
		exit( 10 );
	}
	++$removed;
}

clean_term_cache( (int) $conservas->term_id, 'product_cat' );

foreach ( $expected as $product_id ) {
	if ( ! has_term( (int) $conservas->term_id, 'product_cat', $product_id ) ) {
		fwrite( STDERR, sprintf( "ERROR verification: product %d should have Conservas.\n", $product_id ) );
		exit( 11 );
	}
}
foreach ( $other as $product_id ) {
	if ( has_term( (int) $conservas->term_id, 'product_cat', $product_id ) ) {
		fwrite( STDERR, sprintf( "ERROR verification: product %d should not have Conservas.\n", $product_id ) );
		exit( 12 );
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
