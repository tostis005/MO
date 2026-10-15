<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$connector_file = getenv( 'LATITUDES_CONNECTOR_FILE' );
if ( ! $connector_file || ! is_file( $connector_file ) ) { throw new RuntimeException( 'Falta conector de prueba.' ); }
require_once $connector_file;
$discovery = MDO_Connector_Latitudes::discover( array(
    'source_url' => 'https://latitudes.es/',
    'connector' => 'latitudes',
    'exclusion_url_fragments' => '[]',
) );
$urls = (array) $discovery['products'];
$samples = array();
foreach ( array_slice( $urls, 0, 4 ) as $url ) {
    try {
        $p = MDO_Connector_Latitudes::scrape_product( $url );
        $samples[] = array(
            'url' => $url, 'title' => $p['title'] ?? '', 'price' => $p['price'] ?? null,
            'image_count' => count( (array) ( $p['images'] ?? array() ) ),
            'desc_length' => strlen( wp_strip_all_tags( (string) ( $p['description'] ?? '' ) ) ),
            'stock' => $p['stock_status'] ?? 'unknown',
        );
    } catch ( Throwable $e ) {
        $samples[] = array( 'url' => $url, 'error' => $e->getMessage() );
    }
}
echo 'LATITUDES_CONNECTOR_TEST=' . wp_json_encode( array(
    'detected' => count( $urls ), 'pages' => count( $discovery['pages'] ),
    'first_urls' => array_slice( $urls, 0, 3 ),
    'samples' => $samples,
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
if ( count( $urls ) < 100 || count( $urls ) > 150 || count( $discovery['pages'] ) < 10 ) {
    throw new RuntimeException( 'Número de referencias/páginas fuera de rango; revisar conector.' );
}
foreach ( $samples as $p ) {
    if ( ! empty( $p['error'] ) || empty( $p['title'] ) ||
        ! is_numeric( $p['price'] ?? null ) || (float) $p['price'] <= 0 ||
        (int) $p['image_count'] < 1 ) {
        throw new RuntimeException( 'Ficha incompleta o scraper incompatible: ' . wp_json_encode( $p ) );
    }
}
echo 'LATITUDES_CONNECTOR_TEST_OK' . PHP_EOL;
