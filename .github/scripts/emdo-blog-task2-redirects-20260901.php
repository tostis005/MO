<?php
/**
 * EMDO SEO task 2: permanent redirects for over-fragmented nutrition articles.
 *
 * This file is deployed as an MU plugin so redirects remain independent from
 * child-theme releases and from the redirects created during task 1.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		$path        = trim( $path, '/' );

		if ( '' === $path ) {
			return;
		}

		$redirects = array(
			'cuanta-vitamina-e-tiene-aove' => 'nutrientes-aceite-oliva-virgen-extra',
			'aove-omega-3-omega-6-perfil-grasas' => 'nutrientes-aceite-oliva-virgen-extra',
			'aceite-oliva-tiene-colesterol' => 'nutrientes-aceite-oliva-virgen-extra',
			'cuanta-grasa-saturada-tiene-aove' => 'nutrientes-aceite-oliva-virgen-extra',
			'cuanto-hierro-tiene-lomo-iberico' => 'nutrientes-lomo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
			'grasa-lomo-iberico-saturada-monoinsaturada-poliinsaturada' => 'nutrientes-lomo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
			'cuanto-hierro-tiene-chorizo-iberico' => 'nutrientes-chorizo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
			'grasa-chorizo-iberico-saturada-monoinsaturada-poliinsaturada' => 'nutrientes-chorizo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
		);

		if ( ! isset( $redirects[ $path ] ) ) {
			return;
		}

		wp_redirect( home_url( '/' . $redirects[ $path ] . '/' ), 301, 'EMDO SEO Task 2' );
		exit;
	},
	0
);
