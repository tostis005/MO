<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Connector_Iberico_Family {
	private const MAX_IMAGES = 20;

	public static function discover( array $supplier ): array {
		$config    = self::config( (string) $supplier['connector'], (string) $supplier['source_url'] );
		if ( ! empty( $supplier['_mdo_catalog_source_only'] ) ) {
			$config['catalog_urls'] = array( (string) $supplier['source_url'] );
		}
		$products  = array();
		$excluded  = array();
		$visited   = array();
		$fragments = self::exclusion_fragments( $supplier );

		foreach ( $config['catalog_urls'] as $url ) {
			$url = self::absolute_url( $url, $config['base_url'] );
			if ( ! $url || isset( $visited[ $url ] ) ) {
				continue;
			}
			$visited[ $url ] = true;
			$html  = self::fetch_html( $url );
			$xpath = self::xpath( $html );
			foreach ( self::product_links_from_catalog( $xpath, $url, $config ) as $product_url ) {
				$product_url = self::canonical_url( $product_url );
				if ( self::is_excluded( $product_url, $fragments ) ) {
					$excluded[ $product_url ] = $product_url;
				} else {
					$products[ $product_url ] = $product_url;
				}
			}
		}

		return array(
			'products' => array_values( $products ),
			'excluded' => array_values( $excluded ),
			'pages'    => array_keys( $visited ),
		);
	}

	public static function scrape_product( string $url, array $supplier ): array {
		$config = self::config( (string) $supplier['connector'], (string) $supplier['source_url'] );
		$html   = self::fetch_html( $url );
		$xpath  = self::xpath( $html );
		$json   = self::product_json_ld( $xpath );

		if ( ! $json && ! self::has_product_markers( $xpath ) ) {
			throw new RuntimeException( 'La URL no parece ser una ficha de producto.' );
		}

		$title = self::first_non_empty(
			isset( $json['name'] ) ? wp_strip_all_tags( (string) $json['name'] ) : '',
			self::xpath_text( $xpath, '//h1[1]' )
		);
		if ( '' === $title ) {
			throw new RuntimeException( 'No se pudo detectar el título del producto.' );
		}

		$option_groups = self::option_groups( $xpath );
		$extra_groups  = self::extra_groups( $xpath, $url );
		$price         = self::price_from_json( $json );
		if ( null === $price ) {
			$price = self::price_from_meta( $xpath );
		}
		if ( null === $price ) {
			$price = self::price_from_visible_text( $xpath );
		}

		$description = self::description( $xpath, $json );
		$images      = self::images( $xpath, $json, $url );
		$stock       = self::stock_status( $xpath, $json );
		$product_id  = self::first_non_empty(
			isset( $json['sku'] ) ? sanitize_text_field( (string) $json['sku'] ) : '',
			isset( $json['productID'] ) ? sanitize_text_field( (string) $json['productID'] ) : '',
			self::meta_content( $xpath, 'product:retailer_item_id' )
		);

		$payload = array(
			'title'             => $title,
			'price'             => $price,
			'description'       => $description,
			'images'            => $images,
			'stock_status'      => $stock,
			'sku'               => isset( $json['sku'] ) ? sanitize_text_field( (string) $json['sku'] ) : '',
			'source_product_id' => $product_id,
			'option_groups'     => $option_groups,
			'extra_groups'      => $extra_groups,
			'variations'        => array(),
			'variation_count'   => self::variation_count( $option_groups ),
			'image_count'       => count( $images ),
			'connector'         => $config['key'],
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
			$record['supplier_id']     = $supplier_id;
			$record['source_key']      = $source_key;
			$record['status']          = 'pending';
			$record['first_seen_at']   = $now;
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

	private static function config( string $connector, string $source_url ): array {
		$parts  = wp_parse_url( $source_url );
		$scheme = $parts['scheme'] ?? 'https';
		$host   = $parts['host'] ?? '';
		$base   = $host ? $scheme . '://' . $host : rtrim( $source_url, '/' );

		if ( 'el-catedratico' === $connector ) {
			return array(
				'key'          => 'el-catedratico',
				'base_url'     => $base,
				'catalog_urls' => array( '/es/catalogo' ),
			);
		}
		if ( 'puente-robles' === $connector ) {
			return array(
				'key'      => 'puente-robles',
				'base_url' => $base,
				'catalog_urls' => array(
					'/es/jamones-ibericos',
					'/es/paletas-ibericas',
					'/es/jamones-duroc',
					'/es/lomos',
					'/es/embutidos',
					'/es/lotes-de-productos',
					'/es/quesos',
					'/es/destacados',
				),
			);
		}
		throw new RuntimeException( 'Conector ibérico no soportado: ' . $connector );
	}

	private static function product_links_from_catalog( DOMXPath $xpath, string $base_url, array $config ): array {
		$links = array();

		foreach ( $xpath->query( "//script[contains(translate(@type,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'ld+json')]" ) ?: array() as $script ) {
			$data = json_decode( trim( (string) $script->textContent ), true );
			if ( is_array( $data ) ) {
				self::collect_json_urls( $data, $links, $base_url );
			}
		}

		$nodes = $xpath->query(
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'product') or contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'producto') or contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'item')]"
		);
		foreach ( $nodes ?: array() as $node ) {
			$text = trim( preg_replace( '/\s+/u', ' ', (string) $node->textContent ) );
			if ( ! str_contains( $text, '€' ) && false === stripos( $text, 'Ver Producto' ) ) {
				continue;
			}
			foreach ( $xpath->query( './/a[@href]', $node ) ?: array() as $a ) {
				self::add_candidate_link( $links, (string) $a->getAttribute( 'href' ), $base_url, $config );
			}
		}

		foreach ( $xpath->query( "//a[@href][contains(translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'ver producto')]" ) ?: array() as $a ) {
			self::add_candidate_link( $links, (string) $a->getAttribute( 'href' ), $base_url, $config );
		}

		return array_values( $links );
	}

	private static function add_candidate_link( array &$links, string $href, string $base_url, array $config ): void {
		$url = self::absolute_url( $href, $base_url );
		if ( ! $url || ! self::same_host( $url, $config['base_url'] ) || ! self::looks_like_product_url( $url ) ) {
			return;
		}
		$url = self::canonical_url( $url );
		$links[ $url ] = $url;
	}

	private static function looks_like_product_url( string $url ): bool {
		$path = rtrim( strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) ), '/' );
		if ( ! str_starts_with( $path, '/es/' ) ) {
			return false;
		}
		$deny = array(
			'/es/catalogo', '/es/destacados', '/es/jamones-ibericos', '/es/paletas-ibericas', '/es/jamones-duroc',
			'/es/lomos', '/es/embutidos', '/es/lotes-de-productos', '/es/quesos', '/es/contacto', '/es/registro', '/es/login',
			'/es/carrito', '/es/cesta', '/es/checkout', '/es/mi-cuenta', '/es/aviso-legal', '/es/politica-de-privacidad',
		);
		return ! in_array( $path, $deny, true ) && 1 === substr_count( trim( $path, '/' ), '/' );
	}

	private static function collect_json_urls( array $data, array &$links, string $base_url ): void {
		$type  = $data['@type'] ?? null;
		$types = is_array( $type ) ? $type : array( $type );
		if ( in_array( 'Product', $types, true ) && ! empty( $data['url'] ) ) {
			$url = self::absolute_url( (string) $data['url'], $base_url );
			if ( $url ) {
				$links[ self::canonical_url( $url ) ] = self::canonical_url( $url );
			}
		}
		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				self::collect_json_urls( $value, $links, $base_url );
			}
		}
	}

	private static function has_product_markers( DOMXPath $xpath ): bool {
		$buy = self::xpath_text( $xpath, "//*[self::button or self::a][contains(translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'comprar')][1]" );
		return '' !== $buy || '' !== self::meta_content( $xpath, 'product:price:amount' );
	}

	private static function option_groups( DOMXPath $xpath ): array {
		$groups = array();
		foreach ( $xpath->query( '//select' ) ?: array() as $select ) {
			$name = trim( (string) $select->getAttribute( 'name' ) );
			$id   = trim( (string) $select->getAttribute( 'id' ) );
			$key  = $name ?: $id;
			if ( '' === $key || preg_match( '/quantity|cantidad|order|sort|filter/i', $key ) ) {
				continue;
			}
			$label = $id ? self::xpath_text( $xpath, "//label[@for='" . str_replace( array( "'", '"' ), '', $id ) . "'][1]" ) : '';
			if ( ! $label ) {
				$label = ucfirst( str_replace( array( '_', '-' ), ' ', preg_replace( '/^(attribute_|option_)/', '', $key ) ) );
			}
			$options = array();
			foreach ( $xpath->query( './/option', $select ) ?: array() as $option ) {
				$text  = trim( preg_replace( '/\s+/u', ' ', (string) $option->textContent ) );
				$value = trim( (string) $option->getAttribute( 'value' ) );
				if ( '' === $value && ( '' === $text || preg_match( '/seleccionar|elegir/i', $text ) ) ) {
					continue;
				}
				$data = array();
				if ( $option->hasAttributes() ) {
					foreach ( $option->attributes as $attr ) {
						if ( str_starts_with( strtolower( $attr->name ), 'data-' ) ) {
							$data[ sanitize_key( $attr->name ) ] = sanitize_text_field( (string) $attr->value );
						}
					}
				}
				$options[] = array(
					'value'    => sanitize_text_field( $value ),
					'label'    => sanitize_text_field( $text ),
					'disabled' => $option->hasAttribute( 'disabled' ),
					'data'     => $data,
				);
			}
			if ( $options ) {
				$groups[] = array( 'key' => sanitize_key( $key ), 'label' => sanitize_text_field( $label ), 'options' => $options );
			}
		}
		return $groups;
	}

	/**
	 * Extrae servicios/formato que el origen presenta fuera de la matriz de
	 * variaciones. En El Catedrático/Puente Robles aparecen como enlaces dentro
	 * de .formatos, por ejemplo "Cortado a Cuchillo (+20,00 €)".
	 */
	private static function extra_groups( DOMXPath $xpath, string $base_url ): array {
		$options = array();
		$query   = "//*[contains(concat(' ', normalize-space(@class), ' '), ' formatos ')]//ul//a[@href]";

		foreach ( $xpath->query( $query ) ?: array() as $anchor ) {
			if ( ! $anchor instanceof DOMElement ) {
				continue;
			}
			$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( (string) $anchor->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
			if ( '' === $text ) {
				continue;
			}

			$price = 0.0;
			if ( preg_match( '/\(\s*\+\s*([0-9]+(?:[.,][0-9]{1,2})?)\s*€\s*\)\s*$/u', $text, $match ) ) {
				$price = (float) str_replace( ',', '.', $match[1] );
			}
			$title = trim( html_entity_decode( (string) $anchor->getAttribute( 'title' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			$label = $title ?: trim( preg_replace( '/\s*\(\s*\+\s*[0-9]+(?:[.,][0-9]{1,2})?\s*€\s*\)\s*$/u', '', $text ) );
			if ( '' === $label ) {
				continue;
			}

			$class = strtolower( (string) $anchor->getAttribute( 'class' ) );
			if ( $anchor->parentNode instanceof DOMElement ) {
				$class .= ' ' . strtolower( (string) $anchor->parentNode->getAttribute( 'class' ) );
			}
			$source_id = sanitize_text_field( (string) $anchor->getAttribute( 'data-id' ) );
			$key       = strtolower( $label ) . '|' . number_format( $price, 2, '.', '' );
			$options[ $key ] = array(
				'value'      => $source_id ?: sanitize_title( $label ),
				'label'      => sanitize_text_field( $label ),
				'price'      => round( $price, 2 ),
				'disabled'   => str_contains( $class, 'inactivo' ) || str_contains( $class, 'disabled' ),
				'source_id'  => $source_id,
				'source_url' => self::canonical_url( self::absolute_url( (string) $anchor->getAttribute( 'href' ), $base_url ) ),
			);
		}

		if ( count( $options ) < 2 ) {
			return array();
		}
		return array(
			array(
				'key'     => 'preparacion',
				'label'   => 'Preparación',
				'type'    => 'select',
				'options' => array_values( $options ),
			),
		);
	}

	private static function variation_count( array $groups ): int {
		if ( ! $groups ) {
			return 0;
		}
		$count = 1;
		foreach ( $groups as $group ) {
			$available = count( array_filter( $group['options'], static fn( array $o ): bool => empty( $o['disabled'] ) ) );
			$count *= max( 1, $available );
			if ( $count > 5000 ) {
				return 5000;
			}
		}
		return $count;
	}

	private static function description( DOMXPath $xpath, array $json ): string {
		if ( ! empty( $json['description'] ) ) {
			return wp_kses_post( (string) $json['description'] );
		}
		foreach ( array(
			"//*[@id='descripcion']", "//*[@id='description']",
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'description')][1]",
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'descripcion')][1]",
		) as $query ) {
			$html = self::xpath_html( $xpath, $query );
			if ( $html ) {
				return $html;
			}
		}
		return '';
	}

	private static function images( DOMXPath $xpath, array $json, string $base_url ): array {
		$images = array();
		if ( isset( $json['image'] ) ) {
			$raw = is_array( $json['image'] ) ? $json['image'] : array( $json['image'] );
			foreach ( $raw as $image ) {
				if ( is_array( $image ) ) {
					$image = $image['url'] ?? $image['contentUrl'] ?? '';
				}
				self::add_image( $images, (string) $image, $base_url );
			}
		}
		self::add_image( $images, self::meta_content( $xpath, 'og:image' ), $base_url );
		$query = "//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'gallery') or contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'galeria') or contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'slider') or contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'swiper')]//img";
		foreach ( $xpath->query( $query ) ?: array() as $img ) {
			foreach ( array( 'data-large', 'data-large-image', 'data-src', 'data-zoom-image', 'src' ) as $attr ) {
				$value = trim( (string) $img->getAttribute( $attr ) );
				if ( $value ) {
					self::add_image( $images, $value, $base_url );
					break;
				}
			}
			if ( count( $images ) >= self::MAX_IMAGES ) {
				break;
			}
		}
		return array_slice( array_values( $images ), 0, self::MAX_IMAGES );
	}

	private static function add_image( array &$images, string $value, string $base_url ): void {
		$url = self::absolute_url( $value, $base_url );
		if ( ! $url || preg_match( '~/(logo|icon|avatar|flag|sprite)[^/]*\.(?:jpe?g|png|webp|gif|svg)(?:\?|$)~i', $url ) ) {
			return;
		}
		$images[ $url ] = $url;
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
			if ( str_contains( $value, 'instock' ) ) {
				return 'instock';
			}
		}
		$text = strtolower( trim( preg_replace( '/\s+/u', ' ', (string) $xpath->document->textContent ) ) );
		if ( str_contains( $text, 'producto agotado' ) || str_contains( $text, 'no disponible' ) ) {
			return 'outofstock';
		}
		return self::has_product_markers( $xpath ) ? 'instock' : 'unknown';
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

	private static function price_from_meta( DOMXPath $xpath ): ?float {
		foreach ( array( 'product:price:amount', 'og:price:amount' ) as $property ) {
			$value = str_replace( ',', '.', self::meta_content( $xpath, $property ) );
			if ( is_numeric( $value ) ) {
				return (float) $value;
			}
		}
		return null;
	}

	private static function price_from_visible_text( DOMXPath $xpath ): ?float {
		$h1 = $xpath->query( '//h1[1]' );
		if ( $h1 && $h1->length ) {
			$node = $h1->item( 0 )->parentNode;
			for ( $i = 0; $i < 3 && $node; $i++, $node = $node->parentNode ) {
				$text = trim( preg_replace( '/\s+/u', ' ', (string) $node->textContent ) );
				if ( preg_match( '/(?:desde\s*)?([0-9]{1,5}(?:[.,][0-9]{1,2})?)\s*€/iu', $text, $m ) ) {
					$value = str_replace( ',', '.', $m[1] );
					return is_numeric( $value ) ? (float) $value : null;
				}
			}
		}
		return null;
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
		$type  = $data['@type'] ?? null;
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

	private static function meta_content( DOMXPath $xpath, string $property ): string {
		$query = "//meta[translate(@property,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='" . strtolower( $property ) . "' or translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='" . strtolower( $property ) . "']/@content";
		$nodes = $xpath->query( $query );
		return $nodes && $nodes->length ? trim( (string) $nodes->item( 0 )->nodeValue ) : '';
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

	private static function same_host( string $a, string $b ): bool {
		return strtolower( (string) wp_parse_url( $a, PHP_URL_HOST ) ) === strtolower( (string) wp_parse_url( $b, PHP_URL_HOST ) );
	}

	private static function canonical_url( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) ) {
			return esc_url_raw( $url );
		}
		$scheme = $parts['scheme'] ?? 'https';
		$path   = isset( $parts['path'] ) ? rtrim( $parts['path'], '/' ) : '/';
		return esc_url_raw( $scheme . '://' . strtolower( $parts['host'] ) . ( $path ?: '/' ) );
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
