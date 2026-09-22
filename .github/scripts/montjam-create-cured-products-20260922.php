<?php
/**
 * One-off idempotent production creation of four Montjam cured-meat products.
 * Requested 2026-09-22. Creates/updates only the four named products.
 */
if (!defined('ABSPATH')) { fwrite(STDERR,"ABORT: WordPress not loaded\n"); exit(2); }
if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) { fwrite(STDERR,"ABORT: WooCommerce unavailable\n"); exit(3); }

function mjnp_out($label,$value=null){
    if (is_array($value)||is_object($value)) $value=wp_json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    echo $label.($value===null?'':': '.(string)$value)."\n";
}
function mjnp_term($taxonomy,$slug){
    if (!taxonomy_exists($taxonomy)) return null;
    $t=get_term_by('slug',$slug,$taxonomy);
    return ($t && !is_wp_error($t)) ? $t : null;
}
function mjnp_global_attr($taxonomy,array $term_ids,$visible=true,$position=0){
    $attribute_id=wc_attribute_taxonomy_id_by_name($taxonomy);
    if(!$attribute_id) return null;
    $a=new WC_Product_Attribute();
    $a->set_id((int)$attribute_id);
    $a->set_name($taxonomy);
    $a->set_options(array_map('intval',$term_ids));
    $a->set_position((int)$position);
    $a->set_visible((bool)$visible);
    $a->set_variation(false);
    return $a;
}
function mjnp_media_from_local($source_path,$post_id,$asset_key,$alt){
    $existing=get_posts([
        'post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids',
        'meta_key'=>'_montjam_source_asset','meta_value'=>$asset_key,
    ]);
    if($existing){
        $id=(int)$existing[0];
        wp_update_post(['ID'=>$id,'post_parent'=>$post_id]);
        update_post_meta($id,'_wp_attachment_image_alt',$alt);
        return $id;
    }
    if(!is_readable($source_path)) throw new RuntimeException('Image file not readable: '.$source_path);
    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/media.php';
    require_once ABSPATH.'wp-admin/includes/image.php';
    $tmp=wp_tempnam(basename($source_path));
    if(!$tmp || !copy($source_path,$tmp)) throw new RuntimeException('Could not stage image '.$source_path);
    $file=['name'=>basename($source_path),'tmp_name'=>$tmp];
    $id=media_handle_sideload($file,$post_id,$alt);
    if(is_wp_error($id)){ @unlink($tmp); throw new RuntimeException('Image upload failed: '.$id->get_error_message()); }
    update_post_meta($id,'_montjam_source_asset',$asset_key);
    update_post_meta($id,'_wp_attachment_image_alt',$alt);
    return (int)$id;
}

$vendor=get_user_by('login','montjam');
if(!$vendor) throw new RuntimeException('Montjam vendor user not found');
$producer=mjnp_term('pa_productor','montjam');
if(!$producer) throw new RuntimeException('pa_productor=montjam not found');
$category=get_term_by('slug','embutidos-y-curados','product_cat');
if(!$category || is_wp_error($category)) throw new RuntimeException('Category embutidos-y-curados not found');

$huelva=mjnp_term('pa_origen','huelva');
$bellota=mjnp_term('pa_alimentacion','bellota');
$nodop=mjnp_term('pa_con-dop','no');
$race100=mjnp_term('pa_raza-iberica','100-iberico');

