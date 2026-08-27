<?php
/** One-time/idempotent migration: give every product-related post one canonical product-family category. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$families = array(
    'jamones-y-paletas' => array(
        'name'=>'Jamones y paletas','en_name'=>'Hams and shoulders','en_slug'=>'hams-and-shoulders',
        'category_aliases'=>array('jamones-y-paletas','jamones-paletas','jamones'),
        'product_aliases'=>array('jamones-paletas','jamones-y-paletas','jamones-paletas-ibericas'),
        'topics'=>array('hams','ham'),
        'keywords'=>array('jamon','paleta','pata negra','precinto negro','precinto rojo','precinto verde','precinto blanco','bellota','cebo de campo','montanera','dehesa','los pedroches'),
    ),
    'embutidos-y-curados' => array(
        'name'=>'Embutidos y curados','en_name'=>'Cured meats','en_slug'=>'cured-meats',
        'category_aliases'=>array('embutidos-y-curados','embutidos','curados'),
        'product_aliases'=>array('embutidos-y-curados','embutidos','embutidos-curados'),
        'topics'=>array('cured_meats','cured-meats','charcuterie'),
        'keywords'=>array('embutido','chorizo','salchichon','morcón','morcon','lomo embuchado','lomito','caña de lomo','cana de lomo','curado'),
    ),
    'carnes' => array(
        'name'=>'Carnes','en_name'=>'Meat','en_slug'=>'meat',
        'category_aliases'=>array('carnes','carne'),
        'product_aliases'=>array('carnes','carne'),
        'topics'=>array('meat','meats','beef'),
        'keywords'=>array('carne','vacuno','ternera','vaca','chulet','entrecot','entrecote','morcillo','osobuco','rabo','churrasco','ragú','ragu','hamburgues','solomillo','lomo alto','lomo bajo','picaña','picana','aguja de ternera'),
    ),
    'aceites' => array(
        'name'=>'Aceites','en_name'=>'Oils','en_slug'=>'oils',
        'category_aliases'=>array('aceites','aceite-de-oliva'),
        'product_aliases'=>array('aceites','aceite-de-oliva','aceites-de-oliva'),
        'topics'=>array('olive_oil','olive-oil','oils','oil'),
        'keywords'=>array('aove','aceite de oliva','virgen extra','almazara','aceituna','extraccion en frio','extracción en frío','acidez del aceite'),
    ),
    'conservas' => array(
        'name'=>'Conservas','en_name'=>'Preserves','en_slug'=>'preserves',
        'category_aliases'=>array('conservas','preserves'),
        'product_aliases'=>array('conservas','preserves'),
        'topics'=>array('preserves','conserves'),
        'keywords'=>array('conserva','en conserva','tarro','bote de','peso escurrido','esteriliza','pasteuriza','confitura','liquido de gobierno','líquido de gobierno'),
    ),
    'hortalizas-y-verduras' => array(
        'name'=>'Hortalizas y verduras','en_name'=>'Vegetables','en_slug'=>'vegetables',
        'category_aliases'=>array('hortalizas-y-verduras','hortalizas','verduras'),
        'product_aliases'=>array('hortalizas-verduras','hortalizas','verduras'),
        'topics'=>array('vegetables','vegetable'),
        'keywords'=>array('hortaliza','verdura','lechuga','escarola','puerro','berenjena','coliflor','espinaca','brócoli','brocoli','calabac','tomate','pimiento','cebolla','ajo','patata','repollo','acelga','lombarda','guindilla'),
    ),
    'legumbres' => array(
        'name'=>'Legumbres','en_name'=>'Pulses','en_slug'=>'pulses',
        'category_aliases'=>array('legumbres','pulses'),
        'product_aliases'=>array('legumbres','pulses'),
        'topics'=>array('legumes','pulses','pulse'),
        'keywords'=>array('legumbre','garbanzo','lenteja','alubia','judia seca','judía seca'),
    ),
    'packs-y-lotes' => array(
        'name'=>'Packs y lotes','en_name'=>'Packs and bundles','en_slug'=>'packs-and-bundles',
        'category_aliases'=>array('packs-y-lotes','lotes','packs'),
        'product_aliases'=>array('packs-y-lotes','lotes','lotes-y-regalos'),
        'topics'=>array('packs','bundles','packs_bundles'),
        'keywords'=>array('lote de','lotes de','pack de','packs de','cesta regalo','cesta de regalo'),
    ),
);

function emdo_blogcat_term_010263( string $slug, array $cfg ): int {
    $term=get_term_by('slug',$slug,'category');
    if(!$term instanceof WP_Term){
        $created=wp_insert_term($cfg['name'],'category',array('slug'=>$slug));
        if(is_wp_error($created)){throw new RuntimeException($created->get_error_message());}
        $term=get_term((int)$created['term_id'],'category');
    }
    if(!$term instanceof WP_Term){throw new RuntimeException('Missing blog category '.$slug);}
    update_term_meta($term->term_id,'_en_US_name',$cfg['en_name']);
    update_term_meta($term->term_id,'_en_US_slug',sanitize_title($cfg['en_slug']));
    update_term_meta($term->term_id,'_en_US_published','1');
    return (int)$term->term_id;
}

$term_ids=array();$category_alias_to_family=array();$product_alias_to_family=array();$topic_to_family=array();
foreach($families as $slug=>$cfg){
    $term_ids[$slug]=emdo_blogcat_term_010263($slug,$cfg);
    foreach($cfg['category_aliases'] as $alias){$category_alias_to_family[sanitize_title($alias)]=$slug;}
    foreach($cfg['product_aliases'] as $alias){$product_alias_to_family[sanitize_title($alias)]=$slug;}
    foreach($cfg['topics'] as $topic){$topic_to_family[strtolower($topic)]=$slug;}
}

function emdo_blogcat_from_shortcode_010263( string $content, array $alias_map ): string {
    if(!preg_match_all('/\[products\b[^\]]*\bcategory=["\']([^"\']+)["\']/i',$content,$matches)){return '';}
    foreach($matches[1] as $raw){foreach(preg_split('/\s*,\s*/',(string)$raw) as $slug){$slug=sanitize_title($slug);if(isset($alias_map[$slug])){return $alias_map[$slug];}}}
    return '';
}
function emdo_blogcat_from_keywords_010263( string $haystack, array $families ): string {
    $haystack=strtolower(remove_accents($haystack));
    $priority=array('packs-y-lotes','conservas','aceites','legumbres','jamones-y-paletas','embutidos-y-curados','carnes','hortalizas-y-verduras');
    foreach($priority as $family){foreach($families[$family]['keywords'] as $keyword){$keyword=strtolower(remove_accents((string)$keyword));if($keyword!==''&&str_contains($haystack,$keyword)){return $family;}}}
    return '';
}

