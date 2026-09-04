<?php
/**
 * One-off production task: create/update the Montjam 6–6.5 kg / €225 Special.
 *
 * Uses the same mdo_promotion post type and metadata consumed by the EMDO
 * Specials backend. The task is deliberately idempotent and preserves every
 * other promotion.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( "WordPress not loaded\n" );
}

const MDO_MONTJAM_SPECIAL_SEED = 'montjam-jamon-bellota-100-iberico-6-65-225-v1';
const MDO_MONTJAM_PRODUCT_ID   = 14264;
const MDO_MONTJAM_VARIATION_ID = 14280;
const MDO_MONTJAM_IMAGE_ID     = 14269;
const MDO_MONTJAM_VENDOR_ID    = 4723;

if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
	fwrite( STDERR, "WooCommerce is not available\n" );
	exit( 2 );
}
if ( ! post_type_exists( 'mdo_promotion' ) || ! class_exists( 'MDO_Specials' ) || ! class_exists( 'MDO_Promotions' ) ) {
	fwrite( STDERR, "EMDO Specials is not available\n" );
	exit( 3 );
}

$product   = wc_get_product( MDO_MONTJAM_PRODUCT_ID );
$variation = wc_get_product( MDO_MONTJAM_VARIATION_ID );

if ( ! $product || 'variable' !== $product->get_type() ) {
	fwrite( STDERR, "Expected Montjam variable product #" . MDO_MONTJAM_PRODUCT_ID . " not found\n" );
	exit( 4 );
}
if ( ! $variation || 'variation' !== $variation->get_type() || MDO_MONTJAM_PRODUCT_ID !== (int) $variation->get_parent_id() ) {
	fwrite( STDERR, "Expected Montjam variation #" . MDO_MONTJAM_VARIATION_ID . " not found under parent\n" );
	exit( 5 );
}

$price = (float) $variation->get_price();
if ( abs( $price - 225.0 ) > 0.001 ) {
	fwrite( STDERR, "Variation price is not 225 EUR; found {$price}\n" );
	exit( 6 );
}

$image_id = MDO_MONTJAM_IMAGE_ID;
if ( 'attachment' !== get_post_type( $image_id ) || ! wp_attachment_is_image( $image_id ) ) {
	$image_id = (int) get_post_thumbnail_id( MDO_MONTJAM_PRODUCT_ID );
}
if ( ! $image_id ) {
	fwrite( STDERR, "Montjam product image is missing\n" );
	exit( 7 );
}

// Resolve the producer from the same repository used by the Specials backend.
$supplier_id = 0;
if ( class_exists( 'MDO_Supplier_Repository' ) && method_exists( 'MDO_Supplier_Repository', 'all' ) ) {
	foreach ( (array) MDO_Supplier_Repository::all() as $supplier ) {
		$name = preg_replace( '/[^a-z0-9]+/', '', remove_accents( strtolower( (string) ( $supplier['name'] ?? '' ) ) ) );
		$vendor_user_id = (int) ( $supplier['vendor_user_id'] ?? 0 );
		if ( 'montjam' === $name || MDO_MONTJAM_VENDOR_ID === $vendor_user_id ) {
			$supplier_id = (int) ( $supplier['id'] ?? 0 );
			break;
		}
	}
}

// Choose a valid internal type when the class exposes its type list.
$type = 'offer';
if ( method_exists( 'MDO_Specials', 'types' ) ) {
	try {
		$types = (array) MDO_Specials::types();
		if ( $types ) {
			$preferred = array( 'offer', 'oferta', 'discount', 'price', 'special' );
			$chosen = '';
			foreach ( $preferred as $needle ) {
				foreach ( $types as $key => $label ) {
					$haystack = remove_accents( strtolower( (string) $key . ' ' . (string) $label ) );
					if ( false !== strpos( $haystack, $needle ) ) {
						$chosen = (string) $key;
						break 2;
					}
				}
			}
			$type = $chosen ?: (string) array_key_first( $types );
		}
	} catch ( Throwable $e ) {
		// Keep the stable fallback; type is editorial and does not govern pricing.
	}
}

$existing = get_posts(
	array(
		'post_type'      => 'mdo_promotion',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_mdo_promo_seed_key',
		'meta_value'     => MDO_MONTJAM_SPECIAL_SEED,
	)
);

$post_id = $existing ? (int) $existing[0] : 0;
if ( ! $post_id ) {
	$legacy = get_page_by_path( 'jamon-bellota-100-iberico-montjam-6-65-kg-225-euros', OBJECT, 'mdo_promotion' );
	$post_id = $legacy ? (int) $legacy->ID : 0;
}

$es = array(
	'title'      => 'Jamón de bellota 100% ibérico Montjam · 6–6,5 kg por 225 €',
	'slug'       => 'jamon-bellota-100-iberico-montjam-6-65-kg-225-euros',
	'eyebrow'    => 'Especial Montjam',
	'summary'    => 'Una pieza de jamón de bellota 100% ibérico Montjam, de 6 a 6,5 kg, disponible por 225 €.',
	'benefit'    => 'Elige la pieza de 6 a 6,5 kg y disfruta de este jamón de bellota 100% ibérico de Montjam por 225 €.',
	'content'    => '<p>Una ocasión para disfrutar de uno de los grandes ibéricos de Montjam: jamón de bellota 100% ibérico, en pieza de 6 a 6,5 kg, por 225 €.</p><p>Selecciona el peso de 6 a 6,5 kg en la ficha del producto.</p>',
	'cta_label'  => 'Ver el jamón',
	'conditions' => 'Precio correspondiente a la variante de 6 a 6,5 kg del jamón de bellota 100% ibérico Montjam.',
);
$en = array(
	'title'      => 'Montjam 100% Iberian acorn-fed ham · 6–6.5 kg for €225',
	'slug'       => 'montjam-100-iberian-acorn-fed-ham-6-65-kg-225-euros',
	'eyebrow'    => 'Montjam special',
	'summary'    => 'A 6–6.5 kg Montjam 100% Iberian acorn-fed ham, available for €225.',
	'benefit'    => 'Choose the 6–6.5 kg piece and enjoy this Montjam 100% Iberian acorn-fed ham for €225.',
	'content'    => '<p>A special opportunity to enjoy Montjam 100% Iberian acorn-fed ham: a 6–6.5 kg whole piece for €225.</p><p>Select the 6–6.5 kg weight on the product page.</p>',
	'cta_label'  => 'View the ham',
	'conditions' => 'Price applies to the 6–6.5 kg variation of Montjam 100% Iberian acorn-fed ham.',
);

if ( ! $post_id ) {
	$created = wp_insert_post(
		array(
			'post_type'    => 'mdo_promotion',
			'post_status'  => 'publish',
			'post_title'   => $es['title'],
			'post_name'    => $es['slug'],
			'post_excerpt' => $es['summary'],
			'post_content' => $es['content'],
			'menu_order'   => 0,
		),
		true
	);
	if ( is_wp_error( $created ) ) {
		fwrite( STDERR, "Could not create Special: " . $created->get_error_message() . "\n" );
		exit( 8 );
	}
	$post_id = (int) $created;
}

// Use the actual variation permalink so the 6–6.5 kg choice is carried through.
$cta_url = $variation->get_permalink();
if ( ! $cta_url ) {
	$cta_url = get_permalink( MDO_MONTJAM_PRODUCT_ID );
}

$shared = array(
	'_mdo_promo_seed_key'         => MDO_MONTJAM_SPECIAL_SEED,
	'_mdo_promo_type'             => $type,
	'_mdo_promo_supplier_id'      => $supplier_id,
	'_mdo_promo_start'            => current_time( 'Y-m-d' ),
	'_mdo_promo_end'              => '',
	'_mdo_promo_coupon'           => '',
	'_mdo_promo_product_ids'      => (string) MDO_MONTJAM_PRODUCT_ID,
	'_mdo_promo_image_product_id' => MDO_MONTJAM_PRODUCT_ID,
	'_mdo_promo_cta_url'          => esc_url_raw( $cta_url ),
	'_mdo_promo_featured_home'    => '1',
);
foreach ( $shared as $key => $value ) {
	update_post_meta( $post_id, $key, $value );
}
foreach ( array( 'es' => $es, 'en' => $en ) as $lang => $copy ) {
	foreach ( $copy as $field => $value ) {
		update_post_meta( $post_id, '_mdo_promo_' . $field . '_' . $lang, $value );
	}
}

set_post_thumbnail( $post_id, $image_id );

$updated = wp_update_post(
	array(
		'ID'           => $post_id,
		'post_status'  => 'publish',
		'post_title'   => $es['title'],
		'post_name'    => $es['slug'],
		'post_excerpt' => $es['summary'],
		'post_content' => $es['content'],
		'menu_order'   => 0,
	),
	true
);
if ( is_wp_error( $updated ) ) {
	fwrite( STDERR, "Could not update Special: " . $updated->get_error_message() . "\n" );
	exit( 9 );
}

clean_post_cache( $post_id );
wp_cache_flush();

$active = (bool) MDO_Promotions::is_active( $post_id );
$render = class_exists( 'MDO_Home_Featured_Special' ) ? (string) MDO_Home_Featured_Special::render() : '';
$render_ok = '' !== $render
	&& false !== strpos( $render, 'data-mdo-home-featured-special="' . $post_id . '"' )
	&& false !== strpos( $render, 'Montjam' )
	&& false !== strpos( $render, '225' );

$result = array(
	'post_id'       => $post_id,
	'status'        => get_post_status( $post_id ),
	'active'        => $active,
	'featured_home' => get_post_meta( $post_id, '_mdo_promo_featured_home', true ),
	'title'         => get_the_title( $post_id ),
	'permalink'     => MDO_Specials::permalink( $post_id, 'es' ),
	'cta_url'       => get_post_meta( $post_id, '_mdo_promo_cta_url', true ),
	'product_id'    => MDO_MONTJAM_PRODUCT_ID,
	'variation_id'  => MDO_MONTJAM_VARIATION_ID,
	'variation_price' => $price,
	'image_id'      => (int) get_post_thumbnail_id( $post_id ),
	'supplier_id'   => $supplier_id,
	'type'          => $type,
	'home_render_ok'=> $render_ok,
);

echo 'MONTJAM_SPECIAL_RESULT: ' . wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

if ( ! $active || ! $render_ok ) {
	fwrite( STDERR, "Special was stored but did not pass active/home renderer verification\n" );
	exit( 10 );
}
