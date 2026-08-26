<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$lang = MDO_Specials::language();
get_header();
?>
<main class="mdo-promotions" id="main">
	<section class="mdo-promotions__intro">
		<div class="mdo-promotions__shell">
			<h1><?php echo esc_html( 'en' === $lang ? MDO_Specials::strings( 'title', $lang ) : 'Especiales del Mercado de Origen' ); ?></h1>
		</div>
	</section>
	<section class="mdo-promotions__listing">
		<div class="mdo-promotions__shell">
			<?php if ( have_posts() ) : ?>
				<div class="mdo-promo-grid">
					<?php while ( have_posts() ) : the_post();
						$id       = get_the_ID();
						$meta     = MDO_Specials::shared( $id );
						$supplier = MDO_Specials::supplier( $id );
						$title    = MDO_Specials::text( $id, 'title', $lang );
						$summary  = MDO_Specials::text( $id, 'summary', $lang );
						$eyebrow  = MDO_Specials::text( $id, 'eyebrow', $lang );
						$url      = MDO_Specials::permalink( $id, $lang );
						$image    = MDO_Specials::image_html( $id, 'large', array( 'loading' => 'lazy' ) );
						?>
						<article <?php post_class( 'mdo-promo-card' ); ?>>
							<a class="mdo-promo-card__media" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
								<?php if ( $image ) : echo $image; else : ?><span class="mdo-promo-card__placeholder" aria-hidden="true"></span><?php endif; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
							<div class="mdo-promo-card__body">
								<?php if ( $eyebrow ) : ?><p class="mdo-promo-card__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
								<h2><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h2>
								<?php if ( $supplier ) : ?><p class="mdo-promo-card__producer"><?php echo esc_html( (string) $supplier['name'] ); ?></p><?php endif; ?>
								<?php if ( $summary ) : ?><p class="mdo-promo-card__summary"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
								<?php if ( $meta['end'] ) : ?><p class="mdo-promo-card__date"><?php echo esc_html( MDO_Specials::strings( 'until', $lang ) . ' ' . MDO_Specials::format_date( $meta['end'], $lang ) ); ?></p><?php endif; ?>
								<a class="mdo-promo-link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( MDO_Specials::strings( 'view', $lang ) ); ?> <span aria-hidden="true">→</span></a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => MDO_Specials::strings( 'prev', $lang ), 'next_text' => MDO_Specials::strings( 'next', $lang ) ) ); ?>
			<?php else : ?>
				<div class="mdo-promotions__empty">
					<h2><?php echo esc_html( MDO_Specials::strings( 'empty_title', $lang ) ); ?></h2>
					<p><?php echo esc_html( MDO_Specials::strings( 'empty', $lang ) ); ?></p>
					<?php $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' ); if ( 'en' === $lang ) { $shop_url = home_url( '/en/shop/' ); } ?>
					<a class="mdo-promo-button" href="<?php echo esc_url( $shop_url ); ?>"><?php echo esc_html( MDO_Specials::strings( 'shop', $lang ) ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
