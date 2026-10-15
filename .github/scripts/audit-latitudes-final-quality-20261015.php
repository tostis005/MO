<?php
/** Final quality audit of 138 hidden Latitudes products, no database writes. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$table = MDO_Database::table( 'source_products' );
$sup_table = MDO_Database::table( 'suppliers' );
$sup = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$sup_table} WHERE code=%s", 'latitudes' ), ARRAY_A );
if ( ! $sup || (string) get_user_meta( (int) $sup['vendor_user_id'], '_disable_vendor', true ) !== '1' ) {
    throw new RuntimeException( 'Latitudes vendor must remain disabled.' );
}
$rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT id,wc_product_id,title,source_url,source_price,source_stock_status,source_payload,status FROM {$table} WHERE supplier_id=%d ORDER BY id", (int) $sup['id']
), ARRAY_A );
if ( count( $rows ) !== 138 ) { throw new RuntimeException( 'Unexpected source count.' ); }
$summary = array(
    'total' => count( $rows ), 'reviewed_descriptions' => 0, 'categorized' => 0,
    'prices_ok' => 0, 'stocks_ok' => 0, 'images_ok' => 0,
    'visible' => 0, 'duplicates' => 0, 'source_source_urls_ok' => 0,
    'pending' => 0, 'short_content' => 0, 'allergen_declarations_preserved' => 0,
    'source_allergen_declarations' => 0, 'category_totals' => array(), 'unexpected_packs' => array(), 'issues' => array(),
    'manual_review' => array(),
);
$hashes = array();
foreach ( $rows as $row ) {
    $id = (int) $row['id'];
    $pid = (int) $row['wc_product_id'];
    $p = json_decode( (string) $row['source_payload'], true ) ?: array();
    $wc = wc_get_product( $pid );
    $flags = array();
    if ( ! $wc || 'active' !== $row['status'] ) {
        $flags[] = 'not_imported'; $summary['pending']++; continue;
    }
    if ( $wc->get_status() !== 'draft' || $wc->get_catalog_visibility() !== 'hidden' ||
        (int) get_post_field( 'post_author', $pid ) !== (int) $sup['vendor_user_id'] ) {
        $flags[] = 'not_hidden_or_wrong_vendor'; $summary['visible']++;
    }
    if ( abs( (float) $wc->get_price() - (float) $row['source_price'] ) < 0.02 ) { $summary['prices_ok']++; }
    else { $flags[] = 'price_mismatch'; }
    if ( $wc->get_stock_status() === $row['source_stock_status'] &&
        in_array( $row['source_stock_status'], array( 'instock', 'outofstock' ), true ) ) { $summary['stocks_ok']++; }
    else { $flags[] = 'stock_mismatch'; }

    $url = (string) $row['source_url'];
    if ( str_starts_with( $url, 'https://latitudes.es/' ) &&
        $url === (string) get_post_meta( $pid, '_emdo_source_url', true ) &&
        (int) get_post_meta( $pid, '_emdo_source_product_id', true ) === $id ) {
        $summary['source_source_urls_ok']++;
    } else { $flags[] = 'source_link'; }

    $ids = $wc->get_category_ids();
    $cats = array();
    foreach ( $ids as $term_id ) {
        $term = get_term( $term_id, 'product_cat' );
        if ( $term instanceof WP_Term ) {
            $cats[] = $term->slug;
            $summary['category_totals'][ $term->slug ] = ( $summary['category_totals'][ $term->slug ] ?? 0 ) + 1;
        }
    }
    if ( in_array( 'packs-y-lotes', $cats, true ) && ! str_contains( (string) $row['source_url'], '/pack-medallones-exoticos-' ) ) {
        $summary['unexpected_packs'][] = array( 'source_id' => $id, 'title' => $row['title'], 'cats' => $cats );
        $flags[] = 'unexpected_packs_category';
    }
    if ( $cats && get_post_meta( $pid, '_emdo_latitudes_category_v1', true ) &&
        ! array_intersect( array( 'sin-categorizar','uncategorized','naranjas','embutidos-y-curados' ), $cats ) ) {
        $summary['categorized']++;
    } else { $flags[] = 'incorrect_category'; }

    $map = json_decode( (string) get_post_meta( $pid, '_emdo_image_map', true ), true );
    $map = is_array( $map ) ? $map : array();
    $image_ok = false;
    foreach ( (array) ( $p['images'] ?? array() ) as $image_url ) {
        if ( isset( $map[ $image_url ] ) && (int) $map[ $image_url ] === (int) $wc->get_image_id() &&
            'attachment' === get_post_type( (int) $map[ $image_url ] ) ) { $image_ok = true; break; }
    }
    if ( $image_ok ) { $summary['images_ok']++; }
    else { $flags[] = 'source_image_not_attached'; }

    $desc = (string) $wc->get_description();
    $approved = (string) get_post_meta( $pid, '_emdo_latitudes_editorial_description', true );
    $clean = trim( wp_strip_all_tags( $desc ) );
    $h = md5( mb_strtolower( preg_replace( '/\s+/u', ' ', $clean ) ) );
    $hashes[$h] = ( $hashes[$h] ?? 0 ) + 1;
    if ( '1' === (string) get_post_meta( $pid, '_emdo_latitudes_editorial_approved', true ) &&
        $desc === $approved && mb_strlen( $clean ) >= 200 ) {
        $summary['reviewed_descriptions']++;
    } else { $flags[] = 'editorial_missing_or_drifted'; }
    if ( mb_strlen( $clean ) < 200 ) { $summary['short_content']++; }
    $src = trim( wp_strip_all_tags( (string) ( $p['description'] ?? '' ) ) );
    if ( preg_match( '/Al[eé]rgenos?\s*:/iu', $src ) ) {
        $summary['source_allergen_declarations']++;
        if ( preg_match( '/al[eé]rgenos?/iu', $clean ) ) {
            $summary['allergen_declarations_preserved']++;
        } else { $flags[] = 'source_allergen_information_lost'; }
    }
    $note = (string) get_post_meta( $pid, '_emdo_latitudes_manual_review', true );
    if ( $note ) {
        $summary['manual_review'][] = array( 'source_id' => $id, 'title' => $row['title'], 'reason' => $note );
    }
    if ( $flags ) {
        $summary['issues'][] = array( 'source_id' => $id, 'wc_id' => $pid, 'title' => $row['title'], 'flags' => $flags );
    }
}
$summary['duplicates'] = array_sum( array_map( fn($n)=>$n>1?$n:0, array_values( $hashes ) ) );
echo 'LATITUDES_EDITORIAL_FINAL_AUDIT=' . wp_json_encode( $summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
if ( $summary['pending'] || $summary['visible'] ||
    $summary['reviewed_descriptions'] !== 138 || $summary['categorized'] !== 138 ||
    $summary['prices_ok'] !== 138 || $summary['stocks_ok'] !== 138 ||
    $summary['images_ok'] !== 138 || $summary['duplicates'] !== 0 ||
    $summary['source_source_urls_ok'] !== 138 || $summary['issues'] ) {
    exit( 44 );
}
echo 'LATITUDES_138_EDITORIAL_AND_IMPORT_QA_OK' . PHP_EOL;
