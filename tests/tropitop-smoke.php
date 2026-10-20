<?php
/** Isolated connector/shipping contract smoke test; run: php tests/tropitop-smoke.php */
define( 'ABSPATH', __DIR__ . '/' );
function wp_parse_url( $url, $part = -1 ) { return $part === -1 ? parse_url( $url ) : parse_url( $url, $part ); }
function esc_url_raw( $url ) { return $url; }
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/connectors/class-mdo-connector-tropitop.php';
require_once dirname( __DIR__ ) . '/mdo-supplier-sync/includes/class-mdo-tropitop-shipping.php';

function verify( $truth, string $message ): void {
    if ( ! $truth ) { throw new RuntimeException( $message ); }
}
function reflected( string $method, ...$args ) {
    return ( new ReflectionMethod( 'MDO_Connector_Tropitop', $method ) )->invoke( null, ...$args );
}

$a = 'https://tropitop.com/es/aguacates/33-3063-comprar-aguacate-bacon.html#/407-kilos-900_kg';
$b = 'https://tropitop.com/es/aguacates/33-comprar-aguacate-bacon.html';
verify( reflected( 'canonical', $a ) === $b, 'Combos need one canonical source URL.' );
verify( reflected( 'canonical', $b ) === $b, 'Canonical URL must be stable.' );
verify( reflected( 'product_id', $b ) === '33', 'Preserve original product ID.' );
verify( reflected( 'category_url', $b ) === 'https://tropitop.com/es/12-aguacates', 'Spanish category URL is required.' );

$rejected = false;
try { reflected( 'canonical', 'http://tropitop.com/es/aguacates/33-comprar-aguacate-bacon.html' ); }
catch ( RuntimeException $e ) { $rejected = true; }
verify( $rejected, 'HTTP links must be rejected.' );
$rejected = false;
try { reflected( 'canonical', 'https://example.org/es/aguacates/33-comprar-aguacate-bacon.html' ); }
catch ( RuntimeException $e ) { $rejected = true; }
verify( $rejected, 'Untrusted hosts must be rejected.' );

verify( MDO_Tropitop_Shipping::allowed( 'ES', '28013' ), 'Madrid allowed.' );
verify( ! MDO_Tropitop_Shipping::allowed( 'ES', '' ), 'Postcode is mandatory for a shipping quote.' );
verify( ! MDO_Tropitop_Shipping::allowed( 'ES', '07001' ), 'Balearics excluded.' );
verify( ! MDO_Tropitop_Shipping::allowed( 'ES', '35001' ), 'Canaries excluded.' );
verify( ! MDO_Tropitop_Shipping::allowed( 'ES', '51001' ), 'Ceuta excluded.' );
verify( ! MDO_Tropitop_Shipping::allowed( 'PT', '1000-001' ), 'Portugal blocked until validated quotes.' );
verify( ! MDO_Tropitop_Shipping::allowed( 'PT', '9000-001' ), 'Portuguese islands excluded.' );
verify( ! MDO_Tropitop_Shipping::allowed( 'FR', '75001' ), 'France blocked until validated quotes.' );
verify( ! MDO_Tropitop_Shipping::allowed( 'US', '10001' ), 'Outside supply list blocked.' );

if ( class_exists( 'DOMDocument' ) && class_exists( 'DOMXPath' ) ) {
    $doc = new DOMDocument();
    @$doc->loadHTML( '<?xml encoding="UTF-8" ?><div class="product-variants"><ul>'
        . '<li><a>3.00 kg 14,90 €</a></li><li><a>4.00 kg 17,90 €</a></li>'
        . '<li><a>9.00 kg 32,90 €</a></li></ul></div>'
        . '<aside class="related"><a>9 kg 2,00 €</a></aside>', LIBXML_NONET );
    $xp = new DOMXPath( $doc );
    $sizes = reflected( 'sizes', $xp );
    verify( count( $sizes ) === 3, 'Three official weights, no related products.' );
    verify( abs( (float) $sizes['9.000']['price'] - 32.90 ) < 0.001, 'Weight and exact box price must match.' );
}
echo "Tropitop connector and shipping smoke checks passed.\n";

$expected = array( 3 => 2.95, 4 => 2.95, 6 => 5.95, 8 => 5.95, 9 => 5.95,
    12 => 7.95, 18 => 10.95, 20 => 10.95, 24 => 12.95,
    27 => 15.95, 36 => 20.95 );
foreach ( $expected as $kg => $price ) {
    verify( MDO_Tropitop_Shipping::cost_for_weight( (float) $kg ) === $price,
        'Source cart shipping mismatch for ' . $kg . 'kg.' );
}
verify( null === MDO_Tropitop_Shipping::cost_for_weight( 33.0 ),
    'Unverified weight bracket must not be guessed.' );


// The source storefront sometimes leaves the buy button clickable while
// simultaneously displaying "no disponible temporalmente". Never import
// that as purchasable stock; only a verified positive availability qualifies.
$status_method = new ReflectionMethod( MDO_Connector_Tropitop::class, 'stock' );
$fixture = new DOMDocument();
$fixture->loadHTML( '<html><body><div id="product-availability">no disponible temporalmente</div>'
    . '<button data-button-action="add-to-cart">Comprar</button></body></html>' );
verify( $status_method->invoke( null, new DOMXPath( $fixture ) ) === 'outofstock',
    'Negative Prestashop stock label must override an enabled buy button.' );
$fixture2 = new DOMDocument();
$fixture2->loadHTML( '<html><body><div id="product-availability"></div>'
    . '<button data-button-action="add-to-cart">Comprar</button></body></html>' );
verify( $status_method->invoke( null, new DOMXPath( $fixture2 ) ) === 'instock',
    'Enabled buy button without stock warning is in stock.' );
$fixture3 = new DOMDocument();
$fixture3->loadHTML( '<html><body><div id="product-availability"></div>'
    . '<button data-button-action="add-to-cart" disabled="disabled">Agotado</button></body></html>' );
verify( $status_method->invoke( null, new DOMXPath( $fixture3 ) ) === 'outofstock',
    'Disabled and sold-out Prestashop buy button is out of stock.' );
