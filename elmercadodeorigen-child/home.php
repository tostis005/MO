<?php
/**
 * Archivo principal del blog.
 *
 * @package ElMercadoDeOrigen
 */

get_header();
?>

<main id="primary" class="site-main emo-journal">
	<section class="emo-journal-hero">
		<div class="emo-shell emo-journal-hero__inner">
			<span class="emo-kicker emo-kicker--light"><?php esc_html_e( 'El cuaderno de origen', 'elmercadodeorigen' ); ?></span>
			<h1><?php esc_html_e( 'Historias para elegir con más criterio.', 'elmercadodeorigen' ); ?></h1>
			<p><?php esc_html_e( 'Ideas, procesos y personas para entender qué hay detrás de un producto y por qué su origen importa.', 'elmercadodeorigen' ); ?></p>
		</div>
	</section>

	<section class="emo-journal-listing">
		<div class="emo-shell">
			<header class="emo-journal-toolbar">
				<div>
					<span class="emo-kicker"><?php esc_html_e( 'Últimas historias', 'elmercadodeorigen' ); ?></span>
					<h2><?php esc_html_e( 'Conocer mejor para elegir mejor', 'elmercadodeorigen' ); ?></h2>
				</div>
			</header>

			<?php if ( have_posts() ) : ?>
				<div class="emo-journal-grid">
					<?php
					$index = 0;
					while ( have_posts() ) :
						the_post();
						echo elmercado_render_post_card( get_the_ID(), 0 === $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						++$index;
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
					<span class="emo-kicker"><?php esc_html_e( 'Próximamente', 'elmercadodeorigen' ); ?></span>
					<h2><?php esc_html_e( 'Estamos preparando nuevas historias.', 'elmercadodeorigen' ); ?></h2>
					<p><?php esc_html_e( 'Muy pronto encontrarás aquí nuevas formas de conocer el origen, los productos y a quienes los hacen posibles.', 'elmercadodeorigen' ); ?></p>
				</section>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
