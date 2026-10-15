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

$home = wp_safe_remote_get( 'https://latitudes.es/', array( 'timeout' => 20 ) );
if ( ! is_wp_error( $home ) ) {
    $home_html = (string) wp_remote_retrieve_body( $home );
    $doc = new DOMDocument();
    $old = libxml_use_internal_errors( true );
    $doc->loadHTML( '<?xml encoding="utf-8" ?>' . $home_html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
    libxml_clear_errors();
    libxml_use_internal_errors( $old );
    $xp = new DOMXPath( $doc );
    $img_list = array();
    foreach ( $xp->query( '//img[@src]' ) ?: array() as $img ) {
        $src = (string) $img->getAttribute( 'src' );
        $alt = (string) $img->getAttribute( 'alt' );
        $cl = (string) $img->getAttribute( 'class' );
        if ( preg_match( '/logo|banner|latitudes|hero|slider/i', $src . ' ' . $alt . ' ' . $cl ) ) {
            $img_list[] = array( 'src' => $src, 'alt' => $alt, 'class' => $cl );
        }
    }
    $backgrounds = array();
    foreach ( $xp->query( '//*[@style]' ) ?: array() as $node ) {
        if ( preg_match( '/background-image\\s*:\\s*url\\([\\x22\\x27]?([^\\)\\x22\\x27]+)/i', (string) $node->getAttribute( 'style' ), $m ) ) {
            $backgrounds[] = (string) $m[1];
        }
    }
    echo 'LATITUDES_ASSETS=' . wp_json_encode( array( 'images' => array_slice( $img_list, 0, 25 ), 'backgrounds' => array_slice( array_unique( $backgrounds ), 0, 15 ) ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
}

echo 'LATITUDES_CONNECTOR_TEST_OK' . PHP_EOL;
