<?php
/**
 * Enlaces editoriales contextuales para Latitudes.
 *
 * Solo se activan si hay productos públicos de este vendedor; jamás crea
 * contenido ficticio ni enlaza a artículos todavía no publicados.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_latitudes_vendor_id_20261015(): int {
	static $id = null;
	if ( null !== $id ) {
		return $id;
	}
	$id   = 0;
	$user = get_user_by( 'slug', 'latitudes' );
	if ( $user instanceof WP_User ) {
		$id = (int) $user->ID;
	} else {
		$candidates = function_exists( 'elmercado_latitudes_vendor_candidates_20261015' )
			? elmercado_latitudes_vendor_candidates_20261015() : array();
		foreach ( $candidates as $candidate ) {
			if ( sanitize_title( $candidate->display_name ) === 'latitudes' ) {
				$id = (int) $candidate->ID;
				break;
			}
		}
	}
	return $id;
}

function elmercado_latitudes_has_public_products_20261015(): bool {
	static $public = null;
	if ( null !== $public ) {
		return $public;
	}
	$public = false;
	$id     = elmercado_latitudes_vendor_id_20261015();
	if ( $id <= 0 || ! function_exists( 'wc_get_product' ) ) {
		return false;
	}
	if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' ) && elmercado_wcfm_vendor_is_disabled_010210( $id ) ) {
		return false;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'author'         => $id,
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	foreach ( $ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product && $product->is_visible() && $product->is_in_stock() ) {
			$public = true;
			return true;
		}
	}
	return false;
}

function elmercado_latitudes_store_url_20261015(): string {
	$id = elmercado_latitudes_vendor_id_20261015();
	return $id > 0 && function_exists( 'wcfmmp_get_store_url' )
		? (string) wcfmmp_get_store_url( $id ) : '';
}

/** Guías editoriales existentes (no se inventan títulos ni URLs). */
function elmercado_latitudes_editorial_guides_20261015( int $limit = 3 ): string {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'category_name'  => 'carnes',
			'posts_per_page' => max( 1, min( 4, $limit ) ),
			'orderby'       => 'date',
			'order'         => 'DESC',
			'no_found_rows'  => true,
		)
	);
	if ( ! $posts ) {
		return '';
	}
	$is_en = function_exists( 'elmercado_blog_products_is_english_010277' )
		&& elmercado_blog_products_is_english_010277();
	$html  = '<div class="emo-latitudes-guides"><h3>' .
		esc_html( $is_en ? 'Useful guides from our blog' : 'Guías para conocer mejor las carnes' ) .
		'</h3><ul>';
	foreach ( $posts as $post ) {
		$html .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
	}
	return $html . '</ul></div>';
}

/** La página principal de la tienda Latitudes, no sus pestañas auxiliares. */
function elmercado_latitudes_is_store_main_20261015(): bool {
	if ( ! function_exists( 'elmercado_vendor_store_is_request_010225' )
		|| ! elmercado_vendor_store_is_request_010225()
		|| ! function_exists( 'elmercado_vendor_store_vendor_id_010225' )
		|| elmercado_vendor_store_vendor_id_010225() !== elmercado_latitudes_vendor_id_20261015() ) {
		return false;
	}
	$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
	$parts = array_values( array_filter( explode( '/', $path ) ) );
	$slug_pos = array_search( 'latitudes', $parts, true );
	return false !== $slug_pos && ! isset( $parts[ $slug_pos + 1 ] );
}

