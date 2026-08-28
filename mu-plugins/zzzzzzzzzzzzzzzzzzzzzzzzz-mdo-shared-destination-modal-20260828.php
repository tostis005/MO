<?php
/**
 * Plugin Name: MDO Shared Destination Modal 2026-08-28
 * Description: One shared destination form, stylesheet and interaction owner for the global and producer catalogues.
 * Version: 1.0.1
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_shared_destination_is_producer_20260828(): bool {
	if ( function_exists( 'mdo_ps_safe_is_store_20260821' ) ) {
		return (bool) mdo_ps_safe_is_store_20260821();
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

function mdo_shared_destination_is_global_catalog_20260828(): bool {
	if ( mdo_shared_destination_is_producer_20260828() ) {
		return false;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	return function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();
}

function mdo_shared_destination_is_surface_20260828(): bool {
	return mdo_shared_destination_is_global_catalog_20260828() || mdo_shared_destination_is_producer_20260828();
}

function mdo_shared_destination_is_english_20260828(): bool {
	if ( function_exists( 'mdo_ps_safe_is_english_20260821' ) && mdo_ps_safe_is_english_20260821() ) {
		return true;
	}
	if ( function_exists( 'mdo_sst_is_english' ) && mdo_sst_is_english() ) {
		return true;
	}
	if ( function_exists( 'mdo_en_is_request' ) && mdo_en_is_request() ) {
		return true;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( (string) $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return '/en' === $path || 0 === strpos( $path, '/en/' );
}

function mdo_shared_destination_text_20260828( string $es, string $en ): string {
	return mdo_shared_destination_is_english_20260828() ? $en : $es;
}

/** @return array{country:string,postcode:string} */
function mdo_shared_destination_current_20260828(): array {
	if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		$current = MDO_Catalog_Destination_Frontend::current_destination();
		return array(
			'country'  => strtoupper( sanitize_text_field( (string) ( $current['country'] ?? 'ES' ) ) ),
			'postcode' => sanitize_text_field( (string) ( $current['postcode'] ?? '' ) ),
		);
	}
	return array( 'country' => 'ES', 'postcode' => '' );
}

/** @return array<string,string> */
function mdo_shared_destination_countries_20260828(): array {
	$countries = class_exists( 'MDO_Catalog_Destination_Frontend' )
		? (array) MDO_Catalog_Destination_Frontend::supported_countries()
		: array( 'ES' => 'España' );

	if ( ! mdo_shared_destination_is_english_20260828() ) {
		return $countries;
	}

	$english = array(
		'ES' => 'Spain', 'DE' => 'Germany', 'AT' => 'Austria', 'BE' => 'Belgium',
		'BG' => 'Bulgaria', 'FR' => 'France', 'GR' => 'Greece', 'HU' => 'Hungary',
		'IT' => 'Italy', 'LU' => 'Luxembourg', 'NL' => 'Netherlands', 'PL' => 'Poland',
		'PT' => 'Portugal', 'CZ' => 'Czechia', 'SE' => 'Sweden', 'CH' => 'Switzerland',
	);
	foreach ( $countries as $code => $label ) {
		$code = strtoupper( (string) $code );
		$countries[ $code ] = $english[ $code ] ?? (string) $label;
	}
	return $countries;
}

