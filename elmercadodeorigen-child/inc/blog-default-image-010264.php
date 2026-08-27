<?php
/**
 * Imagen visual por defecto para las tarjetas del blog sin imagen destacada.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! ( is_home() || is_archive() ) ) {
			return;
		}

		$image_url = ELMERCADO_THEME_URL . '/assets/images/blog-default.webp';
		?>
		<style id="elmercado-blog-default-image-010264">
			body.elmercado-editorial-content .emo-article-card__placeholder {
				display: block !important;
				width: 100% !important;
				background-image: url('<?php echo esc_url( $image_url ); ?>') !important;
				background-size: cover !important;
				background-position: center !important;
				background-repeat: no-repeat !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/*
 * 0.10.266: la capa SEO de imágenes se carga desde este módulo ya registrado
 * para mantener el bootstrap estable y limitar el cambio al sistema editorial.
 */
$elmercado_blog_image_seo_010266 = __DIR__ . '/blog-image-seo-010266.php';
if ( is_readable( $elmercado_blog_image_seo_010266 ) ) {
	require_once $elmercado_blog_image_seo_010266;
}
