<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Editorial Specials block for the current homepage.
 *
 * The block is injected into the homepage content immediately before the
 * existing emo-categories section. This avoids buffering the complete HTTP
 * response, which is unsafe on the current production homepage stack.
 */
final class MDO_Home_Specials {
	private const POST_TYPE = 'mdo_promotion';

	public static function init(): void {
		add_filter( 'the_content', array( __CLASS__, 'inject_block' ), 9999 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 90 );
	}

	public static function enqueue_assets(): void {
		if ( is_admin() || ! self::is_home_request() ) {
			return;
		}

		wp_enqueue_style(
			'mdo-home-specials',
			MDO_SUPPLIER_SYNC_URL . 'assets/css/home-specials.css',
			array(),
			MDO_SUPPLIER_SYNC_VERSION
		);
	}

	public static function inject_block( string $html ): string {
		if (
			is_admin()
			|| ! self::is_home_request()
			|| ! in_the_loop()
			|| ! is_main_query()
			|| '' === $html
			|| false !== strpos( $html, 'id="mdo-home-specials"' )
		) {
			return $html;
		}

		$special_id = self::active_special_id();
		if ( ! $special_id ) {
			return $html;
		}

		$block = self::render_block( $special_id );
		if ( '' === $block ) {
			return $html;
		}

		$pattern = '~(<section\s+class="[^"]*\bemo-categories\b[^"]*"[^>]*>)~i';
		$result  = preg_replace( $pattern, $block . '$1', $html, 1 );

		return is_string( $result ) ? $result : $html;
	}

	private static function is_home_request(): bool {
		if ( is_front_page() ) {
			return true;
		}

		// Falang can resolve /en/ through its own routing layer and does not always
		// expose it as is_front_page() at every hook priority.
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		return 'en' === $path;
	}

	private static function active_special_id(): int {
		if ( ! post_type_exists( self::POST_TYPE ) || ! class_exists( 'MDO_Specials' ) ) {
			return 0;
		}

		$featured = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'fields'         => 'ids',
				'meta_key'       => '_mdo_promo_featured_home',
				'meta_value'     => '1',
			)
		);

		foreach ( $featured as $id ) {
			if ( 'active' === MDO_Specials::status( (int) $id ) ) {
				return (int) $id;
			}
		}

		$fallback = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'fields'         => 'ids',
			)
		);

		foreach ( $fallback as $id ) {
			if ( 'active' === MDO_Specials::status( (int) $id ) ) {
				return (int) $id;
			}

		return 0;
	}

	private static function language(): string {
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		return ( 'en' === $path || 0 === strpos( $path, 'en/' ) ) ? 'en' : 'es';
	}

	private static function render_block( int $post_id ): string {
		$lang    = self::language();
		$title   = MDO_Specials::text( $post_id, 'title', $lang );
		$summary = MDO_Specials::text( $post_id, 'summary', $lang );
		$url     = MDO_Specials::permalink( $post_id, $lang );
		$meta    = MDO_Specials::shared( $post_id );
		$image   = MDO_Specials::image_html(
			$post_id,
			'large',
			array(
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);

		if ( ! $title || ! $url ) {
			return '';
		}

		$section_title = 'en' === $lang ? 'NOW AT EL MERCADO DE ORIGEN' : 'AHORA EN EL MERCADO DE ORIGEN';
		$cta           = 'en' === $lang ? 'View special' : 'Ver especial';
		$until         = 'en' === $lang ? 'Available until' : 'Disponible hasta el';
		$date          = ! empty( $meta['end'] ) ? MDO_Specials::format_date( (string) $meta['end'], $lang ) : '';

		ob_start();
		?>
		<section class="mdo-home-specials" id="mdo-home-specials" aria-labelledby="mdo-home-specials-title">
			<div class="mdo-home-specials__shell">
				<h2 class="mdo-home-specials__heading" id="mdo-home-specials-title"><?php echo esc_html( $section_title ); ?></h2>
				<article class="mdo-home-specials__card">
					<?php if ( $image ) : ?>
						<a class="mdo-home-specials__media" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
							<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php endif; ?>
					<div class="mdo-home-specials__content">
						<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h3>
						<?php if ( $summary ) : ?><p class="mdo-home-specials__summary"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
						<div class="mdo-home-specials__footer">
							<?php if ( $date ) : ?><p class="mdo-home-specials__date"><?php echo esc_html( $until . ' ' . $date ); ?></p><?php endif; ?>
							<a class="mdo-home-specials__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $cta ); ?> <span aria-hidden="true">→</span></a>
						</div>
					</div>
				</article>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
