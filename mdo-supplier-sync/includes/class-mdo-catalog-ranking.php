<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reusable backend scoring engine for the future EMDO default shop order.
 * Nothing here is attached to WooCommerce catalogue queries yet.
 */
final class MDO_Catalog_Ranking {
	public const PRIORITY_META = '_mdo_catalog_priority';

	public static function priority_levels(): array {
		return array(
			0   => 'Sin prioridad',
			40  => 'Baja',
			60  => 'Media',
			80  => 'Alta',
			100 => 'Máxima',
		);
	}

	public static function get_priority( int $product_id ): int {
		$value = absint( get_post_meta( $product_id, self::PRIORITY_META, true ) );
		return array_key_exists( $value, self::priority_levels() ) ? $value : 0;
	}

	/** Set 0 to remove the explicit priority and return to the default state. */
	public static function set_priority( int $product_id, int $priority ): bool {
		$product_id = absint( $product_id );
		$priority   = absint( $priority );
		if ( ! $product_id || 'product' !== get_post_type( $product_id ) || ! array_key_exists( $priority, self::priority_levels() ) ) {
			return false;
		}

		if ( 0 === $priority ) {
			delete_post_meta( $product_id, self::PRIORITY_META );
			return true;
		}

		update_post_meta( $product_id, self::PRIORITY_META, $priority );
		return self::get_priority( $product_id ) === $priority;
	}

	/**
	 * Rank a candidate product set using recent behaviour, editorial priority,
	 * freshness and a deterministic small rotation. Vendor diversity is applied
	 * as a second pass so one producer cannot monopolise the first positions.
	 *
	 * @return int[] Product IDs in ranked order.
	 */
	public static function rank_products( array $product_ids, array $args = array() ): array {
		$items = self::score_products( $product_ids, $args );
		if ( empty( $items ) ) {
			return array();
		}

		usort(
			$items,
			static function( array $a, array $b ): int {
				if ( abs( $a['score'] - $b['score'] ) > 0.000001 ) {
					return $a['score'] < $b['score'] ? 1 : -1;
				}
				return $a['product_id'] <=> $b['product_id'];
			}
		);

		$diversify = array_key_exists( 'diversify_vendors', $args ) ? (bool) $args['diversify_vendors'] : true;
		if ( $diversify ) {
			$items = self::diversify_vendors( $items, $args );
		}

		return array_values( array_map( 'absint', wp_list_pluck( $items, 'product_id' ) ) );
	}

	/**
	 * Return the complete scoring breakdown for diagnostics/tuning in EMDO.
	 * Interest is an extension point: no invasive product-view tracker is added.
	 */
	public static function score_products( array $product_ids, array $args = array() ): array {
		$product_ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
		if ( empty( $product_ids ) ) {
			return array();
		}

		$defaults = array(
			'recent_sales_days' => 90,
			'product_new_days'  => 45,
			'vendor_new_days'   => 60,
			'rotation_seed'     => gmdate( 'Y-m-d' ),
		);
		$args = wp_parse_args( $args, $defaults );

		$weights = array(
			'priority'        => 0.35,
			'recent_sales'    => 0.25,
			'recent_interest' => 0.10,
			'product_new'     => 0.10,
			'vendor_new'      => 0.15,
			'rotation'        => 0.05,
		);
		$weights = (array) apply_filters( 'mdo_catalog_ranking_weights', $weights, $args );

		$sales_counts    = self::recent_sales_counts( $product_ids, max( 1, absint( $args['recent_sales_days'] ) ) );
		$interest_raw    = (array) apply_filters( 'mdo_catalog_recent_interest_scores', array(), $product_ids, $args );
		$sales_scores    = self::normalize_positive_metric( $sales_counts, true );
		$interest_scores = self::normalize_positive_metric( $interest_raw, true );
		$vendor_dates    = self::vendor_first_product_dates( $product_ids );
		$now             = time();
		$items           = array();

		foreach ( $product_ids as $product_id ) {
			$post = get_post( $product_id );
			if ( ! $post || 'product' !== $post->post_type || 'trash' === $post->post_status ) {
				continue;
			}

			$vendor_id    = absint( $post->post_author );
			$product_age  = self::age_in_days( (string) $post->post_date_gmt, $now );
			$vendor_age   = isset( $vendor_dates[ $vendor_id ] ) ? self::age_in_days( $vendor_dates[ $vendor_id ], $now ) : PHP_INT_MAX;
			$product_new  = self::decaying_newness( $product_age, max( 1, absint( $args['product_new_days'] ) ) );
			$vendor_new   = self::decaying_newness( $vendor_age, max( 1, absint( $args['vendor_new_days'] ) ) );
			$priority     = self::get_priority( $product_id ) / 100;
			$recent_sales = (float) ( $sales_scores[ $product_id ] ?? 0.0 );
			$interest     = (float) ( $interest_scores[ $product_id ] ?? 0.0 );
			$rotation     = self::stable_rotation( $product_id, (string) $args['rotation_seed'] );

			$components = array(
				'priority'        => $priority,
				'recent_sales'    => $recent_sales,
				'recent_interest' => $interest,
				'product_new'     => $product_new,
				'vendor_new'      => $vendor_new,
				'rotation'        => $rotation,
			);
			$score = 0.0;
			foreach ( $components as $key => $value ) {
				$score += max( 0.0, (float) ( $weights[ $key ] ?? 0.0 ) ) * max( 0.0, min( 1.0, (float) $value ) );
			}

			$items[] = array(
				'product_id' => $product_id,
				'vendor_id'  => $vendor_id,
				'score'      => (float) apply_filters( 'mdo_catalog_product_score', $score, $product_id, $components, $args ),
				'components' => $components,
			);
		}

		return $items;
	}

