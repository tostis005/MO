<?php
/**
 * Idempotent, non-public Latitudes onboarding for WP-CLI.
 * LATITUDES_PHASE=setup|report|smoke. No mass publication.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$phase = getenv( 'LATITUDES_PHASE' ) ?: 'setup';
$suppliers = MDO_Database::table( 'suppliers' );
$sources = MDO_Database::table( 'source_products' );
$runs = MDO_Database::table( 'sync_runs' );

$find_vendor = static function () {
    $user = get_user_by( 'login', 'latitudes' );
    if ( ! $user ) { $user = get_user_by( 'slug', 'latitudes' ); }
    return $user ?: false;
};
$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1", 'latitudes' ), ARRAY_A );

if ( 'setup' === $phase ) {
    if ( ! class_exists( 'MDO_Connector_Latitudes' ) ) {
        throw new RuntimeException( 'El plugin EMDO debe desplegar el nuevo conector antes del alta.' );
    }
    $vendor = $find_vendor();
    if ( ! $vendor ) {
        $emails = $wpdb->get_col( "SELECT user_email FROM {$wpdb->users} WHERE user_email LIKE 'tostis005+%@%'" );
        $max = -1;
        $domains = array();
        foreach ( (array) $emails as $email ) {
            if ( preg_match( '/^tostis005\\+([0-9]+)@([^@]+)$/i', (string) $email, $m ) ) {
                $max = max( $max, (int) $m[1] );
                $domain = strtolower( $m[2] );
                $domains[ $domain ] = ( $domains[ $domain ] ?? 0 ) + 1;
            }
        }
        if ( $max < 0 || ! $domains ) {
            throw new RuntimeException( 'No se ha encontrado un patrón correlativo seguro para los correos tostis005+N.' );
        }
        arsort( $domains );
        $domain = (string) array_key_first( $domains );
        for ( $number = $max + 1; $number < $max + 100; $number++ ) {
            $email = 'tostis005+' . $number . '@' . $domain;
            if ( ! email_exists( $email ) ) { break; }
        }
        if ( email_exists( $email ) ) { throw new RuntimeException( 'No hay correo interno libre.' ); }
        $user_id = wp_insert_user( array(
            'user_login' => 'latitudes',
            'user_nicename' => 'latitudes',
            'user_email' => $email,
            'display_name' => 'Latitudes',
            'first_name' => 'Latitudes',
            'role' => 'wcfm_vendor',
            'user_pass' => wp_generate_password( 32, true, true ),
        ) );
        if ( is_wp_error( $user_id ) ) { throw new RuntimeException( $user_id->get_error_message() ); }
        $vendor = get_user_by( 'id', (int) $user_id );
    }
    if ( ! $vendor || 'latitudes' !== strtolower( (string) $vendor->user_login ) ) {
        throw new RuntimeException( 'Existe un usuario potencialmente conflictivo: revisar antes de reutilizar.' );
    }
    $vendor_id = (int) $vendor->ID;
    // Disable BEFORE configuring vendor assets or running the scraper.
    update_user_meta( $vendor_id, '_disable_vendor', 1 );
    $vendor->set_role( 'wcfm_vendor' );
    update_user_meta( $vendor_id, 'store_name', 'Latitudes' );
    $description = '<p>Latitudes selecciona carnes exóticas y cortes premium de diferentes procedencias, desde avestruz, bisonte y canguro hasta wagyu y Black Angus. Su propuesta permite descubrir sabores de distintas regiones del mundo, con información de cada producto sobre su preparación y conservación.</p>';
    $profile = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    $profile = is_array( $profile ) ? $profile : array();
    $profile['user_name'] = 'latitudes';
    $profile['user_email'] = (string) $vendor->user_email;
    $profile['store_name'] = 'Latitudes';
    $profile['store_slug'] = 'latitudes';
    $profile['vendor_id'] = $vendor_id;
    $profile['shop_description'] = $description;
    $profile['store_hide_email'] = 'yes';
    $profile['store_hide_phone'] = 'yes';
    $profile['store_hide_address'] = 'yes';
    update_user_meta( $vendor_id, '_store_description', $description );

    // Reuse original public assets, but never guess image IDs or hotlink.
    // Only the supplier's own uploaded images may become WCFM media attachments.
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $home = wp_safe_remote_get( 'https://latitudes.es/', array( 'timeout' => 25 ) );
    $candidate_images = array( 'logo' => array(), 'banner' => array() );
    if ( ! is_wp_error( $home ) && 200 === (int) wp_remote_retrieve_response_code( $home ) ) {
        $doc = new DOMDocument();
        $old = libxml_use_internal_errors( true );
        $doc->loadHTML( '<?xml encoding="utf-8" ?>' . (string) wp_remote_retrieve_body( $home ), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors();
        libxml_use_internal_errors( $old );
        $xp = new DOMXPath( $doc );
        foreach ( $xp->query( '//img[@src]' ) ?: array() as $img ) {
            $hint = strtolower( (string) $img->getAttribute( 'alt' ) . ' ' . $img->getAttribute( 'class' ) . ' ' . $img->getAttribute( 'src' ) );
            $src = trim( (string) $img->getAttribute( 'data-src' ) );
            if ( ! $src ) { $src = trim( (string) $img->getAttribute( 'src' ) ); }
            if ( preg_match( '/logo|latitudes/i', $hint ) ) { $candidate_images['logo'][] = $src; }
            if ( preg_match( '/banner|slider|hero|cabecera|porto-ibanner-img/i', $hint ) ) { $candidate_images['banner'][] = $src; }
        }
        foreach ( $xp->query( '//*[@style]' ) ?: array() as $el ) {
            $style = (string) $el->getAttribute( 'style' );
            if ( preg_match( '/background-image\\s*:\\s*url\\([\\x22\\x27]?([^\\)\\x22\\x27]+)/i', $style, $m ) ) {
                $candidate_images['banner'][] = html_entity_decode( (string) $m[1], ENT_QUOTES | ENT_HTML5 );
            }
        }
    }
    $media = static function ( array $urls, string $label, int $min_width ) {
        foreach ( array_unique( $urls ) as $url ) {
            if ( str_starts_with( $url, '//' ) ) { $url = 'https:' . $url; }
            elseif ( str_starts_with( $url, '/' ) ) { $url = 'https://latitudes.es' . $url; }
            $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
            $path = (string) wp_parse_url( $url, PHP_URL_PATH );
            if ( ! in_array( $host, array( 'latitudes.es', 'www.latitudes.es' ), true ) ||
                ! preg_match( '/\\.(?:png|jpe?g|webp)$/i', $path ) ) { continue; }
            $attachment_id = media_sideload_image( $url, 0, $label, 'id' );
            if ( is_wp_error( $attachment_id ) ) { continue; }
            $meta = wp_get_attachment_metadata( (int) $attachment_id );
            if ( is_array( $meta ) && (int) ( $meta['width'] ?? 0 ) >= $min_width ) {
                update_post_meta( (int) $attachment_id, '_mdo_latitudes_asset', $label );
                return (int) $attachment_id;
            }
            wp_delete_attachment( (int) $attachment_id, true );
        }
        return 0;
    };
    if ( empty( $profile['gravatar'] ) ) {
        $logo = $media( $candidate_images['logo'], 'Latitudes - logotipo', 100 );
        if ( $logo ) { $profile['gravatar'] = (string) $logo; }
    }
    if ( empty( $profile['banner'] ) ) {
        $banner = $media( $candidate_images['banner'], 'Latitudes - imagen de cabecera', 900 );
        if ( $banner ) {
            $profile['banner_type'] = 'image';
            $profile['banner'] = (string) $banner;
            $profile['mobile_banner'] = (string) $banner;
            $profile['list_banner_type'] = 'image';
            $profile['list_banner'] = (string) $banner;
        }
    }
    update_user_meta( $vendor_id, 'wcfmmp_profile_settings', $profile );

    $id = MDO_Supplier_Repository::save( array(
        'code' => 'latitudes',
        'name' => 'Latitudes',
        'source_url' => 'https://latitudes.es/',
        'vendor_user_id' => $vendor_id,
        'connector' => 'latitudes',
        'commercial_rule' => 'percentage',
        'fixed_fee_scope' => 'order',
        'currency' => 'EUR',
        'sync_frequency' => 'daily',
        'notification_email' => (string) get_option( 'admin_email' ),
        'notes' => 'Prelanzamiento: vendedor deshabilitado. Importaciones pendientes de revisión de precios, imágenes, origen y cadena de frío. Comisión comercial sin fijar.',
        'active' => 1,
    ), $existing ? (int) $existing['id'] : 0 );
    if ( $id <= 0 ) { throw new RuntimeException( 'No se pudo guardar Latitudes en EMDO.' ); }
    $supplier = MDO_Supplier_Repository::find( $id );
    MDO_Nightly_Scheduler::ensure_nightly_dispatcher();
    $discovery = MDO_Connector_Latitudes::discover( $supplier );
    $count = count( $discovery['products'] );
    if ( $count < 60 || $count > 250 ) { throw new RuntimeException( 'Cantidad inesperada de referencias: ' . $count ); }
    $sample = MDO_Connector_Latitudes::scrape_product( (string) $discovery['products'][0] );
    if ( empty( $sample['title'] ) || ! is_numeric( $sample['price'] ?? null ) ||
        (float) $sample['price'] <= 0 || empty( $sample['images'] ) ) {
        throw new RuntimeException( 'La ficha de muestra no tiene precio o imágenes válidos.' );
    }
    echo 'LATITUDES_SETUP=' . wp_json_encode( array(
        'supplier_id' => $id,
        'vendor_id' => $vendor_id,
        'vendor_email' => (string) $vendor->user_email,
        'disabled' => get_user_meta( $vendor_id, '_disable_vendor', true ),
        'gravatar' => (string) ( $profile['gravatar'] ?? '' ),
        'banner' => (string) ( $profile['banner'] ?? '' ),
        'catalog_count' => $count,
        'catalog_pages' => count( $discovery['pages'] ),
        'sample_title' => $sample['title'],
        'sample_price' => $sample['price'],
        'sample_images' => count( $sample['images'] ),
        'sample_stock' => $sample['stock_status'] ?? '',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
    MDO_Scheduler::run_supplier( $id, 'manual' );
    echo "LATITUDES_SUPPLIER_ID={$id}" . PHP_EOL;
    exit;
}

if ( ! $existing ) { throw new RuntimeException( 'El registro Latitudes todavía no existe.' ); }
$vendor_id = (int) $existing['vendor_user_id'];
if ( ! $vendor_id || ! get_user_meta( $vendor_id, '_disable_vendor', true ) ) {
    throw new RuntimeException( 'Freno de seguridad: vendedor Latitudes no está deshabilitado.' );
}
$id = (int) $existing['id'];
if ( 'report' === $phase ) {
    $run = $wpdb->get_row( $wpdb->prepare( "SELECT id,status,products_found,products_new,errors_count,message,finished_at FROM {$runs} WHERE supplier_id=%d ORDER BY id DESC LIMIT 1", $id ), ARRAY_A );
    $counts = $wpdb->get_results( $wpdb->prepare( "SELECT status,COUNT(*) AS count FROM {$sources} WHERE supplier_id=%d GROUP BY status", $id ), ARRAY_A );
    echo 'LATITUDES_SCRAPE_REPORT=' . wp_json_encode( array( 'run' => $run, 'source_statuses' => $counts ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
    if ( ! $run || ! in_array( $run['status'], array( 'success', 'warning' ), true ) ||
        empty( $run['finished_at'] ) || (int) $run['products_found'] < 60 ||
        (int) $run['errors_count'] > 5 ) { exit( 31 ); }
    exit;
}
if ( 'smoke' === $phase ) {
    $ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
        "SELECT id FROM {$sources} WHERE supplier_id=%d AND status='pending' AND source_price>0 ORDER BY id LIMIT 2", $id
    ) ) );
    if ( count( $ids ) < 2 ) { throw new RuntimeException( 'Menos de dos fichas pendientes para probar.' ); }
    $out = array();
    foreach ( $ids as $source_id ) {
        $wc_id = MDO_Woo_Importer::import_source_product( $source_id );
        $product = wc_get_product( $wc_id );
        $out[] = array(
            'source_id' => $source_id,
            'product_id' => $wc_id,
            'status' => $product->get_status(),
            'catalog_visibility' => $product->get_catalog_visibility(),
            'price' => $product->get_price(),
            'has_image' => (bool) $product->get_image_id(),
            'description_length' => strlen( wp_strip_all_tags( $product->get_description() ) ),
        );
        if ( 'draft' !== $product->get_status() || 'hidden' !== $product->get_catalog_visibility() ) {
            throw new RuntimeException( 'Prueba fallida: producto publicado o visible.' );
        }
        if ( ! $product->get_image_id() || ! is_numeric( $product->get_price() ) ||
            strlen( wp_strip_all_tags( $product->get_description() ) ) < 30 ) {
            throw new RuntimeException( 'Producto de muestra incompleto: falta imagen, precio o descripción.' );
        }
    }
    echo 'LATITUDES_SMOKE=' . wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
    exit;
}
throw new RuntimeException( 'LATITUDES_PHASE no reconocida.' );
