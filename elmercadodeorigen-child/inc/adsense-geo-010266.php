<?php
/**
 * AdSense geolocalizado para contenido editorial.
 *
 * El codigo oficial de AdSense se imprime en el <head> de las entradas, pero
 * las solicitudes de anuncios quedan pausadas por defecto. Un endpoint REST
 * sin cache determina el pais de la visita con WooCommerce y el navegador
 * solo reanuda las solicitudes si ese pais no esta cubierto por una zona de
 * envio activa.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ELMERCADO_ADSENSE_PUBLISHER' ) ) {
	define( 'ELMERCADO_ADSENSE_PUBLISHER', 'ca-pub-3168527008181132' );
}

/**
 * Limita AdSense a entradas individuales publicadas del blog.
 *
 * @return bool
 */
function elmercado_adsense_is_blog_post_request(): bool {
	return ! is_admin() && is_singular( 'post' ) && ! is_feed() && ! is_preview();
}

/**
 * Comprueba si un metodo de envio esta habilitado.
 *
 * @param object $method Metodo de WooCommerce.
 * @return bool
 */
function elmercado_adsense_shipping_method_is_enabled( $method ): bool {
	if ( ! is_object( $method ) ) {
		return false;
	}

	if ( method_exists( $method, 'is_enabled' ) ) {
		return (bool) $method->is_enabled();
	}

	return isset( $method->enabled ) && 'yes' === $method->enabled;
}

/**
 * Devuelve los codigos ISO de pais cubiertos por zonas de envio activas.
 *
 * Una zona por estado (por ejemplo ES:PM) cuenta como pais cubierto: la regla
 * de negocio es no mostrar anuncios si existe capacidad de envio a cualquier
 * parte de ese pais.
 *
 * @return string[]|array{'*'}
 */
function elmercado_adsense_get_shippable_countries(): array {
	static $shippable_countries = null;

	if ( null !== $shippable_countries ) {
		return $shippable_countries;
	}

	$shippable_countries = array();

	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return $shippable_countries;
	}

	// Si "Ubicaciones no cubiertas" tiene un metodo activo, cualquier pais
	// restante es vendible y no debemos mostrar publicidad en ninguno.
	$rest_of_world = new WC_Shipping_Zone( 0 );
	foreach ( $rest_of_world->get_shipping_methods( false ) as $method ) {
		if ( elmercado_adsense_shipping_method_is_enabled( $method ) ) {
			$shippable_countries = array( '*' );
			return $shippable_countries;
		}
	}

	$continents = array();
	if ( function_exists( 'WC' ) && WC() && isset( WC()->countries ) && WC()->countries ) {
		$continents = WC()->countries->get_continents();
	} elseif ( class_exists( 'WC_Countries' ) ) {
		$continents = ( new WC_Countries() )->get_continents();
	}

	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		$has_enabled_method = false;

		foreach ( $zone['shipping_methods'] ?? array() as $method ) {
			if ( elmercado_adsense_shipping_method_is_enabled( $method ) ) {
				$has_enabled_method = true;
				break;
			}
		}

		if ( ! $has_enabled_method ) {
			continue;
		}

		foreach ( $zone['zone_locations'] ?? array() as $location ) {
			$type = isset( $location->type ) ? (string) $location->type : '';
			$code = isset( $location->code ) ? strtoupper( (string) $location->code ) : '';

			if ( '' === $code ) {
				continue;
			}

			if ( 'country' === $type ) {
				$shippable_countries[] = $code;
				continue;
			}

			if ( 'state' === $type ) {
				$country = strstr( $code, ':', true );
				if ( false !== $country && '' !== $country ) {
					$shippable_countries[] = $country;
				}
				continue;
			}

			if ( 'continent' === $type && isset( $continents[ $code ]['countries'] ) ) {
				$shippable_countries = array_merge( $shippable_countries, $continents[ $code ]['countries'] );
			}
		}
	}

	$shippable_countries = array_values(
		array_unique(
			array_filter(
				array_map( 'strtoupper', $shippable_countries )
			)
		)
	);

	return $shippable_countries;
}

/**
 * Indica si WooCommerce tiene una zona de envio activa para el pais.
 *
 * @param string $country Codigo ISO alfa-2.
 * @return bool
 */
