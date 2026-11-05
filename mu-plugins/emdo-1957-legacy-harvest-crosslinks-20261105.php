<?php
/**
 * Plugin Name: EMDO 1957 — enlaces campaña anterior → cosecha 26/27
 * Description: Offers a helpful route to the equivalent new harvest format, without retiring live 25/26 inventory.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) {exit;}
function emdo_1957_legacy_harvest_map_20261105(): array {
    return array(
        'aceite-de-oliva-virgen-extra-5l' => array(
            'cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio',
            'Ver AOVE 5 L de la cosecha 2026/27',
        ),
        'aceite-de-oliva-virgen-extra-1l' => array(
            'sin-filtrar-nueva-campana-26-27-aceite-de-oliva-virgen-extra-15x1l',
            'Ver pack de 15 × 1 L de la cosecha 2026/27',
        ),
        'aceite-de-oliva-virgen-extra-500ml-2' => array(
            'aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27',
            'Ver pack de 12 × 500 ml de la cosecha 2026/27',
        ),
    );
}
function emdo_1957_legacy_harvest_markup_20261105(int $post_id): string {
    if($post_id<=0 || get_post_type($post_id)!=='product') {return '';}
    $slug=(string)get_post_field('post_name',$post_id);
    $map=emdo_1957_legacy_harvest_map_20261105();
    if(!isset($map[$slug])){return '';}
    $target=get_page_by_path($map[$slug][0],OBJECT,'product');
    if(!$target instanceof WP_Post || $target->post_status!=='publish' || !function_exists('wc_get_product')){return '';}
    $product=wc_get_product((int)$target->ID);
    if(!$product || !$product->is_visible() || !$product->is_in_stock()){return '';}
    $url=(string)get_permalink((int)$target->ID);
    if(!str_starts_with($url,'https://www.elmercadodeorigen.com/producto/')){return '';}
    return '<aside class="emdo-harvest-newer-product" data-emdo-1957-harvest-link-20261105="1"'
        .' style="margin:16px 0;padding:15px 17px;border-left:3px solid #386548;background:#f5f7ef;border-radius:4px">'
        .'<p style="margin:0 0 5px"><strong>¿Buscas la nueva cosecha 26/27 de 1957?</strong></p>'
        .'<p style="margin:0"><a href="'.esc_url($url).'">'.esc_html($map[$slug][1]).'</a>.'
        .' También puedes consultar las opciones de esta ficha si prefieres la campaña 25/26.</p></aside>';
}
add_action('woocommerce_single_product_summary', static function(): void {
    if(is_admin() || !function_exists('is_product') || !is_product()) {return;}
    $markup=emdo_1957_legacy_harvest_markup_20261105((int)get_queried_object_id());
    if($markup!=='')echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
},25);
