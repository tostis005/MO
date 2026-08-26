<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<main class="mdo-promotions" id="main">
	<section class="mdo-promotions__intro">
		<div class="mdo-promotions__shell">
			<p class="mdo-promotions__kicker">Ahora en El Mercado</p>
			<h1>Ofertas y propuestas especiales</h1>
			<p>Promociones, packs, regalos y oportunidades que preparamos junto a nuestros productores. Van cambiando: aquí encontrarás únicamente las que están disponibles ahora.</p>
		</div>
	</section>
	<section class="mdo-promotions__listing">
		<div class="mdo-promotions__shell">
			<?php if ( have_posts() ) : ?>
				<div class="mdo-promo-grid">
					<?php while ( have_posts() ) : the_post();
						$meta = MDO_Promotions::meta( get_the_ID() );
						$supplier = MDO_Promotions::supplier( get_the_ID() );
						?>
						<article <?php post_class( 'mdo-promo-card' ); ?>>
							<a class="mdo-promo-card__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
								<?php else : ?>
									<span class="mdo-promo-card__placeholder" aria-hidden="true"></span>
								<?php endif; ?>
							</a>
							<div class="mdo-promo-card__body">
								<?php if ( $meta['eyebrow'] ) : ?><p class="mdo-promo-card__eyebrow"><?php echo esc_html( $meta['eyebrow'] ); ?></p><?php endif; ?>
								<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<?php if ( $supplier ) : ?><p class="mdo-promo-card__producer"><?php echo esc_html( (string) $supplier['name'] ); ?></p><?php endif; ?>
								<?php if ( $meta['summary'] ) : ?><p class="mdo-promo-card__summary"><?php echo esc_html( $meta['summary'] ); ?></p><?php elseif ( has_excerpt() ) : ?><p class="mdo-promo-card__summary"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
								<?php if ( $meta['end'] ) : ?><p class="mdo-promo-card__date">Disponible hasta el <?php echo esc_html( MDO_Promotions::format_date( $meta['end'] ) ); ?></p><?php endif; ?>
								<a class="mdo-promo-link" href="<?php the_permalink(); ?>">Ver oferta <span aria-hidden="true">→</span></a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => 'Anterior', 'next_text' => 'Siguiente' ) ); ?>
			<?php else : ?>
				<div class="mdo-promotions__empty">
					<h2>No hay promociones activas ahora mismo</h2>
					<p>Cuando tengamos una propuesta especial disponible, aparecerá aquí.</p>
					<a class="mdo-promo-button" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' ) ); ?>">Ver la tienda</a>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
