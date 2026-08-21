<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;

function emdo_text_len( $html ) {
    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $html ) ) ) );
    return function_exists('mb_strlen') ? mb_strlen($text,'UTF-8') : strlen($text);
}

function emdo_vendor_label( $user_id ) {
    $u = get_userdata($user_id);
    if (!$u) return 'user-'.$user_id;
    $store = get_user_meta($user_id, 'wcfmmp_profile_settings', true);
    if (is_array($store) && !empty($store['store_name'])) return (string)$store['store_name'];
    return $u->display_name ?: $u->user_login;
}

function emdo_vendor_disabled( $user_id, $user ) {
    $roles = $user ? (array)$user->roles : array();
    if (in_array('disable_vendor', $roles, true)) return true;
    $disabled = strtolower(trim((string)get_user_meta($user_id, '_disable_vendor', true)));
    if (in_array($disabled, array('1','yes','true','on'), true)) return true;
    $offline = strtolower(trim((string)get_user_meta($user_id, '_wcfm_store_offline', true)));
    if (in_array($offline, array('1','yes','true','on'), true)) return true;
    return false;
}

$authors = $wpdb->get_col("SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type='product' ORDER BY post_author");
echo "EMDO DISABLED VENDOR SEO READINESS AUDIT V2 2026-08-21\n";
echo 'PRODUCT_AUTHORS=' . count($authors) . "\n";
$disabled_count = 0;

foreach ($authors as $author_id_raw) {
    $author_id = (int)$author_id_raw;
    $u = get_userdata($author_id);
    if (!$u) continue;

    $status_counts = $wpdb->get_results($wpdb->prepare(
        "SELECT post_status, COUNT(*) c FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d GROUP BY post_status ORDER BY post_status",
        $author_id
    ), ARRAY_A);

    $is_disabled = emdo_vendor_disabled($author_id, $u);
    if ($is_disabled) $disabled_count++;

    $meta_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND (meta_key LIKE '%%wcfm%%' OR meta_key LIKE '%%vendor%%' OR meta_key LIKE '%%store%%' OR meta_key LIKE '%%disable%%') ORDER BY meta_key",
        $author_id
    ), ARRAY_A);
    $signals = array();
    foreach ($meta_rows as $row) {
        $key=(string)$row['meta_key'];
        $val=maybe_unserialize($row['meta_value']);
        if (is_array($val)) {
            $keep=array();
            foreach (array('store_name','store_slug','store_hide_email','store_hide_phone','store_hide_address','disable_vendor','vendor_enabled','store_enabled','is_store_offline') as $k) {
                if (array_key_exists($k,$val)) $keep[$k]=$val[$k];
            }
            if ($keep) $signals[$key]=$keep;
        } else {
            $s=trim((string)$val);
            if ($s!=='' && strlen($s)<250) $signals[$key]=$s;
        }
    }

    // Query IDs directly from wp_posts so marketplace/query hooks cannot leak products
    // from another vendor into this audit.
    $product_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type='product' AND post_author=%d
           AND post_status IN ('publish','draft','pending','private','future')
         ORDER BY ID ASC",
        $author_id
    ));

    $issues=array('missing_content'=>0,'short_content'=>0,'missing_excerpt'=>0,'missing_featured_image'=>0,'missing_category'=>0,'featured_alt_missing'=>0,'gallery_alt_missing'=>0,'english_unpublished'=>0,'english_missing_title'=>0,'english_missing_content'=>0);
    $samples=array();
    $unique_images=array();
    $status_audited=array();
    foreach ($product_ids as $product_id_raw) {
        $p = get_post((int)$product_id_raw);
        if (!$p || $p->post_type !== 'product' || (int)$p->post_author !== $author_id) continue;
        $status_audited[$p->post_status] = ($status_audited[$p->post_status] ?? 0) + 1;
        $reasons=array();
        $len=emdo_text_len($p->post_content);
        if ($len===0) { $issues['missing_content']++; $reasons[]='missing_content'; }
        elseif ($len<120) { $issues['short_content']++; $reasons[]='short_content'; }
        if (emdo_text_len($p->post_excerpt)===0) $issues['missing_excerpt']++;
        $thumb=(int)get_post_thumbnail_id($p->ID);
        if (!$thumb) { $issues['missing_featured_image']++; $reasons[]='missing_featured_image'; }
        else {
            $unique_images[$thumb]=true;
            if (trim((string)get_post_meta($thumb,'_wp_attachment_image_alt',true))==='') { $issues['featured_alt_missing']++; $reasons[]='featured_alt_missing'; }
        }
        $cats=wp_get_post_terms($p->ID,'product_cat',array('fields'=>'ids'));
        if (is_wp_error($cats)||!$cats) { $issues['missing_category']++; $reasons[]='missing_category'; }
        $gallery=(string)get_post_meta($p->ID,'_product_image_gallery',true);
        foreach (array_filter(array_map('intval',explode(',',$gallery))) as $img) {
            $unique_images[$img]=true;
            if (trim((string)get_post_meta($img,'_wp_attachment_image_alt',true))==='') $issues['gallery_alt_missing']++;
        }
        if ((string)get_post_meta($p->ID,'_en_US_published',true)!=='1') $issues['english_unpublished']++;
        if (trim((string)get_post_meta($p->ID,'_en_US_post_title',true))==='') $issues['english_missing_title']++;
        if (emdo_text_len(get_post_meta($p->ID,'_en_US_post_content',true))===0) $issues['english_missing_content']++;
        if (($reasons || $is_disabled) && count($samples)<30) {
            $samples[]=array(
                'id'=>$p->ID,'status'=>$p->post_status,'title'=>$p->post_title,'content_len'=>$len,
                'excerpt_len'=>emdo_text_len($p->post_excerpt),'reasons'=>$reasons,
                'categories'=>wp_get_post_terms($p->ID,'product_cat',array('fields'=>'names')),
                'sku'=>(string)get_post_meta($p->ID,'_sku',true),
                'source_url'=>(string)get_post_meta($p->ID,'_mdo_source_url',true),
            );
        }
    }

    $profile=get_user_meta($author_id,'wcfmmp_profile_settings',true);
    $store_name=is_array($profile)&&!empty($profile['store_name']) ? $profile['store_name'] : emdo_vendor_label($author_id);
    $store_slug=is_array($profile)&&!empty($profile['store_slug']) ? $profile['store_slug'] : '';
    $row=array(
        'user_id'=>$author_id,'disabled'=>$is_disabled,'user_login'=>$u->user_login,'display_name'=>$u->display_name,
        'roles'=>array_values($u->roles),'store_name'=>$store_name,'store_slug'=>$store_slug,
        'product_status_counts'=>$status_counts,'audited_status_counts'=>$status_audited,
        'products_checked'=>count($product_ids),'product_ids'=>array_map('intval',$product_ids),
        'unique_images'=>count($unique_images),'issues'=>$issues,'status_signals'=>$signals,'samples'=>$samples,
    );
    echo ($is_disabled ? 'DISABLED_VENDOR=' : 'VENDOR=') . wp_json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
}

echo 'DISABLED_VENDOR_COUNT=' . $disabled_count . "\n";
echo "END_AUDIT\n";
