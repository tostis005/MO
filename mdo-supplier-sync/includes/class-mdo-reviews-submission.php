<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Formulario público de reseñas de tienda gestionado directamente por EMDO.
 */
final class MDO_Reviews_Submission {
	private const ACTION = 'mdo_store_review_submit';
	private const RATE_LIMIT_SECONDS = 300;

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle_submit' ) );
	}

	public static function render_form( int $vendor_id ): void {
		if ( $vendor_id < 1 ) {
			return;
		}
		$user = wp_get_current_user();
		$name = $user && $user->exists() ? (string) $user->display_name : '';
		$email = $user && $user->exists() ? (string) $user->user_email : '';
		$success = isset( $_GET['mdo_review_submitted'] ) && '1' === (string) $_GET['mdo_review_submitted'];
		$error = sanitize_key( (string) ( $_GET['mdo_review_error'] ?? '' ) );

		echo '<section class="mdo-review-form-section" id="dejar-resena">';
		echo '<h3>' . esc_html__( 'Deja tu reseña', 'mdo-supplier-sync' ) . '</h3>';
		if ( $success ) {
			echo '<div class="mdo-review-form-notice is-success">' . esc_html__( 'Gracias. Tu reseña se ha publicado correctamente.', 'mdo-supplier-sync' ) . '</div>';
		} elseif ( $error ) {
			echo '<div class="mdo-review-form-notice is-error">' . esc_html( self::error_message( $error ) ) . '</div>';
		}
		echo '<form class="mdo-review-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
		echo '<input type="hidden" name="vendor_id" value="' . esc_attr( (string) $vendor_id ) . '">';
		wp_nonce_field( self::ACTION . '_' . $vendor_id );
		echo '<div class="mdo-review-hp" aria-hidden="true"><label>Website<input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label></div>';
		echo '<div class="mdo-review-form-grid">';
		echo '<p><label><span>' . esc_html__( 'Nombre', 'mdo-supplier-sync' ) . '</span><input type="text" name="author_name" required maxlength="120" value="' . esc_attr( $name ) . '"></label></p>';
		echo '<p><label><span>' . esc_html__( 'Email', 'mdo-supplier-sync' ) . '</span><input type="email" name="author_email" required maxlength="190" value="' . esc_attr( $email ) . '"></label></p>';
		echo '</div>';
		echo '<fieldset class="mdo-review-rating"><legend>' . esc_html__( 'Valoración', 'mdo-supplier-sync' ) . '</legend><div class="mdo-review-rating-options">';
		for ( $rating = 5; $rating >= 1; $rating-- ) {
			$id = 'mdo-rating-' . $vendor_id . '-' . $rating;
			echo '<input type="radio" id="' . esc_attr( $id ) . '" name="rating" value="' . esc_attr( (string) $rating ) . '" ' . ( 5 === $rating ? 'required' : '' ) . '><label for="' . esc_attr( $id ) . '" title="' . esc_attr( sprintf( '%d de 5', $rating ) ) . '">★</label>';
		}
		echo '</div></fieldset>';
		echo '<p><label><span>' . esc_html__( 'Tu opinión', 'mdo-supplier-sync' ) . '</span><textarea name="review_text" rows="5" required minlength="10" maxlength="3000"></textarea></label></p>';
		echo '<p><button type="submit" class="button alt mdo-review-submit">' . esc_html__( 'Publicar reseña', 'mdo-supplier-sync' ) . '</button></p>';
		echo '</form></section>';
	}

	public static function handle_submit(): void {
		$vendor_id = absint( $_POST['vendor_id'] ?? 0 );
		if ( $vendor_id < 1 || ! self::valid_vendor( $vendor_id ) ) {
			self::redirect( $vendor_id, 'vendor' );
		}
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::ACTION . '_' . $vendor_id ) ) {
			self::redirect( $vendor_id, 'nonce' );
		}
		if ( ! empty( $_POST['website'] ) ) {
			self::redirect( $vendor_id, '1', true );
		}

		$name = sanitize_text_field( wp_unslash( (string) ( $_POST['author_name'] ?? '' ) ) );
		$email = sanitize_email( wp_unslash( (string) ( $_POST['author_email'] ?? '' ) ) );
		$text = trim( wp_kses_post( wp_unslash( (string) ( $_POST['review_text'] ?? '' ) ) ) );
		$rating = absint( $_POST['rating'] ?? 0 );
		if ( '' === $name || ! is_email( $email ) || $rating < 1 || $rating > 5 || strlen( wp_strip_all_tags( $text ) ) < 10 ) {
			self::redirect( $vendor_id, 'fields' );
		}
		if ( self::rate_limited( $vendor_id, $email ) ) {
			self::redirect( $vendor_id, 'rate' );
		}

		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$now_local = current_time( 'mysql' );
		$now_gmt = current_time( 'mysql', true );
		$source_id = 'store-' . wp_generate_uuid4();
		$source_key = hash( 'sha256', 'emdo|' . $source_id );
		$fingerprint = self::fingerprint( $vendor_id, $name, $text, $rating, $now_local );

		if ( $fingerprint ) {
			$existing = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE source='emdo' AND content_fingerprint=%s AND status<>'rejected' LIMIT 1", $fingerprint ) );
			if ( $existing > 0 ) {
				self::mark_rate_limit( $vendor_id, $email );
				self::redirect( $vendor_id, '1', true );
			}
		}

		$row = array(
			'source' => 'emdo',
			'source_review_id' => $source_id,
			'source_key' => $source_key,
			'content_fingerprint' => $fingerprint ?: null,
			'source_url' => null,
			'author_name' => $name,
			'author_email' => $email,
			'author_avatar_url' => null,
			'rating' => $rating,
			'review_title' => null,
			'review_text' => $text,
			'review_date' => $now_local,
			'review_date_gmt' => $now_gmt,
			'vendor_user_id' => $vendor_id,
			'suggested_vendor_user_id' => $vendor_id,
			'wc_product_id' => null,
			'source_product_id' => null,
			'wc_order_id' => null,
			'assignment_type' => 'store_form',
			'assignment_confidence' => 1.0,
			'assignment_reason' => 'Reseña enviada directamente desde la ficha pública de esta tienda en EMDO.',
			'status' => 'validated',
			'validation_method' => 'customer',
			'source_payload' => wp_json_encode( array( 'channel' => 'emdo_store_form', 'user_id' => get_current_user_id() ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			'validated_at' => $now_local,
			'first_seen_at' => $now_local,
			'last_seen_at' => $now_local,
			'created_at' => $now_local,
			'updated_at' => $now_local,
		);
		$inserted = $wpdb->insert( $table, $row );
		if ( false === $inserted ) {
			self::redirect( $vendor_id, 'save' );
		}
		$review_id = (int) $wpdb->insert_id;
		if ( $review_id > 0 ) {
			MDO_Reviews_Vendors::add( $review_id, $vendor_id );
		}
		self::mark_rate_limit( $vendor_id, $email );
		self::redirect( $vendor_id, '1', true );
	}

	private static function valid_vendor( int $vendor_id ): bool {
		$user = get_userdata( $vendor_id );
		if ( ! $user ) {
			return false;
		}
		if ( function_exists( 'wcfm_is_vendor' ) ) {
			return (bool) wcfm_is_vendor( $vendor_id );
		}
		return in_array( 'wcfm_vendor', (array) $user->roles, true );
	}

	private static function fingerprint( int $vendor_id, string $name, string $text, int $rating, string $date ): string {
		$normalize = static function ( string $value ): string {
			$value = remove_accents( wp_strip_all_tags( $value ) );
			$value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
			$value = preg_replace( '/[^a-z0-9]+/u', ' ', $value );
			return trim( preg_replace( '/\s+/', ' ', (string) $value ) );
		};
		$normalized_text = $normalize( $text );
		if ( '' === $normalized_text ) {
			return '';
		}
		return hash( 'sha256', implode( '|', array( $vendor_id, 'date:' . substr( $date, 0, 10 ), $normalize( $name ), $normalized_text, $rating ) ) );
	}

	private static function rate_key( int $vendor_id, string $email ): string {
		$ip = sanitize_text_field( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		return 'mdo_review_rate_' . md5( wp_hash( strtolower( $email ) . '|' . $vendor_id . '|' . $ip ) );
	}

	private static function rate_limited( int $vendor_id, string $email ): bool {
		return (bool) get_transient( self::rate_key( $vendor_id, $email ) );
	}

	private static function mark_rate_limit( int $vendor_id, string $email ): void {
		set_transient( self::rate_key( $vendor_id, $email ), 1, self::RATE_LIMIT_SECONDS );
	}

	private static function redirect( int $vendor_id, string $value, bool $success = false ): void {
		$user = $vendor_id > 0 ? get_userdata( $vendor_id ) : false;
		$base = trim( (string) get_option( 'wcfm_store_url', 'store' ), '/' );
		$url = $user ? home_url( '/' . $base . '/' . $user->user_nicename . '/reviews/' ) : home_url( '/' );
		$url = add_query_arg( $success ? 'mdo_review_submitted' : 'mdo_review_error', $value, $url );
		wp_safe_redirect( $url . '#dejar-resena' );
		exit;
	}

	private static function error_message( string $error ): string {
		$messages = array(
			'vendor' => 'No se ha podido identificar la tienda.',
			'nonce' => 'La sesión del formulario ha caducado. Recarga la página e inténtalo de nuevo.',
			'fields' => 'Revisa el nombre, email, valoración y texto de la reseña.',
			'rate' => 'Ya has enviado una reseña hace unos minutos. Inténtalo de nuevo más tarde.',
			'save' => 'No se ha podido guardar la reseña. Inténtalo de nuevo.',
		);
		return $messages[ $error ] ?? 'No se ha podido enviar la reseña.';
	}
}
