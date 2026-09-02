<?php
/**
 * Plugin Name: EMDO Authority Redirects 2026-09-02
 * Description: Permanent redirects created by the authority/cannibalization cleanup.
 */
if (!defined('ABSPATH')) { exit; }
add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax()) return;
    $map = get_option('emdo_authority_redirects_20260902', array());
    if (!is_array($map) || !$map) return;
    $uri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
    $path = (string)wp_parse_url($uri, PHP_URL_PATH);
    $key = trim(rawurldecode($path), '/');
    if ($key === '' || !isset($map[$key])) return;
    $target = trim((string)$map[$key], '/');
    if ($target === '' || $target === $key) return;
    wp_safe_redirect(home_url('/'.$target.'/'), 301, 'EMDO authority cleanup');
    exit;
}, 0);
