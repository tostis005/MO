<?php
/**
 * Idempotent prelaunch provisioning for Chocolates Artesanos Isabel on production.
 * Run with: ISABEL_PHASE=setup|discover|import|report wp eval-file ... --path=...
 * Never publishes products or enables checkout while commercial shipping is unknown.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$phase = getenv( 'ISABEL_PHASE' ) ?: 'report';
$code = 'chocolates-artesanos-isabel';
$source = 'https://chocolatesartesanosisabel.com/tienda/';
$suppliers = MDO_Database::table( 'suppliers' );
$products = MDO_Database::table( 'source_products' );
$vendor_login = 'chocolates-artesanos-isabel';
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$suppliers} WHERE code = %s LIMIT 1", $code ), ARRAY_A );

$ensure_production = static function () {
    foreach ( array( 'home', 'siteurl' ) as $key ) {
        $host = strtolower( (string) wp_parse_url( (string) get_option( $key ), PHP_URL_HOST ) );
        if ( ! in_array( $host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
            throw new RuntimeException( 'La instancia no es producción: ' . $key );
        }
    }
};
$ensure_production();

$find_vendor = static function () use ( $vendor_login ) {
    $user = get_user_by( 'login', $vendor_login );
    return $user ?: false;
};

if ( 'setup' === $phase ) {
    if ( ! class_exists( 'MDO_Connector_Chocolates_Isabel' ) ) {
        throw new RuntimeException( 'El nuevo conector aún no está instalado.' );
    }
    $existing_vendor = $find_vendor();
    // Reruns after launch must NEVER disable an already active selling account.
    if ( $supplier && $existing_vendor && ! get_user_meta( (int) $existing_vendor->ID, '_disable_vendor', true ) ) {
        echo "ISABEL_ALREADY_ENABLED_NO_CHANGES\n";
        exit;
    }
    if ( $supplier && (int) $supplier['vendor_user_id'] && $existing_vendor &&
        (int) $supplier['vendor_user_id'] !== (int) $existing_vendor->ID ) {
        throw new RuntimeException( 'Colisión de vendedor: la cuenta asociada no coincide.' );
    }
    if ( $supplier ) {
        $published = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'product' AND post_status = 'publish'",
            (int) $supplier['vendor_user_id']
        ) );
        if ( $published ) {
            echo "ISABEL_ALREADY_LIVE_NO_CHANGES published={$published}\n";
            exit;
        }
    }
    $vendor = $existing_vendor;
    if ( ! $vendor ) {
        // Use the same internal mailbox naming convention as current EMDO vendors.
        $emails = $wpdb->get_col( "SELECT user_email FROM {$wpdb->users} WHERE user_email LIKE 'tostis005+%@%'" );
        $max = -1;
        $domains = array();
        foreach ( (array) $emails as $email ) {
            if ( preg_match( '/^tostis005\+([0-9]+)@([^@]+)$/i', (string) $email, $m ) ) {
                $max = max( $max, (int) $m[1] );
                $domain = strtolower( $m[2] );
                $domains[ $domain ] = ( $domains[ $domain ] ?? 0 ) + 1;
            }
        }
        if ( $max < 0 || ! $domains ) { throw new RuntimeException( 'No se ha podido validar el patrón de correo interno para los vendedores.' ); }
        arsort( $domains );
        $domain = (string) array_key_first( $domains );
        $email = '';
        for ( $n = $max + 1; $n < $max + 100; $n++ ) {
            $candidate = 'tostis005+' . $n . '@' . $domain;
            if ( ! email_exists( $candidate ) ) { $email = $candidate; break; }
        }
        if ( ! $email ) { throw new RuntimeException( 'Sin buzón interno disponible.' ); }
        $id = wp_insert_user( array(
            'user_login' => $vendor_login, 'user_nicename' => $vendor_login,
            'user_email' => $email, 'display_name' => 'Chocolates Artesanos Isabel',
            'first_name' => 'Chocolates Artesanos Isabel', 'role' => 'wcfm_vendor',
            'user_pass' => wp_generate_password( 32, true, true ),
        ) );
        if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
        $vendor = get_user_by( 'id', (int) $id );
    }
    if ( ! $vendor || $vendor->user_login !== $vendor_login ) {
        throw new RuntimeException( 'Usuario vendedor inesperado.' );
    }
    $vendor_id = (int) $vendor->ID;
    update_user_meta( $vendor_id, '_disable_vendor', 1 );
    $vendor->set_role( 'wcfm_vendor' );
    update_user_meta( $vendor_id, 'store_name', 'Chocolates Artesanos Isabel' );

    $bio = '<p>Chocolates Artesanos Isabel elabora chocolate ecológico y de Comercio Justo en su obrador, desde el haba de cacao hasta la tableta. Sus especialidades abarcan tabletas bean to bar, bombones, chocolatinas, cremas de cacao y chocolates para beber o cocinar, con recetas artesanas y una atención especial al origen de los ingredientes, los alérgenos y la conservación.</p>';
    $profile = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    $profile = is_array( $profile ) ? $profile : array();
    $profile['user_name'] = $vendor_login;
    $profile['user_email'] = (string) $vendor->user_email;
    $profile['store_name'] = 'Chocolates Artesanos Isabel';
    $profile['store_slug'] = $vendor_login;
    $profile['vendor_id'] = $vendor_id;
    $profile['shop_description'] = $bio;
    $profile['store_hide_email'] = 'yes';
    $profile['store_hide_phone'] = 'yes';
    $profile['store_hide_address'] = 'yes';
    update_user_meta( $vendor_id, '_store_description', $bio );

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $host = 'chocolatesartesanosisabel.com';
    $home = wp_safe_remote_get( 'https://' . $host . '/', array( 'timeout' => 25 ) );
    $candidate_logos = array();
    $candidate_banners = array();
    if ( ! is_wp_error( $home ) && 200 === (int) wp_remote_retrieve_response_code( $home ) ) {
        $html = (string) wp_remote_retrieve_body( $home );
        $dom = new DOMDocument();
        $old = libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors(); libxml_use_internal_errors( $old );
        $xp = new DOMXPath( $dom );
        foreach ( $xp->query( '//img' ) ?: array() as $img ) {
            $src = trim( (string) $img->getAttribute( 'data-src' ) ) ?: trim( (string) $img->getAttribute( 'src' ) );
            $hint = strtolower( implode( ' ', array(
                $img->getAttribute( 'alt' ), $img->getAttribute( 'class' ), $src
            ) ) );
            if ( preg_match( '/logo|logotipo|site-logo|custom-logo/', $hint ) ) { $candidate_logos[] = $src; }
            if ( preg_match( '/banner|slider|hero|cabecera/', $hint ) ) { $candidate_banners[] = $src; }
        }
        foreach ( $xp->query( '//*[@style]' ) ?: array() as $el ) {
            if ( preg_match( '~background-image\s*:\s*url\(["\x27]?([^\)"\x27]+)~i', $el->getAttribute( 'style' ), $m ) ) {
                $candidate_banners[] = $m[1];
            }
        }
    }

    $sideload = static function ( array $urls, string $label, int $width ) use ( $host ) {
        foreach ( array_unique( $urls ) as $url ) {
            $url = html_entity_decode( trim( (string) $url ), ENT_QUOTES | ENT_HTML5 );
            if ( str_starts_with( $url, '//' ) ) { $url = 'https:' . $url; }
            elseif ( str_starts_with( $url, '/' ) ) { $url = 'https://' . $host . $url; }
            $asset_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
            $path = (string) wp_parse_url( $url, PHP_URL_PATH );
            if ( ! in_array( $asset_host, array( $host, 'www.' . $host ), true ) ||
                ! preg_match( '/\.(?:png|jpe?g|webp)$/i', $path ) ) { continue; }
            $attachment_id = media_sideload_image( $url, 0, $label, 'id' );
            if ( is_wp_error( $attachment_id ) ) { continue; }
            $meta = wp_get_attachment_metadata( (int) $attachment_id );
            if ( is_array( $meta ) && (int) ( $meta['width'] ?? 0 ) >= $width ) {
                update_post_meta( (int) $attachment_id, '_mdo_isabel_brand_asset', $label );
                return (int) $attachment_id;
            }
            wp_delete_attachment( (int) $attachment_id, true );
        }
        return 0;
    };

    if ( empty( $profile['gravatar'] ) ) {
        $logo = $sideload( $candidate_logos, 'Chocolates Artesanos Isabel - logotipo oficial', 100 );
        if ( $logo ) { $profile['gravatar'] = (string) $logo; }
    }
    if ( empty( $profile['banner'] ) ) {
        $banner = $sideload( $candidate_banners, 'Chocolates Artesanos Isabel - imagen oficial', 1200 );
        if ( ! $banner && function_exists( 'imagecreatefromstring' ) ) {
            $discovery = MDO_Connector_Chocolates_Isabel::discover( array( 'source_url' => $source ) );
            $photos = array();
            foreach ( array_slice( $discovery['products'], 0, 12 ) as $product_url ) {
                try {
                    $data = MDO_Connector_Chocolates_Isabel::scrape_product( $product_url );
                } catch ( Throwable $ignore ) { continue; }
                foreach ( (array) ( $data['images'] ?? array() ) as $photo_url ) {
                    $img_host = strtolower( (string) wp_parse_url( $photo_url, PHP_URL_HOST ) );
                    if ( ! in_array( $img_host, array( $host, 'www.' . $host ), true ) ) { continue; }
                    $response = wp_safe_remote_get( $photo_url, array( 'timeout' => 20 ) );
                    $bytes = ! is_wp_error( $response ) ? (string) wp_remote_retrieve_body( $response ) : '';
                    $image = $bytes ? @imagecreatefromstring( $bytes ) : false;
                    if ( $image && imagesx( $image ) >= 300 && imagesy( $image ) >= 250 ) {
                        $photos[] = $image;
                        break;
                    }
                    if ( $image ) { imagedestroy( $image ); }
                }
                if ( count( $photos ) === 3 ) { break; }
            }
            if ( count( $photos ) === 3 ) {
                $canvas = imagecreatetruecolor( 1800, 400 );
                foreach ( $photos as $n => $image ) {
                    $w = imagesx( $image ); $h = imagesy( $image );
                    $crop_w = min( $w, (int) round( $h * 1.5 ) );
                    $crop_h = min( $h, (int) round( $w / 1.5 ) );
                    imagecopyresampled( $canvas, $image, $n * 600, 0,
                        (int) floor( ( $w - $crop_w ) / 2 ), (int) floor( ( $h - $crop_h ) / 2 ),
                        600, 400, $crop_w, $crop_h );
                }
                $upload = wp_upload_dir();
                if ( empty( $upload['error'] ) ) {
                    $path = trailingslashit( $upload['path'] ) . wp_unique_filename( $upload['path'], 'chocolates-isabel-banner.jpg' );
                    if ( imagejpeg( $canvas, $path, 88 ) ) {
                        $attachment = wp_insert_attachment( array(
                            'post_title' => 'Chocolates Artesanos Isabel - chocolates artesanos',
                            'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit',
                        ), $path );
                        if ( ! is_wp_error( $attachment ) && $attachment ) {
                            wp_update_attachment_metadata( $attachment, wp_generate_attachment_metadata( $attachment, $path ) );
                            update_post_meta( (int) $attachment, '_mdo_isabel_brand_asset', 'banner' );
                            $banner = (int) $attachment;
                        }
                    }
                }
                imagedestroy( $canvas );
            }
            foreach ( $photos as $image ) { imagedestroy( $image ); }
        }
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
        'code' => $code, 'name' => 'Chocolates Artesanos Isabel',
        'source_url' => $source, 'vendor_user_id' => $vendor_id,
        'connector' => $code, 'currency' => 'EUR',
        'commercial_rule' => 'percentage', 'fixed_fee_scope' => 'order',
        'sync_frequency' => 'daily', 'notification_email' => (string) get_option( 'admin_email' ),
        'notes' => 'PRELANZAMIENTO: vendedor deshabilitado y productos ocultos. Condiciones comerciales, portes por zona, estacionalidad por temperatura, productos personalizados y variantes pendientes de validación. No publicar automáticamente.',
        'active' => 1,
    ), $supplier ? (int) $supplier['id'] : 0 );
    if ( ! $id ) { throw new RuntimeException( 'Isabel: alta de proveedor fallida.' ); }
    echo 'ISABEL_SETUP=' . wp_json_encode( array(
        'supplier_id' => $id, 'vendor_id' => $vendor_id,
        'disabled' => get_user_meta( $vendor_id, '_disable_vendor', true ),
        'logo_id' => (int) ( $profile['gravatar'] ?? 0 ),
        'banner_id' => (int) ( $profile['banner'] ?? 0 ),
        'source_url' => $source,
    ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
    exit;
}

if ( ! $supplier || ! (int) $supplier['vendor_user_id'] ) {
    throw new RuntimeException( 'Isabel: el registro proveedor/vendedor todavía no existe.' );
}
$vendor_id = (int) $supplier['vendor_user_id'];
if ( ! get_user_meta( $vendor_id, '_disable_vendor', true ) ) {
    echo "ISABEL_ALREADY_LIVE_PHASE_SKIPPED={$phase}\n";
    exit;
}
$id = (int) $supplier['id'];
if ( 'discover' === $phase ) {
    $catalog = MDO_Connector_Chocolates_Isabel::discover( $supplier );
    echo 'ISABEL_DISCOVERED=' . count( $catalog['products'] ) . "\n";
    MDO_Scheduler::run_supplier( $id, 'manual' );
    exit;
}
if ( 'import' === $phase ) {
    $rows = $wpdb->get_col( $wpdb->prepare(
        "SELECT id FROM {$products} WHERE supplier_id = %d AND status = 'pending' AND (last_error IS NULL OR last_error = '') ORDER BY id LIMIT 8", $id
    ) );
    $success = 0; $failed = 0;
    foreach ( $rows as $row_id ) {
        try {
            $product_id = MDO_Woo_Importer::import_source_product( (int) $row_id );
            $product = wc_get_product( $product_id );
            if ( ! $product || 'draft' !== $product->get_status() ||
                'hidden' !== $product->get_catalog_visibility() ||
                (int) get_post_field( 'post_author', $product_id ) !== $vendor_id ) {
                throw new RuntimeException( 'La importación no mantuvo las restricciones de prelanzamiento.' );
            }
            // Compact chocolate taxonomy, preserving all existing categories.
            $root = term_exists( 'Chocolates artesanos', 'product_cat' );
            if ( ! $root ) { $root = wp_insert_term( 'Chocolates artesanos', 'product_cat' ); }
            $root_id = is_wp_error( $root ) ? 0 : (int) ( is_array( $root ) ? $root['term_id'] : $root );
            if ( $root_id ) {
                $title = mb_strtolower( $product->get_name(), 'UTF-8' );
                $group = '';
                if ( preg_match( '/tableta|bean to bar/', $title ) ) { $group = 'Tabletas de chocolate'; }
                elseif ( preg_match( '/bomb[oó]n|chocolatina|carr[eé]|cracker|torino/', $title ) ) { $group = 'Bombones y chocolatinas'; }
                elseif ( preg_match( '/crema|untable|untar/', $title ) ) { $group = 'Cremas de cacao'; }
                elseif ( preg_match( '/cacao|beber|cocinar|nibs|cascarilla|manteca/', $title ) ) { $group = 'Cacao para beber y cocinar'; }
                $terms = array( $root_id );
                if ( $group ) {
                    $term = term_exists( $group, 'product_cat', $root_id );
                    if ( ! $term ) { $term = wp_insert_term( $group, 'product_cat', array( 'parent' => $root_id ) ); }
                    if ( ! is_wp_error( $term ) && $term ) { $terms[] = (int) ( is_array( $term ) ? $term['term_id'] : $term ); }
                }
                wp_set_object_terms( $product_id, $terms, 'product_cat', true );
            }
            $success++;
        } catch ( Throwable $error ) {
            MDO_Woo_Importer::mark_import_error( (int) $row_id, $error->getMessage() );
            $failed++;
            echo 'ISABEL_IMPORT_BLOCKED id=' . (int) $row_id . ' reason=' . substr( $error->getMessage(), 0, 180 ) . "\n";
        }
    }
    $remaining = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$products} WHERE supplier_id = %d AND status = 'pending' AND (last_error IS NULL OR last_error = '')", $id
    ) );
    echo 'ISABEL_IMPORT=' . wp_json_encode( array( 'success' => $success, 'blocked' => $failed, 'remaining' => $remaining ) ) . "\n";
    exit;
}
if ( 'report' === $phase ) {
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT status, COUNT(*) n FROM {$products} WHERE supplier_id = %d GROUP BY status", $id
    ), ARRAY_A );
    $run = $wpdb->get_row( $wpdb->prepare(
        "SELECT id,status,products_found,products_new,errors_count,message,finished_at FROM " . MDO_Database::table('sync_runs') . " WHERE supplier_id = %d ORDER BY id DESC LIMIT 1", $id
    ), ARRAY_A );
    $profile = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    $visible = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author=%d AND post_type='product' AND post_status='publish'", $vendor_id
    ) );
    $bad = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$products} sp LEFT JOIN {$wpdb->posts} p ON p.ID=sp.wc_product_id
          WHERE sp.supplier_id=%d AND sp.status='active' AND
          (sp.wc_product_id IS NULL OR p.post_author != %d OR p.post_status != 'draft')",
        $id, $vendor_id
    ) );
    echo 'ISABEL_REPORT=' . wp_json_encode( array(
        'supplier_id' => $id, 'vendor_id' => $vendor_id,
        'vendor_disabled' => (bool) get_user_meta( $vendor_id, '_disable_vendor', true ),
        'logo_id' => (int) ( $profile['gravatar'] ?? 0 ), 'banner_id' => (int) ( $profile['banner'] ?? 0 ),
        'status_counts' => $rows, 'last_sync' => $run,
        'published_products' => $visible, 'import_inconsistencies' => $bad,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    if ( $visible || $bad ) { exit( 42 ); }
    exit;
}
throw new RuntimeException( 'ISABEL_PHASE no válido.' );
