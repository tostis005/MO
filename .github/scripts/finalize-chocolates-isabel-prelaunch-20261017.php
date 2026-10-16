<?php
/**
 * Isabel prelaunch closure:
 * - 7 customisable supplier references: WooCommerce drafts, not purchasable.
 * - 3 unavailable/past activity references: explicitly excluded from ecommerce.
 * Makes NO product public and never enables vendor checkout.
 */
if ( ! defined('ABSPATH') ) { exit(1); }
global $wpdb;
foreach (array('home','siteurl') as $k) {
    $h=strtolower((string)wp_parse_url((string)get_option($k),PHP_URL_HOST));
    if(!in_array($h,array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true)) {
        throw new RuntimeException('El sitio actual no es la instancia de producción.');
    }
}
$src=MDO_Database::table('source_products');
$sup=MDO_Database::table('suppliers');
$vendor=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$sup} WHERE code=%s LIMIT 1",'chocolates-artesanos-isabel'),ARRAY_A);
if(!$vendor){ throw new RuntimeException('Proveedor no encontrado.'); }
$sid=(int)$vendor['id'];$vid=(int)$vendor['vendor_user_id'];
if(!$vid || !get_user_meta($vid,'_disable_vendor',true)){throw new RuntimeException('Vendedor no está desactivado.');}
$phase=getenv('ISABEL_FINAL_PHASE')?:'report';
$custom_ids=array(692,693,700,701,702,757,758);
$service_ids=array(759,760,761);
$allowed=array_merge($custom_ids,$service_ids);
$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$src} WHERE supplier_id=%d AND id IN (".implode(',',array_map('intval',$allowed)).") ORDER BY id",$sid),ARRAY_A);
$by_id=array();
foreach((array)$rows as $row){$by_id[(int)$row['id']]=$row;}
if(count($by_id)!==10){throw new RuntimeException('Las diez referencias no coinciden con las comprobadas previamente.');}
$flag = '_emdo_isabel_customization_review_required';
if('apply'===$phase){
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $created=0;$reused=0;$error=0;$excluded=0;
    foreach($custom_ids as $id){
        $row=$by_id[$id];
        $payload=json_decode((string)$row['source_payload'],true);
        if(!is_array($payload)||empty($payload['title'])||empty($payload['description'])||
            !isset($payload['price'])||!is_numeric($payload['price'])||
            !in_array((string)$payload['stock_status'],array('instock','outofstock'),true)||
            (int)($payload['variation_count']??0)<2){
            echo "ISABEL_CUSTOM_BLOCKED={$id} reason=payload_incompleto\n";$error++;continue;
        }
        if(strtolower((string)wp_parse_url((string)$row['source_url'],PHP_URL_HOST))!=='chocolatesartesanosisabel.com'){
            echo "ISABEL_CUSTOM_BLOCKED={$id} reason=origen_no_valido\n";$error++;continue;
        }
        $existing=(int)$row['wc_product_id'];
        if($existing){
            if((int)get_post_field('post_author',$existing)!==$vid){
                echo "ISABEL_CUSTOM_BLOCKED={$id} reason=autor_no_coincide\n";$error++;continue;
            }
            $product=wc_get_product($existing);
            if(!$product||!$product->is_type('simple')||'draft'!==$product->get_status()){
                echo "ISABEL_CUSTOM_BLOCKED={$id} reason=producto_existente_conflictivo\n";$error++;continue;
            }
            $reused++;
        }else{
            $product=new WC_Product_Simple();
        }
        $product->set_name(MDO_Text::normalize_title((string)$payload['title']));
        $product->set_description(wp_kses_post((string)$payload['description']));
        $price=wc_format_decimal($payload['price']);
        $product->set_regular_price($price);
        $product->set_sale_price('');
        $product->set_price($price);
        $product->set_manage_stock(false);
        $product->set_stock_status((string)$payload['stock_status']);
        $product->set_status('draft');
        $product->set_catalog_visibility('hidden');
        $sku=sanitize_text_field((string)($payload['sku']??''));
        if($sku){
            $sku_owner=(int)wc_get_product_id_by_sku($sku);
            if(!$sku_owner||$sku_owner===(int)$product->get_id()){$product->set_sku($sku);}
        }
        $pid=(int)$product->save();
        if(!$pid){echo "ISABEL_CUSTOM_BLOCKED={$id} reason=error_guardado\n";$error++;continue;}
        wp_update_post(array('ID'=>$pid,'post_author'=>$vid,'post_status'=>'draft'));
        update_post_meta($pid,'_emdo_source_product_id',$id);
        update_post_meta($pid,'_emdo_supplier_id',$sid);
        update_post_meta($pid,'_emdo_source_url',esc_url_raw((string)$row['source_url']));
        update_post_meta($pid,'_emdo_source_hash',sanitize_text_field((string)$row['source_hash']));
        update_post_meta($pid,$flag,'1');
        update_post_meta($pid,'_emdo_isabel_customization_warning',
            'FICHA EN REVISION: el comprador debe poder configurar todas las opciones, textos y archivos solicitados por el fabricante. NO PUBLICAR hasta implementar y verificar formulario y transmisión al proveedor.');
        // Explicit categories + source-only short text and shipping attributes.
        MDO_Isabel_Quality::apply($pid,$payload);
        $p=wc_get_product($pid);
        if(!$p->get_image_id() && !empty($payload['images']) && is_array($payload['images'])){
            $image_url=esc_url_raw((string)$payload['images'][0]);
            $host=strtolower((string)wp_parse_url($image_url,PHP_URL_HOST));
            if(in_array($host,array('chocolatesartesanosisabel.com','www.chocolatesartesanosisabel.com'),true)){
                $attachment=media_sideload_image($image_url,$pid,$p->get_name(),'id');
                if(!is_wp_error($attachment) && (int)$attachment>0){
                    $p->set_image_id((int)$attachment);$p->save();
                    update_post_meta($pid,'_emdo_image_map',wp_json_encode(array($image_url=>(int)$attachment)));
                }else{
                    echo "ISABEL_CUSTOM_IMAGE_FAILED={$id}\n";
                }
            }
        }
        // The source record stays PENDING. The standard importer refuses to
        // turn custom checkout forms into fake simple/variable products.
        $wpdb->update($src,array(
            'wc_product_id'=>$pid,'status'=>'pending',
            'last_error'=>'Borrador seguro creado; falta implementar y validar opciones de personalizacion. No es comprable.'
        ),array('id'=>$id,'supplier_id'=>$sid));
        $p=wc_get_product($pid);
        if(!$p||$p->get_status()!=='draft'||$p->get_catalog_visibility()!=='hidden'||
           (int)get_post_field('post_author',$pid)!==$vid){
            throw new RuntimeException('Un borrador de personalización no mantiene el bloqueo de publicación.');
        }
        if(!$existing){$created++;}
        echo 'ISABEL_CUSTOM_DRAFT='.wp_json_encode(array('source_id'=>$id,'woo_id'=>$pid,
            'price'=>$p->get_price(),'image'=>(int)$p->get_image_id(),
            'status'=>$p->get_status(),'visibility'=>$p->get_catalog_visibility()),
            JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    }
    foreach($service_ids as $id){
        $row=$by_id[$id];$name=mb_strtolower((string)$row['title'],'UTF-8');
        if((int)$row['wc_product_id']>0){
            echo "ISABEL_SERVICE_CONFLICT={$id} existing_woocommerce_product\n";$error++;continue;
        }
        if((string)$row['source_stock_status']!=='outofstock'||
           ($id!==761&&!preg_match('/20|27.*junio.*2026/iu',$name))){
            echo "ISABEL_SERVICE_CONFLICT={$id} sale_status_or_date_changed\n";$error++;continue;
        }
        $wpdb->update($src,array('status'=>'excluded',
            'last_error'=>'Actividad excluida de ecommerce: visita con fecha anterior al 17/10/2026 o taller sin disponibilidad. No es un producto fisico.'
        ),array('id'=>$id,'supplier_id'=>$sid));
        $excluded++;
        echo "ISABEL_SERVICE_EXCLUDED={$id}\n";
    }
    echo 'ISABEL_FINAL_APPLY='.wp_json_encode(array('created'=>$created,'reused'=>$reused,
        'blocked'=>$error,'activities_excluded'=>$excluded))."\n";
    if($error){exit(31);}
}
if($phase==='apply'||$phase==='report'){
    $counts=$wpdb->get_results($wpdb->prepare("SELECT status,COUNT(*) n FROM {$src} WHERE supplier_id=%d GROUP BY status",$sid),ARRAY_A);
    $custom=$wpdb->get_results($wpdb->prepare(
        "SELECT sp.id,sp.wc_product_id,sp.status,p.post_status,p.post_author,
          (SELECT meta_value FROM {$wpdb->postmeta} m WHERE m.post_id=p.ID AND m.meta_key=%s LIMIT 1) as review_flag
          FROM {$src} sp LEFT JOIN {$wpdb->posts} p ON p.ID=sp.wc_product_id
          WHERE sp.supplier_id=%d AND sp.id IN (692,693,700,701,702,757,758)
          ORDER BY sp.id",$flag,$sid
    ),ARRAY_A);
    $published=(int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",$vid));
    $drafts=(int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='draft' AND post_author=%d",$vid));
    echo 'ISABEL_FINAL_REPORT='.wp_json_encode(array('supplier_id'=>$sid,'counts'=>$counts,
        'drafts'=>$drafts,'published'=>$published,'vendor_disabled'=>(bool)get_user_meta($vid,'_disable_vendor',true),
        'specials'=>$custom),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    if($published){exit(32);}
    exit;
}
throw new RuntimeException('Fase de cierre no válida.');
