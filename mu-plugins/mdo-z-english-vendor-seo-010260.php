<?php
/**
 * Plugin Name: MDO English Vendor SEO
 * Description: Clean English WCFM store routes, persisted English store descriptions, SEO redirects and policy-tab cleanup.
 * Version: 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Capture the browser-facing URI before this MU plugin maps clean English
// WCFM routes to the plugin's native Spanish endpoints internally.
if ( ! isset( $GLOBALS['mdoev_original_public_uri_010260'] ) ) {
	$GLOBALS['mdoev_original_public_uri_010260'] = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_unslash( $_SERVER['REQUEST_URI'] )
		: '/';
}

function mdoev_public_path_010260(): string {
	$uri = isset( $GLOBALS['mdoev_original_public_uri_010260'] )
		? (string) $GLOBALS['mdoev_original_public_uri_010260']
		: ( isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
	return (string) wp_parse_url( $uri, PHP_URL_PATH );
}

function mdoev_en_010260(): bool {
	$path = mdoev_public_path_010260();
	return 1 === preg_match( '#^/en(?:/|$)#i', $path );
}

function mdoev_store_slug_010260( string $slug ): string {
	return sanitize_title( rawurldecode( $slug ) );
}

/**
 * Public English WCFM URLs are SEO-friendly, while WCFM itself continues to
 * receive its existing native Spanish rewrite endpoints internally.
 * This runs as an MU plugin after mdo-production-english-seo-routes.php and
 * before ordinary plugins (including TranslatePress/WCFM) load.
 */
function mdoev_bootstrap_store_route_010260(): void {
	if ( ! mdoev_en_010260() ) {
		return;
	}

	$path     = trim( mdoev_public_path_010260(), '/' );
	$internal = '';

	if ( preg_match( '#^en/store/([^/]+)/about$#i', $path, $m ) ) {
		$internal = '/en/tienda/' . mdoev_store_slug_010260( $m[1] ) . '/acercade/';
	} elseif ( preg_match( '#^en/store/([^/]+)/page/(\d+)$#i', $path, $m ) ) {
		$internal = '/en/tienda/' . mdoev_store_slug_010260( $m[1] ) . '/page/' . max( 1, (int) $m[2] ) . '/';
	} elseif ( preg_match( '#^en/store/([^/]+)$#i', $path, $m ) ) {
		$internal = '/en/tienda/' . mdoev_store_slug_010260( $m[1] ) . '/';
	}

	if ( '' === $internal ) {
		return;
	}

	$query = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
	$_SERVER['REQUEST_URI'] = $internal . ( '' !== $query ? '?' . $query : '' );
	$GLOBALS['mdoev_internal_request_uri_010260'] = $_SERVER['REQUEST_URI'];
}
mdoev_bootstrap_store_route_010260();

function mdoev_vendor_id_by_store_slug_010260( string $slug ): int {
	global $wpdb;
	$slug = mdoev_store_slug_010260( $slug );
	if ( '' === $slug ) {
		return 0;
	}

	$rows = $wpdb->get_results(
		"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key='wcfmmp_profile_settings'",
		ARRAY_A
	);
	foreach ( (array) $rows as $row ) {
		$settings = maybe_unserialize( $row['meta_value'] ?? '' );
		if ( ! is_array( $settings ) ) {
			continue;
		}
		$candidate = sanitize_title( (string) ( $settings['store_slug'] ?? '' ) );
		if ( $candidate === $slug ) {
			return (int) $row['user_id'];
		}
	}
	return 0;
}

function mdoev_persisted_store_description_010260( int $user_id ): string {
	global $wpdb;
	if ( $user_id < 1 ) {
		return '';
	}
	$raw = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key='_mdo_en_store_description' ORDER BY umeta_id DESC LIMIT 1",
			$user_id
		)
	);
	return is_string( $raw ) ? (string) maybe_unserialize( $raw ) : '';
}

/** WCFM reads the About copy directly from _store_description. */
add_filter(
	'get_user_metadata',
	static function ( $value, $object_id, $meta_key, $single ) {
		if ( ! mdoev_en_010260() || '_store_description' !== (string) $meta_key ) {
			return $value;
		}
		$english = mdoev_persisted_store_description_010260( (int) $object_id );
		if ( '' === trim( wp_strip_all_tags( $english ) ) ) {
			return $value;
		}
		return $single ? $english : array( $english );
	},
	PHP_INT_MAX,
	4
);

