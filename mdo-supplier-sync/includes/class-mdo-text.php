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

		/*
		 * Usamos una estrategia determinista de formato frase:
		 * 1. Protegemos solo siglas conocidas que ya vienen realmente en mayúsculas.
		 * 2. Pasamos TODO el resto del título a minúsculas con Unicode.
		 * 3. Recuperamos las siglas y ponemos en mayúscula únicamente el inicio del
		 *    título y el inicio de un descriptor entre paréntesis.
		 *
		 * Esto elimina también errores heredados como JamÓn, JamóN, PAnceta, etc.
		 */
		$protected = array();
		$title = preg_replace_callback(
			'/(?<![\p{L}\p{N}])(?:' . implode( '|', array_map( 'preg_quote', self::PRESERVE_UPPERCASE ) ) . ')(?![\p{L}\p{N}])/u',
			static function ( array $match ) use ( &$protected ): string {
				$marker = '@@' . count( $protected ) . '@@';
				$protected[ $marker ] = (string) $match[0];
				return $marker;
			},
			$title
		);

		$title = mb_strtolower( (string) $title, 'UTF-8' );

		foreach ( $protected as $marker => $value ) {
			$title = str_replace( $marker, $value, $title );
		}

		// Primera letra real del título en mayúscula, independientemente de tildes.
		$title = preg_replace_callback(
			'/\p{L}/u',
			static fn( array $match ): string => mb_strtoupper( (string) $match[0], 'UTF-8' ),
			$title,
			1
		);

		// Descriptor entre paréntesis: "(deshuesado)" -> "(Deshuesado)".
		$title = preg_replace_callback(
			'/(\(\s*)(\p{L})/u',
			static function ( array $match ): string {
				return (string) $match[1] . mb_strtoupper( (string) $match[2], 'UTF-8' );
			},
			$title
		);

		return $title;
	}

	/**
	 * Decodifica entidades HTML simples o dobles sin eliminar el HTML válido.
	 * Ejemplos: &eacute; -> é, &amp;eacute; -> é, &nbsp; -> espacio normal.
	 */
	public static function normalize_description( string $description ): string {
		$value = $description;

		// Algunos proveedores entregan entidades HTML doblemente codificadas.
		// Repetimos unas pocas veces y paramos en cuanto el contenido sea estable.
		for ( $i = 0; $i < 4; $i++ ) {
			$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( $decoded === $value ) {
				break;
			}
			$value = $decoded;
		}

		// No queremos que &nbsp; termine visible ni que introduzca espacios no separables
		// difíciles de editar en WooCommerce.
		$value = str_replace( array( "\xC2\xA0", "\u{00A0}" ), ' ', $value );

		return wp_kses_post( $value );
	}

	public static function normalize_product( array $product ): array {
		if ( isset( $product['title'] ) ) {
			$product['title'] = self::normalize_title( (string) $product['title'] );
		}
		if ( isset( $product['description'] ) ) {
			$product['description'] = self::normalize_description( (string) $product['description'] );
		}

		if ( class_exists( 'MDO_Pricing' ) ) {
			$product = MDO_Pricing::enrich_product( $product );
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
