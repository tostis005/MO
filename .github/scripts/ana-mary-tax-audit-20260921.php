<?php
if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "ABORT: WordPress not loaded\n" );
    exit( 2 );
}
if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "ABORT: WooCommerce not available\n" );
    exit( 3 );
}

function emdo_norm_name( $value ) {
    $value = remove_accents( wp_strip_all_tags( (string) $value ) );
    $value = strtolower( trim( preg_replace( '/\s+/', ' ', $value ) ) );
    return $value;
}

$needles = array(
    'la huerta de ana mary',
    'la huerta de ana mari',
    'huerta de ana mary',
    'huerta de ana mari',
);

$candidates = array();
foreach ( get_users( array( 'fields' => 'all' ) ) as $user ) {
    $profile = get_user_meta( $user->ID, 'wcfmmp_profile_settings', true );
    $store_name = is_array( $profile ) && ! empty( $profile['store_name'] ) ? (string) $profile['store_name'] : '';
    $meta_store_name = (string) get_user_meta( $user->ID, 'store_name', true );

    $values = array_filter( array(
        $user->display_name,
        $user->user_login,
        $user->user_nicename,
        $store_name,
        $meta_store_name,
    ) );

    foreach ( $values as $value ) {
        $norm = emdo_norm_name( $value );
        foreach ( $needles as $needle ) {
            if ( $norm === $needle || strpos( $norm, 'huerta de ana mar' ) !== false ) {
                $candidates[ $user->ID ] = array(
                    'id' => (int) $user->ID,
                    'display_name' => $user->display_name,
                    'login' => $user->user_login,
                    'store_name' => $store_name ?: $meta_store_name,
                );
                break 2;
            }
        }
    }
}

echo "=== VENDOR_CANDIDATES ===\n";
echo wp_json_encode( array_values( $candidates ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";

if ( count( $candidates ) !== 1 ) {
    fwrite( STDERR, 'ABORT: expected exactly one vendor candidate, found ' . count( $candidates ) . "\n" );
    exit( 4 );
}

$vendor_id = (int) array_key_first( $candidates );

global $wpdb;
$tax_rows = $wpdb->get_results(
    "SELECT tax_rate_id, tax_rate_country, tax_rate_state, tax_rate, tax_rate_name, tax_rate_priority, tax_rate_compound, tax_rate_shipping, tax_rate_order, tax_rate_class
     FROM {$wpdb->prefix}woocommerce_tax_rates
     ORDER BY tax_rate_class, tax_rate_order, tax_rate_id",
    ARRAY_A
);

echo "=== TAX_RATES ===\n";
echo wp_json_encode( $tax_rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";

$product_ids = get_posts( array(
    'post_type'      => 'product',
    'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
    'author'         => $vendor_id,
    'posts_per_page' => -1,
    'orderby'        => 'ID',
    'order'          => 'ASC',
    'fields'         => 'ids',
    'no_found_rows'  => true,
) );

$rows = array();
$class_counts = array();
$variation_count = 0;

foreach ( $product_ids as $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        continue;
    }

    $tax_class = (string) $product->get_tax_class();
    $tax_status = (string) $product->get_tax_status();
    $key = $tax_status . '|' . ( $tax_class === '' ? '(standard)' : $tax_class );
    $class_counts[ $key ] = isset( $class_counts[ $key ] ) ? $class_counts[ $key ] + 1 : 1;

    $rows[] = array(
        'id' => (int) $product_id,
        'name' => $product->get_name(),
        'type' => $product->get_type(),
        'status' => get_post_status( $product_id ),
        'tax_status' => $tax_status,
        'tax_class' => $tax_class === '' ? '(standard)' : $tax_class,
        'variation_count' => $product->is_type( 'variable' ) ? count( $product->get_children() ) : 0,
    );

    if ( $product->is_type( 'variable' ) ) {
        $variation_count += count( $product->get_children() );
    }
}

echo "=== PRODUCT_SUMMARY ===\n";
echo 'vendor_id=' . $vendor_id . "\n";
echo 'products=' . count( $rows ) . "\n";
echo 'variations=' . $variation_count . "\n";
echo 'tax_buckets=' . wp_json_encode( $class_counts, JSON_UNESCAPED_UNICODE ) . "\n";

echo "=== PRODUCTS ===\n";
echo wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";

if ( count( $rows ) < 1 ) {
    fwrite( STDERR, "ABORT: no products found for vendor\n" );
    exit( 5 );
}

echo "AUDIT_OK\n";
