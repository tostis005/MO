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
		add_action( 'woocommerce_cart_totals_before_order_total', array( __CLASS__, 'render_cart_rows' ), 20 );
		add_action( 'woocommerce_review_order_before_order_total', array( __CLASS__, 'render_checkout_rows' ), 20 );
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
		foreach ( self::cart_minimums() as $entry ) {
			if ( $entry['missing'] <= 0 ) {
				continue;
			}
			wc_add_notice(
				sprintf(
					'Pedido mínimo de %1$s: te faltan %2$s para alcanzar el mínimo de %3$s.',
					$entry['name'],
					wp_strip_all_tags( wc_price( $entry['missing'] ) ),
					wp_strip_all_tags( wc_price( $entry['minimum'] ) )
				),
				'error'
			);
		}
	}

	public static function render_cart_rows(): void {
		self::render_rows( true );
	}

	public static function render_checkout_rows(): void {
		self::render_rows( false );
	}

	private static function render_rows( bool $cart_context ): void {
		foreach ( self::cart_minimums() as $entry ) {
			$ok    = $entry['missing'] <= 0;
			$label = 'Pedido mínimo · ' . $entry['name'];
			?>
			<tr class="emdo-minimum-order-row <?php echo $ok ? 'is-ok' : 'is-missing'; ?>">
				<td colspan="2"<?php echo $cart_context ? ' data-title="' . esc_attr( $label ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<div class="emdo-minimum-order-card">
						<div class="emdo-minimum-order-card__title"><?php echo esc_html( $label ); ?></div>
						<?php if ( $ok ) : ?>
							<div class="emdo-minimum-order-card__message">Pedido mínimo alcanzado.</div>
							<div class="emdo-minimum-order-card__meta">
								<?php echo wp_kses_post( 'Llevas ' . wc_price( $entry['subtotal'] ) . ' en productos de ' . esc_html( $entry['name'] ) . ' (mínimo ' . wc_price( $entry['minimum'] ) . ').' ); ?>
							</div>
						<?php else : ?>
							<div class="emdo-minimum-order-card__message">
								<?php echo wp_kses_post( 'Te faltan <strong>' . wc_price( $entry['missing'] ) . '</strong> para alcanzar el pedido mínimo de <strong>' . wc_price( $entry['minimum'] ) . '</strong>.' ); ?>
							</div>
							<div class="emdo-minimum-order-card__meta">
								<?php echo wp_kses_post( 'Llevas ' . wc_price( $entry['subtotal'] ) . ' en productos de ' . esc_html( $entry['name'] ) . '.' ); ?>
							</div>
						<?php endif; ?>
					</div>
				</td>
			</tr>
			<?php
		}
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
