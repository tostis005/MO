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

$hidalgo_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico', 'Jamón de bellota 100 % ibérico de Hidalgo de la Jara' );
$hidalgo_palet = emdo_bf2026_photo( 'paleta-de-bellota-100-iberico', 'Paleta de bellota 100 % ibérica de Hidalgo de la Jara' );
$montjam_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico-montjam', 'Jamón de bellota 100 % ibérico de Montjam' );
$montjam_slice = emdo_bf2026_photo( 'jamon-bellota-100-iberico-loncheado-montjam-90g', 'Sobre de jamón de bellota 100 % ibérico loncheado Montjam' );

get_header();
?>
<main id="contenido" class="bf26" aria-labelledby="bf26-title">

	<section class="bf26-hero">
		<div class="bf26-wrap bf26-hero__grid">
			<div class="bf26-hero__copy">
				<h1 id="bf26-title">Black Friday.<em>Ofertas que apetecen.</em><span>Y mucho por descubrir.</span></h1>
				<p class="bf26-hero__lead">El Black Friday llega con <strong>descuentos especiales, regalos y ventajas exclusivas</strong> en una selección de productos que merece la pena descubrir. Explora las promociones de cada productor y elige tus favoritas.</p>
				<div class="bf26-hero__buttons">
					<a class="bf26-button bf26-button--cream" href="#ofertas">Ver todas las promociones <span aria-hidden="true">↗</span></a>
				</div>
				<div class="bf26-hero__fine">DESCUENTOS ESPECIALES <i></i> REGALOS <i></i> OPORTUNIDADES PARA DISFRUTAR</div>
			</div>
			<div class="bf26-hero__visual" aria-label="Selección de productos ibéricos de El Mercado de Origen">
				<div class="bf26-hero__frame bf26-hero__frame--back">
					<?php if ( $montjam_ham ) { echo $montjam_ham; } else { echo '<div class="bf26-image-fallback">BLACK<br><strong>FRIDAY</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-hero__frame bf26-hero__frame--front">
					<?php if ( $hidalgo_palet ) { echo $hidalgo_palet; } else { echo '<div class="bf26-image-fallback">HIDALGO<br><strong>DE LA JARA</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-hero__seal"><small>EDICIÓN<br>ESPECIAL</small><strong>BF</strong><span>OFERTAS Y REGALOS</span></div>
				<div class="bf26-hero__stamp" aria-hidden="true">CALIDAD <span>✳</span> TRADICIÓN <span>✳</span> SABOR</div>
			</div>
		</div>
		<div class="bf26-hero__bigtype" aria-hidden="true">BLACK FRIDAY</div>
	</section>

	<div class="bf26-band" aria-label="Resumen de las ofertas de Black Friday">
		<div class="bf26-wrap bf26-band__inner">
			<span>BLACK FRIDAY EN EL MERCADO DE ORIGEN</span><span class="bf26-band__star">✳</span>
			<strong>DESCUENTOS Y REGALOS ESPECIALES</strong><span class="bf26-band__star">✳</span>
			<span>DESCUBRE TODAS LAS PROMOCIONES</span>
		</div>
	</div>

	<section id="ofertas" class="bf26-offers">
		<div class="bf26-wrap">
			<div class="bf26-heading">
				<div>
					<p class="bf26-eyebrow">LAS PROMOCIONES</p>
					<h2>Promociones para elegir.<br><em>Ofertas para disfrutar.</em></h2>
				</div>
				<p>Cada productor tiene su propia promoción. Descubre sus descuentos, regalos y condiciones, y encuentra la oferta que estabas esperando.</p>
			</div>

			<article class="bf26-offer bf26-offer--hidalgo" id="hidalgo-oferta" aria-labelledby="bf26-hidalgo-title">
				<div class="bf26-offer__media">
					<div class="bf26-offer__photobg">
						<?php if ( $hidalgo_ham ) { echo $hidalgo_ham; } else { echo '<div class="bf26-image-fallback">HIDALGO<br><strong>DE LA JARA</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="bf26-offer__imagecaption">HIDALGO DE LA JARA <span>·</span> LOS PEDROCHES</div>
					<div class="bf26-offer__number">01</div>
				</div>
				<div class="bf26-offer__content">
					<div class="bf26-offer__top"><span class="bf26-offer__origin">DE LA DEHESA DE LOS PEDROCHES</span><span class="bf26-offer__tag">15 % EN TODA SU SELECCIÓN</span></div>
					<h3 id="bf26-hidalgo-title">Hidalgo <em>de la Jara</em></h3>
					<p class="bf26-offer__description">Llévate tus ibéricos favoritos con un 15 % de descuento en todos los productos de Hidalgo de la Jara de nuestra tienda. Y aprovecha las ventajas especiales para tus jamones y pedidos.</p>
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
					<p class="bf26-offer__cashback"><span aria-hidden="true">✳</span> <strong>¿Compras con tu cuenta?</strong> Además, acumulas el <strong>1 % en tu monedero Club EMDO</strong> por tus compras. <a href="#club-emdo">Descubre el club ↗</a></p>
					<a href="<?php echo esc_url( $hidalgo_url ); ?>" class="bf26-button bf26-button--green">Explorar Hidalgo de la Jara <span aria-hidden="true">↗</span></a>
					
				</div>
			</article>

			<article class="bf26-offer bf26-offer--montjam" aria-labelledby="bf26-montjam-title">
				<div class="bf26-offer__media">
					<div class="bf26-offer__photobg">
						<?php if ( $montjam_ham ) { echo $montjam_ham; } elseif ( $montjam_slice ) { echo $montjam_slice; } else { echo '<div class="bf26-image-fallback">MONTJAM<br><strong>Ibéricos</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="bf26-offer__imagecaption">MONTJAM <span>·</span> HUELVA</div>
					<div class="bf26-offer__number">02</div>
				</div>
				<div class="bf26-offer__content">
					<div class="bf26-offer__top"><span class="bf26-offer__origin">EL CARÁCTER DE LOS IBÉRICOS DE HUELVA</span><span class="bf26-offer__tag">15 % + EXTRA SELECCIONADOS*</span></div>
					<h3 id="bf26-montjam-title">Montjam<em>.</em></h3>
					<p class="bf26-offer__description">Este Black Friday, tus jamones Montjam tienen premio: <strong>15 % de descuento</strong> con su propio cupón, un <strong>extra automático en jamones seleccionados*</strong> y <strong>fuet de regalo en todos sus jamones</strong>.</p>
					<div class="bf26-offer__discount"><strong>–15<span>%</span></strong><div class="bf26-offer__discount-details"><span>CON SU CUPÓN PARA MONTJAM</span><div class="bf26-offer__coupon-row"><span class="bf26-offer__coupon-hint">CÓDIGO</span><button type="button" class="bf26-coupon" data-bf26-copy="BFEMDOM" aria-label="Copiar cupón BFEMDOM">BFEMDOM <span aria-hidden="true">⧉</span></button></div></div></div>
					<div class="bf26-montjam-extras" aria-label="Promociones adicionales de Montjam">
						<div class="bf26-montjam-extra" id="montjam-condiciones">
							<div class="bf26-montjam-extra__badge"><strong>+2,5<span>%</span></strong><small>ADICIONAL</small></div>
							<div class="bf26-montjam-extra__copy">
								<span class="bf26-hidalgo-benefits__eyebrow">SOLO EN JAMONES SELECCIONADOS <a href="#montjam-letra-pequena" aria-label="Ver condiciones del descuento adicional">*</a></span>
								<h4>Un extra más. <em>Sin hacer nada.</em></h4>
								<p>En <strong>jamones de bellota 100 % ibérico de más de 7 kg</strong>, sumas un <strong>2,5 % adicional</strong> que se aplica <strong>automáticamente en el carrito</strong>. Para el 15 % general, introduce el cupón <strong>BFEMDOM</strong>.</p>
							</div>
						</div>
						<p class="bf26-montjam-extras__conditions" id="montjam-letra-pequena"><strong>* Condiciones de los jamones seleccionados:</strong> extra exclusivo para jamones Montjam de <strong>bellota 100 % ibérico</strong> con peso <strong>superior a 7 kg</strong>. No incluye otras piezas ni pesos de 7 kg o menos. El descuento adicional se refleja en el carrito; el 15 % requiere aplicar BFEMDOM.</p>
						<div class="bf26-montjam-gift">
							<div class="bf26-montjam-gift__emblem" aria-hidden="true">✳</div>
							<div>
								<span class="bf26-hidalgo-benefits__eyebrow">REGALO EN TODOS LOS JAMONES</span>
								<h4>Un fuet de regalo.</h4>
								<p>Con la compra de <strong>cualquier jamón Montjam</strong>. No necesitas llegar a un importe mínimo.</p>
							</div>
						</div>
					</div>
					<p class="bf26-offer__cashback"><span aria-hidden="true">✳</span> <strong>¿Compras con tu cuenta?</strong> Además, acumulas el <strong>1 % en tu monedero Club EMDO</strong> por tus compras. <a href="#club-emdo">Descubre el club ↗</a></p>
					<a href="<?php echo esc_url( $montjam_url ); ?>" class="bf26-button bf26-button--green">Explorar Montjam <span aria-hidden="true">↗</span></a>
					
				</div>
			</article>
		</div>
	</section>

	<section class="bf26-club" id="club-emdo" aria-labelledby="bf26-club-title">
		<div class="bf26-wrap bf26-club__grid">
			<div class="bf26-club__copy">
				<p class="bf26-eyebrow bf26-eyebrow--light">DESCUBRE EL CLUB EMDO</p>
				<h2 id="bf26-club-title">Tus compras<br><em>tienen recompensa.</em></h2>
				<p class="bf26-club__lead">Y el Black Friday es solo el principio. Si tienes una cuenta en El Mercado de Origen, con Club EMDO recuperas el <strong>1 % del importe de los productos de tus compras</strong> en tu monedero para disfrutarlo cuando quieras.</p>
				<p class="bf26-club__sublead">También puedes recargar tu saldo cuando quieras y utilizarlo para comprar de forma más cómoda.</p>
				<div class="bf26-club__actions">
					<a href="<?php echo esc_url( $club_account_url ); ?>" class="bf26-button bf26-button--cream">Únete al Club EMDO <span aria-hidden="true">↗</span></a>
					<a href="<?php echo esc_url( $club_wallet_url ); ?>" class="bf26-club__textlink">Ver mi monedero <span aria-hidden="true">↗</span></a>
				</div>
			</div>
			<div class="bf26-club__visual">
				<div class="bf26-club__wallet" aria-label="Club EMDO: tu monedero digital">
					<div class="bf26-club__wallet-top"><span>CLUB EMDO</span><span aria-hidden="true">✳</span></div>
					<div class="bf26-club__wallet-main"><small>RECOMPENSA POR COMPRA</small><strong>1<span>%</span></strong><span>PARA TU MONEDERO</span></div>
					<div class="bf26-club__wallet-foot"><span>Acumula</span><span>Recarga</span><span>Disfruta</span></div>
				</div>
				<div class="bf26-club__token bf26-club__token--one" aria-hidden="true">+</div>
				<div class="bf26-club__token bf26-club__token--two" aria-hidden="true">✳</div>
			</div>
		</div>
		<div class="bf26-wrap bf26-club__footer">
			<div class="bf26-club__conditions">
				<span>Con tu cuenta registrada, acumulas un 1 % del importe de tus productos en el monedero. No incluye envíos, recargas ni tarjetas regalo.</span>
				<small>Black Friday en preparación: el descuento y los regalos todavía no están activos. Fechas y condiciones pendientes de confirmar.</small>
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
				<p id="bf26-overlay-desc">Este Black Friday trae <strong>descuentos especiales, regalos y ventajas</strong> de nuestros productores. Descubre cada promoción y, si compras con tu cuenta, acumula además <strong>un 1 % en tu monedero Club EMDO</strong>.</p>
				<div class="bf26-overlay__buttons">
					<button type="button" class="bf26-overlay__cta" data-bf26-enter>Descubrir las promociones <span aria-hidden="true">↗</span></button>
					<button type="button" class="bf26-overlay__skip" data-bf26-close>Ahora no, ver la página</button>
				</div>
			</div>
			<div class="bf26-overlay__art" aria-hidden="true">
				<div class="bf26-overlay__circle"></div>
				<div class="bf26-overlay__photo">
					<?php if ( $hidalgo_palet ) { echo $hidalgo_palet; } elseif ( $montjam_ham ) { echo $montjam_ham; } else { echo '<div class="bf26-image-fallback">IBÉRICOS<br><strong>SELECCIONADOS</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-overlay__discount"><small>EDICIÓN ESPECIAL</small><strong>BF</strong><span>DESCUENTOS + REGALOS</span></div>
				<div class="bf26-overlay__asterisk">✳</div>
			</div>
		</div>
	</div>
</div>
<noscript><style>.bf26-overlay{display:none!important}</style></noscript>
<?php get_footer(); ?>
