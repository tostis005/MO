<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Huerta_Defaults {
	private const BASE_CATEGORY_SLUG      = 'hortalizas-verduras';
	private const CONSERVAS_CATEGORY_SLUG = 'conservas';
	private const CONSERVAS_URL_FRAGMENT  = '/conservas-3/';
	private const SOURCE_HOSTS             = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );

	public static function init(): void {
		add_action( 'save_post_product', array( __CLASS__, 'on_product_save' ), 30, 3 );
		add_action( 'added_post_meta', array( __CLASS__, 'on_post_meta' ), 10, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'on_post_meta' ), 10, 4 );
		add_filter( 'http_request_args', array( __CLASS__, 'image_request_args' ), 10, 2 );
	}

	public static function on_product_save( int $post_id, WP_Post $post, bool $update ): void {
		if ( wp_is_post_revision( $post_id ) || 'product' !== $post->post_type ) {
			return;
		}
		self::assign_categories_if_huerta( $post_id );
	}

	public static function on_post_meta( int $meta_id, int $object_id, string $meta_key, mixed $meta_value ): void {
		if ( ! in_array( $meta_key, array( '_emdo_supplier_id', '_emdo_source_url' ), true ) || 'product' !== get_post_type( $object_id ) ) {
			return;
		}
		self::assign_categories_if_huerta( $object_id );
	}

	public static function image_request_args( array $args, string $url ): array {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( ! in_array( $host, self::SOURCE_HOSTS, true ) || ! str_starts_with( $path, '/data/productos/imagenes/' ) ) {
			return $args;
		}

		$args['timeout']    = max( 30, (int) ( $args['timeout'] ?? 0 ) );
		$args['user-agent'] = 'Mozilla/5.0 (compatible; EMDO/' . MDO_SUPPLIER_SYNC_VERSION . '; +https://www.elmercadodeorigen.com/)';
		$headers            = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : array();
		$headers['Referer'] = 'https://www.lahuertadeanamary.com/';
		$headers['Accept']  = 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8';
		$args['headers']    = $headers;
		return $args;
	}

	private static function assign_categories_if_huerta( int $product_id ): void {
		$source_url = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
		if ( ! self::is_huerta_product( $product_id, $source_url ) ) {
			return;
		}

		$base_term = get_term_by( 'slug', self::BASE_CATEGORY_SLUG, 'product_cat' );
		if ( $base_term && ! is_wp_error( $base_term ) ) {
			wp_set_object_terms( $product_id, array( (int) $base_term->term_id ), 'product_cat', true );
		}

		if ( '' === $source_url ) {
			return;
		}

		$conservas_term = get_term_by( 'slug', self::CONSERVAS_CATEGORY_SLUG, 'product_cat' );
		if ( ! $conservas_term || is_wp_error( $conservas_term ) ) {
			return;
		}

		if ( str_contains( strtolower( $source_url ), self::CONSERVAS_URL_FRAGMENT ) ) {
			wp_set_object_terms( $product_id, array( (int) $conservas_term->term_id ), 'product_cat', true );
			return;
		}

		wp_remove_object_terms( $product_id, (int) $conservas_term->term_id, 'product_cat' );
	}

	private static function is_huerta_product( int $product_id, string $source_url ): bool {
		if ( '' !== $source_url ) {
			$host = strtolower( (string) wp_parse_url( $source_url, PHP_URL_HOST ) );
			if ( in_array( $host, self::SOURCE_HOSTS, true ) ) {
				return true;
			}
		}

		$supplier_id = absint( get_post_meta( $product_id, '_emdo_supplier_id', true ) );
		if ( ! $supplier_id ) {
			return false;
		}
		$supplier = MDO_Supplier_Repository::find( $supplier_id );
		return $supplier && 'la-huerta-ana-mary' === (string) ( $supplier['connector'] ?? '' );
	}
}
