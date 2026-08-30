<?php
/**
 * Siembra editorial: primer clúster nutricional de legumbres (ES/EN).
 *
 * Crea o actualiza cinco artículos, los publica en la categoría Legumbres,
 * persiste su traducción inglesa en Falang y reutiliza la imagen provisional
 * del blog como destacada. La operación es idempotente y se ejecuta una vez.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ELMERCADO_LEGUME_NUTRITION_SEED_VERSION_010267 = '2026-08-30.1';
const ELMERCADO_LEGUME_NUTRITION_SEED_OPTION_010267  = 'elmercado_legume_nutrition_seed_010267';

/**
 * Obtiene o crea la categoría editorial Legumbres.
 */
function elmercado_legume_blog_category_id_010267(): int {
	$category = get_category_by_slug( 'legumbres' );
	if ( $category instanceof WP_Term ) {
		return (int) $category->term_id;
	}

	$by_name = get_term_by( 'name', 'Legumbres', 'category' );
	if ( $by_name instanceof WP_Term ) {
		return (int) $by_name->term_id;
	}

	$created = wp_insert_term(
		'Legumbres',
		'category',
		array(
			'slug'        => 'legumbres',
			'description' => 'Guías, comparativas y consejos sobre legumbres.',
		)
	);

	return is_wp_error( $created ) ? 0 : (int) ( $created['term_id'] ?? 0 );
}

/**
 * Devuelve el objeto de idioma inglés configurado en Falang.
 *
 * @return object|null
 */
function elmercado_falang_english_language_010267() {
	if ( ! function_exists( 'Falang' ) ) {
		return null;
	}

	$instance = Falang();
	if ( ! is_object( $instance ) || ! method_exists( $instance, 'get_model' ) ) {
		return null;
	}

	$model = $instance->get_model();
	if ( ! is_object( $model ) ) {
		return null;
	}

	if ( method_exists( $model, 'get_language_by_locale' ) ) {
		$language = $model->get_language_by_locale( 'en_US' );
		if ( is_object( $language ) ) {
			return $language;
		}
	}

	if ( method_exists( $model, 'get_languages_list' ) ) {
		$languages = $model->get_languages_list( array( 'hide_default' => false ) );
		if ( is_array( $languages ) ) {
			foreach ( $languages as $language ) {
				$slug   = is_object( $language ) && isset( $language->slug ) ? strtolower( (string) $language->slug ) : '';
				$locale = is_object( $language ) && isset( $language->locale ) ? strtolower( str_replace( '-', '_', (string) $language->locale ) ) : '';
				if ( 'en' === $slug || 0 === strpos( $locale, 'en_' ) ) {
					return $language;
				}
			}
		}
	}

	return null;
}

/**
 * Guarda un campo traducido y verifica con la propia API de lectura de Falang
 * qué convención de metadatos utiliza la versión instalada.
 *
 * Esto evita acoplar el tema a una única versión de Falang: probamos las
 * convenciones conocidas y conservamos la que el plugin reconoce realmente.
 *
 * @param int    $post_id  ID de la entrada.
 * @param string $field    Campo WP: post_title, post_content, post_excerpt o post_name.
 * @param string $value    Valor inglés.
 * @param object $language Objeto de idioma Falang.
 */
