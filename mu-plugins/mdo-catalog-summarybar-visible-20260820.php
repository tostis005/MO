<?php
/**
 * Plugin Name: MDO Catalog Toolbar Destination
 * Description: Places the exact result count and shipping destination inside the real Woostify ordering toolbar on every catalog viewport.
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_toolbar_destination_is_surface_20260820(): bool {
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

function mdo_catalog_toolbar_destination_text_20260820( string $es, string $en ): string {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return ( '/en' === $path || 0 === strpos( $path, '/en/' ) ) ? $en : $es;
}

function mdo_catalog_toolbar_destination_payload_20260820(): array {
	$total = function_exists( 'elmercado_catalog_exact_result_total_010220' )
		? max( 0, (int) elmercado_catalog_exact_result_total_010220() )
		: max( 0, (int) ( $GLOBALS['wp_query']->found_posts ?? 0 ) );

	$destination = class_exists( 'MDO_Catalog_Destination_Frontend' )
		? MDO_Catalog_Destination_Frontend::current_destination()
		: array( 'country' => 'ES', 'postcode' => '' );
	$countries = class_exists( 'MDO_Catalog_Destination_Frontend' )
		? (array) MDO_Catalog_Destination_Frontend::supported_countries()
		: array( 'ES' => mdo_catalog_toolbar_destination_text_20260820( 'España', 'Spain' ) );

	$country  = strtoupper( (string) ( $destination['country'] ?? 'ES' ) );
	$postcode = trim( (string) ( $destination['postcode'] ?? '' ) );
	$label    = (string) ( $countries[ $country ] ?? $country );
	if ( 'ES' === $country && '' !== $postcode ) {
		$label .= ' · ' . $postcode;
	}

	$result_label = sprintf(
		esc_html( _n( '%s resultado', '%s resultados', $total, 'elmercadodeorigen' ) ),
		number_format_i18n( $total )
	);

	return array(
		'results'      => $result_label,
		'shippingTo'   => mdo_catalog_toolbar_destination_text_20260820( 'Envío a', 'Shipping to' ),
		'destination'  => $label,
		'country'      => $country,
		'postcode'     => $postcode,
	);
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! mdo_catalog_toolbar_destination_is_surface_20260820() ) {
			return;
		}
		?>
		<style id="mdo-catalog-toolbar-destination-20260820">
			/* The historical controls stay in the DOM for their modal/query logic, but the real toolbar owns the visible UI. */
			body .emo-catalog-result-count-010220,
			body .mdo-catalog-destination--canonical,
			body .mdo-catalog-summarybar{display:none!important}

			body .mdo-catalog-toolbar-ready{
				display:flex!important;
				visibility:visible!important;
				opacity:1!important;
				align-items:center!important;
				gap:10px!important;
				flex-wrap:wrap!important;
			}
			body .mdo-catalog-toolbar__count{
				display:inline-flex!important;
				visibility:visible!important;
				opacity:1!important;
				align-items:center!important;
				margin:0!important;
				padding:0!important;
				color:#5b6964!important;
				font-size:13px!important;
				font-weight:500!important;
				line-height:34px!important;
				white-space:nowrap!important;
				float:none!important;
			}
			body .mdo-catalog-toolbar__destination{
				display:inline-flex!important;
				visibility:visible!important;
				opacity:1!important;
				align-items:center!important;
				justify-content:center!important;
				gap:6px!important;
				min-height:34px!important;
				margin:0!important;
				padding:0 11px!important;
				border:1px solid rgba(23,63,50,.18)!important;
				border-radius:999px!important;
				background:#fff!important;
				box-shadow:none!important;
				color:#173f32!important;
				font:inherit!important;
				font-size:13px!important;
				line-height:1!important;
				white-space:nowrap!important;
				cursor:pointer!important;
				float:none!important;
			}
			body .mdo-catalog-toolbar__destination:hover,
			body .mdo-catalog-toolbar__destination:focus-visible{
				border-color:rgba(23,63,50,.36)!important;
				background:#fbfdfc!important;
				outline:none!important;
			}
			body .mdo-catalog-toolbar__destination strong{font-weight:750!important}
			body .mdo-catalog-toolbar__pin,
			body .mdo-catalog-toolbar__chevron{display:block!important;flex:0 0 auto!important}
			body .mdo-catalog-toolbar__chevron{opacity:.65!important}
			body .mdo-catalog-toolbar-ready .woocommerce-ordering{
				display:flex!important;
				visibility:visible!important;
				opacity:1!important;
				float:none!important;
				margin-left:auto!important;
				margin-bottom:0!important;
			}
			@media(max-width:767px){
				body .mdo-catalog-toolbar-ready{gap:7px!important}
				body .mdo-catalog-toolbar__count{font-size:12px!important;line-height:32px!important}
				body .mdo-catalog-toolbar__destination{min-height:32px!important;padding:0 9px!important;font-size:12px!important}
				body .mdo-catalog-toolbar-ready .woocommerce-ordering{margin-left:auto!important}
			}
			@media(max-width:380px){
				body .mdo-catalog-toolbar-ready{column-gap:6px!important;row-gap:7px!important}
				body .mdo-catalog-toolbar-ready .woocommerce-ordering{margin-left:0!important}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_catalog_toolbar_destination_is_surface_20260820() ) {
			return;
		}
		$payload = mdo_catalog_toolbar_destination_payload_20260820();
		?>
		<script id="mdo-catalog-toolbar-destination-js-20260820">
		(() => {
			'use strict';
			const config = <?php echo wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
			let scheduled = false;

			const visible = el => {
				if (!el || !el.isConnected) return false;
				const cs = getComputedStyle(el);
				const r = el.getBoundingClientRect();
				return cs.display !== 'none' && cs.visibility !== 'hidden' && Number(cs.opacity || 1) > 0 && r.width > 0 && r.height > 0;
			};

			const findToolbar = () => {
				const exact = document.querySelector('.woostify-sorting.emo-catalog-toolbar-shared-010229');
				if (exact) return exact;
				const ordering = document.querySelector('.woocommerce-ordering');
				if (!ordering) return null;
				return ordering.closest('.woostify-sorting') || ordering.parentElement;
			};

			const openDestination = (button) => {
				const modal = document.querySelector('[data-mdo-destination-modal]');
				if (!modal) {
					const canonical = document.querySelector('.mdo-catalog-destination--canonical [data-mdo-destination-open]');
					if (canonical) canonical.click();
					return;
				}
				const panel = modal.querySelector('.mdo-destination-modal__panel');
				const country = modal.querySelector('[data-mdo-destination-country]');
				const postcodeWrap = modal.querySelector('[data-mdo-postcode-wrap]');
				const postcode = modal.querySelector('[data-mdo-destination-postcode]');
				if (country && postcodeWrap && postcode) {
					const es = country.value === 'ES';
					postcodeWrap.hidden = !es;
					postcode.disabled = !es;
				}
				modal.hidden = false;
				modal.setAttribute('aria-hidden', 'false');
				document.body.classList.add('mdo-destination-modal-open');
				button.setAttribute('aria-expanded', 'true');
				setTimeout(() => { if (panel) panel.focus({preventScroll:true}); }, 0);
			};

			const makeCount = () => {
				const el = document.createElement('span');
				el.className = 'mdo-catalog-toolbar__count';
				el.dataset.mdoToolbarCount = '';
				el.setAttribute('aria-live', 'polite');
				el.textContent = config.results;
				return el;
			};

			const makeDestination = () => {
				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'mdo-catalog-toolbar__destination';
				button.dataset.mdoToolbarDestination = '';
				button.dataset.mdoDestinationOpen = '';
				button.setAttribute('aria-haspopup', 'dialog');
				button.setAttribute('aria-controls', 'mdo-catalog-destination-dialog');
				button.setAttribute('aria-expanded', 'false');
				button.innerHTML = '<svg class="mdo-catalog-toolbar__pin" aria-hidden="true" viewBox="0 0 24 24" width="15" height="15"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Zm0-8.5A2.5 2.5 0 1 1 12 7a2.5 2.5 0 0 1 0 5.5Z" fill="currentColor"/></svg><span></span><svg class="mdo-catalog-toolbar__chevron" aria-hidden="true" viewBox="0 0 20 20" width="13" height="13"><path d="m5 7.5 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
				const label = button.querySelector('span');
				label.append(document.createTextNode(config.shippingTo + ' '));
				const strong = document.createElement('strong');
				strong.textContent = config.destination;
				label.append(strong);
				button.addEventListener('click', event => {
					event.preventDefault();
					openDestination(button);
				});
				return button;
			};

			const ensure = () => {
				const toolbar = findToolbar();
				if (!toolbar) return;
				const ordering = toolbar.querySelector('.woocommerce-ordering') || document.querySelector('.woocommerce-ordering');
				toolbar.classList.add('mdo-catalog-toolbar-ready');

				let count = toolbar.querySelector('[data-mdo-toolbar-count]');
				if (!count) count = makeCount();
				count.textContent = config.results;

				let destination = toolbar.querySelector('[data-mdo-toolbar-destination]');
				if (!destination) destination = makeDestination();

				const reference = ordering && ordering.parentElement === toolbar ? ordering : toolbar.firstChild;
				if (count.parentElement !== toolbar || (reference && count.nextSibling !== destination)) {
					toolbar.insertBefore(count, reference);
				}
				if (destination.parentElement !== toolbar || (ordering && destination.nextSibling !== ordering)) {
					toolbar.insertBefore(destination, ordering && ordering.parentElement === toolbar ? ordering : (count.nextSibling || null));
				}
			};

			const schedule = () => {
				if (scheduled) return;
				scheduled = true;
				requestAnimationFrame(() => { scheduled = false; ensure(); });
			};

			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ensure, {once:true});
			else ensure();
			window.addEventListener('load', ensure, {once:true});
			const observer = new MutationObserver(schedule);
			if (document.body) observer.observe(document.body, {childList:true, subtree:true});

			document.addEventListener('click', event => {
				if (event.target.closest('[data-mdo-destination-close]')) {
					document.querySelectorAll('[data-mdo-toolbar-destination]').forEach(button => button.setAttribute('aria-expanded', 'false'));
				}
			}, true);

			window.__mdoCatalogToolbarVerify = () => ({
				toolbar: !!findToolbar(),
				countVisible: visible(document.querySelector('[data-mdo-toolbar-count]')),
				destinationVisible: visible(document.querySelector('[data-mdo-toolbar-destination]')),
				orderingVisible: visible(document.querySelector('.woocommerce-ordering'))
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
