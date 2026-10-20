<?php
/**
 * Production-safe editorial and original-gallery reconciliation.
 * Run via WP-CLI in dry-run (EMDO_REPAIR_MODE=audit) or apply mode.
 * Keeps prices, shipping, variations, taxonomy, stock and featured images.
 */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_product' ) || ! class_exists( 'MDO_Database' ) ) {
	throw new RuntimeException( 'WooCommerce/EMDO no disponible.' );
}
$mode = (string) ( getenv( 'EMDO_REPAIR_MODE' ) ?: 'audit' );
if ( ! in_array( $mode, array( 'audit', 'apply' ), true ) ) { throw new RuntimeException( 'Modo no permitido.' ); }
$apply = 'apply' === $mode;
global $wpdb;
$source_table = MDO_Database::table( 'source_products' );
$supplier_table = MDO_Database::table( 'suppliers' );
$lat = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$supplier_table} WHERE code=%s", 'latitudes' ), ARRAY_A );
if ( ! $lat ) { throw new RuntimeException( 'Proveedor Latitudes no encontrado.' ); }
$rows = $wpdb->get_results(
	$wpdb->prepare( "SELECT id,wc_product_id,source_url,source_payload FROM {$source_table} WHERE supplier_id=%d AND wc_product_id>0 ORDER BY id", (int) $lat['id'] ),
	ARRAY_A
);
if ( count( $rows ) < 125 || count( $rows ) > 160 ) {
	throw new RuntimeException( 'Conteo Latitudes inesperado: ' . count( $rows ) );
}
$copy = array( 'scanned' => 0, 'updated' => 0, 'metadata' => 0, 'remaining' => 0, 'examples' => array(), 'errors' => array() );
$gallery = array( 'source_rows' => count( $rows ), 'checked' => 0, 'with_more_photos' => 0, 'added_photos' => 0, 'updated_products' => 0, 'errors' => array(), 'sample' => array() );

function emdo_repair_copy_20261020( string $html ): string {
	$rules = array(
		// Verifiable specifications remain; only third-person source framing goes away.
		'~\bseg[uú]n\s+(?:se\s+(?:indica|informa)\s+en\s+)?(?:la\s+)?(?:web|p[aá]gina|ficha(?:\s+t[eé]cnica)?|informaci[oó]n|cat[aá]logo)\s+(?:de(?:l|\s+la)\s+)?(?:productor|fabricante|proveedor|Latitudes)\b,?\s*~iu' => '',
		'~\bseg[uú]n\s+la\s+lista\s+de\s+ingredientes\s+publicada\s+por\s+(?:el\s+)?(?:productor|fabricante|proveedor)\b,?\s*~iu' => '',
		'~\b(?:de\s+acuerdo\s+con|conforme\s+a)\s+(?:la\s+)?(?:ficha|web|informaci[oó]n)\s+(?:de(?:l|\s+la)\s+)?(?:productor|fabricante|proveedor|Latitudes)\b,?\s*~iu' => '',
		'~\b(?:La\s+)?(?:ficha|web|p[aá]gina)\s+del\s+(?:productor|fabricante|proveedor)\s+(?:indica|se[nñ]ala|explica|especifica)\s+que\s+~iu' => '',
		'~\b(?:El\s+)?(?:productor|fabricante|proveedor)\s+(?:indica|informa|se[nñ]ala|explica|especifica)\s+que\s+~iu' => '',
		'~\bLa\s+ficha\s+del\s+(?:productor|fabricante|proveedor)\s+describe\s+~iu' => 'Presenta ',
		'~\bLa\s+ficha\s+del\s+(?:productor|fabricante|proveedor)\s+destaca\s+~iu' => 'Destaca ',
		'~\bInformaci[oó]n\s+sobre\s+preparaci[oó]n,\s+ingredientes\s+y\s+al[eé]rgenos\s+publicada\s+por\s+(?:Latitudes|el\s+productor)\s*:~iu' => 'Preparación, ingredientes y alérgenos:',
	);
	$out = preg_replace( array_keys( $rules ), array_values( $rules ), $html );
	if ( ! is_string( $out ) ) { return $html; }
	// Clean the orphan comma if a removed attribution appeared after the previous word.
	$out = preg_replace( '/\s+,/u', ',', $out );
	$out = preg_replace( '/,\s*,/u', ',', $out );
	$out = preg_replace( '/\s{2,}/u', ' ', $out );
	// Sentence starts after <p>, punctuation or <li>.
	$out = preg_replace_callback(
		'~(^|<p[^>]*>|<li[^>]*>|[.!?]\s+)([a-záéíóúñ])~iu',
		static fn( array $m ): string => $m[1] . mb_strtoupper( $m[2], 'UTF-8' ),
		$out
	);
	return wp_kses_post( trim( (string) $out ) );
}

