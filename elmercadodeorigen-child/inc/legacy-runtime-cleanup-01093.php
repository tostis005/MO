<?php
/**
 * Utilidades heredadas que siguen siendo necesarias, sin observadores globales.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function (): void {
		remove_action( 'wp_enqueue_scripts', 'wpcf7_recaptcha_enqueue_scripts', 20 );
		remove_filter( 'wpcf7_form_hidden_fields', 'wpcf7_recaptcha_add_hidden_fields', 100 );
		remove_filter( 'wpcf7_spam', 'wpcf7_recaptcha_verify_response', 9 );
	},
	100
);

add_filter(
	'wpcf7_form_elements',
	static function ( string $content ): string {
		return $content
			. '<div class="emo-form-honeypot" aria-hidden="true"><label>No completar este campo<input type="text" name="_emo_website" value="" tabindex="-1" autocomplete="off"></label></div>'
			. '<input type="hidden" name="_emo_started" value="' . esc_attr( (string) time() ) . '">';
	}
);

add_filter(
	'wpcf7_spam',
	static function ( bool $spam, $submission ): bool {
		if ( $spam ) {
			return true;
		}
		$honeypot = isset( $_POST['_emo_website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['_emo_website'] ) ) ) : '';
		$started  = isset( $_POST['_emo_started'] ) ? absint( $_POST['_emo_started'] ) : 0;
		if ( '' === $honeypot && ( 0 === $started || ( time() - $started ) >= 2 ) ) {
			return false;
		}
		if ( is_object( $submission ) && method_exists( $submission, 'add_spam_log' ) ) {
			$submission->add_spam_log(
				array(
					'agent'  => 'elmercado-honeypot',
					'reason' => 'La comprobación antispam ligera no se superó.',
				)
			);
		}
		return true;
	},
	8,
	2
);

if ( ! function_exists( 'elmercado_is_wishlist_screen' ) ) {
	function elmercado_is_wishlist_screen(): bool {
		return function_exists( 'yith_wcwl_is_wishlist_page' ) && yith_wcwl_is_wishlist_page();
	}
}

function elmercado_legacy_runtime_fragments(): array {
	$fragments = array(
		'/plugins/waitlist-woocommerce/',
		'/plugins/contact-form-7/modules/recaptcha/',
		'google.com/recaptcha/api.js',
		'recaptcha.net/recaptcha/api.js',
	);
	if ( ! elmercado_is_wishlist_screen() ) {
		$fragments[] = '/plugins/yith-woocommerce-wishlist/';
	}
	return $fragments;
}

function elmercado_legacy_runtime_remove_assets(): void {
	if ( is_admin() ) {
		return;
	}
	global $wp_styles, $wp_scripts;
	$style_handles  = array( 'xoo-wl-style', 'xoo-wl-fonts', 'yith-wcwl-main', 'yith-wcwl-font-awesome' );
	$script_handles = array( 'google-recaptcha', 'wpcf7-recaptcha', 'xoo-wl-js', 'jquery-yith-wcwl', 'yith-wcwl' );
	foreach ( $style_handles as $handle ) {
		if ( ! elmercado_is_wishlist_screen() || ! str_contains( $handle, 'yith' ) ) wp_dequeue_style( $handle );
	}
	foreach ( $script_handles as $handle ) {
		if ( ! elmercado_is_wishlist_screen() || ! str_contains( $handle, 'yith' ) ) wp_dequeue_script( $handle );
	}
	$fragments = elmercado_legacy_runtime_fragments();
	if ( $wp_styles instanceof WP_Styles ) {
		foreach ( $wp_styles->registered as $handle => $style ) {
			$source = isset( $style->src ) ? (string) $style->src : '';
			foreach ( $fragments as $fragment ) {
				if ( $source && str_contains( $source, $fragment ) ) { wp_dequeue_style( (string) $handle ); break; }
			}
		}
	}
	if ( $wp_scripts instanceof WP_Scripts ) {
		foreach ( $wp_scripts->registered as $handle => $script ) {
			$source = isset( $script->src ) ? (string) $script->src : '';
			foreach ( $fragments as $fragment ) {
				if ( $source && str_contains( $source, $fragment ) ) { wp_dequeue_script( (string) $handle ); break; }
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'elmercado_legacy_runtime_remove_assets', PHP_INT_MAX );

add_filter(
	'do_shortcode_tag',
	static function ( string $output, string $tag ): string {
		return in_array( $tag, array( 'xoo_wl_form', 'yith_wcwl_add_to_wishlist' ), true ) ? '' : $output;
	},
	20,
	2
);

add_filter(
	'style_loader_tag',
	static function ( string $html, string $handle, string $href ): string {
		foreach ( elmercado_legacy_runtime_fragments() as $fragment ) {
			if ( str_contains( $href, $fragment ) ) return '';
		}
		return $html;
	},
	PHP_INT_MAX,
	3
);

add_filter(
	'script_loader_tag',
	static function ( string $html, string $handle, string $src ): string {
		foreach ( elmercado_legacy_runtime_fragments() as $fragment ) {
			if ( str_contains( $src, $fragment ) ) return '';
		}
		return $html;
	},
	PHP_INT_MAX,
	3
);

function elmercado_legacy_runtime_output( string $html ): string {
	if ( '' === $html ) return $html;
	foreach ( elmercado_legacy_runtime_fragments() as $fragment ) {
		$quoted = preg_quote( $fragment, '/' );
		$html   = (string) preg_replace( '/<link\b[^>]*' . $quoted . '[^>]*>/i', '', $html );
		$html   = (string) preg_replace( '/<script\b[^>]*' . $quoted . '[^>]*>\s*<\/script>/is', '', $html );
	}
	if ( is_front_page() ) {
		$html = str_replace(
			array( 'Productores y artesanos con nombre propio', 'Pago seguro y atención cercana', 'Envíos preparados desde el origen' ),
			array( 'Directamente de productores seleccionados', 'Compra segura y atención cercana', 'Envíos directos desde el productor' ),
			$html
		);
	}
	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || is_feed() || is_trackback() || wp_doing_ajax() ) return;
		ob_start( 'elmercado_legacy_runtime_output' );
	},
	-100
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) return;
		?>
		<style id="elmercado-legacy-runtime-cleanup-01093">
			.emo-form-honeypot,.grecaptcha-badge,.xoo-wl-form-wrapper,.xoo-wl-modal,.xoo-wl-inmodal,
			.xoo-wl-btn-container,.xoo-wl-waitlist-button,.xoo-wl-form,.xoo-wl-notice,.xoo-wl-added-to-waitlist,
			.yith-wcwl-add-to-wishlist,.yith-wcwl-add-button,a.add_to_wishlist{display:none!important}
		</style>
		<?php
	},
	PHP_INT_MAX
);
