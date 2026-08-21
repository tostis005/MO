<?php
/** Apply reviewed category editorial copy and English metadata. */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }

$targets = array(
    'conservas' => array(
        'es' => 'Conservas artesanas de pimientos, puerros, ajetes, tomate y otras elaboraciones vegetales.',
        'en_name' => 'Preserves',
        'en_slug' => 'preserves',
        'en' => 'Artisan preserves made with peppers, leeks, garlic shoots, tomato and other vegetables.',
        'required' => true,
    ),
    'hortalizas-verduras' => array(
        'es' => 'Hortalizas y verduras frescas de temporada, desde patatas, pimientos y calabacines hasta otras variedades de la huerta.',
        'en_name' => 'Vegetables',
        'en_slug' => 'vegetables',
        'en' => 'Fresh seasonal vegetables, from potatoes, peppers and courgettes to other produce from the market garden.',
        'required' => true,
    ),
    'legumbres' => array(
        'es' => 'Alubias, garbanzos y lentejas en distintas variedades, seleccionadas para guisos, potajes y otras recetas.',
        'en_name' => 'Pulses',
        'en_slug' => 'pulses',
        'en' => 'Beans, chickpeas and lentils in different varieties, selected for stews, casseroles and other recipes.',
        'required' => true,
    ),
    'naranjas' => array(
        'es' => 'Naranjas frescas de distintas variedades, seleccionadas para mesa, zumo y otros usos.',
        'en_name' => 'Oranges',
        'en_slug' => 'oranges',
        'en' => 'Fresh oranges of different varieties, selected for eating, juicing and other uses.',
        'required' => false,
    ),
    'quesos' => array(
        'es' => 'Quesos de distintas procedencias, tipos de leche, curaciones y formatos.',
        'en_name' => 'Cheeses',
        'en_slug' => 'cheeses',
        'en' => 'Cheeses of different origins, milk types, maturities and formats.',
        'required' => false,
    ),
);

$out = array('updated'=>array(),'missing'=>array(),'issues'=>array());
foreach ( $targets as $slug => $copy ) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if ( ! $term instanceof WP_Term ) {
        $out['missing'][] = $slug;
        if ( ! empty($copy['required']) ) { $out['issues'][] = array('slug'=>$slug,'reason'=>'missing_required_term'); }
        continue;
    }
    $id = (int) $term->term_id;
    update_term_meta($id, '_en_US_published', '1');
    update_term_meta($id, '_en_US_ready', '1');
    update_term_meta($id, '_en_US_name', $copy['en_name']);
    update_term_meta($id, '_en_US_slug', sanitize_title($copy['en_slug']));
    update_term_meta($id, '_en_US_description', $copy['en']);
    update_term_meta($id, '_emdo_en_hub_summary', $copy['en']);
    update_term_meta($id, '_emdo_es_hub_summary', $copy['es']);
    $out['updated'][] = array(
        'id'=>$id,'slug'=>$slug,'count'=>(int)$term->count,
        'en_name'=>(string)get_term_meta($id,'_en_US_name',true),
        'en_slug'=>(string)get_term_meta($id,'_en_US_slug',true),
        'en_description'=>(string)get_term_meta($id,'_en_US_description',true),
        'en_hub_summary'=>(string)get_term_meta($id,'_emdo_en_hub_summary',true),
        'en_published'=>(string)get_term_meta($id,'_en_US_published',true),
    );
}

$used = array();
$published = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false,'meta_key'=>'_en_US_published','meta_value'=>'1'));
if ( ! is_wp_error($published) ) {
    foreach ($published as $term) {
        $slug = sanitize_title((string)get_term_meta((int)$term->term_id,'_en_US_slug',true));
        if ($slug==='') { continue; }
        if (isset($used[$slug]) && $used[$slug] !== (int)$term->term_id) {
            $out['issues'][] = array('slug'=>$slug,'reason'=>'duplicate_english_slug','term_ids'=>array($used[$slug],(int)$term->term_id));
        }
        $used[$slug]=(int)$term->term_id;
    }
}
wp_cache_flush();
echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
if ($out['issues']) { exit(2); }
