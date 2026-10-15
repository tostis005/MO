<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$vendors = array();
foreach ( get_users( array( 'role' => 'wcfm_vendor', 'number' => 2000 ) ) as $user ) {
    $settings = get_user_meta( $user->ID, 'wcfmmp_profile_settings', true );
    $vendors[] = array(
        'id' => (int) $user->ID,
        'name' => (string) $user->display_name,
        'disabled' => (string) get_user_meta( $user->ID, '_disable_vendor', true ),
        'offline' => (string) get_user_meta( $user->ID, '_wcfm_store_offline', true ),
        'profile_keys' => is_array( $settings ) ? array_keys( $settings ) : array(),
        'banner_type' => isset( $settings['banner'] ) ? gettype( $settings['banner'] ) : null,
        'gravatar_type' => isset( $settings['gravatar'] ) ? gettype( $settings['gravatar'] ) : null,
    );
}
$patterns = array();
foreach ( get_users( array( 'fields' => array( 'ID', 'user_email', 'user_login' ), 'number' => 5000 ) ) as $u ) {
    $local = strtolower( (string) explode( '@', $u->user_email )[0] );
    if ( strpos( $local, 'tostis005' ) !== false ) {
        $patterns[] = preg_replace( '/[0-9]+/', '#', $local );
    }
}
$patterns = array_count_values( $patterns );
$report = array(
    'vendor_count' => count( $vendors ),
    'sample_profiles' => array_slice( $vendors, -12 ),
    'tostis005_email_local_patterns' => $patterns,
    'existing_latitudes_user' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE user_nicename=%s OR user_login=%s LIMIT 1", 'latitudes', 'latitudes' ) ),
    'supplier_exists' => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . MDO_Database::table( 'suppliers' ) . ' WHERE code=%s OR source_url LIKE %s', 'latitudes', '%latitudes.es%' ) ),
);
foreach ( array( 'https://latitudes.es/exoticos/', 'https://latitudes.es/premium/' ) as $url ) {
    $response = wp_remote_get( $url, array( 'timeout' => 20, 'headers' => array( 'Accept' => 'text/html' ) ) );
    $html = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
    $report['source_tests'][] = array(
        'url' => $url,
        'status' => is_wp_error( $response ) ? $response->get_error_code() : wp_remote_retrieve_response_code( $response ),
        'html_bytes' => strlen( $html ),
        'product_link_candidates' => preg_match_all( '~href=["\x27][^"\x27]*latitudes\\.es/(?:exoticos|premium)/[^"\x27]*["\x27]~i', $html ),
        'pagination_detected' => (bool) preg_match( '~/page/[2-9]/~', $html ),
    );
}

