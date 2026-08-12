<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mantiene los slugs de productos EMDO legibles y SEO-friendly.
 *
 * Los títulos pueden llegar con entidades HTML. Antes de generar el slug las
 * decodificamos y transliteramos acentos: "Jamón ibérico" -> "jamon-iberico".
 * Solo actúa sobre productos vinculados a EMDO.
 */
final class MDO_Product_Slugs {
	private const SOURCE_META = '_emdo_source_product_id';
	private const MIGRATION_OPTION = 'mdo_product_slug_migration_v1';

	public static function init(): void {
		add_action( 'added_post_meta', array( __CLASS__, 'maybe_normalize_from_meta' ), 20, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'maybe_normalize_from_meta' ), 20, 4 );
		self::migrate_once();
	}

	public static function maybe_normalize_from_meta( int $meta_id, int $object_id, string $meta_key, mixed $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( self::SOURCE_META !== $meta_key ) {
			return;
		}
		self::normalize_product_slug( $object_id );
	}

	public static function normalize_product_slug( int $product_id ): void {
		if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
			return;
		}
		if ( '' === (string) get_post_meta( $product_id, self::SOURCE_META, true ) ) {
			return;
		}

		$post = get_post( $product_id );
		if ( ! $post ) {
			return;
		}
		$title = self::decode_entities( (string) $post->post_title );
		$slug  = sanitize_title( remove_accents( $title ) );
		if ( '' === $slug ) {
			return;
		}

		$slug = wp_unique_post_slug(
			$slug,
			$product_id,
			(string) $post->post_status,
			'product',
			(int) $post->post_parent
		);
		if ( $slug === (string) $post->post_name ) {
			return;
		}

		wp_update_post(
			array(
				'ID'        => $product_id,
				'post_name' => $slug,
			)
		);
	}

	private static function migrate_once(): void {
		if ( '1' === (string) get_option( self::MIGRATION_OPTION, '' ) ) {
			return;
		}
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => self::SOURCE_META,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		foreach ( $ids as $id ) {
			self::normalize_product_slug( (int) $id );
		}
		update_option( self::MIGRATION_OPTION, '1', false );
	}

	private static function decode_entities( string $value ): string {
		for ( $i = 0; $i < 4; $i++ ) {
			$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( $decoded === $value ) {
				break;
			}
			$value = $decoded;
		}
		return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $value ) ) );
	}
}
