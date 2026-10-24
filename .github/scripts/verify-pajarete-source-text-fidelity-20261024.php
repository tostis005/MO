<?php
/**
 * Read-only Quesos Pajarete text integrity and product-SEO quality check.
 * Compare long product descriptions with source-supplied descriptions and
 * surface materially missing passages, not HTML entity-only differences.
 */
if(!defined('ABSPATH')||!class_exists('MDO_Database')||!class_exists('WooCommerce'))throw new RuntimeException('WP required');
$host=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Wrong site');
global $wpdb;
$t=MDO_Database::table('suppliers');$st=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if(!$s||(int)$s['vendor_user_id']!==4974)throw new RuntimeException('Wrong vendor');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,source_payload,source_url,status,wc_product_id,title FROM {$st} WHERE supplier_id=%d AND wc_product_id>0",(int)$s['id']),ARRAY_A);
$norm=static function($html):string{
    $v=html_entity_decode((string)wp_strip_all_tags((string)$html),ENT_QUOTES|ENT_HTML5,'UTF-8');
    $v=preg_replace('/[\x{00A0}\x{200B}\x{202F}]/u',' ',$v);
    $v=preg_replace('/\s+/u',' ',$v);
    return trim((string)$v);
};
$out=['total'=>0,'source_exact'=>0,'normalized_exact'=>0,'similarity_ge99'=>0,
    'similarity_97_99'=>0,'similarity_lt97'=>0,'differing'=>0,'longs_empty'=>0,
    'different_length_gt50'=>0,'source_placeholders'=>[],'potential_wordloss'=>[]];
$examples=[];
foreach($rows as $r){
    $p=wc_get_product((int)$r['wc_product_id']);
    if(!$p)continue;
    $src=json_decode((string)$r['source_payload'],true);$src=is_array($src)?$src:[];
    $a=(string)($src['description']??'');
    $b=(string)$p->get_description();
    $out['total']++;
    if(!$norm($b))$out['longs_empty']++;
    if($a===$b){$out['source_exact']++;continue;}
    $sa=$norm($a);$sb=$norm($b);
    if($sa===$sb){$out['normalized_exact']++;continue;}
    $out['differing']++;
    similar_text($sa,$sb,$pct);
    if($pct>=99)$out['similarity_ge99']++;
    elseif($pct>=97)$out['similarity_97_99']++;
    else $out['similarity_lt97']++;
    $diff=abs(mb_strlen($sa)-mb_strlen($sb));
    if($diff>50)$out['different_length_gt50']++;
    $first=0;$limit=min(mb_strlen($sa),mb_strlen($sb));
    for(;$first<$limit;$first++)if(mb_substr($sa,$first,1)!==mb_substr($sb,$first,1))break;
    $one=mb_substr($sa,max(0,$first-60),145);
    $two=mb_substr($sb,max(0,$first-60),145);
    $item=['id'=>$p->get_id(),'title'=>$p->get_name(),'status'=>$p->get_status(),'score'=>round($pct,2),
        'source_chars'=>mb_strlen($sa),'marketplace_chars'=>mb_strlen($sb),'first_diff_at'=>$first,
        'source_fragment'=>$one,'marketplace_fragment'=>$two];
    if($pct<97)$out['potential_wordloss'][]=$item;
    elseif(count($examples)<10)$examples[]=$item;
}
echo 'PAJARETE_TEXT_FIDELITY='.wp_json_encode(['counts'=>$out,'examples'=>$examples],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
