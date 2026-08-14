<?php
/**
 * Keep public Home fresh and apply the isolated Home presentation layer.
 *
 * 0.10.244 adds the active-producer hero, conservative small-copy sizing and
 * a final category-order assertion without touching the supplier synchronizer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public front-page request only.
 */
function elmercado_home_fresh_is_front_010244(): bool {
	return ! is_admin() && is_front_page() && ! is_feed() && ! is_trackback() && ! wp_doing_ajax();
}

/* Keep the Home HTML fresh across devices. */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}

		$version = defined( 'ELMERCADO_THEME_VERSION' ) ? (string) ELMERCADO_THEME_VERSION : '';
		if ( '' !== $version ) {
			$key = 'elmercado_home_' . md5( $version . '|' . home_url( '/' ) );
			delete_transient( $key );
		}

		$static_html = WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html';
		if ( is_file( $static_html ) ) {
			@unlink( $static_html );
		}

		nocache_headers();
		if ( ! headers_sent() ) {
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
			header( 'Pragma: no-cache', true );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
			header( 'X-El-Mercado-Home-Fresh: BYPASS', true );
		}
	},
	-3000
);

/**
 * Normalize WCFM / WordPress image values to an URL.
 *
 * @param mixed $value Raw value.
 */
function elmercado_home_vendor_image_url_010244( $value ): string {
	if ( is_array( $value ) ) {
		if ( ! empty( $value['url'] ) ) {
			$value = $value['url'];
		} elseif ( ! empty( $value['id'] ) ) {
			$value = $value['id'];
		} elseif ( ! empty( $value['ID'] ) ) {
			$value = $value['ID'];
		}
	}

	if ( is_numeric( $value ) ) {
		$url = wp_get_attachment_image_url( absint( $value ), 'large' );
		if ( $url ) {
			return (string) $url;
		}
	}

	if ( is_string( $value ) ) {
		$value = trim( $value );
		if ( '' !== $value && preg_match( '~^https?://~i', $value ) ) {
			return esc_url_raw( $value );
		}
	}

	return '';
}

/**
 * WCFM store object.
 *
 * @return object|null
 */
function elmercado_home_vendor_store_010244( int $vendor_id ) {
	if ( function_exists( 'wcfmmp_get_store' ) ) {
		$store = wcfmmp_get_store( $vendor_id );
		if ( is_object( $store ) ) {
			return $store;
		}
	}

	return null;
}

/**
 * Producer name, matching WCFM store data.
 */
function elmercado_home_vendor_name_010244( int $vendor_id ): string {
	$store = elmercado_home_vendor_store_010244( $vendor_id );
	if ( $store && method_exists( $store, 'get_shop_info' ) ) {
		$info = (array) $store->get_shop_info();
		if ( ! empty( $info['store_name'] ) ) {
			return wp_strip_all_tags( (string) $info['store_name'] );
		}
	}

	$settings = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
	if ( is_array( $settings ) && ! empty( $settings['store_name'] ) ) {
		return wp_strip_all_tags( (string) $settings['store_name'] );
	}

	$user = get_userdata( $vendor_id );
	return $user ? (string) $user->display_name : '';
}

/**
 * Same store-list banner WCFM uses on the Producers page.
 */
function elmercado_home_vendor_banner_010244( int $vendor_id ): string {
	$store = elmercado_home_vendor_store_010244( $vendor_id );
	if ( $store && method_exists( $store, 'get_list_banner' ) ) {
		$url = elmercado_home_vendor_image_url_010244( $store->get_list_banner() );
		if ( $url ) {
			return $url;
		}
	}

	$settings = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
	if ( is_array( $settings ) ) {
		foreach ( array( 'list_banner', 'banner', 'gravatar' ) as $key ) {
			if ( empty( $settings[ $key ] ) ) {
				continue;
			}
			$url = elmercado_home_vendor_image_url_010244( $settings[ $key ] );
			if ( $url ) {
				return $url;
			}
		}
	}

	if ( $store && method_exists( $store, 'get_avatar' ) ) {
		$url = elmercado_home_vendor_image_url_010244( $store->get_avatar() );
		if ( $url ) {
			return $url;
		}
	}

	$avatar = get_avatar_url( $vendor_id, array( 'size' => 900 ) );
	return $avatar ? esc_url_raw( $avatar ) : '';
}

