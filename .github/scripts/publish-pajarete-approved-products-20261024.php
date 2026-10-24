<?php
/**
 * Publish only approved Quesos Pajarete source products and keep every excluded
 * reference off sale. Scope: existing products of supplier 'quesos-pajarete',
 * vendor 4974. Also save approved, citation-free Spanish store About.
 * PAJARETE_LAUNCH_PHASE=preview|apply|verify.
 */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database') ||
    !class_exists('MDO_Connector_Quesos_Pajarete')) {
    throw new RuntimeException('WooCommerce and Pajarete connector required.');
}
foreach (['home','siteurl'] as $key) {
    $host=strtolower((string)wp_parse_url((string)get_option($key),PHP_URL_HOST));
    if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) {
        throw new RuntimeException('Publication is production-only.');
    }
}
global $wpdb;
$vendor_id=4974;
$supplier_table=MDO_Database::table('suppliers');
$source_table=MDO_Database::table('source_products');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$supplier_table} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
$vendor=get_user_by('id',$vendor_id);
if (!$supplier || (int)$supplier['vendor_user_id']!==$vendor_id || !$vendor ||
    $vendor->user_login!=='quesos-pajarete') {
    throw new RuntimeException('Pajarete identity does not match');
}
if (get_user_meta($vendor_id,'_disable_vendor',true) || get_user_meta($vendor_id,'_wcfm_store_offline',true)) {
    throw new RuntimeException('Vendor is disabled/offline; refusing to make products publicly purchasable');
}
$profile=get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
if (!is_array($profile) || (string)($profile['store_name']??'')!=='Quesos Pajarete') {
    throw new RuntimeException('Store profile mismatch');
}
$about=<<<'HTML'
<p>Desde Villamartín, en plena Sierra de Cádiz, Quesos Pajarete elabora quesos que reflejan el valor de una tradición quesera profundamente arraigada a su territorio. Su proyecto gira en torno a una idea clara: transformar materias primas de calidad en productos con identidad, cuidando cada detalle del proceso para que el origen también se perciba en el sabor.</p>

<p>Su catálogo reúne quesos de cabra, de oveja y de mezcla de cabra y oveja, además de una selección gourmet pensada para acompañar y completar la experiencia alrededor del queso. En cada elaboración se aprecia una apuesta por la autenticidad, la artesanía y el respeto por un saber hacer que forma parte del carácter de la Sierra de Cádiz.</p>

<p>Quesos Pajarete ha logrado además un reconocimiento muy destacado por la calidad de sus elaboraciones, con más de 91 galardones y presencia en concursos y certámenes de referencia dentro del mundo quesero. Esa combinación de tradición, oficio y reconocimiento convierte a Pajarete en una referencia para quienes buscan quesos con personalidad propia y un vínculo real con su lugar de origen.</p>
HTML;
$about=wp_kses_post($about);
if (preg_match('~(?:turn[0-9]+|filecite|sourcecite|【|】||<Cite|https?://)~ui',$about)) {
    throw new RuntimeException('Source citation artifact found in About');
}
$rows=$wpdb->get_results($wpdb->prepare(
 "SELECT id,wc_product_id,source_url,title,status,source_payload FROM {$source_table} WHERE supplier_id=%d ORDER BY id",
 (int)$supplier['id']),ARRAY_A);
if (count($rows)<125 || count($rows)>180) {
    throw new RuntimeException('Unexpected Pajarete source count');
}
$approved=[];$excluded=[];$unlinked=[];$invalid=[];
foreach($rows as $r) {
    $id=(int)$r['wc_product_id'];
    $url=(string)$r['source_url'];
    if (!in_array(strtolower((string)wp_parse_url($url,PHP_URL_HOST)),
        ['quesospajarete.com','www.quesospajarete.com'],true)) {
        throw new RuntimeException('Non-Pajarete source URL');
    }
    $payload=json_decode((string)$r['source_payload'],true);
    $payload=is_array($payload)?$payload:[];
    $blocked=MDO_Connector_Quesos_Pajarete::excluded($url,(string)$r['title'])
        || MDO_Connector_Quesos_Pajarete::excluded($url,(string)($payload['title']??''));
    if (!$id) {
        $unlinked[]=['id'=>(int)$r['id'],'status'=>$r['status'],'blocked'=>$blocked];
        continue;
    }
    $p=wc_get_product($id);
    if (!$p || (int)get_post_field('post_author',$id)!==$vendor_id ||
        $p->is_type('variation')) {
        throw new RuntimeException('Incorrect Woo product association: '.$id);
    }
    if ($r['status']==='excluded' || $blocked || $r['status']==='unavailable') {
        $excluded[$id]=['product'=>$p,'status'=>(string)$r['status'],'blocked'=>$blocked];
        continue;
    }
    if ($r['status']!=='active') {
        // Do not publish pending/importing/failed references.
        $invalid[$id]=(string)$r['status'];
        continue;
    }
    $image_id=(int)$p->get_image_id();
    $image_file=$image_id?get_attached_file($image_id):'';
    $ok_images=$image_file&&is_readable($image_file)&&filesize($image_file)>0;
    if (!$ok_images || !trim(wp_strip_all_tags($p->get_short_description())) ||
        !trim(wp_strip_all_tags($p->get_description())) ||
        !in_array($p->get_stock_status(),['instock','outofstock'],true)) {
        throw new RuntimeException('Incomplete eligible product: '.$id);
    }
    if ($p->is_type('variable')) {
        $children=$p->get_children();
        if (!$children) throw new RuntimeException('Variable product without children: '.$id);
        foreach ($children as $child_id) {
            $variant=wc_get_product($child_id);
            if (!$variant || !is_numeric($variant->get_regular_price()) ||
                (float)$variant->get_regular_price()<=0) {
                throw new RuntimeException('Invalid variant price for: '.$id.'/'.$child_id);
            }
        }
    } elseif (!is_numeric($p->get_regular_price()) ||
              (float)$p->get_regular_price()<=0) {
        throw new RuntimeException('Invalid regular price: '.$id);
    }
    $approved[$id]=$p;
}
if (count($approved)+count($excluded)+count($invalid)!==count($rows)-count($unlinked)) {
    throw new RuntimeException('Product classification is incomplete');
}
$phase=getenv('PAJARETE_LAUNCH_PHASE')?:'preview';
$before=['eligible'=>count($approved),'excluded'=>count($excluded),
 'unlinked'=>count($unlinked),'other_linked'=>count($invalid),
 'eligible_drafts'=>count(array_filter($approved,static fn($p)=>$p->get_status()==='draft')),
 'eligible_published'=>count(array_filter($approved,static fn($p)=>$p->get_status()==='publish')),
 'excluded_published'=>count(array_filter($excluded,static fn($r)=>$r['product']->get_status()==='publish')),
 'excluded_drafts'=>count(array_filter($excluded,static fn($r)=>$r['product']->get_status()==='draft')),
 'about_chars'=>mb_strlen(trim(wp_strip_all_tags($about)))];
