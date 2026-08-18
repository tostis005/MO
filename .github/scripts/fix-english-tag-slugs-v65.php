<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$backup_key = 'mdo_english_tag_slug_backup_20260818_v65';
$backup = get_option( $backup_key, array() );
if ( ! is_array( $backup ) ) { $backup = array(); }
$report = array( 'backup_key' => $backup_key, 'changed' => array(), 'unchanged' => 0 );

if ( taxonomy_exists( 'product_tag' ) ) {
	$terms = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => false ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( (string) get_term_meta( $term->term_id, '_en_US_published', true ) !== '1' ) { continue; }
			$name = trim( wp_strip_all_tags( (string) get_term_meta( $term->term_id, '_en_US_name', true ) ) );
			if ( '' === $name ) { continue; }
			$new = sanitize_title( $name );
			$old = sanitize_title( (string) get_term_meta( $term->term_id, '_en_US_slug', true ) );
			if ( '' === $new || $new === $old ) { $report['unchanged']++; continue; }
			if ( ! isset( $backup[ $term->term_id ] ) ) { $backup[ $term->term_id ] = $old; }
			update_term_meta( $term->term_id, '_en_US_slug', $new );
			$report['changed'][] = array(
				'term_id' => (int) $term->term_id,
				'native_slug' => $term->slug,
				'english_name' => $name,
				'old' => $old,
				'new' => $new,
			);
		}
	}
}

update_option( $backup_key, $backup, false );
wp_cache_flush();
echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
