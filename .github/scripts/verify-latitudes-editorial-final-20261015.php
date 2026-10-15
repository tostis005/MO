<?php
/** Read-only sign-off of all 138 Latitudes products after editorial and category work. */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
$st = MDO_Database::table( 'source_products' );
$spt = MDO_Database::table( 'suppliers' );
$sup = $wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$spt} WHERE code=%s LIMIT 1", 'latitudes'), ARRAY_A);
if ( ! $sup || '1' !== (string) get_user_meta((int) $sup['vendor_user_id'], '_disable_vendor', true) ) { throw new RuntimeException('El vendedor Latitudes debe estar deshabilitado.'); }
$rows = $wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,source_price,source_stock_status,source_payload,status,source_url FROM {$st} WHERE supplier_id=%d ORDER BY id", (int) $sup['id']), ARRAY_A);
$summary = array('sources'=>count($rows),'editorial_approved'=>0,'editorial_saved'=>0,'category_reviewed'=>0,'price_matched'=>0,'stock_matched'=>0,'image_present'=>0,'hidden_drafts'=>0,'errors'=>array(),'flagged_for_manual_review'=>array());
foreach($rows as $row) {
    $id=(int) $row['wc_product_id'];
    $p=$id?wc_get_product($id):false;
    $errors=array();
    if(!$p || $row['status']!=='active'){ $errors[]='producto_emdo_no_activo'; }
    if($p){
        $desc=(string) get_post_meta($id,'_emdo_latitudes_editorial_description',true);
        $approved='1'===(string)get_post_meta($id,'_emdo_latitudes_editorial_approved',true);
        if($approved && mb_strlen(trim(wp_strip_all_tags($desc)))>=170){$summary['editorial_approved']++;}else{$errors[]='editorial_pendiente';}
        if($desc && trim($p->get_description())===trim(wp_kses_post($desc))){$summary['editorial_saved']++;}else{$errors[]='editorial_no_coincide';}
        if('20261015'===(string)get_post_meta($id,'_emdo_latitudes_category_v1',true)){
            $ids=$p->get_category_ids();
            if($ids && !in_array((int)get_option('default_product_cat',0),$ids,true)){$summary['category_reviewed']++;}else{$errors[]='categoria_sin_categoria';}
        } else { $errors[]='clasificacion_pendiente'; }
        if(abs((float)$p->get_price()-(float)$row['source_price'])<0.02){$summary['price_matched']++;}else{$errors[]='precio';}
        if($p->get_stock_status()===(string)$row['source_stock_status']){$summary['stock_matched']++;}else{$errors[]='stock';}
        if($p->get_image_id() && 'attachment'===get_post_type($p->get_image_id())){$summary['image_present']++;}else{$errors[]='imagen';}
        if($p->get_status()==='draft' && $p->get_catalog_visibility()==='hidden' && (int)get_post_field('post_author',$id)===(int)$sup['vendor_user_id']){$summary['hidden_drafts']++;}else{$errors[]='publicacion';}
        $manual=(string)get_post_meta($id,'_emdo_latitudes_manual_review',true);
        if($manual){$summary['flagged_for_manual_review'][]=array('source_id'=>(int)$row['id'],'title'=>$p->get_name(),'reason'=>$manual);}
    }
    if($errors){$summary['errors'][]=array('source_id'=>(int)$row['id'],'issues'=>$errors);}
}
$summary['pass']=count($rows)===138 && count($summary['errors'])===0;
echo 'LATITUDES_EDITORIAL_FINAL_SIGNOFF='.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$summary['pass']){exit(41);}
