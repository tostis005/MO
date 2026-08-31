<?php
/** Safe bilingual publisher for nutrition priority articles 51-55. */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }

$seed_dir = getenv('EMDO_NUTRITION_5155_SEED_DIR');
$token = getenv('EMDO_NUTRITION_5155_TOKEN');
if ( ! is_string($seed_dir) || '' === trim($seed_dir) || ! is_dir($seed_dir) ) { WP_CLI::error('Invalid seed directory.'); }
if ( ! is_string($token) || '' === trim($token) ) { WP_CLI::error('Missing batch token.'); }
$seed_dir = rtrim($seed_dir, '/\\');

function emdo_5155_articles(string $dir): array {
    $base=$dir.'/content-seeds/nutrition-priority-51-55-010276.part';
    $encoded='';
    for($i=1;$i<=3;$i++){
        $file=$base.$i;
        if(!is_readable($file)){ WP_CLI::error('Missing payload part '.$i); }
        $encoded .= trim((string)file_get_contents($file));
    }
    $expected_sha='5382f18ecfb7ff6890d9e5a83db43d1b6661f41cb30e7b1ebbe608c8e8afb869';
    if(hash('sha256',$encoded)!==$expected_sha){ WP_CLI::error('Payload SHA256 mismatch.'); }
    $gz=base64_decode($encoded,true); if(false===$gz){ WP_CLI::error('Invalid Base64 payload.'); }
    $json=gzdecode($gz); if(false===$json){ WP_CLI::error('Cannot decompress payload.'); }
    $data=json_decode($json,true);
    if(!is_array($data) || 5!==count($data)){ WP_CLI::error('Expected exactly five articles.'); }
    $expected=array(
        'nutrientes-chorizo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
        'cuanta-proteina-tiene-chorizo-iberico',
        'cuanto-hierro-tiene-chorizo-iberico',
        'grasa-chorizo-iberico-saturada-monoinsaturada-poliinsaturada',
        'chorizo-iberico-vs-salchichon-iberico-diferencias-nutricionales'
    );
    $required=array('slug','title','excerpt','content','en_slug','en_title','en_excerpt','en_content','category_slug','category_name','product_cat_slugs','related_heading','en_related_heading');
    foreach($data as $i=>$a){
        if(($a['slug']??'')!==$expected[$i]){ WP_CLI::error('Unexpected article order/slug.'); }
        foreach($required as $k){
            if(!array_key_exists($k,$a) || (is_string($a[$k]) && ''===trim($a[$k]))){ WP_CLI::error('Missing '.$k.' in '.$a['slug']); }
        }
        if('embutidos-y-curados'!==(string)$a['category_slug']){ WP_CLI::error('Unexpected category '.$a['slug']); }
        if(false===strpos((string)$a['content'],'<!-- EMDO_RELATED_PRODUCTS -->') || false===strpos((string)$a['en_content'],'<!-- EMDO_RELATED_PRODUCTS -->')){
            WP_CLI::error('Related products placeholder missing: '.$a['slug']);
        }
    }
    return $data;
}
function emdo_5155_category_id(array $a): int {
    $t=get_category_by_slug((string)$a['category_slug']);
    if($t instanceof WP_Term){ return (int)$t->term_id; }
    $t=get_term_by('name',(string)$a['category_name'],'category');
    return $t instanceof WP_Term ? (int)$t->term_id : 0;
}
function emdo_5155_product_slugs(array $a): array {
    $out=array();
    foreach((array)$a['product_cat_slugs'] as $slug){
        $t=get_term_by('slug',(string)$slug,'product_cat');
        if(!$t instanceof WP_Term){ WP_CLI::error('WooCommerce product category not found: '.$slug.' for '.$a['slug']); }
        $out[]=(string)$t->slug;
    }
    return array_values(array_unique($out));
}
function emdo_5155_render(array $a,array $product_slugs,bool $en=false): string {
    $content=$en?(string)$a['en_content']:(string)$a['content'];
    $heading=$en?(string)$a['en_related_heading']:(string)$a['related_heading'];
    $block='<h2>'.esc_html($heading).'</h2>'."\n".'[products category="'.esc_attr(implode(',',$product_slugs)).'" limit="4" columns="4" orderby="date" order="DESC"]';
    return str_replace('<!-- EMDO_RELATED_PRODUCTS -->',$block,$content);
}
function emdo_5155_image_id(): int {
    $ids=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_wp_attachment_image_alt','meta_value'=>'Imagen provisional del blog de El Mercado de Origen'));
    if(!empty($ids)){ return (int)$ids[0]; }
    $sample=get_page_by_path('nutrientes-legumbres-proteinas-fibra-hierro-vitaminas-minerales',OBJECT,'post');
    return $sample instanceof WP_Post ? (int)get_post_thumbnail_id($sample->ID) : 0;
}
function emdo_5155_save_en(int $id,array $a,string $content): void {
    update_post_meta($id,'_en_US_post_title',(string)$a['en_title']);
    update_post_meta($id,'_en_US_post_name',(string)$a['en_slug']);
    update_post_meta($id,'_en_US_post_excerpt',(string)$a['en_excerpt']);
    update_post_meta($id,'_en_US_post_content',$content);
    update_post_meta($id,'_en_US_published','1');
}
function emdo_5155_diff_existing(WP_Post $p,array $a,int $cat_id,int $image_id,string $es,string $en): array {
    $bad=array();
    if('publish'!==$p->post_status){$bad[]='status';}
    if(trim((string)$p->post_title)!==trim((string)$a['title'])){$bad[]='title';}
    if(trim((string)$p->post_name)!==trim((string)$a['slug'])){$bad[]='slug';}
    if(trim((string)$p->post_excerpt)!==trim((string)$a['excerpt'])){$bad[]='excerpt';}
    if(trim((string)$p->post_content)!==trim($es)){$bad[]='content';}
    if(!has_category($cat_id,$p->ID)){$bad[]='category';}
    if((int)get_post_thumbnail_id($p->ID)!==$image_id){$bad[]='thumbnail';}
    $checks=array(
        '_en_US_post_title'=>(string)$a['en_title'],
        '_en_US_post_name'=>(string)$a['en_slug'],
        '_en_US_post_excerpt'=>(string)$a['en_excerpt'],
        '_en_US_post_content'=>$en,
        '_en_US_published'=>'1'
    );
    foreach($checks as $k=>$v){ if(trim((string)get_post_meta($p->ID,$k,true))!==trim($v)){ $bad[]=$k; } }
    return $bad;
}

