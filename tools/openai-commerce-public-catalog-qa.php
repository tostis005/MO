<?php
/**
 * Exact-set QA for the OpenAI catalog.
 * Run with WP-CLI eval-file on production after loading the EMDO plugin.
 *
 * Deliberately enumerates published product parents directly from wp_posts so
 * WCFM front-end visibility hooks cannot hide disabled-vendor products from
 * the audit itself. The expected set is then reduced independently to the
 * anonymous/public, purchasable, image-complete catalog.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'mdo_openai_provider_20260909' ) ) {
    throw new RuntimeException( 'WooCommerce/OpenAI feed runtime unavailable.' );
}

function mdo_openai_public_qa_vendor_active_20260910( int $vendor_id ): bool {
    $user = get_userdata( $vendor_id );
    if ( ! $user instanceof WP_User ) { return false; }
    if ( in_array( 'disable_vendor', (array) $user->roles, true ) ) { return false; }
    if ( in_array( 'pending_vendor', (array) $user->roles, true ) ) { return false; }
    if ( function_exists( 'mdo_gmf_vendor_is_active_v1' ) && ! mdo_gmf_vendor_is_active_v1( $vendor_id ) ) { return false; }
    return true;
}

function mdo_openai_public_qa_image_20260910( WC_Product $product ): string {
    $image_id = (int) $product->get_image_id();
    if ( $image_id <= 0 && $product instanceof WC_Product_Variation ) {
        $parent = wc_get_product( (int) $product->get_parent_id() );
        if ( $parent instanceof WC_Product ) { $image_id = (int) $parent->get_image_id(); }
    }
    if ( $image_id <= 0 ) { return ''; }
    $url = wp_get_attachment_url( $image_id );
    if ( ! is_string( $url ) || '' === $url ) { return ''; }
    $parts = wp_parse_url( $url );
    return is_array( $parts ) && ! empty( $parts['host'] ) ? $url : '';
}

function mdo_openai_public_qa_parent_public_20260910( WC_Product $parent, WP_Post $post ): bool {
    if ( 'publish' !== (string) $post->post_status || '' !== (string) $post->post_password ) { return false; }
    if ( 'hidden' === (string) $parent->get_catalog_visibility() ) { return false; }
    if ( ! $parent->is_visible() ) { return false; }
    return mdo_openai_public_qa_vendor_active_20260910( (int) $post->post_author );
}

$settings = mdo_openai_settings_20260909();
$settings['format'] = 'native_jsonl_gz';
$settings['delivery'] = 'none';
$settings['auto_upload'] = '0';

$stats = array(
    'published_parents' => 0,
    'variable_parents' => 0,
    'simple_parents' => 0,
    'published_variants' => 0,
    'disabled_vendor_parents' => 0,
    'disabled_vendor_items' => 0,
    'nonpublic_parents' => 0,
    'nonpurchasable_items' => 0,
    'imageless_items' => 0,
    'eligible_simple_items' => 0,
    'eligible_variant_items' => 0,
    'expected_items' => 0,
    'feed_items' => 0,
    'missing_from_feed' => 0,
    'unexpected_in_feed' => 0,
    'retired_rows' => 0,
);

$disabled_vendors = array();
$imageless = array();
$expected = array();
$montjam_bad_skus = array(
    'MONTJAM-JDOP-600-650','MONTJAM-JDOP-650-700','MONTJAM-JDOP-700-750','MONTJAM-JDOP-750-800',
    'MONTJAM-PDOP-500-550','MONTJAM-PDOP-550-600',
);

global $wpdb;
$parent_ids = $wpdb->get_col(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_password='' ORDER BY ID ASC"
);

foreach ( array_map( 'absint', (array) $parent_ids ) as $parent_id ) {
    $parent = wc_get_product( $parent_id );
    $post = get_post( $parent_id );
    if ( ! $parent instanceof WC_Product || ! $post instanceof WP_Post ) { continue; }
    ++$stats['published_parents'];

    $vendor_id = (int) $post->post_author;
    $vendor_active = mdo_openai_public_qa_vendor_active_20260910( $vendor_id );
    if ( ! $vendor_active ) {
        ++$stats['disabled_vendor_parents'];
        $user = get_userdata( $vendor_id );
        $disabled_vendors[ $vendor_id ] = $user instanceof WP_User ? $user->user_login : (string) $vendor_id;
    }

    $parent_public = mdo_openai_public_qa_parent_public_20260910( $parent, $post );
    if ( ! $parent_public ) { ++$stats['nonpublic_parents']; }

    $items = array();
    if ( $parent instanceof WC_Product_Variable && $parent->is_type( 'variable' ) ) {
        ++$stats['variable_parents'];
        foreach ( array_map( 'absint', (array) $parent->get_children() ) as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( ! $variation instanceof WC_Product_Variation || ! $variation->exists() || 'publish' !== (string) $variation->get_status() ) { continue; }
            ++$stats['published_variants'];
            $items[] = $variation;
        }
    } else {
        ++$stats['simple_parents'];
        $items[] = $parent;
    }

    foreach ( $items as $item ) {
        $sku = (string) $item->get_sku();
        if ( ! $vendor_active ) { ++$stats['disabled_vendor_items']; continue; }
        if ( ! $parent_public ) { continue; }
        if ( ! $item->is_purchasable() ) { ++$stats['nonpurchasable_items']; continue; }
        $image = mdo_openai_public_qa_image_20260910( $item );
        if ( '' === $image ) {
            ++$stats['imageless_items'];
            $imageless[] = array( 'product_id'=>(int) $item->get_id(), 'parent_id'=>$parent_id, 'sku'=>$sku, 'name'=>$item->get_name() );
            continue;
        }

        $item_id = mdo_openai_item_id_20260909( $item );
        if ( '' === $item_id ) { throw new RuntimeException( 'Eligible item has empty OpenAI item_id: WC ' . (int) $item->get_id() ); }
        $expected[ $item_id ] = array(
            'product_id'=>(int) $item->get_id(), 'parent_id'=>$parent_id, 'sku'=>$sku,
            'vendor_id'=>$vendor_id, 'name'=>$item->get_name(), 'image'=>$image,
        );
        if ( $item instanceof WC_Product_Variation ) { ++$stats['eligible_variant_items']; }
        else { ++$stats['eligible_simple_items']; }
    }
}

ksort( $expected );
$stats['expected_items'] = count( $expected );

$provider = mdo_openai_provider_20260909( $settings );
if ( ! $provider instanceof MDO_OpenAI_Strict_Snapshot_Provider_20260910 ) {
    throw new RuntimeException( 'Strict current snapshot provider is not active: ' . get_class( $provider ) );
}
$result = $provider->generate();
if ( empty( $result['ok'] ) ) { throw new RuntimeException( 'Feed generation failed: ' . (string) ( $result['error'] ?? 'unknown' ) ); }
$report = (array) ( $result['report'] ?? array() );
if ( (int) ( $report['errors'] ?? -1 ) !== 0 ) { throw new RuntimeException( 'Feed report contains validation errors.' ); }
if ( (int) ( $report['retired'] ?? -1 ) !== 0 ) { throw new RuntimeException( 'Strict feed unexpectedly contains retired rows.' ); }

$path = (string) ( $result['path'] ?? '' );
if ( '' === $path || ! is_readable( $path ) ) { throw new RuntimeException( 'Generated feed path is not readable.' ); }
$fh = gzopen( $path, 'rb' );
if ( false === $fh ) { throw new RuntimeException( 'Cannot open generated gzip feed.' ); }
$actual = array();
$unexpected_ineligible = array();
while ( ! gzeof( $fh ) ) {
    $line = trim( (string) gzgets( $fh ) );
    if ( '' === $line ) { continue; }
    $row = json_decode( $line, true );
    if ( ! is_array( $row ) ) { gzclose( $fh ); throw new RuntimeException( 'Invalid JSONL row.' ); }
    $id = (string) ( $row['item_id'] ?? '' );
    if ( '' === $id ) { gzclose( $fh ); throw new RuntimeException( 'Physical feed row without item_id.' ); }
    if ( isset( $actual[ $id ] ) ) { gzclose( $fh ); throw new RuntimeException( 'Duplicate physical feed item_id: ' . $id ); }
    if ( array_key_exists( 'is_eligible_search', $row ) && false === $row['is_eligible_search'] ) {
        $unexpected_ineligible[] = $id;
        ++$stats['retired_rows'];
    }
    $actual[ $id ] = $row;
}
gzclose( $fh );
ksort( $actual );
$stats['feed_items'] = count( $actual );

$missing = array_diff_key( $expected, $actual );
$unexpected = array_diff_key( $actual, $expected );
$stats['missing_from_feed'] = count( $missing );
$stats['unexpected_in_feed'] = count( $unexpected );

$montjam_presence = array();
foreach ( $actual as $item_id => $row ) {
    $offer = (string) ( $row['offer_id'] ?? '' );
    foreach ( $montjam_bad_skus as $sku ) {
        if ( $item_id === $sku || false !== strpos( $offer, $sku ) ) { $montjam_presence[ $sku ] = $item_id; }
    }
}

$output = array(
    'ok' => 0 === count( $missing ) && 0 === count( $unexpected ) && 0 === $stats['retired_rows'] && empty( $montjam_presence ),
    'stats' => $stats,
    'disabled_vendors' => $disabled_vendors,
    'imageless' => $imageless,
    'missing' => $missing,
    'unexpected_item_ids' => array_keys( $unexpected ),
    'ineligible_rows' => $unexpected_ineligible,
    'montjam_wrong_image_skus_present' => $montjam_presence,
    'generation' => array(
        'provider'=>get_class( $provider ),
        'exported'=>(int) ( $report['exported'] ?? 0 ),
        'excluded'=>(int) ( $report['excluded'] ?? 0 ),
        'errors'=>(int) ( $report['errors'] ?? 0 ),
        'warnings'=>(int) ( $report['warnings'] ?? 0 ),
        'retired'=>(int) ( $report['retired'] ?? 0 ),
        'bytes'=>(int) ( $report['bytes'] ?? 0 ),
    ),
);

echo wp_json_encode( $output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n";
if ( ! $output['ok'] ) { exit( 3 ); }
