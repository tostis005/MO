<?php
/**
 * Plugin Name: EMDO Out-of-stock Product SEO
 * Description: Documents that EMDO respects WooCommerce's native hide-out-of-stock policy. No visibility override is applied.
 * Version: 2026.08.21.3
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
 * Intentionally no filters here.
 *
 * When WooCommerce > Products > Inventory > "Hide out of stock items from the
 * catalog" is enabled, EMDO keeps those products out of the public storefront.
 * The dynamic sitemap applies the same option so search engines do not receive
 * URLs that the storefront deliberately resolves as unavailable/404.
 */
