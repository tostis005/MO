<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$duck_ids = get_posts( array(
	'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
	'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck','orderby'=>'ID','order'=>'ASC',
	'emdo_include_hidden_blog_islands'=>true,
) );
if ( 60 !== count( $duck_ids ) ) { throw new Exception( 'Expected 60 duck posts, found ' . count( $duck_ids ) ); }

if ( ! function_exists( 'emdo_duck_serp_titles_20260928' ) || ! function_exists( 'emdo_duck_serp_descriptions_20260930' ) ) {
	throw new Exception( 'Duck SEO MU plugin is not loaded' );
}
$seo_titles = emdo_duck_serp_titles_20260928();
$seo_descriptions = emdo_duck_serp_descriptions_20260930();
if ( 60 !== count( $seo_titles ) || 60 !== count( $seo_descriptions ) ) {
	throw new Exception( 'Expected 60 SEO titles and descriptions, found ' . count( $seo_titles ) . '/' . count( $seo_descriptions ) );
}

$carnes = get_term_by( 'slug', 'carnes', 'category' );
if ( ! $carnes instanceof WP_Term ) { throw new Exception( 'Missing Carnes category' ); }

$pato = get_term_by( 'slug', 'pato', 'category' );
if ( ! $pato instanceof WP_Term ) {
	$created = wp_insert_term( 'Pato', 'category', array( 'slug'=>'pato', 'parent'=>(int)$carnes->term_id ) );
	if ( is_wp_error( $created ) ) { throw new Exception( $created->get_error_message() ); }
	$pato = get_term( (int) $created['term_id'], 'category' );
}
if ( ! $pato instanceof WP_Term ) { throw new Exception( 'Could not resolve Pato category' ); }

$by_key = array();
foreach ( $duck_ids as $id ) {
	$key = (string) get_post_meta( (int) $id, '_emdo_seo_landing_key', true );
	if ( '' === $key ) { throw new Exception( 'Missing duck key for post ' . (int) $id ); }
	$by_key[ $key ] = array(
		'id' => (int) $id,
		'url' => (string) get_permalink( (int) $id ),
		'title' => (string) get_the_title( (int) $id ),
	);
}
if ( 60 !== count( $by_key ) ) { throw new Exception( 'Expected 60 unique duck keys' ); }

$link = static function ( string $key, string $anchor ) use ( $by_key ): string {
	if ( ! isset( $by_key[ $key ] ) ) { return ''; }
	return '<a href="' . esc_url( $by_key[ $key ]['url'] ) . '">' . esc_html( $anchor ) . '</a>';
};

