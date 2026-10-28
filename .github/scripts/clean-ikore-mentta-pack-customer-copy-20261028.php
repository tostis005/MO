<?php
/**
 * Clean customer-facing branding from three existing coffee tasting packs.
 * Leave their real supplier, SKUs, vendor ownership, images, variations,
 * prices, categories, visibility and Mentta routing untouched.
 *
 * MDO_COFFEE_COPY_MODE=preflight|apply|verify|rollback wp eval-file SCRIPT
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
    fwrite(STDERR, "WPCLI_WOOCOMMERCE_REQUIRED\n"); exit(2);
}
$host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($host, ['elmercadodeorigen.com','www.elmercadodeorigen.com'], true)) exit(2);
$mode = (string) (getenv('MDO_COFFEE_COPY_MODE') ?: 'preflight');
if (!in_array($mode, ['preflight','apply','verify','rollback'], true)) exit(2);
$backup_key = 'emdo_coffee_packs_customer_copy_before_20261028';
$catalog = [
    21904 => [
        'sku'=>'IKORE-MENTTA-PACK-COSTA-RICA',
        'slug'=>'pack-cafe-esencia-de-costa-rica',
        'name'=>'Pack degustación de café de especialidad – Esencia de Costa Rica',
        'short'=>'Tres cafés de especialidad 100 % arábica de Costa Rica en bolsas de 250 g. Descubre tres perfiles de una misma tierra y elige la molienda para tu cafetera.',
        'intro'=>'Tres perfiles de Costa Rica en una degustación de café de especialidad 100 % arábica, con notas dulces, cítricas y florales.',
        'items'=>['Costa Rica Villa Sarchí Miel','Costa Rica Typica Mejorado','Costa Rica Geisha'],
        'seo'=>'Tres cafés de Costa Rica de 250 g en un pack de degustación. Elige entre siete opciones de molienda.'
    ],
    21906 => [
        'sku'=>'IKORE-MENTTA-PACK-4-ORIGENES',
        'slug'=>'pack-cafe-ruta-de-los-cuatro-origenes',
        'name'=>'Pack degustación de café de especialidad – Ruta de los 4 Orígenes',
        'short'=>'Cuatro cafés de especialidad 100 % arábica de Costa Rica, Colombia, Kenia y Panamá. Cuatro bolsas de 250 g con siete opciones de molienda, sin cambio de precio.',
        'intro'=>'Una ruta de degustación por cuatro países cafeteros para comparar aromas, dulzor, acidez y matices frutales.',
        'items'=>['Costa Rica Villa Sarchí Miel','Colombia El Indio','Kenia Washed Adumu','Panamá Chiriquí'],
        'seo'=>'Cuatro cafés de Costa Rica, Colombia, Kenia y Panamá en un pack de degustación. Elige tu molienda.'
    ],
    21908 => [
        'sku'=>'IKORE-MENTTA-PACK-6-CAFES',
        'slug'=>'pack-cafe-coleccion-completa',
        'name'=>'Pack degustación de café de especialidad – Colección Completa | 6 Cafés',
        'short'=>'Seis cafés de especialidad 100 % arábica de Costa Rica, Colombia, Kenia y Panamá. Seis bolsas de 250 g para descubrir distintos aromas y perfiles. Elige tu molienda.',
        'intro'=>'La colección completa de seis cafés de especialidad, con perfiles dulces, florales, afrutados, cítricos y achocolatados.',
        'items'=>['Costa Rica Villa Sarchí Miel','Costa Rica Typica Mejorado','Costa Rica Geisha','Colombia El Indio','Kenia Washed Adumu','Panamá Chiriquí'],
        'seo'=>'Seis cafés de especialidad en bolsas de 250 g, de cuatro países. Descubre la colección completa y elige tu molienda.'
    ]
];
function emdo_coffee_copy_description_20261028($item) {
    $html = '<p>'.esc_html($item['intro']).'</p><h2>¿Qué cafés incluye este pack?</h2><ul>';
    foreach ($item['items'] as $coffee) $html .= '<li>'.esc_html($coffee).' — 250 g.</li>';
    $html .= '</ul><h2>Elige la molienda para tu cafetera</h2>';
    $html .= '<p>Selecciona cómo recibir todos los cafés del pack: en grano, Italiana, Espresso, Prensa francesa, Cold brew, Aeropress o Filtro y V60. La misma opción se aplica a todas las bolsas, sin modificar el precio.</p>';
    $html .= '<h2>Presentación y conservación</h2><p>Se entregan en sus bolsas originales de 250 g, sin molinillo ni cafetera. Para comparar los perfiles, prepara cada café por separado con la misma receta. Conserva los envases bien cerrados, protegidos de la luz, la humedad y los olores. Consulta el lote y el consumo preferente en cada bolsa. Las notas de cata describen aromas, no ingredientes añadidos.</p>';
    return $html;
}
function emdo_coffee_copy_has_brand_20261028($value) {
    return (bool) preg_match('/\b(?:ikore|icore)(?:\s*(?:kofi|coffee))?\b/iu', wp_strip_all_tags((string)$value));
}
$before = [];
foreach ($catalog as $id=>$item) {
    $p = wc_get_product($id);
    if (!$p || !$p->is_type('variable') || $p->get_status() !== 'publish'
        || $p->get_sku() !== $item['sku'] || $p->get_slug() !== $item['slug']
        || (int)get_post_field('post_author',$id) !== 4970
        || (int)wc_get_product_id_by_sku($item['sku']) !== $id
        || count($p->get_children()) !== 7 || !$p->get_image_id()
        || !in_array($p->get_catalog_visibility(), ['visible','hidden'], true)
        || get_post_meta($id,'_emdo_mentta_only',true) !== 'yes') {
        fwrite(STDERR,"COFFEE_PACK_BASELINE_FAILED $id\n"); exit(3);
    }
    $img = (int)$p->get_image_id();
    $post = get_post($img);
    if (!$post || $post->post_type !== 'attachment' || (int)$post->post_parent !== $id) {
        fwrite(STDERR,"FEATURED_COVER_NOT_EXCLUSIVE $id\n");exit(3);
    }
    $before[$id] = [
        'name'=>$p->get_name('edit'), 'short'=>$p->get_short_description('edit'),
        'description'=>$p->get_description('edit'),
        'seo_title'=>get_post_meta($id,'_yoast_wpseo_title',true),
        'seo_description'=>get_post_meta($id,'_yoast_wpseo_metadesc',true),
        'image'=>$img,'image_title'=>$post->post_title,
        'image_caption'=>$post->post_excerpt,
        'image_description'=>$post->post_content,
        'image_alt'=>get_post_meta($img,'_wp_attachment_image_alt',true),
        'sku'=>$p->get_sku(), 'visibility'=>$p->get_catalog_visibility(),
        'gallery'=>$p->get_gallery_image_ids(), 'children'=>$p->get_children(),
        'price'=>(float)$p->get_price('edit')
    ];
}
echo 'COFFEE_PACK_COPY_PREFLIGHT '.wp_json_encode(['mode'=>$mode,'ids'=>array_keys($before),'backup_exists'=>(bool)get_option($backup_key,false)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if ($mode==='preflight') exit(0);
$backup=get_option($backup_key,false);
if ($mode==='rollback' && (!is_array($backup) || count($backup)!==3)) exit(4);
if ($mode==='apply' && !$backup) {
    if (!add_option($backup_key, $before, '', false)) {
        fwrite(STDERR,"COPY_BACKUP_FAILED\n");exit(4);
    }
    $backup=$before;
}
if ($mode==='apply' || $mode==='rollback') {
    foreach ($catalog as $id=>$item) {
        $p=wc_get_product($id);
        $restore=$mode==='rollback';
        $p->set_name($restore?$backup[$id]['name']:$item['name']);
        $p->set_short_description($restore?$backup[$id]['short']:'<p>'.esc_html($item['short']).'</p>');
        $p->set_description($restore?$backup[$id]['description']:emdo_coffee_copy_description_20261028($item));
        $p->save();
        update_post_meta($id,'_yoast_wpseo_title',$restore?$backup[$id]['seo_title']:$item['name']);
        update_post_meta($id,'_yoast_wpseo_metadesc',$restore?$backup[$id]['seo_description']:$item['seo']);
        $img=(int)$p->get_image_id();
        $imageData = $restore ? [
            'title'=>$backup[$id]['image_title'], 'caption'=>$backup[$id]['image_caption'],
            'description'=>$backup[$id]['image_description'],'alt'=>$backup[$id]['image_alt']
        ] : [
            'title'=>$item['name'].' – portada', 'caption'=>'',
            'description'=>'Portada del pack de degustación de café de especialidad.',
            'alt'=>$item['name'].' – selección de bolsas de café'
        ];
        $updated=wp_update_post([
            'ID'=>$img,'post_title'=>$imageData['title'],
            'post_excerpt'=>$imageData['caption'],'post_content'=>$imageData['description']
        ],true);
        if (is_wp_error($updated)) {fwrite(STDERR,"COVER_METADATA_FAILED $id\n");exit(5);}
        update_post_meta($img,'_wp_attachment_image_alt',$imageData['alt']);
        clean_post_cache($id);wc_delete_product_transients($id);
        if(function_exists('rocket_clean_post'))rocket_clean_post($id);
    }
    if (function_exists('rocket_clean_domain')) rocket_clean_domain();
    wp_cache_flush();
}
if ($mode!=='rollback') {
    foreach ($catalog as $id=>$item) {
        $p=wc_get_product($id);$previous=$before[$id];$img=(int)$p->get_image_id();$photo=get_post($img);
        $copy=[
            $p->get_name('edit'), $p->get_short_description('edit'),
            $p->get_description('edit'), get_post_meta($id,'_yoast_wpseo_title',true),
            get_post_meta($id,'_yoast_wpseo_metadesc',true),
            $photo->post_title,$photo->post_excerpt,$photo->post_content,
            get_post_meta($img,'_wp_attachment_image_alt',true)
        ];
        foreach ($copy as $field) if(emdo_coffee_copy_has_brand_20261028($field)) {
            fwrite(STDERR,"BRAND_REMAINS_IN_CUSTOMER_COPY $id\n");exit(6);
        }
        if($p->get_name('edit')!==$item['name'] || $img!==$previous['image']
            || $p->get_sku()!==$previous['sku']
            || $p->get_catalog_visibility()!==$previous['visibility']
            || $p->get_gallery_image_ids()!==$previous['gallery']
            || $p->get_children()!==$previous['children']
            || abs((float)$p->get_price('edit')-$previous['price'])>.0001) {
            fwrite(STDERR,"NON_COPY_PRODUCT_CHANGED $id\n");exit(7);
        }
        echo 'COFFEE_PACK_COPY_OK '.wp_json_encode(['id'=>$id,'name'=>$p->get_name(),'slug'=>$p->get_slug(),'variations'=>count($p->get_children()),'image'=>$img,'visibility'=>$p->get_catalog_visibility()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    }
} else echo "COFFEE_PACK_COPY_ROLLBACK_COMPLETE\n";

if ($mode==='verify' && class_exists('WC_Mentta_Plugin')) {
    if (!defined('REST_REQUEST')) define('REST_REQUEST',true);
    foreach ($catalog as $id=>$item) {
        $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id='.$id;
        $req=new WP_REST_Request('GET','/mentta_marketplace/products');
        $req->set_param('product_id',$id);
        $res=WC_Mentta_Plugin::get_products($req);
        if(!($res instanceof WP_REST_Response)||$res->get_status()!==200)exit(8);
        $raw=$res->get_data();
        $data=is_object($raw)?($raw->data??[]):(is_array($raw)?($raw['data']??[]):[]);
        $entry=is_array($data)?($data[0]??[]):[];
        if(!is_array($entry)||(int)($entry['Id']??0)!==$id||
            ($entry['IsVisible']??null)!==true||
            ($entry['IsInStock']??null)!==true||
            ($entry['IsPurchasable']??null)!==true||
            count((array)($entry['Variations']??[]))!==7) {
            fwrite(STDERR,"COFFEE_PACK_MENTTA_FEED_REGRESSION $id\n");exit(8);
        }
        foreach(['Name','Title','ProductName','Description','ShortDescription'] as $field) {
            if(isset($entry[$field])&&is_string($entry[$field])&&emdo_coffee_copy_has_brand_20261028($entry[$field])) {
                fwrite(STDERR,"BRAND_REMAINS_IN_MENTTA_{$field} $id\n");exit(9);
            }
        }
        echo "COFFEE_PACK_MENTTA_FEED_OK $id\n";
    }
}
echo "COFFEE_PACK_CUSTOMER_COPY_".strtoupper($mode)."_OK\n";
