<?php
/**
 * Vendor mobile ordering touch activation 0.10.271.
 *
 * Ensures a real touch/pointer interaction reaches the native WooCommerce
 * ordering select in WCFM producer stores without cancelling its default picker.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-vendor-ordering-touch-fix-010271">
		(() => {
			'use strict';
			if (!document.body || !document.body.classList.contains('wcfmmp-store-page')) return;

			const selector = '.emo-catalog-toolbar-shared-010229 .woocommerce-ordering select.orderby, .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select';

			const bind = select => {
				if (!(select instanceof HTMLSelectElement) || select.dataset.mdoTouchFix010271 === '1') return;
				select.dataset.mdoTouchFix010271 = '1';
				select.disabled = false;
				select.style.setProperty('pointer-events', 'auto', 'important');
				select.style.setProperty('touch-action', 'manipulation', 'important');
				select.style.setProperty('position', 'relative', 'important');
				select.style.setProperty('z-index', '20', 'important');

				const focusNative = () => {
					try {
						select.focus({ preventScroll: true });
					} catch (_) {
						try { select.focus(); } catch (_) {}
					}
				};

				// Do not preventDefault: the browser must retain its native picker action.
				select.addEventListener('pointerdown', focusNative, { passive: true });
				select.addEventListener('touchstart', focusNative, { passive: true });
				select.addEventListener('mousedown', focusNative, { passive: true });

				select.addEventListener('click', event => {
					focusNative();
					// Chromium/modern engines expose showPicker(). Calling it from the
					// trusted user click is a safe fallback; unsupported engines keep
					// their normal native click behaviour.
					if (event.isTrusted && typeof select.showPicker === 'function') {
						try { select.showPicker(); } catch (_) {}
					}
				}, { passive: true });
			};

			const repair = () => document.querySelectorAll(selector).forEach(bind);
			repair();
			window.addEventListener('load', repair, { once: true });

			new MutationObserver(mutations => {
				if (mutations.some(m => m.type === 'childList')) requestAnimationFrame(repair);
			}).observe(document.body, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
