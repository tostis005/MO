<?php
/**
 * Plugin Name: MDO Producer Store Catalog Rules Safe
 * Description: Destination-aware filtering and EMDO recommended order for WCFM producer stores without rebuilding store queries.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_ps_safe_is_store_20260821(): bool {
	if ( function_exists( 'elmercado_public_store_fix_is_store_010261' ) ) {
		return (bool) elmercado_public_store_fix_is_store_010261();
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

function mdo_ps_safe_vendor_id_20260821(): int {
	return function_exists( 'elmercado_public_store_fix_vendor_id_010261' )
		? absint( elmercado_public_store_fix_vendor_id_010261() )
		: 0;
}

function mdo_ps_safe_is_english_20260821(): bool {
	if ( function_exists( 'mdo_sst_is_english' ) && mdo_sst_is_english() ) {
		return true;
	}
	if ( function_exists( 'mdo_en_is_request' ) && mdo_en_is_request() ) {
		return true;
	}
	if ( function_exists( 'mdoev_en_010260' ) && mdoev_en_010260() ) {
		return true;
	}
	$path = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_parse_url( wp_unslash( (string) $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
		: '';
	return '/en' === $path || 0 === strpos( $path, '/en/' );
}

function mdo_ps_safe_text_20260821( string $es, string $en ): string {
	return mdo_ps_safe_is_english_20260821() ? $en : $es;
}

/** @return array{country:string,postcode:string} */
function mdo_ps_safe_destination_20260821(): array {
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
function mdo_ps_safe_countries_20260821(): array {
	$countries = class_exists( 'MDO_Catalog_Destination_Frontend' )
		? (array) MDO_Catalog_Destination_Frontend::supported_countries()
		: array( 'ES' => 'España' );

	if ( ! mdo_ps_safe_is_english_20260821() ) {
		return $countries;
	}

	$english = array(
		'ES' => 'Spain',
		'DE' => 'Germany',
		'AT' => 'Austria',
		'BE' => 'Belgium',
		'BG' => 'Bulgaria',
		'FR' => 'France',
		'GR' => 'Greece',
		'HU' => 'Hungary',
		'IT' => 'Italy',
		'LU' => 'Luxembourg',
		'NL' => 'Netherlands',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'CZ' => 'Czechia',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
	);

	foreach ( $countries as $code => $label ) {
		$code = strtoupper( (string) $code );
		$countries[ $code ] = $english[ $code ] ?? (string) $label;
	}
	return $countries;
}

function mdo_ps_safe_requested_orderby_20260821(): string {
	return isset( $_GET['orderby'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		? sanitize_key( wp_unslash( (string) $_GET['orderby'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';
}

function mdo_ps_safe_is_recommended_20260821(): bool {
	return in_array( mdo_ps_safe_requested_orderby_20260821(), array( '', 'mdo_recommended' ), true );
}

/**
 * Filter and rank an already-resolved store ID list. This function does not
 * discover products and therefore cannot recurse into the WCFM store query.
 *
 * @param int[] $ids
 * @return int[]
 */
function mdo_ps_safe_filter_ids_20260821( array $ids, int $vendor_id, string $country, string $postcode, bool $recommended ): array {
	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	$vendor_id = absint( $vendor_id );
	if ( ! $vendor_id || ! $ids ) {
		return array();
	}

	if ( class_exists( 'MDO_Shipping_Destinations' ) && ! MDO_Shipping_Destinations::vendor_can_ship_to( $vendor_id, $country, $postcode ) ) {
		return array();
	}

	if ( $recommended && class_exists( 'MDO_Catalog_Ranking' ) ) {
		$ids = MDO_Catalog_Ranking::rank_products(
			$ids,
			array(
				'rotation_seed'      => gmdate( 'Y-m-d' ),
				'diversify_vendors' => true,
			)
		);
	}

	return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
}

/**
 * Runs after the existing stable producer-store guard. We only touch the query
 * that guard has explicitly marked as the public store catalogue loop.
 */
function mdo_ps_safe_apply_query_20260821( WP_Query $query ): void {
	if ( is_admin() || ! $query->get( 'emo_public_store_loop_010261' ) || ! mdo_ps_safe_is_store_20260821() ) {
		return;
	}

	$vendor_id = mdo_ps_safe_vendor_id_20260821();
	if ( $vendor_id <= 0 ) {
		return;
	}

	/* Never rebuild IDs here. The stable guard has already resolved ownership. */
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) ) ) );
	if ( ! $ids || array( 0 ) === $ids ) {
		return;
	}

	$destination = mdo_ps_safe_destination_20260821();
	$recommended = mdo_ps_safe_is_recommended_20260821();
	$resolved = mdo_ps_safe_filter_ids_20260821(
		$ids,
		$vendor_id,
		$destination['country'],
		$destination['postcode'],
		$recommended
	);

	$query->set( 'mdo_ps_safe_applied_20260821', 1 );
	$query->set( 'mdo_ps_safe_blocked_20260821', $resolved ? 0 : 1 );
	$query->set( 'mdo_ps_safe_ranked_ids_20260821', $resolved );
	$query->set( 'post__in', $resolved ?: array( 0 ) );

	if ( $recommended && $resolved ) {
		$query->set( 'orderby', 'post__in' );
		$query->set( 'order', 'ASC' );
		$query->set( 'meta_key', '' );
	}
}

function mdo_ps_safe_final_posts_20260821( array $posts, WP_Query $query ): array {
	if ( is_admin() || ! $query->get( 'mdo_ps_safe_applied_20260821' ) ) {
		return $posts;
	}

	/* The older recovery layer runs first. A blocked destination must win last. */
	if ( $query->get( 'mdo_ps_safe_blocked_20260821' ) ) {
		$query->found_posts   = 0;
		$query->max_num_pages = 0;
		return array();
	}

	$ranked = array_values( array_filter( array_map( 'absint', (array) $query->get( 'mdo_ps_safe_ranked_ids_20260821' ) ) ) );
	if ( $ranked && mdo_ps_safe_is_recommended_20260821() ) {
		$position = array_flip( $ranked );
		usort(
			$posts,
			static function ( $a, $b ) use ( $position ): int {
				$a_id = $a instanceof WP_Post ? (int) $a->ID : 0;
				$b_id = $b instanceof WP_Post ? (int) $b->ID : 0;
				return ( $position[ $a_id ] ?? PHP_INT_MAX ) <=> ( $position[ $b_id ] ?? PHP_INT_MAX );
			}
		);
	}
	return $posts;
}

add_action(
	'wp_loaded',
	static function (): void {
		add_action( 'pre_get_posts', 'mdo_ps_safe_apply_query_20260821', PHP_INT_MAX );
		add_filter( 'the_posts', 'mdo_ps_safe_final_posts_20260821', PHP_INT_MAX, 2 );
	},
	PHP_INT_MAX
);

/* Keep the standard sort UI aligned with the default EMDO ranking on stores. */
add_filter(
	'woocommerce_catalog_orderby',
	static function ( array $options ): array {
		if ( ! mdo_ps_safe_is_store_20260821() ) {
			return $options;
		}
		unset( $options['mdo_recommended'] );
		return array( 'mdo_recommended' => mdo_ps_safe_text_20260821( 'Recomendados', 'Recommended' ) ) + $options;
	},
	PHP_INT_MAX
);
add_filter(
	'woocommerce_default_catalog_orderby',
	static function ( string $orderby ): string {
		return mdo_ps_safe_is_store_20260821() ? 'mdo_recommended' : $orderby;
	},
	PHP_INT_MAX
);

function mdo_ps_safe_render_trigger_20260821(): void {
	if ( ! mdo_ps_safe_is_store_20260821() || ! class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		return;
	}
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	$destination = mdo_ps_safe_destination_20260821();
	$countries   = mdo_ps_safe_countries_20260821();
	$label       = (string) ( $countries[ $destination['country'] ] ?? $destination['country'] );
	$suffix      = 'ES' === $destination['country'] && '' !== $destination['postcode'] ? ' · ' . $destination['postcode'] : '';
	?>
	<div class="mdo-ps-destination" data-mdo-ps-destination-trigger-wrap>
		<button type="button" class="mdo-ps-destination__trigger" data-mdo-ps-destination-open aria-haspopup="dialog" aria-controls="mdo-ps-destination-dialog">
			<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Zm0-8.5A2.5 2.5 0 1 1 12 7a2.5 2.5 0 0 1 0 5.5Z" fill="currentColor"/></svg>
			<span><?php echo esc_html( mdo_ps_safe_text_20260821( 'Envío a', 'Shipping to' ) ); ?> <strong><?php echo esc_html( $label . $suffix ); ?></strong></span>
			<svg aria-hidden="true" viewBox="0 0 20 20" width="14" height="14"><path d="m5 7.5 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>
	<?php
}
add_action( 'woocommerce_before_shop_loop', 'mdo_ps_safe_render_trigger_20260821', 21 );

function mdo_ps_safe_footer_20260821(): void {
	if ( ! mdo_ps_safe_is_store_20260821() || ! class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		return;
	}
	$destination = mdo_ps_safe_destination_20260821();
	$countries   = mdo_ps_safe_countries_20260821();
	$nonce       = wp_create_nonce( 'mdo_shipping_destination' );
	?>
	<div data-mdo-ps-destination-fallback hidden><?php mdo_ps_safe_render_trigger_20260821(); ?></div>
	<div id="mdo-ps-destination-dialog" class="mdo-ps-modal" hidden aria-hidden="true">
		<div class="mdo-ps-modal__backdrop" data-mdo-ps-destination-close></div>
		<section class="mdo-ps-modal__panel" role="dialog" aria-modal="true" aria-labelledby="mdo-ps-destination-title" tabindex="-1">
			<button type="button" class="mdo-ps-modal__close" data-mdo-ps-destination-close aria-label="<?php echo esc_attr( mdo_ps_safe_text_20260821( 'Cerrar', 'Close' ) ); ?>">×</button>
			<h2 id="mdo-ps-destination-title"><?php echo esc_html( mdo_ps_safe_text_20260821( '¿Dónde quieres recibir tu pedido?', 'Where do you want to receive your order?' ) ); ?></h2>
			<p><?php echo esc_html( mdo_ps_safe_text_20260821( 'Mostramos solo los productos de este productor que pueden enviarse al destino elegido.', 'We only show this producer’s products that can be shipped to your selected destination.' ) ); ?></p>
			<form data-mdo-ps-destination-form>
				<label for="mdo-ps-country"><?php echo esc_html( mdo_ps_safe_text_20260821( 'País', 'Country' ) ); ?></label>
				<select id="mdo-ps-country" name="country" data-mdo-ps-country>
					<?php foreach ( $countries as $code => $country_label ) : ?>
						<option value="<?php echo esc_attr( (string) $code ); ?>" <?php selected( $destination['country'], (string) $code ); ?>><?php echo esc_html( (string) $country_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="mdo-ps-modal__postcode" data-mdo-ps-postcode-wrap <?php echo 'ES' === $destination['country'] ? '' : 'hidden'; ?>>
					<label for="mdo-ps-postcode"><?php echo esc_html( mdo_ps_safe_text_20260821( 'Código postal', 'Postcode' ) ); ?> <span><?php echo esc_html( mdo_ps_safe_text_20260821( '(opcional)', '(optional)' ) ); ?></span></label>
					<input id="mdo-ps-postcode" name="postcode" data-mdo-ps-postcode inputmode="numeric" maxlength="5" pattern="[0-9]{5}" value="<?php echo esc_attr( $destination['postcode'] ); ?>" placeholder="28001">
					<small><?php echo esc_html( mdo_ps_safe_text_20260821( 'Úsalo para ajustar Península, Baleares o Canarias. Actualmente no enviamos a Ceuta ni Melilla.', 'Use it to refine Mainland Spain, Balearic Islands or Canary Islands. We currently do not ship to Ceuta or Melilla.' ) ); ?></small>
				</div>
				<p class="mdo-ps-modal__error" data-mdo-ps-error hidden></p>
				<button type="submit" class="mdo-ps-modal__save" data-mdo-ps-save><?php echo esc_html( mdo_ps_safe_text_20260821( 'Guardar destino', 'Save destination' ) ); ?></button>
			</form>
		</section>
	</div>
	<style id="mdo-ps-destination-css">
		.mdo-ps-destination{display:inline-flex;align-items:center;margin:0 0 18px 12px;vertical-align:top}.mdo-ps-destination__trigger{display:inline-flex;min-height:38px;align-items:center;gap:7px;padding:0 13px;border:1px solid rgba(23,63,50,.17);border-radius:999px;background:#fff;color:#173f32;font:inherit;font-size:13px;line-height:1;cursor:pointer;box-shadow:0 1px 2px rgba(15,38,31,.03)}.mdo-ps-destination__trigger:hover,.mdo-ps-destination__trigger:focus-visible{border-color:rgba(23,63,50,.34);background:#fbfdfc;outline:none}.mdo-ps-destination__trigger strong{font-weight:750}.mdo-ps-modal[hidden]{display:none!important}.mdo-ps-modal{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px}.mdo-ps-modal__backdrop{position:absolute;inset:0;background:rgba(13,26,21,.46);backdrop-filter:blur(2px)}.mdo-ps-modal__panel{position:relative;z-index:1;width:min(100%,440px);padding:28px;border-radius:16px;background:#fff;color:#173f32;box-shadow:0 24px 70px rgba(0,0,0,.22)}.mdo-ps-modal__panel h2{margin:0 34px 8px 0;font-size:22px;line-height:1.25}.mdo-ps-modal__panel>p{margin:0 0 22px;color:#5e6f68;font-size:14px;line-height:1.5}.mdo-ps-modal__close{position:absolute;top:15px;right:15px;width:36px;height:36px;padding:0;border:0;border-radius:50%;background:#f3f6f4;color:#173f32;font-size:25px;line-height:34px;cursor:pointer}.mdo-ps-modal label{display:block;margin:0 0 7px;font-size:13px;font-weight:750;color:#284b40}.mdo-ps-modal label span{font-weight:500;color:#798680}.mdo-ps-modal select,.mdo-ps-modal input{box-sizing:border-box;width:100%;min-height:46px;margin:0;padding:0 13px;border:1px solid #d7dfdb;border-radius:9px;background:#fff;color:#173f32;font:inherit;font-size:15px}.mdo-ps-modal__postcode{margin-top:17px}.mdo-ps-modal__postcode[hidden]{display:none!important}.mdo-ps-modal__postcode small{display:block;margin-top:7px;color:#718078;font-size:12px;line-height:1.45}.mdo-ps-modal__error{margin:14px 0 0;padding:10px 12px;border-radius:8px;background:#fff4f2;color:#8f2d20;font-size:13px}.mdo-ps-modal__save{display:flex;width:100%;min-height:46px;align-items:center;justify-content:center;margin-top:20px;padding:0 18px;border:0;border-radius:9px;background:#173f32;color:#fff;font:inherit;font-size:14px;font-weight:800;cursor:pointer}.mdo-ps-modal__save[disabled]{opacity:.58;cursor:wait}body.mdo-ps-modal-open{overflow:hidden}@media(max-width:600px){.mdo-ps-destination{margin:0 0 14px 8px}.mdo-ps-modal{align-items:flex-end;padding:0}.mdo-ps-modal__panel{width:100%;padding:24px 20px 22px;border-radius:18px 18px 0 0}}
	</style>
	<script id="mdo-ps-destination-js">
	(() => {
		'use strict';
		let trigger = document.querySelector('[data-mdo-ps-destination-open]');
		const fallback = document.querySelector('[data-mdo-ps-destination-fallback]');
		if (!trigger && fallback) {
			fallback.hidden = false;
			const anchor = document.querySelector('.woocommerce-no-products-found, .woocommerce-info, .woocommerce-result-count, .woocommerce-ordering, .wcfmmp_store_info, main#main, .site-main');
			if (anchor && anchor.parentNode) anchor.parentNode.insertBefore(fallback, anchor);
			trigger = fallback.querySelector('[data-mdo-ps-destination-open]');
		}
		const modal = document.getElementById('mdo-ps-destination-dialog');
		if (!trigger || !modal) return;
		const panel = modal.querySelector('.mdo-ps-modal__panel');
		const form = modal.querySelector('[data-mdo-ps-destination-form]');
		const country = modal.querySelector('[data-mdo-ps-country]');
		const postcodeWrap = modal.querySelector('[data-mdo-ps-postcode-wrap]');
		const postcode = modal.querySelector('[data-mdo-ps-postcode]');
		const error = modal.querySelector('[data-mdo-ps-error]');
		const save = modal.querySelector('[data-mdo-ps-save]');
		let returnFocus = null;
		const syncPostcode = () => { const es = country.value === 'ES'; postcodeWrap.hidden = !es; postcode.disabled = !es; if (!es) postcode.value = ''; };
		const open = () => { returnFocus = document.activeElement; modal.hidden = false; modal.setAttribute('aria-hidden','false'); document.body.classList.add('mdo-ps-modal-open'); syncPostcode(); setTimeout(() => panel.focus({preventScroll:true}), 0); };
		const close = () => { modal.hidden = true; modal.setAttribute('aria-hidden','true'); document.body.classList.remove('mdo-ps-modal-open'); error.hidden = true; if (returnFocus && returnFocus.focus) returnFocus.focus({preventScroll:true}); };
		trigger.addEventListener('click', open);
		modal.querySelectorAll('[data-mdo-ps-destination-close]').forEach((el) => el.addEventListener('click', close));
		country.addEventListener('change', syncPostcode);
		document.addEventListener('keydown', (event) => { if (!modal.hidden && event.key === 'Escape') close(); });
		form.addEventListener('submit', async (event) => {
			event.preventDefault(); error.hidden = true;
			if (country.value === 'ES' && postcode.value && !/^\d{5}$/.test(postcode.value.trim())) { error.textContent = <?php echo wp_json_encode( mdo_ps_safe_text_20260821( 'Introduce un código postal español de 5 cifras o déjalo vacío.', 'Enter a 5-digit Spanish postcode or leave it blank.' ) ); ?>; error.hidden = false; postcode.focus(); return; }
			save.disabled = true; save.textContent = <?php echo wp_json_encode( mdo_ps_safe_text_20260821( 'Guardando…', 'Saving…' ) ); ?>;
			try {
				const body = new URLSearchParams({action:'mdo_set_shipping_destination', nonce:<?php echo wp_json_encode( $nonce ); ?>, country:country.value, postcode:country.value === 'ES' ? postcode.value.trim() : ''});
				const response = await fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, body:body.toString()});
				const payload = await response.json();
				if (!response.ok || !payload.success) throw new Error(payload?.data?.message || <?php echo wp_json_encode( mdo_ps_safe_text_20260821( 'No se pudo guardar el destino.', 'The destination could not be saved.' ) ); ?>);
				window.location.reload();
			} catch (exception) {
				error.textContent = exception?.message || <?php echo wp_json_encode( mdo_ps_safe_text_20260821( 'No se pudo guardar el destino.', 'The destination could not be saved.' ) ); ?>; error.hidden = false; save.disabled = false; save.textContent = <?php echo wp_json_encode( mdo_ps_safe_text_20260821( 'Guardar destino', 'Save destination' ) ); ?>;
			}
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mdo_ps_safe_footer_20260821', PHP_INT_MAX );

add_action(
	'template_redirect',
	static function (): void {
		if ( ! mdo_ps_safe_is_store_20260821() ) {
			return;
		}
		$destination = mdo_ps_safe_destination_20260821();
		if ( 'ES' === $destination['country'] && '' === $destination['postcode'] ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	},
	1
);
