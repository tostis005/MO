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

if ( ! defined( 'ELMERCADO_ADSENSE_INARTICLE_SLOT' ) ) {
	define( 'ELMERCADO_ADSENSE_INARTICLE_SLOT', '2804638564' );
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
 * @param object $method Metodo de envio.
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
 * Imprime el codigo moderno de Auto Ads en el head, pausado por defecto.
 *
 * El snippet moderno con ?client= inicializa Auto Ads por si solo. Antes de
 * cargarlo fijamos pauseAdRequests=1 para que no solicite anuncios hasta que
 * la geografia sea apta. No usamos la inicializacion antigua
 * enable_page_level_ads porque mezclar ambos mecanismos provoca una doble
 * inicializacion y Google la rechaza en tiempo de ejecucion.
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
	echo "\n</script>\n";
	printf(
		'<script async data-wcc="necessary" src="%1$s" crossorigin="anonymous"></script>' . "\n",
		esc_url( $src )
	);
}
add_action( 'wp_head', 'elmercado_adsense_output_paused_head_code', 2 );

/**
 * Devuelve el marcador sin espacio que luego el navegador convierte en In-article.
 */
function elmercado_adsense_inarticle_placeholder_html_010278( int $position ): string {
	return sprintf(
		'<div class="emo-inarticle-ad-slot" data-emo-inarticle-ad="%d" aria-hidden="true"></div>',
		max( 1, $position )
	);
}

/**
 * Inserta marcadores de In-article solo entre parrafos editoriales.
 *
 * 3-6 parrafos utiles: 1 anuncio.
 * 7-13 parrafos utiles: 2 anuncios.
 * 14 o mas: 3 anuncios.
 *
 * Los marcadores no contienen un <ins> de AdSense y no ocupan espacio. El JS
 * los hidrata unicamente si la geolocalizacion confirma que la visita es apta.
 */
function elmercado_adsense_insert_inarticle_placeholders_010278( string $content ): string {
	if ( '' === trim( $content ) || false !== strpos( $content, 'data-emo-inarticle-ad=' ) ) {
		return $content;
	}

	$commercial_marker = '<section class="emo-related-products-dynamic"';
	$commercial_offset = strpos( $content, $commercial_marker );

	if ( false === $commercial_offset ) {
		$editorial = $content;
		$suffix    = '';
	} else {
		$editorial = substr( $content, 0, $commercial_offset );
		$suffix    = substr( $content, $commercial_offset );
	}

	$matches = array();
	if ( ! preg_match_all( '/<p\b[^>]*>.*?<\/p>/isu', $editorial, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}

	$paragraphs = array();
	foreach ( $matches[0] as $match ) {
		$html = (string) $match[0];
		$text = trim(
			preg_replace(
				'/\s+/u',
				' ',
				html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' )
			)
		);

		// Ignora parrafos vacios o demasiado pequenos para no colocar publicidad
		// pegada a pies de foto, notas sueltas o restos de maquetacion.
		if ( strlen( $text ) < 80 ) {
			continue;
		}

		$paragraphs[] = array(
			'end' => (int) $match[1] + strlen( $html ),
		);
	}

	$count = count( $paragraphs );
	if ( $count < 3 ) {
		return $content;
	}

	$desired = 1;
	if ( $count >= 14 ) {
		$desired = 3;
	} elseif ( $count >= 7 ) {
		$desired = 2;
	}

	$targets = array( min( 2, $count - 1 ) );

	if ( $desired >= 2 ) {
		$second = max(
			$targets[0] + 3,
			(int) round( ( $count - 1 ) * 0.55 )
		);
		$second = min( $second, $count - 2 );

		if ( $second > $targets[0] ) {
			$targets[] = $second;
		}
	}

	if ( $desired >= 3 && count( $targets ) >= 2 ) {
		$third = max(
			$targets[1] + 3,
			(int) round( ( $count - 1 ) * 0.78 )
		);
		$third = min( $third, $count - 2 );

		if ( $third > $targets[1] ) {
			$targets[] = $third;
		}
	}

	$insertions = array();
	foreach ( array_values( array_unique( $targets ) ) as $position => $paragraph_index ) {
		if ( ! isset( $paragraphs[ $paragraph_index ] ) ) {
			continue;
		}

		$insertions[] = array(
			'offset' => (int) $paragraphs[ $paragraph_index ]['end'],
			'html'   => "\n" . elmercado_adsense_inarticle_placeholder_html_010278( $position + 1 ),
		);
	}

	// Insertamos desde abajo para que los offsets previos sigan siendo validos.
	usort(
		$insertions,
		static function ( array $a, array $b ): int {
			return $b['offset'] <=> $a['offset'];
		}
	);

	foreach ( $insertions as $insertion ) {
		$editorial = substr( $editorial, 0, $insertion['offset'] )
			. $insertion['html']
			. substr( $editorial, $insertion['offset'] );
	}

	return $editorial . $suffix;
}

/**
 * Aplica la insercion solo al cuerpo principal de una entrada individual.
 */
function elmercado_adsense_inarticle_content_010278( $content ) {
	if ( ! elmercado_adsense_is_blog_post_request() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	return elmercado_adsense_insert_inarticle_placeholders_010278( (string) $content );
}
// wpautop corre a 10 y do_shortcode a 11; esperamos a 12 para trabajar con
// parrafos reales y dejar fuera la seccion dinamica de productos.
add_filter( 'the_content', 'elmercado_adsense_inarticle_content_010278', 12 );

/**
 * CSS del marcador In-article.
 *
 * Sin anuncio rellenado no hay margen ni hueco. El margen aparece solo cuando
 * Google marca el <ins> como filled.
 */
function elmercado_adsense_inarticle_styles_010278(): void {
	if ( ! elmercado_adsense_is_blog_post_request() ) {
		return;
	}
	?>
	<style id="elmercado-adsense-inarticle-010278">
		.emo-article-content .emo-inarticle-ad-slot {
			display: none;
			width: 100%;
			min-width: 0;
			min-height: 0;
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		.emo-article-content .emo-inarticle-ad-slot.is-requested {
			display: block;
		}

		.emo-article-content .emo-inarticle-ad-slot.is-filled {
			margin: clamp(28px, 5vw, 46px) 0;
		}

		.emo-article-content .emo-inarticle-ad-slot > ins.adsbygoogle {
			display: block;
			width: 100%;
			text-align: center;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'elmercado_adsense_inarticle_styles_010278', 20 );

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
			'endpoint'      => esc_url_raw( rest_url( 'elmercado/v1/adsense-eligibility' ) ),
			'publisher'     => ELMERCADO_ADSENSE_PUBLISHER,
			'inArticleSlot' => ELMERCADO_ADSENSE_INARTICLE_SLOT,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'elmercado_adsense_enqueue_geo_controller', 40 );