/** Also replace the profile array copy for templates that use shop_data. */
add_filter(
	'wcfmmp_popluate_store_data',
	static function ( $shop_data, $store_id ) {
		if ( ! mdoev_en_010260() || ! is_array( $shop_data ) ) {
			return $shop_data;
		}
		$english = mdoev_persisted_store_description_010260( (int) $store_id );
		if ( '' !== trim( wp_strip_all_tags( $english ) ) ) {
			$shop_data['shop_description'] = $english;
			$shop_data['store_description'] = $english;
		}
		return $shop_data;
	},
	PHP_INT_MAX,
	2
);

/** Policies are not part of the public English storefront. */
add_filter( 'wcfm_is_pref_policies', static fn( $allowed ) => mdoev_en_010260() ? false : $allowed, PHP_INT_MAX );
add_filter( 'wcfm_is_allow_store_policy', static fn( $allowed ) => mdoev_en_010260() ? false : $allowed, PHP_INT_MAX );

add_filter(
	'wcfmmp_store_tabs',
	static function ( $tabs ) {
		if ( ! mdoev_en_010260() || ! is_array( $tabs ) ) {
			return $tabs;
		}
		foreach ( array_keys( $tabs ) as $key ) {
			$label = wp_strip_all_tags( (string) $tabs[ $key ] );
			if ( false !== stripos( (string) $key, 'polic' ) || preg_match( '/\b(?:policies|policy|políticas|politicas)\b/iu', $label ) ) {
				unset( $tabs[ $key ] );
			}
		}
		if ( isset( $tabs['products'] ) ) {
			$tabs['products'] = 'Products';
		}
		if ( isset( $tabs['about'] ) ) {
			$tabs['about'] = 'About';
		}
		return $tabs;
	},
	PHP_INT_MAX,
	2
);

add_filter(
	'woocommerce_product_tabs',
	static function ( $tabs ) {
		if ( ! mdoev_en_010260() || ! is_array( $tabs ) ) {
			return $tabs;
		}
		foreach ( array_keys( $tabs ) as $key ) {
			$title = is_array( $tabs[ $key ] ) ? (string) ( $tabs[ $key ]['title'] ?? '' ) : '';
			if ( false !== stripos( (string) $key, 'polic' ) || preg_match( '/\b(?:policies|policy|store policies|políticas|politicas)\b/iu', wp_strip_all_tags( $title ) ) ) {
				unset( $tabs[ $key ] );
			}
		}
		return $tabs;
	},
	PHP_INT_MAX
);

function mdoev_english_store_url_010260( string $slug, string $endpoint = '', int $page = 0 ): string {
	$slug = mdoev_store_slug_010260( $slug );
	if ( '' === $slug ) {
		return home_url( '/en/' );
	}
	$url = home_url( '/en/store/' . $slug . '/' );
	if ( $page > 1 ) {
		return trailingslashit( $url . 'page/' . $page );
	}
	if ( 'about' === $endpoint ) {
		return trailingslashit( $url . 'about' );
	}
	return $url;
}

