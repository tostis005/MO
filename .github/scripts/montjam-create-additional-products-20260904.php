<?php
/**
 * Create the additional Montjam catalogue pieces requested on 2026-09-04.
 * IMPORTANT: does not edit the three previously-created Montjam products.
 */
if (!defined('ABSPATH')) { exit(1); }
if (!class_exists('WooCommerce') || !class_exists('WC_Product_Variable')) {
    throw new RuntimeException('WooCommerce unavailable');
}
global $wpdb;

function mjan_out($label, $value = null) {
    if (is_array($value) || is_object($value)) $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo $label . ($value === null ? '' : ': ' . (string)$value) . "\n";
}

function mjan_term($taxonomy, $name, $slug) {
    if (!taxonomy_exists($taxonomy)) throw new RuntimeException('Missing taxonomy ' . $taxonomy);
    $term = get_term_by('slug', $slug, $taxonomy);
    if ($term && !is_wp_error($term)) return (int)$term->term_id;
    $created = wp_insert_term($name, $taxonomy, ['slug'=>$slug]);
    if (is_wp_error($created)) throw new RuntimeException($created->get_error_message());
    return (int)$created['term_id'];
}

function mjan_attr($taxonomy, array $ids, $visible=true, $variation=false, $position=0) {
    $attribute = new WC_Product_Attribute();
    $aid = wc_attribute_taxonomy_id_by_name($taxonomy);
    if (!$aid) throw new RuntimeException('Missing global attribute ' . $taxonomy);
    $attribute->set_id((int)$aid);
    $attribute->set_name($taxonomy);
    $attribute->set_options(array_map('intval', $ids));
    $attribute->set_position((int)$position);
    $attribute->set_visible((bool)$visible);
    $attribute->set_variation((bool)$variation);
    return $attribute;
}

