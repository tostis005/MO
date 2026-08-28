<?php
/**
 * Plugin Name: MDO Catalogue Destination Grid Slots 2026-08-28
 * Description: CSS-only slot ownership for destination pin, label and chevron.
 * Version: 1.0.1
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_destination_grid_slots_rules_20260828(): void {
	?>
	html body button[data-mdo-destination-open][data-mdo-destination-open],
	html body button[data-mdo-ps-destination-open][data-mdo-ps-destination-open],
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-destination-open][data-mdo-destination-open],
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] {
		display:grid !important;
		grid-template-columns:14px minmax(0,1fr) 12px !important;
		grid-template-rows:minmax(0,1fr) !important;
		grid-template-areas:"mdo-pin mdo-label mdo-arrow" !important;
		column-gap:8px !important;
		align-items:center !important;
	}

	html body button[data-mdo-destination-open][data-mdo-destination-open]::before,
	html body button[data-mdo-ps-destination-open][data-mdo-ps-destination-open]::before,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-destination-open][data-mdo-destination-open]::before,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open]::before {
		grid-area:mdo-pin !important;
		grid-column:1 !important;
		grid-row:1 !important;
		position:static !important;
		align-self:center !important;
		justify-self:start !important;
		margin:0 !important;
		transform:none !important;
	}

	html body button[data-mdo-destination-open][data-mdo-destination-open] > span,
	html body button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] > span,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-destination-open][data-mdo-destination-open] > span,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open] > span {
		grid-area:mdo-label !important;
		grid-column:2 !important;
		grid-row:1 !important;
		align-self:center !important;
		justify-self:stretch !important;
		margin:0 !important;
	}

	html body button[data-mdo-destination-open][data-mdo-destination-open]::after,
	html body button[data-mdo-ps-destination-open][data-mdo-ps-destination-open]::after,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-destination-open][data-mdo-destination-open]::after,
	html body #mdo-catalog-parity-final-20260824 button[data-mdo-ps-destination-open][data-mdo-ps-destination-open]::after {
		grid-area:mdo-arrow !important;
		grid-column:3 !important;
		grid-row:1 !important;
		position:static !important;
		top:auto !important;
		right:auto !important;
		bottom:auto !important;
		left:auto !important;
		align-self:center !important;
		justify-self:end !important;
		margin:0 !important;
		transform:none !important;
	}
	<?php
}

function mdo_catalog_destination_grid_slots_head_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-destination-grid-slots-critical-20260828">
	<?php mdo_catalog_destination_grid_slots_rules_20260828(); ?>
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_catalog_destination_grid_slots_head_20260828', PHP_INT_MAX );

function mdo_catalog_destination_grid_slots_footer_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-destination-grid-slots-final-20260828">
	<?php mdo_catalog_destination_grid_slots_rules_20260828(); ?>
	</style>
	<?php
}
add_action(
	'wp_footer',
	static function (): void {
		add_action( 'wp_footer', 'mdo_catalog_destination_grid_slots_footer_20260828', PHP_INT_MAX );
	},
	PHP_INT_MAX - 1
);
