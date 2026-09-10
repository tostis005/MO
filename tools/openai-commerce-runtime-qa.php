<?php
/**
 * Read-only runtime QA for EMDO OpenAI Commerce feed.
 * Usage from WordPress root:
 *   wp eval-file /path/to/repo/tools/openai-commerce-runtime-qa.php
 *
 * It does not change products, vendors, prices or settings. The feed mapper may
 * persist stable _mdo_openai_item_id/_mdo_openai_group_id metadata by design.
 */
if ( ! defined( 'ABSPATH' ) ) { fwrite( STDERR, "Run through WP-CLI.\n" ); exit( 1 ); }

$required_functions = array(
    'wc_get_product',
    'mdo_openai_settings_20260909',
    'mdo_openai_validate_catalog_20260909',
    'mdo_openai_provider_20260909',
    'mdo_openai_native_record_20260909',
    'mdo_openai_validate_record_20260909',
);
foreach ( $required_functions as $fn ) {
    if ( ! function_exists( $fn ) ) { throw new RuntimeException( 'Missing required runtime function: ' . $fn ); }
}

$result = array(
    'generated_at' => gmdate( 'c' ),
    'site' => home_url( '/' ),
    'woocommerce_currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
    'checks' => array(),
    'catalog' => array(),
    'sample' => array(),
    'los_pedroches' => array(),
);

$check = static function( string $name, bool $ok, $detail = null ) use ( &$result ): void {
    $result['checks'][] = array( 'name'=>$name, 'ok'=>$ok, 'detail'=>$detail );
};

$settings = mdo_openai_settings_20260909();
$native_settings = $settings;
$native_settings['format'] = 'native_jsonl_gz';
$native_settings['delivery'] = 'none';

$report = mdo_openai_validate_catalog_20260909( $native_settings );
$result['catalog'] = array(
    'parents' => (int) ( $report['parents'] ?? 0 ),
    'variants' => (int) ( $report['variants'] ?? 0 ),
    'exportable' => (int) ( $report['exported'] ?? 0 ),
    'excluded' => (int) ( $report['excluded'] ?? 0 ),
    'errors' => (int) ( $report['errors'] ?? 0 ),
    'warnings' => (int) ( $report['warnings'] ?? 0 ),
    'issues' => array_slice( (array) ( $report['issues'] ?? array() ), 0, 50 ),
);
$check( 'catalog_has_exportable_offers', (int) $result['catalog']['exportable'] > 0, $result['catalog']['exportable'] );
$check( 'catalog_has_no_validation_errors', 0 === (int) $result['catalog']['errors'], $result['catalog']['errors'] );

$preview = ( new MDO_OpenAI_Commerce_Feed_Provider_20260909( $native_settings ) )->preview( 20 );
$result['sample'] = (array) ( $preview['rows'] ?? array() );
$check( 'preview_has_rows', count( $result['sample'] ) > 0, count( $result['sample'] ) );
$check( 'preview_max_20', count( $result['sample'] ) <= 20, count( $result['sample'] ) );

foreach ( $result['sample'] as $index => $row ) {
    $prefix = 'sample_' . ( $index + 1 ) . '_';
    foreach ( array( 'item_id','title','description','url','brand','seller_name','image_url','availability','price' ) as $field ) {
        $check( $prefix . $field, isset( $row[ $field ] ) && '' !== trim( (string) $row[ $field ] ), $row[ $field ] ?? null );
    }
    $check( $prefix . 'https_url', 0 === strpos( (string) ( $row['url'] ?? '' ), 'https://' ), $row['url'] ?? '' );
    $check( $prefix . 'https_image', 0 === strpos( (string) ( $row['image_url'] ?? '' ), 'https://' ), $row['image_url'] ?? '' );
    $check( $prefix . 'money', 1 === preg_match( '/^(?:0|[1-9]\d*)\.\d{2}\s[A-Z]{3}$/', (string) ( $row['price'] ?? '' ) ), $row['price'] ?? '' );
    if ( isset( $row['sale_price'] ) ) {
        $price = mdo_openai_parse_money_20260909( $row['price'] );
        $sale = mdo_openai_parse_money_20260909( $row['sale_price'] );
        $check( $prefix . 'sale_relationship', null !== $price && null !== $sale && $sale[1] === $price[1] && $sale[0] > 0 && $sale[0] < $price[0], $row['sale_price'] );
    }
    if ( ! empty( $row['group_id'] ) ) {
        $check( $prefix . 'group_distinct', (string) $row['group_id'] !== (string) $row['item_id'], $row['group_id'] );
        $check( $prefix . 'variant_dict_nonempty', ! empty( $row['variant_dict'] ) && is_array( $row['variant_dict'] ), $row['variant_dict'] ?? null );
    }
}

