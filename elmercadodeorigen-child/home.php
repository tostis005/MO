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

$special_product_ids = array();
if ( ! $is_filtered && function_exists( 'wc_get_product_ids_on_sale' ) && function_exists( 'wc_get_product' ) ) {
	$special_candidates = array();
	foreach ( array_values( array_unique( array_map( 'absint', (array) wc_get_product_ids_on_sale() ) ) ) as $product_id ) {
		if ( $product_id <= 0 ) {
			continue;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! is_a( $product, 'WC_Product' ) || ! $product->is_visible() || ! $product->is_in_stock() || ! $product->is_on_sale() ) {
			continue;
		}

		$special_candidates[ $product_id ] = (int) $product->get_total_sales();
	}

	if ( ! empty( $special_candidates ) ) {
		arsort( $special_candidates, SORT_NUMERIC );
		$special_product_ids = array_slice( array_map( 'intval', array_keys( $special_candidates ) ), 0, 4 );
	}
}

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

<style id="elmercado-blog-specials-grid-010266">
	@media (min-width: 1041px) {
		.emo-journal--discovery .emo-blog-grid {
			grid-template-columns: repeat(4, minmax(0, 1fr));
		}
	}

	.emo-blog-specials {
		margin-top: clamp(42px, 6vw, 72px);
	}

	.emo-blog-specials:first-of-type {
		margin-top: 0;
	}

	.emo-blog-specials .woocommerce,
	.emo-blog-specials ul.products {
		width: 100%;
	}

	.emo-blog-specials ul.products {
		display: grid !important;
		grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
		gap: 24px !important;
		margin: 0 !important;
	}

	.emo-blog-specials ul.products::before,
	.emo-blog-specials ul.products::after {
		display: none !important;
		content: none !important;
	}

	.emo-blog-specials ul.products li.product {
		float: none !important;
		width: 100% !important;
		max-width: none !important;
		margin: 0 !important;
	}

	@media (max-width: 1040px) {
		.emo-blog-specials ul.products {
			grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
		}
	}

	@media (max-width: 560px) {
		.emo-blog-specials ul.products {
			grid-template-columns: minmax(0, 1fr) !important;
		}
	}
</style>

<main id="primary" class="site-main emo-journal emo-journal--discovery">
	<section class="emo-journal-hero">
		<div class="emo-shell emo-journal-hero__inner">
			<span class="emo-kicker emo-kicker--light"><?php echo esc_html( elmercado_blog_copy_010263( 'Blog', 'Blog' ) ); ?></span>
			<h1><?php echo esc_html( elmercado_blog_copy_010263( 'Todo lo que necesitas saber sobre nuestros productos', 'Everything you need to know about our products' ) ); ?></h1>
			<p><?php echo esc_html( elmercado_blog_copy_010263( 'Artículos sobre jamón ibérico, carnes, aceite de oliva, conservas y otros productos: cómo elegirlos, conservarlos, prepararlos y conocer mejor su origen.', 'Articles about Iberian ham, meat, olive oil, preserves and other products: how to choose them, store them, prepare them and understand their origin.' ) ); ?></p>
		</div>
	</section>

	<section class="emo-journal-listing emo-journal-listing--discovery"><div class="emo-shell">
		<?php if ( function_exists( 'elmercado_render_blog_discovery_controls_010263' ) ) { elmercado_render_blog_discovery_controls_010263( $filter_state['query'], $filter_state['categories'] ); } ?>

		<?php if ( ! empty( $special_product_ids ) ) : ?>
			<section class="emo-blog-specials" aria-labelledby="emo-blog-specials-title">
				<header class="emo-blog-section-heading"><div><span class="emo-kicker"><?php echo esc_html( elmercado_blog_copy_010263( 'Especiales', 'Specials' ) ); ?></span><h2 id="emo-blog-specials-title"><?php echo esc_html( elmercado_blog_copy_010263( 'Productos especiales', 'Special products' ) ); ?></h2></div></header>
				<?php
				$special_products_shortcode = sprintf(
					'[products ids="%s" limit="4" columns="4" orderby="post__in"]',
					implode( ',', array_map( 'absint', $special_product_ids ) )
				);
				echo do_shortcode( $special_products_shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</section>
		<?php endif; ?>

		<?php if ( ! $is_filtered && ! empty( $featured_ids ) ) : ?>
			<section class="emo-blog-featured">
				<header class="emo-blog-section-heading"><div><span class="emo-kicker"><?php echo esc_html( elmercado_blog_copy_010263( 'Artículos destacados', 'Featured articles' ) ); ?></span><h2><?php echo esc_html( elmercado_blog_copy_010263( 'Una selección para empezar', 'A selection to get you started' ) ); ?></h2></div></header>
				<div class="emo-blog-featured-grid"><?php foreach ( $featured_ids as $featured_id ) { echo elmercado_render_post_card( (int) $featured_id ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</section>
		<?php endif; ?>

		<section class="emo-blog-results">
			<header class="emo-blog-section-heading"><div>
				<span class="emo-kicker"><?php echo esc_html( $is_filtered ? elmercado_blog_copy_010263( 'Resultados', 'Results' ) : elmercado_blog_copy_010263( 'Últimos artículos', 'Latest articles' ) ); ?></span>
				<h2><?php echo esc_html( $is_filtered ? elmercado_blog_copy_010263( 'Artículos relacionados con tu búsqueda', 'Articles matching your search' ) : elmercado_blog_copy_010263( 'Lo último que hemos publicado', 'What we have published recently' ) ); ?></h2>
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
