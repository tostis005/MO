<?php
/**
 * Seguimientos rapidos desde WooCommerce > Pedidos, sin recargar la lista.
 *
 * La nota usa exactamente el mecanismo publico de WooCommerce para que se
 * dispare el correo "Nota del cliente" del mismo modo que al editar un pedido.
 * Compatible con HPOS y el almacenamiento clasico de pedidos.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'ELMERCADO_QUICK_TRACKING_20261118_LOADED' ) ) {
	return;
}
define( 'ELMERCADO_QUICK_TRACKING_20261118_LOADED', true );

if ( ! is_admin() ) {
	return;
}

/**
 * Coloca la columna inmediatamente despues del estado del pedido.
 *
 * @param array<string, string> $columns Columnas del listado.
 * @return array<string, string>
 */
function elmercado_tracking_orders_columns_20261118( array $columns ): array {
	$result = array();
	foreach ( $columns as $key => $label ) {
		$result[ $key ] = $label;
		if ( 'order_status' === $key ) {
			$result['emo_quick_tracking'] = 'Seguimiento';
		}
	}
	if ( ! isset( $result['emo_quick_tracking'] ) ) {
		$result['emo_quick_tracking'] = 'Seguimiento';
	}
	return $result;
}

add_filter( 'manage_woocommerce_page_wc-orders_columns', 'elmercado_tracking_orders_columns_20261118', 30 );
add_filter( 'manage_edit-shop_order_columns', 'elmercado_tracking_orders_columns_20261118', 30 );

/**
 * La columna recibe un WC_Order en HPOS y un ID en el listado clasico.
 *
 * @param string       $column Columna solicitada.
 * @param WC_Order|int $order_or_id Pedido o identificador.
 */
function elmercado_tracking_orders_cell_20261118( $column, $order_or_id ): void {
	if ( 'emo_quick_tracking' !== $column || ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order( (int) $order_or_id );
	if ( ! $order instanceof WC_Order ) {
		echo '<span aria-label="Pedido no disponible">—</span>';
		return;
	}

	$order_id = $order->get_id();
	$sent_at  = (int) $order->get_meta( '_elmercado_tracking_sent_at', true );

	if ( $sent_at ) {
		echo '<div class="emo-quick-tracking emo-quick-tracking--sent">';
		echo '<strong class="emo-tracking-badge" role="status">✓ Enviado</strong>';
		echo '<small class="emo-tracking-date">' . esc_html( wp_date( 'd/m/Y H:i', $sent_at ) ) . '</small>';
		echo '</div>';
		return;
	}

	?>
	<div class="emo-quick-tracking" data-order-id="<?php echo esc_attr( $order_id ); ?>">
		<textarea
			class="emo-tracking-message"
			rows="4"
			maxlength="5000"
			placeholder="Texto o enlace de seguimiento…"
			aria-label="<?php echo esc_attr( sprintf( 'Seguimiento del pedido %s', $order->get_order_number() ) ); ?>"
		></textarea>
		<div class="emo-tracking-actions">
			<button type="button" class="button button-primary emo-tracking-send">Enviar al cliente</button>
			<span class="emo-tracking-feedback" role="status" aria-live="polite"></span>
		</div>
	</div>
	<?php
}

add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'elmercado_tracking_orders_cell_20261118', 30, 2 );
add_action( 'manage_shop_order_posts_custom_column', 'elmercado_tracking_orders_cell_20261118', 30, 2 );

