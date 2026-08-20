<?php
/**
 * Plugin Name: MDO Catalog Toolbar Layout
 * Description: Keeps the exact result count, canonical shipping destination and WooCommerce ordering together inside Woostify's native white toolbar.
 * Version: 1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_summarybar_is_surface_20260820(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'elmercado_core_filters_is_catalog' ) && elmercado_core_filters_is_catalog() ) {
		return true;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		return true;
	}
	return is_search() && 'product' === get_query_var( 'post_type' );
}

function mdo_catalog_toolbar_text_20260820( string $es, string $en ): string {
	if ( function_exists( 'mdo_catalog_default_spain_text_20260820' ) ) {
		return mdo_catalog_default_spain_text_20260820( $es, $en );
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return ( '/en' === $path || 0 === strpos( $path, '/en/' ) ) ? $en : $es;
}

function mdo_catalog_toolbar_destination_20260820(): array {
	if ( function_exists( 'mdo_catalog_default_spain_destination_20260820' ) ) {
		return (array) mdo_catalog_default_spain_destination_20260820();
	}
	if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		return (array) MDO_Catalog_Destination_Frontend::current_destination();
	}
	return array( 'country' => 'ES', 'postcode' => '' );
}

function mdo_catalog_toolbar_countries_20260820(): array {
	if ( function_exists( 'mdo_catalog_default_spain_countries_20260820' ) ) {
		return (array) mdo_catalog_default_spain_countries_20260820();
	}
	if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		$countries = (array) MDO_Catalog_Destination_Frontend::supported_countries();
		if ( $countries ) {
			return $countries;
		}
	}
	return array( 'ES' => mdo_catalog_toolbar_text_20260820( 'España', 'Spain' ) );
}

/**
 * Only the trigger belongs inside Woostify's toolbar. The modal is deliberately
 * rendered at wp_footer level so no catalogue stacking context can cover it.
 */
function mdo_catalog_toolbar_render_destination_control_20260820(): void {
	if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
		return;
	}

	$destination = mdo_catalog_toolbar_destination_20260820();
	$countries   = mdo_catalog_toolbar_countries_20260820();
	$country     = strtoupper( (string) ( $destination['country'] ?? 'ES' ) );
	$postcode    = trim( (string) ( $destination['postcode'] ?? '' ) );
	$label       = (string) ( $countries[ $country ] ?? $country );
	if ( 'ES' === $country && '' !== $postcode ) {
		$label .= ' · ' . $postcode;
	}
	?>
	<div class="mdo-catalog-destination mdo-catalog-destination--canonical" data-mdo-destination-control>
		<button type="button" class="mdo-catalog-destination__trigger" data-mdo-destination-open aria-haspopup="dialog" aria-controls="mdo-catalog-destination-dialog">
			<span class="mdo-catalog-destination__label"><?php echo esc_html( mdo_catalog_toolbar_text_20260820( 'Envío a', 'Shipping to' ) ); ?> <strong><?php echo esc_html( $label ); ?></strong></span>
			<span class="mdo-catalog-destination__chevron" aria-hidden="true">
				<svg viewBox="0 0 16 10" width="12" height="8" focusable="false"><path d="M2 2.2 8 8l6-5.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</span>
		</button>
	</div>
	<?php
}

