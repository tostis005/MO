<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
if ( ! taxonomy_exists( 'product_cat' ) || ! function_exists( 'wc_get_product' ) ) {
    throw new Exception('WooCommerce product categories unavailable.');
}

$supplier_id = 0;
if ( class_exists( 'MDO_Supplier_Repository' ) ) {
    foreach ( MDO_Supplier_Repository::all() as $supplier ) {
        if ( 'selectos-de-castilla' === (string)($supplier['connector'] ?? '')
            || 'selectos-de-castilla' === (string)($supplier['code'] ?? '')
            || 'Selectos de Castilla' === (string)($supplier['name'] ?? '') ) {
            $supplier_id = (int)$supplier['id'];
            break;
        }
    }
}
if ( $supplier_id <= 0 ) { $supplier_id = 5; }

$ids = get_posts(array(
    'post_type'      => 'product',
    'post_status'    => array('publish','draft','private','pending','future'),
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_key'       => '_emdo_supplier_id',
    'meta_value'     => $supplier_id,
    'orderby'        => 'ID',
    'order'          => 'ASC',
));
if ( count($ids) < 128 ) {
    throw new Exception('Selectos catalog unexpectedly small: '.count($ids));
}

$ensure_term = static function(string $name,string $slug,int $parent=0,string $description=''): WP_Term {
    $term = get_term_by('slug',$slug,'product_cat');
    $args = array('name'=>$name,'slug'=>$slug,'parent'=>$parent);
    if ($description !== '') $args['description'] = $description;
    if ($term instanceof WP_Term) {
        $r = wp_update_term((int)$term->term_id,'product_cat',$args);
        if (is_wp_error($r)) throw new Exception($r->get_error_message());
        $term = get_term((int)$term->term_id,'product_cat');
        if (!$term instanceof WP_Term) throw new Exception('Could not reload '.$slug);
        return $term;
    }
    $r = wp_insert_term($name,'product_cat',$args);
    if (is_wp_error($r)) throw new Exception($r->get_error_message());
    $term = get_term((int)$r['term_id'],'product_cat');
    if (!$term instanceof WP_Term) throw new Exception('Could not create '.$slug);
    return $term;
};

$required = array(
    'carnes','conservas','embutidos-y-curados','packs-y-lotes','quesos',
    'legumbres','aceites','pescados-mariscos','pato','confit-de-pato',
    'foie-gras-de-pato','jamon-de-pato','magret-de-pato',
    'pate-mousse-rillettes-de-pato','pato-fresco'
);
$terms = array();
foreach ($required as $slug) {
    $t = get_term_by('slug',$slug,'product_cat');
    if (!$t instanceof WP_Term) throw new Exception('Required category missing: '.$slug);
    $terms[$slug] = $t;
}

$global_new = array();
$global_new['foie-pates-untables'] = $ensure_term(
    'Foie, patés y untables',
    'foie-pates-untables',
    0,
    '<p>Foie gras, patés, mousses, rillettes y otras especialidades untables de productores artesanos.</p>'
);
$global_new['despensa-gourmet'] = $ensure_term(
    'Despensa gourmet',
    'despensa-gourmet',
    0,
    '<p>Mieles, mermeladas, chutneys, condimentos, sales y otros productos de despensa seleccionados.</p>'
);
$global_new['vinos-licores'] = $ensure_term(
    'Vinos y licores',
    'vinos-licores',
    0,
    '<p>Vinos, licores, aguardientes y otras bebidas seleccionadas de productores y elaboradores.</p>'
);
foreach ($global_new as $slug=>$term) {
    $terms[$slug] = $term;
    update_term_meta((int)$term->term_id,'display_type','products');
    update_term_meta((int)$term->term_id,'_emdo_global_category_setup','20261001');
}

$pack_ids = array(16188,16203,16340,16384,16463,16615,16619,16669,16678,16726,16756,16763,16782,16828,16836);
$pack_lookup = array_fill_keys($pack_ids,true);

$normalize = static function(string $s): string {
    $s = html_entity_decode($s,ENT_QUOTES | ENT_HTML5,'UTF-8');
    $s = remove_accents(mb_strtolower($s,'UTF-8'));
    return trim($s);
};

$classification = array();
$unmapped = array();
$status_before = array();
$stock_before = array();