function elmercado_save_falang_post_field_010267( int $post_id, string $field, string $value, $language ): bool {
	if ( ! class_exists( '\\Falang\\Core\\Post' ) || ! is_object( $language ) ) {
		return false;
	}

	$locale = isset( $language->locale ) ? (string) $language->locale : 'en_US';
	$locale = str_replace( '-', '_', $locale );
	if ( '' === $locale ) {
		$locale = 'en_US';
	}

	$short = 0 === strpos( $field, 'post_' ) ? substr( $field, 5 ) : $field;
	$candidates = array_unique(
		array(
			'_' . $locale . '_' . $field,
			'_' . $locale . '_' . $short,
			$locale . '_' . $field,
			$locale . '_' . $short,
			'_' . strtolower( $locale ) . '_' . $field,
			'_' . strtolower( $locale ) . '_' . $short,
		)
	);

	if ( 'post_name' === $field ) {
		$candidates[] = '_' . $locale . '_slug';
		$candidates[] = $locale . '_slug';
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	foreach ( $candidates as $key ) {
		update_post_meta( $post_id, $key, $value );

		try {
			$falang_post = new \Falang\Core\Post( $post_id );
			$translated  = $falang_post->translate_post_field( $post, $field, $language, (string) $post->{$field} );
		} catch ( Throwable $exception ) {
			$translated = '';
		}

		if ( (string) $translated === $value ) {
			return true;
		}

		delete_post_meta( $post_id, $key, $value );
	}

	return false;
}

/**
 * Publica la traducción inglesa completa de una entrada.
 */
function elmercado_save_falang_english_post_010267( int $post_id, array $article ): bool {
	$language = elmercado_falang_english_language_010267();
	if ( ! is_object( $language ) ) {
		return false;
	}

	$locale = isset( $language->locale ) ? str_replace( '-', '_', (string) $language->locale ) : 'en_US';
	if ( '' === $locale ) {
		$locale = 'en_US';
	}

	$fields = array(
		'post_title'   => (string) $article['en_title'],
		'post_content' => (string) $article['en_content'],
		'post_excerpt' => (string) $article['en_excerpt'],
		'post_name'    => (string) $article['en_slug'],
	);

	foreach ( $fields as $field => $value ) {
		if ( ! elmercado_save_falang_post_field_010267( $post_id, $field, $value, $language ) ) {
			return false;
		}
	}

	/* La marca _<locale>_published es la usada por Falang para publicar traducciones. */
	update_post_meta( $post_id, '_' . $locale . '_published', '1' );
	return true;
}

/**
 * Localiza la imagen genérica ya existente; si no existe como adjunto, crea
 * una sola copia en uploads a partir del asset oficial del tema.
 */
function elmercado_blog_generic_image_id_010267(): int {
	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_attachment_image_alt',
			'meta_value'     => 'Imagen provisional del blog de El Mercado de Origen',
		)
	);

	if ( ! empty( $attachments ) ) {
		return (int) $attachments[0];
	}

	/* Reutiliza el thumbnail de una entrada reciente que ya tenga el provisional. */
	$sample_slugs = array(
		'verdura-vs-hortaliza-diferencia-que-alimentos-pertenecen-cada-grupo',
		'cuando-echar-sal-garbanzos-lentejas-alubias-endurece-legumbres',
	);
	foreach ( $sample_slugs as $slug ) {
		$sample = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $sample instanceof WP_Post ) {
			$thumbnail_id = (int) get_post_thumbnail_id( $sample->ID );
			if ( $thumbnail_id > 0 ) {
				return $thumbnail_id;
			}
		}
	}

	$source = defined( 'ELMERCADO_THEME_PATH' ) ? ELMERCADO_THEME_PATH . '/assets/images/blog-default.webp' : '';
	if ( '' === $source || ! is_readable( $source ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return 0;
	}

	$filename = wp_unique_filename( $uploads['path'], 'blog-default.webp' );
	$target   = trailingslashit( $uploads['path'] ) . $filename;
	if ( ! copy( $source, $target ) ) {
		return 0;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/webp',
			'post_title'     => 'Imagen provisional del blog de El Mercado de Origen',
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$target
	);

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $target );
		return 0;
	}

	$attachment_id = (int) $attachment_id;
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Imagen provisional del blog de El Mercado de Origen' );
	$metadata = wp_generate_attachment_metadata( $attachment_id, $target );
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	return $attachment_id;
}

/**
 * Crea o actualiza una entrada sin generar duplicados por slug.
 */
function elmercado_upsert_legume_article_010267( array $article, int $category_id, int $image_id ): int {
	$existing = get_page_by_path( (string) $article['slug'], OBJECT, 'post' );
	$postarr = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'post_title'     => (string) $article['title'],
		'post_name'      => (string) $article['slug'],
		'post_excerpt'   => (string) $article['excerpt'],
		'post_content'   => (string) $article['content'],
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	);

	if ( $category_id > 0 ) {
		$postarr['post_category'] = array( $category_id );
	}

	if ( $existing instanceof WP_Post ) {
		$postarr['ID'] = (int) $existing->ID;
		$result = wp_update_post( wp_slash( $postarr ), true );
	} else {
		$result = wp_insert_post( wp_slash( $postarr ), true );
	}

	if ( is_wp_error( $result ) || (int) $result <= 0 ) {
		return 0;
	}

	$post_id = (int) $result;
	if ( $image_id > 0 ) {
		set_post_thumbnail( $post_id, $image_id );
	}

	return $post_id;
}

/**
 * Ejecuta la siembra una sola vez, pero solo la marca como finalizada cuando
 * las cinco entradas y sus traducciones se han persistido correctamente.
 */
function elmercado_seed_legume_nutrition_posts_010267(): void {
	if ( ELMERCADO_LEGUME_NUTRITION_SEED_VERSION_010267 === (string) get_option( ELMERCADO_LEGUME_NUTRITION_SEED_OPTION_010267, '' ) ) {
		return;
	}

	$encoded = '';
	for ( $part = 1; $part <= 4; $part++ ) {
		$file = __DIR__ . '/content-seeds/legume-nutrition-010267-' . $part . '.b64';
		if ( ! is_readable( $file ) ) {
			return;
		}
		$encoded .= trim( (string) file_get_contents( $file ) );
	}

	if ( ! function_exists( 'gzdecode' ) ) {
		return;
	}

	$compressed = base64_decode( $encoded, true );
	if ( false === $compressed ) {
		return;
	}

	$serialized = gzdecode( $compressed );
	if ( false === $serialized ) {
		return;
	}

	$articles = unserialize( $serialized, array( 'allowed_classes' => false ) );
	if ( ! is_array( $articles ) || 5 !== count( $articles ) ) {
		return;
	}

	$category_id = elmercado_legume_blog_category_id_010267();
	$image_id    = elmercado_blog_generic_image_id_010267();
	$all_done    = true;

	foreach ( $articles as $article ) {
		$post_id = elmercado_upsert_legume_article_010267( $article, $category_id, $image_id );
		if ( $post_id <= 0 || ! elmercado_save_falang_english_post_010267( $post_id, $article ) ) {
			$all_done = false;
		}
	}

	if ( $all_done ) {
		update_option( ELMERCADO_LEGUME_NUTRITION_SEED_OPTION_010267, ELMERCADO_LEGUME_NUTRITION_SEED_VERSION_010267, false );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'elmercado_seed_legume_nutrition_posts_010267', 40 );
