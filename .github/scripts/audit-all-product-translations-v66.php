<?php
if ( ! defined('ABSPATH') ) { fwrite(STDERR, "WordPress required\n"); exit(2); }

function mdo_v66_plain($s): string {
    return trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(html_entity_decode((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
}
function mdo_v66_norm($s): string {
    $s = strtolower(remove_accents(mdo_v66_plain($s)));
    $s = preg_replace('/[^a-z0-9%]+/u', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}
function mdo_v66_store_name(int $user_id): string {
    foreach (['wcfmmp_profile_settings','wcfm_profile_settings'] as $key) {
        $v = get_user_meta($user_id, $key, true);
        if (is_array($v) && !empty($v['store_name'])) return mdo_v66_plain($v['store_name']);
    }
    $u = get_userdata($user_id);
    if ($u) return mdo_v66_plain($u->display_name ?: $u->user_login);
    return 'author-'.$user_id;
}
function mdo_v66_target(string $vendor): string {
    $v = mdo_v66_norm($vendor);
    if (strpos($v, 'tolecarnes') !== false) return 'Tolecarnes';
    if (strpos($v, 'catedratic') !== false || strpos($v, 'catedratico') !== false) return 'El Catedrático';
    if (strpos($v, 'puente robles') !== false || strpos($v, 'puenterobles') !== false) return 'Puente Robles';
    return '';
}
function mdo_v66_spanish_hits(string $s): array {
    $plain = mdo_v66_plain($s);
    if ($plain === '') return [];
    $patterns = [
        'descripción' => '/\bdescripci[oó]n\b/iu',
        'ingredientes' => '/\bingredientes?\b/iu',
        'conservación' => '/\bconservaci[oó]n\b/iu',
        'modo de empleo' => '/\bmodo\s+de\s+(?:empleo|uso|preparaci[oó]n)\b/iu',
        'elaborado' => '/\belaborad[oa]s?\b/iu',
        'procedencia' => '/\bprocedencia\b/iu',
        'recomendamos' => '/\brecomendamos\b/iu',
        'aproximadamente' => '/\baproximadamente\b/iu',
        'envasado' => '/\benvasad[oa]s?\b/iu',
        'sin gluten' => '/\bsin\s+gluten\b/iu',
        'unidades' => '/\bunidades\b/iu',
        'caja de' => '/\bcaja\s+de\b/iu',
        'pieza de' => '/\bpieza\s+de\b/iu',
        'peso aprox' => '/\bpeso\s+(?:aprox|aproximado)\b/iu',
        'producto refrigerado' => '/\bproducto\s+refrigerado\b/iu',
        'mantener refrigerado' => '/\bmantener\s+refrigerad[oa]\b/iu',
        'fecha de caducidad' => '/\bfecha\s+de\s+caducidad\b/iu',
        'consumo preferente' => '/\bconsumo\s+preferente\b/iu',
        'una vez abierto' => '/\buna\s+vez\s+abierto\b/iu',
        'aceite de oliva virgen extra' => '/\baceite\s+de\s+oliva\s+virgen\s+extra\b/iu',
    ];
    $hits=[]; foreach($patterns as $label=>$re){ if(preg_match($re,$plain)) $hits[]=$label; }
    return $hits;
}
function mdo_v66_spanish_slug_hits(string $slug): array {
    $parts = preg_split('/-+/', strtolower(remove_accents($slug)));
    $bad = ['producto','carne','carnes','pieza','piezas','caja','cajas','cortado','cortada','maquina','deshuesado','deshuesada','envasado','envasada','vacio','virutas','codillo','paleta','jamon','aceite','oliva','tradicional','filtrado','sin','meses','lote','ternera','vaca','cerdo','hamburguesas'];
    return array_values(array_unique(array_intersect($parts,$bad)));
}

$out = [
    'generated_at' => gmdate('c'),
    'summary' => [
        'published_products' => 0,
        'products_with_any_issue' => 0,
        'missing_translation_products' => 0,
        'spanish_residue_products' => 0,
        'attribute_issue_products' => 0,
        'exact_copy_review_products' => 0,
    ],
    'vendors' => [],
    'targets' => [
        'Tolecarnes'=>['products'=>0,'issues'=>0,'missing'=>0,'spanish_residue'=>0,'attribute_issues'=>0,'exact_copy_review'=>0],
        'El Catedrático'=>['products'=>0,'issues'=>0,'missing'=>0,'spanish_residue'=>0,'attribute_issues'=>0,'exact_copy_review'=>0],
        'Puente Robles'=>['products'=>0,'issues'=>0,'missing'=>0,'spanish_residue'=>0,'attribute_issues'=>0,'exact_copy_review'=>0],
    ],
    'issues' => [],
    'exact_copy_review' => [],
    'products' => [],
];

$ids = get_posts([
    'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
    'orderby'=>'ID','order'=>'ASC','suppress_filters'=>true,
]);
$out['summary']['published_products'] = count($ids);

foreach (array_map('intval',$ids) as $id) {
    $p = get_post($id); if(!$p) continue;
    $product = function_exists('wc_get_product') ? wc_get_product($id) : null;
    $vendor = mdo_v66_store_name((int)$p->post_author);
    $target = mdo_v66_target($vendor);
    $visibility = $product ? (string)$product->get_catalog_visibility() : '';

    $src = [
        'title'=>(string)$p->post_title,
        'slug'=>(string)$p->post_name,
        'excerpt'=>(string)$p->post_excerpt,
        'content'=>(string)$p->post_content,
    ];
    $en = [
        'published'=>(string)get_post_meta($id,'_en_US_published',true),
        'title'=>(string)get_post_meta($id,'_en_US_post_title',true),
        'slug'=>(string)get_post_meta($id,'_en_US_post_name',true),
        'excerpt'=>(string)get_post_meta($id,'_en_US_post_excerpt',true),
        'content'=>(string)get_post_meta($id,'_en_US_post_content',true),
    ];
    $gb = [
        'published'=>(string)get_post_meta($id,'_en_GB_published',true),
        'title'=>(string)get_post_meta($id,'_en_GB_post_title',true),
        'slug'=>(string)get_post_meta($id,'_en_GB_post_name',true),
    ];

    $missing=[];
    if ($en['published'] !== '1') $missing[]='en_US_not_published';
    if (mdo_v66_plain($en['title'])==='') $missing[]='title';
    if (trim($en['slug'])==='') $missing[]='slug';
    if (mdo_v66_plain($src['excerpt'])!=='' && mdo_v66_plain($en['excerpt'])==='') $missing[]='excerpt';
    if (mdo_v66_plain($src['content'])!=='' && mdo_v66_plain($en['content'])==='') $missing[]='content';

    $spanish=[];
    foreach (['title','excerpt','content'] as $field) {
        $hits=mdo_v66_spanish_hits($en[$field]);
        if($hits) $spanish[$field]=$hits;
    }
    $slug_hits=mdo_v66_spanish_slug_hits($en['slug']); if($slug_hits) $spanish['slug']=$slug_hits;

    $exact=[];
    foreach(['title','excerpt','content'] as $field){
        $a=mdo_v66_norm($src[$field]); $b=mdo_v66_norm($en[$field]);
        if($a!=='' && $a===$b){
            $words=count(array_filter(explode(' ',$a)));
            if(($field==='title' && $words>=3) || ($field!=='title' && $words>=8)) $exact[]=$field;
        }
    }

    $attribute_issues=[];
    if($product){
        foreach($product->get_attributes() as $attr){
            $attr_name=(string)$attr->get_name();
            if($attr->is_taxonomy() && taxonomy_exists($attr_name)){
                foreach((array)$attr->get_options() as $term_id){
                    $term=get_term((int)$term_id,$attr_name); if(!$term || is_wp_error($term)) continue;
                    $en_name=mdo_v66_plain((string)get_term_meta($term->term_id,'_en_US_name',true));
                    $en_slug=trim((string)get_term_meta($term->term_id,'_en_US_slug',true));
                    $en_pub=(string)get_term_meta($term->term_id,'_en_US_published',true);
                    $ai=[];
                    if($en_name==='')$ai[]='missing_en_name';
                    if($en_slug==='')$ai[]='missing_en_slug';
                    if($en_pub!=='1')$ai[]='en_not_published';
                    $hits=mdo_v66_spanish_hits($en_name);if($hits)$ai[]='spanish_in_en_name:'.implode(',',$hits);
                    $sh=mdo_v66_spanish_slug_hits($en_slug);if($sh)$ai[]='spanish_in_en_slug:'.implode(',',$sh);
                    if($ai)$attribute_issues[]=['attribute'=>$attr_name,'term_id'=>(int)$term->term_id,'native'=>$term->name,'english'=>$en_name,'english_slug'=>$en_slug,'issues'=>$ai];
                }
            } else {
                $custom_name_hits=mdo_v66_spanish_hits($attr_name);
                $custom_options=[];
                foreach((array)$attr->get_options() as $opt){$h=mdo_v66_spanish_hits((string)$opt);if($h)$custom_options[]=['value'=>(string)$opt,'hits'=>$h];}
                if($custom_name_hits || $custom_options)$attribute_issues[]=['attribute'=>$attr_name,'custom'=>true,'name_hits'=>$custom_name_hits,'option_hits'=>$custom_options];
            }
        }
    }

    $has_issue = (bool)($missing || $spanish || $attribute_issues);
    $vendor_key = $vendor ?: '(unknown)';
    if(!isset($out['vendors'][$vendor_key]))$out['vendors'][$vendor_key]=['products'=>0,'issues'=>0,'missing'=>0,'spanish_residue'=>0,'attribute_issues'=>0,'exact_copy_review'=>0,'hidden'=>0];
    $out['vendors'][$vendor_key]['products']++;
    if($visibility==='hidden')$out['vendors'][$vendor_key]['hidden']++;
    if($has_issue)$out['vendors'][$vendor_key]['issues']++;
    if($missing)$out['vendors'][$vendor_key]['missing']++;
    if($spanish)$out['vendors'][$vendor_key]['spanish_residue']++;
    if($attribute_issues)$out['vendors'][$vendor_key]['attribute_issues']++;
    if($exact)$out['vendors'][$vendor_key]['exact_copy_review']++;

    if($target){
        $out['targets'][$target]['products']++;
        if($has_issue)$out['targets'][$target]['issues']++;
        if($missing)$out['targets'][$target]['missing']++;
        if($spanish)$out['targets'][$target]['spanish_residue']++;
        if($attribute_issues)$out['targets'][$target]['attribute_issues']++;
        if($exact)$out['targets'][$target]['exact_copy_review']++;
    }

    if($has_issue){
        $out['summary']['products_with_any_issue']++;
        if($missing)$out['summary']['missing_translation_products']++;
        if($spanish)$out['summary']['spanish_residue_products']++;
        if($attribute_issues)$out['summary']['attribute_issue_products']++;
        $out['issues'][]=[
            'id'=>$id,'vendor'=>$vendor,'target'=>$target,'visibility'=>$visibility,
            'native_title'=>mdo_v66_plain($src['title']),'english_title'=>mdo_v66_plain($en['title']),
            'native_slug'=>$src['slug'],'english_slug'=>$en['slug'],'en_US_published'=>$en['published'],
            'en_GB_title'=>mdo_v66_plain($gb['title']),'en_GB_slug'=>$gb['slug'],'en_GB_published'=>$gb['published'],
            'missing'=>$missing,'spanish'=>$spanish,'attribute_issues'=>$attribute_issues,
        ];
    }
    if($exact){
        $out['summary']['exact_copy_review_products']++;
        $out['exact_copy_review'][]=['id'=>$id,'vendor'=>$vendor,'target'=>$target,'visibility'=>$visibility,'title'=>mdo_v66_plain($src['title']),'fields'=>$exact];
    }
    $out['products'][]=['id'=>$id,'vendor'=>$vendor,'target'=>$target,'visibility'=>$visibility,'native_title'=>mdo_v66_plain($src['title']),'english_title'=>mdo_v66_plain($en['title']),'english_slug'=>$en['slug'],'issues'=>$has_issue?1:0,'exact_review'=>$exact];
}

ksort($out['vendors'], SORT_NATURAL|SORT_FLAG_CASE);
echo wp_json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