$description = '<div class="emdo-duck-topic-hub" data-emdo-duck-topic-hub="1">'
	. '<p>Guías para conocer, elegir y cocinar pato con criterio. Este índice agrupa los contenidos por intención para que puedas pasar de los cortes y formatos a la técnica, el servicio y los acompañamientos sin mezclar temas distintos.</p>'
	. '<h2>Carne de pato y cortes</h2><p>'
	. $link( 'duck-01', 'Guía de carne de pato y sus cortes' ) . ', '
	. $link( 'duck-07', 'cortes de pato fresco' ) . ', '
	. $link( 'duck-48', 'pato entero al horno' ) . ', '
	. $link( 'duck-51', 'valor nutricional' ) . ' y '
	. $link( 'duck-53', 'conservación y congelación' ) . '.</p>'
	. '<h2>Magret de pato</h2><p>'
	. $link( 'duck-03', 'Qué es el magret' ) . ', '
	. $link( 'duck-09', 'cómo cocinarlo en sartén' ) . ', '
	. $link( 'duck-24', 'temperaturas y punto de cocción' ) . ', '
	. $link( 'duck-23', 'guarniciones' ) . ' y '
	. $link( 'duck-22', 'magret a la naranja' ) . '.</p>'
	. '<h2>Foie, paté, mousse y rillettes</h2><p>'
	. $link( 'duck-02', 'Cómo elegir foie gras' ) . ', '
	. $link( 'duck-06', 'diferencias entre foie, paté, mousse, parfait, bloc y micuit' ) . ', '
	. $link( 'duck-14', 'entier frente a bloc' ) . ', '
	. $link( 'duck-15', 'fresco, micuit y conserva' ) . ' y '
	. $link( 'duck-20', 'cómo conservar foie gras' ) . '.</p>'
	. '<h2>Confit de pato</h2><p>'
	. $link( 'duck-04', 'Qué es el confit de pato' ) . ', '
	. $link( 'duck-10', 'cómo conseguir piel crujiente' ) . ', '
	. $link( 'duck-27', 'temperatura y tiempo al horno' ) . ', '
	. $link( 'duck-28', 'cómo recalentar confit envasado' ) . ' y '
	. $link( 'duck-29', 'guarniciones para confit' ) . '.</p>'
	. '<h2>Jamón de pato</h2><p>'
	. $link( 'duck-05', 'Guía de jamón de pato' ) . ', '
	. $link( 'duck-16', 'curado frente a ahumado' ) . ', '
	. $link( 'duck-34', 'cómo servirlo' ) . ', '
	. $link( 'duck-35', 'acompañamientos' ) . ' y '
	. $link( 'duck-38', 'cómo conservarlo' ) . '.</p>'
	. '<h2>Salsas, maridajes y menús</h2><p>'
	. $link( 'duck-54', 'Salsas para pato' ) . ', '
	. $link( 'duck-55', 'vinos para magret, confit, foie y jamón' ) . ', '
	. $link( 'duck-56', 'pato con frutos rojos' ) . ', '
	. $link( 'duck-57', 'pato con Pedro Ximénez' ) . ' y '
	. $link( 'duck-59', 'menú de Navidad con pato' ) . '.</p>'
	. '</div>';

$updated = wp_update_term( (int)$pato->term_id, 'category', array(
	'name'=>'Pato','slug'=>'pato','parent'=>(int)$carnes->term_id,'description'=>$description,
) );
if ( is_wp_error( $updated ) ) { throw new Exception( $updated->get_error_message() ); }
update_term_meta( (int)$pato->term_id, '_emdo_category_hub', 'duck-20260930' );

$carnes_desc = (string) term_description( $carnes );
$pato_link = get_category_link( $pato );
if ( ! is_wp_error( $pato_link ) && false === strpos( $carnes_desc, (string)$pato_link ) ) {
	$carnes_desc .= '<p>Dentro de la familia de carnes también puedes explorar nuestras <a href="' . esc_url( $pato_link ) . '">guías sobre pato, magret, foie y confit</a>, con técnicas y criterios específicos para estas piezas y elaboraciones.</p>';
	$r = wp_update_term( (int)$carnes->term_id, 'category', array( 'description'=>$carnes_desc ) );
	if ( is_wp_error( $r ) ) { throw new Exception( $r->get_error_message() ); }
}

$subtopics = function_exists( 'emdo_duck_subtopics_20260930' ) ? emdo_duck_subtopics_20260930() : array();
$key_group = array();
foreach ( $subtopics as $group_slug => $group ) {
	foreach ( (array) $group['keys'] as $key ) { $key_group[ $key ] = $group_slug; }
}
if ( 60 !== count( $key_group ) ) { throw new Exception( 'Expected all 60 duck keys to have a primary subtopic, found ' . count( $key_group ) ); }

$rows = array();
$title_lengths = array();
$description_lengths = array();