foreach ($ids as $id) {
    $id = (int)$id;
    $title = $normalize((string)get_the_title($id));
    $source = mb_strtolower((string)get_post_meta($id,'_emdo_source_url',true),'UTF-8');
    $target = array();
    $status_before[$id] = (string)get_post_status($id);
    $product = wc_get_product($id);
    $stock_before[$id] = $product ? (string)$product->get_stock_status() : '';

    if (isset($pack_lookup[$id])) {
        $target = array('packs-y-lotes');
    } elseif (str_contains($source,'/vinos-y-licores/')) {
        $target = array('vinos-licores');
    } elseif (str_contains($source,'/mermeladas-confituras-miel/')
        || str_starts_with($title,'miel ')
        || str_contains($title,'mermelada')
        || str_contains($title,'confitura')
        || str_contains($title,'jalea de ')) {
        $target = array('despensa-gourmet');
    } elseif (str_contains($title,'queso ')) {
        $target = array('quesos');
    } elseif (str_contains($title,'lenteja ') || str_contains($title,'alubia ') || str_contains($title,'garbanzo ')) {
        $target = array('legumbres');
    } elseif (str_contains($title,'aceite de oliva')) {
        $target = array('aceites');
    } elseif (str_starts_with($title,'tubo sal ') || str_starts_with($title,'tubo pimienta ')) {
        $target = array('despensa-gourmet');
    } elseif (str_contains($title,'chutney') || str_contains($title,'agridulce ') || str_contains($title,'reduccion de ')) {
        $target = array('despensa-gourmet');
    } elseif (str_contains($title,'pimientos asados') || str_contains($title,'cebolla caramelizada')
        || str_contains($title,'castanas al natural') || str_contains($title,'faisan escabechado')) {
        $target = array('conservas');
    } elseif (str_contains($title,'crema de morcilla') || str_contains($title,'crema de boletus')) {
        $target = array('foie-pates-untables');
    } elseif (str_contains($source,'/trucha/') || str_contains($title,'trucha')) {
        $target = array('pescados-mariscos');
        if (str_contains($title,'mousse')) $target[] = 'foie-pates-untables';
    } elseif (str_contains($source,'/jamon-de-pato/')) {
        $target = array('embutidos-y-curados','pato','jamon-de-pato');
    } elseif (str_contains($source,'/pato-fresco/')) {
        $target = array('pato','pato-fresco');
    } elseif (str_contains($source,'/magret/')) {
        $target = array('pato','magret-de-pato');
    } elseif (str_contains($source,'/foie-gras/')) {
        $target = array('foie-pates-untables','pato');
        $target[] = str_contains($title,'mousse') ? 'pate-mousse-rillettes-de-pato' : 'foie-gras-de-pato';
    } elseif (str_contains($source,'/pates/')) {
        $target = array('foie-pates-untables');
        $is_other = str_contains($title,'avestruz') || str_contains($title,'cochinillo')
            || str_contains($title,'lechazo') || str_contains($title,'oca') || str_contains($title,'trucha');
        if (str_contains($title,'trucha')) $target[] = 'pescados-mariscos';
        if (!$is_other) {
            $target[] = 'pato';
            if (str_contains($title,'bloc de foie gras')) {
                $target[] = 'foie-gras-de-pato';
            } else {
                $target[] = 'pate-mousse-rillettes-de-pato';
            }
        }
    } elseif (str_contains($source,'/confit/')) {
        if (str_contains($title,'cochinillo') || str_contains($title,'codorniz')) {
            $target = array('carnes');
        } elseif (str_contains($title,'lechazo')) {
            $target = array('foie-pates-untables');
        } elseif (str_contains($title,'rillettes')) {
            $target = array('foie-pates-untables','pato','pate-mousse-rillettes-de-pato');
        } else {
            $target = array('pato','confit-de-pato');
        }
    } elseif (str_contains($source,'/los-manjares-de-la-tierra/') && str_contains($title,'cochinillo')) {
        $target = array('carnes');
    } elseif ($id === 16816 || str_contains($title,'manchon confitado')) {
        $target = array('pato','confit-de-pato');
    }

    if (!$target) {
        $unmapped[] = array('id'=>$id,'status'=>get_post_status($id),'title'=>get_the_title($id),'source'=>$source);
        continue;
    }
    $classification[$id] = array_values(array_unique($target));
}

