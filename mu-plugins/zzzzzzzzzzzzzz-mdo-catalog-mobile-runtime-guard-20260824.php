<?php
/**
 * Plugin Name: MDO Catalog Mobile Runtime Guard
 * Description: Keeps mobile catalogue controls at stable equal width after late WCFM mutations and localizes destination country names on Spanish catalogue pages.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_mobile_runtime_guard_surface_20260824(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_catalog_top_controls_arrow_final_surface_20260824' ) ) {
		return (bool) mdo_catalog_top_controls_arrow_final_surface_20260824();
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_final_is_surface_20260824' ) ) {
		return (bool) mdo_catalog_top_controls_parity_final_is_surface_20260824();
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
		return true;
	}
	return function_exists( 'is_shop' ) && is_shop();
}

function mdo_catalog_mobile_runtime_guard_is_english_20260824(): bool {
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

/** @return array<string,string> */
function mdo_catalog_mobile_runtime_guard_spanish_countries_20260824(): array {
	$labels = array();
	if ( function_exists( 'WC' ) && WC() && isset( WC()->countries ) && is_callable( array( WC()->countries, 'get_countries' ) ) ) {
		$labels = (array) WC()->countries->get_countries();
	}

	/* These are the currently supported public destinations. Explicit Spanish
	 * names also protect the catalogue if WooCommerce's locale was not switched. */
	$labels = array_replace(
		$labels,
		array(
			'ES' => 'España',
			'DE' => 'Alemania',
			'AT' => 'Austria',
			'BE' => 'Bélgica',
			'BG' => 'Bulgaria',
			'FR' => 'Francia',
			'GR' => 'Grecia',
			'HU' => 'Hungría',
			'IT' => 'Italia',
			'LU' => 'Luxemburgo',
			'NL' => 'Países Bajos',
			'PL' => 'Polonia',
			'PT' => 'Portugal',
			'CZ' => 'Chequia',
			'SE' => 'Suecia',
			'CH' => 'Suiza',
		)
	);
	return $labels;
}

