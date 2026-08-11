<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Connector_Tolecarnes {
	private const MAX_CATALOG_PAGES = 10;

	public static function discover( array $supplier ): array {
		$seed_urls = array( self::catalog_url( (string) $supplier['source_url'] ) );
		$visited   = array();
		$products  = array();
		$excluded  = array();
		$fragments = self::exclusion_fragments( $supplier );

		while ( $seed_urls && count( $visited ) < self::MAX_CATALOG_PAGES ) {
			$url = array_shift( $seed_urls );
			if ( isset( $visited[ $url ] ) ) {
				continue;
			}
			$visited[ $url ] = true;
			$html = self::fetch_html( $url );
			$xpath = self::xpath( $html );

			foreach ( $xpath->query( '//a[@href]' ) ?: array() as $link ) {
				$href = self::absolute_url( trim( (string) $link->getAttribute( 'href' ) ), $url );
				if ( ! $href ) {
					continue;
				}
				if ( self::is_product_url( $href ) ) {
					if ( self::is_excluded( $href, $fragments ) ) {
						$excluded[ self::canonical_url( $href ) ] = self::canonical_url( $href );
					} else {
						$products[ self::canonical_url( $href ) ] = self::canonical_url( $href );
					}
					continue;
				}
				if ( self::is_catalog_page_url( $href, $url ) && ! isset( $visited[ $href ] ) ) {
					$seed_urls[] = $href;
				}
			}
		}

		return array(
			'products' => array_values( $products ),
			'excluded' => array_values( $excluded ),
			'pages'    => array_keys( $visited ),
		);
	}

	public static function scrape_product( string $url ): array {
		$html  = self::fetch_html( $url );
		$xpath = self::xpath( $html );
		$json  = self::product_json_ld( $xpath );

		$title = self::first_non_empty(
			isset( $json['name'] ) ? wp_strip_all_tags( (string) $json['name'] ) : '',
			self::xpath_text( $xpath, "//h1[contains(concat(' ', normalize-space(@class), ' '), ' product_title ')]" ),
			self::xpath_text( $xpath, '//h1[1]' )
		);
		if ( '' === $title ) {
			throw new RuntimeException( 'No se pudo detectar el título del producto.' );
		}

		$variations = self::variations( $xpath );
		$price      = self::price_from_json( $json );
		if ( null === $price && $variations ) {
			$variation_prices = array_values( array_filter( array_map( static fn( array $v ) => isset( $v['display_price'] ) && is_numeric( $v['display_price'] ) ? (float) $v['display_price'] : null, $variations ), static fn( $v ) => null !== $v ) );
			if ( $variation_prices ) {
				$price = min( $variation_prices );
			}
		}
		if ( null === $price ) {
			$price = self::price_from_html( $xpath );
		}

		$description = self::first_non_empty(
			isset( $json['description'] ) ? wp_kses_post( (string) $json['description'] ) : '',
			self::xpath_html( $xpath, "//*[@id='tab-description']" ),
			self::xpath_html( $xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' woocommerce-product-details__short-description ')]" )
		);
		$images = self::images( $xpath, $json, $url );
		$stock  = self::stock_status( $xpath, $json, $variations );
		$sku    = self::first_non_empty(
			isset( $json['sku'] ) ? sanitize_text_field( (string) $json['sku'] ) : '',
			self::xpath_text( $xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' sku ')]" )
		);
		$product_id = self::product_id( $xpath, $json, $sku );

		$payload = array(
			'title'             => $title,
			'price'             => $price,
			'description'       => $description,
			'images'            => $images,
			'stock_status'      => $stock,
			'sku'               => $sku,
			'source_product_id' => $product_id,
			'variations'        => $variations,
			'variation_count'   => count( $variations ),
			'image_count'       => count( $images ),
		);
		$hash_payload = $payload;
		unset( $hash_payload['description'] );
		$hash_payload['description_hash'] = hash( 'sha256', wp_strip_all_tags( $description ) );

		return array_merge(
			$payload,
			array(
				'source_url'  => self::canonical_url( $url ),
				'source_hash' => hash( 'sha256', wp_json_encode( $hash_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
			)
		);
	}

	public static function upsert_product( int $supplier_id, array $product ): string {
		global $wpdb;
		$table      = MDO_Database::table( 'source_products' );
		$source_url = self::canonical_url( (string) $product['source_url'] );
		$source_key = hash( 'sha256', $source_url );
		$now        = current_time( 'mysql' );
		$existing   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE supplier_id = %d AND source_key = %s", $supplier_id, $source_key ),
			ARRAY_A
		);

		if ( $existing && 'excluded' === $existing['status'] ) {
			$wpdb->update( $table, array( 'last_seen_at' => $now, 'last_error' => null ), array( 'id' => (int) $existing['id'] ) );
			return 'excluded';
		}

		$record = array(
			'source_url'          => $source_url,
			'source_product_id'   => sanitize_text_field( (string) ( $product['source_product_id'] ?? '' ) ) ?: null,
			'title'               => sanitize_text_field( (string) $product['title'] ),
			'source_price'        => null !== $product['price'] ? number_format( (float) $product['price'], 2, '.', '' ) : null,
			'source_stock_status' => sanitize_key( (string) ( $product['stock_status'] ?? 'unknown' ) ),
			'source_hash'         => (string) $product['source_hash'],
			'source_payload'      => wp_json_encode( $product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			'last_seen_at'        => $now,
			'last_error'          => null,
		);

		if ( ! $existing ) {
			$record['supplier_id']    = $supplier_id;
			$record['source_key']     = $source_key;
			$record['status']         = 'pending';
			$record['first_seen_at']  = $now;
			$record['last_changed_at'] = $now;
			$wpdb->insert( $table, $record );
			return 'new';
		}

		$changed = (string) $existing['source_hash'] !== (string) $record['source_hash'];
		if ( $changed ) {
			$record['last_changed_at'] = $now;
		}
		$wpdb->update( $table, $record, array( 'id' => (int) $existing['id'] ) );
		return $changed ? 'updated' : 'unchanged';
	}

	private static function fetch_html( string $url ): string {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 20,
				'redirection' => 5,
				'user-agent'   => 'EMDO Catalog Sync/' . MDO_SUPPLIER_SYNC_VERSION . ' (+https://www.elmercadodeorigen.com/)',
				'headers'      => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 400 ) {
			throw new RuntimeException( 'HTTP ' . $status . ' al consultar ' . $url );
		}
		$html = (string) wp_remote_retrieve_body( $response );
		if ( '' === trim( $html ) ) {
			throw new RuntimeException( 'La página devolvió HTML vacío.' );
		}
		return $html;
	}

	private static function xpath( string $html ): DOMXPath {
		$dom = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return new DOMXPath( $dom );
	}

	private static function product_json_ld( DOMXPath $xpath ): array {
		foreach ( $xpath->query( "//script[contains(translate(@type,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'ld+json')]" ) ?: array() as $script ) {
			$data = json_decode( trim( (string) $script->textContent ), true );
			if ( ! is_array( $data ) ) {
				continue;
			}
			$product = self::find_product_node( $data );
			if ( $product ) {
				return $product;
			}
		}
		return array();
	}

	private static function find_product_node( array $data ): array {
		$type = $data['@type'] ?? null;
		$types = is_array( $type ) ? $type : array( $type );
		if ( in_array( 'Product', $types, true ) ) {
			return $data;
		}
		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				$found = self::find_product_node( $value );
				if ( $found ) {
					return $found;
				}
			}
		}
		return array();
	}

	private static function price_from_json( array $json ): ?float {
		$offers = $json['offers'] ?? null;
		if ( ! is_array( $offers ) ) {
			return null;
		}
		if ( isset( $offers[0] ) && is_array( $offers[0] ) ) {
			$offers = $offers[0];
		}
		foreach ( array( 'price', 'lowPrice' ) as $key ) {
			if ( isset( $offers[ $key ] ) && is_numeric( $offers[ $key ] ) ) {
				return (float) $offers[ $key ];
			}
		}
		return null;
	}

	private static function price_from_html( DOMXPath $xpath ): ?float {
		$selectors = array(
			"//*[contains(concat(' ', normalize-space(@class), ' '), ' summary ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' price ')]",
			"//*[contains(concat(' ', normalize-space(@class), ' '), ' price ')][1]",
		);
		foreach ( $selectors as $selector ) {
			$text = self::xpath_text( $xpath, $selector );
			if ( preg_match_all( '/([0-9]+(?:[.,][0-9]{1,2})?)/u', str_replace( array( "\xC2\xA0", '€' ), ' ', $text ), $matches ) && ! empty( $matches[1] ) ) {
				$value = str_replace( ',', '.', end( $matches[1] ) );
				if ( is_numeric( $value ) ) {
					return (float) $value;
				}
			}
		}
		return null;
	}

	private static function variations( DOMXPath $xpath ): array {
		$nodes = $xpath->query( "//form[contains(concat(' ', normalize-space(@class), ' '), ' variations_form ')]" );
		if ( ! $nodes || 0 === $nodes->length ) {
			return array();
		}
		$raw = html_entity_decode( (string) $nodes->item( 0 )->getAttribute( 'data-product_variations' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}
		$out = array();
		foreach ( $data as $variation ) {
			if ( ! is_array( $variation ) ) {
				continue;
			}
			$out[] = array(
				'variation_id'          => isset( $variation['variation_id'] ) ? absint( $variation['variation_id'] ) : 0,
				'attributes'            => isset( $variation['attributes'] ) && is_array( $variation['attributes'] ) ? array_map( 'sanitize_text_field', $variation['attributes'] ) : array(),
				'display_price'         => isset( $variation['display_price'] ) && is_numeric( $variation['display_price'] ) ? (float) $variation['display_price'] : null,
				'display_regular_price' => isset( $variation['display_regular_price'] ) && is_numeric( $variation['display_regular_price'] ) ? (float) $variation['display_regular_price'] : null,
				'is_in_stock'           => ! empty( $variation['is_in_stock'] ),
				'image'                 => isset( $variation['image']['full_src'] ) ? esc_url_raw( (string) $variation['image']['full_src'] ) : '',
			);
		}
		return $out;
	}

	private static function images( DOMXPath $xpath, array $json, string $base_url ): array {
		$images = array();
		if ( isset( $json['image'] ) ) {
			$raw = is_array( $json['image'] ) ? $json['image'] : array( $json['image'] );
			foreach ( $raw as $image ) {
				if ( is_array( $image ) ) {
					$image = $image['url'] ?? $image['contentUrl'] ?? '';
				}
				$url = self::absolute_url( (string) $image, $base_url );
				if ( $url ) {
					$images[ $url ] = $url;
				}
			}
		}
		foreach ( $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' woocommerce-product-gallery ')]//img" ) ?: array() as $img ) {
			foreach ( array( 'data-large_image', 'data-src', 'src' ) as $attr ) {
				$url = self::absolute_url( (string) $img->getAttribute( $attr ), $base_url );
				if ( $url ) {
					$images[ $url ] = $url;
					break;
				}
			}
		}
		return array_values( array_filter( $images, static fn( string $url ): bool => ! preg_match( '~/(logo|icon|avatar)[^/]*\.(?:jpe?g|png|webp|gif|svg)(?:\?|$)~i', $url ) ) );
	}

	private static function stock_status( DOMXPath $xpath, array $json, array $variations ): string {
		$offers = $json['offers'] ?? array();
		if ( isset( $offers[0] ) && is_array( $offers[0] ) ) {
			$offers = $offers[0];
		}
		if ( is_array( $offers ) && ! empty( $offers['availability'] ) ) {
			$availability = strtolower( (string) $offers['availability'] );
			if ( str_contains( $availability, 'instock' ) ) {
				return 'instock';
			}
			if ( str_contains( $availability, 'outofstock' ) || str_contains( $availability, 'soldout' ) ) {
				return 'outofstock';
			}
		}
		if ( $variations ) {
			return array_filter( $variations, static fn( array $v ): bool => ! empty( $v['is_in_stock'] ) ) ? 'instock' : 'outofstock';
		}
		$text = strtolower( self::xpath_text( $xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' stock ')][1]" ) );
		if ( str_contains( $text, 'agotado' ) || str_contains( $text, 'sin existencias' ) || str_contains( $text, 'out of stock' ) ) {
			return 'outofstock';
		}
		return '' !== $text ? 'instock' : 'unknown';
	}

	private static function product_id( DOMXPath $xpath, array $json, string $sku ): string {
		if ( $sku ) {
			return $sku;
		}
		if ( ! empty( $json['productID'] ) ) {
			return sanitize_text_field( (string) $json['productID'] );
		}
		$nodes = $xpath->query( "//form[contains(concat(' ', normalize-space(@class), ' '), ' cart ')]" );
		if ( $nodes && $nodes->length ) {
			foreach ( array( 'data-product_id', 'data-product-id' ) as $attr ) {
				$value = trim( (string) $nodes->item( 0 )->getAttribute( $attr ) );
				if ( $value ) {
					return sanitize_text_field( $value );
				}
			}
		}
		return '';
	}

	private static function catalog_url( string $source_url ): string {
		$parts = wp_parse_url( $source_url );
		if ( empty( $parts['host'] ) ) {
			return $source_url;
		}
		$path = $parts['path'] ?? '/';
		if ( '/' === rtrim( $path, '/' ) || '' === rtrim( $path, '/' ) ) {
			$scheme = $parts['scheme'] ?? 'https';
			return $scheme . '://' . $parts['host'] . '/tienda-online/';
		}
		return $source_url;
	}

	private static function exclusion_fragments( array $supplier ): array {
		$items = json_decode( (string) ( $supplier['exclusion_url_fragments'] ?? '[]' ), true );
		return is_array( $items ) ? array_values( array_filter( array_map( 'strval', $items ) ) ) : array();
	}

	private static function is_excluded( string $url, array $fragments ): bool {
		foreach ( $fragments as $fragment ) {
			if ( '' !== $fragment && false !== stripos( $url, $fragment ) ) {
				return true;
			}
		}
		return false;
	}

	private static function is_product_url( string $url ): bool {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return (bool) preg_match( '~/(?:producto|product)/[^/]+/?$~i', $path );
	}

	private static function is_catalog_page_url( string $url, string $current_url ): bool {
		$current_host = strtolower( (string) wp_parse_url( $current_url, PHP_URL_HOST ) );
		$host         = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$path         = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( $host !== $current_host ) {
			return false;
		}
		return (bool) preg_match( '~/(?:tienda|tienda-online)(?:/page/\d+)?/?$~i', $path );
	}

	private static function canonical_url( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) ) {
			return esc_url_raw( $url );
		}
		$scheme = $parts['scheme'] ?? 'https';
		$path   = isset( $parts['path'] ) ? trailingslashit( $parts['path'] ) : '/';
		return esc_url_raw( $scheme . '://' . strtolower( $parts['host'] ) . $path );
	}

	private static function absolute_url( string $url, string $base_url ): string {
		$url = trim( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( '' === $url || str_starts_with( $url, 'data:' ) || str_starts_with( $url, 'javascript:' ) || '#' === $url[0] ) {
			return '';
		}
		if ( str_starts_with( $url, '//' ) ) {
			return 'https:' . $url;
		}
		if ( preg_match( '~^https?://~i', $url ) ) {
			return esc_url_raw( $url );
		}
		$parts = wp_parse_url( $base_url );
		if ( empty( $parts['host'] ) ) {
			return '';
		}
		$scheme = $parts['scheme'] ?? 'https';
		if ( str_starts_with( $url, '/' ) ) {
			return esc_url_raw( $scheme . '://' . $parts['host'] . $url );
		}
		$base_path = isset( $parts['path'] ) ? dirname( $parts['path'] ) : '';
		return esc_url_raw( $scheme . '://' . $parts['host'] . trailingslashit( $base_path ) . $url );
	}

	private static function xpath_text( DOMXPath $xpath, string $query ): string {
		$nodes = $xpath->query( $query );
		return $nodes && $nodes->length ? trim( preg_replace( '/\s+/u', ' ', (string) $nodes->item( 0 )->textContent ) ) : '';
	}

	private static function xpath_html( DOMXPath $xpath, string $query ): string {
		$nodes = $xpath->query( $query );
		if ( ! $nodes || ! $nodes->length ) {
			return '';
		}
		$node = $nodes->item( 0 );
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $node->ownerDocument->saveHTML( $child );
		}
		return wp_kses_post( trim( $html ) );
	}

	private static function first_non_empty( string ...$values ): string {
		foreach ( $values as $value ) {
			if ( '' !== trim( $value ) ) {
				return trim( $value );
			}
		}
		return '';
	}
}