if ($unmapped) {
    throw new Exception('Unmapped Selectos products: '.wp_json_encode($unmapped,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
if (count($classification) !== count($ids)) {
    throw new Exception('Classification count mismatch.');
}

foreach ($classification as $id=>$slugs) {
    $current = wp_get_post_terms($id,'product_cat');
    if (is_wp_error($current)) throw new Exception($current->get_error_message());

    if ('' === (string)get_post_meta($id,'_emdo_selectos_categories_backup_global_20261001',true)) {
        update_post_meta($id,'_emdo_selectos_categories_backup_global_20261001',wp_json_encode(array_map(
            static fn(WP_Term $t): int => (int)$t->term_id,
            array_filter($current,static fn($t)=>$t instanceof WP_Term)
        )));
    }

    $target_ids = array();
    foreach ($slugs as $slug) {
        if (!isset($terms[$slug])) {
            $t = get_term_by('slug',$slug,'product_cat');
            if (!$t instanceof WP_Term) throw new Exception('Missing target category '.$slug);
            $terms[$slug] = $t;
        }
        $target_ids[] = (int)$terms[$slug]->term_id;
    }
    $r = wp_set_object_terms($id,array_values(array_unique($target_ids)),'product_cat',false);
    if (is_wp_error($r)) throw new Exception('Failed setting categories for '.$id.': '.$r->get_error_message());

    delete_post_meta($id,'_emdo_auto_category_attempted');
    delete_post_meta($id,'_emdo_auto_category_reason');
    delete_post_meta($id,'_emdo_auto_category_ids');
    delete_post_meta($id,'_emdo_auto_category_score');
    clean_post_cache($id);
}

/* Use representative products for category imagery where available. */
$thumb_sources = array(
    'foie-pates-untables' => 16207,
    'despensa-gourmet'    => 16291,
    'vinos-licores'       => 16218,
);
foreach ($thumb_sources as $slug=>$pid) {
    $thumb = (int)get_post_thumbnail_id($pid);
    if ($thumb > 0 && isset($terms[$slug])) {
        update_term_meta((int)$terms[$slug]->term_id,'thumbnail_id',$thumb);
    }
}

/* Remove the supplier-specific categories created in the previous pass, but only if truly unused. */
$obsolete_slugs = array('cochinillo','lechazo','otras-aves','trucha');
$deleted_obsolete = array();
$retained_obsolete = array();
foreach ($obsolete_slugs as $slug) {
    $term = get_term_by('slug',$slug,'product_cat');
    if (!$term instanceof WP_Term) continue;
    $objects = get_objects_in_term((int)$term->term_id,'product_cat');
    if (is_wp_error($objects)) throw new Exception($objects->get_error_message());
    $marker = (string)get_term_meta((int)$term->term_id,'_emdo_selectos_category_setup',true);
    if (!$objects && $marker === '20261001') {
        $r = wp_delete_term((int)$term->term_id,'product_cat');
        if (is_wp_error($r)) throw new Exception($r->get_error_message());
        $deleted_obsolete[] = $slug;
    } else {
        $retained_obsolete[] = array('slug'=>$slug,'objects'=>count($objects),'marker'=>$marker);
    }
}

$missing_after = 0;
$uncategorized_after = 0;
$mentta_after = 0;
$obsolete_assignments_after = 0;
$status_changes = array();
$stock_changes = array();
$counts = array();
$rows = array();

foreach ($ids as $id) {
    $id = (int)$id;
    $product = wc_get_product($id);
    $after_status = (string)get_post_status($id);
    $after_stock = $product ? (string)$product->get_stock_status() : '';
    if ($after_status !== $status_before[$id]) $status_changes[] = array('id'=>$id,'before'=>$status_before[$id],'after'=>$after_status);
    if ($after_stock !== $stock_before[$id]) $stock_changes[] = array('id'=>$id,'before'=>$stock_before[$id],'after'=>$after_stock);

    $assigned = wp_get_post_terms($id,'product_cat');
    if (is_wp_error($assigned)) throw new Exception($assigned->get_error_message());
    $slugs = array_map(static fn(WP_Term $t): string => $t->slug,$assigned);
    if (!$slugs) $missing_after++;
    foreach ($slugs as $slug) {
        $counts[$slug] = ($counts[$slug] ?? 0) + 1;
        if (in_array($slug,array('sin-categorizar','uncategorized'),true)) $uncategorized_after++;
        if ($slug === 'mentta' || str_starts_with($slug,'mentta-')) $mentta_after++;
        if (in_array($slug,$obsolete_slugs,true)) $obsolete_assignments_after++;
    }

    $expected = $classification[$id];
    $a = $slugs; $b = $expected; sort($a); sort($b);
    if ($a !== $b) {
        throw new Exception('Verification mismatch for product '.$id.' expected '.implode(',',$b).' got '.implode(',',$a));
    }

    $rows[] = array(
        'id'=>$id,
        'status'=>$after_status,
        'title'=>get_the_title($id),
        'categories'=>$slugs,
    );
}

ksort($counts);
if ($missing_after !== 0 || $uncategorized_after !== 0 || $mentta_after !== 0 || $obsolete_assignments_after !== 0) {
    throw new Exception('Final category verification failed.');
}
if ($status_changes || $stock_changes) {
    throw new Exception('Product status/stock changed unexpectedly.');
}

clean_term_cache(array_map(static fn(WP_Term $t): int => (int)$t->term_id,$global_new),'product_cat');
flush_rewrite_rules(false);

$status_counts = array_count_values(array_values($status_before));
ksort($status_counts);

echo wp_json_encode(array(
    'batch'=>'20261001-selectos-global-taxonomy',
    'supplier_id'=>$supplier_id,
    'products'=>count($ids),
    'status_counts'=>$status_counts,
    'new_global_categories'=>array_map(static fn(WP_Term $t): array => array(
        'id'=>(int)$t->term_id,
        'name'=>$t->name,
        'slug'=>$t->slug,
        'url'=>is_wp_error(get_term_link($t))?'':get_term_link($t),
    ),$global_new),
    'deleted_obsolete_categories'=>$deleted_obsolete,
    'retained_obsolete_categories'=>$retained_obsolete,
    'missing_after'=>$missing_after,
    'uncategorized_after'=>$uncategorized_after,
    'mentta_after'=>$mentta_after,
    'obsolete_assignments_after'=>$obsolete_assignments_after,
    'status_changes'=>$status_changes,
    'stock_changes'=>$stock_changes,
    'category_counts'=>$counts,
    'rows'=>$rows,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
