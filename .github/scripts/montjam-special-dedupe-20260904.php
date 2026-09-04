<?php
/**
 * Keep a single active Montjam 6–6.5 kg / €225 Special in the EMDO backend.
 * Older one-off records created while validating the integration are retained
 * as drafts instead of being deleted.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( "WordPress not loaded\n" );
}

$current_seed = 'montjam-jamon-bellota-100-iberico-6-65-225-v1';
$legacy_seed  = 'montjam-jamon-bellota-225-v1';
$product_id   = 14264;

$current = get_posts(
	array(
		'post_type'      => 'mdo_promotion',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_mdo_promo_seed_key',
		'meta_value'     => $current_seed,
	)
);
if ( ! $current ) {
	fwrite( STDERR, "Current Montjam Special not found\n" );
	exit( 2 );
}
$keep_id = (int) $current[0];

$candidates = get_posts(
	array(
		'post_type'      => 'mdo_promotion',
		'post_status'    => 'any',
		'posts_per_page' => 50,
		'fields'         => 'ids',
		'meta_key'       => '_mdo_promo_seed_key',
		'meta_value'     => $legacy_seed,
	)
);

$legacy_slug = get_page_by_path( 'jamon-bellota-100-iberico-montjam-225', OBJECT, 'mdo_promotion' );
if ( $legacy_slug ) {
	$candidates[] = (int) $legacy_slug->ID;
}

$drafted = array();
foreach ( array_unique( array_map( 'intval', $candidates ) ) as $candidate_id ) {
	if ( ! $candidate_id || $candidate_id === $keep_id ) {
		continue;
	}
	$linked_products = (string) get_post_meta( $candidate_id, '_mdo_promo_product_ids', true );
	$seed            = (string) get_post_meta( $candidate_id, '_mdo_promo_seed_key', true );
	$title           = (string) get_the_title( $candidate_id );
	if ( $legacy_seed !== $seed && (string) $product_id !== $linked_products ) {
		continue;
	}
	if ( false === stripos( $title, 'Montjam' ) ) {
		continue;
	}
	update_post_meta( $candidate_id, '_mdo_promo_featured_home', '0' );
	$result = wp_update_post(
		array(
			'ID'          => $candidate_id,
			'post_status' => 'draft',
		),
		true
	);
	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, 'Could not draft legacy Special #' . $candidate_id . ': ' . $result->get_error_message() . "\n" );
		exit( 3 );
	}
	$drafted[] = $candidate_id;
}

clean_post_cache( $keep_id );
wp_cache_flush();

echo 'MONTJAM_SPECIAL_DEDUPE: ' . wp_json_encode(
	array(
		'keep_id' => $keep_id,
		'drafted' => $drafted,
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) . "\n";
