<?php
/**
 * Plugin Name: MDO Catalog Default Spain Safety
 * Description: Keeps the default Spain catalogue neutral and renders the destination selector on EMDO's canonical catalogue surface.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "España" with no postcode is only the visual default. It must never exclude vendors.
 */
add_filter(
	'mdo_shipping_vendor_can_ship_to',
	static function ( $available, $vendor_id, $destination, $type ) {
		unset( $vendor_id, $type );
		$country  = strtoupper( trim( (string) ( $destination['country'] ?? '' ) ) );
		$postcode = trim( (string) ( $destination['postcode'] ?? '' ) );

		if ( 'ES' === $country && '' === $postcode ) {
			return true;
		}

		return (bool) $available;
	},
	PHP_INT_MAX,
	4
);

/**
 * Extra guard for the default unfiltered shop. The destination frontend uses [0]
 * only as an internal empty-ranking sentinel; never let that blank the initial shop.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$is_catalog = function_exists( 'elmercado_catalog_is_main_query_010224' )
			? elmercado_catalog_is_main_query_010224( $query )
			: ( $query->is_post_type_archive( 'product' ) || $query->is_tax( 'product_cat' ) || 'product' === $query->get( 'post_type' ) );
		if ( ! $is_catalog ) {
			return;
		}

		$country  = isset( $_COOKIE['mdo_shipping_country'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE['mdo_shipping_country'] ) ) ) : 'ES';
		$postcode = isset( $_COOKIE['mdo_shipping_postcode'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mdo_shipping_postcode'] ) ) : '';
		if ( 'ES' !== $country || '' !== trim( $postcode ) ) {
			return;
		}

		$raw_post_in = array_values( (array) $query->get( 'post__in' ) );
		if ( 1 === count( $raw_post_in ) && 0 === absint( $raw_post_in[0] ) ) {
			$query->set( 'post__in', array() );
			if ( 'post__in' === $query->get( 'orderby' ) ) {
				$query->set( 'orderby', 'menu_order title' );
				$query->set( 'order', 'ASC' );
			}
		}
	},
	PHP_INT_MAX
);

function mdo_catalog_default_spain_is_surface_20260820(): bool {
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

function mdo_catalog_default_spain_text_20260820( string $es, string $en ): string {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return ( '/en' === $path || 0 === strpos( $path, '/en/' ) ) ? $en : $es;
}

function mdo_catalog_default_spain_destination_20260820(): array {
	if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		return MDO_Catalog_Destination_Frontend::current_destination();
	}
	return array( 'country' => 'ES', 'postcode' => '' );
}

function mdo_catalog_default_spain_countries_20260820(): array {
	if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		$countries = MDO_Catalog_Destination_Frontend::supported_countries();
		if ( is_array( $countries ) && $countries ) {
			return $countries;
		}
	}
	return array( 'ES' => mdo_catalog_default_spain_text_20260820( 'España', 'Spain' ) );
}

function mdo_catalog_default_spain_render_20260820(): void {
	if ( ! mdo_catalog_default_spain_is_surface_20260820() ) {
		return;
	}
	$destination = mdo_catalog_default_spain_destination_20260820();
	$countries   = mdo_catalog_default_spain_countries_20260820();
	$country     = (string) ( $destination['country'] ?? 'ES' );
	$postcode    = (string) ( $destination['postcode'] ?? '' );
	$label       = (string) ( $countries[ $country ] ?? $country );
	$suffix      = 'ES' === $country && '' !== $postcode ? ' · ' . $postcode : '';
	?>
	<div class="mdo-catalog-destination mdo-catalog-destination--canonical" data-mdo-destination-control>
		<button type="button" class="mdo-catalog-destination__trigger" data-mdo-destination-open aria-haspopup="dialog" aria-controls="mdo-catalog-destination-dialog">
			<span class="mdo-catalog-destination__pin" aria-hidden="true">⌖</span>
			<span><?php echo esc_html( mdo_catalog_default_spain_text_20260820( 'Envío a', 'Shipping to' ) ); ?> <strong><?php echo esc_html( $label . $suffix ); ?></strong></span>
			<span class="mdo-catalog-destination__chevron" aria-hidden="true">⌄</span>
		</button>
	</div>

	<div id="mdo-catalog-destination-dialog" class="mdo-destination-modal" data-mdo-destination-modal hidden aria-hidden="true">
		<div class="mdo-destination-modal__backdrop" data-mdo-destination-close></div>
		<section class="mdo-destination-modal__panel" role="dialog" aria-modal="true" aria-labelledby="mdo-destination-title" tabindex="-1">
			<button type="button" class="mdo-destination-modal__close" data-mdo-destination-close aria-label="<?php echo esc_attr( mdo_catalog_default_spain_text_20260820( 'Cerrar', 'Close' ) ); ?>">×</button>
			<h2 id="mdo-destination-title"><?php echo esc_html( mdo_catalog_default_spain_text_20260820( '¿Dónde quieres recibir tu pedido?', 'Where do you want to receive your order?' ) ); ?></h2>
			<p class="mdo-destination-modal__intro"><?php echo esc_html( mdo_catalog_default_spain_text_20260820( 'Elige un destino para ver solo los productos que pueden enviarse allí.', 'Choose a destination to see only products that can be shipped there.' ) ); ?></p>
			<form data-mdo-destination-form>
				<label for="mdo-destination-country"><?php echo esc_html( mdo_catalog_default_spain_text_20260820( 'País', 'Country' ) ); ?></label>
				<select id="mdo-destination-country" name="country" data-mdo-destination-country>
					<?php foreach ( $countries as $code => $country_label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>><?php echo esc_html( $country_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="mdo-destination-modal__postcode" data-mdo-postcode-wrap <?php echo 'ES' === $country ? '' : 'hidden'; ?>>
					<label for="mdo-destination-postcode"><?php echo esc_html( mdo_catalog_default_spain_text_20260820( 'Código postal', 'Postcode' ) ); ?> <span><?php echo esc_html( mdo_catalog_default_spain_text_20260820( '(opcional)', '(optional)' ) ); ?></span></label>
					<input id="mdo-destination-postcode" name="postcode" data-mdo-destination-postcode inputmode="numeric" autocomplete="postal-code" maxlength="5" pattern="[0-9]{5}" value="<?php echo esc_attr( $postcode ); ?>" placeholder="28001">
					<small><?php echo esc_html( mdo_catalog_default_spain_text_20260820( 'Sin código postal se muestra la tienda completa. No realizamos envíos a Ceuta ni Melilla.', 'Without a postcode the full shop is shown. We do not ship to Ceuta or Melilla.' ) ); ?></small>
				</div>
				<p class="mdo-destination-modal__error" data-mdo-destination-error role="alert" hidden></p>
				<button type="submit" class="mdo-destination-modal__save" data-mdo-destination-save><?php echo esc_html( mdo_catalog_default_spain_text_20260820( 'Aplicar destino', 'Apply destination' ) ); ?></button>
			</form>
		</section>
	</div>
	<?php
}

function mdo_catalog_default_spain_styles_20260820(): void {
	if ( ! mdo_catalog_default_spain_is_surface_20260820() ) {
		return;
	}
	?>
	<style id="mdo-catalog-default-spain-ui-20260820">
		body .emo-catalog-result-count-010220{display:inline-flex!important;align-items:center!important;margin-right:10px!important;vertical-align:middle!important}
		.mdo-catalog-destination--canonical{display:inline-flex;align-items:center;vertical-align:middle;margin:0 0 18px 0}
		.mdo-catalog-destination__trigger{display:inline-flex;min-height:36px;align-items:center;gap:6px;padding:0 12px;border:1px solid rgba(23,63,50,.16);border-radius:999px;background:#fff;color:#173f32;font:inherit;font-size:13px;line-height:1;cursor:pointer;box-shadow:none}
		.mdo-catalog-destination__trigger:hover,.mdo-catalog-destination__trigger:focus-visible{border-color:rgba(23,63,50,.34);background:#fbfdfc;outline:none}
		.mdo-catalog-destination__trigger strong{font-weight:750}.mdo-catalog-destination__pin{font-size:15px;line-height:1}.mdo-catalog-destination__chevron{font-size:16px;opacity:.65}
		.mdo-destination-modal[hidden]{display:none!important}.mdo-destination-modal{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px}
		.mdo-destination-modal__backdrop{position:absolute;inset:0;background:rgba(13,26,21,.46);backdrop-filter:blur(2px)}
		.mdo-destination-modal__panel{position:relative;z-index:1;width:min(100%,440px);padding:28px;border-radius:16px;background:#fff;color:#173f32;box-shadow:0 24px 70px rgba(0,0,0,.22)}
		.mdo-destination-modal__panel h2{margin:0 34px 8px 0;font-size:22px;line-height:1.25;color:#173f32}.mdo-destination-modal__intro{margin:0 0 22px;color:#5e6f68;font-size:14px;line-height:1.5}
		.mdo-destination-modal__close{position:absolute;top:15px;right:15px;width:36px;height:36px;padding:0;border:0;border-radius:50%;background:#f3f6f4;color:#173f32;font-size:25px;line-height:34px;cursor:pointer}
		.mdo-destination-modal label{display:block;margin:0 0 7px;font-size:13px;font-weight:750;color:#284b40}.mdo-destination-modal label span{font-weight:500;color:#798680}
		.mdo-destination-modal select,.mdo-destination-modal input{box-sizing:border-box;width:100%;min-height:46px;margin:0;padding:0 13px;border:1px solid #d7dfdb;border-radius:9px;background:#fff;color:#173f32;font:inherit;font-size:15px;box-shadow:none}
		.mdo-destination-modal__postcode{margin-top:17px}.mdo-destination-modal__postcode[hidden]{display:none!important}.mdo-destination-modal__postcode small{display:block;margin-top:7px;color:#718078;font-size:12px;line-height:1.45}
		.mdo-destination-modal__error{margin:14px 0 0;padding:10px 12px;border-radius:8px;background:#fff4f2;color:#8f2d20;font-size:13px}.mdo-destination-modal__save{display:flex;width:100%;min-height:46px;align-items:center;justify-content:center;margin-top:20px;padding:0 18px;border:0;border-radius:9px;background:#173f32;color:#fff;font:inherit;font-size:14px;font-weight:800;cursor:pointer}
		.mdo-destination-modal__save[disabled]{opacity:.58;cursor:wait}body.mdo-destination-modal-open{overflow:hidden}
		@media(max-width:600px){body .emo-catalog-result-count-010220{margin-right:7px!important}.mdo-catalog-destination--canonical{margin-bottom:14px}.mdo-catalog-destination__trigger{min-height:34px;padding:0 10px;font-size:12px}.mdo-destination-modal{align-items:flex-end;padding:0}.mdo-destination-modal__panel{width:100%;padding:24px 20px 22px;border-radius:18px 18px 0 0}.mdo-destination-modal__panel h2{font-size:20px}}
	</style>
	<?php
}

function mdo_catalog_default_spain_script_20260820(): void {
	if ( ! mdo_catalog_default_spain_is_surface_20260820() ) {
		return;
	}
	$nonce = wp_create_nonce( 'mdo_shipping_destination' );
	?>
	<script id="mdo-catalog-default-spain-ui-js-20260820">
	(()=>{'use strict';const modal=document.querySelector('[data-mdo-destination-modal]');const openButton=document.querySelector('[data-mdo-destination-open]');if(!modal||!openButton)return;const panel=modal.querySelector('.mdo-destination-modal__panel');const form=modal.querySelector('[data-mdo-destination-form]');const country=modal.querySelector('[data-mdo-destination-country]');const postcodeWrap=modal.querySelector('[data-mdo-postcode-wrap]');const postcode=modal.querySelector('[data-mdo-destination-postcode]');const error=modal.querySelector('[data-mdo-destination-error]');const save=modal.querySelector('[data-mdo-destination-save]');let returnFocus=null;const sync=()=>{const es=country.value==='ES';postcodeWrap.hidden=!es;postcode.disabled=!es;if(!es)postcode.value=''};const open=()=>{returnFocus=document.activeElement;modal.hidden=false;modal.setAttribute('aria-hidden','false');document.body.classList.add('mdo-destination-modal-open');sync();setTimeout(()=>panel.focus({preventScroll:true}),0)};const close=()=>{modal.hidden=true;modal.setAttribute('aria-hidden','true');document.body.classList.remove('mdo-destination-modal-open');error.hidden=true;if(returnFocus&&typeof returnFocus.focus==='function')returnFocus.focus({preventScroll:true})};openButton.addEventListener('click',open);modal.querySelectorAll('[data-mdo-destination-close]').forEach(el=>el.addEventListener('click',close));country.addEventListener('change',sync);document.addEventListener('keydown',e=>{if(!modal.hidden&&e.key==='Escape')close()});form.addEventListener('submit',async e=>{e.preventDefault();error.hidden=true;if(country.value==='ES'&&postcode.value&&!/^\d{5}$/.test(postcode.value.trim())){error.textContent=<?php echo wp_json_encode( mdo_catalog_default_spain_text_20260820( 'Introduce un código postal español de 5 cifras o déjalo vacío.', 'Enter a 5-digit Spanish postcode or leave it blank.' ) ); ?>;error.hidden=false;postcode.focus();return}save.disabled=true;try{const body=new URLSearchParams({action:'mdo_set_shipping_destination',nonce:<?php echo wp_json_encode( $nonce ); ?>,country:country.value,postcode:country.value==='ES'?postcode.value.trim():''});const response=await fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString()});const payload=await response.json();if(!response.ok||!payload.success)throw new Error(payload?.data?.message||'Error');window.location.reload()}catch(ex){error.textContent=ex?.message||<?php echo wp_json_encode( mdo_catalog_default_spain_text_20260820( 'No se pudo aplicar el destino.', 'The destination could not be applied.' ) ); ?>;error.hidden=false;save.disabled=false}})})();
	</script>
	<?php
}

/**
 * Replace only the old UI callbacks. Query/ranking logic remains untouched.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
			remove_action( 'woocommerce_before_shop_loop', array( 'MDO_Catalog_Destination_Frontend', 'render_destination_control' ), 22 );
			remove_action( 'wp_head', array( 'MDO_Catalog_Destination_Frontend', 'render_styles' ), PHP_INT_MAX );
			remove_action( 'wp_footer', array( 'MDO_Catalog_Destination_Frontend', 'render_script' ), PHP_INT_MAX );
		}
		add_action( 'woocommerce_before_shop_loop', 'mdo_catalog_default_spain_render_20260820', 22 );
		add_action( 'wp_head', 'mdo_catalog_default_spain_styles_20260820', PHP_INT_MAX );
		add_action( 'wp_footer', 'mdo_catalog_default_spain_script_20260820', PHP_INT_MAX );
	},
	PHP_INT_MAX
);

/** Cache must only be bypassed after an actual non-generic destination is selected. */
add_action(
	'template_redirect',
	static function (): void {
		if ( ! mdo_catalog_default_spain_is_surface_20260820() ) {
			return;
		}
		$destination = mdo_catalog_default_spain_destination_20260820();
		if ( 'ES' === (string) ( $destination['country'] ?? 'ES' ) && '' === trim( (string) ( $destination['postcode'] ?? '' ) ) ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	},
	1
);
