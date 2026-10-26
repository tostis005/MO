<?php
/**
 * Compra rápida en tarjetas del catálogo y tiendas WCFM.
 *
 * Reutiliza el enlace nativo de WooCommerce para respetar las reglas de compra,
 * integraciones AJAX, stock y productos con opciones. No duplica botones.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return bool */
function elmercado_is_quick_add_catalog_page_20261026(): bool {
	if ( is_admin() || ! function_exists( 'is_shop' ) ) {
		return false;
	}

	return is_shop()
		|| is_product_category()
		|| is_product_tag()
		|| ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() )
		|| ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() )
		|| (bool) get_query_var( 'store' );
}

/**
 * Woostify oculta/elimina los botones nativos del HTML de las tarjetas.
 * Pintamos una acción desde el producto real, sin depender de una acción
 * anterior que no existe en el DOM. El JavaScript la posiciona como overlay.
 */
add_action(
	'woocommerce_after_shop_loop_item_title',
	static function (): void {
		if ( ! elmercado_is_quick_add_catalog_page_20261026() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product || ! $product->is_visible() || ! $product->is_in_stock() ) {
			return;
		}

		$is_simple = $product->is_type( 'simple' ) && $product->is_purchasable();
		if ( $product->is_type( 'simple' ) && ! $is_simple ) {
			return;
		}

		$label = $is_simple
			? __( 'Añadir al carrito', 'elmercadodeorigen' )
			: __( 'Seleccionar opciones', 'elmercadodeorigen' );
		$url = $is_simple ? $product->add_to_cart_url() : get_permalink( $product->get_id() );
		if ( ! $url ) {
			return;
		}

		$classes = $is_simple
			? 'button product_type_simple add_to_cart_button ajax_add_to_cart emo-card-quick-add-source'
			: 'button product_type_variable emo-card-quick-add-source';

		printf(
			'<a href="%1$s" class="%2$s" data-product_id="%3$d" data-product_sku="%4$s" data-quantity="1" aria-label="%5$s" rel="nofollow">%6$s</a>',
			esc_url( $url ),
			esc_attr( $classes ),
			(int) $product->get_id(),
			esc_attr( $product->get_sku() ),
			esc_attr( sprintf( '%s: %s', $label, $product->get_name() ) ),
			esc_html( $label )
		);
	},
	999
);


/**
 * El selector rápido utiliza la plantilla REAL de variaciones de WooCommerce.
 * Esto respeta sus precios, stock y los campos adicionales que impriman los
 * plugins, sin inventar un modelo de opciones distinto al de cada productor.
 */
function elmercado_quick_variations_form_20261026(): void {
	if ( ! check_ajax_referer( 'emo_quick_cart_20261026', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'La sesión ha caducado. Actualiza la página.' ), 403 );
	}

	$id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
	$item = wc_get_product( $id );
	if ( ! $item || ! $item->is_type( 'variable' ) || ! $item->is_visible() || ! $item->is_purchasable() || ! $item->is_in_stock() ) {
		wp_send_json_error( array( 'message' => 'Este producto no está disponible para compra rápida.' ), 400 );
	}

	global $product;
	$original_product = $product;
	$product          = $item;
	ob_start();
	try {
		woocommerce_variable_add_to_cart();
		$html = ob_get_clean();
	} catch ( Throwable $error ) {
		ob_end_clean();
		$product = $original_product;
		wp_send_json_error( array( 'message' => 'No se han podido cargar las opciones.' ), 500 );
	}
	$product = $original_product;

	if ( ! $html || false === strpos( $html, 'variations_form' ) ) {
		wp_send_json_error( array( 'message' => 'Este artículo necesita configurarse en su ficha.' ), 400 );
	}

	wp_send_json_success( array(
		'html' => $html,
		'title' => $item->get_name(),
		'url' => get_permalink( $id ),
	) );
}

add_action( 'wp_ajax_emo_quick_variations_20261026', 'elmercado_quick_variations_form_20261026' );
add_action( 'wp_ajax_nopriv_emo_quick_variations_20261026', 'elmercado_quick_variations_form_20261026' );

/**
 * Registrar la variación real en el carrito; no crear una compra provisional.
 * Usar WC_Cart garantiza las comprobaciones de atributos, existencias y
 * validaciones de plugins, y devolver fragmentos nativos sincroniza el mini-cart.
 */
