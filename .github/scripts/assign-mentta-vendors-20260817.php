<?php
/**
 * One-off production operation: make the MENTTA category contain exactly the
 * products belonging to the vendors "1957" and "Hidalgo de la Jara".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

function mdo_mentta_norm( $value ) {
    $value = wp_strip_all_tags( (string) $value );
    $value = remove_accents( $value );
    $value = preg_replace( '/\s+/u', ' ', trim( $value ) );
    return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
}

function mdo_mentta_vendor_names( $user ) {
    global $WCFM;

    $names = array(
        $user->display_name,
        $user->user_login,
        $user->user_nicename,
        get_user_meta( $user->ID, 'nickname', true ),
        get_user_meta( $user->ID, 'store_name', true ),
        get_user_meta( $user->ID, '_wcfm_store_name', true ),
    );

    foreach ( array( 'wcfmmp_profile_settings', 'wcfm_profile_settings' ) as $key ) {
        $profile = get_user_meta( $user->ID, $key, true );
        if ( is_array( $profile ) && ! empty( $profile['store_name'] ) ) {
            $names[] = $profile['store_name'];
        }
    }

    if ( isset( $WCFM->wcfm_vendor_support ) && is_callable( array( $WCFM->wcfm_vendor_support, 'wcfm_get_vendor_store_name' ) ) ) {
        $names[] = $WCFM->wcfm_vendor_support->wcfm_get_vendor_store_name( $user->ID );
    }

    return array_values( array_unique( array_filter( array_map( 'strval', $names ) ) ) );
}

$wanted = array(
    '1957'               => '1957',
    'hidalgo de la jara' => 'Hidalgo de la Jara',
);

$matches = array();
foreach ( get_users( array( 'fields' => 'all' ) ) as $user ) {
    foreach ( mdo_mentta_vendor_names( $user ) as $name ) {
        $norm = mdo_mentta_norm( $name );
        if ( isset( $wanted[ $norm ] ) ) {
            $matches[ $norm ][ $user->ID ] = $name;
        }
    }
}

foreach ( $wanted as $norm => $label ) {
    $candidate_ids = isset( $matches[ $norm ] ) ? array_keys( $matches[ $norm ] ) : array();
    if ( 1 !== count( $candidate_ids ) ) {
        fwrite( STDERR, sprintf( "ERROR: expected exactly one vendor for %s; found %d (%s)\n", $label, count( $candidate_ids ), implode( ',', $candidate_ids ) ) );
        exit( 2 );
    }
}

$vendor_ids = array(
    '1957'               => (int) array_key_first( $matches['1957'] ),
    'hidalgo de la jara' => (int) array_key_first( $matches['hidalgo de la jara'] ),
);

$term = get_term_by( 'slug', 'mentta', 'product_cat' );
if ( ! ( $term instanceof WP_Term ) ) {
    fwrite( STDERR, "ERROR: MENTTA category not found.\n" );
    exit( 3 );
}

global $wpdb;
$valid_statuses = array( 'publish', 'draft', 'pending', 'private', 'future' );
$status_placeholders = implode( ',', array_fill( 0, count( $valid_statuses ), '%s' ) );

$query_args = array_merge( array_values( $vendor_ids ), $valid_statuses );
$sql = $wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_author IN (%d,%d) AND post_status IN ($status_placeholders) ORDER BY ID ASC",
    $query_args
);
$target_ids = array_map( 'intval', $wpdb->get_col( $sql ) );
$target_ids = array_values( array_unique( $target_ids ) );
sort( $target_ids, SORT_NUMERIC );

if ( ! $target_ids ) {
    fwrite( STDERR, "ERROR: no products found for the two resolved vendors.\n" );
    exit( 4 );
}

$existing_ids = get_objects_in_term( (int) $term->term_id, 'product_cat' );
if ( is_wp_error( $existing_ids ) ) {
    fwrite( STDERR, 'ERROR: ' . $existing_ids->get_error_message() . "\n" );
    exit( 5 );
}
$existing_ids = array_values( array_unique( array_map( 'intval', $existing_ids ) ) );

$to_remove = array_values( array_diff( $existing_ids, $target_ids ) );
foreach ( $to_remove as $product_id ) {
    $result = wp_remove_object_terms( $product_id, (int) $term->term_id, 'product_cat' );
    if ( is_wp_error( $result ) ) {
        fwrite( STDERR, sprintf( "ERROR removing MENTTA from product %d: %s\n", $product_id, $result->get_error_message() ) );
        exit( 6 );
    }
}

foreach ( $target_ids as $product_id ) {
    $result = wp_set_object_terms( $product_id, (int) $term->term_id, 'product_cat', true );
    if ( is_wp_error( $result ) ) {
        fwrite( STDERR, sprintf( "ERROR adding MENTTA to product %d: %s\n", $product_id, $result->get_error_message() ) );
        exit( 7 );
    }
}

clean_term_cache( (int) $term->term_id, 'product_cat' );

$final_ids = get_objects_in_term( (int) $term->term_id, 'product_cat' );
if ( is_wp_error( $final_ids ) ) {
    fwrite( STDERR, 'ERROR: ' . $final_ids->get_error_message() . "\n" );
    exit( 8 );
}
$final_ids = array_values( array_unique( array_map( 'intval', $final_ids ) ) );
sort( $final_ids, SORT_NUMERIC );

if ( $final_ids !== $target_ids ) {
    fwrite( STDERR, sprintf( "ERROR: final MENTTA membership mismatch. expected=%d actual=%d\n", count( $target_ids ), count( $final_ids ) ) );
    exit( 9 );
}

$counts = array();
foreach ( $vendor_ids as $norm => $vendor_id ) {
    $count_args = array_merge( array( $vendor_id ), $valid_statuses );
    $count_sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_author = %d AND post_status IN ($status_placeholders)",
        $count_args
    );
    $counts[ $norm ] = (int) $wpdb->get_var( $count_sql );
}

if ( $final_ids ) {
    $id_placeholders = implode( ',', array_fill( 0, count( $final_ids ), '%d' ) );
    $author_sql = $wpdb->prepare(
        "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE ID IN ($id_placeholders) ORDER BY post_author ASC",
        $final_ids
    );
    $final_authors = array_map( 'intval', $wpdb->get_col( $author_sql ) );
    $unexpected_authors = array_values( array_diff( $final_authors, array_values( $vendor_ids ) ) );
    if ( $unexpected_authors ) {
        fwrite( STDERR, 'ERROR: unexpected product authors in MENTTA: ' . implode( ',', $unexpected_authors ) . "\n" );
        exit( 10 );
    }
}

echo 'MENTTA term ID: ' . (int) $term->term_id . "\n";
echo '1957 vendor ID: ' . $vendor_ids['1957'] . ' products: ' . $counts['1957'] . "\n";
echo 'Hidalgo de la Jara vendor ID: ' . $vendor_ids['hidalgo de la jara'] . ' products: ' . $counts['hidalgo de la jara'] . "\n";
echo 'Removed products not belonging to these vendors: ' . count( $to_remove ) . "\n";
echo 'Final MENTTA products: ' . count( $final_ids ) . "\n";
echo 'Final product authors: ' . implode( ',', $final_authors ) . "\n";
echo "assignment_ok\n";
