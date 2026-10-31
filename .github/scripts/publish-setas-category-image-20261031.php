<?php
/**
 * Publish the approved Setas y hongos category image only.
 * Modes: audit, apply, verify. Idempotent and backed up.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
if (!taxonomy_exists('product_cat')) { throw new RuntimeException('product_cat missing'); }
$host = strtolower((string) wp_parse_url((string)get_option('siteurl'), PHP_URL_HOST));
if (!in_array($host, ['elmercadodeorigen.com','www.elmercadodeorigen.com'], true)) {
    throw new RuntimeException('Not the expected production host');
}
$mode = (string)(getenv('EMDO_SETAS_MODE') ?: 'audit');
if (!in_array($mode, ['audit','apply','verify'], true)) { throw new RuntimeException('Bad mode'); }
$source = (string)getenv('EMDO_SETAS_ASSET');
$slug = 'setas-y-hongos';
$term = get_term_by('slug', $slug, 'product_cat');
if (!$term instanceof WP_Term) { throw new RuntimeException('Category not found: ' . $slug); }
$old = (int)get_term_meta($term->term_id, 'thumbnail_id', true);
$filename = 'categoria-setas-y-hongos-20261031.webp';
$expected_sha = (string)getenv('EMDO_SETAS_SHA256');
if (strlen($expected_sha) !== 64 || !ctype_xdigit($expected_sha)) {
    throw new RuntimeException('Missing asset checksum');
}
if ($mode !== 'verify') {
    if (!is_readable($source) || !is_file($source)) { throw new RuntimeException('Image not accessible'); }
    $real_sha = hash_file('sha256', $source);
    if (!hash_equals($expected_sha, $real_sha)) { throw new RuntimeException('Asset checksum mismatch'); }
    $info = @getimagesize($source);
    if (!$info || $info['mime'] !== 'image/webp' || $info[0] < 250 || $info[1] < 375) {
        throw new RuntimeException('Invalid image dimensions/mime');
    }
}
if ($mode === 'apply') {
    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/image.php';
    $prior_sha = $old ? (string)get_post_meta($old, '_emdo_setas_asset_sha256', true) : '';
    if (!hash_equals($expected_sha, $prior_sha)) {
        $base = rtrim((string)getenv('HOME'), '/').'/.elmercado-rollbacks/category-setas-image';
        if (!is_dir($base) && !wp_mkdir_p($base)) { throw new RuntimeException('Cannot save rollback'); }
        $backup = $base.'/before-'.gmdate('Ymd-His').'-'.getmypid().'.json';
        $written = file_put_contents($backup, wp_json_encode([
            'siteurl' => site_url(), 'category_slug' => $slug, 'term_id' => (int)$term->term_id,
            'previous_attachment_id' => $old, 'previous_image_url' => $old ? wp_get_attachment_url($old) : null,
            'date_utc' => gmdate('c')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (!$written) { throw new RuntimeException('Could not write backup'); }
        $upload = wp_upload_bits($filename, null, file_get_contents($source));
        if (!empty($upload['error']) || empty($upload['file'])) {
            throw new RuntimeException('Upload failed: '.($upload['error'] ?? 'unknown'));
        }
        $attachment = wp_insert_attachment([
            'guid' => $upload['url'], 'post_mime_type' => 'image/webp',
            'post_title' => 'Setas y hongos — fotografía de categoría',
            'post_status' => 'inherit'
        ], $upload['file']);
        if (!$attachment || is_wp_error($attachment)) { throw new RuntimeException('Insert attachment failed'); }
        $meta = wp_generate_attachment_metadata((int)$attachment, $upload['file']);
        if (is_array($meta)) { wp_update_attachment_metadata((int)$attachment, $meta); }
        update_post_meta((int)$attachment, '_wp_attachment_image_alt', 'Setas y hongos variados sobre pizarra negra');
        update_post_meta((int)$attachment, '_emdo_setas_asset_sha256', $expected_sha);
        update_term_meta((int)$term->term_id, 'thumbnail_id', (int)$attachment);
        clean_term_cache((int)$term->term_id, 'product_cat');
        wp_cache_flush();
        echo 'EMDO_SETAS_BACKUP='.$backup.PHP_EOL;
    }
}
$id = (int)get_term_meta((int)$term->term_id, 'thumbnail_id', true);
$url = $id ? (string)wp_get_attachment_image_url($id, 'full') : '';
$sha = $id ? (string)get_post_meta($id, '_emdo_setas_asset_sha256', true) : '';
if ($mode !== 'audit' && (!$id || !$url || !hash_equals($expected_sha, $sha))) {
    throw new RuntimeException('Category image verification failed');
}
echo 'EMDO_SETAS_IMAGE_RESULT='.wp_json_encode([
    'mode' => $mode, 'category' => $slug, 'term_id' => (int)$term->term_id,
    'thumbnail_id' => $id, 'image_url' => $url, 'asset_sha256' => $sha,
    'expected_sha256' => $expected_sha
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