function mjan_media($url, $post_id, $alt) {
    $existing = get_posts([
        'post_type'=>'attachment','post_status'=>'inherit','meta_key'=>'_montjam_source_url','meta_value'=>$url,
        'posts_per_page'=>1,'fields'=>'ids'
    ]);
    if ($existing) {
        update_post_meta((int)$existing[0], '_wp_attachment_image_alt', $alt);
        return (int)$existing[0];
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $id = media_sideload_image($url, $post_id, $alt, 'id');
    if (is_wp_error($id)) {
        $response = wp_remote_get($url, [
            'timeout'=>30,'redirection'=>5,
            'headers'=>[
                'User-Agent'=>'Mozilla/5.0 (compatible; ElMercadoDeOrigen/1.0)',
                'Referer'=>'https://www.iberuss.es/',
                'Accept'=>'image/avif,image/webp,image/apng,image/*,*/*;q=0.8'
            ]
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
            mjan_out('IMAGE_WARNING', $url);
            return 0;
        }
        $body = wp_remote_retrieve_body($response);
        if (!$body) return 0;
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $tmp = wp_tempnam($filename);
        file_put_contents($tmp, $body);
        $id = media_handle_sideload(['name'=>$filename,'tmp_name'=>$tmp], $post_id, $alt);
        if (is_wp_error($id)) { @unlink($tmp); mjan_out('IMAGE_WARNING', $url); return 0; }
    }
    update_post_meta((int)$id, '_montjam_source_url', $url);
    update_post_meta((int)$id, '_wp_attachment_image_alt', $alt);
    return (int)$id;
}

function mjan_yith_format($product_id, $kind) {
    global $wpdb;
    $blocks = $wpdb->prefix . 'yith_wapo_blocks';
    $assoc  = $wpdb->prefix . 'yith_wapo_blocks_assoc';
    $addons = $wpdb->prefix . 'yith_wapo_addons';
    foreach ([$blocks,$assoc,$addons] as $t) {
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) !== $t) throw new RuntimeException('Missing YITH table ' . $t);
    }
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) throw new RuntimeException('Invalid variable product ' . $product_id);

    $slice = $kind === 'jamon' ? '50.00' : '35.00';
    $block_name = 'Montjam · Formato · ' . $product_id;
    $block_settings = [
        'name'=>$block_name,'priority'=>'1',
        'rules'=>[
            'show_in'=>'products','show_in_products'=>[(string)$product_id],'show_in_categories'=>'',
            'exclude_products'=>'','exclude_products_products'=>'','exclude_products_categories'=>'',
            'show_to'=>'all','show_to_user_roles'=>'','show_to_membership'=>''
        ]
    ];
    $block_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `$blocks` WHERE name=%s LIMIT 1", $block_name));
    $data = [
        'user_id'=>null,'vendor_id'=>'0','settings'=>maybe_serialize($block_settings),'priority'=>'1.00000','visibility'=>1,
        'last_update'=>current_time('mysql', true),'name'=>$block_name,'product_association'=>'products','exclude_products'=>0,
        'user_association'=>'all','exclude_users'=>0
    ];
    if ($block_id) $wpdb->update($blocks, $data, ['id'=>$block_id]);
    else {
        $data['creation_date'] = current_time('mysql', true);
        if ($wpdb->insert($blocks, $data) === false) throw new RuntimeException('Could not insert YITH block');
        $block_id = (int)$wpdb->insert_id;
    }

    $association_ids = array_merge([$product_id], array_map('intval', $product->get_children()));
    $wpdb->delete($assoc, ['rule_id'=>$block_id, 'type'=>'product']);
    foreach ($association_ids as $object_id) {
        if ($wpdb->insert($assoc, ['rule_id'=>$block_id,'object'=>(string)$object_id,'type'=>'product']) === false) {
            throw new RuntimeException('Could not insert YITH association ' . $object_id);
        }
    }

    $settings = [
        'type'=>'select','title'=>'FORMATO','title_in_cart'=>'no','title_in_cart_opt'=>'','description'=>'','required'=>'',
        'show_image'=>'','image'=>'','image_replacement'=>'','options_images_position'=>'','show_as_toggle'=>'','hide_options_images'=>'',
        'hide_options_label'=>'','hide_options_prices'=>'','hide_products_prices'=>'','show_add_to_cart'=>'','show_sku'=>'','show_stock'=>'',
        'show_quantity'=>'','show_in_a_grid'=>'','options_per_row'=>'','options_width'=>'','select_width'=>'','image_position'=>'',
        'label_content_align'=>'','image_equal_height'=>'','images_height'=>'','label_position'=>'','label_padding'=>'','description_position'=>'',
        'product_out_of_stock'=>'','enable_rules'=>'','enable_rules_variations'=>'','conditional_logic_display'=>'show',
        'conditional_rule_variations'=>'','conditional_set_conditions'=>'','conditional_logic_display_if'=>'all',
        'conditional_rule_addon'=>['empty'],'conditional_rule_addon_is'=>[''],'first_options_selected'=>'','first_free_options'=>'',
        'selection_type'=>'','enable_min_max'=>'','min_max_rule'=>'','min_max_value'=>'','sell_individually'=>'no',
        'enable_min_max_numbers'=>'','numbers_min'=>'','numbers_max'=>'','text_content'=>'','heading_text'=>'','heading_type'=>'',
        'heading_color'=>'','separator_style'=>'','separator_width'=>'','separator_size'=>'','separator_color'=>'','conditional_logic'=>[]
    ];
    $labels = ['Pieza entera','Loncheado a cuchillo + huesos + taquitos','Deshuesado'];
    $descs = [
        '',
        $kind === 'jamon'
            ? 'Loncheado a cuchillo por un profesional. Se entrega en sobres al vacío junto con los huesos y los taquitos aprovechables de la pieza.'
            : 'Paleta loncheada a cuchillo por un profesional. Se entrega en sobres al vacío junto con los huesos y los taquitos aprovechables de la pieza.',
        'La pieza se entrega deshuesada y envasada al vacío para facilitar su almacenamiento y corte en casa.'
    ];
    $options = [
        'default'=>['yes','no','no'],'addon_enabled'=>['yes','yes','yes'],'label'=>$labels,'description'=>$descs,'image'=>['','',''],
        'price_method'=>['free','increase','increase'],'price'=>['0.00',$slice,'15.00'],'price_sale'=>['','',''],
        'price_type'=>['fixed','fixed','fixed'],'show_image'=>['no','no','no'],'label_in_cart'=>['no','no','no']
    ];
    $addon_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `$addons` WHERE block_id=%d ORDER BY priority,id LIMIT 1", $block_id));
    $addon_data = ['block_id'=>$block_id,'settings'=>maybe_serialize($settings),'options'=>maybe_serialize($options),'priority'=>'1.00000','visibility'=>1,'last_update'=>current_time('mysql', true)];
    if ($addon_id) $wpdb->update($addons, $addon_data, ['id'=>$addon_id]);
    else {
        $addon_data['creation_date'] = current_time('mysql', true);
        if ($wpdb->insert($addons, $addon_data) === false) throw new RuntimeException('Could not insert YITH addon');
        $addon_id = (int)$wpdb->insert_id;
    }

    if (!function_exists('YITH_WAPO_DB')) throw new RuntimeException('YITH_WAPO_DB unavailable');
    foreach ($product->get_children() as $child_id) {
        $found = array_map('intval', YITH_WAPO_DB()->yith_wapo_get_blocks_by_product($product_id, (int)$child_id, 'yes'));
        if (!in_array($block_id, $found, true)) throw new RuntimeException('YITH block disappears for variation ' . $child_id);
    }
    return ['block_id'=>$block_id,'addon_id'=>$addon_id,'slice'=>$slice,'boneless'=>'15.00','associations'=>$association_ids];
}