$homepage = wp_remote_get( 'https://latitudes.es/', array( 'timeout' => 20 ) );
$home_html = is_wp_error( $homepage ) ? '' : (string) wp_remote_retrieve_body( $homepage );
if ( $home_html ) {
    $doc = new DOMDocument();
    $old = libxml_use_internal_errors( true );
    $doc->loadHTML( '<?xml encoding="utf-8" ?>' . $home_html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
    libxml_clear_errors();
    libxml_use_internal_errors( $old );
    $xp = new DOMXPath( $doc );
    $found = array();
    foreach ( $xp->query( '//img[@src]' ) ?: array() as $img ) {
        $src = (string) $img->getAttribute( 'src' );
        $hint = strtolower( $src . ' ' . $img->getAttribute( 'alt' ) . ' ' . $img->getAttribute( 'class' ) );
        if ( preg_match( '/logo|latitudes|cabecera|banner|slider|hero/i', $hint ) ) {
            $found[] = array( 'src' => $src, 'alt' => $img->getAttribute( 'alt' ), 'class' => $img->getAttribute( 'class' ) );
        }
    }
    foreach ( $xp->query( '//*[@style]' ) ?: array() as $el ) {
        $style = (string) $el->getAttribute( 'style' );
        if ( preg_match( '/background-image\\s*:\\s*url\\([\\x22\\x27]?([^\\)\\x22\\x27]+)/i', $style, $m ) ) {
            $found[] = array( 'background' => html_entity_decode( $m[1] ) );
        }
    }
    $report['image_candidates'] = array_slice( $found, 0, 25 );
}


$sup_table = MDO_Database::table( 'suppliers' );
$source_table = MDO_Database::table( 'source_products' );
$run_table = MDO_Database::table( 'sync_runs' );
$sup_row = $wpdb->get_row( $wpdb->prepare( "SELECT id,name,code,vendor_user_id,connector,sync_frequency,active,last_sync_at FROM {$sup_table} WHERE code=%s LIMIT 1", 'latitudes' ), ARRAY_A );
$lat_user = get_user_by( 'login', 'latitudes' );
$lat_profile = $lat_user ? get_user_meta( (int) $lat_user->ID, 'wcfmmp_profile_settings', true ) : array();
$lat_profile = is_array( $lat_profile ) ? $lat_profile : array();
$report['latitudes_state'] = array(
    'supplier' => $sup_row ?: null,
    'user_id' => $lat_user ? (int) $lat_user->ID : 0,
    'disabled' => $lat_user ? (string) get_user_meta( (int) $lat_user->ID, '_disable_vendor', true ) : '',
    'gravatar' => (string) ( $lat_profile['gravatar'] ?? '' ),
    'banner' => (string) ( $lat_profile['banner'] ?? '' ),
);
if ( $sup_row ) {
    $supplier_id = (int) $sup_row['id'];
    $report['latitudes_state']['source_counts'] = $wpdb->get_results( $wpdb->prepare(
        "SELECT status,COUNT(*) AS count FROM {$source_table} WHERE supplier_id=%d GROUP BY status", $supplier_id
    ), ARRAY_A );
    $report['latitudes_state']['last_run'] = $wpdb->get_row( $wpdb->prepare(
        "SELECT id,status,products_found,products_new,products_updated,errors_count,message,started_at,finished_at FROM {$run_table} WHERE supplier_id=%d ORDER BY id DESC LIMIT 1", $supplier_id
    ), ARRAY_A );
}


if ( $sup_row ) {
    $product_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id,title,status,source_url,source_price,source_stock_status,source_payload FROM {$source_table} WHERE supplier_id=%d", (int) $sup_row['id']
    ), ARRAY_A );
    $summary = array(
        'total' => count( $product_rows ),
        'missing_price' => 0, 'missing_images' => 0, 'short_description' => 0,
        'many_variants' => 0, 'unknown_stock' => 0, 'potentially_blocked' => 0,
        'stock_statuses' => array(), 'sample_flags' => array(),
    );
    foreach ( $product_rows as $row ) {
        $payload = json_decode( (string) $row['source_payload'], true );
        $payload = is_array( $payload ) ? $payload : array();
        $desc_len = mb_strlen( trim( wp_strip_all_tags( (string) ( $payload['description'] ?? '' ) ) ) );
        $image_count = (int) ( $payload['image_count'] ?? count( (array) ( $payload['images'] ?? array() ) ) );
        $variation_count = (int) ( $payload['variation_count'] ?? 0 );
        $stock = (string) $row['source_stock_status'];
        $summary['stock_statuses'][ $stock ] = ( $summary['stock_statuses'][ $stock ] ?? 0 ) + 1;
        $flags = array();
        if ( ! is_numeric( $row['source_price'] ) || (float) $row['source_price'] <= 0 ) { $summary['missing_price']++; $flags[]='price'; }
        if ( $image_count < 1 ) { $summary['missing_images']++; $flags[]='image'; }
        if ( $desc_len < 35 ) { $summary['short_description']++; $flags[]='description'; }
        if ( $variation_count > 1 ) { $summary['many_variants']++; $flags[]='variants'; }
        if ( ! in_array( $stock, array( 'instock', 'outofstock' ), true ) ) { $summary['unknown_stock']++; $flags[]='stock'; }
        if ( in_array( 'price', $flags, true ) || in_array( 'image', $flags, true ) || in_array( 'stock', $flags, true ) || in_array( 'variants', $flags, true ) ) {
            $summary['potentially_blocked']++;
        }
        if ( $flags && count( $summary['sample_flags'] ) < 35 ) {
            $summary['sample_flags'][] = array(
                'id' => (int) $row['id'], 'title' => $row['title'], 'flags' => $flags,
                'price' => $row['source_price'], 'stock' => $stock, 'images' => $image_count,
                'description_length' => $desc_len, 'variations' => $variation_count
            );
        }
    }
    $report['latitudes_quality'] = $summary;
}


