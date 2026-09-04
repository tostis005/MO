<?php
/**
 * Idempotent production importer for Mont Jam products.
 * Creates/updates the Mont Jam vendor, 3 variable WooCommerce products,
 * weight variations, SEO metadata, provisional images, and YITH preparation add-ons.
 */
if (!defined('ABSPATH')) { exit(1); }

if (!class_exists('WooCommerce') || !class_exists('WC_Product_Variable')) {
    fwrite(STDERR, "WooCommerce is not available.\n");
    exit(2);
}

global $wpdb;

function mj_log($label, $value = null) {
    if ($value === null) {
        echo $label . "\n";
        return;
    }
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ': ' . (string) $value . "\n";
}

function mj_term_id($taxonomy, $name, $slug) {
    if (!taxonomy_exists($taxonomy)) {
        throw new RuntimeException("Taxonomy not found: {$taxonomy}");
    }
    $term = get_term_by('slug', $slug, $taxonomy);
    if ($term && !is_wp_error($term)) return (int) $term->term_id;
    $created = wp_insert_term($name, $taxonomy, ['slug' => $slug]);
    if (is_wp_error($created)) {
        throw new RuntimeException("Could not create term {$taxonomy}/{$slug}: " . $created->get_error_message());
    }
    return (int) $created['term_id'];
}

function mj_vendor_id() {
    $user = get_user_by('login', 'montjam');
    if (!$user) {
        $user_id = wp_insert_user([
            'user_login'   => 'montjam',
            'user_pass'    => wp_generate_password(40, true, true),
            'user_email'   => '',
            'display_name' => 'Mont Jam',
            'nickname'     => 'Mont Jam',
            'user_nicename'=> 'mont-jam',
            'role'         => 'wcfm_vendor',
        ]);
        if (is_wp_error($user_id)) {
            throw new RuntimeException('Could not create Mont Jam vendor: ' . $user_id->get_error_message());
        }
        $user = get_user_by('id', $user_id);
    } else {
        wp_update_user([
            'ID' => $user->ID,
            'display_name' => 'Mont Jam',
            'nickname' => 'Mont Jam',
            'user_nicename' => 'mont-jam',
        ]);
        $user->set_role('wcfm_vendor');
    }

    update_user_meta($user->ID, 'store_name', 'Mont Jam');
    $profile = get_user_meta($user->ID, 'wcfmmp_profile_settings', true);
    if (!is_array($profile)) $profile = [];
    $profile['store_name'] = 'Mont Jam';
    $profile['store_slug'] = 'mont-jam';
    if (empty($profile['list_banner_type'])) $profile['list_banner_type'] = 'single_img';
    update_user_meta($user->ID, 'wcfmmp_profile_settings', $profile);

    return (int) $user->ID;
}

function mj_attribute($taxonomy, array $term_ids, $visible = true, $variation = false, $position = 0) {
    $attribute = new WC_Product_Attribute();
    $attribute_id = wc_attribute_taxonomy_id_by_name($taxonomy);
    if (!$attribute_id) {
        throw new RuntimeException("WooCommerce global attribute missing: {$taxonomy}");
    }
    $attribute->set_id((int) $attribute_id);
    $attribute->set_name($taxonomy);
    $attribute->set_options(array_map('intval', $term_ids));
    $attribute->set_position((int) $position);
    $attribute->set_visible((bool) $visible);
    $attribute->set_variation((bool) $variation);
    return $attribute;
}

function mj_find_product_by_slug($slug) {
    $post = get_page_by_path($slug, OBJECT, 'product');
    return $post ? (int) $post->ID : 0;
}

