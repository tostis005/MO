<?php
// Offline regression tests: never require a live WordPress database.
define( 'ABSPATH', __DIR__ );
require_once __DIR__ . '/../mdo-supplier-sync/connectors/class-mdo-connector-latitudes.php';

function stock_from_html( string $html ): string {
    $doc = new DOMDocument();
    $doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
    $xp = new DOMXPath( $doc );
    $method = new ReflectionMethod( 'MDO_Connector_Latitudes', 'visible_stock' );
    return $method->invoke( null, $xp );
}
$cases = array(
    array( '<div class="availability">In stock</div><div class="stock in-stock">15 disponibles</div>', 'instock' ),
    array( '<div class="availability">Out of stock</div><div class="stock out-of-stock">Proximamente</div>', 'outofstock' ),
    array( '<div class="availability">?</div>', 'unknown' ),
);
foreach ( $cases as $case ) {
    $got = stock_from_html( $case[0] );
    if ( $got !== $case[1] ) {
        fwrite( STDERR, "Expected {$case[1]}, got {$got}\n" );
        exit( 1 );
    }
}
$raised = false;
try {
    stock_from_html( '<div class="availability">In stock</div><div class="stock out-of-stock">Proximamente</div>' );
} catch ( RuntimeException $e ) {
    $raised = true;
}
if ( ! $raised ) { fwrite( STDERR, 'Conflicting indicators did not fail closed.' ); exit( 2 ); }
echo "LATITUDES_STOCK_UNIT_OK\n";
