<?php
/**
 * Regression for the 2026/27 harvest commercial SEO first batch.
 * Offline: only checks narrow metadata maps and the homepage CTA.
 */
define( 'ABSPATH', __DIR__ );
function add_action( $name, $callback, $priority = 10 ) {}
function add_filter( $name, $callback, $priority = 10 ) {}
function esc_url( $value ) { return $value; }

require __DIR__ . '/../mu-plugins/mdo-gsc-commerce-seo-20260929.php';
require __DIR__ . '/../mu-plugins/emdo-home-aove-harvest-promo-20261030.php';

$targets = array(
    '/producto/cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio/' => '5L',
    '/producto/sin-filtrar-nueva-campana-26-27-aceite-de-oliva-virgen-extra-15x1l/' => '15×1L',
    '/producto/aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27/' => '12×500ml',
);
$map = mdo_gsc_commerce_map_20260929();
foreach ( $targets as $path => $format ) {
    if ( ! isset( $map[ $path ] ) ) {
        fwrite( STDERR, "Missing harvest SEO target: $path\n" );
        exit( 1 );
    }
    $title = $map[ $path ]['title'] ?? '';
    $description = $map[ $path ]['description'] ?? '';
    if ( ! str_contains( $title, $format )
        || ! str_contains( $title, '26/27' )
        || ! str_contains( $description, '26/27' )
        || ! str_contains( $description, 'sin filtrar' )
        || ! str_contains( $description, 'tradicional' )
        || str_contains( $title, 'SIN FILTRAR' ) ) {
        fwrite( STDERR, "Misleading harvest metadata: $path\n" );
        exit( 1 );
    }
}
if ( ( $map['/categoria-producto/aceites/']['title'] ?? '' ) !== 'Comprar Aceite de Oliva Virgen Extra | El Mercado de Origen' ) {
    fwrite( STDERR, "Unexpected change to evergreen Aceites metadata\n" );
    exit( 1 );
}
if ( ( $map['/tienda/1957/']['title'] ?? '' ) !== 'Aceite de Oliva Virgen Extra 1957 | Cosecha 2026/27'
    || ! str_contains( $map['/tienda/1957/']['description'] ?? '', 'productor 1957' ) ) {
    fwrite( STDERR, "Missing 1957 vendor commercial SEO metadata\n" );
    exit( 1 );
}
$banner = emdo_home_aove_banner_markup_20261030();
if ( ! str_contains( $banner, 'href="https://www.elmercadodeorigen.com/tienda/1957/"' )
    || str_contains( $banner, 'href="https://www.elmercadodeorigen.com/categoria-producto/aceites/"' ) ) {
    fwrite( STDERR, "Harvest banner does not point to 1957 producer store\n" );
    exit( 1 );
}
echo "HARVEST_COMMERCIAL_SEO_TESTS_OK\n";
