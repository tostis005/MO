<?php
/**
 * Install 3 user-approved, latest Ikore Kofi coffee-pack covers, preserving
 * product IDs, SKUs, gallery photos, descriptions, prices, categories, and variations.
 *
 * Run with:
 * MDO_IKORE_COVER_MODE=preflight|apply|verify
 * MDO_IKORE_COVER_DIR=/tmp/...
 * wp eval-file .github/scripts/install-ikore-final-covers-20261028.php
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
    fwrite(STDERR, "WOOCOMMERCE_WPCLI_REQUIRED\n"); exit(2);
}
$host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($host, array('www.elmercadodeorigen.com','elmercadodeorigen.com'), true)) {
    fwrite(STDERR, "NOT_PRODUCTION\n"); exit(2);
}
$mode = (string) (getenv('MDO_IKORE_COVER_MODE') ?: 'preflight');
if (!in_array($mode, array('preflight','apply','verify'), true)) exit(2);
$dir = rtrim((string) getenv('MDO_IKORE_COVER_DIR'), '/');
if ($dir === '' || !is_dir($dir)) { fwrite(STDERR, "COVERS_DIRECTORY_MISSING\n"); exit(3); }
$packs = array(
    21904 => array('sku'=>'IKORE-MENTTA-PACK-COSTA-RICA',
        'slug'=>'esencia-costa-rica',
        'file'=>'esencia-costa-rica.jpg',
        'label'=>'Esencia de Costa Rica — tres cafés de especialidad'),
    21906 => array('sku'=>'IKORE-MENTTA-PACK-4-ORIGENES',
        'slug'=>'ruta-cuatro-origenes',
        'file'=>'ruta-cuatro-origenes.jpg',
        'label'=>'Ruta de los 4 Orígenes — cafés de especialidad'),
    21908 => array('sku'=>'IKORE-MENTTA-PACK-6-CAFES',
        'slug'=>'coleccion-completa',
        'file'=>'coleccion-completa.jpg',
        'label'=>'Colección completa — seis cafés de especialidad Ikore Kofi'),
);
$root = get_term_by('slug','mentta','product_cat');
$child = get_term_by('slug','mentta-cafes','product_cat');
if (!$root || !$child || (int)$child->parent !== (int)$root->term_id) {
    fwrite(STDERR, "MENTTA_CATEGORY_GUARD_FAILED\n"); exit(3);
}
$validated = array();
foreach ($packs as $id => $c) {
    $product = wc_get_product($id);
    if (!$product || !$product->is_type('variable') || $product->get_status() !== 'publish'
        || $product->get_sku() !== $c['sku']
        || (int)get_post_field('post_author', $id) !== 4970
        || (int)wc_get_product_id_by_sku($c['sku']) !== (int)$id
        || !in_array((int)$root->term_id, array_map('intval',$product->get_category_ids()), true)
        || !in_array((int)$child->term_id, array_map('intval',$product->get_category_ids()), true)
        || count($product->get_children()) !== 7) {
        fwrite(STDERR, "UNEXPECTED_IKORE_PACK_STATE {$id}\n"); exit(4);
    }
    $file = $dir . '/' . $c['file'];
    $info = is_readable($file) ? @getimagesize($file) : false;
    if (!$info || $info[0] !== 1254 || $info[1] !== 1254 || $info['mime'] !== 'image/jpeg'
        || filesize($file) < 100000) {
        fwrite(STDERR, "INVALID_COVER_FILE {$id}\n"); exit(5);
    }
    $validated[$id] = array(
        'product'=>$product,
        'path'=>$file,
        'file_sha256'=>hash_file('sha256',$file),
        'old_image_id'=>(int)$product->get_image_id(),
        'gallery_ids'=>$product->get_gallery_image_ids(),
        'categories'=>$product->get_category_ids(),
        'visibility'=>$product->get_catalog_visibility(),
        'status'=>$product->get_status(),
        'variations'=>$product->get_children(),
        'sku'=>$product->get_sku(),
    );
    echo 'IKORE_COVER_CHECK '.wp_json_encode(array(
        'mode'=>$mode, 'id'=>$id, 'old_image'=>$product->get_image_id(),
        'input_file'=>$c['file'], 'sha256'=>$validated[$id]['file_sha256']
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
if ($mode === 'preflight') { echo "IKORE_COVERS_PREFLIGHT_OK\n"; exit(0); }
require_once ABSPATH.'wp-admin/includes/image.php';
require_once ABSPATH.'wp-admin/includes/file.php';
$uploads = wp_upload_dir();
if (!empty($uploads['error'])) { fwrite(STDERR, "UPLOADS_ERROR\n"); exit(6); }
$folder = trailingslashit($uploads['basedir']).'mentta-ikore-packs';
if (!is_dir($folder) && !wp_mkdir_p($folder)) { fwrite(STDERR, "UPLOADS_DIR_ERROR\n"); exit(6); }

if ($mode === 'verify') {
    foreach ($validated as $id=>$v) {
        $p = $v['product'];
        $attachment_id = (int)$p->get_meta('_emdo_ikore_final_20261028_attachment', true);
        $attachment = $attachment_id ? get_post($attachment_id) : null;
        if (!$attachment || $attachment->post_type !== 'attachment'
            || $attachment->post_mime_type !== 'image/jpeg'
            || (int)$p->get_image_id() !== $attachment_id
            || !is_readable((string)get_attached_file($attachment_id))) {
            fwrite(STDERR,"IKORE_COVER_NOT_ATTACHED {$id}\n"); exit(7);
        }
        $actual_hash = hash_file('sha256',get_attached_file($attachment_id));
        if ($actual_hash !== $v['file_sha256']
            || (int)get_post_meta($attachment_id,'_emdo_ikore_cover_parent',true)!==(int)$id
            || count($p->get_children()) !== 7) {
            fwrite(STDERR,"IKORE_COVER_HASH_OR_VARIATION_ERROR {$id}\n"); exit(7);
        }
        echo 'IKORE_COVER_VERIFIED '.wp_json_encode(array(
            'id'=>$id, 'attachment'=>$attachment_id,
            'url'=>wp_get_attachment_url($attachment_id),
            'sha256'=>$actual_hash
        ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    }
    echo "IKORE_THREE_FINAL_COVERS_VERIFIED\n"; exit(0);
}
$oldImages = array(); $createdAttachments = array();
try {
    foreach ($validated as $id => $v) {
        $c = $packs[$id];
        $filename = "ikore-{$c['slug']}-portada-final-20261028.jpg";
        $target = "{$folder}/{$filename}";
        // Never overwrite or alter a previously published attachment's image.
        if (file_exists($target) && hash_file('sha256',$target) !== $v['file_sha256']) {
            $filename = 'ikore-'.$c['slug'].'-portada-final-'.substr($v['file_sha256'],0,12).'.jpg';
            $target = "{$folder}/{$filename}";
        }
        if (!file_exists($target) && !copy($v['path'],$target)) {
            throw new RuntimeException("COPY_FAILED {$id}");
        }
        if (hash_file('sha256',$target) !== $v['file_sha256']) {
            throw new RuntimeException("FILE_CORRUPTED {$id}");
        }
        $existing = (int)$v['product']->get_meta('_emdo_ikore_final_20261028_attachment',true);
        if ($existing && get_post($existing)
            && hash_file('sha256',(string)get_attached_file($existing)) === $v['file_sha256']) {
            $attachment_id=$existing;
        } else {
            $attachment_id = wp_insert_attachment(array(
                'post_mime_type'=>'image/jpeg',
                'post_title'=>$c['label'],
                'post_status'=>'inherit',
                'post_content'=>'Portada final del pack de café.'
            ),$target,$id,true);
            if (is_wp_error($attachment_id)) {
                throw new RuntimeException("ATTACHMENT_CREATE_FAILED {$id}");
            }
            $createdAttachments[]=$attachment_id;
            wp_update_attachment_metadata($attachment_id,wp_generate_attachment_metadata($attachment_id,$target));
            update_post_meta($attachment_id,'_emdo_ikore_cover_parent',(int)$id);
            update_post_meta($attachment_id,'_wp_attachment_image_alt',$c['label']);
        }
        $oldImages[$id]=$v['old_image_id'];
        $p=wc_get_product($id);
        if (!$p || $p->get_sku()!==$v['sku']) throw new RuntimeException("PACK_CHANGED {$id}");
        if (!$p->get_meta('_emdo_ikore_featured_before_final_20261028',true)) {
            $p->update_meta_data('_emdo_ikore_featured_before_final_20261028',$v['old_image_id']);
        }
        $p->set_image_id((int)$attachment_id);
        $p->update_meta_data('_emdo_ikore_final_20261028_attachment',(int)$attachment_id);
        $p->update_meta_data('_emdo_ikore_final_20261028_sha256',$v['file_sha256']);
        $p->save();
        $after=wc_get_product($id);
        if (!$after || (int)$after->get_image_id() !== (int)$attachment_id
            || $after->get_gallery_image_ids() !== $v['gallery_ids']
            || $after->get_category_ids() !== $v['categories']
            || $after->get_catalog_visibility() !== $v['visibility']
            || $after->get_status() !== $v['status']
            || $after->get_children() !== $v['variations']
            || $after->get_sku() !== $v['sku']) {
            throw new RuntimeException("POST_UPDATE_INVARIANT_FAILED {$id}");
        }
        clean_post_cache($id);
        wc_delete_product_transients($id);
        if (function_exists('rocket_clean_post')) rocket_clean_post($id);
        if (function_exists('mdo_product_early_cache_purge_product_20261002')) {
            mdo_product_early_cache_purge_product_20261002($id);
        }
        echo 'IKORE_FINAL_COVER_UPDATED '.wp_json_encode(array(
            'id'=>$id,'old_attachment'=>$v['old_image_id'],'new_attachment'=>$attachment_id,
            'url'=>wp_get_attachment_url($attachment_id)
        ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    }
} catch (Throwable $e) {
    foreach ($oldImages as $id=>$imageId) {
        $p=wc_get_product($id);
        if ($p) { $p->set_image_id($imageId);$p->save();clean_post_cache($id);wc_delete_product_transients($id); }
    }
    fwrite(STDERR, "IKORE_COVER_INSTALL_ABORTED_AND_IMAGE_IDS_RESTORED: ".$e->getMessage()."\n");
    exit(9);
}
echo "IKORE_THREE_FINAL_COVERS_APPLIED\n";
