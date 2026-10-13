<?php
/**
 * Landing comercial de cestas de Navidad 2026.
 * Solo esta ruta: los banners se integrarán después de la validación.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Fotografía REAL de una referencia del catálogo; nunca un producto inventado. */
function emdo_navidad_real_photo_2026( string $slug, string $alt ): string {
	if ( ! function_exists( 'wc_get_product' ) ) { return ''; }
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	// El catálogo utiliza slugs propios: seleccionar una ficha real por su título.
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		$items = get_posts( array(
			'post_type' => 'product', 'post_status' => 'publish',
			'posts_per_page' => 30, 's' => 'Montjam', 'fields' => 'all',
			'no_found_rows' => true,
		) );
		foreach ( $items as $item ) {
			$name = strtolower( remove_accents( (string) $item->post_title ) );
			if ( false === strpos( $name, 'montjam' ) ) { continue; }
			$is_premium = false !== strpos( $slug, 'jamon-de-bellota' )
				&& false !== strpos( $name, 'jamon de bellota 100%' )
				&& false !== strpos( $name, 'brida negra' )
				&& false === strpos( $name, 'loncheado' )
				&& false === strpos( $name, 'jabugo' );
			$is_paleta = false !== strpos( $slug, 'paleta-cebo' )
				&& false !== strpos( $name, 'paleta de cebo de campo' )
				&& false === strpos( $name, 'lonchead' );
			$is_embutido = false !== strpos( $slug, 'salchichon' )
				&& false !== strpos( $name, 'salchichon iberico de bellota' )
				&& false === strpos( $name, 'lonchead' );
			if ( $is_premium || $is_paleta || $is_embutido ) { $post = $item; break; }
		}
	}
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) { return ''; }
	$product = wc_get_product( $post->ID );
	if ( ! $product || ! $product->get_image_id() ) { return ''; }
	return (string) wp_get_attachment_image(
		$product->get_image_id(),
		'woocommerce_single',
		false,
		array( 'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async', 'class' => 'emdo-xmas__real-photo' )
	);
}

$quote_url = '#presupuesto';
$privacy_url = get_privacy_policy_url();
if ( ! $privacy_url ) { $privacy_url = home_url( '/politica-de-privacidad/' ); }

