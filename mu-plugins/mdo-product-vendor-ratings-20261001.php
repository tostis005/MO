<?php
/**
 * Plugin Name: MDO - Valoraciones del productor en productos
 * Description: Muestra la valoración real del productor en el catálogo global y en la ficha individual, reutilizando la fuente de reseñas EMDO.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detecta la isla inglesa sin depender de que su helper ya esté cargado.
 */
function mdo_vendor_rating_is_english_20261001(): bool {
	if ( function_exists( 'mdo_island_en_request' ) ) {
		return (bool) mdo_island_en_request();
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

	return (bool) preg_match( '#^/en(?:/|$)#i', $path );
}

/**
 * Evita expresamente las tiendas individuales de productor.
 */
function mdo_vendor_rating_is_vendor_store_20261001(): bool {
	if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
		return true;
	}

	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() ) {
		return true;
	}

	return false;
}

/**
 * Contextos donde el usuario ha pedido la valoración:
 * - catálogo global y taxonomías de producto;
 * - producto individual principal.
 *
 * No se muestra en Home, tiendas de productor ni tarjetas relacionadas dentro
 * de la ficha individual.
 */
function mdo_vendor_rating_context_20261001( bool $single = false ): bool {
	if ( is_admin() || mdo_vendor_rating_is_vendor_store_20261001() ) {
		return false;
	}

	if ( $single ) {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return false;
		}

		global $product;
		return $product instanceof WC_Product && (int) $product->get_id() === (int) get_queried_object_id();
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}

	return function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();
}

/**
 * Obtiene el productor del producto actual.
 */
function mdo_vendor_rating_vendor_id_20261001( int $product_id ): int {
	if ( $product_id <= 0 ) {
		return 0;
	}

	if ( function_exists( 'wcfm_get_vendor_id_by_post' ) ) {
		$vendor_id = (int) wcfm_get_vendor_id_by_post( $product_id );
		if ( $vendor_id > 0 ) {
			return $vendor_id;
		}
	}

	return max( 0, (int) get_post_field( 'post_author', $product_id ) );
}

/**
 * Lee exactamente los agregados que mantiene el sistema de reseñas de WCFM.
 * La caché estática evita repetir lecturas para productos del mismo productor.
 *
 * @return array{rating:float,count:int}
 */
function mdo_vendor_rating_stats_20261001( int $vendor_id ): array {
	static $cache = array();

	if ( $vendor_id <= 0 ) {
		return array( 'rating' => 0.0, 'count' => 0 );
	}

	if ( isset( $cache[ $vendor_id ] ) ) {
		return $cache[ $vendor_id ];
	}

	$rating = 0.0;
	$count  = 0;

	/*
	 * Fuente de verdad: el sistema propio de reseñas de EMDO, exactamente el
	 * mismo que alimenta la pestaña pública "Reseñas" de cada productor.
	 *
	 * Es importante no caer a WCFM cuando EMDO devuelve cero: un productor sin
	 * reseñas EMDO debe mostrarse igualmente sin reseñas, aunque WCFM conserve
	 * datos históricos distintos.
	 */
	$using_emdo_reviews = (
		class_exists( 'MDO_Database' )
		&& class_exists( 'MDO_Reviews_Vendors' )
		&& method_exists( 'MDO_Database', 'table' )
		&& method_exists( 'MDO_Reviews_Vendors', 'review_matches_vendor_sql' )
	);

	if ( $using_emdo_reviews ) {
		global $wpdb;

		$table      = MDO_Database::table( 'reviews' );
		$vendor_sql = MDO_Reviews_Vendors::review_matches_vendor_sql( 'r' );
		$summary    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total, AVG(r.rating) AS average_rating
				 FROM {$table} r
				 WHERE r.status='validated' AND r.rating>0 AND {$vendor_sql}",
				$vendor_id,
				$vendor_id,
				$vendor_id
			),
			ARRAY_A
		);

		if ( is_array( $summary ) ) {
			$count  = (int) ( $summary['total'] ?? 0 );
			$rating = (float) ( $summary['average_rating'] ?? 0 );
		}
	} else {
		/*
		 * Compatibilidad defensiva si el plugin EMDO no estuviera cargado.
		 * No se usa mientras el sistema propio esté disponible.
		 */
		$rating = (float) get_user_meta( $vendor_id, '_wcfmmp_avg_review_rating', true );
		$count  = (int) get_user_meta( $vendor_id, '_wcfmmp_total_review_count', true );

		global $WCFMmp;
		if ( isset( $WCFMmp->wcfmmp_reviews ) ) {
			if ( $rating <= 0 && method_exists( $WCFMmp->wcfmmp_reviews, 'get_vendor_review_rating' ) ) {
				$rating = (float) $WCFMmp->wcfmmp_reviews->get_vendor_review_rating( $vendor_id );
			}
			if ( $count <= 0 && method_exists( $WCFMmp->wcfmmp_reviews, 'get_vendor_reviews_count' ) ) {
				$count = (int) $WCFMmp->wcfmmp_reviews->get_vendor_reviews_count( $vendor_id, 'approved' );
			}
		}
	}

	$rating = max( 0.0, min( 5.0, $rating ) );
	$count  = max( 0, $count );

	$cache[ $vendor_id ] = array(
		'rating' => $rating,
		'count'  => $count,
	);

	return $cache[ $vendor_id ];
}

