<?php
/**
 * Scoped finishing pass for the October 2026 category families.
 * Run with WP-CLI: EMDO_CATEGORY_ASSETS_DIR=/tmp/assets EMDO_CATEGORY_FINISH_MODE=audit|apply wp eval-file ...
 * No product records, category slugs, existing curated copy or other media are modified.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
$host = strtolower( (string) wp_parse_url( get_option( 'siteurl' ), PHP_URL_HOST ) );
if ( ! in_array( $host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
	throw new RuntimeException( 'Category finishing pass is production-only.' );
}
if ( ! taxonomy_exists( 'product_cat' ) ) {
	throw new RuntimeException( 'WooCommerce product_cat taxonomy is unavailable.' );
}
$mode = (string) ( getenv( 'EMDO_CATEGORY_FINISH_MODE' ) ?: 'audit' );
if ( ! in_array( $mode, array( 'audit', 'apply' ), true ) ) {
	throw new RuntimeException( 'Invalid finishing mode.' );
}
$asset_dir = rtrim( (string) getenv( 'EMDO_CATEGORY_ASSETS_DIR' ), '/' );
$assets = array(
	'bebidas'          => 'categoria-bebidas-20261021.webp',
	'mieles'           => 'categoria-mieles-20261021.webp',
	'frutos-secos'     => 'categoria-frutos-secos-20261021.webp',
	'cosmetica-natural'=> 'categoria-cosmetica-natural-20261021.webp',
);
/* New categories only: preserve the older approved editorial descriptions. */
$descriptions = array(
	'frutas'                       => 'Frutas de temporada y variedades tropicales para disfrutar al natural, en macedonias o en recetas.',
	'verduras'                     => 'Verduras de la huerta para ensaladas, guarniciones y platos de cada día, según la temporada.',
	'chocolates-artesanos'          => 'Chocolates de obradores especializados, con distintos porcentajes de cacao, sabores y formatos para degustar.',
	'cafe-de-especialidad'         => 'Cafés de distintos orígenes, tuestes y perfiles aromáticos, en grano o molidos según el método de preparación.',
	'huevos'                       => 'Huevos de productores identificados, con información sobre su procedencia y presentación.',
	'bebidas'                     => 'Zumos, néctares, tés, infusiones y otras bebidas para explorar diferentes sabores y elaboraciones.',
	'frutos-secos'                => 'Almendras, avellanas, castañas y otros frutos secos, solos o combinados en surtidos.',
	'mieles'                      => 'Mieles de distintos orígenes florales y perfiles de sabor, desde variedades delicadas hasta otras más intensas.',
	'cosmetica-natural'           => 'Jabones, cremas y otros cuidados personales elaborados con ingredientes de inspiración natural.',
	'condimentos-y-especias'      => 'Sales, especias y otros condimentos para dar matices nuevos a recetas dulces y saladas.',
	'mermeladas-y-confituras'     => 'Mermeladas y confituras artesanas de frutas y otras combinaciones, para desayunos, postres y maridajes.',
	'zumos-nectares-y-limonadas'  => 'Zumos, néctares y limonadas de frutas y otras materias primas, en diferentes mezclas y formatos.',
	'tes-e-infusiones'            => 'Tés e infusiones con distintos ingredientes y perfiles aromáticos, para disfrutar fríos o calientes.',
	'vinos-y-licores'             => 'Vinos y licores de distintas elaboraciones y procedencias, para ocasiones y gustos diversos.',
);
$english = array(
	'frutas'                      => 'Seasonal fruits and tropical varieties to enjoy fresh, in fruit salads or in recipes.',
	'verduras'                    => 'Garden vegetables for salads, side dishes and everyday meals, depending on the season.',
	'chocolates-artesanos'        => 'Chocolate from specialist makers, with different cocoa percentages, flavours and formats.',
	'cafe-de-especialidad'        => 'Coffees from different origins and roasts, whole bean or ground for your brewing method.',
	'huevos'                      => 'Eggs from identifiable producers, with clear information about origin and packaging.',
	'bebidas'                     => 'Juices, nectars, teas, infusions and other drinks in a range of flavours and styles.',
	'frutos-secos'                => 'Almonds, hazelnuts, chestnuts and other nuts, individually or in assorted mixes.',
	'mieles'                      => 'Honey from different floral sources, ranging from delicate to fuller-bodied flavours.',
	'cosmetica-natural'           => 'Soaps, creams and personal-care products made with naturally inspired ingredients.',
	'condimentos-y-especias'      => 'Salts, spices and seasonings to add new notes to sweet and savoury cooking.',
	'mermeladas-y-confituras'     => 'Artisan jams and preserves for breakfast, desserts and food pairings.',
	'zumos-nectares-y-limonadas' => 'Juices, nectars and lemonades made from fruit and other ingredients in varied blends.',
	'tes-e-infusiones'           => 'Teas and herbal infusions with different ingredients and aromas, hot or cold.',
	'vinos-y-licores'            => 'Wines and liqueurs in a variety of styles and origins.',
);
$previous_generic = array(
	'mermeladas-y-confituras'     => 'Mermeladas y confituras artesanas, dulces y saladas.',
	'bebidas'                    => 'Zumos, néctares, infusiones y otras bebidas.',
	'zumos-nectares-y-limonadas' => 'Zumos, néctares y limonadas de distintas frutas y hortalizas.',
	'tes-e-infusiones'           => 'Tés e infusiones de distintos sabores y plantas.',
	'vinos-y-licores'            => 'Vinos y licores del catálogo.',
	'frutos-secos'               => 'Frutos secos y sus surtidos.',
	'mieles'                     => 'Mieles artesanales y surtidos de miel.',
	'cosmetica-natural'          => 'Productos de cosmética natural.',
	'condimentos-y-especias'     => 'Sales, especias y condimentos culinarios.',
);
$terms = array();
$not_found = array();
foreach ( $descriptions as $slug => $new_copy ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term instanceof WP_Term ) {
		$terms[ $slug ] = $term;
	} else {
		$not_found[] = $slug;
	}
}
foreach ( $assets as $slug => $filename ) {
	if ( ! isset( $terms[ $slug ] ) ) {
		throw new RuntimeException( 'Missing category for supplied photo: ' . $slug );
	}
	$filepath = $asset_dir . '/' . $filename;
	if ( ! is_file( $filepath ) || filesize( $filepath ) < 1000 || ! @getimagesize( $filepath ) ) {
		throw new RuntimeException( 'Invalid image file: ' . $filepath );
	}
}
$before = array();
$actions = array();
foreach ( $terms as $slug => $term ) {
	$term_id = (int) $term->term_id;
	$before[ $slug ] = array(
		'id' => $term_id,
		'description' => (string) $term->description,
		'thumbnail_id' => (int) get_term_meta( $term_id, 'thumbnail_id', true ),
		'en_hub_summary' => (string) get_term_meta( $term_id, '_emdo_en_hub_summary', true ),
	);
	$present = trim( wp_strip_all_tags( html_entity_decode( (string) $term->description, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	$generic = '' === $present || ( isset( $previous_generic[ $slug ] ) && $present === $previous_generic[ $slug ] ) ||
		(bool) preg_match( '/agrupados en una misma categoría|origen claro, productor visible|selección disponible de/u', $present );
	if ( $generic ) {
		$actions[] = 'description:' . $slug;
	}
	if ( '1' === (string) get_term_meta( $term_id, '_en_US_published', true ) &&
		'' === $before[ $slug ]['en_hub_summary'] ) {
		$actions[] = 'english:' . $slug;
	}
	if ( isset( $assets[ $slug ] ) ) {
		$thumbnail = $before[ $slug ]['thumbnail_id'];
		$existing_file = $thumbnail ? get_attached_file( $thumbnail ) : '';
		$working = $thumbnail && $existing_file && is_file( $existing_file ) &&
			wp_get_attachment_image_url( $thumbnail, 'woocommerce_thumbnail' );
		if ( ! $working ) {
			$actions[] = 'image:' . $slug;
		}
	}
}
WP_CLI::log( 'CATEGORY_FINISH_PREFLIGHT=' . wp_json_encode( array(
	'mode' => $mode, 'existing_terms' => array_keys( $terms ),
	'missing_optional_terms' => $not_found, 'actions' => $actions,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
if ( 'audit' === $mode ) {
	return;
}
$backup_dir = rtrim( (string) getenv( 'HOME' ), '/' ) . '/.elmercado-rollbacks/category-assets';
if ( ! is_dir( $backup_dir ) && ! wp_mkdir_p( $backup_dir ) ) {
	throw new RuntimeException( 'Cannot create category rollback directory.' );
}
$backup = $backup_dir . '/' . gmdate( 'Ymd-His' ) . '-' . (string) getmypid() . '.json';
if ( ! file_put_contents( $backup, wp_json_encode( $before, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) ) {
	throw new RuntimeException( 'Cannot save pre-change category backup.' );
}
WP_CLI::log( 'CATEGORY_FINISH_BACKUP=' . $backup );
$updated = array();
$attached = array();
try {
	foreach ( $terms as $slug => $term ) {
		$term_id = (int) $term->term_id;
		$present = trim( wp_strip_all_tags( html_entity_decode( (string) $term->description, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
		$generic = '' === $present || ( isset( $previous_generic[ $slug ] ) && $present === $previous_generic[ $slug ] ) ||
			(bool) preg_match( '/agrupados en una misma categoría|origen claro, productor visible|selección disponible de/u', $present );
		if ( $generic ) {
			$result = wp_update_term( $term_id, 'product_cat', array( 'description' => $descriptions[ $slug ] ) );
			if ( is_wp_error( $result ) ) {
				throw new RuntimeException( 'Category description update failed: ' . $slug );
			}
			$updated[] = 'description:' . $slug;
		}
		if ( '1' === (string) get_term_meta( $term_id, '_en_US_published', true ) &&
			'' === $before[ $slug ]['en_hub_summary'] ) {
			update_term_meta( $term_id, '_emdo_en_hub_summary', $english[ $slug ] );
			$updated[] = 'english:' . $slug;
		}
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	foreach ( $assets as $slug => $filename ) {
		$term_id = (int) $terms[ $slug ]->term_id;
		$old_id = (int) get_term_meta( $term_id, 'thumbnail_id', true );
		$existing_file = $old_id ? get_attached_file( $old_id ) : '';
		$working = $old_id && $existing_file && is_file( $existing_file ) &&
			wp_get_attachment_image_url( $old_id, 'woocommerce_thumbnail' );
		if ( $working ) {
			$updated[] = 'preserved-existing-image:' . $slug;
			continue;
		}
		$existing = get_posts( array(
			'post_type' => 'attachment', 'post_status' => 'inherit',
			'posts_per_page' => 1, 'fields' => 'ids',
			'meta_key' => '_emdo_category_photo_slug_20261021', 'meta_value' => $slug,
		) );
		$attachment_id = $existing ? (int) $existing[0] : 0;
		if ( ! $attachment_id ) {
			$uploaded = wp_upload_bits( $filename, null, file_get_contents( $asset_dir . '/' . $filename ) );
			if ( ! empty( $uploaded['error'] ) || empty( $uploaded['file'] ) ) {
				throw new RuntimeException( 'Upload failed for ' . $slug . ': ' . (string) ( $uploaded['error'] ?? '' ) );
			}
			$attachment_id = wp_insert_attachment( array(
				'post_mime_type' => 'image/webp',
				'post_title' => 'Categoría ' . $terms[ $slug ]->name,
				'post_status' => 'inherit',
			), $uploaded['file'], 0, true );
			if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
				throw new RuntimeException( 'Media attachment could not be registered for ' . $slug );
			}
			update_post_meta( $attachment_id, '_emdo_category_photo_slug_20261021', $slug );
			$metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
			if ( ! is_array( $metadata ) || empty( $metadata['width'] ) ) {
				throw new RuntimeException( 'Invalid generated image metadata for ' . $slug );
			}
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
		update_term_meta( $term_id, 'thumbnail_id', $attachment_id );
		clean_term_cache( $term_id, 'product_cat' );
		$attached[ $slug ] = $attachment_id;
		$updated[] = 'image:' . $slug;
	}
	foreach ( $assets as $slug => $filename ) {
		$id = (int) get_term_meta( (int) $terms[ $slug ]->term_id, 'thumbnail_id', true );
		$file = $id ? get_attached_file( $id ) : '';
		$url = $id ? wp_get_attachment_image_url( $id, 'woocommerce_thumbnail' ) : '';
		if ( ! $id || ! $file || ! is_file( $file ) || ! $url ) {
			throw new RuntimeException( 'Final WooCommerce category image verification failed: ' . $slug );
		}
		WP_CLI::log( 'CATEGORY_FINISH_IMAGE=' . wp_json_encode( array(
			'slug' => $slug, 'attachment_id' => $id, 'url' => $url,
		), JSON_UNESCAPED_SLASHES ) );
	}
	wp_cache_flush();
	WP_CLI::log( 'CATEGORY_FINISH_APPLIED=' . wp_json_encode( array(
		'updates' => $updated, 'new_attachments' => $attached, 'backup' => $backup,
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
} catch ( Throwable $e ) {
	foreach ( $before as $slug => $prior ) {
		$id = $prior['id'];
		wp_update_term( $id, 'product_cat', array( 'description' => $prior['description'] ) );
		if ( $prior['thumbnail_id'] ) {
			update_term_meta( $id, 'thumbnail_id', $prior['thumbnail_id'] );
		} else {
			delete_term_meta( $id, 'thumbnail_id' );
		}
		if ( '' !== $prior['en_hub_summary'] ) {
			update_term_meta( $id, '_emdo_en_hub_summary', $prior['en_hub_summary'] );
		} else {
			delete_term_meta( $id, '_emdo_en_hub_summary' );
		}
	}
	wp_cache_flush();
	throw $e;
}