if ($phase==='preview') {
    echo 'PAJARETE_LAUNCH_PREVIEW='.wp_json_encode($before,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}
if (!in_array($phase,['apply','verify'],true)) {
    throw new RuntimeException('Invalid phase');
}
if ($phase==='apply') {
    $snapshot_key='_mdo_pajarete_publication_snapshot_20261024';
    $snapshot=get_option($snapshot_key,null);
    if (null===$snapshot || false===$snapshot) {
        $old=['about'=>get_user_meta($vendor_id,'_store_description',true),
          'shop_description'=>$profile['shop_description']??'', 'product_status'=>[]];
        foreach (array_merge($approved,array_map(static fn($r)=>$r['product'],$excluded)) as $id=>$p) {
            $old['product_status'][$id]=['status'=>$p->get_status(),'visibility'=>$p->get_catalog_visibility()];
        }
        add_option($snapshot_key,$old,'','no');
    }
    // Disable explicitly excluded references FIRST; never leave them on sale.
    foreach ($excluded as $id=>$r) {
        $p=$r['product'];
        if ($p->get_status()!=='draft' || $p->get_catalog_visibility()!=='hidden') {
            $p->set_status('draft');
            $p->set_catalog_visibility('hidden');
            $p->save();
        }
    }
    foreach ($approved as $id=>$p) {
        if ($p->get_status()!=='publish' || $p->get_catalog_visibility()!=='visible') {
            $p->set_status('publish');
            $p->set_catalog_visibility('visible');
            $p->save();
        }
    }
    $profile['shop_description']=$about;
    update_user_meta($vendor_id,'wcfmmp_profile_settings',$profile);
    update_user_meta($vendor_id,'_store_description',$about);
    if (function_exists('wc_delete_product_transients')) {
        foreach ($approved as $id=>$p) wc_delete_product_transients($id);
        foreach ($excluded as $id=>$p) wc_delete_product_transients($id);
    }
}
$result=['supplier_id'=>(int)$supplier['id'],'vendor_id'=>$vendor_id,'before'=>$before,
 'eligible_published'=>0,'eligible_nonpublic'=>[],'excluded_public'=>[],
 'excluded_hidden'=>0,'pending_untouched'=>count($unlinked),
 'other_not_published'=>count($invalid),
 'about_is_exact'=>get_user_meta($vendor_id,'_store_description',true)===$about,
 'profile_about_is_exact'=>((get_user_meta($vendor_id,'wcfmmp_profile_settings',true)['shop_description']??'')===$about)];
foreach ($approved as $id=>$p) {
    $fresh=wc_get_product($id);
    if ($fresh->get_status()==='publish' && $fresh->get_catalog_visibility()==='visible') {
        $result['eligible_published']++;
    } else {
        $result['eligible_nonpublic'][]=$id;
    }
}
foreach ($excluded as $id=>$r) {
    $fresh=wc_get_product($id);
    if ($fresh->get_status()==='publish' || $fresh->get_catalog_visibility()!=='hidden') {
        $result['excluded_public'][]=$id;
    } else {
        $result['excluded_hidden']++;
    }
}
echo 'PAJARETE_LAUNCH_RESULT='.wp_json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if ($result['eligible_published']!==count($approved) || $result['excluded_public'] ||
    !$result['about_is_exact'] || !$result['profile_about_is_exact']) {
    throw new RuntimeException('Pajarete launch state is incomplete');
}