/**
 * URL de la pestaña Reseñas del productor.
 */
function mdo_vendor_rating_reviews_url_20261001( int $vendor_id ): string {
	if ( $vendor_id <= 0 ) {
		return '';
	}

	$store_url = function_exists( 'wcfmmp_get_store_url' )
		? (string) wcfmmp_get_store_url( $vendor_id )
		: (string) get_author_posts_url( $vendor_id );

	if ( '' === $store_url ) {
		return '';
	}

	/*
	 * La pestaña propia de EMDO registra literalmente /reviews/.
	 * No usamos aquí el endpoint traducido de WCFM (p. ej. /resenas/), porque
	 * pertenece a la ruta nativa y no a la pestaña pública personalizada.
	 */
	if ( class_exists( 'MDO_Reviews_Public' ) ) {
		return trailingslashit( trailingslashit( $store_url ) . 'reviews' );
	}

	$endpoint = 'reviews';
	global $WCFMmp;
	if (
		isset( $WCFMmp->wcfmmp_rewrite )
		&& method_exists( $WCFMmp->wcfmmp_rewrite, 'store_endpoint' )
	) {
		$resolved = trim( (string) $WCFMmp->wcfmmp_rewrite->store_endpoint( 'reviews' ), '/' );
		if ( '' !== $resolved ) {
			$endpoint = $resolved;
		}
	}

	return trailingslashit( trailingslashit( $store_url ) . $endpoint );
}

/**
 * Nombre visible del productor, para accesibilidad.
 */
function mdo_vendor_rating_vendor_name_20261001( int $vendor_id ): string {
	if ( function_exists( 'wcfm_get_vendor_store_name' ) ) {
		$name = trim( wp_strip_all_tags( (string) wcfm_get_vendor_store_name( $vendor_id ) ) );
		if ( '' !== $name ) {
			return $name;
		}
	}

	$user = get_userdata( $vendor_id );
	return $user instanceof WP_User ? (string) $user->display_name : '';
}

/**
 * Construye el bloque, sin añadir schema de Product: son reseñas del vendedor,
 * no del SKU concreto.
 */