/** Solo carga CSS y JS en el listado NATIVO de pedidos. */
add_action(
	'admin_enqueue_scripts',
	static function (): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order' ), true ) ) {
			return;
		}

		wp_register_style( 'elmercado-order-quick-tracking', false, array(), ELMERCADO_THEME_VERSION );
		wp_enqueue_style( 'elmercado-order-quick-tracking' );
		wp_add_inline_style(
			'elmercado-order-quick-tracking',
			<<<'CSS'
.wp-list-table .column-emo_quick_tracking{width:330px;min-width:290px}
.emo-quick-tracking{position:relative;z-index:2;isolation:isolate;max-width:330px;box-sizing:border-box}
.emo-quick-tracking .emo-tracking-message{display:block;position:relative;z-index:1;box-sizing:border-box;width:100%;min-height:104px;padding:9px 10px;margin:0 0 8px;border:1px solid #aab5ae;border-radius:6px;background:#fff;color:#1d2327;font-size:13px;line-height:1.5;resize:vertical;cursor:text}
.emo-quick-tracking .emo-tracking-message:focus{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}
.emo-tracking-actions{display:flex;flex-direction:column;align-items:flex-start;gap:4px}
.emo-tracking-actions .button{min-height:29px;line-height:1.4;padding:4px 10px;white-space:normal;text-align:center}
.emo-tracking-feedback{font-size:12px;line-height:1.35;color:#b32d2e}
.emo-tracking-badge{display:inline-block;color:#176a40;font-size:12px;font-weight:700}
.emo-tracking-date{display:block;margin-top:3px;color:#646970;font-size:11px;white-space:nowrap}
@media(max-width:782px){.wp-list-table .column-emo_quick_tracking{width:auto;min-width:230px}.emo-quick-tracking{max-width:100%}}
CSS
		);

		wp_register_script( 'elmercado-order-quick-tracking', false, array(), ELMERCADO_THEME_VERSION, true );
		wp_enqueue_script( 'elmercado-order-quick-tracking' );
		wp_add_inline_script(
			'elmercado-order-quick-tracking',
			'window.elmercadoOrderQuickTracking = ' . wp_json_encode(
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'elmercado_send_order_tracking' ),
					'timeZone' => wp_timezone_string(),
					'utcOffsetMinutes' => (int) round( wp_timezone()->getOffset( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) ) / 60 ),
				)
			) . ';',
			'before'
		);
		wp_add_inline_script(
			'elmercado-order-quick-tracking',
			<<<'JS'
(function () {
	"use strict";
	const config = window.elmercadoOrderQuickTracking || {};

	// Hora local de WordPress (normalmente Espana): se recalcula en cada pegado.
	// El fallback admite sitios configurados con un desplazamiento UTC fijo.
	function greetingForNow() {
		let hour;
		try {
			const parts = new Intl.DateTimeFormat("en-GB", {
				timeZone: config.timeZone || "UTC",
				hour: "2-digit",
				hourCycle: "h23"
			}).formatToParts(new Date());
			hour = Number(parts.find((part) => part.type === "hour")?.value);
			if (!Number.isInteger(hour) || hour < 0 || hour > 23) throw new Error("Invalid timezone hour");
		} catch (error) {
			const offset = Number(config.utcOffsetMinutes);
			hour = new Date(Date.now() + (Number.isFinite(offset) ? offset : 0) * 60000).getUTCHours();
		}
		if (hour >= 6 && hour < 14) return "Buenos días";
		if (hour >= 14 && hour < 21) return "Buenas tardes";
		return "Buenas noches";
	}

	function normalizeTrackingPaste(raw) {
		let message = String(raw).trim();
		if (!message) return message;

		// Unicamente las comillas exteriores; nunca tocar las del mensaje.
		const quoted = (message.startsWith('"') && message.endsWith('"'))
			|| (message.startsWith("“") && message.endsWith("”"));
		if (quoted && message.length >= 2) message = message.slice(1, -1).trim();

		// El encabezamiento es la unica parte personalizada del texto.
		const greeting = /^(?:buenos\s+d[ií]as|buenas\s+tardes|buenas\s+noches)\b/iu;
		const match = message.match(greeting);
		if (!match) return message;
		message = greetingForNow() + message.slice(match[0].length);

		// El nombre va al principio, tras el saludo, y termina en coma +
		// salto de linea. No alterar las lineas posteriores ni otros nombres.
		const header = message.match(/^(?:Buenos días|Buenas tardes|Buenas noches)([ \t]*,?[ \t]*)([^\r\n,]{1,120}),([ \t]*)(\r?\n|$)/u);
		if (header) {
			const fullName = header[2].trim();
			const firstName = fullName.split(/\s+/u)[0];
			// Evitar truncar una frase cualquiera si no parece un nombre.
			if (/^\p{Lu}[\p{L}\p{M}'’\-]*$/u.test(firstName) && /^[\p{L}\p{M}'’\- ]+$/u.test(fullName)) {
				const separator = header[1].includes(",") ? ", " : " ";
				message = greetingForNow() + separator + firstName + "," + header[3] + header[4] + message.slice(header[0].length);
			}
		}
		return message;
	}

	// The standard WooCommerce orders table has a row-level click-to-open
	// handler. Keep pointer, click and keyboard events inside our controls.
	// Do not preventDefault: textarea focus, selection, paste and buttons
	// must continue to behave exactly like normal form controls.
	document.querySelectorAll(".emo-quick-tracking").forEach(function (trackingControl) {
		["pointerdown", "mousedown", "mouseup", "click", "dblclick", "touchstart", "keydown"].forEach(function (eventName) {
			trackingControl.addEventListener(eventName, function (event) {
				event.stopPropagation();
			});
		});


		const textarea = trackingControl.querySelector(".emo-tracking-message");
		if (textarea) {
			textarea.addEventListener("paste", function (event) {
				const clipboard = event.clipboardData;
				if (!clipboard || typeof clipboard.getData !== "function") return;
				const raw = clipboard.getData("text/plain");
				if (typeof raw !== "string" || !raw) return;
				const cleaned = normalizeTrackingPaste(raw);
				if (cleaned === raw) return;
				event.preventDefault();
				// Conserva el texto ya escrito fuera de la seleccion actual.
				textarea.setRangeText(cleaned, textarea.selectionStart, textarea.selectionEnd, "end");
				textarea.dispatchEvent(new Event("input", { bubbles: true }));
			});
		}

		trackingControl.addEventListener("click", async function (event) {
		if (!(event.target instanceof Element)) return;
		const button = event.target.closest(".emo-tracking-send");
		if (!button || button.disabled) return;

		const wrapper = button.closest(".emo-quick-tracking");
		const textarea = wrapper && wrapper.querySelector(".emo-tracking-message");
		const feedback = wrapper && wrapper.querySelector(".emo-tracking-feedback");
		if (!wrapper || !textarea || !feedback) return;

		const message = textarea.value.trim();
		feedback.textContent = "";
		if (!message) {
			feedback.textContent = "Escribe o pega el seguimiento.";
			textarea.focus();
			return;
		}
		if (!config.ajaxUrl || !config.nonce) {
			feedback.textContent = "Recarga la pagina para enviar el seguimiento.";
			return;
		}

		button.disabled = true;
		textarea.disabled = true;
		button.textContent = "Enviando…";

		try {
			const body = new URLSearchParams({
				action: "elmercado_send_order_tracking",
				nonce: config.nonce,
				order_id: wrapper.dataset.orderId || "",
				message: message
			});
			const response = await fetch(config.ajaxUrl, {
				method: "POST",
				credentials: "same-origin",
				headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"},
				body: body.toString()
			});
			const payload = await response.json();
			if (!response.ok || !payload.success) {
				throw new Error((payload.data && payload.data.message) || "No se pudo enviar. Intentalo otra vez.");
			}

			const badge = document.createElement("strong");
			badge.className = "emo-tracking-badge";
			badge.setAttribute("role", "status");
			badge.textContent = "✓ Enviado";
			const date = document.createElement("small");
			date.className = "emo-tracking-date";
			date.textContent = payload.data.date || "";
			wrapper.classList.add("emo-quick-tracking--sent");
			wrapper.replaceChildren(badge, date);
		} catch (error) {
			feedback.textContent = error instanceof Error ? error.message : "No se pudo enviar. Intentalo otra vez.";
			button.disabled = false;
			textarea.disabled = false;
			button.textContent = "Enviar al cliente";
		}
		});
	});
})();
JS
		);
	}
);