function elmercado_adsense_country_is_shippable( string $country ): bool {
	$country = strtoupper( trim( $country ) );

	if ( 2 !== strlen( $country ) ) {
		return true; // Fallo seguro: ante una ubicacion dudosa, no mostramos ads.
	}

	$countries = elmercado_adsense_get_shippable_countries();

	return in_array( '*', $countries, true ) || in_array( $country, $countries, true );
}

/**
 * Obtiene el pais del visitante mediante la geolocalizacion de WooCommerce.
 *
 * WooCommerce ya contempla X-Real-IP, X-Forwarded-For y cabeceras de pais de
 * proxies/CDN antes de recurrir a su base/API de geolocalizacion.
 *
 * @return string Codigo ISO alfa-2 o cadena vacia si no puede determinarse.
 */
function elmercado_adsense_get_visitor_country(): string {
	if ( ! class_exists( 'WC_Geolocation' ) ) {
		return '';
	}

	$location = WC_Geolocation::geolocate_ip();
	$country  = isset( $location['country'] ) ? strtoupper( sanitize_text_field( (string) $location['country'] ) ) : '';

	return 2 === strlen( $country ) ? $country : '';
}

/**
 * Endpoint publico: devuelve si esta visita puede recibir Auto Ads.
 *
 * @return WP_REST_Response
 */
function elmercado_adsense_rest_eligibility(): WP_REST_Response {
	$country  = elmercado_adsense_get_visitor_country();
	$can_buy  = '' !== $country ? elmercado_adsense_country_is_shippable( $country ) : null;
	$show_ads = '' !== $country && false === $can_buy;

	$response = new WP_REST_Response(
		array(
			'country'  => $country,
			'can_buy'  => $can_buy,
			'show_ads' => $show_ads,
		),
		200
	);

	$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private' );
	$response->header( 'Pragma', 'no-cache' );
	$response->header( 'Expires', '0' );
	$response->header( 'Vary', 'Cookie, X-Forwarded-For, X-Real-IP, CF-IPCountry, X-Country-Code' );

	return $response;
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'elmercado/v1',
			'/adsense-eligibility',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'elmercado_adsense_rest_eligibility',
				'permission_callback' => '__return_true',
			)
		);
	}
);

/**
 * Imprime el codigo oficial de Auto Ads en el head, pausado por defecto.
 *
 * Google documenta pauseAdRequests para cargar el tag sin enviar solicitudes
 * hasta que la aplicacion decida reanudarlas. Esto mantiene el snippet en el
 * HTML que rastrea AdSense y evita anuncios en paises donde si vendemos.
 */
function elmercado_adsense_output_paused_head_code(): void {
	if ( ! elmercado_adsense_is_blog_post_request() ) {
		return;
	}

	$publisher = ELMERCADO_ADSENSE_PUBLISHER;
	$src       = add_query_arg(
		'client',
		$publisher,
		'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js'
	);

	echo "<script data-wcc=\"necessary\">\n";
	echo 'window.adsbygoogle=window.adsbygoogle||[];';
	echo 'window.adsbygoogle.pauseAdRequests=1;';
	echo 'window.adsbygoogle.push({google_ad_client:' . wp_json_encode( $publisher ) . ',enable_page_level_ads:true});';
	echo "\n</script>\n";
	printf(
		'<script async data-wcc="necessary" src="%1$s" crossorigin="anonymous"></script>' . "\n",
		esc_url( $src )
	);
}
add_action( 'wp_head', 'elmercado_adsense_output_paused_head_code', 2 );

/**
 * Carga el controlador geografico en el head inmediatamente despues del tag.
 */
function elmercado_adsense_enqueue_geo_controller(): void {
	if ( ! elmercado_adsense_is_blog_post_request() ) {
		return;
	}

	$handle = 'elmercado-adsense-geo';
	$src    = ELMERCADO_THEME_URL . '/assets/js/adsense-geo-010266.js';
	$path   = ELMERCADO_THEME_PATH . '/assets/js/adsense-geo-010266.js';
	$ver    = is_readable( $path ) ? (string) filemtime( $path ) : ELMERCADO_THEME_VERSION;

	wp_enqueue_script( $handle, $src, array(), $ver, false );
	wp_localize_script(
		$handle,
		'ElMercadoAdsenseGeo',
		array(
			'endpoint'  => esc_url_raw( rest_url( 'elmercado/v1/adsense-eligibility' ) ),
			'publisher' => ELMERCADO_ADSENSE_PUBLISHER,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'elmercado_adsense_enqueue_geo_controller', 40 );
