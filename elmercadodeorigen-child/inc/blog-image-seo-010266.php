<?php
/**
 * SEO y rendimiento de imágenes del blog.
 *
 * Mantiene los ALT editoriales existentes y completa únicamente los vacíos con
 * contexto real del adjunto o de la entrada. También normaliza la carga de las
 * imágenes editoriales sin alterar productos ni el resto de la tienda.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indica si la petición pública actual pertenece al blog editorial.
 */
function elmercado_is_blog_image_context_010266(): bool {
	if ( is_admin() || is_feed() ) {
		return false;
	}

	return is_home()
		|| is_singular( 'post' )
		|| is_category()
		|| is_tag()
		|| is_author()
		|| is_date();
}

/**
 * Limpia una etiqueta candidata sin convertirla en una lista artificial de keywords.
 */
function elmercado_clean_image_alt_010266( string $value ): string {
	$value = html_entity_decode( wp_strip_all_tags( $value, true ), ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
	$value = preg_replace( '/\s+/u', ' ', $value );

	return trim( (string) $value );
}

/**
 * Detecta títulos/nombres técnicos que no describen la fotografía.
 */
function elmercado_image_alt_candidate_is_generic_010266( string $value ): bool {
	$value = elmercado_clean_image_alt_010266( $value );

	if ( '' === $value ) {
		return true;
	}

	$normalized = strtolower( remove_accents( $value ) );
	$normalized = preg_replace( '/\.(?:jpe?g|png|gif|webp|avif)$/i', '', $normalized );
	$normalized = trim( (string) preg_replace( '/[_-]+/', ' ', (string) $normalized ) );

	if ( ! preg_match( '/[a-z]/', $normalized ) ) {
		return true;
	}

	if ( preg_match( '/^[a-f0-9]{8,}$/', str_replace( ' ', '', $normalized ) ) ) {
		return true;
	}

	return 1 === preg_match(
		'/^(?:img|image|imagen|dsc|dscn|pxl|screenshot|captura(?: de pantalla)?|whatsapp image|photo|foto|download|untitled)(?:\s+\d[\d\s-]*)?$/',
		$normalized
	);
}

/**
 * Convierte un nombre de archivo semántico en texto legible.
 */
function elmercado_image_filename_candidate_010266( int $attachment_id ): string {
	$file = (string) get_attached_file( $attachment_id );

	if ( '' === $file ) {
		return '';
	}

	$name = pathinfo( $file, PATHINFO_FILENAME );
	$name = preg_replace( '/-(?:scaled|rotated)$/i', '', (string) $name );
	$name = preg_replace( '/-\d+x\d+$/i', '', (string) $name );
	$name = str_replace( array( '-', '_' ), ' ', (string) $name );

	return elmercado_clean_image_alt_010266( $name );
}

/**
 * Devuelve un ALT descriptivo, priorizando siempre el texto editorial ya guardado.
 */
function elmercado_blog_image_alt_010266( int $attachment_id, int $post_id = 0 ): string {
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$stored = elmercado_clean_image_alt_010266( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
	if ( '' !== $stored ) {
		return $stored;
	}

	$attachment = get_post( $attachment_id );
	if ( $attachment instanceof WP_Post ) {
		$title = elmercado_clean_image_alt_010266( (string) $attachment->post_title );
		if ( ! elmercado_image_alt_candidate_is_generic_010266( $title ) ) {
			return $title;
		}
	}

	$filename = elmercado_image_filename_candidate_010266( $attachment_id );
	if ( ! elmercado_image_alt_candidate_is_generic_010266( $filename ) ) {
		return $filename;
	}

	if ( $post_id <= 0 && is_singular( 'post' ) ) {
		$post_id = (int) get_queried_object_id();
	}

	if ( $post_id > 0 && 'post' === get_post_type( $post_id ) ) {
		return elmercado_clean_image_alt_010266( (string) get_the_title( $post_id ) );
	}

	return '';
}

/**
 * Atributos coherentes para imágenes generadas por las APIs de adjuntos de WordPress.
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attr, WP_Post $attachment, $size ): array {
		if ( ! elmercado_is_blog_image_context_010266() ) {
			return $attr;
		}

		$is_decorative = 'presentation' === ( $attr['role'] ?? '' ) || 'true' === ( $attr['aria-hidden'] ?? '' );
		$post_id       = in_the_loop() ? (int) get_the_ID() : (int) get_queried_object_id();
		$post_id       = 'post' === get_post_type( $post_id ) ? $post_id : 0;
		$is_hero       = is_singular( 'post' )
			&& $post_id > 0
			&& (int) get_post_thumbnail_id( $post_id ) === (int) $attachment->ID;

		if ( ! $is_decorative && '' === trim( (string) ( $attr['alt'] ?? '' ) ) ) {
			$alt = elmercado_blog_image_alt_010266( (int) $attachment->ID, $post_id );
			if ( '' !== $alt ) {
				$attr['alt'] = $alt;
			}
		}

		$attr['decoding'] = 'async';

		if ( $is_hero ) {
			$attr['loading']       = 'eager';
			$attr['fetchpriority'] = 'high';
			if ( empty( $attr['sizes'] ) ) {
				$attr['sizes'] = '(max-width: 820px) calc(100vw - 32px), 100vw';
			}
		} else {
			if ( empty( $attr['loading'] ) ) {
				$attr['loading'] = 'lazy';
			}
			if ( empty( $attr['sizes'] ) ) {
				$attr['sizes'] = '(max-width: 860px) calc(100vw - 36px), 800px';
			}
		}

		return $attr;
	},
	20,
	3
);

/**
 * Completa imágenes insertadas en el cuerpo de la entrada, también en HTML legado.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( ! elmercado_is_blog_image_context_010266() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( '' === $content || ! str_contains( strtolower( $content ), '<img' ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $content;
		}

		$post_id   = (int) get_the_ID();
		$processor = new WP_HTML_Tag_Processor( $content );

		while ( $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
			$is_decorative = 'presentation' === (string) $processor->get_attribute( 'role' )
				|| 'true' === (string) $processor->get_attribute( 'aria-hidden' );
			$class         = (string) $processor->get_attribute( 'class' );
			$attachment_id = 0;

			if ( preg_match( '/(?:^|\s)wp-image-(\d+)(?:\s|$)/', $class, $match ) ) {
				$attachment_id = (int) $match[1];
			}

			if ( $attachment_id <= 0 ) {
				$src = (string) $processor->get_attribute( 'src' );
				if ( '' !== $src && function_exists( 'attachment_url_to_postid' ) ) {
					$attachment_id = (int) attachment_url_to_postid( $src );
				}
			}

			$alt = $processor->get_attribute( 'alt' );
			if ( ! $is_decorative && ( null === $alt || '' === trim( (string) $alt ) ) && $attachment_id > 0 ) {
				$generated_alt = elmercado_blog_image_alt_010266( $attachment_id, $post_id );
				if ( '' !== $generated_alt ) {
					$processor->set_attribute( 'alt', $generated_alt );
				}
			}

			$processor->set_attribute( 'decoding', 'async' );
			if ( null === $processor->get_attribute( 'loading' ) ) {
				$processor->set_attribute( 'loading', 'lazy' );
			}
			if ( null === $processor->get_attribute( 'sizes' ) ) {
				$processor->set_attribute( 'sizes', '(max-width: 860px) calc(100vw - 36px), 800px' );
			}
		}

		return $processor->get_updated_html();
	},
	25
);
