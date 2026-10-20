<?php
/**
 * EMDO editorial rollout batch 06: citrus varieties, citrus season, seasonal fruit, tropical fruit and delivery boxes.
 * Deploys only approved new posts; never overwrites unrelated editorial work.
 * Run via: EMDO_NEW_PRODUCERS_PREFLIGHT=1 wp eval-file ...
 */
if (!defined('ABSPATH')) { throw new RuntimeException('Requires WordPress'); }
const EMDO_NEWPROD_MARKER = 'emdo-approved-editorial-20261020-batch06';
const EMDO_NEWPROD_IDS = [19, 20, 21, 23, 24];
const EMDO_NEWPROD_FILES = [
    'editorial-nuevos-productores-20261020-19.json',
    'editorial-nuevos-productores-20261020-20.json',
    'editorial-nuevos-productores-20261020-21.json',
    'editorial-nuevos-productores-20261020-23.json',
    'editorial-nuevos-productores-20261020-24.json',
];

function emdo_np_words(string $html): int {
    $plain = wp_strip_all_tags($html);
    preg_match_all('/[\p{L}\p{M}\p{N}]+/u', $plain, $matches);
    return count($matches[0]);
}
function emdo_np_body_ok(string $html): bool {
    if (preg_match('~<(?:script|iframe|style|form|object)\b~i', $html)) return false;
    if (preg_match('~\b(?:javascript:|data:text/html)~i', $html)) return false;
    if (substr_count($html, '<p>') < 5 || substr_count($html, '<h2>') < 2) return false;
    return true;
}
function emdo_np_posts(): array {
    $dir = trim((string)getenv('EMDO_NEW_PRODUCERS_DATA_DIR'));
    if (!$dir || !is_dir($dir)) throw new RuntimeException('Data directory not provided');
    $posts = []; $es=[]; $en=[];
    foreach (EMDO_NEWPROD_FILES as $i => $name) {
        $path = $dir . '/' . $name;
        if (!is_readable($path)) throw new RuntimeException('Missing source: ' . $name);
        $p = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (($p['id'] ?? null) !== EMDO_NEWPROD_IDS[$i]) throw new RuntimeException('Unexpected editorial identifier');
        foreach (['slug','en_slug','title','en_title','excerpt','en_excerpt','seo_title','en_seo_title','seo_description','en_seo_description','focus_keyword','en_focus_keyword','es','en','category','category_name','kind','product_category_terms'] as $key) {
            if (!array_key_exists($key, $p) || (is_string($p[$key]) && trim($p[$key])==='')) throw new RuntimeException('Incomplete article ' . $p['id'] . ':' . $key);
        }
        if (isset($es[$p['slug']]) || isset($en[$p['en_slug']])) throw new RuntimeException('Duplicate slug in manifest');
        $es[$p['slug']]=true; $en[$p['en_slug']]=true;
        $wc_es=emdo_np_words($p['es']); $wc_en=emdo_np_words($p['en']);
        if ($wc_es < 750 || $wc_en < 700) throw new RuntimeException('Editorial length failure ' . $p['slug'] . ' ES=' . $wc_es . ' EN=' . $wc_en);
        if (!emdo_np_body_ok($p['es']) || !emdo_np_body_ok($p['en'])) throw new RuntimeException('Unsafe or thin markup ' . $p['slug']);
        if (!is_array($p['product_category_terms']) || !$p['product_category_terms']) throw new RuntimeException('Category missing');
        $posts[]=$p;
    }
    return $posts;
}
function emdo_np_find_shop_term(array $slugs): ?WP_Term {
    if (!taxonomy_exists('product_cat')) return null;
    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term instanceof WP_Term) return $term;
    }
    return null;
}
function emdo_np_has_available_products(WP_Term $term): bool {
    if (!function_exists('wc_get_products')) return false;
    $p = wc_get_products(['status'=>'publish','category'=>[$term->slug],'stock_status'=>'instock','limit'=>12,'orderby'=>'date','order'=>'DESC']);
    foreach ($p as $product) {
        if ($product instanceof WC_Product && $product->is_visible() && $product->is_purchasable() && $product->is_in_stock()) return true;
    }
    return false;
}
function emdo_np_featured_for(WP_Term $term): int {
    if (!function_exists('wc_get_products')) return 0;
    $products = wc_get_products(['status'=>'publish','category'=>[$term->slug],'stock_status'=>'instock','limit'=>16]);
    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_visible() || !$product->is_purchasable() || !$product->is_in_stock()) continue;
        $id=(int)$product->get_image_id();
        if ($id > 0 && wp_attachment_is_image($id)) return $id;
    }
    return 0;
}
function emdo_np_editorial_link(string $html, string $kind, string $url, bool $english): string {
    // Only add contextual links to categories confirmed to contain in-stock purchasable products.
    $link=esc_url($url);
    if ($kind==='frutas') {
        $pairs=$english ? [
            'When ordering online, check net weight, growing origin' => 'When ordering <a href="'.$link.'">fresh fruit online</a>, check net weight, growing origin',
            'Look for named varieties, growing origins, net weight' => 'Look through <a href="'.$link.'">the fresh fruit selection</a> for named varieties, growing origins, net weight',
            'Before paying, check net weight, the named contents' => 'Before paying for <a href="'.$link.'">a box of fruit</a>, check net weight, the named contents',
        ] : [
            'En un pedido online revisa siempre el peso neto' => 'En <a href="'.$link.'">una compra de fruta online</a> revisa siempre el peso neto',
            'Busca descripciones que indiquen variedad, origen, peso neto' => 'En <a href="'.$link.'">la selección de frutas</a> busca descripciones que indiquen variedad, origen, peso neto',
            'Antes de pagar, busca el peso neto, la lista de frutas' => 'Antes de pagar por <a href="'.$link.'">una caja de frutas</a>, busca el peso neto, la lista de frutas',
        ];
        return str_replace(array_keys($pairs),array_values($pairs),$html);
    }
    if ($kind==='citricos') {
        $needle=$english?'The strongest product descriptions identify the variety':'Las fichas detalladas permiten escoger de verdad';
        $replacement=$english?'Our <a href="'.$link.'">current orange selection</a> should identify the variety':'Nuestra <a href="'.$link.'">selección de naranjas disponibles</a> debe permitir escoger de verdad';
        return str_replace($needle,$replacement,$html);
    }
    return $html;
}

