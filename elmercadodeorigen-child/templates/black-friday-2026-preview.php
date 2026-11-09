<?php
/**
 * Página de revisión Black Friday 2026. Todo el contenido promocional es
 * una propuesta visual: no activa el cupón ni entrega regalos.
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
$cheese_url   = home_url( '/producto/miniqueso-a-la-pimienta-negra/' );
$chorizo_url  = home_url( '/producto/chorizo-cular-iberico-extra-loncheado-montjam-90g/' );
$club_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mi-cuenta/' );
$club_wallet_url  = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'monedero' ) : $club_account_url;

$hidalgo_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico', 'Jamón de bellota 100 % ibérico de Hidalgo de la Jara' );
$hidalgo_palet = emdo_bf2026_photo( 'paleta-de-bellota-100-iberico', 'Paleta de bellota 100 % ibérica de Hidalgo de la Jara' );
$montjam_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico-montjam', 'Jamón de bellota 100 % ibérico de Montjam' );
$montjam_slice = emdo_bf2026_photo( 'jamon-bellota-100-iberico-loncheado-montjam-90g', 'Sobre de jamón de bellota 100 % ibérico loncheado Montjam' );
$cheese        = emdo_bf2026_photo( 'miniqueso-a-la-pimienta-negra', 'Miniqueso de oveja Pajarete de 100 gramos' );
$chorizo       = emdo_bf2026_photo( 'chorizo-cular-iberico-extra-loncheado-montjam-90g', 'Sobre de chorizo ibérico loncheado de Montjam' );

get_header();
?>
<main id="contenido" class="bf26" aria-labelledby="bf26-title">

	<section class="bf26-hero">
		<div class="bf26-wrap bf26-hero__grid">
			<div class="bf26-hero__copy">
				<h1 id="bf26-title">Black Friday.<em>15 % menos.</em><span>Un regalo más.</span></h1>
				<p class="bf26-hero__lead">Tus ibéricos favoritos tienen premio: disfruta de un <strong>15 % de descuento</strong> con el código <strong>BFEMDO</strong> y llévate un regalo extra al elegir un jamón o una paleta.</p>
				<div class="bf26-hero__buttons">
					<a class="bf26-button bf26-button--cream" href="#ofertas">Descubrir las ofertas <span aria-hidden="true">↗</span></a>
				</div>
				<div class="bf26-hero__fine">15 % DE DESCUENTO <i></i> CÓDIGO BFEMDO <i></i> REGALO EXTRA</div>
			</div>
			<div class="bf26-hero__visual" aria-label="Selección de productos ibéricos de El Mercado de Origen">
				<div class="bf26-hero__frame bf26-hero__frame--back">
					<?php if ( $montjam_ham ) { echo $montjam_ham; } else { echo '<div class="bf26-image-fallback">MONTJAM<br><strong>Ibéricos</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-hero__frame bf26-hero__frame--front">
					<?php if ( $hidalgo_palet ) { echo $hidalgo_palet; } else { echo '<div class="bf26-image-fallback">HIDALGO<br><strong>DE LA JARA</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-hero__seal"><small>OFERTA<br>ESPECIAL</small><strong>15<span>%</span></strong><span>EN IBÉRICOS</span></div>
				<div class="bf26-hero__stamp" aria-hidden="true">CALIDAD <span>✳</span> TRADICIÓN <span>✳</span> SABOR</div>
			</div>
		</div>
		<div class="bf26-hero__bigtype" aria-hidden="true">BLACK FRIDAY</div>
	</section>

	<div class="bf26-band" aria-label="Resumen de las ofertas de Black Friday">
		<div class="bf26-wrap bf26-band__inner">
			<span>TUS FAVORITOS, CON VENTAJA</span><span class="bf26-band__star">✳</span>
			<strong>–15 % CON BFEMDO</strong><span class="bf26-band__star">✳</span>
			<span>REGALOS CON JAMONES Y PALETAS</span>
		</div>
	</div>

	<section id="ofertas" class="bf26-offers">
		<div class="bf26-wrap">
			<div class="bf26-heading">
				<div>
					<p class="bf26-eyebrow">LAS PROMOCIONES</p>
					<h2>15 % de descuento.<br><em>Y un detalle que enamora.</em></h2>
				</div>
				<p>El Black Friday sabe mejor cuando viene con regalo. Descubre las ofertas y elige tu próximo capricho ibérico.</p>
			</div>

			<article class="bf26-offer bf26-offer--hidalgo" aria-labelledby="bf26-hidalgo-title">
				<div class="bf26-offer__media">
					<div class="bf26-offer__photobg">
						<?php if ( $hidalgo_ham ) { echo $hidalgo_ham; } else { echo '<div class="bf26-image-fallback">HIDALGO<br><strong>DE LA JARA</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="bf26-offer__imagecaption">HIDALGO DE LA JARA <span>·</span> LOS PEDROCHES</div>
					<div class="bf26-offer__number">01</div>
				</div>
				<div class="bf26-offer__content">
					<div class="bf26-offer__top"><span class="bf26-offer__origin">DE LA DEHESA DE LOS PEDROCHES</span><span class="bf26-offer__tag">15% DE DESCUENTO</span></div>
					<h3 id="bf26-hidalgo-title">Hidalgo <em>de la Jara</em></h3>
					<p class="bf26-offer__description">Ese jamón que llevas tiempo queriendo probar, ahora con un motivo más para decidirte. Aprovecha el descuento en toda su selección.</p>
					<div class="bf26-offer__discount"><strong>–15<span>%</span></strong><div><span>EN TODOS SUS PRODUCTOS</span><p>Introduce el código <button type="button" class="bf26-coupon" data-bf26-copy="BFEMDO" aria-label="Copiar cupón BFEMDO">BFEMDO <span aria-hidden="true">⧉</span></button> al finalizar tu compra.</p></div></div>
					<div class="bf26-gift">
						<div class="bf26-gift__image">
							<?php if ( $cheese ) { echo $cheese; } else { echo '<span class="bf26-gift__illustration" aria-hidden="true">✷</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div class="bf26-gift__text"><span>Y ADEMÁS, UN REGALO MUY ESPECIAL</span><strong>Un miniqueso Pajarete de 100 g</strong><p>Con la compra de cualquier jamón o paleta de Hidalgo de la Jara, ya sea entero o loncheado. <a href="<?php echo esc_url( $cheese_url ); ?>">Conoce el miniqueso ↗</a></p></div>
						<div class="bf26-gift__icon" aria-hidden="true">✳</div>
					</div>
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
					<div class="bf26-offer__top"><span class="bf26-offer__origin">EL CARÁCTER DE LOS IBÉRICOS DE HUELVA</span><span class="bf26-offer__tag">15% DE DESCUENTO</span></div>
					<h3 id="bf26-montjam-title">Montjam<em>.</em></h3>
					<p class="bf26-offer__description">Para una ocasión especial o simplemente porque te apetece. Descubre sus ibéricos con un 15 % menos y un regalo extra.</p>
					<div class="bf26-offer__discount"><strong>–15<span>%</span></strong><div><span>EN TODOS SUS PRODUCTOS</span><p>Introduce el código <button type="button" class="bf26-coupon" data-bf26-copy="BFEMDO" aria-label="Copiar cupón BFEMDO">BFEMDO <span aria-hidden="true">⧉</span></button> al finalizar tu compra.</p></div></div>
					<div class="bf26-gift bf26-gift--montjam">
						<div class="bf26-gift__image">
							<?php if ( $chorizo ) { echo $chorizo; } else { echo '<span class="bf26-gift__illustration" aria-hidden="true">✷</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div class="bf26-gift__text"><span>Y PARA COMPLETAR LA EXPERIENCIA</span><strong>Un sobre de chorizo + uno de salchichón</strong><p>Con la compra de cualquier jamón o paleta Montjam, entero o loncheado. <a href="<?php echo esc_url( $chorizo_url ); ?>">Ver el chorizo loncheado ↗</a></p></div>
						<div class="bf26-gift__icon" aria-hidden="true">✳</div>
					</div>
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
				<p class="bf26-club__lead">Y el Black Friday es solo el principio. Si tienes una cuenta en El Mercado de Origen, con Club EMDO recuperas el <strong>1 % del importe de los productos de tus pedidos válidos</strong> en tu monedero para tus próximas compras.</p>
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
				<span>El 1 % se acredita en pedidos válidos; no incluye envíos, recargas ni tarjetas regalo.</span>
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
				<p id="bf26-overlay-desc">Este Black Friday, elige tus ibéricos favoritos con <strong>un 15 % de descuento</strong>. Y si compras un jamón o una paleta, tenemos un regalo para ti.</p>
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
				<div class="bf26-overlay__discount"><small>OFERTA ESPECIAL</small><strong>–15<span>%</span></strong><span>CÓDIGO BFEMDO</span></div>
				<div class="bf26-overlay__asterisk">✳</div>
			</div>
		</div>
	</div>
</div>
<noscript><style>.bf26-overlay{display:none!important}</style></noscript>
<?php get_footer(); ?>
