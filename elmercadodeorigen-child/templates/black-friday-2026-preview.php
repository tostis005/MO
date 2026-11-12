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
	function emdo_bf2026_photo( string $slug, string $alt, string $size = 'large' ): string {
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
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
	}
}

$hidalgo_url  = home_url( '/tienda/hidalgo-de-la-jara/' );
$montjam_url  = home_url( '/tienda/montjam/' );
$club_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mi-cuenta/' );
$club_wallet_url  = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'monedero' ) : $club_account_url;

/**
 * Control de visibilidad de la landing, independiente de WooCommerce.
 * Primera fase del lanzamiento: únicamente Hidalgo de la Jara.
 * Montjam conserva su ficha y sus condiciones, pero permanece oculto
 * hasta que se autorice su incorporación posterior.
 * La opción emdo_bf2026_visible_sellers controla las fichas y fotos.
 */
$bf26_known_sellers = array( 'hidalgo', 'montjam' );
$bf26_launch_sellers = array( 'hidalgo' );
$bf26_enabled_option = get_option( 'emdo_bf2026_visible_sellers', $bf26_launch_sellers );
$bf26_enabled_option = is_array( $bf26_enabled_option ) ? $bf26_enabled_option : $bf26_launch_sellers;
$bf26_visible_sellers = array_values( array_intersect( $bf26_known_sellers, array_map( 'sanitize_key', $bf26_enabled_option ) ) );
$bf26_show_hidalgo = in_array( 'hidalgo', $bf26_visible_sellers, true );
$bf26_show_montjam = in_array( 'montjam', $bf26_visible_sellers, true );
$bf26_offer_position = 0;