$articles=emdo_np_posts();
$known_ids=[]; $new=[];
foreach ($articles as $p) {
    $existing=get_page_by_path($p['slug'],OBJECT,'post');
    if ($existing instanceof WP_Post) {
        if ((string)get_post_meta($existing->ID,'_emdo_newprod_release',true)!==EMDO_NEWPROD_MARKER)
            throw new RuntimeException('Slug occupied by another post: '.$p['slug']);
        if ((string)get_post_meta($existing->ID,'_en_US_post_name',true)!==$p['en_slug'])
            throw new RuntimeException('Existing English slug differs: '.$p['slug']);
        $known_ids[$p['slug']]=(int)$existing->ID;
    }
    $collisions = get_posts(['post_type'=>'post','post_status'=>'any','numberposts'=>2,'meta_key'=>'_en_US_post_name','meta_value'=>$p['en_slug'],'suppress_filters'=>true]);
    foreach ($collisions as $other) {
        if ($other->post_name!==$p['slug']) throw new RuntimeException('English slug collision: '.$p['en_slug']);
    }
    // The live inventory should remain the authority, not a stale snapshot.
    $needle=remove_accents(mb_strtolower($p['title']));
    $needle=preg_replace('/[^\p{L}\p{N}]+/u','',$needle);
    $near=get_posts(['post_type'=>'post','post_status'=>'publish','s'=>$p['title'],'numberposts'=>50,'suppress_filters'=>true]);
    foreach ($near as $candidate) {
        if ($candidate->post_name===$p['slug']) continue;
        $other=remove_accents(mb_strtolower($candidate->post_title));
        $other=preg_replace('/[^\p{L}\p{N}]+/u','',$other);
        if ($other===$needle) throw new RuntimeException('Same title already published: '.$p['title']);
    }
}
if (getenv('EMDO_NEW_PRODUCERS_PREFLIGHT')==='1') {
    echo 'EMDO_NP_PREFLIGHT '.wp_json_encode(['ok'=>true,'count'=>count($articles),'already_present'=>count($known_ids),'word_counts'=>array_map(static fn($p)=>['id'=>$p['id'],'es'=>emdo_np_words($p['es']),'en'=>emdo_np_words($p['en'])],$articles)],JSON_UNESCAPED_UNICODE).PHP_EOL;
    return;
}

