<?php
/**
 * Idempotent, scoped onboarding of Quesos Pajarete.
 * PAJARETE_PHASE=setup|discover|scrape|import|report
 * Vendor and products remain hidden until commercial and shipping conditions are confirmed.
 */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'MDO_Connector_Quesos_Pajarete' ) ||
     ! class_exists( 'MDO_Supplier_Repository' ) || ! class_exists( 'WooCommerce' ) ) {
    throw new RuntimeException( 'EMDO, Pajarete connector and WooCommerce must be active.' );
}
foreach ( array( 'home', 'siteurl' ) as $key ) {
    $host = strtolower( (string) wp_parse_url( (string) get_option( $key ), PHP_URL_HOST ) );
    if ( ! in_array( $host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
        throw new RuntimeException( 'Not production: ' . $key );
    }
}
global $wpdb;
$phase = getenv( 'PAJARETE_PHASE' ) ?: 'report';
$code = 'quesos-pajarete';
$login = $code;
$name = 'Quesos Pajarete';
$origin = 'https://quesospajarete.com/tienda/';
$state_key = '_mdo_pajarete_catalog_20261023';
$table = MDO_Database::table( 'suppliers' );
$products_table = MDO_Database::table( 'source_products' );
$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE code = %s LIMIT 1", $code ), ARRAY_A );
$vendor = get_user_by( 'login', $login );
$store_url = '';

if ( 'setup' === $phase ) {
    if ( $existing && (int) $existing['vendor_user_id'] && $vendor &&
         (int) $existing['vendor_user_id'] !== (int) $vendor->ID ) {
        throw new RuntimeException( 'Pajarete supplier has different associated vendor; refusing mutation.' );
    }
    if ( $existing && (int) $existing['vendor_user_id'] ) {
        $linked = get_user_by( 'id', (int) $existing['vendor_user_id'] );
        if ( $linked && $linked->user_login !== $login ) {
            throw new RuntimeException( 'Pajarete supplier points to unrelated vendor.' );
        }
    }
    if ( ! $vendor ) {
        $emails = $wpdb->get_col( "SELECT user_email FROM {$wpdb->users} WHERE user_email LIKE 'tostis005+%@%'" );
        $max = -1; $domains = array();
        foreach ( (array) $emails as $email ) {
            if ( preg_match( '/^tostis005\+([0-9]+)@([^@]+)$/i', (string) $email, $parts ) ) {
                $max = max( $max, (int) $parts[1] );
                $domain = strtolower( $parts[2] );
                $domains[$domain] = ( $domains[$domain] ?? 0 ) + 1;
            }
        }
        if ( $max < 0 || ! $domains ) { throw new RuntimeException( 'Internal WCFM email convention unavailable.' ); }
        arsort( $domains );
        $domain = (string) array_key_first( $domains );
        $email = '';
        for ( $n = $max + 1; $n <= $max + 100; $n++ ) {
            $candidate = 'tostis005+' . $n . '@' . $domain;
            if ( ! email_exists( $candidate ) ) { $email = $candidate; break; }
        }
        if ( ! $email ) { throw new RuntimeException( 'Internal WCFM email not available.' ); }
        $vendor_id = wp_insert_user( array(
            'user_login' => $login, 'user_nicename' => $login,
            'user_email' => $email, 'display_name' => $name,
            'first_name' => $name, 'role' => 'wcfm_vendor',
            'user_pass' => wp_generate_password( 36, true, true ),
        ) );
        if ( is_wp_error( $vendor_id ) ) { throw new RuntimeException( $vendor_id->get_error_message() ); }
        $vendor = get_user_by( 'id', (int) $vendor_id );
    }
    if ( ! $vendor || $vendor->user_login !== $login ) {
        throw new RuntimeException( 'Wrong vendor login.' );
    }
    $vendor_id = (int) $vendor->ID;
    $published = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status='publish'",
        $vendor_id ) );
    if ( $published || ( $existing && ! get_user_meta( $vendor_id, '_disable_vendor', true ) ) ) {
        throw new RuntimeException( 'Existing Pajarete vendor is live; refusing to disable or reset it.' );
    }
    $vendor->set_role( 'wcfm_vendor' );
    update_user_meta( $vendor_id, '_disable_vendor', 1 );
    update_user_meta( $vendor_id, 'store_name', $name );
    $bio = '<p>En Villamartín, en plena Sierra de Cádiz, Quesos Pajarete mantiene viva la tradición quesera con elaboraciones artesanales de cabra payoya, oveja y mezclas de ambas leches. Cada queso refleja el carácter de su tierra, la selección de las materias primas y el cuidado de sus procesos de maduración.</p><p>Sus semicurados, curados y grandes reservas, junto a especialidades de sabores tradicionales, han recibido numerosos reconocimientos nacionales e internacionales. Un proyecto que combina oficio, calidad y respeto por el origen para acercar a la mesa quesos con identidad propia.</p>';
    $profile = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    $profile = is_array( $profile ) ? $profile : array();
    $profile['user_name'] = $login;
    $profile['user_email'] = (string) $vendor->user_email;
    $profile['store_name'] = $name;
    $profile['store_slug'] = $login;
    $profile['vendor_id'] = $vendor_id;
    $profile['shop_description'] = $bio;
    $profile['store_hide_email'] = 'yes';
    $profile['store_hide_phone'] = 'yes';
    $profile['store_hide_address'] = 'yes';
    update_user_meta( $vendor_id, '_store_description', $bio );
    update_user_meta( $vendor_id, 'wcfmmp_profile_settings', $profile );
    $excluded = implode( "\n", array(
        '/fresco/', '/queso-fresco/', '/tierno/', '/queso-tierno/',
        '/untable', '/crema-de-queso/', '/cremas-de-queso/', '/queso-para-untar/',
    ) );
    $id = MDO_Supplier_Repository::save( array(
        'code' => $code, 'name' => $name, 'source_url' => $origin, 'vendor_user_id' => $vendor_id,
        'connector' => $code, 'commercial_rule' => 'percentage', 'currency' => 'EUR',
        'sync_frequency' => 'daily', 'notification_email' => (string) get_option( 'admin_email' ),
        'exclusion_url_fragments' => $excluded, 'active' => 1, 'fixed_fee_scope' => 'order',
        'notes' => 'PRELANZAMIENTO: fichas en borrador y tienda oculta hasta cerrar portes, comisión y autorización comercial. Excluir fresco, tierno y queso untable/crema de queso, incluso en sincronizaciones futuras. Precios y variantes desde origen.',
    ), $existing ? (int) $existing['id'] : 0 );
    if ( ! $id ) { throw new RuntimeException( 'Failed to save Pajarete supplier.' ); }
    echo 'PAJARETE_SETUP=' . wp_json_encode( array( 'supplier_id' => $id, 'vendor_id' => $vendor_id,
        'vendor_disabled' => (bool) get_user_meta( $vendor_id, '_disable_vendor', true ),
        'store_slug' => $login, 'source' => $origin, 'excluded' => explode( "\n", $excluded ) ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
    exit;
}

if ( ! $existing || ! $vendor || (int) $existing['vendor_user_id'] !== (int) $vendor->ID ||
     ! get_user_meta( (int) $vendor->ID, '_disable_vendor', true ) ) {
    throw new RuntimeException( 'Pajarete prelaunch supplier/vendor missing or enabled; refusing import.' );
}
$id = (int) $existing['id'];
$state = get_option( $state_key, array() );
$state = is_array( $state ) ? $state : array();
if ( 'discover' === $phase ) {
    $catalog = MDO_Connector_Quesos_Pajarete::discover( $existing );
    $eligible = (array) ( $catalog['products'] ?? array() );
    $excluded = (array) ( $catalog['excluded'] ?? array() );
    if ( count( $eligible ) < 20 || count( $eligible ) > 700 ) {
        throw new RuntimeException( 'Pajarete: unusual eligible catalog size.' );
    }
    if ( ! $excluded ) { throw new RuntimeException( 'Pajarete: zero exclusions detected; review source categories before proceeding.' ); }
    foreach ( $eligible as $url ) {
        if ( MDO_Connector_Quesos_Pajarete::excluded( $url ) ) {
            throw new RuntimeException( 'Excluded URL was included: ' . $url );
        }
    }
    $state = array(
        'urls' => array_values( $eligible ), 'excluded' => array_values( $excluded ),
        'pages' => count( (array) ( $catalog['pages'] ?? array() ) ),
        'offset' => 0, 'errors' => array(), 'started' => time(),
    );
    update_option( $state_key, $state, false );
    echo 'PAJARETE_DISCOVER=' . wp_json_encode( array(
        'eligible' => count( $eligible ), 'excluded' => count( $excluded ), 'pages' => $state['pages'],
        'excluded_urls' => array_slice( $excluded, 0, 15 ),
    ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
    exit;
}
if ( empty( $state['urls'] ) || ! isset( $state['offset'] ) ) {
    throw new RuntimeException( 'Pajarete: discovery cache missing; run discover first.' );
}
if ( 'scrape' === $phase ) {
    $offset = (int) $state['offset'];
    $limit = min( count( $state['urls'] ), $offset + 6 );
    $imported = 0;
    for ( $k = $offset; $k < $limit; $k++ ) {
        $url = (string) $state['urls'][$k];
        try {
            $payload = MDO_Connector_Quesos_Pajarete::scrape_product( $url );
            if ( MDO_Connector_Quesos_Pajarete::excluded( $url, (string) $payload['title'] ) ) {
                throw new RuntimeException( 'Name exclusion failed closed.' );
            }
            MDO_Connector_Quesos_Pajarete::upsert_product( $id, MDO_Text::normalize_product( $payload ) );
            $imported++;
        } catch ( Throwable $error ) {
            $state['errors'][$url] = mb_substr( $error->getMessage(), 0, 180 );
        }
        $state['offset'] = $k + 1;
        update_option( $state_key, $state, false );
    }
    echo 'PAJARETE_SCRAPE=' . wp_json_encode( array(
        'processed' => $state['offset'], 'total' => count( $state['urls'] ),
        'imported' => $imported, 'errors' => count( $state['errors'] ),
        'remaining' => count( $state['urls'] ) - $state['offset'],
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
    exit;
}
if ( 'import' === $phase ) {
    if ( (int) $state['offset'] < count( $state['urls'] ) ) {
        throw new RuntimeException( 'Pajarete: scrape not complete.' );
    }
    $rows = $wpdb->get_col( $wpdb->prepare(
        "SELECT id FROM {$products_table} WHERE supplier_id=%d AND status='pending' AND (last_error IS NULL OR last_error='') ORDER BY id LIMIT 4", $id ) );
    $done = 0;
    $blocked = 0;
    foreach ( $rows as $row_id ) {
        try {
            $product_id = MDO_Woo_Importer::import_source_product( (int) $row_id );
            $product = wc_get_product( $product_id );
            if ( ! $product || 'draft' !== $product->get_status() || 'hidden' !== $product->get_catalog_visibility() ||
                 (int) get_post_field( 'post_author', $product_id ) !== (int) $vendor->ID ) {
                throw new RuntimeException( 'Pajarete: draft/hidden/vendor safety validation failed.' );
            }
            $title = remove_accents( strtolower( $product->get_name() ) );
            if ( MDO_Connector_Quesos_Pajarete::excluded( (string) get_post_meta( $product_id, '_emdo_source_url', true ), $product->get_name() ) ) {
                throw new RuntimeException( 'Pajarete: excluded cheese reached WooCommerce.' );
            }
            if ( preg_match( '/\bqueso\b|\bsemicurado\b|\bcurado\b|\bpatrufo\b|\bminiqueso\b|\bblister\b|\breserva\b/i', $title ) ) {
                $root = get_term_by( 'slug', 'quesos', 'product_cat' );
                if ( ! $root ) { $root = get_term_by( 'slug', 'quesos-y-lacteos', 'product_cat' ); }
                if ( ! $root ) {
                    $new_term = term_exists( 'Quesos artesanos', 'product_cat' );
                    if ( ! $new_term ) { $new_term = wp_insert_term( 'Quesos artesanos', 'product_cat' ); }
                    $term_id = ! is_wp_error( $new_term ) ? (int) ( is_array( $new_term ) ? $new_term['term_id'] : $new_term ) : 0;
                } else { $term_id = (int) $root->term_id; }
                if ( $term_id ) { wp_set_object_terms( $product_id, array( $term_id ), 'product_cat', true ); }
            }
            $done++;
        } catch ( Throwable $error ) {
            MDO_Woo_Importer::mark_import_error( (int) $row_id, $error->getMessage() );
            $blocked++;
            echo 'PAJARETE_IMPORT_REVIEW=' . (int) $row_id . ' ' . mb_substr( $error->getMessage(), 0, 170 ) . PHP_EOL;
        }
    }
    $remaining = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$products_table} WHERE supplier_id=%d AND status='pending' AND (last_error IS NULL OR last_error='')", $id ) );
    echo 'PAJARETE_IMPORT=' . wp_json_encode( array(
        'created' => $done, 'blocked' => $blocked, 'remaining' => $remaining ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
    exit;
}
if ( 'report' === $phase ) {
    $statuses = $wpdb->get_results( $wpdb->prepare(
        "SELECT status, COUNT(*) quantity FROM {$products_table} WHERE supplier_id=%d GROUP BY status", $id ), ARRAY_A );
    $drafts = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author=%d AND post_type='product' AND post_status='draft'", (int) $vendor->ID ) );
    $public = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author=%d AND post_type='product' AND post_status='publish'", (int) $vendor->ID ) );
    $violations = $wpdb->get_results( $wpdb->prepare(
        "SELECT source_url,title,wc_product_id FROM {$products_table}
         WHERE supplier_id=%d AND wc_product_id IS NOT NULL ORDER BY id", $id ), ARRAY_A );
    $bad = array();
    foreach ( $violations as $row ) {
        $p = wc_get_product( (int) $row['wc_product_id'] );
        if ( MDO_Connector_Quesos_Pajarete::excluded( (string) $row['source_url'], (string) $row['title'] ) ||
             ! $p || 'draft' !== $p->get_status() || 'hidden' !== $p->get_catalog_visibility() ) {
            $bad[] = (int) $row['wc_product_id'];
        }
    }
    $result = array(
        'supplier_id' => $id, 'vendor_id' => (int) $vendor->ID,
        'vendor_disabled' => (bool) get_user_meta( (int) $vendor->ID, '_disable_vendor', true ),
        'discovered' => count( $state['urls'] ), 'excluded' => count( $state['excluded'] ),
        'scraped' => (int) $state['offset'], 'scrape_errors' => $state['errors'],
        'statuses' => $statuses, 'woo_draft_products' => $drafts,
        'woo_published_products' => $public, 'safety_violations' => $bad,
        'store_slug' => $login,
    );
    echo 'PAJARETE_REPORT=' . wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
    if ( $public || $bad ) { exit( 42 ); }
    exit;
}
throw new RuntimeException( 'Unknown PAJARETE_PHASE.' );