$hidalgo_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico', 'Jamón de bellota 100 % ibérico de Hidalgo de la Jara' );
$hidalgo_palet = emdo_bf2026_photo( 'paleta-de-bellota-100-iberico', 'Paleta de bellota 100 % ibérica de Hidalgo de la Jara' );
$montjam_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico-montjam', 'Jamón de bellota 100 % ibérico de Montjam' );
$montjam_slice = emdo_bf2026_photo( 'jamon-bellota-100-iberico-loncheado-montjam-90g', 'Sobre de jamón de bellota 100 % ibérico loncheado Montjam' );

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
					<div class="bf26-offer__photobg">
						<?php if ( $hidalgo_ham ) { echo $hidalgo_ham; } else { echo '<div class="bf26-image-fallback">HIDALGO<br><strong>DE LA JARA</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="bf26-offer__imagecaption">HIDALGO DE LA JARA <span>·</span> LOS PEDROCHES</div>
					<div class="bf26-offer__number"><?php echo esc_html( sprintf( '%02d', $bf26_offer_position ) ); ?></div>
				</div>
				<div class="bf26-offer__content">
					<div class="bf26-offer__top"><span class="bf26-offer__origin">DE LA DEHESA DE LOS PEDROCHES</span><span class="bf26-offer__tag">15 % EN TODA SU SELECCIÓN</span></div>
					<h3 id="bf26-hidalgo-title">Hidalgo <em>de la Jara</em></h3>
					<p class="bf26-offer__description"><strong>15 % de descuento en todos los productos Hidalgo de la Jara de nuestra tienda.</strong> Aplica el cupón <strong>BFEMDO</strong> al hacer tu pedido. Además, descubre el salto al tramo de peso superior en jamones seleccionados y los regalos por importe.</p>
					<div class="bf26-offer__discount"><strong>–15<span>%</span></strong><div class="bf26-offer__discount-details"><span>EN TODOS SUS PRODUCTOS</span><div class="bf26-offer__coupon-row"><span class="bf26-offer__coupon-hint">CÓDIGO</span><button type="button" class="bf26-coupon" data-bf26-copy="BFEMDO" aria-label="Copiar cupón BFEMDO">BFEMDO <span aria-hidden="true">⧉</span></button></div></div></div>
					<div class="bf26-hidalgo-benefits" aria-label="Ventajas especiales de Black Friday para Hidalgo de la Jara">
						<div class="bf26-hidalgo-upgrade">
							<div class="bf26-hidalgo-upgrade__emblem" aria-hidden="true"><strong>+0,5</strong><span>KG</span></div>
							<div class="bf26-hidalgo-upgrade__copy" id="hidalgo-condiciones">
								<span class="bf26-hidalgo-benefits__eyebrow">VENTAJA EXTRA EN JAMONES SELECCIONADOS <a href="#hidalgo-condiciones" aria-label="Ver condiciones de los jamones seleccionados de Hidalgo">*</a></span>
								<h4>Compras un peso. <em>Recibes el siguiente.</em></h4>
								<p>Desde el tramo de <strong>7,5–8 kg</strong> en adelante, te enviamos el tramo superior, <strong>medio kilo más sin coste adicional</strong>. Por ejemplo, eliges 7,5–8 kg y recibes 8–8,5 kg.</p>
								<p class="bf26-hidalgo-upgrade__note"><strong>* Condiciones de los jamones seleccionados:</strong> exclusivamente jamones de <strong>brida negra</strong> de Hidalgo de la Jara, desde el tramo de <strong>7,5–8 kg</strong> en adelante, tanto con Denominación de Origen Protegida (DOP) como sin DOP. No incluye paletas ni tramos inferiores.</p>
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
					<p class="bf26-offer__cashback"><span aria-hidden="true">✳</span> <strong>Estrenamos el Club EMDO:</strong> compra con tu cuenta y acumula <strong>2 % de cashback durante Black Friday</strong> en tu monedero. Después de la campaña, seguirás acumulando el 1 %. <a href="#club-emdo">Descubre el club ↗</a></p>
					<a href="<?php echo esc_url( $hidalgo_url ); ?>" class="bf26-button bf26-button--green">Explorar Hidalgo de la Jara <span aria-hidden="true">↗</span></a>
					
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
					<p class="bf26-offer__description"><strong>15 % de descuento en todos los productos Montjam de nuestra tienda.</strong> Introduce <strong>BFMONTJAM</strong> al hacer tu pedido. Y si eliges uno de los jamones seleccionados*, tendrás un descuento adicional automático en el carrito. <strong>Todos sus jamones incluyen un fuet de regalo.</strong></p>
					<div class="bf26-offer__discount"><strong>–15<span>%</span></strong><div class="bf26-offer__discount-details"><span>EN TODOS LOS PRODUCTOS MONTJAM</span><div class="bf26-offer__coupon-row"><span class="bf26-offer__coupon-hint">CÓDIGO</span><button type="button" class="bf26-coupon" data-bf26-copy="BFMONTJAM" aria-label="Copiar cupón BFMONTJAM">BFMONTJAM <span aria-hidden="true">⧉</span></button></div></div></div>
					<div class="bf26-montjam-extras" aria-label="Promociones adicionales de Montjam">
						<div class="bf26-montjam-extra" id="montjam-condiciones">
							<div class="bf26-montjam-extra__badge"><strong>+2,5<span>%</span></strong><small>ADICIONAL</small></div>
							<div class="bf26-montjam-extra__copy">
								<span class="bf26-hidalgo-benefits__eyebrow">SOLO EN JAMONES SELECCIONADOS <a href="#montjam-letra-pequena" aria-label="Ver condiciones del descuento adicional">*</a></span>
								<h4>Un extra más. <em>Sin hacer nada.</em></h4>
								<p>Solo en los <strong>jamones Montjam de bellota 100 % ibérico de 7 kg o más</strong>, disfrutas de un <strong>2,5 % adicional</strong>. Este extra se aplica <strong>automáticamente en el carrito</strong>, sin introducir otro código. El 15 % para todos los productos Montjam se consigue con <strong>BFMONTJAM</strong>.</p>
							</div>
						</div>
						<p class="bf26-montjam-extras__conditions" id="montjam-letra-pequena"><strong>* Condiciones del 2,5 % adicional:</strong> solo para jamones Montjam de <strong>bellota 100 % ibérico</strong> con peso <strong>igual o superior a 7 kg</strong>; no incluye paletas, embutidos ni jamones de menos de 7 kg. Este descuento se refleja automáticamente en el carrito. El <strong>15 % general de Montjam</strong> se obtiene al introducir <strong>BFMONTJAM</strong>.</p>
						<div class="bf26-montjam-gift">
							<div class="bf26-montjam-gift__emblem" aria-hidden="true">✳</div>
							<div>
								<span class="bf26-hidalgo-benefits__eyebrow">REGALO EN TODOS LOS JAMONES</span>
								<h4>Un fuet de regalo.</h4>
								<p>Con la compra de <strong>cualquier jamón Montjam</strong>. No necesitas llegar a un importe mínimo.</p>
							</div>
						</div>
					</div>
					<p class="bf26-offer__cashback"><span aria-hidden="true">✳</span> <strong>Estrenamos el Club EMDO:</strong> compra con tu cuenta y acumula <strong>2 % de cashback durante Black Friday</strong> en tu monedero. Después de la campaña, seguirás acumulando el 1 %. <a href="#club-emdo">Descubre el club ↗</a></p>
					<a href="<?php echo esc_url( $montjam_url ); ?>" class="bf26-button bf26-button--green">Explorar Montjam <span aria-hidden="true">↗</span></a>
					
				</div>
			</article>
			<?php endif; ?>
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
				<p id="bf26-overlay-desc">Descubre nuestro <strong>Black Friday con Hidalgo de la Jara</strong>: 15 % con <strong>BFEMDO</strong>, regalos y ventajas especiales. Además, estrenamos el <strong>Club EMDO</strong> con un <strong>2 % de cashback</strong> al comprar con tu cuenta. <strong>Próximamente llegarán nuevas promociones.</strong></p>
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
