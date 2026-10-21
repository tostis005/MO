<?php
/**
 * Plugin Name: EMDO — Coffee, Mango, Avocado and Citrus Editorial Clusters
 * Description: Small product/guide links for live editorial clusters, without modifying articles or the established chocolate cluster.
 * Version: 2026.10.21.1
 */
if (!defined('ABSPATH')) exit;

function emdo_cluster21_is_en(): bool {
    $path = function_exists('mdoer_public_path') ? (string)mdoer_public_path() : (string)wp_parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);
    return (bool)preg_match('~^/en(?:/|$)~i', $path);
}
function emdo_cluster21_root(): string {
    return untrailingslashit((string)preg_replace('~/en/?$~i','',untrailingslashit((string)get_option('home'))));
}
function emdo_cluster21_posts(): array {
    static $cached=null;
    if(is_array($cached))return $cached;
    $posts=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>40,
        'meta_query'=>[['key'=>'_emdo_newprod_plan_id','compare'=>'EXISTS']],'suppress_filters'=>true]);
    $cached=[];
    foreach($posts as $post){
        $num=(int)get_post_meta($post->ID,'_emdo_newprod_plan_id',true);
        if($num>=1&&$num<=24)$cached[$num]=$post;
    }
    return $cached;
}
function emdo_cluster21_url_post(WP_Post $post,bool $english): string {
    $base=emdo_cluster21_root();
    if($english){
        if((string)get_post_meta($post->ID,'_en_US_published',true)!=='1')return '';
        $slug=(string)get_post_meta($post->ID,'_en_US_post_name',true);
        return $slug!==''?$base.'/en/'.sanitize_title($slug).'/':'';
    }
    return $base.'/'. $post->post_name .'/';
}
function emdo_cluster21_url_product(WC_Product $product,bool $english): string {
    $base=emdo_cluster21_root();
    $id=$product->get_id();
    if($english){
        if((string)get_post_meta($id,'_en_US_published',true)!=='1')return '';
        $slug=sanitize_title((string)get_post_meta($id,'_en_US_post_name',true));
        return $slug!==''?$base.'/en/product/'.$slug.'/':'';
    }
    return (string)get_permalink($id);
}
function emdo_cluster21_article_title(WP_Post $post,bool $english): string {
    if($english){
        $title=(string)get_post_meta($post->ID,'_en_US_post_title',true);
        if($title!=='')return $title;
    }
    return $post->post_title;
}
function emdo_cluster21_group(int $n): string {
    if($n>=1&&$n<=9)return 'cafe';
    if($n===10)return 'chocolate';
    if($n>=11&&$n<=14)return 'aguacate';
    if($n>=15&&$n<=17)return 'mango';
    if($n>=18&&$n<=20)return 'citricos';
    return $n>=21&&$n<=24?'frutas':'';
}
function emdo_cluster21_related(int $n): array {
    $map=[
        1=>[2,3],2=>[1,9],3=>[1,6],4=>[2,8],5=>[2,9],6=>[3,7],7=>[9,6],8=>[4,9],9=>[7,8],
        10=>[],11=>[12,13],12=>[11,14],13=>[11,14],14=>[12,11],
        15=>[16,17],16=>[15,17],17=>[15,16],
        18=>[19,20],19=>[18,20],20=>[18,19],
        21=>[22,23],22=>[24,21],23=>[15,21],24=>[22,21]
    ];
    return $map[$n]??[];
}
function emdo_cluster21_is_fruit_cat(int $pid): bool {
    $slugs=wp_get_post_terms($pid,'product_cat',['fields'=>'slugs']);
    return !is_wp_error($slugs) && (bool)array_intersect($slugs,['frutas','frutas-tropicales','frutas-del-mundo','packs-y-lotes','aguacates']);
}
function emdo_cluster21_products(string $group,int $seed=0,int $max=2): array {
    if(!function_exists('wc_get_products'))return [];
    static $cache=[];
    if(!isset($cache[$group])){
        $q=['status'=>'publish','stock_status'=>'instock','limit'=>100,'orderby'=>'title','order'=>'ASC'];
        if($group==='cafe')$q['category']=['cafe-de-especialidad'];
        elseif($group==='aguacate')$q['category']=['aguacates'];
        elseif($group==='chocolate')$q['category']=['cremas-de-cacao'];
        else $q['category']=['frutas','frutas-tropicales','frutas-del-mundo','packs-y-lotes'];
        $pool=wc_get_products($q);
        $cache[$group]=[];
        foreach($pool as $p){
            if(!$p instanceof WC_Product || !$p->is_visible() || !$p->is_purchasable() || !$p->is_in_stock())continue;
            $id=$p->get_id();$name=remove_accents(mb_strtolower($p->get_name()));
            if($group==='mango' && (!preg_match('~(?<![a-z])mangos?(?![a-z])~u',$name)||!emdo_cluster21_is_fruit_cat($id)))continue;
            if($group==='citricos' && (!preg_match('~(?<![a-z])(naranjas?|mandarinas?|pomelos?)(?![a-z])~u',$name)||!emdo_cluster21_is_fruit_cat($id)))continue;
            if($group==='frutas' && !preg_match('~frutas?|kiwi|papaya|guayaba|granada|pitahaya|mango|aguacate|chirimoya|mandarina|naranja|cereza|melon|sandia~u',$name))continue;
            $cache[$group][]=$p;
        }
    }
    $list=$cache[$group];$count=count($list);if(!$count)return [];
    $result=[];
    for($i=0;$i<min($max,$count);$i++)$result[]=$list[($seed+$i)%$count];
    return $result;
}
function emdo_cluster21_guide_list(array $numbers,bool $english,int $exclude=0): string {
    $available=emdo_cluster21_posts();$markup='';
    foreach($numbers as $n){
        if($n===$exclude || !isset($available[$n]))continue;
        $post=$available[$n];$url=emdo_cluster21_url_post($post,$english);
        if(!$url)continue;
        $markup.='<li><a href="'.esc_url($url).'">'.esc_html(emdo_cluster21_article_title($post,$english)).'</a></li>';
    }
    return $markup?'<ul>'.$markup.'</ul>':'';
}
function emdo_cluster21_english_products_were_published(string $group,int $seed): array {
    // Avoid linking from an English guide to an unlocalised product route.
    $candidates=emdo_cluster21_products($group,$seed,12);
    return array_values(array_filter($candidates,static fn($p)=>emdo_cluster21_url_product($p,true)!==''));
}
function emdo_cluster21_product_list(string $group,int $seed,bool $english): string {
    $products=$english?emdo_cluster21_english_products_were_published($group,$seed):emdo_cluster21_products($group,$seed,2);
    if(!$products)return '';
    $items='';
    foreach(array_slice($products,0,2) as $product){
        $url=emdo_cluster21_url_product($product,$english);
        if(!$url)continue;
        $title=$english?(string)get_post_meta($product->get_id(),'_en_US_post_title',true):'';
        if($title==='')$title=$product->get_name();
        $items.='<li><a href="'.esc_url($url).'">'.esc_html($title).'</a></li>';
    }
    return $items?'<ul>'.$items.'</ul>':'';
}
// A post-to-post pathway is meaningful even when no stock is available.
// Product recommendations are stock-checked and EN recommendations require a real EN listing.
add_filter('the_content',static function(string $body):string {
    if(is_admin()||is_feed()||!is_singular('post')||!in_the_loop()||!is_main_query())return $body;
    $n=(int)get_post_meta(get_the_ID(),'_emdo_newprod_plan_id',true);
    if(!$n||$n>24||str_contains($body,'data-emdo-editorial-cluster="1"'))return $body;
    $en=emdo_cluster21_is_en();
    $links=emdo_cluster21_guide_list(emdo_cluster21_related($n),$en,$n);
    $group=emdo_cluster21_group($n);
    if($group==='chocolate' && function_exists('emdo_choco_articles')){
        $chosen=[];
        foreach(emdo_choco_articles() as $p){
            $topic=(int)get_post_meta($p->ID,'_emdo_chocolate_topic',true);
            if(in_array($topic,[1,7],true))$chosen[$topic]=$p;
        }
        $rows='';
        foreach([1,7] as $topic){
            if(!isset($chosen[$topic]))continue;
            $p=$chosen[$topic];
            $url=emdo_cluster21_url_post($p,$en);
            if(!$url)continue;
            $rows.='<li><a href="'.esc_url($url).'">'.esc_html(emdo_cluster21_article_title($p,$en)).'</a></li>';
        }
        if($rows)$links='<ul>'.$rows.'</ul>';
    }
    $items=emdo_cluster21_product_list($group,$n*3,$en);
    if(!$links && !$items)return $body;
    $html='<aside class="emdo-editorial-cluster" data-emdo-editorial-cluster="1" aria-label="'.esc_attr($en?'Related reading and products':'Lecturas y productos relacionados').'">';
    if($links)$html.='<div class="emdo-editorial-cluster__reading"><h2>'.esc_html($en?'Keep exploring the subject':'Para profundizar en el tema').'</h2>'.$links.'</div>';
    if($items)$html.='<div class="emdo-editorial-cluster__products"><h2>'.esc_html($en?'Available products':'Productos disponibles').'</h2>'.$items.'</div>';
    return $body."\n".$html.'</aside>';
},45);

