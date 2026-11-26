<?php
/**
 * Página promocional de Black Friday 2026: condiciones por vendedor.
 * Esta plantilla presenta las ofertas, sin crear descuentos ni regalos en WooCommerce.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Usa exclusivamente fotografías de artículos publicados en el catálogo.
 * Si una ficha cambia o no hay fotografía, se utiliza una composición gráfica.
 */
if ( ! function_exists( 'emdo_bf2026_photo' ) ) {
	function emdo_bf2026_photo( string $slug, string $alt, string $size = 'large', string $loading = 'lazy' ): string {
		if ( ! function_exists( 'wc_get_product' ) ) { return ''; }
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) { return ''; }
		$product = wc_get_product( $post->ID );
		if ( ! $product instanceof WC_Product || ! $product->get_image_id() ) { return ''; }
		return (string) wp_get_attachment_image(
			$product->get_image_id(),
			$size,
			false,
			array(
				'alt'      => $alt,
				'class'    => 'bf26-photo',
				'loading'  => $loading,
				'decoding' => 'async',
			)
		);
	}
}

$hidalgo_url  = home_url( '/tienda/hidalgo-de-la-jara/' );
$montjam_url  = home_url( '/tienda/montjam/' );
$bf26_1957_url     = home_url( '/tienda/1957/' );
$bf26_chocolate_url = home_url( '/tienda/chocolates-artesanos-isabel/' );
$bf26_huerta_url    = home_url( '/tienda/la-huerta-de-ana-mary/' );
$bf26_ikore_url     = home_url( '/tienda/ikore-kofi/' );
$bf26_tolecarnes_url = home_url( '/tienda/tolecarnes/' );
$bf26_pajarete_url   = home_url( '/tienda/quesos-pajarete/' );
$bf26_selectos_url   = home_url( '/tienda/selectos-de-castilla/' );
$club_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mi-cuenta/' );
$club_wallet_url  = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'monedero' ) : $club_account_url;

/**
 * Control de visibilidad de la landing, independiente de WooCommerce.
 * Hidalgo de la Jara y Montjam participan en la campaña.
 * La lista puede seguir gestionándose en WordPress tras la activación inicial.
 * La opción emdo_bf2026_visible_sellers controla las fichas y fotos.
 */
$bf26_known_sellers = array( 'hidalgo', 'montjam' );
$bf26_launch_sellers = array( 'hidalgo', 'montjam' );
$bf26_enabled_option = get_option( 'emdo_bf2026_visible_sellers', $bf26_launch_sellers );
$bf26_enabled_option = is_array( $bf26_enabled_option ) ? $bf26_enabled_option : $bf26_launch_sellers;
$bf26_visible_sellers = array_values( array_intersect( $bf26_known_sellers, array_map( 'sanitize_key', $bf26_enabled_option ) ) );
$bf26_show_hidalgo = in_array( 'hidalgo', $bf26_visible_sellers, true );
$bf26_show_montjam = in_array( 'montjam', $bf26_visible_sellers, true );
$bf26_offer_position = 0;

$hidalgo_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico', 'Jamón de bellota 100 % ibérico de Hidalgo de la Jara' );
$hidalgo_ham_dop = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico-con-dop-los-pedroches-brida-negra', 'Jamón de bellota 100 % ibérico con DOP Los Pedroches de Hidalgo de la Jara' );
$hidalgo_palet = emdo_bf2026_photo( 'paleta-de-bellota-100-iberico', 'Paleta de bellota 100 % ibérica de Hidalgo de la Jara' );
$montjam_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico-montjam', 'Jamón de bellota 100 % ibérico de Montjam' );
$montjam_slice = emdo_bf2026_photo( 'jamon-bellota-100-iberico-loncheado-montjam-90g', 'Sobre de jamón de bellota 100 % ibérico loncheado Montjam' );

/* Imágenes de artículos publicados, con fallback visual si dejan de estar disponibles. */
$bf26_1957_photo = emdo_bf2026_photo( 'cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio', 'Aceite de oliva virgen extra de la cosecha 2026/27 del productor 1957', 'large', 'eager' );
$bf26_chocolate_photo = emdo_bf2026_photo( 'crema-bioft-de-chocolate-negro-con-45-de-avellanas-y-cafe-arabica-200-g', 'Crema artesana de chocolate de Chocolates Artesanos Isabel', 'large', 'eager' );
$bf26_huerta_photo    = emdo_bf2026_photo( '10-kg-de-hortalizas-frescas', 'Caja de hortalizas frescas de La Huerta de Ana Mary', 'large', 'eager' );
$bf26_ikore_photo     = emdo_bf2026_photo( 'cafe-ikore-kofi-el-indio', 'Café de especialidad Colombia El Indio de Ikore Kofi', 'large', 'eager' );
/* Imágenes de ambientación fotográfica con permiso de uso comercial:
 * Vacuno a la brasa: Unsplash / https://unsplash.com/id/foto/steak-dimasak-di-atas-panggangan-dengan-penjepit-uRrKbxHiazE
 * Foie gras emplatado: Pexels / https://www.pexels.com/photo/a-foie-gras-with-stainless-fork-on-a-ceramic-plate-6880221/
 * Ambas se almacenan en el directorio público de la campaña durante el despliegue.
 * Las fotos no muestran necesariamente el producto exacto a la venta.
 */