$vendor = get_user_by('login', 'montjam');
if (!$vendor) throw new RuntimeException('Montjam vendor missing');
$vendor_id = (int)$vendor->ID;

// Guard: these existing products must not be modified by this task.
$protected_ids = [14264,14271,14275];
$protected_before = [];
foreach ($protected_ids as $pid) {
    $p = get_post($pid);
    if ($p) $protected_before[$pid] = [$p->post_modified_gmt, get_post_meta($pid, '_edit_last', true)];
}

// Existing/global attribute terms; only create missing terms, never reassign protected products.
$term = [];
$term['producer'] = mjan_term('pa_productor','Montjam','montjam');
$term['huelva'] = mjan_term('pa_origen','Huelva','huelva');
$term['bellota'] = mjan_term('pa_alimentacion','Bellota','bellota');
$term['cebo_campo'] = mjan_term('pa_alimentacion','Cebo de campo','cebo-de-campo');
$term['dop_yes'] = mjan_term('pa_con-dop','Sí','si');
$term['dop_no'] = mjan_term('pa_con-dop','No','no');
$term['jamon'] = mjan_term('pa_tipo-pieza','Jamón','jamon');
$term['paleta'] = mjan_term('pa_tipo-pieza','Paleta','paleta');
$term['bellota100'] = mjan_term('pa_calidad','Bellota 100% ibérico','bellota-100-iberico');
$term['cebo50'] = mjan_term('pa_calidad','Cebo de campo ibérico 50%','cebo-de-campo-iberico-50');
$term['race100'] = mjan_term('pa_raza-iberica','100% ibérico','100-iberico');
$term['race50'] = mjan_term('pa_raza-iberica','50% ibérico','50-iberico');
$term['cur38'] = mjan_term('pa_curacion','38 meses mínimo','38-meses-minimo');
$term['cur24'] = mjan_term('pa_curacion','24 meses mínimo','24-meses-minimo');
$term['cur20'] = mjan_term('pa_curacion','20 meses mínimo','20-meses-minimo');
$term['prep_piece'] = mjan_term('pa_preparacion','Pieza entera','pieza-entera');
$term['prep_knife'] = mjan_term('pa_preparacion','Cortado a cuchillo','cortado-a-cuchillo');
$term['prep_slice'] = mjan_term('pa_preparacion','Loncheado','loncheado');
$term['prep_boneless'] = mjan_term('pa_preparacion','Deshuesado','deshuesado');

