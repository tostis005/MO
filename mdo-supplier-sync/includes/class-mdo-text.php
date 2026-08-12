<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Text {
	private const PRESERVE_UPPERCASE = array( 'DOP', 'IGP', 'DO', 'ETG', 'IVA', 'SKU' );

	public static function normalize_title( string $title ): string {
		/*
		 * Los orígenes pueden entregar títulos con entidades (&oacute;, &iacute;,
		 * &amp;oacute;...). Si WooCommerce genera el slug antes de decodificarlas,
		 * WordPress elimina la entidad completa y aparecen URLs como "jamn" o
		 * "ibrico". Decodificamos primero (también el caso doble) para que después
		 * remove_accents()/sanitize_title() puedan transliterar jamón -> jamon.
		 */
		for ( $i = 0; $i < 4; $i++ ) {
			$decoded = html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( $decoded === $title ) {
				break;
			}
			$title = $decoded;
		}

		$title = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $title ) ) );
		if ( '' === $title || ! function_exists( 'mb_strtoupper' ) || ! function_exists( 'mb_strtolower' ) ) {
			return $title;
		}

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

		$title = preg_replace_callback(
			'/\p{L}/u',
			static fn( array $match ): string => mb_strtoupper( (string) $match[0], 'UTF-8' ),
			$title,
			1
		);

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
		for ( $i = 0; $i < 4; $i++ ) {
			$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( $decoded === $value ) {
				break;
			}
			$value = $decoded;
		}

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
		if ( class_exists( 'MDO_Iberico_Variations' ) ) {
			$product = MDO_Iberico_Variations::enrich_product( $product );
		}
		if ( class_exists( 'MDO_Pricing' ) ) {
			$product = MDO_Pricing::enrich_product( $product );
		}

		$hash_payload = $product;
		unset( $hash_payload['source_hash'] );
		if ( isset( $hash_payload['description'] ) ) {
			$hash_payload['description_hash'] = hash( 'sha256', (string) $hash_payload['description'] );
			unset( $hash_payload['description'] );
		}
		$product['source_hash'] = hash(
			'sha256',
			(string) wp_json_encode( $hash_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
		return $product;
	}
}