function mdo_catalog_toolbar_render_destination_modal_20260820(): void {
	if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
		return;
	}

	$destination = mdo_catalog_toolbar_destination_20260820();
	$countries   = mdo_catalog_toolbar_countries_20260820();
	$country     = strtoupper( (string) ( $destination['country'] ?? 'ES' ) );
	$postcode    = trim( (string) ( $destination['postcode'] ?? '' ) );
	?>
	<div id="mdo-catalog-destination-dialog" class="mdo-destination-modal mdo-destination-modal--root" data-mdo-destination-modal hidden aria-hidden="true">
		<div class="mdo-destination-modal__backdrop" data-mdo-destination-close></div>
		<section class="mdo-destination-modal__panel" role="dialog" aria-modal="true" aria-labelledby="mdo-destination-title" tabindex="-1">
			<button type="button" class="mdo-destination-modal__close" data-mdo-destination-close aria-label="<?php echo esc_attr( mdo_catalog_toolbar_text_20260820( 'Cerrar', 'Close' ) ); ?>"><span aria-hidden="true">×</span></button>
			<h2 id="mdo-destination-title"><?php echo esc_html( mdo_catalog_toolbar_text_20260820( '¿Dónde quieres recibir tu pedido?', 'Where do you want to receive your order?' ) ); ?></h2>
			<p class="mdo-destination-modal__intro"><?php echo esc_html( mdo_catalog_toolbar_text_20260820( 'Elige un destino para ver solo los productos que pueden enviarse allí.', 'Choose a destination to see only products that can be shipped there.' ) ); ?></p>
			<form data-mdo-destination-form>
				<label for="mdo-destination-country"><?php echo esc_html( mdo_catalog_toolbar_text_20260820( 'País', 'Country' ) ); ?></label>
				<select id="mdo-destination-country" name="country" data-mdo-destination-country>
					<?php foreach ( $countries as $code => $country_label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>><?php echo esc_html( $country_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="mdo-destination-modal__postcode" data-mdo-postcode-wrap <?php echo 'ES' === $country ? '' : 'hidden'; ?>>
					<label for="mdo-destination-postcode"><?php echo esc_html( mdo_catalog_toolbar_text_20260820( 'Código postal', 'Postcode' ) ); ?> <span><?php echo esc_html( mdo_catalog_toolbar_text_20260820( '(opcional)', '(optional)' ) ); ?></span></label>
					<input id="mdo-destination-postcode" name="postcode" data-mdo-destination-postcode inputmode="numeric" autocomplete="postal-code" maxlength="5" pattern="[0-9]{5}" value="<?php echo esc_attr( $postcode ); ?>" placeholder="28001">
					<small><?php echo esc_html( mdo_catalog_toolbar_text_20260820( 'Sin código postal se muestra la tienda completa. No realizamos envíos a Ceuta ni Melilla.', 'Without a postcode the full shop is shown. We do not ship to Ceuta or Melilla.' ) ); ?></small>
				</div>
				<p class="mdo-destination-modal__error" data-mdo-destination-error role="alert" hidden></p>
				<button type="submit" class="mdo-destination-modal__save" data-mdo-destination-save><?php echo esc_html( mdo_catalog_toolbar_text_20260820( 'Aplicar destino', 'Apply destination' ) ); ?></button>
			</form>
		</section>
	</div>
	<?php
}

/**
 * Normalize the server-side hook layout after every MU plugin and the theme have
 * registered their callbacks. No browser-side relocation, cloning or observer.
 */
add_action(
	'wp',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}

		/* Keep only EMDO's exact result count. */
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

		/* Replace the historical combined control+modal renderer with a trigger only. */
		if ( function_exists( 'mdo_catalog_default_spain_render_20260820' ) ) {
			remove_action( 'woocommerce_before_shop_loop', 'mdo_catalog_default_spain_render_20260820', 22 );
			remove_action( 'wp_footer', 'mdo_catalog_default_spain_render_20260820', 5 );
		}
		remove_action( 'woocommerce_before_shop_loop', 'mdo_catalog_toolbar_render_destination_control_20260820', 22 );
		add_action( 'woocommerce_before_shop_loop', 'mdo_catalog_toolbar_render_destination_control_20260820', 22 );
	},
	1100
);

/* The modal itself is a direct body child, outside every catalogue z-index context. */
add_action( 'wp_footer', 'mdo_catalog_toolbar_render_destination_modal_20260820', 5 );