$post_ids=get_posts(array('post_type'=>'post','post_status'=>array('publish','draft','future'),'posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC','suppress_filters'=>true));
$report=array('total'=>count($post_ids),'changed'=>0,'unchanged'=>0,'unresolved'=>array(),'families'=>array_fill_keys(array_keys($families),0));
foreach(array_map('intval',$post_ids) as $post_id){
    $family='';$reason='';$cats=get_the_category($post_id);
    foreach((array)$cats as $cat){$slug=sanitize_title($cat->slug);if(isset($category_alias_to_family[$slug])){$family=$category_alias_to_family[$slug];$reason='existing-category';break;}}
    $content=(string)get_post_field('post_content',$post_id);
    if($family===''){$family=emdo_blogcat_from_shortcode_010263($content,$product_alias_to_family);if($family!==''){$reason='product-shortcode';}}
    if($family===''){$topic=strtolower(trim((string)get_post_meta($post_id,'_emdo_authority_topic',true)));if($topic!==''&&isset($topic_to_family[$topic])){$family=$topic_to_family[$topic];$reason='topic-meta';}}
    if($family===''){$text=(string)get_post_field('post_title',$post_id).' '.(string)get_post_field('post_name',$post_id);$family=emdo_blogcat_from_keywords_010263($text,$families);if($family!==''){$reason='title-slug';}}
    if($family===''){$report['unresolved'][]=array('id'=>$post_id,'slug'=>(string)get_post_field('post_name',$post_id),'title'=>(string)get_post_field('post_title',$post_id),'categories'=>wp_list_pluck((array)$cats,'slug'));continue;}
    $before=array_map('intval',wp_get_post_categories($post_id));sort($before);$after=array($term_ids[$family]);
    wp_set_post_categories($post_id,$after,false);
    update_post_meta($post_id,'_emdo_blog_primary_category',$family);update_post_meta($post_id,'_emdo_blog_category_reason',$reason);update_post_meta($post_id,'_emdo_blog_category_migrated_at',gmdate('c'));
    $report['families'][$family]++;if($before!==$after){$report['changed']++;}else{$report['unchanged']++;}
}
foreach($term_ids as $term_id){clean_term_cache($term_id,'category');}
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