$sizes = [];
foreach ([
    '5-55-kg'=>'5-5,5 Kg','55-6-kg'=>'5,5-6 Kg','6-65-kg'=>'6-6,5 Kg','65-7-kg'=>'6,5-7 Kg',
    '7-75-kg'=>'7-7,5 Kg','75-8-kg'=>'7,5-8 Kg'
] as $slug=>$name) $sizes[$slug] = mjan_term('pa_tamano',$name,$slug);

$nutrition_ham = '<h3>Ingredientes e información nutricional</h3><p><strong>Ingredientes:</strong> jamón de cerdo ibérico, sal, azúcar, corrector de la acidez E-331iii, conservadores E-252 y E-250 y antioxidante E-301. Sin gluten y sin lactosa.</p><p><strong>Valores nutricionales por 100 g:</strong> 383,20 kcal; proteínas 48,59 g; hidratos de carbono &lt;0,1 g; grasas 20,98 g (saturadas 4,72 g); azúcares &lt;0,1 g; sal 2,10 g.</p>';
$nutrition_paleta = '<h3>Ingredientes e información nutricional</h3><p><strong>Ingredientes:</strong> paleta de cerdo ibérico, sal, azúcar, corrector de la acidez E-331iii, conservadores E-252 y E-250 y antioxidante E-301. Sin gluten y sin lactosa.</p><p><strong>Valores nutricionales por 100 g:</strong> 383,20 kcal; proteínas 48,59 g; hidratos de carbono &lt;0,1 g; grasas 20,98 g (saturadas 4,72 g); azúcares &lt;0,1 g; sal 2,10 g.</p>';

