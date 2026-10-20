<?php
/**
 * Read-only content and gallery inventory for WooCommerce products.
 *
 * Usage from the WordPress root:
 *   wp eval-file wp-content/themes/elmercadodeorigen-child/scripts/audit-product-content.php > /tmp/emdo-products.csv
 *
 * Review the CSV before rewriting copy or importing extra supplier images.
 * Does not update the product database, images, prices or shipping rules.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
if ( ! function_exists( 'wc_get_product' ) ) {
	WP_CLI::error( 'WooCommerce no está disponible.' );
}

$handle = fopen( 'php://stdout', 'w' );
if ( false === $handle ) {
	WP_CLI::error( 'No se puede crear la salida CSV.' );
}
fwrite( $handle, "\xEF\xBB\xBF" ); // Excel-compatible UTF-8.
fputcsv(
	$handle,
	array(
		'id', 'productor', 'producto', 'estado', 'url',
		'imagen_principal', 'fotos_galeria', 'redaccion_proveedor',
		'comprobar_galeria', 'frase_detectada', 'descripcion_corta',
	),
	';'
);

$pattern = '/\\b(?:seg[uú]n\\s+(?:(?:la|el)\\s+)?(?:web|p[aá]gina|ficha|informaci[oó]n|lista|cat[aá]logo|productor|fabricante|proveedor)|(?:la|el)\\s+(?:web|p[aá]gina|ficha)\\s+del\\s+(?:productor|fabricante|proveedor)|(?:productor|proveedor|fabricante)\\s+(?:indica|informa|se[nñ]ala|explica)|hemos\\s+(?:encontrado|consultado)|de\\s+acuerdo\\s+con\\s+la\\s+(?:web|ficha))\\b/iu';

$page       = 1;
$checked    = 0;
$copy_flags = 0;
$no_gallery = 0;
do {
	$query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page'         => 150,
			'paged'                  => $page,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	$ids = $query->posts;
	foreach ( $ids as $id ) {
		$product = wc_get_product( (int) $id );
		if ( ! $product ) {
			continue;
		}
		$post   = get_post( (int) $id );
		$vendor = $post ? get_userdata( (int) $post->post_author ) : null;
		$name   = $vendor ? $vendor->display_name : '';
		if ( function_exists( 'elmercado_core_filter_vendor_name' ) && $post ) {
			$display_name = elmercado_core_filter_vendor_name( (int) $post->post_author );
			if ( is_string( $display_name ) && '' !== $display_name ) {
				$name = $display_name;
			}
		}
		$short = trim( preg_replace( '/\\s+/u', ' ', wp_strip_all_tags( $product->get_short_description() ) ) );
		$long  = trim( preg_replace( '/\\s+/u', ' ', wp_strip_all_tags( $product->get_description() ) ) );
		$text  = $short . ' ' . $long;
		$match = array();
		$flag  = preg_match( $pattern, $text, $match );
		$gallery_count = count( $product->get_gallery_image_ids() );
		$missing_gallery = 0 === $gallery_count;
		$checked++;
		$copy_flags += (int) (bool) $flag;
		$no_gallery += (int) $missing_gallery;
		fputcsv(
			$handle,
			array(
				$id, $name, $product->get_name(),
				$post ? $post->post_status : '', get_permalink( $id ),
				$product->get_image_id() ? 'si' : 'no',
				$gallery_count,
				$flag ? 'revisar' : 'no',
				$missing_gallery ? 'comparar con proveedor' : 'no',
				isset( $match[0] ) ? $match[0] : '',
				$short,
			),
			';'
		);
	}
	$page++;
	wp_reset_postdata();
} while ( count( $ids ) === 150 );

fclose( $handle );
fwrite(
	STDERR,
	sprintf(
		'Auditados: %d productos. Frases de proveedor detectadas: %d. Sin fotos adicionales: %d (verificar con fuentes antes de importar).\n',
		$checked,
		$copy_flags,
		$no_gallery
	)
);
