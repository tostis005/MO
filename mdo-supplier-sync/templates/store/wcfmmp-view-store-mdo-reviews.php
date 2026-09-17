<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'MDO_Reviews_Public' ) ) {
	MDO_Reviews_Public::render_current_store();
}

if ( class_exists( 'MDO_Reviews_Submission' ) ) {
	$store_url = (string) get_option( 'wcfm_store_url', 'store' );
	$store_name = apply_filters( 'wcfmmp_store_query_var', get_query_var( $store_url ) );
	$store_user = is_string( $store_name ) && '' !== $store_name ? get_user_by( 'slug', $store_name ) : false;
	if ( $store_user ) {
		MDO_Reviews_Submission::render_form( (int) $store_user->ID );
	}
}