	/** Recent quantities sold, deliberately not lifetime total_sales. */
	public static function recent_sales_counts( array $product_ids, int $days = 90 ): array {
		global $wpdb;

		$product_ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
		if ( empty( $product_ids ) ) {
			return array();
		}

		$item_table   = $wpdb->prefix . 'woocommerce_order_items';
		$meta_table   = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$since        = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * max( 1, $days ) );
		$id_marks     = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
		$statuses     = array( 'wc-processing', 'wc-completed', 'wc-on-hold' );
		$status_marks = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$params       = array_merge( $product_ids, $statuses, array( $since ) );

		$hpos = class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' )
			&& is_callable( array( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil', 'custom_orders_table_usage_is_enabled' ) )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

		if ( $hpos ) {
			$order_table = $wpdb->prefix . 'wc_orders';
			$sql = "SELECT CAST(pid.meta_value AS UNSIGNED) product_id, SUM(CAST(COALESCE(qty.meta_value, '1') AS DECIMAL(18,4))) qty
				FROM {$item_table} oi
				INNER JOIN {$meta_table} pid ON pid.order_item_id = oi.order_item_id AND pid.meta_key = '_product_id'
				LEFT JOIN {$meta_table} qty ON qty.order_item_id = oi.order_item_id AND qty.meta_key = '_qty'
				INNER JOIN {$order_table} o ON o.id = oi.order_id
				WHERE oi.order_item_type = 'line_item'
				AND CAST(pid.meta_value AS UNSIGNED) IN ({$id_marks})
				AND o.status IN ({$status_marks})
				AND o.date_created_gmt >= %s
				GROUP BY CAST(pid.meta_value AS UNSIGNED)";
		} else {
			$sql = "SELECT CAST(pid.meta_value AS UNSIGNED) product_id, SUM(CAST(COALESCE(qty.meta_value, '1') AS DECIMAL(18,4))) qty
				FROM {$item_table} oi
				INNER JOIN {$meta_table} pid ON pid.order_item_id = oi.order_item_id AND pid.meta_key = '_product_id'
				LEFT JOIN {$meta_table} qty ON qty.order_item_id = oi.order_item_id AND qty.meta_key = '_qty'
				INNER JOIN {$wpdb->posts} o ON o.ID = oi.order_id
				WHERE oi.order_item_type = 'line_item'
				AND CAST(pid.meta_value AS UNSIGNED) IN ({$id_marks})
				AND o.post_status IN ({$status_marks})
				AND o.post_date_gmt >= %s
				GROUP BY CAST(pid.meta_value AS UNSIGNED)";
		}

		$query = $wpdb->prepare( $sql, ...$params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows  = $wpdb->get_results( $query, ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out   = array_fill_keys( $product_ids, 0.0 );
		foreach ( $rows as $row ) {
			$id = absint( $row['product_id'] ?? 0 );
			if ( $id ) {
				$out[ $id ] = max( 0.0, (float) ( $row['qty'] ?? 0 ) );
			}
		}

		return $out;
	}

	private static function vendor_first_product_dates( array $product_ids ): array {
		global $wpdb;

		$vendor_ids = array();
		foreach ( $product_ids as $product_id ) {
			$vendor_ids[] = absint( get_post_field( 'post_author', $product_id ) );
		}
		$vendor_ids = array_values( array_unique( array_filter( $vendor_ids ) ) );
		if ( empty( $vendor_ids ) ) {
			return array();
		}

		$marks = implode( ',', array_fill( 0, count( $vendor_ids ), '%d' ) );
		$sql   = "SELECT post_author, MIN(post_date_gmt) first_date
			FROM {$wpdb->posts}
			WHERE post_type = 'product'
			AND post_status = 'publish'
			AND post_author IN ({$marks})
			GROUP BY post_author";
		$query = $wpdb->prepare( $sql, ...$vendor_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows  = $wpdb->get_results( $query, ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out   = array();
		foreach ( $rows as $row ) {
			$out[ absint( $row['post_author'] ) ] = (string) $row['first_date'];
		}
		return $out;
	}

	private static function normalize_positive_metric( array $values, bool $logarithmic = false ): array {
		$clean = array();
		$max   = 0.0;
		foreach ( $values as $key => $value ) {
			$value = max( 0.0, (float) $value );
			$value = $logarithmic ? log( 1 + $value ) : $value;
			$clean[ absint( $key ) ] = $value;
			$max = max( $max, $value );
		}
		if ( $max <= 0 ) {
			return array_fill_keys( array_keys( $clean ), 0.0 );
		}
		foreach ( $clean as $key => $value ) {
			$clean[ $key ] = $value / $max;
		}
		return $clean;
	}

	private static function age_in_days( string $gmt_date, int $now ): int {
		$timestamp = $gmt_date ? strtotime( $gmt_date . ' UTC' ) : false;
		if ( false === $timestamp ) {
			return PHP_INT_MAX;
		}
		return max( 0, (int) floor( ( $now - $timestamp ) / DAY_IN_SECONDS ) );
	}

	private static function decaying_newness( int $age_days, int $window_days ): float {
		if ( $age_days < 0 || $age_days >= $window_days ) {
			return 0.0;
		}
		return max( 0.0, 1.0 - ( $age_days / max( 1, $window_days ) ) );
	}

	private static function stable_rotation( int $product_id, string $seed ): float {
		$hex = substr( hash( 'sha256', $seed . '|' . $product_id ), 0, 8 );
		$int = hexdec( $hex );
		return $int / 4294967295;
	}

	private static function diversify_vendors( array $items, array $args ): array {
		$window_size      = max( 4, absint( $args['diversity_candidate_window'] ?? 12 ) );
		$recent_window    = max( 2, absint( $args['diversity_recent_window'] ?? 8 ) );
		$repeat_penalty   = max( 0.0, (float) ( $args['diversity_repeat_penalty'] ?? 0.08 ) );
		$consecutive_cost = max( 0.0, (float) ( $args['diversity_consecutive_penalty'] ?? 0.14 ) );
		$result            = array();
		$recent_vendors    = array();

		while ( $items ) {
			$limit      = min( $window_size, count( $items ) );
			$best_index = 0;
			$best_score = -PHP_FLOAT_MAX;

			for ( $i = 0; $i < $limit; $i++ ) {
				$vendor_id = absint( $items[ $i ]['vendor_id'] );
				$repeats   = 0;
				foreach ( $recent_vendors as $recent_vendor ) {
					if ( $vendor_id && $vendor_id === $recent_vendor ) {
						$repeats++;
					}
				}
				$adjusted = (float) $items[ $i ]['score'] - $repeats * $repeat_penalty;
				if ( $recent_vendors && $vendor_id && $vendor_id === end( $recent_vendors ) ) {
					$adjusted -= $consecutive_cost;
				}
				if ( $adjusted > $best_score ) {
					$best_score = $adjusted;
					$best_index = $i;
				}
			}

			$chosen   = $items[ $best_index ];
			$result[] = $chosen;
			array_splice( $items, $best_index, 1 );
			$recent_vendors[] = absint( $chosen['vendor_id'] );
			if ( count( $recent_vendors ) > $recent_window ) {
				array_shift( $recent_vendors );
			}
		}

		return $result;
	}
}
