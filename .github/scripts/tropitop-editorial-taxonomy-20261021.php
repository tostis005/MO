<?php
/**
 * Idempotent, Tropitop-only editorial + category reconciliation.
 * Runs with WordPress/WooCommerce loaded via WP-CLI.
 * Default mode is audit. Set TROPITOP_EDITORIAL_MODE=apply to change products.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 1 ); }
$hostname = strtolower( (string) wp_parse_url( get_option( 'siteurl' ), PHP_URL_HOST ) );
if ( ! in_array( $hostname, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
    throw new RuntimeException( 'Tropitop audit restricted to EMDO production.' );
}
foreach ( array( 'WooCommerce', 'MDO_Database', 'MDO_Woo_Importer', 'MDO_Tropitop_Editorial' ) as $needed ) {
    if ( ! class_exists( $needed ) ) { throw new RuntimeException( 'Missing ' . $needed ); }
}
$mode = (string) ( getenv( 'TROPITOP_EDITORIAL_MODE' ) ?: 'audit' );
if ( ! in_array( $mode, array( 'audit', 'apply' ), true ) ) { throw new RuntimeException( 'Invalid mode.' ); }
$canonical = get_term_by( 'slug', 'hortalizas-verduras', 'product_cat' );
if ( ! $canonical instanceof WP_Term ) {
    throw new RuntimeException( 'Existing canonical Hortalizas/Verduras category was not found. No changes.' );
}
global $wpdb;
$suppliers = MDO_Database::table( 'suppliers' );
$sources = MDO_Database::table( 'source_products' );
$supplier = $wpdb->get_row(
    $wpdb->prepare( "SELECT id,code,vendor_user_id FROM {$suppliers} WHERE code=%s", 'tropitop' ), ARRAY_A
);
if ( ! $supplier || (int) $supplier['vendor_user_id'] <= 0 ) {
    throw new RuntimeException( 'The Tropitop supplier or vendor is missing.' );
}
$vendor = (int) $supplier['vendor_user_id'];
$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id,wc_product_id,source_payload,title,source_url FROM {$sources} WHERE supplier_id=%d AND wc_product_id>0 ORDER BY id",
        (int) $supplier['id']
    ), ARRAY_A
);
if ( ! $rows ) { throw new RuntimeException( 'No linked Tropitop catalog found; refuse an empty migration.' ); }
$counts = array(
    'linked_source_rows' => count( $rows ), 'reviewed' => 0,
    'changed_copy' => 0, 'changed_categories' => 0,
    'no_description_after_cleanup' => 0, 'residual_flags' => 0,
    'errors' => 0, 'skipped' => 0,
);
$processed = array();
foreach ( $rows as $row ) {
    $id = (int) $row['wc_product_id'];
    if ( isset( $processed[$id] ) ) { continue; }
    $processed[$id] = true;
    $product = wc_get_product( $id );
    if ( ! $product || $product->is_type( 'variation' ) ||
        (int) get_post_field( 'post_author', $id ) !== $vendor ) {
        ++$counts['skipped'];
        echo 'TROPITOP_EDITORIAL_SKIP=' . wp_json_encode( array('id'=>$id,'source_id'=>(int)$row['id']) ) . PHP_EOL;
        continue;
    }
    ++$counts['reviewed'];
    try {
        $payload = json_decode( (string) $row['source_payload'], true );
        $payload = is_array($payload) ? $payload : array();
        $payload['source_url'] = (string) ( $payload['source_url'] ?? $row['source_url'] ?? '' );
        $payload['title'] = (string) ( $payload['title'] ?? $row['title'] ?? $product->get_name() );

        $before_description = (string) $product->get_description('edit');
        $before_short = (string) $product->get_short_description('edit');
        $after_description = MDO_Tropitop_Editorial::description(
            $before_description, $product->get_name(), (string) ( $payload['short_description'] ?? $before_short )
        );
        $after_short = MDO_Tropitop_Editorial::short_description( $before_short );
        $flags = array_values(array_unique(array_merge(
            MDO_Tropitop_Editorial::residual_markers( $after_description ),
            MDO_Tropitop_Editorial::residual_markers( $after_short )
        )));
        if ( $flags ) { ++$counts['residual_flags']; }
        if ( '' === trim(wp_strip_all_tags($after_description)) ) {
            ++$counts['no_description_after_cleanup'];
        }
        $current_cats = array_map('intval', $product->get_category_ids('edit'));
        sort($current_cats);
        // The importer owns the authoritative, bounded source taxonomy.
        $new_cats = MDO_Woo_Importer::tropitop_category_ids( $payload, $mode === 'apply' );
        sort($new_cats);
        $text_changed = $before_description !== $after_description || $before_short !== $after_short;
        $categories_changed = $current_cats !== $new_cats;
        $details = array(
            'id' => $id, 'sku' => (string) $product->get_sku(),
            'title' => (string) $product->get_name(),
            'copy_changed' => $text_changed, 'category_changed' => $categories_changed,
            'categories_before' => $current_cats, 'categories_after' => $new_cats,
            'remaining' => $flags,
            'description_empty' => '' === trim(wp_strip_all_tags($after_description)),
        );
        if ( $text_changed ) { ++$counts['changed_copy']; }
        if ( $categories_changed ) { ++$counts['changed_categories']; }
        if ( $mode === 'apply' && ( $text_changed || $categories_changed ) ) {
            // No price, image, slug, stock, variant, weight or visibility changes.
            if ( $text_changed ) {
                $product->set_description( $after_description );
                $product->set_short_description( $after_short );
            }
            if ( $categories_changed ) { $product->set_category_ids( $new_cats ); }
            $product->save();
            if ( $categories_changed ) {
                // WooCommerce post-save hooks sometimes add unrelated categories
                // (previously "Jamones y paletas" to a melon pack). Directly
                // reassert only this supplier's verified category mapping.
                $live = wp_get_post_terms($id,'product_cat',array('fields'=>'ids'));
                if (is_wp_error($live))throw new RuntimeException('Category verification failed '.$id);
                $live = array_map('intval',(array)$live);
                sort($live);
                if ($live !== $new_cats) {
                    $applied = wp_set_object_terms($id,$new_cats,'product_cat',false);
                    if (is_wp_error($applied))throw new RuntimeException('Post-save category correction failed '.$id);
                    clean_post_cache($id);
                }
            }
        }
        if ( $flags ) {
            // Only public-facing copy. Bounded excerpts expose residual wording
            // without dumping full product descriptions into the action log.
            $plain = trim(preg_replace('/\\s+/u', ' ', html_entity_decode(
                wp_strip_all_tags($after_description . ' ' . $after_short), ENT_QUOTES | ENT_HTML5, 'UTF-8'
            )));
            $re = '/\\b(?:nuestr\\w*|nosotr\\w*|ofrecemos|seleccionamos|preparamos|enviamos|cultivamos|recolectamos|trabajamos|colaboramos|cosechamos|garantizamos|suscr[ií]bete|whatsapp|visita|escr[ií]benos)\\b/iu';
            $ctx = array();
            if ( preg_match_all($re, $plain, $hits, PREG_OFFSET_CAPTURE) ) {
                foreach ( array_slice($hits[0],0,6) as $hit ) {
                    $ctx[] = mb_substr($plain,max(0,mb_strlen(mb_strcut($plain,0,$hit[1],'UTF-8'))-90),220,'UTF-8');
                }
            }
            $details['residual_context']=$ctx;
        }
        if ( $categories_changed ) {
            $details['category_term_names']=array();
            foreach (array_values(array_unique(array_merge($current_cats,$new_cats))) as $term_id) {
                $term=get_term($term_id,'product_cat');
                $details['category_term_names'][(string)$term_id]=$term instanceof WP_Term ? $term->name : 'term_missing';
            }
        }
        if ($text_changed || $categories_changed || $flags || $details['description_empty']) {
            echo 'TROPITOP_EDITORIAL_PRODUCT=' . wp_json_encode($details,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }
    } catch ( Throwable $e ) {
        ++$counts['errors'];
        echo 'TROPITOP_EDITORIAL_ERROR=' . wp_json_encode(array('id'=>$id,'reason'=>$e->getMessage()),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
}

// The redundant category is deleted only when every post/variation has
// been detached, including drafts. Unrelated vendors are never touched.
$old = get_term_by( 'slug', 'verduras', 'product_cat' );
$remaining = $old instanceof WP_Term ? get_objects_in_term( (int) $old->term_id, 'product_cat' ) : array();
$remaining = is_wp_error( $remaining ) ? array( 'lookup_failed' ) : (array) $remaining;
$counts['legacy_category_remaining_objects'] = count($remaining);
$counts['legacy_category_deleted'] = false;
if ($mode === 'apply' && $old instanceof WP_Term && ! $remaining && $counts['errors'] === 0) {
    $deleted = wp_delete_term( (int)$old->term_id, 'product_cat' );
    $counts['legacy_category_deleted'] = ! is_wp_error($deleted) && (bool)$deleted;
}
$counts['mode'] = $mode;
$counts['canonical_category_id'] = (int) $canonical->term_id;
echo 'TROPITOP_EDITORIAL_SUMMARY=' . wp_json_encode($counts,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($counts['errors']>0){ throw new RuntimeException('Tropitop editorial/category reconciliation contained errors.'); }
