<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_diag_words_20261001( string $html ): int {
    $text=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(strip_shortcodes($html))));
    if(''===$text) return 0;
    preg_match_all('/[\p{L}\p{M}]+(?:[’\x{27}’-][\p{L}\p{M}]+)*/u',$text,$m);
    return count($m[0]);
}
function mdo_diag_norm_20261001( string $text ): string {
    $text=remove_accents(mb_strtolower(wp_strip_all_tags($text),'UTF-8'));
    $text=preg_replace('/[^a-z0-9]+/u',' ',$text);
    return trim(preg_replace('/\s+/u',' ',(string)$text));
}

$boilerplate=array(
    'Esta guía está planteada para resolver la intención de búsqueda completa, no solo para dar una definición rápida. Veremos qué significa cada término, qué cambia realmente en el queso, cómo leer una etiqueta y cómo utilizar esa información al comprar, conservar o servir.',
    'También merece la pena separar tres preguntas que a menudo se mezclan: si el producto es seguro, si está bien elaborado y si encaja con nuestro gusto o nuestra receta. La seguridad depende de controles y de la integridad del producto; la calidad incluye materia prima y proceso; y la preferencia personal depende del uso. Separar esas preguntas evita conclusiones precipitadas y permite comprar con criterios más claros.',
    'No necesariamente. En alimentos transformados hay variaciones normales ligadas a la materia prima, al proceso y al almacenamiento. Lo importante es distinguir esas variaciones de señales claras de alteración y seguir las indicaciones del fabricante.',
    'No, pero aporta información esencial: ingredientes, origen cuando se declara, peso, responsable del producto, fechas e instrucciones de conservación. Es la primera referencia para interpretar correctamente una conserva, un embutido, una legumbre o un aceite.',
    'No. El precio puede reflejar origen, rendimiento, formato, selección, costes de elaboración o distribución. Para comparar hay que hacerlo entre productos equivalentes y valorar también rendimiento real y uso.',
    'Si hay pérdida de cierre, hinchado, fugas, olor claramente alterado, moho no esperado o cualquier indicio incompatible con el producto, no conviene probarlo para decidir. En productos comerciales puede conservarse el lote y contactar con vendedor o elaborador.',
    'Busca una descripción transparente, trazabilidad suficiente y un formato coherente con tu consumo. Después valora características sensoriales y culinarias. La mejor opción no es una categoría abstracta, sino la que combina buena elaboración con el uso que realmente vas a darle.',
);
$norms=array_map('mdo_diag_norm_20261001',$boilerplate);

$slugs=array('jamones-y-paletas','aceites','carnes','hortalizas-y-verduras','legumbres','embutidos-y-curados','conservas','packs-y-lotes','quesos','pato','wagyu','foie-pates-untables');
$rows=array();
foreach($slugs as $slug){
    $term=get_term_by('slug',$slug,'category');
    if(!$term instanceof WP_Term) continue;
    $posts=get_posts(array(
        'post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,
        'tax_query'=>array(array('taxonomy'=>'category','field'=>'term_id','terms'=>array((int)$term->term_id),'include_children'=>false)),
        'orderby'=>'ID','order'=>'ASC'
    ));
    foreach($posts as $post){
        if(!$post instanceof WP_Post) continue;
        $html=(string)$post->post_content;
        $norm=mdo_diag_norm_20261001($html);
        $hits=0;
        foreach($norms as $needle){ $hits+=substr_count($norm,$needle); }
        $words=mdo_diag_words_20261001($html);
        if($words<750 || $hits>0){
            $rows[]=array(
                'id'=>(int)$post->ID,'category'=>$slug,'title'=>$post->post_title,'slug'=>$post->post_name,
                'words'=>$words,'boilerplate_hits'=>$hits,
                'depth_marker'=>(bool)strpos($html,'mdo-editorial-depth-20261001:'),
                'wagyu_marker'=>(string)get_post_meta($post->ID,'_emdo_editorial_wagyu_65',true),
                'url'=>(string)get_permalink($post)
            );
        }
    }
}
usort($rows,fn($a,$b)=>$a['words']<=>$b['words']);

$wagyu=get_term_by('slug','wagyu','category');
$wagyu_shop=get_term_by('slug','wagyu','product_cat');
$foie=get_term_by('slug','foie-pates-untables','category');
$uncat=get_term_by('slug','sin-categorizar','product_cat');
$uncat_count=0;
if($uncat instanceof WP_Term){
    $uncat_count=count(get_posts(array(
        'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
        'tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>array((int)$uncat->term_id),'include_children'=>false))
    )));
}

echo wp_json_encode(array(
    'batch'=>'20261001-global-cluster-diagnostic',
    'low_or_template_posts'=>$rows,
    'wagyu'=>array(
        'blog'=>$wagyu instanceof WP_Term?array('id'=>(int)$wagyu->term_id,'parent'=>(int)$wagyu->parent,'count'=>(int)$wagyu->count,'url'=>get_term_link($wagyu)):null,
        'shop'=>$wagyu_shop instanceof WP_Term?array('id'=>(int)$wagyu_shop->term_id,'parent'=>(int)$wagyu_shop->parent,'count'=>(int)$wagyu_shop->count,'url'=>get_term_link($wagyu_shop)):null,
    ),
    'foie_blog'=>$foie instanceof WP_Term?array('id'=>(int)$foie->term_id,'parent'=>(int)$foie->parent,'count'=>(int)$foie->count,'url'=>get_term_link($foie)):null,
    'uncategorized_published'=>$uncat_count,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