$specs = [
    [
        'kind'=>'jamon','slug'=>'jamon-bellota-100-iberico-dop-jabugo-onofre-montjam',
        'title'=>'Jamón de bellota 100% ibérico D.O.P. Jabugo Onofre (brida negra)',
        'short'=>'Jamón de bellota 100% ibérico D.O.P. Jabugo Onofre, elaborado por la casa Montjam en Huelva y amparado por la Denominación de Origen Protegida Jabugo. Curación prolongada, aroma profundo y sabor persistente.',
        'description'=>'<h2>Jamón de bellota 100% ibérico D.O.P. Jabugo Onofre</h2><p>Este jamón de bellota 100% ibérico pertenece a la línea <strong>Onofre</strong>, la marca con la que Pedro Parra e Hijos, la casa elaboradora de Montjam, comercializa sus jamones y paletas amparados por la <strong>D.O.P. Jabugo</strong>. Se elabora en Huelva siguiendo una curación lenta en secaderos y bodegas naturales.</p><p>Procede de cerdo <strong>100% ibérico alimentado con bellota durante la montanera</strong> y se identifica con brida negra. Su curación mínima de 38 meses favorece un perfil aromático intenso, grasa infiltrada y un sabor largo y equilibrado.</p><h3>Una pieza amparada por la D.O.P. Jabugo</h3><p>La Denominación de Origen Protegida Jabugo certifica el origen y las condiciones de elaboración de la pieza dentro de su zona protegida. En este jamón se combina esa garantía de origen con la experiencia de una casa familiar especializada en ibéricos desde varias generaciones.</p>' . $nutrition_ham . '<h3>Conservación</h3><p>Conservar en un lugar fresco y seco, protegido de fuentes de calor. Una vez iniciado, conviene proteger la superficie de corte con una fina loncha de grasa de la propia pieza para mantener mejor su textura y aroma.</p>',
        'focus'=>'jamón de bellota 100% ibérico DOP Jabugo Onofre',
        'seo_title'=>'Jamón 100% ibérico D.O.P. Jabugo Onofre | Mercado de Origen',
        'meta'=>'Jamón de bellota 100% ibérico D.O.P. Jabugo Onofre, elaborado en Huelva por la casa Montjam, con curación prolongada y brida negra.',
        'tags'=>['Montjam','Onofre','Jamón ibérico','Jamón de bellota','100% ibérico','D.O.P. Jabugo','Brida negra','Huelva'],
        'terms'=>['food'=>'bellota','dop'=>'dop_yes','piece'=>'jamon','quality'=>'bellota100','race'=>'race100','cur'=>'cur38'],
        'variations'=>[
            ['slug'=>'6-65-kg','price'=>'301.88','sku'=>'MONTJAM-JDOP-600-650'],
            ['slug'=>'65-7-kg','price'=>'326.03','sku'=>'MONTJAM-JDOP-650-700'],
            ['slug'=>'7-75-kg','price'=>'350.18','sku'=>'MONTJAM-JDOP-700-750'],
            ['slug'=>'75-8-kg','price'=>'351.14','sku'=>'MONTJAM-JDOP-750-800'],
        ],
        'images'=>['https://www.iberuss.es/img/products/0000000607_0.jpg','https://www.iberuss.es/img/products/0000000607_1.jpg'],
        'alt'=>'Jamón de bellota 100% ibérico D.O.P. Jabugo Onofre brida negra'
    ],
    [
        'kind'=>'jamon','slug'=>'jamon-cebo-de-campo-iberico-50-montjam',
        'title'=>'Jamón de cebo de campo ibérico 50% raza ibérica Montjam (brida verde)',
        'short'=>'Jamón de cebo de campo ibérico 50% raza ibérica Montjam, elaborado en El Repilado, Huelva. Brida verde, curación natural y un perfil sabroso, equilibrado y aromático.',
        'description'=>'<h2>Jamón de cebo de campo ibérico 50% Montjam, brida verde</h2><p>El jamón de cebo de campo ibérico 50% raza ibérica Montjam se elabora en El Repilado, Huelva, en el entorno de la Sierra de Aracena y Picos de Aroche. Es una pieza de <strong>brida verde</strong>, procedente de cerdo ibérico criado con acceso a los recursos del campo y complementado con alimentación a base de piensos.</p><p>Su curación mínima de 24 meses en secaderos y bodegas naturales permite desarrollar un sabor equilibrado, una textura agradable y un aroma característico, ofreciendo una alternativa especialmente interesante dentro de los ibéricos de cebo de campo.</p><h3>Ibérico de Huelva elaborado por Montjam</h3><p>Montjam pertenece a Pedro Parra e Hijos, una casa familiar dedicada a la elaboración de jamones, paletas y embutidos en El Repilado. La combinación del clima local y una curación pausada forma parte del carácter de sus piezas.</p><h3>Ingredientes</h3><p>Jamón de cerdo ibérico, sal, azúcar, corrector de la acidez E-331iii, conservadores E-252 y E-250 y antioxidante E-301. Sin gluten.</p><h3>Conservación</h3><p>Mantener en un lugar fresco y seco, a temperatura estable. Una vez empezado, proteger la zona de corte con grasa de la propia pieza y evitar dejarla expuesta al aire durante periodos prolongados.</p>',
        'focus'=>'jamón de cebo de campo ibérico 50% Montjam',
        'seo_title'=>'Jamón de cebo de campo ibérico 50% Montjam | Mercado de Origen',
        'meta'=>'Jamón de cebo de campo ibérico 50% Montjam, brida verde, elaborado en El Repilado, Huelva, con curación natural y sabor equilibrado.',
        'tags'=>['Montjam','Jamón ibérico','Jamón de cebo de campo','50% ibérico','Brida verde','Huelva'],
        'terms'=>['food'=>'cebo_campo','dop'=>'dop_no','piece'=>'jamon','quality'=>'cebo50','race'=>'race50','cur'=>'cur24'],
        'variations'=>[
            ['slug'=>'6-65-kg','price'=>'180.00','sku'=>'MONTJAM-JV-600-650'],
            ['slug'=>'65-7-kg','price'=>'194.40','sku'=>'MONTJAM-JV-650-700'],
            ['slug'=>'7-75-kg','price'=>'208.80','sku'=>'MONTJAM-JV-700-750'],
            ['slug'=>'75-8-kg','price'=>'223.20','sku'=>'MONTJAM-JV-750-800'],
        ],
        'images'=>['https://www.iberuss.es/img/products/0000000537_0.jpg','https://www.iberuss.es/img/products/0000000537_1.jpg'],
        'alt'=>'Jamón de cebo de campo ibérico 50% Montjam brida verde'
    ],
    [
        'kind'=>'paleta','slug'=>'paleta-bellota-100-iberica-dop-jabugo-onofre-montjam',
        'title'=>'Paleta de bellota 100% ibérica D.O.P. Jabugo Onofre (brida negra)',
        'short'=>'Paleta de bellota 100% ibérica D.O.P. Jabugo Onofre, elaborada por la casa Montjam en Huelva. Brida negra, carácter intenso y curación natural amparada por la D.O.P. Jabugo.',
        'description'=>'<h2>Paleta de bellota 100% ibérica D.O.P. Jabugo Onofre</h2><p>La paleta de bellota 100% ibérica D.O.P. Jabugo Onofre forma parte de la gama protegida que Pedro Parra e Hijos, la casa elaboradora de Montjam, comercializa bajo la marca Onofre. Se produce en Huelva y está amparada por la <strong>Denominación de Origen Protegida Jabugo</strong>.</p><p>Procede de cerdo <strong>100% ibérico alimentado con bellota</strong>, se identifica con brida negra y cuenta con una curación aproximada de 24 meses, que potencia su aroma, jugosidad y persistencia en boca.</p><h3>Carácter de la paleta ibérica de bellota</h3><p>La paleta ofrece una intensidad característica y una relación muy marcada entre carne y grasa infiltrada. La elaboración en secaderos y bodegas naturales contribuye a desarrollar el perfil profundo y aromático propio de este tipo de pieza.</p>' . $nutrition_paleta . '<h3>Conservación</h3><p>Guardar en un lugar fresco y seco. Una vez iniciada, proteger la superficie de corte con grasa de la propia paleta y consumir con regularidad para conservar mejor su aroma y textura.</p>',
        'focus'=>'paleta de bellota 100% ibérica DOP Jabugo Onofre',
        'seo_title'=>'Paleta 100% ibérica D.O.P. Jabugo Onofre | Mercado de Origen',
        'meta'=>'Paleta de bellota 100% ibérica D.O.P. Jabugo Onofre, elaborada en Huelva por la casa Montjam, con brida negra y curación natural.',
        'tags'=>['Montjam','Onofre','Paleta ibérica','Paleta de bellota','100% ibérico','D.O.P. Jabugo','Brida negra','Huelva'],
        'terms'=>['food'=>'bellota','dop'=>'dop_yes','piece'=>'paleta','quality'=>'bellota100','race'=>'race100','cur'=>'cur24'],
        'variations'=>[
            ['slug'=>'5-55-kg','price'=>'169.05','sku'=>'MONTJAM-PDOP-500-550'],
            ['slug'=>'55-6-kg','price'=>'185.15','sku'=>'MONTJAM-PDOP-550-600'],
        ],
        'images'=>['https://www.iberuss.es/img/products/0000000587_0.jpg'],
        'alt'=>'Paleta de bellota 100% ibérica D.O.P. Jabugo Onofre brida negra'
    ],
    [
        'kind'=>'paleta','slug'=>'paleta-cebo-de-campo-iberica-50-montjam',
        'title'=>'Paleta de cebo de campo ibérica 50% raza ibérica Montjam (brida verde)',
        'short'=>'Paleta de cebo de campo ibérica 50% raza ibérica Montjam, elaborada en El Repilado, Huelva. Brida verde, curación natural y sabor intenso y equilibrado.',
        'description'=>'<h2>Paleta de cebo de campo ibérica 50% Montjam, brida verde</h2><p>La paleta de cebo de campo ibérica 50% raza ibérica Montjam se elabora en El Repilado, Huelva, y se identifica con <strong>brida verde</strong>. Procede de cerdo ibérico criado con acceso a los recursos naturales del campo y alimentación complementada con piensos.</p><p>Su curación mínima de 20 meses en secaderos y bodegas naturales favorece un perfil sabroso y aromático, con la intensidad característica de la paleta y una textura jugosa.</p><h3>Una paleta ibérica elaborada en Huelva</h3><p>Montjam forma parte de Pedro Parra e Hijos, una casa familiar especializada en ibéricos cuya elaboración se desarrolla en la Sierra de Aracena y Picos de Aroche. El clima de la zona y la curación pausada aportan carácter a sus piezas.</p><h3>Ingredientes</h3><p>Paleta de cerdo ibérico, sal, azúcar, corrector de la acidez E-331iii, conservadores E-252 y E-250 y antioxidante E-301. Sin gluten y sin lactosa.</p><h3>Conservación</h3><p>Conservar en un lugar fresco y seco. Una vez comenzada, cubrir la superficie de corte con grasa de la propia paleta y evitar la exposición prolongada al aire.</p>',
        'focus'=>'paleta de cebo de campo ibérica 50% Montjam',
        'seo_title'=>'Paleta de cebo de campo ibérica 50% Montjam | Mercado de Origen',
        'meta'=>'Paleta de cebo de campo ibérica 50% Montjam, brida verde, elaborada en El Repilado, Huelva, con curación natural y sabor intenso.',
        'tags'=>['Montjam','Paleta ibérica','Paleta de cebo de campo','50% ibérico','Brida verde','Huelva'],
        'terms'=>['food'=>'cebo_campo','dop'=>'dop_no','piece'=>'paleta','quality'=>'cebo50','race'=>'race50','cur'=>'cur20'],
        'variations'=>[
            ['slug'=>'5-55-kg','price'=>'96.39','sku'=>'MONTJAM-PV-500-550'],
            ['slug'=>'55-6-kg','price'=>'105.57','sku'=>'MONTJAM-PV-550-600'],
        ],
        'images'=>['https://www.iberuss.es/img/products/0000000538_0.jpg','https://www.iberuss.es/img/products/0000000538_1.jpg'],
        'alt'=>'Paleta de cebo de campo ibérica 50% Montjam brida verde'
    ],
];