/**
 * Redirect every public hybrid Spanish/English route to one canonical English
 * URL. Internal rewrites are compared against the captured public URI, so
 * these redirects never loop when a clean URL is internally mapped to WCFM.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || wp_doing_ajax() || ! mdoev_en_010260() ) {
			return;
		}

		$path = '/' . trim( mdoev_public_path_010260(), '/' ) . '/';
		$to   = '';

		if ( preg_match( '#^/en/tienda/([^/]+)/acercade/$#i', $path, $m ) || preg_match( '#^/en/store/([^/]+)/acercade/$#i', $path, $m ) ) {
			$to = mdoev_english_store_url_010260( $m[1], 'about' );
		} elseif ( preg_match( '#^/en/(?:tienda|store)/([^/]+)/(?:politicas|policies)/$#i', $path, $m ) ) {
			$to = mdoev_english_store_url_010260( $m[1] );
		} elseif ( preg_match( '#^/en/tienda/([^/]+)/page/(\d+)/$#i', $path, $m ) ) {
			$to = mdoev_english_store_url_010260( $m[1], '', (int) $m[2] );
		} elseif ( preg_match( '#^/en/tienda/([^/]+)/$#i', $path, $m ) ) {
			$to = mdoev_english_store_url_010260( $m[1] );
		} elseif ( preg_match( '#^/en/producto/([^/]+)/$#i', $path, $m ) && function_exists( 'mdoer_post_row_by_native_slug' ) ) {
			$row = mdoer_post_row_by_native_slug( rawurldecode( $m[1] ), array( 'product' ) );
			if ( $row ) {
				$post = get_post( (int) $row['ID'] );
				$to = $post instanceof WP_Post && function_exists( 'mdoer_en_url' ) ? (string) mdoer_en_url( $post ) : '';
			}
		} elseif ( preg_match( '#^/en/categoria-producto/([^/]+)(?:/page/(\d+))?/$#i', $path, $m ) && function_exists( 'mdoer_term_row' ) ) {
			$row = mdoer_term_row( 'product_cat', rawurldecode( $m[1] ), false );
			if ( $row ) {
				$term = get_term( (int) $row['term_id'], 'product_cat' );
				$base = $term instanceof WP_Term && function_exists( 'mdoer_term_en_url' ) ? (string) mdoer_term_en_url( $term ) : '';
				$to = $base && ! empty( $m[2] ) ? trailingslashit( $base . 'page/' . (int) $m[2] ) : $base;
			}
		} elseif ( preg_match( '#^/en/etiqueta-producto/([^/]+)(?:/page/(\d+))?/$#i', $path, $m ) && function_exists( 'mdoer_term_row' ) ) {
			$row = mdoer_term_row( 'product_tag', rawurldecode( $m[1] ), false );
			if ( $row ) {
				$term = get_term( (int) $row['term_id'], 'product_tag' );
				$base = $term instanceof WP_Term && function_exists( 'mdoer_term_en_url' ) ? (string) mdoer_term_en_url( $term ) : '';
				$to = $base && ! empty( $m[2] ) ? trailingslashit( $base . 'page/' . (int) $m[2] ) : $base;
			}
		} elseif ( preg_match( '#^/en/([^/]+)/$#i', $path, $m ) && function_exists( 'mdoer_post_row_by_native_slug' ) ) {
			$row = mdoer_post_row_by_native_slug( rawurldecode( $m[1] ), array( 'page', 'post' ) );
			if ( $row ) {
				$post = get_post( (int) $row['ID'] );
				if ( $post instanceof WP_Post ) {
					$to = (int) get_option( 'page_on_front' ) === $post->ID ? home_url( '/en/' ) : ( function_exists( 'mdoer_en_url' ) ? (string) mdoer_en_url( $post ) : '' );
				}
			}
		}

		if ( $to ) {
			$current = home_url( $path );
			if ( untrailingslashit( $current ) !== untrailingslashit( $to ) ) {
				$query = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
				if ( '' !== $query ) {
					$to .= ( false === strpos( $to, '?' ) ? '?' : '&' ) . $query;
				}
				wp_safe_redirect( $to, 301, 'MDO English SEO' );
				exit;
			}
		}
	},
	-3000
);

/** Normalize WCFM-generated links server-side; no browser translation pass. */
function mdoev_normalize_vendor_url_010260( string $url ): string {
	$decoded = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$parts   = wp_parse_url( $decoded );
	if ( false === $parts ) {
		return $url;
	}
	$host = strtolower( (string) ( $parts['host'] ?? '' ) );
	$site = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	if ( '' !== $host && $host !== $site && 'www.' . $host !== $site && $host !== 'www.' . $site ) {
		return $url;
	}
	$path = (string) ( $parts['path'] ?? '' );
	$new  = '';
	if ( preg_match( '#^/(?:en/)?tienda/([^/]+)/acercade/?$#i', $path, $m ) ) {
		$new = '/en/store/' . mdoev_store_slug_010260( $m[1] ) . '/about/';
	} elseif ( preg_match( '#^/(?:en/)?tienda/([^/]+)/page/(\d+)/?$#i', $path, $m ) ) {
		$new = '/en/store/' . mdoev_store_slug_010260( $m[1] ) . '/page/' . max( 1, (int) $m[2] ) . '/';
	} elseif ( preg_match( '#^/(?:en/)?tienda/([^/]+)/?$#i', $path, $m ) ) {
		$new = '/en/store/' . mdoev_store_slug_010260( $m[1] ) . '/';
	} elseif ( preg_match( '#^/en/store/([^/]+)/acercade/?$#i', $path, $m ) ) {
		$new = '/en/store/' . mdoev_store_slug_010260( $m[1] ) . '/about/';
	}
	if ( '' === $new ) {
		return $url;
	}
	$target = home_url( $new );
	if ( ! empty( $parts['query'] ) ) {
		$target .= '?' . $parts['query'];
	}
	if ( ! empty( $parts['fragment'] ) ) {
		$target .= '#' . $parts['fragment'];
	}
	return $target;
}

