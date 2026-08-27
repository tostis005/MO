<?php
/**
 * Archivos editoriales: categorías, etiquetas, fechas y autores.
 *
 * @package ElMercadoDeOrigen
 */

get_header();

$title       = get_the_archive_title();
$description = get_the_archive_description();
$blog_url    = function_exists( 'elmercado_blog_public_url_010263' ) ? elmercado_blog_public_url_010263() : elmercado_blog_url();
?>

<main id="primary" class="site-main emo-journal">
	<section class="emo-journal-hero">
		<div class="emo-shell emo-journal-hero__inner">
			<span class="emo-kicker emo-kicker--light"><?php echo esc_html( function_exists( 'elmercado_blog_copy_010263' ) ? elmercado_blog_copy_010263( 'Blog', 'Blog' ) : 'Blog' ); ?></span>
			<h1><?php echo wp_kses_post( $title ); ?></h1>
			<?php if ( $description ) : ?>
				<div class="emo-article-hero__lead"><?php echo wp_kses_post( $description ); ?></div>
			<?php else : ?>
				<p><?php echo esc_html( function_exists( 'elmercado_blog_copy_010263' ) ? elmercado_blog_copy_010263( 'Artículos para conocer mejor nuestros productos, sus características y cómo elegirlos.', 'Articles to learn more about our products, their characteristics and how to choose them.' ) : 'Artículos para conocer mejor nuestros productos, sus características y cómo elegirlos.' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="emo-journal-listing">
		<div class="emo-shell">
			<header class="emo-journal-toolbar">
				<div>
					<span class="emo-kicker"><?php echo esc_html( function_exists( 'elmercado_blog_copy_010263' ) ? elmercado_blog_copy_010263( 'Artículos', 'Articles' ) : 'Artículos' ); ?></span>
					<h2><?php echo esc_html( function_exists( 'elmercado_blog_copy_010263' ) ? elmercado_blog_copy_010263( 'Más artículos', 'More articles' ) : 'Más artículos' ); ?></h2>
				</div>
				<a class="emo-text-link" href="<?php echo esc_url( $blog_url ); ?>"><?php echo esc_html( function_exists( 'elmercado_blog_copy_010263' ) ? elmercado_blog_copy_010263( 'Ver todo el blog', 'View all articles' ) : 'Ver todo el blog' ); ?> <span aria-hidden="true">→</span></a>
			</header>

			<?php if ( have_posts() ) : ?>
				<div class="emo-journal-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						echo elmercado_render_post_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					endwhile;
					?>
				</div>

				<div class="emo-journal-pagination">
					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 1,
							'prev_text' => '←',
							'next_text' => '→',
						)
					);
					?>
				</div>
			<?php else : ?>
				<section class="emo-empty-state">
					<h2><?php echo esc_html( function_exists( 'elmercado_blog_copy_010263' ) ? elmercado_blog_copy_010263( 'No hay artículos en este archivo.', 'There are no articles in this archive.' ) : 'No hay artículos en este archivo.' ); ?></h2>
					<a class="emo-button" href="<?php echo esc_url( $blog_url ); ?>"><?php echo esc_html( function_exists( 'elmercado_blog_copy_010263' ) ? elmercado_blog_copy_010263( 'Volver al blog', 'Back to the blog' ) : 'Volver al blog' ); ?></a>
				</section>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
