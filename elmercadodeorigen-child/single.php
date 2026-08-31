<?php
/**
 * Plantilla individual de entradas.
 *
 * @package ElMercadoDeOrigen
 */

get_header();

/*
 * 0.10.252: la entrada se lee como dos piezas visuales claras:
 * 1) una portada única con título + imagen y 2) una única tarjeta de lectura.
 * Se imprime al final del footer para ganar a las capas editoriales históricas
 * sin modificar la apariencia global de las tarjetas de producto.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-entry-cover-010252">
			html body.single-post main#primary.emo-article-page > article.emo-article {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
				background: transparent !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
				overflow: visible !important;
			}

			html body.single-post main#primary.emo-article-page .emo-article-cover {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				background: #fff !important;
				border: 1px solid rgba(13, 33, 27, 0.08) !important;
				border-radius: 26px !important;
				box-shadow: 0 18px 48px rgba(13, 33, 27, 0.1) !important;
				overflow: hidden !important;
				box-sizing: border-box !important;
			}

			/* El título deja de ser una tarjeta independiente dentro de la portada. */
			html body.single-post main#primary.emo-article-page .emo-article-cover > .emo-article-hero {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
				overflow: hidden !important;
			}

			html body.single-post main#primary.emo-article-page .emo-article-cover .emo-article-hero__inner {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: clamp(48px, 6vw, 74px) clamp(32px, 6vw, 76px) !important;
				box-sizing: border-box !important;
			}

			/* La imagen forma parte de la misma portada, sin un segundo marco. */
			html body.single-post main#primary.emo-article-page .emo-article-cover > .emo-article-featured {
				display: block !important;
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
				background: #eee9df !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
				overflow: hidden !important;
			}

			html body.single-post main#primary.emo-article-page .emo-article-cover > .emo-article-featured img {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				height: auto !important;
				max-height: 560px !important;
				aspect-ratio: 2 / 1 !important;
				object-fit: cover !important;
			}

			html body.single-post main#primary.emo-article-page .emo-article-main {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: clamp(30px, 4.5vw, 52px) 0 0 !important;
				background: transparent !important;
				border: 0 !important;
				box-shadow: none !important;
			}

			/* Un único recuadro para todo el contenido editorial. */
			html body.single-post main#primary.emo-article-page .emo-article-content {
				width: min(100%, 800px) !important;
				max-width: 800px !important;
				margin: 0 auto !important;
				padding: clamp(30px, 4.2vw, 52px) !important;
				background: #fff !important;
				border: 1px solid rgba(13, 33, 27, 0.075) !important;
				border-radius: 20px !important;
				box-shadow: 0 9px 28px rgba(13, 33, 27, 0.055) !important;
				overflow: visible !important;
				box-sizing: border-box !important;
			}

			/* Los listados de producto abren el ancho, pero no añaden otra tarjeta exterior. */
			html body.single-post main#primary.emo-article-page .emo-article-content :is(.woocommerce, .wp-block-woocommerce-product-collection, .wc-block-grid) {
				background: transparent !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
			}

			html body.single-post main#primary.emo-article-page .emo-jamon-embutidos-fallback {
				margin-top: 0 !important;
			}

			/* Cuatro lecturas relacionadas en una única fila de escritorio. */
			html body.single-post .emo-related-reading .emo-journal-grid {
				grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
			}

			@media (max-width: 1100px) {
				html body.single-post .emo-related-reading .emo-journal-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 820px) {
				html body.single-post main#primary.emo-article-page .emo-article-cover {
					border-radius: 20px !important;
				}

				html body.single-post main#primary.emo-article-page .emo-article-cover .emo-article-hero__inner {
					padding: 42px 26px 46px !important;
				}

				html body.single-post main#primary.emo-article-page .emo-article-cover > .emo-article-featured img {
					max-height: 460px !important;
					aspect-ratio: 16 / 10 !important;
				}

				html body.single-post main#primary.emo-article-page .emo-article-content {
					padding: 28px 22px !important;
					border-radius: 18px !important;
				}
			}

			@media (max-width: 560px) {
				html body.single-post .emo-related-reading .emo-journal-grid {
					grid-template-columns: minmax(0, 1fr) !important;
				}
			}

			@media (max-width: 420px) {
				html body.single-post main#primary.emo-article-page .emo-article-cover {
					border-radius: 16px !important;
				}

				html body.single-post main#primary.emo-article-page .emo-article-cover .emo-article-hero__inner {
					padding: 34px 20px 38px !important;
				}

				html body.single-post main#primary.emo-article-page .emo-article-content {
					padding: 24px 18px !important;
					border-radius: 16px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

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
			<div class="emo-article-cover">
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
			</div>

			<div class="emo-article-main">
				<div class="emo-shell">
					<div class="emo-article-content">
						<?php
						$content_html = apply_filters( 'the_content', get_the_content() );

						/*
						 * La entrada histórica de Jamón Ibérico conserva el encabezado de
						 * embutidos, pero algunas versiones antiguas del shortcode no devuelven
						 * el loop. Si no hay una rejilla real después de ese encabezado, se
						 * inserta el listado actual de la categoría Embutidos justo debajo.
						 */
						if ( 'jamon-iberico' === (string) get_post_field( 'post_name', $post_id ) ) {
							$heading_match = array();
							$has_heading   = 1 === preg_match(
								'/<h[1-6][^>]*>.*?embutidos.*?<\/h[1-6]>/isu',
								$content_html,
								$heading_match,
								PREG_OFFSET_CAPTURE
							);

							$tail = $has_heading
								? substr( $content_html, (int) $heading_match[0][1] )
								: '';

							$has_products_after_heading = $has_heading && 1 === preg_match(
								'/<ul[^>]+class=(?:"[^"]*\bproducts\b[^"]*"|\'[^\']*\bproducts\b[^\']*\')|wc-block-grid__products|wc-block-product-template/isu',
								$tail
							);

							if ( ! $has_products_after_heading && shortcode_exists( 'products' ) ) {
								$embutidos_products = do_shortcode( '[products category="embutidos" limit="8" columns="3" orderby="popularity"]' );

								if ( preg_match( '/\bproducts\b|wc-block-grid__products|wc-block-product-template/isu', $embutidos_products ) ) {
									$fallback_block = '<div class="emo-jamon-embutidos-fallback">' . $embutidos_products . '</div>';

									if ( $has_heading ) {
										$heading_end  = (int) $heading_match[0][1] + strlen( (string) $heading_match[0][0] );
										$content_html = substr( $content_html, 0, $heading_end ) . $fallback_block . substr( $content_html, $heading_end );
									} else {
										$content_html .= '<h5>NUESTROS EMBUTIDOS MÁS VENDIDOS</h5>' . $fallback_block;
									}
								}
							}
						}

						echo $content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
		$specials_html = '';
		if ( class_exists( 'MDO_Home_Featured_Special' ) && is_callable( array( 'MDO_Home_Featured_Special', 'render' ) ) ) {
			try {
				$specials_html = (string) MDO_Home_Featured_Special::render();
			} catch ( Throwable $exception ) {
				$specials_html = '';
			}
		}

		$related_args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 4,
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

		<?php if ( '' !== $specials_html ) : ?>
			<?php echo $specials_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>

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
					<div class="emo-journal-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
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
