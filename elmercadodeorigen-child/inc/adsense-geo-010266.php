<?php
/**
 * AdSense geolocalizado para contenido editorial.
 *
 * La pagina puede estar cacheada, por lo que la decision geografica nunca se
 * imprime en el HTML. Un endpoint REST sin cache determina el pais de la
 * visita con WooCommerce y el navegador carga AdSense solo si ese pais no
 * esta cubierto por ninguna zona de envio activa.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

	$shippable_countries = array_values( array_unique( array_filter( array_map( 'strtoupper', $shippable_countries ) ) ) );

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
 * Obtiene el pais del visitante mediante la misma geolocalizacion de WooCommerce.
 *
 * @return string Codigo ISO alfa-2 o cadena vacia si no puede determinarse.
 */
function elmercado_adsense_get_visitor_country(): string {
	if ( ! class_exists( 'WC_Geolocation' ) ) {
		return '';
	}

	$location = WC_Geolocation::geolocate_ip();
	$country  = isset( $location['country'] ) ? strtoupper( (string) $location['country'] ) : '';

	return 2 === strlen( $country ) ? $country : '';
}

/**
 * Endpoint publico: solo devuelve si esta visita puede recibir Auto Ads.
 *
 * @return WP_REST_Response
 */
function elmercado_adsense_rest_eligibility(): WP_REST_Response {
	$country  = elmercado_adsense_get_visitor_country();
	$show_ads = '' !== $country && ! elmercado_adsense_country_is_shippable( $country );

	$response = new WP_REST_Response(
		array(
			'show_ads' => $show_ads,
		),
		200
	);

	$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private' );
	$response->header( 'Pragma', 'no-cache' );
	$response->header( 'Expires', '0' );
	$response->header( 'Vary', 'Cookie' );

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
 * Carga el pequeno controlador solo en entradas individuales del blog.
 * El script de Google no se encola aqui: se inyecta en cliente tras validar
 * geografia y consentimiento.
 */
function elmercado_adsense_enqueue_geo_loader(): void {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return;
	}

	$handle = 'elmercado-adsense-geo';
	$src    = ELMERCADO_THEME_URL . '/assets/js/adsense-geo-010266.js';
	$path   = ELMERCADO_THEME_PATH . '/assets/js/adsense-geo-010266.js';
	$ver    = is_readable( $path ) ? (string) filemtime( $path ) : ELMERCADO_THEME_VERSION;

	wp_enqueue_script( $handle, $src, array(), $ver, true );
	wp_localize_script(
		$handle,
		'ElMercadoAdsenseGeo',
		array(
			'endpoint'  => esc_url_raw( rest_url( 'elmercado/v1/adsense-eligibility' ) ),
			'publisher' => 'ca-pub-3168527008181132',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'elmercado_adsense_enqueue_geo_loader', 40 );
