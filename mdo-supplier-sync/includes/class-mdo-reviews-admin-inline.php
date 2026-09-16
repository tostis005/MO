<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Reviews_Admin_Inline {
	private const NONCE_ACTION = 'mdo_reviews_inline';

	public static function init(): void {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 40 );
		add_action( 'wp_ajax_mdo_reviews_inline_data', array( __CLASS__, 'ajax_data' ) );
		add_action( 'wp_ajax_mdo_reviews_inline_save', array( __CLASS__, 'ajax_save' ) );
	}

	public static function enqueue_assets(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( 'mdo-reviews' !== sanitize_key( (string) ( $_GET['page'] ?? '' ) ) || ! empty( $_GET['review_id'] ) ) {
			return;
		}
		wp_enqueue_script( 'mdo-reviews-admin-inline', MDO_SUPPLIER_SYNC_URL . 'assets/reviews-admin-inline.js', array(), MDO_SUPPLIER_SYNC_VERSION, true );
		wp_localize_script(
			'mdo-reviews-admin-inline',
			'MDOReviewsInline',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( self::NONCE_ACTION ),
				'labels' => array(
					'save' => 'Guardar',
					'saving' => 'Guardando…',
					'saved' => 'Guardado',
					'error' => 'No se ha podido guardar.',
					'vendorRequired' => 'Selecciona al menos una tienda antes de publicar.',
					'detail' => 'Detalle',
				),
			)
		);
	}

	public static function ajax_data(): void {
		self::guard_ajax();
		$ids = isset( $_POST['ids'] ) && is_array( $_POST['ids'] )
			? array_values( array_unique( array_filter( array_map( 'absint', $_POST['ids'] ) ) ) )
			: array();
		$ids = array_slice( $ids, 0, 100 );
		if ( ! $ids ) {
			wp_send_json_success( array( 'reviews' => array(), 'vendors' => self::vendor_options() ) );
		}
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,status,vendor_user_id,suggested_vendor_user_id FROM {$table} WHERE id IN ({$placeholders})", ...$ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$mapped = MDO_Reviews_Vendors::ids_for_reviews( $ids );
		$reviews = array();
		foreach ( (array) $rows as $row ) {
			$id = (int) $row['id'];
			$status = sanitize_key( (string) $row['status'] );
			if ( ! in_array( $status, array( 'pending', 'validated', 'rejected' ), true ) ) {
				$status = 'pending';
			}
			$vendor_ids = array_values( array_unique( array_filter( array_map( 'intval', $mapped[ $id ] ?? array() ) ) ) );
			if ( ! $vendor_ids ) {
				foreach ( array( (int) $row['vendor_user_id'], (int) $row['suggested_vendor_user_id'] ) as $vendor_id ) {
					if ( $vendor_id > 0 ) {
						$vendor_ids[] = $vendor_id;
					}
				}
				$vendor_ids = array_values( array_unique( $vendor_ids ) );
			}
			$reviews[ $id ] = array( 'id' => $id, 'status' => $status, 'vendor_ids' => $vendor_ids );
		}
		wp_send_json_success( array( 'reviews' => $reviews, 'vendors' => self::vendor_options() ) );
	}

	public static function ajax_save(): void {
		self::guard_ajax();
		$id = absint( $_POST['review_id'] ?? 0 );
		$status = sanitize_key( (string) ( $_POST['status'] ?? 'pending' ) );
		$vendor_ids = isset( $_POST['vendor_user_ids'] ) && is_array( $_POST['vendor_user_ids'] )
			? array_values( array_unique( array_filter( array_map( 'absint', $_POST['vendor_user_ids'] ) ) ) )
			: array();
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Reseña no válida.' ), 400 );
		}
		if ( ! in_array( $status, array( 'pending', 'validated', 'rejected' ), true ) ) {
			$status = 'pending';
		}
		if ( 'validated' === $status && ! $vendor_ids ) {
			wp_send_json_error( array( 'message' => 'Selecciona al menos una tienda antes de publicar.' ), 400 );
		}
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id=%d", $id ) );
		if ( ! $exists ) {
			wp_send_json_error( array( 'message' => 'Reseña no encontrada.' ), 404 );
		}
		if ( ! MDO_Reviews_Vendors::replace( $id, $vendor_ids ) ) {
			wp_send_json_error( array( 'message' => 'No se han podido guardar las tiendas.' ), 500 );
		}
		$primary = $vendor_ids ? (int) $vendor_ids[0] : 0;
		$now = current_time( 'mysql' );
		$update = array(
			'vendor_user_id' => 'validated' === $status && $primary ? $primary : null,
			'suggested_vendor_user_id' => $primary ?: null,
			'status' => $status,
			'validation_method' => 'manual',
			'validated_by' => 'validated' === $status ? get_current_user_id() : null,
			'validated_at' => 'validated' === $status ? $now : null,
			'updated_at' => $now,
			'assignment_type' => $vendor_ids ? 'manual_multi' : null,
			'assignment_confidence' => $vendor_ids ? 1.0 : 0,
			'assignment_reason' => $vendor_ids ? sprintf( 'Asignación manual a %d tienda(s) desde el listado de reseñas.', count( $vendor_ids ) ) : null,
		);
		$result = $wpdb->update( $table, $update, array( 'id' => $id ) );
		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'No se ha podido guardar la reseña.' ), 500 );
		}
		wp_send_json_success( array( 'id' => $id, 'status' => $status, 'vendor_ids' => $vendor_ids, 'message' => 'Guardado' ) );
	}

	private static function guard_ajax(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos para realizar esta acción.' ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}

	private static function vendor_options(): array {
		$vendors = array();
		foreach ( get_users( array( 'role' => 'wcfm_vendor', 'fields' => array( 'ID', 'display_name' ) ) ) as $user ) {
			$name = (string) $user->display_name;
			if ( function_exists( 'wcfmmp_get_store' ) ) {
				$store = wcfmmp_get_store( (int) $user->ID );
				if ( $store && method_exists( $store, 'get_shop_name' ) ) {
					$name = (string) $store->get_shop_name();
				}
			}
			$vendors[ (int) $user->ID ] = $name;
		}
		asort( $vendors, SORT_NATURAL | SORT_FLAG_CASE );
		return $vendors;
	}
}