function mdo_vendor_rating_markup_20261001( int $vendor_id, bool $compact ): string {
	$stats = mdo_vendor_rating_stats_20261001( $vendor_id );
	$url   = mdo_vendor_rating_reviews_url_20261001( $vendor_id );

	if ( '' === $url ) {
		return '';
	}

	$is_en = mdo_vendor_rating_is_english_20261001();
	$name  = mdo_vendor_rating_vendor_name_20261001( $vendor_id );
	$count = (int) $stats['count'];
	$rating = (float) $stats['rating'];

	if ( $count > 0 && $rating > 0 ) {
		$score          = number_format_i18n( $rating, 1 );
		$rating_percent = number_format( ( $rating / 5 ) * 100, 2, '.', '' );
		$reviews_text   = $is_en
			? sprintf( '%s %s', number_format_i18n( $count ), 1 === $count ? 'review' : 'reviews' )
			: sprintf( '%s %s', number_format_i18n( $count ), 1 === $count ? 'reseña' : 'reseñas' );
		$aria = $is_en
			? sprintf( 'Producer %s: %s out of 5, %s', $name, $score, $reviews_text )
			: sprintf( 'Productor %s: %s de 5, %s', $name, $score, $reviews_text );

		if ( $compact ) {
			$store_url = function_exists( 'wcfmmp_get_store_url' )
				? (string) wcfmmp_get_store_url( $vendor_id )
				: (string) get_author_posts_url( $vendor_id );

			return '<div class="mdo-vendor-rating-row">'
				. '<a class="mdo-vendor-rating__vendor" href="' . esc_url( $store_url ) . '">' . esc_html( $name ) . '</a>'
				. '<span class="mdo-vendor-rating__separator" aria-hidden="true">·</span>'
				. '<a class="mdo-vendor-rating mdo-vendor-rating--loop" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $aria ) . '">'
				. '<span class="mdo-vendor-rating__stars" aria-hidden="true" style="--mdo-vendor-rating-width:' . esc_attr( $rating_percent ) . '%"></span>'
				. '<span class="mdo-vendor-rating__score">' . esc_html( $score ) . '</span>'
				. '<span class="mdo-vendor-rating__count">(' . esc_html( number_format_i18n( $count ) ) . ')</span>'
				. '</a>'
				. '</div>';
		}

		$label = $is_en ? 'Producer rating' : 'Valoración del productor';

		return '<a class="mdo-vendor-rating mdo-vendor-rating--single" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $aria ) . '">'
			. '<span class="mdo-vendor-rating__label">' . esc_html( $label ) . '</span>'
			. '<span class="mdo-vendor-rating__stars" aria-hidden="true" style="--mdo-vendor-rating-width:' . esc_attr( $rating_percent ) . '%"></span>'
			. '<span class="mdo-vendor-rating__score">' . esc_html( $score ) . '</span>'
			. '<span class="mdo-vendor-rating__count">· ' . esc_html( $reviews_text ) . '</span>'
			. '</a>';
	}

	$empty = $is_en ? 'No reviews yet' : 'Sin reseñas';
	$aria  = $is_en
		? sprintf( 'Producer %s: no reviews yet', $name )
		: sprintf( 'Productor %s: todavía sin reseñas', $name );

	if ( $compact ) {
		$store_url = function_exists( 'wcfmmp_get_store_url' )
			? (string) wcfmmp_get_store_url( $vendor_id )
			: (string) get_author_posts_url( $vendor_id );

		return '<div class="mdo-vendor-rating-row mdo-vendor-rating-row--no-reviews">'
			. '<a class="mdo-vendor-rating__vendor" href="' . esc_url( $store_url ) . '">' . esc_html( $name ) . '</a>'
			. '</div>';
	}

	$label = $is_en ? 'Producer rating' : 'Valoración del productor';
	$empty_single = $is_en ? 'No producer reviews yet' : 'Sin reseñas del productor';

	return '<a class="mdo-vendor-rating mdo-vendor-rating--single mdo-vendor-rating--empty" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $aria ) . '">'
		. '<span class="mdo-vendor-rating__label">' . esc_html( $label ) . '</span>'
		. '<span class="mdo-vendor-rating__empty">' . esc_html( $empty_single ) . '</span>'
		. '</a>';
}

