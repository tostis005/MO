<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }

if ( class_exists( 'Elementor\\Plugin' ) ) {
    try {
        $plugin = \Elementor\Plugin::$instance;
        if ( $plugin && isset( $plugin->files_manager ) ) {
            $plugin->files_manager->clear_cache();
        }
    } catch ( Throwable $e ) {}
}

if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
if ( function_exists( 'wp_cache_clear_cache' ) ) { wp_cache_clear_cache(); }
if ( function_exists( 'w3tc_flush_all' ) ) { w3tc_flush_all(); }
do_action( 'litespeed_purge_all' );
do_action( 'autoptimize_action_cachepurged' );
wp_cache_flush();

echo "home_cache_purged\n";
