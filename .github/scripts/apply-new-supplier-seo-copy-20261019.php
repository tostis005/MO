<?php
/**
 * Apply SEO copy corrections to the two imported 2026-10 suppliers only.
 * Source facts, allergens, prices, shipping, images, availability and taxonomy
 * are not changed. Approved copy is stored so supplier sync cannot undo it.
 */
if (!defined('ABSPATH')||!class_exists('WooCommerce')||!class_exists('MDO_Database')||
    !class_exists('MDO_Woo_Importer')||!method_exists('MDO_Woo_Importer','neutralize_isabel_supplier_copy')) {
    throw new RuntimeException('Updated EMDO supplier sync required before copy edit');
}
global $wpdb;
$sp=MDO_Database::table('source_products');$su=MDO_Database::table('suppliers');
$report=['applied_at'=>gmdate('c'),'latitudes'=>['reviewed'=>0,'long_reworded'=>0,'short_added'=>0,'ids'=>[]],
    'chocolates-artesanos-isabel'=>['reviewed'=>0,'long_reworded'=>0,'short_reworded'=>0,'ids'=>[]],
    'commercial_fields_preserved'=>true,'verified'=>false];
$snapshots=[];
$meta_keys=['_emdo_latitudes_editorial_description','_emdo_latitudes_editorial_short_description'];
$lat_clean=static function(string $html):string {
    $replacements=[
        'Información sobre preparación, ingredientes y alérgenos publicada por Latitudes:' => 'Información de preparación, ingredientes y alérgenos:',
        'Información sobre preparación, ingredientes y alérgenos publicada por latitudes:' => 'Información de preparación, ingredientes y alérgenos:',
        'según la ficha de Latitudes' => 'según la ficha del productor',
        'según la ficha técnica de Latitudes' => 'según la ficha técnica del productor',
        'según Latitudes' => 'según la ficha del productor',
        'de Latitudes' => 'del productor',
        'por Latitudes' => 'por el productor',
        'para Latitudes' => 'para el productor',
        'en Latitudes' => 'en el catálogo del productor',
        'Latitudes lo comercializa' => 'Se comercializa',
        'Latitudes la comercializa' => 'Se comercializa',
        'Latitudes las comercializa' => 'Se comercializan',
        'Latitudes los comercializa' => 'Se comercializan',
        'Latitudes lo ofrece' => 'Se ofrece',
        'Latitudes la ofrece' => 'Se ofrece',
        'Latitudes las ofrece' => 'Se ofrecen',
        'Latitudes los ofrece' => 'Se ofrecen',
        'Latitudes la presenta' => 'Se presenta',
        'Latitudes lo presenta' => 'Se presenta',
        'Latitudes las presenta' => 'Se presentan',
        'Latitudes los presenta' => 'Se presentan',
        'Latitudes describe' => 'La ficha del productor describe',
        'Latitudes señala' => 'La ficha del productor señala',
        'Latitudes destaca' => 'La ficha del productor destaca',
        'Latitudes especifica' => 'La ficha del productor especifica',
        'Latitudes recomienda' => 'El productor recomienda',
    ];
    $html=str_replace(array_keys($replacements),array_values($replacements),$html);
    $html=preg_replace('/(^|[.!?]\s+)Latitudes\b/um','$1El productor',$html);
    $html=preg_replace('/\bLatitudes\b/iu','el productor',$html);
    return wp_kses_post((string)$html);
};
$first_sentence=static function(string $html):string {
    $sections=preg_split('~</p>|<br\s*/?>~iu',$html,2);
    $intro=trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)($sections[0]??$html)),ENT_QUOTES|ENT_HTML5,'UTF-8')));
    if(mb_strlen($intro)<50) $intro=trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8')));
    $sentences=preg_split('/(?<=[.!?])\s+(?=[\p{Lu}0-9])/u',$intro,2);
    $short=trim((string)($sentences[0]??$intro));
    if(mb_strlen($short)<58 && isset($sentences[1])) {
        $extra=preg_split('/(?<=[.!?])\s+(?=[\p{Lu}0-9])/u',$sentences[1],2);
        $short.=' '.trim((string)($extra[0]??''));
    }
    if(mb_strlen($short)>235) {
        $short=mb_substr($short,0,220);
        $short=(string)preg_replace('/\s+\S*$/u','',$short);
        $short=rtrim($short,' ,;:.').'…';
    }
    return trim($short);
};
try {
  foreach(['latitudes','chocolates-artesanos-isabel'] as $code) {
    $s=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$su} WHERE code=%s LIMIT 1",$code),ARRAY_A);
    if(!$s)throw new RuntimeException('Supplier unavailable: '.$code);
    $rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,status,source_payload FROM {$sp} WHERE supplier_id=%d ORDER BY id",(int)$s['id']),ARRAY_A);
    foreach($rows as $r) {
      if($r['status']==='excluded')continue;
      $id=(int)$r['wc_product_id'];$p=$id?wc_get_product($id):false;
      if(!$p||(int)get_post_field('post_author',$id)!==(int)$s['vendor_user_id'])throw new RuntimeException('Wrong supplier owner or missing product '.$id);
      $report[$code]['reviewed']++;
      $oldlong=(string)$p->get_description();$oldshort=(string)$p->get_short_description();
      $cats=$p->get_category_ids();
      $commercial=['price'=>$p->get_price('edit'),'regular'=>$p->get_regular_price('edit'),
        'sale'=>$p->get_sale_price('edit'),'stock'=>$p->get_stock_status('edit'),
        'status'=>$p->get_status('edit'),'visibility'=>$p->get_catalog_visibility('edit'),
        'sku'=>$p->get_sku('edit'),'weight'=>$p->get_weight('edit')];
      $snapshots[$id]=['long'=>$oldlong,'short'=>$oldshort,'cats'=>$cats,'commercial'=>$commercial,
        'meta'=>array_combine($meta_keys,array_map(static fn($k)=>get_post_meta($id,$k,true),$meta_keys))];
      $newlong=$oldlong;$newshort=$oldshort;
      if($code==='latitudes') {
        if(get_post_meta($id,'_emdo_latitudes_editorial_approved',true)!=='1')throw new RuntimeException('Latitudes editorial not approved '.$id);
        $newlong=$lat_clean($oldlong);
        if(preg_match('/\bLatitudes\b/iu',wp_strip_all_tags($newlong)))throw new RuntimeException('Latitudes residual mention '.$id);
        $summ=$first_sentence($newlong);
        if(mb_strlen($summ)<45)throw new RuntimeException('No verified summary sentence '.$id);
        $newshort='<p>'.esc_html($summ).'</p>';
        if($newlong!==$oldlong)$report[$code]['long_reworded']++;
        if($newshort!==$oldshort)$report[$code]['short_added']++;
      } else {
        $payload=json_decode((string)$r['source_payload'],true);
        if(!is_array($payload)||trim(wp_strip_all_tags((string)($payload['description']??'')))==='')throw new RuntimeException('Chocolate technical source missing '.$id);
        $neutral=MDO_Woo_Importer::neutralize_isabel_supplier_copy((string)$payload['description']);
        $approved=get_post_meta($id,'_emdo_isabel_editorial_approved',true)==='1';
        $intro=$approved?(string)get_post_meta($id,'_emdo_isabel_editorial_intro',true):'';
        $newlong=trim(wp_kses_post($intro))!==''?wp_kses_post($intro)."\n".$neutral:$neutral;
        if($approved){
          $shortapproved=(string)get_post_meta($id,'_emdo_isabel_editorial_short_description',true);
          if(trim(wp_strip_all_tags($shortapproved))!=='')$newshort=$shortapproved;
        }
        $newshort=MDO_Woo_Importer::neutralize_isabel_supplier_copy($newshort);
        if($newlong!==$oldlong)$report[$code]['long_reworded']++;
        if($newshort!==$oldshort)$report[$code]['short_reworded']++;
      }
      if($newlong!==$oldlong||$newshort!==$oldshort){
        $p->set_description($newlong);$p->set_short_description($newshort);$p->save();
        if($code==='latitudes'){
          update_post_meta($id,'_emdo_latitudes_editorial_description',$newlong);
          update_post_meta($id,'_emdo_latitudes_editorial_short_description',$newshort);
        }
        // Some WooCommerce hooks may recategorize on save. Undo any drift.
        $after=wc_get_product($id);$aftercats=$after->get_category_ids();
        sort($aftercats);$oldcats=$cats;sort($oldcats);
        if($aftercats!==$oldcats)wp_set_object_terms($id,$cats,'product_cat',false);
        $after=wc_get_product($id);
        $compare=['price'=>$after->get_price('edit'),'regular'=>$after->get_regular_price('edit'),
          'sale'=>$after->get_sale_price('edit'),'stock'=>$after->get_stock_status('edit'),
          'status'=>$after->get_status('edit'),'visibility'=>$after->get_catalog_visibility('edit'),
          'sku'=>$after->get_sku('edit'),'weight'=>$after->get_weight('edit')];
        if($commercial!==$compare)throw new RuntimeException('Commercial data modified in copy-only pass '.$id);
        $report[$code]['ids'][]=$id;
      }
    }
  }
  if($report['latitudes']['reviewed']!==138||$report['chocolates-artesanos-isabel']['reviewed']<95)throw new RuntimeException('Incomplete supplier product audit');
  $report['verified']=true;
} catch(Throwable $e) {
  foreach(array_reverse(array_keys($snapshots)) as $id){
    $snap=$snapshots[$id];$p=wc_get_product($id);
    if($p) {
      $p->set_description($snap['long']);$p->set_short_description($snap['short']);$p->save();
      wp_set_object_terms($id,$snap['cats'],'product_cat',false);
    }
    foreach($snap['meta'] as $key=>$v){if($v==='')delete_post_meta($id,$key);else update_post_meta($id,$key,$v);}
  }
  wp_cache_flush();throw $e;
}
wp_cache_flush();if(function_exists('rocket_clean_domain'))rocket_clean_domain();
echo 'EMDO_SEO_COPY_APPLY='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