function mj_media_from_url($url, $post_id, $desc) {
    // Reuse an attachment previously imported from the same source URL.
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_key' => '_montjam_source_url',
        'meta_value' => $url,
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);
    if ($existing) return (int) $existing[0];

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // First use WP's normal sideloader.
    $id = media_sideload_image($url, $post_id, $desc, 'id');
    if (!is_wp_error($id)) {
        update_post_meta($id, '_montjam_source_url', $url);
        update_post_meta($id, '_wp_attachment_image_alt', $desc);
        return (int) $id;
    }

    // Fallback with browser-like headers for stores that block generic hotlinking.
    $response = wp_remote_get($url, [
        'timeout' => 30,
        'redirection' => 5,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (compatible; ElMercadoDeOrigen/1.0)',
            'Referer' => 'https://www.iberuss.es/',
            'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
        ],
    ]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
        $msg = is_wp_error($response) ? $response->get_error_message() : ('HTTP ' . wp_remote_retrieve_response_code($response));
        mj_log('IMAGE_WARNING', $url . ' -> ' . $msg);
        return 0;
    }

    $body = wp_remote_retrieve_body($response);
    if (!$body) {
        mj_log('IMAGE_WARNING', $url . ' -> empty body');
        return 0;
    }
    $filename = basename(parse_url($url, PHP_URL_PATH));
    $tmp = wp_tempnam($filename);
    file_put_contents($tmp, $body);
    $file = ['name' => $filename, 'tmp_name' => $tmp];
    $id = media_handle_sideload($file, $post_id, $desc);
    if (is_wp_error($id)) {
        @unlink($tmp);
        mj_log('IMAGE_WARNING', $url . ' -> ' . $id->get_error_message());
        return 0;
    }
    update_post_meta($id, '_montjam_source_url', $url);
    update_post_meta($id, '_wp_attachment_image_alt', $desc);
    return (int) $id;
}

function mj_create_or_update_product(array $spec, $vendor_id, $producer_term_id) {
    $existing_id = mj_find_product_by_slug($spec['slug']);
    if ($existing_id) {
        $product = wc_get_product($existing_id);
        if (!$product || !$product->is_type('variable')) {
            wp_set_object_terms($existing_id, 'variable', 'product_type');
            $product = new WC_Product_Variable($existing_id);
        }
    } else {
        $product = new WC_Product_Variable();
    }

    $product->set_name($spec['title']);
    $product->set_slug($spec['slug']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_description($spec['description']);
    $product->set_short_description($spec['short_description']);
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_sold_individually(false);

    $cat = get_term_by('slug', 'jamones-paletas', 'product_cat');
    if (!$cat) {
        $newcat = wp_insert_term('Jamones y paletas', 'product_cat', ['slug'=>'jamones-paletas']);
        if (is_wp_error($newcat)) throw new RuntimeException($newcat->get_error_message());
        $cat_id = (int)$newcat['term_id'];
    } else {
        $cat_id = (int)$cat->term_id;
    }
    $product->set_category_ids([$cat_id]);

    $taxonomy_terms = $spec['taxonomies'];
    $taxonomy_terms['pa_productor'] = [['id'=>$producer_term_id, 'slug'=>'mont-jam']];

    $attributes = [];
    $position = 0;
    foreach ($taxonomy_terms as $taxonomy => $terms) {
        $ids = [];
        foreach ($terms as $t) {
            $ids[] = (int)$t['id'];
        }
        $attributes[] = mj_attribute($taxonomy, $ids, true, false, $position++);
    }
    $size_ids = array_map(function($v){ return (int)$v['term_id']; }, $spec['variations']);
    $attributes[] = mj_attribute('pa_tamano', $size_ids, true, true, $position++);
    $product->set_attributes($attributes);
    $product->set_default_attributes(['pa_tamano' => $spec['variations'][0]['slug']]);

    $product_id = $product->save();
    if (!$product_id) throw new RuntimeException('Product save failed for ' . $spec['slug']);

    wp_update_post(['ID'=>$product_id, 'post_author'=>$vendor_id]);

    foreach ($taxonomy_terms as $taxonomy => $terms) {
        wp_set_object_terms($product_id, array_map(function($t){ return (int)$t['id']; }, $terms), $taxonomy, false);
    }
    wp_set_object_terms($product_id, $size_ids, 'pa_tamano', false);

    // Tags for search/discovery.
    wp_set_object_terms($product_id, $spec['tags'], 'product_tag', false);

    update_post_meta($product_id, '_yoast_wpseo_focuskw', $spec['focus_keyword']);
    update_post_meta($product_id, '_yoast_wpseo_metadesc', $spec['meta_description']);
    update_post_meta($product_id, '_yoast_wpseo_title', $spec['seo_title']);
    update_post_meta($product_id, '_montjam_import_version', '2026-09-04-v1');

    // Upsert variations by pa_tamano and remove obsolete Mont Jam child variations for this parent.
    $wanted_slugs = [];
    $seen_ids = [];
    foreach ($spec['variations'] as $v) {
        $wanted_slugs[] = $v['slug'];
        $variation_id = 0;
        foreach ($product->get_children() as $child_id) {
            $child = wc_get_product($child_id);
            if ($child && $child->get_attribute('pa_tamano') === $v['slug']) {
                $variation_id = (int)$child_id;
                break;
            }
        }
        $variation = $variation_id ? new WC_Product_Variation($variation_id) : new WC_Product_Variation();
        $variation->set_parent_id($product_id);
        $variation->set_attributes(['pa_tamano' => $v['slug']]);
        $variation->set_regular_price(number_format((float)$v['price'], 2, '.', ''));
        $variation->set_sale_price('');
        $variation->set_status('publish');
        $variation->set_manage_stock(false);
        $variation->set_stock_status('instock');
        $variation->set_virtual(false);
        $variation->set_downloadable(false);
        $variation->set_sku($v['sku']);
        $vid = $variation->save();
        $seen_ids[] = (int)$vid;
    }

    foreach ($product->get_children() as $child_id) {
        if (!in_array((int)$child_id, $seen_ids, true)) {
            $child = wc_get_product($child_id);
            if ($child && strpos((string)$child->get_sku(), 'MJ-') === 0) {
                $child->delete(true);
            }
        }
    }

    WC_Product_Variable::sync($product_id);
    wc_delete_product_transients($product_id);

    // Provisional images from Iberuss, explicitly marked with source metadata for easy replacement later.
    $image_ids = [];
    foreach ($spec['images'] as $i => $url) {
        $id = mj_media_from_url($url, $product_id, $spec['image_alt'] . ($i ? ' - detalle' : ''));
        if ($id) $image_ids[] = $id;
    }
    if ($image_ids) {
        set_post_thumbnail($product_id, $image_ids[0]);
        if (count($image_ids) > 1) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', array_slice($image_ids, 1)));
        }
    }

    return (int)$product_id;
}

