<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Pricing {
	public static function init(): void {
		add_action( 'added_post_meta', array( __CLASS__, 'sync_woo_price_from_source_meta' ), 10, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'sync_woo_price_from_source_meta' ), 10, 4 );
		add_action( 'admin_footer', array( __CLASS__, 'enhance_admin_price_column' ), 30 );
	}

	/**
	 * Completa el payload del scraper con precio regular, precio de oferta y descuento.
	 * `price` siempre representa el precio actual que paga el cliente.
	 */
	public static function enrich_product( array $product ): array {
		$current = self::number( $product['price'] ?? null );
		$regular = self::number( $product['regular_price'] ?? null );
		$sale    = self::number( $product['sale_price'] ?? null );

		// WooCommerce expone en las variaciones display_price y display_regular_price.
		// Para el resumen del producto usamos los mínimos del catálogo de variaciones.
		if ( ! empty( $product['variations'] ) && is_array( $product['variations'] ) ) {
			$current_prices = array();
			$regular_prices = array();
			foreach ( $product['variations'] as $variation ) {
				if ( ! is_array( $variation ) ) {
					continue;
				}
				$variation_current = self::number( $variation['display_price'] ?? null );
				$variation_regular = self::number( $variation['display_regular_price'] ?? null );
				if ( null !== $variation_current ) {
					$current_prices[] = $variation_current;
				}
				if ( null !== $variation_regular ) {
					$regular_prices[] = $variation_regular;
				}
			}
			if ( $current_prices ) {
				$current = min( $current_prices );
			}
			if ( $regular_prices ) {
				$regular = min( $regular_prices );
			}
		} elseif ( ! empty( $product['source_url'] ) && ( null === $regular || null === $sale ) ) {
			/*
			 * El conector ya conoce el precio vigente y es la fuente autoritativa.
			 * La segunda lectura sólo puede completar un precio que falte o validar el
			 * precio regular de una oferta cuando el precio actual detectado coincide
			 * con el del conector. Así evitamos tomar ofertas de productos relacionados
			 * que también puedan aparecer en el HTML de la ficha.
			 */
			try {
				$detected         = self::detect_from_url( (string) $product['source_url'] );
				$detected_current = self::number( $detected['current'] ?? null );
				$detected_regular = self::number( $detected['regular'] ?? null );

				if ( null === $current && null !== $detected_current ) {
					$current = $detected_current;
				}

				if (
					null === $regular
					&& null !== $current
					&& null !== $detected_current
					&& null !== $detected_regular
					&& abs( $current - $detected_current ) < 0.005
					&& $detected_regular > $current
				) {
					$regular = $detected_regular;
				}
			} catch ( Throwable $error ) {
				// La detección complementaria nunca debe romper el scraping principal.
			}
		}

		if ( null === $current && null !== $sale ) {
			$current = $sale;
		}
		if ( null === $regular ) {
			$regular = $current;
		}

		if ( null !== $regular && null !== $current && $current < $regular ) {
			$sale = $current;
		} else {
			$sale = null;
			if ( null !== $current ) {
				$regular = $current;
			}
		}

		$product['price']         = $current;
		$product['regular_price'] = $regular;
		$product['sale_price']    = $sale;
		$product['discount_percent'] = ( null !== $sale && null !== $regular && $regular > 0 )
			? max( 0, (int) round( ( ( $regular - $sale ) / $regular ) * 100 ) )
			: 0;

		return $product;
	}

	/**
	 * El importador guarda la relación EMDO después de crear/actualizar el producto.
	 * Aprovechamos ese meta para trasladar precio regular/oferta sin acoplar esta
	 * funcionalidad al scraper ni duplicar lógica de WooCommerce.
	 */
	public static function sync_woo_price_from_source_meta( int $meta_id, int $object_id, string $meta_key, mixed $meta_value ): void {
		if ( '_emdo_source_product_id' !== $meta_key || ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$source_product_id = absint( $meta_value );
		if ( ! $source_product_id || ! $object_id ) {
			return;
		}

		$product = wc_get_product( $object_id );
		if ( ! $product || $product->is_type( 'variable' ) || $product->is_type( 'variation' ) ) {
			return;
		}

		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT source_payload, source_url FROM {$table} WHERE id = %d", $source_product_id ),
			ARRAY_A
		);
		if ( ! $row ) {
			return;
		}

		$payload = json_decode( (string) $row['source_payload'], true );
		if ( ! is_array( $payload ) ) {
			return;
		}
		if ( empty( $payload['source_url'] ) && ! empty( $row['source_url'] ) ) {
			$payload['source_url'] = (string) $row['source_url'];
		}

		// Permite importar correctamente productos analizados antes de EMDO 0.6.0.
		if ( ! array_key_exists( 'regular_price', $payload ) ) {
			$payload = self::enrich_product( $payload );
			$wpdb->update(
				$table,
				array(
					'source_payload' => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
					'source_price'   => null !== self::number( $payload['price'] ?? null ) ? number_format( (float) $payload['price'], 2, '.', '' ) : null,
				),
				array( 'id' => $source_product_id )
			);
		}

		$regular = self::number( $payload['regular_price'] ?? $payload['price'] ?? null );
		$sale    = self::number( $payload['sale_price'] ?? null );
		$current = self::number( $payload['price'] ?? null );
		if ( null === $regular && null === $current ) {
			return;
		}
		if ( null === $regular ) {
			$regular = $current;
		}
		if ( null === $current ) {
			$current = $sale ?? $regular;
		}
		if ( null === $regular || null === $current ) {
			return;
		}

		$product->set_regular_price( wc_format_decimal( $regular ) );
		if ( null !== $sale && $sale < $regular ) {
			$product->set_sale_price( wc_format_decimal( $sale ) );
			$product->set_price( wc_format_decimal( $sale ) );
		} else {
			$product->set_sale_price( '' );
			$product->set_price( wc_format_decimal( $current ) );
		}
		$product->save();
	}

	/**
	 * Mejora visual en EMDO > Productos origen sin modificar la tabla histórica:
	 * muestra precio original tachado, precio actual y porcentaje cuando hay oferta.
	 */
	public static function enhance_admin_price_column(): void {
		if ( ! is_admin() || ! isset( $_GET['page'] ) || 'mdo-supplier-sync-products' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table       = MDO_Database::table( 'source_products' );
		$supplier_id = isset( $_GET['supplier_id'] ) ? absint( $_GET['supplier_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sql         = $supplier_id
			? $wpdb->prepare( "SELECT source_payload, source_price FROM {$table} WHERE supplier_id = %d ORDER BY id DESC LIMIT 500", $supplier_id )
			: "SELECT source_payload, source_price FROM {$table} ORDER BY id DESC LIMIT 500";
		$rows        = $wpdb->get_results( $sql, ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$prices      = array();

		foreach ( $rows as $row ) {
			$payload = json_decode( (string) $row['source_payload'], true );
			$payload = is_array( $payload ) ? $payload : array();
			$current = self::number( $payload['price'] ?? $row['source_price'] ?? null );
			$regular = self::number( $payload['regular_price'] ?? null );
			$sale    = self::number( $payload['sale_price'] ?? null );
			$discount = isset( $payload['discount_percent'] ) ? absint( $payload['discount_percent'] ) : 0;
			$prices[] = array(
				'current'  => $current,
				'regular'  => $regular,
				'sale'     => $sale,
				'discount' => $discount,
			);
		}
		?>
		<script>
		(function () {
			const prices = <?php echo wp_json_encode( $prices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>;
			const table = document.querySelector('.mdo-sync-wrap .mdo-panel table.widefat');
			if (!table || !prices.length) return;
			const headers = Array.from(table.querySelectorAll('thead th'));
			const priceIndex = headers.findIndex(th => th.textContent.trim() === 'Precio');
			if (priceIndex < 0) return;
			const rows = Array.from(table.querySelectorAll('tbody tr'));
			const money = value => Number(value).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
			rows.forEach((row, index) => {
				const data = prices[index];
				const cell = row.children[priceIndex];
				if (!data || !cell || data.current === null) return;
				if (data.sale !== null && data.regular !== null && Number(data.sale) < Number(data.regular)) {
					cell.innerHTML = '<del style="opacity:.65;">' + money(data.regular) + '</del><br><strong>' + money(data.sale) + '</strong>' + (data.discount ? '<br><small>-' + data.discount + '%</small>' : '');
				} else {
					cell.textContent = money(data.current);
				}
			});
		})();
		</script>
		<?php
	}

	private static function detect_from_url( string $url ): array {
		$url = esc_url_raw( $url );
		if ( ! $url ) {
			return array( 'current' => null, 'regular' => null );
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 18,
				'redirection' => 4,
				'user-agent'   => 'EMDO Pricing/' . MDO_SUPPLIER_SYNC_VERSION . ' (+https://www.elmercadodeorigen.com/)',
				'headers'      => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 400 ) {
			throw new RuntimeException( 'HTTP ' . $status );
		}
		$html = (string) wp_remote_retrieve_body( $response );
		if ( '' === trim( $html ) ) {
			return array( 'current' => null, 'regular' => null );
		}

		$dom = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		$xpath = new DOMXPath( $dom );

		// WooCommerce: <del>precio original</del> <ins>precio actual</ins>.
		$price_nodes = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' summary ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' price ')]" );
		foreach ( $price_nodes ?: array() as $node ) {
			$del = $xpath->query( './/del', $node );
			$ins = $xpath->query( './/ins', $node );
			if ( $del && $del->length && $ins && $ins->length ) {
				$regular = self::first_price_in_text( (string) $del->item( 0 )->textContent );
				$current = self::first_price_in_text( (string) $ins->item( 0 )->textContent );
				if ( null !== $regular && null !== $current && $current < $regular ) {
					return array( 'current' => $current, 'regular' => $regular );
				}
			}
		}

		// Fallback WooCommerce por si el tema mueve la zona de precio fuera de summary.
		foreach ( $xpath->query( '//del' ) ?: array() as $del_node ) {
			$parent = $del_node->parentNode;
			if ( ! $parent ) {
				continue;
			}
			$ins = $xpath->query( './/ins', $parent );
			if ( ! $ins || ! $ins->length ) {
				continue;
			}
			$regular = self::first_price_in_text( (string) $del_node->textContent );
			$current = self::first_price_in_text( (string) $ins->item( 0 )->textContent );
			if ( null !== $regular && null !== $current && $current < $regular ) {
				return array( 'current' => $current, 'regular' => $regular );
			}
		}

		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );

		// El Catedrático / Puente Robles: "282,28 € antes 332,10 €".
		$number = '([0-9]{1,3}(?:[.\s][0-9]{3})*(?:,[0-9]{1,2})|[0-9]+(?:[.,][0-9]{1,2})?)';
		if ( preg_match( '/' . $number . '\s*€\s*antes\s*' . $number . '\s*€/iu', $text, $match ) ) {
			$current = self::parse_price( $match[1] );
			$regular = self::parse_price( $match[2] );
			if ( null !== $regular && null !== $current && $current < $regular ) {
				return array( 'current' => $current, 'regular' => $regular );
			}
		}

		// Texto de accesibilidad habitual de WooCommerce.
		if ( preg_match( '/precio original era:\s*' . $number . '\s*€.*?' . $number . '\s*€\s*el precio actual es/iu', $text, $match ) ) {
			$regular = self::parse_price( $match[1] );
			$current = self::parse_price( $match[2] );
			if ( null !== $regular && null !== $current && $current < $regular ) {
				return array( 'current' => $current, 'regular' => $regular );
			}
		}

		return array( 'current' => null, 'regular' => null );
	}

	private static function first_price_in_text( string $text ): ?float {
		if ( preg_match( '/([0-9]{1,3}(?:[.\s][0-9]{3})*(?:,[0-9]{1,2})|[0-9]+(?:[.,][0-9]{1,2})?)/u', str_replace( "\xC2\xA0", ' ', $text ), $match ) ) {
			return self::parse_price( $match[1] );
		}
		return null;
	}

	private static function parse_price( string $raw ): ?float {
		$raw = trim( str_replace( array( "\xC2\xA0", ' ' ), '', $raw ) );
		if ( str_contains( $raw, ',' ) ) {
			$raw = str_replace( '.', '', $raw );
			$raw = str_replace( ',', '.', $raw );
		} elseif ( substr_count( $raw, '.' ) > 1 ) {
			$raw = str_replace( '.', '', $raw );
		}
		return is_numeric( $raw ) ? (float) $raw : null;
	}

	private static function number( mixed $value ): ?float {
		if ( null === $value || '' === $value ) {
			return null;
		}
		if ( is_numeric( $value ) ) {
			return (float) $value;
		}
		return self::parse_price( (string) $value );
	}
}
