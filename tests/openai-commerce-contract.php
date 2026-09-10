<?php
/**
 * Static regression tests for the EMDO OpenAI Commerce integration.
 * Run: php tests/openai-commerce-contract.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$files = array(
    'loader'    => $root . '/mdo-supplier-sync/mdo-supplier-sync.php',
    'core'      => $root . '/mdo-supplier-sync/includes/openai/class-mdo-openai-commerce.php',
    'transport' => $root . '/mdo-supplier-sync/includes/openai/class-mdo-openai-transports.php',
    'admin'     => $root . '/mdo-supplier-sync/includes/openai/class-mdo-openai-admin.php',
);

foreach ($files as $name => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "FAIL: missing {$name}: {$path}\n");
        exit(1);
    }
}

$src = array_map(static fn(string $path): string => (string) file_get_contents($path), $files);
$failures = array();
$passes = 0;

$assert = static function (bool $condition, string $message) use (&$failures, &$passes): void {
    if ($condition) {
        ++$passes;
        echo "PASS: {$message}\n";
    } else {
        $failures[] = $message;
        echo "FAIL: {$message}\n";
    }
};
$contains = static fn(string $haystack, string $needle): bool => false !== strpos($haystack, $needle);

$assert($contains($src['loader'], "includes/openai/class-mdo-openai-commerce.php") && $contains($src['loader'], "includes/openai/class-mdo-openai-transports.php") && $contains($src['loader'], "includes/openai/class-mdo-openai-admin.php"), 'EMDO loads OpenAI core, transports and admin');
$assert($contains($src['loader'], 'Plugin Name: EMDO'), 'OpenAI Commerce is integrated into EMDO');
$assert($contains($src['core'], "'format'                           => 'native_jsonl_gz'"), 'native JSONL.gz is default');
$assert($contains($src['core'], "'schedule'                         => 'six_hours'"), 'six-hour generation is default');
$assert($contains($src['core'], "'delivery'                         => 'none'"), 'delivery is disabled by default');
$assert($contains($src['core'], "'seller_model'                     => 'marketplace_vendor'"), 'WCFM vendor is default seller model');
$assert($contains($src['core'], "'marketplace_enabled'              => '0'"), 'marketplace_seller is disabled by default');
$assert($contains($src['core'], "'shipping_capability_confirmed'    => '0'"), 'shipping capability is disabled by default');
$assert($contains($src['core'], "'returns_enabled'                  => '0'"), 'returns are disabled by default');
$assert($contains($src['core'], 'mercado-de-origen-products.jsonl.gz'), 'stable native snapshot filename is present');
$assert($contains($src['core'], 'MDO_OPENAI_COMMERCE_TOMBSTONE_DAYS = 14'), 'missing offers are retained for 14 days');

foreach (array('item_id','title','description','url','brand','seller_name','image_url','availability','price') as $field) {
    $assert($contains($src['core'], "'{$field}'"), "native required field {$field} is mapped");
}

$assert($contains($src['core'], "'wc-' . (int) \$product->get_parent_id() . '-v'"), 'variation fallback item_id is stable Woo parent+variation ID');
$assert($contains($src['core'], "'wc-' . (int) \$product->get_id()"), 'simple-product fallback item_id is stable Woo ID');
$assert($contains($src['core'], "meta_key = '_sku' AND meta_value = %s"), 'SKU is used only after uniqueness check');
$assert($contains($src['core'], "update_post_meta( \$product->get_id(), '_mdo_openai_item_id'"), 'resolved item_id is persisted for stability');
$assert($contains($src['core'], 'mdo_get_openai_brand'), 'central OpenAI brand resolver exists');
$assert($contains($src['core'], "'pa_productor'"), 'brand resolver can use producer taxonomy');
$assert(!$contains($src['core'], "return 'El Mercado de Origen';\n}\n\nfunction mdo_get_openai_brand"), 'brand resolver does not hardcode marketplace as brand');
$assert($contains($src['core'], "apply_filters( 'mdo_openai_seller_name'"), 'seller_name is filterable');
$assert($contains($src['core'], "apply_filters( 'mdo_openai_marketplace_seller'"), 'marketplace_seller is filterable');
$assert($contains($src['core'], "apply_filters( 'mdo_openai_product_category'"), 'product_category is filterable');
$assert($contains($src['core'], "apply_filters( 'mdo_openai_availability'"), 'availability is filterable');
$assert($contains($src['core'], "apply_filters( 'mdo_openai_price'"), 'price is filterable');
$assert($contains($src['core'], "apply_filters( 'mdo_openai_shipping'"), 'shipping is filterable');
$assert($contains($src['core'], "apply_filters( 'mdo_openai_is_eligible_search'"), 'search eligibility is filterable');

$assert($contains($src['core'], "'instock'=>'in_stock'") && $contains($src['core'], "'outofstock'=>'out_of_stock'") && $contains($src['core'], "'onbackorder'=>'backorder'"), 'Woo stock states map to native OpenAI availability');
$assert($contains($src['core'], "'_mdo_openai_preorder'"), 'pre-order is explicit rather than inferred');
$assert($contains($src['core'], "'_mdo_openai_availability_date'"), 'availability_date can be supplied explicitly');
$assert($contains($src['core'], 'Google-compatible exige availability_date'), 'Google-compatible preorder/backorder is rejected without availability_date');
$assert($contains($src['core'], "'pre_order'=>'preorder'"), 'native pre_order maps to Google-compatible preorder');
$assert($contains($src['core'], "'google_compatible_confirmed'"), 'Google-compatible path is capability-gated');
$assert($contains($src['core'], "'shipping_capability_confirmed'"), 'shipping output is capability-gated');
$assert($contains($src['core'], "isset( \$record['shipping_price'], \$record['shipping'] )"), 'validator rejects both shipping representations together');
$assert($contains($src['core'], "array( 'g','kg','oz','lb' )"), 'weight unit allow-list is enforced');
$assert($contains($src['core'], 'is_eligible_search') && $contains($src['core'], "\$row['is_eligible_search'] = false"), 'retired items are explicitly search-ineligible');
$assert($contains($src['core'], "JSON_UNESCAPED_UNICODE"), 'JSONL preserves UTF-8 characters');
$assert($contains($src['core'], 'gzopen') && $contains($src['core'], 'gzwrite'), 'feed is streamed as gzip rather than buffered wholesale');

$assert($contains($src['transport'], 'aes-256-gcm'), 'stored transport secrets use authenticated encryption');
$assert($contains($src['transport'], 'direct_feed_access_confirmed') && $contains($src['transport'], 'market_confirmed'), 'delivery is blocked until onboarding and market are confirmed');
$assert(!$contains($src['transport'], "'/product_feeds/'"), 'no unconfirmed Product API route is hardcoded');
$assert($contains($src['transport'], 'mdo_openai_api_upload') && $contains($src['transport'], 'mdo_openai_api_test_connection'), 'API transport exposes extension hooks for confirmed account contract');
$assert($contains($src['transport'], 'api_mapper_not_enabled'), 'API upload fails closed until the exact contract is implemented');
$assert($contains($src['transport'], "'.tmp'"), 'SFTP upload uses a temporary remote name before replacement');

$assert($contains($src['admin'], 'Feeds · OpenAI / ChatGPT'), 'admin page is registered');
$assert($contains($src['admin'], 'OpenAI no la consume automáticamente'), 'admin warns that the QA URL is not automatic ingestion');
$assert($contains($src['admin'], 'Vista previa (20)'), 'admin exposes 20-record preview');
$assert($contains($src['admin'], 'Validar catálogo'), 'admin exposes validation');
$assert($contains($src['admin'], 'Generar y enviar ahora'), 'admin exposes explicit upload action');
$assert($contains($src['admin'], 'Food &amp; Beverage'), 'admin includes merchant-application category');
$assert($contains($src['admin'], 'Product Feed / Product Discovery'), 'admin includes merchant-application interest');

if ($failures) {
    fwrite(STDERR, "\n" . count($failures) . " contract test(s) failed.\n");
    exit(1);
}

echo "\nAll {$passes} OpenAI Commerce contract tests passed.\n";