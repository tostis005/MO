<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Edición rápida de reseñas desde el listado de EMDO.
 *
 * Mantiene la vista de detalle como respaldo, pero permite asignar tienda y
 * estado sin abrir cada reseña individualmente.
 */
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

		wp_enqueue_script(
			'mdo-reviews-admin-inline',
			MDO_SUPPLIER_SYNC_URL . 'assets/reviews-admin-inline.js',
			array(),
			MDO_SUPPLIER_SYNC_VERSION,
			true
		);
		wp_localize_script(
			'mdo-reviews-admin-inline',
			'MDOReviewsInline',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'labels'  => array(
					'save'          => 'Guardar',
					'saving'        => 'Guardando…',
					'saved'         => 'Guardado',
					'error'         => 'No se ha podido guardar.',
					'vendorRequired'=> 'Selecciona una tienda antes de publicar.',
					'detail'        => 'Detalle',
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
		if ( empty( $ids ) ) {
			wp_send_json_success( array( 'reviews' => array(), 'vendors' => self::vendor_options() ) );
		}

		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql = "SELECT id,status,vendor_user_id,suggested_vendor_user_id FROM {$table} WHERE id IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$reviews = array();
		foreach ( (array) $rows as $row ) {
			$id = (int) $row['id'];
			$status = sanitize_key( (string) $row['status'] );
			if ( ! in_array( $status, array( 'pending', 'validated', 'rejected' ), true ) ) {
				$status = 'pending';
			}
			$reviews[ $id ] = array(
				'id'        => $id,
				'status'    => $status,
				'vendor_id' => (int) ( $row['vendor_user_id'] ?: $row['suggested_vendor_user_id'] ),
			);
		}

		wp_send_json_success(
			array(
				'reviews' => $reviews,
				'vendors' => self::vendor_options(),
			)
		);
	}

	public static function ajax_save(): void {
		self::guard_ajax();

		$id = absint( $_POST['review_id'] ?? 0 );
		$status = sanitize_key( (string) ( $_POST['status'] ?? 'pending' ) );
		$vendor_id = absint( $_POST['vendor_user_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Reseña no válida.' ), 400 );
		}
		if ( ! in_array( $status, array( 'pending', 'validated', 'rejected' ), true ) ) {
			$status = 'pending';
		}
		if ( 'validated' === $status && ! $vendor_id ) {
			wp_send_json_error( array( 'message' => 'Selecciona una tienda antes de publicar.' ), 400 );
		}

		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id=%d", $id ) );
		if ( ! $exists ) {
			wp_send_json_error( array( 'message' => 'Reseña no encontrada.' ), 404 );
		}

		$now = current_time( 'mysql' );
		$update = array(
			'vendor_user_id'            => 'validated' === $status ? $vendor_id : null,
			'suggested_vendor_user_id'  => $vendor_id ?: null,
			'status'                    => $status,
			'validation_method'         => 'manual',
			'validated_by'              => 'validated' === $status ? get_current_user_id() : null,
			'validated_at'              => 'validated' === $status ? $now : null,
			'updated_at'                => $now,
		);
		if ( $vendor_id ) {
			$update['assignment_type'] = 'manual';
			$update['assignment_confidence'] = 1.0;
			$update['assignment_reason'] = 'Asignación manual desde el listado de reseñas.';
		}

		$result = $wpdb->update( $table, $update, array( 'id' => $id ) );
		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'No se ha podido guardar la reseña.' ), 500 );
		}

		wp_send_json_success(
			array(
				'id'        => $id,
				'status'    => $status,
				'vendor_id' => $vendor_id,
				'message'   => 'Guardado',
			)
		);
	}

	private static function guard_ajax(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos para realizar esta acción.' ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}

	private static function vendor_options(): array {
		try {
			$reflection = new ReflectionMethod( 'MDO_Reviews', 'vendor_options' );
			if ( method_exists( $reflection, 'setAccessible' ) ) {
				$reflection->setAccessible( true );
			}
			$vendors = $reflection->invoke( null );
			if ( is_array( $vendors ) ) {
				return $vendors;
			}
		} catch ( Throwable $error ) {
			error_log( '[EMDO reviews] No se pudieron cargar las tiendas para edición rápida: ' . $error->getMessage() );
		}

		$vendors = array();
		foreach ( get_users( array( 'role' => 'wcfm_vendor', 'fields' => array( 'ID', 'display_name' ) ) ) as $user ) {
			$vendors[ (int) $user->ID ] = (string) $user->display_name;
		}
		asort( $vendors, SORT_NATURAL | SORT_FLAG_CASE );
		return $vendors;
	}
}