function mj_yith_addon($product_id, $kind) {
    global $wpdb;
    $blocks = $wpdb->prefix . 'yith_wapo_blocks';
    $assoc  = $wpdb->prefix . 'yith_wapo_blocks_assoc';
    $addons = $wpdb->prefix . 'yith_wapo_addons';

    $existing_tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->esc_like($wpdb->prefix . 'yith_wapo') . "%'");
    foreach ([$blocks,$assoc,$addons] as $t) {
        if (!in_array($t, $existing_tables, true)) {
            throw new RuntimeException('YITH WAPO table missing: ' . $t);
        }
    }

    $is_jamon = ($kind === 'jamon');
    $slice_price = $is_jamon ? '50.00' : '35.00';
    $labels = ['Pieza entera', 'Loncheado a cuchillo + huesos + taquitos', 'Deshuesado'];
    $descriptions = [
        '',
        $is_jamon
            ? 'Loncheado a cuchillo por un profesional. Se entrega en sobres al vacío junto con los huesos y los taquitos aprovechables de la pieza.'
            : 'Paleta loncheada a cuchillo por un profesional. Se entrega en sobres al vacío junto con los huesos y los taquitos aprovechables de la pieza.',
        'La pieza se entrega deshuesada y envasada al vacío para facilitar su almacenamiento y corte en casa.'
    ];

    $block_name = 'Mont Jam · Formato · ' . $product_id;
    $block_settings = [
        'name' => $block_name,
        'priority' => '1',
        'rules' => [
            'show_in' => 'products',
            'show_in_products' => [(string)$product_id],
            'show_in_categories' => '',
            'exclude_products' => '',
            'exclude_products_products' => '',
            'exclude_products_categories' => '',
            'show_to' => 'all',
            'show_to_user_roles' => '',
            'show_to_membership' => '',
        ],
    ];

    $block_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `$blocks` WHERE name=%s LIMIT 1", $block_name));
    $block_data = [
        'user_id' => null,
        'vendor_id' => '0',
        'settings' => maybe_serialize($block_settings),
        'priority' => '1.00000',
        'visibility' => 1,
        'last_update' => current_time('mysql', true),
        'name' => $block_name,
        'product_association' => 'products',
        'exclude_products' => 0,
        'user_association' => 'all',
        'exclude_users' => 0,
    ];
    if ($block_id) {
        $wpdb->update($blocks, $block_data, ['id'=>$block_id]);
    } else {
        $block_data['creation_date'] = current_time('mysql', true);
        $wpdb->insert($blocks, $block_data);
        $block_id = (int)$wpdb->insert_id;
    }
    if (!$block_id) throw new RuntimeException('Could not create YITH block for product ' . $product_id);

    // Match the association type used by existing blocks if present.
    $assoc_type = $wpdb->get_var("SELECT type FROM `$assoc` WHERE rule_id IN (1,2) LIMIT 1");
    if (!$assoc_type) $assoc_type = 'product';
    $wpdb->delete($assoc, ['rule_id'=>$block_id]);
    $wpdb->insert($assoc, ['rule_id'=>$block_id, 'object'=>(string)$product_id, 'type'=>$assoc_type]);

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
        'heading_color'=>'','separator_style'=>'','separator_width'=>'','separator_size'=>'','separator_color'=>'','conditional_logic'=>[],
    ];
    $options = [
        'default'=>['yes','no','no'],
        'addon_enabled'=>['yes','yes','yes'],
        'label'=>$labels,
        'description'=>$descriptions,
        'image'=>['','',''],
        'price_method'=>['free','increase','increase'],
        'price'=>['0.00',$slice_price,'15.00'],
        'price_sale'=>['','',''],
        'price_type'=>['fixed','fixed','fixed'],
        'show_image'=>['no','no','no'],
        'label_in_cart'=>['no','no','no'],
    ];

    $addon_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `$addons` WHERE block_id=%d ORDER BY priority,id LIMIT 1", $block_id));
    $addon_data = [
        'block_id'=>$block_id,
        'settings'=>maybe_serialize($settings),
        'options'=>maybe_serialize($options),
        'priority'=>'1.00000',
        'visibility'=>1,
        'last_update'=>current_time('mysql', true),
    ];
    if ($addon_id) {
        $wpdb->update($addons, $addon_data, ['id'=>$addon_id]);
    } else {
        $addon_data['creation_date'] = current_time('mysql', true);
        $wpdb->insert($addons, $addon_data);
        $addon_id = (int)$wpdb->insert_id;
    }

    return ['block_id'=>$block_id,'addon_id'=>$addon_id,'assoc_type'=>$assoc_type,'slice_price'=>$slice_price,'boneless_price'=>'15.00'];
}

