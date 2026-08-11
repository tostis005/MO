<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Text {
	public static function normalize_title( string $title ): string {
		$title = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $title ) ) );
		if ( '' === $title || ! function_exists( 'mb_strtoupper' ) || ! function_exists( 'mb_strtolower' ) ) {
			return $title;
		}

		$letters = preg_replace( '/[^\p{L}]+/u', '', $title );
		if ( '' === $letters ) {
			return $title;
		}

		$chars      = preg_split( '//u', $letters, -1, PREG_SPLIT_NO_EMPTY ) ?: array();
		$uppercase  = 0;
		$lowercase  = 0;
		foreach ( $chars as $char ) {
			$upper = mb_strtoupper( $char, 'UTF-8' );
			$lower = mb_strtolower( $char, 'UTF-8' );
			if ( $upper === $lower ) {
				continue;
			}
			if ( $char === $upper ) {
				$uppercase++;
			} elseif ( $char === $lower ) {
				$lowercase++;
			}
		}

		$cased = $uppercase + $lowercase;
		if ( $cased < 4 || 0 === $uppercase ) {
			return $title;
		}

		// Algunos proveedores escriben títulos prácticamente enteros en mayúsculas,
		// pero dejan unidades como "Kg" o pequeñas partículas en minúscula. Los
		// tratamos igualmente como títulos en mayúsculas para mantener consistencia.
		if ( ( $uppercase / $cased ) < 0.75 ) {
			return $title;
		}

		$lower = mb_strtolower( $title, 'UTF-8' );
		return mb_strtoupper( mb_substr( $lower, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $lower, 1, null, 'UTF-8' );
	}

	public static function normalize_product( array $product ): array {
		if ( isset( $product['title'] ) ) {
			$product['title'] = self::normalize_title( (string) $product['title'] );
		}

		$hash_payload = $product;
		unset( $hash_payload['source_hash'] );
		if ( isset( $hash_payload['description'] ) ) {
			$hash_payload['description_hash'] = hash( 'sha256', wp_strip_all_tags( (string) $hash_payload['description'] ) );
			unset( $hash_payload['description'] );
		}
		$product['source_hash'] = hash(
			'sha256',
			(string) wp_json_encode( $hash_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
		return $product;
	}
}