$specs=[
    [
        'key'=>'lomo','slug'=>'lomo-bellota-100-iberico-montjam',
        'title'=>'Lomo de bellota 100% ibérico Montjam',
        'price'=>'68.75','image'=>'/tmp/montjam-lomo.jpg',
        'alt'=>'Lomo de bellota 100% ibérico Montjam envasado al vacío',
        'short'=>'Lomo de bellota 100% ibérico Montjam elaborado en Huelva y presentado en pieza envasada al vacío. Un curado de corte magro, aroma intenso y sabor equilibrado.',
        'description'=>'<h2>Lomo de bellota 100% ibérico Montjam</h2><p>El lomo de bellota 100% ibérico Montjam es una pieza curada elaborada en Huelva a partir de <strong>lomo de cerdo 100% ibérico</strong>. Su formato entero permite cortarlo al gusto y disfrutar de un perfil limpio, aromático y equilibrado, con la textura firme y jugosa característica de un buen lomo ibérico.</p><p>La pieza se presenta <strong>envasada al vacío</strong>, protegida hasta el momento de abrirla. Es una opción especialmente versátil para aperitivos, tablas de ibéricos, celebraciones o para tener en casa un curado que puede ir cortándose según se necesita.</p><h3>Cómo disfrutarlo</h3><p>Para apreciar mejor su aroma y textura, conviene cortar lonchas finas y atemperar el producto unos minutos antes de servir. Combina especialmente bien con pan, aceite de oliva virgen extra y quesos curados.</p><h3>Conservación</h3><p>Conservar en un lugar fresco y seco mientras permanezca cerrado. Una vez abierto, mantener refrigerado, bien protegido, y consumir progresivamente siguiendo las indicaciones del etiquetado.</p>',
        'focus'=>'lomo de bellota 100% ibérico Montjam',
        'seo'=>'Lomo de bellota 100% ibérico Montjam | Mercado de Origen',
        'meta'=>'Compra lomo de bellota 100% ibérico Montjam, elaborado en Huelva y presentado en pieza envasada al vacío.',
        'tags'=>['Montjam','Lomo ibérico','Lomo de bellota','100% ibérico','Embutidos ibéricos','Huelva'],
        'bellota'=>true,'race100'=>true,
    ],
    [
        'key'=>'chorizo','slug'=>'chorizo-iberico-bellota-montjam',
        'title'=>'Chorizo ibérico de bellota Montjam',
        'price'=>'24.75','image'=>'/tmp/montjam-chorizo.jpg',
        'alt'=>'Chorizo ibérico de bellota Montjam en pieza envasada al vacío',
        'short'=>'Chorizo ibérico de bellota Montjam elaborado en Huelva, de perfil aromático y especiado, presentado en pieza envasada al vacío para cortar al gusto.',
        'description'=>'<h2>Chorizo ibérico de bellota Montjam</h2><p>El chorizo ibérico de bellota Montjam reúne el carácter de los embutidos curados de Huelva con un perfil sabroso y aromático. La curación de la pieza integra el punto especiado característico del chorizo y deja una textura firme, fácil de cortar en lonchas finas o algo más gruesas según el uso.</p><p>Se presenta <strong>en pieza y envasado al vacío</strong>, un formato práctico para conservarlo cerrado hasta el momento de consumo. Funciona muy bien en tablas de ibéricos, aperitivos, bocadillos y recetas en las que se busca un sabor intenso.</p><h3>Para aperitivos y tablas</h3><p>Puede servirse solo o junto a jamón, paleta, lomo y salchichón. Para una mejor experiencia, es recomendable abrir el envase con antelación y servirlo a una temperatura que permita apreciar su aroma.</p><h3>Conservación</h3><p>Guardar en un lugar fresco y seco mientras esté cerrado. Una vez abierto, conservar refrigerado y bien protegido para mantener sus características durante el consumo.</p>',
        'focus'=>'chorizo ibérico de bellota Montjam',
        'seo'=>'Chorizo ibérico de bellota Montjam | Mercado de Origen',
        'meta'=>'Compra chorizo ibérico de bellota Montjam elaborado en Huelva, curado y presentado en pieza envasada al vacío.',
        'tags'=>['Montjam','Chorizo ibérico','Chorizo de bellota','Embutidos ibéricos','Huelva'],
        'bellota'=>true,'race100'=>false,
    ],
    [
        'key'=>'salchichon','slug'=>'salchichon-iberico-bellota-montjam',
        'title'=>'Salchichón ibérico de bellota Montjam',
        'price'=>'24.75','image'=>'/tmp/montjam-salchichon.jpg',
        'alt'=>'Salchichón ibérico de bellota Montjam en pieza envasada al vacío',
        'short'=>'Salchichón ibérico de bellota Montjam elaborado en Huelva, con sabor equilibrado y especiado suave, presentado en pieza envasada al vacío.',
        'description'=>'<h2>Salchichón ibérico de bellota Montjam</h2><p>El salchichón ibérico de bellota Montjam es un embutido curado de perfil equilibrado, pensado para quienes prefieren un sabor especiado más suave que el del chorizo. La pieza ofrece un corte firme y aromático, adecuado tanto para lonchas finas como para cortes algo más generosos.</p><p>Elaborado en Huelva y presentado <strong>en pieza envasada al vacío</strong>, resulta cómodo para conservarlo cerrado y abrirlo cuando se vaya a consumir. Es una referencia muy versátil para tablas, aperitivos, bocadillos y reuniones.</p><h3>Cómo servirlo</h3><p>Cortado fino, puede combinarse con lomo, chorizo, jamón o paleta para preparar una tabla variada de ibéricos. Atemperarlo ligeramente antes de servir ayuda a que despliegue mejor sus aromas.</p><h3>Conservación</h3><p>Conservar en un lugar fresco y seco mientras el envase permanezca cerrado. Tras abrirlo, mantener refrigerado y bien protegido.</p>',
        'focus'=>'salchichón ibérico de bellota Montjam',
        'seo'=>'Salchichón ibérico de bellota Montjam | Mercado de Origen',
        'meta'=>'Compra salchichón ibérico de bellota Montjam elaborado en Huelva, curado y presentado en pieza envasada al vacío.',
        'tags'=>['Montjam','Salchichón ibérico','Salchichón de bellota','Embutidos ibéricos','Huelva'],
        'bellota'=>true,'race100'=>false,
    ],
    [
        'key'=>'jabuguito','slug'=>'jabuguito-iberico-montjam',
        'title'=>'Jabuguito ibérico Montjam',
        'price'=>'15.125','image'=>'/tmp/montjam-jabuguito.jpg',
        'alt'=>'Jabuguito ibérico Montjam envasado al vacío',
        'short'=>'Jabuguito ibérico Montjam, un embutido curado de pequeño formato elaborado en Huelva y envasado al vacío, ideal para aperitivos, tapas y cocina.',
        'description'=>'<h2>Jabuguito ibérico Montjam</h2><p>El Jabuguito ibérico Montjam es un embutido curado de pequeño formato, ligado a la tradición charcutera de la Sierra de Huelva. Su tamaño lo hace especialmente práctico para servir en aperitivos, tapas o tablas, pero también para utilizar en cocina cuando se busca aportar el sabor de un embutido curado.</p><p>Se presenta <strong>envasado al vacío</strong> para proteger el producto hasta su apertura. Su formato facilita consumirlo en varias ocasiones y combinarlo con otros ibéricos, quesos, pan o elaboraciones calientes.</p><h3>Un formato muy versátil</h3><p>Puede cortarse en rodajas para servir directamente o incorporarse a recetas y guisos. Para degustarlo como aperitivo, conviene atemperarlo ligeramente antes de servir.</p><h3>Conservación</h3><p>Mantener en un lugar fresco y seco mientras permanezca cerrado. Una vez abierto, conservar refrigerado y bien protegido, siguiendo siempre las indicaciones del etiquetado.</p>',
        'focus'=>'Jabuguito ibérico Montjam',
        'seo'=>'Jabuguito ibérico Montjam | Mercado de Origen',
        'meta'=>'Compra Jabuguito ibérico Montjam, embutido curado de pequeño formato elaborado en Huelva y presentado envasado al vacío.',
        'tags'=>['Montjam','Jabuguito','Embutidos ibéricos','Huelva'],
        'bellota'=>false,'race100'=>false,
    ],
];

