<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Connector_Huerta_Ana_Mary {
	private const MAX_CATALOG_PAGES = 25;

	public static function discover( array $supplier ): array {
		$catalog   = self::catalog_url( (string) $supplier['source_url'] );
		$queue     = array( $catalog );
		$visited   = array();
		$products  = array();
		$excluded  = array();
		$fragments = self::exclusion_fragments( $supplier );

		while ( $queue && count( $visited ) < self::MAX_CATALOG_PAGES ) {
			$url = self::canonical_url( array_shift( $queue ) );
			if ( ! $url || isset( $visited[ $url ] ) ) {
				continue;
			}
			$visited[ $url ] = true;
			$xpath = self::xpath( self::fetch_html( $url ) );
			foreach ( $xpath->query( '//a[@href]' ) ?: array() as $link ) {
				$href = self::absolute_url( (string) $link->getAttribute( 'href' ), $url );
				if ( ! $href ) {
					continue;
				}
				if ( self::is_product_url( $href, $catalog ) ) {
					$href = self::canonical_url( $href );
					if ( self::is_excluded( $href, $fragments ) ) {
						$excluded[ $href ] = $href;
					} else {
						$products[ $href ] = $href;
					}
				} elseif ( self::is_catalog_page_url( $href, $catalog ) ) {
					$href = self::canonical_url( $href );
					if ( ! isset( $visited[ $href ] ) ) {
						$queue[] = $href;
					}
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
		$url   = self::canonical_url( $url );
		$xpath = self::xpath( self::fetch_html( $url ) );
		$json  = self::product_json_ld( $xpath );
		$id    = self::product_id( $url );
		$title = self::first_non_empty(
			isset( $json['name'] ) ? wp_strip_all_tags( (string) $json['name'] ) : '',
			self::meta( $xpath, 'property', 'og:title' ),
			self::heading_title( $xpath, $url ),
			self::xpath_text( $xpath, '//title[1]' )
		);
		$title = preg_replace( '/\s+' . preg_quote( $id, '/' ) . '\s*$/u', '', trim( explode( '|', $title, 2 )[0] ) ) ?: $title;
		$title = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $title ) ) );
		if ( '' === $title ) {
			throw new RuntimeException( 'No se pudo detectar el título del producto.' );
		}

		$price = self::price_from_json( $json );
		if ( null === $price ) {
			$price = self::price_from_html( $xpath );
		}
		if ( null === $price ) {
			throw new RuntimeException( 'No se pudo detectar un precio válido para el producto.' );
		}

		$description = self::description( $xpath, $json, $title );
		$images      = self::images( $xpath, $json, $url, $title );
		$payload     = array(
			'title'             => $title,
			'price'             => $price,
			'description'       => $description,
			'images'            => $images,
			'stock_status'      => self::stock_status( $xpath, $json ),
			'sku'               => isset( $json['sku'] ) ? sanitize_text_field( (string) $json['sku'] ) : '',
			'source_product_id' => $id,
			'variations'        => array(),
			'variation_count'   => 0,
			'image_count'       => count( $images ),
		);
		$hash_payload = $payload;
		unset( $hash_payload['description'] );
		$hash_payload['description_hash'] = hash( 'sha256', wp_strip_all_tags( $description ) );
		return array_merge(
			$payload,
			array(
				'source_url'  => $url,
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
		$existing   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE supplier_id = %d AND source_key = %s", $supplier_id, $source_key ), ARRAY_A );
		if ( $existing && 'excluded' === $existing['status'] ) {
			$wpdb->update( $table, array( 'last_seen_at' => $now, 'last_error' => null ), array( 'id' => (int) $existing['id'] ) );
			return 'excluded';
		}
		$record = array(
			'source_url'          => $source_url,
			'source_product_id'   => sanitize_text_field( (string) ( $product['source_product_id'] ?? '' ) ) ?: null,
			'title'               => sanitize_text_field( (string) $product['title'] ),
			'source_price'        => number_format( (float) $product['price'], 2, '.', '' ),
			'source_stock_status' => sanitize_key( (string) ( $product['stock_status'] ?? 'unknown' ) ),
			'source_hash'         => (string) $product['source_hash'],
			'source_payload'      => wp_json_encode( $product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			'last_seen_at'        => $now,
			'last_error'          => null,
		);
		if ( ! $existing ) {
			$record += array( 'supplier_id' => $supplier_id, 'source_key' => $source_key, 'status' => 'pending', 'first_seen_at' => $now, 'last_changed_at' => $now );
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
				'timeout' => 25, 'redirection' => 5,
				'user-agent' => 'EMDO Catalog Sync/' . MDO_SUPPLIER_SYNC_VERSION . ' (+https://www.elmercadodeorigen.com/)',
				'headers' => array( 'Accept' => 'text/html,application/xhtml+xml', 'Accept-Language' => 'es-ES,es;q=0.9' ),
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
			if ( is_array( $data ) ) {
				$found = self::find_product_json( $data );
				if ( $found ) {
					return $found;
				}
			}
		}
		return array();
	}

	private static function find_product_json( array $data ): array {
		$types = is_array( $data['@type'] ?? null ) ? $data['@type'] : array( $data['@type'] ?? null );
		if ( in_array( 'Product', $types, true ) ) {
			return $data;
		}
		foreach ( $data as $value ) {
			if ( is_array( $value ) && ( $found = self::find_product_json( $value ) ) ) {
				return $found;
			}
		}
		return array();
	}

	private static function price_from_json( array $json ): ?float {
		$offers = $json['offers'] ?? null;
		if ( isset( $offers[0] ) && is_array( $offers[0] ) ) {
			$offers = $offers[0];
		}
		if ( ! is_array( $offers ) ) {
			return null;
		}
		foreach ( array( 'price', 'lowPrice' ) as $key ) {
			if ( isset( $offers[ $key ] ) && null !== ( $price = self::parse_price( (string) $offers[ $key ] ) ) ) {
				return $price;
			}
		}
		return null;
	}

	private static function price_from_html( DOMXPath $xpath ): ?float {
		$queries = array(
			"//*[@itemprop='price']/@content", "//*[@itemprop='price']",
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'precio')]",
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'price')]",
			"//*[contains(translate(@id,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'precio')]",
			"//*[contains(translate(@id,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'price')]",
			"//*[contains(normalize-space(.),'€/')]/following::*[1]",
		);
		foreach ( $queries as $query ) {
			foreach ( $xpath->query( $query ) ?: array() as $node ) {
				$value = $node instanceof DOMAttr ? $node->value : $node->textContent;
				if ( null !== ( $price = self::parse_price( (string) $value ) ) ) {
					return $price;
				}
			}
		}
		return null;
	}

	private static function parse_price( string $raw ): ?float {
		$text = str_replace( "\xC2\xA0", ' ', html_entity_decode( wp_strip_all_tags( $raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( preg_match( '/(\d{1,4}(?:[.,]\d{1,2})?)\s*€/u', $text, $match ) ) {
			return (float) str_replace( ',', '.', $match[1] );
		}
		if ( preg_match( '/^\s*(\d{1,4}(?:[.,]\d{1,2})?)\s*$/u', $text, $match ) ) {
			return (float) str_replace( ',', '.', $match[1] );
		}
		return null;
	}

	private static function description( DOMXPath $xpath, array $json, string $title ): string {
		if ( ! empty( $json['description'] ) ) {
			return wp_kses_post( (string) $json['description'] );
		}
		$queries = array(
			"//*[@itemprop='description']",
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'descripcion')]",
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'description')]",
			"//*[contains(translate(@id,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'descripcion')]",
			"//*[contains(translate(@id,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'description')]",
		);
		foreach ( $queries as $query ) {
			foreach ( $xpath->query( $query ) ?: array() as $node ) {
				$html = self::inner_html( $node );
				if ( mb_strlen( trim( wp_strip_all_tags( $html ) ) ) >= 40 ) {
					return wp_kses_post( $html );
				}
			}
		}
		$title_slug = sanitize_title( $title );
		foreach ( $xpath->query( '//h1|//h2|//h3|//h4|//h5' ) ?: array() as $heading ) {
			if ( ! $heading instanceof DOMElement || sanitize_title( (string) $heading->textContent ) !== $title_slug ) {
				continue;
			}
			$container = $heading;
			for ( $i = 0; $i < 4 && $container->parentNode instanceof DOMElement; $i++ ) {
				$container = $container->parentNode;
				$text = trim( preg_replace( '/\s+/u', ' ', (string) $container->textContent ) );
				if ( mb_strlen( $text ) >= 120 && mb_strlen( $text ) <= 12000 ) {
					return wp_kses_post( self::inner_html( $container ) );
				}
			}
		}
		return '';
	}

	private static function images( DOMXPath $xpath, array $json, string $base_url, string $title ): array {
		$images = array();
		$raw = isset( $json['image'] ) ? ( is_array( $json['image'] ) ? $json['image'] : array( $json['image'] ) ) : array();
		foreach ( $raw as $image ) {
			$image = is_array( $image ) ? ( $image['url'] ?? $image['contentUrl'] ?? '' ) : $image;
			self::add_image( $images, (string) $image, $base_url );
		}
		self::add_image( $images, self::meta( $xpath, 'property', 'og:image' ), $base_url );
		$title_slug = sanitize_title( $title );
		foreach ( $xpath->query( '//img[@src or @data-src]' ) ?: array() as $img ) {
			$alt = sanitize_title( trim( (string) $img->getAttribute( 'alt' ) . ' ' . (string) $img->getAttribute( 'title' ) ) );
			$title_tokens = array_filter( explode( '-', $title_slug ), static fn( string $token ): bool => mb_strlen( $token ) > 2 );
			$alt_tokens   = array_filter( explode( '-', $alt ), static fn( string $token ): bool => mb_strlen( $token ) > 2 );
			$relevant     = $alt && ( str_contains( $title_slug, $alt ) || str_contains( $alt, $title_slug ) || count( array_intersect( $title_tokens, $alt_tokens ) ) >= 2 );
			if ( $relevant ) {
				self::add_image( $images, (string) ( $img->getAttribute( 'data-src' ) ?: $img->getAttribute( 'src' ) ), $base_url );
			}
		}
		return array_values( array_filter( $images, static fn( string $image ): bool => ! preg_match( '~(?:logo|icon|paypal|visa|mastercard|sodexo|segura|banner|sprite)~i', $image ) ) );
	}

	private static function add_image( array &$images, string $raw, string $base_url ): void {
		$url = self::absolute_url( $raw, $base_url );
		if ( $url && preg_match( '~\.(?:jpe?g|png|webp|gif)(?:\?|$)~i', $url ) ) {
			$images[ $url ] = $url;
		}
	}

	private static function stock_status( DOMXPath $xpath, array $json ): string {
		$offers = $json['offers'] ?? array();
		if ( isset( $offers[0] ) && is_array( $offers[0] ) ) {
			$offers = $offers[0];
		}
		if ( is_array( $offers ) && ! empty( $offers['availability'] ) ) {
			$value = strtolower( (string) $offers['availability'] );
			if ( str_contains( $value, 'outofstock' ) || str_contains( $value, 'soldout' ) ) {
				return 'outofstock';
			}
		}
		$body = strtolower( self::xpath_text( $xpath, '//body' ) );
		return preg_match( '/\b(agotado|sin\s+stock|sin\s+existencias|no\s+disponible)\b/u', $body ) ? 'outofstock' : 'instock';
	}

	private static function heading_title( DOMXPath $xpath, string $url ): string {
		$slug = preg_replace( '/-\d+$/', '', sanitize_title( pathinfo( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ), PATHINFO_FILENAME ) ) );
		$tokens = array_filter( explode( '-', (string) $slug ), static fn( string $token ): bool => mb_strlen( $token ) > 2 );
		foreach ( $xpath->query( '//h1|//h2|//h3|//h4|//h5' ) ?: array() as $heading ) {
			$text = trim( preg_replace( '/\s+/u', ' ', (string) $heading->textContent ) );
			$heading_tokens = explode( '-', sanitize_title( $text ) );
			if ( count( array_intersect( $tokens, $heading_tokens ) ) >= 2 ) {
				return $text;
			}
		}
		return '';
	}

	private static function meta( DOMXPath $xpath, string $attribute, string $value ): string {
		$query = sprintf( "//meta[translate(@%s,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='%s']/@content", $attribute, strtolower( $value ) );
		$nodes = $xpath->query( $query );
		return $nodes && $nodes->length ? trim( (string) $nodes->item( 0 )->nodeValue ) : '';
	}

	private static function product_id( string $url ): string {
		return preg_match( '/-(\d+)\.html$/i', (string) wp_parse_url( $url, PHP_URL_PATH ), $matches ) ? sanitize_text_field( $matches[1] ) : '';
	}

	private static function catalog_url( string $url ): string {
		return self::canonical_url( $url );
	}

	private static function is_product_url( string $url, string $catalog ): bool {
		if ( strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) !== strtolower( (string) wp_parse_url( $catalog, PHP_URL_HOST ) ) ) {
			return false;
		}
		$path = self::normalize_path( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$base = rtrim( self::normalize_path( (string) wp_parse_url( $catalog, PHP_URL_PATH ) ), '/' );
		return str_starts_with( $path, $base . '/' ) && (bool) preg_match( '~-[0-9]+\.html$~i', $path );
	}

	private static function is_catalog_page_url( string $url, string $catalog ): bool {
		if ( strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) !== strtolower( (string) wp_parse_url( $catalog, PHP_URL_HOST ) ) ) {
			return false;
		}
		$path = rtrim( self::normalize_path( (string) wp_parse_url( $url, PHP_URL_PATH ) ), '/' );
		$base = rtrim( self::normalize_path( (string) wp_parse_url( $catalog, PHP_URL_PATH ) ), '/' );
		return $path === $base || (bool) preg_match( '~^' . preg_quote( $base, '~' ) . '/\d+$~', $path );
	}

	private static function canonical_url( string $url ): string {
		$parts = wp_parse_url( trim( $url ) );
		if ( empty( $parts['host'] ) ) {
			return '';
		}
		return esc_url_raw( ( $parts['scheme'] ?? 'https' ) . '://' . strtolower( $parts['host'] ) . self::normalize_path( (string) ( $parts['path'] ?? '/' ) ) );
	}

	private static function absolute_url( string $url, string $base_url ): string {
		$url = trim( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( '' === $url || str_starts_with( $url, 'data:' ) || str_starts_with( $url, 'javascript:' ) || '#' === $url[0] ) {
			return '';
		}
		if ( str_starts_with( $url, '//' ) ) {
			return self::canonical_url( 'https:' . $url );
		}
		if ( preg_match( '~^https?://~i', $url ) ) {
			return self::canonical_url( $url );
		}
		$base = wp_parse_url( $base_url );
		if ( empty( $base['host'] ) ) {
			return '';
		}
		if ( str_starts_with( $url, '/' ) ) {
			$path = $url;
		} else {
			$base_path = (string) ( $base['path'] ?? '/' );
			$basename  = basename( rtrim( $base_path, '/' ) );
			$is_file   = (bool) preg_match( '/\.[a-z0-9]{2,5}$/i', $basename );
			$is_page   = (bool) preg_match( '/^\d+$/', $basename );
			$directory = ( $is_file || $is_page ) ? dirname( $base_path ) : rtrim( $base_path, '/' );
			$path      = $directory . '/' . $url;
		}
		return self::canonical_url( ( $base['scheme'] ?? 'https' ) . '://' . $base['host'] . self::normalize_path( $path ) );
	}

	private static function normalize_path( string $path ): string {
		$out = array();
		foreach ( explode( '/', '/' . ltrim( $path, '/' ) ) as $part ) {
			if ( '' === $part || '.' === $part ) {
				continue;
			}
			if ( '..' === $part ) {
				array_pop( $out );
			} else {
				$out[] = $part;
			}
		}
		return '/' . implode( '/', $out );
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

	private static function xpath_text( DOMXPath $xpath, string $query ): string {
		$nodes = $xpath->query( $query );
		return $nodes && $nodes->length ? trim( preg_replace( '/\s+/u', ' ', (string) $nodes->item( 0 )->textContent ) ) : '';
	}

	private static function inner_html( DOMNode $node ): string {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $node->ownerDocument->saveHTML( $child );
		}
		return trim( $html );
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
