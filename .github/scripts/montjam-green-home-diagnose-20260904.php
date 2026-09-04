<?php
if ( ! defined( 'ABSPATH' ) ) { exit( "WordPress not loaded\n" ); }

if ( ! function_exists( 'wc_get_products' ) ) {
    fwrite( STDERR, "WooCommerce unavailable\n" );
    exit( 2 );
}

$products = wc_get_products([
    'limit'  => -1,
    'status' => ['publish','draft','private','pending'],
    'type'   => ['simple','variable'],
    'return' => 'objects',
]);

echo "=== MONTJAM PRODUCT CANDIDATES ===\n";
foreach ( $products as $product ) {
    $id = $product->get_id();
    $author = (int) get_post_field( 'post_author', $id );
    $title = $product->get_name();
    $slug = get_post_field( 'post_name', $id );
    if ( 4723 !== $author && false === stripos( $title, 'Montjam' ) && false === stripos( $title, 'cebo de campo' ) ) {
        continue;
    }
    echo wp_json_encode([
        'id' => $id,
        'author' => $author,
        'status' => $product->get_status(),
        'type' => $product->get_type(),
        'title' => $title,
        'slug' => $slug,
        'price' => $product->get_price(),
        'regular_price' => $product->get_regular_price(),
        'children' => $product->is_type('variable') ? $product->get_children() : [],
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
    if ( $product->is_type('variable') ) {
        foreach ( $product->get_children() as $child_id ) {
            $v = wc_get_product( $child_id );
            if ( ! $v ) { continue; }
            echo '  VAR ' . wp_json_encode([
                'id' => $child_id,
                'status' => $v->get_status(),
                'price' => $v->get_price(),
                'regular_price' => $v->get_regular_price(),
                'attributes' => $v->get_attributes(),
                'sku' => $v->get_sku(),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
}

echo "=== HOME SPECIAL RENDER ===\n";
if ( class_exists( 'MDO_Home_Featured_Special' ) ) {
    try {
        $ref = new ReflectionClass( 'MDO_Home_Featured_Special' );
        echo 'CLASS_FILE=' . $ref->getFileName() . "\n";
    } catch ( Throwable $e ) {
        echo 'CLASS_REFLECTION_ERROR=' . $e->getMessage() . "\n";
    }
    echo MDO_Home_Featured_Special::render() . "\n";
} else {
    echo "MDO_Home_Featured_Special missing\n";
}
