<?php
/**
 * Plugin Name: MDO Merchant Feeds (Google + OpenAI Commerce)
 * Description: Google Merchant country feeds plus the modular OpenAI/ChatGPT Product Discovery full-snapshot integration.
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$base = __DIR__ . '/mdo-google-merchant/';
require_once $base . 'core.php';
require_once $base . 'product.php';
require_once $base . 'feed.php';
require_once $base . 'admin.php';

$openai = $base . 'openai/';
require_once $openai . 'core.php';
require_once $openai . 'transport.php';
require_once $openai . 'admin.php';