function elmercado_quick_add_variation_20261026(): void {
	if ( ! check_ajax_referer( 'emo_quick_cart_20261026', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'La sesión ha caducado. Actualiza la página.' ), 403 );
	}

	$id           = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
	$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
	$quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1;
	$item         = wc_get_product( $id );
	$variation    = wc_get_product( $variation_id );

	if ( ! $item || ! $item->is_type( 'variable' ) || ! $item->is_visible() || ! $item->is_purchasable()
		|| ! $item->is_in_stock() || ! $variation || ! $variation->is_type( 'variation' )
		|| (int) $variation->get_parent_id() !== $id || ! $variation->is_purchasable()
		|| ! $variation->is_in_stock() || $quantity <= 0 ) {
		wp_send_json_error( array( 'message' => 'Selecciona una variante disponible y una cantidad válida.' ), 400 );
	}

	$attributes = array();
	foreach ( $_POST as $key => $value ) {
		if ( str_starts_with( (string) $key, 'attribute_' ) && is_scalar( $value ) ) {
			$attributes[ sanitize_text_field( wp_unslash( $key ) ) ] = sanitize_text_field( wp_unslash( $value ) );
		}
	}

	try {
		$is_valid = apply_filters( 'woocommerce_add_to_cart_validation', true, $id, $quantity, $variation_id, $attributes );
		$added = $is_valid && WC()->cart && WC()->cart->add_to_cart( $id, $quantity, $variation_id, $attributes );
	} catch ( Throwable $error ) {
		$added = false;
	}

	if ( ! $added ) {
		$errors = wc_get_notices( 'error' );
		$message = ! empty( $errors[0]['notice'] ) ? wp_strip_all_tags( $errors[0]['notice'] ) : 'No se ha podido añadir el artículo. Revisa las opciones seleccionadas.';
		wc_clear_notices();
		wp_send_json_error( array( 'message' => $message ), 400 );
	}

	wc_clear_notices();
	do_action( 'woocommerce_ajax_added_to_cart', $id );
	WC_AJAX::get_refreshed_fragments();
}

add_action( 'wp_ajax_emo_quick_add_variation_20261026', 'elmercado_quick_add_variation_20261026' );
add_action( 'wp_ajax_nopriv_emo_quick_add_variation_20261026', 'elmercado_quick_add_variation_20261026' );

