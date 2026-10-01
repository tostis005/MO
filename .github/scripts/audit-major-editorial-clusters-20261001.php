<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_cluster_norm_20261001( string $text ): string {
    $text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = remove_accents( mb_strtolower( $text, 'UTF-8' ) );
    $text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
    return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
}
function emdo_cluster_words_20261001( string $html ): int {
    $text=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(strip_shortcodes($html))));
    if(''===$text) return 0;
    preg_match_all('/[\p{L}\p{M}]+(?:[’\x{27}’-][\p{L}\p{M}]+)*/u',$text,$m);
    return count($m[0]);
}
function emdo_cluster_paras_20261001( string $html ): array {
    $out=array();
    if(preg_match_all('/<p\b[^>]*>(.*?)<\/p>/isu',$html,$m)){
        foreach($m[1] as $raw){
            $plain=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags($raw)));
            if(mb_strlen($plain,'UTF-8')<100) continue;
            $norm=emdo_cluster_norm_20261001($plain);
            if(count(preg_split('/\s+/u',$norm,-1,PREG_SPLIT_NO_EMPTY)?:array())<15) continue;
            $out[]=array('plain'=>$plain,'norm'=>$norm);
        }
    }
    return $out;
}

$clusters=array(
    'jamones'=>array('blog_slug'=>'jamones-y-paletas','product_slug'=>'jamones-paletas'),
    'aceites'=>array('blog_slug'=>'aceites','product_slug'=>'aceites'),
    'carnes'=>array('blog_slug'=>'carnes','product_slug'=>'carnes'),
    'hortalizas'=>array('blog_slug'=>'hortalizas-y-verduras','product_slug'=>'hortalizas-verduras'),
    'legumbres'=>array('blog_slug'=>'legumbres','product_slug'=>'legumbres'),
    'embutidos'=>array('blog_slug'=>'embutidos-y-curados','product_slug'=>'embutidos-y-curados'),
    'conservas'=>array('blog_slug'=>'conservas','product_slug'=>'conservas'),
    'packs'=>array('blog_slug'=>'packs-y-lotes','product_slug'=>'packs-y-lotes'),
    'quesos'=>array('blog_slug'=>'quesos','product_slug'=>'quesos'),
    'pato'=>array('blog_slug'=>'pato','product_slug'=>'pato'),
);

$out=array();
foreach($clusters as $key=>$cfg){
    $bterm=get_term_by('slug',$cfg['blog_slug'],'category');
    $pterm=get_term_by('slug',$cfg['product_slug'],'product_cat');
    $row=array(
        'blog_term'=>$bterm instanceof WP_Term ? array(
            'id'=>(int)$bterm->term_id,'name'=>$bterm->name,'parent'=>(int)$bterm->parent,'count'=>(int)$bterm->count,
            'url'=>is_wp_error(get_term_link($bterm))?'':(string)get_term_link($bterm)
        ):null,
        'product_term'=>$pterm instanceof WP_Term ? array(
            'id'=>(int)$pterm->term_id,'name'=>$pterm->name,'parent'=>(int)$pterm->parent,'count'=>(int)$pterm->count,
            'url'=>is_wp_error(get_term_link($pterm))?'':(string)get_term_link($pterm)
        ):null,
    );
    if($bterm instanceof WP_Term){
        $posts=get_posts(array(
            'post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,
            'tax_query'=>array(array('taxonomy'=>'category','field'=>'term_id','terms'=>array((int)$bterm->term_id),'include_children'=>false)),
            'orderby'=>'ID','order'=>'ASC'
        ));
        $para_map=array();$post_rows=array();$sum=0;$min=PHP_INT_MAX;$max=0;$h2min=PHP_INT_MAX;
        foreach($posts as $post){
            if(!$post instanceof WP_Post) continue;
            $html=(string)$post->post_content;
            $words=emdo_cluster_words_20261001($html);
            preg_match_all('/<h2\b/i',$html,$hm);
            $h2=count($hm[0]);
            $sum+=$words;$min=min($min,$words);$max=max($max,$words);$h2min=min($h2min,$h2);
            $paras=emdo_cluster_paras_20261001($html);
            foreach($paras as $p){
                $hash=sha1($p['norm']);
                if(!isset($para_map[$hash])) $para_map[$hash]=array('text'=>$p['plain'],'posts'=>array());
                $para_map[$hash]['posts'][(int)$post->ID]=true;
            }
            $post_rows[]=array(
                'id'=>(int)$post->ID,'title'=>$post->post_title,'slug'=>$post->post_name,
                'words'=>$words,'h2'=>$h2,
                'wagyu_marker'=>(string)get_post_meta($post->ID,'_emdo_editorial_wagyu_65',true),
                'duck_cluster'=>(string)get_post_meta($post->ID,'_emdo_blog_cluster',true),
            );
        }
        $dupes=array();
        foreach($para_map as $p){
            $ids=array_keys($p['posts']);
            if(count($ids)>=2) $dupes[]=array('count'=>count($ids),'post_ids'=>$ids,'text'=>$p['text']);
        }
        usort($dupes,fn($a,$b)=>$b['count']<=>$a['count']);
        $row['editorial']=array(
            'posts'=>count($post_rows),
            'words'=>array('min'=>$min===PHP_INT_MAX?0:$min,'avg'=>$post_rows?round($sum/count($post_rows),1):0,'max'=>$max),
            'h2_min'=>$h2min===PHP_INT_MAX?0:$h2min,
            'exact_long_duplicate_groups'=>count($dupes),
            'duplicate_groups_top'=>array_slice($dupes,0,20),
            'thin_under_600'=>array_values(array_filter($post_rows,fn($p)=>$p['words']<600)),
            'wagyu_marked_count'=>count(array_filter($post_rows,fn($p)=>''!==$p['wagyu_marker'])),
        );
    }
    if($pterm instanceof WP_Term){
        $ids=get_posts(array(
            'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
            'tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>array((int)$pterm->term_id),'include_children'=>false))
        ));
        $row['commerce']=array('published_direct'=>count($ids));
    }
    $out[$key]=$row;
}