/** Store -> blog. Categoría -> catálogo. */
add_action(
	'wcfmmp_after_store_tabs',
	static function (): void {
		if ( ! elmercado_latitudes_is_store_main_20261015()
			|| ! elmercado_latitudes_has_public_products_20261015() ) {
			return;
		}
		$en   = function_exists( 'elmercado_blog_products_is_english_010277' )
			&& elmercado_blog_products_is_english_010277();
		$term = get_term_by( 'slug', 'carnes', 'product_cat' );
		$url  = $term instanceof WP_Term ? get_term_link( $term ) : '';
		echo '<section class="emo-latitudes-seo" aria-label="Latitudes">';
		echo '<h2>' . esc_html( $en ? 'Latitudes: exotic and premium meats' : 'Latitudes: carnes exóticas y cortes premium' ) . '</h2>';
		echo '<p>' . esc_html(
			$en
				? 'Explore special cuts and meats from different parts of the world, including wagyu, bison and ostrich. See each product for its specific origin and preparation.'
				: 'Una selección de cortes especiales y carnes de distintas procedencias, como wagyu, bisonte o avestruz. En cada ficha puedes consultar las características concretas del producto.'
		) . '</p>';
		if ( ! is_wp_error( $url ) && $url ) {
			echo '<p><a href="' . esc_url( $url ) . '">' . esc_html( $en ? 'Explore our meat selection' : 'Explorar la categoría de carnes' ) . ' &rarr;</a></p>';
		}
		echo elmercado_latitudes_editorial_guides_20261015(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</section>';
	},
	40
);

/** Producto Latitudes -> artículos publicados del blog. */
add_action(
	'woocommerce_after_single_product_summary',
	static function (): void {
		if ( ! is_product() ) {
			return;
		}
		$product_id = get_queried_object_id();
		$owner = function_exists( 'wcfm_get_vendor_id_by_post' )
			? (int) wcfm_get_vendor_id_by_post( $product_id )
			: (int) get_post_field( 'post_author', $product_id );
		if ( $owner !== elmercado_latitudes_vendor_id_20261015()
			|| ! elmercado_latitudes_has_public_products_20261015() ) {
			return;
		}
		echo '<section class="emo-latitudes-seo emo-latitudes-seo--product">';
		echo elmercado_latitudes_editorial_guides_20261015(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</section>';
	},
	18
);

/** Blog pertinente -> tienda Latitudes, sin promocionar artículos ajenos. */
add_filter(
	'the_content',
	static function ( $content ) {
		if ( is_admin() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$title = (string) get_the_title( get_the_ID() );
		if ( ! preg_match( '~\b(wagyu|exótic[ao]s?|bisonte|avestruz|canguro|cocodrilo|cebra)\b~iu', $title )
			|| ! elmercado_latitudes_has_public_products_20261015() ) {
			return $content;
		}
		$url = elmercado_latitudes_store_url_20261015();
		if ( ! $url ) {
			return $content;
		}
		$en = function_exists( 'elmercado_blog_products_is_english_010277' )
			&& elmercado_blog_products_is_english_010277();
		$label = $en ? 'Explore Latitudes products' : 'Descubre la selección de Latitudes';
		return $content . '<aside class="emo-latitudes-blog-cta"><a href="' . esc_url( $url ) . '">' .
			esc_html( $label ) . ' &rarr;</a></aside>';
	},
	10
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="emo-latitudes-links-20261015">
			.emo-latitudes-seo{margin:1.5rem 0;padding:1.5rem;border:1px solid #dfebe3;border-radius:18px;background:#f7faf7;color:#193d31}
			.emo-latitudes-seo h2{font-size:clamp(1.25rem,2vw,1.7rem);margin:0 0 .6rem}
			.emo-latitudes-seo p{max-width:72ch}
			.emo-latitudes-seo a,.emo-latitudes-blog-cta a{color:#205742;text-decoration:underline;text-underline-offset:3px}
			.emo-latitudes-guides{margin-top:1rem}
			.emo-latitudes-guides ul{margin:.5rem 0 0;padding-left:1.25rem}
			.emo-latitudes-guides li+li{margin-top:.45rem}
			.emo-latitudes-seo--product{margin:1.8rem 0}
			.emo-latitudes-blog-cta{margin:2rem 0;padding:1rem 1.25rem;background:#f7faf7;border-left:3px solid #205742}
		</style>';
	},
	99
);