$articles=emdo_5155_articles($seed_dir);
$image_id=emdo_5155_image_id();
if($image_id<=0){ WP_CLI::error('Generic provisional image not found.'); }
$rows=array(); $errors=array();

foreach($articles as $a){
    $slug=(string)$a['slug'];
    $cat_id=emdo_5155_category_id($a); if($cat_id<=0){ WP_CLI::error('Blog category not found: '.$a['category_slug']); }
    $product_slugs=emdo_5155_product_slugs($a);
    $es=emdo_5155_render($a,$product_slugs,false);
    $en=emdo_5155_render($a,$product_slugs,true);
    $existing=get_page_by_path($slug,OBJECT,'post');
    $adopted=false;
    if($existing instanceof WP_Post){
        $bad=emdo_5155_diff_existing($existing,$a,$cat_id,$image_id,$es,$en);
        if(!empty($bad)){ WP_CLI::error('Safety stop: existing target differs from expected article: '.$slug.' id='.$existing->ID.' fields='.implode(',',$bad)); }
        $id=(int)$existing->ID; $adopted=true;
    } else {
        $r=wp_insert_post(wp_slash(array(
            'post_type'=>'post','post_status'=>'publish','post_title'=>(string)$a['title'],'post_name'=>$slug,
            'post_excerpt'=>(string)$a['excerpt'],'post_content'=>$es,'post_category'=>array($cat_id),
            'comment_status'=>'closed','ping_status'=>'closed'
        )),true);
        if(is_wp_error($r)||(int)$r<=0){ WP_CLI::error('Could not publish '.$slug); }
        $id=(int)$r;
        set_post_thumbnail($id,$image_id);
        emdo_5155_save_en($id,$a,$en);
        update_post_meta($id,'_emdo_nutrition_batch_token',$GLOBALS['token']);
    }
    $p=get_post($id);
    if(!$p instanceof WP_Post || 'publish'!==$p->post_status){$errors[]='Not published: '.$slug;}
    if(!has_category($cat_id,$id)){$errors[]='Category missing: '.$slug;}
    if((int)get_post_thumbnail_id($id)<=0){$errors[]='Image missing: '.$slug;}
    if('1'!==(string)get_post_meta($id,'_en_US_published',true)){$errors[]='English flag missing: '.$slug;}
    $rows[]=array(
        'id'=>$id,'slug'=>$slug,'en_slug'=>(string)$a['en_slug'],'title'=>(string)$a['title'],'en_title'=>(string)$a['en_title'],
        'category_slug'=>(string)$a['category_slug'],'permalink'=>(string)get_permalink($id),
        'en_permalink'=>(string)home_url('/en/'.trim((string)$a['en_slug'],'/').'/'),
        'thumbnail_id'=>(int)get_post_thumbnail_id($id),'product_cats'=>$product_slugs,'adopted'=>$adopted
    );
}
flush_rewrite_rules(false); wp_cache_flush();
$out=array('verified'=>empty($errors)&&5===count($rows),'count'=>count($rows),'posts'=>$rows,'errors'=>$errors);
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$out['verified']){ WP_CLI::error('Batch 51-55 verification failed.'); }
