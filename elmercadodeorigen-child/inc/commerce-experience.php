<?php
/**
 * Experiencia de compra: avisos, carrito, checkout y confirmación accesible.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_is_commerce_surface(): bool {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return is_front_page();
	}

	return is_front_page()
		|| is_woocommerce()
		|| is_cart()
		|| is_checkout()
		|| is_account_page();
}

function elmercado_compact_css( string $css ): string {
	$css = (string) preg_replace( '!/\*.*?\*/!s', '', $css );
	$css = (string) preg_replace( '/\s+/', ' ', $css );
	$css = str_replace( array( ' {', '{ ', ' }', '; ', ': ', ', ' ), array( '{', '{', '}', ';', ':', ',' ), $css );

	return trim( $css );
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$styles = array(
			'elmercado-commerce'        => '/assets/css/commerce.css',
			'elmercado-commerce-finish' => '/assets/css/commerce-finish.css',
		);

		if ( elmercado_is_commerce_surface() ) {
			if ( function_exists( 'elmercado_is_optimized_home' ) && elmercado_is_optimized_home() ) {
				$parent_handle = wp_style_is( 'woostify-parent-style', 'registered' )
					? 'woostify-parent-style'
					: ( wp_style_is( 'woostify-parent', 'registered' ) ? 'woostify-parent' : '' );

				foreach ( $styles as $relative ) {
					$file    = ELMERCADO_THEME_PATH . $relative;
					$content = is_readable( $file ) ? file_get_contents( $file ) : false;

					if ( '' !== $parent_handle && false !== $content ) {
						wp_add_inline_style( $parent_handle, elmercado_compact_css( $content ) );
					}
				}
			} else {
				$dependency = 'elmercado-editorial';

				foreach ( $styles as $handle => $relative ) {
					$file = ELMERCADO_THEME_PATH . $relative;

					if ( ! is_readable( $file ) ) {
						continue;
					}

					wp_enqueue_style(
						$handle,
						ELMERCADO_THEME_URL . $relative,
						array( $dependency ),
						elmercado_asset_version( $relative )
					);
					$dependency = $handle;
				}
			}
		}

		wp_enqueue_script(
			'elmercado-commerce',
			ELMERCADO_THEME_URL . '/assets/js/commerce.js',
			array(),
			elmercado_asset_version( '/assets/js/commerce.js' ),
			true
		);
		wp_script_add_data( 'elmercado-commerce', 'strategy', 'defer' );

		wp_localize_script(
			'elmercado-commerce',
			'elMercadoCommerce',
			array(
				'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/carrito/' ),
				'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/finalizar-compra/' ),
			)
		);
	},
	10120
);

add_action(
	'woocommerce_before_cart',
	static function (): void {
		?>
		<section class="emo-cart-intro" aria-labelledby="emo-cart-heading">
			<div>
				<span class="emo-kicker"><?php esc_html_e( 'Tu selección', 'elmercadodeorigen' ); ?></span>
				<h2 id="emo-cart-heading"><?php esc_html_e( 'Revisa tu carrito', 'elmercadodeorigen' ); ?></h2>
			</div>
			<p><?php esc_html_e( 'Comprueba cantidades y productos antes de continuar. Verás el coste final y las opciones disponibles en el siguiente paso.', 'elmercadodeorigen' ); ?></p>
		</section>
		<div class="emo-cart-layout">
		<?php
	},
	5
);

add_action(
	'woocommerce_after_cart',
	static function (): void {
		echo '</div>';
	},
	100
);

add_action(
	'woocommerce_after_cart_totals',
	static function (): void {
		?>
		<div class="emo-cart-assurance" aria-label="Información de compra">
			<span><?php esc_html_e( 'Pago protegido durante todo el proceso', 'elmercadodeorigen' ); ?></span>
			<span><?php esc_html_e( 'Información clara antes de confirmar', 'elmercadodeorigen' ); ?></span>
			<span><?php esc_html_e( 'Atención cercana si necesitas ayuda', 'elmercadodeorigen' ); ?></span>
		</div>
		<?php
	}
);

add_action(
	'woocommerce_before_checkout_form',
	static function (): void {
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		?>
		<section class="emo-checkout-intro" aria-labelledby="emo-checkout-heading">
			<div>
				<span class="emo-kicker"><?php esc_html_e( 'Último paso', 'elmercadodeorigen' ); ?></span>
				<h2 id="emo-checkout-heading"><?php esc_html_e( 'Datos y pago', 'elmercadodeorigen' ); ?></h2>
			</div>
			<p><?php esc_html_e( 'Completa tus datos, revisa el resumen y confirma únicamente cuando toda la información sea correcta.', 'elmercadodeorigen' ); ?></p>
		</section>
		<?php
	},
	4
);

/**
 * Devuelve productores con productos publicados para el filtro de la tienda.
 *
 * @return WP_User[]
 */
function elmercado_get_shop_vendors(): array {
	$author_ids = get_posts(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => 'author',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( empty( $author_ids ) ) {
		return array();
	}

	$vendor_ids = array();
	foreach ( $author_ids as $product_id ) {
		$author_id = (int) get_post_field( 'post_author', $product_id );
		if ( $author_id > 0 ) {
			$vendor_ids[ $author_id ] = $author_id;
		}
	}

	if ( empty( $vendor_ids ) ) {
		return array();
	}

	return get_users(
		array(
			'include' => array_values( $vendor_ids ),
			'orderby' => 'display_name',
			'order'   => 'ASC',
		)
	);
}

add_action(
	'woocommerce_before_shop_loop',
	static function (): void {
		if ( ! is_shop() ) {
			return;
		}

		$vendors = elmercado_get_shop_vendors();
		if ( empty( $vendors ) ) {
			return;
		}

		$selected_vendor = isset( $_GET['productor'] ) ? absint( wp_unslash( $_GET['productor'] ) ) : 0;
		?>
		<form class="emo-vendor-filter" method="get" action="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
			<label for="emo-vendor-filter-select"><?php esc_html_e( 'Productor', 'elmercadodeorigen' ); ?></label>
			<select id="emo-vendor-filter-select" name="productor" onchange="this.form.submit()">
				<option value=""><?php esc_html_e( 'Todos los productores', 'elmercadodeorigen' ); ?></option>
				<?php foreach ( $vendors as $vendor ) : ?>
					<option value="<?php echo esc_attr( (string) $vendor->ID ); ?>" <?php selected( $selected_vendor, (int) $vendor->ID ); ?>>
						<?php echo esc_html( $vendor->display_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<noscript><button type="submit"><?php esc_html_e( 'Filtrar', 'elmercadodeorigen' ); ?></button></noscript>
		</form>
		<?php
	},
	14
);

add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! function_exists( 'is_shop' ) || ! is_shop() ) {
			return;
		}

		$vendor_id = isset( $_GET['productor'] ) ? absint( wp_unslash( $_GET['productor'] ) ) : 0;
		if ( $vendor_id > 0 ) {
			$query->set( 'author', $vendor_id );
		}
	},
	20
);
