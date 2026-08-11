<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Text {
	private const PRESERVE_UPPERCASE = array( 'DOP', 'IGP', 'DO', 'ETG', 'IVA', 'SKU' );

	public static function normalize_title( string $title ): string {
		$title = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $title ) ) );
		if ( '' === $title || ! function_exists( 'mb_strtoupper' ) || ! function_exists( 'mb_strtolower' ) ) {
			return $title;
		}

		// Normalizamos por palabra, no por título completo. De este modo textos como
		// "JAMÓN RESERVA (Cortado a máquina)" pasan a "Jamón reserva (Cortado a máquina)"
		// aunque dentro del mismo título existan fragmentos correctamente escritos.
		$title = preg_replace_callback(
			'/\p{L}[\p{L}\p{M}]*/u',
			static function ( array $match ): string {
				$word = (string) $match[0];
				$upper = mb_strtoupper( $word, 'UTF-8' );
				$lower = mb_strtolower( $word, 'UTF-8' );

				if ( $upper === $lower || $word !== $upper ) {
					return $word;
				}

				if ( in_array( $upper, self::PRESERVE_UPPERCASE, true ) ) {
					return $upper;
				}

				return $lower;
			},
			$title
		);

		// El primer término del título debe mantener formato de frase incluso cuando
		// empieza por números o símbolos, por ejemplo "100% IBÉRICO".
		$title = preg_replace_callback(
			'/\p{Ll}/u',
			static fn( array $match ): string => mb_strtoupper( (string) $match[0], 'UTF-8' ),
			$title,
			1
		);

		// Si un proveedor pone un descriptor completo en mayúsculas entre paréntesis,
		// lo dejamos también en formato natural: "(DESHUESADO)" -> "(Deshuesado)".
		$title = preg_replace_callback(
			'/\(\s*\K\p{Ll}/u',
			static fn( array $match ): string => mb_strtoupper( (string) $match[0], 'UTF-8' ),
			$title
		);

		return $title;
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
