<?php
/**
 * Vendor-store destination controls parity 0.10.270.
 *
 * IMPORTANT: ordering is intentionally not handled here anymore. The old
 * ordering repair observer fought with the dedicated mobile ordering control
 * and could resurrect an invisible/native select over the real button.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp',
	static function (): void {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		add_action(
			'wp_head',
			static function (): void {
				?>
				<style id="elmercado-vendor-catalog-controls-010270">
					body.wcfmmp-store-page [data-mdo-toolbar-destination],
					body.wcfmmp-store-page .mdo-catalog-toolbar__destination {
						position: relative !important;
						z-index: 2 !important;
						display: inline-flex !important;
						min-height: 44px !important;
						align-items: center !important;
						justify-content: center !important;
						gap: 6px !important;
						padding-top: 0 !important;
						padding-bottom: 0 !important;
						overflow: visible !important;
						line-height: 1.3 !important;
						vertical-align: middle !important;
						pointer-events: auto !important;
						touch-action: manipulation !important;
					}

					body.wcfmmp-store-page [data-mdo-toolbar-destination] > span,
					body.wcfmmp-store-page .mdo-catalog-toolbar__destination > span {
						display: inline-flex !important;
						min-height: 20px !important;
						align-items: center !important;
						padding: 2px 0 3px !important;
						overflow: visible !important;
						line-height: 1.3 !important;
						white-space: nowrap !important;
					}

					body.wcfmmp-store-page [data-mdo-toolbar-destination] svg,
					body.wcfmmp-store-page .mdo-catalog-toolbar__destination svg {
						flex: 0 0 auto !important;
						pointer-events: none !important;
					}

					body.wcfmmp-store-page .mdo-destination-modal[hidden],
					body.wcfmmp-store-page .mdo-destination-modal[aria-hidden="true"] {
						display: none !important;
						visibility: hidden !important;
						opacity: 0 !important;
						pointer-events: none !important;
					}

					body.wcfmmp-store-page .mdo-destination-modal:not([hidden])[aria-hidden="false"] {
						display: flex !important;
						position: fixed !important;
						inset: 0 !important;
						z-index: 1000000 !important;
						width: 100vw !important;
						height: 100vh !important;
						height: 100dvh !important;
						min-width: 100vw !important;
						min-height: 100vh !important;
						min-height: 100dvh !important;
						max-width: none !important;
						max-height: none !important;
						visibility: visible !important;
						opacity: 1 !important;
						pointer-events: auto !important;
					}

					body.wcfmmp-store-page .mdo-destination-modal [data-mdo-destination-close],
					body.wcfmmp-store-page .mdo-destination-modal .mdo-destination-modal__close,
					body.wcfmmp-store-page .mdo-destination-modal .mdo-destination-close {
						display: inline-flex !important;
						width: 36px !important;
						height: 36px !important;
						min-width: 36px !important;
						min-height: 36px !important;
						align-items: center !important;
						justify-content: center !important;
						padding: 0 !important;
						background: #173f32 !important;
						border: 1px solid #173f32 !important;
						border-radius: 999px !important;
						box-shadow: none !important;
						color: #fff !important;
						font-size: 20px !important;
						line-height: 1 !important;
						cursor: pointer !important;
					}

					body.wcfmmp-store-page .mdo-destination-modal [data-mdo-destination-close] svg,
					body.wcfmmp-store-page .mdo-destination-modal .mdo-destination-modal__close svg,
					body.wcfmmp-store-page .mdo-destination-modal .mdo-destination-close svg {
						color: inherit !important;
						fill: currentColor !important;
						stroke: currentColor !important;
						pointer-events: none !important;
					}
				</style>
				<?php
			},
			PHP_INT_MAX
		);

		add_action(
			'wp_footer',
			static function (): void {
				?>
				<script id="elmercado-vendor-catalog-controls-script-010270">
				(() => {
					'use strict';
					if (!document.body || !document.body.classList.contains('wcfmmp-store-page')) return;

					const important = (node, name, value) => node?.style?.setProperty(name, value, 'important');
					const clear = (node, ...names) => names.forEach(name => node?.style?.removeProperty(name));

					const repairDestination = () => {
						document.querySelectorAll('[data-mdo-toolbar-destination], .mdo-catalog-toolbar__destination').forEach(button => {
							important(button, 'overflow', 'visible');
							important(button, 'line-height', '1.3');
							important(button, 'pointer-events', 'auto');
							button.querySelectorAll('span').forEach(span => {
								important(span, 'overflow', 'visible');
								important(span, 'line-height', '1.3');
							});
						});
					};

					const syncModal = modal => {
						const open = !modal.hasAttribute('hidden') && modal.getAttribute('aria-hidden') === 'false';
						if (open) {
							important(modal, 'visibility', 'visible');
							important(modal, 'opacity', '1');
							important(modal, 'pointer-events', 'auto');
							important(modal, 'width', '100vw');
							important(modal, 'height', '100dvh');
							important(modal, 'min-width', '100vw');
							important(modal, 'min-height', '100dvh');
							important(modal, 'max-width', 'none');
							important(modal, 'max-height', 'none');
						} else {
							clear(modal, 'visibility', 'opacity', 'pointer-events', 'width', 'height', 'min-width', 'min-height', 'max-width', 'max-height');
						}
					};

					const repair = () => {
						repairDestination();
						document.querySelectorAll('.mdo-destination-modal, [data-mdo-destination-modal]').forEach(syncModal);
					};

					repair();
					window.addEventListener('load', repair, { once: true });

					new MutationObserver(mutations => {
						let needsRepair = false;
						for (const mutation of mutations) {
							if (mutation.type === 'childList') needsRepair = true;
							if (mutation.type === 'attributes' && mutation.target instanceof Element && mutation.target.matches('.mdo-destination-modal, [data-mdo-destination-modal]')) syncModal(mutation.target);
						}
						if (needsRepair) requestAnimationFrame(repair);
					}).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden', 'aria-hidden', 'class'] });
				})();
				</script>
				<?php
			},
			PHP_INT_MAX
		);
	},
	PHP_INT_MAX
);
