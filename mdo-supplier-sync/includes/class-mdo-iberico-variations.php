<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reconstruye las variaciones de peso que El Catedrático publica dentro de una
 * misma ficha de producto. Los formatos (pieza, deshuesado, corte, etc.) se
 * mantienen como fichas distintas porque la tienda origen los enlaza mediante
 * URLs de producto independientes.
 */
final class MDO_Iberico_Variations {
	private const IMPORT_HOOK = 'mdo_supplier_sync_import_product';
	private const MAX_VARIATIONS = 80;

	public static function init(): void {
		// Los productos analizados antes de esta versión también deben poder
		// enriquecerse justo antes de importarlos.
		add_action( self::IMPORT_HOOK, array( __CLASS__, 'prepare_source_before_import' ), 5, 1 );
	}

	public static function enrich_product( array $product ): array {
		if ( 'el-catedratico' !== (string) ( $product['connector'] ?? '' ) ) {
			return $product;
		}
		if ( ! self::looks_like_weight_product( (string) ( $product['title'] ?? '' ) ) ) {
			return $product;
		}
		if ( self::has_usable_variations( $product ) ) {
			return $product;
		}

		$url = esc_url_raw( (string) ( $product['source_url'] ?? '' ) );
		if ( ! $url ) {
			return $product;
		}

		try {
			$matrix = self::matrix_from_url( $url, $product );
		} catch ( Throwable $error ) {
			// Las variaciones son una mejora del producto. Si la ficha origen no se
			// puede validar, no inventamos datos y dejamos que la protección del
			// importador mantenga el producto pendiente cuando corresponda.
			return $product;
		}

		if ( count( $matrix['variations'] ) < 2 ) {
			return $product;
		}

		$product['option_groups'] = array(
			array(
				'key'     => 'peso',
				'label'   => 'Tamaño',
				'options' => $matrix['options'],
			),
		);
		$product['variations']      = $matrix['variations'];
		$product['variation_count'] = count( $matrix['variations'] );
		$product['variation_source'] = 'el-catedratico-weight-range';
		$product['unit_price']       = $matrix['unit_price'];

		$current_prices = array_column( $matrix['variations'], 'display_price' );
		$regular_prices = array_column( $matrix['variations'], 'display_regular_price' );
		if ( $current_prices ) {
			$product['price'] = min( array_map( 'floatval', $current_prices ) );
		}
		if ( $regular_prices ) {
			$product['regular_price'] = min( array_map( 'floatval', $regular_prices ) );
		}
		if ( isset( $product['regular_price'], $product['price'] ) && (float) $product['price'] < (float) $product['regular_price'] ) {
			$product['sale_price'] = (float) $product['price'];
			$product['discount_percent'] = (int) round(
				( ( (float) $product['regular_price'] - (float) $product['price'] ) / (float) $product['regular_price'] ) * 100
			);
		} else {
			$product['sale_price'] = null;
			$product['discount_percent'] = 0;
		}

		// Si ya había una ficha EMDO importada como simple, la dejamos preparada
		// para que la sincronización pueda convertirla en variable sin duplicarla.
		self::prepare_existing_product_type_by_url( $url );

		return $product;
	}

	public static function prepare_source_before_import( int $source_product_id ): void {
		if ( $source_product_id <= 0 ) {
			return;
		}

		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT id, source_url, source_payload, wc_product_id FROM {$table} WHERE id = %d", $source_product_id ),
			ARRAY_A
		);
		if ( ! $row ) {
			return;
		}

		$payload = json_decode( (string) $row['source_payload'], true );
		if ( ! is_array( $payload ) || 'el-catedratico' !== (string) ( $payload['connector'] ?? '' ) ) {
			return;
		}
		if ( empty( $payload['source_url'] ) ) {
			$payload['source_url'] = (string) $row['source_url'];
		}

		// Completa primero el precio normal/rebajado del producto base para poder
		// trasladar el mismo descuento proporcional a cada tramo de peso.
		if ( class_exists( 'MDO_Pricing' ) ) {
			$payload = MDO_Pricing::enrich_product( $payload );
		}
		$payload = self::enrich_product( $payload );
		if ( ! self::has_usable_variations( $payload ) ) {
			return;
		}