/**
 * Producer store URL.
 */
function elmercado_home_vendor_url_010244( int $vendor_id ): string {
	if ( function_exists( 'wcfmmp_get_store_url' ) ) {
		$url = wcfmmp_get_store_url( $vendor_id );
		if ( $url ) {
			return (string) $url;
		}
	}

	return get_author_posts_url( $vendor_id );
}

/**
 * Candidate active WCFM vendors that have at least one published product.
 * Final renderability (valid store name + URL) is checked when cards are built.
 *
 * @return int[]
 */
function elmercado_home_active_vendor_ids_010244( int $limit = 5 ): array {
	global $wpdb;

	$limit = max( 1, min( 5, $limit ) );
	$ids   = get_users(
		array(
			'role__in' => array( 'wcfm_vendor', 'vendor', 'seller' ),
			'fields'   => 'ID',
		)
	);

	if ( empty( $ids ) ) {
		$ids = $wpdb->get_col(
			"SELECT DISTINCT post_author
			 FROM {$wpdb->posts}
			 WHERE post_type = 'product'
			   AND post_status = 'publish'
			   AND post_author > 0"
		);
	}

	$disabled = function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' )
		? array_values( array_unique( array_map( 'absint', (array) elmercado_wcfm_disabled_vendor_ids_010210() ) ) )
		: array();
	$vendors  = array();

	foreach ( array_unique( array_map( 'absint', (array) $ids ) ) as $vendor_id ) {
		if ( ! $vendor_id || in_array( $vendor_id, $disabled, true ) ) {
			continue;
		}

		$disable_meta = strtolower( trim( (string) get_user_meta( $vendor_id, '_disable_vendor', true ) ) );
		$offline_meta = strtolower( trim( (string) get_user_meta( $vendor_id, '_wcfm_store_offline', true ) ) );
		if ( in_array( $disable_meta, array( 'yes', '1', 'true', 'on' ), true ) || in_array( $offline_meta, array( 'yes', '1', 'true', 'on' ), true ) ) {
			continue;
		}

		$product_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID)
				 FROM {$wpdb->posts}
				 WHERE post_type = 'product'
				   AND post_status = 'publish'
				   AND post_author = %d",
				$vendor_id
			)
		);
		if ( $product_count > 0 ) {
			$vendors[] = $vendor_id;
		}
	}

	usort(
		$vendors,
		static function ( int $left, int $right ): int {
			return strnatcasecmp( elmercado_home_vendor_name_010244( $left ), elmercado_home_vendor_name_010244( $right ) );
		}
	);

	/* Allow a few extra candidates so invalid stores do not prevent reaching 5 valid cards. */
	return array_slice( $vendors, 0, max( 5, $limit + 3 ) );
}

/**
 * Render between one and five valid producer cards.
 */
function elmercado_render_home_vendor_visual_010244(): string {
	$vendor_ids = elmercado_home_active_vendor_ids_010244( 5 );
	if ( empty( $vendor_ids ) ) {
		return '';
	}

	$cards = array();
	foreach ( $vendor_ids as $vendor_id ) {
		if ( count( $cards ) >= 5 ) {
			break;
		}

		$name = trim( elmercado_home_vendor_name_010244( $vendor_id ) );
		$url  = trim( elmercado_home_vendor_url_010244( $vendor_id ) );
		if ( '' === $name || '' === $url ) {
			continue;
		}

		$banner      = elmercado_home_vendor_banner_010244( $vendor_id );
		$card_number = count( $cards ) + 1;
		$initial     = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 );
		$image       = $banner
			? '<img src="' . esc_url( $banner ) . '" alt="' . esc_attr( $name ) . '" loading="' . ( 1 === $card_number ? 'eager' : 'lazy' ) . '" decoding="async">'
			: '<span class="emo-hero-vendor-fallback" aria-hidden="true">' . esc_html( $initial ) . '</span>';

		$cards[] = '<a class="emo-hero-card emo-hero-card--' . $card_number . '" href="' . esc_url( $url ) . '"><figure>'
			. $image
			. '<figcaption><span>Productor</span><strong>' . esc_html( $name ) . '</strong></figcaption>'
			. '</figure></a>';
	}

	$count = count( $cards );
	if ( 0 === $count ) {
		return '';
	}

	return '<div class="emo-hero__visual emo-hero__visual--vendors emo-vendor-count-' . $count . '" data-emo-vendor-count="' . $count . '" aria-label="Productores activos de El Mercado de Origen">'
		. implode( '', $cards )
		. '</div>';
}

