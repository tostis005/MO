<?php
/**
 * One-shot WP-CLI tool: synchronize the actual WCFM vendor store AND store-list
 * banners to the same verified 10 producer assets used on /productores/.
 * Modes: audit, apply, verify, rollback. Preserves logos and all other settings.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    throw new RuntimeException( 'WP-CLI only' );
}
$ids = [
    '1957' => 3,
    'hidalgo-de-la-jara' => 6,
    'tolecarnes' => 4507,
    'la-huerta-de-ana-mary' => 4514,
    'montjam' => 4723,
    'selectos-de-castilla' => 4917,
    'latitudes' => 4949,
    'chocolates-artesanos-isabel' => 4964,
    'ikore-kofi' => 4970,
    'tropitop' => 4971,
];
$mode = getenv( 'EMDO_BANNER_MODE' ) ?: 'audit';
$backup = getenv( 'EMDO_BANNER_META_BACKUP' ) ?: '';
$base = get_stylesheet_directory() . '/assets/images/producer-banners/';
if ( $mode === 'rollback' ) {
    if ( ! $backup || ! is_readable( $backup ) ) {
        throw new RuntimeException( 'No saved vendor-settings backup found' );
    }
    $restore = unserialize( file_get_contents( $backup ), ['allowed_classes' => false] );
    if ( ! is_array( $restore ) || count( $restore ) !== count( $ids ) ) {
        throw new RuntimeException( 'Invalid or incomplete vendor-settings backup' );
    }
    foreach ( $ids as $slug => $id ) {
        if ( ! isset( $restore[$slug]['id'], $restore[$slug]['settings'] )
          || (int) $restore[$slug]['id'] !== $id
          || ! is_array( $restore[$slug]['settings'] ) ) {
            throw new RuntimeException( 'Invalid backup entry for ' . $slug );
        }
    }
    foreach ( $ids as $slug => $id ) {
        update_user_meta( $id, 'wcfmmp_profile_settings', $restore[$slug]['settings'] );
        echo 'WCFM_BANNER_ROLLBACK_OK ' . $slug . PHP_EOL;
    }
    exit;
}
if ( $mode === 'verify' ) {
    foreach ( $ids as $slug => $id ) {
        $settings = get_user_meta( $id, 'wcfmmp_profile_settings', true );
        if ( ! is_array( $settings ) ) {
            throw new RuntimeException( 'Missing settings: ' . $slug );
        }
        $banner = (int) ( $settings['banner'] ?? 0 );
        $list = (int) ( $settings['list_banner'] ?? 0 );
        $mobile = (int) ( $settings['mobile_banner'] ?? 0 );
        $url = $banner ? wp_get_attachment_url( $banner ) : '';
        $filepath = $banner ? get_attached_file( $banner ) : '';
        $mobile_path = $mobile ? get_attached_file( $mobile ) : '';
        $mobile_url = $mobile ? wp_get_attachment_url( $mobile ) : '';
        $mobile_size = $mobile_path && is_readable( $mobile_path ) ? @getimagesize( $mobile_path ) : false;
        if ( ! $banner || ! $mobile || $list !== $mobile ||
             ( $settings['banner_type'] ?? '' ) !== 'single_img' ||
             ( $settings['list_banner_type'] ?? '' ) !== 'single_img' ||
             ! $url || ! str_contains( $url, 'emdo-producer-' . $slug . '-banner-20261020.webp' ) ||
             ! $filepath || ! is_readable( $filepath ) ||
             hash_file( 'sha256', $filepath ) !== hash_file( 'sha256', $base . $slug . '.webp' ) ||
             ! $mobile_url || ! str_contains( $mobile_url, 'emdo-producer-' . $slug . '-banner-mobile-960-20261021' ) ||
             ! $mobile_size || $mobile_size[0] !== 960 || $mobile_size[1] !== 369 ||
             ( $mobile_size['mime'] ?? '' ) !== 'image/webp' ||
             get_post_meta( $mobile, '_emdo_banner_variant_source_sha256', true ) !== hash_file( 'sha256', $base . $slug . '.webp' ) ) {
            throw new RuntimeException( 'WCFM desktop/mobile/list banner mismatch: ' . $slug );
        }
        echo 'WCFM_THREE_BANNERS_VERIFIED ' . $slug . ' desktop=' . $banner . ' mobile/list=' . $mobile . ' mobile_bytes=' . filesize( $mobile_path ) . PHP_EOL;
    }
    echo 'ALL_TEN_WCFM_NATIVE_BANNERS_VERIFIED' . PHP_EOL;
    exit;
}
if ( ! in_array( $mode, ['audit', 'apply'], true ) ) {
    throw new RuntimeException( 'Unknown mode' );
}
$state = [];
foreach ( $ids as $slug => $id ) {
    $user = get_user_by( 'id', $id );
    if ( ! $user || $user->user_nicename !== (string) $slug ) {
        throw new RuntimeException( 'Vendor slug/ID mismatch: ' . $slug );
    }
    $settings = get_user_meta( $id, 'wcfmmp_profile_settings', true );
    if ( ! is_array( $settings ) || empty( $settings['store_name'] ) ) {
        throw new RuntimeException( 'Missing WCFM store settings: ' . $slug );
    }
    $path = $base . $slug . '.webp';
    if ( ! is_readable( $path ) ) {
        throw new RuntimeException( 'Banner source is not available: ' . $slug );
    }
    $bytes = file_get_contents( $path );
    $size = @getimagesizefromstring( $bytes );
    if ( ! $size || $size[0] !== 2022 || $size[1] !== 778 || ($size['mime'] ?? '') !== 'image/webp' ) {
        throw new RuntimeException( 'Unexpected banner image dimensions/type: ' . $slug );
    }
    $state[$slug] = [ 'id' => $id, 'settings' => $settings,
      'source_path' => $path, 'hash' => hash( 'sha256', $bytes ) ];
    echo 'WCFM_BANNER_SOURCE_OK ' . $slug . ' ' . $state[$slug]['hash'] . PHP_EOL;
}
if ( $mode === 'audit' ) {
    foreach ( $state as $slug => $v ) {
        echo 'WCFM_OLD_BANNER_META ' . $slug . ' banner=' . ( $v['settings']['banner'] ?? '' ) .
            ' mobile_banner=' . ( $v['settings']['mobile_banner'] ?? '' ) .
            ' list_banner=' . ( $v['settings']['list_banner'] ?? '' ) .
            ' banner_type=' . ( $v['settings']['banner_type'] ?? '' ) .
            ' list_banner_type=' . ( $v['settings']['list_banner_type'] ?? '' ) . PHP_EOL;
    }
    exit;
}
if ( ! $backup || file_exists( $backup ) ) {
    throw new RuntimeException( 'Backup destination missing or already exists' );
}
$dir = dirname( $backup );
if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
    throw new RuntimeException( 'Backup folder not writable' );
}
$before = [];
foreach ( $state as $slug => $v ) {
    $before[$slug] = [ 'id' => $v['id'], 'settings' => $v['settings'] ];
}
if ( false === file_put_contents( $backup, serialize( $before ), LOCK_EX ) ) {
    throw new RuntimeException( 'Failed to create settings backup' );
}
chmod( $backup, 0600 );
echo 'WCFM_BANNER_META_BACKED_UP' . PHP_EOL;
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
foreach ( $state as $slug => $v ) {
    $match = get_posts( [
        'post_type' => 'attachment', 'post_status' => 'inherit',
        'fields' => 'ids', 'posts_per_page' => 1,
        'meta_query' => [
            [ 'key' => '_emdo_approved_producer_banner', 'value' => $slug ],
            [ 'key' => '_emdo_approved_banner_sha256', 'value' => $v['hash'] ],
        ],
    ] );
    $attachment_id = $match ? (int) $match[0] : 0;
    if ( ! $attachment_id ) {
        $content = file_get_contents( $v['source_path'] );
        $written = wp_upload_bits( 'emdo-producer-' . $slug . '-banner-20261020.webp', null, $content );
        if ( ! empty( $written['error'] ) || ! is_file( $written['file'] ?? '' ) ) {
            throw new RuntimeException( 'Media upload failed for ' . $slug );
        }
        $attachment_id = wp_insert_attachment( [
            'post_title' => 'Banner productor ' . $v['settings']['store_name'],
            'post_mime_type' => 'image/webp',
            'post_status' => 'inherit',
        ], $written['file'], 0, true );
        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            throw new RuntimeException( 'Could not register image in Media Library: ' . $slug );
        }
        $metadata = wp_generate_attachment_metadata( $attachment_id, $written['file'] );
        wp_update_attachment_metadata( $attachment_id, $metadata );
        update_post_meta( $attachment_id, '_emdo_approved_producer_banner', $slug );
        update_post_meta( $attachment_id, '_emdo_approved_banner_sha256', $v['hash'] );
    }
    if ( ! wp_get_attachment_url( $attachment_id ) ) {
        throw new RuntimeException( 'Attachment URL unavailable for ' . $slug );
    }
    // Keep the original approved 2022x778 WebP as the desktop header.
    // WCFM stores mobile/list references separately; use a smaller, exact same
    // photograph (960x369) for those two surfaces to reduce transfer bytes.
    $existing = get_posts( [
        'post_type' => 'attachment', 'post_status' => 'inherit',
        'fields' => 'ids', 'posts_per_page' => 1,
        'meta_query' => [
            [ 'key' => '_emdo_banner_variant_slug', 'value' => $slug ],
            [ 'key' => '_emdo_banner_variant_source_sha256', 'value' => $v['hash'] ],
            [ 'key' => '_emdo_banner_variant_width', 'value' => '960' ],
        ],
    ] );
    $mobile_id = $existing ? (int) $existing[0] : 0;
    if ( $mobile_id ) {
        $mobile_file = get_attached_file( $mobile_id );
        $mobile_size = $mobile_file && is_readable( $mobile_file ) ? @getimagesize( $mobile_file ) : false;
        if ( ! $mobile_size || $mobile_size[0] !== 960 || $mobile_size[1] !== 369 ||
            ( $mobile_size['mime'] ?? '' ) !== 'image/webp' ) {
            $mobile_id = 0;
        }
    }
    if ( ! $mobile_id ) {
        $editor = wp_get_image_editor( $v['source_path'] );
        if ( is_wp_error( $editor ) ) {
            throw new RuntimeException( 'Cannot optimize mobile banner: ' . $slug . ': ' . $editor->get_error_message() );
        }
        $editor->set_quality( 78 );
        $resized = $editor->resize( 960, null, false );
        if ( is_wp_error( $resized ) ) {
            throw new RuntimeException( 'Cannot resize mobile banner: ' . $slug );
        }
        $tmp = sys_get_temp_dir() . '/emdo-banner-' . wp_generate_uuid4() . '.webp';
        $saved = $editor->save( $tmp, 'image/webp' );
        if ( is_wp_error( $saved ) || ! is_readable( $tmp ) ) {
            throw new RuntimeException( 'Cannot encode mobile banner: ' . $slug );
        }
        $pixel_size = @getimagesize( $tmp );
        $mobile_bytes = file_get_contents( $tmp );
        @unlink( $tmp );
        if ( ! $pixel_size || $pixel_size[0] !== 960 || $pixel_size[1] !== 369 ||
             ( $pixel_size['mime'] ?? '' ) !== 'image/webp' ||
             strlen( $mobile_bytes ) >= filesize( $v['source_path'] ) ) {
            throw new RuntimeException( 'Mobile optimization did not reduce original bytes: ' . $slug );
        }
        $mobile_written = wp_upload_bits( 'emdo-producer-' . $slug . '-banner-mobile-960-20261021.webp', null, $mobile_bytes );
        if ( ! empty( $mobile_written['error'] ) || ! is_file( $mobile_written['file'] ?? '' ) ) {
            throw new RuntimeException( 'Mobile media upload failed: ' . $slug );
        }
        $mobile_id = wp_insert_attachment( [
            'post_title' => 'Banner móvil optimizado ' . $v['settings']['store_name'],
            'post_mime_type' => 'image/webp',
            'post_status' => 'inherit',
        ], $mobile_written['file'], 0, true );
        if ( is_wp_error( $mobile_id ) || ! $mobile_id ) {
            throw new RuntimeException( 'Cannot register mobile banner: ' . $slug );
        }
        $mobile_id = (int) $mobile_id;
        wp_update_attachment_metadata( $mobile_id, wp_generate_attachment_metadata( $mobile_id, $mobile_written['file'] ) );
        update_post_meta( $mobile_id, '_emdo_banner_variant_slug', $slug );
        update_post_meta( $mobile_id, '_emdo_banner_variant_source_sha256', $v['hash'] );
        update_post_meta( $mobile_id, '_emdo_banner_variant_width', '960' );
    }
    $settings = $v['settings'];
    $settings['banner_type'] = 'single_img';
    $settings['list_banner_type'] = 'single_img';
    $settings['banner'] = (string) $attachment_id;
    $settings['mobile_banner'] = (string) $mobile_id;
    $settings['list_banner'] = (string) $mobile_id;
    update_user_meta( $v['id'], 'wcfmmp_profile_settings', $settings );
    $test = get_user_meta( $v['id'], 'wcfmmp_profile_settings', true );
    if ( ! is_array( $test ) ||
       (string) ( $test['banner'] ?? '' ) !== (string) $attachment_id ||
       (string) ( $test['mobile_banner'] ?? '' ) !== (string) $mobile_id ||
       (string) ( $test['list_banner'] ?? '' ) !== (string) $mobile_id ||
       (string) ( $test['banner_type'] ?? '' ) !== 'single_img' ||
       (string) ( $test['list_banner_type'] ?? '' ) !== 'single_img' ) {
        throw new RuntimeException( 'Banner settings verification failed: ' . $slug );
    }
    echo 'WCFM_DESKTOP_MOBILE_LIST_UPDATED ' . $slug . ' desktop=' . $attachment_id .
         ' mobile/list=' . $mobile_id . ' desktop_url=' . wp_get_attachment_url( $attachment_id ) .
         ' mobile_url=' . wp_get_attachment_url( $mobile_id ) . PHP_EOL;
}
echo 'ALL_TEN_WCFM_NATIVE_BANNERS_UPDATED' . PHP_EOL;
