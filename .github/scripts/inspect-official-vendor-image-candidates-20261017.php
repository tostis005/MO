<?php
/** Read-only inspect official vendor imagery and current WCFM banners in production. */
if(!defined('ABSPATH'))exit(1);
global $wpdb;
if(!in_array(wp_parse_url(home_url(),PHP_URL_HOST),array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true))throw new RuntimeException('Production only');
$t=MDO_Database::table('suppliers');
$v=$wpdb->get_results("SELECT code,vendor_user_id FROM {$t} WHERE code IN ('chocolates-artesanos-isabel','latitudes')",ARRAY_A);
foreach($v as $row){
 $id=(int)$row['vendor_user_id'];$p=get_user_meta($id,'wcfmmp_profile_settings',true);
 $p=is_array($p)?$p:array();
 $out=array('code'=>$row['code'],'vendor_id'=>$id,'disabled'=>(bool)get_user_meta($id,'_disable_vendor',true),'banner_id'=>$p['banner']??null,
 'banner_url'=>!empty($p['banner'])?wp_get_attachment_url((int)$p['banner']):null,'banner_meta'=>!empty($p['banner'])?wp_get_attachment_metadata((int)$p['banner']):null,
 'shipping_settings'=>$p['shipping']??null,'shipping_keys'=>array_values(array_filter(array_keys($p),static fn($k)=>stripos($k,'ship')!==false)));
 echo 'VENDOR_CURRENT='.wp_json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
}
$pages=array(
 'isabel-obrador'=>'https://chocolatesartesanosisabel.com/visitas-obrador-chocolate-artesano/',
 'isabel-productos'=>'https://chocolatesartesanosisabel.com/nuestros-productos/',
 'isabel-home'=>'https://chocolatesartesanosisabel.com/',
 'latitudes-home'=>'https://latitudes.es/',
 'latitudes-premium'=>'https://latitudes.es/premium/',
);
foreach($pages as $name=>$url){
 $r=wp_remote_get($url,array('timeout'=>26,'redirection'=>3,'user-agent'=>'Mozilla/5.0 EMDO Publisher'));
 $status=is_wp_error($r)?0:wp_remote_retrieve_response_code($r);
 $html=is_wp_error($r)?'':wp_remote_retrieve_body($r);
 $rows=array();
 if($status===200 && stripos($html,'</html>')!==false){
  $dom=new DOMDocument();$prev=libxml_use_internal_errors(true);
  $ok=$dom->loadHTML('<?xml encoding="UTF-8" ?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
  libxml_clear_errors();libxml_use_internal_errors($prev);
  if($ok){
   $xpath=new DOMXPath($dom);
   foreach($xpath->query('//img') as $node){
    $src=trim($node->getAttribute('data-src'));
    if(!$src)$src=trim($node->getAttribute('src'));
    $srcset=$node->getAttribute('data-srcset')?:$node->getAttribute('srcset');
    if(stripos($src,'data:')===0 && preg_match('~(https?://[^\s,]+)\s+\d+w~',$srcset,$m))$src=$m[1];
    if(!$src)continue;
    $row=array('src'=>$src,'alt'=>$node->getAttribute('alt'),'class'=>mb_substr($node->getAttribute('class'),0,100),
    'w'=>$node->getAttribute('width'),'h'=>$node->getAttribute('height'));
    $key=hash('sha256',$src);$rows[$key]=$row;
    if(count($rows)>=65)break;
   }
   foreach($xpath->query('//meta[@property="og:image"] | //meta[@name="twitter:image"]') as $node){
    $src=$node->getAttribute('content');$rows[hash('sha256',$src)]=array('src'=>$src,'alt'=>'og:image','w'=>'','h'=>'');
   }
   foreach($xpath->query('//*[@style]') as $node){
    if(preg_match_all('~url\(\s*["\x27]?([^)\x27"]+)~i',$node->getAttribute('style'),$matches)){
     foreach(array_slice($matches[1],0,4) as $src){if(preg_match('~\.(jpe?g|png|webp)(\?|$)~i',$src))$rows[hash('sha256',$src)]=array('src'=>$src,'alt'=>'background','w'=>'','h'=>'');}
    }
   }
  }
 }
 echo 'VENDOR_IMAGE_CANDIDATES='.wp_json_encode(array('page'=>$name,'status'=>$status,'len'=>strlen($html),'found'=>count($rows),
 'images'=>array_values($rows)),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR)."\n";
}
