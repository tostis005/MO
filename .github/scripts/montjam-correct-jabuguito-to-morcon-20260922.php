<?php
/**
 * One-off correction for Montjam product #14636:
 * Jabuguito -> Morcón ibérico, 750 g, €33.
 * Keeps existing product image and producer/category assignments.
 */
if (!defined('ABSPATH')) { fwrite(STDERR,"ABORT: WordPress not loaded\n"); exit(2); }
if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) { fwrite(STDERR,"ABORT: WooCommerce unavailable\n"); exit(3); }

$product_id = 14636;
$product = wc_get_product($product_id);
if (!$product || !$product->is_type('simple')) {
    fwrite(STDERR,"ABORT: expected simple product #{$product_id}\n");
    exit(4);
}

$old_title = $product->get_name();
if ($old_title !== 'Jabuguito ibérico Montjam' && $old_title !== 'Morcón ibérico Montjam') {
    fwrite(STDERR,"ABORT: unexpected product identity: {$old_title}\n");
    exit(5);
}

$producer_slugs = wp_get_object_terms($product_id,'pa_productor',['fields'=>'slugs']);
if (is_wp_error($producer_slugs) || !in_array('montjam',$producer_slugs,true)) {
    fwrite(STDERR,"ABORT: product is not assigned to Montjam\n");
    exit(6);
}

$thumb_before = (int)get_post_thumbnail_id($product_id);
if (!$thumb_before) {
    fwrite(STDERR,"ABORT: product has no featured image to preserve\n");
    exit(7);
}

$title = 'Morcón ibérico Montjam';
$slug  = 'morcon-iberico-montjam';
$short = 'Morcón ibérico Montjam en pieza de 750 g, elaborado en Huelva y presentado envasado al vacío. Un embutido curado de sabor intenso, textura jugosa y corte generoso.';
$description = '<h2>Morcón ibérico Montjam</h2><p>El morcón ibérico Montjam es un embutido curado tradicional elaborado en Huelva, reconocible por su calibre más grueso y su textura jugosa. Frente a otros embutidos de formato más fino, el morcón ofrece un corte amplio y carnoso, con un sabor intenso y equilibrado.</p><p>Esta referencia se presenta en <strong>pieza de aproximadamente 750 g</strong> y envasada al vacío, un formato cómodo para conservarla cerrada hasta el momento de consumo. Es ideal para tablas de ibéricos, aperitivos, celebraciones o para cortar al gusto en casa.</p><h3>Cómo disfrutarlo</h3><p>Para apreciar mejor su aroma y textura, conviene abrir el envase con antelación y servirlo a temperatura ambiente. Cortado en lonchas finas o de grosor medio, combina especialmente bien con pan, aceite de oliva virgen extra y quesos curados.</p><h3>Conservación</h3><p>Conservar en un lugar fresco y seco mientras permanezca cerrado. Una vez abierto, mantener refrigerado y bien protegido, siguiendo las indicaciones del etiquetado.</p>';

$product->set_name($title);
$product->set_slug($slug);
$product->set_short_description($short);
$product->set_description($description);
$product->set_regular_price('33.00');
$product->set_sale_price('');
$product->set_weight('0.75');
$product->save();

wp_set_object_terms($product_id,['Montjam','Morcón ibérico','Embutidos ibéricos','Huelva'],'product_tag',false);
update_post_meta($product_id,'_yoast_wpseo_focuskw','morcón ibérico Montjam');
update_post_meta($product_id,'_yoast_wpseo_title','Morcón ibérico Montjam 750 g | Mercado de Origen');
update_post_meta($product_id,'_yoast_wpseo_metadesc','Compra morcón ibérico Montjam en pieza de 750 g, elaborado en Huelva y presentado envasado al vacío.');
update_post_meta($product_id,'_montjam_morcon_correction_version','2026-09-22-v1');

wc_delete_product_transients($product_id);
clean_post_cache($product_id);
wp_cache_flush();

$fresh = wc_get_product($product_id);
$thumb_after = (int)get_post_thumbnail_id($product_id);
$producer_after = wp_get_object_terms($product_id,'pa_productor',['fields'=>'slugs']);
$cats_after = wp_get_object_terms($product_id,'product_cat',['fields'=>'slugs']);

$result = [
    'id'=>$product_id,
    'title'=>$fresh ? $fresh->get_name() : '',
    'slug'=>get_post_field('post_name',$product_id),
    'price'=>$fresh ? $fresh->get_price('edit') : '',
    'regular_price'=>$fresh ? $fresh->get_regular_price('edit') : '',
    'weight'=>$fresh ? $fresh->get_weight('edit') : '',
    'thumbnail_before'=>$thumb_before,
    'thumbnail_after'=>$thumb_after,
    'producer'=>is_wp_error($producer_after)?[]:array_values($producer_after),
    'categories'=>is_wp_error($cats_after)?[]:array_values($cats_after),
    'url'=>get_permalink($product_id),
];

$ok = $fresh
    && $result['title'] === $title
    && $result['slug'] === $slug
    && abs((float)$result['regular_price'] - 33.0) < 0.0005
    && abs((float)$result['price'] - 33.0) < 0.0005
    && abs((float)$result['weight'] - 0.75) < 0.0005
    && $thumb_after === $thumb_before
    && !is_wp_error($producer_after)
    && in_array('montjam',$result['producer'],true);

if (!$ok) {
    fwrite(STDERR,"Verification failed: ".wp_json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
    exit(8);
}

echo "MONTJAM_MORCON_CORRECTION_OK: ".wp_json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