$neutralization_pattern = '~\b(?:seg[uú]n\s+(?:la\s+)?(?:web|ficha|p[aá]gina|lista\s+de\s+ingredientes)|la\s+ficha\s+del\s+(?:productor|fabricante)|el\s+(?:productor|fabricante)\s+(?:indica|describe|se[nñ]ala))\b~iu';
$page = 1;
do {
	$ids = get_posts( array(
		'post_type'=>'product', 'post_status'=>array( 'publish', 'draft', 'pending' ),
		'posts_per_page'=>200, 'paged'=>$page, 'orderby'=>'ID', 'order'=>'ASC',
		'fields'=>'ids', 'suppress_filters'=>true,
	) );
	foreach ( $ids as $id ) {
		$p = wc_get_product( (int) $id );
		if ( ! $p || $p->is_type( 'variation' ) ) { continue; }
		$copy['scanned']++;
		$old_long = (string) $p->get_description( 'edit' );
		$old_short = (string) $p->get_short_description( 'edit' );
		$new_long = emdo_repair_copy_20261020( $old_long );
		$new_short = emdo_repair_copy_20261020( $old_short );
		if ( ( $new_long !== $old_long || $new_short !== $old_short ) &&
			( mb_strlen( wp_strip_all_tags( $new_long ) ) >= min( 35, mb_strlen( wp_strip_all_tags( $old_long ) ) ) ) ) {
			if ( $apply ) {
				$p->set_description( $new_long );
				$p->set_short_description( $new_short );
				$p->save();
				foreach ( array(
					'_emdo_latitudes_editorial_description',
					'_emdo_latitudes_editorial_short_description',
					'_emdo_isabel_editorial_intro',
					'_emdo_isabel_editorial_short_description',
					'_emdo_isabel_personalizable_editorial_intro',
				) as $key ) {
					$meta = (string) get_post_meta( $id, $key, true );
					if ( '' === $meta ) { continue; }
					$fixed = emdo_repair_copy_20261020( $meta );
					if ( $fixed !== $meta ) { update_post_meta( $id, $key, $fixed ); $copy['metadata']++; }
				}
			}
			$copy['updated']++;
			if ( count( $copy['examples'] ) < 20 ) { $copy['examples'][] = array( 'id'=>$id, 'name'=>$p->get_name(), 'url'=>get_permalink( $id ) ); }
		}
		if ( preg_match( $neutralization_pattern, wp_strip_all_tags( $new_long . ' ' . $new_short ) ) ) {
			$copy['remaining']++;
		}
	}
	$page++;
} while ( count( $ids ) === 200 );

