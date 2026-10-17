<?php
/**
 * Seller-specific chocolate enrichment. No changes outside Isabel products.
 * Re-applied after every import so generic auto-categorization cannot leak
 * vegetable/fruit taxonomy from chocolate ingredient names.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Isabel_Quality {
    /** Preserve the maker's BIO&FT acronym after EMDO's generic title casing. */
    public static function canonical_title( string $title ): string {
        return (string) preg_replace(
            '/(?<![\p{L}\p{N}])bio\s*&(?:amp;){0,3}\s*ft(?![\p{L}\p{N}])/iu',
            'BIO&FT',
            $title
        );
    }


    /** Personalized Isabel products may be shown but not bought without their original customization form. */
    public static function init(): void {
        add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'safe_purchase_gate' ), 999, 2 );
    }

    public static function safe_purchase_gate( bool $purchasable, $product ): bool {
        if ( $product instanceof WC_Product &&
            '1' === (string) get_post_meta( $product->get_id(), '_emdo_isabel_customization_review_required', true ) ) {
            return false;
        }
        return $purchasable;
    }
    /**
     * The seven personalized Isabel entries were intentionally created as
     * visible-but-NOT-purchasable drafts for configuration. The generic importer
     * rightly refuses their incomplete variations; keep their *displayed* stock
     * synchronized without importing a fake simple product or enabling checkout.
     *
     * Called only after an authoritative Isabel source scrape and upsert.
     * Idempotent, seller-scoped, and never changes publish/visibility/price.
     */
    public static function sync_review_stock( int $supplier_id, string $source_url ): bool {
        global $wpdb;
        $supplier = MDO_Supplier_Repository::find( $supplier_id );
        if ( ! $supplier || 'chocolates-artesanos-isabel' !== (string) $supplier['code'] ) {
            return false;
        }
        $table = MDO_Database::table( 'source_products' );
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id,wc_product_id,status,source_stock_status FROM {$table} WHERE supplier_id=%d AND source_url=%s LIMIT 1",
                $supplier_id, $source_url
            ),
            ARRAY_A
        );
        if ( ! $row || 'pending' !== (string) $row['status'] || empty( $row['wc_product_id'] ) ) {
            return false;
        }
        $wc_id = (int) $row['wc_product_id'];
        if ( '1' !== (string) get_post_meta( $wc_id, '_emdo_isabel_customization_review_required', true ) ) {
            return false;
        }
        if ( (int) get_post_field( 'post_author', $wc_id ) !== (int) $supplier['vendor_user_id'] ) {
            throw new RuntimeException( 'Isabel: el propietario del producto personalizable es incorrecto.' );
        }
        $stock = (string) $row['source_stock_status'];
        if ( ! in_array( $stock, array( 'instock', 'outofstock' ), true ) ) {
            throw new RuntimeException( 'Isabel: disponibilidad no validada en artículo personalizable.' );
        }
        $product = wc_get_product( $wc_id );
        if ( ! $product || ! $product->is_type( 'simple' ) ) {
            throw new RuntimeException( 'Isabel: tipo de producto personalizable inesperado.' );
        }
        if ( $stock === $product->get_stock_status( 'edit' ) ) {
            return false;
        }
        $product->set_stock_status( $stock );
        $product->set_manage_stock( false );
        $product->save();
        if ( $product->get_stock_status( 'edit' ) !== $stock ) {
            throw new RuntimeException( 'Isabel: no se ha guardado el cambio de disponibilidad.' );
        }
        return true;
    }

    public static function apply( int $product_id, array $payload ): array {
        $p = wc_get_product( $product_id );
        if ( ! $p ) { throw new RuntimeException( 'Isabel: producto WooCommerce no encontrado.' ); }
        $canonical_name = self::canonical_title( (string) $p->get_name() );
        if ( $canonical_name !== $p->get_name() ) { $p->set_name( $canonical_name ); }

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

    /**
     * Controlled chocolate taxonomy: broad reusable product families only.
     * The root and one child are re-applied on every nightly synchronization.
     * Give the physical format priority over generic words such as "crujiente"
     * and never call couverture drops a tablet.
     */
    private static function group( string $name, string $url ): string {
        $title = mb_strtolower( html_entity_decode( $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 'UTF-8' );
        $source_path = mb_strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ), 'UTF-8' );
        $text = $title . ' ' . $source_path;

        if ( preg_match( '/turr[oó]n|navidad|navideñ|pascua|huevo.*chocolate|figura.*navideñ/u', $text ) ) {
            return 'Chocolates de temporada';
        }
        if ( preg_match( '/taller|visita|curso|kit.*chocolate|haz tu propio/u', $text ) ) {
            return 'Kits y experiencias';
        }
        if ( preg_match( '/lote|estuche|pack|caja|cesta|regalo|surtido|chocomensaje|personaliz|tarjeta[ -]regalo/u', $text ) ) {
            return 'Packs y regalos';
        }
        if ( preg_match( '/\\btableta\\b/u', $title ) ) {
            return 'Tabletas de chocolate';
        }
        if ( preg_match( '/\\bcrema\\b|untable|untar/u', $title ) ) {
            return 'Cremas de cacao';
        }
        if ( preg_match( '/nibs|cascarilla|manteca|habas? de cacao|pasta de cacao|cacao en polvo|chocolate.*(?:beber|bebidas)|preparar bebidas|gotas de cobertura|chips de chocolate|cobertura.*chocolate/u', $text ) ) {
            return 'Cacao para beber y cocinar';
        }
        if ( preg_match( '/bomb[oó]n|torino|carr[eé]|piedrecica|piruleta|chocolatina|roca|crujiente|chocobone|figurita|figura de chocolate|laminero/u', $text ) ) {
            return 'Bombones y chocolatinas';
        }
        return '';
    }
}
