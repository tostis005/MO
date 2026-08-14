<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Woo_Importer {
	public static function import_source_product( int $source_product_id ): int {
		if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Product' ) ) {
			throw new RuntimeException( 'WooCommerce no está disponible.' );
		}

		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $source_product_id ), ARRAY_A );
		if ( ! $row ) {
			throw new RuntimeException( 'No existe el producto origen solicitado.' );
		}
		if ( 'excluded' === (string) $row['status'] ) {
			throw new RuntimeException( 'El producto está excluido de EMDO.' );
		}

		$supplier = MDO_Supplier_Repository::find( (int) $row['supplier_id'] );
		if ( ! $supplier ) {
			throw new RuntimeException( 'No se encontró el proveedor asociado.' );
		}
		$vendor_id = (int) $supplier['vendor_user_id'];
		$vendor    = $vendor_id ? get_user_by( 'id', $vendor_id ) : false;
		// WCFM puede retirar o sustituir temporalmente el rol wcfm_vendor al desactivar
		// un vendedor. La asignación EMDO sigue siendo válida mientras el usuario exista;
		// WCFM conserva la responsabilidad de ocultar al vendedor/productos desactivados.
		if ( ! $vendor ) {
			throw new RuntimeException( 'El proveedor no tiene un usuario vendedor válido asignado.' );
		}

		$payload = json_decode( (string) $row['source_payload'], true );
		if ( ! is_array( $payload ) || empty( $payload['title'] ) ) {
			throw new RuntimeException( 'Los datos extraídos del producto están incompletos.' );
		}
		$payload['title'] = MDO_Text::normalize_title( (string) $payload['title'] );

		$variations = self::usable_variations( $payload );
		$declared_variations = isset( $payload['variation_count'] ) ? (int) $payload['variation_count'] : 0;
		if ( $declared_variations > 1 && ! $variations ) {
			throw new RuntimeException( 'El producto tiene opciones/variantes, pero EMDO aún no dispone de la matriz completa de precios y stock. No se importará hasta evitar una ficha incorrecta.' );
		}

		$existing_id = ! empty( $row['wc_product_id'] ) ? (int) $row['wc_product_id'] : 0;
		$product     = $existing_id ? wc_get_product( $existing_id ) : false;
		$is_variable = ! empty( $variations );

		if ( $product ) {
			if ( $is_variable && ! $product->is_type( 'variable' ) ) {
				throw new RuntimeException( 'El producto existente en WooCommerce no es variable y el origen ahora sí lo es. Requiere revisión manual.' );
			}
			if ( ! $is_variable && $product->is_type( 'variable' ) ) {
				throw new RuntimeException( 'El producto existente en WooCommerce es variable y el origen ya no lo es. Requiere revisión manual.' );
			}
		} else {
			$product = $is_variable ? new WC_Product_Variable() : new WC_Product_Simple();
		}

		self::apply_common_fields( $product, $payload );
		if ( $is_variable ) {
			self::apply_variable_product( $product, $variations );
		} else {
			self::apply_simple_product( $product, $payload );
		}

		$product_id = $product->save();
		if ( ! $product_id ) {
			throw new RuntimeException( 'WooCommerce no pudo guardar el producto.' );
		}

		wp_update_post( array( 'ID' => $product_id, 'post_author' => $vendor_id ) );

		self::sync_images( $product, $payload );
		if ( $is_variable ) {
			self::sync_variations( $product, $variations );
			WC_Product_Variable::sync( $product_id );
		}
		if ( class_exists( 'MDO_YITH_Extras' ) ) {
			MDO_YITH_Extras::sync_product( $product_id, $payload );
		}

		update_post_meta( $product_id, '_emdo_source_product_id', $source_product_id );
		update_post_meta( $product_id, '_emdo_supplier_id', (int) $row['supplier_id'] );
		update_post_meta( $product_id, '_emdo_source_url', esc_url_raw( (string) $row['source_url'] ) );
		update_post_meta( $product_id, '_emdo_source_hash', sanitize_text_field( (string) $row['source_hash'] ) );

		$wpdb->update(
			$table,
			array(
				'wc_product_id' => $product_id,
				'status'        => 'active',
				'title'         => $payload['title'],
				'last_error'    => null,
			),
			array( 'id' => $source_product_id )
		);

		return $product_id;
	}

	public static function sync_if_active( int $supplier_id, string $source_url ): void {
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, status, wc_product_id FROM {$table} WHERE supplier_id = %d AND source_url = %s ORDER BY id DESC LIMIT 1",
				$supplier_id,
				$source_url
			),
			ARRAY_A
		);
		if ( ! $row || 'active' !== (string) $row['status'] || empty( $row['wc_product_id'] ) ) {
			return;
		}
		self::import_source_product( (int) $row['id'] );
	}

	/**
	 * Retira de la venta un producto que ya no aparece en el catálogo de origen.
	 * Conservamos la relación EMDO para poder restaurarlo automáticamente si
	 * vuelve a aparecer en una sincronización posterior.
	 */
	public static function mark_source_unavailable( int $source_product_id ): bool {
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id,status,wc_product_id,title FROM {$table} WHERE id = %d", $source_product_id ), ARRAY_A );
		if ( ! $row || in_array( (string) $row['status'], array( 'excluded', 'unavailable' ), true ) ) {
			return false;
		}

		$wpdb->update(
			$table,
			array(
				'status'              => 'unavailable',
				'source_stock_status' => 'outofstock',
				'last_error'          => 'No encontrado en el catálogo de origen durante la última sincronización completa.',
			),
			array( 'id' => $source_product_id )
		);

		$product_id = ! empty( $row['wc_product_id'] ) ? (int) $row['wc_product_id'] : 0;
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$product->set_stock_status( 'outofstock' );
				$product->set_catalog_visibility( 'hidden' );
				$product->set_status( 'draft' );
				$product->save();
				update_post_meta( $product_id, '_emdo_source_unavailable', '1' );
			}
		}
		return true;
	}

	public static function mark_source_url_unavailable( int $supplier_id, string $source_url ): bool {
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$id    = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE supplier_id = %d AND source_url = %s ORDER BY id DESC LIMIT 1",
				$supplier_id,
				$source_url
			)
		);
		return $id > 0 ? self::mark_source_unavailable( $id ) : false;
	}

	/**
	 * Si un producto marcado como no disponible reaparece, el payload ya ha sido
	 * actualizado por el conector. Lo reimportamos para recuperar publicación,
	 * visibilidad, precio y stock exactamente desde la fuente.
	 */
	public static function restore_if_unavailable( int $supplier_id, string $source_url ): bool {
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id,status,wc_product_id FROM {$table} WHERE supplier_id = %d AND source_url = %s ORDER BY id DESC LIMIT 1",
				$supplier_id,
				$source_url
			),
			ARRAY_A
		);
		if ( ! $row || 'unavailable' !== (string) $row['status'] ) {
			return false;
		}

		if ( ! empty( $row['wc_product_id'] ) ) {
			self::import_source_product( (int) $row['id'] );
			delete_post_meta( (int) $row['wc_product_id'], '_emdo_source_unavailable' );
		} else {
			$wpdb->update( $table, array( 'status' => 'pending', 'last_error' => null ), array( 'id' => (int) $row['id'] ) );
		}
		return true;
	}

	public static function exclude_source_product( int $source_product_id ): void {
		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT wc_product_id FROM {$table} WHERE id = %d", $source_product_id ), ARRAY_A );
		if ( ! $row ) {
			return;
		}
		$wpdb->update( $table, array( 'status' => 'excluded', 'last_error' => null ), array( 'id' => $source_product_id ) );
		if ( ! empty( $row['wc_product_id'] ) ) {
			$product = wc_get_product( (int) $row['wc_product_id'] );
			if ( $product ) {
				$product->set_status( 'draft' );
				$product->save();
			}
		}
	}

	public static function mark_importing( int $source_product_id ): bool {
		global $wpdb;
		$table  = MDO_Database::table( 'source_products' );
		$result = $wpdb->update(
			$table,
			array( 'status' => 'importing', 'last_error' => null ),
			array( 'id' => $source_product_id, 'status' => 'pending' )
		);
		return is_int( $result ) && $result > 0;
	}

	public static function mark_import_error( int $source_product_id, string $message ): void {
		global $wpdb;
		$wpdb->update(
			MDO_Database::table( 'source_products' ),
			array( 'status' => 'pending', 'last_error' => sanitize_textarea_field( $message ) ),
			array( 'id' => $source_product_id )
		);
	}

	private static function apply_common_fields( WC_Product $product, array $payload ): void {
		$product->set_name( MDO_Text::normalize_title( (string) $payload['title'] ) );
		$product->set_description( isset( $payload['description'] ) ? wp_kses_post( (string) $payload['description'] ) : '' );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_stock_status( 'outofstock' === (string) ( $payload['stock_status'] ?? '' ) ? 'outofstock' : 'instock' );

		$sku = sanitize_text_field( (string) ( $payload['sku'] ?? '' ) );
		if ( $sku ) {
			$sku_owner = wc_get_product_id_by_sku( $sku );
			if ( ! $sku_owner || (int) $sku_owner === (int) $product->get_id() ) {
				$product->set_sku( $sku );
			}
		}
	}

	private static function apply_simple_product( WC_Product $product, array $payload ): void {
		if ( ! isset( $payload['price'] ) || null === $payload['price'] || ! is_numeric( $payload['price'] ) ) {
			throw new RuntimeException( 'No se ha podido determinar un precio válido para el producto.' );
		}

		$current = (float) $payload['price'];
		$regular = isset( $payload['regular_price'] ) && is_numeric( $payload['regular_price'] )
			? (float) $payload['regular_price']
			: $current;
		$sale = isset( $payload['sale_price'] ) && is_numeric( $payload['sale_price'] )
			? (float) $payload['sale_price']
			: null;

		/*
		 * El payload de EMDO es la fuente de verdad completa del precio. No basta
		 * con cambiar _price: un producto ya importado puede conservar un
		 * _sale_price antiguo y WooCommerce seguirá mostrándolo como oferta.
		 * Del mismo modo, una oferta real debe trasladar regular + sale, no
		 * degradarse a un único precio. Cada sincronización reescribe los tres
		 * valores para que también sanee metadatos heredados.
		 */
		if ( null !== $sale && $sale < $regular && abs( $current - $sale ) < 0.005 ) {
			$product->set_regular_price( wc_format_decimal( $regular ) );
			$product->set_sale_price( wc_format_decimal( $sale ) );
			$product->set_price( wc_format_decimal( $sale ) );
		} else {
			$product->set_regular_price( wc_format_decimal( $current ) );
			$product->set_sale_price( '' );
			$product->set_price( wc_format_decimal( $current ) );
		}
		$product->set_manage_stock( false );
	}

	private static function apply_variable_product( WC_Product $product, array $variations ): void {
		$attribute_values = array();
		foreach ( $variations as $variation ) {
			foreach ( (array) ( $variation['attributes'] ?? array() ) as $source_key => $source_value ) {
				$key   = self::local_attribute_key( (string) $source_key );
				$value = self::attribute_display_value( (string) $source_value );
				if ( '' === $key || '' === $value ) {
					continue;
				}
				$attribute_values[ $key ][ $value ] = $value;
			}
		}

		$attributes = array();
		$position   = 0;
		foreach ( $attribute_values as $key => $values ) {
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( 0 );
			$attribute->set_name( self::attribute_label( $key ) );
			$attribute->set_options( array_values( $values ) );
			$attribute->set_position( $position++ );
			$attribute->set_visible( true );
			$attribute->set_variation( true );
			$attributes[] = $attribute;
		}
		$product->set_attributes( $attributes );
		$product->set_manage_stock( false );
	}

	private static function sync_variations( WC_Product $product, array $variations ): void {
		$product_id = $product->get_id();
		$existing   = array();
		foreach ( $product->get_children() as $child_id ) {
			$source_variation_id = (string) get_post_meta( $child_id, '_emdo_source_variation_id', true );
			if ( '' !== $source_variation_id ) {
				$existing[ $source_variation_id ] = $child_id;
			}
		}

		$seen = array();
		foreach ( $variations as $source_variation ) {
			$source_id = ! empty( $source_variation['variation_id'] ) ? (string) $source_variation['variation_id'] : hash( 'sha256', (string) wp_json_encode( $source_variation['attributes'] ?? array() ) );
			$variation = isset( $existing[ $source_id ] ) ? new WC_Product_Variation( (int) $existing[ $source_id ] ) : new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$attrs = array();
			foreach ( (array) ( $source_variation['attributes'] ?? array() ) as $source_key => $source_value ) {
				$key   = sanitize_title( self::attribute_label( self::local_attribute_key( (string) $source_key ) ) );
				$value = self::attribute_display_value( (string) $source_value );
				if ( $key && $value ) {
					$attrs[ $key ] = $value;
				}
			}
			$variation->set_attributes( $attrs );

			$price   = isset( $source_variation['display_price'] ) && is_numeric( $source_variation['display_price'] ) ? (float) $source_variation['display_price'] : null;
			$regular = isset( $source_variation['display_regular_price'] ) && is_numeric( $source_variation['display_regular_price'] ) ? (float) $source_variation['display_regular_price'] : $price;
			if ( null === $price ) {
				continue;
			}
			$variation->set_regular_price( wc_format_decimal( $regular ) );
			if ( null !== $regular && $price < $regular ) {
				$variation->set_sale_price( wc_format_decimal( $price ) );
			} else {
				$variation->set_sale_price( '' );
			}
			$variation->set_price( wc_format_decimal( $price ) );
			$variation->set_manage_stock( false );
			$variation->set_stock_status( ! empty( $source_variation['is_in_stock'] ) ? 'instock' : 'outofstock' );
			$variation_id = $variation->save();
			update_post_meta( $variation_id, '_emdo_source_variation_id', $source_id );
			$seen[ $source_id ] = true;
		}

		foreach ( $existing as $source_id => $variation_id ) {
			if ( ! isset( $seen[ $source_id ] ) ) {
				wp_delete_post( (int) $variation_id, true );
			}
		}
	}

	private static function sync_images( WC_Product $product, array $payload ): void {
		$urls = isset( $payload['images'] ) && is_array( $payload['images'] ) ? array_values( array_unique( array_filter( array_map( 'esc_url_raw', $payload['images'] ) ) ) ) : array();
		if ( ! $urls ) {
			return;
		}
		$urls = array_slice( $urls, 0, 12 );
		$map  = json_decode( (string) get_post_meta( $product->get_id(), '_emdo_image_map', true ), true );
		$map  = is_array( $map ) ? $map : array();
		$ids  = array();

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( $urls as $url ) {
			$attachment_id = isset( $map[ $url ] ) ? absint( $map[ $url ] ) : 0;
			if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
				$attachment_id = media_sideload_image( $url, $product->get_id(), $product->get_name(), 'id' );
				if ( is_wp_error( $attachment_id ) ) {
					continue;
				}
				$map[ $url ] = (int) $attachment_id;
			}
			$ids[] = (int) $attachment_id;
		}

		if ( $ids ) {
			$product->set_image_id( array_shift( $ids ) );
			$product->set_gallery_image_ids( array_values( array_unique( $ids ) ) );
			$product->save();
			update_post_meta( $product->get_id(), '_emdo_image_map', wp_json_encode( $map, JSON_UNESCAPED_SLASHES ) );
		}
	}

	private static function usable_variations( array $payload ): array {
		$raw = isset( $payload['variations'] ) && is_array( $payload['variations'] ) ? $payload['variations'] : array();
		return array_values( array_filter( $raw, static fn( array $variation ): bool => ! empty( $variation['attributes'] ) && isset( $variation['display_price'] ) && is_numeric( $variation['display_price'] ) ) );
	}

	private static function local_attribute_key( string $key ): string {
		$key = preg_replace( '/^attribute_/', '', $key );
		$key = preg_replace( '/^pa_/', '', (string) $key );
		return sanitize_title( (string) $key );
	}

	private static function attribute_label( string $key ): string {
		$label = str_replace( array( '-', '_' ), ' ', $key );
		return function_exists( 'mb_convert_case' ) ? mb_convert_case( $label, MB_CASE_TITLE, 'UTF-8' ) : ucwords( $label );
	}

	private static function attribute_display_value( string $value ): string {
		$value = trim( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$value = preg_replace( '/-(kg|g)$/i', ' $1', $value );
		$value = str_replace( '_', ' ', (string) $value );
		return trim( (string) $value );
	}
}
