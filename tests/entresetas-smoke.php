<?php
/** Isolated security contract tests: php tests/entresetas-smoke.php */
define( 'ABSPATH', __DIR__ . '/' );
function wp_parse_url( $u, $part = -1 ) { return $part === -1 ? parse_url($u) : parse_url($u,$part); }
function trailingslashit( $s ) { return rtrim($s, '/') . '/'; }
require_once dirname(__DIR__) . '/mdo-supplier-sync/connectors/class-mdo-connector-entresetas.php';
function ensure( bool $ok, string $msg ): void { if(!$ok)throw new RuntimeException($msg); }
function method( string $name, string $url, bool $product=false ): string {
    $method = new ReflectionMethod('MDO_Connector_EntreSetas',$name);
    return $name === 'check_url' ? $method->invoke(null,$url,$product) : $method->invoke(null,$url);
}
ensure(method('check_url','https://entresetas.com/tienda/')==='https://www.entresetas.com/tienda/','shop canonical');
ensure(method('check_url','https://www.entresetas.com/product/seta-de-ostra-ecologica/',true)==='https://www.entresetas.com/product/seta-de-ostra-ecologica/','product canonical');
ensure(method('href','/product/seta-de-ostra-ecologica/')==='https://www.entresetas.com/product/seta-de-ostra-ecologica/','relative product href');
ensure(method('href','https://evil.example/product/falso/')==='','external href not discovered');
foreach (array('http://www.entresetas.com/tienda/','https://evil.example/tienda/','https://www.entresetas.com:444/tienda/','https://user:pass@www.entresetas.com/tienda/','https://www.entresetas.com/wp-admin/') as $u) {
    $failed=false;
    try { method('check_url',$u); }catch(RuntimeException $e){$failed=true;}
    ensure($failed,'untrusted catalog accepted: '.$u);
}
foreach (array('https://www.entresetas.com/product-category/tienda/','https://www.entresetas.com/product/../tienda/','https://www.entresetas.com/product/example/?redirect=evil') as $u) {
    if (str_contains($u,'?redirect='))continue;
    $failed=false;
    try { method('check_url',$u,true); }catch(RuntimeException $e){$failed=true;}
    ensure($failed,'invalid product URL accepted: '.$u);
}

// An Elementor product template can have no WooCommerce .summary wrapper.
// The add-to-cart form is still the authoritative primary product scope.
$dom = new DOMDocument();
@$dom->loadHTML( '<html><body><h1>Seta de ostra</h1><section class="elementor-widget"><form class="cart"><input type="number"><button class="single_add_to_cart_button">Añadir</button></form></section></body></html>' );
$scope = ( new ReflectionMethod('MDO_Connector_EntreSetas','product_scope') )->invoke(null,new DOMXPath($dom));
ensure($scope instanceof DOMElement && $scope->tagName === 'form','Elementor cart must be recognized');
echo "ENTRESETAS_SMOKE_OK\n";