		$source_hash = self::source_hash( $payload );
		$wpdb->update(
			$table,
			array(
				'source_payload' => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
				'source_price'   => isset( $payload['price'] ) && is_numeric( $payload['price'] ) ? number_format( (float) $payload['price'], 2, '.', '' ) : null,
				'source_hash'    => $source_hash,
			),
			array( 'id' => $source_product_id )
		);

		if ( ! empty( $row['wc_product_id'] ) ) {
			self::ensure_variable_product_type( (int) $row['wc_product_id'] );
		}
	}

	private static function matrix_from_url( string $url, array $product ): array {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 20,
				'redirection' => 4,
				'user-agent'   => 'EMDO Variations/' . MDO_SUPPLIER_SYNC_VERSION . ' (+https://www.elmercadodeorigen.com/)',
				'headers'      => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 400 ) {
			throw new RuntimeException( 'HTTP ' . $status . ' al recuperar variaciones.' );
		}
		$html = (string) wp_remote_retrieve_body( $response );
		if ( '' === trim( $html ) ) {
			throw new RuntimeException( 'La ficha de variaciones está vacía.' );
		}

		$text    = self::page_text( $html );
		$weights = self::weight_ranges( $text );
		if ( count( $weights ) < 2 || count( $weights ) > self::MAX_VARIATIONS ) {
			return array( 'options' => array(), 'variations' => array(), 'unit_price' => null );
		}

		$unit_current = self::unit_price_from_text( $text );
		$base_current = self::number( $product['price'] ?? null );
		$base_regular = self::number( $product['regular_price'] ?? null );
		$first_weight = (float) $weights[0]['midpoint'];

		if ( null === $unit_current && null !== $base_current && $first_weight > 0 ) {
			$unit_current = $base_current / $first_weight;
		}
		if ( null === $unit_current || $unit_current <= 0 || $first_weight <= 0 ) {
			return array( 'options' => array(), 'variations' => array(), 'unit_price' => null );
		}

		// La primera opción visible debe justificar el precio base publicado. Esta
		// comprobación evita construir una matriz si el proveedor cambia su modelo.
		$expected_first = round( $unit_current * $first_weight, 2 );
		if ( null !== $base_current && abs( $expected_first - $base_current ) > 0.08 ) {
			return array( 'options' => array(), 'variations' => array(), 'unit_price' => null );
		}

		$unit_regular = $unit_current;
		if ( null !== $base_regular && null !== $base_current && $base_regular > $base_current && $first_weight > 0 ) {
			$unit_regular = $base_regular / $first_weight;
		}

		$instock    = 'outofstock' !== (string) ( $product['stock_status'] ?? '' );
		$options    = array();
		$variations = array();
		foreach ( $weights as $weight ) {
			$label   = (string) $weight['label'];
			$current = round( $unit_current * (float) $weight['midpoint'], 2 );
			$regular = round( $unit_regular * (float) $weight['midpoint'], 2 );
			$options[] = array(
				'value'    => sanitize_title( $label ),
				'label'    => $label,
				'disabled' => ! $instock,
				'data'     => array(
					'weight-midpoint' => (float) $weight['midpoint'],
				),
			);
			$attributes = array( 'peso' => $label );
			$variations[] = array(
				'variation_id'          => substr( hash( 'sha256', $url . '|' . $label ), 0, 24 ),
				'attributes'            => $attributes,
				'display_price'         => $current,
				'display_regular_price' => max( $current, $regular ),
				'is_in_stock'           => $instock,
				'image'                 => '',
			);
		}

		return array(
			'options'     => $options,
			'variations'  => $variations,
			'unit_price'  => round( $unit_current, 4 ),
		);
	}

	private static function weight_ranges( string $text ): array {
		$matches = array();
		$weights = array();

		// Rango habitual: "Piezas entre 7,200 kg - 7,400 kg aprox.".
		preg_match_all(
			'/Piezas?\s+entre\s+([0-9]{1,2}(?:[.,][0-9]{1,3})?)\s*kg\s*[-–]\s*([0-9]{1,2}(?:[.,][0-9]{1,3})?)\s*kg\s*aprox\.?/iu',
			$text,
			$matches,
			PREG_SET_ORDER
		);
		foreach ( $matches as $match ) {
			$min = self::decimal( (string) $match[1] );
			$max = self::decimal( (string) $match[2] );
			if ( null === $min || null === $max || $min <= 0 || $max < $min ) {
				continue;
			}
			$label = trim( preg_replace( '/\s+/u', ' ', (string) $match[0] ) );
			$weights[ $label ] = array(
				'label'    => $label,
				'midpoint' => ( $min + $max ) / 2,
			);
		}

		// Algunas referencias pueden publicarse con un peso único aproximado.
		if ( ! $weights ) {
			preg_match_all(
				'/Piezas?\s+(?:de\s+)?([0-9]{1,2}(?:[.,][0-9]{1,3})?)\s*kg\s*aprox\.?/iu',
				$text,
				$matches,
				PREG_SET_ORDER
			);
			foreach ( $matches as $match ) {
				$value = self::decimal( (string) $match[1] );
				if ( null === $value || $value <= 0 ) {
					continue;
				}
				$label = trim( preg_replace( '/\s+/u', ' ', (string) $match[0] ) );
				$weights[ $label ] = array( 'label' => $label, 'midpoint' => $value );
			}
		}

		return array_values( $weights );
	}

	private static function unit_price_from_text( string $text ): ?float {
		if ( preg_match( '/([0-9]{1,4}(?:[.,][0-9]{1,2})?)\s*€\s*\/\s*Kg/iu', $text, $match ) ) {
			return self::decimal( (string) $match[1] );
		}
		return null;
	}

	private static function page_text( string $html ): string {
		$dom      = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		$text = html_entity_decode( (string) $dom->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( preg_replace( '/\s+/u', ' ', str_replace( "\xC2\xA0", ' ', $text ) ) );
	}

	private static function looks_like_weight_product( string $title ): bool {
		$title = html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( ! preg_match( '/\b(jam[oó]n|paleta)\b/iu', $title ) ) {
			return false;
		}
		return ! preg_match( '/\b(pack|tacos?|sobres?|virutas?|codillo)\b/iu', $title );
	}

	private static function has_usable_variations( array $product ): bool {
		$variations = isset( $product['variations'] ) && is_array( $product['variations'] ) ? $product['variations'] : array();
		foreach ( $variations as $variation ) {
			if ( is_array( $variation ) && ! empty( $variation['attributes'] ) && isset( $variation['display_price'] ) && is_numeric( $variation['display_price'] ) ) {
				return true;
			}
		}
		return false;
	}

	private static function prepare_existing_product_type_by_url( string $source_url ): void {
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$wc_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT wc_product_id FROM {$table} WHERE source_url = %s AND wc_product_id IS NOT NULL ORDER BY id DESC LIMIT 1",
				$source_url
			)
		);
		if ( $wc_id > 0 ) {
			self::ensure_variable_product_type( $wc_id );
		}
	}

	private static function ensure_variable_product_type( int $product_id ): void {
		if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$product = wc_get_product( $product_id );
		if ( ! $product || $product->is_type( 'variable' ) ) {
			return;
		}
		if ( ! get_post_meta( $product_id, '_emdo_source_product_id', true ) ) {
			return;
		}
		wp_set_object_terms( $product_id, 'variable', 'product_type', false );
		clean_post_cache( $product_id );
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( $product_id );
		}
	}

	private static function source_hash( array $payload ): string {
		$hash_payload = $payload;
		unset( $hash_payload['source_hash'] );
		if ( isset( $hash_payload['description'] ) ) {
			$hash_payload['description_hash'] = hash( 'sha256', wp_strip_all_tags( (string) $hash_payload['description'] ) );
			unset( $hash_payload['description'] );
		}
		return hash(
			'sha256',
			(string) wp_json_encode( $hash_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}

	private static function decimal( string $value ): ?float {
		$value = trim( str_replace( array( "\xC2\xA0", '€', ' ' ), '', $value ) );
		if ( str_contains( $value, ',' ) && str_contains( $value, '.' ) ) {
			if ( strrpos( $value, ',' ) > strrpos( $value, '.' ) ) {
				$value = str_replace( '.', '', $value );
				$value = str_replace( ',', '.', $value );
			} else {
				$value = str_replace( ',', '', $value );
			}
		} elseif ( str_contains( $value, ',' ) ) {
			$value = str_replace( ',', '.', $value );
		}
		return is_numeric( $value ) ? (float) $value : null;
	}

	private static function number( mixed $value ): ?float {
		if ( null === $value || '' === $value ) {
			return null;
		}
		if ( is_numeric( $value ) ) {
			return (float) $value;
		}
		return self::decimal( (string) $value );
	}
}