/**
 * Final geometry contract. Printed late so historical catalogue CSS cannot force
 * the old fixed mobile row or fixed ordering width back into place.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		?>
		<style id="mdo-catalog-toolbar-layout-20260820">
			body .mdo-catalog-summarybar,
			body .mdo-catalog-toolbar__count,
			body .mdo-catalog-toolbar__destination {
				display:none !important;
			}

			/* One calm white toolbar on desktop. */
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
				display:flex !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:auto !important;
				min-height:68px !important;
				max-height:none !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:18px !important;
				overflow:visible !important;
				margin:0 0 16px !important;
				padding:12px 14px !important;
				border:1px solid rgba(23,63,50,.11) !important;
				border-radius:16px !important;
				background:#fff !important;
				box-shadow:0 10px 28px rgba(17,42,34,.055) !important;
			}

			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
				display:flex !important;
				visibility:visible !important;
				opacity:1 !important;
				position:static !important;
				box-sizing:border-box !important;
				flex:1 1 auto !important;
				width:auto !important;
				min-width:0 !important;
				max-width:none !important;
				height:auto !important;
				min-height:42px !important;
				align-items:center !important;
				gap:15px !important;
				overflow:visible !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				transform:none !important;
			}

			/* Exactly one result count. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-result-count:not(.emo-catalog-result-count-010220):not(.emo-vendor-result-count-010225) {
				display:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220 {
				display:inline-flex !important;
				visibility:visible !important;
				opacity:1 !important;
				position:static !important;
				box-sizing:border-box !important;
				flex:0 0 auto !important;
				width:auto !important;
				min-width:0 !important;
				height:auto !important;
				min-height:42px !important;
				max-height:none !important;
				align-items:center !important;
				overflow:visible !important;
				margin:0 !important;
				padding:0 2px !important;
				float:none !important;
				color:#53665f !important;
				font-family:inherit !important;
				font-size:12.5px !important;
				font-weight:700 !important;
				line-height:1.35 !important;
				white-space:nowrap !important;
			}

			/* Destination pill: no leading icon, balanced text and chevron. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
				display:inline-flex !important;
				visibility:visible !important;
				opacity:1 !important;
				position:static !important;
				box-sizing:border-box !important;
				flex:0 1 auto !important;
				width:auto !important;
				min-width:0 !important;
				max-width:100% !important;
				align-items:center !important;
				margin:0 !important;
				padding:0 !important;
				float:none !important;
				transform:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) 16px !important;
				column-gap:9px !important;
				visibility:visible !important;
				opacity:1 !important;
				box-sizing:border-box !important;
				width:auto !important;
				min-width:0 !important;
				max-width:100% !important;
				height:42px !important;
				min-height:42px !important;
				max-height:42px !important;
				align-items:center !important;
				margin:0 !important;
				padding:0 13px 0 14px !important;
				border:1px solid rgba(23,63,50,.15) !important;
				border-radius:999px !important;
				background:#f8faf8 !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12.5px !important;
				font-weight:600 !important;
				line-height:1 !important;
				white-space:nowrap !important;
				cursor:pointer !important;
				transition:background-color .15s ease,border-color .15s ease,box-shadow .15s ease !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible {
				background:#f2f6f3 !important;
				border-color:rgba(23,63,50,.30) !important;
				box-shadow:0 0 0 3px rgba(23,63,50,.055) !important;
				color:#173f32 !important;
				outline:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover *,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus *,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible * {
				color:#173f32 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__label {
				display:block !important;
				min-width:0 !important;
				overflow:hidden !important;
				text-overflow:ellipsis !important;
				white-space:nowrap !important;
				line-height:1.2 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger strong {
				color:inherit !important;
				font-weight:760 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__chevron {
				dis:grid !important;
				display:grid !important;
				place-items:center !important;
				width:16px !important;
				height:16px !important;
				min-width:16px !important;
				margin:0 !important;
				padding:0 !important;
				line-height:0 !important;
				opacity:.72 !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__chevron svg {
				display:block !important;
				width:12px !important;
				height:8px !important;
				margin:0 !important;
			}

			/* Ordering has the same visual weight as the destination control. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				display:flex !important;
				visibility:visible !important;
				opacity:1 !important;
				position:static !important;
				box-sizing:border-box !important;
				flex:0 1 248px !important;
				width:clamp(190px,22vw,248px) !important;
				min-width:170px !important;
				max-width:248px !important;
				height:42px !important;
				min-height:42px !important;
				max-height:42px !important;
				align-items:center !important;
				margin:0 0 0 auto !important;
				padding:0 !important;
				float:none !important;
				clear:none !important;
				transform:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
				display:block !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:42px !important;
				min-height:42px !important;
				max-height:42px !important;
				margin:0 !important;
				padding:0 34px 0 13px !important;
				border:1px solid rgba(23,63,50,.15) !important;
				border-radius:999px !important;
				background-color:#f8faf8 !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12.5px !important;
				font-weight:700 !important;
				letter-spacing:0 !important;
				line-height:1 !important;
			}

			/* Tablet and landscape phone: keep one row, but let controls shrink calmly. */
			@media (min-width:641px) and (max-width:991px) {
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
					min-height:64px !important;
					gap:12px !important;
					padding:10px 12px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left {
					flex:1 1 auto !important;
					min-width:0 !important;
					gap:10px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220 {
					font-size:11.5px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
					padding:0 11px 0 12px !important;
					font-size:11.75px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					flex:0 1 190px !important;
					width:190px !important;
					min-width:150px !important;
					max-width:190px !important;
				}
			}

			/* Phone: count gets breathing room; the two controls share the second row. */
			@media (max-width:640px) {
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
					display:grid !important;
					grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important;
					grid-template-rows:auto auto !important;
					align-items:center !important;
					justify-items:stretch !important;
					column-gap:8px !important;
					row-gap:9px !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:auto !important;
					min-height:0 !important;
					max-height:none !important;
					overflow:visible !important;
					margin-bottom:12px !important;
					padding:11px !important;
					border-radius:15px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
					display:contents !important;
			}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220 {
					grid-column:1 / -1 !important;
					grid-row:1 !important;
					width:100% !important;
					min-height:18px !important;
					padding:0 2px !important;
					font-size:11px !important;
					line-height:1.25 !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
					grid-column:1 !important;
					grid-row:2 !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					padding:0 10px 0 11px !important;
					font-size:11.5px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					grid-column:2 !important;
					grid-row:2 !important;
					justify-self:stretch !important;
					flex:none !important;
					box-sizing:border-box !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					margin:0 !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					padding-left:10px !important;
					padding-right:28px !important;
					font-size:11.25px !important;
				}
			}

			/* Very narrow phones stack the two controls instead of squeezing them. */
			@media (max-width:350px) {
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
					grid-template-columns:minmax(0,1fr) !important;
					grid-template-rows:auto auto auto !important;
					row-gap:8px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
					grid-column:1 !important;
					grid-row:2 !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					grid-column:1 !important;
					grid-row:3 !important;
				}
			}

			/* Root-level modal: always above filters, cards, sticky rails, menu and cart layers. */
			html body .mdo-destination-modal--root[hidden] {
				display:none !important;
			}
			html body .mdo-destination-modal--root {
				position:fixed !important;
				inset:0 !important;
				z-index:2147483000 !important;
				display:flex !important;
				align-items:center !important;
				justify-content:center !important;
				box-sizing:border-box !important;
				width:100vw !important;
				height:100dvh !important;
				max-width:none !important;
				max-height:none !important;
				margin:0 !important;
				padding:20px !important;
				isolation:isolate !important;
			}
			html body .mdo-destination-modal--root .mdo-destination-modal__backdrop {
				position:absolute !important;
				inset:0 !important;
				z-index:0 !important;
				background:rgba(13,26,21,.52) !important;
				backdrop-filter:blur(3px) !important;
			}
			html body .mdo-destination-modal--root .mdo-destination-modal__panel {
				position:relative !important;
				z-index:1 !important;
				box-sizing:border-box !important;
				width:min(100%,448px) !important;
				max-height:calc(100dvh - 40px) !important;
				overflow:auto !important;
				padding:30px !important;
				border-radius:18px !important;
				background:#fff !important;
				color:#173f32 !important;
				box-shadow:0 28px 90px rgba(0,0,0,.28) !important;
			}

			/* Same quiet round close language as the menu/cart controls. */
			html body .mdo-destination-modal--root .mdo-destination-modal__close {
				position:absolute !important;
				top:13px !important;
				right:13px !important;
				z-index:2 !important;
				display:grid !important;
				place-items:center !important;
				box-sizing:border-box !important;
				width:40px !important;
				height:40px !important;
				min-width:40px !important;
				min-height:40px !important;
				margin:0 !important;
				padding:0 !important;
				border:0 !important;
				border-radius:999px !important;
				background:transparent !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-size:0 !important;
				line-height:1 !important;
				cursor:pointer !important;
				transition:background-color .15s ease !important;
			}
			html body .mdo-destination-modal--root .mdo-destination-modal__close:hover,
			html body .mdo-destination-modal--root .mdo-destination-modal__close:focus-visible {
				background:rgba(23,63,50,.075) !important;
				outline:none !important;
			}
			html body .mdo-destination-modal--root .mdo-destination-modal__close span {
				display:none !important;
			}
			html body .mdo-destination-modal--root .mdo-destination-modal__close::before,
			html body .mdo-destination-modal--root .mdo-destination-modal__close::after {
				content:"" !important;
				position:absolute !important;
				left:50% !important;
				top:50% !important;
				width:18px !important;
				height:1.6px !important;
				border-radius:999px !important;
				background:#173f32 !important;
				transform-origin:center !important;
			}
			html body .mdo-destination-modal--root .mdo-destination-modal__close::before {
				transform:translate(-50%,-50%) rotate(45deg) !important;
			}
			html body .mdo-destination-modal--root .mdo-destination-modal__close::after {
				transform:translate(-50%,-50%) rotate(-45deg) !important;
			}

			@media (max-width:600px) {
				html body .mdo-destination-modal--root {
					align-items:flex-end !important;
					padding:0 !important;
				}
				html body .mdo-destination-modal--root .mdo-destination-modal__panel {
					width:100% !important;
					max-height:min(86dvh,760px) !important;
					padding:28px 20px 22px !important;
					border-radius:20px 20px 0 0 !important;
				}
				html body .mdo-destination-modal--root .mdo-destination-modal__panel h2 {
					margin-right:46px !important;
				}
				html body .mdo-destination-modal--root .mdo-destination-modal__close {
					top:10px !important;
					right:10px !important;
					width:42px !important;
					height:42px !important;
					min-width:42px !important;
					min-height:42px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
