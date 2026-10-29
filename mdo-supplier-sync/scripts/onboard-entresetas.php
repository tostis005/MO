<?php
/**
 * Alta idempotente, sólo en producción, del productor EntreSetas.
 * Fases: ENTRESETAS_PHASE=setup|discover|import|report
 * Nunca activa cuentas WCFM ni publica fichas: falta acuerdo/logística/licencia.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$phase = getenv( 'ENTRESETAS_PHASE' ) ?: 'report';
$code = 'entresetas';
$origin = 'https://www.entresetas.com/tienda/';
$suppliers_table = MDO_Database::table( 'suppliers' );
$source_table = MDO_Database::table( 'source_products' );
$supplier = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$suppliers_table} WHERE code = %s LIMIT 1", $code
), ARRAY_A );
foreach ( array( 'home', 'siteurl' ) as $key ) {
    $host = strtolower( (string) wp_parse_url( (string) get_option( $key ), PHP_URL_HOST ) );
    if ( ! in_array( $host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
        throw new RuntimeException( 'EntreSetas: instancia no productiva (' . $key . ')' );
    }
}
if ( ! class_exists( 'MDO_Connector_EntreSetas' ) ) {
    throw new RuntimeException( 'EntreSetas: plugin no desplegado o sin conector.' );
}
$login = 'entresetas';
$user = get_user_by( 'login', $login );
if ( $supplier && ! empty( $supplier['vendor_user_id'] ) ) {
    $linked = get_user_by( 'id', (int) $supplier['vendor_user_id'] );
    if ( $linked && ( ! $user || (int) $linked->ID !== (int) $user->ID ) ) {
        throw new RuntimeException( 'EntreSetas: vinculación WCFM en conflicto; no modificar otro vendedor.' );
    }
}
if ( 'setup' === $phase ) {
    if ( $user ) {
        if ( ! in_array( 'wcfm_vendor', (array) $user->roles, true ) ) {
            throw new RuntimeException( 'EntreSetas: login ocupado por cuenta no vendedora.' );
        }
        if ( ! get_user_meta( (int) $user->ID, '_disable_vendor', true ) ) {
            echo "ENTRESETAS_VENDOR_ALREADY_ENABLED_SETUP_SKIPPED\n";
            exit;
        }
        $published = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author=%d AND post_type='product' AND post_status='publish'",
            (int) $user->ID
        ) );
        if ( $published ) { throw new RuntimeException( 'EntreSetas: vendedor ya tiene productos publicados.' ); }
    } else {
        // Match only existing, verified internal aliases; no fictional commercial email.
        $emails = $wpdb->get_col(
            "SELECT user_email FROM {$wpdb->users} WHERE user_email LIKE 'tostis005+%@%'"
        );
        $max = -1;
        $domains = array();
        foreach ( (array) $emails as $email ) {
            if ( preg_match( '/^tostis005\+([0-9]+)@([^@]+)$/i', (string) $email, $match ) ) {
                $max = max( $max, (int) $match[1] );
                $domain = strtolower( $match[2] );
                $domains[$domain] = ( $domains[$domain] ?? 0 ) + 1;
            }
        }
        if ( $max < 0 || ! $domains ) {
            throw new RuntimeException( 'EntreSetas: patrón de correo interno WCFM no validable.' );
        }
        arsort( $domains );
        $domain = (string) array_key_first( $domains );
        $email = '';
        for ( $next = $max + 1; $next < $max + 50; $next++ ) {
            $candidate = 'tostis005+' . $next . '@' . $domain;
            if ( ! email_exists( $candidate ) ) { $email = $candidate; break; }
        }
        if ( ! $email ) { throw new RuntimeException( 'EntreSetas: sin alias interno libre.' ); }
        $new_user = wp_insert_user( array(
            'user_login' => $login, 'user_nicename' => $login,
            'display_name' => 'EntreSetas', 'first_name' => 'EntreSetas',
            'role' => 'wcfm_vendor', 'user_email' => $email,
            'user_pass' => wp_generate_password( 32, true, true ),
        ) );
        if ( is_wp_error( $new_user ) ) { throw new RuntimeException( $new_user->get_error_message() ); }
        $user = get_user_by( 'id', (int) $new_user );
    }
    if ( ! $user || $user->user_login !== $login ) {
        throw new RuntimeException( 'EntreSetas: identidad inesperada.' );
    }
    $vendor_id = (int) $user->ID;
    update_user_meta( $vendor_id, '_disable_vendor', 1 );
    $description = '<p>EntreSetas es un proyecto de cultivo y elaboración de setas de Paredes de Nava, Palencia. Su catálogo reúne setas frescas de temporada, variedades deshidratadas, conservas y elaborados, harinas, sales e infusiones. Las características y disponibilidad se detallan en cada producto.</p>';
    $profile = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    $profile = is_array( $profile ) ? $profile : array();
    $profile['vendor_id'] = $vendor_id;
    $profile['user_name'] = $login;
    $profile['store_name'] = 'EntreSetas';
    $profile['store_slug'] = $login;
    $profile['user_email'] = (string) $user->user_email;
    $profile['shop_description'] = $description;
    $profile['store_hide_email'] = 'yes';
    $profile['store_hide_phone'] = 'yes';
    $profile['store_hide_address'] = 'yes';
    update_user_meta( $vendor_id, 'store_name', 'EntreSetas' );
    update_user_meta( $vendor_id, '_store_description', $description );
    update_user_meta( $vendor_id, 'wcfmmp_profile_settings', $profile );

    // Both flags default to false and require independent verification.
    add_option( 'mdo_entresetas_commercial_ready', 0, '', false );
    add_option( 'mdo_entresetas_media_authorized', 0, '', false );
    if ( $supplier && (int) $supplier['vendor_user_id'] !== $vendor_id ) {
        throw new RuntimeException( 'EntreSetas: ficha EMDO ya existe pero no está ligada al vendedor esperado.' );
    }
    $supplier_id = $supplier ? (int) $supplier['id'] : MDO_Supplier_Repository::save( array(
        'code' => $code, 'name' => 'EntreSetas',
        'source_url' => $origin, 'vendor_user_id' => $vendor_id,
        'connector' => $code, 'currency' => 'EUR', 'commercial_rule' => 'percentage',
        'sync_frequency' => 'daily', 'notification_email' => (string) get_option( 'admin_email' ),
        'notes' => 'PRELANZAMIENTO: vendedor deshabilitado. Sin tarifas WCFM verificadas, sin licencia de fotografías/logo y sin acuerdo comercial confirmado. Productos en borrador oculto. Origen: Paredes de Nava, Palencia.',
        'active' => 1,
    ) );
    if ( ! $supplier_id ) { throw new RuntimeException( 'EntreSetas: no se guardó EMDO.' ); }
    echo 'ENTRESETAS_SETUP=' . wp_json_encode( array(
        'supplier_id' => $supplier_id, 'vendor_id' => $vendor_id,
        'disabled' => (bool) get_user_meta( $vendor_id, '_disable_vendor', true ),
        'source_url' => $origin, 'brand_images_pending_authorization' => true
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    exit;
}
if ( ! $supplier || empty( $supplier['vendor_user_id'] ) || ! $user ||
    (int) $supplier['vendor_user_id'] !== (int) $user->ID ) {
    throw new RuntimeException( 'EntreSetas: ejecutar setup antes de otras fases.' );
}
$vendor_id = (int) $user->ID;
if ( ! get_user_meta( $vendor_id, '_disable_vendor', true ) &&
    in_array( $phase, array( 'discover','import' ), true ) ) {
    throw new RuntimeException( 'EntreSetas: seller habilitado; no ejecutar alta masiva automáticamente.' );
}
$id = (int) $supplier['id'];
if ( 'discover' === $phase ) {
    $found = MDO_Connector_EntreSetas::discover( $supplier );
    echo 'ENTRESETAS_DISCOVERY=' . count( $found['products'] ) . "\n";
    MDO_Scheduler::run_supplier( $id, 'manual' );
    exit;
}
if ( 'import' === $phase ) {
    // Manually reviewed sources that are valid become editable WCFM drafts.
    $rows = $wpdb->get_col( $wpdb->prepare(
        "SELECT id FROM {$source_table} WHERE supplier_id=%d AND status='pending' AND
        (last_error IS NULL OR last_error='') ORDER BY id ASC LIMIT 8", $id
    ) );
    $ok = 0; $blocked = 0;
    foreach ( $rows as $source_id ) {
        try {
            $product_id = MDO_Woo_Importer::import_source_product( (int) $source_id );
            $item = wc_get_product( $product_id );
            if ( ! $item || 'draft' !== $item->get_status() ||
                'hidden' !== $item->get_catalog_visibility() ||
                (int) get_post_field( 'post_author', $product_id ) !== $vendor_id ) {
                throw new RuntimeException( 'EntreSetas: producto no quedó en borrador privado del vendedor.' );
            }
            $ok++;
        } catch ( Throwable $error ) {
            MDO_Woo_Importer::mark_import_error( (int) $source_id, $error->getMessage() );
            echo 'ENTRESETAS_IMPORT_BLOCKED=' . (int) $source_id . ': ' . substr( $error->getMessage(), 0, 200 ) . "\n";
            $blocked++;
        }
    }
    $remaining = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$source_table} WHERE supplier_id=%d AND status='pending' AND
        (last_error IS NULL OR last_error='')", $id
    ) );
    echo 'ENTRESETAS_IMPORT=' . wp_json_encode(
        array( 'imported' => $ok, 'blocked' => $blocked, 'remaining' => $remaining )
    ) . "\n";
    exit;
}
if ( 'report' === $phase ) {
    $statuses = $wpdb->get_results( $wpdb->prepare(
        "SELECT status,COUNT(*) AS qty FROM {$source_table} WHERE supplier_id=%d GROUP BY status", $id
    ), ARRAY_A );
    $sync = $wpdb->get_row( $wpdb->prepare(
        'SELECT status,products_found,products_new,products_updated,errors_count,message,finished_at FROM ' .
        MDO_Database::table( 'sync_runs' ) . ' WHERE supplier_id=%d ORDER BY id DESC LIMIT 1', $id
    ), ARRAY_A );
    $published = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author=%d AND post_type='product' AND post_status='publish'",
        $vendor_id
    ) );
    $wrong = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$source_table} sp LEFT JOIN {$wpdb->posts} p ON p.ID=sp.wc_product_id
        WHERE sp.supplier_id=%d AND sp.status='active' AND
        (sp.wc_product_id IS NULL OR p.post_author != %d OR p.post_status != 'draft')",
        $id, $vendor_id
    ) );
    $error_events = $wpdb->get_results( $wpdb->prepare(
        'SELECT event_type,message FROM ' . MDO_Database::table('sync_events') .
        " WHERE supplier_id=%d AND severity='error' ORDER BY id DESC LIMIT 6", $id
    ), ARRAY_A );
    echo 'ENTRESETAS_REPORT=' . wp_json_encode( array(
        'supplier_id' => $id, 'vendor_id' => $vendor_id,
        'disabled' => (bool) get_user_meta( $vendor_id, '_disable_vendor', true ),
        'commercial_ready' => (bool) get_option( 'mdo_entresetas_commercial_ready', false ),
        'media_authorized' => (bool) get_option( 'mdo_entresetas_media_authorized', false ),
        'status_counts' => $statuses, 'sync' => $sync, 'recent_errors' => $error_events,
        'published_products' => $published, 'invalid_draft_links' => $wrong
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    if ( $published || $wrong || ( empty( $statuses ) && ! empty( $sync['errors_count'] ) ) ) { exit( 42 ); }
    exit;
}
throw new RuntimeException( 'EntreSetas: fase desconocida.' );
