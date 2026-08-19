<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

global $wpdb;
$supplier = null;
foreach ( MDO_Supplier_Repository::all() as $candidate ) {
	if ( 'la-huerta-ana-mary' === (string) ( $candidate['connector'] ?? '' ) ) {
		$supplier = $candidate;
		break;
	}
}
if ( ! $supplier ) {
	echo wp_json_encode( array( 'error' => 'supplier_not_found' ), JSON_PRETTY_PRINT );
	exit( 2 );
}

$products_table = MDO_Database::table( 'source_products' );
$targets = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT id,title,source_product_id,source_url,source_payload,wc_product_id,status FROM {$products_table} WHERE supplier_id=%d AND source_product_id IN ('32','1','73','113') ORDER BY id",
		(int) $supplier['id']
	),
	ARRAY_A
) ?: array();

function emdo_diag_abs( string $raw, string $base ): string {
	$raw = trim( html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( '' === $raw || str_starts_with( $raw, 'data:' ) || str_starts_with( $raw, 'javascript:' ) ) return '';
	if ( str_starts_with( $raw, '//' ) ) return 'https:' . $raw;
	if ( preg_match( '~^https?://~i', $raw ) ) return $raw;
	$b = wp_parse_url( $base );
	if ( empty( $b['host'] ) ) return '';
	if ( str_starts_with( $raw, '/' ) ) return ( $b['scheme'] ?? 'https' ) . '://' . $b['host'] . $raw;
	return ( $b['scheme'] ?? 'https' ) . '://' . $b['host'] . '/' . ltrim( $raw, '/' );
}

$out = array(
	'plugin_version' => MDO_SUPPLIER_SYNC_VERSION,
	'supplier_id' => (int) $supplier['id'],
	'categories' => array(),
	'products' => array(),
);

$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term ) {
		$hay = strtolower( remove_accents( $term->name . ' ' . $term->slug ) );
		if ( str_contains( $hay, 'hortal') || str_contains( $hay, 'verdura') ) {
			$out['categories'][] = array( 'term_id' => (int) $term->term_id, 'name' => $term->name, 'slug' => $term->slug, 'parent' => (int) $term->parent );
		}
	}
}

foreach ( $targets as $row ) {
	$item = array(
		'id' => (int) $row['id'],
		'source_product_id' => (string) $row['source_product_id'],
		'title' => $row['title'],
		'source_url' => $row['source_url'],
		'status' => $row['status'],
		'wc_product_id' => $row['wc_product_id'],
	);
	$payload = json_decode( (string) $row['source_payload'], true );
	$item['payload_images'] = is_array( $payload ) ? ( $payload['images'] ?? array() ) : array();
	$item['payload_description'] = is_array( $payload ) ? wp_strip_all_tags( (string) ( $payload['description'] ?? '' ) ) : '';
	$item['payload_description_length'] = strlen( $item['payload_description'] );

	$response = wp_remote_get( $row['source_url'], array( 'timeout' => 25, 'redirection' => 5, 'user-agent' => 'Mozilla/5.0 (compatible; EMDO diagnostic/1.0.15)' ) );
	if ( is_wp_error( $response ) ) {
		$item['fetch_error'] = $response->get_error_message();
		$out['products'][] = $item;
		continue;
	}
	$item['http'] = (int) wp_remote_retrieve_response_code( $response );
	$item['content_type'] = wp_remote_retrieve_header( $response, 'content-type' );
	$html = (string) wp_remote_retrieve_body( $response );
	$dom = new DOMDocument();
	$prev = libxml_use_internal_errors( true );
	$dom->loadHTML( $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	$xp = new DOMXPath( $dom );
	$item['meta_images'] = array();
	foreach ( $xp->query( "//meta[contains(translate(@property,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'image') or contains(translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'image')]/@content" ) ?: array() as $node ) {
		$u = emdo_diag_abs( (string) $node->nodeValue, $row['source_url'] );
		if ( $u ) $item['meta_images'][] = $u;
	}
	$item['img_candidates'] = array();
	foreach ( $xp->query( '//img' ) ?: array() as $img ) {
		if ( ! $img instanceof DOMElement ) continue;
		$attrs = array();
		foreach ( array( 'src','data-src','data-original','data-lazy-src','srcset','alt','title','class','id' ) as $a ) {
			if ( $img->hasAttribute( $a ) ) $attrs[$a] = $img->getAttribute( $a );
		}
		$text = strtolower( remove_accents( implode( ' ', $attrs ) ) );
		if ( str_contains( $text, strtolower( remove_accents( $row['source_product_id'] ) ) ) || str_contains( $text, 'producto') || str_contains( $text, 'product') || str_contains( $text, strtolower( remove_accents( strtok( $row['title'], ' ' ) ?: '' ) ) ) ) {
			$item['img_candidates'][] = $attrs;
		}
		if ( count( $item['img_candidates'] ) >= 20 ) break;
	}
	$item['payload_image_http'] = array();
	foreach ( (array) $item['payload_images'] as $image_url ) {
		$head = wp_remote_head( $image_url, array( 'timeout' => 15, 'redirection' => 5, 'user-agent' => 'Mozilla/5.0' ) );
		$item['payload_image_http'][] = is_wp_error( $head )
			? array( 'url' => $image_url, 'error' => $head->get_error_message() )
			: array( 'url' => $image_url, 'status' => (int) wp_remote_retrieve_response_code( $head ), 'type' => wp_remote_retrieve_header( $head, 'content-type' ) );
	}
	$out['products'][] = $item;
}

echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
