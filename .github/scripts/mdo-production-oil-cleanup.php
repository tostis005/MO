<?php
/** Remove obsolete oil-pack link and shipping-included copy from current oil products. Production only. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$siteurl = rtrim( (string)get_option( 'siteurl' ), '/' );
if ( ! preg_match( '~^https?://(www\.)?elmercadodeorigen\.com$~i', $siteurl ) ) { fwrite( STDERR, "Refusing non-production site: {$siteurl}\n" ); exit( 2 ); }

function mdo_clean_oil_text( $value ) {
    $value = (string)$value;
    /* Remove the obsolete custom 4-jug-pack link as a complete anchor first. */
    $value = preg_replace( '~<a\b[^>]*>\s*PARA UNA COMBINACIÓN A TU GUSTO DE GARRAFAS PINCHA AQUÍ\s*</a>~iu', '', $value );
    $value = str_ireplace( 'PARA UNA COMBINACIÓN A TU GUSTO DE GARRAFAS PINCHA AQUÍ', '', $value );
    /* User explicitly requested removing the shipping-included statement from oils. */
    $value = preg_replace( '~\bGastos? de envío incluidos?\.\s*~iu', '', $value );
    $value = preg_replace( "/[ \t]+\n/u", "\n", $value );
    $value = preg_replace( "/\n{3,}/u", "\n\n", $value );
    return trim( $value );
}

$products = get_posts( array( 'post_type'=>'product', 'post_status'=>'publish', 'posts_per_page'=>-1, 'fields'=>'ids', 'tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'slug','terms'=>array('aceites','aceite'))) ) );
$changed = array();
foreach ( $products as $id ) {
    $post = get_post( $id ); if ( ! $post ) { continue; }
    $content = mdo_clean_oil_text( $post->post_content );
    $excerpt = mdo_clean_oil_text( $post->post_excerpt );
    if ( $content === $post->post_content && $excerpt === $post->post_excerpt ) { continue; }
    if ( ! metadata_exists( 'post', $id, '_mdo_pre_multilingual_oil_content_20260815' ) ) {
        update_post_meta( $id, '_mdo_pre_multilingual_oil_content_20260815', $post->post_content );
        update_post_meta( $id, '_mdo_pre_multilingual_oil_excerpt_20260815', $post->post_excerpt );
    }
    wp_update_post( array( 'ID'=>$id, 'post_content'=>$content, 'post_excerpt'=>$excerpt ) );
    clean_post_cache( $id ); $changed[] = (int)$id;
}
wp_cache_flush();
echo 'Oil products changed: ' . implode( ',', $changed ) . PHP_EOL;