$default_image=(int)get_option('emdo_default_blog_featured_attachment_id',0);
if (!$default_image || !wp_attachment_is_image($default_image)) {
    $default_image=wp_attachment_is_image(13442)?13442:0;
}
if (!$default_image) throw new RuntimeException('No valid default featured image');
$authors=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID','orderby'=>'ID','order'=>'ASC']);
$author=$authors?(int)$authors[0]:1;
$results=[]; $created=[];
try {
    foreach ($articles as $p) {
        $existing_id=$known_ids[$p['slug']]??0;
        if ($existing_id) {
            $obj=get_post($existing_id);
            if (!$obj || $obj->post_status!=='publish' || (string)get_post_meta($existing_id,'_en_US_published',true)!=='1')
                throw new RuntimeException('Existing edition cannot be safely reused: '.$p['slug']);
            $id=$existing_id;
            $is_new=false;
        } else {
            $cat=get_category_by_slug($p['category']);
            if (!$cat instanceof WP_Term) {
                $new_cat=wp_insert_term($p['category_name'],'category',['slug'=>$p['category']]);
                if (is_wp_error($new_cat)) throw new RuntimeException('Cannot create blog category '.$p['category']);
                $cat=get_term((int)$new_cat['term_id'],'category');
            }
            if (!$cat instanceof WP_Term) throw new RuntimeException('No blog category '.$p['category']);
            $term=emdo_np_find_shop_term($p['product_category_terms']);
            $es=$p['es']; $en=$p['en']; $featured=$default_image; $shop_link='';
            if ($term instanceof WP_Term && emdo_np_has_available_products($term)) {
                $url=get_term_link($term);
                if (!is_wp_error($url) && is_string($url) && str_starts_with($url,home_url('/'))) {
                    $shop_link=$url;
                    $es=emdo_np_editorial_link($es,$p['kind'],$url,false);
                    $en=emdo_np_editorial_link($en,$p['kind'],$url,true);
                }
                $photo=emdo_np_featured_for($term);
                if ($photo) $featured=$photo;
            }
            $id=wp_insert_post(wp_slash([
                'post_type'=>'post','post_status'=>'draft','post_name'=>$p['slug'],
                'post_title'=>$p['title'],'post_excerpt'=>$p['excerpt'],
                'post_content'=>wp_kses_post($es),'post_category'=>[(int)$cat->term_id],
                'post_author'=>$author,'comment_status'=>'closed','ping_status'=>'closed'
            ]),true);
            if (is_wp_error($id) || !$id) throw new RuntimeException('Draft creation failed: '.$p['slug']);
            $id=(int)$id; $created[]=$id; $is_new=true;
            update_post_meta($id,'_emdo_newprod_release',EMDO_NEWPROD_MARKER);
            update_post_meta($id,'_emdo_newprod_plan_id',(int)$p['id']);
            update_post_meta($id,'_en_US_post_name',$p['en_slug']);
            update_post_meta($id,'_en_US_post_title',$p['en_title']);
            update_post_meta($id,'_en_US_post_excerpt',$p['en_excerpt']);
            update_post_meta($id,'_en_US_post_content',wp_kses_post($en));
            update_post_meta($id,'_en_US_ready','1');
            update_post_meta($id,'_en_US_published','1');
            update_post_meta($id,'_emdo_seo_title',$p['seo_title']);
            update_post_meta($id,'_emdo_seo_description',$p['seo_description']);
            update_post_meta($id,'_en_US_seo_title',$p['en_seo_title']);
            update_post_meta($id,'_en_US_seo_description',$p['en_seo_description']);
            update_post_meta($id,'rank_math_title',$p['seo_title']);
            update_post_meta($id,'rank_math_description',$p['seo_description']);
            update_post_meta($id,'rank_math_focus_keyword',$p['focus_keyword']);
            update_post_meta($id,'_en_US_rank_math_focus_keyword',$p['en_focus_keyword']);
            if ($shop_link) update_post_meta($id,'_emdo_newprod_product_category_url',$shop_link);
            if (!set_post_thumbnail($id,$featured)) throw new RuntimeException('Featured image failed: '.$p['slug']);
            if ($featured===$default_image) update_post_meta($id,'_emdo_uses_default_featured','1');
            $posted=wp_update_post(['ID'=>$id,'post_status'=>'publish'],true);
            if (is_wp_error($posted)) throw new RuntimeException('Publication failed: '.$p['slug']);
        }
        if (get_post_status($id)!=='publish' || (string)get_post_meta($id,'_en_US_published',true)!=='1'
            || !get_post_thumbnail_id($id) || (string)get_post_meta($id,'_emdo_newprod_release',true)!==EMDO_NEWPROD_MARKER)
            throw new RuntimeException('Final integrity failed: '.$p['slug']);
        $results[]=[
            'id'=>$id,'plan_id'=>(int)$p['id'],'slug'=>$p['slug'],'en_slug'=>$p['en_slug'],
            'es_url'=>get_permalink($id),'en_url'=>home_url('/en/'.$p['en_slug'].'/'),
            'es_words'=>emdo_np_words((string)get_post_field('post_content',$id)),
            'en_words'=>emdo_np_words((string)get_post_meta($id,'_en_US_post_content',true)),
            'image_id'=>(int)get_post_thumbnail_id($id),'new'=>$is_new,
            'shop_category_url'=>(string)get_post_meta($id,'_emdo_newprod_product_category_url',true)
        ];
    }
} catch (Throwable $e) {
    foreach (array_reverse($created) as $id) wp_delete_post($id,true);
    wp_cache_flush();
    throw $e;
}
wp_cache_flush();
if (function_exists('rocket_clean_domain')) rocket_clean_domain();
echo 'EMDO_NP_PUBLISHED '.wp_json_encode(['verified'=>count($results)===count(EMDO_NEWPROD_IDS),'count'=>count($results),'posts'=>$results],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