// Product-to-editorial. Cocoa already has a 30-guide product widget: only add the missing cream guide.
add_action('woocommerce_after_single_product_summary',static function():void {
    if(is_admin()||!is_product()||!function_exists('wc_get_product'))return;
    $id=(int)get_queried_object_id();$product=wc_get_product($id);if(!$product || $product->get_status()!=='publish')return;
    $terms=wp_get_post_terms($id,'product_cat',['fields'=>'slugs']);
    if(is_wp_error($terms))return;
    $name=remove_accents(mb_strtolower($product->get_name()));
    $group='';
    if(in_array('cafe-de-especialidad',$terms,true))$group='cafe';
    elseif(in_array('aguacates',$terms,true))$group='aguacate';
    elseif(in_array('cremas-de-cacao',$terms,true))$group='chocolate';
    elseif(emdo_cluster21_is_fruit_cat($id)&&preg_match('~(?<![a-z])mangos?(?![a-z])~u',$name))$group='mango';
    elseif(emdo_cluster21_is_fruit_cat($id)&&preg_match('~(?<![a-z])(naranjas?|mandarinas?|pomelos?)(?![a-z])~u',$name))$group='citricos';
    if(!$group)return;
    $ids=[
        'cafe'=>[1,3,2,8],
        'aguacate'=>[11,12,14,13],
        'mango'=>[15,16,17],
        'citricos'=>[18,19,20],
        'chocolate'=>[10]
    ][$group];
    if($group==='cafe')$ids=($id%2)?[1,3,8]:[1,2,3];
    if($group==='aguacate')$ids=($id%2)?[11,12,14]:[11,13,14];
    $en=emdo_cluster21_is_en();
    $links=emdo_cluster21_guide_list(array_slice($ids,0,$group==='chocolate'?1:3),$en);
    if(!$links)return;
    echo '<nav class="emdo-editorial-product-guides" data-emdo-editorial-product-guides="1" aria-label="'.esc_attr($en?'Buying and care guides':'Guías para elegir y conservar').'"><h2>'.esc_html($en?'Guides to help you choose':'Guías para elegir y disfrutar').'</h2>'.$links.'</nav>';
},20);

