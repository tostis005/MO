<?php
/**
 * Plugin Name: MDO Google Merchant Country Feeds
 * Description: Live Google Merchant XML feeds per shipping country, derived from EMDO/WCFM public catalogue and vendor shipping rules.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$base = __DIR__ . '/mdo-google-merchant/';
require_once $base . 'core.php';
require_once $base . 'product.php';
require_once $base . 'feed.php';
require_once $base . 'admin.php';