$photo_premium = emdo_navidad_real_photo_2026( 'jamon-de-bellota-100-iberico-montjam', 'Jamón de bellota 100 % ibérico Montjam, fotografía real del producto' );
$photo_paleta = emdo_navidad_real_photo_2026( 'paleta-cebo-de-campo-iberica-50-montjam', 'Paleta ibérica Montjam de cebo de campo, fotografía real del producto' );
$photo_conserva = emdo_navidad_real_photo_2026( 'salchichon-iberico-bellota-montjam', 'Salchichón ibérico de bellota Montjam en pieza, fotografía real del producto' );
get_header();
?>
<main id="contenido" class="emdo-xmas emdo-xmas--landing">
	<section class="emdo-xmas__hero" aria-labelledby="emdo-xmas-title">
		<div class="emdo-xmas__wrap emdo-xmas__hero-grid">
			<div class="emdo-xmas__hero-copy">
				<span class="emdo-xmas__eyebrow">NAVIDAD 2026 · REGALOS DE EMPRESA</span>
				<h1 id="emdo-xmas-title">Cestas de Navidad para empresas, empleados y clientes</h1>
				<p class="emdo-xmas__lead">Dinos cuántas cestas necesitas y tu presupuesto aproximado por unidad. Te preparamos una propuesta personalizada.</p>
				<div class="emdo-xmas__actions">
					<a class="emdo-xmas__btn" href="<?php echo esc_url( $quote_url ); ?>">Solicitar presupuesto <span aria-hidden="true">→</span></a>
					<a class="emdo-xmas__btn emdo-xmas__btn--outline" href="#ideas">Ver ejemplos de cestas</a>
				</div>
				<p class="emdo-xmas__hero-note">Para empresas, asociaciones, equipos y regalos a clientes. Diferentes cantidades y presupuestos.</p>
			</div>
			<div class="emdo-xmas__hero-product">
				<?php if ( $photo_premium ) : ?>
					<?php echo $photo_premium; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generado por wp_get_attachment_image(). ?>
				<?php else : ?>
					<div class="emdo-xmas__photo-fallback" aria-hidden="true">Cestas de Navidad<br><strong>2026</strong></div>
				<?php endif; ?>
				<span class="emdo-xmas__photo-note">Fotografía de producto. Las cestas se preparan a medida.</span>
			</div>
		</div>
	</section>

	<section class="emdo-xmas__section emdo-xmas__section--examples" id="ideas">
		<div class="emdo-xmas__wrap">
			<div class="emdo-xmas__section-head">
				<div>
					<span class="emdo-xmas__eyebrow">EJEMPLOS DE PRESUPUESTO</span>
					<h2>Tres opciones para orientarte</h2>
				</div>
				<p>Son ejemplos. El precio final depende de la cantidad de cestas, el peso y la calidad de los productos elegidos.</p>
			</div>
			<div class="emdo-xmas__cards">
				<article class="emdo-xmas__card">
					<div class="emdo-xmas__card-visual emdo-xmas__card-visual--photo">
						<?php if ( $photo_conserva ) { echo $photo_conserva; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="emdo-xmas__card-copy">
						<span class="emdo-xmas__tag">CESTA BÁSICA</span>
						<p class="emdo-xmas__price">~60 € <small>/ unidad</small></p>
						<h3>Para empleados y clientes</h3>
						<p class="emdo-xmas__includes">Surtido de productos ibéricos, aceite de oliva, queso curado, patés y dulces navideños. Composición adaptada a la cantidad de cestas.</p>
						<a href="#presupuesto">Solicitar presupuesto →</a>
					</div>
				</article>
				<article class="emdo-xmas__card">
					<div class="emdo-xmas__card-visual emdo-xmas__card-visual--photo">
						<?php if ( $photo_paleta ) { echo $photo_paleta; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="emdo-xmas__card-copy">
						<span class="emdo-xmas__tag">CESTA CON PALETA</span>
						<p class="emdo-xmas__price">~150 € <small>/ unidad</small></p>
						<h3>Paleta ibérica y productos gourmet</h3>
						<p class="emdo-xmas__includes">Paleta ibérica o una selección más completa de ibéricos, con aceite, queso, patés y dulces según el presupuesto.</p>
						<a href="#presupuesto">Solicitar presupuesto →</a>
					</div>
				</article>
				<article class="emdo-xmas__card">
					<div class="emdo-xmas__card-visual emdo-xmas__card-visual--photo">
						<?php if ( $photo_premium ) { echo $photo_premium; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="emdo-xmas__card-copy">
						<span class="emdo-xmas__tag">CESTA PREMIUM</span>
						<p class="emdo-xmas__price">~350 € <small>/ unidad</small></p>
						<h3>Jamón ibérico de bellota</h3>
						<p class="emdo-xmas__includes">Jamón ibérico de bellota, brida negra, acompañado de una selección de ibéricos, aceite, queso, patés o dulces. El contenido depende del peso del jamón elegido.</p>
						<a href="#presupuesto">Solicitar presupuesto →</a>
					</div>
				</article>
			</div>
			<p class="emdo-xmas__note">Precios orientativos por cesta. El precio final depende de la cantidad y los productos elegidos.</p>
		</div>
	</section>

	<section class="emdo-xmas__how">
		<div class="emdo-xmas__wrap">
			<h2>¿Cómo funciona?</h2>
			<div class="emdo-xmas__how-items">
				<p><strong>1. Indícanos la cantidad.</strong> Cuántas cestas necesitas aproximadamente.</p>
				<p><strong>2. Dinos tu presupuesto.</strong> El importe que quieres destinar por cesta.</p>
				<p><strong>3. Recibe una propuesta.</strong> Te confirmamos productos, precio y condiciones de entrega.</p>
			</div>
		</div>
	</section>

	<section class="emdo-xmas__section emdo-xmas__section--form-last" id="presupuesto">
		<div class="emdo-xmas__wrap emdo-xmas__form-layout">
			<div class="emdo-xmas__form-intro">
				<h2>Solicita tu presupuesto</h2>
			</div>
			<div class="emdo-xmas__form-card">
				<?php $state = isset( $_GET['estado'] ) ? sanitize_key( wp_unslash( $_GET['estado'] ) ) : ''; ?>
				<?php if ( 'enviada' === $state ) : ?>
					<p class="emdo-xmas__notice emdo-xmas__notice--ok" role="status">Solicitud enviada. Te contactaremos para preparar tu propuesta.</p>
				<?php elseif ( 'revisar' === $state ) : ?>
					<p class="emdo-xmas__notice" role="alert">Revisa los campos obligatorios y vuelve a enviarlo.</p>
				<?php elseif ( 'limite' === $state ) : ?>
					<p class="emdo-xmas__notice" role="alert">Acabas de enviar una solicitud. Prueba de nuevo en unos minutos.</p>
				<?php elseif ( 'error' === $state ) : ?>
					<p class="emdo-xmas__notice" role="alert">No hemos podido enviar el mensaje. Inténtalo de nuevo más tarde.</p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="emdo_navidad_2026_quote">
					<?php wp_nonce_field( 'emdo_navidad_presupuesto', 'emdo_navidad_nonce' ); ?>
					<div class="emdo-xmas__trap" aria-hidden="true"><label>Tu sitio web <input type="text" name="emdo_web_field" tabindex="-1" autocomplete="off"></label></div>
					<div class="emdo-xmas__fields">
						<label>Nombre <span>*</span><input name="emdo_nombre" type="text" autocomplete="name" minlength="2" maxlength="120" required></label>
						<label>Email <span>*</span><input name="emdo_email" type="email" autocomplete="email" maxlength="190" required></label>
						<label>Número aproximado de cestas <span>*</span><input name="emdo_cantidad" type="number" min="1" max="100000" placeholder="Ej.: 30" required></label>
						<label>Presupuesto por cesta (€) <span>*</span><input name="emdo_presupuesto" type="number" min="1" max="10000" step="1" placeholder="Ej.: 150" required></label>
						<label>Empresa u organización<input name="emdo_empresa" type="text" autocomplete="organization" maxlength="160"></label>
						<label>Teléfono (opcional)<input name="emdo_telefono" type="tel" autocomplete="tel" maxlength="40"></label>
						<label class="emdo-xmas__field-wide">Fecha prevista de entrega (opcional)<input name="emdo_fecha" type="text" maxlength="100" placeholder="Ej.: primera semana de diciembre"></label>
						<label class="emdo-xmas__field-wide">Productos o preferencias (opcional)<textarea name="emdo_detalles" maxlength="2000" rows="3" placeholder="Jamón, paleta, patés, cantidad de destinatarios, destinos…"></textarea></label>
					</div>
					<label class="emdo-xmas__check"><input type="checkbox" name="emdo_privacidad" value="1" required><span>He leído la <a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener">política de privacidad</a> y autorizo el tratamiento de mis datos para responder a esta solicitud de presupuesto. <strong>*</strong></span></label>
					<button class="emdo-xmas__btn emdo-xmas__submit" type="submit">Enviar solicitud <span aria-hidden="true">→</span></button>
					<small>Los precios y la entrega se confirmarán en el presupuesto personalizado.</small>
				</form>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
