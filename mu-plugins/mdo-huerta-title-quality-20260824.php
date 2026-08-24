<?php
/**
 * Persistent title/slug guard for known La Huerta de Ana Mary source encoding defects.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', static function (): void {
    add_action( 'save_post_product', 'mdo_huerta_title_quality_20260824_on_save', 180, 3 );
    add_action( 'added_post_meta', 'mdo_huerta_title_quality_20260824_on_meta', 180, 4 );
    add_action( 'updated_post_meta', 'mdo_huerta_title_quality_20260824_on_meta', 180, 4 );
}, 30 );

function mdo_huerta_title_quality_20260824_on_save( int $post_id, WP_Post $post, bool $update ): void {
    unset( $update );
    if ( wp_is_post_revision( $post_id ) || 'product' !== $post->post_type ) {
        return;
    }
    mdo_huerta_title_quality_20260824_apply( $post_id );
}

function mdo_huerta_title_quality_20260824_on_meta( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
    unset( $meta_id, $meta_value );
    if ( ! in_array( $meta_key, array( '_emdo_source_url', '_emdo_source_product_id' ), true ) || 'product' !== get_post_type( $object_id ) ) {
        return;
    }
    mdo_huerta_title_quality_20260824_apply( $object_id );
}

function mdo_huerta_title_quality_20260824_apply( int $product_id ): void {
    static $busy = array();
    if ( isset( $busy[ $product_id ] ) || 'product' !== get_post_type( $product_id ) ) {
        return;
    }

    $source_url = (string) get_post_meta( $product_id, '_emdo_source_url', true );
    $desired = '';
    if ( str_contains( $source_url, '/loras-o-a-oras-40.html' ) ) {
        $desired = 'Loras o ñoras';
    } elseif ( str_contains( $source_url, '/alubia-blanca-de-ri-n-200.html' ) ) {
        $desired = 'Alubia blanca de riñón';
    }
    if ( '' === $desired ) {
        return;
    }

    $busy[ $product_id ] = true;
    try {
        $post = get_post( $product_id );
        if ( ! $post ) {
            return;
        }
        $desired_slug = sanitize_title( remove_accents( $desired ) );
        $desired_slug = wp_unique_post_slug( $desired_slug, $product_id, (string) $post->post_status, 'product', (int) $post->post_parent );
        if ( $post->post_title !== $desired || $post->post_name !== $desired_slug ) {
            wp_update_post( array(
                'ID' => $product_id,
                'post_title' => $desired,
                'post_name' => $desired_slug,
            ) );
            clean_post_cache( $product_id );
        }

        $source_id = absint( get_post_meta( $product_id, '_emdo_source_product_id', true ) );
        if ( $source_id && class_exists( 'MDO_Database' ) ) {
            global $wpdb;
            $table = MDO_Database::table( 'source_products' );
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT title, source_payload FROM {$table} WHERE id = %d LIMIT 1", $source_id ), ARRAY_A );
            if ( $row ) {
                $payload = json_decode( (string) ( $row['source_payload'] ?? '' ), true );
                $payload = is_array( $payload ) ? $payload : array();
                $payload['title'] = $desired;
                $wpdb->update(
                    $table,
                    array(
                        'title' => $desired,
                        'source_payload' => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                    ),
                    array( 'id' => $source_id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
            }
        }
    } finally {
        unset( $busy[ $product_id ] );
    }
}
