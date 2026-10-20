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
		if ( in_array( (string) $supplier['code'], array( 'latitudes', 'chocolates-artesanos-isabel', 'tropitop' ), true ) &&
			! in_array( (string) ( $payload['stock_status'] ?? 'unknown' ), array( 'instock', 'outofstock' ), true ) ) {
			throw new RuntimeException( 'Stock del proveedor sin verificar: se cancela la importación de esta referencia.' );
		}


		$variations = self::usable_variations( $payload );
		$declared_variations = isset( $payload['variation_count'] ) ? (int) $payload['variation_count'] : 0;
		if ( $declared_variations > 1 && ( ! $variations ||
			( in_array( (string) $supplier['code'], array( 'chocolates-artesanos-isabel', 'tropitop' ), true ) && count( $variations ) < $declared_variations ) ) ) {
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
		if ( 'tropitop' === (string) $supplier['code'] ) {
			$product->set_short_description( wp_kses_post( (string) ( $payload['short_description'] ?? '' ) ) );
		}
		// Product copy may be authored for the supplier's own checkout.
		// Neutralize ONLY verified supplier-first-person/promotional wording,
		// leaving ingredients, trace warnings, nutritional data and specs intact.
		if ( 'chocolates-artesanos-isabel' === (string) $supplier['code'] ) {
			$product->set_description( self::neutralize_isabel_supplier_copy( (string) ( $payload['description'] ?? '' ) ) );
			$existing_short = (string) $product->get_short_description();
			if ( '' !== trim( $existing_short ) ) {
				$product->set_short_description( self::neutralize_isabel_supplier_copy( $existing_short ) );
			}
		}

		// Independently edited supplier descriptions must survive nightly syncs.
		// Price, images, title, variations and stock still come exclusively from
		// the live supplier feed. The editorial override is limited to Latitudes
		// and only applies to a locally approved, existing product.
		if ( 'latitudes' === (string) $supplier['code'] && $existing_id ) {
			$editorial_description = (string) get_post_meta( $existing_id, '_emdo_latitudes_editorial_description', true );
			if ( '1' === (string) get_post_meta( $existing_id, '_emdo_latitudes_editorial_approved', true ) &&
				'' !== trim( wp_strip_all_tags( $editorial_description ) ) ) {
				$product->set_description( wp_kses_post( $editorial_description ) );
				$editorial_short = (string) get_post_meta( $existing_id, '_emdo_latitudes_editorial_short_description', true );
				if ( '' !== trim( wp_strip_all_tags( $editorial_short ) ) ) {
					$product->set_short_description( wp_kses_post( $editorial_short ) );
				}
			}
		}

		// Chocolate editorial improvements are approved separately, scoped to
		// Isabel and kept stable across daily source imports. Nutrition,
		// composition, allergen and conservation details are preserved in the
		// approved full text. No source-driven price or stock field is changed.
		if ( 'chocolates-artesanos-isabel' === (string) $supplier['code'] && $existing_id &&
			'1' === (string) get_post_meta( $existing_id, '_emdo_isabel_editorial_approved', true ) ) {
			$approved_intro = (string) get_post_meta( $existing_id, '_emdo_isabel_editorial_intro', true );
			$approved_short = (string) get_post_meta( $existing_id, '_emdo_isabel_editorial_short_description', true );
			if ( trim( wp_strip_all_tags( $approved_intro ) ) !== '' ) {
				// Always append the NEW supplier description from this synchronization.
				// Ingredient, allergen and storage changes must never be frozen.
				$product->set_description( wp_kses_post( $approved_intro ) . "\n" . self::neutralize_isabel_supplier_copy( (string) ( $payload['description'] ?? '' ) ) );
			}
			if ( trim( wp_strip_all_tags( $approved_short ) ) !== '' ) {
				$product->set_short_description( wp_kses_post( $approved_short ) );
			}
		}

		// Seven seller-customizable chocolates cannot be purchased until their
		// variation forms are configured. A factual, approved introduction helps
		// describe each product without promising an available checkout option.
		// Keep source ingredients, allergen and conservation details current.
		if ( 'chocolates-artesanos-isabel' === (string) $supplier['code'] && $existing_id &&
			'1' === (string) get_post_meta( $existing_id, '_emdo_isabel_customization_review_required', true ) ) {
			$custom_intro = (string) get_post_meta( $existing_id, '_emdo_isabel_personalizable_editorial_intro', true );
			if ( '' !== trim( wp_strip_all_tags( $custom_intro ) ) ) {
				$product->set_description( wp_kses_post( $custom_intro ) . "\n" .
					self::neutralize_isabel_supplier_copy( (string) ( $payload['description'] ?? '' ) ) );
			}
		}

		// Prelaunch products from Latitudes must never become public while its
		// WCFM vendor is hard-disabled, including after nightly updates.
		if ( in_array( (string) $supplier['code'], array( 'latitudes', 'chocolates-artesanos-isabel', 'tropitop' ), true ) && get_user_meta( $vendor_id, '_disable_vendor', true ) ) {
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
		}
		if ( 'tropitop' === (string) $supplier['code'] && ! MDO_Tropitop_Shipping::is_ready() ) {
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
		}
		// Never auto-publish an unreviewed new SKU merely because this vendor
		// becomes active later. Human-validated existing products retain their
		// approved publication state during regular nightly stock updates.
		if ( 'tropitop' === (string) $supplier['code'] &&
			( ! $existing_id || '1' !== (string) get_post_meta( $existing_id, '_mdo_tropitop_prelaunch_validated', true ) ) ) {
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
		}
		$category_result = class_exists( 'MDO_Auto_Categorizer' )
			? MDO_Auto_Categorizer::maybe_assign( $product, $payload, $row )
			: array();
		// Source category paths are matched against an explicit, bounded
		// mapping, never guessed by the generic meat-oriented categorizer.
		if ( 'tropitop' === (string) $supplier['code'] ) {
			$categories = self::tropitop_category_ids( $payload );
			$product->set_category_ids( $categories );
			$category_result = array( 'assigned' => true, 'reason' => 'tropitop_source_category',
				'category_ids' => $categories, 'score' => null );
		}
		if ( $is_variable ) {
			self::apply_variable_product( $product, $variations );
		} else {
			self::apply_simple_product( $product, $payload );
		}

        // Latitudes does not expose weight in its synchronizer payload. Preserve
        // verified manual overrides; otherwise read the advertised pack weight.
        $latitudes_inferred_weight = null;
        if ('latitudes' === (string)$supplier['code']) {
            $latitudes_inferred_weight = MDO_Latitudes_Logistics::weight_from_title((string)$payload['title']);
            $saved_basis = $existing_id ? (string)get_post_meta($existing_id,'_mdo_latitudes_weight_basis',true) : '';
            if ($latitudes_inferred_weight && ($product->get_weight('edit') === '' || $saved_basis !== 'verified_manual')) {
                $product->set_weight((string)$latitudes_inferred_weight['kg']);
            }
        }
        $product_id = $product->save();
		if ( ! $product_id ) {
			throw new RuntimeException( 'WooCommerce no pudo guardar el producto.' );
		}

        if ($latitudes_inferred_weight && 'verified_manual' !== (string)get_post_meta($product_id,'_mdo_latitudes_weight_basis',true)) {
            update_post_meta($product_id,'_mdo_latitudes_weight_basis',(string)$latitudes_inferred_weight['basis']);
        }
        wp_update_post( array( 'ID' => $product_id, 'post_author' => $vendor_id ) );
		if ( class_exists( 'MDO_Auto_Categorizer' ) ) {
			MDO_Auto_Categorizer::record_result( $product_id, $category_result );
		}

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
		if ( 'chocolates-artesanos-isabel' === (string) $supplier['code'] ) {
			MDO_Isabel_Quality::apply( $product_id, $payload );
		}

		// Product-title heuristics elsewhere in WooCommerce sometimes add
		// "Packs y lotes" to simple multi-unit packs (e.g. two burger patties or
		// 250 g of frog legs). That category is reserved for genuine mixed
		// assortments: a supplier-specific, post-save guard prevents taxonomy
		// drift on every future Latitudes synchronization.
		if ( 'latitudes' === (string) $supplier['code'] &&
			! str_contains( (string) $row['source_url'], '/pack-medallones-exoticos-' ) ) {
			$packs = get_term_by( 'slug', 'packs-y-lotes', 'product_cat' );
			if ( $packs instanceof WP_Term && has_term( (int) $packs->term_id, 'product_cat', $product_id ) ) {
				wp_remove_object_terms( $product_id, (int) $packs->term_id, 'product_cat' );
			}
		}


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


	/**
	 * Keep source specifications intact but remove the producer's own-store
	 * voice and outbound shop links when presenting its copy on EMDO.
	 * The supplier payload remains unchanged for subsequent stock/allergen QA.
	 */
	public static function neutralize_isabel_supplier_copy( string $html ): string {
		$replacements = array(
			'Con este kit le proponemos' => 'Este kit ofrece',
			'-como la nuestra-' => '',
			'Con nuestra Tarjeta-Regalo acertar es muy fácil.' => 'Tarjeta regalo personalizable para disfrutar o regalar chocolate artesano.',
			'le enviaremos a usted una copia por e-mail' => 'La posibilidad de recibir una copia por correo electrónico requiere confirmación previa.',
			'le enviaremos a usted una copia por email' => 'La posibilidad de recibir una copia por correo electrónico requiere confirmación previa.',
			'indíquenos el correo electrónico y el mensaje para el destinatario y nosotras se la haremos llegar de su parte' => 'consulte con el productor las opciones de envío directo y dedicatoria antes de la compra',
			'Chocolates Artesanos Isabel planta un árbol por usted a través de la ONG Plant For The Planet.' => '',
			'Tiene más información aquí.' => 'Consulte las características y el contenido del producto en esta ficha.',
		);
		$html = str_ireplace( array_keys( $replacements ), array_values( $replacements ), $html );
		// Supplier identity can appear inside formatting tags; keep the product
		// claims and ingredient labels, but make the merchant attribution neutral.
		$html = preg_replace( '/Chocolates?\\s+Artesanos?\\s+Isabel/iu', 'el fabricante', $html );
		// Original maker email is not the checkout support channel on EMDO.
		$html = preg_replace( '~<a\\b[^>]*href\\s*=\\s*["\\\']mailto:[^"\\\']*@chocolatesartesanosisabel\\.com[^"\\\']*["\\\'][^>]*>.*?</a>~isu', 'consulte antes de comprar las condiciones de personalización', $html );
		// An outbound maker-store link is inappropriate inside EMDO's checkout
		// copy. Retain the visible label; never touch third-party certifications.
		$html = preg_replace( '~<a\b[^>]*href\s*=\s*["\']https?://(?:www\.)?chocolatesartesanosisabel\.com[^"\']*["\'][^>]*>(.*?)</a>~isu', '$1', $html );
		return self::remove_source_attribution( wp_kses_post( (string) $html ) );
	}

	/**
	 * Keep verifiable product facts and eliminate third-person editorial
	 * references to supplier webpages. This also runs during night imports.
	 */
	public static function remove_source_attribution( string $html ): string {
		$patterns = array(
			'~\bseg[uú]n\s+(?:se\s+(?:indica|informa)\s+en\s+)?(?:la\s+)?(?:web|p[aá]gina|ficha(?:\s+t[eé]cnica)?|informaci[oó]n|cat[aá]logo)\s+(?:de(?:l|\s+la)\s+)?(?:productor|fabricante|proveedor|Latitudes)\b,?\s*~iu' => '',
			'~\bseg[uú]n\s+la\s+lista\s+de\s+ingredientes\s+publicada\s+por\s+(?:el\s+)?(?:productor|fabricante|proveedor)\b,?\s*~iu' => '',
			'~\b(?:La\s+)?(?:ficha|web|p[aá]gina)\s+del\s+(?:productor|fabricante|proveedor)\s+(?:indica|se[nñ]ala|explica|especifica)\s+que\s+~iu' => '',
			'~\b(?:El\s+)?(?:productor|fabricante|proveedor)\s+(?:indica|informa|se[nñ]ala|explica|especifica)\s+que\s+~iu' => '',
			'~\bLa\s+ficha\s+del\s+(?:productor|fabricante|proveedor)\s+describe\s+~iu' => 'Presenta ',
			'~\bLa\s+ficha\s+del\s+(?:productor|fabricante|proveedor)\s+destaca\s+~iu' => 'Destaca ',
		);
		$clean = preg_replace( array_keys( $patterns ), array_values( $patterns ), $html );
		return wp_kses_post( is_string( $clean ) ? $clean : $html );
	}

    /**
     * Tropitop catalog taxonomy: explicit source-path map, not heuristic
     * scoring (meat/chocolate categories elsewhere in this marketplace).
     * A new term is created only for these audited product category slugs.
     */
    private static function tropitop_category_ids( array $payload ): array {
        $source = (string) ( $payload['source_url'] ?? '' );
        if ( ! in_array( strtolower( (string) wp_parse_url( $source, PHP_URL_HOST ) ),
            array( 'tropitop.com', 'www.tropitop.com' ), true ) ) {
            throw new RuntimeException( 'Categoría Tropitop: host de origen incorrecto.' );
        }
        $path = explode( '/', trim( (string) wp_parse_url( $source, PHP_URL_PATH ), '/' ) );
        $key = sanitize_title( (string) ( $path[1] ?? '' ) );
        $title = mb_strtolower( (string) ( $payload['title'] ?? '' ) );
        $is_pack = (bool) preg_match( '/\b(?:pack|mix|caja(?:s)? de|lote|cesta)\b/u', $title );
        if ( $key === 'productos' ) {
            $key = preg_match( '/pimient|tomate|patata|verdura/u', $title ) ? 'verduras' : 'frutas';
        }
        if ( $key === 'home-verduras' ) { $key = 'verduras'; }
        if ( $key === 'disponible' ) { $key = 'packs'; }
        $map = array(
            'aguacates' => array( 'Aguacates', 'aguacates', 'frutas' ),
            'frutas' => array( 'Frutas', 'frutas', '' ),
            'frutas-tropicales' => array( 'Frutas tropicales', 'frutas-tropicales', 'frutas' ),
            'frutas-del-mundo' => array( 'Frutas del mundo', 'frutas-del-mundo', 'frutas' ),
            'verduras' => array( 'Verduras', 'verduras', '' ),
            'gourmet' => array( 'Gourmet', 'gourmet', '' ),
            'packs' => array( 'Packs y lotes', 'packs-y-lotes', '' ),
            'cajas-de-temporada' => array( 'Cajas de temporada', 'cajas-de-temporada', 'packs-y-lotes' ),
            'cestas-de-fruta' => array( 'Cestas de fruta', 'cestas-de-fruta', 'packs-y-lotes' ),
            'citricos' => array( 'Cítricos', 'citricos', 'frutas' ),
            'mangos' => array( 'Mangos', 'mangos', 'frutas' ),
            'bio' => array( 'Productos BIO', 'productos-bio', '' ),
            'apadrina-un-arbol' => array( 'Apadrina un árbol', 'apadrina-un-arbol', '' ),
            'cosmetica' => array( 'Cosmética', 'cosmetica', 'gourmet' ),
            'zumos-y-tes' => array( 'Zumos e infusiones', 'zumos-y-tes', 'gourmet' ),
        );
        if ( ! isset( $map[ $key ] ) ) {
            throw new RuntimeException( 'Categoría Tropitop no verificada: ' . $key );
        }
        $target = $map[ $key ];
        $parent_id = 0;
        if ( $target[2] ) {
            $parent = get_term_by( 'slug', $target[2], 'product_cat' );
            if ( ! $parent instanceof WP_Term && isset( $map[ $target[2] ] ) ) {
                $p = $map[ $target[2] ];
                $result = wp_insert_term( $p[0], 'product_cat', array( 'slug' => $p[1] ) );
                if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
                $parent = get_term( (int) $result['term_id'], 'product_cat' );
            }
            if ( ! $parent instanceof WP_Term ) {
                throw new RuntimeException( 'Categoría padre Tropitop ausente: ' . $target[2] );
            }
            $parent_id = (int) $parent->term_id;
        }
        $term = get_term_by( 'slug', $target[1], 'product_cat' );
        if ( ! $term instanceof WP_Term ) {
            $result = wp_insert_term( $target[0], 'product_cat', array(
                'slug' => $target[1], 'parent' => $parent_id,
                'description' => 'Selección de productos disponibles del productor Tropitop.',
            ) );
            if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
            $term = get_term( (int) $result['term_id'], 'product_cat' );
        }
        if ( ! $term instanceof WP_Term ) { throw new RuntimeException( 'No se guardó la categoría Tropitop.' ); }
        $ids = array( (int) $term->term_id );
        if ( $is_pack && 'packs' !== $key && 'cajas-de-temporada' !== $key ) {
            $pack = get_term_by( 'slug', 'packs-y-lotes', 'product_cat' );
            if ( $pack instanceof WP_Term ) { $ids[] = (int) $pack->term_id; }
        }
        return array_values( array_unique( $ids ) );
    }

	private static function apply_common_fields( WC_Product $product, array $payload ): void {
		$product->set_name( MDO_Text::normalize_title( (string) $payload['title'] ) );
		$product->set_description( isset( $payload['description'] ) ? self::remove_source_attribution( (string) $payload['description'] ) : '' );
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
			if ( isset( $source_variation['weight_kg'] ) && is_numeric( $source_variation['weight_kg'] ) && (float) $source_variation['weight_kg'] > 0 ) {
				$variation->set_weight( wc_format_decimal( $source_variation['weight_kg'] ) );
			}
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
		// Tropitop's authorized PrestaShop gallery can contain more than 12
		// photos. Preserve every verified image (bounded to 20), without changing
		// the image budget of any other marketplace supplier.
		$max_images = 'tropitop.com' === strtolower( (string) wp_parse_url( (string) ( $payload['source_url'] ?? '' ), PHP_URL_HOST ) ) ? 20 : 12;
		$urls = array_slice( $urls, 0, $max_images );
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
