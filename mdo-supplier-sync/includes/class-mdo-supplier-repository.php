<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Supplier_Repository {
	public static function all(): array {
		global $wpdb;
		$table = MDO_Database::table( 'suppliers' );
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY active DESC, name ASC", ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function active(): array {
		global $wpdb;
		$table = MDO_Database::table( 'suppliers' );
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE active = 1 ORDER BY name ASC", ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$table = MDO_Database::table( 'suppliers' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function save( array $data, int $id = 0 ): int {
		global $wpdb;
		$table = MDO_Database::table( 'suppliers' );
		$now   = current_time( 'mysql' );

		$record = array(
			'code'                    => sanitize_key( (string) ( $data['code'] ?? '' ) ),
			'name'                    => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'source_url'              => self::normalize_source_urls( (string) ( $data['source_url'] ?? '' ) ),
			'vendor_user_id'          => ! empty( $data['vendor_user_id'] ) ? absint( $data['vendor_user_id'] ) : null,
			'connector'               => sanitize_key( (string) ( $data['connector'] ?? 'none' ) ),
			'commercial_rule'         => sanitize_key( (string) ( $data['commercial_rule'] ?? 'percentage' ) ),
			'commission_percent'      => self::nullable_decimal( $data['commission_percent'] ?? null ),
			'fixed_fee'               => self::nullable_decimal( $data['fixed_fee'] ?? null ),
			'fixed_fee_scope'         => in_array( ( $data['fixed_fee_scope'] ?? 'order' ), array( 'order', 'line' ), true ) ? $data['fixed_fee_scope'] : 'order',
			'minimum_order_amount'    => self::nullable_decimal( $data['minimum_order_amount'] ?? null ),
			'currency'                => strtoupper( substr( sanitize_text_field( (string) ( $data['currency'] ?? 'EUR' ) ), 0, 3 ) ),
			'sync_frequency'          => in_array( ( $data['sync_frequency'] ?? 'weekly' ), array( 'manual', 'daily', 'weekly' ), true ) ? $data['sync_frequency'] : 'weekly',
			'notification_email'      => sanitize_email( (string) ( $data['notification_email'] ?? '' ) ) ?: null,
			'exclusion_url_fragments' => self::normalize_fragments( (string) ( $data['exclusion_url_fragments'] ?? '' ) ),
			'notes'                   => sanitize_textarea_field( (string) ( $data['notes'] ?? '' ) ),
			'active'                  => ! empty( $data['active'] ) ? 1 : 0,
			'updated_at'              => $now,
		);

		if ( $id > 0 ) {
			$wpdb->update( $table, $record, array( 'id' => $id ) );
			return $id;
		}

		$record['created_at'] = $now;
		$wpdb->insert( $table, $record );
		return (int) $wpdb->insert_id;
	}

	public static function fragments_as_text( ?string $json ): string {
		$items = json_decode( (string) $json, true );
		if ( ! is_array( $items ) ) {
			return '';
		}
		return implode( "\n", array_map( 'strval', $items ) );
	}

	public static function source_urls( ?string $raw ): array {
		$lines = preg_split( '/\R+/', (string) $raw ) ?: array();
		$urls  = array();
		foreach ( $lines as $line ) {
			$url = esc_url_raw( trim( (string) $line ) );
			if ( $url ) {
				$urls[ $url ] = $url;
			}
		}
		return array_values( $urls );
	}

	private static function normalize_source_urls( string $raw ): string {
		return implode( "\n", self::source_urls( $raw ) );
	}

	private static function normalize_fragments( string $raw ): string {
		$lines = preg_split( '/\R+/', $raw ) ?: array();
		$lines = array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( string $line ): string => trim( sanitize_text_field( $line ) ),
						$lines
					)
				)
			)
		);
		return wp_json_encode( $lines, JSON_UNESCAPED_SLASHES );
	}

	private static function nullable_decimal( mixed $value ): ?string {
		if ( '' === $value || null === $value ) {
			return null;
		}
		$value = str_replace( ',', '.', (string) $value );
		return is_numeric( $value ) ? number_format( max( 0, (float) $value ), 4, '.', '' ) : null;
	}
}