/**
 * Catálogo global: sustituye visualmente el bloque "Vendido por" por una línea compacta de valoración.
 */
add_action(
	'woocommerce_after_shop_loop_item',
	static function (): void {
		if ( ! mdo_vendor_rating_context_20261001( false ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$vendor_id = mdo_vendor_rating_vendor_id_20261001( (int) $product->get_id() );
		if ( $vendor_id <= 0 ) {
			return;
		}

		echo mdo_vendor_rating_markup_20261001( $vendor_id, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	60
);

/**
 * Ficha individual: entre "Vendido por" y los metadatos SKU/categoría.
 */
add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		if ( ! mdo_vendor_rating_context_20261001( true ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$vendor_id = mdo_vendor_rating_vendor_id_20261001( (int) $product->get_id() );
		if ( $vendor_id <= 0 ) {
			return;
		}

		echo mdo_vendor_rating_markup_20261001( $vendor_id, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	39
);

/**
 * Presentación compacta y compatible con el lenguaje visual actual.
 */
add_action(
	'wp_head',
	static function (): void {
		if (
			is_admin()
			|| mdo_vendor_rating_is_vendor_store_20261001()
			|| ! ( ( function_exists( 'is_product' ) && is_product() ) || ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) )
		) {
			return;
		}
		?>
		<style id="mdo-product-vendor-ratings-20261001">
			.mdo-vendor-rating {
				display: flex;
				align-items: center;
				flex-wrap: wrap;
				gap: .28rem .42rem;
				width: fit-content;
				max-width: 100%;
				color: #42564e;
				text-decoration: none !important;
				line-height: 1.3;
			}
			.mdo-vendor-rating:hover,
			.mdo-vendor-rating:focus-visible {
				color: #173f32;
				text-decoration: none !important;
			}
			.mdo-vendor-rating__label {
				font-weight: 700;
				color: #42564e;
			}
			.mdo-vendor-rating__stars {
				--mdo-vendor-rating-width: 0%;
				position: relative;
				display: inline-block;
				width: 5.55em;
				height: 1em;
				flex: 0 0 5.55em;
				overflow: hidden;
				font-size: .9em;
				line-height: 1;
				letter-spacing: .11em;
				white-space: nowrap;
			}
			.mdo-vendor-rating__stars::before,
			.mdo-vendor-rating__stars::after {
				content: "★★★★★";
				position: absolute;
				inset: 0 auto auto 0;
				height: 1em;
				line-height: 1;
				white-space: nowrap;
			}
			.mdo-vendor-rating__stars::before {
				color: rgba(66, 86, 78, .22);
			}
			.mdo-vendor-rating__stars::after {
				width: var(--mdo-vendor-rating-width);
				overflow: hidden;
				color: #b9852f;
			}
			.mdo-vendor-rating__score {
				font-weight: 800;
				color: #173f32;
			}
			.mdo-vendor-rating__count,
			.mdo-vendor-rating__empty {
				color: #66756f;
			}
			.mdo-vendor-rating-row {
				display: flex;
				align-items: center;
				flex-wrap: wrap;
				column-gap: .34rem;
				row-gap: .12rem;
				box-sizing: border-box;
				width: auto;
				max-width: 100%;
				margin-top: .42rem;
				margin-bottom: 0;
				padding: 0;
				line-height: 1.2;
			}
			.mdo-vendor-rating__vendor {
				min-width: 0;
				color: #42564e !important;
				font-size: .76rem;
				font-weight: 700;
				line-height: 1.2;
				text-decoration: none !important;
			}
			.mdo-vendor-rating__vendor:hover,
			.mdo-vendor-rating__vendor:focus-visible {
				color: #173f32 !important;
				text-decoration: underline !important;
				text-underline-offset: 2px;
			}
			.mdo-vendor-rating__separator {
				color: rgba(66, 86, 78, .42);
				font-size: .76rem;
				line-height: 1;
			}
			.mdo-vendor-rating--loop {
				display: inline-flex;
				align-items: center;
				flex: 0 0 auto;
				flex-wrap: nowrap;
				gap: .24rem;
				margin: 0;
				padding: 0;
				font-size: .76rem;
				line-height: 1;
				white-space: nowrap;
				vertical-align: middle;
			}
			.mdo-vendor-rating--loop .mdo-vendor-rating__stars {
				width: 5.22em;
				flex-basis: 5.22em;
				font-size: .92rem;
				letter-spacing: .08em;
			}
			.mdo-vendor-rating--loop .mdo-vendor-rating__score {
				font-size: .76rem;
			}
			.mdo-vendor-rating--loop .mdo-vendor-rating__count {
				font-size: .72rem;
				color: #66756f;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag,.tax-product_brand) ul.products li.product .wcfmmp_sold_by_container,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag,.tax-product_brand) ul.products li.product .wcfmmp_sold_by_container_advanced,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag,.tax-product_brand) ul.products li.product [class*="sold_by"] {
				display: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			.mdo-vendor-rating--single {
				margin: .6rem 0 .42rem;
				padding: .58rem .72rem;
				border: 1px solid rgba(23, 63, 50, .11);
				border-radius: 10px;
				background: rgba(250, 248, 243, .72);
				font-size: .84rem;
			}
			.mdo-vendor-rating--single .mdo-vendor-rating__label {
				margin-right: .1rem;
				color: #173f32;
			}
			.mdo-vendor-rating--empty .mdo-vendor-rating__empty {
				font-style: italic;
			}

			@media (max-width: 600px) {
				.mdo-vendor-rating-row {
					column-gap: .28rem;
					row-gap: .1rem;
					margin-top: .38rem;
				}
				.mdo-vendor-rating__vendor {
					font-size: .73rem;
				}
				.mdo-vendor-rating--loop {
					gap: .2rem;
					font-size: .72rem;
				}
				.mdo-vendor-rating--loop .mdo-vendor-rating__stars {
					font-size: .88rem;
				}
				.mdo-vendor-rating--single {
					width: 100%;
					box-sizing: border-box;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Alinea la fila del productor exactamente con el contenido textual de cada
 * tarjeta. Esto evita depender de los paddings internos que Woostify cambia
 * según breakpoint y mantiene la integración en carga infinita.
 */
add_action(
	'wp_footer',
	static function (): void {
		if (
			is_admin()
			|| mdo_vendor_rating_is_vendor_store_20261001()
			|| ! ( ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) )
		) {
			return;
		}
		?>
		<script id="mdo-product-vendor-rating-align-20261001">
		(() => {
			'use strict';

			let raf = 0;
			const align = () => {
				raf = 0;
				document.querySelectorAll('ul.products > li.product').forEach((card) => {
					const row = card.querySelector('.mdo-vendor-rating-row');
					const reference = card.querySelector('.woocommerce-loop-product__title, .woostify-loop-product__title, .product-title, h2, h3');
					if (!row || !reference) return;

					const cardRect = card.getBoundingClientRect();
					const refRect = reference.getBoundingClientRect();
					const left = Math.max(0, refRect.left - cardRect.left);
					const right = Math.max(0, cardRect.right - refRect.right);

					row.style.marginInlineStart = left + 'px';
					row.style.marginInlineEnd = right + 'px';
					row.style.maxWidth = Math.max(0, cardRect.width - left - right) + 'px';
				});
			};

			const schedule = () => {
				if (raf) return;
				raf = requestAnimationFrame(align);
			};

			schedule();
			window.addEventListener('resize', schedule, { passive: true });

			const grid = document.querySelector('ul.products');
			if (grid) {
				new MutationObserver(schedule).observe(grid, { childList: true });
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);

