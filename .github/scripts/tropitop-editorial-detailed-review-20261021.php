<?php
/** Read-only detailed review: existing published Tropitop descriptions, not producer source copies. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI) exit(1);
if(!in_array(strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST)),['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))throw new RuntimeException('Wrong WP installation');
foreach(['WooCommerce','MDO_Database','MDO_Tropitop_Editorial'] as $class)if(!class_exists($class))throw new RuntimeException('Unavailable '.$class);
global $wpdb;
$source=MDO_Database::table('source_products');$suppliers=MDO_Database::table('suppliers');
$rows=$wpdb->get_results($wpdb->prepare("SELECT s.wc_product_id FROM {$source} s INNER JOIN {$suppliers} p ON s.supplier_id=p.id WHERE p.code=%s AND s.wc_product_id>0 ORDER BY s.wc_product_id",'tropitop'),ARRAY_A);
$all=[];$summary=['linked'=>count($rows),'reviewed'=>0,'suspected'=>0,'first_person'=>0,'retailer_context'=>0,'tropitop_identity'=>0,'external_urls'=>0,'no_copy'=>0,'classifier_residual'=>0,'full_copy_over_40'=>0];
$pattern='~\b(?:Tropitop|nuestr(?:o|a|os|as)|nosotr(?:os|as)|trabajamos|colaboramos|garantizamos|seleccionamos|enviamos|vendemos|cuidamos|somos|ofrecemos|cultivamos|preparamos|comprar|compra\s+(?:online|directamente)|suscripci[oó]n|suscr[ií]bete|whatsapp|https?://|te\s+(?:enviamos|ofrecemos|preparamos)|visita\s+(?:la|nuestra)\s+tienda|env[ií]o\s+a\s+domicilio)\b~iu';
foreach($rows as $row){
 $id=(int)$row['wc_product_id'];if(isset($all[$id]))continue;$all[$id]=true;
 $p=wc_get_product($id);if(!$p)continue;
 $description=(string)$p->get_description('edit');$short=(string)$p->get_short_description('edit');
 $plain=trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags($description.' '.$short),ENT_QUOTES|ENT_HTML5,'UTF-8')));
 $flags=MDO_Tropitop_Editorial::residual_markers($description.' '.$short);
 $ctx=[];
 preg_match_all($pattern,$plain,$matches,PREG_OFFSET_CAPTURE);
 foreach(array_slice($matches[0],0,6) as $m){
  $offset=max(0,(int)$m[1]-140);
  $ctx[]=trim(mb_strcut($plain,$offset,320,'UTF-8'));
 }
 if(preg_match('/\b(?:nuestr|nosotr|trabajamos|colaboramos|garantizamos|seleccionamos|enviamos|vendemos|cuidamos|somos|ofrecemos|cultivamos|preparamos)\w*\b/iu',$plain))++$summary['first_person'];
 if(preg_match('/\b(?:compra\s+(?:online|directamente)|comprar|te\s+(?:enviamos|ofrecemos)|suscr[ií]bete|whatsapp)\b/iu',$plain))++$summary['retailer_context'];
 if(preg_match('/\bTropitop\b/u',$plain))++$summary['tropitop_identity'];
 if(preg_match('~https?://(?:www\.)?tropitop\.com~iu',$description.' '.$short))++$summary['external_urls'];
 if(strlen($plain)>40)++$summary['full_copy_over_40'];
 if(!$plain)++$summary['no_copy'];
 if($flags)++$summary['classifier_residual'];
 if($ctx||$flags){
  ++$summary['suspected'];
  $html_samples=[];
  foreach(['Trabajamos','Tropitop ofrece','compra','No trabajamos','de confianza de Tropitop'] as $needle){
    $j=mb_stripos($description,$needle);
    if($j!==false){$html_samples[$needle]=mb_substr($description,max(0,$j-170),430);}
  }
  echo 'EDITORIAL_RAW_ITEM='.wp_json_encode(['id'=>$id,'sku'=>$p->get_sku(),'title'=>$p->get_name(),'status'=>$p->get_status(),'flags'=>$flags,'excerpt'=>$ctx,'html'=>$html_samples],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
 }
 ++$summary['reviewed'];
}
echo 'EDITORIAL_RAW_SUMMARY='.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
