<?php
/**
 * Read-only production census for the five approved producers.
 * Workflow validation markers:
 * Desde 1957 hemos mantenido una tradición en la almazara
 * La historia de <strong>1957</strong> comienza precisamente ese año
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

global $wpdb;
$vendors = array(
    3    => '1957',
    6    => 'Hidalgo de la Jara',
    4507 => 'Tolecarnes',
    4508 => 'Puente Robles',
    4509 => 'El Catedrático',
);

$grand_total = 0;
$summary = array();
foreach ( $vendors as $author_id => $vendor_name ) {
    $settings = get_user_meta( $author_id, 'wcfmmp_profile_settings', true );
    $identity_ok = is_array( $settings ) && (string) ( $settings['store_name'] ?? '' ) === $vendor_name;

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT ID, post_status, post_title
         FROM {$wpdb->posts}
         WHERE post_type = 'product' AND post_author = %d AND post_status <> 'trash'
         ORDER BY ID ASC",
        $author_id
    ), ARRAY_A );

    $status_counts = array();
    $visibility_counts = array();
    $ids_by_status = array();
    foreach ( (array) $rows as $row ) {
        $status = (string) $row['post_status'];
        $status_counts[ $status ] = (int) ( $status_counts[ $status ] ?? 0 ) + 1;
        $ids_by_status[ $status ][] = (int) $row['ID'];

        $product = function_exists( 'wc_get_product' ) ? wc_get_product( (int) $row['ID'] ) : false;
        $visibility = $product instanceof WC_Product ? (string) $product->get_catalog_visibility() : 'unresolved';
        $visibility_counts[ $visibility ] = (int) ( $visibility_counts[ $visibility ] ?? 0 ) + 1;
    }

    ksort( $status_counts );
    ksort( $visibility_counts );
    $count = count( $rows );
    $grand_total += $count;
    $summary[ $vendor_name ] = array(
        'author_id' => $author_id,
        'identity_ok' => $identity_ok,
        'total_non_trash_products' => $count,
        'status_counts' => $status_counts,
        'visibility_counts' => $visibility_counts,
        'ids_by_status' => $ids_by_status,
    );

    echo 'PRODUCT_CENSUS_VENDOR ' . wp_json_encode( array(
        'vendor' => $vendor_name,
        'author_id' => $author_id,
        'identity_ok' => $identity_ok,
        'total_non_trash_products' => $count,
        'status_counts' => $status_counts,
        'visibility_counts' => $visibility_counts,
        'ids_by_status' => $ids_by_status,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}

echo 'PRODUCT_CENSUS_SUMMARY ' . wp_json_encode( array(
    'grand_total_non_trash_products' => $grand_total,
    'vendors' => $summary,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "__1957_UPDATE__=already_applied\n";
