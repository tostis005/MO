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
		if ( 'tropitop' === (string) $supplier['code'] ) {
			$payload['title'] = MDO_Tropitop_Editorial::title( (string) $payload['title'] );
		}
        // Last-line exclusion check for Pajarete: do not accidentally sell
        // fresh, tender or spreadable cheeses found in future source crawls.
        if ( 'quesos-pajarete' === (string) $supplier['code'] &&
            class_exists( 'MDO_Connector_Quesos_Pajarete' ) &&
            MDO_Connector_Quesos_Pajarete::excluded(
                (string) $row['source_url'], (string) $payload['title']
            ) ) {
            throw new RuntimeException( 'Referencia Pajarete excluida; no se importará.' );
        }

		if ( in_array( (string) $supplier['code'], array( 'latitudes', 'chocolates-artesanos-isabel', 'tropitop', 'quesos-pajarete', 'entresetas' ), true ) &&
			! in_array( (string) ( $payload['stock_status'] ?? 'unknown' ), array( 'instock', 'outofstock' ), true ) ) {
			throw new RuntimeException( 'Stock del proveedor sin verificar: se cancela la importación de esta referencia.' );
		}


		$variations = self::usable_variations( $payload );
		$declared_variations = isset( $payload['variation_count'] ) ? (int) $payload['variation_count'] : 0;
		if ( $declared_variations > 1 && ( ! $variations ||
			( in_array( (string) $supplier['code'], array( 'chocolates-artesanos-isabel', 'tropitop', 'quesos-pajarete', 'entresetas' ), true ) && count( $variations ) < $declared_variations ) ) ) {
			throw new RuntimeException( 'El producto tiene opciones/variantes, pero EMDO aún no dispone de la matriz completa de precios y stock. No se importará hasta evitar una ficha incorrecta.' );
		}

		$existing_id = ! empty( $row['wc_product_id'] ) ? (int) $row['wc_product_id'] : 0;
		// Recover products created by older releases that timed out BEFORE
		// persisting their Woo ID. Verify the supplier-specific SKU and author;
		// never create a second entry with that SKU or attach another vendor's.
		if ( ! $existing_id && 'tropitop' === (string) $supplier['code'] &&
			! empty( $payload['sku'] ) ) {
			$matched_id = wc_get_product_id_by_sku( sanitize_text_field( (string) $payload['sku'] ) );
			if ( $matched_id ) {
				if ( (int) get_post_field( 'post_author', $matched_id ) !== $vendor_id ) {
					throw new RuntimeException( 'Referencia Tropitop ya existente con otro vendedor: revisar antes de importar.' );
				}
				$existing_id = (int) $matched_id;
				$wpdb->update( $table, array( 'wc_product_id' => $existing_id ), array( 'id' => $source_product_id ) );
			}
		}
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
        if ( 'quesos-pajarete' === (string) $supplier['code'] ) {
            // Pajarete publishes its verified per-product synopsis via Store API.
            $source_short = wp_kses_post( (string) ( $payload['short_description'] ?? '' ) );
            $product->set_short_description( $source_short );
            // Preserve a verified source-specific editorial excerpt in Pajarete,
            // but only while the original manufacturer's short copy has not
            // changed since the SEO audit. New source facts always take priority.
            if ( $existing_id && '1' === (string) get_post_meta( $existing_id, '_mdo_pajarete_seo_short_approved', true ) ) {
                $approved_short = (string) get_post_meta( $existing_id, '_mdo_pajarete_seo_editorial_short', true );
                $original_sha = (string) get_post_meta( $existing_id, '_mdo_pajarete_seo_short_source_sha256', true );
                if ( $approved_short && $original_sha &&
                    hash_equals( $original_sha, hash( 'sha256', (string) ( $payload['short_description'] ?? '' ) ) ) ) {
                    $product->set_short_description( wp_kses_post( $approved_short ) );
                }
            }
        }

		if ( 'tropitop' === (string) $supplier['code'] ) {
			// This page is sold on EMDO, not on the producer's webshop.
			// Source fields remain untouched for a traceable future sync.
			$product->set_description( MDO_Tropitop_Editorial::description(
				(string) ( $payload['description'] ?? '' ),
				(string) $payload['title'],
				(string) ( $payload['short_description'] ?? '' )
			) );
			$product->set_short_description( MDO_Tropitop_Editorial::short_description(
				(string) ( $payload['short_description'] ?? '' )
			) );
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

		// A physical personalised Isabel item stays hidden until its local
		// product options and checkout can actually accept an order.
		if ( 'chocolates-artesanos-isabel' === (string) $supplier['code'] && $existing_id &&
			'1' === (string) get_post_meta( $existing_id, '_emdo_isabel_customization_review_required', true ) ) {
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
		}

        // A Pajarete source listing with a manufacturer-supplied uncertainty
        // note about its photograph must not be resold automatically until
        // its real image is verified. Only products explicitly flagged in
        // the scoped audit are held; clearing the flag requires approval.
        if ( 'quesos-pajarete' === (string) $supplier['code'] &&
            $existing_id && '1' === (string) get_post_meta(
                $existing_id, '_mdo_pajarete_photo_validation_required', true
            ) ) {
            $product->set_status( 'draft' );
            $product->set_catalog_visibility( 'hidden' );
        }

		// Prelaunch products from Latitudes must never become public while its
		// WCFM vendor is hard-disabled, including after nightly updates.
		if ( in_array( (string) $supplier['code'], array( 'latitudes', 'chocolates-artesanos-isabel', 'tropitop', 'quesos-pajarete', 'entresetas' ), true ) && get_user_meta( $vendor_id, '_disable_vendor', true ) ) {
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
		}
		// This producer may be prepared in WooCommerce, but cannot trade until
		// shipping, commercial agreement and media rights are explicitly signed off.
		if ( 'entresetas' === (string) $supplier['code'] &&
			( ! get_option( 'mdo_entresetas_commercial_ready', false ) ||
			  ! get_option( 'mdo_entresetas_media_authorized', false ) ) ) {
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
		}
		if ( 'tropitop' === (string) $supplier['code'] && ! MDO_Tropitop_Shipping::is_ready() ) {
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
		}
		// An enabled, confirmed Tropitop supplier is allowed to publish newly
		// imported products directly, *only* when checkout and every option are
		// fully shippable. Never require a manual flag for each new reference.
		// Unsupported unit-only packs, unknown prices or invalid shipping remain
		// drafts, rather than selling without a valid delivery quote.
		if ( 'tropitop' === (string) $supplier['code'] ) {
			$ready_to_sell = ! empty( $supplier['active'] )
				&& ! get_user_meta( $vendor_id, '_disable_vendor', true )
				&& MDO_Tropitop_Shipping::is_ready()
				&& count( $variations ) > 0
				&& count( $variations ) === $declared_variations
				&& ! empty( $payload['images'] )
				&& ! empty( $payload['description'] );
			foreach ( $variations as $source_variation ) {
				$kg = isset( $source_variation['weight_kg'] ) && is_numeric( $source_variation['weight_kg'] )
					? (float) $source_variation['weight_kg'] : 0.0;
				if ( $kg <= 0 || null === MDO_Tropitop_Shipping::cost_for_weight( $kg ) ||
					! isset( $source_variation['display_price'] ) ||
					! is_numeric( $source_variation['display_price'] ) ||
					(float) $source_variation['display_price'] <= 0 ) {
					$ready_to_sell = false;
					break;
				}
			}
			if ( ! $ready_to_sell ) {
				$product->set_status( 'draft' );
				$product->set_catalog_visibility( 'hidden' );
			}
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
        if ( 'quesos-pajarete' === (string) $supplier['code'] ) {
            $pajarete_categories = self::pajarete_category_ids( $payload );
            if ( $pajarete_categories ) {
                $product->set_category_ids( $pajarete_categories );
                $category_result = array( 'assigned' => true, 'reason' => 'pajarete_verified_source_category',
                    'category_ids' => $pajarete_categories, 'score' => null );
            }
        }
		// Bypass meat-oriented automatic categorization for mushroom products.
		if ( 'entresetas' === (string) $supplier['code'] ) {
			$categories = self::entresetas_category_ids( $payload );
			$product->set_category_ids( $categories );
			$category_result = array( 'assigned' => true, 'reason' => 'entresetas_source_family', 'category_ids' => $categories );
			if ( isset( $payload['weight_kg'] ) && is_numeric( $payload['weight_kg'] ) &&
				(float) $payload['weight_kg'] > 0 ) {
				$product->set_weight( wc_format_decimal( (float) $payload['weight_kg'] ) );
			}
			if ( ! empty( $payload['short_description'] ) ) {
				$product->set_short_description( wp_kses_post( (string) $payload['short_description'] ) );
			}
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
        // New Tropitop products become public immediately after the full
        // photo and variation commit, not midway through a slow image request.
        $publish_after_verified_import = (
                'tropitop' === (string) $supplier['code'] ||
                ( 'quesos-pajarete' === (string) $supplier['code'] &&
                  ! empty( $supplier['active'] ) &&
                  ! get_user_meta( $vendor_id, '_disable_vendor', true ) )
            ) && ! $existing_id && 'publish' === $product->get_status();
        if ( $publish_after_verified_import ) {
            $product->set_status( 'draft' );
            $product->set_catalog_visibility( 'hidden' );
        }
        $product_id = $product->save();
		if ( ! $product_id ) {
			throw new RuntimeException( 'WooCommerce no pudo guardar el producto.' );
		}

		// A network timeout during image upload must never create a second Woo
		// product on retry. Save the source-to-Woo ID immediately, before any
		// remote image request. The source stays "importing" until completion.
		$wpdb->update(
			$table,
			array( 'wc_product_id' => $product_id ),
			array( 'id' => $source_product_id ),
			array( '%d' ),
			array( '%d' )
		);

        if ($latitudes_inferred_weight && 'verified_manual' !== (string)get_post_meta($product_id,'_mdo_latitudes_weight_basis',true)) {
            update_post_meta($product_id,'_mdo_latitudes_weight_basis',(string)$latitudes_inferred_weight['basis']);
        }
        wp_update_post( array( 'ID' => $product_id, 'post_author' => $vendor_id ) );
		if ( class_exists( 'MDO_Auto_Categorizer' ) ) {
			MDO_Auto_Categorizer::record_result( $product_id, $category_result );
		}

		// Preserve source-image URLs in EMDO, but importing/copying them into
		// WordPress requires explicit permission from the rightsholder.
		$media_payload = $payload;
		if ( 'entresetas' === (string) $supplier['code'] &&
			! get_option( 'mdo_entresetas_media_authorized', false ) ) {
			$media_payload['images'] = array();
		}
		self::sync_images( $product, $media_payload );
		// A product without a real, imported cover cannot be offered for sale.
		// Keep it recoverable as a draft for the next incremental sync.
		if ( 'tropitop' === (string) $supplier['code'] && ! $product->get_image_id() ) {
			$product->set_status( 'draft' );
			$product->set_catalog_visibility( 'hidden' );
			$product->save();
		}
		if ( $is_variable ) {
			self::sync_variations( $product, $variations );
			WC_Product_Variable::sync( $product_id );
		}
		if ( class_exists( 'MDO_YITH_Extras' ) ) {
			MDO_YITH_Extras::sync_product( $product_id, $payload );
		}
		if ( $publish_after_verified_import ) {
			$fresh = wc_get_product( $product_id );
			if ( $fresh && $fresh->get_image_id() &&
				( ! $is_variable || count( $fresh->get_children() ) === count( $variations ) ) ) {
				$fresh->set_status( 'publish' );
				$fresh->set_catalog_visibility( 'visible' );
				$fresh->save();
			}
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


        // Reassert source-backed categories after third-party save hooks.
        if ( 'quesos-pajarete' === (string) $supplier['code'] && ! empty( $pajarete_categories ) ) {
            $set = wp_set_object_terms( $product_id, $pajarete_categories, 'product_cat', false );
            if ( is_wp_error( $set ) ) {
                throw new RuntimeException( 'Pajarete: error al confirmar categorías oficiales.' );
            }
            clean_post_cache( $product_id );
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


        // Tropitop's categories are derived from its validated source path.
        // A generic save-time categorizer has sometimes attached the unrelated
        // "Jamones y paletas" term to a melon pack. Reassert the exact bounded
        // fruit/vegetable category set after all normal WooCommerce save hooks.
        // No other vendor or SEO metadata is touched.
        if ( 'tropitop' === (string) $supplier['code'] ) {
            $expected = self::tropitop_category_ids( $payload, false );
            $current = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
            if ( is_wp_error( $current ) ) {
                throw new RuntimeException( 'No se pudieron verificar categorías de Tropitop.' );
            }
            $current = array_map( 'intval', (array) $current );
            $cmp_expected = $expected; sort( $cmp_expected ); sort( $current );
            if ( $cmp_expected !== $current ) {
                $set = wp_set_object_terms( $product_id, $expected, 'product_cat', false );
                if ( is_wp_error( $set ) ) {
                    throw new RuntimeException( 'No se pudieron corregir categorías de Tropitop.' );
                }
                clean_post_cache( $product_id );
            }
        }
		if ( 'entresetas' === (string) $supplier['code'] ) {
			$expected = self::entresetas_category_ids( $payload );
			$set = wp_set_object_terms( $product_id, $expected, 'product_cat', false );
			if ( is_wp_error( $set ) ) {
				throw new RuntimeException( 'EntreSetas: no se pudieron confirmar las categorías.' );
			}
		}
		return $product_id;
	}

    /**
     * Conservative semantic mapping from verified Pajarete native categories
     * to terms already present in El Mercado de Origen. No new terms created.
     * Generic marketing categories (bestsellers, featured, gourmet) are ignored.
     */
    private static function pajarete_category_ids( array $payload ): array {
        $source_names = array();
        foreach ( (array) ( $payload['source_categories'] ?? array() ) as $item ) {
            if ( is_array( $item ) && isset( $item['name'] ) ) {
                $source_names[] = remove_accents( mb_strtolower( (string) $item['name'] ) );
            }
        }
        if ( ! $source_names ) { return array(); }
        $source = implode( ' | ', $source_names );
        $title = remove_accents( mb_strtolower( (string) ( $payload['title'] ?? '' ) ) );
        $slug = '';
        // Keep the manually verified Pajarete classifications stable when its
        // catalogue is resynchronized. These explicit title families are
        // checked before generic source group/category fallbacks.
        if ( str_contains( $title, 'pack mermeladas' ) ) {
            $slug = 'packs-y-lotes';
        } elseif ( preg_match( '/^gilda\\b/u', $title ) ) {
            $slug = 'conservas';
        } elseif ( str_contains( $title, 'patatas fritas' ) ||
                   str_contains( $title, 'regana' ) ||
                   preg_match( '/\\b(?:craker|cracker)\\b/u', $title ) ) {
            $slug = 'aperitivos-y-snacks';
        } elseif ( str_contains( $title, 'membrillo' ) ) {
            $slug = 'mermeladas-y-confituras';
        } elseif ( preg_match( '/^(?:salon gourmet|quesierra) 20[0-9]{2}\\b/u', $title ) ) {
            $slug = 'packs-y-lotes';
        } elseif ( preg_match( '/queso de (?:cabra|oveja)|queso mezcla de cabra y oveja/i', $source ) ||
             preg_match( '/\\b(?:queso|mini(?:queso|sensaciones)|patrufo|curado|semicurado|gran reserva|blister)\\b/i', $title ) &&
             ! preg_match( '/\\b(?:mermelada|miel|pan|pate|carne|chorizo)\\b/i', $title ) ) {
            $slug = 'quesos';
        } elseif ( str_contains( $source, 'maridaje' ) ) {
            $slug = 'vinos-y-licores';
        } elseif ( str_contains( $source, 'conservas de pescado' ) ||
                   str_contains( $source, 'conservas vegetales' ) ) {
            $slug = 'conservas';
        } elseif ( str_contains( $source, 'embutidos' ) ||
                  ( str_contains( $source, 'conservas de caza' ) &&
                    preg_match( '/\\b(?:chorizo|salchichon|cecina|lomo|embutido)\\b/i', $title ) ) ) {
            $slug = 'embutidos-y-curados';
        } elseif ( str_contains( $source, 'conservas de caza' ) ) {
            $slug = 'conservas';
        } elseif ( str_contains( $source, 'pate' ) || preg_match( '/\\bpate\\b/i', $title ) ) {
            $slug = 'foie-pates-untables';
        } elseif ( preg_match( '/\\b(?:mermelada|confitura)\\b/i', $title ) ) {
            $slug = 'mermeladas-y-confituras';
        } elseif ( preg_match( '/\\bmiel\\b/i', $title ) ) {
            $slug = 'mieles';
        } elseif ( preg_match( '/\\b(?:pistacho|macadamia|almendra|anacardo|avellana|nueces)\\b/i', $title ) ) {
            $slug = 'frutos-secos';
        } elseif ( preg_match( '/\\baceite de oliva\\b/i', $title ) ) {
            $slug = 'aceites';
        } elseif ( str_contains( $source, 'aderezos' ) ) {
            $slug = 'condimentos-y-especias';
        } elseif ( ( str_contains( $source, 'regalos' ) || str_contains( $source, 'seleccion premiada' ) ) &&
                   preg_match( '/\\b(?:pack|lote|cesta|duo|d[uú]o)\\b/iu', $title ) ) {
            $slug = 'packs-y-lotes';
        } elseif ( str_contains( $source, 'productos elaborados' ) &&
                   preg_match( '/\\b(?:carne|cabrito|cordero)\\b/i', $title ) ) {
            $slug = 'elaborados-carnicos';
        }
        if ( '' === $slug ) { return array(); }
        $term = get_term_by( 'slug', $slug, 'product_cat' );
        return $term instanceof WP_Term ? array( (int) $term->term_id ) : array();
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
    public static function tropitop_category_ids( array $payload, bool $create_missing = true ): array {
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
        if ( in_array( $key, array( 'home-verduras', 'hortalizas', 'hortalizas-verduras', 'verduras-y-hortalizas' ), true ) ) { $key = 'verduras'; }
        if ( $key === 'disponible' ) { $key = 'packs'; }
        // Tropitop's "Gourmet" is a source merchandising bucket, not an
        // actual WooCommerce product family. Map by verified source title.
        if ( in_array( $key, array( 'gourmet', 'zumos-y-tes' ), true ) ) {
            if ( preg_match( '/\\b(?:mermeladas?|confituras?)\\b/u', $title ) ) { $key = 'mermeladas-y-confituras'; }
            elseif ( preg_match( '/\\b(?:zumos?|n[eé]ctar|limonada)\\b/u', $title ) ) { $key = 'zumos-nectares-y-limonadas'; }
            elseif ( preg_match( '/\\b(?:infusi[oó](?:n|nes)|t[eé]\\s+verde|t[eé]\\s+negro)\\b/u', $title ) ) { $key = 'tes-e-infusiones'; }
            elseif ( preg_match( '/\\b(?:miel|mieles)\\b/u', $title ) ) { $key = 'mieles'; }
            elseif ( preg_match( '/\\b(?:almendra|almendras|avellana|avellanas|casta[nñ]as?|nueces)\\b/u', $title ) ) { $key = 'frutos-secos'; }
            elseif ( preg_match( '/^aceite\\s+de\\s+oliva\\b/u', $title ) && ! preg_match( '/\\b(?:pack|cesta|lote)\\b/u', $title ) ) { $key = 'aceites'; }
            elseif ( preg_match( '/\\bd[aá]tiles?\\b/u', $title ) ) { $key = 'frutas-del-mundo'; }
            elseif ( preg_match( '/\\bpat[eé]s?\\s+de\\s+aceituna\\b/u', $title ) ) { $key = 'foie-pates-untables'; }
            elseif ( preg_match( '/\\b(?:tomates?\\s+raf\\s+y\\s+aceite|pack\\s+mediterr[aá]neo|cesta\\s+(?:desayuno|regalo))\\b/u', $title ) ) { $key = 'packs'; }
            else { throw new RuntimeException( 'Gourmet Tropitop sin familia verificada: ' . $title ); }
        }
        if ( in_array( $key, array( 'cosmetica', 'cosmetica-natural' ), true ) ) { $key = 'cosmetica-natural'; }
        $map = array(
            'aguacates' => array( 'Aguacates', 'aguacates', 'frutas' ),
            'frutas' => array( 'Frutas', 'frutas', '' ),
            'frutas-tropicales' => array( 'Frutas tropicales', 'frutas-tropicales', 'frutas' ),
            'frutas-del-mundo' => array( 'Frutas del mundo', 'frutas-del-mundo', 'frutas' ),
            'verduras' => array( 'Hortalizas/Verduras', 'hortalizas-verduras', '' ),
            'mermeladas-y-confituras' => array( 'Mermeladas y confituras', 'mermeladas-y-confituras', 'conservas' ),
            'bebidas' => array( 'Bebidas', 'bebidas', '' ),
            'zumos-nectares-y-limonadas' => array( 'Zumos, néctares y limonadas', 'zumos-nectares-y-limonadas', 'bebidas' ),
            'tes-e-infusiones' => array( 'Tés e infusiones', 'tes-e-infusiones', 'bebidas' ),
            'frutos-secos' => array( 'Frutos secos', 'frutos-secos', '' ),
            'mieles' => array( 'Mieles', 'mieles', '' ),
            'conservas' => array( 'Conservas', 'conservas', '' ),
            'aceites' => array( 'Aceites', 'aceites', '' ),
            'foie-pates-untables' => array( 'Foie, patés y untables', 'foie-pates-untables', '' ),
            'packs' => array( 'Packs y lotes', 'packs-y-lotes', '' ),
            'cajas-de-temporada' => array( 'Cajas de temporada', 'cajas-de-temporada', 'packs-y-lotes' ),
            'cestas-de-fruta' => array( 'Cestas de fruta', 'cestas-de-fruta', 'packs-y-lotes' ),
            'citricos' => array( 'Cítricos', 'citricos', 'frutas' ),
            'mangos' => array( 'Mangos', 'mangos', 'frutas' ),
            'bio' => array( 'Productos BIO', 'productos-bio', '' ),
            'apadrina-un-arbol' => array( 'Apadrina un árbol', 'apadrina-un-arbol', '' ),
            'cosmetica-natural' => array( 'Cosmética natural', 'cosmetica-natural', '' ),
        );
        if ( ! isset( $map[ $key ] ) ) {
            throw new RuntimeException( 'Categoría Tropitop no verificada: ' . $key );
        }
        $target = $map[ $key ];
        $parent_id = 0;
        if ( $target[2] ) {
            $parent = get_term_by( 'slug', $target[2], 'product_cat' );
            if ( ! $parent instanceof WP_Term && isset( $map[ $target[2] ] ) && $create_missing ) {
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
        // Canonical vegetable taxonomy predates this importer: never
        // recreate a competing root if the shop term was removed.
        if ( 'verduras' === $key && ! $term instanceof WP_Term ) {
            throw new RuntimeException( 'Falta la categoría existente Hortalizas/Verduras.' );
        }
        if ( ! $term instanceof WP_Term && ! $create_missing ) {
            throw new RuntimeException( 'Categoría aún no existente: ' . $target[1] );
        }
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
        // Keep broad category navigation consistent with other producers.
        // Child terms Aguacates and Mangos remain more precise filters.
        if ( $parent_id > 0 && in_array( $key, array( 'aguacates', 'mangos', 'mermeladas-y-confituras', 'zumos-nectares-y-limonadas', 'tes-e-infusiones', 'frutas-del-mundo' ), true ) ) {
            $ids[] = $parent_id;
        }
        if ( $is_pack && 'packs' !== $key && 'cajas-de-temporada' !== $key ) {
            $pack = get_term_by( 'slug', 'packs-y-lotes', 'product_cat' );
            if ( $pack instanceof WP_Term ) { $ids[] = (int) $pack->term_id; }
        }
        // Mixed product packs retain the relevant family plus the pack archive.
        if ( 'packs' === $key && preg_match( '/tomates?\\s+raf\\s+y\\s+aceite|pack\\s+mediterr[aá]neo/u', $title ) ) {
            $vegetables = get_term_by( 'slug', 'hortalizas-verduras', 'product_cat' );
            if ( $vegetables instanceof WP_Term ) { $ids[] = (int) $vegetables->term_id; }
            if ( preg_match( '/pack\\s+mediterr[aá]neo/u', $title ) ) {
                $frutas = get_term_by( 'slug', 'frutas', 'product_cat' );
                if ( $frutas instanceof WP_Term ) { $ids[] = (int) $frutas->term_id; }
            }
        }
        // Avoid classifying overseas roots as fruit just because of their
        // source "frutas-del-mundo" shelf.
        if ( 'frutas-del-mundo' === $key && preg_match( '/\\b(?:yuca|c[uú]rcuma|jengibre)\\b/u', $title ) ) {
            $vegetables = get_term_by( 'slug', 'hortalizas-verduras', 'product_cat' );
            if ( $vegetables instanceof WP_Term ) { return array( (int) $vegetables->term_id ); }
        }
        return array_values( array_unique( $ids ) );
    }


	/** Producer-specific taxonomy; unknown names are held for review, never guessed. */
	private static function entresetas_category_ids( array $payload ): array {
		$source_host = strtolower( (string) wp_parse_url( (string) ( $payload['source_url'] ?? '' ), PHP_URL_HOST ) );
		if ( ! in_array( $source_host, array( 'entresetas.com', 'www.entresetas.com' ), true ) ) {
			throw new RuntimeException( 'EntreSetas: origen inválido para clasificación.' );
		}
		$title = remove_accents( mb_strtolower( (string) ( $payload['title'] ?? '' ), 'UTF-8' ) );
		if ( preg_match( '/\b(?:extracto|reishi|melena de leon|cordyceps)\b/u', $title ) ) {
			$label = 'Extractos de setas'; $slug = 'extractos-de-setas';
		} elseif ( preg_match( '/\b(?:matcha|infusion|te de setas)\b/u', $title ) ) {
			$label = 'Infusiones y tés de setas'; $slug = 'infusiones-y-tes-de-setas';
		} elseif ( preg_match( '/\b(?:harina|sal de|sal con|polvo)\b/u', $title ) ) {
			$label = 'Harinas y sales de setas'; $slug = 'entresetas-harinas-sales';
		} elseif ( preg_match( '/(?:deshidratad|boletus edulis|trompeta de los muertos|colmenillas|angula de monte|niscalos)/u', $title ) ) {
			$label = 'Setas deshidratadas'; $slug = 'setas-deshidratadas';
		} elseif ( preg_match( '/(?:salsa|vinagre|mousse|en su jugo|conserva)/u', $title ) ) {
			$label = 'Conservas y elaborados de setas'; $slug = 'conservas-de-setas';
		} elseif ( preg_match( '/(?:fresc|ostra|shiitake|portobello|trufa|caja de setas)/u', $title ) ) {
			$label = 'Setas frescas'; $slug = 'setas-frescas';
		} else {
			throw new RuntimeException( 'EntreSetas: categoría no comprobada para ' . $title );
		}
		$root = get_term_by( 'slug', 'setas-y-hongos', 'product_cat' );
		if ( ! $root ) {
			$created = wp_insert_term( 'Setas y hongos', 'product_cat', array( 'slug' => 'setas-y-hongos' ) );
			if ( is_wp_error( $created ) ) { throw new RuntimeException( $created->get_error_message() ); }
			$root = get_term_by( 'id', (int) $created['term_id'], 'product_cat' );
		}
		if ( ! $root ) { throw new RuntimeException( 'EntreSetas: no se pudo crear Setas y hongos.' ); }
		$child = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $child ) {
			$created = wp_insert_term( $label, 'product_cat', array( 'slug' => $slug, 'parent' => (int) $root->term_id ) );
			if ( is_wp_error( $created ) ) { throw new RuntimeException( $created->get_error_message() ); }
			$child = get_term_by( 'id', (int) $created['term_id'], 'product_cat' );
		}
		if ( ! $child ) { throw new RuntimeException( 'EntreSetas: no se pudo clasificar.' ); }
		return array( (int) $root->term_id, (int) $child->term_id );
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
			// Keep an internal audit trail for merchant-authorized approximate
			// shipping weights; customer-facing units/prices stay source-exact.
			if ( ! empty( $source_variation['shipping_weight_estimated'] ) ) {
				update_post_meta( $variation_id, '_mdo_tropitop_weight_estimated', '1' );
				update_post_meta( $variation_id, '_mdo_tropitop_weight_basis',
					sanitize_text_field( (string) ( $source_variation['shipping_weight_basis'] ?? '' ) ) );
			} else {
				delete_post_meta( $variation_id, '_mdo_tropitop_weight_estimated' );
				delete_post_meta( $variation_id, '_mdo_tropitop_weight_basis' );
			}
			$seen[ $source_id ] = true;
		}

		foreach ( $existing as $source_id => $variation_id ) {
			if ( ! isset( $seen[ $source_id ] ) ) {
				wp_delete_post( (int) $variation_id, true );
			}
		}
	}

	private static function sync_images( WC_Product $product, array $payload ): void {
		$urls = isset( $payload['images'] ) && is_array( $payload['images'] )
			? array_values( array_unique( array_filter( array_map( 'esc_url_raw', $payload['images'] ) ) ) )
			: array();
		if ( ! $urls ) { return; }
		$max_images = 'tropitop.com' === strtolower( (string) wp_parse_url( (string) ( $payload['source_url'] ?? '' ), PHP_URL_HOST ) ) ? 20 : 12;
		$urls = array_slice( $urls, 0, $max_images );
		$map = json_decode( (string) get_post_meta( $product->get_id(), '_emdo_image_map', true ), true );
		$map = is_array( $map ) ? $map : array();
		$ids = array();
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( $urls as $url ) {
			$aid = isset( $map[ $url ] ) ? absint( $map[ $url ] ) : 0;
			if ( $aid && 'attachment' !== get_post_type( $aid ) ) { $aid = 0; }
			if ( ! $aid ) {
				// download_url() supports a short per-image timeout. The default
				// 300-second sideload can block an entire importer batch.
				$tmp = download_url( $url, 12 );
				if ( is_wp_error( $tmp ) ) { continue; }
				$bytes = (int) filesize( $tmp );
				$info = @getimagesize( $tmp );
				if ( $bytes <= 0 || $bytes > 14000000 || ! $info ||
					! in_array( (string) ( $info['mime'] ?? '' ), array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
					@unlink( $tmp );
					continue;
				}
				$path = (string) wp_parse_url( $url, PHP_URL_PATH );
				$file = array( 'name' => sanitize_file_name( basename( $path ) ), 'tmp_name' => $tmp, 'size' => $bytes, 'error' => 0 );
				$result = media_handle_sideload( $file, $product->get_id(), $product->get_name() );
				if ( is_wp_error( $result ) ) { @unlink( $tmp ); continue; }
				$aid = absint( $result );
				update_post_meta( $aid, '_source_url', esc_url_raw( $url ) );
				$map[ $url ] = $aid;
				// Persist the checkpoint after EVERY valid image; a timeout
				// never invalidates finished uploads or triggers duplicate work.
				update_post_meta( $product->get_id(), '_emdo_image_map', wp_json_encode( $map, JSON_UNESCAPED_SLASHES ) );
			}
			$ids[] = $aid;
		}
		if ( ! $ids ) { return; }
		$cover = (int) $product->get_image_id();
		if ( ! $cover ) {
			$cover = (int) array_shift( $ids );
			$product->set_image_id( $cover );
		}
		// Existing galleries may already contain additional correct photos:
		// never erase them simply because today's source request was partial.
		$gallery = array_map( 'intval', $product->get_gallery_image_ids() );
		foreach ( $ids as $aid ) {
			if ( $aid !== $cover && ! in_array( $aid, $gallery, true ) ) { $gallery[] = $aid; }
		}
		$product->set_gallery_image_ids( array_values( array_unique( $gallery ) ) );
		$product->save();
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
