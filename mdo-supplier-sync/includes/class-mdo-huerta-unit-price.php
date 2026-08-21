<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conserva la base de precio por kilo sin copiar el importe del proveedor.
 *
 * La auditoría inicial contrasta cada producto de La Huerta con su URL de origen.
 * Después, la base detectada queda guardada como metadato y la etiqueta se
 * vuelve a aplicar tras cada sincronización, incluso aunque la política editorial
 * sanee/restaure la descripción canónica durante la importación.
 */
final class MDO_Huerta_Unit_Price {
	private const BASIS_META = '_emdo_huerta_price_basis';
	private const SOURCE_HOSTS = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );
	private static array $busy = array();

	public static function init(): void {
		/* Captura cualquier señal conservada en el payload antes de guardar. */
		add_action( 'woocommerce_before_product_object_save', array( __CLASS__, 'capture_before_product_save' ), 5, 2 );

		/* En productos nuevos el vínculo de origen se crea tras el primer save. */
		add_action( 'added_post_meta', array( __CLASS__, 'capture_before_cleanup' ), 5, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'capture_before_cleanup' ), 5, 4 );

		/* La política de descripciones actúa a prioridad 95; nosotros reponemos la etiqueta después. */
		add_action( 'save_post_product', array( __CLASS__, 'apply_after_product_save' ), 160, 3 );
		add_action( 'added_post_meta', array( __CLASS__, 'apply_after_source_link' ), 160, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'apply_after_source_link' ), 160, 4 );
	}

	public static function capture_before_product_save( $product, $data_store = null ): void {
		unset( $data_store );
		if ( ! $product instanceof WC_Product || ! $product->get_id() ) {
			return;
		}
		$product_id = (int) $product->get_id();
		if ( ! self::is_huerta_product( $product_id ) ) {
			return;
		}
		if ( 'kg' === self::detect_from_source_payload( $product_id ) ) {
			update_post_meta( $product_id, self::BASIS_META, 'kg' );
		}
	}

	public static function capture_before_cleanup( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( '_emdo_source_url' !== $meta_key || 'product' !== get_post_type( $object_id ) || ! self::is_huerta_product( $object_id ) ) {
			return;
		}

		if ( 'kg' === self::detect_from_source_payload( $object_id ) ) {
			update_post_meta( $object_id, self::BASIS_META, 'kg' );
			return;
		}

		/* Fallback solo para productos todavía sin base registrada. Evita una petición
		 * HTTP adicional en cada sincronización de los productos ya auditados. */
		if ( ! metadata_exists( 'post', $object_id, self::BASIS_META ) && self::source_url_is_per_kg( (string) get_post_meta( $object_id, '_emdo_source_url', true ) ) ) {
			update_post_meta( $object_id, self::BASIS_META, 'kg' );
		}
	}

	public static function apply_after_product_save( int $post_id, WP_Post $post, bool $update ): void {
		unset( $update );
		if ( wp_is_post_revision( $post_id ) || 'product' !== $post->post_type || ! self::is_huerta_product( $post_id ) ) {
			return;
		}
		self::apply_labels( $post_id );
	}

	public static function apply_after_source_link( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( '_emdo_source_url' !== $meta_key || 'product' !== get_post_type( $object_id ) || ! self::is_huerta_product( $object_id ) ) {
			return;
		}
		self::apply_labels( $object_id );
	}

	/**
	 * Audita todos los productos Huerta existentes contra su ficha original.
	 * Se ejecuta expresamente desde el despliegue, no en cada carga de WordPress.
	 *
	 * @return array{scanned:int,per_kg:int,not_per_kg:int,changed:int,errors:int,error_items:array}
	 */
	public static function audit_all_products(): array {
		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_type = 'product'
				AND pm.meta_key = '_emdo_source_url'
				AND pm.meta_value LIKE %s
				ORDER BY p.ID ASC",
				'%lahuertadeanamary.com%'
			)
		) ?: array();

		$stats = array(
			'scanned'     => 0,
			'per_kg'      => 0,
			'not_per_kg'  => 0,
			'changed'     => 0,
			'errors'      => 0,
			'error_items' => array(),
		);

		foreach ( $ids as $raw_id ) {
			$product_id = absint( $raw_id );
			if ( ! $product_id || ! self::is_huerta_product( $product_id ) ) {
				continue;
			}
			$stats['scanned']++;
			$url = (string) get_post_meta( $product_id, '_emdo_source_url', true );
			try {
				$is_per_kg = self::source_url_is_per_kg( $url, true );
				$before = (string) get_post_meta( $product_id, self::BASIS_META, true );
				if ( $is_per_kg ) {
					$stats['per_kg']++;
					update_post_meta( $product_id, self::BASIS_META, 'kg' );
					self::apply_labels( $product_id );
					if ( 'kg' !== $before ) {
						$stats['changed']++;
					}
				} else {
					$stats['not_per_kg']++;
					delete_post_meta( $product_id, self::BASIS_META );
					if ( self::remove_labels( $product_id ) || '' !== $before ) {
						$stats['changed']++;
					}
				}
			} catch ( Throwable $error ) {
				$stats['errors']++;
				$stats['error_items'][] = array(
					'product_id' => $product_id,
					'url'        => esc_url_raw( $url ),
					'error'      => sanitize_text_field( $error->getMessage() ),
				);
			}
		}

		update_option( 'mdo_huerta_unit_price_audit_stats', $stats, false );
		update_option( 'mdo_huerta_unit_price_audit_at', current_time( 'mysql', true ), false );
		return $stats;
	}

	public static function source_text_is_per_kg( string $value ): bool {
		$plain = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$plain = preg_replace( '/\s+/u', ' ', $plain );
		if ( ! is_string( $plain ) || '' === trim( $plain ) ) {
			return false;
		}

		/* Solo señales explícitas de precio ligado a kg/kilo. Un peso como
		 * "20 kg" o "caja de 7 kg" nunca activa esta regla. */
		$patterns = array(
			'/€\s*(?:\/|por)\s*(?:kg|kilo(?:gramo)?s?)\b/iu',
			'/\b(?:eur|euros?)\s*(?:\/|por)\s*(?:kg|kilo(?:gramo)?s?)\b/iu',
			'/\bprecio\s+(?:por|\/)?\s*(?:kg|kilo(?:gramo)?s?)\b/iu',
			'/\b\d{1,4}(?:[.,]\d{1,2})?\s*€\s*(?:\/|por)\s*(?:kg|kilo(?:gramo)?s?)\b/iu',
			'/\b\d{1,4}(?:[.,]\d{1,2})?\s*(?:€|eur|euros?)\s*(?:el|por)\s+kilo\b/iu',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $plain ) ) {
				return true;
			}
		}
		return false;
	}

	private static function source_url_is_per_kg( string $url, bool $throw = false ): bool {
		$url = trim( $url );
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( '' === $url || ! in_array( $host, self::SOURCE_HOSTS, true ) ) {
			return false;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 25,
				'redirection' => 5,
				'user-agent'  => 'Mozilla/5.0 (compatible; EMDO/' . ( defined( 'MDO_SUPPLIER_SYNC_VERSION' ) ? MDO_SUPPLIER_SYNC_VERSION : '1.0' ) . '; +https://www.elmercadodeorigen.com/)',
				'headers'     => array( 'Accept-Language' => 'es-ES,es;q=0.9' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			if ( $throw ) {
				throw new RuntimeException( $response->get_error_message() );
			}
			return false;
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 400 ) {
			if ( $throw ) {
				throw new RuntimeException( 'HTTP ' . $status . ' al consultar la ficha original.' );
			}
			return false;
		}
		$body = (string) wp_remote_retrieve_body( $response );
		if ( '' === trim( $body ) ) {
			if ( $throw ) {
				throw new RuntimeException( 'La ficha original devolvió HTML vacío.' );
			}
			return false;
		}
		return self::source_text_is_per_kg( $body );
	}

	private static function detect_from_source_payload( int $product_id ): string {
		$source_id = absint( get_post_meta( $product_id, '_emdo_source_product_id', true ) );
		if ( ! $source_id || ! class_exists( 'MDO_Database' ) ) {
			return '';
		}

		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$raw = $wpdb->get_var( $wpdb->prepare( "SELECT source_payload FROM {$table} WHERE id = %d LIMIT 1", $source_id ) );
		$payload = json_decode( (string) $raw, true );
		if ( ! is_array( $payload ) ) {
			return '';
		}

		if ( 'kg' === strtolower( trim( (string) ( $payload['price_basis'] ?? '' ) ) ) ) {
			return 'kg';
		}
		foreach ( array( 'description', 'title', 'price_text', 'unit_price', 'price_label' ) as $field ) {
			if ( isset( $payload[ $field ] ) && self::source_text_is_per_kg( (string) $payload[ $field ] ) ) {
				return 'kg';
			}
		}
		return '';
	}

	private static function apply_labels( int $product_id ): void {
		if ( isset( self::$busy[ $product_id ] ) || 'kg' !== (string) get_post_meta( $product_id, self::BASIS_META, true ) ) {
			return;
		}

		self::$busy[ $product_id ] = true;
		try {
			global $wpdb;
			$current = (string) $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d LIMIT 1", $product_id ) );
			$spanish = self::with_label( $current, 'Precio por kilo' );
			if ( $spanish !== $current ) {
				$wpdb->update( $wpdb->posts, array( 'post_content' => $spanish ), array( 'ID' => $product_id ), array( '%s' ), array( '%d' ) );
				clean_post_cache( $product_id );
			}

			/* La versión protegida debe incluir la línea para que futuras importaciones
			 * no la hagan desaparecer al restaurar el contenido canónico. */
			if ( metadata_exists( 'post', $product_id, '_emdo_huerta_description_canonical' ) ) {
				update_post_meta( $product_id, '_emdo_huerta_description_canonical', $spanish );
			}

			$english = (string) get_post_meta( $product_id, '_en_US_post_content', true );
			if ( '' !== trim( wp_strip_all_tags( $english ) ) ) {
				update_post_meta( $product_id, '_en_US_post_content', self::with_label( $english, 'Price per kg' ) );
			}
		} finally {
			unset( self::$busy[ $product_id ] );
		}
	}

	private static function remove_labels( int $product_id ): bool {
		if ( isset( self::$busy[ $product_id ] ) ) {
			return false;
		}
		self::$busy[ $product_id ] = true;
		$changed = false;
		try {
			global $wpdb;
			$current = (string) $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d LIMIT 1", $product_id ) );
			$clean = self::without_label( $current );
			if ( $clean !== $current ) {
				$wpdb->update( $wpdb->posts, array( 'post_content' => $clean ), array( 'ID' => $product_id ), array( '%s' ), array( '%d' ) );
				clean_post_cache( $product_id );
				$changed = true;
			}
			if ( metadata_exists( 'post', $product_id, '_emdo_huerta_description_canonical' ) ) {
				$canonical = (string) get_post_meta( $product_id, '_emdo_huerta_description_canonical', true );
				$canonical_clean = self::without_label( $canonical );
				if ( $canonical_clean !== $canonical ) {
					update_post_meta( $product_id, '_emdo_huerta_description_canonical', $canonical_clean );
					$changed = true;
				}
			}
			$english = (string) get_post_meta( $product_id, '_en_US_post_content', true );
			$english_clean = self::without_label( $english );
			if ( $english_clean !== $english ) {
				update_post_meta( $product_id, '_en_US_post_content', $english_clean );
				$changed = true;
			}
		} finally {
			unset( self::$busy[ $product_id ] );
		}
		return $changed;
	}

	private static function with_label( string $content, string $label ): string {
		$content = self::without_label( $content );
		$line = '<p class="emdo-source-unit-price"><strong>' . esc_html( $label ) . '</strong></p>';
		return '' === $content ? $line : $content . "\n" . $line;
	}

	private static function without_label( string $content ): string {
		$content = (string) preg_replace( '~\s*<p\b[^>]*class=["\'][^"\']*\bemdo-source-unit-price\b[^"\']*["\'][^>]*>.*?</p>\s*~isu', "\n", $content );
		return trim( $content );
	}

	private static function is_huerta_product( int $product_id ): bool {
		$url = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
		if ( '' === $url ) {
			return false;
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return in_array( $host, self::SOURCE_HOSTS, true );
	}
}
