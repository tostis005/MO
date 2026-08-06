<?php
/**
 * Entrega crítica de la portada.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Comprueba que el CSS crítico generado es utilizable antes de cambiar la carga
 * de la hoja completa del tema padre.
 */
function elmercado_home_critical_css(): string {
	static $critical = null;

	if ( null !== $critical ) {
		return $critical;
	}

	$file = ELMERCADO_THEME_PATH . '/assets/css/critical-woostify-home.min.css';

	if ( ! is_readable( $file ) || filesize( $file ) < 7000 ) {
		$critical = '';
		return $critical;
	}

	$content  = file_get_contents( $file );
	$critical = false !== $content ? trim( $content ) : '';

	return $critical;
}

/**
 * Convierte una hoja secundaria en no bloqueante y conserva noscript.
 */
function elmercado_async_stylesheet_tag( string $href, string $id, string $media = 'all' ): string {
	$href  = esc_url( $href );
	$id    = esc_attr( $id );
	$media = esc_attr( $media );

	return sprintf(
		'<link rel="preload" as="style" id="%1$s" href="%2$s" onload="this.onload=null;this.rel=\'stylesheet\';this.media=\'%3$s\'" media="print">' .
		'<noscript><link rel="stylesheet" id="%1$s-noscript" href="%2$s" media="%3$s"></noscript>',
		$id,
		$href,
		$media
	);
}

add_filter(
	'style_loader_tag',
	static function ( string $html, string $handle, string $href, string $media ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return $html;
		}

		$critical = elmercado_home_critical_css();
		$is_parent = in_array( $handle, array( 'woostify-parent', 'woostify-parent-style', 'woostify-style' ), true )
			|| str_contains( $href, '/themes/woostify/style.css' );

		if ( $is_parent && '' !== $critical ) {
			return '<style id="elmercado-woostify-critical">' . $critical . '</style>'
			. elmercado_async_stylesheet_tag( $href, $handle . '-css', $media ?: 'all' );
		}

		$is_secondary = str_contains( $href, '/cookie-law-info/' )
			|| str_contains( $href, '/ajax-search-for-woocommerce/assets/css/' );

		if ( $is_secondary ) {
			return elmercado_async_stylesheet_tag( $href, $handle . '-css', $media ?: 'all' );
		}

		return $html;
	},
	PHP_INT_MAX,
	4
);

/**
 * Los scripts necesarios para interacción dejan de bloquear el primer pintado.
 * Se conserva el orden de ejecución con defer.
 */
add_filter(
	'script_loader_tag',
	static function ( string $tag, string $handle, string $src ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || '' === $src ) {
			return $tag;
		}

		$deferred_fragments = array(
			'/cookie-law-info-public.js',
			'/ajax-search-for-woocommerce/assets/js/search.min.js',
			'/woocommerce/assets/js/sourcebuster/',
			'/woocommerce/assets/js/frontend/order-attribution',
			'/p/woocommerce/',
		);

		$should_defer = false;
		foreach ( $deferred_fragments as $fragment ) {
			if ( str_contains( $src, $fragment ) ) {
				$should_defer = true;
				break;
			}
		}

		if ( ! $should_defer || str_contains( $tag, ' defer' ) || str_contains( $tag, ' async' ) ) {
			return $tag;
		}

		return str_replace( '<script ', '<script defer ', $tag );
	},
	PHP_INT_MAX,
	3
);

/**
 * jQuery y sus scripts inline asociados pasan al pie en la portada. WordPress
 * mantiene el orden de dependencias, a diferencia de añadir defer a mano a la
 * biblioteca mientras quedan scripts inline en la cabecera.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return;
		}

		$wp_scripts = wp_scripts();
		foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate' ) as $handle ) {
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				$wp_scripts->add_data( $handle, 'group', 1 );
			}
		}
	},
	PHP_INT_MAX
);

/**
 * Evita que el navegador espere a interacción para conocer la prioridad de la
 * primera composición; las imágenes posteriores siguen siendo diferidas.
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attributes ): array {
		if ( function_exists( 'elmercado_is_optimized_home' ) && elmercado_is_optimized_home() && isset( $attributes['class'] ) && str_contains( $attributes['class'], 'emo-hero' ) ) {
			$attributes['loading']       = 'eager';
			$attributes['fetchpriority'] = 'high';
			$attributes['decoding']      = 'async';
		}

		return $attributes;
	},
	30
);
