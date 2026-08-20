<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Production deployment trigger: 2026-08-20.
/**
 * Completa únicamente datos de formato/peso verificados en las fichas de
 * Tolecarnes que el conector no conserva actualmente en su descripción corta.
 *
 * No usamos el campo _weight de WooCommerce: ese campo interviene en cálculos
 * logísticos y no siempre representa el peso de venta (por ejemplo, precio/kg,
 * bandejas variables o lotes). La información se muestra al cliente dentro de
 * la descripción y se vuelve a aplicar después de cada sincronización.
 */
final class MDO_Tolecarnes_Weight_Info {
	private const MIGRATION_OPTION = 'mdo_tolecarnes_weight_info_20260820';
	private const MIGRATION_VERSION = '1';
	private const START_MARKER = '<!-- emdo-tolecarnes-format:start -->';
	private const END_MARKER   = '<!-- emdo-tolecarnes-format:end -->';

	private static bool $repairing = false;

	public static function init(): void {
		add_action( 'woocommerce_new_product', array( __CLASS__, 'repair_product' ), 70, 1 );
		add_action( 'woocommerce_update_product', array( __CLASS__, 'repair_product' ), 70, 1 );
		add_action( 'added_post_meta', array( __CLASS__, 'after_meta_added' ), 70, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'after_meta_updated' ), 70, 4 );
		add_action( 'init', array( __CLASS__, 'maybe_migrate' ), 80 );
	}

	public static function after_meta_added( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( '_emdo_source_url' === $meta_key ) {
			self::repair_product( $object_id );
		}
	}

	public static function after_meta_updated( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( '_emdo_source_url' === $meta_key ) {
			self::repair_product( $object_id );
		}
	}

	public static function maybe_migrate(): void {
		if ( self::MIGRATION_VERSION === (string) get_option( self::MIGRATION_OPTION, '' ) ) {
			return;
		}
		self::repair_all();
		update_option( self::MIGRATION_OPTION, self::MIGRATION_VERSION, false );
	}

	/**
	 * Repara todas las fichas WooCommerce vinculadas a Tolecarnes.
	 * Devuelve contadores para poder auditar el despliegue por WP-CLI.
	 */
	public static function repair_all(): array {
		global $wpdb;
		$ids = $wpdb->get_col(
			"SELECT DISTINCT post_id
			 FROM {$wpdb->postmeta}
			 WHERE meta_key = '_emdo_source_url'
			   AND meta_value LIKE '%tolecarnes.com/producto/%'"
		);

		$result = array(
			'linked'      => count( $ids ),
			'mapped'      => 0,
			'changed'     => 0,
			'unchanged'   => 0,
			'not_needed'  => 0,
		);

		foreach ( $ids as $product_id ) {
			$product_id = absint( $product_id );
			$url        = (string) get_post_meta( $product_id, '_emdo_source_url', true );
			$note       = self::note_for_url( $url );
			if ( '' === $note ) {
				++$result['not_needed'];
				continue;
			}
			++$result['mapped'];
			if ( self::repair_product( $product_id ) ) {
				++$result['changed'];
			} else {
				++$result['unchanged'];
			}
		}

		return $result;
	}

	/**
	 * @return bool True si la descripción fue modificada.
	 */
	public static function repair_product( int $product_id ): bool {
		if ( self::$repairing || $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
			return false;
		}

		$url  = (string) get_post_meta( $product_id, '_emdo_source_url', true );
		$note = self::note_for_url( $url );
		if ( '' === $note ) {
			return false;
		}

		$current = (string) get_post_field( 'post_content', $product_id, 'raw' );
		$base    = self::strip_existing_block( $current );
		$block   = self::START_MARKER
			. '<p><strong>Formato y peso:</strong> ' . esc_html( $note ) . '</p>'
			. self::END_MARKER;
		$updated = rtrim( $base ) . "\n\n" . $block;

		update_post_meta( $product_id, '_emdo_tolecarnes_format_note', $note );
		if ( $updated === $current ) {
			return false;
		}

		self::$repairing = true;
		wp_update_post(
			array(
				'ID'           => $product_id,
				'post_content' => $updated,
			)
		);
		self::$repairing = false;
		return true;
	}

	private static function strip_existing_block( string $description ): string {
		$pattern = '~\s*' . preg_quote( self::START_MARKER, '~' ) . '.*?' . preg_quote( self::END_MARKER, '~' ) . '~s';
		return trim( (string) preg_replace( $pattern, '', $description ) );
	}

	/**
	 * Notas contrastadas el 20-08-2026 con las fichas de Tolecarnes.
	 * Solo incluimos los productos vinculados cuya descripción EMDO carecía del
	 * dato aunque la ficha original sí lo publicaba. Los huesos quedan fuera:
	 * Tolecarnes indica bandeja de 2 huesos, pero no publica un peso.
	 */
	private static function verified_notes(): array {
		return array(
			'carne-picada-de-ternera'            => 'Paquete de 1 kg, envasado al vacío.',
			'burger-de-ternera-sin-gluten-awh'   => 'Bandeja de 2 unidades; aprox. 140 g por unidad y 280 g en total.',
			'magro-ragu-ternera-az'               => 'Bandeja de 1 kg, envasada al vacío.',
			'filetes-aguja-de-ternera'            => 'Bandeja de 1 kg, envasada al vacío.',
			'chuleton-de-vaca-vieja-madurado-adw'=> 'Precio por pieza; opciones de 500 g o 800 g.',
			'morcillo-de-ternera-ak'              => 'Peso indicado por el productor: 1 kg.',
			'churrasco-deternera'                 => 'Bandeja de 1 kg, envasada al vacío.',
			'rabo-de-ternera-montes-de-toledo-awd'=> 'Bandeja de aprox. 0,700 a 1,200 kg.',
			'burguer-100-ternera'                 => 'Bandeja de 2 unidades; aprox. 150 g por unidad.',
			'lote-tomahawk-de-aguja-y-vino'      => 'Lote de 2 Tomahawk de aguja (aprox. 0,8–1 kg por pieza, según la ficha individual) y una botella de vino.',
			'burger-vaca'                         => 'Bandeja de 2 unidades; aprox. 150 g por unidad.',
		);
	}

	private static function note_for_url( string $url ): string {
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		if ( '' === $path ) {
			return '';
		}
		$parts = explode( '/', $path );
		$slug  = (string) end( $parts );
		$notes = self::verified_notes();
		return isset( $notes[ $slug ] ) ? (string) $notes[ $slug ] : '';
	}
}
