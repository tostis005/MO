<?php
/** Apply verified per-kg IDs and ensure Huerta English Falang fields. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

global $wpdb;
$ids_file = '/tmp/mdo-huerta-perkg-ids.json';
$per_kg_ids = is_file( $ids_file ) ? json_decode( (string) file_get_contents( $ids_file ), true ) : array();
$per_kg_ids = array_fill_keys( array_map( 'absint', is_array( $per_kg_ids ) ? $per_kg_ids : array() ), true );

$title_map = array(
12699=>'White Potatoes',12702=>'Zucchini',12706=>'Broccoli',12709=>'20 kg White Kennebec Potatoes',12711=>'8 Zucchini Flowers',12715=>'Italian Pepper',12718=>'Dried Onions (kg)',12721=>'Green Lamuyo Peppers',12724=>'Approx. 300 g Bag of Padrón Peppers',12727=>'Picaguin Extra-Hot Sauce',12730=>'Artisan Sweet-and-Sour Peppers, 720 ml',12733=>'Fried Peppers in Olive Oil, 314 ml',12735=>'Eggplant',12740=>'Preserved Young Garlic, 314 ml',12743=>'Hot Green Peppers in Vinegar, 314 ml',12746=>'20 kg Red Pontiac Potatoes',12748=>'Artisan Preserved Leeks, 720 ml',12751=>'Artisan Sweet Roasted Peppers, 314 ml',12754=>'Red Pontiac Potatoes',12757=>'Hot Peppers',12761=>'Cabbage',12764=>'Artisan Leek Jam, 250 ml',12767=>'Artisan Pepper Jam, 250 ml',12770=>'Artisan Tomato Jam, 250 ml',12773=>'Chili Peppers in Vinegar, 720 ml',12775=>'12 Jars of Leek Jam, 250 ml',12779=>'12 Jars of Tomato Jam, 250 ml',12783=>'12 Jars of Spicy Roasted Peppers, 314 ml',12786=>'12 Jars of Preserved Leeks, 720 ml',12789=>'12 Jars of Hot Green Peppers in Vinegar, 314 ml',12793=>'12 Jars of Pepper Jam, 250 ml',12797=>'Artisan Tomato Fritada, 314 ml',12799=>'Padrón Peppers (kg)',12802=>'Artisan Spicy Roasted Peppers, 314 ml',12806=>'Cucumber',12809=>'12 Jars of Artisan Tomato Fritada, 314 ml',12813=>'12 Jars of Sweet-and-Sour Peppers, 720 ml',12816=>'12 Jars of Sweet Roasted Peppers',12819=>'12 Jars of Fried Peppers in Olive Oil, 314 ml',12825=>'10 kg of Fresh Vegetables',12829=>'4 Weekly 7 kg Vegetable Boxes',12832=>'Canela Beans',12837=>'White Kidney Beans',12841=>'Pinto Beans',12845=>'Pedrosillano Chickpeas',12849=>'Pardina Lentils'
);

function mdo_apply_unit_label_20260821( string $content, ?string $label ): string {
    $content = (string) preg_replace( '~\s*<p\b[^>]*class=["\'][^"\']*\bemdo-source-unit-price\b[^"\']*["\'][^>]*>.*?</p>\s*~isu', "\n", $content );
    $content = trim( $content );
    if ( null === $label ) { return $content; }
    $line = '<p class="emdo-source-unit-price"><strong>' . esc_html( $label ) . '</strong></p>';
    return '' === $content ? $line : $content . "\n" . $line;
}
function mdo_apply_polish_english_20260821( string $content ): string {
    $map = array(
        '/\bprecio\s+por\s+(?:kg|kilo)\b/iu'=>'Price per kg',
        '/\bconservas\b/iu'=>'preserves',
        '/\bhortalizas\b/iu'=>'vegetables',
        '/\blegumbres\b/iu'=>'pulses',
        '/\bingredientes\b/iu'=>'ingredients',
        '/\binformaci[oó]n\s+nutricional\b/iu'=>'nutritional information',
        '/\bconservaci[oó]n\b/iu'=>'storage',
        '/\baproximadamente\b/iu'=>'approximately',
        '/\benv[ií]o\b/iu'=>'shipping',
        '/\bproducto\b/iu'=>'product'
    );
    foreach ( $map as $pattern => $replacement ) { $content = (string) preg_replace( $pattern, $replacement, $content ); }
    return $content;
}

$product_ids = $wpdb->get_col("SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID WHERE p.post_type='product' AND pm.meta_key='_emdo_source_url' AND pm.meta_value LIKE '%lahuertadeanamary.com%' ORDER BY p.ID ASC") ?: array();
$out = array( 'scanned'=>0, 'complete'=>0, 'per_kg'=>0, 'issues'=>array(), 'products'=>array() );
foreach ( $product_ids as $raw_id ) {
    $id = absint( $raw_id ); $post = get_post( $id ); if ( ! $post instanceof WP_Post ) { continue; }
    $out['scanned']++;
    if ( isset( $title_map[$id] ) ) {
        update_post_meta( $id, '_en_US_post_title', $title_map[$id] );
        update_post_meta( $id, '_en_US_post_name', sanitize_title( $title_map[$id] ) );
    }
    update_post_meta( $id, '_en_US_published', '1' );
    update_post_meta( $id, '_en_US_ready', '1' );

    $is_per_kg = isset( $per_kg_ids[$id] );
    $spanish = mdo_apply_unit_label_20260821( (string) $post->post_content, $is_per_kg ? 'Precio por kilo' : null );
    $english = mdo_apply_polish_english_20260821( (string) get_post_meta( $id, '_en_US_post_content', true ) );
    $english = mdo_apply_unit_label_20260821( $english, $is_per_kg ? 'Price per kg' : null );

    if ( $spanish !== (string) $post->post_content ) {
        $wpdb->update( $wpdb->posts, array( 'post_content'=>$spanish ), array( 'ID'=>$id ), array('%s'), array('%d') );
        clean_post_cache( $id );
    }
    update_post_meta( $id, '_emdo_huerta_description_canonical', $spanish );
    update_post_meta( $id, '_emdo_huerta_description_locked', '1' );
    update_post_meta( $id, '_en_US_post_content', $english );
    if ( $is_per_kg ) { update_post_meta( $id, '_emdo_huerta_price_basis', 'kg' ); $out['per_kg']++; }
    else { delete_post_meta( $id, '_emdo_huerta_price_basis' ); }

    $en_title = trim((string)get_post_meta($id,'_en_US_post_title',true));
    $en_slug = trim((string)get_post_meta($id,'_en_US_post_name',true));
    $en_content = trim(wp_strip_all_tags((string)get_post_meta($id,'_en_US_post_content',true)));
    $complete = $en_title!=='' && $en_slug!=='' && $en_content!=='' && '1'===(string)get_post_meta($id,'_en_US_published',true);
    if ($complete) { $out['complete']++; } else { $out['issues'][]=array('id'=>$id,'type'=>'incomplete_english'); }
    $out['products'][]=array('id'=>$id,'english_title'=>$en_title,'english_slug'=>$en_slug,'per_kg'=>$is_per_kg);
}
wp_cache_flush();
if ( function_exists('rocket_clean_domain') ) { rocket_clean_domain(); }
if ( function_exists('w3tc_flush_all') ) { w3tc_flush_all(); }
if ( has_action('litespeed_purge_all') ) { do_action('litespeed_purge_all'); }
echo wp_json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),"\n";
if ($out['scanned']!==46 || $out['complete']!==46 || $out['issues']) { exit(2); }
