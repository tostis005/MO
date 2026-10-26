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
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add {
				box-sizing:border-box !important;
				position:absolute !important;
				top:10px !important;
				right:10px !important;
				left:auto !important;
				bottom:auto !important;
				z-index:12 !important;
				display:flex !important;
				align-items:center !important;
				justify-content:center !important;
				width:36px !important;
				min-width:36px !important;
				max-width:36px !important;
				height:36px !important;
				min-height:36px !important;
				max-height:36px !important;
				padding:0 !important;
				margin:0 !important;
				gap:0 !important;
				border:1px solid rgba(23,63,50,.16) !important;
				border-radius:50% !important;
				background:rgba(255,255,255,.96) !important;
				color:#173f32 !important;
				box-shadow:0 2px 9px rgba(16,43,34,.12) !important;
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
				width:20px !important;
				height:20px !important;
				margin:0 !important;
				color:inherit !important;
				font-size:0 !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add .emo-card-quick-add__symbol svg {
				display:block !important;
				width:19px !important;
				height:19px !important;
				stroke:currentColor !important;
				fill:none !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add:is(:hover,:focus-visible,.emo-card-quick-add--confirmed) {
				background:#173f32 !important;
				border-color:#173f32 !important;
				color:white !important;
			}
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add:focus-visible { outline:3px solid #ecc66f !important; outline-offset:2px !important; }
			body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add.loading { pointer-events:none !important; opacity:.62 !important; }
			body.elmercado-child-theme ul.products li.product a.added_to_cart.wc-forward { display:none !important; }
			@media (max-width:767px) {
				body.elmercado-child-theme ul.products li.product > a.emo-card-quick-add {
					top:7px !important;right:7px !important;
					width:36px !important;min-width:36px !important;max-width:36px !important;
					height:36px !important;min-height:36px !important;max-height:36px !important;
					padding:0 !important;
				}
			}
			.emo-card-quick-add__announcer {
				position:absolute !important;width:1px !important;height:1px !important;
				padding:0 !important;margin:-1px !important;border:0 !important;
				overflow:hidden !important;clip:rect(0,0,0,0) !important;white-space:nowrap !important;
			}
			body.emo-quick-cart-open { overflow:hidden !important; }
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
			if (!document.body.matches('.woocommerce-shop,.post-type-archive-product,.tax-product_cat,.tax-product_tag,.wcfmmp-store-page')) return;

			const sourceSelector = [
				'a.add_to_cart_button',
				'a.product_type_variable.button',
				'a.product_type_grouped.button',
				'a.product_type_external.button'
			].join(',');

			const enhanceCard = (card) => {
				if (card.querySelector(':scope > a.emo-card-quick-add')) return;

				const controls = [...card.querySelectorAll(sourceSelector)]
					.filter((anchor) => !anchor.matches('.emo-card-quick-add,.added_to_cart,.wc-forward') && anchor.getAttribute('href'));
				const control = controls.find((anchor) => anchor.classList.contains('add_to_cart_button')) || controls[0];
				if (!control) return;

				const label = (control.getAttribute('aria-label') || control.textContent || '').replace(/\s+/g, ' ').trim();
				if (!label) return;
				const productName = (card.querySelector('.woocommerce-loop-product__title,.product-title,h2,h3')?.textContent || '').replace(/\s+/g, ' ').trim();
				if (!control.getAttribute('aria-label')) {
					control.setAttribute('aria-label', productName ? label + ': ' + productName : label);
				}

				const options = control.matches('.product_type_variable,.product_type_grouped,.product_type_external');
				/* Producto simple + ID nativo: WooCommerce gestiona el AJAX y el stock. */
				if (!options && control.matches('.product_type_simple.add_to_cart_button') &&
					control.dataset.product_id && window.wc_add_to_cart_params) {
					control.classList.add('ajax_add_to_cart');
				}

				control.classList.add('emo-card-quick-add');
				control.dataset.emoQuickAction = options ? 'options' : 'add';
				control.setAttribute('title', control.getAttribute('aria-label'));

				const symbol = document.createElement('span');
				symbol.className = 'emo-card-quick-add__symbol';
				symbol.setAttribute('aria-hidden', 'true');
				symbol.textContent = options ? '›' : '+';
				control.prepend(symbol);

				/* Mover, no clonar: conserva el enlace nativo y evita compras duplicadas.
				   Fuera de la imagen enlazada, el clic no abre la ficha accidentalmente. */
				card.append(control);
			};

			const enhance = () => {
				document.querySelectorAll('ul.products li.product').forEach(enhanceCard);
			};

			const start = () => {
				enhance();
				let scheduled = false;
				const schedule = () => {
					if (scheduled) return;
					scheduled = true;
					requestAnimationFrame(() => {
						scheduled = false;
						enhance();
					});
				};

				/* La carga continua y los filtros insertan tarjetas sin recargar la página. */
				new MutationObserver((mutations) => {
					const addedProducts = mutations.some((mutation) =>
						[...mutation.addedNodes].some((node) =>
							node.nodeType === 1 &&
							(node.matches?.('li.product,ul.products') || node.querySelector?.('li.product'))
						)
					);
					if (addedProducts) schedule();
				}).observe(document.body, { childList: true, subtree: true });

				const announce = document.createElement('span');
				announce.className = 'emo-card-quick-add__announcer';
				announce.setAttribute('role', 'status');
				announce.setAttribute('aria-live', 'polite');
				document.body.append(announce);

				if (window.jQuery) {
					window.jQuery(document.body).on('added_to_cart', (_event, _fragments, _hash, $button) => {
						const control = $button?.[0];
						if (!control?.classList?.contains('emo-card-quick-add')) return;
						const symbol = control.querySelector('.emo-card-quick-add__symbol');
						if (!symbol) return;
						symbol.textContent = '✓';
						control.classList.add('emo-card-quick-add--confirmed');
						announce.textContent = document.documentElement.lang.startsWith('en')
							? 'Added to cart' : 'Producto añadido al carrito';
						window.setTimeout(() => {
							if (!control.isConnected) return;
							symbol.textContent = '+';
							control.classList.remove('emo-card-quick-add--confirmed');
						}, 1600);
					});
				}
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', start, { once: true });
			} else {
				start();
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
