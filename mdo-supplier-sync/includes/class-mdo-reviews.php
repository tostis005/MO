<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Reviews {
	private const CRON_HOOK = 'mdo_reviews_daily_import';
	private const SEED_HOOK = 'mdo_reviews_seed_import';
	private const SEED_VERSION = '1.0.31';
	private const PAGE_SIZE = 50;
	private const PUBLIC_PAGE_SIZE = 10;

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 30 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'admin_post_mdo_reviews_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_post_mdo_reviews_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_mdo_reviews_validate_suggestion', array( __CLASS__, 'handle_validate_suggestion' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'import_all' ) );
		add_action( self::SEED_HOOK, array( __CLASS__, 'import_all' ) );
		add_action( 'init', array( __CLASS__, 'ensure_schedule' ), 30 );
		add_action( 'init', array( __CLASS__, 'maybe_seed' ), 31 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ), 30 );
		add_action( 'wcfmmp_store_before_latest_reviews', array( __CLASS__, 'render_store_reviews' ), 5, 1 );
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		wp_clear_scheduled_hook( self::SEED_HOOK );
	}

	public static function admin_menu(): void {
		add_submenu_page( 'mdo-supplier-sync', 'Reseñas', 'Reseñas', 'manage_woocommerce', 'mdo-reviews', array( __CLASS__, 'admin_page' ) );
	}

	public static function admin_assets( string $hook_suffix ): void {
		if ( false !== strpos( $hook_suffix, 'mdo-reviews' ) ) {
			wp_enqueue_style( 'mdo-reviews', MDO_SUPPLIER_SYNC_URL . 'assets/reviews.css', array(), MDO_SUPPLIER_SYNC_VERSION );
		}
	}

	public static function frontend_assets(): void {
		if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
			wp_enqueue_style( 'mdo-reviews', MDO_SUPPLIER_SYNC_URL . 'assets/reviews.css', array(), MDO_SUPPLIER_SYNC_VERSION );
		}
	}

	public static function ensure_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function maybe_seed(): void {
		if ( self::SEED_VERSION === (string) get_option( 'mdo_reviews_seed_version', '' ) ) {
			return;
		}
		if ( ! wp_next_scheduled( self::SEED_HOOK ) ) {
			wp_schedule_single_event( time() + 30, self::SEED_HOOK );
		}
	}

	public static function import_all(): array {
		$stats = array(
			'woocommerce' => array( 'found' => 0, 'saved' => 0 ),
			'wcfm'        => array( 'found' => 0, 'saved' => 0 ),
			'google'      => array( 'found' => 0, 'saved' => 0 ),
			'trustpilot'  => array( 'found' => 0, 'saved' => 0 ),
		);
		$context = self::assignment_context();
		$stats['wcfm']        = self::import_wcfm_reviews();
		$stats['woocommerce'] = self::import_woocommerce_reviews();
		$stats['google']      = self::import_cached_source( 'google', array( 'trustindex-google-review-content', 'trustindex-google-reviews', 'google_reviews' ), $context );
		$stats['trustpilot']  = self::import_cached_source( 'trustpilot', array( 'trustindex-trustpilot-review-content', 'trustindex-trustpilot-reviews', 'trustpilot_reviews' ), $context );
		$extra = apply_filters( 'mdo_reviews_import_payloads', array() );
		if ( is_array( $extra ) ) {
			foreach ( $extra as $payload ) {
				if ( ! is_array( $payload ) ) {
					continue;
				}
				$source = sanitize_key( (string) ( $payload['source'] ?? 'external' ) );
				self::upsert_review( $source ?: 'external', self::apply_assignment( $payload, $context ) );
			}
		}
		update_option( 'mdo_reviews_seed_version', self::SEED_VERSION, false );
		update_option( 'mdo_reviews_last_import', array( 'at' => time(), 'stats' => $stats ), false );
		return $stats;
	}

	private static function import_wcfm_reviews(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'wcfm_marketplace_reviews';
		$meta  = $wpdb->prefix . 'wcfm_marketplace_review_rating_meta';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return array( 'found' => 0, 'saved' => 0 );
		}
		$has_meta = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $meta ) ) === $meta;
		$sql = $has_meta
			? "SELECT r.*, MAX(CASE WHEN m.`key` = 'product' AND m.`type` = 'rating_product' THEN m.`value` ELSE NULL END) AS product_id FROM {$table} r LEFT JOIN {$meta} m ON m.review_id = r.ID WHERE r.approved = 1 GROUP BY r.ID ORDER BY r.created ASC"
			: "SELECT r.*, NULL AS product_id FROM {$table} r WHERE r.approved = 1 ORDER BY r.created ASC";
		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$saved = 0;
		foreach ( (array) $rows as $row ) {
			$data = array(
				'source_review_id' => (string) $row->ID,
				'author_name' => (string) $row->author_name,
				'author_email' => (string) $row->author_email,
				'rating' => max( 1, min( 5, (int) round( (float) $row->review_rating ) ) ),
				'review_title' => (string) $row->review_title,
				'review_text' => (string) $row->review_description,
				'review_date' => self::normalize_datetime( (string) $row->created ),
				'vendor_user_id' => (int) $row->vendor_id,
				'wc_product_id' => (int) $row->product_id,
				'assignment_type' => 'wcfm_vendor',
				'assignment_confidence' => 1.0,
				'assignment_reason' => 'La reseña nativa de WCFM ya está vinculada a esta tienda.',
				'status' => 'validated',
				'validation_method' => 'source',
				'source_payload' => wp_json_encode( (array) $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			);
			if ( self::upsert_review( 'wcfm', $data ) ) {
				++$saved;
			}
		}
		return array( 'found' => count( (array) $rows ), 'saved' => $saved );
	}

	private static function import_woocommerce_reviews(): array {
		global $wpdb;
		$sql = "SELECT c.comment_ID,c.comment_author,c.comment_author_email,c.comment_content,c.comment_date,c.comment_date_gmt,p.ID AS product_id,p.post_author AS vendor_id,MAX(CAST(cm.meta_value AS DECIMAL(4,2))) AS rating FROM {$wpdb->comments} c INNER JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID AND p.post_type='product' INNER JOIN {$wpdb->commentmeta} cm ON cm.comment_id=c.comment_ID AND cm.meta_key='rating' WHERE c.comment_approved='1' GROUP BY c.comment_ID HAVING rating>0 ORDER BY c.comment_date ASC";
		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$saved = 0;
		foreach ( (array) $rows as $row ) {
			$vendor_id = (int) $row->vendor_id;
			$data = array(
				'source_review_id' => (string) $row->comment_ID,
				'author_name' => (string) $row->comment_author,
				'author_email' => (string) $row->comment_author_email,
				'rating' => max( 1, min( 5, (int) round( (float) $row->rating ) ) ),
				'review_text' => (string) $row->comment_content,
				'review_date' => self::normalize_datetime( (string) $row->comment_date ),
				'review_date_gmt' => self::normalize_datetime( (string) $row->comment_date_gmt ),
				'wc_product_id' => (int) $row->product_id,
				'vendor_user_id' => $vendor_id > 0 ? $vendor_id : null,
				'assignment_type' => 'product_owner',
				'assignment_confidence' => $vendor_id > 0 ? 1.0 : 0.0,
				'assignment_reason' => $vendor_id > 0 ? 'El producto reseñado pertenece directamente a esta tienda.' : 'No se ha podido determinar el propietario del producto.',
				'status' => $vendor_id > 0 ? 'validated' : 'pending',
				'validation_method' => $vendor_id > 0 ? 'automatic' : null,
				'source_payload' => wp_json_encode( (array) $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			);
			if ( self::upsert_review( 'woocommerce_product', $data ) ) {
				++$saved;
			}
		}
		return array( 'found' => count( (array) $rows ), 'saved' => $saved );
	}

	private static function import_cached_source( string $source, array $option_names, array $context ): array {
		$found = array();
		foreach ( $option_names as $option_name ) {
			$value = get_option( $option_name, null );
			if ( null === $value || false === $value || '' === $value ) {
				continue;
			}
			self::walk_candidates( self::decode_mixed( $value ), $found, $option_name );
		}
		$saved = 0;
		$seen = array();
		foreach ( $found as $candidate ) {
			$normalized = self::normalize_external_candidate( $candidate['data'], $candidate['option'] );
			if ( ( empty( $normalized['review_text'] ) && empty( $normalized['author_name'] ) ) || empty( $normalized['rating'] ) ) {
				continue;
			}
			$dedupe = hash( 'sha256', self::normalize_text( (string) $normalized['author_name'] ) . '|' . self::normalize_text( (string) $normalized['review_text'] ) . '|' . (string) $normalized['rating'] . '|' . (string) ( $normalized['review_date'] ?? '' ) );
			if ( isset( $seen[ $dedupe ] ) ) {
				continue;
			}
			$seen[ $dedupe ] = true;
			if ( self::upsert_review( $source, self::apply_assignment( $normalized, $context ) ) ) {
				++$saved;
			}
		}
		return array( 'found' => count( $seen ), 'saved' => $saved );
	}

	private static function decode_mixed( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return json_decode( wp_json_encode( $value ), true );
		}
		if ( ! is_string( $value ) ) {
			return $value;
		}
		$unserialized = maybe_unserialize( $value );
		if ( $unserialized !== $value ) {
			return self::decode_mixed( $unserialized );
		}
		$json = json_decode( $value, true );
		return JSON_ERROR_NONE === json_last_error() ? $json : array( 'raw' => $value );
	}

	private static function walk_candidates( $node, array &$found, string $option_name ): void {
		if ( is_object( $node ) ) {
			$node = (array) $node;
		}
		if ( ! is_array( $node ) ) {
			return;
		}
		if ( self::looks_like_review( $node ) ) {
			$found[] = array( 'data' => $node, 'option' => $option_name );
		}
		foreach ( $node as $child ) {
			if ( is_array( $child ) || is_object( $child ) ) {
				self::walk_candidates( $child, $found, $option_name );
			}
		}
	}

	private static function looks_like_review( array $row ): bool {
		$keys = array_change_key_case( array_fill_keys( array_keys( $row ), true ), CASE_LOWER );
		$rating = isset( $keys['rating'] ) || isset( $keys['stars'] ) || isset( $keys['score'] ) || isset( $keys['review_rating'] );
		$body = isset( $keys['text'] ) || isset( $keys['review'] ) || isset( $keys['review_text'] ) || isset( $keys['content'] ) || isset( $keys['comment'] ) || isset( $keys['reviewcontent'] ) || isset( $keys['author'] ) || isset( $keys['name'] ) || isset( $keys['reviewer'] );
		return $rating && $body;
	}

	private static function normalize_external_candidate( array $row, string $option_name ): array {
		$id = self::first_value( $row, array( 'review_id', 'google_review_id', 'trustpilot_review_id', 'id' ) );
		$author = self::first_value( $row, array( 'author_name', 'reviewer_name', 'user_name', 'reviewer', 'author', 'name' ) );
		$text = self::first_value( $row, array( 'review_text', 'reviewContent', 'content', 'comment', 'review', 'text' ) );
		$title = self::first_value( $row, array( 'review_title', 'title', 'headline' ) );
		$rating = self::first_value( $row, array( 'rating', 'stars', 'score', 'review_rating' ) );
		$date = self::first_value( $row, array( 'review_date', 'published_at', 'created_at', 'timestamp', 'time', 'date' ) );
		$url = self::first_value( $row, array( 'review_url', 'url', 'permalink' ) );
		$avatar = self::first_value( $row, array( 'profile_photo_url', 'avatar', 'photo', 'image' ) );
		$email = self::first_value( $row, array( 'author_email', 'email' ) );
		$rating_value = is_numeric( $rating ) ? (int) round( (float) $rating ) : 0;
		return array(
			'source_review_id' => self::scalar_string( $id ),
			'source_url' => self::scalar_string( $url ),
			'author_name' => self::scalar_string( $author ),
			'author_email' => is_email( self::scalar_string( $email ) ) ? self::scalar_string( $email ) : '',
			'author_avatar_url' => esc_url_raw( self::scalar_string( $avatar ) ),
			'rating' => $rating_value >= 1 && $rating_value <= 5 ? $rating_value : 0,
			'review_title' => self::scalar_string( $title ),
			'review_text' => self::scalar_string( $text ),
			'review_date' => self::normalize_datetime( $date ),
			'status' => 'pending',
			'validation_method' => null,
			'assignment_confidence' => 0.0,
			'assignment_reason' => 'No se ha encontrado todavía una relación fiable con una tienda.',
			'source_payload' => wp_json_encode( array( 'option' => $option_name, 'data' => $row ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
		);
	}

	private static function first_value( array $row, array $aliases ) {
		$lower = array();
		foreach ( $row as $key => $value ) {
			$lower[ strtolower( (string) $key ) ] = $value;
		}
		foreach ( $aliases as $alias ) {
			$key = strtolower( $alias );
			if ( array_key_exists( $key, $lower ) && null !== $lower[ $key ] && '' !== $lower[ $key ] ) {
				return $lower[ $key ];
			}
		}
		return '';
	}

	private static function scalar_string( $value ): string {
		if ( is_scalar( $value ) ) {
			return trim( wp_strip_all_tags( (string) $value ) );
		}
		if ( is_array( $value ) ) {
			foreach ( array( 'name', 'display_name', 'displayName', 'text', 'value', 'url' ) as $key ) {
				if ( isset( $value[ $key ] ) && is_scalar( $value[ $key ] ) ) {
					return trim( wp_strip_all_tags( (string) $value[ $key ] ) );
				}
			}
		}
		return '';
	}

	private static function normalize_datetime( $value ): ?string {
		if ( null === $value || '' === $value ) {
			return null;
		}
		try {
			if ( is_numeric( $value ) ) {
				$timestamp = (int) $value;
				if ( $timestamp > 9999999999 ) {
					$timestamp = (int) floor( $timestamp / 1000 );
				}
				return wp_date( 'Y-m-d H:i:s', $timestamp );
			}
			$timestamp = strtotime( (string) $value );
			return $timestamp ? wp_date( 'Y-m-d H:i:s', $timestamp ) : null;
		} catch ( Throwable $e ) {
			return null;
		}
	}

	private static function assignment_context(): array {
		global $wpdb;
		$vendors = array();
		$suppliers = MDO_Database::table( 'suppliers' );
		$rows = $wpdb->get_results( "SELECT id,code,name,vendor_user_id FROM {$suppliers} WHERE vendor_user_id IS NOT NULL AND vendor_user_id>0" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( (array) $rows as $row ) {
			$id = (int) $row->vendor_user_id;
			if ( ! isset( $vendors[ $id ] ) ) {
				$vendors[ $id ] = array( 'aliases' => array(), 'name' => self::vendor_name( $id ) );
			}
			foreach ( array( $row->name, $row->code ) as $alias ) {
				$alias = self::normalize_text( (string) $alias );
				if ( strlen( $alias ) >= 4 ) {
					$vendors[ $id ]['aliases'][] = $alias;
				}
			}
		}
		$users = get_users( array( 'role' => 'wcfm_vendor', 'fields' => array( 'ID', 'display_name' ) ) );
		foreach ( (array) $users as $user ) {
			$id = (int) $user->ID;
			if ( ! isset( $vendors[ $id ] ) ) {
				$vendors[ $id ] = array( 'aliases' => array(), 'name' => self::vendor_name( $id ) );
			}
			foreach ( array( $user->display_name, self::vendor_name( $id ) ) as $alias ) {
				$alias = self::normalize_text( (string) $alias );
				if ( strlen( $alias ) >= 4 ) {
					$vendors[ $id ]['aliases'][] = $alias;
				}
			}
			$vendors[ $id ]['aliases'] = array_values( array_unique( $vendors[ $id ]['aliases'] ) );
		}
		$products_table = MDO_Database::table( 'source_products' );
		$products = $wpdb->get_results( "SELECT p.id,p.wc_product_id,p.title,s.vendor_user_id FROM {$products_table} p INNER JOIN {$suppliers} s ON s.id=p.supplier_id WHERE s.vendor_user_id IS NOT NULL AND s.vendor_user_id>0 AND p.title IS NOT NULL AND p.title<>''" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$product_map = array();
		foreach ( (array) $products as $product ) {
			$title = self::normalize_text( (string) $product->title );
			if ( strlen( $title ) >= 12 ) {
				$product_map[] = array( 'source_product_id' => (int) $product->id, 'wc_product_id' => (int) $product->wc_product_id, 'vendor_user_id' => (int) $product->vendor_user_id, 'title' => $title );
			}
		}
		return array( 'vendors' => $vendors, 'products' => $product_map );
	}

	private static function apply_assignment( array $data, array $context ): array {
		if ( ! empty( $data['vendor_user_id'] ) ) {
			return $data;
		}
		$haystack = self::normalize_text( trim( (string) ( $data['review_title'] ?? '' ) . ' ' . (string) ( $data['review_text'] ?? '' ) ) );
		if ( '' === $haystack ) {
			return $data;
		}
		$order = self::match_order( $haystack );
		if ( $order ) {
			return self::finalize_assignment( array_merge( $data, $order ) );
		}
		$product_matches = array();
		foreach ( $context['products'] as $product ) {
			if ( false !== strpos( $haystack, $product['title'] ) ) {
				$product_matches[] = $product;
			}
		}
		$vendor_ids = array_values( array_unique( array_map( static fn( $item ) => (int) $item['vendor_user_id'], $product_matches ) ) );
		if ( 1 === count( $vendor_ids ) && $product_matches ) {
			$match = $product_matches[0];
			$data['suggested_vendor_user_id'] = $vendor_ids[0];
			$data['source_product_id'] = (int) $match['source_product_id'];
			if ( ! empty( $match['wc_product_id'] ) ) {
				$data['wc_product_id'] = (int) $match['wc_product_id'];
			}
			$data['assignment_type'] = 'source_product';
			$data['assignment_confidence'] = 0.97;
			$data['assignment_reason'] = 'El texto menciona de forma exacta un producto origen asociado a una única tienda.';
			return self::finalize_assignment( $data );
		}
		if ( count( $vendor_ids ) > 1 ) {
			$data['assignment_reason'] = 'Se han detectado productos de varias tiendas; requiere validación manual.';
			return $data;
		}
		$matches = array();
		foreach ( $context['vendors'] as $vendor_id => $vendor ) {
			foreach ( $vendor['aliases'] as $alias ) {
				if ( strlen( $alias ) >= 4 && false !== strpos( $haystack, $alias ) ) {
					$matches[ (int) $vendor_id ] = true;
					break;
				}
			}
		}
		if ( 1 === count( $matches ) ) {
			$data['suggested_vendor_user_id'] = (int) array_key_first( $matches );
			$data['assignment_type'] = 'store_alias';
			$data['assignment_confidence'] = 0.95;
			$data['assignment_reason'] = 'El texto menciona de forma inequívoca el nombre o alias de una única tienda.';
			return self::finalize_assignment( $data );
		}
		if ( count( $matches ) > 1 ) {
			$data['assignment_reason'] = 'El texto coincide con más de una tienda; requiere validación manual.';
		}
		return $data;
	}

	private static function match_order( string $text ): ?array {
		if ( ! function_exists( 'wc_get_order' ) || ! preg_match( '/\b(?:pedido|order)\s*(?:n[ºo.]?\s*)?#?\s*(\d{2,12})\b/iu', $text, $match ) ) {
			return null;
		}
		$order_id = (int) $match[1];
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array( 'wc_order_id' => $order_id, 'assignment_type' => 'order_unresolved', 'assignment_confidence' => 0.20, 'assignment_reason' => 'El texto parece mencionar el pedido #' . $order_id . ', pero no se ha podido cargar.' );
		}
		$vendors = array();
		$products = array();
		foreach ( $order->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();
			if ( ! $product_id ) {
				continue;
			}
			$products[] = $product_id;
			$vendor_id = (int) get_post_field( 'post_author', $product_id );
			if ( $vendor_id > 0 ) {
				$vendors[ $vendor_id ] = true;
			}
		}
		if ( 1 === count( $vendors ) ) {
			return array( 'wc_order_id' => $order_id, 'wc_product_id' => 1 === count( array_unique( $products ) ) ? (int) $products[0] : null, 'suggested_vendor_user_id' => (int) array_key_first( $vendors ), 'assignment_type' => 'order', 'assignment_confidence' => 0.98, 'assignment_reason' => 'El pedido #' . $order_id . ' contiene productos de una única tienda.' );
		}
		return array( 'wc_order_id' => $order_id, 'assignment_type' => 'order_multiple_vendors', 'assignment_confidence' => 0.60, 'assignment_reason' => 'El pedido #' . $order_id . ' contiene productos de varias tiendas; requiere validación manual.' );
	}

	private static function finalize_assignment( array $data ): array {
		$confidence = (float) ( $data['assignment_confidence'] ?? 0 );
		$suggested = (int) ( $data['suggested_vendor_user_id'] ?? 0 );
		if ( $suggested > 0 && $confidence >= 0.95 ) {
			$data['vendor_user_id'] = $suggested;
			$data['status'] = 'validated';
			$data['validation_method'] = 'automatic';
		}
		return $data;
	}

	private static function upsert_review( string $source, array $data ): bool {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$source = sanitize_key( $source );
		$source_id = (string) ( $data['source_review_id'] ?? '' );
		$key_basis = $source_id ?: implode( '|', array( (string) ( $data['author_name'] ?? '' ), (string) ( $data['review_date'] ?? '' ), (string) ( $data['rating'] ?? '' ), (string) ( $data['review_text'] ?? '' ) ) );
		$source_key = hash( 'sha256', $source . '|' . $key_basis );
		$fingerprint = self::content_fingerprint( $data );
		$now = current_time( 'mysql' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE source_key=%s LIMIT 1", $source_key ) );
		if ( ! $existing && $fingerprint ) {
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE content_fingerprint=%s LIMIT 1", $fingerprint ) );
		}
		$resolved_status = trim( (string) ( $data['status'] ?? 'pending' ) );
		if ( ! in_array( $resolved_status, array( 'pending', 'validated', 'rejected' ), true ) ) {
			$resolved_status = 'pending';
		}
		$row = array(
			'source' => $source,
			'source_review_id' => $source_id ?: null,
			'source_key' => $existing ? (string) $existing->source_key : $source_key,
			'content_fingerprint' => $fingerprint,
			'source_url' => esc_url_raw( (string) ( $data['source_url'] ?? '' ) ) ?: null,
			'author_name' => sanitize_text_field( (string) ( $data['author_name'] ?? '' ) ) ?: null,
			'author_email' => sanitize_email( (string) ( $data['author_email'] ?? '' ) ) ?: null,
			'author_avatar_url' => esc_url_raw( (string) ( $data['author_avatar_url'] ?? '' ) ) ?: null,
			'rating' => ! empty( $data['rating'] ) ? max( 1, min( 5, (int) $data['rating'] ) ) : null,
			'review_title' => sanitize_text_field( (string) ( $data['review_title'] ?? '' ) ) ?: null,
			'review_text' => wp_kses_post( (string) ( $data['review_text'] ?? '' ) ),
			'review_date' => $data['review_date'] ?? null,
			'review_date_gmt' => $data['review_date_gmt'] ?? null,
			'vendor_user_id' => ! empty( $data['vendor_user_id'] ) ? (int) $data['vendor_user_id'] : null,
			'suggested_vendor_user_id' => ! empty( $data['suggested_vendor_user_id'] ) ? (int) $data['suggested_vendor_user_id'] : null,
			'wc_product_id' => ! empty( $data['wc_product_id'] ) ? (int) $data['wc_product_id'] : null,
			'source_product_id' => ! empty( $data['source_product_id'] ) ? (int) $data['source_product_id'] : null,
			'wc_order_id' => ! empty( $data['wc_order_id'] ) ? (int) $data['wc_order_id'] : null,
			'assignment_type' => sanitize_key( (string) ( $data['assignment_type'] ?? '' ) ) ?: null,
			'assignment_confidence' => max( 0, min( 1, (float) ( $data['assignment_confidence'] ?? 0 ) ) ),
			'assignment_reason' => sanitize_textarea_field( (string) ( $data['assignment_reason'] ?? '' ) ) ?: null,
			'status' => $resolved_status,
			'validation_method' => sanitize_key( (string) ( $data['validation_method'] ?? '' ) ) ?: null,
			'source_payload' => (string) ( $data['source_payload'] ?? '' ) ?: null,
			'last_seen_at' => $now,
			'updated_at' => $now,
		);
		if ( $existing ) {
			if ( 'manual' === (string) $existing->validation_method || 'rejected' === (string) $existing->status ) {
				unset( $row['vendor_user_id'], $row['suggested_vendor_user_id'], $row['status'], $row['validation_method'], $row['assignment_type'], $row['assignment_confidence'], $row['assignment_reason'] );
			}
			if ( empty( $row['wc_product_id'] ) && ! empty( $existing->wc_product_id ) ) {
				$row['wc_product_id'] = (int) $existing->wc_product_id;
			}
			if ( empty( $row['wc_order_id'] ) && ! empty( $existing->wc_order_id ) ) {
				$row['wc_order_id'] = (int) $existing->wc_order_id;
			}
			return false !== $wpdb->update( $table, $row, array( 'id' => (int) $existing->id ) );
		}
		$row['first_seen_at'] = $now;
		$row['created_at'] = $now;
		if ( 'validated' === $row['status'] ) {
			$row['validated_at'] = $now;
		}
		return false !== $wpdb->insert( $table, $row );
	}

	private static function content_fingerprint( array $data ): string {
		$author = self::normalize_text( (string) ( $data['author_name'] ?? '' ) );
		$text = self::normalize_text( (string) ( $data['review_text'] ?? '' ) );
		if ( '' === $text ) {
			return '';
		}
		$date = (string) ( $data['review_date'] ?? '' );
		$date = $date ? substr( $date, 0, 10 ) : '';
		$vendor = (int) ( $data['vendor_user_id'] ?? $data['suggested_vendor_user_id'] ?? 0 );
		$product = (int) ( $data['wc_product_id'] ?? 0 );
		$scope = $product > 0 ? 'product:' . $product : 'date:' . $date;
		return hash( 'sha256', implode( '|', array( $vendor, $scope, $author, $text, (int) ( $data['rating'] ?? 0 ) ) ) );
	}

	private static function normalize_text( string $text ): string {
		$text = remove_accents( wp_strip_all_tags( $text ) );
		$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
		$text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
		return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
	}

	public static function handle_import(): void {
		self::guard_admin_action( 'mdo_reviews_import' );
		$stats = self::import_all();
		$found = 0;
		$saved = 0;
		foreach ( $stats as $source_stats ) {
			$found += (int) $source_stats['found'];
			$saved += (int) $source_stats['saved'];
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'mdo-reviews', 'mdo_notice' => 'imported', 'found' => $found, 'saved' => $saved ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_validate_suggestion(): void {
		self::guard_admin_action( 'mdo_reviews_validate_suggestion' );
		$id = absint( $_POST['review_id'] ?? 0 );
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$review = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $id ) );
		if ( $review && (int) $review->suggested_vendor_user_id > 0 ) {
			$wpdb->update( $table, array( 'vendor_user_id' => (int) $review->suggested_vendor_user_id, 'status' => 'validated', 'validation_method' => 'manual', 'validated_by' => get_current_user_id(), 'validated_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'mdo-reviews', 'mdo_notice' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_save(): void {
		self::guard_admin_action( 'mdo_reviews_save' );
		$id = absint( $_POST['review_id'] ?? 0 );
		$status = sanitize_key( (string) ( $_POST['status'] ?? 'pending' ) );
		if ( ! in_array( $status, array( 'pending', 'validated', 'rejected' ), true ) ) {
			$status = 'pending';
		}
		$vendor_id = absint( $_POST['vendor_user_id'] ?? 0 );
		$product_id = absint( $_POST['wc_product_id'] ?? 0 );
		$order_id = absint( $_POST['wc_order_id'] ?? 0 );
		$notice = 'saved';
		if ( 'validated' === $status && ! $vendor_id ) {
			$status = 'pending';
			$notice = 'vendor_required';
		}
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$wpdb->update( $table, array(
			'vendor_user_id' => 'validated' === $status ? $vendor_id : null,
			'suggested_vendor_user_id' => 'validated' === $status ? $vendor_id : ( $vendor_id ?: null ),
			'wc_product_id' => $product_id ?: null,
			'wc_order_id' => $order_id ?: null,
			'status' => $status,
			'validation_method' => 'manual',
			'validated_by' => 'validated' === $status ? get_current_user_id() : null,
			'validated_at' => 'validated' === $status ? current_time( 'mysql' ) : null,
			'updated_at' => current_time( 'mysql' ),
		), array( 'id' => $id ) );
		wp_safe_redirect( add_query_arg( array( 'page' => 'mdo-reviews', 'review_id' => $id, 'mdo_notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function guard_admin_action( string $action ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'mdo-supplier-sync' ) );
		}
		check_admin_referer( $action );
	}

	public static function admin_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$review_id = absint( $_GET['review_id'] ?? 0 );
		if ( $review_id ) {
			self::render_detail( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $review_id ) ) );
			return;
		}
		$status = sanitize_key( (string) ( $_GET['status'] ?? '' ) );
		$source = sanitize_key( (string) ( $_GET['source'] ?? '' ) );
		$vendor = absint( $_GET['vendor'] ?? 0 );
		$search = sanitize_text_field( (string) ( $_GET['s'] ?? '' ) );
		$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$where = array( '1=1' );
		$params = array();
		if ( in_array( $status, array( 'pending', 'validated', 'rejected', 'unassigned' ), true ) ) {
			if ( 'unassigned' === $status ) {
				$where[] = "status='pending' AND (suggested_vendor_user_id IS NULL OR suggested_vendor_user_id=0)";
			} else {
				$where[] = 'status=%s';
				$params[] = $status;
			}
		}
		if ( $source ) {
			$where[] = 'source=%s';
			$params[] = $source;
		}
		if ( $vendor ) {
			$where[] = '(vendor_user_id=%d OR suggested_vendor_user_id=%d)';
			$params[] = $vendor;
			$params[] = $vendor;
		}
		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(author_name LIKE %s OR review_text LIKE %s OR review_title LIKE %s OR assignment_reason LIKE %s)';
			$params = array_merge( $params, array( $like, $like, $like, $like ) );
		}
		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, ...$params ) : $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$offset = ( $page - 1 ) * self::PAGE_SIZE;
		$list_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY (review_date IS NULL) ASC,review_date DESC,id DESC LIMIT %d OFFSET %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...array_merge( $params, array( self::PAGE_SIZE, $offset ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$counts = self::status_counts();
		$sources = $wpdb->get_col( "SELECT DISTINCT source FROM {$table} ORDER BY source ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$vendors = self::vendor_options();
		?>
		<div class="wrap mdo-sync-wrap mdo-reviews-admin">
			<h1>Reseñas</h1>
			<p>Importa, asigna y valida opiniones de producto, WCFM, Google y Trustpilot. Solo las validadas se muestran públicamente.</p>
			<?php self::render_notice(); ?>
			<div class="mdo-review-toolbar"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mdo_reviews_import"><?php wp_nonce_field( 'mdo_reviews_import' ); ?><button class="button button-primary">Importar ahora</button></form><?php $last = get_option( 'mdo_reviews_last_import', array() ); if ( ! empty( $last['at'] ) ) : ?><span class="description">Última importación: <?php echo esc_html( wp_date( 'd/m/Y H:i', (int) $last['at'] ) ); ?></span><?php endif; ?></div>
			<div class="mdo-review-cards"><?php foreach ( array( 'all' => 'Todas', 'pending' => 'Pendientes', 'validated' => 'Validadas', 'unassigned' => 'Sin tienda', 'rejected' => 'Descartadas' ) as $key => $label ) : ?><a class="mdo-review-card" href="<?php echo esc_url( add_query_arg( array( 'page' => 'mdo-reviews', 'status' => 'all' === $key ? false : $key ), admin_url( 'admin.php' ) ) ); ?>"><strong><?php echo esc_html( (string) ( $counts[ $key ] ?? 0 ) ); ?></strong><span><?php echo esc_html( $label ); ?></span></a><?php endforeach; ?></div>
			<form method="get" class="mdo-review-filters"><input type="hidden" name="page" value="mdo-reviews"><select name="status"><option value="">Todos los estados</option><?php foreach ( array( 'pending' => 'Pendientes', 'validated' => 'Validadas', 'unassigned' => 'Sin tienda', 'rejected' => 'Descartadas' ) as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><select name="source"><option value="">Todas las fuentes</option><?php foreach ( $sources as $source_name ) : ?><option value="<?php echo esc_attr( $source_name ); ?>" <?php selected( $source, $source_name ); ?>><?php echo esc_html( self::source_label( $source_name ) ); ?></option><?php endforeach; ?></select><select name="vendor"><option value="0">Todas las tiendas</option><?php foreach ( $vendors as $vendor_id => $vendor_name ) : ?><option value="<?php echo esc_attr( (string) $vendor_id ); ?>" <?php selected( $vendor, $vendor_id ); ?>><?php echo esc_html( $vendor_name ); ?></option><?php endforeach; ?></select><input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Persona, reseña o motivo"><button class="button">Filtrar</button></form>
			<div class="mdo-review-table-wrap"><table class="widefat striped mdo-review-table"><thead><tr><th>Fecha</th><th>Fuente</th><th>Persona</th><th>Puntuación</th><th>Reseña</th><th>Tienda</th><th>Producto / pedido</th><th>Confianza</th><th>Estado</th><th></th></tr></thead><tbody>
			<?php if ( empty( $rows ) ) : ?><tr><td colspan="10">No hay reseñas para estos filtros.</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( self::format_date( $row->review_date ) ); ?></td><td><span class="mdo-source-badge"><?php echo esc_html( self::source_label( $row->source ) ); ?></span></td><td><strong><?php echo esc_html( $row->author_name ?: 'Anónimo' ); ?></strong></td><td><?php echo wp_kses_post( self::stars( (int) $row->rating ) ); ?></td><td><div class="mdo-review-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) $row->review_text ), 24 ) ); ?></div></td><td><?php echo wp_kses_post( self::store_cell( $row ) ); ?></td><td><?php echo wp_kses_post( self::relation_cell( $row ) ); ?></td><td><?php echo esc_html( number_format_i18n( (float) $row->assignment_confidence * 100, 0 ) . '%' ); ?><br><small><?php echo esc_html( (string) $row->assignment_reason ); ?></small></td><td><span class="mdo-review-status is-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( self::status_label( $row->status ) ); ?></span></td><td class="mdo-review-actions"><a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'mdo-reviews', 'review_id' => $row->id ), admin_url( 'admin.php' ) ) ); ?>">Abrir</a><?php if ( 'pending' === $row->status && (int) $row->suggested_vendor_user_id > 0 ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mdo_reviews_validate_suggestion"><input type="hidden" name="review_id" value="<?php echo esc_attr( (string) $row->id ); ?>"><?php wp_nonce_field( 'mdo_reviews_validate_suggestion' ); ?><button class="button button-small">Validar sugerencia</button></form><?php endif; ?></td></tr><?php endforeach; ?>
			</tbody></table></div><?php self::render_admin_pagination( $page, $total ); ?></div>
		<?php
	}

	private static function render_detail( $review ): void {
		$vendors = self::vendor_options();
		?>
		<div class="wrap mdo-sync-wrap mdo-reviews-admin"><p><a href="<?php echo esc_url( add_query_arg( 'page', 'mdo-reviews', admin_url( 'admin.php' ) ) ); ?>">← Volver a reseñas</a></p><?php self::render_notice(); ?>
		<?php if ( ! $review ) : ?><div class="notice notice-error"><p>Reseña no encontrada.</p></div></div><?php return; endif; ?>
		<h1>Reseña #<?php echo esc_html( (string) $review->id ); ?></h1><div class="mdo-review-detail-grid"><section class="mdo-review-detail-card"><h2><?php echo esc_html( $review->author_name ?: 'Anónimo' ); ?></h2><div class="mdo-review-stars-large"><?php echo wp_kses_post( self::stars( (int) $review->rating ) ); ?></div><p class="description"><?php echo esc_html( self::format_date( $review->review_date ) ); ?> · <?php echo esc_html( self::source_label( $review->source ) ); ?></p><?php if ( $review->review_title ) : ?><h3><?php echo esc_html( $review->review_title ); ?></h3><?php endif; ?><div class="mdo-review-fulltext"><?php echo wpautop( wp_kses_post( $review->review_text ) ); ?></div><?php if ( $review->source_url ) : ?><p><a href="<?php echo esc_url( $review->source_url ); ?>" target="_blank" rel="noopener">Abrir reseña original ↗</a></p><?php endif; ?><dl><dt>Correo</dt><dd><?php echo esc_html( $review->author_email ?: '—' ); ?></dd><dt>ID fuente</dt><dd><?php echo esc_html( $review->source_review_id ?: '—' ); ?></dd><dt>Motivo de asignación</dt><dd><?php echo esc_html( $review->assignment_reason ?: '—' ); ?></dd><dt>Confianza</dt><dd><?php echo esc_html( number_format_i18n( (float) $review->assignment_confidence * 100, 0 ) . '%' ); ?></dd></dl><?php if ( $review->source_payload ) : ?><details><summary>Datos originales importados</summary><pre><?php echo esc_html( self::pretty_payload( $review->source_payload ) ); ?></pre></details><?php endif; ?></section>
		<section class="mdo-review-detail-card"><h2>Asignación y validación</h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mdo_reviews_save"><input type="hidden" name="review_id" value="<?php echo esc_attr( (string) $review->id ); ?>"><?php wp_nonce_field( 'mdo_reviews_save' ); ?><p><label><strong>Tienda</strong><br><select name="vendor_user_id"><option value="0">Sin asignar</option><?php $selected_vendor = (int) ( $review->vendor_user_id ?: $review->suggested_vendor_user_id ); foreach ( $vendors as $vendor_id => $vendor_name ) : ?><option value="<?php echo esc_attr( (string) $vendor_id ); ?>" <?php selected( $selected_vendor, $vendor_id ); ?>><?php echo esc_html( $vendor_name ); ?></option><?php endforeach; ?></select></label></p><p><label><strong>Producto WooCommerce (ID)</strong><br><input type="number" min="0" name="wc_product_id" value="<?php echo esc_attr( (string) ( $review->wc_product_id ?: '' ) ); ?>"></label></p><p><label><strong>Pedido WooCommerce (ID)</strong><br><input type="number" min="0" name="wc_order_id" value="<?php echo esc_attr( (string) ( $review->wc_order_id ?: '' ) ); ?>"></label></p><p><label><strong>Estado</strong><br><select name="status"><option value="pending" <?php selected( $review->status, 'pending' ); ?>>Pendiente</option><option value="validated" <?php selected( $review->status, 'validated' ); ?>>Validada</option><option value="rejected" <?php selected( $review->status, 'rejected' ); ?>>Descartada</option></select></label></p><p><button class="button button-primary">Guardar</button></p></form></section></div></div>
		<?php
	}

	private static function render_notice(): void {
		$notice = sanitize_key( (string) ( $_GET['mdo_notice'] ?? '' ) );
		if ( 'imported' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>Importación completada. Detectadas ' . esc_html( (string) absint( $_GET['found'] ?? 0 ) ) . ' y guardadas/actualizadas ' . esc_html( (string) absint( $_GET['saved'] ?? 0 ) ) . ' reseñas.</p></div>';
		} elseif ( 'saved' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>Reseña guardada.</p></div>';
		} elseif ( 'vendor_required' === $notice ) {
			echo '<div class="notice notice-warning is-dismissible"><p>Para validar una reseña debes asignarla a una tienda. Se ha mantenido pendiente.</p></div>';
		}
	}

	private static function status_counts(): array {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$counts = array( 'all' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ), 'pending' => 0, 'validated' => 0, 'rejected' => 0, 'unassigned' => 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( (array) $wpdb->get_results( "SELECT status,COUNT(*) total FROM {$table} GROUP BY status" ) as $row ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$counts[ $row->status ] = (int) $row->total;
		}
		$counts['unassigned'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status='pending' AND (suggested_vendor_user_id IS NULL OR suggested_vendor_user_id=0)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $counts;
	}

	private static function vendor_options(): array {
		$vendors = array();
		$users = get_users( array( 'role' => 'wcfm_vendor', 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array( 'ID', 'display_name' ) ) );
		foreach ( $users as $user ) {
			$vendors[ (int) $user->ID ] = self::vendor_name( (int) $user->ID ) ?: $user->display_name;
		}
		global $wpdb;
		$suppliers = MDO_Database::table( 'suppliers' );
		$linked_ids = $wpdb->get_col( "SELECT DISTINCT vendor_user_id FROM {$suppliers} WHERE vendor_user_id IS NOT NULL AND vendor_user_id>0" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( (array) $linked_ids as $vendor_id ) {
			$vendor_id = (int) $vendor_id;
			if ( $vendor_id > 0 && ! isset( $vendors[ $vendor_id ] ) ) {
				$vendors[ $vendor_id ] = self::vendor_name( $vendor_id );
			}
		}
		natcasesort( $vendors );
		return $vendors;
	}

	private static function vendor_name( int $vendor_id ): string {
		if ( function_exists( 'wcfmmp_get_store' ) ) {
			$store = wcfmmp_get_store( $vendor_id );
			if ( $store && method_exists( $store, 'get_shop_name' ) ) {
				$name = (string) $store->get_shop_name();
				if ( $name ) {
					return $name;
				}
			}
		}
		$user = get_userdata( $vendor_id );
		return $user ? (string) $user->display_name : 'Tienda #' . $vendor_id;
	}

	private static function store_cell( $row ): string {
		if ( (int) $row->vendor_user_id > 0 ) {
			return '<strong>' . esc_html( self::vendor_name( (int) $row->vendor_user_id ) ) . '</strong>';
		}
		if ( (int) $row->suggested_vendor_user_id > 0 ) {
			return '<span class="mdo-suggestion">Sugerida: <strong>' . esc_html( self::vendor_name( (int) $row->suggested_vendor_user_id ) ) . '</strong></span>';
		}
		return '<span class="description">Sin asignar</span>';
	}

	private static function relation_cell( $row ): string {
		$parts = array();
		if ( (int) $row->wc_product_id > 0 ) {
			$title = get_the_title( (int) $row->wc_product_id );
			$parts[] = 'Producto #' . (int) $row->wc_product_id . ( $title ? ': ' . esc_html( $title ) : '' );
		}
		if ( (int) $row->wc_order_id > 0 ) {
			$parts[] = 'Pedido #' . (int) $row->wc_order_id;
		}
		return $parts ? implode( '<br>', $parts ) : '<span class="description">—</span>';
	}

	private static function render_admin_pagination( int $page, int $total ): void {
		$pages = (int) ceil( $total / self::PAGE_SIZE );
		if ( $pages <= 1 ) {
			return;
		}
		$args = $_GET;
		unset( $args['paged'] );
		$args['page'] = 'mdo-reviews';
		echo '<div class="tablenav"><div class="tablenav-pages">';
		echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( array_merge( $args, array( 'paged' => '%#%' ) ), admin_url( 'admin.php' ) ), 'current' => $page, 'total' => $pages ) ) );
		echo '</div></div>';
	}

	public static function render_store_reviews( int $vendor_id ): void {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status='validated' AND vendor_user_id=%d", $vendor_id ) );
		if ( $total < 1 ) {
			return;
		}
		$page = max( 1, absint( $_GET['mdo_review_page'] ?? 1 ) );
		$offset = ( $page - 1 ) * self::PUBLIC_PAGE_SIZE;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status='validated' AND vendor_user_id=%d ORDER BY (review_date IS NULL) ASC,review_date DESC,id DESC LIMIT %d OFFSET %d", $vendor_id, self::PUBLIC_PAGE_SIZE, $offset ) );
		if ( ! $rows ) {
			return;
		}
		echo '<style>.bd_review_section>.review_section,.bd_review_section>.pagination{display:none!important}</style><div class="mdo-store-reviews" aria-label="Reseñas de la tienda">';
		foreach ( $rows as $row ) {
			self::render_public_review( $row );
		}
		$pages = (int) ceil( $total / self::PUBLIC_PAGE_SIZE );
		if ( $pages > 1 ) {
			$base_url = remove_query_arg( 'mdo_review_page' );
			echo '<nav class="mdo-review-pagination" aria-label="Paginación de reseñas">';
			for ( $i = 1; $i <= $pages; $i++ ) {
				$url = 1 === $i ? $base_url : add_query_arg( 'mdo_review_page', $i, $base_url );
				echo '<a class="' . ( $i === $page ? 'is-current' : '' ) . '" href="' . esc_url( $url ) . '#reviews">' . esc_html( (string) $i ) . '</a>';
			}
			echo '</nav>';
		}
		echo '</div>';
	}

	private static function render_public_review( $row ): void {
		$name = $row->author_name ?: 'Cliente';
		$initial = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : substr( $name, 0, 1 );
		$product_title = (int) $row->wc_product_id > 0 ? get_the_title( (int) $row->wc_product_id ) : '';
		echo '<article class="mdo-store-review"><div class="mdo-store-review-avatar">';
		if ( $row->author_avatar_url ) {
			echo '<img src="' . esc_url( $row->author_avatar_url ) . '" alt="">';
		} else {
			echo '<span>' . esc_html( strtoupper( $initial ) ) . '</span>';
		}
		echo '</div><div class="mdo-store-review-body"><div class="mdo-store-review-head"><strong>' . esc_html( $name ) . '</strong><span class="mdo-source-badge">' . esc_html( self::source_label( $row->source ) ) . '</span></div><div class="mdo-store-review-meta">' . wp_kses_post( self::stars( (int) $row->rating ) ) . '<span>' . esc_html( self::format_date( $row->review_date ) ) . '</span></div>';
		if ( $row->review_title ) {
			echo '<h4>' . esc_html( $row->review_title ) . '</h4>';
		}
		echo '<div class="mdo-store-review-text">' . wpautop( wp_kses_post( $row->review_text ) ) . '</div>';
		if ( $product_title && (int) $row->wc_product_id > 0 ) {
			echo '<p class="mdo-store-review-product">Sobre: <a href="' . esc_url( get_permalink( (int) $row->wc_product_id ) ) . '">' . esc_html( $product_title ) . '</a></p>';
		}
		echo '</div></article>';
	}

	private static function stars( int $rating ): string {
		$rating = max( 0, min( 5, $rating ) );
		$out = '<span class="mdo-stars" aria-label="' . esc_attr( sprintf( '%d de 5 estrellas', $rating ) ) . '">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$out .= '<span class="' . ( $i <= $rating ? 'is-filled' : '' ) . '" aria-hidden="true">★</span>';
		}
		return $out . '</span>';
	}

	private static function source_label( string $source ): string {
		$labels = array( 'woocommerce_product' => 'Producto', 'wcfm' => 'Tienda', 'google' => 'Google', 'trustpilot' => 'Trustpilot', 'external' => 'Externa' );
		return $labels[ $source ] ?? ucfirst( str_replace( '_', ' ', $source ) );
	}

	private static function status_label( string $status ): string {
		$labels = array( 'pending' => 'Pendiente', 'validated' => 'Validada', 'rejected' => 'Descartada' );
		return $labels[ $status ] ?? $status;
	}

	private static function format_date( $date ): string {
		if ( ! $date ) {
			return 'Sin fecha';
		}
		$timestamp = strtotime( (string) $date );
		return $timestamp ? wp_date( 'd/m/Y', $timestamp ) : 'Sin fecha';
	}

	private static function pretty_payload( string $payload ): string {
		$decoded = json_decode( $payload, true );
		return JSON_ERROR_NONE === json_last_error() ? (string) wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : $payload;
	}
}
