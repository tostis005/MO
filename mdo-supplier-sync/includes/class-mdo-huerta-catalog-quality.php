<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Correcciones de calidad específicas del catálogo de La Huerta de Ana Mary.
 *
 * La tienda de origen mezcla ISO-8859-1/Windows-1252 con HTML antiguo y, en
 * algunas fichas, expone el precio/unidad fuera de la descripción principal.
 * Esta capa mantiene saneada la ficha Woo después de cada sincronización sin
 * modificar el contenido de otros proveedores.
 */
final class MDO_Huerta_Catalog_Quality {
	private const SOURCE_HOSTS = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );
	private const UNIT_PRICE_CLASS = 'emdo-source-unit-price';
	private static array $busy = array();

	public static function init(): void {
		add_action( 'save_post_product', array( __CLASS__, 'on_product_save' ), 65, 3 );
		add_action( 'added_post_meta', array( __CLASS__, 'on_post_meta' ), 65, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'on_post_meta' ), 65, 4 );
	}

	public static function on_product_save( int $post_id, WP_Post $post, bool $update ): void {
		if ( wp_is_post_revision( $post_id ) || 'product' !== $post->post_type ) {
			return;
		}
		self::repair_product( $post_id );
	}

	public static function on_post_meta( int $meta_id, int $object_id, string $meta_key, mixed $meta_value ): void {
		if ( ! in_array( $meta_key, array( '_emdo_source_product_id', '_emdo_supplier_id', '_emdo_source_url' ), true ) ) {
			return;
		}
		if ( 'product' === get_post_type( $object_id ) ) {
			self::repair_product( $object_id );
		}
	}

	/**
	 * @return array{changed:bool,title_changed:bool,description_changed:bool,unit_price:string,error:string}
	 */
	public static function repair_product( int $product_id ): array {
		$result = array(
			'changed'             => false,
			'title_changed'       => false,
			'description_changed' => false,
			'unit_price'          => '',
			'error'               => '',
		);

		if ( isset( self::$busy[ $product_id ] ) || ! function_exists( 'wc_get_product' ) ) {
			return $result;
		}

		$source_url = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
		if ( ! self::is_huerta_url( $source_url ) ) {
			return $result;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			$result['error'] = 'Producto WooCommerce no disponible.';
			return $result;
		}

		self::$busy[ $product_id ] = true;
		try {
			$current_title = (string) $product->get_name();
			$fixed_title   = self::repair_title( $current_title, $source_url );
			if ( $fixed_title !== $current_title ) {
				$product->set_name( $fixed_title );
				$result['title_changed'] = true;
			}

			$current_description = (string) $product->get_description();
			$fixed_description   = self::repair_text( $current_description );
			$fixed_description   = self::remove_unit_price_line( $fixed_description );

			if ( self::is_pepino( $source_url ) && self::description_is_polluted( $fixed_description ) ) {
				$fixed_description = self::pepino_description();
			}

			$source_price = self::source_price( $product_id );
			$unit_info    = null !== $source_price ? self::source_unit_price( $source_url, $source_price ) : null;
			if ( is_array( $unit_info ) && ! empty( $unit_info['label'] ) ) {
				$line = '<p class="' . esc_attr( self::UNIT_PRICE_CLASS ) . '"><strong>' . esc_html( (string) $unit_info['label'] ) . '</strong></p>';
				$fixed_description = rtrim( $fixed_description ) . "\n" . $line;
				$result['unit_price'] = (string) $unit_info['label'];
			}

			if ( trim( $fixed_description ) !== trim( $current_description ) ) {
				$product->set_description( wp_kses_post( $fixed_description ) );
				$result['description_changed'] = true;
			}

			if ( $result['title_changed'] || $result['description_changed'] ) {
				$product->save();
				$result['changed'] = true;
			}
		} catch ( Throwable $error ) {
			$result['error'] = $error->getMessage();
		} finally {
			unset( self::$busy[ $product_id ] );
		}

		return $result;
	}

	private static function repair_title( string $title, string $source_url ): string {
		$title = self::repair_text( $title );
		if ( str_contains( $source_url, '/alubia-blanca-de-ri-n-200.html' ) || preg_match( '/\bri\?+n\b/iu', $title ) ) {
			return 'Alubia blanca de riñón';
		}

		$replacements = array(
			'/\bCalabacin\b/iu' => 'Calabacín',
			'/\bBrocoli\b/iu'   => 'Brócoli',
			'/\bpadron\b/iu'    => 'Padrón',
			'/\brecibelas\b/iu' => 'recíbelas',
			'/\bpicate\b/iu'    => 'picante',
		);
		foreach ( $replacements as $pattern => $replacement ) {
			$title = (string) preg_replace( $pattern, $replacement, $title );
		}
		return trim( preg_replace( '/\s+/u', ' ', $title ) );
	}

	private static function repair_text( string $text ): string {
		for ( $i = 0; $i < 3; $i++ ) {
			$decoded = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( $decoded === $text ) {
				break;
			}
			$text = $decoded;
		}
		return strtr(
			$text,
			array(
				'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú', 'Ã±' => 'ñ',
				'Ã' => 'Á', 'Ã‰' => 'É', 'Ã' => 'Í', 'Ã“' => 'Ó', 'Ãš' => 'Ú', 'Ã‘' => 'Ñ',
				'Â¿' => '¿', 'Â¡' => '¡', 'Âº' => 'º', 'Âª' => 'ª', 'Â€' => '€', "\xC2\xA0" => ' ',
			)
		);
	}

	private static function remove_unit_price_line( string $description ): string {
		$pattern = '~\s*<p\b[^>]*class=["\'][^"\']*\b' . preg_quote( self::UNIT_PRICE_CLASS, '~' ) . '\b[^"\']*["\'][^>]*>.*?</p>\s*~isu';
		return trim( (string) preg_replace( $pattern, "\n", $description ) );
	}

	private static function source_price( int $product_id ): ?float {
		$source_id = absint( get_post_meta( $product_id, '_emdo_source_product_id', true ) );
		if ( ! $source_id || ! class_exists( 'MDO_Database' ) ) {
			return null;
		}
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT source_price FROM {$table} WHERE id = %d", $source_id ) );
		if ( null === $value || ! is_numeric( $value ) ) {
			return null;
		}
		$price = (float) $value;
		return $price > 0 ? $price : null;
	}

	/** @return array{unit:string,label:string,display:string}|null */
	private static function source_unit_price( string $source_url, float $source_price ): ?array {
		$html = self::fetch_source_html( $source_url );
		if ( '' === $html ) {
			return null;
		}

		$text = self::html_text( $html );
		if ( '' === $text ) {
			return null;
		}

		$matches = array();
		$unit_pattern = '(kg|kgs?|kilo(?:gramos?)?|ud\.?|uds\.?|unidad(?:es)?|pieza(?:s)?|caja(?:s)?|cesta(?:s)?)';
		if ( preg_match_all( '~€\s*/\s*' . $unit_pattern . '\s*\.?\s*(\d{1,4}(?:[.,]\d{1,2})?)~iu', $text, $forward, PREG_SET_ORDER ) ) {
			foreach ( $forward as $match ) {
				$matches[] = array( 'unit' => (string) $match[1], 'price' => (string) $match[2] );
			}
		}
		if ( preg_match_all( '~(\d{1,4}(?:[.,]\d{1,2})?)\s*€\s*/\s*' . $unit_pattern . '~iu', $text, $reverse, PREG_SET_ORDER ) ) {
			foreach ( $reverse as $match ) {
				$matches[] = array( 'unit' => (string) $match[2], 'price' => (string) $match[1] );
			}
		}

		foreach ( $matches as $candidate ) {
			$price = (float) str_replace( ',', '.', (string) $candidate['price'] );
			if ( abs( $price - $source_price ) > 0.015 ) {
				continue;
			}
			$normalized = self::normalize_unit( (string) $candidate['unit'] );
			if ( null === $normalized ) {
				continue;
			}
			return array(
				'unit'    => $normalized['unit'],
				'label'   => $normalized['label'],
				'display' => number_format( $price, 2, ',', '.' ) . ' ' . $normalized['suffix'],
			);
		}
		return null;
	}

	/** @return array{unit:string,label:string,suffix:string}|null */
	private static function normalize_unit( string $unit ): ?array {
		$unit = strtolower( remove_accents( trim( $unit, " .\t\n\r\0\x0B" ) ) );
		if ( in_array( $unit, array( 'kg', 'kgs', 'kilo', 'kilos', 'kilogramo', 'kilogramos' ), true ) ) {
			return array( 'unit' => 'kg', 'label' => 'Precio por kilo', 'suffix' => '€/kg' );
		}
		if ( preg_match( '/^(?:ud|uds|unidad|unidades|pieza|piezas)$/', $unit ) ) {
			return array( 'unit' => 'ud', 'label' => 'Precio por unidad', 'suffix' => '€/ud' );
		}
		if ( preg_match( '/^cajas?$/', $unit ) ) {
			return array( 'unit' => 'caja', 'label' => 'Precio por caja', 'suffix' => '€/caja' );
		}
		if ( preg_match( '/^cestas?$/', $unit ) ) {
			return array( 'unit' => 'cesta', 'label' => 'Precio por cesta', 'suffix' => '€/cesta' );
		}
		return null;
	}

	private static function fetch_source_html( string $url ): string {
		$key    = 'mdo_huerta_unit_' . md5( $url );
		$cached = get_transient( $key );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 20,
				'redirection' => 5,
				'user-agent'  => 'Mozilla/5.0 (compatible; EMDO/' . MDO_SUPPLIER_SYNC_VERSION . '; +https://www.elmercadodeorigen.com/)',
				'headers'     => array( 'Accept-Language' => 'es-ES,es;q=0.9' ),
			)
		);
		if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) >= 400 ) {
			return '';
		}
		$html = (string) wp_remote_retrieve_body( $response );
		if ( '' === trim( $html ) ) {
			return '';
		}

		$head = substr( $html, 0, 8192 );
		if ( preg_match( '/charset\s*=\s*["\']?\s*(iso-8859-1|latin1|windows-1252)/i', $head ) || ( function_exists( 'mb_check_encoding' ) && ! mb_check_encoding( $html, 'UTF-8' ) ) ) {
			$converted = @mb_convert_encoding( $html, 'UTF-8', 'Windows-1252' );
			if ( is_string( $converted ) && '' !== $converted ) {
				$html = $converted;
			}
		}
		set_transient( $key, $html, 12 * HOUR_IN_SECONDS );
		return $html;
	}

	private static function html_text( string $html ): string {
		$dom      = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		$text = $body ? (string) $body->textContent : wp_strip_all_tags( $html );
		$text = self::repair_text( $text );
		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	private static function description_is_polluted( string $description ): bool {
		$plain = strtolower( wp_strip_all_tags( $description ) );
		foreach ( array( 'grecaptcha.ready', 'document.ready', 'thickbox', '@media all', 'g_recaptcha' ) as $needle ) {
			if ( str_contains( $plain, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	private static function pepino_description(): string {
		return '<p>Pepino fresco de temporada de La Huerta de Ana Mary.</p><p><strong>Conservación:</strong> puede mantenerse en el frigorífico más de una semana. No es conveniente congelarlo, ya que su carne se ablanda considerablemente.</p>';
	}

	private static function is_pepino( string $source_url ): bool {
		return str_contains( strtolower( $source_url ), '/pepino-34.html' );
	}

	private static function is_huerta_url( string $url ): bool {
		if ( '' === $url ) {
			return false;
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return in_array( $host, self::SOURCE_HOSTS, true );
	}
}