/**
 * Requested Home-only sizing and 1–5 producer collage CSS.
 */
function elmercado_home_vendor_css_010244(): string {
	return <<<'CSS'
/* elmercado-home-vendors-010244 */
body.home .emo-hero {
	min-height: min(600px, calc(100svh - 108px)) !important;
	padding-top: clamp(1.75rem, 2.35vw, 2.35rem) !important;
	padding-bottom: clamp(2rem, 3vw, 3rem) !important;
}
body.home .emo-hero__grid { gap: clamp(2rem, 4vw, 4rem) !important; }
body.home .emo-hero h1 {
	font-size: clamp(3.75rem, 5.45vw, 4.9rem) !important;
	line-height: .94 !important;
}
body.home .emo-hero__copy > p {
	font-size: clamp(1rem, 1.25vw, 1.12rem) !important;
	line-height: 1.5 !important;
	margin-top: .75rem !important;
	margin-bottom: 1rem !important;
}
body.home .emo-hero__proof {
	margin-top: clamp(1.2rem, 2vw, 1.75rem) !important;
	padding-top: .8rem !important;
	gap: .65rem !important;
}
body.home .emo-hero__proof span { font-size: .78rem !important; line-height: 1.42 !important; }
body.home .emo-hero__proof strong { font-size: .86rem !important; }
body.home .emo-trust article > span,
body.home .emo-story__values article > span { font-size: .74rem !important; }
body.home .emo-trust strong { font-size: 1rem !important; }
body.home .emo-trust p { font-size: .88rem !important; line-height: 1.6 !important; }
body.home .emo-story__panel > p { font-size: 1.1rem !important; line-height: 1.66 !important; }
body.home .emo-story__values p { font-size: .96rem !important; line-height: 1.62 !important; }
body.home .emo-vendor-cta p { font-size: 1.02rem !important; line-height: 1.65 !important; }

body.home .emo-hero__visual--vendors {
	transform: translateY(-34px);
	grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
	grid-template-rows: repeat(10, 38px) !important;
	min-width: 0;
}
body.home .emo-hero__visual--vendors .emo-hero-card--1 {
	grid-column: 1 / 7 !important;
	grid-row: 1 / 11 !important;
	transform: rotate(-1.2deg) !important;
}
body.home .emo-hero__visual--vendors .emo-hero-card--2 {
	grid-column: 7 / 13 !important;
	grid-row: 1 / 6 !important;
	transform: rotate(1.1deg) !important;
}
body.home .emo-hero__visual--vendors .emo-hero-card--3 {
	grid-column: 7 / 13 !important;
	grid-row: 6 / 11 !important;
	transform: rotate(.45deg) !important;
}

body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--1 {
	grid-column: 1 / 7 !important;
	grid-row: 1 / 7 !important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--2 {
	grid-column: 7 / 13 !important;
	grid-row: 1 / 6 !important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--3 {
	grid-column: 1 / 6 !important;
	grid-row: 7 / 11 !important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-4 .emo-hero-card--4 {
	grid-column: 6 / 13 !important;
	grid-row: 6 / 11 !important;
	transform: rotate(-.55deg) !important;
}

body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--1 {
	grid-column: 1 / 6 !important;
	grid-row: 1 / 7 !important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--2 {
	grid-column: 6 / 13 !important;
	grid-row: 1 / 5 !important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--3 {
	grid-column: 1 / 5 !important;
	grid-row: 7 / 11 !important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--4 {
	grid-column: 5 / 9 !important;
	grid-row: 5 / 11 !important;
	transform: rotate(-.55deg) !important;
}
body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--5 {
	grid-column: 9 / 13 !important;
	grid-row: 5 / 11 !important;
	transform: rotate(.65deg) !important;
}

body.home .emo-hero-vendor-fallback {
	display: grid;
	width: 100%;
	height: 100%;
	place-items: center;
	background: rgba(255,255,255,.08);
	color: #fff;
	font-family: Georgia, serif;
	font-size: clamp(3rem, 7vw, 6rem);
}

@media (max-width: 767px) {
	body.home .emo-hero {
		min-height: 0 !important;
		padding-top: 1.6rem !important;
		padding-bottom: 2rem !important;
	}
	body.home .emo-hero__grid {
		gap: 1.2rem !important;
	}
	body.home .emo-hero h1 {
		font-size: 2.55rem !important;
		line-height: .96 !important;
	}
	body.home .emo-hero__copy > p {
		font-size: .91rem !important;
		line-height: 1.48 !important;
		margin-top: .6rem !important;
		margin-bottom: .75rem !important;
	}
	body.home .emo-hero__proof {
		margin-top: 1rem !important;
		padding-top: .7rem !important;
		gap: .55rem !important;
	}
	body.home .emo-hero__visual--vendors {
		transform: translateY(-6px);
		display: grid !important;
		grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
		grid-template-rows: none !important;
		gap: .75rem !important;
	}
	body.home .emo-hero__visual--vendors .emo-hero-card {
		grid-column: auto !important;
		grid-row: auto !important;
		min-height: 145px !important;
		transform: none !important;
	}
	body.home .emo-hero__visual--vendors.emo-vendor-count-1 .emo-hero-card--1,
	body.home .emo-hero__visual--vendors.emo-vendor-count-3 .emo-hero-card--1,
	body.home .emo-hero__visual--vendors.emo-vendor-count-5 .emo-hero-card--1 {
		grid-column: 1 / -1 !important;
		min-height: 185px !important;
	}
	body.home .emo-trust p { font-size: .9rem !important; }
	body.home .emo-story__values p,
	body.home .emo-vendor-cta p { font-size: .98rem !important; }
}
CSS;
}

/**
 * Replace the existing product collage with producers, reassert category order,
 * and inject the final CSS after the Home asset optimizer has already run.
 */
function elmercado_home_fresh_output_010244( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$visual = elmercado_render_home_vendor_visual_010244();
	if ( $visual ) {
		$updated = preg_replace( '~<div class="emo-hero__visual"[^>]*>.*?</div>~s', $visual, $html, 1 );
		if ( is_string( $updated ) ) {
			$html = $updated;
		}
	}

	if ( function_exists( 'elmercado_home_category_output_html_010226' ) ) {
		$categories = (string) elmercado_home_category_output_html_010226();
		if ( '' !== $categories ) {
			$start = strpos( $html, '<section class="emo-section emo-categories"' );
			$end   = false !== $start ? strpos( $html, '</section>', $start ) : false;
			if ( false !== $start && false !== $end ) {
				$end  += strlen( '</section>' );
				$html = substr_replace( $html, $categories, $start, $end - $start );
			}
		}
	}

	if ( ! str_contains( $html, 'id="elmercado-home-vendors-010244"' ) ) {
		$style    = '<style id="elmercado-home-vendors-010244">' . elmercado_home_vendor_css_010244() . '</style>';
		$head_end = strpos( $html, '</head>' );
		if ( false !== $head_end ) {
			$html = substr_replace( $html, $style, $head_end, 0 );
		} else {
			$hero_start = strpos( $html, '<section class="emo-hero"' );
			if ( false !== $hero_start ) {
				$html = substr_replace( $html, $style, $hero_start, 0 );
			}
		}
	}

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( elmercado_home_fresh_is_front_010244() ) {
			ob_start( 'elmercado_home_fresh_output_010244' );
		}
	},
	-9000
);
