<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$lang = MDO_Promotions::language();
get_header();
?>
<!-- Ofertas y propuestas especiales -->
<main class="mdo-promotions" id="main">
<section class="mdo-promotions__intro"><div class="mdo-promotions__shell">
<p class="mdo-promotions__kicker"><?php echo esc_html( MDO_Promotions::text( 'kicker', $lang ) ); ?></p>
<h1><?php echo esc_html( MDO_Promotions::text( 'archive_title', $lang ) ); ?></h1>
<p><?php echo esc_html( MDO_Promotions::text( 'archive_intro', $lang ) ); ?></p>
</div></section>
<section class="mdo-promotions__listing"><div class="mdo-promotions__shell">
<?php if ( have_posts() ) : ?><div class="mdo-promo-grid">
<?php while ( have_posts() ) : the_post(); $id=get_the_ID(); $m=MDO_Promotions::localized($id,$lang); $supplier=MDO_Promotions::supplier($id); $url=MDO_Promotions::permalink($id,$lang); $img=MDO_Promotions::image_html($id,'large',array('loading'=>'lazy')); ?>
<article <?php post_class('mdo-promo-card'); ?>><a class="mdo-promo-card__media" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($m['title']); ?>"><?php echo $img ? $img : '<span class="mdo-promo-card__placeholder" aria-hidden="true"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
<div class="mdo-promo-card__body">
<?php if($m['eyebrow']): ?><p class="mdo-promo-card__eyebrow"><?php echo esc_html($m['eyebrow']); ?></p><?php endif; ?>
<h2><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($m['title']); ?></a></h2>
<?php if($supplier): ?><p class="mdo-promo-card__producer"><?php echo esc_html((string)$supplier['name']); ?></p><?php endif; ?>
<?php if($m['summary']): ?><p class="mdo-promo-card__summary"><?php echo esc_html($m['summary']); ?></p><?php endif; ?>
<?php if($m['end']): ?><p class="mdo-promo-card__date"><?php echo esc_html(MDO_Promotions::text('until',$lang).' '.MDO_Promotions::format_date($m['end'],$lang)); ?></p><?php endif; ?>
<a class="mdo-promo-link" href="<?php echo esc_url($url); ?>"><?php echo esc_html(MDO_Promotions::text('view',$lang)); ?> <span aria-hidden="true">→</span></a>
</div></article><?php endwhile; ?></div>
<?php the_posts_pagination(array('mid_size'=>1,'prev_text'=>MDO_Promotions::text('prev',$lang),'next_text'=>MDO_Promotions::text('next',$lang))); ?>
<?php else: ?><div class="mdo-promotions__empty"><h2><?php echo esc_html(MDO_Promotions::text('empty_title',$lang)); ?></h2><p><?php echo esc_html(MDO_Promotions::text('empty_text',$lang)); ?></p><a class="mdo-promo-button" href="<?php echo esc_url('en'===$lang?home_url('/en/shop/'):(function_exists('wc_get_page_permalink')?wc_get_page_permalink('shop'):home_url('/tienda/'))); ?>"><?php echo esc_html(MDO_Promotions::text('shop',$lang)); ?></a></div><?php endif; ?>
</div></section></main><?php get_footer(); ?>