/* El AJAX de WooCommerce actualiza el contador y los fragmentos del minicart. */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( elmercado_is_quick_add_catalog_page_20261026() && function_exists( 'WC' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}
	},
	30
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_is_quick_add_catalog_page_20261026() ) {
			return;
		}
		?>
		<style id="emo-catalog-quick-add-20261026">
			body.elmercado-child-theme ul.products li.product:has(> a.emo-card-quick-add) { position:relative !important; }
			/* Pestaña de carrito anclada a la esquina exterior de la tarjeta, sin margen. */
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add {
				box-sizing:border-box !important;
				position:absolute !important;
				top:-1px !important;
				right:-1px !important;
				left:auto !important;
				bottom:auto !important;
				z-index:12 !important;
				display:flex !important;
				align-items:center !important;
				justify-content:center !important;
				width:58px !important;
				min-width:58px !important;
				max-width:58px !important;
				height:53px !important;
				min-height:53px !important;
				max-height:53px !important;
				padding:0 !important;
				margin:0 !important;
				gap:0 !important;
				border:0 !important;
				border-radius:0 0 0 16px !important;
				background:#173f32 !important;
				color:#fff !important;
				box-shadow:-2px 3px 9px rgba(13,38,30,.14) !important;
				font-size:0 !important;
				line-height:0 !important;
				letter-spacing:0 !important;
				text-decoration:none !important;
				text-transform:none !important;
				white-space:nowrap !important;
				opacity:1 !important;
				visibility:visible !important;
				pointer-events:auto !important;
				transform:none !important;
				overflow:hidden !important;
				transition:background-color .15s,color .15s !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add::before,
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add::after { content:none !important; display:none !important; }
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add .emo-card-quick-add__symbol {
				display:flex !important;
				align-items:center !important;
				justify-content:center !important;
				width:29px !important;
				height:29px !important;
				margin:0 !important;
				color:inherit !important;
				font-size:0 !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add .emo-card-quick-add__symbol svg {
				display:block !important;
				width:27px !important;
				height:27px !important;
				stroke:currentColor !important;
				fill:none !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add:is(:hover,:focus-visible,.emo-card-quick-add--confirmed) {
				background:#0c2b20 !important;
				border-color:rgba(255,255,255,.68) !important;
				color:#fff !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add:focus-visible { outline:3px solid #ecc66f !important; outline-offset:2px !important; }
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add.loading { pointer-events:none !important; opacity:.62 !important; }
			body.elmercado-child-theme ul.products li.product a.added_to_cart.wc-forward { display:none !important; }
			@media (max-width:767px) {
				body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add {
					top:-1px !important;right:-1px !important;
					width:58px !important;min-width:58px !important;max-width:58px !important;
					height:53px !important;min-height:53px !important;max-height:53px !important;
					padding:0 !important;
				}
			}
			.emo-card-quick-add__announcer {
				position:absolute !important;width:1px !important;height:1px !important;
				padding:0 !important;margin:-1px !important;border:0 !important;
				overflow:hidden !important;clip:rect(0,0,0,0) !important;white-space:nowrap !important;
			}
			body.emo-quick-cart-open { overflow:hidden !important; }
			/* La ventana es una interfaz de compra, no una ficha WooCommerce.
			   Aislar estilos agresivos de tablas, títulos y widgets del tema. */
			#emo-quick-cart-modal, #emo-quick-cart-modal * { box-sizing:border-box; }
			#emo-quick-cart-modal .emo-quick-cart-head {
				padding:18px 20px 16px !important;
				background:#f8f9f5 !important;
				border-bottom:1px solid #e6ebe6 !important;
			}
			#emo-quick-cart-modal .emo-quick-cart-heading { min-width:0; }
			#emo-quick-cart-modal .emo-quick-cart-eyebrow {
				display:block;margin:0 0 6px;font-size:10px;font-weight:800;
				line-height:1.15;letter-spacing:.11em;text-transform:uppercase;color:#687b70;
			}
			#emo-quick-cart-modal .emo-quick-cart-title {
				font-size:19px !important;line-height:1.25 !important;
				font-weight:750 !important;letter-spacing:-.015em !important;
			}
			#emo-quick-cart-modal .emo-quick-cart-close:hover { background:#e5ece5 !important; }
			#emo-quick-cart-modal .emo-quick-cart-body { padding:18px 22px 20px !important; }
			#emo-quick-cart-modal form.variations_form.cart {
				display:block !important;
				padding:0 !important;margin:0 !important;border:0 !important;
				background:transparent !important;box-shadow:none !important;
			}
			#emo-quick-cart-modal form.variations_form table.variations {
				display:block !important;width:100% !important;
				margin:0 0 8px !important;padding:0 !important;
				border:0 !important;box-shadow:none !important;
				border-collapse:collapse !important;background:transparent !important;
			}
			#emo-quick-cart-modal form.variations_form table.variations tbody,
			#emo-quick-cart-modal form.variations_form table.variations tr {
				display:block !important;width:100% !important;height:auto !important;
				padding:0 !important;margin:0 0 10px !important;
				background:transparent !important;border:0 !important;
			}
			#emo-quick-cart-modal form.variations_form table.variations th,
			#emo-quick-cart-modal form.variations_form table.variations td {
				display:block !important;width:100% !important;float:none !important;
				height:auto !important;box-shadow:none !important;background:none !important;
				padding:0 !important;margin:0 !important;border:0 !important;
				text-align:left !important;vertical-align:middle !important;
			}
			#emo-quick-cart-modal form.variations_form table.variations th.label {
				margin:0 0 7px !important;
				color:#244637 !important;
			}
			#emo-quick-cart-modal form.variations_form table.variations label,
			#emo-quick-cart-modal .yith-wapo-addon .label-container label {
				display:inline-block !important;
				font-size:13px !important;font-weight:750 !important;
				line-height:1.35 !important;color:#294c3b !important;
				margin:0 !important;padding:0 !important;border:0 !important;
			}
			#emo-quick-cart-modal form.variations_form table.variations select,
			#emo-quick-cart-modal .yith-wapo-addon select {
				display:block !important;width:100% !important;max-width:100% !important;
				height:43px !important;min-height:43px !important;
				padding:0 12px !important;margin:0 !important;
				border:1px solid #d5dfd8 !important;border-radius:9px !important;
				background:#fff !important;color:#203f32 !important;
				box-shadow:none !important;font-size:14px !important;line-height:1.25 !important;
			}
			#emo-quick-cart-modal form.variations_form select:focus-visible {
				outline:2px solid #67947d !important;outline-offset:2px !important;
			}
			#emo-quick-cart-modal .reset_variations {
				display:inline-block !important;margin:5px 0 0 !important;
				padding:0 !important;color:#577366 !important;
				font-size:11px !important;line-height:1.35 !important;
				text-decoration:underline !important;
			}
			#emo-quick-cart-modal .single_variation_wrap {
				margin:0 !important;padding:0 !important;clear:both !important;
			}
			#emo-quick-cart-modal .woocommerce-variation.single_variation {
				padding:0 !important;margin:0 !important;border:0 !important;
			}
			#emo-quick-cart-modal .yith-wapo-container,
			#emo-quick-cart-modal .yith-wapo-block {
				margin:0 !important;padding:0 !important;
				border:0 !important;box-shadow:none !important;
				background:transparent !important;min-height:0 !important;
			}
			#emo-quick-cart-modal .yith-wapo-addon {
				padding:0 !important;margin:0 0 11px !important;
				border:0 !important;box-shadow:none !important;
				background:transparent !important;min-height:0 !important;
			}
			#emo-quick-cart-modal .yith-wapo-addon .label-container {
				margin:0 0 7px !important;padding:0 !important;border:0 !important;
				background:none !important;box-shadow:none !important;
			}
			#emo-quick-cart-modal .yith-wapo-addon .options {
				margin:0 !important;padding:0 !important;border:0 !important;
				background:none !important;
			}
			
			/* YITH WAPO calcula el precio final con corte/deshuesado. Evitar
			   duplicar el precio WooCommerce: mostrar UN solo total, el de
			   YITH, únicamente después de elegir el peso. Sin add-ons,
			   WooCommerce sigue mostrando su precio habitual. */
			#emo-quick-cart-modal.emo-quick-cart-yith .woocommerce-variation-price {
				display:none !important;
			}
			#emo-quick-cart-modal.emo-quick-cart-yith:not(.emo-quick-cart-priced) #wapo-total-price-table {
				display:none !important;
			}
			#emo-quick-cart-modal #wapo-total-price-table {
				display:block !important;width:100% !important;
				flex:1 1 100% !important;order:2 !important;
				margin:10px 0 0 !important;padding:0 !important;border:0 !important;
				background:transparent !important;box-shadow:none !important;
			}
			#emo-quick-cart-modal #wapo-total-price-table table.only_final,
			#emo-quick-cart-modal #wapo-total-price-table tbody {
				display:block !important;width:100% !important;
				margin:0 !important;padding:0 !important;
				border:0 !important;background:transparent !important;
				box-shadow:none !important;
			}
			#emo-quick-cart-modal #wapo-total-price-table .wapo-total-order {
				display:flex !important;justify-content:space-between !important;
				align-items:center !important;gap:12px !important;
				width:100% !important;
				padding:10px 12px !important;margin:0 !important;
				border:0 !important;border-radius:10px !important;
				background:#eef4ef !important;
			}
			#emo-quick-cart-modal #wapo-total-price-table .wapo-total-order > th,
			#emo-quick-cart-modal #wapo-total-price-table .wapo-total-order > td {
				display:block !important;width:auto !important;
				margin:0 !important;padding:0 !important;
				background:none !important;border:0 !important;
				color:#244736 !important;box-shadow:none !important;
			}
			#emo-quick-cart-modal #wapo-total-price-table .wapo-total-order > th {
				font-size:12px !important;font-weight:650 !important;
				white-space:nowrap !important;
			}
			#emo-quick-cart-modal #wapo-total-price-table .wapo-total-order > td {
				font-size:19px !important;font-weight:800 !important;
				text-align:right !important;white-space:nowrap !important;
			}
			#emo-quick-cart-modal #wapo-total-price-table .wapo-product-price,
			#emo-quick-cart-modal #wapo-total-price-table .wapo-total-options {
				display:none !important;
			}
			#emo-quick-cart-modal .yith-wapo-container {
				flex:1 1 100% !important;width:100% !important;
				order:0 !important;display:block !important;
			}
			#emo-quick-cart-modal .yith-wapo-addon h6.wapo-addon-title {
				display:block !important;
				margin:0 0 7px !important;padding:0 !important;
				border:0 !important;background:none !important;
				font-size:13px !important;font-weight:750 !important;
				line-height:1.3 !important;color:#294c3b !important;
				letter-spacing:.02em !important;box-shadow:none !important;
			}
			#emo-quick-cart-modal .yith-wapo-addon .addon-header,
			#emo-quick-cart-modal .yith-wapo-addon .options-container {
				margin:0 !important;padding:0 !important;
				border:0 !important;background:none !important;box-shadow:none !important;
			}
			#emo-quick-cart-modal .yith-wapo-addon .option-description:empty {display:none !important;}
			/* YITH asigna anchuras de ~55% al contenedor del desplegable;
			   en el popup, Tamaño y Formato deben ocupar la misma columna. */
			#emo-quick-cart-modal .yith-wapo-addon-type-select,
			#emo-quick-cart-modal .yith-wapo-addon-type-select .options-container,
			#emo-quick-cart-modal .yith-wapo-addon-type-select .options,
			#emo-quick-cart-modal .yith-wapo-addon-type-select .options select {
				width:100% !important;max-width:100% !important;min-width:0 !important;
				float:none !important;
			}
			#emo-quick-cart-modal table.variations .reset_variations::before,
			#emo-quick-cart-modal table.variations .reset_variations::after {
				content:none !important;display:none !important;
			}


			/* La acción contiene también las opciones YITH. La línea superior
			   antes de todas las opciones creaba un hueco de 30px; separar
			   exclusivamente cantidad y botón mediante un margen propio. */
			#emo-quick-cart-modal table.variations td.value { position:relative !important; }
			#emo-quick-cart-modal table.variations .reset_variations {
				position:absolute !important;
				top:-25px !important;right:0 !important;
				margin:0 !important;padding:0 !important;
				height:auto !important;min-height:0 !important;
			}
			#emo-quick-cart-modal form.variations_form table.variations {
				margin-bottom:4px !important;
			}
			#emo-quick-cart-modal form.variations_form table.variations tr {
				margin-bottom:5px !important;
			}
