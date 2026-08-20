<?php
/**
 * Plugin Name: MDO Catalog Recommended Order
 * Description: Makes the EMDO multi-factor ranking the canonical default WooCommerce catalogue order while preserving explicit customer sorting choices.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Catalog_Recommended_Order_20260820 {
	private const ORDERBY = 'mdo_recommended';

	public static function init(): void {
		add_filter( 'woocommerce_catalog_orderby', array( __CLASS__, 'catalog_orderby_options' ), PHP_INT_MAX );
		add_filter( 'woocommerce_default_catalog_orderby_options', array( __CLASS__, 'catalog_orderby_options' ), PHP_INT_MAX );
		add_filter( 'woocommerce_default_catalog_orderby', array( __CLASS__, 'default_catalog_orderby' ), PHP_INT_MAX );
		add_filter( 'woocommerce_get_catalog_ordering_args', array( __CLASS__, 'safe_ordering_args' ), PHP_INT_MAX, 3 );

		/*
		 * The destination frontend already applies the EMDO rank when there is no
		 * explicit orderby. If the customer explicitly chooses “Recomendados”,
		 * invoke that same public method after its normal 1600 hook so both paths
		 * are guaranteed to use one ranking implementation.
		 */
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_explicit_recommended_order' ), 1700 );

		/* A newly published product must enter the cached rank immediately. */
		add_action( 'save_post_product', array( __CLASS__, 'invalidate_rank_after_product_save' ), 20, 3 );
		add_action( 'transition_post_status', array( __CLASS__, 'invalidate_rank_after_status_change' ), 20, 3 );
	}

	public static function catalog_orderby_options( array $options ): array {
		unset( $options[ self::ORDERBY ] );
		return array( self::ORDERBY => self::label() ) + $options;
	}

	public static function default_catalog_orderby( string $orderby ): string {
		unset( $orderby );
		return self::ORDERBY;
	}

	/**
	 * WooCommerce does not know our custom key natively. Give it a harmless
	 * temporary SQL order; the main catalogue query is replaced with post__in by
		 * the EMDO rank hook at priority 1600/1700 before SQL execution.
	 */
	public static function safe_ordering_args( array $args, string $orderby, string $order ): array {
		unset( $order );
		if ( self::ORDERBY !== $orderby ) {
			return $args;
		}

		$args['orderby'] = 'menu_order title';
		$args['order']   = 'ASC';
		unset( $args['meta_key'] );
		return $args;
	}

	public static function apply_explicit_recommended_order( WP_Query $query ): void {
		if ( is_admin() || ! class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
			return;
		}

		$requested = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::ORDERBY !== $requested ) {
			return;
		}

		/*
		 * Reuse the existing destination/ranking method without duplicating any of
		 * its eligibility, vendor-exclusion, cache or score logic. That method only
		 * skips when it sees an explicit GET orderby, so hide this one momentarily.
		 */
		$original_get = $_GET['orderby']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['orderby'] );
		try {
			MDO_Catalog_Destination_Frontend::apply_default_ranking( $query );
		} finally {
			$_GET['orderby'] = $original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	public static function invalidate_rank_after_product_save( int $post_id, WP_Post $post, bool $update ): void {
		unset( $post_id, $post, $update );
		self::invalidate_rank_cache();
	}

	public static function invalidate_rank_after_status_change( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'product' !== $post->post_type || $new_status === $old_status ) {
			return;
		}
		self::invalidate_rank_cache();
	}

	private static function invalidate_rank_cache(): void {
		/* Same key used by MDO_Catalog_Destination_Frontend::ranked_product_ids(). */
		delete_transient( 'mdo_catalog_rank_v3_' . gmdate( 'Ymd' ) );
	}

	private static function label(): string {
		if ( function_exists( 'mdo_sst_is_english' ) && mdo_sst_is_english() ) {
			return 'Recommended';
		}
		if ( function_exists( 'mdo_en_is_request' ) && mdo_en_is_request() ) {
			return 'Recommended';
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		return ( '/en' === $path || 0 === strpos( $path, '/en/' ) ) ? 'Recommended' : 'Recomendados';
	}
}

MDO_Catalog_Recommended_Order_20260820::init();
