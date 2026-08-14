<?php
/**
 * Home vendor hero and legibility polish 0.10.244.
 *
 * Keeps this change isolated from the supplier synchronizer. The hero is built
 * from active WCFM vendors with published products and uses the same store-list
 * banner source as the Producers page. It also makes the requested small-copy
 * adjustments and reasserts the final visible-category ordering.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request is the public front page.
 */
function elmercado_home_vendors_is_front_010244(): bool {
	return ! is_admin() && is_front_page() && ! is_feed() && ! is_trackback() && ! wp_doing_ajax();
}

/**
 * Normalize WCFM/WordPress image values to a usable URL.
 *
 * @param mixed $value Raw image value.
 */
function elmercado_home_vendors_image_url_010244( $value ): string {
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
		$attachment_id = absint( $value );
		if ( $attachment_id ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'large' );
			if ( $url ) {
				return (string) $url;
			}
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
 * Return disabled/offline vendor IDs using the theme guard when available.
 *
 * @return int[]
 */
function elmercado_home_vendors_disabled_ids_010244(): array {
	if ( function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' ) ) {
		return array_values( array_unique( array_map( 'absint', (array) elmercado_wcfm_disabled_vendor_ids_010210() ) ) );
	}

	return array();
}

/**
 * Active vendors that currently have at least one published product.
 *
 * @param int $limit Maximum vendors returned.
 * @return int[]
 */
function elmercado_home_active_vendor_ids_010244( int $limit = 5 ): array {
	global $wpdb;

	$limit = max( 1, min( 5, $limit ) );
	$roles = array( 'wcfm_vendor', 'vendor', 'seller' );
	$ids   = get_users(
		array(
			'role__in' => $roles,
			'fields'   => 'ID',
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		)
	);

	/* Defensive fallback for installations with a customised vendor role. */
	if ( empty( $ids ) ) {
		$ids = $wpdb->get_col(
			"SELECT DISTINCT post_author
			 FROM {$wpdb->posts}
			 WHERE post_type = 'product'
			   AND post_status = 'publish'
			   AND post_author > 0"
		);
	}

	$disabled = elmercado_home_vendors_disabled_ids_010244();
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
		if ( $product_count < 1 ) {
			continue;
		}

		$vendors[] = $vendor_id;
	}

	usort(
		$vendors,
		static function ( int $left, int $right ): int {
			$left_name  = elmercado_home_vendor_name_010244( $left );
			$right_name = elmercado_home_vendor_name_010244( $right );
			return strnatcasecmp( $left_name, $right_name );
		}
	);

	return array_slice( $vendors, 0, $limit );
}

/**
 * Store object when WCFM Marketplace is available.
 *
 * @param int $vendor_id Vendor user ID.
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
 * Store display name.
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
 * Same listing banner used by WCFM's Producers/store-list page.
 */
function elmercado_home_vendor_banner_010244( int $vendor_id ): string {
	$store = elmercado_home_vendor_store_010244( $vendor_id );
	if ( $store && method_exists( $store, 'get_list_banner' ) ) {
		$url = elmercado_home_vendors_image_url_010244( $store->get_list_banner() );
		if ( $url ) {
			return $url;
		}
	}

	$settings = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
	if ( is_array( $settings ) ) {
		foreach ( array( 'list_banner', 'banner', 'gravatar' ) as $key ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$url = elmercado_home_vendors_image_url_010244( $settings[ $key ] );
				if ( $url ) {
					return $url;
				}
			}
	}

	if ( $store && method_exists( $store, 'get_avatar' ) ) {
		$url = elmercado_home_vendors_image_url_010244( $store->get_avatar() );
		if ( $url ) {
			return $url;
		}
	}

	$avatar = get_avatar_url( $vendor_id, array( 'size' => 900 ) );
	return $avatar ? esc_url_raw( $avatar ) : '';
}

/**
 * Store URL.
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
 * Render between one and five active producer cards.
 */
function elmercado_render_home_vendor_visual_010244(): string {
	$vendor_ids = elmercado_home_active_vendor_ids_010244( 5 );
	if ( empty( $vendor_ids ) ) {
		return '';
	}

	$count = count( $vendor_ids );
	$html  = '<div class="emo-hero__visual emo-hero__visual--vendors" data-emo-vendor-count="' . (int) $count . '" aria-label="Productores activos de El Mercado de Origen">';

	foreach ( $vendor_ids as $index => $vendor_id ) {
		$name   = elmercado_home_vendor_name_010244( $vendor_id );
		$url    = elmercado_home_vendor_url_010244( $vendor_id );
		$banner = elmercado_home_vendor_banner_010244( $vendor_id );

		if ( '' === $name || '' === $url ) {
			continue;
		}

		$image = $banner
			? '<img src="' . esc_url( $banner ) . '" alt="' . esc_attr( $name ) . '" loading="' . ( 0 === $index ? 'eager' : 'lazy' ) . '" decoding="async">'
			: '<span class="emo-hero-vendor-fallback" aria-hidden="true">' . esc_html( mb_substr( $name, 0, 1 ) ) . '</span>';

		$html .= '<a class="emo-hero-card emo-hero-card--' . ( (int) $index + 1 ) . '" href="' . esc_url( $url ) . '"><figure>'
			. $image
			. '<figcaption><span>Productor</span><strong>' . esc_html( $name ) . '</strong></figcaption>'
			. '</figure></a>';
	}

	$html .= '</div>';
	return $html;
}

