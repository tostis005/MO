<?php
/** Isolated connector/shipping contract smoke test; run: php tests/tropitop-smoke.php */
define( 'ABSPATH', __DIR__ . '/' );
function wp_parse_url( $url, $part = -1 ) { return $part === -1 ? parse_url( $url ) : parse_url( $url, $part ); }
function esc_url_raw( $url ) { return $url; }
function sanitize_text_field( $text ) { return trim( strip_tags( (string) $text ) ); }
function wp_strip_all_tags( $text ) { return strip_tags( (string) $text ); }
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
foreach ( array(
    'disponible' => '84-disponible',
    'cestas-de-fruta' => '52-cestas-de-fruta',
    'bio' => '109-bio',
    'mangos' => '10-mangos',
    'apadrina-un-arbol' => '77-apadrina-un-arbol',
) as $slug => $category ) {
    $source_url = 'https://tropitop.com/es/' . $slug . '/1234-una-caja.html';
    verify( reflected( 'category_url', $source_url ) === 'https://tropitop.com/es/' . $category,
        'Known producer category must resolve: ' . $slug );
}

// If an official product breadcrumb supplies the same category slug and a
// numeric category ID, recover it without guessing the vendor's taxonomy.
$bdoc = new DOMDocument();
@$bdoc->loadHTML( '<?xml encoding="UTF-8" ?><nav class="breadcrumb">'
    . '<a href="https://tropitop.com/es/315-fruta-de-temporada">Temporada</a>'
    . '</nav><aside><a href="https://example.org/es/315-fruta-de-temporada">Invalid</a></aside>' );
verify( reflected( 'category_url',
    'https://tropitop.com/es/fruta-de-temporada/2340-algo.html',
    new DOMXPath( $bdoc ) ) === 'https://tropitop.com/es/315-fruta-de-temporada',
    'Only a matching producer breadcrumb may prove a new category URL.' );

$unknown_category_rejected = false;
try { reflected( 'category_url', 'https://tropitop.com/es/otra-categoria/1234-una-caja.html' ); }
catch ( RuntimeException $e ) { $unknown_category_rejected = true; }
verify( $unknown_category_rejected, 'Unknown producer category cannot be guessed.' );


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
    verify( abs( (float) $sizes['kg:9.000']['price'] - 32.90 ) < 0.001, 'Weight and exact box price must match.' );
}
if ( class_exists( 'DOMDocument' ) ) {
    $u = new DOMDocument();
    @$u->loadHTML( '<?xml encoding="UTF-8" ?><div class="product-variants"><ul><li><a>5.00 unid 32,90 €</a></li></ul></div>' );
    $unit_sizes = reflected( 'sizes', new DOMXPath( $u ) );
    verify( count( $unit_sizes ) === 1 && ( $unit_sizes['unid:5.000']['unit'] ?? '' ) === 'unid',
        'Unit presentations must not be mislabelled as kilograms.' );
}

// Real-world producer format: two different 4-jar boxes with separate
// prices. Do not collapse to one "4 unidades" variant or borrow prices from
// recommendation cards elsewhere on the page.
if ( class_exists( 'DOMDocument' ) ) {
    $d = new DOMDocument();
    @$d->loadHTML( '<?xml encoding="UTF-8" ?><div class="product-variants">'
        . '<div class="variant-box">Tradicional - 4 u. 17,90 €</div>'
        . '<div class="variant-box">Sin azúcar - 4 u. 23,90 €</div>'
        . '</div><aside class="related"><div class="variant-box">3 kg 4,90 €</div></aside>', LIBXML_NONET );
    $options = reflected( 'sizes', new DOMXPath( $d ) );
    verify( count( $options ) === 2, 'Both labeled 4-unit packs must be separate variants.' );
    $by_label = array();
    foreach ( $options as $opt ) { $by_label[ $opt['label'] ] = $opt; }
    verify( isset( $by_label['Tradicional'], $by_label['Sin azúcar'] ), 'Both pack labels must be preserved.' );
    verify( abs( (float) $by_label['Tradicional']['price'] - 17.9 ) < 0.001, 'Traditional pack price verified.' );
    verify( abs( (float) $by_label['Sin azúcar']['price'] - 23.9 ) < 0.001, 'Sugar-free pack price verified.' );
    verify( (string) $by_label['Tradicional']['unit'] === 'unid' &&
        (float) $by_label['Tradicional']['amount'] === 4.0,
        'Units must never be converted to kilograms.' );
    $d1 = new DOMDocument();
    @$d1->loadHTML( '<?xml encoding="UTF-8" ?><div class="product-variants">'
        . '<div class="variant-box">4.00 unid 22,90 €</div></div>', LIBXML_NONET );
    verify( count( reflected( 'sizes', new DOMXPath( $d1 ) ) ) === 1,
        'Single correctly priced 4-unit product must be parsable.' );
}