$wagyu_blog=get_term_by('slug','wagyu','category');
$wagyu_shop=get_term_by('slug','wagyu','product_cat');
$wagyu_posts=get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
    'meta_key'=>'_emdo_editorial_wagyu_65','meta_value'=>'2026-09-10.wagyu-65.v1'
));
$wagyu_products=get_posts(array(
    'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
    's'=>'Wagyu'
));
$wagyu_product_rows=array();
foreach($wagyu_products as $id){
    $title=(string)get_the_title((int)$id);
    $slug=(string)get_post_field('post_name',(int)$id);
    $content=(string)get_post_field('post_content',(int)$id);
    $source=(string)get_post_meta((int)$id,'_emdo_source_url',true);
    $hay=emdo_cluster_norm_20261001($title.' '.$slug.' '.$content.' '.$source);
    if(false===strpos($hay,'wagyu')) continue;
    $cats=wp_get_post_terms((int)$id,'product_cat',array('fields'=>'slugs'));
    $cats=is_wp_error($cats)?array():array_values($cats);
    $wagyu_product_rows[]=array(
        'id'=>(int)$id,'title'=>$title,'slug'=>$slug,'url'=>(string)get_permalink((int)$id),
        'supplier_id'=>(int)get_post_meta((int)$id,'_emdo_supplier_id',true),'categories'=>$cats
    );
}

$uncat=get_term_by('slug','sin-categorizar','product_cat');
$uncat_rows=array();
if($uncat instanceof WP_Term){
    $ids=get_posts(array(
        'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
        'tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>array((int)$uncat->term_id),'include_children'=>false))
    ));
    foreach($ids as $id){
        $uncat_rows[]=array(
            'id'=>(int)$id,'title'=>(string)get_the_title((int)$id),
            'supplier_id'=>(int)get_post_meta((int)$id,'_emdo_supplier_id',true),
            'url'=>(string)get_permalink((int)$id)
        );
    }
}

echo wp_json_encode(array(
    'batch'=>'20261001-major-editorial-cluster-audit',
    'clusters'=>$out,
    'wagyu'=>array(
        'blog_term'=>$wagyu_blog instanceof WP_Term ? array('id'=>(int)$wagyu_blog->term_id,'parent'=>(int)$wagyu_blog->parent,'count'=>(int)$wagyu_blog->count):null,
        'product_term'=>$wagyu_shop instanceof WP_Term ? array('id'=>(int)$wagyu_shop->term_id,'parent'=>(int)$wagyu_shop->parent,'count'=>(int)$wagyu_shop->count):null,
        'marked_posts'=>count($wagyu_posts),
        'matching_products'=>count($wagyu_product_rows),
        'products'=>$wagyu_product_rows,
    ),
    'uncategorized_products'=>$uncat_rows,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