function mdo_catalog_mobile_runtime_guard_output_20260824(): void {
	if ( ! mdo_catalog_mobile_runtime_guard_surface_20260824() ) {
		return;
	}
	$country_labels = mdo_catalog_mobile_runtime_guard_is_english_20260824()
		? array()
		: mdo_catalog_mobile_runtime_guard_spanish_countries_20260824();
	?>
	<script id="mdo-catalog-mobile-runtime-guard-20260824">
	(() => {
		'use strict';
		const countryLabels = <?php echo wp_json_encode( $country_labels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?> || {};
		let mutationObserver = null;
		let resizeObserver = null;
		let watchedForm = null;
		let watchedDestination = null;
		let raf = 0;

		const isMobile = () => window.matchMedia('(max-width:767px)').matches;
		const setImportant = (el, name, value) => {
			if (!el?.style) return;
			if (el.style.getPropertyValue(name) === value && el.style.getPropertyPriority(name) === 'important') return;
			el.style.setProperty(name, value, 'important');
		};

		const localizeCountries = () => {
			if (!Object.keys(countryLabels).length) return;
			document.querySelectorAll('[data-mdo-destination-country],[data-mdo-ps-country]').forEach(select => {
				[...select.options].forEach(option => {
					const label = countryLabels[String(option.value || '').toUpperCase()];
					if (label && option.textContent.trim() !== label) option.textContent = label;
				});
				const producer = select.hasAttribute('data-mdo-ps-country');
				const trigger = document.querySelector(producer ? '[data-mdo-ps-destination-open]' : '[data-mdo-destination-open]');
				const strong = trigger?.querySelector('strong');
				const label = countryLabels[String(select.value || '').toUpperCase()];
				if (!strong || !label) return;
				const previous = strong.textContent.trim();
				const suffix = previous.match(/\s·\s.*$/)?.[0] || '';
				const wanted = `${label}${suffix}`;
				if (strong.textContent !== wanted) strong.textContent = wanted;
			});
		};

		const enforce = () => {
			localizeCountries();
			if (!isMobile()) return false;
			const toolbar = document.querySelector('.emo-catalog-toolbar-shared-010229');
			const form = toolbar?.querySelector('.woocommerce-ordering');
			const select = form?.querySelector('select[name="orderby"]');
			const destination = toolbar?.querySelector('[data-mdo-destination-open],[data-mdo-ps-destination-open]');
			const destinationWrap = destination?.closest('.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination') || destination?.parentElement;
			if (!toolbar || !form || !select || !destination || !destinationWrap) return false;

			const destRect = destinationWrap.getBoundingClientRect();
			if (!destRect.width) return false;
			const width = `${destRect.width}px`;

			setImportant(form, 'position', 'relative');
			setImportant(form, 'box-sizing', 'border-box');
			setImportant(form, 'transform', 'none');
			setImportant(form, 'width', width);
			setImportant(form, 'min-width', width);
			setImportant(form, 'max-width', width);
			setImportant(form, 'flex', `0 0 ${width}`);
			setImportant(form, 'margin-left', '0px');
			setImportant(form, 'margin-right', '0px');
			setImportant(form, 'left', '0px');
			const formRect = form.getBoundingClientRect();
			const leftDelta = destRect.left - formRect.left;
			if (Math.abs(leftDelta) > 0.25) setImportant(form, 'left', `${leftDelta}px`);

			setImportant(select, 'box-sizing', 'border-box');
			setImportant(select, 'width', '100%');
			setImportant(select, 'min-width', '0px');
			setImportant(select, 'max-width', '100%');
			setImportant(select, 'padding-left', '13px');
			setImportant(select, 'padding-right', '36px');
			setImportant(select, 'text-align', 'left');
			setImportant(select, 'text-align-last', 'left');
			const destinationText = destination.querySelector(':scope > span') || destination.querySelector('span');
			setImportant(destination, 'text-align', 'left');
			setImportant(destinationText, 'text-align', 'left');
			toolbar.dataset.mdoCatalogRuntimeGuard = '20260824-v2';
			return true;
		};

		const schedule = () => {
			if (raf) return;
			raf = requestAnimationFrame(() => {
				raf = 0;
				enforce();
				watch();
			});
		};

		const watch = () => {
			if (!isMobile()) return;
			const toolbar = document.querySelector('.emo-catalog-toolbar-shared-010229');
			const form = toolbar?.querySelector('.woocommerce-ordering');
			const destination = toolbar?.querySelector('[data-mdo-destination-open],[data-mdo-ps-destination-open]');
			const destinationWrap = destination?.closest('.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination') || destination?.parentElement;
			if (!toolbar || !form || !destinationWrap) return;
			if (watchedForm === form && watchedDestination === destinationWrap && mutationObserver) return;

			mutationObserver?.disconnect();
			resizeObserver?.disconnect();
			watchedForm = form;
			watchedDestination = destinationWrap;
			mutationObserver = new MutationObserver(schedule);
			mutationObserver.observe(form, {attributes:true, attributeFilter:['style','class'], subtree:true});
			mutationObserver.observe(toolbar, {childList:true, subtree:true});
			if ('ResizeObserver' in window) {
				resizeObserver = new ResizeObserver(schedule);
				resizeObserver.observe(form);
				resizeObserver.observe(destinationWrap);
			}
		};

		enforce();
		watch();
		requestAnimationFrame(() => requestAnimationFrame(schedule));
		window.setTimeout(schedule, 250);
		window.setTimeout(schedule, 1200);
		window.setTimeout(schedule, 3500);
		window.addEventListener('DOMContentLoaded', schedule, {once:true});
		window.addEventListener('load', schedule, {once:true});
		window.addEventListener('pageshow', schedule, {passive:true});
		window.addEventListener('resize', schedule, {passive:true});
		window.addEventListener('orientationchange', schedule, {passive:true});
		document.addEventListener('visibilitychange', () => { if (!document.hidden) schedule(); }, {passive:true});
	})();
	</script>
	<?php
}

add_action( 'wp_footer', 'mdo_catalog_mobile_runtime_guard_output_20260824', PHP_INT_MAX );
