<?php
/**
 * Seller-specific chocolate enrichment. No changes outside Isabel products.
 * Re-applied after every import so generic auto-categorization cannot leak
 * vegetable/fruit taxonomy from chocolate ingredient names.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Isabel_Quality {
    public static function apply( int $product_id, array $payload ): array {
        $p = wc_get_product( $product_id );
        if ( ! $p ) { throw new RuntimeException( 'Isabel: producto WooCommerce no encontrado.' ); }
        if ( (string) get_post_meta( $product_id, '_emdo_source_url', true ) !== '' &&
            ! str_contains( (string) get_post_meta( $product_id, '_emdo_source_url', true ), 'chocolatesartesanosisabel.com' ) ) {
            throw new RuntimeException( 'Isabel: producto asociado a otro origen.' );
        }

        // Content is copied from supplier, not made-up allergen assertions.
        if ( '' === trim( wp_strip_all_tags( $p->get_short_description() ) ) ) {
            $raw = (string) ( $payload['short_description'] ?? '' );
            if ( '' === trim( wp_strip_all_tags( $raw ) ) ) {
                $html = (string) ( $payload['description'] ?? '' );
                if ( preg_match( '~<p(?:\s[^>]*)?>(.*?)</p>~si', $html, $matches ) ) {
                    $raw = trim( wp_strip_all_tags( $matches[1] ) );
                }
            }
            $plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $raw ) ) );
            if ( mb_strlen( $plain ) >= 35 && ! preg_match( '/^(composici[oó]n|ingredientes|informaci[oó]n al[eé]rgenos)/iu', $plain ) ) {
                $short = mb_substr( $plain, 0, 260 );
                if ( mb_strlen( $plain ) > 260 ) { $short = rtrim( preg_replace( '/\s+\S*$/u', '', $short ), ' ,.;' ) . '…'; }
                $p->set_short_description( wp_kses_post( '<p>' . esc_html( $short ) . '</p>' ) );
            }
        }

        $weight = isset( $payload['weight_kg'] ) && is_numeric( $payload['weight_kg'] ) ? (float) $payload['weight_kg'] : 0.0;
        if ( $weight > 0 && $weight <= 250 ) {
            $p->set_weight( wc_format_decimal( wc_get_weight( $weight, get_option( 'woocommerce_weight_unit', 'kg' ), 'kg' ) ) );
        }
        $dimensions = (array) ( $payload['dimensions_cm'] ?? array() );
        if ( count( $dimensions ) !== 3 ) {
            $description_plain = html_entity_decode( wp_strip_all_tags( (string) ( $payload['description'] ?? '' ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            if ( preg_match( '/\\b(?:medidas|dimensiones)\\s*[:\\-]?\\s*([0-9]+(?:[,.][0-9]+)?)\\s*[x×]\\s*([0-9]+(?:[,.][0-9]+)?)\\s*[x×]\\s*([0-9]+(?:[,.][0-9]+)?)\\s*cm\\b/iu', $description_plain, $match ) ) {
                $dimensions = array_map( static fn( $v ) => (float) str_replace( ',', '.', $v ), array_slice( $match, 1, 3 ) );
            }
        }
        if ( count( $dimensions ) === 3 && count( array_filter( $dimensions, static fn( $n ) => is_numeric( $n ) && (float) $n > 0 && (float) $n < 1000 ) ) === 3 ) {
            foreach ( array( 'length', 'width', 'height' ) as $index => $key ) {
                $method = 'set_' . $key;
                $p->$method( wc_format_decimal( wc_get_dimension( (float) $dimensions[ $index ], get_option( 'woocommerce_dimension_unit', 'cm' ), 'cm' ) ) );
            }
        }
        $p->save();

        $group = self::group( (string) ( $payload['title'] ?? $p->get_name() ), (string) ( $payload['source_url'] ?? '' ) );
        $root = term_exists( 'Chocolates artesanos', 'product_cat' );
        if ( ! $root ) { $root = wp_insert_term( 'Chocolates artesanos', 'product_cat' ); }
        if ( is_wp_error( $root ) || ! $root ) { throw new RuntimeException( 'Isabel: no se puede asignar su categoría raíz.' ); }
        $root_id = (int) ( is_array( $root ) ? $root['term_id'] : $root );
        $terms = array( $root_id );
        if ( '' !== $group ) {
            $term = term_exists( $group, 'product_cat', $root_id );
            if ( ! $term ) { $term = wp_insert_term( $group, 'product_cat', array( 'parent' => $root_id ) ); }
            if ( is_wp_error( $term ) || ! $term ) { throw new RuntimeException( 'Isabel: no se puede asignar ' . $group ); }
            $terms[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
        }
        // Replace, don't append: remove 'Sin categorizar', 'Naranjas' etc.
        $assigned = wp_set_object_terms( $product_id, $terms, 'product_cat', false );
        if ( is_wp_error( $assigned ) ) { throw new RuntimeException( $assigned->get_error_message() ); }

        $description = wp_strip_all_tags( (string) ( $payload['description'] ?? $p->get_description() ) );
        $has_composition = (bool) preg_match( '/composici[oó]n|ingredientes/iu', html_entity_decode( $description, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        $has_allergens = (bool) preg_match( '/informaci[oó]n\s+al[eé]rgen|presencia en el producto|trazas/iu', html_entity_decode( $description, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        update_post_meta( $product_id, '_emdo_isabel_composition_in_source', $has_composition ? 'yes' : 'unverified' );
        update_post_meta( $product_id, '_emdo_isabel_allergens_in_source', $has_allergens ? 'yes' : 'unverified' );
        update_post_meta( $product_id, '_emdo_isabel_conservation_in_source',
            preg_match( '/conservar|conservaci[oó]n|entre\s*14\s*y\s*20/iu', html_entity_decode( $description, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ? 'yes' : 'unverified' );
        return array( 'category' => $group ?: 'Chocolates artesanos', 'weight' => $p->get_weight(), 'dimensions' => array($p->get_length(),$p->get_width(),$p->get_height()),
            'image' => (int) $p->get_image_id(), 'short_description' => (bool)$p->get_short_description(), 'allergens' => $has_allergens );
    }

    private static function group( string $name, string $url ): string {
        $title = mb_strtolower( $name, 'UTF-8' );
        $text = $title . ' ' . mb_strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ), 'UTF-8' );
        if ( preg_match( '/turr[oó]n|navidad|navideñ|pascua|figura|huevo/', $text ) ) { return 'Chocolates de temporada'; }
        if ( preg_match( '/taller|visita|curso|kit.*chocolate|haz tu propio/', $text ) ) { return 'Kits y experiencias'; }
        if ( preg_match( '/lote|estuche|pack|caja|cesta|regalo|surtido|chocomensaje/', $text ) ) { return 'Packs y regalos'; }
        if ( preg_match( '/bomb[oó]n|torino|carr[eé]|piedrecica|piruleta|chocolatina|roca|crujiente/', $text ) ) { return 'Bombones y chocolatinas'; }
        if ( preg_match( '/crema|untable|untar/', $text ) ) { return 'Cremas de cacao'; }
        if ( preg_match( '/tableta|bean.to.bar|chocolate negro|chocolate blanco|chocolate con leche/', $text ) ) { return 'Tabletas de chocolate'; }
        if ( preg_match( '/nibs|cascarilla|manteca|habas de cacao|cacao en polvo|cacao.*beber|cacao.*cocinar/', $text ) ) { return 'Cacao para beber y cocinar'; }
        return '';
    }
}