$cat = get_term_by('slug','jamones-paletas','product_cat');
if (!$cat) {
    $newcat = wp_insert_term('Jamones y paletas','product_cat',['slug'=>'jamones-paletas']);
    if (is_wp_error($newcat)) throw new RuntimeException($newcat->get_error_message());
    $cat_id = (int)$newcat['term_id'];
} else $cat_id = (int)$cat->term_id;

$common_prep_ids = [$term['prep_piece'],$term['prep_knife'],$term['prep_slice'],$term['prep_boneless']];
$created = [];
foreach ($specs as $spec) {
    $existing = get_page_by_path($spec['slug'], OBJECT, 'product');
    if ($existing) {
        // Idempotency is limited to the new four pieces; never touch the earlier catalogue.
        $product = wc_get_product((int)$existing->ID);
        if (!$product || !$product->is_type('variable')) throw new RuntimeException('Existing target slug is not variable: ' . $spec['slug']);
    } else {
        $product = new WC_Product_Variable();
    }
    $product->set_name($spec['title']);
    $product->set_slug($spec['slug']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_description($spec['description']);
    $product->set_short_description($spec['short']);
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_category_ids([$cat_id]);

    $map = [
        'pa_productor'=>[$term['producer']],
        'pa_origen'=>[$term['huelva']],
        'pa_alimentacion'=>[$term[$spec['terms']['food']]],
        'pa_con-dop'=>[$term[$spec['terms']['dop']]],
        'pa_tipo-pieza'=>[$term[$spec['terms']['piece']]],
        'pa_calidad'=>[$term[$spec['terms']['quality']]],
        'pa_raza-iberica'=>[$term[$spec['terms']['race']]],
        'pa_curacion'=>[$term[$spec['terms']['cur']]],
        'pa_preparacion'=>$common_prep_ids,
    ];
    $attrs = []; $pos = 0;
    foreach ($map as $tax=>$ids) $attrs[] = mjan_attr($tax,$ids,true,false,$pos++);
    $size_ids = array_map(fn($v)=>(int)$sizes[$v['slug']], $spec['variations']);
    $attrs[] = mjan_attr('pa_tamano',$size_ids,true,true,$pos++);
    $product->set_attributes($attrs);
    $product->set_default_attributes(['pa_tamano'=>$spec['variations'][0]['slug']]);
    $product_id = (int)$product->save();
    if (!$product_id) throw new RuntimeException('Product save failed ' . $spec['slug']);
    wp_update_post(['ID'=>$product_id,'post_author'=>$vendor_id]);
    foreach ($map as $tax=>$ids) wp_set_object_terms($product_id,array_map('intval',$ids),$tax,false);
    wp_set_object_terms($product_id,$size_ids,'pa_tamano',false);
    wp_set_object_terms($product_id,$spec['tags'],'product_tag',false);
    update_post_meta($product_id,'_yoast_wpseo_focuskw',$spec['focus']);
    update_post_meta($product_id,'_yoast_wpseo_title',$spec['seo_title']);
    update_post_meta($product_id,'_yoast_wpseo_metadesc',$spec['meta']);
    update_post_meta($product_id,'_montjam_additional_import','2026-09-04-v1');

    $children_by_size = [];
    foreach ($product->get_children() as $cid) {
        $c = wc_get_product($cid);
        if ($c) $children_by_size[$c->get_attribute('pa_tamano')] = (int)$cid;
    }
    $wanted_ids = [];
    foreach ($spec['variations'] as $v) {
        $vid = $children_by_size[$v['slug']] ?? 0;
        $variation = $vid ? new WC_Product_Variation($vid) : new WC_Product_Variation();
        $variation->set_parent_id($product_id);
        $variation->set_attributes(['pa_tamano'=>$v['slug']]);
        $variation->set_regular_price($v['price']);
        $variation->set_sale_price('');
        $variation->set_status('publish');
        $variation->set_manage_stock(false);
        $variation->set_stock_status('instock');
        $variation->set_virtual(false);
        $variation->set_downloadable(false);
        $variation->set_sku($v['sku']);
        $wanted_ids[] = (int)$variation->save();
    }
    // Only prune child variations belonging to these four new products.
    foreach ($product->get_children() as $cid) {
        if (!in_array((int)$cid,$wanted_ids,true)) {
            $c = wc_get_product($cid);
            if ($c && strpos((string)$c->get_sku(),'MONTJAM-') === 0) $c->delete(true);
        }
    }
    WC_Product_Variable::sync($product_id);
    wc_delete_product_transients($product_id);
    $product = wc_get_product($product_id);

    $imgs = [];
    foreach ($spec['images'] as $i=>$url) {
        $aid = mjan_media($url,$product_id,$spec['alt'] . ($i ? ' - detalle' : ''));
        if ($aid) $imgs[] = $aid;
    }
    if ($imgs) {
        set_post_thumbnail($product_id,$imgs[0]);
        update_post_meta($product_id,'_product_image_gallery',count($imgs)>1 ? implode(',',array_slice($imgs,1)) : '');
    }

    $format = mjan_yith_format($product_id,$spec['kind']);
    $variation_summary = [];
    foreach (wc_get_product($product_id)->get_children() as $cid) {
        $c = wc_get_product($cid);
        if ($c) $variation_summary[] = ['id'=>(int)$cid,'size'=>$c->get_attribute('pa_tamano'),'price'=>$c->get_regular_price(),'sku'=>$c->get_sku()];
    }
    $created[] = [
        'id'=>$product_id,'title'=>get_the_title($product_id),'url'=>get_permalink($product_id),'variations'=>$variation_summary,
        'format'=>$format,'featured'=>(int)get_post_thumbnail_id($product_id),'gallery'=>get_post_meta($product_id,'_product_image_gallery',true)
    ];
}

foreach ($protected_before as $pid=>$before) {
    $p = get_post($pid);
    $after = $p ? [$p->post_modified_gmt,get_post_meta($pid,'_edit_last',true)] : null;
    if ($after !== $before) throw new RuntimeException('Protected existing product was modified: ' . $pid);
}

wp_cache_flush();
mjan_out('MONTJAM_ADDITIONAL_PRODUCTS_SUCCESS', [
    'vendor_id'=>$vendor_id,
    'protected_products_untouched'=>array_keys($protected_before),
    'created_or_updated'=>$created,
    'note'=>'Rows without a margin-side price were intentionally not created.'
]);
