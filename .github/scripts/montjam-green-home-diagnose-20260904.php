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
$special_file = '';
if ( class_exists( 'MDO_Home_Featured_Special' ) ) {
    try {
        $ref = new ReflectionClass( 'MDO_Home_Featured_Special' );
        $special_file = (string) $ref->getFileName();
        echo 'CLASS_FILE=' . $special_file . "\n";
    } catch ( Throwable $e ) {
        echo 'CLASS_REFLECTION_ERROR=' . $e->getMessage() . "\n";
    }
    echo MDO_Home_Featured_Special::render() . "\n";
} else {
    echo "MDO_Home_Featured_Special missing\n";
}

if ( $special_file ) {
    $plugin_root = dirname( dirname( $special_file ) );
    echo "=== FEATURED SPECIAL STYLE MATCHES ===\n";
    $matches = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin_root, FilesystemIterator::SKIP_DOTS));
    foreach ( $it as $file ) {
        if ( ! $file->isFile() ) { continue; }
        $ext = strtolower( pathinfo( $file->getFilename(), PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, ['php','css'], true ) ) { continue; }
        $path = $file->getPathname();
        $text = @file_get_contents( $path );
        if ( false === $text || false === strpos( $text, 'mdo-home-featured-special__' ) ) { continue; }
        echo 'STYLE_FILE=' . $path . "\n";
        $lines = preg_split('/\R/', $text);
        foreach ( $lines as $i => $line ) {
            if ( false !== strpos( $line, 'mdo-home-featured-special__' ) ) {
                $start = max(0, $i - 4);
                $end = min(count($lines) - 1, $i + 12);
                for ( $j = $start; $j <= $end; $j++ ) {
                    echo sprintf("%05d %s\n", $j + 1, $lines[$j]);
                }
                echo "---\n";
                $matches++;
                if ( $matches >= 25 ) { break 2; }
            }
        }
    }
}