if ( ! class_exists( 'MDO_Connector_Tolecarnes' ) ) { throw new RuntimeException( 'Conector para galerías no disponible.' ); }
if ( $apply ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
}
$exemplar_found = false;
foreach ( $rows as $row ) {
	$id = (int) $row['wc_product_id'];
	$url = (string) $row['source_url'];
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	if ( ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ), array( 'latitudes.es', 'www.latitudes.es' ), true ) ) {
		$gallery['errors'][] = array( 'id'=>$id, 'reason'=>'host_mismatch' );
		continue;
	}
	$p = wc_get_product( $id );
	if ( ! $p || (int) get_post_field( 'post_author', $id ) !== (int) $lat['vendor_user_id'] ) {
		$gallery['errors'][] = array( 'id'=>$id, 'reason'=>'vendor_mismatch' );
		continue;
	}
	try {
		$data = MDO_Connector_Tolecarnes::scrape_product( $url );
		$urls = array_values( array_unique( array_filter( (array) ( $data['images'] ?? array() ), static function ( $u ): bool {
			$host = strtolower( (string) wp_parse_url( (string) $u, PHP_URL_HOST ) );
			$path = (string) wp_parse_url( (string) $u, PHP_URL_PATH );
			return in_array( $host, array( 'latitudes.es', 'www.latitudes.es' ), true )
				&& str_contains( $path, '/wp-content/uploads/' )
				&& (bool) preg_match( '~\.(?:jpe?g|png|webp)$~i', $path );
		} ) ) );
		if ( count( $urls ) > 30 ) { throw new RuntimeException( 'Más de 30 imágenes; revisar manualmente.' ); }
		$gallery['checked']++;
		if ( str_contains( $path, '/burger-meat-wagyu-100-japones-150-gr/' ) ) {
			$exemplar_found = true;
			if ( count( $urls ) < 7 ) { throw new RuntimeException( 'La galería de la referencia ejemplo sigue incompleta: '.count( $urls ) ); }
		}
		$old_ids = array_map( 'intval', $p->get_gallery_image_ids() );
		if ( count( $urls ) > count( $old_ids ) + 1 ) { $gallery['with_more_photos']++; }
		$added = 0;
		if ( $apply && count( $urls ) > 1 ) {
			$map = json_decode( (string) get_post_meta( $id, '_emdo_image_map', true ), true );
			$map = is_array( $map ) ? $map : array();
			$ids_to_save = $old_ids;
			foreach ( $urls as $index => $image_url ) {
				$aid = isset( $map[ $image_url ] ) ? (int) $map[ $image_url ] : 0;
				if ( $aid && 'attachment' !== get_post_type( $aid ) ) { $aid = 0; }
				if ( ! $aid ) {
					$matches = get_posts( array( 'post_type'=>'attachment', 'post_status'=>'inherit', 'posts_per_page'=>1, 'fields'=>'ids',
						'meta_key'=>'_source_url', 'meta_value'=>$image_url ) );
					$aid = $matches ? (int) $matches[0] : 0;
				}
				if ( ! $aid && $index > 0 ) {
					$result = media_sideload_image( $image_url, $id, $p->get_name(), 'id' );
					if ( is_wp_error( $result ) ) {
						$gallery['errors'][] = array( 'id'=>$id, 'reason'=>'download', 'url'=>$image_url, 'message'=>$result->get_error_message() );
						continue;
					}
					$aid = (int) $result;
					update_post_meta( $aid, '_wp_attachment_image_alt', sanitize_text_field( $p->get_name() . ' — imagen ' . ( $index + 1 ) ) );
					$added++;
				}
				if ( $aid ) {
					$map[ $image_url ] = $aid;
					if ( $index > 0 && $aid !== (int) $p->get_image_id() && ! in_array( $aid, $ids_to_save, true ) ) {
						$ids_to_save[] = $aid;
					}
				}
			}
			if ( count( $ids_to_save ) > count( $old_ids ) ) {
				$p->set_gallery_image_ids( $ids_to_save );
				$p->save();
				$saved = wc_get_product( $id );
				if ( ! $saved || count( $saved->get_gallery_image_ids() ) !== count( $ids_to_save ) ) {
					throw new RuntimeException( 'No se ha confirmado el guardado de la galería.' );
				}
				update_post_meta( $id, '_emdo_image_map', wp_json_encode( $map, JSON_UNESCAPED_SLASHES ) );
				$gallery['updated_products']++;
			}
			// Preserve extended image URLs in cached supplier payload until next source scrape.
			$payload = json_decode( (string) $row['source_payload'], true );
			if ( is_array( $payload ) && count( $urls ) > count( (array) ( $payload['images'] ?? array() ) ) ) {
				$payload['images'] = $urls;
				$payload['image_count'] = count( $urls );
				$hash_data = $payload;
				unset( $hash_data['description'], $hash_data['source_hash'] );
				$hash_data['description_hash'] = hash( 'sha256', wp_strip_all_tags( (string) ( $payload['description'] ?? '' ) ) );
				$hash = hash( 'sha256', wp_json_encode( $hash_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
				$wpdb->update( $source_table,
					array( 'source_payload'=>wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), 'source_hash'=>$hash ),
					array( 'id'=>(int) $row['id'] ), array( '%s', '%s' ), array( '%d' ) );
			}
		}
		$gallery['added_photos'] += $added;
		if ( count( $gallery['sample'] ) < 20 || str_contains( $path, '/burger-meat-wagyu-100-japones-150-gr/' ) ) {
			$gallery['sample'][] = array( 'id'=>$id, 'source_images'=>count($urls), 'previous_gallery'=>count($old_ids), 'added'=>$added, 'url'=>get_permalink($id) );
		}
	} catch ( Throwable $e ) {
		$gallery['errors'][] = array( 'id'=>$id, 'reason'=>'exception', 'message'=>mb_substr($e->getMessage(),0,250) );
	}
}
if ( ! $exemplar_found ) { throw new RuntimeException( 'No se encontró la referencia ejemplo Wagyu en Latitudes.' ); }
$report = array( 'mode'=>$mode, 'timestamp'=>gmdate('c'), 'copy'=>$copy, 'gallery'=>$gallery );
echo 'EMDO_CONTENT_GALLERY_REPAIR=' . wp_json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
if ( $gallery['checked'] < 100 ) { throw new RuntimeException( 'No se ha podido auditar la mayoría de las galerías de Latitudes.' ); }
