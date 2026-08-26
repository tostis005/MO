<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$lang = MDO_Specials::language();
get_header();
while ( have_posts() ) :
	the_post();
	$id         = get_the_ID();
	$meta       = MDO_Specials::shared( $id );
	$status     = MDO_Specials::status( $id );
	$supplier   = MDO_Specials::supplier( $id );
	$cta_url    = MDO_Specials::cta_url( $id, $lang );
	$title      = MDO_Specials::text( $id, 'title', $lang );
	$eyebrow    = MDO_Specials::text( $id, 'eyebrow', $lang );
	$summary    = MDO_Specials::text( $id, 'summary', $lang );
	$benefit    = MDO_Specials::text( $id, 'benefit', $lang );
	$content    = MDO_Specials::text( $id, 'content', $lang );
	$conditions = MDO_Specials::text( $id, 'conditions', $lang );
	$cta_text   = MDO_Specials::text( $id, 'cta_label', $lang ) ?: MDO_Specials::strings( 'buy', $lang );
	$products   = MDO_Specials::product_ids( $id );
	$image      = MDO_Specials::image_html( $id, 'large' );
	?>
	<main class="mdo-promotions mdo-promo-single" id="main">
		<section class="mdo-promo-hero">
			<div class="mdo-promotions__shell mdo-promo-hero__grid">
				<div class="mdo-promo-hero__content">
					<?php if ( $eyebrow ) : ?><p class="mdo-promotions__kicker"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
					<h1><?php echo esc_html( $title ); ?></h1>
					<?php if ( $supplier ) : ?><p class="mdo-promo-hero__producer"><?php echo esc_html( MDO_Specials::strings( 'with', $lang ) . ' ' . (string) $supplier['name'] ); ?></p><?php endif; ?>
					<?php if ( $summary ) : ?><p class="mdo-promo-hero__summary"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
					<?php if ( 'expired' === $status ) : ?>
						<div class="mdo-promo-notice"><strong><?php echo esc_html( MDO_Specials::strings( 'expired_title', $lang ) ); ?></strong> <?php echo esc_html( MDO_Specials::strings( 'expired', $lang ) ); ?></div>
						<a class="mdo-promo-button" href="<?php echo esc_url( MDO_Specials::archive_url( $lang ) ); ?>"><?php echo esc_html( MDO_Specials::strings( 'current', $lang ) ); ?></a>
					<?php elseif ( 'scheduled' === $status ) : ?>
						<div class="mdo-promo-notice"><?php echo esc_html( MDO_Specials::strings( 'scheduled', $lang ) . ' ' . MDO_Specials::format_date( $meta['start'], $lang ) . '.' ); ?></div>
					<?php else : ?>
						<?php if ( $meta['end'] ) : ?><p class="mdo-promo-hero__date"><?php echo esc_html( MDO_Specials::strings( 'until', $lang ) . ' ' . MDO_Specials::format_date( $meta['end'], $lang ) ); ?></p><?php endif; ?>
						<?php if ( $cta_url ) : ?><a class="mdo-promo-button" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_text ); ?></a><?php endif; ?>
					<?php endif; ?>
				</div>
				<?php if ( $image ) : ?><div class="mdo-promo-hero__media"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			</div>
		</section>

		<section class="mdo-promo-detail">
			<div class="mdo-promotions__shell mdo-promo-detail__grid">
				<div class="mdo-promo-detail__main">
					<?php if ( $benefit ) : ?><section class="mdo-promo-section"><h2><?php echo esc_html( MDO_Specials::strings( 'how', $lang ) ); ?></h2><p><?php echo nl2br( esc_html( $benefit ) ); ?></p></section><?php endif; ?>
					<?php if ( trim( $content ) ) : ?><section class="mdo-promo-section mdo-promo-section--content"><?php echo wp_kses_post( $content ); ?></section><?php endif; ?>
					<?php if ( $products && function_exists( 'wc_get_product' ) ) : ?>
						<section class="mdo-promo-section"><h2><?php echo esc_html( MDO_Specials::strings( 'related', $lang ) ); ?></h2><?php echo do_shortcode( '[products ids="' . esc_attr( implode( ',', $products ) ) . '" columns="3" orderby="post__in"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></section>
					<?php endif; ?>
					<?php if ( $conditions ) : ?><section class="mdo-promo-section mdo-promo-conditions"><h2><?php echo esc_html( MDO_Specials::strings( 'conditions', $lang ) ); ?></h2><p><?php echo nl2br( esc_html( $conditions ) ); ?></p></section><?php endif; ?>
				</div>
				<aside class="mdo-promo-detail__aside">
					<?php if ( $meta['coupon'] && 'active' === $status ) : ?><div class="mdo-promo-coupon"><span><?php echo esc_html( MDO_Specials::strings( 'code', $lang ) ); ?></span><strong><?php echo esc_html( $meta['coupon'] ); ?></strong><button type="button" class="mdo-promo-copy" data-copy="<?php echo esc_attr( $meta['coupon'] ); ?>" data-label="<?php echo esc_attr( MDO_Specials::strings( 'copy', $lang ) ); ?>" data-copied="<?php echo esc_attr( MDO_Specials::strings( 'copied', $lang ) ); ?>"><?php echo esc_html( MDO_Specials::strings( 'copy', $lang ) ); ?></button></div><?php endif; ?>
					<a class="mdo-promo-back" href="<?php echo esc_url( MDO_Specials::archive_url( $lang ) ); ?>">← <?php echo esc_html( MDO_Specials::strings( 'all', $lang ) ); ?></a>
				</aside>
			</div>
		</section>
	</main>
	<?php if ( $meta['coupon'] && 'active' === $status ) : ?>
	<script>(function(){var b=document.querySelector('.mdo-promo-copy');if(!b){return;}b.addEventListener('click',function(){var code=b.getAttribute('data-copy')||'',label=b.getAttribute('data-label')||'',copied=b.getAttribute('data-copied')||'';if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(code).then(function(){b.textContent=copied;setTimeout(function(){b.textContent=label;},1800);});}});})();</script>
	<?php endif; ?>
<?php endwhile; ?>
<?php get_footer(); ?>
