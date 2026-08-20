<?php
/**
 * Plugin Name: MDO Catalog Destination Modal Footer (Retired)
 * Description: Compatibility placeholder. The canonical destination trigger now remains server-rendered inside Woostify's catalogue toolbar.
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Intentionally no hooks.
 *
 * The previous version removed mdo_catalog_default_spain_render_20260820 from
 * woocommerce_before_shop_loop (priority 22) and rendered the whole control in
 * wp_footer. That made both the trigger and dialog direct children of <body>,
 * which is why "Envío a …" appeared outside the white ordering toolbar.
 *
 * The canonical renderer now stays at priority 22, between Woostify's toolbar
 * left open (15) and close (25). The dialog may remain a fixed descendant while
 * hidden/open; no client-side DOM relocation is required.
 */
