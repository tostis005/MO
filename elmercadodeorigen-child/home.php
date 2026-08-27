<?php
/**
 * Archivo principal del blog.
 *
 * @package ElMercadoDeOrigen
 */

$elmercado_blog_discovery_module_010263 = __DIR__ . '/inc/blog-discovery-010263.php';
if ( ! function_exists( 'elmercado_blog_filter_state_010263' ) && is_readable( $elmercado_blog_discovery_module_010263 ) ) {
	require_once $elmercado_blog_discovery_module_010263;
}

get_header();

$filter_state = function_exists( 'elmercado_blog_filter_state_010263' ) ? elmercado_blog_filter_state_010263() : array( 'query' => '', 'categories' => array() );
$is_filtered = '' !== $filter_state['query'] || ! empty( $filter_state['categories'] );
$paged = max( 1, (int) get_query_var( 'paged' ) );
$featured_ids = ! $is_filtered && function_exists( 'elmercado_blog_featured_ids_010263' ) ? elmercado_blog_featured_ids_010263( 3, true ) : array();

$query_args = array(
	'post_type' => 'post',
	'post_status' => 'publish',
	'posts_per_page' => 12,
	'paged' => $paged,
	'orderby' => 'date',
	'order' => 'DESC',
	'ignore_sticky_posts' => true,
	'suppress_filters' => false,
);
if ( ! $is_filtered && ! empty( $featured_ids ) ) { $query_args['post__not_in'] = $featured_ids; }
if ( '' !== $filter_state['query'] ) { $query_args['s'] = $filter_state['query']; }
if ( ! empty( $filter_state['categories'] ) ) {
	$query_args['tax_query'] = array( array( 'taxonomy' => 'category', 'field' => 'slug', 'terms' => $filter_state['categories'], 'operator' => 'IN' ) );
}
$articles_query = new WP_Query( $query_args );
$blog_url = function_exists( 'elmercado_blog_public_url_010263' ) ? elmercado_blog_public_url_010263() : elmercado_blog_url();
?>

<main id="primary" class="site-main emo-journal emo-journal--discovery">
	<section class="emo-journal-hero">
		<div class="emo-shell emo-journal-hero__inner">
			<span class="emo-kicker emo-kicker--light"><?php esc_html_e( 'El cuaderno de origen', 'elmercadodeorigen' ); ?></span>
			<h1><?php esc_html_e( 'Historias para elegir con más criterio.', 'elmercadodeorigen' ); ?></h1>
			<p><?php esc_html_e( 'Ideas, procesos y personas para entender qué hay detrás de un producto y por qué su origen importa.', 'elmercadodeorigen' ); ?></p>
		</div>
	</section>

	<section class="emo-journal-listing emo-journal-listing--discovery"><div class="emo-shell">
		<?php if ( function_exists( 'elmercado_render_blog_discovery_controls_010263' ) ) { elmercado_render_blog_discovery_controls_010263( $filter_state['query'], $filter_state['categories'] ); } ?>

		<?php if ( ! $is_filtered && ! empty( $featured_ids ) ) : ?>
			<section class="emo-blog-featured">
				<header class="emo-blog-section-heading"><div><span class="emo-kicker"><?php echo esc_html( elmercado_blog_copy_010263( 'Destacados', 'Featured' ) ); ?></span><h2><?php echo esc_html( elmercado_blog_copy_010263( 'Para empezar por lo más relevante', 'A good place to start' ) ); ?></h2></div></header>
				<div class="emo-blog-featured-grid"><?php foreach ( $featured_ids as $featured_id ) { echo elmercado_render_post_card( (int) $featured_id ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</section>
		<?php endif; ?>

		<section class="emo-blog-results">
			<header class="emo-blog-section-heading"><div>
				<span class="emo-kicker"><?php echo esc_html( $is_filtered ? elmercado_blog_copy_010263( 'Resultados', 'Results' ) : elmercado_blog_copy_010263( 'Últimos artículos', 'Latest articles' ) ); ?></span>
				<h2><?php echo esc_html( $is_filtered ? elmercado_blog_copy_010263( 'Artículos relacionados con tu búsqueda', 'Articles matching your search' ) : elmercado_blog_copy_010263( 'Sigue descubriendo el origen', 'Keep exploring the origin' ) ); ?></h2>
			</div><?php if ( $is_filtered ) : ?><span class="emo-blog-result-count"><?php echo esc_html( sprintf( _n( '%s artículo', '%s artículos', (int) $articles_query->found_posts, 'elmercadodeorigen' ), number_format_i18n( (int) $articles_query->found_posts ) ) ); ?></span><?php endif; ?></header>

			<?php if ( $articles_query->have_posts() ) : ?>
				<div class="emo-blog-grid">
					<?php while ( $articles_query->have_posts() ) : $articles_query->the_post(); echo elmercado_render_post_card( get_the_ID() ); endwhile; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php if ( function_exists( 'elmercado_render_blog_pagination_010263' ) ) { elmercado_render_blog_pagination_010263( $articles_query ); } ?>
			<?php else : ?>
				<section class="emo-empty-state emo-blog-empty-state"><span class="emo-kicker"><?php echo esc_html( elmercado_blog_copy_010263( 'Sin resultados', 'No results' ) ); ?></span><h2><?php echo esc_html( elmercado_blog_copy_010263( 'No hemos encontrado artículos con esos filtros.', 'We could not find articles with those filters.' ) ); ?></h2><p><?php echo esc_html( elmercado_blog_copy_010263( 'Prueba con otra búsqueda o elimina alguna categoría.', 'Try another search or remove a category.' ) ); ?></p><a class="emo-button" href="<?php echo esc_url( $blog_url ); ?>"><?php echo esc_html( elmercado_blog_copy_010263( 'Ver todo el blog', 'View all articles' ) ); ?></a></section>
			<?php endif; ?>
		</section>

		<?php if ( function_exists( 'elmercado_render_blog_category_navigation_010263' ) ) { elmercado_render_blog_category_navigation_010263(); } ?>
	</div></section>
</main>

<?php wp_reset_postdata(); get_footer();