$scenario = array(
    'simple' => 0,
    'variable' => 0,
    'no_sku' => 0,
    'out_of_stock' => 0,
    'on_sale' => 0,
    'has_reviews' => 0,
    'variable_four_or_more_variants' => 0,
    'variation_out_of_stock' => 0,
);
$sku_counts = array();
$ids = get_posts( array( 'post_type'=>'product', 'post_status'=>'publish', 'posts_per_page'=>-1, 'fields'=>'ids', 'orderby'=>'ID', 'order'=>'ASC', 'suppress_filters'=>true ) );
foreach ( array_map( 'absint', (array) $ids ) as $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product ) { continue; }
    if ( $product->is_type( 'simple' ) ) { ++$scenario['simple']; }
    if ( $product->is_type( 'variable' ) ) {
        ++$scenario['variable'];
        $children = array_map( 'absint', (array) $product->get_children() );
        if ( count( $children ) >= 4 ) { ++$scenario['variable_four_or_more_variants']; }
        foreach ( $children as $child_id ) {
            $variation = wc_get_product( $child_id );
            if ( $variation instanceof WC_Product_Variation ) {
                if ( 'outofstock' === (string) $variation->get_stock_status() ) { ++$scenario['variation_out_of_stock']; }
                $vsku = trim( (string) $variation->get_sku() );
                if ( '' === $vsku ) { ++$scenario['no_sku']; } else { $sku_counts[ $vsku ] = ( $sku_counts[ $vsku ] ?? 0 ) + 1; }
            }
        }
    }
    if ( 'outofstock' === (string) $product->get_stock_status() ) { ++$scenario['out_of_stock']; }
    if ( $product->is_on_sale() ) { ++$scenario['on_sale']; }
    if ( (int) $product->get_review_count() > 0 ) { ++$scenario['has_reviews']; }
    $sku = trim( (string) $product->get_sku() );
    if ( '' === $sku ) { ++$scenario['no_sku']; } else { $sku_counts[ $sku ] = ( $sku_counts[ $sku ] ?? 0 ) + 1; }
}
$duplicate_skus = array_filter( $sku_counts, static fn( int $count ): bool => $count > 1 );
$scenario['duplicate_sku_values'] = count( $duplicate_skus );
$result['scenarios'] = $scenario;
foreach ( $scenario as $name=>$count ) { $check( 'observed_' . $name, true, $count ); }

// Synthetic validator cases; these do not touch WooCommerce data.
$base = array(
    'item_id'=>'qa-1',
    'title'=>'Jamón de bellota 100% Ibérico — QA',
    'description'=>'Descripción de QA con UTF-8, comillas “curvas” y texto limpio.',
    'url'=>'https://www.elmercadodeorigen.com/producto/qa/',
    'brand'=>'Productor QA',
    'seller_name'=>'Productor QA',
    'image_url'=>'https://www.elmercadodeorigen.com/wp-content/uploads/qa.jpg',
    'availability'=>'in_stock',
    'price'=>'440.00 EUR',
    'is_eligible_search'=>true,
);
$state = array( 'item_ids'=>array(), 'variants'=>array() );
$synthetic_report = mdo_openai_report_template_20260909();
$check( 'validator_accepts_valid_simple', mdo_openai_validate_record_20260909( $base, 'native', $state, $synthetic_report ) );

$missing_brand = $base; $missing_brand['item_id']='qa-2'; $missing_brand['brand']='';
$state2 = array( 'item_ids'=>array(), 'variants'=>array() ); $r2=mdo_openai_report_template_20260909();
$check( 'validator_rejects_missing_brand', ! mdo_openai_validate_record_20260909( $missing_brand, 'native', $state2, $r2 ) );

$missing_image = $base; $missing_image['item_id']='qa-3'; $missing_image['image_url']='';
$state3 = array( 'item_ids'=>array(), 'variants'=>array() ); $r3=mdo_openai_report_template_20260909();
$check( 'validator_rejects_missing_image', ! mdo_openai_validate_record_20260909( $missing_image, 'native', $state3, $r3 ) );

$sale = $base; $sale['item_id']='qa-4'; $sale['price']='500.00 EUR'; $sale['sale_price']='440.00 EUR';
$state4 = array( 'item_ids'=>array(), 'variants'=>array() ); $r4=mdo_openai_report_template_20260909();
$check( 'validator_accepts_valid_sale', mdo_openai_validate_record_20260909( $sale, 'native', $state4, $r4 ) );

$reviews = $base; $reviews['item_id']='qa-5'; $reviews['review_count']=5; $reviews['star_rating']=4.80;
$state5 = array( 'item_ids'=>array(), 'variants'=>array() ); $r5=mdo_openai_report_template_20260909();
$check( 'validator_accepts_reviews_pair', mdo_openai_validate_record_20260909( $reviews, 'native', $state5, $r5 ) );

$shipping = $base; $shipping['item_id']='qa-6'; $shipping['shipping_price']='0.00 EUR';
$state6 = array( 'item_ids'=>array(), 'variants'=>array() ); $r6=mdo_openai_report_template_20260909();
$check( 'validator_accepts_zero_shipping_when_factual', mdo_openai_validate_record_20260909( $shipping, 'native', $state6, $r6 ) );

