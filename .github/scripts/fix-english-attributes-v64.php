<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$backup_key = 'mdo_english_attribute_backup_20260818_v64';
$backup = get_option( $backup_key, array() );
if ( ! is_array( $backup ) ) { $backup = array(); }
$report = array( 'backup_key' => $backup_key, 'explicit' => array(), 'normalized_slugs' => array(), 'normalized_names' => array() );

$set = static function ( string $taxonomy, string $native_slug, string $name, string $slug ) use ( &$backup, &$report ): void {
	$term = get_term_by( 'slug', $native_slug, $taxonomy );
	if ( ! $term instanceof WP_Term ) {
		$report['explicit'][] = array( 'taxonomy' => $taxonomy, 'native_slug' => $native_slug, 'status' => 'not-found' );
		return;
	}
	$id = (int) $term->term_id;
	foreach ( array( '_en_US_name', '_en_US_slug', '_en_US_published' ) as $key ) {
		if ( ! isset( $backup[ $id ][ $key ] ) ) { $backup[ $id ][ $key ] = (string) get_term_meta( $id, $key, true ); }
	}
	update_term_meta( $id, '_en_US_name', '<span data-no-translation>' . esc_html( $name ) . '</span>' );
	update_term_meta( $id, '_en_US_slug', sanitize_title( $slug ) );
	update_term_meta( $id, '_en_US_published', '1' );
	$report['explicit'][] = array( 'taxonomy' => $taxonomy, 'term_id' => $id, 'native_slug' => $native_slug, 'name' => $name, 'slug' => sanitize_title( $slug ) );
};

/* Known untranslated values found by the production inventory. */
$set( 'pa_con-dop', 'si', 'Yes', 'yes' );
$set( 'pa_curacion', '24-36-meses', '24–36 months', '24-36-months' );
$set( 'pa_curacion', '36-48-meses', '36–48 months', '36-48-months' );
$set( 'pa_curacion', 'menos-de-24-meses', 'Under 24 months', 'under-24-months' );
$set( 'pa_preparacion', 'codillo', 'Hock', 'hock' );
$set( 'pa_preparacion', 'taco', 'Ham chunk', 'ham-chunk' );
$set( 'pa_preparacion', 'virutas', 'Ham shavings', 'ham-shavings' );
$set( 'pa_tamano', '45-5kg-con-dop-los-pedroches', '4.5–5 kg, PDO Los Pedroches', '4-5-5-kg-pdo-los-pedroches' );
$set( 'pa_tamano', '5-55kg-con-dop-los-pedroches', '5–5.5 kg, PDO Los Pedroches', '5-5-5-kg-pdo-los-pedroches' );
$set( 'pa_tamano', 'carne-de-cerdo-de-bellota-100-iberico-con-dop-los-pedroches', '100% Iberian acorn-fed pork, PDO Los Pedroches', '100-iberian-acorn-fed-pork-pdo-los-pedroches' );
$set( 'pa_tamano', 'pack-carne-de-cerdo-de-bellota-100-iberico-con-dop-los-pedroches', '100% Iberian acorn-fed pork pack, PDO Los Pedroches', '100-iberian-acorn-fed-pork-pack-pdo-los-pedroches' );
$set( 'pa_tamano', 'paleta-de-cebo-de-campo-100-iberica-de-45-5kg-con-dop-los-pedroches', '4.5–5 kg 100% Iberian free-range grain-fed shoulder ham, PDO Los Pedroches', '4-5-5-kg-100-iberian-free-range-grain-fed-shoulder-ham-pdo-los-pedroches' );

if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
	foreach ( (array) wc_get_attribute_taxonomies() as $attribute ) {
		$taxonomy = 'pa_' . $attribute->attribute_name;
		if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) { continue; }
		foreach ( $terms as $term ) {
			$id = (int) $term->term_id;
			$en_name = (string) get_term_meta( $id, '_en_US_name', true );
			if ( '' === trim( $en_name ) ) { continue; }
			$plain = trim( wp_strip_all_tags( $en_name ) );

			/* English numeric/typographic formatting and PDO wording. */
			$clean = preg_replace( '/(?<=\d),(?=\d)/u', '.', $plain );
			$clean = preg_replace( '/(?<=\d)\s*Kg\b/u', ' kg', (string) $clean );
			$clean = preg_replace( '/\bDOP\b/u', 'PDO', (string) $clean );
			$clean = preg_replace( '/\s+con\s+PDO\b/iu', ', PDO', (string) $clean );
			$clean = preg_replace( '/(?<=\d(?:\.\d)?)-(?=\d)/u', '–', (string) $clean );
			if ( is_string( $clean ) && $clean !== $plain ) {
				if ( ! isset( $backup[ $id ]['_en_US_name'] ) ) { $backup[ $id ]['_en_US_name'] = $en_name; }
				$en_name = '<span data-no-translation>' . esc_html( $clean ) . '</span>';
				update_term_meta( $id, '_en_US_name', $en_name );
				$plain = $clean;
				$report['normalized_names'][] = array( 'taxonomy' => $taxonomy, 'term_id' => $id, 'name' => $clean );
			}

			/* The English slug must be derived from the reviewed English display name. */
			$new_slug = sanitize_title( $plain );
			$old_slug = (string) get_term_meta( $id, '_en_US_slug', true );
			if ( '' !== $new_slug && $new_slug !== $old_slug ) {
				if ( ! isset( $backup[ $id ]['_en_US_slug'] ) ) { $backup[ $id ]['_en_US_slug'] = $old_slug; }
				update_term_meta( $id, '_en_US_slug', $new_slug );
				$report['normalized_slugs'][] = array( 'taxonomy' => $taxonomy, 'term_id' => $id, 'old' => $old_slug, 'new' => $new_slug );
			}
		}
	}
}

update_option( $backup_key, $backup, false );
if ( function_exists( 'wc_delete_product_transients' ) ) { wc_delete_product_transients(); }
wp_cache_flush();
echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
