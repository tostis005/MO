<?php
/**
 * Read-only live content audit for EMDO's two newly onboarded suppliers.
 * Scopes by source_products + suppliers and verifies WooCommerce author.
 * No product, shipping, inventory, image, taxonomy or source changes.
 */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) {
    throw new RuntimeException('WordPress, WooCommerce and supplier sync required');
}
global $wpdb;
$sp = MDO_Database::table('source_products');
$su = MDO_Database::table('suppliers');
$codes = ['latitudes','chocolates-artesanos-isabel'];
$report = ['generated_at'=>gmdate('c'),'scope'=>$codes,'suppliers'=>[],'flags'=>[],'examples'=>[]];
$contexts = static function(string $plain, string $pattern): array {
    if (!preg_match_all($pattern,$plain,$matches,PREG_OFFSET_CAPTURE)) return [];
    $out=[];
    foreach(array_slice($matches[0],0,8) as $hit) {
        $start=max(0,(int)$hit[1]-80);
        $excerpt=trim(preg_replace('/\s+/u',' ',mb_strcut($plain,$start,220,'UTF-8')));
        if ($excerpt!=='') $out[]=$excerpt;
    }
    return array_values(array_unique($out));
};
$first = '/\b(?:nuestr[oa]s?|nosotr[ao]s?|contacta(?:d)?nos|escr[ií]benos|cons[uú]ltanos|ofrecemos|proponemos|elaboramos|enviamos|nuestro\s+obrador|nuestra\s+tienda|nuestra\s+web|somos|puedes\s+comprar\s+en)\b/iu';
$commercial = '/\b(?:visita\s+(?:nuestra|la)\s+(?:web|tienda)|haz\s+(?:tu|su)\s+pedido\s+en|a\s+trav[eé]s\s+de\s+nuestra\s+web|pulsa\s+(?:aqui|aquí)|haz\s+clic\s+aquí|comprar\s+en\s+(?:nuestra|esta)\s+web)\b/iu';
foreach($codes as $code) {
    $supplier=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$su} WHERE code=%s LIMIT 1",$code),ARRAY_A);
    if(!$supplier) throw new RuntimeException("Supplier missing: ".$code);
    $rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,status,title,source_payload,source_url FROM {$sp} WHERE supplier_id=%d ORDER BY id",(int)$supplier['id']),ARRAY_A);
    $counts=['source_rows'=>count($rows),'valid_products'=>0,'published'=>0,'source_excluded'=>0,'missing_product'=>0,'approved'=>0,'long_short'=>0,'short_empty'=>0,'supplier_name_mentions'=>0,'first_person'=>0,'commercial_cta'=>0,'supplier_links'=>0,'source_exact'=>0,'html_artifacts'=>0,'duplicate_intro'=>0];
    $starts=[];
    $products=[];
    foreach($rows as $r) {
        $id=(int)$r['wc_product_id'];
        if($r['status']==='excluded'){$counts['source_excluded']++;continue;}
        $p=$id?wc_get_product($id):false;
        if(!$p || (int)get_post_field('post_author',$id)!==(int)$supplier['vendor_user_id']) {
            $counts['missing_product']++;$products[]=['id'=>$id,'source_id'=>(int)$r['id'],'issue'=>['missing_or_wrong_author']];continue;
        }
        $counts['valid_products']++;
        $counts['published']+=(int)($p->get_status()==='publish');
        $long=(string)$p->get_description();
        $short=(string)$p->get_short_description();
        $plain=html_entity_decode(trim(wp_strip_all_tags($long)),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $brief=html_entity_decode(trim(wp_strip_all_tags($short)),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $payload=json_decode((string)$r['source_payload'],true)?:[];
        $original=html_entity_decode(trim(wp_strip_all_tags((string)($payload['description']??''))),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $word_count=preg_match_all('/[\p{L}0-9]+/u',$plain);
        $approved=$code==='latitudes'?get_post_meta($id,'_emdo_latitudes_editorial_approved',true)==='1':get_post_meta($id,'_emdo_isabel_editorial_approved',true)==='1';
        $counts['approved']+=(int)$approved;
        $issues=[];
        if ($word_count<60){$issues[]='less_than_60_words';$counts['long_short']++;}
        if(mb_strlen($brief)<25){$issues[]='short_empty_or_very_short';$counts['short_empty']++;}
        $supplier_pattern=$code==='latitudes'?'/\blatitudes\b/iu':'/chocolates?\s+artesanos?\s+isabel|\bisabel\b/iu';
        $brand=$contexts($plain.' '.$brief,$supplier_pattern);
        if($brand){$issues[]='supplier_name_in_copy';$counts['supplier_name_mentions']++;}
        $firsts=$contexts($plain.' '.$brief,$first);
        if($firsts){$issues[]='supplier_first_person';$counts['first_person']++;}
        $cta=$contexts($plain.' '.$brief,$commercial);
        if($cta){$issues[]='source_store_cta';$counts['commercial_cta']++;}
        $external=$contexts($long.' '.$short,'~(?:https?://)?(?:www\.)?(?:latitudes\.es|chocolatesartesanosisabel\.com)[^\s<"\']*~iu');
        if($external){$issues[]='supplier_url_in_copy';$counts['supplier_links']++;}
        $source_same=$original!==''&&preg_replace('/\s+/u',' ',mb_strtolower($plain))===preg_replace('/\s+/u',' ',mb_strtolower($original));
        if($source_same){$issues[]='identical_to_supplier_source';$counts['source_exact']++;}
        $artifacts=$contexts($long.' '.$short,'/(?:&amp;amp;|&lt;p&gt;|\[vc_[a-z]+|(?:<script)\b)/iu');
        if($artifacts){$issues[]='html_artifact';$counts['html_artifacts']++;}
        $start=mb_substr(mb_strtolower(preg_replace('/\s+/u',' ',$plain)),0,110);
        if($start!==''){$starts[$start][]=$id;}
        if($issues || !$approved) {
            $products[]=[
                'id'=>$id,'source_id'=>(int)$r['id'],'name'=>$p->get_name(),'url'=>get_permalink($id),'status'=>$p->get_status(),
                'long_chars'=>mb_strlen($plain),'long_words'=>$word_count,'short_chars'=>mb_strlen($brief),'approved'=>$approved,
                'issues'=>$issues,'brand_context'=>$brand,'first_person_context'=>$firsts,'commercial_context'=>$cta,'external_context'=>$external,'html_context'=>$artifacts,
                'long_intro'=>mb_substr($plain,0,280),'short_text'=>mb_substr($brief,0,200),
                'source_intro'=>mb_substr($original,0,170),'source_description_present'=>($original!=='')
            ];
        }
    }
    foreach($starts as $ids)if(count($ids)>1){$counts['duplicate_intro']+=count($ids);$report['examples'][]=['supplier'=>$code,'same_opening_ids'=>$ids];}
    $report['suppliers'][$code]=['counts'=>$counts,'products'=>$products];
}
echo 'EMDO_SEO_COPY_AUDIT='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
