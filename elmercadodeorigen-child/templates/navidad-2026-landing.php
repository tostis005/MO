<?php
/**
 * Landing independiente: /cestas-navidad-empresas-2026/
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$quote_url = '#presupuesto';
$privacy_url = get_privacy_policy_url();
if ( ! $privacy_url ) { $privacy_url = home_url( '/politica-de-privacidad/' ); }
get_header();
?>
<main id="contenido" class="emdo-xmas">
	<div class="emdo-xmas__topline"><div class="emdo-xmas__wrap"><span>EL MERCADO DE ORIGEN · NAVIDAD 2026</span><span>Regalos para empleados, equipos y clientes</span></div></div>
	<section class="emdo-xmas__hero" aria-labelledby="emdo-xmas-title">
		<div class="emdo-xmas__wrap emdo-xmas__hero-grid">
			<div class="emdo-xmas__hero-copy">
				<span class="emdo-xmas__eyebrow">El valor de regalar algo con origen</span>
				<h1 id="emdo-xmas-title">Cestas de Navidad <em>para empresas</em></h1>
				<p class="emdo-xmas__lead">Regalos gourmet con productos de productores españoles. Nos cuentas cuántos necesitas y tu presupuesto por cesta; preparamos una propuesta a medida.</p>
				<div class="emdo-xmas__actions">
					<a class="emdo-xmas__btn" href="<?php echo esc_url( $quote_url ); ?>">Pedir presupuesto <span aria-hidden="true">↗</span></a>
					<a class="emdo-xmas__btn emdo-xmas__btn--outline" href="#ideas">Ver ideas de cestas</a>
				</div>
				<ul class="emdo-xmas__proof">
					<li>Propuestas según cantidad y presupuesto</li>
					<li>Selección de productos con origen</li>
					<li>Opciones para regalos corporativos</li>
				</ul>
			</div>
			<div class="emdo-xmas__visual" role="img" aria-label="Composición gráfica de una caja regalo gourmet para la campaña de Navidad. Fotografía definitiva pendiente de validar.">
				<div class="emdo-xmas__visual-glow"></div>
				<div class="emdo-xmas__pack">
					<span class="emdo-xmas__pack-stamp">EMDO · 2026</span>
					<span class="emdo-xmas__pack-icon" aria-hidden="true">✦</span>
					<strong>Regalos<br>con origen.</strong>
					<span class="emdo-xmas__pack-bottom">Selección gourmet · Navidad</span>
				</div>
				<div class="emdo-xmas__roundel">Para compartir<br><strong>lo mejor de aquí</strong></div>
				<span class="emdo-xmas__visual-caption">Representación del concepto · Pendiente de fotografía final</span>
			</div>
		</div>
	</section>

	<section class="emdo-xmas__bar">
		<div class="emdo-xmas__wrap emdo-xmas__bar-grid">
			<div><strong>01. Tú decides</strong><span>Número de cestas y presupuesto</span></div>
			<div><strong>02. Seleccionamos</strong><span>Productos adecuados al envío</span></div>
			<div><strong>03. Te proponemos</strong><span>Una selección personalizada</span></div>
		</div>
	</section>

	<section class="emdo-xmas__section" id="ideas">
		<div class="emdo-xmas__wrap">
			<div class="emdo-xmas__section-head">
				<div><span class="emdo-xmas__eyebrow">Una idea para cada ocasión</span><h2>Regalos que se adaptan a tu equipo, no al revés.</h2></div>
				<p>Estas composiciones son <strong>ejemplos orientativos</strong>, no cestas cerradas ni referencias disponibles confirmadas. Ajustamos la selección tras revisar producto, conservación y presupuesto.</p>
			</div>
			<div class="emdo-xmas__cards">
				<article class="emdo-xmas__card">
					<div class="emdo-xmas__card-visual emdo-xmas__card-visual--one"><span>01</span><strong>El detalle<br>con origen</strong><small>Esencial</small></div>
					<div class="emdo-xmas__card-copy"><span class="emdo-xmas__tag">Para presupuestos ajustados</span><h3>Cesta Esencial</h3><p>Un detalle cuidado para agradecer el año y llegar a todo el equipo.</p>
					<p class="emdo-xmas__includes">Ejemplo: aceite de oliva virgen extra, conserva vegetal y dulce navideño envasado.</p><a href="<?php echo esc_url( $quote_url ); ?>">Quiero algo así →</a></div>
				</article>
				<article class="emdo-xmas__card">
					<div class="emdo-xmas__card-visual emdo-xmas__card-visual--two"><span>02</span><strong>Sabores<br>para compartir</strong><small>Gourmet</small></div>
					<div class="emdo-xmas__card-copy"><span class="emdo-xmas__tag">El equilibrio perfecto</span><h3>Cesta Gourmet</h3><p>Más variedad para sorprender con una selección de despensa y aperitivos.</p>
					<p class="emdo-xmas__includes">Ejemplo: aceite de oliva virgen extra, embutido curado apto para temperatura ambiente, conservas gourmet y turrón.</p><a href="<?php echo esc_url( $quote_url ); ?>">Quiero algo así →</a></div>
				</article>
				<article class="emdo-xmas__card">
					<div class="emdo-xmas__card-visual emdo-xmas__card-visual--three"><span>03</span><strong>Un regalo<br>que deja huella</strong><small>Premium</small></div>
					<div class="emdo-xmas__card-copy"><span class="emdo-xmas__tag">Para ocasiones especiales</span><h3>Cesta Premium</h3><p>El formato más representativo, con una pieza protagonista y complementos a la altura.</p>
					<p class="emdo-xmas__includes">Ejemplo: paleta o jamón curado entero de productor, aceite de oliva, conservas esterilizadas y surtido navideño.</p><a href="<?php echo esc_url( $quote_url ); ?>">Quiero algo así →</a></div>
				</article>
			</div>
			<p class="emdo-xmas__note">Para el envío sin refrigeración únicamente se incorporarán referencias cuya etiqueta confirme conservación a temperatura ambiente durante el transporte. No incluiremos productos frescos, micuit refrigerados ni loncheados o quesos que requieran frío.</p>
		</div>
	</section>

	<section class="emdo-xmas__section emdo-xmas__section--ink">
		<div class="emdo-xmas__wrap emdo-xmas__split">
			<div><span class="emdo-xmas__eyebrow">Regalos de empresa con sentido</span><h2>Cuidamos la selección; tú eliges cuánto quieres regalar.</h2><p>Para empleados, clientes, colaboradores, asociaciones o eventos. Puedes plantearnos una cantidad aproximada y nosotros preparamos una selección que tenga en cuenta presentación, variedad, logística y fecha objetivo.</p></div>
			<div class="emdo-xmas__features">
				<div><span>✦</span><div><h3>Flexible desde el primer contacto</h3><p>No necesitas saber qué productos poner dentro. Cuéntanos tu presupuesto y para quién va dirigido.</p></div></div>
				<div><span>✦</span><div><h3>Productos con productor detrás</h3><p>Priorizamos procedencia y calidad, con opciones ibéricas, aceites y conservas.</p></div></div>
				<div><span>✦</span><div><h3>La logística, desde el principio</h3><p>Confirmaremos formatos, conservación, disponibilidad, costes y condiciones de entrega antes de cerrar cada propuesta.</p></div></div>
			</div>
		</div>
	</section>

	<section class="emdo-xmas__section" id="presupuesto">
		<div class="emdo-xmas__wrap emdo-xmas__form-layout">
			<div class="emdo-xmas__form-intro">
				<span class="emdo-xmas__eyebrow">Tu propuesta empieza aquí</span>
				<h2>Cuéntanos qué necesitas.</h2>
				<p>Sin compromiso. Con unos pocos datos podemos orientarte y preparar un presupuesto personalizado para tu empresa.</p>
				<div class="emdo-xmas__form-facts"><p><strong>¿Cuántas cestas?</strong><br>Desde pequeños equipos hasta pedidos de mayor volumen.</p><p><strong>¿Qué presupuesto?</strong><br>Nos adaptamos al rango que tengas previsto por unidad.</p><p><strong>¿Cuándo las necesitas?</strong><br>Comprobaremos contigo los plazos y destinos.</p></div>
			</div>
			<div class="emdo-xmas__form-card">
				<?php $state = isset( $_GET['estado'] ) ? sanitize_key( wp_unslash( $_GET['estado'] ) ) : ''; ?>
				<?php if ( 'enviada' === $state ) : ?>
					<p class="emdo-xmas__notice emdo-xmas__notice--ok" role="status">Solicitud enviada. Hemos recibido tu petición; nos pondremos en contacto contigo.</p>
				<?php elseif ( 'revisar' === $state ) : ?>
					<p class="emdo-xmas__notice" role="alert">Revisa los datos obligatorios del formulario e inténtalo de nuevo.</p>
				<?php elseif ( 'limite' === $state ) : ?>
					<p class="emdo-xmas__notice" role="alert">Ya se ha enviado una petición recientemente. Inténtalo de nuevo en unos minutos.</p>
				<?php elseif ( 'error' === $state ) : ?>
					<p class="emdo-xmas__notice" role="alert">No se ha podido enviar la petición. Inténtalo de nuevo más tarde.</p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="emdo_navidad_2026_quote">
					<?php wp_nonce_field( 'emdo_navidad_presupuesto', 'emdo_navidad_nonce' ); ?>
					<div class="emdo-xmas__trap" aria-hidden="true"><label>Tu sitio web <input type="text" name="emdo_web_field" tabindex="-1" autocomplete="off"></label></div>
					<div class="emdo-xmas__fields">
						<label>Nombre y apellidos <span>*</span><input name="emdo_nombre" type="text" autocomplete="name" minlength="2" maxlength="120" required></label>
						<label>Empresa u organización<input name="emdo_empresa" type="text" autocomplete="organization" maxlength="160"></label>
						<label>Correo electrónico <span>*</span><input name="emdo_email" type="email" autocomplete="email" maxlength="190" required></label>
						<label>Teléfono<input name="emdo_telefono" type="tel" autocomplete="tel" maxlength="40"></label>
						<label>¿Cuántas cestas necesitas? <span>*</span><input name="emdo_cantidad" type="number" min="1" max="100000" placeholder="Ej.: 30" required></label>
						<label>Presupuesto por cesta <span>*</span><select name="emdo_presupuesto" required><option value="">Elige un rango</option><option value="sin-definir">Todavía no lo sé</option><option value="menos-30">Menos de 30 €</option><option value="30-50">30–50 €</option><option value="50-80">50–80 €</option><option value="80-120">80–120 €</option><option value="120-200">120–200 €</option><option value="mas-200">Más de 200 €</option></select></label>
						<label class="emdo-xmas__field-wide">Fecha aproximada de entrega<input name="emdo_fecha" type="text" maxlength="100" placeholder="Ej.: primera semana de diciembre"></label>
						<label class="emdo-xmas__field-wide">Preferencias o detalles<textarea name="emdo_detalles" maxlength="2000" rows="4" placeholder="Qué te gustaría incluir, destinos, presentación, preferencias…"></textarea></label>
					</div>
					<label class="emdo-xmas__check"><input type="checkbox" name="emdo_privacidad" value="1" required><span>Acepto el tratamiento de mis datos para responder a mi solicitud de presupuesto. He leído la <a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener">política de privacidad</a>. Esta solicitud no implica suscribirse a publicidad. <strong>*</strong></span></label>
					<button class="emdo-xmas__btn emdo-xmas__submit" type="submit">Solicitar presupuesto <span aria-hidden="true">↗</span></button>
					<small>Los campos con * son obligatorios. La propuesta estará sujeta a disponibilidad, condiciones de envío y confirmación comercial.</small>
				</form>
			</div>
		</div>
	</section>

	<section class="emdo-xmas__section emdo-xmas__faq"><div class="emdo-xmas__wrap">
		<div class="emdo-xmas__section-head"><div><span class="emdo-xmas__eyebrow">Resolvemos tus dudas</span><h2>Preguntas frecuentes</h2></div></div>
		<details><summary>¿Puedo elegir el presupuesto por cesta?</summary><p>Sí. Indícanos tu rango por unidad y la cantidad aproximada; estudiaremos una composición adecuada.</p></details>
		<details><summary>¿Se pueden personalizar productos y presentación?</summary><p>Prepararemos opciones según presupuesto y disponibilidad. La presentación y los posibles elementos corporativos se confirmarán antes de cerrar el pedido.</p></details>
		<details><summary>¿Los productos necesitan refrigeración?</summary><p>Esta campaña se plantea solo con referencias aptas para transporte sin frío, contrastando el etiquetado de cada producto. No utilizaremos productos frescos ni referencias que exijan refrigeración.</p></details>
		<details><summary>¿Puedo pedir cestas para diferentes destinatarios?</summary><p>Sí, indícanos destinos y fechas. Revisaremos viabilidad, costes, preparación y condiciones de reparto antes de presupuestar.</p></details>
	</div></section>
	<footer class="emdo-xmas__end"><div class="emdo-xmas__wrap"><strong>Regala productos con origen.</strong><span>Navidad 2026 · El Mercado de Origen</span><a href="#presupuesto">Pedir presupuesto →</a></div></footer>
</main>
<?php get_footer(); ?>