$results=[];
foreach($specs as $spec){
    $post=get_page_by_path($spec['slug'],OBJECT,'product');
    $product_id=$post ? (int)$post->ID : 0;
    if($product_id){
        $product=wc_get_product($product_id);
        if(!$product || !$product->is_type('simple')) throw new RuntimeException('Existing slug is not simple product: '.$spec['slug']);
    } else {
        $product=new WC_Product_Simple();
    }

    $product->set_name($spec['title']);
    $product->set_slug($spec['slug']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_description($spec['description']);
    $product->set_short_description($spec['short']);
    $product->set_regular_price($spec['price']);
    $product->set_sale_price('');
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_category_ids([(int)$category->term_id]);

    $attrs=[]; $pos=0;
    $pa=mjnp_global_attr('pa_productor',[(int)$producer->term_id],true,$pos++); if($pa)$attrs[]=$pa;
    if($huelva){ $a=mjnp_global_attr('pa_origen',[(int)$huelva->term_id],true,$pos++); if($a)$attrs[]=$a; }
    if($nodop){ $a=mjnp_global_attr('pa_con-dop',[(int)$nodop->term_id],true,$pos++); if($a)$attrs[]=$a; }
    if($spec['bellota'] && $bellota){ $a=mjnp_global_attr('pa_alimentacion',[(int)$bellota->term_id],true,$pos++); if($a)$attrs[]=$a; }
    if($spec['race100'] && $race100){ $a=mjnp_global_attr('pa_raza-iberica',[(int)$race100->term_id],true,$pos++); if($a)$attrs[]=$a; }
    $product->set_attributes($attrs);

    $saved=(int)$product->save();
    if(!$saved) throw new RuntimeException('Product save failed: '.$spec['slug']);
    $product_id=$saved;
    wp_update_post(['ID'=>$product_id,'post_author'=>(int)$vendor->ID]);

    wp_set_object_terms($product_id,[(int)$producer->term_id],'pa_productor',false);
    if($huelva) wp_set_object_terms($product_id,[(int)$huelva->term_id],'pa_origen',false);
    if($nodop) wp_set_object_terms($product_id,[(int)$nodop->term_id],'pa_con-dop',false);
    if($spec['bellota'] && $bellota) wp_set_object_terms($product_id,[(int)$bellota->term_id],'pa_alimentacion',false);
    if($spec['race100'] && $race100) wp_set_object_terms($product_id,[(int)$race100->term_id],'pa_raza-iberica',false);
    wp_set_object_terms($product_id,$spec['tags'],'product_tag',false);

    update_post_meta($product_id,'_yoast_wpseo_focuskw',$spec['focus']);
    update_post_meta($product_id,'_yoast_wpseo_title',$spec['seo']);
    update_post_meta($product_id,'_yoast_wpseo_metadesc',$spec['meta']);
    update_post_meta($product_id,'_montjam_new_product_version','2026-09-22-v1');

    $attachment_id=mjnp_media_from_local($spec['image'],$product_id,'2026-09-22-'.$spec['key'],$spec['alt']);
    set_post_thumbnail($product_id,$attachment_id);
    delete_post_meta($product_id,'_product_image_gallery');

    wc_delete_product_transients($product_id);
    clean_post_cache($product_id);
    $fresh=wc_get_product($product_id);
    $producer_slugs=wp_get_object_terms($product_id,'pa_productor',['fields'=>'slugs']);
    $cat_slugs=wp_get_object_terms($product_id,'product_cat',['fields'=>'slugs']);

    $row=[
        'id'=>$product_id,'title'=>$fresh?$fresh->get_name():'','slug'=>get_post_field('post_name',$product_id),
        'price'=>$fresh?$fresh->get_price('edit'):'','regular_price'=>$fresh?$fresh->get_regular_price('edit'):'',
        'producer'=>is_wp_error($producer_slugs)?[]:array_values($producer_slugs),
        'categories'=>is_wp_error($cat_slugs)?[]:array_values($cat_slugs),
        'thumbnail_id'=>(int)get_post_thumbnail_id($product_id),
        'url'=>get_permalink($product_id),
    ];
    if(!$fresh || abs((float)$row['regular_price']-(float)$spec['price'])>0.0005 || !in_array('montjam',$row['producer'],true) || !in_array('embutidos-y-curados',$row['categories'],true) || !$row['thumbnail_id']){
        fwrite(STDERR,'Verification failed: '.wp_json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
        exit(20);
    }
    $results[]=$row;
}
wp_cache_flush();
mjnp_out('MONTJAM_NEW_PRODUCTS_OK',['vendor_id'=>(int)$vendor->ID,'products'=>$results]);