// PrestaShop quantity-by-volume variants: neither 1L nor 200ml is 1kg.
$doc = new DOMDocument();
@$doc->loadHTML( '<?xml encoding="UTF-8" ?><div class="product-variants">'
    . '<div class="variant-box">3 x 1L 13,90 €</div>'
    . '<div class="variant-box">5 x 1L 19,90 €</div>'
    . '</div><aside><div class="variant-box">8kg 2,90 €</div></aside>' );
$volume = reflected( 'sizes', new DOMXPath( $doc ) );
verify( count( $volume ) === 2, '3x1L and 5x1L must remain separate.' );
verify( abs( (float) ( $volume['unid:3.000:1 l cada unidad']['price'] ?? 0 ) - 13.90 ) < 0.001,
    'Three bottles retain verified producer price.' );
verify( ! isset( $volume['unid:3.000:1 l cada unidad']['weight_kg'] ),
    'Volume alone is not verified shipping weight.' );
$doc = new DOMDocument();
@$doc->loadHTML( '<?xml encoding="UTF-8" ?><div class="product-variants">'
    . '<div class="variant-box">12 x 200ml 15,90 €</div>'
    . '<div class="variant-box">6 x 250ml 18,90 €</div></div>' );
verify( count( reflected( 'sizes', new DOMXPath( $doc ) ) ) === 2,
    '200ml and 250ml multipacks must parse.' );
$doc = new DOMDocument();
@$doc->loadHTML( '<?xml encoding="UTF-8" ?><div class="product-variants">'
    . '<div class="variant-box">5.00 unid 32,90 € El kilo te sale a:5,48 € / kg</div>'
    . '</div>' );
$basket = reflected( 'sizes', new DOMXPath( $doc ) );
verify( count( $basket ) === 1, 'Unit price must not block a real price.' );
verify( abs( (float) ( $basket['unid:5.000']['weight_kg'] ?? 0 ) - 6.0 ) < 0.001,
    'Direct source EUR/kg quote yields this basket 6kg weight.' );

// Merchant's literal 1:1 tariff convention with NO packaging added.
verify( abs( (float) reflected( 'volume_in_liters', 1.0, 'L' ) - 1.0 ) < .001,
    'One liter is one liter of net liquid.' );
verify( abs( (float) reflected( 'volume_in_liters', 200.0, 'ml' ) - .2 ) < .001,
    '200ml is 0.2L.' );
verify( null === reflected( 'volume_in_liters', 4.0, 'unid' ),
    'Unit count must not be interpreted as liquid.' );
verify( abs( (float) reflected( 'estimated_chargeable_weight', 3.0 ) - 3.0 ) < .001,
    '3x1L is exactly 3kg for shipping.' );
verify( abs( (float) reflected( 'estimated_chargeable_weight', 5.0 ) - 5.0 ) < .001,
    '5x1L is exactly 5kg for shipping.' );
verify( abs( (float) reflected( 'estimated_chargeable_weight', 2.4 ) - 2.4 ) < .001,
    '12x200ml is exactly 2.4kg for shipping.' );
verify( abs( (float) reflected( 'estimated_jar_weight',
    array( 'unit' => 'unid', 'amount' => 4.0 ),
    '<p>1 caja contiene 4 botes de 200g de mermelada.</p>' ) - 0.8 ) < .001,
    'Four 200g jars charge only the exact 0.8kg contents.' );
verify( null === reflected( 'estimated_jar_weight',
    array( 'unit' => 'unid', 'amount' => 5.0 ), '<p>Cinco frutas.</p>' ),
    'Piece-based fruit stays unestimated.' );
verify( abs( (float) reflected( 'estimated_chargeable_weight', 29.0 ) - 29.0 ) < .001,
    'Existing 29kg shipping tariff can be used without an added buffer.' );
verify( null === reflected( 'estimated_chargeable_weight', 30.5 ),
    'Do not invent shipping rates for unverified high weight bands.' );

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
