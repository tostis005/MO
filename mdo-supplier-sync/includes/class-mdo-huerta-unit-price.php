<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conserva la base de precio por kilo sin copiar el importe del proveedor.
 * La detección se hace sobre el payload original antes de que la política
 * editorial de La Huerta elimine precios y datos de contacto.
 *
 * Importante: esta clase nunca realiza peticiones HTTP. Solo lee el payload que
 * el sincronizador ya ha guardado en la base de datos.
 */
final class MDO_Huerta_Unit_Price {
	private const BASIS_META = '_emdo_huerta_price_basis';
	private const SOURCE_HOSTS = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );
	private static array $busy = array();

	public static function init(): void {
		/* Productos ya existentes: capturar el payload crudo antes de que el
		 * importador guarde/restaure la descripción protegida. */
		add_action( 'woocommerce_before_product_object_save', array( __CLASS__, 'capture_before_product_save' ), 5, 2 );

		/* Productos nuevos: el vínculo de origen se añade después del primer save.
		 * Prioridad 5 = antes de MDO_Huerta_Description_Policy (95), que sanea el payload. */
		add_action( 'added_post_meta', array( __CLASS__, 'capture_before_cleanup' ), 5, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'capture_before_cleanup' ), 5, 4 );

		/* Después de la limpieza: añadir únicamente la indicación comercial útil. */
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

		foreach ( array( 'description', 'title', 'price_text', 'unit_price', 'price_label' ) as $field ) {
			if ( isset( $payload[ $field ] ) && self::source_text_is_per_kg( (string) $payload[ $field ] ) ) {
				return 'kg';
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

	private static function with_label( string $content, string $label ): string {
		/* Eliminar versiones antiguas o duplicadas de ambas lenguas. */
		$content = (string) preg_replace( '~\s*<p\b[^>]*class=["\'][^"\']*\bemdo-source-unit-price\b[^"\']*["\'][^>]*>.*?</p>\s*~isu', "\n", $content );
		$content = trim( $content );
		$line = '<p class="emdo-source-unit-price"><strong>' . esc_html( $label ) . '</strong></p>';
		return '' === $content ? $line : $content . "\n" . $line;
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