#emo-quick-cart-modal .woocommerce-variation-add-to-cart {
				display:flex !important;align-items:center !important;
				gap:10px !important;margin:0 !important;
				padding:0 !important;border-top:0 !important;
				flex-wrap:wrap !important;
			}
			#emo-quick-cart-modal .woocommerce-variation-add-to-cart .quantity {
				flex:0 0 84px !important;max-width:84px !important;
				min-width:0 !important;height:43px !important;min-height:43px !important;
				margin:0 !important;padding:0 !important;
				border:1px solid #d7dfd9 !important;border-radius:9px !important;
				box-shadow:none !important;overflow:hidden !important;
			}
			#emo-quick-cart-modal .woocommerce-variation-add-to-cart .qty {
				width:100% !important;min-height:41px !important;height:41px !important;
				padding:0 4px !important;margin:0 !important;border:0 !important;
				text-align:center !important;background:transparent !important;
				box-shadow:none !important;
			}
			#emo-quick-cart-modal .woocommerce-variation-add-to-cart .single_add_to_cart_button {
				flex:1 1 180px !important;min-width:0 !important;
				width:auto !important;height:43px !important;min-height:43px !important;
				margin:0 !important;padding:0 15px !important;
				border:1px solid #173f32 !important;border-radius:9px !important;
				box-shadow:none !important;
			}
			#emo-quick-cart-modal .emo-quick-cart-view-product {
				display:block !important;width:max-content !important;max-width:100% !important;
				margin:14px auto 0 !important;padding:0 !important;
				font-size:12px !important;font-weight:550 !important;
				color:#597467 !important;text-decoration:underline !important;
				text-underline-offset:3px !important;
			}
			@media(max-width:600px) {
				#emo-quick-cart-modal .emo-quick-cart-head { padding:15px 16px 13px !important; }
				#emo-quick-cart-modal .emo-quick-cart-body { padding:16px !important; }
				#emo-quick-cart-modal .emo-quick-cart-title { font-size:17px !important; }
			}

			#emo-quick-cart-modal {
				position:fixed;inset:0;z-index:2147483000;
				display:flex;align-items:center;justify-content:center;
				padding:18px;background:rgba(13,35,28,.62);
			}
			#emo-quick-cart-modal[hidden] { display:none !important; }
			#emo-quick-cart-modal .emo-quick-cart-dialog {
				box-sizing:border-box;width:min(510px,100%);max-height:min(85vh,750px);
				display:flex;flex-direction:column;overflow:hidden;
				background:#fff;border-radius:17px;box-shadow:0 17px 55px rgba(0,0,0,.25);
				color:#173f32;
			}
			#emo-quick-cart-modal .emo-quick-cart-head {
				display:flex;justify-content:space-between;align-items:center;
				padding:17px 20px;border-bottom:1px solid #e4eae6;gap:16px;
			}
			#emo-quick-cart-modal .emo-quick-cart-title { margin:0;font-size:18px;line-height:1.35;font-weight:750; }
			#emo-quick-cart-modal .emo-quick-cart-close {
				display:grid;place-items:center;flex:0 0 36px;
				width:36px;height:36px;min-width:36px;padding:0;
				background:#f4f6f4;border:1px solid #dce5de;border-radius:50%;
				color:#173f32;font-size:24px;line-height:1;cursor:pointer;
			}
			#emo-quick-cart-modal .emo-quick-cart-body { overflow-y:auto;overscroll-behavior:contain;padding:20px; }
			#emo-quick-cart-modal .emo-quick-cart-error { color:#9b2226;font-size:13px;line-height:1.5; }
			#emo-quick-cart-modal .emo-quick-cart-error:empty { display:none; }
			#emo-quick-cart-modal .variations_form { display:block !important; }
			#emo-quick-cart-modal .variations { width:100% !important;margin:0 0 12px !important; }
			#emo-quick-cart-modal .variations tbody,#emo-quick-cart-modal .variations tr {
				display:block !important;width:100% !important;
			}
			#emo-quick-cart-modal .variations tr { margin-bottom:12px !important; }
			#emo-quick-cart-modal .variations th,#emo-quick-cart-modal .variations td {
				display:block !important;width:100% !important;text-align:left !important;padding:3px 0 !important;
			}
			#emo-quick-cart-modal .variations select { width:100% !important;max-width:100% !important;min-height:43px !important; }
			#emo-quick-cart-modal .quantity { display:inline-flex !important;max-width:100px !important; }
			#emo-quick-cart-modal .single_add_to_cart_button {
				display:inline-flex !important;justify-content:center !important;align-items:center !important;
				min-height:43px !important;margin:8px 0 0 !important;padding:10px 18px !important;
				background:#173f32 !important;color:#fff !important;border-radius:9px !important;
				cursor:pointer !important;font-weight:700 !important;
			}
			#emo-quick-cart-modal .single_add_to_cart_button:disabled { opacity:.55 !important;cursor:not-allowed !important; }
			#emo-quick-cart-modal .woocommerce-variation-price { margin:9px 0 !important;font-weight:700; }
			#emo-quick-cart-modal .emo-quick-cart-view-product {
				display:inline-block;margin:12px 0 0;font-size:13px;color:#173f32;text-decoration:underline;
			}
			@media (max-width:600px) {
				#emo-quick-cart-modal { padding:10px;align-items:flex-end; }
				#emo-quick-cart-modal .emo-quick-cart-dialog { max-height:90dvh;width:100%;border-radius:15px 15px 10px 10px; }
				#emo-quick-cart-modal .emo-quick-cart-body { padding:16px; }
			}
			@media (prefers-reduced-motion:reduce) {
				body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add { transition:none !important; }
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( ! elmercado_is_quick_add_catalog_page_20261026() ) {
			return;
		}
		?>
		<script id="emo-catalog-quick-add-js-20261026">
		(() => {
			'use strict';
			const body = document.body;
			if (!body.matches('.woocommerce-shop,.post-type-archive-product,.tax-product_cat,.tax-product_tag,.wcfmmp-store-page')) return;

			const config = <?php echo wp_json_encode( array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'emo_quick_cart_20261026' ),
			) ); ?>;
			const sourceSelector = [
				'a.emo-card-quick-add-source', 'a.add_to_cart_button',
				'a.product_type_variable.button', 'a.product_type_grouped.button',
				'a.product_type_external.button'
			].join(',');
			const cartSvg = '<svg viewBox="0 0 24 24" width="20" height="20" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M2 3h2l2.4 11.2a2 2 0 0 0 2 1.6h10a2 2 0 0 0 2-1.6L22 7H5"/></svg>';
			const checkSvg = '<svg viewBox="0 0 24 24" width="20" height="20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m5 12 4 4L19 6"/></svg>';

			const cardType = (card) => {
				const className = card.className || '';
				if (/product-type-variable/.test(className)) return 'variable';
				if (/product-type-simple/.test(className)) return 'simple';
				return '';
			};

			/* Segunda vía para productos de paginaciones cacheadas o HTML dinámico
			   que no incluye los hooks de WooCommerce. Nunca ofrecer compra de
			   productos agotados, ocultos o sin precio. */
			const fallbackControl = (card) => {
				const type = cardType(card);
				const id = (card.className.match(/(?:^|\s)post-(\d+)(?:\s|$)/) || [])[1];
				if (!id || !type || !card.classList.contains('instock') || card.classList.contains('outofstock')) return null;
				if (!card.querySelector('.price')) return null;

				const link = card.querySelector('a.woocommerce-LoopProduct-link[href],a[href*="/producto/"]');
				if (!link) return null;
				const control = document.createElement('a');
				const simple = type === 'simple';
				control.className = 'button emo-card-quick-add-source ' + (simple
					? 'product_type_simple add_to_cart_button ajax_add_to_cart'
					: 'product_type_variable');
				control.href = simple ? '?add-to-cart=' + encodeURIComponent(id) : link.href;
				control.dataset.product_id = id;
				control.dataset.quantity = '1';
				control.textContent = simple ? 'Añadir al carrito' : 'Seleccionar opciones';
				card.append(control);
				return control;
			};

			const enhanceCard = (card) => {
				if (card.querySelector(':scope > a.emo-card-quick-add')) return;

				const controls = [...card.querySelectorAll(sourceSelector)]
					.filter(anchor => !anchor.classList.contains('emo-card-quick-add')
						&& !anchor.matches('.added_to_cart,.wc-forward') && anchor.getAttribute('href'));
				const control = controls.find(anchor => anchor.classList.contains('emo-card-quick-add-source'))
					|| controls.find(anchor => anchor.classList.contains('add_to_cart_button'))
					|| controls[0] || fallbackControl(card);
				if (!control) return;

				const name = (card.querySelector('.woocommerce-loop-product__title,.product-title,h2,h3')?.textContent || '').trim();
				const options = control.matches('.product_type_variable,.product_type_grouped,.product_type_external');
				const label = options ? 'Seleccionar opciones' : 'Añadir al carrito';
				control.setAttribute('aria-label', name ? label + ': ' + name : label);
				control.setAttribute('title', label);
				control.classList.add('emo-card-quick-add');
				/* Woostify añade margin:14px !important a los enlaces button del loop.
				   Un estilo inline important cancela ese margen y alinea el icono
				   con la pastilla «Selección EMDO» en todos los tamaños. */
				control.style.setProperty('margin', '0px', 'important');
				control.classList.remove('button'); // La clase genérica impone min-height:46px en Woostify.
				control.style.setProperty('height', '53px', 'important');
				control.style.setProperty('min-height', '53px', 'important');
				control.style.setProperty('max-height', '53px', 'important');
				control.style.setProperty('padding', '0px', 'important');
				control.style.setProperty('line-height', '0px', 'important');
				control.dataset.emoQuickAction = options ? 'options' : 'add';
				if (!options && control.matches('.product_type_simple') && control.dataset.product_id && window.wc_add_to_cart_params) {
					control.classList.add('ajax_add_to_cart');
				}
				const symbol = document.createElement('span');
				symbol.className = 'emo-card-quick-add__symbol';
				symbol.setAttribute('aria-hidden','true');
				symbol.innerHTML = cartSvg;
				control.prepend(symbol);
				/* Mover el nodo real: no crear dos add_to_cart_button con un mismo ID. */
				card.append(control);
			};

			const enhance = () => document.querySelectorAll('ul.products li.product').forEach(enhanceCard);
			let scheduled = false;
			const scheduleEnhance = () => {
				if (scheduled) return;
				scheduled = true;
				requestAnimationFrame(() => {
					scheduled = false;
					enhance();
				});
			};

			let announcement;
			const announce = (message) => {
				if (!announcement) return;
				announcement.textContent = '';
				requestAnimationFrame(() => { announcement.textContent = message; });
			};
			const markAdded = (control) => {
				if (!control?.classList?.contains('emo-card-quick-add')) return;
				const symbol = control.querySelector('.emo-card-quick-add__symbol');
				if (!symbol) return;
				symbol.innerHTML = checkSvg;
				control.classList.add('emo-card-quick-add--confirmed');
				announce('Producto añadido al carrito');
				window.setTimeout(() => {
					if (!control.isConnected) return;
					symbol.innerHTML = cartSvg;
					control.classList.remove('emo-card-quick-add--confirmed');
				}, 1500);
			};

			let modal = null;
			let opener = null;
			let currentProductUrl = '';
			let currentControl = null;
			let formatWidthObserver = null;

			const closeModal = () => {
				if (!modal || modal.hidden) return;
				modal.hidden = true;
				formatWidthObserver?.disconnect();
				formatWidthObserver = null;
				modal.classList.remove('emo-quick-cart-yith','emo-quick-cart-priced');
				modal.querySelector('.emo-quick-cart-body').replaceChildren();
				body.classList.remove('emo-quick-cart-open');
				if (opener?.isConnected) opener.focus({ preventScroll: true });
				opener = null;
				currentControl = null;
			};

			const makeModal = () => {
				if (modal) return modal;
				modal = document.createElement('div');
				modal.id = 'emo-quick-cart-modal';
				modal.hidden = true;
				modal.innerHTML = '<section class="emo-quick-cart-dialog" role="dialog" aria-modal="true" aria-labelledby="emo-quick-cart-title">'
					+ '<header class="emo-quick-cart-head"><div class="emo-quick-cart-heading"><span class="emo-quick-cart-eyebrow">Personaliza tu pedido</span><h2 id="emo-quick-cart-title" class="emo-quick-cart-title">Seleccionar opciones</h2></div>'
					+ '<button type="button" class="emo-quick-cart-close" aria-label="Cerrar">×</button></header>'
					+ '<div class="emo-quick-cart-body"></div></section>';
				body.append(modal);
				modal.querySelector('.emo-quick-cart-close').addEventListener('click', closeModal);
				modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
				modal.addEventListener('submit', submitVariation, true);
				/* Los add-ons (p. ej., YITH WAPO) escuchan el click del botón
				   y pueden bloquear el submit del formulario inyectado.
				   Aquí capturamos únicamente el botón del modal, antes de
				   handlers de terceros, y aplicamos validación en WC_Cart. */
				modal.addEventListener('click', event => {
					const button = event.target.closest?.('.single_add_to_cart_button');
					if (!button || !modal.contains(button)) return;
					event.preventDefault();
					event.stopImmediatePropagation();
					const form = button.closest('form.variations_form') || modal.querySelector('form.variations_form');
					if (!form) return;
					submitVariation({
						target: form,
						preventDefault() {},
						stopPropagation() {}
					});
				}, true);
				document.addEventListener('keydown', (event) => {
					if (modal.hidden) return;
					if (event.key === 'Escape') { event.preventDefault(); closeModal(); return; }
					if (event.key !== 'Tab') return;
					const focusables = [...modal.querySelectorAll('button:not([disabled]),a[href],select:not([disabled]),input:not([disabled]),textarea:not([disabled])')]
						.filter(el => getComputedStyle(el).display !== 'none' && el.getClientRects().length > 0);
					if (!focusables.length) return;
					if (event.shiftKey && document.activeElement === focusables[0]) {
						event.preventDefault(); focusables[focusables.length - 1].focus();
					} else if (!event.shiftKey && document.activeElement === focusables[focusables.length - 1]) {
						event.preventDefault(); focusables[0].focus();
					}
				});
				return modal;
			};

			const showError = (message) => {
				const target = modal?.querySelector('.emo-quick-cart-error');
				if (target) { target.textContent = message; target.setAttribute('role','alert'); }
			};
			const productLink = () => {
				const link = document.createElement('a');
				link.className = 'emo-quick-cart-view-product';
				link.href = currentProductUrl;
				link.textContent = 'Ver ficha completa del producto';
				return link;
			};
			const ajax = async (formData) => {
				const response = await fetch(config.ajaxUrl, { method:'POST', body:formData, credentials:'same-origin', headers:{'Accept':'application/json'} });
				const result = await response.json();
				if (!response.ok || result.success === false) {
					throw new Error(result.data?.message || 'No se ha podido completar la operación.');
				}
				return result;
			};

			const openVariations = async (control) => {
				const id = control.dataset.product_id;
				if (!id) { window.location.href = control.href; return; }
				const dialog = makeModal();
				opener = control;
				currentControl = control;
				currentProductUrl = control.href;
				const title = dialog.querySelector('.emo-quick-cart-title');
				const content = dialog.querySelector('.emo-quick-cart-body');
				title.textContent = 'Seleccionar opciones';
				content.textContent = 'Cargando opciones…';
				dialog.hidden = false;
				body.classList.add('emo-quick-cart-open');
				dialog.querySelector('.emo-quick-cart-close').focus();
				const payload = new FormData();
				payload.set('action', 'emo_quick_variations_20261026');
				payload.set('nonce', config.nonce);
				payload.set('product_id', id);
				try {
					const answer = await ajax(payload);
					if (dialog.hidden || currentControl !== control) return;
					title.textContent = answer.data.title || 'Seleccionar opciones';
					content.innerHTML = answer.data.html;
					const error = document.createElement('div');
					error.className = 'emo-quick-cart-error';
					content.prepend(error);
					content.append(productLink());

					const form = content.querySelector('form.variations_form');
					if (form && window.jQuery?.fn?.wc_variation_form) {
						/* YITH inyecta width:75% !important inline tanto en .options
						   como en el select. Un stylesheet no puede prevalecer:
						   normalizar solo los dos nodos dentro de ESTE modal. */
						const alignFormatFields = () => {
							if (dialog.hidden) return;
							dialog.querySelectorAll('.yith-wapo-addon-type-select .options, .yith-wapo-addon-type-select select.yith-wapo-option-value').forEach(field => {
								if (field.style.getPropertyValue('width') !== '100%' || field.style.getPropertyPriority('width') !== 'important') {
									field.style.setProperty('width', '100%', 'important');
								}
								if (field.style.getPropertyValue('max-width') !== '100%' || field.style.getPropertyPriority('max-width') !== 'important') {
									field.style.setProperty('max-width', '100%', 'important');
								}
							});
						};

						const hasYithTotal = Boolean(form.querySelector('#wapo-total-price-table .wapo-total-order'));
						dialog.classList.toggle('emo-quick-cart-yith', hasYithTotal);
						dialog.classList.remove('emo-quick-cart-priced');
						if (hasYithTotal) {
							window.jQuery(form)
								.on('found_variation.emoQuickCart', () => dialog.classList.add('emo-quick-cart-priced'))
								.on('reset_data.emoQuickCart hide_variation.emoQuickCart', () => dialog.classList.remove('emo-quick-cart-priced'));
						}
						window.jQuery(form).wc_variation_form();
						alignFormatFields();
						window.setTimeout(alignFormatFields, 180);
						window.setTimeout(alignFormatFields, 700);
						window.setTimeout(alignFormatFields, 1350);
						/* En móvil YITH reemplaza controles DOM completos tras renderizar.
						   Observar el modal (no el nodo form que puede sustituirse);
						   la función evita escrituras repetidas y no hay bucles. */
						formatWidthObserver?.disconnect();
						formatWidthObserver = new MutationObserver(mutations => {
							if (dialog.hidden) return;
							if (mutations.some(m => m.type === 'childList'
								|| m.target.matches?.('.yith-wapo-addon-type-select .options, .yith-wapo-addon-type-select select.yith-wapo-option-value'))) {
								alignFormatFields();
							}
						});
						formatWidthObserver.observe(dialog, { attributes: true, attributeFilter: ['style'], childList: true, subtree: true });
						alignFormatFields();
						window.jQuery(form).find('.variations select').trigger('change');
					} else if (form) {
						showError('No se ha podido iniciar el selector. Abre la ficha del producto.');
						form.querySelectorAll('button[type="submit"],.single_add_to_cart_button').forEach(btn => btn.disabled = true);
					} else {
						showError('Las opciones de este producto requieren abrir su ficha.');
					}
				} catch (error) {
					if (dialog.hidden || currentControl !== control) return;
					content.replaceChildren();
					const notice = document.createElement('p');
					notice.className = 'emo-quick-cart-error';
					notice.setAttribute('role','alert');
					notice.textContent = error.message;
					content.append(notice, productLink());
				}
			};

			async function submitVariation(event) {
				const form = event.target;
				if (!form.matches('form.variations_form')) return;
				event.preventDefault();
				event.stopPropagation();
				const variation = Number(form.querySelector('input[name="variation_id"]')?.value || 0);
				if (!variation) {
					showError('Selecciona todas las opciones para poder añadir este producto.');
					return;
				}
				const button = form.querySelector('.single_add_to_cart_button');
				if (button?.disabled) return;
				if (button) button.disabled = true;
				showError('');
				const payload = new FormData(form);
				payload.set('action', 'emo_quick_add_variation_20261026');
				payload.set('nonce', config.nonce);
				payload.set('product_id', form.dataset.product_id || currentControl?.dataset.product_id || '');
				payload.set('variation_id', String(variation));
				try {
					const response = await ajax(payload);
					if (!modal || modal.hidden) return;
					const buttonControl = currentControl;
					/* Evento de WooCommerce: actualiza icono, fragmentos y mini-cart. */
					if (window.jQuery) {
						window.jQuery(document.body).trigger('added_to_cart', [
							response.fragments || {}, response.cart_hash || '', window.jQuery(buttonControl)
						]);
					}
					markAdded(buttonControl);
					closeModal();
				} catch (error) {
					showError(error.message);
				} finally {
					if (button && button.isConnected) button.disabled = false;
				}
			}

			const start = () => {
				announcement = document.createElement('span');
				announcement.className = 'emo-card-quick-add__announcer';
				announcement.setAttribute('role','status');
				announcement.setAttribute('aria-live','polite');
				body.append(announcement);

				enhance();
				new MutationObserver(mutations => {
					/* También se ejecuta al añadir tarjetas desde un DocumentFragment,
					   reemplazar resultados por filtros o cargar el siguiente lote. */
					const found = mutations.some(m => [...m.addedNodes].some(node =>
						node.nodeType === 1 && (
							node.matches?.('li.product,ul.products,.products')
							|| node.querySelector?.('li.product,a.emo-card-quick-add-source')
						)
					));
					if (found) scheduleEnhance();
				}).observe(document.body, { childList:true, subtree:true });
				document.addEventListener('emo:catalog:products-added', scheduleEnhance);

				document.addEventListener('click', event => {
					const control = event.target.closest?.('a.emo-card-quick-add[data-emo-quick-action="options"]');
					if (!control || !document.body.contains(control)) return;
					if (!control.matches('.product_type_variable')) return; // grouped/external conservan su ficha
					event.preventDefault();
					event.stopPropagation();
					openVariations(control);
				}, true);

				if (window.jQuery) {
					window.jQuery(document.body).on('added_to_cart', (_event, _fragments, _hash, $button) => {
						const control = $button?.[0];
						if (control && control.dataset.emoQuickAction !== 'options') markAdded(control);
					});
				}
				/* Carga incremental: los elementos se introducen en el documento
				   después del HTML inicial. El observer los procesa sin reiniciar. */
				scheduleEnhance();
			};
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once:true });
			else start();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
