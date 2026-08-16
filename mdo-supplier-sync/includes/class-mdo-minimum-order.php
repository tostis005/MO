<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Minimum_Order {
	private const MANUAL_SOURCE_SENTINEL = ' ';
	private static bool $validated = false;

	public static function init(): void {
		add_action( 'admin_footer', array( __CLASS__, 'admin_supplier_field' ) );
		add_action( 'admin_post_mdo_save_supplier', array( __CLASS__, 'prepare_manual_supplier_save' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'validate_cart' ), 20 );
		// Antes de que WooCommerce pinte el botón estándar, lo retiramos si falta algún mínimo.
		add_action( 'woocommerce_proceed_to_checkout', array( __CLASS__, 'maybe_disable_checkout_button' ), 1 );
		// El aviso se renderiza fuera de la tabla de totales para ocupar todo el ancho.
		add_action( 'woocommerce_proceed_to_checkout', array( __CLASS__, 'render_cart_notice' ), 5 );
		add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'render_checkout_notice' ), 5 );
	}

	public static function enqueue_styles(): void {
		if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
			return;
		}
		wp_enqueue_style(
			'mdo-supplier-sync-frontend',
			MDO_SUPPLIER_SYNC_URL . 'assets/frontend.css',
			array(),
			MDO_SUPPLIER_SYNC_VERSION
		);
	}

	public static function prepare_manual_supplier_save(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$connector = isset( $_POST['connector'] ) ? sanitize_key( wp_unslash( $_POST['connector'] ) ) : 'none';
		$source    = isset( $_POST['source_url'] ) ? trim( (string) wp_unslash( $_POST['source_url'] ) ) : '';
		if ( 'none' === $connector && '' === $source ) {
			$_POST['source_url'] = self::MANUAL_SOURCE_SENTINEL;
		}
	}

	public static function admin_supplier_field(): void {
		if ( ! is_admin() || ! isset( $_GET['page'] ) || 'mdo-supplier-sync-suppliers' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$supplier_id = isset( $_GET['supplier_id'] ) ? absint( $_GET['supplier_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$supplier    = $supplier_id ? MDO_Supplier_Repository::find( $supplier_id ) : null;
		$minimum     = $supplier && null !== $supplier['minimum_order_amount'] ? (string) $supplier['minimum_order_amount'] : '';
		?>
		<script>
		(function () {
			const form = document.querySelector('form.mdo-form');
			if (!form) return;

			const commission = form.querySelector('input[name="commission_percent"]');
			const commercialGrid = commission ? commission.closest('.mdo-grid') : null;
			if (commercialGrid && !form.querySelector('input[name="minimum_order_amount"]')) {
				const label = document.createElement('label');
				label.innerHTML = '<span>Pedido mínimo (€)</span><input type="number" step="0.01" min="0" name="minimum_order_amount" value="<?php echo esc_js( $minimum ); ?>"><small>Subtotal mínimo de productos de este vendedor para permitir completar el pedido. Déjalo vacío o a 0 para no exigir mínimo.</small>';
				commercialGrid.appendChild(label);
			}

			const source = form.querySelector('input[name="source_url"]');
			const connector = form.querySelector('select[name="connector"]');
			if (source && connector) {
				const updateSourceRequirement = () => {
					const manual = connector.value === 'none';
					source.required = !manual;
					const small = source.closest('label') ? source.closest('label').querySelector('small.mdo-manual-source-note') : null;
					if (small) small.remove();
					if (manual && source.closest('label')) {
						const note = document.createElement('small');
						note.className = 'mdo-manual-source-note';
						note.textContent = 'Opcional si el proveedor solo se gestiona manualmente y no usa scraper.';
						source.closest('label').appendChild(note);
					}
				};
				connector.addEventListener('change', updateSourceRequirement);
				updateSourceRequirement();
			}
		})();
		</script>
		<?php
	}

	public static function validate_cart(): void {
		if ( self::$validated || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		self::$validated = true;
		$is_english = self::is_english_request();
		foreach ( self::cart_minimums() as $entry ) {
			if ( $entry['missing'] <= 0 ) {
				continue;
			}
			$format = $is_english
				? 'Minimum order for %1$s: you need %2$s more to reach the minimum of %3$s.'
				: 'Pedido mínimo de %1$s: te faltan %2$s para alcanzar el mínimo de %3$s.';
			wc_add_notice(
				sprintf(
					$format,
					$entry['name'],
					self::plain_price( $entry['missing'] ),
					self::plain_price( $entry['minimum'] )
				),
				'error'
			);
		}
	}

	public static function maybe_disable_checkout_button(): void {
		if ( ! self::has_unmet_minimum() ) {
			return;
		}
		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
	}

	public static function render_cart_notice(): void {
		self::render_notices();
	}

	public static function render_checkout_notice(): void {
		self::render_notices();
	}

	private static function render_notices(): void {
		$missing = array_values(
			array_filter(
				self::cart_minimums(),
				static fn( array $entry ): bool => $entry['missing'] > 0
			)
		);
		if ( ! $missing ) {
			return;
		}
		$is_english = self::is_english_request();
		?>
		<div class="emdo-minimum-order-notices" role="alert" aria-live="polite">
			<?php foreach ( $missing as $entry ) : ?>
				<div class="emdo-minimum-order-card">
					<div class="emdo-minimum-order-card__title"><?php echo esc_html( ( $is_english ? 'Minimum order · ' : 'Pedido mínimo · ' ) . $entry['name'] ); ?></div>
					<div class="emdo-minimum-order-card__message">
						<?php if ( $is_english ) : ?>
							You need <strong><?php echo esc_html( self::plain_price( $entry['missing'] ) ); ?></strong> more to reach the minimum order of <strong><?php echo esc_html( self::plain_price( $entry['minimum'] ) ); ?></strong>.
						<?php else : ?>
							Te faltan <strong><?php echo esc_html( self::plain_price( $entry['missing'] ) ); ?></strong> para alcanzar el pedido mínimo de <strong><?php echo esc_html( self::plain_price( $entry['minimum'] ) ); ?></strong>.
						<?php endif; ?>
					</div>
					<div class="emdo-minimum-order-card__meta">
						<?php
						$meta = $is_english
							? 'You currently have ' . self::plain_price( $entry['subtotal'] ) . ' in products from ' . $entry['name'] . '.'
							: 'Llevas ' . self::plain_price( $entry['subtotal'] ) . ' en productos de ' . $entry['name'] . '.';
						echo esc_html( $meta );
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function is_english_request(): bool {
		global $TRP_LANGUAGE;

		if ( isset( $TRP_LANGUAGE ) && is_string( $TRP_LANGUAGE ) && '' !== $TRP_LANGUAGE ) {
			return 0 === strpos( strtolower( $TRP_LANGUAGE ), 'en' );
		}

		if ( function_exists( 'trp_get_current_language' ) ) {
			$language = trp_get_current_language();
			if ( is_string( $language ) && '' !== $language ) {
				return 0 === strpos( strtolower( $language ), 'en' );
			}
		}

		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		return 1 === preg_match( '#^/en(?:/|$)#i', $path );
	}

	private static function has_unmet_minimum(): bool {
		foreach ( self::cart_minimums() as $entry ) {
			if ( $entry['missing'] > 0 ) {
				return true;
			}
		}
		return false;
	}

	private static function plain_price( float $amount ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	private static function cart_minimums(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}
		$rules = self::rules_by_vendor();
		if ( ! $rules ) {
			return array();
		}

		$totals = array();
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;
			if ( ! $product || $product->is_type( 'bundle' ) && ! empty( $cart_item['bundled_by'] ) ) {
				continue;
			}
			$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$vendor_id  = (int) get_post_field( 'post_author', $product_id );
			if ( ! $vendor_id || ! isset( $rules[ $vendor_id ] ) ) {
				continue;
			}
			$quantity = isset( $cart_item['quantity'] ) ? (float) $cart_item['quantity'] : 0.0;
			$line     = (float) wc_get_price_to_display( $product, array( 'qty' => $quantity ) );
			$totals[ $vendor_id ] = ( $totals[ $vendor_id ] ?? 0.0 ) + $line;
		}

		$out = array();
		foreach ( $totals as $vendor_id => $subtotal ) {
			$rule = $rules[ $vendor_id ];
			$minimum = (float) $rule['minimum_order_amount'];
			if ( $minimum <= 0 ) {
				continue;
			}
			$out[] = array(
				'vendor_id' => (int) $vendor_id,
				'name'      => (string) $rule['name'],
				'subtotal'  => round( $subtotal, wc_get_price_decimals() ),
				'minimum'   => $minimum,
				'missing'   => max( 0.0, round( $minimum - $subtotal, wc_get_price_decimals() ) ),
			);
		}
		return $out;
	}

	private static function rules_by_vendor(): array {
		global $wpdb;
		$table = MDO_Database::table( 'suppliers' );
		$rows  = $wpdb->get_results(
			"SELECT id, name, vendor_user_id, minimum_order_amount FROM {$table} WHERE vendor_user_id IS NOT NULL AND minimum_order_amount IS NOT NULL AND minimum_order_amount > 0 ORDER BY id ASC",
			ARRAY_A
		) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rules = array();
		foreach ( $rows as $row ) {
			$vendor_id = (int) $row['vendor_user_id'];
			if ( $vendor_id > 0 ) {
				$rules[ $vendor_id ] = $row;
			}
		}
		return $rules;
	}
}
