<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Política editorial para las descripciones importadas de La Huerta de Ana Mary.
 *
 * - Elimina precios, llamadas de contacto, correos, teléfonos y enlaces de la
 *   tienda de origen de las descripciones que se muestran en EMDO.
 * - Protege el texto ya saneado frente a futuras sincronizaciones automáticas.
 * - Los cambios manuales posteriores siguen siendo posibles y pasan por el
 *   mismo saneado antes de convertirse en la nueva versión protegida.
 */
final class MDO_Huerta_Description_Policy {
	private const SOURCE_HOSTS = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );
	private const LOCK_META = '_emdo_huerta_description_locked';
	private const CANONICAL_META = '_emdo_huerta_description_canonical';
	private const OPTION_VERSION = 'mdo_huerta_description_cleanup_version';
	private const OPTION_STATS = 'mdo_huerta_description_cleanup_stats';
	private const VERSION = '1';
	private static array $busy = array();

	public static function init(): void {
		add_action( 'woocommerce_before_product_object_save', array( __CLASS__, 'preserve_during_emdo_import' ), 999, 2 );
		add_action( 'save_post_product', array( __CLASS__, 'after_product_save' ), 95, 3 );
		add_action( 'added_post_meta', array( __CLASS__, 'on_post_meta' ), 95, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'on_post_meta' ), 95, 4 );
		self::run_once();
	}

	/**
	 * El importador EMDO vuelve a asignar la descripción del payload antes de
	 * guardar. Solo durante esa llamada restauramos la versión editorial bloqueada.
	 * Un guardado manual de WooCommerce no entra aquí y por tanto sí puede editarse.
	 */
	public static function preserve_during_emdo_import( $product, $data_store = null ): void {
		unset( $data_store );
		if ( ! $product instanceof WC_Product || ! $product->get_id() || ! self::is_emdo_import_save() ) {
			return;
		}

		$product_id = (int) $product->get_id();
		if ( '1' !== (string) get_post_meta( $product_id, self::LOCK_META, true ) || ! self::is_huerta_product( $product_id ) ) {
			return;
		}
		if ( ! metadata_exists( 'post', $product_id, self::CANONICAL_META ) ) {
			return;
		}

		$product->set_description( (string) get_post_meta( $product_id, self::CANONICAL_META, true ) );
	}

	public static function after_product_save( int $post_id, WP_Post $post, bool $update ): void {
		unset( $update );
		if ( wp_is_post_revision( $post_id ) || 'product' !== $post->post_type || ! self::is_huerta_product( $post_id ) ) {
			return;
		}
		self::clean_and_lock_product( $post_id );
	}

	public static function on_post_meta( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( '_emdo_source_url' !== $meta_key || 'product' !== get_post_type( $object_id ) ) {
			return;
		}
		if ( self::is_huerta_product( $object_id ) ) {
			self::clean_and_lock_product( $object_id );
		}
	}

	private static function run_once(): void {
		if ( self::VERSION === (string) get_option( self::OPTION_VERSION, '' ) ) {
			return;
		}

		global $wpdb;
		$like = '%lahuertadeanamary.com%';
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_type = 'product'
				AND pm.meta_key = '_emdo_source_url'
				AND pm.meta_value LIKE %s
				ORDER BY p.ID ASC",
				$like
			)
		) ?: array();

		$stats = array(
			'scanned'      => 0,
			'changed'      => 0,
			'locked'       => 0,
			'errors'       => 0,
			'completed_at' => current_time( 'mysql', true ),
		);

		foreach ( $ids as $id ) {
			$product_id = absint( $id );
			if ( ! $product_id ) {
				continue;
			}
			$stats['scanned']++;
			try {
				$result = self::clean_and_lock_product( $product_id );
				if ( $result['changed'] ) {
					$stats['changed']++;
				}
				if ( $result['locked'] ) {
					$stats['locked']++;
				}
			} catch ( Throwable $error ) {
				$stats['errors']++;
			}
		}

		$stats['completed_at'] = current_time( 'mysql', true );
		update_option( self::OPTION_STATS, $stats, false );
		if ( 0 === (int) $stats['errors'] ) {
			update_option( self::OPTION_VERSION, self::VERSION, false );
		}
	}

	/** @return array{changed:bool,locked:bool} */
	private static function clean_and_lock_product( int $product_id ): array {
		$result = array( 'changed' => false, 'locked' => false );
		if ( isset( self::$busy[ $product_id ] ) || ! self::is_huerta_product( $product_id ) ) {
			return $result;
		}

		self::$busy[ $product_id ] = true;
		try {
			global $wpdb;
			$current = (string) $wpdb->get_var(
				$wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d LIMIT 1", $product_id )
			);
			$clean = self::clean_description( $current );

			if ( $clean !== $current ) {
				$updated = $wpdb->update(
					$wpdb->posts,
					array( 'post_content' => $clean ),
					array( 'ID' => $product_id ),
					array( '%s' ),
					array( '%d' )
				);
				if ( false === $updated ) {
					throw new RuntimeException( 'No se pudo sanear la descripción del producto Huerta.' );
				}
				clean_post_cache( $product_id );
				$result['changed'] = true;
			}

			update_post_meta( $product_id, self::CANONICAL_META, $clean );
			update_post_meta( $product_id, self::LOCK_META, '1' );
			$result['locked'] = true;
			self::clean_source_payload( $product_id );
		} finally {
			unset( self::$busy[ $product_id ] );
		}
		return $result;
	}

	private static function clean_source_payload( int $product_id ): void {
		$source_id = absint( get_post_meta( $product_id, '_emdo_source_product_id', true ) );
		if ( ! $source_id || ! class_exists( 'MDO_Database' ) ) {
			return;
		}
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$raw = $wpdb->get_var( $wpdb->prepare( "SELECT source_payload FROM {$table} WHERE id = %d LIMIT 1", $source_id ) );
		$payload = json_decode( (string) $raw, true );
		if ( ! is_array( $payload ) || ! array_key_exists( 'description', $payload ) ) {
			return;
		}
		$current = (string) $payload['description'];
		$clean = self::clean_description( $current );
		if ( $clean === $current ) {
			return;
		}
		$payload['description'] = $clean;
		$wpdb->update(
			$table,
			array( 'source_payload' => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
			array( 'id' => $source_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	private static function clean_description( string $description ): string {
		if ( class_exists( 'MDO_Text' ) ) {
			$description = MDO_Text::normalize_description( $description );
		}
		$description = wp_kses_post( $description );

		// Elimina la línea de "Precio por kilo/unidad" añadida por versiones anteriores.
		$description = (string) preg_replace(
			'~\s*<p\b[^>]*class=["\'][^"\']*\bemdo-source-unit-price\b[^"\']*["\'][^>]*>.*?</p>\s*~isu',
			"\n",
			$description
		);

		// Enlaces directos de contacto nunca deben quedar dentro de la ficha EMDO.
		$description = (string) preg_replace( '~<a\b[^>]*href=["\'](?:mailto:|tel:)[^"\']*["\'][^>]*>.*?</a>~isu', '', $description );

		// Retira bloques completos cuando su función es mostrar precio o desviar el contacto al proveedor.
		$description = (string) preg_replace_callback(
			'~<(p|div|li|address|blockquote)\b[^>]*>.*?</\1>~isu',
			static function ( array $match ): string {
				return self::should_remove_fragment( (string) $match[0] ) ? '' : (string) $match[0];
			},
			$description
		);

		// Algunas fichas antiguas llegan como texto plano con saltos de línea.
		$lines = preg_split( '/\R/u', $description ) ?: array( $description );
		$kept = array();
		foreach ( $lines as $line ) {
			if ( self::should_remove_fragment( (string) $line ) ) {
				continue;
			}
			$kept[] = (string) $line;
		}
		$description = implode( "\n", $kept );

		// Por seguridad, elimina correos o URLs del proveedor que pudieran haber quedado incrustados en texto útil.
		$description = (string) preg_replace( '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/iu', '', $description );
		$description = (string) preg_replace( '~https?://(?:www\.)?lahuertadeanamary\.com[^\s<]*~iu', '', $description );
		$description = (string) preg_replace( '~\b(?:www\.)?lahuertadeanamary\.com\b[^\s<]*~iu', '', $description );
		$description = (string) preg_replace( '~<p\b[^>]*>\s*</p>~iu', '', $description );
		$description = (string) preg_replace( "/\n{3,}/u", "\n\n", $description );
		return trim( $description );
	}

	private static function should_remove_fragment( string $fragment ): bool {
		$plain = html_entity_decode( wp_strip_all_tags( $fragment ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$plain = strtolower( remove_accents( trim( preg_replace( '/\s+/u', ' ', $plain ) ) ) );
		if ( '' === $plain ) {
			return false;
		}

		if ( str_contains( $plain, '€' ) || preg_match( '/\b(?:precio|pvp|importe|coste)\b/u', $plain ) ) {
			return true;
		}
		if ( preg_match( '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/iu', $fragment ) ) {
			return true;
		}
		if ( str_contains( strtolower( $fragment ), 'mailto:' ) || str_contains( strtolower( $fragment ), 'tel:' ) ) {
			return true;
		}
		if ( str_contains( $plain, 'lahuertadeanamary.com' ) ) {
			return true;
		}
		if ( preg_match( '/\b(?:contacta(?:r|nos)?|contacto|escribenos|llamanos|telefono|whatsapp|correo electronico|e-mail|email)\b/u', $plain ) ) {
			return true;
		}
		return false;
	}

	private static function is_huerta_product( int $product_id ): bool {
		$url = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
		if ( '' === $url ) {
			return false;
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return in_array( $host, self::SOURCE_HOSTS, true );
	}

	private static function is_emdo_import_save(): bool {
		foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 16 ) as $frame ) {
			if ( 'MDO_Woo_Importer' === (string) ( $frame['class'] ?? '' ) && 'import_source_product' === (string) ( $frame['function'] ?? '' ) ) {
				return true;
			}
		}
		return false;
	}
}