$sample_urls = array(
    'foie' => 'https://latitudes.es/exoticos/pato/foie-de-pato-pieza-700-g-aprox/',
    'magret' => 'https://latitudes.es/exoticos/pato/magret-de-pato-pieza-390-gr-aprox/',
);
$report['latitudes_stock_diagnostic'] = array();
foreach ( $sample_urls as $key => $sample_url ) {
    $response = wp_safe_remote_get( $sample_url, array( 'timeout' => 30, 'headers' => array( 'Cache-Control' => 'no-cache' ) ) );
    if ( is_wp_error( $response ) ) {
        $report['latitudes_stock_diagnostic'][ $key ] = array( 'error' => $response->get_error_message() );
        continue;
    }
    $html = (string) wp_remote_retrieve_body( $response );
    $doc = new DOMDocument();
    $old = libxml_use_internal_errors( true );
    $doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
    libxml_clear_errors(); libxml_use_internal_errors( $old );
    $xp = new DOMXPath( $doc );
    $nodes = array();
    foreach ( $xp->query( "//*[contains(concat(' ',normalize-space(@class),' '),' stock ') or contains(concat(' ',normalize-space(@class),' '),' availability ')]" ) ?: array() as $node ) {
        $nodes[] = array(
            'tag' => $node->nodeName,
            'class' => $node->getAttribute( 'class' ),
            'text' => mb_substr( trim( preg_replace( '/\\s+/u', ' ', $node->textContent ) ), 0, 130 ),
        );
    }
    $offer_data = array();
    foreach ( $xp->query( "//script[contains(translate(@type,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'ld+json')]" ) ?: array() as $node ) {
        $script = (string) $node->textContent;
        if ( strpos( $script, '"availability"' ) !== false || strpos( $script, '"@type"' ) !== false ) {
            $offer_data[] = array( 'product_stock_matches' => array_slice( array_values( array_unique( array_filter( array_map(
                static function( $chunk ) { return strpos( strtolower( $chunk ), 'instock' ) !== false || strpos( strtolower( $chunk ), 'outofstock' ) !== false ? mb_substr( $chunk, 0, 130 ) : ''; },
                preg_split( '/[{}]/', $script )
            ) ) ) ), 0, 8 ), 'bytes' => strlen( $script ) );
        }
    }
    $product = MDO_Connector_Latitudes::scrape_product( $sample_url );
    $record = $wpdb->get_row( $wpdb->prepare(
        "SELECT id,status,source_stock_status,wc_product_id,source_payload FROM {$source_table} WHERE supplier_id=%d AND source_url=%s LIMIT 1",
        $supplier_id, $sample_url
    ), ARRAY_A );
    $wc = $record && ! empty( $record['wc_product_id'] ) ? wc_get_product( (int) $record['wc_product_id'] ) : false;
    $report['latitudes_stock_diagnostic'][ $key ] = array(
        'status' => wp_remote_retrieve_response_code( $response ),
        'nodes' => array_slice( $nodes, 0, 12 ),
        'schema' => array_slice( $offer_data, 0, 4 ),
        'scraped_stock' => $product['stock_status'],
        'emdo_stock' => $record['source_stock_status'] ?? null,
        'wc_stock' => $wc ? $wc->get_stock_status() : null,
        'source_status' => $record['status'] ?? null,
        'raw_excerpt_add_to_cart' => strpos( $html, 'single_add_to_cart_button' ) !== false,
        'raw_excerpt_soldout' => strpos( $html, 'Out of stock' ) !== false,
    );
}

echo 'LATITUDES_READONLY_DIAGNOSTIC=' . wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