function mdo_shared_destination_trigger_markup_20260828( bool $producer ): void {
	if ( ! class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		return;
	}
	$destination  = mdo_shared_destination_current_20260828();
	$countries    = mdo_shared_destination_countries_20260828();
	$label        = (string) ( $countries[ $destination['country'] ] ?? $destination['country'] );
	$suffix       = 'ES' === $destination['country'] && '' !== $destination['postcode'] ? ' · ' . $destination['postcode'] : '';
	$wrap_class   = $producer ? 'mdo-ps-destination' : 'mdo-catalog-destination';
	$button_class = $producer ? 'mdo-ps-destination__trigger' : 'mdo-catalog-destination__trigger';
	$open_attr    = $producer ? 'data-mdo-ps-destination-open' : 'data-mdo-destination-open';
	?>
	<div class="<?php echo esc_attr( $wrap_class ); ?>" data-mdo-shared-destination-trigger-wrap>
		<button type="button" class="<?php echo esc_attr( $button_class ); ?>" <?php echo esc_attr( $open_attr ); ?> aria-haspopup="dialog" aria-controls="mdo-shared-destination-dialog">
			<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Zm0-8.5A2.5 2.5 0 1 1 12 7a2.5 2.5 0 0 1 0 5.5Z" fill="currentColor"/></svg>
			<span><?php echo esc_html( mdo_shared_destination_text_20260828( 'Envío a', 'Shipping to' ) ); ?> <strong><?php echo esc_html( $label . $suffix ); ?></strong></span>
			<svg aria-hidden="true" viewBox="0 0 20 20" width="14" height="14"><path d="m5 7.5 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>
	<?php
}

function mdo_shared_destination_render_global_trigger_20260828(): void {
	if ( mdo_shared_destination_is_global_catalog_20260828() ) {
		mdo_shared_destination_trigger_markup_20260828( false );
	}
}

function mdo_shared_destination_render_producer_trigger_20260828(): void {
	if ( mdo_shared_destination_is_producer_20260828() ) {
		mdo_shared_destination_trigger_markup_20260828( true );
	}
}

