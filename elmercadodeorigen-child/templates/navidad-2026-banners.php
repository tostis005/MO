<?php
/**
 * Muestrario independiente: /vista-previa-banners-navidad-2026/
 * No inserta ningún banner en páginas existentes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$landing_url = emdo_navidad_2026_url();
get_header();
?>
<main class="emdo-xmas emdo-xmas--demo" id="contenido">
	<div class="emdo-xmas__topline"><div class="emdo-xmas__wrap"><span>EL MERCADO DE ORIGEN</span><span>Laboratorio visual · Navidad 2026</span></div></div>
	<section class="emdo-xmas__demo-head"><div class="emdo-xmas__wrap">
		<span class="emdo-xmas__eyebrow">Presentación para validar</span>
		<h1>Tres formas de presentar las cestas sin interrumpir la compra.</h1>
		<p>El banner de portada no tiene por qué ser igual que el de tienda o blog. Aquí puedes compararlos antes de decidir su integración. Estas son muestras, no están insertadas en ninguna página real.</p>
		<a class="emdo-xmas__btn" href="<?php echo esc_url( $landing_url ); ?>">Visitar landing de campaña ↗</a>
	</div></section>
	<section class="emdo-xmas__demo-section"><div class="emdo-xmas__wrap">
		<div class="emdo-xmas__demo-label"><span>01 · PORTADA</span><strong>Bloque editorial bajo el hero existente</strong><p>En lugar de pisar el hero actual, propongo una franja con personalidad propia justo debajo, antes de las categorías. Mantiene visible la identidad de la home.</p></div>
		<div class="emdo-xmas__banner emdo-xmas__banner--home">
			<div class="emdo-xmas__banner-copy"><span class="emdo-xmas__eyebrow">Esta Navidad, regala origen</span><h2>Cestas de Navidad para empresas.</h2><p>Una propuesta a medida para tu equipo y tus clientes.</p><a href="<?php echo esc_url( $landing_url ); ?>">Preparar mi presupuesto <span aria-hidden="true">↗</span></a></div>
			<div class="emdo-xmas__banner-art" aria-hidden="true"><div class="emdo-xmas__mini-pack"><span>REGALOS · 2026</span><strong>Un detalle<br>que importa.</strong><i>✦</i></div></div>
		</div>
	</div></section>
	<section class="emdo-xmas__demo-section emdo-xmas__demo-section--warm"><div class="emdo-xmas__wrap">
		<div class="emdo-xmas__demo-label"><span>02 · TIENDA</span><strong>Franja comercial compacta</strong><p>Pensada para aparecer después de la cabecera de categoría o antes del listado de productos, sin competir con los filtros ni ocupar demasiado alto.</p></div>
		<div class="emdo-xmas__banner emdo-xmas__banner--shop">
			<div class="emdo-xmas__shop-mark" aria-hidden="true">✦</div>
			<div><span>Regalos de empresa · Navidad 2026</span><h2>¿Buscas cestas para empleados o clientes?</h2><p>Te ayudamos a elegir según cantidad y presupuesto.</p></div>
			<a href="<?php echo esc_url( $landing_url ); ?>">Pedir presupuesto →</a>
		</div>
	</div></section>
	<section class="emdo-xmas__demo-section"><div class="emdo-xmas__wrap">
		<div class="emdo-xmas__demo-label"><span>03 · BLOG</span><strong>Tarjeta editorial contextual</strong><p>Una invitación integrada en el artículo, preferiblemente después de un bloque de lectura relevante. Sin popups ni imágenes pesadas que perjudiquen la lectura o la publicidad.</p></div>
		<div class="emdo-xmas__article-mock">
			<div class="emdo-xmas__article-text"><span>GUÍA Y CONSEJOS</span><h2>Cómo elegir un regalo gourmet según el presupuesto</h2><p>En un lote de productos gourmet importa tanto la procedencia como la selección. Para grupos más grandes conviene planificar cantidades, conservación y fecha de entrega.</p><p>Por eso es útil definir primero una cifra por destinatario y decidir después las referencias que compondrán cada regalo.</p></div>
			<aside class="emdo-xmas__banner emdo-xmas__banner--blog">
				<div><span>PARA EQUIPOS Y EMPRESAS</span><h3>¿Necesitas preparar varios regalos gourmet?</h3><p>Cuéntanos tu presupuesto y te proponemos cestas de Navidad personalizadas.</p><a href="<?php echo esc_url( $landing_url ); ?>">Solicitar propuesta →</a></div>
				<span class="emdo-xmas__blog-symbol" aria-hidden="true">✳</span>
			</aside>
			<div class="emdo-xmas__article-text"><p>Sea cual sea el formato elegido, el envío debe adaptarse a las necesidades de conservación indicadas en el etiquetado.</p></div>
		</div>
	</div></section>
	<div class="emdo-xmas__demo-bottom"><div class="emdo-xmas__wrap"><strong>Estado de la propuesta:</strong> vista previa. Las tres ubicaciones son ilustrativas y no se activarán hasta la aprobación. Las fotografías definitivas se incorporarán a partir de imágenes reales verificadas de los productos.</div></div>
</main>
<?php get_footer(); ?>
