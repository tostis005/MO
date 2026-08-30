<?php
/** Publish bilingual nutrition priority articles 41-45 (EVOO nutrition FAQs). */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$seed_dir = getenv( 'EMDO_NUTRITION_4145_SEED_DIR' );
if ( ! is_string( $seed_dir ) || '' === trim( $seed_dir ) || ! is_dir( $seed_dir ) ) { WP_CLI::error( 'Invalid seed directory.' ); }
$seed_dir = rtrim( $seed_dir, '/\\' );
$batch_token = getenv( 'EMDO_BATCH_TOKEN' );
if ( ! is_string( $batch_token ) || ! preg_match( '/^[A-Za-z0-9._:-]{1,128}$/', $batch_token ) ) { WP_CLI::error( 'Invalid batch token.' ); }

function emdo_4145_articles( string $dir ): array {
    $files=glob($dir.'/content-seeds/nutrition-priority-41-45-010274-v1.part*');
    sort($files,SORT_STRING);
    if(3!==count($files)){ WP_CLI::error('Expected three payload parts.'); }
    $encoded=''; foreach($files as $file){ if(!is_readable($file)){ WP_CLI::error('Unreadable payload part.'); } $encoded.=trim((string)file_get_contents($file)); }
    if(hash('sha256',$encoded)!=='2dfecb9ff19c2e939afb96d8a931f1f9a7b02c7f0c1334b10104689d66749e0a'){ WP_CLI::error('Payload integrity check failed.'); }
    $gz=base64_decode($encoded,true); if(false===$gz){ WP_CLI::error('Invalid Base64 payload.'); }
    $json=gzdecode($gz); if(false===$json){ WP_CLI::error('Cannot decompress payload.'); }
    $data=json_decode($json,true);
    if(!is_array($data)||5!==count($data)){ WP_CLI::error('Expected exactly five articles.'); }
    $required=array('slug','title','excerpt','content','en_slug','en_title','en_excerpt','en_content','category_slug','category_name','product_cat_slugs','related_heading','en_related_heading');
    foreach($data as $a){
        foreach($required as $k){
            if(!isset($a[$k]) || (is_string($a[$k]) && ''===trim($a[$k]))){ WP_CLI::error('Missing '.$k.' in '.($a['slug']??'unknown')); }
        }
        if('aceites'!==(string)$a['category_slug']){ WP_CLI::error('Unexpected category: '.$a['category_slug']); }
    }
    return $data;
}
function emdo_4145_category_id(array $a): int {
    $t=get_category_by_slug((string)$a['category_slug']);
    if($t instanceof WP_Term){ return (int)$t->term_id; }
    $t=get_term_by('name',(string)$a['category_name'],'category');
    return $t instanceof WP_Term ? (int)$t->term_id : 0;
}
function emdo_4145_product_slugs(array $a): array {
    $out=array();
    foreach((array)$a['product_cat_slugs'] as $slug){
        $t=get_term_by('slug',(string)$slug,'product_cat');
        if(!$t instanceof WP_Term){ WP_CLI::error('WooCommerce product category not found: '.$slug.' for '.$a['slug']); }
        $out[]=$t->slug;
    }
    return array_values(array_unique($out));
}
function emdo_4145_render(array $a,array $product_slugs,bool $en=false): string {
    $content=$en?(string)$a['en_content']:(string)$a['content'];
    $heading=$en?(string)$a['en_related_heading']:(string)$a['related_heading'];
    $block='<h2>'.esc_html($heading).'</h2>'."\n".'[products category="'.esc_attr(implode(',',$product_slugs)).'" limit="4" columns="4" orderby="date" order="DESC"]';
    return str_replace('<!-- EMDO_RELATED_PRODUCTS -->',$block,$content);
}
function emdo_4145_image_id(): int {
    $ids=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_wp_attachment_image_alt','meta_value'=>'Imagen provisional del blog de El Mercado de Origen'));
    if(!empty($ids)){ return (int)$ids[0]; }
    $sample=get_page_by_path('nutrientes-legumbres-proteinas-fibra-hierro-vitaminas-minerales',OBJECT,'post');
    return $sample instanceof WP_Post ? (int)get_post_thumbnail_id($sample->ID) : 0;
}
function emdo_4145_save_en(int $id,array $a,string $content): void {
    update_post_meta($id,'_en_US_post_title',(string)$a['en_title']);
    update_post_meta($id,'_en_US_post_name',(string)$a['en_slug']);
    update_post_meta($id,'_en_US_post_excerpt',(string)$a['en_excerpt']);
    update_post_meta($id,'_en_US_post_content',$content);
    update_post_meta($id,'_en_US_published','1');
}
function emdo_4145_existing_post_id(string $slug): int {
    global $wpdb;
    $id=$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='post' AND post_name=%s LIMIT 1",$slug));
    return $id ? (int)$id : 0;
}
function emdo_4145_assert_existing_matches(int $id,array $a,int $cat_id,string $es,string $en): void {
    $p=get_post($id);
    $slug=(string)$a['slug'];
    if(!$p instanceof WP_Post){ WP_CLI::error('Existing target could not be loaded: '.$slug.' id='.$id); }
    $mismatches=array();
    if('publish'!==(string)$p->post_status){ $mismatches[]='status'; }
    if(trim((string)$p->post_title)!==trim((string)$a['title'])){ $mismatches[]='title'; }
    if((string)$p->post_name!==$slug){ $mismatches[]='slug'; }
    if(trim((string)$p->post_excerpt)!==trim((string)$a['excerpt'])){ $mismatches[]='excerpt'; }
    if(trim((string)$p->post_content)!==trim($es)){ $mismatches[]='content'; }
    if(!has_category($cat_id,$id)){ $mismatches[]='category'; }
    if((int)get_post_thumbnail_id($id)<=0){ $mismatches[]='thumbnail'; }
    if('1'!==(string)get_post_meta($id,'_en_US_published',true)){ $mismatches[]='en_published'; }
    if(trim((string)get_post_meta($id,'_en_US_post_title',true))!==trim((string)$a['en_title'])){ $mismatches[]='en_title'; }
    if(trim((string)get_post_meta($id,'_en_US_post_name',true))!==trim((string)$a['en_slug'])){ $mismatches[]='en_slug'; }
    if(trim((string)get_post_meta($id,'_en_US_post_excerpt',true))!==trim((string)$a['en_excerpt'])){ $mismatches[]='en_excerpt'; }
    if(trim((string)get_post_meta($id,'_en_US_post_content',true))!==trim($en)){ $mismatches[]='en_content'; }
    if(!empty($mismatches)){ WP_CLI::error('Safety stop: existing target differs from expected article: '.$slug.' id='.$id.' fields='.implode(',',$mismatches)); }
}

