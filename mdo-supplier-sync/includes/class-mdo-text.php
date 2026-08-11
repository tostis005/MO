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

		// Normalizamos palabra a palabra y corregimos también capitalizaciones parciales
		// heredadas de versiones anteriores, por ejemplo "JamÓn" o "PAnceta".
		$title = preg_replace_callback(
			'/\p{L}[\p{L}\p{M}]*/u',
			static function ( array $match ): string {
				$word  = (string) $match[0];
				$upper = mb_strtoupper( $word, 'UTF-8' );
				$lower = mb_strtolower( $word, 'UTF-8' );

				if ( $upper === $lower ) {
					return $word;
				}

				if ( in_array( $upper, self::PRESERVE_UPPERCASE, true ) && $word === $upper ) {
					return $upper;
				}

				// Una palabra ya natural ("Cortado") o completamente minúscula se conserva.
				$first        = mb_substr( $word, 0, 1, 'UTF-8' );
				$rest         = mb_substr( $word, 1, null, 'UTF-8' );
				$natural_word = mb_strtoupper( $first, 'UTF-8' ) . mb_strtolower( $rest, 'UTF-8' );
				if ( $word === $lower || $word === $natural_word ) {
					return $word;
				}

				// Todo lo demás es mayúscula total o una mezcla anómala: JAMÓN, JamÓn, PAnceta…
				return $lower;
			},
			$title
		);

		// Formato de frase: primera letra real del título en mayúscula, también con Unicode.
		$title = preg_replace_callback(
			'/\p{Ll}/u',
			static fn( array $match ): string => mb_strtoupper( (string) $match[0], 'UTF-8' ),
			$title,
			1
		);

		// Los descriptores entre paréntesis se presentan también con inicio natural.
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
