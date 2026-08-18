<?php
/**
 * Plugin Name: EMDO Instagram Status
 * Description: Public, read-only status endpoint for the internal Instagram publisher.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'emdo-instagram/v1',
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					$account = get_option( 'emdo_ig_account', array() );
					$log     = get_option( 'emdo_ig_publish_log', array() );
					$last    = is_array( $log ) && ! empty( $log ) && is_array( $log[0] ) ? $log[0] : array();

					return rest_ensure_response(
						array(
							'connected' => is_array( $account ) && ! empty( $account['id'] ),
							'username'  => is_array( $account ) && ! empty( $account['username'] ) ? sanitize_text_field( (string) $account['username'] ) : '',
							'last'      => empty( $last ) ? null : array(
								'media_id'     => isset( $last['media_id'] ) ? sanitize_text_field( (string) $last['media_id'] ) : '',
								'published_at' => isset( $last['published_at'] ) ? sanitize_text_field( (string) $last['published_at'] ) : '',
								'image_url'    => isset( $last['image_url'] ) ? esc_url_raw( (string) $last['image_url'] ) : '',
								'caption'      => isset( $last['caption'] ) ? wp_strip_all_tags( (string) $last['caption'] ) : '',
							),
						)
					);
				},
			)
		);
	}
);