$bf26_grill_photo_url = content_url( '/mu-plugins/emdo-black-friday-2026-preview/tolecarnes-grill.jpg' );
$bf26_foie_photo_url  = content_url( '/mu-plugins/emdo-black-friday-2026-preview/selectos-foie.jpg' );
$bf26_pajarete_photo   = emdo_bf2026_photo( 'gran-reserva-mezcla', 'Queso gran reserva mezcla de Quesos Pajarete', 'large', 'eager' );

/**
 * Siete packs públicos de Latitudes, independientes de los originales Mentta.
 * Sus precios ya son precios finales, sin comparar con un PVP que no estuvo
 * a la venta en este escaparate.
 */
$bf26_exotic_catalog = array(
    'pasaporte-salvaje'       => 'Pasaporte Salvaje',
    'reserva-de-caza'        => 'La Reserva de Caza',
    'safari-africano'        => 'Safari Africano',
    'vuelta-al-mundo'        => 'La Vuelta al Mundo',
    'parrilla-sin-fronteras-xl' => 'Parrilla Sin Fronteras XL',
    'universo-wagyu'         => 'Universo Wagyu',
    'leyendas-del-vacuno'    => 'Leyendas del Vacuno',
);
$bf26_exotic_packs = array();
foreach ( $bf26_exotic_catalog as $pack_key => $pack_name ) {
    $pack_post = get_page_by_path( 'pack-musee-' . $pack_key . '-black-friday', OBJECT, 'product' );
    if ( ! $pack_post instanceof WP_Post || 'publish' !== $pack_post->post_status ) {
        continue;
    }
    $pack_product = function_exists( 'wc_get_product' ) ? wc_get_product( $pack_post->ID ) : false;
    if ( ! $pack_product instanceof WC_Product
        || (string) get_post_meta( $pack_post->ID, '_emdo_web_pack_key', true ) !== $pack_key
        || ! $pack_product->get_price() ) {
        continue;
    }
    // Load the seven lightweight previews eagerly to avoid blank desktop tiles.
    $bf26_exotic_packs[] = array(
        'title' => $pack_name,
        'url'   => get_permalink( $pack_post->ID ),
        'price' => (float) $pack_product->get_price(),
        'image' => $pack_product->get_image_id() ? wp_get_attachment_image( $pack_product->get_image_id(), 'thumbnail', false, array( 'alt' => '', 'class' => 'bf26-photo', 'loading' => 'eager', 'decoding' => 'async' ) ) : '',
    );
}


