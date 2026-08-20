<?php
/**
 * Plugin Name: MDO Catalog Destination Frontend
 * Description: Destination-aware catalogue filtering, selector and EMDO default ranking for the public WooCommerce catalogue.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Catalog_Destination_Frontend {
	private const COUNTRY_COOKIE = 'mdo_shipping_country';
	private const POSTCODE_COOKIE = 'mdo_shipping_postcode';
	private const RANK_CACHE_PREFIX = 'mdo_catalog_rank_v3_';
	private const RANK_CACHE_TTL = 3 * HOUR_IN_SECONDS;

	private static bool $building_rank = false;
	private static ?array $destination_cache = null;
	private static array $excluded_cache = array();

	public static function init(): void {
		add_filter( 'mdo_catalog_ranking_weights', array( __CLASS__, 'ranking_weights_without_views' ), 50, 2 );
		add_filter( 'mdo_shipping_vendor_can_ship_to', array( __CLASS__, 'block_ceuta_melilla_for_vendor' ), PHP_INT_MAX, 4 );
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'block_ceuta_melilla_rates' ), PHP_INT_MAX, 2 );

		add_action( 'wp_ajax_mdo_set_shipping_destination', array( __CLASS__, 'ajax_set_destination' ) );
		add_action( 'wp_ajax_nopriv_mdo_set_shipping_destination', array( __CLASS__, 'ajax_set_destination' ) );

		add_action( 'pre_get_posts', array( __CLASS__, 'apply_default_ranking' ), 1600 );
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'render_destination_control' ), 22 );
		add_action( 'template_redirect', array( __CLASS__, 'protect_personalized_catalog_from_cache' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'render_styles' ), PHP_INT_MAX );
		add_action( 'wp_footer', array( __CLASS__, 'render_script' ), PHP_INT_MAX );

		add_action( 'added_post_meta', array( __CLASS__, 'maybe_invalidate_rank_from_meta' ), 10, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'maybe_invalidate_rank_from_meta' ), 10, 4 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'maybe_invalidate_rank_from_meta' ), 10, 4 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'maybe_invalidate_rank_from_order' ), 10, 4 );
	}

	/** Views/visits are deliberately not part of the scoring model. */
	public static function ranking_weights_without_views( array $weights, array $args = array() ): array {
		unset( $args );
		return array(
			'priority'        => 0.38,
			'recent_sales'    => 0.27,
			'recent_interest' => 0.00,
			'product_new'     => 0.10,
			'vendor_new'      => 0.18,
			'rotation'        => 0.07,
		);
	}

	public static function current_destination(): array {
		if ( is_array( self::$destination_cache ) ) {
			return self::$destination_cache;
		}

		$country  = isset( $_COOKIE[ self::COUNTRY_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COUNTRY_COOKIE ] ) ) : 'ES';
		$postcode = isset( $_COOKIE[ self::POSTCODE_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::POSTCODE_COOKIE ] ) ) : '';

		if ( class_exists( 'MDO_Shipping_Destinations' ) ) {
			$destination = MDO_Shipping_Destinations::normalize_destination( $country, $postcode );
		} else {
			$destination = array(
				'country'  => strtoupper( trim( $country ) ) ?: 'ES',
				'postcode' => trim( $postcode ),
			);
		}

		$supported = self::supported_countries();
		if ( ! isset( $supported[ $destination['country'] ] ) ) {
			$destination = array( 'country' => 'ES', 'postcode' => '' );
		}
		if ( 'ES' !== $destination['country'] ) {
			$destination['postcode'] = '';
		}

		self::$destination_cache = $destination;
		return $destination;
	}

	public static function supported_countries(): array {
		$countries = class_exists( 'MDO_Shipping_Destinations' )
			? (array) MDO_Shipping_Destinations::supported_countries()
			: array();

		if ( ! isset( $countries['ES'] ) ) {
			$countries = array( 'ES' => self::text( 'España', 'Spain' ) ) + $countries;
		}

		return $countries;
	}

	/**
	 * Vendor IDs that must be removed for the currently selected destination.
	 * This public method is also consumed by the child theme's exact counts.
	 *
	 * @return int[]
	 */
	public static function excluded_vendor_ids(): array {
		$destination = self::current_destination();
		$key = $destination['country'] . '|' . $destination['postcode'];
		if ( isset( self::$excluded_cache[ $key ] ) ) {
			return self::$excluded_cache[ $key ];
		}

		if ( ! class_exists( 'MDO_Shipping_Destinations' ) ) {
			self::$excluded_cache[ $key ] = array();
			return array();
		}

		global $wpdb;
		$author_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_author > 0",
				'product',
				'publish'
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$excluded = array();
		foreach ( array_values( array_unique( array_filter( array_map( 'absint', (array) $author_ids ) ) ) ) as $vendor_id ) {
			$user = get_userdata( $vendor_id );
			if ( ! $user instanceof WP_User || ! in_array( 'wcfm_vendor', (array) $user->roles, true ) ) {
				continue;
			}

			if ( ! MDO_Shipping_Destinations::vendor_can_ship_to( $vendor_id, $destination['country'], $destination['postcode'] ) ) {
				$excluded[] = $vendor_id;
			}
		}

		self::$excluded_cache[ $key ] = array_values( array_unique( array_filter( array_map( 'absint', $excluded ) ) ) );
		return self::$excluded_cache[ $key ];
	}

	public static function block_ceuta_melilla_for_vendor( bool $available, int $vendor_id, array $destination, string $type ): bool {
		unset( $vendor_id, $type );
		if ( self::is_ceuta_or_melilla( (string) ( $destination['country'] ?? '' ), (string) ( $destination['postcode'] ?? '' ) ) ) {
			return false;
		}
		return $available;
	}

	public static function block_ceuta_melilla_rates( array $rates, array $package ): array {
		$destination = isset( $package['destination'] ) && is_array( $package['destination'] ) ? $package['destination'] : array();
		$country = strtoupper( trim( (string) ( $destination['country'] ?? '' ) ) );
		$postcode = trim( (string) ( $destination['postcode'] ?? '' ) );
		$state = strtoupper( trim( (string) ( $destination['state'] ?? '' ) ) );

		if ( ( 'ES' === $country && in_array( $state, array( 'CE', 'ML' ), true ) ) || self::is_ceuta_or_melilla( $country, $postcode ) ) {
			return array();
		}
		return $rates;
	}

	public static function ajax_set_destination(): void {
		check_ajax_referer( 'mdo_shipping_destination', 'nonce' );

		$country = isset( $_POST['country'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country'] ) ) ) : 'ES';
		$postcode = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';
		$supported = self::supported_countries();

		if ( ! isset( $supported[ $country ] ) ) {
			wp_send_json_error( array( 'message' => self::text( 'Ese destino no está disponible actualmente.', 'That destination is not currently available.' ) ), 400 );
		}

		if ( 'ES' === $country ) {
			$postcode = preg_replace( '/\D+/', '', $postcode );
			if ( '' !== $postcode && 5 !== strlen( $postcode ) ) {
				wp_send_json_error( array( 'message' => self::text( 'Introduce un código postal español de 5 cifras o déjalo vacío.', 'Enter a 5-digit Spanish postcode or leave it blank.' ) ), 400 );
			}
			if ( self::is_ceuta_or_melilla( $country, $postcode ) ) {
				wp_send_json_error( array( 'message' => self::text( 'Actualmente no realizamos envíos a Ceuta ni Melilla.', 'We currently do not ship to Ceuta or Melilla.' ) ), 400 );
			}
		} else {
			$postcode = '';
		}

		if ( class_exists( 'MDO_Shipping_Destinations' ) ) {
			$destination = MDO_Shipping_Destinations::normalize_destination( $country, $postcode );
		} else {
			$destination = array( 'country' => $country, 'postcode' => $postcode );
		}

		self::set_cookie( self::COUNTRY_COOKIE, $destination['country'] );
		self::set_cookie( self::POSTCODE_COOKIE, $destination['postcode'] );
		self::$destination_cache = $destination;
		self::$excluded_cache = array();

		if ( function_exists( 'WC' ) && WC()->customer ) {
			if ( is_callable( array( WC()->customer, 'set_shipping_country' ) ) ) {
				WC()->customer->set_shipping_country( $destination['country'] );
			}
			if ( is_callable( array( WC()->customer, 'set_shipping_postcode' ) ) ) {
				WC()->customer->set_shipping_postcode( $destination['postcode'] );
			}
			if ( is_callable( array( WC()->customer, 'save' ) ) ) {
				WC()->customer->save();
			}
		}

		wp_send_json_success(
			array(
				'country'  => $destination['country'],
				'postcode' => $destination['postcode'],
				'label'    => (string) ( $supported[ $destination['country'] ] ?? $destination['country'] ),
			)
		);
	}

	public static function apply_default_ranking( WP_Query $query ): void {
		if ( self::$building_rank || is_admin() || ! self::is_catalog_query( $query ) ) {
			return;
		}
		if ( isset( $_GET['orderby'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! class_exists( 'MDO_Catalog_Ranking' ) ) {
			return;
		}

		$ranked = self::ranked_product_ids();
		if ( ! $ranked ) {
			$query->set( 'post__in', array( 0 ) );
			return;
		}

		$current = array_values( array_unique( array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) ) ) );
		if ( $current ) {
			$ranked = array_values( array_intersect( $ranked, $current ) );
		}
		if ( ! $ranked ) {
			$ranked = array( 0 );
		}

		$query->set( 'post__in', $ranked );
		$query->set( 'orderby', 'post__in' );
		$query->set( 'order', 'ASC' );
	}

	public static function render_destination_control(): void {
		if ( is_admin() || ! self::is_catalog_surface() ) {
			return;
		}

		$destination = self::current_destination();
		$countries = self::supported_countries();
		$label = (string) ( $countries[ $destination['country'] ] ?? $destination['country'] );
		$postcode_suffix = 'ES' === $destination['country'] && '' !== $destination['postcode'] ? ' · ' . $destination['postcode'] : '';
		?>
		<div class="mdo-catalog-destination" data-mdo-destination-control>
			<button type="button" class="mdo-catalog-destination__trigger" data-mdo-destination-open aria-haspopup="dialog" aria-controls="mdo-catalog-destination-dialog">
				<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Zm0-8.5A2.5 2.5 0 1 1 12 7a2.5 2.5 0 0 1 0 5.5Z" fill="currentColor"/></svg>
				<span><?php echo esc_html( self::text( 'Envío a', 'Shipping to' ) ); ?> <strong><?php echo esc_html( $label . $postcode_suffix ); ?></strong></span>
				<svg class="mdo-catalog-destination__chevron" aria-hidden="true" viewBox="0 0 20 20" width="14" height="14"><path d="m5 7.5 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		</div>

		<div id="mdo-catalog-destination-dialog" class="mdo-destination-modal" data-mdo-destination-modal hidden aria-hidden="true">
			<div class="mdo-destination-modal__backdrop" data-mdo-destination-close></div>
			<section class="mdo-destination-modal__panel" role="dialog" aria-modal="true" aria-labelledby="mdo-destination-title" tabindex="-1">
				<button type="button" class="mdo-destination-modal__close" data-mdo-destination-close aria-label="<?php echo esc_attr( self::text( 'Cerrar', 'Close' ) ); ?>">×</button>
				<h2 id="mdo-destination-title"><?php echo esc_html( self::text( '¿Dónde quieres recibir tu pedido?', 'Where do you want to receive your order?' ) ); ?></h2>
				<p class="mdo-destination-modal__intro"><?php echo esc_html( self::text( 'Mostramos solo los productos que pueden enviarse al destino elegido.', 'We only show products that can be shipped to your selected destination.' ) ); ?></p>

				<form data-mdo-destination-form>
					<label for="mdo-destination-country"><?php echo esc_html( self::text( 'País', 'Country' ) ); ?></label>
					<select id="mdo-destination-country" name="country" data-mdo-destination-country>
						<?php foreach ( $countries as $code => $country_label ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $destination['country'], $code ); ?>><?php echo esc_html( $country_label ); ?></option>
						<?php endforeach; ?>
					</select>

					<div class="mdo-destination-modal__postcode" data-mdo-postcode-wrap <?php echo 'ES' === $destination['country'] ? '' : 'hidden'; ?>>
						<label for="mdo-destination-postcode"><?php echo esc_html( self::text( 'Código postal', 'Postcode' ) ); ?> <span><?php echo esc_html( self::text( '(opcional)', '(optional)' ) ); ?></span></label>
						<input id="mdo-destination-postcode" name="postcode" data-mdo-destination-postcode inputmode="numeric" autocomplete="postal-code" maxlength="5" pattern="[0-9]{5}" value="<?php echo esc_attr( $destination['postcode'] ); ?>" placeholder="28001">
						<small><?php echo esc_html( self::text( 'Úsalo para ajustar Península, Baleares o Canarias. Actualmente no enviamos a Ceuta ni Melilla.', 'Use it to refine Mainland Spain, Balearic Islands or Canary Islands. We currently do not ship to Ceuta or Melilla.' ) ); ?></small>
					</div>

					<p class="mdo-destination-modal__error" data-mdo-destination-error role="alert" hidden></p>
					<button type="submit" class="mdo-destination-modal__save" data-mdo-destination-save><?php echo esc_html( self::text( 'Guardar destino', 'Save destination' ) ); ?></button>
				</form>
			</section>
		</div>
		<?php
	}

	public static function render_styles(): void {
		if ( is_admin() || ! self::is_catalog_surface() ) {
			return;
		}
		?>
		<style id="mdo-catalog-destination-frontend-css">
			.mdo-catalog-destination{display:inline-flex;align-items:center;vertical-align:top;margin:0 0 18px 12px;}
			.mdo-catalog-destination__trigger{display:inline-flex;min-height:38px;align-items:center;gap:7px;padding:0 13px;border:1px solid rgba(23,63,50,.17);border-radius:999px;background:#fff;color:#173f32;font:inherit;font-size:13px;line-height:1;cursor:pointer;box-shadow:0 1px 2px rgba(15,38,31,.03);transition:border-color .16s ease,box-shadow .16s ease,background .16s ease;}
			.mdo-catalog-destination__trigger:hover,.mdo-catalog-destination__trigger:focus-visible{border-color:rgba(23,63,50,.34);background:#fbfdfc;box-shadow:0 2px 8px rgba(15,38,31,.06);outline:none;}
			.mdo-catalog-destination__trigger strong{font-weight:750;}
			.mdo-catalog-destination__chevron{opacity:.62;}
			.mdo-destination-modal[hidden]{display:none!important;}
			.mdo-destination-modal{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px;}
			.mdo-destination-modal__backdrop{position:absolute;inset:0;background:rgba(13,26,21,.46);backdrop-filter:blur(2px);}
			.mdo-destination-modal__panel{position:relative;z-index:1;width:min(100%,440px);padding:28px;border-radius:16px;background:#fff;color:#173f32;box-shadow:0 24px 70px rgba(0,0,0,.22);}
			.mdo-destination-modal__panel h2{margin:0 34px 8px 0;font-size:22px;line-height:1.25;color:#173f32;}
			.mdo-destination-modal__intro{margin:0 0 22px;color:#5e6f68;font-size:14px;line-height:1.5;}
			.mdo-destination-modal__close{position:absolute;top:15px;right:15px;width:36px;height:36px;padding:0;border:0;border-radius:50%;background:#f3f6f4;color:#173f32;font-size:25px;line-height:34px;cursor:pointer;}
			.mdo-destination-modal form{margin:0;}
			.mdo-destination-modal label{display:block;margin:0 0 7px;font-size:13px;font-weight:750;color:#284b40;}
			.mdo-destination-modal label span{font-weight:500;color:#798680;}
			.mdo-destination-modal select,.mdo-destination-modal input{box-sizing:border-box;width:100%;min-height:46px;margin:0;padding:0 13px;border:1px solid #d7dfdb;border-radius:9px;background:#fff;color:#173f32;font:inherit;font-size:15px;box-shadow:none;}
			.mdo-destination-modal select:focus,.mdo-destination-modal input:focus{border-color:#658a7d;outline:2px solid rgba(23,63,50,.08);outline-offset:1px;}
			.mdo-destination-modal__postcode{margin-top:17px;}
			.mdo-destination-modal__postcode[hidden]{display:none!important;}
			.mdo-destination-modal__postcode small{display:block;margin-top:7px;color:#718078;font-size:12px;line-height:1.45;}
			.mdo-destination-modal__error{margin:14px 0 0;padding:10px 12px;border-radius:8px;background:#fff4f2;color:#8f2d20;font-size:13px;line-height:1.4;}
			.mdo-destination-modal__save{display:flex;width:100%;min-height:46px;align-items:center;justify-content:center;margin-top:20px;padding:0 18px;border:0;border-radius:9px;background:#173f32;color:#fff;font:inherit;font-size:14px;font-weight:800;cursor:pointer;}
			.mdo-destination-modal__save[disabled]{opacity:.58;cursor:wait;}
			body.mdo-destination-modal-open{overflow:hidden;}
			@media (max-width:600px){.mdo-catalog-destination{margin:0 0 14px 8px;}.mdo-catalog-destination__trigger{min-height:36px;padding:0 11px;font-size:12px;}.mdo-destination-modal{align-items:flex-end;padding:0;}.mdo-destination-modal__panel{width:100%;padding:24px 20px 22px;border-radius:18px 18px 0 0;}.mdo-destination-modal__panel h2{font-size:20px;}}
		</style>
		<?php
	}

	public static function render_script(): void {
		if ( is_admin() || ! self::is_catalog_surface() ) {
			return;
		}
		$nonce = wp_create_nonce( 'mdo_shipping_destination' );
		?>
		<script id="mdo-catalog-destination-frontend-js">
		(() => {
			'use strict';
			const modal = document.querySelector('[data-mdo-destination-modal]');
			const openButton = document.querySelector('[data-mdo-destination-open]');
			if (!modal || !openButton) return;
			const panel = modal.querySelector('.mdo-destination-modal__panel');
			const form = modal.querySelector('[data-mdo-destination-form]');
			const country = modal.querySelector('[data-mdo-destination-country]');
			const postcodeWrap = modal.querySelector('[data-mdo-postcode-wrap]');
			const postcode = modal.querySelector('[data-mdo-destination-postcode]');
			const error = modal.querySelector('[data-mdo-destination-error]');
			const save = modal.querySelector('[data-mdo-destination-save]');
			let returnFocus = null;

			const syncPostcode = () => {
				const spanish = country.value === 'ES';
				postcodeWrap.hidden = !spanish;
				postcode.disabled = !spanish;
				if (!spanish) postcode.value = '';
			};
			const open = () => {
				returnFocus = document.activeElement;
				modal.hidden = false;
				modal.setAttribute('aria-hidden', 'false');
				document.body.classList.add('mdo-destination-modal-open');
				syncPostcode();
				setTimeout(() => panel.focus({preventScroll:true}), 0);
			};
			const close = () => {
				modal.hidden = true;
				modal.setAttribute('aria-hidden', 'true');
				document.body.classList.remove('mdo-destination-modal-open');
				error.hidden = true;
				if (returnFocus && typeof returnFocus.focus === 'function') returnFocus.focus({preventScroll:true});
			};

			openButton.addEventListener('click', open);
			modal.querySelectorAll('[data-mdo-destination-close]').forEach((button) => button.addEventListener('click', close));
			country.addEventListener('change', syncPostcode);
			document.addEventListener('keydown', (event) => {
				if (!modal.hidden && event.key === 'Escape') close();
			});

			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				error.hidden = true;
				if (country.value === 'ES' && postcode.value && !/^\d{5}$/.test(postcode.value.trim())) {
					error.textContent = <?php echo wp_json_encode( self::text( 'Introduce un código postal español de 5 cifras o déjalo vacío.', 'Enter a 5-digit Spanish postcode or leave it blank.' ) ); ?>;
					error.hidden = false;
					postcode.focus();
					return;
				}
				save.disabled = true;
				save.textContent = <?php echo wp_json_encode( self::text( 'Guardando…', 'Saving…' ) ); ?>;
				try {
					const body = new URLSearchParams({
						action: 'mdo_set_shipping_destination',
						nonce: <?php echo wp_json_encode( $nonce ); ?>,
						country: country.value,
						postcode: country.value === 'ES' ? postcode.value.trim() : ''
					});
					const response = await fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
						method: 'POST',
						credentials: 'same-origin',
						headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
						body: body.toString()
					});
					const payload = await response.json();
					if (!response.ok || !payload.success) throw new Error(payload?.data?.message || <?php echo wp_json_encode( self::text( 'No se pudo guardar el destino.', 'The destination could not be saved.' ) ); ?>);
					window.location.reload();
				} catch (exception) {
					error.textContent = exception?.message || <?php echo wp_json_encode( self::text( 'No se pudo guardar el destino.', 'The destination could not be saved.' ) ); ?>;
					error.hidden = false;
					save.disabled = false;
					save.textContent = <?php echo wp_json_encode( self::text( 'Guardar destino', 'Save destination' ) ); ?>;
				}
			});
		})();
		</script>
		<?php
	}

	public static function protect_personalized_catalog_from_cache(): void {
		if ( is_admin() || ! self::is_catalog_surface() ) {
			return;
		}
		$destination = self::current_destination();
		if ( 'ES' === $destination['country'] && '' === $destination['postcode'] ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}

	public static function maybe_invalidate_rank_from_meta( $meta_id, $object_id, $meta_key, $meta_value ): void {
		unset( $meta_id, $object_id, $meta_value );
		if ( class_exists( 'MDO_Catalog_Ranking' ) && MDO_Catalog_Ranking::PRIORITY_META === (string) $meta_key ) {
			self::invalidate_rank_cache();
		}
	}

	public static function maybe_invalidate_rank_from_order( int $order_id, string $from, string $to, $order ): void {
		unset( $order_id, $from, $order );
		if ( in_array( $to, array( 'processing', 'completed', 'on-hold' ), true ) ) {
			self::invalidate_rank_cache();
		}
	}

	private static function ranked_product_ids(): array {
		$key = self::rank_cache_key();
		$cached = get_transient( $key );
		if ( is_array( $cached ) && $cached ) {
			return array_values( array_filter( array_map( 'absint', $cached ) ) );
		}

		self::$building_rank = true;
		$ids = get_posts(
			array(
				'post_type'        => 'product',
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		self::$building_rank = false;

		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
		if ( ! $ids || ! class_exists( 'MDO_Catalog_Ranking' ) ) {
			return $ids;
		}

		$ranked = MDO_Catalog_Ranking::rank_products(
			$ids,
			array(
				'rotation_seed' => gmdate( 'Y-m-d' ),
				'diversify_vendors' => true,
			)
		);
		set_transient( $key, $ranked, self::RANK_CACHE_TTL );
		return $ranked;
	}

	private static function invalidate_rank_cache(): void {
		delete_transient( self::rank_cache_key() );
	}

	private static function rank_cache_key(): string {
		return self::RANK_CACHE_PREFIX . gmdate( 'Ymd' );
	}

	private static function is_catalog_query( WP_Query $query ): bool {
		if ( ! $query->is_main_query() || $query->is_singular() ) {
			return false;
		}
		if ( $query->is_post_type_archive( 'product' ) || $query->is_tax( 'product_cat' ) || $query->is_tax( 'product_tag' ) ) {
			return true;
		}
		if ( $query->is_tax() && 0 === strpos( (string) $query->get( 'taxonomy' ), 'pa_' ) ) {
			return true;
		}
		$post_type = $query->get( 'post_type' );
		return 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) );
	}

	private static function is_catalog_surface(): bool {
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return true;
		}
		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			return true;
		}
		return is_search() && 'product' === get_query_var( 'post_type' );
	}

	private static function is_ceuta_or_melilla( string $country, string $postcode ): bool {
		if ( 'ES' !== strtoupper( trim( $country ) ) ) {
			return false;
		}
		$digits = preg_replace( '/\D+/', '', $postcode );
		if ( strlen( $digits ) < 2 ) {
			return false;
		}
		return in_array( substr( $digits, 0, 2 ), array( '51', '52' ), true );
	}

	private static function set_cookie( string $name, string $value ): void {
		$path = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		$domain = defined( 'COOKIE_DOMAIN' ) ? (string) COOKIE_DOMAIN : '';
		setcookie(
			$name,
			$value,
			array(
				'expires'  => time() + 180 * DAY_IN_SECONDS,
				'path'     => $path,
				'domain'   => $domain,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		$_COOKIE[ $name ] = $value;
	}

	private static function text( string $es, string $en ): string {
		if ( function_exists( 'mdo_sst_is_english' ) && mdo_sst_is_english() ) {
			return $en;
		}
		if ( function_exists( 'mdo_en_is_request' ) && mdo_en_is_request() ) {
			return $en;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		return ( '/en' === $path || 0 === strpos( $path, '/en/' ) ) ? $en : $es;
	}
}

MDO_Catalog_Destination_Frontend::init();