// Hub pages link readers into the right part of the editorial cluster.
add_action('woocommerce_after_shop_loop',static function():void {
    if(is_admin()||!is_product_category()||is_paged())return;
    $term=get_queried_object();if(!$term instanceof WP_Term)return;
    $hubs=[
        'cafe-de-especialidad'=>[1,2,3,8],
        'aguacates'=>[11,12,13,14],
        'frutas-tropicales'=>[15,16,17,23],
        'frutas'=>[21,22,23,24],
        'cremas-de-cacao'=>[10]
    ];
    $nums=$hubs[$term->slug]??[];
    if(!$nums)return;
    $en=emdo_cluster21_is_en();
    $list=emdo_cluster21_guide_list($nums,$en);
    if(!$list)return;
    echo '<section class="emdo-editorial-category-guides" data-emdo-editorial-category-guides="1"><h2>'.esc_html($en?'Learn before you buy':'Guías de compra y conservación').'</h2>'.$list.'</section>';
},40);
add_action('wp_head',static function():void {
    if(!is_singular(['post','product'])&&!is_product_category())return;
    echo '<style id="emdo-editorial-cluster-style">.emdo-editorial-cluster,.emdo-editorial-product-guides,.emdo-editorial-category-guides{margin:28px 0;padding:18px 0;border-top:1px solid #e1e1e1}.emdo-editorial-cluster ul,.emdo-editorial-product-guides ul,.emdo-editorial-category-guides ul{margin:8px 0 0;padding-left:22px}.emdo-editorial-cluster li,.emdo-editorial-product-guides li,.emdo-editorial-category-guides li{margin:7px 0}.emdo-editorial-cluster h2,.emdo-editorial-product-guides h2,.emdo-editorial-category-guides h2{font-size:clamp(1.1rem,2vw,1.35rem);margin:8px 0}.emdo-editorial-cluster{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:20px}</style>';
},90);
