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
	 * Keep the offer concise: the explanatory "Cómo funciona" block contains the
	 * useful mechanics, so the separate expanded paragraph remains intentionally empty.
	 */
	public static function migrate_tolecarnes_copy(): void {
		if ( get_option( 'mdo_specials_tolecarnes_copy_v4' ) ) {
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
			'content'    => '',
			'cta_label'  => 'Ver productos de Tolecarnes',
			'conditions' => 'Promoción válida para compras de productos de Tolecarnes realizadas en El Mercado de Origen hasta el 31 de agosto de 2026 incluido. No requiere código promocional.',
		);
		$en      = array(
			'summary'    => 'Buy Tolecarnes products on El Mercado de Origen and receive two 100% beef burgers as a gift.',
			'benefit'    => 'No code or special action is required. When you buy Tolecarnes products on El Mercado de Origen, two 100% beef burgers will be included as a gift with your order.',
			'content'    => '',
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
				'post_content' => '',
			)
		);

		update_option( 'mdo_specials_tolecarnes_copy_v4', 1, false );
	}

	/**
	 * Keep exactly one canonical Tolecarnes burger special and permanently remove
	 * any legacy/test duplicate left by the v1 -> bilingual v2 migration.
	 */
	public static function cleanup_legacy_tolecarnes_special(): void {
		if ( get_option( 'mdo_specials_tolecarnes_cleanup_v5' ) ) {
			return;
		}

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		$candidates = array();

		foreach ( $posts as $post ) {
			$seed    = (string) get_post_meta( $post->ID, '_mdo_promo_seed_key', true );
			$slug_es = (string) get_post_meta( $post->ID, '_mdo_promo_slug_es', true );
			$slug_en = (string) get_post_meta( $post->ID, '_mdo_promo_slug_en', true );
			$is_match = in_array( $seed, array( 'tolecarnes-hamburguesas-v1', 'tolecarnes-hamburguesas-v2' ), true )
				|| in_array( $post->post_name, array( 'hamburguesas-regalo-tole-carnes', 'hamburguesas-ternera-regalo-tolecarnes' ), true )
				|| 'hamburguesas-ternera-regalo-tolecarnes' === $slug_es
				|| 'free-beef-burgers-tolecarnes' === $slug_en;

			if ( ! $is_match ) {
				continue;
			}

			$summary_es = (string) get_post_meta( $post->ID, '_mdo_promo_summary_es', true );
			$score      = 0;
			$score     += 'tolecarnes-hamburguesas-v2' === $seed ? 100 : 0;
			$score     += 'hamburguesas-ternera-regalo-tolecarnes' === $post->post_name ? 80 : 0;
			$score     += 'hamburguesas-ternera-regalo-tolecarnes' === $slug_es ? 60 : 0;
			$score     += 'free-beef-burgers-tolecarnes' === $slug_en ? 30 : 0;
			$score     += false !== strpos( $summary_es, 'El Mercado de Origen' ) ? 40 : 0;
			$score     += (int) get_post_meta( $post->ID, '_mdo_promo_image_product_id', true ) > 0 ? 20 : 0;
			$score     += 'publish' === $post->post_status ? 10 : 0;
			$candidates[] = array( 'id' => (int) $post->ID, 'score' => $score );
		}

		if ( count( $candidates ) > 1 ) {
			usort(
				$candidates,
				static function ( array $a, array $b ): int {
					if ( $a['score'] === $b['score'] ) {
						return $a['id'] <=> $b['id'];
					}
					return $b['score'] <=> $a['score'];
				}
			);
			$keep_id = (int) $candidates[0]['id'];
			foreach ( $candidates as $candidate ) {
				$id = (int) $candidate['id'];
				if ( $id !== $keep_id ) {
					wp_delete_post( $id, true );
				}
			}
		}

		update_option( 'mdo_specials_tolecarnes_cleanup_v5', 1, false );
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