get_header();
?>
<main id="contenido" class="bf26" aria-labelledby="bf26-title" data-bf26-visible-sellers="<?php echo esc_attr( implode( ',', $bf26_visible_sellers ) ); ?>">

	<section class="bf26-hero">
		<div class="bf26-wrap bf26-hero__grid">
			<div class="bf26-hero__copy">
				<h1 id="bf26-title">Black Friday.<em>Ofertas que apetecen.</em><span>Y mucho por descubrir.</span></h1>
				<p class="bf26-hero__lead">Este Black Friday reunimos <strong>descuentos, regalos y ventajas especiales</strong> de nuestros productores. Y estrenamos el <strong>Club EMDO</strong>: durante la campaña, tus compras con cuenta registrada acumulan un <strong>2 % de cashback</strong> en el monedero.</p>
				<div class="bf26-hero__buttons">
					<a class="bf26-button bf26-button--cream" href="#ofertas">Ver todas las promociones <span aria-hidden="true">↗</span></a>
				</div>
				<div class="bf26-hero__fine">DESCUENTOS ESPECIALES <i></i> REGALOS <i></i> CLUB EMDO: 2 % CASHBACK</div>
			</div>
			<div class="bf26-hero__visual" aria-label="Selección de productos ibéricos de El Mercado de Origen">
				<div class="bf26-hero__frame bf26-hero__frame--back">
					<?php if ( $bf26_show_montjam && $montjam_ham ) { echo $montjam_ham; } elseif ( $bf26_show_hidalgo && $hidalgo_ham ) { echo $hidalgo_ham; } else { echo '<div class="bf26-image-fallback">BLACK<br><strong>FRIDAY</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-hero__frame bf26-hero__frame--front">
					<?php if ( $bf26_show_hidalgo && $hidalgo_palet ) { echo $hidalgo_palet; } elseif ( $bf26_show_montjam && $montjam_slice ) { echo $montjam_slice; } else { echo '<div class="bf26-image-fallback">SELECCIÓN<br><strong>ESPECIAL</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-hero__seal bf26-campaign-stamp" aria-label="Black Friday 2026: ofertas y regalos"><small>EDICIÓN 2026</small><strong>BLACK<em>FRIDAY</em></strong><span>OFERTAS <b aria-hidden="true">✳</b> REGALOS</span></div>
				<div class="bf26-hero__stamp" aria-hidden="true">CALIDAD <span>✳</span> TRADICIÓN <span>✳</span> SABOR</div>
			</div>
		</div>
		<div class="bf26-hero__bigtype" aria-hidden="true">BLACK FRIDAY</div>
	</section>

	<div class="bf26-band" aria-label="Resumen de las ofertas de Black Friday">
		<div class="bf26-wrap bf26-band__inner">
			<span>BLACK FRIDAY EN EL MERCADO DE ORIGEN</span><span class="bf26-band__star">✳</span>
			<strong>DESCUENTOS Y REGALOS ESPECIALES</strong><span class="bf26-band__star">✳</span>
			<span>ESTRENAMOS CLUB EMDO · 2 % CASHBACK</span>
		</div>
	</div>

	<section id="ofertas" class="bf26-offers">
		<div class="bf26-wrap">
			<div class="bf26-heading">
				<div>
					<p class="bf26-eyebrow">LAS PROMOCIONES</p>
					<h2>Promociones para elegir.<br><em>Ofertas para disfrutar.</em></h2>
				</div>
				<p>Descubre las ofertas disponibles y las condiciones de cada promoción. <strong>Próximamente iremos incorporando nuevas promociones, así que estate atento a las novedades.</strong></p>
			</div>

			<?php if ( $bf26_show_hidalgo ) : $bf26_offer_position++; ?>
			<article class="bf26-offer bf26-offer--hidalgo" id="hidalgo-oferta" data-bf26-seller="hidalgo" aria-labelledby="bf26-hidalgo-title">
				<div class="bf26-offer__media">
					<div class="bf26-offer__photobg bf26-offer__photobg--hidalgo-duo">
						<figure class="bf26-hidalgo-piece">
							<div class="bf26-hidalgo-piece__photo">
								<?php if ( $hidalgo_ham ) { echo $hidalgo_ham; } else { echo '<div class="bf26-image-fallback">JAMÓN<br><strong>SIN DOP</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<figcaption>JAMÓN SIN DOP</figcaption>
						</figure>
						<figure class="bf26-hidalgo-piece">
							<div class="bf26-hidalgo-piece__photo">
								<?php if ( $hidalgo_ham_dop ) { echo $hidalgo_ham_dop; } else { echo '<div class="bf26-image-fallback">JAMÓN<br><strong>CON DOP</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<figcaption>JAMÓN CON DOP</figcaption>
						</figure>
					</div>
					<div class="bf26-offer__imagecaption">HIDALGO DE LA JARA <span>·</span> LOS PEDROCHES</div>
					<div class="bf26-offer__number"><?php echo esc_html( sprintf( '%02d', $bf26_offer_position ) ); ?></div>
				</div>
				<div class="bf26-offer__content">
					<div class="bf26-offer__top"><span class="bf26-offer__origin">DE LA DEHESA DE LOS PEDROCHES</span><span class="bf26-offer__tag">15 % EN TODA SU SELECCIÓN</span></div>
					<h3 id="bf26-hidalgo-title">Hidalgo <em>de la Jara</em></h3>
					<p class="bf26-offer__description"><strong>15 % de descuento en todos los productos Hidalgo de la Jara de nuestra tienda.</strong> Aplica el cupón <strong>BFEMDO</strong> al hacer tu pedido. Además, descubre el salto al tramo de peso superior en jamones seleccionados y los regalos por importe.</p>
					<div class="bf26-offer__discount"><strong>–15<span>%</span></strong><div class="bf26-offer__discount-details"><span>EN TODOS SUS PRODUCTOS</span><div class="bf26-offer__coupon-row"><span class="bf26-offer__coupon-hint">CÓDIGO</span><button type="button" class="bf26-coupon" data-bf26-copy="BFEMDO" aria-label="Copiar cupón BFEMDO">BFEMDO <span aria-hidden="true">⧉</span></button></div></div></div>
					</div>
				<div class="bf26-offer__details">
					<div class="bf26-hidalgo-benefits" aria-label="Ventajas especiales de Black Friday para Hidalgo de la Jara">
						<div class="bf26-hidalgo-upgrade">
							<div class="bf26-hidalgo-upgrade__emblem" aria-hidden="true"><strong>+0,5</strong><span>KG</span></div>
							<div class="bf26-hidalgo-upgrade__copy" id="hidalgo-condiciones">
								<span class="bf26-hidalgo-benefits__eyebrow">VENTAJA EXTRA EN JAMONES SELECCIONADOS <a href="#hidalgo-condiciones" aria-label="Ver condiciones de los jamones seleccionados de Hidalgo">*</a></span>
								<h4>Compras un peso. <em>Recibes el siguiente.</em></h4>
								<p>Desde el tramo de <strong>7–7,5 kg</strong> en adelante, te enviamos el tramo superior, <strong>medio kilo más sin coste adicional</strong>. Por ejemplo, eliges 7–7,5 kg y recibes 7,5–8 kg.</p>
								<p class="bf26-hidalgo-upgrade__note"><strong>* Condiciones de los jamones seleccionados:</strong> exclusivamente jamones de <strong>brida negra</strong> de Hidalgo de la Jara, desde el tramo de <strong>7–7,5 kg</strong> en adelante, tanto con Denominación de Origen Protegida (DOP) como sin DOP. No incluye paletas ni tramos inferiores.</p>
							</div>
						</div>
						<div class="bf26-hidalgo-rewards">
							<div class="bf26-hidalgo-rewards__heading"><span class="bf26-hidalgo-benefits__eyebrow">REGALOS SEGÚN TU PEDIDO</span><h4>Cuanto más disfrutas, <em>más te llevas.</em></h4></div>
							<div class="bf26-hidalgo-rewards__grid">
								<div class="bf26-hidalgo-rewards__tier">
									<span>COMPRAS SUPERIORES A</span><strong>150 €</strong>
									<p>Te regalamos <b>un sobre de salchichón</b>.</p>
								</div>
								<div class="bf26-hidalgo-rewards__tier bf26-hidalgo-rewards__tier--premium">
									<span>COMPRAS SUPERIORES A</span><strong>250 €</strong>
									<p>Te llevas <b>el sobre de salchichón y un miniqueso Pajarete de 90 g</b>.</p>
								</div>
							</div>
							<p class="bf26-hidalgo-rewards__note">Se tiene en cuenta el importe de los productos de Hidalgo de la Jara de tu pedido. <strong>Los regalos son acumulables:</strong> al superar los 250 €, recibes ambos.</p>
						</div>
					</div>
					<div class="bf26-offer__closing">
						<p class="bf26-offer__cashback"><span aria-hidden="true">✳</span> <strong>Estrenamos el Club EMDO:</strong> compra con tu cuenta y acumula <strong>2 % de cashback durante Black Friday</strong> en tu monedero. Después de la campaña, seguirás acumulando el 1 %. <a href="#club-emdo">Descubre el club ↗</a></p>
						<a href="<?php echo esc_url( $hidalgo_url ); ?>" class="bf26-button bf26-button--green">Explorar Hidalgo de la Jara <span aria-hidden="true">↗</span></a>
					</div>
					
				</div>
			</article>

			<?php endif; ?>
			<?php if ( $bf26_show_montjam ) : $bf26_offer_position++; ?>
			<article class="bf26-offer bf26-offer--montjam" id="montjam-oferta" data-bf26-seller="montjam" aria-labelledby="bf26-montjam-title">
				<div class="bf26-offer__media">
					<div class="bf26-offer__photobg">
						<?php if ( $montjam_ham ) { echo $montjam_ham; } elseif ( $montjam_slice ) { echo $montjam_slice; } else { echo '<div class="bf26-image-fallback">MONTJAM<br><strong>Ibéricos</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="bf26-offer__imagecaption">MONTJAM <span>·</span> HUELVA</div>
					<div class="bf26-offer__number"><?php echo esc_html( sprintf( '%02d', $bf26_offer_position ) ); ?></div>
				</div>
				<div class="bf26-offer__content">
					<div class="bf26-offer__top"><span class="bf26-offer__origin">EL CARÁCTER DE LOS IBÉRICOS DE HUELVA</span><span class="bf26-offer__tag">15 % EN TODA SU SELECCIÓN</span></div>
					<h3 id="bf26-montjam-title">Montjam<em>.</em></h3>
					<p class="bf26-offer__description"><strong>15 % de descuento en todos los productos Montjam de nuestra tienda.</strong> Introduce <strong>BFMONTJAM</strong> al hacer tu pedido. Y en los jamones de bellota 100 % ibéricos, disfrutarás de un 2,5 % adicional automático en el carrito. <strong>Todos sus jamones incluyen medio morcón de regalo (aproximadamente 400–500 g).</strong></p>
					<div class="bf26-offer__discount"><strong>–15<span>%</span></strong><div class="bf26-offer__discount-details"><span>EN TODOS LOS PRODUCTOS MONTJAM</span><div class="bf26-offer__coupon-row"><span class="bf26-offer__coupon-hint">CÓDIGO</span><button type="button" class="bf26-coupon" data-bf26-copy="BFMONTJAM" aria-label="Copiar cupón BFMONTJAM">BFMONTJAM <span aria-hidden="true">⧉</span></button></div></div></div>
					<div class="bf26-montjam-extras" aria-label="Promociones adicionales de Montjam">
						<div class="bf26-montjam-extra" id="montjam-condiciones">
							<div class="bf26-montjam-extra__badge"><strong>+2,5<span>%</span></strong><small>ADICIONAL</small></div>
							<div class="bf26-montjam-extra__copy">
								<span class="bf26-hidalgo-benefits__eyebrow">EN JAMONES DE BELLOTA 100 % IBÉRICOS <a href="#montjam-letra-pequena" aria-label="Ver condiciones del descuento adicional">*</a></span>
								<h4>Un extra más. <em>Sin hacer nada.</em></h4>
								<p>Con los <strong>jamones Montjam de bellota 100 % ibéricos</strong>, disfrutas de un <strong>2,5 % adicional</strong>. Este extra se aplica <strong>automáticamente en el carrito</strong>, sin introducir otro código. El 15 % para todos los productos Montjam se consigue con <strong>BFMONTJAM</strong>.</p>
							</div>
						</div>
						<p class="bf26-montjam-extras__conditions" id="montjam-letra-pequena"><strong>* Condiciones del 2,5 % adicional:</strong> aplicable a los jamones Montjam de <strong>bellota 100 % ibéricos</strong>; no incluye paletas ni embutidos. Este descuento se refleja automáticamente en el carrito. El <strong>15 % general de Montjam</strong> se obtiene al introducir <strong>BFMONTJAM</strong>.</p>
						<div class="bf26-montjam-gift">
							<div class="bf26-montjam-gift__emblem" aria-hidden="true">✳</div>
							<div>
								<span class="bf26-hidalgo-benefits__eyebrow">REGALO EN TODOS LOS JAMONES</span>
								<h4>Medio morcón de regalo.</h4>
								<p>Con la compra de <strong>cualquier jamón Montjam</strong> recibirás <strong>medio morcón de aproximadamente 400–500 g</strong>. No necesitas llegar a un importe mínimo.</p>
							</div>
						</div>
					</div>
					<p class="bf26-offer__cashback"><span aria-hidden="true">✳</span> <strong>Estrenamos el Club EMDO:</strong> compra con tu cuenta y acumula <strong>2 % de cashback durante Black Friday</strong> en tu monedero. Después de la campaña, seguirás acumulando el 1 %. <a href="#club-emdo">Descubre el club ↗</a></p>
					<a href="<?php echo esc_url( $montjam_url ); ?>" class="bf26-button bf26-button--green">Explorar Montjam <span aria-hidden="true">↗</span></a>
					
				</div>
			</article>
			<?php endif; ?>



            <section class="bf26-featured-producer bf26-featured-producer--selectos" id="bf26-selectos-promo" aria-labelledby="bf26-selectos-title">
                <p class="bf26-featured-producer__eyebrow">✳ BLACK FRIDAY · ESPECIALIDADES DE PATO</p>
                <article class="bf26-new-maker bf26-new-maker--selectos bf26-new-maker--featured bf26-new-maker--featured-selectos" id="bf26-selectos-de-castilla" data-bf26-promo="selectos" aria-labelledby="bf26-selectos-title">
						<div class="bf26-new-maker__visual">
							<img class="bf26-photo bf26-photo--editorial" src="<?php echo esc_url( $bf26_foie_photo_url ); ?>" alt="Foie gras emplatado en vajilla oscura, fotografía gastronómica de ambientación" width="960" height="610" loading="eager" decoding="async" />
							<span class="bf26-new-maker__visual-label">ESPECIALIDADES DE PATO</span>
						</div>
						<div class="bf26-new-maker__body">
							<p class="bf26-new-maker__category">SELECCIÓN GOURMET · FOIE GRAS · MAGRET · CONFIT</p>
							<h4 id="bf26-selectos-title">Selectos de Castilla</h4>
							<p class="bf26-new-maker__description">Foie gras, magret, confit y especialidades de pato para disfrutar de una mesa especial. <strong>Este Black Friday, llévate un 20 % de descuento en todos sus productos</strong> con el cupón <strong>BFSELECTOS</strong>.</p>
                            <p class="bf26-featured-producer__highlight"><span aria-hidden="true">✳</span> OFERTA ESPECIAL · 20 % DE DESCUENTO</p>
							<div class="bf26-new-maker__promotion">
								<strong class="bf26-new-maker__percentage">–20<span>%</span></strong>
								<span>DE DESCUENTO EN TODOS SUS PRODUCTOS</span>
							</div>
							<div class="bf26-new-maker__coupon">
								<span>CÓDIGO DE DESCUENTO</span>
								<button type="button" class="bf26-coupon" data-bf26-copy="BFSELECTOS" aria-label="Copiar cupón BFSELECTOS">BFSELECTOS <span aria-hidden="true">⧉</span></button>
							</div>
							<a class="bf26-button bf26-button--green bf26-new-maker__cta" href="<?php echo esc_url( $bf26_selectos_url ); ?>">Ver sus especialidades <span aria-hidden="true">↗</span></a>
						</div>
					</article>
            </section>

            <section class="bf26-featured-producer" id="bf26-tolecarnes-promo" aria-labelledby="bf26-tolecarnes-title">
                <p class="bf26-featured-producer__eyebrow">✳ BLACK FRIDAY · CARNES DE TOLEDO</p>
                <article class="bf26-new-maker bf26-new-maker--tolecarnes bf26-new-maker--featured" id="bf26-tolecarnes" data-bf26-promo="tolecarnes" aria-labelledby="bf26-tolecarnes-title">
						<div class="bf26-new-maker__visual">
							<img class="bf26-photo bf26-photo--editorial" src="<?php echo esc_url( $bf26_grill_photo_url ); ?>" alt="Vacuno a la brasa con llamas, fotografía gastronómica de ambientación" width="960" height="610" loading="eager" decoding="async" />
							<span class="bf26-new-maker__visual-label">CARNES PARA DISFRUTAR</span>
						</div>
						<div class="bf26-new-maker__body">
							<p class="bf26-new-maker__category">CARNES · CORTES Y LOTES</p>
							<h4 id="bf26-tolecarnes-title">Tolecarnes</h4>
							<p class="bf26-new-maker__description">Carnes seleccionadas, cortes de ternera y lotes para disfrutar de una buena parrilla.</p>
<div class="bf26-featured-producer__shipping" role="note"><span aria-hidden="true" class="bf26-featured-producer__shipping-icon">✳</span><div><strong>Envío gratuito a Madrid y Toledo</strong><span>En productos de Tolecarnes. El coste definitivo se confirma en el carrito según la dirección de entrega.</span></div></div>
							<div class="bf26-new-maker__promotion">
								<strong class="bf26-new-maker__percentage">–10<span>%</span></strong>
								<span>DE DESCUENTO EN SUS PRODUCTOS</span>
							</div>
							<div class="bf26-new-maker__coupon">
								<span>CÓDIGO DE DESCUENTO</span>
								<button type="button" class="bf26-coupon" data-bf26-copy="BFTOLECARNES" aria-label="Copiar cupón BFTOLECARNES">BFTOLECARNES <span aria-hidden="true">⧉</span></button>
							</div>
							<a class="bf26-button bf26-button--green bf26-new-maker__cta" href="<?php echo esc_url( $bf26_tolecarnes_url ); ?>">Descubrir sus carnes <span aria-hidden="true">↗</span></a>
						</div>
					</article>
            </section>

			<?php if ( $bf26_exotic_packs ) : ?>
			<section class="bf26-exotic" id="packs-carnes-exoticas" aria-labelledby="bf26-exotic-title">
				<div class="bf26-exotic__head">
					<div class="bf26-exotic__intro">
						<p class="bf26-exotic__eyebrow"><span aria-hidden="true">✳</span> SELECCIÓN ESPECIAL · BLACK FRIDAY</p>
						<h3 id="bf26-exotic-title">Packs de <em>carnes exóticas</em></h3>
						<p>Descubre nuestras selecciones de carnes exóticas, de caza y wagyu premium, reunidas en siete packs a <strong>precios especiales</strong>.</p>
					</div>
					<div class="bf26-exotic__wallet">
						<span class="bf26-exotic__wallet-icon" aria-hidden="true">✳</span>
						<p><strong>Y además, un 2 % para tu monedero.</strong><span>Compra con tu cuenta registrada y la sesión iniciada. El cashback se calcula sobre los productos, sin gastos de envío, y se acredita cuando se complete el pedido.</span></p>
					</div>
				</div>
				<div class="bf26-exotic__grid" aria-label="Los siete packs de carnes exóticas y cortes premium">
					<?php foreach ( $bf26_exotic_packs as $bf26_exotic_pack ) : ?>
					<a class="bf26-exotic__item" href="<?php echo esc_url( $bf26_exotic_pack['url'] ); ?>" aria-label="<?php echo esc_attr( 'Ver ' . $bf26_exotic_pack['title'] . ', ' . wp_strip_all_tags( wc_price( $bf26_exotic_pack['price'] ) ) ); ?>">
						<span class="bf26-exotic__media" aria-hidden="true"><?php if ( $bf26_exotic_pack['image'] ) { echo $bf26_exotic_pack['image']; } else { echo '<span class="bf26-exotic__placeholder">✳</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="bf26-exotic__info"><span class="bf26-exotic__name"><?php echo esc_html( $bf26_exotic_pack['title'] ); ?></span><span class="bf26-exotic__price"><?php echo wp_kses_post( wc_price( $bf26_exotic_pack['price'] ) ); ?></span></span>
						<span class="bf26-exotic__arrow" aria-hidden="true">↗</span>
					</a>
					<?php endforeach; ?>
				</div>
				<p class="bf26-exotic__note">El 2 % del Club EMDO está sujeto a las <a href="#club-emdo">condiciones del monedero</a>.</p>
			</section>
			<?php endif; ?>

			<section class="bf26-new-makers" id="bf26-nuevos-productores" aria-labelledby="bf26-new-makers-title">
				<div class="bf26-new-makers__header">
					<div>
						<p class="bf26-new-makers__eyebrow"><span aria-hidden="true">✳</span> MÁS OFERTAS · BLACK FRIDAY</p>
						<h3 id="bf26-new-makers-title">Más productores. <em>Más para disfrutar.</em></h3>
					</div>
					<p>Aceite de oliva virgen extra, chocolates artesanos, productos de la huerta, café de especialidad y quesos. Descubre <strong>cinco promociones con un 10 % de descuento</strong> utilizando el cupón de cada productor.</p>
				</div>
				<div class="bf26-new-makers__grid">
					<article class="bf26-new-maker bf26-new-maker--1957" id="bf26-1957" data-bf26-promo="1957" aria-labelledby="bf26-1957-title">
						<div class="bf26-new-maker__visual">
							<?php if ( $bf26_1957_photo ) { echo $bf26_1957_photo; } else { echo '<span class="bf26-new-maker__fallback" aria-hidden="true">🫒</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="bf26-new-maker__visual-label">ACEITE DE OLIVA VIRGEN EXTRA</span>
						</div>
						<div class="bf26-new-maker__body">
							<p class="bf26-new-maker__category">AOVE · TRADICIÓN DE CÓRDOBA</p>
							<h4 id="bf26-1957-title">1957</h4>
							<p class="bf26-new-maker__description">Aceites de oliva virgen extra de tradición familiar, con variedades como Arbequina, Picual y Lechín, en distintos formatos.</p>
							<div class="bf26-new-maker__promotion">
								<strong class="bf26-new-maker__percentage">–10<span>%</span></strong>
								<span>DE DESCUENTO EN TODOS SUS PRODUCTOS</span>
							</div>
							<div class="bf26-new-maker__coupon">
								<span>CÓDIGO DE DESCUENTO</span>
								<button type="button" class="bf26-coupon" data-bf26-copy="SINFILTRO" aria-label="Copiar cupón SINFILTRO">SINFILTRO <span aria-hidden="true">⧉</span></button>
							</div>
							<a class="bf26-button bf26-button--green bf26-new-maker__cta" href="<?php echo esc_url( $bf26_1957_url ); ?>">Descubrir sus aceites <span aria-hidden="true">↗</span></a>
						</div>
					</article>
					<article class="bf26-new-maker bf26-new-maker--chocolate" id="bf26-chocolates-isabel" data-bf26-promo="chocoisabel" aria-labelledby="bf26-chocolate-title">
						<div class="bf26-new-maker__visual">
							<?php if ( $bf26_chocolate_photo ) { echo $bf26_chocolate_photo; } else { echo '<span class="bf26-new-maker__fallback" aria-hidden="true">🍫</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="bf26-new-maker__visual-label">CHOCOLATE ARTESANO</span>
						</div>
						<div class="bf26-new-maker__body">
							<p class="bf26-new-maker__category">CHOCOLATE · ELABORACIÓN ARTESANA</p>
							<h4 id="bf26-chocolate-title">Chocolates Artesanos Isabel</h4>
							<p class="bf26-new-maker__description">Tabletas, bombones y cremas de cacao para dar un capricho especial a tu Black Friday.</p>
							<div class="bf26-new-maker__promotion">
								<strong class="bf26-new-maker__percentage">–10<span>%</span></strong>
								<span>DE DESCUENTO EN SUS PRODUCTOS</span>
							</div>
							<div class="bf26-new-maker__coupon">
								<span>CÓDIGO DE DESCUENTO</span>
								<button type="button" class="bf26-coupon" data-bf26-copy="CHOCOISABEL" aria-label="Copiar cupón CHOCOISABEL">CHOCOISABEL <span aria-hidden="true">⧉</span></button>
							</div>
							<a class="bf26-button bf26-button--green bf26-new-maker__cta" href="<?php echo esc_url( $bf26_chocolate_url ); ?>">Descubrir sus chocolates <span aria-hidden="true">↗</span></a>
						</div>
					</article>
					<article class="bf26-new-maker bf26-new-maker--huerta" id="bf26-huerta-ana-mary" data-bf26-promo="anamary" aria-labelledby="bf26-huerta-title">
						<div class="bf26-new-maker__visual">
							<?php if ( $bf26_huerta_photo ) { echo $bf26_huerta_photo; } else { echo '<span class="bf26-new-maker__fallback" aria-hidden="true">🥬</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="bf26-new-maker__visual-label">DIRECTO DE LA HUERTA</span>
						</div>
						<div class="bf26-new-maker__body">
							<p class="bf26-new-maker__category">HUERTA · PRODUCTOS DE TEMPORADA</p>
							<h4 id="bf26-huerta-title">La Huerta de Ana Mary</h4>
							<p class="bf26-new-maker__description">Hortalizas frescas, legumbres y elaboraciones de la huerta, directamente del productor.</p>
							<div class="bf26-new-maker__promotion">
								<strong class="bf26-new-maker__percentage">–10<span>%</span></strong>
								<span>DE DESCUENTO EN SUS PRODUCTOS</span>
							</div>
							<div class="bf26-new-maker__coupon">
								<span>CÓDIGO DE DESCUENTO</span>
								<button type="button" class="bf26-coupon" data-bf26-copy="BFANAMARY" aria-label="Copiar cupón BFANAMARY">BFANAMARY <span aria-hidden="true">⧉</span></button>
							</div>
							<a class="bf26-button bf26-button--green bf26-new-maker__cta" href="<?php echo esc_url( $bf26_huerta_url ); ?>">Explorar la huerta <span aria-hidden="true">↗</span></a>
						</div>
					</article>
					<article class="bf26-new-maker bf26-new-maker--ikore" id="bf26-ikore-kofi" data-bf26-promo="ikore" aria-labelledby="bf26-ikore-title">
						<div class="bf26-new-maker__visual">
							<?php if ( $bf26_ikore_photo ) { echo $bf26_ikore_photo; } else { echo '<span class="bf26-new-maker__fallback" aria-hidden="true">☕</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="bf26-new-maker__visual-label">CAFÉ DE ESPECIALIDAD</span>
						</div>
						<div class="bf26-new-maker__body">
							<p class="bf26-new-maker__category">CAFÉ · ORÍGENES SELECCIONADOS</p>
							<h4 id="bf26-ikore-title">Ikore Kofi</h4>
							<p class="bf26-new-maker__description">Cafés de especialidad de distintos orígenes, para descubrir nuevos aromas en cada taza.</p>
							<div class="bf26-new-maker__promotion">
								<strong class="bf26-new-maker__percentage">–10<span>%</span></strong>
								<span>DE DESCUENTO EN SUS PRODUCTOS</span>
							</div>
							<div class="bf26-new-maker__coupon">
								<span>CÓDIGO DE DESCUENTO</span>
								<button type="button" class="bf26-coupon" data-bf26-copy="BFIKORE" aria-label="Copiar cupón BFIKORE">BFIKORE <span aria-hidden="true">⧉</span></button>
							</div>
							<a class="bf26-button bf26-button--green bf26-new-maker__cta" href="<?php echo esc_url( $bf26_ikore_url ); ?>">Descubrir sus cafés <span aria-hidden="true">↗</span></a>
						</div>
					</article>

					<article class="bf26-new-maker bf26-new-maker--pajarete" id="bf26-quesos-pajarete" data-bf26-promo="pajarete" aria-labelledby="bf26-pajarete-title">
						<div class="bf26-new-maker__visual">
							<?php if ( $bf26_pajarete_photo ) { echo $bf26_pajarete_photo; } else { echo '<span class="bf26-new-maker__fallback" aria-hidden="true">🧀</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="bf26-new-maker__visual-label">QUESOS DE LA SIERRA DE CÁDIZ</span>
						</div>
						<div class="bf26-new-maker__body">
							<p class="bf26-new-maker__category">QUESOS · TRADICIÓN ARTESANA</p>
							<h4 id="bf26-pajarete-title">Quesos Pajarete</h4>
							<p class="bf26-new-maker__description">Quesos de cabra, oveja y mezcla con personalidad propia y todo el sabor de la Sierra de Cádiz.</p>
							<div class="bf26-new-maker__promotion">
								<strong class="bf26-new-maker__percentage">–10<span>%</span></strong>
								<span>DE DESCUENTO EN SUS PRODUCTOS</span>
							</div>
							<div class="bf26-new-maker__coupon">
								<span>CÓDIGO DE DESCUENTO</span>
								<button type="button" class="bf26-coupon" data-bf26-copy="BFPAJARETE" aria-label="Copiar cupón BFPAJARETE">BFPAJARETE <span aria-hidden="true">⧉</span></button>
							</div>
							<a class="bf26-button bf26-button--green bf26-new-maker__cta" href="<?php echo esc_url( $bf26_pajarete_url ); ?>">Descubrir sus quesos <span aria-hidden="true">↗</span></a>
						</div>
					</article>
				</div>
				<p class="bf26-new-makers__note">Aplica el código correspondiente en el carrito o al finalizar la compra. Los cupones de cada productor se utilizan por separado. Las fotografías de ambientación de Tolecarnes y Selectos de Castilla son ilustrativas. Además, con tu cuenta registrada acumulas el 2 % de cashback del <a href="#club-emdo">Club EMDO</a> durante Black Friday.</p>
			</section>
		</div>
	</section>

	<section class="bf26-club" id="club-emdo" aria-labelledby="bf26-club-title">
		<div class="bf26-wrap bf26-club__grid">
			<div class="bf26-club__copy">
				<p class="bf26-eyebrow bf26-eyebrow--light">ADEMÁS, ESTRENAMOS EL CLUB EMDO</p>
				<h2 id="bf26-club-title">Tus compras<br><em>vuelven a ti.</em></h2>
				<p class="bf26-club__lead">Lanzamos el <strong>Club EMDO</strong> para que tus compras tengan recompensa. Al comprar con tu cuenta registrada, acumulas cashback en tu monedero para utilizarlo en próximas compras. Durante <strong>Black Friday acumulas un 2 % de cashback en todas tus compras</strong>, con independencia del productor. <strong>Después de Black Friday, el cashback será del 1 %.</strong></p>
				<p class="bf26-club__sublead">Y, además de acumular cashback, puedes <strong>recargar tu monedero</strong> cuando quieras y utilizar tu saldo para pagar tus pedidos.</p>
				<div class="bf26-club__actions">
					<a href="<?php echo esc_url( $club_account_url ); ?>" class="bf26-button bf26-button--cream">Únete al Club EMDO <span aria-hidden="true">↗</span></a>
					<a href="<?php echo esc_url( $club_wallet_url ); ?>" class="bf26-club__textlink">Ver mi monedero <span aria-hidden="true">↗</span></a>
				</div>
			</div>
			<div class="bf26-club__visual">
				<div class="bf26-club__wallet" aria-label="Club EMDO: tu monedero digital">
					<div class="bf26-club__wallet-top"><span>CLUB EMDO</span><span aria-hidden="true">✳</span></div>
					<div class="bf26-club__wallet-main"><small>BLACK FRIDAY · CASHBACK</small><strong>2<span>%</span></strong><span>1 % DESPUÉS DEL BLACK FRIDAY</span></div>
					<div class="bf26-club__wallet-foot"><span>Acumula</span><span>Recarga</span><span>Disfruta</span></div>
				</div>
				<div class="bf26-club__token bf26-club__token--one" aria-hidden="true">+</div>
				<div class="bf26-club__token bf26-club__token--two" aria-hidden="true">✳</div>
			</div>
		</div>
		<div class="bf26-wrap bf26-club__footer">
			<div class="bf26-club__conditions">
				<span>Durante Black Friday, las compras con cuenta registrada acumulan un 2 % de cashback en el monedero; después de la campaña, acumularán el 1 %. No incluye gastos de envío, recargas ni tarjetas regalo.</span>
				<small>Los regalos y las mejoras de peso están sujetos a las condiciones de cada oferta. Consulta los detalles de cada promoción antes de comprar.</small>
			</div>
			<button type="button" class="bf26-reopen" data-bf26-open>Volver a ver la presentación ↗</button>
		</div>
	</section>
</main>

<div class="bf26-overlay" id="bf26-overlay" role="dialog" aria-modal="true" aria-labelledby="bf26-overlay-title" aria-describedby="bf26-overlay-desc" hidden>
	<div class="bf26-overlay__backdrop" aria-hidden="true"></div>
	<div class="bf26-overlay__panel" tabindex="-1">
		<button class="bf26-overlay__close" type="button" data-bf26-close aria-label="Cerrar cortinilla">✕ <span>CERRAR</span></button>
		<div class="bf26-overlay__layout">
			<div class="bf26-overlay__copy">
				<h2 id="bf26-overlay-title">BLACK<br><em>FRIDAY</em></h2>
				<p id="bf26-overlay-desc">Nuestro Black Friday trae <strong>descuentos, regalos y ventajas especiales</strong>. Además, estrenamos el <strong>Club EMDO</strong> con un <strong>2 % de cashback</strong> al comprar con tu cuenta registrada. <strong>Próximamente incorporaremos nuevas promociones. ¡Estate atento!</strong></p>
				<div class="bf26-overlay__buttons">
					<button type="button" class="bf26-overlay__cta" data-bf26-enter>Descubrir las promociones <span aria-hidden="true">↗</span></button>
					<button type="button" class="bf26-overlay__skip" data-bf26-close>Ahora no, ver la página</button>
				</div>
			</div>
			<div class="bf26-overlay__art" aria-hidden="true">
				<div class="bf26-overlay__circle"></div>
				<div class="bf26-overlay__photo">
					<?php if ( $bf26_show_hidalgo && $hidalgo_palet ) { echo $hidalgo_palet; } elseif ( $bf26_show_montjam && $montjam_ham ) { echo $montjam_ham; } else { echo '<div class="bf26-image-fallback">IBÉRICOS<br><strong>SELECCIONADOS</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-overlay__discount bf26-campaign-stamp" aria-label="Black Friday 2026: ofertas y regalos"><small>EDICIÓN 2026</small><strong>BLACK<em>FRIDAY</em></strong><span>OFERTAS <b aria-hidden="true">✳</b> REGALOS</span></div>
				<div class="bf26-overlay__asterisk">✳</div>
			</div>
		</div>
	</div>
</div>
<noscript><style>.bf26-overlay{display:none!important}</style></noscript>
<?php get_footer(); ?>