/**
 * Anade una unica nota publica y deja constancia de su envio rapido.
 * La nota publica dispara la notificacion nativa de correo de WooCommerce.
 */
add_action(
	'wp_ajax_elmercado_send_order_tracking',
	static function (): void {
		if ( ! check_ajax_referer( 'elmercado_send_order_tracking', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'La sesion ha caducado. Recarga el listado.' ), 403 );
		}
		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permiso para editar pedidos.' ), 403 );
		}
		if ( ! function_exists( 'wc_get_order' ) || ! isset( $_POST['message'] ) || ! is_string( $_POST['message'] ) ) {
			wp_send_json_error( array( 'message' => 'Peticion no valida.' ), 400 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$message  = trim( sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) );
		if ( ! $order_id || '' === $message || strlen( $message ) > 10000 ) {
			wp_send_json_error( array( 'message' => 'Indica un texto de seguimiento valido (maximo 5000 caracteres).' ), 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			wp_send_json_error( array( 'message' => 'No se ha encontrado el pedido.' ), 404 );
		}
		if ( ! is_email( $order->get_billing_email() ) ) {
			wp_send_json_error( array( 'message' => 'Este pedido no tiene un correo de cliente valido.' ), 400 );
		}

		$sent_at = (int) $order->get_meta( '_elmercado_tracking_sent_at', true );
		if ( $sent_at ) {
			wp_send_json_success( array( 'date' => wp_date( 'd/m/Y H:i', $sent_at ), 'already_sent' => true ) );
		}

		// add_option es atomico y evita enviar dos correos por dos clics simultaneos.
		$lock_key = 'elmercado_tracking_lock_' . $order_id;
		$locked   = add_option( $lock_key, time(), '', false );
		if ( ! $locked ) {
			$started = (int) get_option( $lock_key, 0 );
			if ( $started > 0 && time() - $started > 120 ) {
				delete_option( $lock_key );
				$locked = add_option( $lock_key, time(), '', false );
			}
		}
		if ( ! $locked ) {
			wp_send_json_error( array( 'message' => 'Ya hay un envio en curso para este pedido.' ), 409 );
		}

		$error   = '';
		$sent_at = 0;
		try {
			// Revalidar bajo el bloqueo, tambien si otra pestaña envio antes.
			$order = wc_get_order( $order_id );
			$sent_at = (int) $order->get_meta( '_elmercado_tracking_sent_at', true );
			if ( ! $sent_at ) {
				$note_id = $order->add_order_note( $message, 1, true );
				if ( ! $note_id ) {
					throw new RuntimeException( 'No se pudo guardar la nota.' );
				}
				$sent_at = time();
				$order->update_meta_data( '_elmercado_tracking_sent_at', $sent_at );
				$order->update_meta_data( '_elmercado_tracking_note_id', (int) $note_id );
				$order->save();
			}
		} catch ( Throwable $exception ) {
			$error = 'No se ha podido completar el envio. Revisa las notas del pedido antes de reintentarlo.';
		} finally {
			delete_option( $lock_key );
		}

		if ( $error ) {
			wp_send_json_error( array( 'message' => $error ), 500 );
		}
		wp_send_json_success( array( 'date' => wp_date( 'd/m/Y H:i', $sent_at ) ) );
	}
);
