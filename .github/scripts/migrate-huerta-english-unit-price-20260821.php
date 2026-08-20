<?php
/**
 * Production migration for La Huerta de Ana Mary:
 * - keep the 46 known English titles/slugs canonical;
 * - ensure Falang English publishing flags and content remain complete;
 * - detect genuine source per-kg pricing and append only a bold basis label,
 *   never the supplier's numeric amount.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

global $wpdb;

$title_map = array(
	12699 => 'White Potatoes',
	12702 => 'Zucchini',
	12706 => 'Broccoli',
	12709 => '20 kg White Kennebec Potatoes',
	12711 => '8 Zucchini Flowers',
	12715 => 'Italian Pepper',
	12718 => 'Dried Onions (kg)',
	12721 => 'Green Lamuyo Peppers',
	12724 => 'Approx. 300 g Bag of Padrón Peppers',
	12727 => 'Picaguin Extra-Hot Sauce',
	12730 => 'Artisan Sweet-and-Sour Peppers, 720 ml',
	12733 => 'Fried Peppers in Olive Oil, 314 ml',
	12735 => 'Eggplant',
	12740 => 'Preserved Young Garlic, 314 ml',
	12743 => 'Hot Green Peppers in Vinegar, 314 ml',
	12746 => '20 kg Red Pontiac Potatoes',
	12748 => 'Artisan Preserved Leeks, 720 ml',
	12751 => 'Artisan Sweet Roasted Peppers, 314 ml',
	12754 => 'Red Pontiac Potatoes',
	12757 => 'Hot Peppers',
	12761 => 'Cabbage',
	12764 => 'Artisan Leek Jam, 250 ml',
	12767 => 'Artisan Pepper Jam, 250 ml',
	12770 => 'Artisan Tomato Jam, 250 ml',
	12773 => 'Chili Peppers in Vinegar, 720 ml',
	12775 => '12 Jars of Leek Jam, 250 ml',
	12779 => '12 Jars of Tomato Jam, 250 ml',
	12783 => '12 Jars of Spicy Roasted Peppers, 314 ml',
	12786 => '12 Jars of Preserved Leeks, 720 ml',
	12789 => '12 Jars of Hot Green Peppers in Vinegar, 314 ml',
	12793 => '12 Jars of Pepper Jam, 250 ml',
	12797 => 'Artisan Tomato Fritada, 314 ml',
	12799 => 'Padrón Peppers (kg)',
	12802 => 'Artisan Spicy Roasted Peppers, 314 ml',
	12806 => 'Cucumber',
	12809 => '12 Jars of Artisan Tomato Fritada, 314 ml',
	12813 => '12 Jars of Sweet-and-Sour Peppers, 720 ml',
	12816 => '12 Jars of Sweet Roasted Peppers',
	12819 => '12 Jars of Fried Peppers in Olive Oil, 314 ml',
	12825 => '10 kg of Fresh Vegetables',
	12829 => '4 Weekly 7 kg Vegetable Boxes',
	12832 => 'Canela Beans',
	12837 => 'White Kidney Beans',
	12841 => 'Pinto Beans',
	12845 => 'Pedrosillano Chickpeas',
	12849 => 'Pardina Lentils',
);

function mdo_huerta_migration_per_kg( string $value ): bool {
	if ( class_exists( 'MDO_Huerta_Unit_Price' ) ) {
		return MDO_Huerta_Unit_Price::source_text_is_per_kg( $value );
	}
	$plain = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$plain = preg_replace( '/\s+/u', ' ', $plain );
	return (bool) preg_match( '/(?:€|\beur(?:os?)?\b|\bprecio\b).{0,25}(?:\/|por|el)?\s*(?:kg|kilo(?:gramo)?s?)\b/iu', (string) $plain );
}

function mdo_huerta_migration_label( string $content, string $label ): string {
	$content = (string) preg_replace( '~\s*<p\b[^>]*class=["\'][^"\']*\bemdo-source-unit-price\b[^"\']*["\'][^>]*>.*?</p>\s*~isu', "\n", $content );
	$content = trim( $content );
	$line = '<p class="emdo-source-unit-price"><strong>' . esc_html( $label ) . '</strong></p>';
	return '' === $content ? $line : $content . "\n" . $line;
}

function mdo_huerta_migration_polish_en( string $content ): string {
	$replacements = array(
		'/\bprecio\s+por\s+kilo\b/iu' => 'Price per kg',
		'/\bprecio\s+por\s+kg\b/iu' => 'Price per kg',
		'/\bconservas\b/iu' => 'preserves',
		'/\bhortalizas\b/iu' => 'vegetables',
		'/\blegumbres\b/iu' => 'pulses',
		'/\bingredientes\b/iu' => 'ingredients',
		'/\binformaci[oó]n\s+nutricional\b/iu' => 'nutritional information',
		'/\bconservaci[oó]n\b/iu' => 'storage',
		'/\baproximadamente\b/iu' => 'approximately',
	);
	foreach ( $replacements as $pattern => $replacement ) {
		$content = (string) preg_replace( $pattern, $replacement, $content );
	}
	return $content;
}

$ids = $wpdb->get_col(
	"SELECT DISTINCT p.ID
	 FROM {$wpdb->posts} p
	 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
	 WHERE p.post_type = 'product'
	   AND pm.meta_key = '_emdo_source_url'
	   AND pm.meta_value LIKE '%lahuertadeanamary.com%'
	 ORDER BY p.ID ASC"
) ?: array();

$out = array(
	'scanned' => 0,
	'english_complete' => 0,
	'per_kg' => 0,
	'per_kg_ids' => array(),
	'errors' => array(),
	'products' => array(),
);

foreach ( $ids as $raw_id ) {
	$id = absint( $raw_id );
	$post = get_post( $id );
	if ( ! $id || ! $post instanceof WP_Post ) { continue; }
	$out['scanned']++;

	if ( isset( $title_map[ $id ] ) ) {
		$title = $title_map[ $id ];
		update_post_meta( $id, '_en_US_post_title', $title );
		update_post_meta( $id, '_en_US_post_name', sanitize_title( $title ) );
	}
	update_post_meta( $id, '_en_US_published', '1' );
	update_post_meta( $id, '_en_US_ready', '1' );

	$source_url = (string) get_post_meta( $id, '_emdo_source_url', true );
	$evidence = '';
	$per_kg = false;

	$source_id = absint( get_post_meta( $id, '_emdo_source_product_id', true ) );
	if ( $source_id && class_exists( 'MDO_Database' ) ) {
		$table = MDO_Database::table( 'source_products' );
		$raw_payload = $wpdb->get_var( $wpdb->prepare( "SELECT source_payload FROM {$table} WHERE id=%d LIMIT 1", $source_id ) );
		$payload = json_decode( (string) $raw_payload, true );
		if ( is_array( $payload ) ) {
			foreach ( array( 'description', 'title', 'price_text', 'unit_price', 'price_label' ) as $field ) {
				if ( isset( $payload[ $field ] ) && mdo_huerta_migration_per_kg( (string) $payload[ $field ] ) ) {
					$per_kg = true;
					$evidence = 'payload:' . $field;
					break;
				}
			}
		}
	}

	/* Previous cleanup versions removed numeric price fragments from the payload,
	 * so existing products get a one-off source-page verification. */
	if ( ! $per_kg && $source_url ) {
		$response = wp_remote_get( $source_url, array(
			'timeout' => 22,
			'redirection' => 5,
			'user-agent' => 'Mozilla/5.0 (compatible; EMDO/1.0.23; +https://www.elmercadodeorigen.com/)',
			'headers' => array( 'Accept-Language' => 'es-ES,es;q=0.9' ),
		) );
		if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) >= 200 && (int) wp_remote_retrieve_response_code( $response ) < 400 ) {
			$body = (string) wp_remote_retrieve_body( $response );
			if ( mdo_huerta_migration_per_kg( $body ) ) {
				$per_kg = true;
				$evidence = 'source-html';
			}
		}
	}

	$spanish = (string) $post->post_content;
	$english = mdo_huerta_migration_polish_en( (string) get_post_meta( $id, '_en_US_post_content', true ) );
	if ( $per_kg ) {
		update_post_meta( $id, '_emdo_huerta_price_basis', 'kg' );
		$spanish = mdo_huerta_migration_label( $spanish, 'Precio por kilo' );
		$english = mdo_huerta_migration_label( $english, 'Price per kg' );
		$out['per_kg']++;
		$out['per_kg_ids'][] = $id;
	} else {
		delete_post_meta( $id, '_emdo_huerta_price_basis' );
	}

	if ( $spanish !== (string) $post->post_content ) {
		$wpdb->update( $wpdb->posts, array( 'post_content' => $spanish ), array( 'ID' => $id ), array( '%s' ), array( '%d' ) );
		clean_post_cache( $id );
	}
	if ( metadata_exists( 'post', $id, '_emdo_huerta_description_canonical' ) ) {
		update_post_meta( $id, '_emdo_huerta_description_canonical', $spanish );
	}
	if ( '' !== trim( wp_strip_all_tags( $english ) ) ) {
		update_post_meta( $id, '_en_US_post_content', $english );
	}

	$en_title = trim( (string) get_post_meta( $id, '_en_US_post_title', true ) );
	$en_slug = trim( (string) get_post_meta( $id, '_en_US_post_name', true ) );
	$en_content = trim( wp_strip_all_tags( (string) get_post_meta( $id, '_en_US_post_content', true ) ) );
	$complete = '' !== $en_title && '' !== $en_slug && '' !== $en_content && '1' === (string) get_post_meta( $id, '_en_US_published', true );
	if ( $complete ) { $out['english_complete']++; }
	else { $out['errors'][] = array( 'id' => $id, 'reason' => 'incomplete English fields' ); }

	$out['products'][] = array(
		'id' => $id,
		'native_title' => html_entity_decode( (string) $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		'english_title' => $en_title,
		'english_slug' => $en_slug,
		'per_kg' => $per_kg,
		'evidence' => $evidence,
	);
}

wp_cache_flush();
if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
if ( function_exists( 'w3tc_flush_all' ) ) { w3tc_flush_all(); }
if ( has_action( 'litespeed_purge_all' ) ) { do_action( 'litespeed_purge_all' ); }

echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ), "\n";
if ( $out['scanned'] !== 46 || $out['english_complete'] !== $out['scanned'] || $out['errors'] ) { exit( 2 ); }
