<?php
/**
 * Read-only Chocolates Artesanos Isabel imported catalog audit.
 * WP-CLI eval-file in the site's WordPress runtime.
 * Emits aggregate counts and bounded diagnostics; makes no database writes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$code = 'chocolates-artesanos-isabel';
$tables = array(
    'suppliers' => MDO_Database::table( 'suppliers' ),
    'products'  => MDO_Database::table( 'source_products' ),
    'runs'      => MDO_Database::table( 'sync_runs' ),
    'events'    => MDO_Database::table( 'sync_events' ),
);
$hosts = array();
foreach ( array( 'home', 'siteurl' ) as $key ) {
    $host = strtolower( (string) wp_parse_url( (string) get_option( $key ), PHP_URL_HOST ) );
    $hosts[ $key ] = $host;
    if ( ! in_array( $host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
        throw new RuntimeException( 'La auditoría se debe ejecutar en producción.' );
    }
}
$supplier = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$tables['suppliers']} WHERE code = %s LIMIT 1", $code
), ARRAY_A );
if ( ! $supplier ) {
    echo "ISABEL_AUDIT=" . wp_json_encode( array(
        'supplier_found' => false, 'production_hosts' => $hosts,
        'explanation' => 'No hay alta del proveedor EMDO en la base de datos real.'
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    exit;
}
$supplier_id = (int) $supplier['id'];
$vendor_id = (int) $supplier['vendor_user_id'];
$vendor = $vendor_id ? get_user_by( 'id', $vendor_id ) : false;
$profile = $vendor ? get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true ) : array();
$profile = is_array( $profile ) ? $profile : array();
$rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT id,source_url,title,status,source_price,source_stock_status,source_payload,source_hash,wc_product_id,last_error
     FROM {$tables['products']} WHERE supplier_id=%d ORDER BY id", $supplier_id
), ARRAY_A ) ?: array();

$counts = array(
    'quality_repaired' => 0, 'quality_errors' => 0,
    'source_total' => count( $rows ), 'active' => 0, 'pending' => 0, 'importing' => 0,
    'excluded' => 0, 'unavailable' => 0, 'other' => 0,
    'wc_products_found' => 0, 'draft' => 0, 'published' => 0, 'hidden' => 0,
    'source_instock' => 0, 'source_outofstock' => 0, 'wc_instock' => 0, 'wc_outofstock' => 0,
    'with_images' => 0, 'with_gallery' => 0, 'with_description' => 0,
    'with_short_description' => 0, 'with_sku' => 0, 'with_weight' => 0,
    'with_categories' => 0, 'with_dimensions' => 0, 'wc_variable' => 0,
    'source_variable' => 0, 'source_unknown_stock' => 0, 'with_source_ingredients' => 0,
    'with_wc_ingredients' => 0, 'with_source_allergen_words' => 0, 'with_wc_allergen_words' => 0,
    'source_images_available' => 0, 'with_media_map' => 0
);
$issues = array(); $samples = array(); $severity = array();
$add_issue = static function ( string $type, array $row, array $extra = array() ) use ( &$issues, &$severity ): void {
    $severity[ $type ] = (int) ( $severity[ $type ] ?? 0 ) + 1;
    if ( count( $issues ) < 55 ) {
        $issues[] = array_merge( array( 'type'=>$type, 'source_id'=>(int)$row['id'], 'source_url'=>(string)$row['source_url'],
             'wc_id'=>(int)$row['wc_product_id'], 'title'=>mb_substr( (string) $row['title'],0,115 ) ), $extra );
    }
};
$plain_description = static function ( string $html ): string {
    // Source JSON sometimes double-escapes rich HTML, while WordPress decodes
    // it before saving. Strip tags only AFTER decoding all nested entities.
    for ( $i = 0; $i < 4; $i++ ) {
        $new = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        if ( $new === $html ) { break; }
        $html = $new;
    }
    $html = wp_strip_all_tags( $html );
    $html = str_replace( "\xc2\xa0", ' ', $html );
    return trim( preg_replace( '/\s+/u', ' ', $html ) );
};
$critical_description_mismatches = array();
$nutrition_source = 0;
$nutrition_woo = 0;
$source_urls = array();
$category_distribution = array();
$taxonomy_defects = array( 'uncategorized' => 0, 'unrelated_to_chocolate' => 0, 'bombon_as_tablet' => 0, 'cacao_as_tablet' => 0 );
$wrong_category_examples = array();
$source_weight_mentioned = 0;
$source_dimensions_mentioned = 0;
$description_fragments = array();
$pending_listing = array();
foreach ( $rows as $row ) {
    $status = (string) $row['status'];
    if ( $status === 'pending' ) { $pending_listing[] = array('id'=>(int)$row['id'],'title'=>$row['title'],'error'=>mb_substr((string)$row['last_error'],0,120),'variation_count'=>(int)(json_decode((string)$row['source_payload'],true)['variation_count']??0) ); }
    if ( isset( $counts[ $status ] ) ) { $counts[ $status ]++; }
    else { $counts['other']++; }
    $url = rtrim( (string) $row['source_url'], '/' );
    if ( isset( $source_urls[ $url ] ) ) { $add_issue( 'duplicate_source_url', $row ); }
    $source_urls[ $url ] = true;
    $payload = json_decode( (string) $row['source_payload'], true );
    $payload = is_array( $payload ) ? $payload : array();
    $source_desc = $plain_description( (string) ( $payload['description'] ?? '' ) );
    $source_stock = (string) ( $payload['stock_status'] ?? $row['source_stock_status'] );
    $source_price = isset( $payload['price'] ) && is_numeric( $payload['price'] ) ? (float) $payload['price'] : null;
    $variation_count = (int) ( $payload['variation_count'] ?? 0 );
    $images = (array) ( $payload['images'] ?? array() );
    if ( $images ) { $counts['source_images_available']++; }
    if ( $variation_count > 0 ) { $counts['source_variable']++; }
    if ( 'instock' === $source_stock ) { $counts['source_instock']++; }
    elseif ( 'outofstock' === $source_stock ) { $counts['source_outofstock']++; }
    else { $counts['source_unknown_stock']++; }
    if ( preg_match( '/ingredientes|composici[oó]n/i', $source_desc ) ) { $counts['with_source_ingredients']++; }
    if ( preg_match( '/peso\s+(?:unitario|envase|neto)|\b\d+\s*(?:grs|gramos|g\.|g\b|kg\b)/ui', $source_desc ) ) { $source_weight_mentioned++; }
    if ( preg_match( '/medidas|dimensiones|\b\d+\s*x\s*\d+\s*x\s*\d+\s*cm/ui', $source_desc ) ) { $source_dimensions_mentioned++; }
    $prefix = mb_substr( preg_replace( '/\s+/u', ' ', trim( $source_desc ) ), 0, 85 );
    if ( $prefix ) { $description_fragments[ $prefix ] = (int)( $description_fragments[ $prefix ] ?? 0 ) + 1; }
    if ( preg_match( '/al[eé]rgen|trazas|puede contener/i', $source_desc ) ) { $counts['with_source_allergen_words']++; }
    if ( ! $payload && $status !== 'excluded' ) { $add_issue( 'missing_source_payload', $row ); }
    if ( $status === 'pending' && $row['last_error'] ) {
        $add_issue( 'import_or_scrape_error', $row, array( 'error'=>mb_substr( (string)$row['last_error'],0,210 ) ) );
    }
    if ( $status === 'active' && ! $row['wc_product_id'] ) {
        $add_issue( 'active_without_wc_id', $row );
    }
    if ( ! (int)$row['wc_product_id'] ) { continue; }
    $p = wc_get_product( (int)$row['wc_product_id'] );
    if ( ! $p ) { $add_issue( 'wc_product_missing', $row ); continue; }
    $counts['wc_products_found']++;
    if (get_post_meta($p->get_id(),'_emdo_isabel_quality_v2_done',true)==='1'){$counts['quality_repaired']++;}
    if (get_post_meta($p->get_id(),'_emdo_isabel_quality_v2_done',true)==='error'){$counts['quality_errors']++;}
    if ( $p->get_status() === 'draft' ) { $counts['draft']++; }
    if ( $p->get_status() === 'publish' ) { $counts['published']++; }
    if ( $p->get_catalog_visibility() === 'hidden' ) { $counts['hidden']++; }
    if ( $p->get_stock_status() === 'instock' ) { $counts['wc_instock']++; }
    if ( $p->get_stock_status() === 'outofstock' ) { $counts['wc_outofstock']++; }
    if ( $p->get_image_id() ) { $counts['with_images']++; }
    if ( $p->get_gallery_image_ids() ) { $counts['with_gallery']++; }
    if ( trim( wp_strip_all_tags( $p->get_description() ) ) !== '' ) { $counts['with_description']++; }
    if ( trim( wp_strip_all_tags( $p->get_short_description() ) ) !== '' ) { $counts['with_short_description']++; }
    if ( $p->get_sku() ) { $counts['with_sku']++; }
    if ( $p->get_weight() ) { $counts['with_weight']++; }
    if ( $p->get_length() || $p->get_width() || $p->get_height() ) { $counts['with_dimensions']++; }
    if ( $p->get_category_ids() ) { $counts['with_categories']++; }
    $terms = wp_get_post_terms( $p->get_id(), 'product_cat' );
    $slugs = array();
    foreach ( is_array($terms) ? $terms : array() as $term ) {
        $slugs[] = (string)$term->slug;
        $category_distribution[(string)$term->name] = (int)( $category_distribution[(string)$term->name] ?? 0 ) + 1;
    }
    $cat_string = implode(' ', $slugs);
    if ( str_contains( $cat_string, 'sin-categorizar' ) || str_contains( $cat_string, 'uncategorized' ) ) { $taxonomy_defects['uncategorized']++; }
    if ( preg_match( '/naranjas|hortalizas|verduras|carnes|pato|jamon|aceites|cerdo|queso/', $cat_string ) ) { $taxonomy_defects['unrelated_to_chocolate']++; }
    $is_bombon = (bool) preg_match( '/bombon|torino|laminero|roca/i', $p->get_name() );
    if ( $is_bombon && preg_match( '/tabletas/', $cat_string ) ) { $taxonomy_defects['bombon_as_tablet']++; }
    if ( preg_match( '/habas de cacao|nibs|cascarilla|manteca de cacao/i', $p->get_name() ) && preg_match( '/tabletas/', $cat_string ) ) { $taxonomy_defects['cacao_as_tablet']++; }
    if ( count($wrong_category_examples) < 20 && (
        $is_bombon && preg_match('/tabletas/',$cat_string) ||
        preg_match('/naranjas|hortalizas|verduras|carnes|pato|jamon|aceites|cerdo|queso/',$cat_string)
    ) ) { $wrong_category_examples[] = array('id'=>$p->get_id(), 'name'=>$p->get_name(), 'categories'=>array_map(static fn($t)=>$t->name, is_array($terms)?$terms:array())); }
    if ( $p->is_type( 'variable' ) ) { $counts['wc_variable']++; }
    $wc_desc = $plain_description( $p->get_description() );
    if ( preg_match( '/nutricional|valor energ[eé]tico/iu', $source_desc ) ) { $nutrition_source++; }
    if ( preg_match( '/nutricional|valor energ[eé]tico/iu', $wc_desc ) ) { $nutrition_woo++; }
    foreach ( array( 'composici[oó]n|ingredientes', 'informaci[oó]n\s+al[eé]rgen|trazas', 'nutricional|valor energ[eé]tico' ) as $section ) {
        if ( preg_match( '~' . $section . '~iu', $source_desc ) && ! preg_match( '~' . $section . '~iu', $wc_desc ) ) {
            if ( count( $critical_description_mismatches ) < 22 ) {
                $critical_description_mismatches[] = array( 'wc_id'=>$p->get_id(),'source_id'=>(int)$row['id'],'section'=>$section );
            }
        }
    }
    if ( preg_match( '/ingredientes|composici[oó]n/i', $wc_desc ) ) { $counts['with_wc_ingredients']++; }
    if ( preg_match( '/al[eé]rgen|trazas|puede contener/i', $wc_desc ) ) { $counts['with_wc_allergen_words']++; }
    $media = json_decode( (string) get_post_meta( $p->get_id(), '_emdo_image_map', true ), true );
    if ( is_array( $media ) && count( $media ) ) { $counts['with_media_map']++; }

    if ( (int)get_post_field( 'post_author', $p->get_id() ) !== $vendor_id ) { $add_issue( 'wrong_vendor_author', $row ); }
    if ( $p->get_status() !== 'draft' ) { $add_issue( 'unexpected_post_status', $row, array( 'value'=>$p->get_status() ) ); }
    if ( $p->get_catalog_visibility() !== 'hidden' ) { $add_issue( 'unexpected_catalog_visibility', $row ); }
    if ( $p->get_image_id() <= 0 ) { $add_issue( 'missing_main_image', $row, array( 'source_images' => count( $images ), 'first_source_image' => esc_url_raw( (string) ( $images[0] ?? '' ) ) ) ); }
    if ( trim( $wc_desc ) === '' ) { $add_issue( 'missing_woocommerce_description', $row ); }
    if ( ! $p->get_category_ids() ) { $add_issue( 'missing_category', $row ); }
    if ( (int)get_post_meta( $p->get_id(), '_emdo_supplier_id', true ) !== $supplier_id ) { $add_issue( 'wrong_supplier_meta', $row ); }
    if ( rtrim( (string)get_post_meta( $p->get_id(), '_emdo_source_url', true ), '/' ) !== $url ) { $add_issue( 'wrong_source_url_meta', $row ); }
    if ( $payload ) {
        $p_name = trim( preg_replace( '/\s+/u', ' ', $p->get_name() ) );
        $src_name = trim( preg_replace( '/\s+/u', ' ', (string)( $payload['title'] ?? '' ) ) );
        if ( $src_name && $p_name !== $src_name && $p_name !== MDO_Text::normalize_title( $src_name ) ) {
            $add_issue( 'name_differs_from_source', $row, array( 'woo_name'=>mb_substr( $p_name,0,100 ) ) );
        }
        if ( $source_stock !== $p->get_stock_status() ) {
            $add_issue( 'stock_mismatch', $row, array( 'source_stock'=>$source_stock, 'wc_stock'=>$p->get_stock_status() ) );
        }
        if ( $source_price !== null && ! $p->is_type('variable') && abs( $source_price - (float)$p->get_price() ) > 0.011 ) {
            $add_issue( 'price_mismatch', $row, array( 'source_price'=>$source_price, 'wc_price'=>$p->get_price() ) );
        }
        $wc_plain = $wc_desc;
        $src_plain = $source_desc;
        if ( $src_plain && $wc_plain !== $src_plain ) { $add_issue( 'description_differs_from_source', $row ); }
        if ( $variation_count > 1 && ! $p->is_type( 'variable' ) ) { $add_issue( 'missing_variations', $row ); }
        if ( $p->is_type( 'variable' ) && $variation_count && count( $p->get_children() ) !== $variation_count ) {
            $add_issue( 'variation_count_mismatch', $row, array( 'source_variations'=>$variation_count, 'wc_variations'=>count($p->get_children()) ) );
        }
        if ( $images && ( ! is_array( $media ) || count( $media ) === 0 ) ) {
            $add_issue( 'source_images_not_mapped', $row );
        }
    }
    if ( count( $samples ) < 15 ) {
        $cats = wp_get_post_terms( $p->get_id(), 'product_cat', array( 'fields'=>'names' ) );
        $samples[] = array(
            'id'=>$p->get_id(), 'title'=>$p->get_name(), 'status'=>$p->get_status(),
            'visibility'=>$p->get_catalog_visibility(), 'price'=>$p->get_price(),
            'source_price'=>$source_price, 'source_stock'=>$source_stock, 'woo_stock'=>$p->get_stock_status(),
            'images_source'=>count($images), 'image_id'=>$p->get_image_id(),
            'gallery_count'=>count($p->get_gallery_image_ids()),
            'description_chars'=>mb_strlen($wc_desc), 'source_description_chars'=>mb_strlen($source_desc),
            'sku'=>$p->get_sku(), 'categories'=>is_array($cats)?$cats:array(),
            'source_variations'=>$variation_count, 'wc_variations'=>count($p->get_children()), 'source_url'=>$url
        );
    }
}
$recent_runs = $wpdb->get_results( $wpdb->prepare(
    "SELECT id,status,trigger_type,started_at,finished_at,products_found,products_new,products_updated,products_excluded,errors_count,message
     FROM {$tables['runs']} WHERE supplier_id=%d ORDER BY id DESC LIMIT 4", $supplier_id
), ARRAY_A ) ?: array();
$events = $wpdb->get_results( $wpdb->prepare(
    "SELECT event_type,severity,COUNT(*) n FROM {$tables['events']} WHERE supplier_id=%d GROUP BY event_type,severity ORDER BY n DESC LIMIT 20", $supplier_id
), ARRAY_A ) ?: array();
$vendor_published = $vendor_id ? (int)$wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status='publish'", $vendor_id
) ) : 0;
$out = array(
    'supplier_found'=>true, 'supplier_id'=>$supplier_id,
    'active_supplier'=>(int)$supplier['active'], 'connector'=>$supplier['connector'],
    'sync_frequency'=>$supplier['sync_frequency'], 'vendor_id'=>$vendor_id,
    'vendor_exists'=>(bool)$vendor, 'vendor_disabled'=>$vendor ? (bool)get_user_meta($vendor_id,'_disable_vendor',true):null,
    'logo_id'=>(int)($profile['gravatar']??0), 'banner_id'=>(int)($profile['banner']??0),
    'logo_attachment_exists'=>!empty($profile['gravatar'])&&get_post_type((int)$profile['gravatar'])==='attachment',
    'banner_attachment_exists'=>!empty($profile['banner'])&&get_post_type((int)$profile['banner'])==='attachment',
    'vendor_published_products'=>$vendor_published,
    'counts'=>$counts, 'issue_counts'=>$severity, 'issues_sample'=>$issues,
    'product_samples'=>$samples, 'recent_sync_runs'=>$recent_runs, 'sync_event_counts'=>$events,
    'pending_listing'=>$pending_listing,
    'nutrition_source_count'=>$nutrition_source,'nutrition_woo_count'=>$nutrition_woo,
    'critical_description_mismatches'=>$critical_description_mismatches,
    'category_distribution'=>$category_distribution, 'taxonomy_defects'=>$taxonomy_defects,
    'wrong_category_examples'=>$wrong_category_examples,
    'source_products_with_weight_text'=>$source_weight_mentioned,
    'source_products_with_dimension_text'=>$source_dimensions_mentioned,
    'repeated_description_prefixes'=>array_slice( array_filter($description_fragments, static fn($n)=>$n>1), 0, 10, true ),
);
echo 'ISABEL_AUDIT=' . wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR ) . "\n";
