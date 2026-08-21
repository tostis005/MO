<?php

if ( ! class_exists( 'MDO_Huerta_Unit_Price' ) ) {
	fwrite( STDERR, "ERROR: MDO_Huerta_Unit_Price no está cargada.\n" );
	exit( 20 );
}

/**
 * Detector deliberadamente byte-safe: la web de origen mezcla históricamente
 * cabeceras/entidades y codificaciones, así que no dependemos de que el HTML
 * sea UTF-8 válido para reconocer la señal comercial €/kg.
 */
function emdo_huerta_source_is_per_kg( string $url ): bool {
	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 25,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (compatible; EMDO/' . ( defined( 'MDO_SUPPLIER_SYNC_VERSION' ) ? MDO_SUPPLIER_SYNC_VERSION : '1.0' ) . '; +https://www.elmercadodeorigen.com/)',
			'headers'     => array(
				'Accept'          => 'text/html,application/xhtml+xml',
				'Accept-Language' => 'es-ES,es;q=0.9',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		throw new RuntimeException( $response->get_error_message() );
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 400 ) {
		throw new RuntimeException( 'HTTP ' . $status . ' al consultar la ficha original.' );
	}
	$body = (string) wp_remote_retrieve_body( $response );
	if ( '' === trim( $body ) ) {
		throw new RuntimeException( 'La ficha original devolvió HTML vacío.' );
	}

	$variants = array( $body );
	$decoded = html_entity_decode( $body, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$variants[] = $decoded;
	$variants[] = html_entity_decode( $decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	foreach ( $variants as $variant ) {
		/* Señales explícitas. No se activa por textos de peso como "caja de 7 kg". */
		$variant = str_replace( array( "\xC2\xA0", "\xA0" ), ' ', (string) $variant );
		$patterns = array(
			'~(?:€|&euro;|&#0*8364;|&#x0*20ac;|\x80|â‚¬)\s*(?:/|por)\s*(?:kg|kilo(?:gramo)?s?)~i',
			'~(?:eur|euros?)\s*(?:/|por)\s*(?:kg|kilo(?:gramo)?s?)~i',
			'~precio\s+(?:por|/)?\s*(?:kg|kilo(?:gramo)?s?)~i',
		);
		foreach ( $patterns as $pattern ) {
			if ( 1 === preg_match( $pattern, $variant ) ) {
				return true;
			}
		}

		$plain = wp_strip_all_tags( $variant );
		$plain = str_replace( array( "\xC2\xA0", "\xA0" ), ' ', (string) $plain );
		foreach ( $patterns as $pattern ) {
			if ( 1 === preg_match( $pattern, $plain ) ) {
				return true;
			}
		}
	}
	return false;
}

function emdo_huerta_remove_unit_label( string $content ): string {
	$content = (string) preg_replace( '~\s*<p\b[^>]*class=["\'][^"\']*\bemdo-source-unit-price\b[^"\']*["\'][^>]*>.*?</p>\s*~is', "\n", $content );
	return trim( $content );
}

global $wpdb;
$ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT DISTINCT p.ID
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
		WHERE p.post_type = 'product'
		AND pm.meta_key = '_emdo_source_url'
		AND pm.meta_value LIKE %s
		ORDER BY p.ID ASC",
		'%lahuertadeanamary.com%'
	)
) ?: array();

$stats = array(
	'scanned'      => 0,
	'per_kg'       => 0,
	'not_per_kg'   => 0,
	'changed'      => 0,
	'errors'       => 0,
	'per_kg_items' => array(),
	'error_items'  => array(),
);

foreach ( $ids as $raw_id ) {
	$product_id = absint( $raw_id );
	if ( ! $product_id ) {
		continue;
	}
	$url = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( ! in_array( $host, array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' ), true ) ) {
		continue;
	}

	$stats['scanned']++;
	$before = (string) get_post_meta( $product_id, '_emdo_huerta_price_basis', true );
	try {
		$is_per_kg = emdo_huerta_source_is_per_kg( $url );
		if ( $is_per_kg ) {
			$stats['per_kg']++;
			$stats['per_kg_items'][] = array(
				'product_id' => $product_id,
				'title'      => get_the_title( $product_id ),
				'source_url' => esc_url_raw( $url ),
			);
			update_post_meta( $product_id, '_emdo_huerta_price_basis', 'kg' );
			/* Dispara la política de descripción (95) y después la reposición del
			 * rótulo por MDO_Huerta_Unit_Price (160), actualizando también canonical. */
			wp_update_post( array( 'ID' => $product_id ) );
			if ( 'kg' !== $before ) {
				$stats['changed']++;
			}
		} else {
			$stats['not_per_kg']++;
			delete_post_meta( $product_id, '_emdo_huerta_price_basis' );
			wp_update_post( array( 'ID' => $product_id ) );

			/* La política sanea el español; limpiamos también cualquier resto en
			 * traducción inglesa por si una pasada anterior hubiera puesto la marca. */
			$english = (string) get_post_meta( $product_id, '_en_US_post_content', true );
			$english_clean = emdo_huerta_remove_unit_label( $english );
			if ( $english_clean !== $english ) {
				update_post_meta( $product_id, '_en_US_post_content', $english_clean );
			}
			if ( '' !== $before ) {
				$stats['changed']++;
			}
		}
	} catch ( Throwable $error ) {
		$stats['errors']++;
		$stats['error_items'][] = array(
			'product_id' => $product_id,
			'url'        => esc_url_raw( $url ),
			'error'      => sanitize_text_field( $error->getMessage() ),
		);
	}
}

update_option( 'mdo_huerta_unit_price_audit_stats', $stats, false );
update_option( 'mdo_huerta_unit_price_audit_at', current_time( 'mysql', true ), false );

echo 'huerta_unit_price_ok ' . wp_json_encode( $stats, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;

if ( ! empty( $stats['errors'] ) ) {
	exit( 2 );
}
