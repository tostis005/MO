<?php
/**
 * One-off, price-only production update for the seven published Montjam ham/paleta products.
 * Source: supplier table supplied 2026-09-22 ("Con margen").
 *
 * Safety:
 * - requires exactly seven published Montjam variable products assigned to the Montjam producer taxonomy;
 * - classifies each product by its current public title;
 * - updates ONLY published variation regular prices;
 * - does not create/remove variations or touch content, images, taxonomies, stock, SKU or sale prices;
 * - aborts before writes if any published weight is not present in the supplied price table.
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "ABORT: WordPress not loaded\n" );
    exit( 2 );
}
if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "ABORT: WooCommerce unavailable\n" );
    exit( 3 );
}


$price_table = [
    'jamon_negra_dop' => [
        '6-65-kg' => '311.85',
        '65-7-kg' => '336.798',
        '7-75-kg' => '361.746',
        '75-8-kg' => '386.694',
        '8-85-kg' => '411.642',
        '85-9-kg' => '436.59',
        '9-95-kg' => '461.538',
    ],
    'jamon_negra' => [
        '6-65-kg' => '227.91',
        '65-7-kg' => '246.14',
        '7-75-kg' => '310.07',
        '75-8-kg' => '331.45',
        '8-85-kg' => '352.84',
        '85-9-kg' => '374.22',
        '9-95-kg' => '395.60',
    ],
    'jamon_roja' => [
        '7-75-kg' => '284.229',
        '75-8-kg' => '303.831',
        '8-85-kg' => '323.433',
        '85-9-kg' => '343.035',
        '9-95-kg' => '362.637',
    ],
    'jamon_verde' => [
        '75-8-kg' => '220.97',
        '8-85-kg' => '235.22',
        '85-9-kg' => '249.48',
        '9-95-kg' => '263.74',
    ],
    'paleta_negra_dop' => [
        '4-45-kg' => '141.372',
        '45-5-kg' => '158.004',
        '5-55-kg' => '174.636',
        '55-6-kg' => '191.268',
    ],
    'paleta_negra' => [
        '4-45-kg' => '121.176',
        '45-5-kg' => '135.432',
        '5-55-kg' => '149.688',
        '55-6-kg' => '163.944',
    ],
    'paleta_verde' => [
        '45-5-kg' => '95.931',
        '5-55-kg' => '106.029',
        '55-6-kg' => '116.127',
    ],
];

function mdo_montjam_price_key( string $title ): string {
    $exact = [
        'Jamón de bellota 100% ibérico D.O.P. Jabugo Montjam (brida negra)' => 'jamon_negra_dop',
        'Jamón de bellota 100% ibérico Montjam (brida negra)' => 'jamon_negra',
        'Jamón de bellota ibérico 50% raza ibérica Montjam (brida roja)' => 'jamon_roja',
        'Jamón de cebo de campo ibérico 50% raza ibérica Montjam (brida verde)' => 'jamon_verde',
        'Paleta de bellota 100% ibérica D.O.P. Jabugo Montjam (brida negra)' => 'paleta_negra_dop',
        'Paleta de bellota 100% ibérica Montjam (brida negra)' => 'paleta_negra',
        'Paleta de cebo de campo ibérica 50% raza ibérica Montjam (brida verde)' => 'paleta_verde',
    ];
    return $exact[ $title ] ?? '';
}

function mdo_montjam_parent_snapshot( int $product_id ): array {
    return [
        'title'       => get_the_title( $product_id ),
        'slug'        => (string) get_post_field( 'post_name', $product_id ),
        'content'     => (string) get_post_field( 'post_content', $product_id ),
        'excerpt'     => (string) get_post_field( 'post_excerpt', $product_id ),
        'thumbnail'   => (int) get_post_thumbnail_id( $product_id ),
        'gallery'     => (string) get_post_meta( $product_id, '_product_image_gallery', true ),
        'author'      => (int) get_post_field( 'post_author', $product_id ),
    ];
}

$producer = get_term_by( 'slug', 'montjam', 'pa_productor' );
if ( ! $producer || is_wp_error( $producer ) ) {
    fwrite( STDERR, "ABORT: Montjam producer term was not found\n" );
    exit( 4 );
}

$ids = get_posts( [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'orderby'        => 'ID',
    'order'          => 'ASC',
    'tax_query'      => [
        [
            'taxonomy' => 'pa_productor',
            'field'    => 'term_id',
            'terms'    => [ (int) $producer->term_id ],
        ],
    ],
] );
$ids = array_values( array_map( 'intval', $ids ) );

if ( count( $ids ) !== 7 ) {
    fwrite( STDERR, 'ABORT: expected exactly 7 published Montjam products from pa_productor=montjam, found ' . count( $ids ) . "\n" );
    exit( 4 );
}

$plan = [];
$parents_before = [];
$seen_keys = [];

foreach ( $ids as $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        fwrite( STDERR, "ABORT: product {$product_id} is not variable\n" );
        exit( 5 );
    }

    $title = $product->get_name();
    $key = mdo_montjam_price_key( $title );
    if ( ! $key || ! isset( $price_table[ $key ] ) ) {
        fwrite( STDERR, "ABORT: unrecognized Montjam product identity #{$product_id}: {$title}\n" );
        exit( 6 );
    }
    if ( isset( $seen_keys[ $key ] ) ) {
        fwrite( STDERR, "ABORT: duplicate Montjam product class {$key}\n" );
        exit( 7 );
    }
    $seen_keys[ $key ] = $product_id;
    $parents_before[ $product_id ] = mdo_montjam_parent_snapshot( $product_id );

    $published = 0;
    foreach ( $product->get_children() as $variation_id ) {
        $variation_id = (int) $variation_id;
        $variation = wc_get_product( $variation_id );
        if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
            fwrite( STDERR, "ABORT: invalid variation {$variation_id} under product {$product_id}\n" );
            exit( 8 );
        }
        if ( 'publish' !== $variation->get_status() ) {
            continue; // intentionally leave disabled/private legacy variations untouched
        }

        $published++;
        $attrs = $variation->get_attributes();
        $size = isset( $attrs['pa_tamano'] ) ? (string) $attrs['pa_tamano'] : '';
        if ( ! $size || ! array_key_exists( $size, $price_table[ $key ] ) ) {
            fwrite( STDERR, "ABORT: no supplied price for {$key} published size '{$size}' (variation {$variation_id})\n" );
            exit( 9 );
        }

        $sale = (string) $variation->get_sale_price( 'edit' );
        if ( $sale !== '' ) {
            fwrite( STDERR, "ABORT: sale price is not empty on variation {$variation_id}; refusing to change sale logic\n" );
            exit( 10 );
        }

        $plan[] = [
            'product_id'       => $product_id,
            'product_key'      => $key,
            'product_title'    => $title,
            'variation_id'     => $variation_id,
            'size'             => $size,
            'old_regular'      => (string) $variation->get_regular_price( 'edit' ),
            'old_price'        => (string) $variation->get_price( 'edit' ),
            'new_regular'      => (string) $price_table[ $key ][ $size ],
            'sku'              => (string) $variation->get_sku(),
            'status'           => (string) $variation->get_status(),
            'stock_status'     => (string) $variation->get_stock_status(),
            'attributes'       => $attrs,
        ];
    }

    if ( $published < 1 ) {
        fwrite( STDERR, "ABORT: product {$product_id} has no published variations\n" );
        exit( 11 );
    }
}

$expected_keys = array_keys( $price_table );
sort( $expected_keys );
$actual_keys = array_keys( $seen_keys );
sort( $actual_keys );
if ( $expected_keys !== $actual_keys ) {
    fwrite( STDERR, "ABORT: the seven Montjam product classes do not match the supplied table\n" );
    exit( 12 );
}

echo "=== PRICE PLAN (NO WRITES YET) ===\n";
echo wp_json_encode( $plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

foreach ( $plan as $row ) {
    $variation = wc_get_product( (int) $row['variation_id'] );
    $variation->set_regular_price( $row['new_regular'] );
    // Deliberately do not call set_sale_price(): sale_price must remain exactly as it was (empty).
    $variation->save();
}

foreach ( array_keys( $parents_before ) as $product_id ) {
    WC_Product_Variable::sync( (int) $product_id, true );
    wc_delete_product_transients( (int) $product_id );
    clean_post_cache( (int) $product_id );
}
wp_cache_flush();

$after = [];
foreach ( $plan as $row ) {
    $variation = wc_get_product( (int) $row['variation_id'] );
    $attrs = $variation ? $variation->get_attributes() : [];
    $check = [
        'product_id'      => (int) $row['product_id'],
        'variation_id'    => (int) $row['variation_id'],
        'size'            => $attrs['pa_tamano'] ?? '',
        'regular_price'   => $variation ? (string) $variation->get_regular_price( 'edit' ) : '',
        'price'           => $variation ? (string) $variation->get_price( 'edit' ) : '',
        'sale_price'      => $variation ? (string) $variation->get_sale_price( 'edit' ) : '',
        'sku'             => $variation ? (string) $variation->get_sku() : '',
        'status'          => $variation ? (string) $variation->get_status() : '',
        'stock_status'    => $variation ? (string) $variation->get_stock_status() : '',
    ];

    if (
        ! $variation
        || abs( (float) $check['regular_price'] - (float) $row['new_regular'] ) > 0.0005
        || abs( (float) $check['price'] - (float) $row['new_regular'] ) > 0.0005
        || $check['sale_price'] !== ''
        || $check['sku'] !== $row['sku']
        || $check['status'] !== $row['status']
        || $check['stock_status'] !== $row['stock_status']
        || $attrs !== $row['attributes']
    ) {
        fwrite( STDERR, "ABORT: post-write verification failed for variation {$row['variation_id']}\n" );
        echo wp_json_encode( $check, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
        exit( 13 );
    }
    $after[] = $check;
}

foreach ( $parents_before as $product_id => $snapshot ) {
    $fresh = mdo_montjam_parent_snapshot( (int) $product_id );
    if ( $fresh !== $snapshot ) {
        fwrite( STDERR, "ABORT: non-price parent product fields changed unexpectedly for product {$product_id}\n" );
        echo wp_json_encode( [ 'before' => $snapshot, 'after' => $fresh ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
        exit( 14 );
    }
}

echo "=== VERIFIED AFTER ===\n";
echo wp_json_encode( $after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "MONTJAM_PRICE_UPDATE_OK\n";