try {
    $vendor_id = mj_vendor_id();
    mj_log('MONTJAM_VENDOR_ID', $vendor_id);

    // Global attribute terms.
    $terms = [];
    $terms['producer'] = mj_term_id('pa_productor', 'Mont Jam', 'mont-jam');
    $terms['huelva'] = mj_term_id('pa_origen', 'Huelva', 'huelva');
    $terms['bellota'] = mj_term_id('pa_alimentacion', 'Bellota', 'bellota');
    $terms['nodop'] = mj_term_id('pa_con-dop', 'No', 'no');
    $terms['jamon'] = mj_term_id('pa_tipo-pieza', 'Jamón', 'jamon');
    $terms['paleta'] = mj_term_id('pa_tipo-pieza', 'Paleta', 'paleta');
    $terms['cal100'] = mj_term_id('pa_calidad', 'Bellota 100% ibérico', 'bellota-100-iberico');
    $terms['calbellota'] = mj_term_id('pa_calidad', 'Bellota ibérico', 'bellota-iberico');
    $terms['race100'] = mj_term_id('pa_raza-iberica', '100% ibérico', '100-iberico');
    $terms['race50'] = mj_term_id('pa_raza-iberica', '50% ibérico', '50-iberico');
    $terms['c24_36'] = mj_term_id('pa_curacion', '24–36 meses', '24-36-meses');
    $terms['c36_48'] = mj_term_id('pa_curacion', '36–48 meses', '36-48-meses');
    $terms['r45_55'] = mj_term_id('pa_rango-peso', '4,5–5,5 kg', '45-55-kg');
    $terms['r55_65'] = mj_term_id('pa_rango-peso', '5,5–6,5 kg', '55-65-kg');
    $terms['r65_75'] = mj_term_id('pa_rango-peso', '6,5–7,5 kg', '65-75-kg');
    $terms['r75_85'] = mj_term_id('pa_rango-peso', '7,5–8,5 kg', '75-85-kg');
    $terms['prep_piece'] = mj_term_id('pa_preparacion', 'Pieza entera', 'pieza-entera');
    $terms['prep_knife'] = mj_term_id('pa_preparacion', 'Cortado a cuchillo', 'cortado-a-cuchillo');
    $terms['prep_slice'] = mj_term_id('pa_preparacion', 'Loncheado', 'loncheado');
    $terms['prep_boneless'] = mj_term_id('pa_preparacion', 'Deshuesado', 'deshuesado');

    $size_terms = [];
    foreach ([
        '5-55-kg'=>'5-5,5 Kg', '55-6-kg'=>'5,5-6 Kg', '6-65-kg'=>'6-6,5 Kg', '65-7-kg'=>'6,5-7 Kg',
        '7-75-kg'=>'7-7,5 Kg', '75-8-kg'=>'7,5-8 Kg', '8-85-kg'=>'8-8,5 Kg'
    ] as $slug=>$name) {
        $size_terms[$slug] = mj_term_id('pa_tamano', $name, $slug);
    }

    $common_prep = [
        ['id'=>$terms['prep_piece'],'slug'=>'pieza-entera'],
        ['id'=>$terms['prep_knife'],'slug'=>'cortado-a-cuchillo'],
        ['id'=>$terms['prep_slice'],'slug'=>'loncheado'],
        ['id'=>$terms['prep_boneless'],'slug'=>'deshuesado'],
    ];

    $nutrition_ham = '<h3>Ingredientes e información nutricional</h3><p><strong>Ingredientes:</strong> jamón de cerdo ibérico, sal, azúcar, corrector de la acidez E-331iii, conservadores E-252 y E-250 y antioxidante E-301. Sin gluten.</p><p><strong>Valores nutricionales por 100 g:</strong> 383,20 kcal; proteínas 48,59 g; hidratos de carbono &lt;0,1 g; grasas 20,98 g (saturadas 4,72 g); azúcares &lt;0,1 g; sal 2,10 g.</p>';
    $nutrition_paleta = '<h3>Ingredientes e información nutricional</h3><p><strong>Ingredientes:</strong> paleta de cerdo ibérico, sal, azúcar, corrector de la acidez E-331iii, conservadores E-252 y E-250 y antioxidante E-301. Sin gluten y sin lactosa.</p><p><strong>Valores nutricionales por 100 g:</strong> 383,20 kcal; proteínas 48,59 g; hidratos de carbono &lt;0,1 g; grasas 20,98 g (saturadas 4,72 g); azúcares &lt;0,1 g; sal 2,10 g.</p>';

    $specs = [
        [
            'kind'=>'jamon',
            'slug'=>'jamon-de-bellota-100-iberico-mont-jam',
            'title'=>'Jamón de bellota 100% ibérico Mont Jam (brida negra)',
            'short_description'=>'Jamón de bellota 100% ibérico Mont Jam (brida negra), elaborado en El Repilado, Huelva, con curación lenta en secaderos y bodegas naturales. Disponible de 6 a 8 kg, entero, deshuesado o loncheado a cuchillo.',
            'description'=>'<h2>Jamón de bellota 100% ibérico Mont Jam, brida negra</h2><p>Una pieza pensada para quienes buscan un jamón ibérico de bellota con sabor profundo, grasa infiltrada y una textura jugosa. Mont Jam elabora sus ibéricos en El Repilado, en la Sierra de Aracena y Picos de Aroche (Huelva), donde la curación lenta en secaderos y bodegas naturales forma parte del carácter de la casa.</p><p>Este jamón procede de cerdo <strong>100% ibérico alimentado con bellota en montanera</strong> y se identifica con brida negra. La curación se sitúa alrededor de 36 meses, permitiendo desarrollar un aroma intenso y un sabor largo y equilibrado.</p><h3>Pesos y formatos disponibles</h3><ul><li>6–6,5 kg</li><li>6,5–7 kg</li><li>7–7,5 kg</li><li>7,5–8 kg</li></ul><p>Puedes pedirlo como <strong>pieza entera</strong>, <strong>deshuesado</strong> o <strong>loncheado a cuchillo</strong>. En el loncheado a cuchillo se incluyen los huesos y los taquitos aprovechables de la pieza.</p><h3>Por qué elegir este jamón Mont Jam</h3><ul><li>100% raza ibérica y alimentación de bellota.</li><li>Elaborado en Huelva por una casa familiar especializada en productos ibéricos.</li><li>Curación prolongada en condiciones naturales para desarrollar aroma y sabor.</li><li>Formato variable para elegir el peso que mejor encaja con tu consumo.</li></ul>'.$nutrition_ham.'<h3>Conservación</h3><p>Conservar en un lugar fresco y seco, protegido de fuentes de calor. Una vez iniciado, conviene cubrir la zona de corte con una fina loncha de grasa de la propia pieza y consumirlo de forma regular para mantener su mejor textura y aroma.</p>',
            'focus_keyword'=>'jamón de bellota 100% ibérico Mont Jam',
            'seo_title'=>'Jamón de bellota 100% ibérico Mont Jam | Mercado de Origen',
            'meta_description'=>'Compra jamón de bellota 100% ibérico Mont Jam, brida negra, elaborado en Huelva. Elige peso y formato: entero, deshuesado o cortado a cuchillo.',
            'tags'=>['Mont Jam','Jamón ibérico','Jamón de bellota','100% ibérico','Brida negra','Huelva'],
            'image_alt'=>'Jamón de bellota 100% ibérico Mont Jam brida negra',
            'images'=>['https://www.iberuss.es/img/products/0000000605_0.jpg','https://www.iberuss.es/img/products/0000000605_1.jpg'],
            'taxonomies'=>[
                'pa_origen'=>[['id'=>$terms['huelva'],'slug'=>'huelva']],
                'pa_alimentacion'=>[['id'=>$terms['bellota'],'slug'=>'bellota']],
                'pa_con-dop'=>[['id'=>$terms['nodop'],'slug'=>'no']],
                'pa_tipo-pieza'=>[['id'=>$terms['jamon'],'slug'=>'jamon']],
                'pa_calidad'=>[['id'=>$terms['cal100'],'slug'=>'bellota-100-iberico']],
                'pa_raza-iberica'=>[['id'=>$terms['race100'],'slug'=>'100-iberico']],
                'pa_curacion'=>[['id'=>$terms['c36_48'],'slug'=>'36-48-meses']],
                'pa_rango-peso'=>[['id'=>$terms['r55_65'],'slug'=>'55-65-kg'],['id'=>$terms['r65_75'],'slug'=>'65-75-kg'],['id'=>$terms['r75_85'],'slug'=>'75-85-kg']],
                'pa_preparacion'=>$common_prep,
            ],
            'variations'=>[
                ['slug'=>'6-65-kg','term_id'=>$size_terms['6-65-kg'],'price'=>225.00,'sku'=>'MJ-JN-600-650'],
                ['slug'=>'65-7-kg','term_id'=>$size_terms['65-7-kg'],'price'=>243.00,'sku'=>'MJ-JN-650-700'],
                ['slug'=>'7-75-kg','term_id'=>$size_terms['7-75-kg'],'price'=>263.00,'sku'=>'MJ-JN-700-750'],
                ['slug'=>'75-8-kg','term_id'=>$size_terms['75-8-kg'],'price'=>323.00,'sku'=>'MJ-JN-750-800'],
            ],
        ],
        [
            'kind'=>'jamon',
            'slug'=>'jamon-de-bellota-iberico-50-mont-jam',
            'title'=>'Jamón de bellota ibérico 50% raza ibérica Mont Jam (brida roja)',
            'short_description'=>'Jamón de bellota ibérico 50% raza ibérica Mont Jam (brida roja), elaborado en El Repilado, Huelva. Pieza de 8–8,5 kg con opción entera, deshuesada o loncheada a cuchillo.',
            'description'=>'<h2>Jamón de bellota ibérico 50% Mont Jam, brida roja</h2><p>El jamón de bellota ibérico 50% raza ibérica Mont Jam ofrece un perfil sabroso, aromático y equilibrado, con la intensidad propia de la bellota y una curación prolongada en El Repilado (Huelva).</p><p>Identificado con <strong>brida roja</strong>, procede de cerdo ibérico 50% alimentado con bellotas durante la montanera. La pieza tiene una curación mínima de 32 meses, pensada para desarrollar un sabor persistente sin perder jugosidad.</p><h3>Peso y preparación</h3><ul><li>Peso: 8–8,5 kg.</li><li>Pieza entera.</li><li>Deshuesado y envasado al vacío.</li><li>Loncheado a cuchillo con huesos y taquitos aprovechables.</li></ul><h3>Un jamón de bellota de Huelva para disfrutar en casa</h3><p>Por su tamaño y equilibrio entre infiltración, aroma y rendimiento, es una opción especialmente interesante para celebraciones, reuniones familiares o para quien busca un jamón de bellota ibérico de brida roja con una pieza generosa.</p>'.$nutrition_ham.'<h3>Conservación</h3><p>Guardar en un lugar fresco y seco. Una vez empezado, proteger la superficie de corte con grasa de la propia pieza y evitar cubrirla directamente con materiales que favorezcan la condensación.</p>',
            'focus_keyword'=>'jamón de bellota ibérico 50% Mont Jam',
            'seo_title'=>'Jamón de bellota ibérico 50% Mont Jam | Mercado de Origen',
            'meta_description'=>'Jamón de bellota ibérico 50% Mont Jam, brida roja, elaborado en Huelva. Pieza 8–8,5 kg con opción entera, deshuesada o cortada a cuchillo.',
            'tags'=>['Mont Jam','Jamón ibérico','Jamón de bellota','50% ibérico','Brida roja','Huelva'],
            'image_alt'=>'Jamón de bellota ibérico 50% Mont Jam brida roja',
            'images'=>['https://www.iberuss.es/img/products/0000000534_0.jpg','https://www.iberuss.es/img/products/0000000534_1.jpg'],
            'taxonomies'=>[
                'pa_origen'=>[['id'=>$terms['huelva'],'slug'=>'huelva']],
                'pa_alimentacion'=>[['id'=>$terms['bellota'],'slug'=>'bellota']],
                'pa_con-dop'=>[['id'=>$terms['nodop'],'slug'=>'no']],
                'pa_tipo-pieza'=>[['id'=>$terms['jamon'],'slug'=>'jamon']],
                'pa_calidad'=>[['id'=>$terms['calbellota'],'slug'=>'bellota-iberico']],
                'pa_raza-iberica'=>[['id'=>$terms['race50'],'slug'=>'50-iberico']],
                'pa_curacion'=>[['id'=>$terms['c24_36'],'slug'=>'24-36-meses']],
                'pa_rango-peso'=>[['id'=>$terms['r75_85'],'slug'=>'75-85-kg']],
                'pa_preparacion'=>$common_prep,
            ],
            'variations'=>[
                ['slug'=>'8-85-kg','term_id'=>$size_terms['8-85-kg'],'price'=>319.00,'sku'=>'MJ-JR-800-850'],
            ],
        ],
        [
            'kind'=>'paleta',
            'slug'=>'paleta-de-bellota-100-iberica-mont-jam',
            'title'=>'Paleta de bellota 100% ibérica Mont Jam (brida negra)',
            'short_description'=>'Paleta de bellota 100% ibérica Mont Jam (brida negra), elaborada en El Repilado, Huelva. Disponible en 5–5,5 kg y 5,5–6 kg, entera, deshuesada o loncheada a cuchillo.',
            'description'=>'<h2>Paleta de bellota 100% ibérica Mont Jam, brida negra</h2><p>La paleta de bellota 100% ibérica Mont Jam concentra en un formato más compacto el carácter de los ibéricos de Huelva: sabor intenso, aroma persistente y una textura jugosa favorecida por la infiltración de grasa.</p><p>Elaborada en El Repilado por Mont Jam, procede de cerdo <strong>100% ibérico alimentado con bellota</strong> y se identifica con brida negra. Su curación mínima de 24 meses ayuda a desarrollar un perfil profundo y equilibrado.</p><h3>Pesos y formatos disponibles</h3><ul><li>5–5,5 kg.</li><li>5,5–6 kg.</li><li>Pieza entera, deshuesada o loncheada a cuchillo.</li></ul><p>Si eliges el loncheado a cuchillo, la preparación incluye los huesos y los taquitos aprovechables para sacar el máximo partido a la pieza.</p><h3>Por qué elegir una paleta Mont Jam</h3><ul><li>100% raza ibérica y alimentación de bellota.</li><li>Elaboración en Huelva, en la Sierra de Aracena y Picos de Aroche.</li><li>Curación natural y formato más compacto que un jamón.</li><li>Buena opción para hogares que quieren disfrutar de una pieza ibérica completa sin ir a pesos de jamón.</li></ul>'.$nutrition_paleta.'<h3>Conservación</h3><p>Mantener en un lugar fresco y seco. Una vez comenzada, cubrir la superficie de corte con grasa de la propia paleta y consumir con regularidad para conservar su aroma y textura.</p>',
            'focus_keyword'=>'paleta de bellota 100% ibérica Mont Jam',
            'seo_title'=>'Paleta de bellota 100% ibérica Mont Jam | Mercado de Origen',
            'meta_description'=>'Paleta de bellota 100% ibérica Mont Jam, brida negra, elaborada en Huelva. Elige 5–5,5 o 5,5–6 kg y formato entero, deshuesado o a cuchillo.',
            'tags'=>['Mont Jam','Paleta ibérica','Paleta de bellota','100% ibérico','Brida negra','Huelva'],
            'image_alt'=>'Paleta de bellota 100% ibérica Mont Jam brida negra',
            'images'=>['https://www.iberuss.es/img/products/0000000606_0.jpg','https://www.iberuss.es/img/products/0000000606_1.jpg'],
            'taxonomies'=>[
                'pa_origen'=>[['id'=>$terms['huelva'],'slug'=>'huelva']],
                'pa_alimentacion'=>[['id'=>$terms['bellota'],'slug'=>'bellota']],
                'pa_con-dop'=>[['id'=>$terms['nodop'],'slug'=>'no']],
                'pa_tipo-pieza'=>[['id'=>$terms['paleta'],'slug'=>'paleta']],
                'pa_calidad'=>[['id'=>$terms['cal100'],'slug'=>'bellota-100-iberico']],
                'pa_raza-iberica'=>[['id'=>$terms['race100'],'slug'=>'100-iberico']],
                'pa_curacion'=>[['id'=>$terms['c24_36'],'slug'=>'24-36-meses']],
                'pa_rango-peso'=>[['id'=>$terms['r45_55'],'slug'=>'45-55-kg'],['id'=>$terms['r55_65'],'slug'=>'55-65-kg']],
                'pa_preparacion'=>$common_prep,
            ],
            'variations'=>[
                ['slug'=>'5-55-kg','term_id'=>$size_terms['5-55-kg'],'price'=>142.50,'sku'=>'MJ-PN-500-550'],
                ['slug'=>'55-6-kg','term_id'=>$size_terms['55-6-kg'],'price'=>156.00,'sku'=>'MJ-PN-550-600'],
            ],
        ],
    ];

    $created = [];
    foreach ($specs as $spec) {
        $id = mj_create_or_update_product($spec, $vendor_id, $terms['producer']);
        $yith = mj_yith_addon($id, $spec['kind']);
        $product = wc_get_product($id);
        $variations = [];
        foreach ($product->get_children() as $vid) {
            $v = wc_get_product($vid);
            if (!$v) continue;
            $variations[] = [
                'id'=>(int)$vid,
                'size'=>$v->get_attribute('pa_tamano'),
                'price'=>$v->get_regular_price(),
                'sku'=>$v->get_sku(),
            ];
        }
        $created[] = [
            'id'=>$id,
            'title'=>$spec['title'],
            'slug'=>$spec['slug'],
            'url'=>get_permalink($id),
            'author'=>(int)get_post_field('post_author', $id),
            'status'=>get_post_status($id),
            'variations'=>$variations,
            'thumbnail_id'=>(int)get_post_thumbnail_id($id),
            'gallery'=>get_post_meta($id, '_product_image_gallery', true),
            'yith'=>$yith,
        ];
    }

    wp_cache_flush();
    mj_log('MONTJAM_IMPORT_SUCCESS', $created);
} catch (Throwable $e) {
    mj_log('MONTJAM_IMPORT_ERROR', $e->getMessage());
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}