$articles=emdo_4145_articles($seed_dir); $image_id=emdo_4145_image_id();
if($image_id<=0){ WP_CLI::error('Generic provisional image not found.'); }
$rows=array(); $errors=array();
foreach($articles as $a){
    $slug=(string)$a['slug'];
    $cat_id=emdo_4145_category_id($a); if($cat_id<=0){ WP_CLI::error('Blog category not found: '.$a['category_slug']); }
    $product_slugs=emdo_4145_product_slugs($a);
    $es=emdo_4145_render($a,$product_slugs,false); $en=emdo_4145_render($a,$product_slugs,true);
    if(false!==strpos($es,'EMDO_RELATED_PRODUCTS')||false!==strpos($en,'EMDO_RELATED_PRODUCTS')){ WP_CLI::error('Related products placeholder not rendered: '.$slug); }

    $existing_id=emdo_4145_existing_post_id($slug);
    $adopted=false;
    if($existing_id>0){
        emdo_4145_assert_existing_matches($existing_id,$a,$cat_id,$es,$en);
        $id=$existing_id;
        $adopted=true;
        WP_CLI::log('Existing article matches expected payload; adopting safely: '.$slug.' id='.$id);
    }else{
        $r=wp_insert_post(wp_slash(array(
            'post_type'=>'post','post_status'=>'publish','post_title'=>(string)$a['title'],'post_name'=>$slug,
            'post_excerpt'=>(string)$a['excerpt'],'post_content'=>$es,'post_category'=>array($cat_id),
            'comment_status'=>'closed','ping_status'=>'closed'
        )),true);
        if(is_wp_error($r)||(int)$r<=0){ WP_CLI::error('Could not publish '.$slug); }
        $id=(int)$r;
        update_post_meta($id,'_emdo_batch_token',$batch_token);
        if($batch_token!==(string)get_post_meta($id,'_emdo_batch_token',true)){
            wp_delete_post($id,true);
            WP_CLI::error('Could not mark new post for safe rollback: '.$slug);
        }
        set_post_thumbnail($id,$image_id); emdo_4145_save_en($id,$a,$en);
    }

    $p=get_post($id);
    if(!$p instanceof WP_Post || 'publish'!==$p->post_status){ $errors[]='Not published: '.$slug; }
    if(!has_category($cat_id,$id)){ $errors[]='Category missing: '.$slug; }
    if((int)get_post_thumbnail_id($id)<=0){ $errors[]='Image missing: '.$slug; }
    if('1'!==(string)get_post_meta($id,'_en_US_published',true)){ $errors[]='English flag missing: '.$slug; }
    if(trim((string)get_post_meta($id,'_en_US_post_title',true))!==trim((string)$a['en_title'])){ $errors[]='English title mismatch: '.$slug; }
    if(trim((string)get_post_meta($id,'_en_US_post_name',true))!==trim((string)$a['en_slug'])){ $errors[]='English slug mismatch: '.$slug; }
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
if(!$out['verified']){ WP_CLI::error('Batch 41-45 verification failed.'); }
