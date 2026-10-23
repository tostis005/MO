<?php
/**
 * Idempotent scoped repair for Quesos Pajarete content using the official Store API.
 * PHASE prepare|batch|report, exclusively vendor 4974 / supplier code.
 * Never publishes products, changes prices, changes stock, or invents copy.
 */
if (!defined('ABSPATH') || !class_exists('MDO_Database') || !class_exists('MDO_Woo_Importer') || !class_exists('WooCommerce')) {
    throw new RuntimeException('EMDO/WooCommerce required.');
}
foreach (array('home','siteurl') as $option) {
    $host=strtolower((string)wp_parse_url((string)get_option($option), PHP_URL_HOST));
    if(!in_array($host,array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true)){throw new RuntimeException('Production only.');}
}
global $wpdb;
$code='quesos-pajarete';
$state_key='_mdo_pajarete_content_repair_20261023';
$phase=getenv('PAJARETE_REPAIR_PHASE')?:'report';
$suppliers=MDO_Database::table('suppliers');
$source_products=MDO_Database::table('source_products');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1",$code),ARRAY_A);
if(!$supplier){throw new RuntimeException('Pajarete supplier missing.');}
$vendor=(int)$supplier['vendor_user_id'];
if($vendor!==4974 || !get_user_meta($vendor,'_disable_vendor',true)){
    throw new RuntimeException('Vendor mismatch or vendor published: refusing repair.');
}
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,title,status,source_url,source_payload,wc_product_id FROM {$source_products} WHERE supplier_id=%d AND wc_product_id>0 ORDER BY id",(int)$supplier['id']), ARRAY_A);
if(count($rows)!==130){throw new RuntimeException('Expected 130 imported rows; catalogue changed, re-audit first. Found '.count($rows));}
foreach($rows as $row){
    $p=wc_get_product((int)$row['wc_product_id']);
    if(!$p||get_post_field('post_author',$p->get_id())!=$vendor||$p->get_status()==='publish'||
       $p->get_catalog_visibility()!=='hidden'||$row['status']!=='active'){
        throw new RuntimeException('Out-of-scope or live Woo item: '.(int)$row['wc_product_id']);
    }
}
$state=get_option($state_key,array());
$state=is_array($state)?$state:array();
if($phase==='prepare'){
    $api=[];
    for($page=1;$page<=6;$page++){
        $url='https://quesospajarete.com/wp-json/wc/store/v1/products?per_page=100&page='.$page;
        $res=wp_remote_get($url,array('timeout'=>45,'redirection'=>2,'headers'=>array('Accept'=>'application/json'),
            'user-agent'=>'EMDO/'.$GLOBALS['mdo_supplier_sync_version'].' (+https://www.elmercadodeorigen.com/)'));
        if(is_wp_error($res)||200!==(int)wp_remote_retrieve_response_code($res)){
            throw new RuntimeException('Official Store API unavailable on page '.$page);
        }
        $data=json_decode((string)wp_remote_retrieve_body($res),true);
        if(!is_array($data)){throw new RuntimeException('Invalid API data.');}
        foreach($data as $item) {
            if(!is_array($item))continue;
            $link=(string)($item['permalink']??'');
            $host=strtolower((string)wp_parse_url($link,PHP_URL_HOST));
            $path=trim((string)wp_parse_url($link,PHP_URL_PATH),'/');
            if(in_array($host,array('quesospajarete.com','www.quesospajarete.com'),true)
              &&preg_match('~^producto/[a-z0-9-]+$~',$path)){
                $api[$path]=$item;
            }
        }
        if(count($data)<100)break;
    }
    $prepared=[];
    foreach($rows as $row){
        $path=trim((string)wp_parse_url((string)$row['source_url'],PHP_URL_PATH),'/');
        $item=$api[$path]??null;
        if(!$item){throw new RuntimeException('No exact API match: '.$path);}
        $p=json_decode((string)$row['source_payload'],true);
        if(!is_array($p)||empty($p['title'])||!isset($p['price'])||!in_array((string)($p['stock_status']??''),array('instock','outofstock'),true)){
            throw new RuntimeException('Source price/stock incomplete: '.$path);
        }
        if(MDO_Connector_Quesos_Pajarete::excluded((string)$row['source_url'],(string)$p['title'])){
            throw new RuntimeException('Excluded title among imported Woo products: '.$path);
        }
        $long=wp_kses_post((string)($item['description']??''));
        $short=wp_kses_post((string)($item['short_description']??''));
        if(mb_strlen(trim(wp_strip_all_tags($long)))<30){throw new RuntimeException('Missing official long description: '.$path);}
        if(!trim(wp_strip_all_tags($short))){
            $short=esc_html(wp_trim_words(wp_strip_all_tags($long),34,'…'));
        }
        $images=[];
        foreach((array)($item['images']??[]) as $image){
            if(!is_array($image))continue;
            $src=esc_url_raw((string)($image['src']??''));
            $host=strtolower((string)wp_parse_url($src,PHP_URL_HOST));
            $file=(string)wp_parse_url($src,PHP_URL_PATH);
            if(in_array($host,array('quesospajarete.com','www.quesospajarete.com'),true)
             &&str_starts_with($file,'/wp-content/uploads/')
             &&preg_match('~\\.(png|jpe?g|webp)$~i',$file)){$images[$src]=$src;}
        }
        if(!$images){throw new RuntimeException('Missing official photo: '.$path);}
        $cats=[];
        foreach((array)($item['categories']??[]) as $cat){
            if(is_array($cat)&&!empty($cat['name'])){
                $cats[]=array('name'=>sanitize_text_field((string)$cat['name']),
                    'slug'=>sanitize_title((string)($cat['slug']??'')));
            }
        }
        $p['description']=$long;
        $p['short_description']=$short;
        $p['images']=array_slice(array_values($images),0,12);
        $p['image_count']=count($p['images']);
        $p['source_categories']=$cats;
        $p['source_api_product_id']=absint((string)($item['id']??''));
        $without=$p;
        unset($without['source_hash']);
        $p['source_hash']=hash('sha256',wp_json_encode($without,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        $prepared[(int)$row['id']]=$p;
    }
    if(count($prepared)!==130){throw new RuntimeException('Wrong preparation total.');}
    foreach($prepared as $id=>$p){
        $u=$wpdb->update($source_products,array(
          'source_payload'=>wp_json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
          'source_hash'=>$p['source_hash'],
          'last_changed_at'=>current_time('mysql'),
        ),array('id'=>$id,'supplier_id'=>(int)$supplier['id']));
        if(false===$u){throw new RuntimeException('Could not prepare source row '.$id);}
    }
    $state=array('ids'=>array_keys($prepared),'offset'=>0,'errors'=>array(),'prepared'=>time(),'completed'=>array());
    update_option($state_key,$state,false);
    echo 'PAJARETE_REPAIR_PREPARE='.wp_json_encode(array('prepared'=>count($prepared),
        'api_count'=>count($api),'original_price_and_stock_preserved'=>true)).PHP_EOL;
    exit;
}
if(!isset($state['ids'])||count((array)$state['ids'])!==130){throw new RuntimeException('Repair not prepared.');}
if($phase==='batch'){
    $offset=(int)($state['offset']??0);
    $batch=array_slice($state['ids'],$offset,3);
    foreach($batch as $id){
        try{
            $wc_id=MDO_Woo_Importer::import_source_product((int)$id);
            $product=wc_get_product($wc_id);
            if(!$product||!$product->get_image_id()||
                !trim(wp_strip_all_tags($product->get_short_description()))||
                !trim(wp_strip_all_tags($product->get_description()))||
                $product->get_status()!=='draft'||$product->get_catalog_visibility()!=='hidden'){
                throw new RuntimeException('Incomplete cover, text or prelaunch status after import.');
            }
            $state['completed'][(int)$id]=(int)$wc_id;
            unset($state['errors'][(int)$id]);
        } catch(Throwable $e){
            $state['errors'][(int)$id]=mb_substr($e->getMessage(),0,300);
        }
        $state['offset']++;
        update_option($state_key,$state,false);
    }
    echo 'PAJARETE_REPAIR_BATCH='.wp_json_encode(array('offset'=>(int)$state['offset'],
      'total'=>130,'completed'=>count($state['completed']),'errors'=>count($state['errors']),
      'remaining'=>max(0,130-(int)$state['offset']))).PHP_EOL;
    exit;
}
if($phase==='report'){
    $c=array('total'=>count($rows),'with_cover'=>0,'with_cover_file'=>0,'with_short'=>0,'with_long'=>0,
       'without_uncategorized'=>0,'published'=>0,'draft_hidden'=>0,'wrong_vendor'=>0);
    $missing=[];
    foreach($rows as $r){
        $p=wc_get_product((int)$r['wc_product_id']);if(!$p)continue;
        if($p->get_image_id())$c['with_cover']++;
        $cover_file=$p->get_image_id()?get_attached_file($p->get_image_id()):null;
        if($cover_file&&is_file($cover_file))$c['with_cover_file']++;
        if(trim(wp_strip_all_tags($p->get_short_description())))$c['with_short']++;
        if(trim(wp_strip_all_tags($p->get_description())))$c['with_long']++;
        $terms=wp_get_post_terms($p->get_id(),'product_cat',array('fields'=>'slugs'));
        if(!is_wp_error($terms)&&!in_array('sin-categorizar',(array)$terms,true))$c['without_uncategorized']++;
        if($p->get_status()==='publish')$c['published']++;
        if($p->get_status()==='draft'&&$p->get_catalog_visibility()==='hidden')$c['draft_hidden']++;
        if((int)get_post_field('post_author',$p->get_id())!==$vendor)$c['wrong_vendor']++;
        if(!$p->get_image_id()||!trim(wp_strip_all_tags($p->get_short_description()))||
            !trim(wp_strip_all_tags($p->get_description()))){
            $missing[]=array('id'=>$p->get_id(),'name'=>$p->get_name(),
                'image'=>(bool)$p->get_image_id(),
                'short'=>(bool)trim(wp_strip_all_tags($p->get_short_description())),
                'long'=>(bool)trim(wp_strip_all_tags($p->get_description())));
        }
    }
    echo 'PAJARETE_REPAIR_REPORT='.wp_json_encode(array('counts'=>$c,
      'offset'=>(int)($state['offset']??0),'completed'=>count($state['completed']),
      'errors'=>$state['errors'],'missing'=>$missing),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    if($c['published']||$c['wrong_vendor'])throw new RuntimeException('Publication/author safety violation');
    exit;
}
throw new RuntimeException('Unknown phase');
