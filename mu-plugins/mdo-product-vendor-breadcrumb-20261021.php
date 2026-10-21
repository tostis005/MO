<?php
/**
 * Rastro de navegación de producto: Tienda > Productor > Producto.
 *
 * Woostify oculta las migas nativas en este sitio. Esta pieza solo añade un
 * bloque en fichas individuales, sin reactivar los page headers del tema.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Construye enlaces y etiquetas sin consultar todos los productos del vendedor.
 *
 * @param int $product_id ID de producto.
 * @return array<string,mixed>
 */
function emdo_product_breadcrumb_data_20261021( int $product_id ): array {
	if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
		return array();
	}

	$title = trim( wp_strip_all_tags( (string) get_the_title( $product_id ) ) );
	if ( '' === $title ) {
		return array();
	}

	$shop_url = function_exists( 'wc_get_page_permalink' )
		? (string) wc_get_page_permalink( 'shop' )
		: '';
	if ( '' === $shop_url ) {
		return array();
	}

	$vendor_id = function_exists( 'wcfm_get_vendor_id_by_post' )
		? absint( wcfm_get_vendor_id_by_post( $product_id ) )
		: 0;

	// Compatibilidad con productos WCFM anteriores a la relación explícita.
	if ( 0 === $vendor_id ) {
		$author_id = absint( get_post_field( 'post_author', $product_id ) );
		if ( $author_id > 0 && function_exists( 'wcfm_is_vendor' ) && wcfm_is_vendor( $author_id ) ) {
			$vendor_id = $author_id;
		}
	}

	$vendor_name = '';
	$vendor_url  = '';
	if ( $vendor_id > 0 && function_exists( 'wcfmmp_get_store_url' ) ) {
		$vendor_url = trim( (string) wcfmmp_get_store_url( $vendor_id ) );
		if ( '' !== $vendor_url && function_exists( 'wcfm_get_vendor_store_name' ) ) {
			$vendor_name = trim( wp_strip_all_tags( (string) wcfm_get_vendor_store_name( $vendor_id ) ) );
		}
		if ( '' !== $vendor_url && '' === $vendor_name ) {
			$user = get_userdata( $vendor_id );
			if ( $user instanceof WP_User ) {
				$vendor_name = trim( wp_strip_all_tags( (string) $user->display_name ) );
			}
		}
	}

	if ( '' === $vendor_name || '' === $vendor_url ) {
		$vendor_name = '';
		$vendor_url  = '';
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$is_english = 0 === strpos( $path, '/en/' )
		|| ( function_exists( 'get_locale' ) && 0 === strpos( (string) get_locale(), 'en_' ) );

	return array(
		'shop_name'   => $is_english ? 'Shop' : 'Tienda',
		'shop_url'    => $shop_url,
		'vendor_name' => $vendor_name,
		'vendor_url'  => $vendor_url,
		'title'       => $title,
		'aria_label'  => $is_english ? 'Breadcrumb' : 'Ruta de navegación',
	);
}

/**
 * Marcado semántico con enlaces solo para Tienda y Productor.
 *
 * @param array<string,mixed> $data Datos precalculados.
 */
function emdo_product_breadcrumb_html_20261021( array $data ): string {
	if ( empty( $data['shop_url'] ) || empty( $data['title'] ) ) {
		return '';
	}

	$html  = '<nav class="emo-product-breadcrumb-20261021" aria-label="' . esc_attr( (string) $data['aria_label'] ) . '">';
	$html .= '<ol class="emo-product-breadcrumb__items">';
	$html .= '<li><a href="' . esc_url( (string) $data['shop_url'] ) . '">' . esc_html( (string) $data['shop_name'] ) . '</a></li>';

	if ( ! empty( $data['vendor_name'] ) && ! empty( $data['vendor_url'] ) ) {
		$html .= '<li><a href="' . esc_url( (string) $data['vendor_url'] ) . '">' . esc_html( (string) $data['vendor_name'] ) . '</a></li>';
	}

	$html .= '<li><span aria-current="page">' . esc_html( (string) $data['title'] ) . '</span></li>';
	$html .= '</ol></nav>';

	return $html;
}

add_action(
	'woocommerce_before_single_product',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$product_id = (int) get_queried_object_id();
		$data = emdo_product_breadcrumb_data_20261021( $product_id );
		if ( $data ) {
			echo emdo_product_breadcrumb_html_20261021( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each dynamic field escaped in builder.
		}
	},
	1
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		?>
		<style id="emo-product-breadcrumb-20261021">
			body.elmercado-child-theme .emo-product-breadcrumb-20261021 {
				display: block;
				margin: 0 0 clamp(18px, 2.5vw, 28px);
				padding: 0;
				color: #68786d;
				font-size: 13px;
				line-height: 1.55;
				font-weight: 550;
			}
			body.elmercado-child-theme .emo-product-breadcrumb__items {
				display: flex;
				flex-wrap: wrap;
				align-items: baseline;
				gap: 4px 9px;
				margin: 0;
				padding: 0;
				list-style: none;
			}
			body.elmercado-child-theme .emo-product-breadcrumb__items li {
				display: inline-flex;
				min-width: 0;
				align-items: baseline;
				gap: 9px;
				margin: 0;
				padding: 0;
				list-style: none;
			}
			body.elmercado-child-theme .emo-product-breadcrumb__items li + li::before {
				content: "›";
				color: #91a098;
				font-weight: 650;
			}
			body.elmercado-child-theme .emo-product-breadcrumb__items a {
				color: #2c6c51;
				text-decoration: none;
				text-underline-offset: 3px;
			}
			body.elmercado-child-theme .emo-product-breadcrumb__items a:hover,
			body.elmercado-child-theme .emo-product-breadcrumb__items a:focus-visible {
				color: #173f32;
				text-decoration: underline;
			}
			body.elmercado-child-theme .emo-product-breadcrumb__items [aria-current="page"] {
				color: #34453b;
				font-weight: 650;
				overflow-wrap: anywhere;
			}
			@media (max-width: 600px) {
				body.elmercado-child-theme .emo-product-breadcrumb-20261021 {
					font-size: 12px;
					margin-bottom: 18px;
				}
				body.elmercado-child-theme .emo-product-breadcrumb__items,
				body.elmercado-child-theme .emo-product-breadcrumb__items li {
					gap: 3px 7px;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
