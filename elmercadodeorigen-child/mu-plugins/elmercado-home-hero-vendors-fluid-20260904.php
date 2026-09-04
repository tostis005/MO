<?php
/**
 * Final responsive geometry for the five-producer Home hero collage.
 *
 * The producer banners have very different source ratios (square, 4:3,
 * landscape and Montjam's extra-wide 12:5 banner). This layer gives each
 * breakpoint a stable composition instead of forcing every image into the
 * legacy collage proportions.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_home_hero_vendors_fluid_is_front_20260904(): bool {
	return ! is_admin() && is_front_page() && ! is_feed() && ! is_trackback() && ! wp_doing_ajax();
}

function elmercado_home_hero_vendors_fluid_css_20260904(): string {
	return <<<'CSS'
/* elmercado-home-hero-vendors-fluid-20260904 */
html body.home .emo-home .emo-hero__visual--vendors {
	min-width: 0 !important;
	isolation: isolate;
}
html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card,
html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card figure,
html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card img {
	min-width: 0;
}
html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card figure {
	height: 100% !important;
}
html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card img {
	display: block !important;
	width: 100% !important;
	height: 100% !important;
	max-width: none !important;
	object-fit: cover !important;
	object-position: 50% 50%;
}

/* Wide desktop: an editorial mosaic matched to each producer banner ratio. */
@media (min-width: 1181px) {
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 {
		display: grid !important;
		grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
		grid-template-rows: repeat(12, 32px) !important;
		gap: 0 !important;
		transform: translateY(-24px) !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--1 {
		grid-column: 1 / 6 !important;
		grid-row: 1 / 8 !important;
		transform: rotate(-1deg) !important;
		z-index: 2;
	}
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--2 {
		grid-column: 6 / 13 !important;
		grid-row: 1 / 6 !important;
		transform: rotate(.8deg) !important;
		z-index: 3;
	}
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--3 {
		grid-column: 1 / 6 !important;
		grid-row: 8 / 13 !important;
		transform: rotate(-.35deg) !important;
		z-index: 3;
	}
	/* Montjam is a 1920x800 banner: keep it genuinely wide instead of portrait-cropping it. */
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--4 {
		grid-column: 6 / 13 !important;
		grid-row: 6 / 10 !important;
		transform: rotate(.2deg) !important;
		z-index: 2;
	}
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--5 {
		grid-column: 8 / 13 !important;
		grid-row: 9 / 13 !important;
		transform: rotate(.9deg) !important;
		z-index: 4;
	}
}

/*
 * Tablet and intermediate widths. The base theme keeps a two-column hero until
 * 991px, which leaves only ~425px for five cards around 1024px. Force the hero
 * to one column through 1180px and give every producer a substantial tile.
 */
@media (min-width: 600px) and (max-width: 1180px) {
	html body.home .emo-home .emo-hero__grid {
		grid-template-columns: minmax(0, 1fr) !important;
		gap: clamp(1.6rem, 3.5vw, 2.5rem) !important;
	}
	html body.home .emo-home .emo-hero__copy {
		max-width: 820px !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 {
		display: grid !important;
		width: min(100%, 680px) !important;
		margin: .35rem auto 0 !important;
		grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
		grid-template-rows:
			clamp(160px, 20vw, 205px)
			clamp(160px, 20vw, 205px)
			clamp(170px, 24vw, 225px) !important;
		gap: clamp(10px, 1.8vw, 16px) !important;
		transform: none !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card {
		grid-column: auto !important;
		grid-row: auto !important;
		min-height: 0 !important;
		transform: none !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--1 { grid-column: 1 !important; grid-row: 1 !important; }
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--2 { grid-column: 2 !important; grid-row: 1 !important; }
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--3 { grid-column: 1 !important; grid-row: 2 !important; }
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--5 { grid-column: 2 !important; grid-row: 2 !important; }
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--4 {
		grid-column: 1 / -1 !important;
		grid-row: 3 !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card figcaption {
		display: flex !important;
		visibility: visible !important;
		opacity: 1 !important;
		padding: .85rem .95rem !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card figcaption strong {
		display: block !important;
		visibility: visible !important;
		opacity: 1 !important;
		font-size: .82rem !important;
		line-height: 1.18 !important;
	}
}

/* Phones: two clean rows plus a full-width Montjam banner. All five remain visible. */
@media (max-width: 599px) {
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 {
		display: grid !important;
		width: 100% !important;
		margin: .15rem auto 0 !important;
		grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
		grid-template-rows:
			clamp(128px, 34vw, 156px)
			clamp(128px, 34vw, 156px)
			clamp(126px, 31vw, 170px) !important;
		gap: 10px !important;
		transform: none !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card {
		grid-column: auto !important;
		grid-row: auto !important;
		min-height: 0 !important;
		border-radius: 14px !important;
		transform: none !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--1 { grid-column: 1 !important; grid-row: 1 !important; }
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--2 { grid-column: 2 !important; grid-row: 1 !important; }
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--3 { grid-column: 1 !important; grid-row: 2 !important; }
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--5 { grid-column: 2 !important; grid-row: 2 !important; }
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--4 {
		grid-column: 1 / -1 !important;
		grid-row: 3 !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card figcaption {
		display: flex !important;
		visibility: visible !important;
		opacity: 1 !important;
		padding: .62rem .7rem !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card figcaption span {
		display: none !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card figcaption strong {
		display: block !important;
		visibility: visible !important;
		opacity: 1 !important;
		font-size: .76rem !important;
		line-height: 1.12 !important;
		-webkit-line-clamp: 2;
	}
}

@media (max-width: 374px) {
	html body.home .emo-home .emo-hero__visual--vendors.emo-vendor-count-5 {
		gap: 8px !important;
		grid-template-rows: 126px 126px 122px !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card figcaption {
		padding: .55rem .62rem !important;
	}
	html body.home .emo-home .emo-hero__visual--vendors .emo-hero-card figcaption strong {
		font-size: .72rem !important;
	}
}
CSS;
}

/*
 * The legacy Home renderer also emits collage rules with !important. Keep this
 * layer at the very end of the public Home markup and give it deliberately
 * higher selector specificity so the responsive geometry is deterministic.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! elmercado_home_hero_vendors_fluid_is_front_20260904() ) {
			return;
		}
		?>
		<style id="elmercado-home-hero-vendors-fluid-20260904"><?php echo elmercado_home_hero_vendors_fluid_css_20260904(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
		<?php
	},
	PHP_INT_MAX
);