foreach ( $duck_ids as $id ) {
	$id = (int) $id;
	$key = (string) get_post_meta( $id, '_emdo_seo_landing_key', true );
	$slug = (string) get_post_field( 'post_name', $id );
	if ( ! isset( $seo_titles[ $slug ], $seo_descriptions[ $slug ], $key_group[ $key ] ) ) {
		throw new Exception( 'Missing SEO mapping for ' . $key . '/' . $slug );
	}

	if ( '' === (string) get_post_meta( $id, '_emdo_duck_architecture_backup_20260928', true ) ) {
		update_post_meta( $id, '_emdo_duck_architecture_backup_20260928', wp_json_encode( array(
			'categories'=>wp_get_post_categories($id),
			'hidden_listing'=>(string)get_post_meta($id,'_emdo_blog_hidden_listing',true),
			'hidden_navigation'=>(string)get_post_meta($id,'_emdo_seo_landing_hidden_navigation',true),
		), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES ) );
	}
	if ( '' === (string) get_post_meta( $id, '_emdo_duck_excerpt_backup_20260930', true ) ) {
		update_post_meta( $id, '_emdo_duck_excerpt_backup_20260930', (string) get_post_field( 'post_excerpt', $id ) );
	}

	wp_set_post_categories( $id, array( (int)$pato->term_id ), false );
	delete_post_meta( $id, '_emdo_blog_hidden_listing' );
	delete_post_meta( $id, '_emdo_seo_landing_hidden_navigation' );
	delete_post_meta( $id, '_emdo_duck_home_suppress' );
	update_post_meta( $id, '_emdo_blog_primary_category', 'pato' );
	update_post_meta( $id, '_emdo_duck_architecture', 'public-blog-topic-hub-20260930' );
	update_post_meta( $id, '_emdo_duck_primary_subtopic', $key_group[ $key ] );

	$seo_title = (string) $seo_titles[ $slug ];
	$seo_description = (string) $seo_descriptions[ $slug ];
	wp_update_post( array( 'ID'=>$id, 'post_excerpt'=>$seo_description ) );

	update_post_meta( $id, '_yoast_wpseo_title', $seo_title );
	update_post_meta( $id, 'rank_math_title', $seo_title );
	update_post_meta( $id, '_yoast_wpseo_metadesc', $seo_description );
	update_post_meta( $id, 'rank_math_description', $seo_description );
	delete_post_meta( $id, '_yoast_wpseo_meta-robots-noindex' );
	delete_post_meta( $id, 'rank_math_robots' );
	clean_post_cache( $id );

	$title_len = function_exists( 'mb_strlen' ) ? mb_strlen( $seo_title ) : strlen( $seo_title );
	$description_len = function_exists( 'mb_strlen' ) ? mb_strlen( $seo_description ) : strlen( $seo_description );
	$title_lengths[] = $title_len;
	$description_lengths[] = $description_len;

	$rows[] = array(
		'id'=>$id,
		'key'=>$key,
		'slug'=>$slug,
		'group'=>$key_group[ $key ],
		'categories'=>wp_get_post_categories($id),
		'hidden'=>(string)get_post_meta($id,'_emdo_blog_hidden_listing',true),
		'home_suppress'=>(string)get_post_meta($id,'_emdo_duck_home_suppress',true),
		'seo_title'=>$seo_title,
		'seo_title_len'=>$title_len,
		'seo_description'=>$seo_description,
		'seo_description_len'=>$description_len,
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
	'batch'=>'20260930-duck-seo-refinement',
	'category'=>array(
		'id'=>(int)$pato->term_id,'name'=>$pato->name,'slug'=>$pato->slug,
		'parent'=>(int)$carnes->term_id,'url'=>get_category_link($pato),
		'hub_marker'=>false !== strpos( (string) term_description( $pato ), 'Guías para conocer, elegir y cocinar pato con criterio.' ),
	),
	'count'=>count($rows),
	'visible_normal_query'=>count($visible_query),
	'category_query'=>count($category_query),
	'homepage_unsuppressed'=>count($rows),
	'seo'=>array(
		'title_min'=>min($title_lengths),
		'title_max'=>max($title_lengths),
		'description_min'=>min($description_lengths),
		'description_max'=>max($description_lengths),
		'groups'=>array_count_values($key_group),
	),
	'rows'=>$rows,
), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT ) . PHP_EOL;
