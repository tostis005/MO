<?php
/**
 * Plugin Name: EMDO — Enlaces comerciales contextuales 2026/27
 * Description: Bridges a few purchase-adjacent Spanish guides to real products without editing their source content.
 * Version: 1.0.0
 */
if ( ! defined('ABSPATH') ) { exit; }

/** Explicit, editorially matched destinations only (not a keyword-wide site rule). */
function emdo_purchase_guides_registry_20261105(): array {
    return array(
        'aove-filtrado-o-sin-filtrar-diferencias' => array(
            'intro' => '¿Quieres ver un ejemplo de aceite de nueva cosecha? En 1957 puedes consultar los formatos sin filtrar disponibles de la campaña 2026/27.',
            'links' => array(
                array('product','cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio','AOVE 26/27 en garrafa de 5 L'),
                array('product','aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27','AOVE 26/27 en 12 botellas de 500 ml'),
            ),
        ),
        'aove-garrafa-5-litros-cuando-compensa-como-conservar' => array(
            'intro' => 'Si cinco litros encajan con tu consumo, puedes consultar las variedades y opciones disponibles de la cosecha 2026/27 de 1957.',
            'links' => array(
                array('product','cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio','Ver garrafa de AOVE 5 L · cosecha 26/27'),
            ),
        ),
        'brida-negra-roja-verde-blanca-jamon-iberico' => array(
            'intro' => 'Para llevar las categorías de esta guía a una compra real, compara la denominación completa y las características de cada pieza.',
            'links' => array(
                array('product','jamon-de-bellota-100-iberico','Ver jamón de bellota 100 % ibérico'),
                array('product','jamon-cebo-de-campo-iberico-50-montjam','Ver jamón de cebo de campo ibérico 50 %'),
            ),
        ),
        '50-75-100-iberico-que-significa-porcentaje-raza-etiqueta' => array(
            'intro' => 'Puedes comprobar cómo se identifica el porcentaje racial en las fichas de piezas ibéricas de distintas categorías.',
            'links' => array(
                array('product','jamon-de-bellota-100-iberico','Consultar jamón de bellota 100 % ibérico'),
                array('product','jamon-cebo-de-campo-iberico-50-montjam','Consultar jamón ibérico 50 % de cebo de campo'),
            ),
        ),
        'jamon-o-paleta-diferencias-cual-elegir' => array(
            'intro' => 'Después de comparar tamaño y rendimiento, revisa las opciones de compra y formatos disponibles.',
            'links' => array(
                array('page','jamon-bellota-100-iberico','Comprar jamón de bellota 100 % ibérico'),
                array('page','paleta-bellota-100-iberica','Comprar paleta de bellota 100 % ibérica'),
            ),
        ),
    );
}

/** Never link to an unpublished / sold-out product or an unavailable landing. */
function emdo_purchase_guides_destination_20261105(string $type,string $slug): string {
    if ( ! in_array($type,array('product','page'),true) ) { return ''; }
    $post = get_page_by_path($slug,OBJECT,$type);
    if ( ! $post instanceof WP_Post || $post->post_status!=='publish' ) { return ''; }
    if ( $type==='product' ) {
        if (!function_exists('wc_get_product')) { return ''; }
        $product=wc_get_product((int)$post->ID);
        if (!$product || !$product->is_visible() || !$product->is_in_stock()) { return ''; }
    }
    $url=(string)get_permalink($post->ID);
    return str_starts_with($url,'https://www.elmercadodeorigen.com/') ? $url : '';
}

function emdo_purchase_guides_filter_20261105($content): string {
    $content=(string)$content;
    if ( is_admin() || !is_singular('post') || !in_the_loop() || !is_main_query()
      || strpos((string)($_SERVER['REQUEST_URI']??''),'/en/')===0
      || str_contains($content,'data-emdo-editorial-commerce-20261105') ) { return $content; }

    $post_id=(int)get_queried_object_id();
    if ($post_id<=0 || get_post_type($post_id)!=='post') {return $content;}
    $slug=(string)get_post_field('post_name',$post_id);
    $registry=emdo_purchase_guides_registry_20261105();
    if (!isset($registry[$slug])) {return $content;}
    $cfg=$registry[$slug];
    $links=array();
    foreach($cfg['links'] as $target) {
        [$type,$destination,$label]=$target;
        $url=emdo_purchase_guides_destination_20261105($type,$destination);
        if ($url==='') {continue;}
        $links[]='<a href="'.esc_url($url).'">'.esc_html($label).'</a>';
    }
    if (!$links) {return $content;}
    $module='<aside class="emdo-editorial-commerce-20261105" data-emdo-editorial-commerce-20261105="1"'
       .' style="margin:26px 0;padding:18px 22px;border-left:3px solid #436b4a;background:#f7f6ef;border-radius:4px">'
       .'<p style="margin:0 0 8px"><strong>Del artículo a la compra:</strong> '.esc_html($cfg['intro']).'</p>'
       .'<p style="margin:0">'.implode(' · ',$links).'</p></aside>';
    return rtrim($content)."\n\n".$module;
}
add_filter('the_content','emdo_purchase_guides_filter_20261105',8);
