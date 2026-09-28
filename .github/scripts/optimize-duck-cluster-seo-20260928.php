<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$duck_ids = get_posts( array(
	'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
	'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck','orderby'=>'ID','order'=>'ASC',
	'emdo_include_hidden_blog_islands'=>true,
) );
if ( 60 !== count( $duck_ids ) ) { throw new Exception( 'Expected 60 duck posts, found ' . count( $duck_ids ) ); }

$carnes = get_term_by( 'slug', 'carnes', 'category' );
if ( ! $carnes instanceof WP_Term ) { throw new Exception( 'Missing Carnes category' ); }

$pato = get_term_by( 'slug', 'pato', 'category' );
if ( ! $pato instanceof WP_Term ) {
	$created = wp_insert_term( 'Pato', 'category', array( 'slug'=>'pato', 'parent'=>(int)$carnes->term_id ) );
	if ( is_wp_error( $created ) ) { throw new Exception( $created->get_error_message() ); }
	$pato = get_term( (int) $created['term_id'], 'category' );
}
if ( ! $pato instanceof WP_Term ) { throw new Exception( 'Could not resolve Pato category' ); }

$pillar = array();
foreach ( $duck_ids as $id ) {
	$key = (string) get_post_meta( $id, '_emdo_seo_landing_key', true );
	$pillar[ $key ] = get_permalink( $id );
}
$description = '<p>Guías para conocer y cocinar pato con criterio: cortes frescos, técnicas de cocción, productos curados y elaboraciones como foie gras, confit, paté, mousse y rillettes. El clúster reúne desde conceptos básicos y conservación hasta recetas, salsas, guarniciones, cantidades y maridajes.</p>'
	. '<p>Si empiezas desde cero, consulta la <a href="' . esc_url( $pillar['duck-01'] ) . '">guía de carne de pato y sus cortes</a>. Para profundizar por producto, tienes la guía del <a href="' . esc_url( $pillar['duck-03'] ) . '">magret de pato</a>, cómo <a href="' . esc_url( $pillar['duck-02'] ) . '">elegir foie gras</a>, la explicación del <a href="' . esc_url( $pillar['duck-04'] ) . '">confit de pato</a> y la guía sobre <a href="' . esc_url( $pillar['duck-05'] ) . '">jamón de pato</a>. Desde cada artículo encontrarás contenidos relacionados para pasar de la elección del producto a su preparación y servicio.</p>';

$updated = wp_update_term( (int)$pato->term_id, 'category', array(
	'name'=>'Pato','slug'=>'pato','parent'=>(int)$carnes->term_id,'description'=>$description,
) );
if ( is_wp_error( $updated ) ) { throw new Exception( $updated->get_error_message() ); }
update_term_meta( (int)$pato->term_id, '_emdo_category_hub', 'duck-20260928' );

$carnes_desc = (string) term_description( $carnes );
$pato_link = get_category_link( $pato );
if ( ! is_wp_error( $pato_link ) && false === strpos( $carnes_desc, (string)$pato_link ) ) {
	$carnes_desc .= '<p>Dentro de la familia de carnes también puedes explorar nuestras <a href="' . esc_url( $pato_link ) . '">guías sobre pato, magret, foie y confit</a>, con técnicas y criterios específicos para estas piezas y elaboraciones.</p>';
	$r = wp_update_term( (int)$carnes->term_id, 'category', array( 'description'=>$carnes_desc ) );
	if ( is_wp_error( $r ) ) { throw new Exception( $r->get_error_message() ); }
}

$homepage_keys = array( 'duck-01','duck-02','duck-03','duck-04','duck-05' );
$rows = array();

foreach ( $duck_ids as $id ) {
	$id = (int) $id;
	$key = (string) get_post_meta( $id, '_emdo_seo_landing_key', true );

	if ( '' === (string) get_post_meta( $id, '_emdo_duck_architecture_backup_20260928', true ) ) {
		update_post_meta( $id, '_emdo_duck_architecture_backup_20260928', wp_json_encode( array(
			'categories'=>wp_get_post_categories($id),
			'hidden_listing'=>(string)get_post_meta($id,'_emdo_blog_hidden_listing',true),
			'hidden_navigation'=>(string)get_post_meta($id,'_emdo_seo_landing_hidden_navigation',true),
		), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES ) );
	}

	wp_set_post_categories( $id, array( (int)$pato->term_id ), false );
	delete_post_meta( $id, '_emdo_blog_hidden_listing' );
	delete_post_meta( $id, '_emdo_seo_landing_hidden_navigation' );
	update_post_meta( $id, '_emdo_blog_primary_category', 'pato' );
	update_post_meta( $id, '_emdo_duck_architecture', 'public-category-hub-20260928' );

	if ( in_array( $key, $homepage_keys, true ) ) {
		delete_post_meta( $id, '_emdo_duck_home_suppress' );
	} else {
		update_post_meta( $id, '_emdo_duck_home_suppress', '1' );
	}

	$title = (string) get_the_title( $id );
	$desc  = trim( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $id ) ) );
	update_post_meta( $id, '_yoast_wpseo_title', $title );
	update_post_meta( $id, 'rank_math_title', $title );
	if ( '' !== $desc ) {
		update_post_meta( $id, '_yoast_wpseo_metadesc', $desc );
		update_post_meta( $id, 'rank_math_description', $desc );
	}
	delete_post_meta( $id, '_yoast_wpseo_meta-robots-noindex' );
	delete_post_meta( $id, 'rank_math_robots' );
	clean_post_cache( $id );

	$rows[] = array(
		'id'=>$id,'key'=>$key,'categories'=>wp_get_post_categories($id),
		'hidden'=>(string)get_post_meta($id,'_emdo_blog_hidden_listing',true),
		'home_suppress'=>(string)get_post_meta($id,'_emdo_duck_home_suppress',true),
	);
}

clean_term_cache( (int)$pato->term_id, 'category' );
flush_rewrite_rules( false );

$visible_query = get_posts( array(
	'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
	'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck',
) );
$category_query = get_posts( array(
	'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
	'cat'=>(int)$pato->term_id,
) );

echo wp_json_encode( array(
	'batch'=>'20260928-duck-seo-architecture',
	'category'=>array(
		'id'=>(int)$pato->term_id,'name'=>$pato->name,'slug'=>$pato->slug,
		'parent'=>(int)$carnes->term_id,'url'=>get_category_link($pato),
	),
	'count'=>count($rows),
	'visible_normal_query'=>count($visible_query),
	'category_query'=>count($category_query),
	'homepage_unsuppressed'=>count($homepage_keys),
	'rows'=>$rows,
), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT ) . PHP_EOL;
