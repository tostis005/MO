<?php
/**
 * Plantilla individual de entradas.
 *
 * @package ElMercadoDeOrigen
 */

get_header();

while ( have_posts() ) :
	the_post();

	$post_id    = get_the_ID();
	$categories = get_the_category( $post_id );
	$category   = ! empty( $categories ) ? $categories[0] : null;
	$previous   = get_previous_post();
	$next       = get_next_post();
	?>
	<main id="primary" class="site-main emo-article-page">
		<article <?php post_class( 'emo-article' ); ?>>
			<header class="emo-article-hero">
				<div class="emo-shell emo-article-hero__inner">
					<div class="emo-article-hero__meta">
						<?php if ( $category instanceof WP_Term ) : ?>
							<a href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
						<?php endif; ?>
						<span><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></span>
						<span><?php echo esc_html( (string) elmercado_reading_time( $post_id ) ); ?> <?php esc_html_e( 'min de lectura', 'elmercadodeorigen' ); ?></span>
					</div>
					<h1><?php the_title(); ?></h1>
				</div>
			</header>

			<div class="emo-article-main">
				<div class="emo-shell">
					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="emo-article-featured">
							<?php
							the_post_thumbnail(
								'full',
								array(
									'loading'       => 'eager',
									'fetchpriority' => 'high',
									'decoding'      => 'async',
								)
							);
							?>
						</figure>
					<?php endif; ?>

					<div class="emo-article-content">
						<?php
						the_content();
						wp_link_pages(
							array(
								'before' => '<nav class="page-links">' . esc_html__( 'Páginas:', 'elmercadodeorigen' ),
								'after'  => '</nav>',
							)
						);
						?>
					</div>

					<footer class="emo-article-footer">
						<a class="emo-article-back" href="<?php echo esc_url( elmercado_blog_url() ); ?>">← <?php esc_html_e( 'Volver al blog', 'elmercadodeorigen' ); ?></a>
						<nav class="emo-article-navigation" aria-label="<?php esc_attr_e( 'Otras entradas', 'elmercadodeorigen' ); ?>">
							<?php if ( $previous instanceof WP_Post ) : ?>
								<a href="<?php echo esc_url( get_permalink( $previous ) ); ?>"><?php esc_html_e( 'Anterior', 'elmercadodeorigen' ); ?></a>
							<?php endif; ?>
							<?php if ( $next instanceof WP_Post ) : ?>
								<a href="<?php echo esc_url( get_permalink( $next ) ); ?>"><?php esc_html_e( 'Siguiente', 'elmercadodeorigen' ); ?></a>
							<?php endif; ?>
						</nav>
					</footer>
				</div>
			</div>
		</article>

		<?php
		$related_args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 3,
			'post__not_in'        => array( $post_id ),
			'ignore_sticky_posts' => true,
		);

		if ( $category instanceof WP_Term ) {
			$related_args['category__in'] = array( (int) $category->term_id );
		}

		$related = new WP_Query( $related_args );

		if ( ! $related->have_posts() && $category instanceof WP_Term ) {
			unset( $related_args['category__in'] );
			$related = new WP_Query( $related_args );
		}
		?>

		<?php if ( $related->have_posts() ) : ?>
			<section class="emo-related-reading">
				<div class="emo-shell">
					<header class="emo-related-reading__heading">
						<div>
							<span class="emo-kicker"><?php esc_html_e( 'Seguir descubriendo', 'elmercadodeorigen' ); ?></span>
							<h2><?php esc_html_e( 'Más historias del mercado', 'elmercadodeorigen' ); ?></h2>
						</div>
						<a class="emo-text-link" href="<?php echo esc_url( elmercado_blog_url() ); ?>"><?php esc_html_e( 'Ver todos los artículos', 'elmercadodeorigen' ); ?> <span aria-hidden="true">→</span></a>
					</header>
					<div class="emo-journal-grid">
						<?php
						while ( $related->have_posts() ) :
							$related->the_post();
							echo elmercado_render_post_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						endwhile;
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</main>
	<?php
endwhile;

get_footer();
