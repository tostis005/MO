<?php
/**
 * Complete four pending, source-verified Pajarete listings only.
 * Phase prepare -> import -> verify; no other vendor or product touched.
 * Requires official Woo Store API exact matches for title, price, stock, photos,
 * complete descriptions, and simple product type before any new publication.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('WooCommerce')||
  !class_exists('MDO_Database')||!class_exists('MDO_Woo_Importer')||
  !class_exists('MDO_Connector_Quesos_Pajarete'))throw new RuntimeException('Woo/EMDO CLI required');
foreach(['siteurl','home'] as $opt){$host=strtolower((string)wp_parse_url((string)get_option($opt),PHP_URL_HOST));
  if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Production only');}
global $wpdb;
$sup=MDO_Database::table('suppliers');$source=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$sup} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if(!$s||(int)$s['vendor_user_id']!==4974||(int)$s['active']!==1||
  get_user_meta(4974,'_disable_vendor',true)||get_user_meta(4974,'_wcfm_store_offline',true))
    throw new RuntimeException('Inactive/unexpected Pajarete vendor');
$ids=[1080,1081,1082,1083];
$mode=getenv('PAJARETE_FOUR_PHASE')?:'verify';
if(!in_array($mode,['prepare','import','verify'],true))throw new RuntimeException('Bad phase');
$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$source} WHERE supplier_id=%d AND id IN (1080,1081,1082,1083) ORDER BY id",(int)$s['id']),ARRAY_A);
if(count($rows)!==4)throw new RuntimeException('Unexpected pending rows');
$hold=wc_get_product(21483);
if(!$hold||$hold->get_status()!=='draft'||$hold->get_catalog_visibility()!=='hidden')
  throw new RuntimeException('Photo review product must stay hidden');
$backup_key='_mdo_pajarete_four_new_products_backup_20261024';
if($mode==='prepare'){
    foreach($rows as $r){
        $id=(int)$r['id'];
        if((int)$r['wc_product_id'])throw new RuntimeException('Source is already linked: '.$id);
        if((string)$r['status']!=='pending')throw new RuntimeException('Not pending: '.$id);
    }
    $prepared=[];
    foreach($rows as $r){
        $id=(int)$r['id'];$old=json_decode((string)$r['source_payload'],true);
        if(!is_array($old)||empty($old['title']))throw new RuntimeException('No original payload '.$id);
        $url=(string)$r['source_url'];$path=trim((string)wp_parse_url($url,PHP_URL_PATH),'/');
        $domain=strtolower((string)wp_parse_url($url,PHP_URL_HOST));
        if(!in_array($domain,['quesospajarete.com','www.quesospajarete.com'],true)||
          !preg_match('~^producto/([a-z0-9-]+)$~',$path,$match)||
          MDO_Connector_Quesos_Pajarete::excluded($url,(string)$old['title'])) {
            throw new RuntimeException('Disallowed product '.$id);
        }
        if(!empty($old['sku'])&&wc_get_product_id_by_sku((string)$old['sku']))
            throw new RuntimeException('SKU already exists in Woo '.$id);
        $api_url='https://quesospajarete.com/wp-json/wc/store/v1/products?slug='.rawurlencode($match[1]);
        $res=wp_remote_get($api_url,['timeout'=>40,'redirection'=>2,'headers'=>['Accept'=>'application/json']]);
        if(is_wp_error($res)||200!==(int)wp_remote_retrieve_response_code($res))
            throw new RuntimeException('Official API unavailable for '.$id);
        $api=json_decode((string)wp_remote_retrieve_body($res),true);
        if(!is_array($api)||count($api)!==1||!is_array($api[0]))
            throw new RuntimeException('Non-unique product API for '.$id);
        $item=$api[0];
        if((string)($item['type']??'')!=='simple'||!$item['is_purchasable']||
          !$item['is_in_stock']||
          trim((string)wp_parse_url((string)($item['permalink']??''),PHP_URL_PATH),'/')!==$path)
            throw new RuntimeException('Product status/identity changed '.$id);
        $minor=(int)($item['prices']['currency_minor_unit']??2);
        if($minor!==2||!is_numeric($item['prices']['price']??null))throw new RuntimeException('API price missing '.$id);
        $api_price=((float)$item['prices']['price'])/100;
        if(!isset($old['price'])||abs((float)$old['price']-$api_price)>0.005||$api_price<=0)
            throw new RuntimeException('Unexpected product price '.$id);
        $long=wp_kses_post((string)($item['description']??''));
        $short=wp_kses_post((string)($item['short_description']??''));
        if(mb_strlen(trim(wp_strip_all_tags($long)))<90||
           mb_strlen(trim(wp_strip_all_tags($short)))<25)
            throw new RuntimeException('Missing official product descriptions '.$id);
        $imgs=[];
        foreach((array)($item['images']??[]) as $image){
            if(!is_array($image))continue;
            $src=esc_url_raw((string)($image['src']??''));
            $image_host=strtolower((string)wp_parse_url($src,PHP_URL_HOST));
            $file=(string)wp_parse_url($src,PHP_URL_PATH);
            if(in_array($image_host,['quesospajarete.com','www.quesospajarete.com'],true)&&
              str_starts_with($file,'/wp-content/uploads/')&&preg_match('~\\.(?:png|jpe?g|webp)$~i',$file))$imgs[$src]=$src;
        }
        if(!$imgs||count($imgs)>12)throw new RuntimeException('Invalid source gallery '.$id);
        $data=$old;
        $data['description']=$long;
        $data['short_description']=$short;
        $data['images']=array_values($imgs);
        $data['image_count']=count($data['images']);
        $data['source_api_product_id']=(int)$item['id'];
        $data['source_categories']=[];
        foreach((array)($item['categories']??[]) as $category)
            if(is_array($category)&&!empty($category['name']))
                $data['source_categories'][]=['name'=>sanitize_text_field((string)$category['name']),
                    'slug'=>sanitize_title((string)($category['slug']??''))];
        if(!$data['source_categories'])throw new RuntimeException('No official category '.$id);
        $data['stock_status']='instock';
        $data['price']=$api_price;
        if(isset($data['regular_price'])&&is_numeric($data['regular_price']) &&
           abs((float)$data['regular_price']-$api_price)>0.005)
            throw new RuntimeException('Regular price inconsistency '.$id);
        $without=$data;unset($without['source_hash']);
        $data['source_hash']=hash('sha256',wp_json_encode($without,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        $prepared[$id]=$data;
    }
    if(!get_option($backup_key,false)){
        $b=[];
        foreach($rows as $r)$b[(int)$r['id']]=[
            'payload'=>$r['source_payload'],'hash'=>$r['source_hash'],
            'status'=>$r['status'],'wc_product_id'=>$r['wc_product_id']
        ];
        if(!add_option($backup_key,$b,'','no'))throw new RuntimeException('Backup failed');
    }
    foreach($prepared as $id=>$data){
        $u=$wpdb->update($source,[
            'source_payload'=>wp_json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'source_hash'=>$data['source_hash'],
            'status'=>'active',
            'last_error'=>''
        ],['id'=>$id,'supplier_id'=>(int)$s['id']]);
        if($u===false)throw new RuntimeException('Source preparation failed '.$id);
    }
    echo 'PAJARETE_FOUR_PREPARED='.wp_json_encode(['count'=>count($prepared),
        'ids'=>array_keys($prepared),'only_verified_simple_products'=>true],JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}
if($mode==='import'){
    $requested=(int)(getenv('PAJARETE_FOUR_SOURCE_ID')?:0);
    if(!in_array($requested,$ids,true))throw new RuntimeException('Not one of the four approved source rows');
    $r=null;foreach($rows as $row)if((int)$row['id']===$requested)$r=$row;
    if(!$r||$r['status']!=='active')throw new RuntimeException('Source not prepared');
    $data=json_decode((string)$r['source_payload'],true);
    if(!is_array($data)||empty($data['source_api_product_id'])||count((array)($data['images']??[]))<1)
       throw new RuntimeException('Payload not verified');
    $pid=MDO_Woo_Importer::import_source_product($requested);
    $p=wc_get_product($pid);
    if(!$p || (int)get_post_field('post_author',$pid)!==4974 || !is_numeric($p->get_regular_price()) ||
      abs((float)$p->get_regular_price()-(float)$data['price'])>0.005 ||
      !trim(wp_strip_all_tags($p->get_description()))||
      !trim(wp_strip_all_tags($p->get_short_description()))){
        throw new RuntimeException('Incomplete new product '.$requested);
    }
    $map=json_decode((string)get_post_meta($pid,'_emdo_image_map',true),true);$map=is_array($map)?$map:[];
    $img_ids=array_unique(array_map('intval',array_merge([$p->get_image_id()],$p->get_gallery_image_ids())));
    foreach(array_values(array_unique((array)$data['images'])) as $url){
        $aid=(int)($map[$url]??0);
        if(!$aid||!in_array($aid,$img_ids,true)||!get_attached_file($aid)||!is_file(get_attached_file($aid)))
            throw new RuntimeException('Missing official image after import for '.$requested);
    }
    $cats=wp_get_post_terms($pid,'product_cat',['fields'=>'slugs']);
    if(is_wp_error($cats)||!$cats||in_array('sin-categorizar',$cats,true))
        throw new RuntimeException('New product uncategorized '.$requested);
    if($p->get_status()!=='publish'||$p->get_catalog_visibility()!=='visible')
        throw new RuntimeException('Failed deferred safe publication of '.$requested);
    echo 'PAJARETE_FOUR_IMPORTED='.wp_json_encode(['source_id'=>$requested,'woo_id'=>$pid,
        'title'=>$p->get_name(),'status'=>$p->get_status(),'price'=>$p->get_regular_price(),
        'photos'=>count($img_ids),'categories'=>$cats],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}
if($mode==='verify'){
    $out=[];
    foreach($rows as $row){
        $pid=(int)$row['wc_product_id'];$p=$pid?wc_get_product($pid):null;
        $payload=json_decode((string)$row['source_payload'],true);$payload=is_array($payload)?$payload:[];
        if(!$p||$row['status']!=='active'||$p->get_status()!=='publish'||
          $p->get_catalog_visibility()!=='visible'||(int)get_post_field('post_author',$pid)!==4974)
            throw new RuntimeException('New product missing or non-public '.$row['id']);
        $img_ids=array_unique(array_map('intval',array_merge([$p->get_image_id()],$p->get_gallery_image_ids())));
        if(count($img_ids)<count(array_unique((array)($payload['images']??[]))))
            throw new RuntimeException('Incomplete media gallery '.$pid);
        $out[]=['source_id'=>(int)$row['id'],'woo_id'=>$pid,'status'=>$p->get_status(),
            'photos'=>count($img_ids),'short_chars'=>mb_strlen(wp_strip_all_tags($p->get_short_description())),
            'long_chars'=>mb_strlen(wp_strip_all_tags($p->get_description()))];
    }
    echo 'PAJARETE_FOUR_VERIFIED='.wp_json_encode(['products'=>$out,'count'=>count($out),
      'prior_photo_review_still_hidden'=>true],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}