/**
 * Replace only the existing hero visual and reassert final category ordering.
 */
function elmercado_home_vendors_output_010244( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$visual = elmercado_render_home_vendor_visual_010244();
	if ( $visual ) {
		$updated = preg_replace(
			'~<div class="emo-hero__visual"[^>]*>.*?</div>~s',
			$visual,
			$html,
			1
		);
		if ( is_string( $updated ) ) {
			$html = $updated;
		}
	}

	/* The 0.10.226 renderer sorts by real visible product count DESC. */
	if ( function_exists( 'elmercado_home_categories_visible_html_010226' ) ) {
		$categories = (string) elmercado_home_categories_visible_html_010226();
		if ( '' !== $categories ) {
			$updated = preg_replace(
				'~<section class="emo-section emo-categories"[^>]*>.*?</section>~s',
				$categories,
				$html,
				1
			);
			if ( is_string( $updated ) ) {
				$html = $updated;
			}
		}
	}

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! elmercado_home_vendors_is_front_010244() ) {
			return;
		}

		ob_start( 'elmercado_home_vendors_output_010244' );
	},
	-9000
);

/**
 * Small, deliberately conservative home-only visual adjustments.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_home_vendors_is_front_010244() ) {
			return;
		}
		?>
		<style id="elmercado-home-vendors-010244">
			body.home .emo-hero {
				min-height: min(650px, calc(100svh - 108px)) !important;
				padding-top: clamp(2.15rem, 3vw, 3rem) !important;
				padding-bottom: clamp(2.6rem, 4.4vw, 4.25rem) !important;
			}

			body.home .emo-hero__grid {
				gap: clamp(2.5rem, 5vw, 5rem) !important;
			}

			body.home .emo-hero__copy > p {
				font-size: clamp(1.07rem, 1.48vw, 1.27rem) !important;
			}

			body.home .emo-hero__proof {
				margin-top: clamp(1.8rem, 3vw, 2.65rem) !important;
			}

			body.home .emo-hero__proof span {
				font-size: .78rem !important;
				line-height: 1.42 !important;
			}

			body.home .emo-hero__proof strong {
				font-size: .86rem !important;
			}

			body.home .emo-trust article > span,
			body.home .emo-story__values article > span {
				font-size: .74rem !important;
			}

			body.home .emo-trust strong {
				font-size: 1rem !important;
			}

			body.home .emo-trust p {
				font-size: .88rem !important;
				line-height: 1.6 !important;
			}

			body.home .emo-story__panel > p {
				font-size: 1.1rem !important;
				line-height: 1.66 !important;
			}

			body.home .emo-story__values p {
				font-size: .96rem !important;
				line-height: 1.62 !important;
			}

			body.home .emo-vendor-cta p {
				font-size: 1.02rem !important;
				line-height: 1.65 !important;
			}

			body.home .emo-hero__visual--vendors {
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

			body.home .emo-hero__visual--vendors[data-emo-vendor-count="4"] .emo-hero-card--1 {
				grid-column: 1 / 7 !important;
				grid-row: 1 / 7 !important;
			}

			body.home .emo-hero__visual--vendors[data-emo-vendor-count="4"] .emo-hero-card--2 {
				grid-column: 7 / 13 !important;
				grid-row: 1 / 6 !important;
			}

			body.home .emo-hero__visual--vendors[data-emo-vendor-count="4"] .emo-hero-card--3 {
				grid-column: 1 / 6 !important;
				grid-row: 7 / 11 !important;
			}

			body.home .emo-hero__visual--vendors[data-emo-vendor-count="4"] .emo-hero-card--4 {
				grid-column: 6 / 13 !important;
				grid-row: 6 / 11 !important;
				transform: rotate(-.55deg) !important;
			}

			body.home .emo-hero__visual--vendors[data-emo-vendor-count="5"] .emo-hero-card--1 {
				grid-column: 1 / 6 !important;
				grid-row: 1 / 7 !important;
			}

			body.home .emo-hero__visual--vendors[data-emo-vendor-count="5"] .emo-hero-card--2 {
				grid-column: 6 / 13 !important;
				grid-row: 1 / 5 !important;
			}

			body.home .emo-hero__visual--vendors[data-emo-vendor-count="5"] .emo-hero-card--3 {
				grid-column: 1 / 5 !important;
				grid-row: 7 / 11 !important;
			}

			body.home .emo-hero__visual--vendors[data-emo-vendor-count="5"] .emo-hero-card--4 {
				grid-column: 5 / 9 !important;
				grid-row: 5 / 11 !important;
				transform: rotate(-.55deg) !important;
			}

			body.home .emo-hero__visual--vendors[data-emo-vendor-count="5"] .emo-hero-card--5 {
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
					padding-top: 2.15rem !important;
					padding-bottom: 2.8rem !important;
				}

				body.home .emo-hero__visual--vendors {
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

				body.home .emo-hero__visual--vendors[data-emo-vendor-count="1"] .emo-hero-card--1,
				body.home .emo-hero__visual--vendors[data-emo-vendor-count="3"] .emo-hero-card--1,
				body.home .emo-hero__visual--vendors[data-emo-vendor-count="5"] .emo-hero-card--1 {
					grid-column: 1 / -1 !important;
					min-height: 185px !important;
				}

				body.home .emo-trust p {
					font-size: .9rem !important;
				}

				body.home .emo-story__values p,
				body.home .emo-vendor-cta p {
					font-size: .98rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
