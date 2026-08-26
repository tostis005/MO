<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deterministic request fallback for the English Specials routes.
 *
 * Some multilingual rewrite stacks can consume /en/* before custom rewrite rules
 * are evaluated. This router normalizes only the two EMDO Specials English paths
 * at parse_request time, leaving the rest of the site's language routing untouched.
 */
final class MDO_Specials_Router {
	private const POST_TYPE = 'mdo_promotion';

	public static function init(): void {
		add_action( 'parse_request', array( __CLASS__, 'parse_request' ), 1 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'disable_canonical' ), 5, 2 );
		add_action( 'init', array( __CLASS__, 'migrate_tolecarnes_copy' ), 32 );
		add_action( 'init', array( __CLASS__, 'cleanup_legacy_tolecarnes_special' ), 33 );
	}

	/**
	 * One-time content migration for the already-published Tolecarnes special.
	 *
	 * The copy must make it explicit that the purchase happens inside El Mercado
	 * de Origen, rather than sounding like an off-platform order to the producer.
	 */
	public static function migrate_tolecarnes_copy(): void {
		if ( get_option( 'mdo_specials_tolecarnes_copy_v3' ) ) {
			return;
		}

		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_mdo_promo_seed_key',
				'meta_value'     => 'tolecarnes-hamburguesas-v2',
			)
		);

		if ( ! $ids ) {
			$post = get_page_by_path( 'hamburguesas-ternera-regalo-tolecarnes', OBJECT, self::POST_TYPE );
			if ( $post ) {
				$ids = array( $post->ID );
			}
		}

		if ( ! $ids ) {
			return;
		}

		$post_id = (int) $ids[0];
		$es      = array(
			'summary'    => 'Compra productos de Tolecarnes en El Mercado de Origen y recibe dos hamburguesas 100% ternera de regalo.',
			'benefit'    => 'No tienes que introducir ningún código ni hacer nada especial. Al comprar productos de Tolecarnes en El Mercado de Origen, recibirás dos hamburguesas 100% ternera de regalo junto a tu pedido.',
			'content'    => '<p>Una ventaja especial al comprar productos de Tolecarnes en El Mercado de Origen: dos hamburguesas 100% ternera de regalo con tu pedido.</p>',
			'cta_label'  => 'Ver productos de Tolecarnes',
			'conditions' => 'Promoción válida para compras de productos de Tolecarnes realizadas en El Mercado de Origen hasta el 31 de agosto de 2026 incluido. No requiere código promocional.',
		);
		$en      = array(
			'summary'    => 'Buy Tolecarnes products on El Mercado de Origen and receive two 100% beef burgers as a gift.',
			'benefit'    => 'No code or special action is required. When you buy Tolecarnes products on El Mercado de Origen, two 100% beef burgers will be included as a gift with your order.',
			'content'    => '<p>A special benefit when buying Tolecarnes products on El Mercado de Origen: receive two 100% beef burgers as a gift with your order.</p>',
			'cta_label'  => 'See Tolecarnes products',
			'conditions' => 'Valid for purchases of Tolecarnes products made on El Mercado de Origen through 31 August 2026 inclusive. No promotional code is required.',
		);

		foreach ( array( 'es' => $es, 'en' => $en ) as $lang => $copy ) {
			foreach ( $copy as $field => $value ) {
				update_post_meta( $post_id, '_mdo_promo_' . $field . '_' . $lang, $value );
			}
		}

		// Keep the legacy Spanish fields aligned with the bilingual source of truth.
		update_post_meta( $post_id, '_mdo_promo_summary', $es['summary'] );
		update_post_meta( $post_id, '_mdo_promo_benefit', $es['benefit'] );
		update_post_meta( $post_id, '_mdo_promo_cta_label', $es['cta_label'] );
		update_post_meta( $post_id, '_mdo_promo_conditions', $es['conditions'] );
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_excerpt' => $es['summary'],
				'post_content' => $es['content'],
			)
		);

		update_option( 'mdo_specials_tolecarnes_copy_v3', 1, false );
	}

	/**
	 * Remove the obsolete v1 Tolecarnes promotion if it survived the bilingual
	 * migration. The v2 record is the only source of truth that should remain.
	 */
	public static function cleanup_legacy_tolecarnes_special(): void {
		if ( get_option( 'mdo_specials_tolecarnes_cleanup_v4' ) ) {
			return;
		}

		$canonical_ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_mdo_promo_seed_key',
				'meta_value'     => 'tolecarnes-hamburguesas-v2',
			)
		);
		$canonical_ids = array_map( 'intval', $canonical_ids );

		$legacy_ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_mdo_promo_seed_key',
				'meta_value'     => 'tolecarnes-hamburguesas-v1',
			)
		);

		$legacy_slug = get_page_by_path( 'hamburguesas-regalo-tole-carnes', OBJECT, self::POST_TYPE );
		if ( $legacy_slug ) {
			$legacy_ids[] = (int) $legacy_slug->ID;
		}

		foreach ( array_unique( array_map( 'intval', $legacy_ids ) ) as $legacy_id ) {
			if ( ! $legacy_id || in_array( $legacy_id, $canonical_ids, true ) ) {
				continue;
			}
			wp_delete_post( $legacy_id, true );
		}

		update_option( 'mdo_specials_tolecarnes_cleanup_v4', 1, false );
	}

	public static function parse_request( WP $wp ): void {
		if ( is_admin() ) {
			return;
		}

		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		if ( 'en/specials' === $path ) {
			$wp->query_vars = array(
				'post_type'         => self::POST_TYPE,
				'post_status'       => 'publish',
				'mdo_specials_lang' => 'en',
			);
			$wp->matched_rule  = 'emdo-en-specials-archive';
			$wp->matched_query = 'post_type=' . self::POST_TYPE . '&mdo_specials_lang=en';
			return;
		}

		if ( ! preg_match( '#^en/specials/([^/]+)$#', $path, $matches ) ) {
			return;
		}

		$slug = sanitize_title( rawurldecode( (string) $matches[1] ) );
		$ids  = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_mdo_promo_slug_en',
				'meta_value'     => $slug,
			)
		);
		if ( ! $ids ) {
			return;
		}

		$wp->query_vars = array(
			'post_type'         => self::POST_TYPE,
			'p'                 => (int) $ids[0],
			'post_status'       => 'publish',
			'mdo_specials_lang' => 'en',
		);
		$wp->matched_rule  = 'emdo-en-specials-single';
		$wp->matched_query = 'post_type=' . self::POST_TYPE . '&p=' . (int) $ids[0] . '&mdo_specials_lang=en';
	}

	public static function disable_canonical( $redirect, $requested ) {
		unset( $requested );
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		if ( 'en/specials' === $path || 0 === strpos( $path, 'en/specials/' ) ) {
			return false;
		}
		return $redirect;
	}
}
