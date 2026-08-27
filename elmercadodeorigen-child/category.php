<?php
/**
 * Archivo de categoría editorial del blog.
 *
 * @package ElMercadoDeOrigen
 */

$elmercado_blog_discovery_module_010263 = __DIR__ . '/inc/blog-discovery-010263.php';
if ( ! function_exists( 'elmercado_blog_filter_state_010263' ) && is_readable( $elmercado_blog_discovery_module_010263 ) ) {
	require_once $elmercado_blog_discovery_module_010263;
}

get_header();
$category = get_queried_object();
$is_category = $category instanceof WP_Term && 'category' === $category->taxonomy;
$title = $is_category ? $category->name : single_cat_title( '', false );
$description = $is_category ? term_description( $category ) : '';
$active = $is_category ? array( $category->slug ) : array();
$blog_url = function_exists( 'elmercado_blog_public_url_010263' ) ? elmercado_blog_public_url_010263() : elmercado_blog_url();
?>

<main id="primary" class="site-main emo-journal emo-journal--category">
	<section class="emo-journal-hero"><div class="emo-shell emo-journal-hero__inner">
		<span class="emo-kicker emo-kicker--light"><?php echo esc_html( elmercado_blog_copy_010263( 'El cuaderno de origen', 'The origin journal' ) ); ?></span>
		<h1><?php echo esc_html( $title ); ?></h1>
		<?php if ( '' !== trim( wp_strip_all_tags( $description ) ) ) : ?><div class="emo-article-hero__lead"><?php echo wp_kses_post( $description ); ?></div><?php else : ?><p><?php echo esc_html( elmercado_blog_copy_010263( 'Guías y artículos para conocer mejor esta familia de productos y elegir con más criterio.', 'Guides and articles to understand this product family and choose with more confidence.' ) ); ?></p><?php endif; ?>
	</div></section>

	<section class="emo-journal-listing emo-journal-listing--discovery"><div class="emo-shell">
		<?php if ( function_exists( 'elmercado_render_blog_discovery_controls_010263' ) ) { elmercado_render_blog_discovery_controls_010263( '', $active ); } ?>
		<section class="emo-blog-results">
			<header class="emo-blog-section-heading"><div><span class="emo-kicker"><?php echo esc_html( elmercado_blog_copy_010263( 'Categoría', 'Category' ) ); ?></span><h2><?php echo esc_html( sprintf( elmercado_blog_copy_010263( 'Artículos sobre %s', 'Articles about %s' ), $title ) ); ?></h2></div><?php if ( $is_category ) : ?><span class="emo-blog-result-count"><?php echo esc_html( sprintf( _n( '%s artículo', '%s artículos', (int) $category->count, 'elmercadodeorigen' ), number_format_i18n( (int) $category->count ) ) ); ?></span><?php endif; ?></header>
			<?php if ( have_posts() ) : ?>
				<div class="emo-blog-grid"><?php while ( have_posts() ) : the_post(); echo elmercado_render_post_card( get_the_ID() ); endwhile; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<nav class="emo-journal-pagination" aria-label="<?php echo esc_attr( elmercado_blog_copy_010263( 'Paginación de categoría', 'Category pagination' ) ); ?>"><?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '←', 'next_text' => '→' ) ); ?></nav>
			<?php else : ?>
				<section class="emo-empty-state emo-blog-empty-state"><h2><?php echo esc_html( elmercado_blog_copy_010263( 'Todavía no hay artículos en esta categoría.', 'There are no articles in this category yet.' ) ); ?></h2><a class="emo-button" href="<?php echo esc_url( $blog_url ); ?>"><?php echo esc_html( elmercado_blog_copy_010263( 'Ver todo el blog', 'View all articles' ) ); ?></a></section>
			<?php endif; ?>
		</section>
		<?php if ( function_exists( 'elmercado_render_blog_category_navigation_010263' ) ) { elmercado_render_blog_category_navigation_010263(); } ?>
	</div></section>
</main>

<?php get_footer();
