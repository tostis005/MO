<?php
if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "ABORT: WordPress not loaded\n" );
    exit( 2 );
}
if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "ABORT: WooCommerce not available\n" );
    exit( 3 );
}

const EMDO_ANA_MARY_TAX_CLASS = 'iva-4';
const EMDO_ANA_MARY_EXPECTED_PRODUCTS = 53;
const EMDO_ANA_MARY_SUPPLIER_ID = 4;

global $wpdb;

$rate_rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT tax_rate_id, tax_rate_country, tax_rate, tax_rate_name, tax_rate_class
         FROM {$wpdb->prefix}woocommerce_tax_rates
         WHERE tax_rate_class = %s AND tax_rate_country = %s",
        EMDO_ANA_MARY_TAX_CLASS,
        'ES'
    ),
    ARRAY_A
);

$valid_rates = array_values(
    array_filter(
        $rate_rows,
        static fn( array $row ): bool => abs( (float) $row['tax_rate'] - 4.0 ) < 0.0001
    )
);

if ( 1 !== count( $valid_rates ) ) {
    fwrite( STDERR, 'ABORT: expected exactly one ES 4% rate for class iva-4; found ' . count( $valid_rates ) . "\n" );
    echo wp_json_encode( $rate_rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
    exit( 4 );
}

$product_ids = get_posts(
    array(
        'post_type'      => 'product',
        'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    )
);

$targets = array();
foreach ( $product_ids as $product_id ) {
    $source_url  = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
    $supplier_id = absint( get_post_meta( $product_id, '_emdo_supplier_id', true ) );
    $host        = strtolower( (string) wp_parse_url( $source_url, PHP_URL_HOST ) );

    $is_huerta = in_array( $host, array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' ), true );
    if ( ! $is_huerta && EMDO_ANA_MARY_SUPPLIER_ID === $supplier_id && class_exists( 'MDO_Supplier_Repository' ) ) {
        $supplier = MDO_Supplier_Repository::find( $supplier_id );
        $is_huerta = $supplier
            && EMDO_ANA_MARY_SUPPLIER_ID === (int) $supplier['id']
            && 'la-huerta-ana-mary' === (string) ( $supplier['connector'] ?? '' );
    }

    if ( ! $is_huerta ) {
        continue;
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        fwrite( STDERR, "ABORT: Woo product unavailable for ID {$product_id}\n" );
        exit( 5 );
    }

    if ( 'publish' !== get_post_status( $product_id ) ) {
        fwrite( STDERR, "ABORT: mapped Ana Mary product {$product_id} is not published\n" );
        exit( 6 );
    }

    if ( $product->is_type( 'variable' ) || ! empty( $product->get_children() ) ) {
        fwrite( STDERR, "ABORT: unexpected variable Ana Mary product {$product_id}; manual review required\n" );
        exit( 7 );
    }

    $targets[] = array(
        'id'            => (int) $product_id,
        'name'          => $product->get_name(),
        'source_url'    => $source_url,
        'supplier_id'   => $supplier_id,
        'old_tax_status'=> $product->get_tax_status(),
        'old_tax_class' => $product->get_tax_class() === '' ? '(standard)' : $product->get_tax_class(),
    );
}

if ( EMDO_ANA_MARY_EXPECTED_PRODUCTS !== count( $targets ) ) {
    fwrite(
        STDERR,
        'ABORT: expected ' . EMDO_ANA_MARY_EXPECTED_PRODUCTS . ' mapped Ana Mary products; found ' . count( $targets ) . "\n"
    );
    echo wp_json_encode( $targets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    exit( 8 );
}

echo "=== PRECHANGE_SNAPSHOT ===\n";
echo 'tax_rate=' . wp_json_encode( $valid_rates[0], JSON_UNESCAPED_UNICODE ) . "\n";
echo 'target_count=' . count( $targets ) . "\n";
echo wp_json_encode( $targets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

$changed = 0;
$already_correct = 0;

foreach ( $targets as $target ) {
    $product = wc_get_product( $target['id'] );
    if ( ! $product ) {
        fwrite( STDERR, "ABORT: product disappeared during update: {$target['id']}\n" );
        exit( 9 );
    }

    if ( 'taxable' === $product->get_tax_status() && EMDO_ANA_MARY_TAX_CLASS === $product->get_tax_class() ) {
        $already_correct++;
        continue;
    }

    $product->set_tax_status( 'taxable' );
    $product->set_tax_class( EMDO_ANA_MARY_TAX_CLASS );
    $saved_id = $product->save();
    if ( (int) $saved_id !== (int) $target['id'] ) {
        fwrite( STDERR, "ABORT: WooCommerce failed saving product {$target['id']}\n" );
        exit( 10 );
    }
    wc_delete_product_transients( $target['id'] );
    clean_post_cache( $target['id'] );
    $changed++;
}

$failed = array();
$verified = array();
foreach ( $targets as $target ) {
    clean_post_cache( $target['id'] );
    $product = wc_get_product( $target['id'] );
    if ( ! $product ) {
        $failed[] = array( 'id' => $target['id'], 'reason' => 'missing_after_save' );
        continue;
    }

    $row = array(
        'id'         => (int) $target['id'],
        'name'       => $product->get_name(),
        'tax_status' => $product->get_tax_status(),
        'tax_class'  => $product->get_tax_class(),
    );
    $verified[] = $row;

    if ( 'taxable' !== $row['tax_status'] || EMDO_ANA_MARY_TAX_CLASS !== $row['tax_class'] ) {
        $failed[] = $row;
    }
}

if ( $failed ) {
    fwrite( STDERR, "ABORT: verification failed after update\n" );
    echo wp_json_encode( $failed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
    exit( 11 );
}

wp_cache_flush();

echo "=== RESULT ===\n";
echo 'target_count=' . count( $targets ) . "\n";
echo 'changed=' . $changed . "\n";
echo 'already_correct=' . $already_correct . "\n";
echo 'verified_iva4=' . count( $verified ) . "\n";
echo wp_json_encode( $verified, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
echo "ANA_MARY_TAX_UPDATE_OK\n";
