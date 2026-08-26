<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	$id       = get_the_ID();
	$meta     = MDO_Promotions::meta( $id );
	$status   = MDO_Promotions::status( $id );
	$supplier = MDO_Promotions::supplier( $id );
	$cta_url  = MDO_Promotions::cta_url( $id );
	$cta_text = $meta['cta_label'] ?: 'Comprar ahora';
	$products = MDO_Promotions::product_ids( $id );
	?>
	<main class="mdo-promotions mdo-promo-single" id="main">
		<section class="mdo-promo-hero">
			<div class="mdo-promotions__shell mdo-promo-hero__grid">
				<div class="mdo-promo-hero__content">
					<?php if ( $meta['eyebrow'] ) : ?><p class="mdo-promotions__kicker"><?php echo esc_html( $meta['eyebrow'] ); ?></p><?php endif; ?>
					<h1><?php the_title(); ?></h1>
					<?php if ( $supplier ) : ?><p class="mdo-promo-hero__producer">Con <?php echo esc_html( (string) $supplier['name'] ); ?></p><?php endif; ?>
					<?php if ( $meta['summary'] ) : ?><p class="mdo-promo-hero__summary"><?php echo esc_html( $meta['summary'] ); ?></p><?php endif; ?>
					<?php if ( 'expired' === $status ) : ?>
						<div class="mdo-promo-notice"><strong>Esta promoción ha finalizado.</strong> Puedes consultar las propuestas que están disponibles actualmente.</div>
						<a class="mdo-promo-button" href="<?php echo esc_url( get_post_type_archive_link( 'mdo_promotion' ) ); ?>">Ver ofertas actuales</a>
					<?php elseif ( 'scheduled' === $status ) : ?>
						<div class="mdo-promo-notice">Esta promoción estará disponible a partir del <?php echo esc_html( MDO_Promotions::format_date( $meta['start'] ) ); ?>.</div>
					<?php else : ?>
						<?php if ( $meta['end'] ) : ?><p class="mdo-promo-hero__date">Disponible hasta el <?php echo esc_html( MDO_Promotions::format_date( $meta['end'] ) ); ?></p><?php endif; ?>
						<?php if ( $cta_url ) : ?><a class="mdo-promo-button" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_text ); ?></a><?php endif; ?>
					<?php endif; ?>
				</div>
				<?php if ( has_post_thumbnail() ) : ?><div class="mdo-promo-hero__media"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
			</div>
		</section>

		<section class="mdo-promo-detail">
			<div class="mdo-promotions__shell mdo-promo-detail__grid">
				<div class="mdo-promo-detail__main">
					<?php if ( $meta['benefit'] ) : ?>
						<section class="mdo-promo-section"><h2>Cómo funciona</h2><p><?php echo nl2br( esc_html( $meta['benefit'] ) ); ?></p></section>
					<?php endif; ?>
					<?php if ( trim( get_the_content() ) ) : ?><section class="mdo-promo-section mdo-promo-section--content"><?php the_content(); ?></section><?php endif; ?>
					<?php if ( $products && function_exists( 'wc_get_product' ) ) : ?>
						<section class="mdo-promo-section">
							<h2>Productos relacionados</h2>
							<?php echo do_shortcode( '[products ids="' . esc_attr( implode( ',', $products ) ) . '" columns="3" orderby="post__in"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</section>
					<?php endif; ?>
					<?php if ( $meta['conditions'] ) : ?><section class="mdo-promo-section mdo-promo-conditions"><h2>Condiciones</h2><p><?php echo nl2br( esc_html( $meta['conditions'] ) ); ?></p></section><?php endif; ?>
				</div>
				<aside class="mdo-promo-detail__aside">
					<?php if ( $meta['coupon'] && 'active' === $status ) : ?>
						<div class="mdo-promo-coupon"><span>Código de la promoción</span><strong><?php echo esc_html( $meta['coupon'] ); ?></strong><button type="button" class="mdo-promo-copy" data-copy="<?php echo esc_attr( $meta['coupon'] ); ?>">Copiar código</button></div>
					<?php endif; ?>
					<a class="mdo-promo-back" href="<?php echo esc_url( get_post_type_archive_link( 'mdo_promotion' ) ); ?>">← Todas las ofertas</a>
				</aside>
			</div>
		</section>
	</main>
	<?php if ( $meta['coupon'] && 'active' === $status ) : ?>
	<script>(function(){var b=document.querySelector('.mdo-promo-copy');if(!b){return;}b.addEventListener('click',function(){var code=b.getAttribute('data-copy')||'';if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(code).then(function(){b.textContent='Copiado';setTimeout(function(){b.textContent='Copiar código';},1800);});}});})();</script>
	<?php endif; ?>
<?php endwhile; ?>
<?php get_footer(); ?>
