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

$hidalgo_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico', 'Jamón de bellota 100 % ibérico de Hidalgo de la Jara' );
$hidalgo_palet = emdo_bf2026_photo( 'paleta-de-bellota-100-iberico', 'Paleta de bellota 100 % ibérica de Hidalgo de la Jara' );
$montjam_ham   = emdo_bf2026_photo( 'jamon-de-bellota-100-iberico-montjam', 'Jamón de bellota 100 % ibérico de Montjam' );
$montjam_slice = emdo_bf2026_photo( 'jamon-bellota-100-iberico-loncheado-montjam-90g', 'Sobre de jamón de bellota 100 % ibérico loncheado Montjam' );
$cheese        = emdo_bf2026_photo( 'miniqueso-a-la-pimienta-negra', 'Miniqueso de oveja Pajarete de 100 gramos' );
$chorizo       = emdo_bf2026_photo( 'chorizo-cular-iberico-extra-loncheado-montjam-90g', 'Sobre de chorizo ibérico loncheado de Montjam' );

get_header();
?>
<main id="contenido" class="bf26" aria-labelledby="bf26-title">
	<div class="bf26-previewbar">
		<div class="bf26-wrap bf26-previewbar__inner">
			<span class="bf26-previewbar__status"><span class="bf26-previewbar__dot"></span> VISTA PREVIA · CAMPAÑA NO ACTIVADA</span>
			<button type="button" class="bf26-previewbar__reopen" data-bf26-open>↗ Volver a ver la cortinilla</button>
		</div>
	</div>

	<section class="bf26-hero">
		<div class="bf26-wrap bf26-hero__grid">
			<div class="bf26-hero__copy">
				<p class="bf26-eyebrow bf26-eyebrow--light"><span class="bf26-spark">✳</span> NOVIEMBRE 2026 · EDICIÓN ESPECIAL</p>
				<h1 id="bf26-title">El Black Friday <em>tiene mucho</em> <span>origen.</span></h1>
				<p class="bf26-hero__lead">De la dehesa a tu mesa. Una selección de ibéricos extraordinarios con un descuento que sabe mejor cuando viene acompañado de un regalo.</p>
				<div class="bf26-hero__buttons">
					<a class="bf26-button bf26-button--cream" href="#ofertas">Descubrir las ofertas <span aria-hidden="true">↗</span></a>
					<a class="bf26-hero__textlink" href="#como-funciona">Así funcionaría <span aria-hidden="true">↓</span></a>
				</div>
				<div class="bf26-hero__fine">DOS PRODUCTORES <i></i> UN SOLO CÓDIGO <i></i> REGALOS ESPECIALES</div>
			</div>
			<div class="bf26-hero__visual" aria-label="Selección de productos ibéricos de El Mercado de Origen">
				<div class="bf26-hero__frame bf26-hero__frame--back">
					<?php if ( $montjam_ham ) { echo $montjam_ham; } else { echo '<div class="bf26-image-fallback">MONTJAM<br><strong>Ibéricos</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-hero__frame bf26-hero__frame--front">
					<?php if ( $hidalgo_palet ) { echo $hidalgo_palet; } else { echo '<div class="bf26-image-fallback">HIDALGO<br><strong>DE LA JARA</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-hero__seal"><small>DESCUENTO<br>PROPUESTO</small><strong>15<span>%</span></strong><span>EN IBÉRICOS</span></div>
				<div class="bf26-hero__stamp" aria-hidden="true">ORIGEN <span>✳</span> CALIDAD <span>✳</span> SABOR</div>
			</div>
		</div>
		<div class="bf26-hero__bigtype" aria-hidden="true">BLACK FRIDAY</div>
	</section>

	<div class="bf26-band" aria-label="Resumen de promoción propuesta">
		<div class="bf26-wrap bf26-band__inner">
			<span>PRODUCTORES CON HISTORIA</span><span class="bf26-band__star">✳</span>
			<strong>–15 % CON BFM2</strong><span class="bf26-band__star">✳</span>
			<span>REGALOS CON JAMONES Y PALETAS</span>
		</div>
	</div>

	<section id="ofertas" class="bf26-offers">
		<div class="bf26-wrap">
			<div class="bf26-heading">
				<div>
					<p class="bf26-eyebrow">01 / LAS PROMOCIONES</p>
					<h2>Elige tu origen.<br><em>Nosotros ponemos el detalle.</em></h2>
				</div>
				<p>Dos productores, el mismo código y un regalo diferente en cada selección. Descubre las propuestas para este Black Friday.</p>
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
					<p class="bf26-offer__description">El sabor de una tradición ibérica de cinco generaciones. Jamones, paletas y embutidos con una historia que empieza en la dehesa.</p>
					<div class="bf26-offer__discount"><strong>–15<span>%</span></strong><div><span>EN TODOS SUS PRODUCTOS</span><p>Introduce el código <button type="button" class="bf26-coupon" data-bf26-copy="BFM2" aria-label="Copiar cupón BFM2">BFM2 <span aria-hidden="true">⧉</span></button> al finalizar tu compra.</p></div></div>
					<div class="bf26-gift">
						<div class="bf26-gift__image">
							<?php if ( $cheese ) { echo $cheese; } else { echo '<span class="bf26-gift__illustration" aria-hidden="true">✷</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div class="bf26-gift__text"><span>Y ADEMÁS, UN REGALO MUY ESPECIAL</span><strong>Un miniqueso Pajarete de 100 g</strong><p>Con la compra de cualquier jamón o paleta de Hidalgo de la Jara, ya sea entero o loncheado. <a href="<?php echo esc_url( $cheese_url ); ?>">Conoce el miniqueso ↗</a></p></div>
						<div class="bf26-gift__icon" aria-hidden="true">✳</div>
					</div>
					<a href="<?php echo esc_url( $hidalgo_url ); ?>" class="bf26-button bf26-button--green">Explorar Hidalgo de la Jara <span aria-hidden="true">↗</span></a>
					<p class="bf26-offer__notice">Promoción propuesta para revisión. Aún no disponible en el carrito.</p>
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
					<p class="bf26-offer__description">Una selección de jamones, paletas y embutidos que lleva el gusto por el buen ibérico a cada mesa.</p>
					<div class="bf26-offer__discount"><strong>–15<span>%</span></strong><div><span>EN TODOS SUS PRODUCTOS</span><p>Introduce el código <button type="button" class="bf26-coupon" data-bf26-copy="BFM2" aria-label="Copiar cupón BFM2">BFM2 <span aria-hidden="true">⧉</span></button> al finalizar tu compra.</p></div></div>
					<div class="bf26-gift bf26-gift--montjam">
						<div class="bf26-gift__image">
							<?php if ( $chorizo ) { echo $chorizo; } else { echo '<span class="bf26-gift__illustration" aria-hidden="true">✷</span>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div class="bf26-gift__text"><span>Y PARA COMPLETAR LA EXPERIENCIA</span><strong>Un sobre de chorizo + uno de salchichón</strong><p>Con la compra de cualquier jamón o paleta Montjam, entero o loncheado. <a href="<?php echo esc_url( $chorizo_url ); ?>">Ver el chorizo loncheado ↗</a></p></div>
						<div class="bf26-gift__icon" aria-hidden="true">✳</div>
					</div>
					<a href="<?php echo esc_url( $montjam_url ); ?>" class="bf26-button bf26-button--green">Explorar Montjam <span aria-hidden="true">↗</span></a>
					<p class="bf26-offer__notice">Promoción propuesta. El sobre de salchichón de regalo está pendiente de definir.</p>
				</div>
			</article>
		</div>
	</section>

	<section class="bf26-coupon-section" id="como-funciona">
		<div class="bf26-wrap bf26-coupon-section__grid">
			<div class="bf26-coupon-section__intro"><p class="bf26-eyebrow bf26-eyebrow--light">02 / ASÍ DE SENCILLO</p><h2>Una buena elección.<br><em>Una gran oportunidad.</em></h2><p>Todo preparado para que el descuento y el regalo resulten fáciles de entender.</p></div>
			<div class="bf26-steps">
				<div><span>01</span><strong>Elige tu productor</strong><p>Hidalgo de la Jara o Montjam. Descubre su selección de ibéricos.</p></div>
				<div><span>02</span><strong>Usa el código BFM2</strong><p>La propuesta es un 15 % de descuento en sus productos.</p></div>
				<div><span>03</span><strong>Añade un jamón o paleta</strong><p>En pieza o loncheado, para recibir el regalo específico de cada productor.</p></div>
			</div>
		</div>
	</section>

	<section class="bf26-close">
		<div class="bf26-wrap bf26-close__inner"><span class="bf26-eyebrow">UNA CELEBRACIÓN DEL BUEN PRODUCTO</span><h2>Las mejores historias<br><em>empiezan en el origen.</em></h2><a href="#ofertas" class="bf26-button bf26-button--green">Volver a las promociones <span aria-hidden="true">↑</span></a><p>De momento, esta página es una simulación visual: los descuentos y los regalos no se han configurado ni activado en WooCommerce. Condiciones, fechas y existencias pendientes de confirmar.</p></div>
	</section>
</main>

<div class="bf26-overlay" id="bf26-overlay" role="dialog" aria-modal="true" aria-labelledby="bf26-overlay-title" aria-describedby="bf26-overlay-desc" hidden>
	<div class="bf26-overlay__backdrop" aria-hidden="true"></div>
	<div class="bf26-overlay__panel" tabindex="-1">
		<button class="bf26-overlay__close" type="button" data-bf26-close aria-label="Cerrar cortinilla">✕ <span>CERRAR</span></button>
		<div class="bf26-overlay__top"><span>EL MERCADO DE ORIGEN</span><span>EDICIÓN BLACK FRIDAY 2026</span></div>
		<div class="bf26-overlay__layout">
			<div class="bf26-overlay__copy">
				<p class="bf26-overlay__kicker">EL SABOR DE UNA BUENA OPORTUNIDAD <span>✳</span></p>
				<h2 id="bf26-overlay-title">BLACK<br><em>FRIDAY</em><span>CON ORIGEN.</span></h2>
				<p id="bf26-overlay-desc">Ibéricos de productores seleccionados, <strong>15 % de descuento</strong> y regalos muy especiales con jamones y paletas.</p>
				<div class="bf26-overlay__buttons">
					<button type="button" class="bf26-overlay__cta" data-bf26-enter>Descubrir las promociones <span aria-hidden="true">↗</span></button>
					<button type="button" class="bf26-overlay__skip" data-bf26-close>Ahora no, ver la página</button>
				</div>
			</div>
			<div class="bf26-overlay__art" aria-hidden="true">
				<div class="bf26-overlay__circle"></div>
				<div class="bf26-overlay__photo">
					<?php if ( $hidalgo_palet ) { echo $hidalgo_palet; } elseif ( $montjam_ham ) { echo $montjam_ham; } else { echo '<div class="bf26-image-fallback">IBÉRICOS<br><strong>CON ORIGEN</strong></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bf26-overlay__discount"><small>HASTA LA MESA</small><strong>–15<span>%</span></strong><span>CÓDIGO BFM2</span></div>
				<div class="bf26-overlay__asterisk">✳</div>
			</div>
		</div>
		<div class="bf26-overlay__bottom"><span>UNA CAMPAÑA DE EL MERCADO DE ORIGEN</span><span>VISTA PREVIA · OFERTAS SIN ACTIVAR</span></div>
	</div>
</div>
<noscript><style>.bf26-overlay{display:none!important}</style></noscript>
<?php get_footer(); ?>