$both_shipping = $shipping; $both_shipping['item_id']='qa-7'; $both_shipping['shipping']='ES::standard:0.00 EUR';
$state7 = array( 'item_ids'=>array(), 'variants'=>array() ); $r7=mdo_openai_report_template_20260909();
$check( 'validator_rejects_two_shipping_representations', ! mdo_openai_validate_record_20260909( $both_shipping, 'native', $state7, $r7 ) );

$variant_a = $base; $variant_a['item_id']='qa-v1'; $variant_a['group_id']='qa-group'; $variant_a['listing_has_variations']=true; $variant_a['variant_dict']=array('peso'=>'7-8 kg');
$variant_b = $variant_a; $variant_b['item_id']='qa-v2';
$state8 = array( 'item_ids'=>array(), 'variants'=>array() ); $r8=mdo_openai_report_template_20260909();
$first_variant_ok = mdo_openai_validate_record_20260909( $variant_a, 'native', $state8, $r8 );
$duplicate_variant_rejected = ! mdo_openai_validate_record_20260909( $variant_b, 'native', $state8, $r8 );
$check( 'validator_rejects_duplicate_variant_dict_in_group', $first_variant_ok && $duplicate_variant_rejected );

$g = array(
    'id'=>'qa-google', 'title'=>'QA', 'description'=>'QA',
    'link'=>'https://www.elmercadodeorigen.com/producto/qa/',
    'image_link'=>'https://www.elmercadodeorigen.com/wp-content/uploads/qa.jpg',
    'availability'=>'backorder', 'price'=>'440.00 EUR', 'brand'=>'QA',
);
$gstate=array('item_ids'=>array(),'variants'=>array()); $gr=mdo_openai_report_template_20260909();
$check( 'google_backorder_requires_availability_date', ! mdo_openai_validate_record_20260909( $g, 'google', $gstate, $gr ) );

$check( 'money_decimal_eur', '440.00 EUR' === mdo_openai_money_20260909( 440, 'EUR' ), mdo_openai_money_20260909( 440, 'EUR' ) );
$dirty = '<p>Jamón &amp; queso\ncon <strong>HTML</strong></p>';
$clean = mdo_openai_clean_text_20260909( $dirty, 500 );
$check( 'text_cleanup_html_entities_utf8', false === strpos( $clean, '<' ) && false !== strpos( $clean, 'Jamón' ) && false !== strpos( $clean, '&' ), $clean );

if ( function_exists( 'mdo_gmf_valid_gtin_v1' ) ) {
    $check( 'gtin_valid_example', '4006381333931' === mdo_gmf_valid_gtin_v1( '4006381333931' ) );
    $check( 'gtin_invalid_example', '' === mdo_gmf_valid_gtin_v1( '4006381333932' ) );
}

// Real Los Pedroches check. Do not infer vendor/brand from the title; inspect mapped rows.
$pedroches_ids = get_posts( array(
    'post_type'=>'product', 'post_status'=>'publish', 'posts_per_page'=>20, 'fields'=>'ids',
    's'=>'Los Pedroches', 'suppress_filters'=>true,
) );
foreach ( array_map( 'absint', (array) $pedroches_ids ) as $product_id ) {
    $parent = wc_get_product( $product_id );
    if ( ! $parent instanceof WC_Product ) { continue; }
    $targets = $parent->is_type( 'variable' ) ? array_map( 'wc_get_product', array_map( 'absint', (array) $parent->get_children() ) ) : array( $parent );
    foreach ( $targets as $target ) {
        if ( ! $target instanceof WC_Product ) { continue; }
        $rr = mdo_openai_report_template_20260909();
        $row = mdo_openai_native_record_20260909( $target, $native_settings, $rr );
        if ( is_array( $row ) ) {
            $result['los_pedroches'][] = array(
                'product_id'=>(int) $target->get_id(),
                'parent_id'=>(int) $product_id,
                'title'=>(string) ($row['title']??''),
                'brand'=>(string) ($row['brand']??''),
                'seller_name'=>(string) ($row['seller_name']??''),
                'price'=>(string) ($row['price']??''),
                'availability'=>(string) ($row['availability']??''),
                'variant_dict'=>$row['variant_dict']??array(),
                'issues'=>$rr['issues']??array(),
            );
        }
    }
}
$check( 'los_pedroches_product_detected', count( $result['los_pedroches'] ) > 0, count( $result['los_pedroches'] ) );
foreach ( $result['los_pedroches'] as $i=>$row ) {
    $check( 'los_pedroches_' . ( $i + 1 ) . '_brand_present', '' !== trim( $row['brand'] ), $row['brand'] );
    $check( 'los_pedroches_' . ( $i + 1 ) . '_seller_present', '' !== trim( $row['seller_name'] ), $row['seller_name'] );
}

$failures = array_values( array_filter( $result['checks'], static fn( array $c ): bool => empty( $c['ok'] ) ) );
$result['summary'] = array( 'checks'=>count($result['checks']), 'failures'=>count($failures), 'ok'=>0===count($failures) );

echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
if ( $failures ) { throw new RuntimeException( count( $failures ) . ' OpenAI Commerce runtime QA checks failed.' ); }