function mdoev_normalize_english_vendor_html_010260( string $html ): string {
	if ( '' === $html || ! mdoev_en_010260() ) {
		return $html;
	}
	return (string) preg_replace_callback(
		'#\b(href|action)=("|\')([^"\']+)\2#iu',
		static function ( array $m ): string {
			$new = mdoev_normalize_vendor_url_010260( (string) $m[3] );
			return $m[1] . '=' . $m[2] . esc_url( $new ) . $m[2];
		},
		$html
	);
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || wp_doing_ajax() || ! mdoev_en_010260() ) {
			return;
		}
		ob_start( 'mdoev_normalize_english_vendor_html_010260' );
	},
	-2400
);

function mdoev_current_public_store_010260(): array {
	$path = trim( mdoev_public_path_010260(), '/' );
	if ( preg_match( '#^en/store/([^/]+)(?:/(about))?/?$#i', $path, $m ) ) {
		return array( 'slug' => mdoev_store_slug_010260( $m[1] ), 'endpoint' => ! empty( $m[2] ) ? 'about' : '' );
	}
	return array( 'slug' => '', 'endpoint' => '' );
}

add_filter(
	'aioseo_canonical_url',
	static function ( $url ) {
		if ( ! mdoev_en_010260() ) {
			return $url;
		}
		$store = mdoev_current_public_store_010260();
		return $store['slug'] ? mdoev_english_store_url_010260( $store['slug'], $store['endpoint'] ) : $url;
	},
	PHP_INT_MAX
);

add_filter(
	'aioseo_title',
	static function ( $title ) {
		if ( ! mdoev_en_010260() ) {
			return $title;
		}
		$store = mdoev_current_public_store_010260();
		if ( ! $store['slug'] ) {
			return $title;
		}
		$user_id = mdoev_vendor_id_by_store_slug_010260( $store['slug'] );
		$name    = $user_id ? (string) get_user_meta( $user_id, 'wcfmmp_store_name', true ) : '';
		if ( '' === trim( $name ) ) {
			$profile = $user_id ? get_user_meta( $user_id, 'wcfmmp_profile_settings', true ) : array();
			$name = is_array( $profile ) ? (string) ( $profile['store_name'] ?? '' ) : '';
		}
		if ( '' === trim( $name ) ) {
			$name = ucwords( str_replace( '-', ' ', $store['slug'] ) );
		}
		return $name . ( 'about' === $store['endpoint'] ? ' – About' : ' – Products' ) . ' | El Mercado de Origen';
	},
	PHP_INT_MAX
);

add_filter(
	'aioseo_description',
	static function ( $description ) {
		if ( ! mdoev_en_010260() ) {
			return $description;
		}
		$store = mdoev_current_public_store_010260();
		if ( ! $store['slug'] ) {
			return $description;
		}
		$user_id = mdoev_vendor_id_by_store_slug_010260( $store['slug'] );
		$copy    = $user_id ? mdoev_persisted_store_description_010260( $user_id ) : '';
		$copy    = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $copy, true ) ) );
		return $copy ? wp_html_excerpt( $copy, 155, '…' ) : $description;
	},
	PHP_INT_MAX
);