/** Both catalogue surfaces open this exact same DOM form. */
function mdo_shared_destination_render_modal_20260828(): void {
	if ( ! mdo_shared_destination_is_surface_20260828() || ! class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		return;
	}
	$destination = mdo_shared_destination_current_20260828();
	$countries   = mdo_shared_destination_countries_20260828();
	?>
	<div id="mdo-shared-destination-dialog" class="mdo-shared-destination-modal" data-mdo-shared-destination-modal data-mdo-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-mdo-nonce="<?php echo esc_attr( wp_create_nonce( 'mdo_shipping_destination' ) ); ?>" hidden aria-hidden="true">
		<div class="mdo-shared-destination-modal__backdrop" data-mdo-shared-destination-close></div>
		<section class="mdo-shared-destination-modal__panel" role="dialog" aria-modal="true" aria-labelledby="mdo-shared-destination-title" tabindex="-1">
			<button type="button" class="mdo-shared-destination-modal__close" data-mdo-shared-destination-close aria-label="<?php echo esc_attr( mdo_shared_destination_text_20260828( 'Cerrar', 'Close' ) ); ?>">×</button>
			<h2 id="mdo-shared-destination-title"><?php echo esc_html( mdo_shared_destination_text_20260828( '¿Dónde quieres recibir tu pedido?', 'Where do you want to receive your order?' ) ); ?></h2>
			<p class="mdo-shared-destination-modal__intro"><?php echo esc_html( mdo_shared_destination_text_20260828( 'Mostramos solo los productos que pueden enviarse al destino elegido.', 'We only show products that can be shipped to your selected destination.' ) ); ?></p>
			<form data-mdo-shared-destination-form>
				<label for="mdo-shared-destination-country"><?php echo esc_html( mdo_shared_destination_text_20260828( 'País', 'Country' ) ); ?></label>
				<select id="mdo-shared-destination-country" name="country" data-mdo-shared-country>
					<?php foreach ( $countries as $code => $country_label ) : ?>
						<option value="<?php echo esc_attr( (string) $code ); ?>" <?php selected( $destination['country'], (string) $code ); ?>><?php echo esc_html( (string) $country_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="mdo-shared-destination-modal__postcode" data-mdo-shared-postcode-wrap <?php echo 'ES' === $destination['country'] ? '' : 'hidden'; ?>>
					<label for="mdo-shared-destination-postcode"><?php echo esc_html( mdo_shared_destination_text_20260828( 'Código postal', 'Postcode' ) ); ?> <span><?php echo esc_html( mdo_shared_destination_text_20260828( '(opcional)', '(optional)' ) ); ?></span></label>
					<input id="mdo-shared-destination-postcode" name="postcode" data-mdo-shared-postcode inputmode="numeric" autocomplete="postal-code" maxlength="5" pattern="[0-9]{5}" value="<?php echo esc_attr( $destination['postcode'] ); ?>" placeholder="28001">
					<small><?php echo esc_html( mdo_shared_destination_text_20260828( 'Úsalo para ajustar Península, Baleares o Canarias. Actualmente no enviamos a Ceuta ni Melilla.', 'Use it to refine Mainland Spain, Balearic Islands or Canary Islands. We currently do not ship to Ceuta or Melilla.' ) ); ?></small>
				</div>
				<p class="mdo-shared-destination-modal__error" data-mdo-shared-destination-error role="alert" hidden></p>
				<button type="submit" class="mdo-shared-destination-modal__save" data-mdo-shared-destination-save><?php echo esc_html( mdo_shared_destination_text_20260828( 'Guardar destino', 'Save destination' ) ); ?></button>
			</form>
		</section>
	</div>
	<?php
}

function mdo_shared_destination_render_styles_20260828(): void {
	if ( ! mdo_shared_destination_is_surface_20260828() ) {
		return;
	}
	?>
	<style id="mdo-shared-destination-modal-css-20260828">
		.mdo-shared-destination-modal[hidden]{display:none!important}
		.mdo-shared-destination-modal{position:fixed!important;inset:0!important;z-index:100000!important;display:flex!important;align-items:center!important;justify-content:center!important;box-sizing:border-box!important;padding:20px!important;color:#173f32!important;font-family:inherit!important}
		.mdo-shared-destination-modal__backdrop{position:absolute!important;inset:0!important;background:rgba(13,26,21,.46)!important;backdrop-filter:blur(2px)!important}
		.mdo-shared-destination-modal__panel{position:relative!important;z-index:1!important;box-sizing:border-box!important;width:min(100%,440px)!important;margin:0!important;padding:28px!important;border:0!important;border-radius:16px!important;background:#fff!important;color:#173f32!important;box-shadow:0 24px 70px rgba(0,0,0,.22)!important}
		.mdo-shared-destination-modal__panel h2{margin:0 42px 8px 0!important;padding:0!important;color:#173f32!important;font-size:22px!important;font-weight:700!important;line-height:1.25!important}
		.mdo-shared-destination-modal__intro{margin:0 0 22px!important;padding:0!important;color:#5e6f68!important;font-size:14px!important;line-height:1.5!important}
		html body .mdo-shared-destination-modal .mdo-shared-destination-modal__close{position:absolute!important;top:15px!important;right:15px!important;display:flex!important;width:36px!important;height:36px!important;min-width:36px!important;min-height:36px!important;align-items:center!important;justify-content:center!important;margin:0!important;padding:0!important;border:0!important;border-radius:50%!important;background:#f3f6f4!important;background-color:#f3f6f4!important;background-image:none!important;box-shadow:none!important;color:#173f32!important;font:inherit!important;font-size:25px!important;font-weight:400!important;line-height:1!important;text-align:center!important;cursor:pointer!important;-webkit-appearance:none!important;appearance:none!important}
		html body .mdo-shared-destination-modal .mdo-shared-destination-modal__close:hover,html body .mdo-shared-destination-modal .mdo-shared-destination-modal__close:focus-visible{background:#eaf0ec!important;outline:2px solid rgba(23,63,50,.12)!important;outline-offset:2px!important}
		.mdo-shared-destination-modal form{margin:0!important;padding:0!important}
		.mdo-shared-destination-modal label{display:block!important;margin:0 0 7px!important;padding:0!important;color:#284b40!important;font-size:13px!important;font-weight:750!important;line-height:1.35!important}
		.mdo-shared-destination-modal label span{font-weight:500!important;color:#798680!important}
		.mdo-shared-destination-modal select,.mdo-shared-destination-modal input{display:block!important;box-sizing:border-box!important;width:100%!important;min-height:46px!important;margin:0!important;padding:0 13px!important;border:1px solid #d7dfdb!important;border-radius:9px!important;background:#fff!important;background-color:#fff!important;color:#173f32!important;font:inherit!important;font-size:15px!important;box-shadow:none!important}
		.mdo-shared-destination-modal select:focus,.mdo-shared-destination-modal input:focus{border-color:#658a7d!important;outline:2px solid rgba(23,63,50,.08)!important;outline-offset:1px!important}
		.mdo-shared-destination-modal__postcode{margin-top:17px!important}
		.mdo-shared-destination-modal__postcode[hidden]{display:none!important}
		.mdo-shared-destination-modal__postcode small{display:block!important;margin-top:7px!important;color:#718078!important;font-size:12px!important;line-height:1.45!important}
		.mdo-shared-destination-modal__error{margin:14px 0 0!important;padding:10px 12px!important;border-radius:8px!important;background:#fff4f2!important;color:#8f2d20!important;font-size:13px!important;line-height:1.4!important}
		html body .mdo-shared-destination-modal .mdo-shared-destination-modal__save{display:flex!important;width:100%!important;min-height:46px!important;align-items:center!important;justify-content:center!important;margin:20px 0 0!important;padding:0 18px!important;border:0!important;border-radius:9px!important;background:#173f32!important;background-color:#173f32!important;background-image:none!important;box-shadow:none!important;color:#fff!important;font:inherit!important;font-size:14px!important;font-weight:800!important;line-height:1!important;cursor:pointer!important;-webkit-appearance:none!important;appearance:none!important}
		.mdo-shared-destination-modal__save[disabled]{opacity:.58!important;cursor:wait!important}
		body.mdo-shared-destination-modal-open{overflow:hidden!important}
		@media(max-width:600px){.mdo-shared-destination-modal{align-items:flex-end!important;padding:0!important}.mdo-shared-destination-modal__panel{width:100%!important;max-height:min(88vh,680px)!important;overflow:auto!important;padding:24px 20px 22px!important;border-radius:18px 18px 0 0!important}.mdo-shared-destination-modal__panel h2{font-size:20px!important}}
	</style>
	<?php
}

function mdo_shared_destination_render_script_20260828(): void {
	if ( ! mdo_shared_destination_is_surface_20260828() ) {
		return;
	}
	?>
	<script id="mdo-shared-destination-modal-js-20260828">
	(() => {
		'use strict';
		const modal = document.querySelector('[data-mdo-shared-destination-modal]');
		if (!modal) return;
		const form = modal.querySelector('[data-mdo-shared-destination-form]');
		const country = modal.querySelector('[data-mdo-shared-country]');
		const postcodeWrap = modal.querySelector('[data-mdo-shared-postcode-wrap]');
		const postcode = modal.querySelector('[data-mdo-shared-postcode]');
		const error = modal.querySelector('[data-mdo-shared-destination-error]');
		const save = modal.querySelector('[data-mdo-shared-destination-save]');
		let returnFocus = null;
		const syncPostcode = () => { const spanish = country.value === 'ES'; postcodeWrap.hidden = !spanish; if (!spanish) postcode.value = ''; };
		const showError = (message) => { error.textContent = message || ''; error.hidden = !message; };
		const open = (trigger) => { returnFocus = trigger || document.activeElement; modal.hidden = false; modal.setAttribute('aria-hidden','false'); document.body.classList.add('mdo-shared-destination-modal-open'); syncPostcode(); showError(''); country.focus({preventScroll:true}); };
		const close = () => { modal.hidden = true; modal.setAttribute('aria-hidden','true'); document.body.classList.remove('mdo-shared-destination-modal-open'); if (returnFocus && typeof returnFocus.focus === 'function') returnFocus.focus({preventScroll:true}); };
		document.addEventListener('click', (event) => {
			const openButton = event.target.closest('[data-mdo-destination-open],[data-mdo-ps-destination-open]');
			if (openButton) { event.preventDefault(); event.stopImmediatePropagation(); open(openButton); return; }
			if (event.target.closest('[data-mdo-shared-destination-close]')) { event.preventDefault(); close(); }
		}, true);
		document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) close(); });
		country.addEventListener('change', syncPostcode);
		form.addEventListener('submit', async (event) => {
			event.preventDefault(); showError('');
			const postcodeValue = country.value === 'ES' ? postcode.value.replace(/\D+/g,'') : '';
			if (country.value === 'ES' && postcodeValue && postcodeValue.length !== 5) { showError(<?php echo wp_json_encode( mdo_shared_destination_text_20260828( 'Introduce un código postal español de 5 cifras o déjalo vacío.', 'Enter a 5-digit Spanish postcode or leave it blank.' ) ); ?>); postcode.focus(); return; }
			save.disabled = true;
			const body = new URLSearchParams(); body.set('action','mdo_set_shipping_destination'); body.set('nonce',modal.dataset.mdoNonce || ''); body.set('country',country.value); body.set('postcode',postcodeValue);
			try {
				const response = await fetch(modal.dataset.mdoAjaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString()});
				const json = await response.json();
				if (!response.ok || !json || !json.success) throw new Error(json?.data?.message || <?php echo wp_json_encode( mdo_shared_destination_text_20260828( 'No hemos podido guardar el destino.', 'We could not save the destination.' ) ); ?>);
				window.location.reload();
			} catch (err) { showError(err?.message || <?php echo wp_json_encode( mdo_shared_destination_text_20260828( 'No hemos podido guardar el destino.', 'We could not save the destination.' ) ); ?>); save.disabled = false; }
		});
	})();
	</script>
	<?php
}

/** Remove only duplicated UI callbacks; all destination/query/AJAX logic stays intact. */
function mdo_shared_destination_remove_legacy_ui_20260828(): void {
	if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		remove_action( 'woocommerce_before_shop_loop', array( 'MDO_Catalog_Destination_Frontend', 'render_destination_control' ), 22 );
		remove_action( 'wp_head', array( 'MDO_Catalog_Destination_Frontend', 'render_styles' ), PHP_INT_MAX );
		remove_action( 'wp_footer', array( 'MDO_Catalog_Destination_Frontend', 'render_script' ), PHP_INT_MAX );
	}
	remove_action( 'woocommerce_before_shop_loop', 'mdo_ps_safe_render_trigger_20260821', 21 );
	remove_action( 'wp_footer', 'mdo_ps_safe_footer_20260821', PHP_INT_MAX );
}

function mdo_shared_destination_register_ui_20260828(): void {
	static $registered = false;
	if ( $registered ) {
		return;
	}
	$registered = true;
	add_action( 'woocommerce_before_shop_loop', 'mdo_shared_destination_render_producer_trigger_20260828', 21 );
	add_action( 'woocommerce_before_shop_loop', 'mdo_shared_destination_render_global_trigger_20260828', 22 );
	add_action( 'wp_head', 'mdo_shared_destination_render_styles_20260828', PHP_INT_MAX );
	add_action( 'wp_footer', 'mdo_shared_destination_render_modal_20260828', PHP_INT_MAX - 2 );
	add_action( 'wp_footer', 'mdo_shared_destination_render_script_20260828', PHP_INT_MAX );
}

/* Cleanup repeatedly at safe lifecycle boundaries so late compatibility layers
 * cannot restore either legacy modal before head, loop or footer rendering. */
add_action( 'wp_loaded', 'mdo_shared_destination_remove_legacy_ui_20260828', PHP_INT_MAX );
add_action( 'wp_loaded', 'mdo_shared_destination_register_ui_20260828', PHP_INT_MAX );
add_action( 'wp', 'mdo_shared_destination_remove_legacy_ui_20260828', PHP_INT_MAX );
add_action( 'template_redirect', 'mdo_shared_destination_remove_legacy_ui_20260828', PHP_INT_MAX );
add_action( 'wp_head', 'mdo_shared_destination_remove_legacy_ui_20260828', 0 );
add_action( 'wp_footer', 'mdo_shared_destination_remove_legacy_ui_20260828', 0 );
