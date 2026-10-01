<?php
/** Apply reviewed category editorial copy and English metadata. */
/* Trigger 2026-08-21: deploy after workflow registration. */
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
    'pescados-mariscos' => array(
        'es' => 'Pescados, huevas y especialidades del mar en distintos formatos y elaboraciones.',
        'en_name' => 'Fish and seafood',
        'en_slug' => 'fish-seafood',
        'en' => 'Fish, roe and seafood specialities in different preparations and formats.',
        'required' => true,
        'image_url' => 'https://raw.githubusercontent.com/tostis005/MO/main/.github/assets/category-images-20261001/category-pescados-mariscos.webp',
        'image_file' => 'category-pescados-mariscos.webp',
        'image_alt' => 'Salmón ahumado sobre pizarra',
        'image_marker' => 'pescados-mariscos-20261001',
        'image_size' => 143118,
    ),
    'foie-pates-untables' => array(
        'es' => 'Foie gras, patés, mousses, rillettes y otras especialidades untables de distintos productores.',
        'en_name' => 'Foie, pâtés and spreads',
        'en_slug' => 'foie-pates-spreads',
        'en' => 'Foie gras, pâtés, mousses, rillettes and other spreadable specialities from selected producers.',
        'required' => true,
        'image_url' => 'https://raw.githubusercontent.com/tostis005/MO/main/.github/assets/category-images-20261001/category-foie-pates-untables.webp',
        'image_file' => 'category-foie-pates-untables.webp',
        'image_alt' => 'Foie gras, paté y untables sobre pizarra',
        'image_marker' => 'foie-pates-untables-20261001',
        'image_size' => 169964,
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
    $term_update = wp_update_term($id, 'product_cat', array('description'=>$copy['es']));
    if ( is_wp_error($term_update) ) {
        $out['issues'][] = array('slug'=>$slug,'reason'=>'term_description_update_failed','message'=>$term_update->get_error_message());
        continue;
    }
    update_term_meta($id, '_en_US_published', '1');
    update_term_meta($id, '_en_US_ready', '1');
    update_term_meta($id, '_en_US_name', $copy['en_name']);
    update_term_meta($id, '_en_US_slug', sanitize_title($copy['en_slug']));
    update_term_meta($id, '_en_US_description', $copy['en']);
    update_term_meta($id, '_emdo_en_hub_summary', $copy['en']);
    update_term_meta($id, '_emdo_es_hub_summary', $copy['es']);

    $image_id = (int) get_term_meta($id, 'thumbnail_id', true);
    if ( ! empty($copy['image_url']) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $marked = get_posts(array(
            'post_type'=>'attachment',
            'post_status'=>'inherit',
            'posts_per_page'=>1,
            'fields'=>'ids',
            'meta_key'=>'_emdo_category_visual_marker',
            'meta_value'=>$copy['image_marker'],
        ));
        $candidate = $marked ? (int) $marked[0] : 0;
        $candidate_file = $candidate ? get_attached_file($candidate) : '';
        $candidate_meta = $candidate ? wp_get_attachment_metadata($candidate) : array();
        $candidate_ok = $candidate > 0
            && is_string($candidate_file) && is_readable($candidate_file)
            && (int) @filesize($candidate_file) === (int) $copy['image_size']
            && (int) ($candidate_meta['width'] ?? 0) === 768
            && (int) ($candidate_meta['height'] ?? 0) === 1152;

        if ( ! $candidate_ok ) {
            if ( $candidate > 0 ) { wp_delete_attachment($candidate, true); }
            $response = wp_remote_get($copy['image_url'], array('timeout'=>60,'redirection'=>5));
            if ( is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response) ) {
                $out['issues'][] = array('slug'=>$slug,'reason'=>'image_download_failed');
                continue;
            }
            $body = wp_remote_retrieve_body($response);
            if ( strlen($body) !== (int) $copy['image_size'] ) {
                $out['issues'][] = array('slug'=>$slug,'reason'=>'image_size_mismatch','bytes'=>strlen($body));
                continue;
            }
            $upload = wp_upload_bits($copy['image_file'], null, $body);
            if ( ! empty($upload['error']) ) {
                $out['issues'][] = array('slug'=>$slug,'reason'=>'image_upload_failed','message'=>$upload['error']);
                continue;
            }
            $filetype = wp_check_filetype(basename($upload['file']), null);
            $candidate = wp_insert_attachment(array(
                'post_mime_type'=>$filetype['type'] ?: 'image/webp',
                'post_title'=>$copy['en_name'],
                'post_content'=>'',
                'post_status'=>'inherit',
            ), $upload['file']);
            if ( is_wp_error($candidate) || ! $candidate ) {
                $out['issues'][] = array('slug'=>$slug,'reason'=>'attachment_create_failed');
                continue;
            }
            $candidate = (int) $candidate;
            $metadata = wp_generate_attachment_metadata($candidate, $upload['file']);
            if ( is_array($metadata) ) { wp_update_attachment_metadata($candidate, $metadata); }
            update_post_meta($candidate, '_wp_attachment_image_alt', $copy['image_alt']);
            update_post_meta($candidate, '_emdo_category_visual_marker', $copy['image_marker']);
        }
        $image_id = (int) $candidate;
        update_term_meta($id, 'thumbnail_id', $image_id);
    }

    $out['updated'][] = array(
        'id'=>$id,'slug'=>$slug,'count'=>(int)$term->count,
        'en_name'=>(string)get_term_meta($id,'_en_US_name',true),
        'en_slug'=>(string)get_term_meta($id,'_en_US_slug',true),
        'en_description'=>(string)get_term_meta($id,'_en_US_description',true),
        'en_hub_summary'=>(string)get_term_meta($id,'_emdo_en_hub_summary',true),
        'en_published'=>(string)get_term_meta($id,'_en_US_published',true),
        'description_es'=>(string)get_term_field('description',$id,'product_cat','raw'),
        'thumbnail_id'=>$image_id,
        'image_url'=>$image_id ? (string)wp_get_attachment_image_url($image_id,'full') : '',
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
