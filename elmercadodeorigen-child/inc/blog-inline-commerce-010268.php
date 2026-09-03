<?php
/**
 * Bloques comerciales editoriales para entradas del blog.
 *
 * Prepara anclas dentro del cuerpo del articulo y un formulario de captacion.
 * El especial vigente no se vuelve a renderizar: el JavaScript geografico mueve
 * al ancla temprana el bloque que single.php ya imprime antes de relacionados.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve textos del bloque de suscripcion segun el idioma activo.
 *
 * @return array<string,string>
 */
function elmercado_blog_newsletter_copy(): array {
	$is_english = 0 === strpos( strtolower( (string) get_locale() ), 'en' );

	if ( $is_english ) {
		return array(
			'eyebrow'     => 'From the market',
			'title'       => 'Keep discovering producers, products and origin stories',
			'body'        => 'Receive our latest articles and selected news from El Mercado de Origen by email.',
			'email_label' => 'Your email address',
			'email'       => 'you@example.com',
			'button'      => 'Subscribe',
			'consent'     => 'I agree to receive emails from El Mercado de Origen. I can unsubscribe at any time.',
			'privacy'     => 'Privacy policy',
			'success'     => 'Check your inbox to confirm your subscription.',
			'already'     => 'You are already subscribed.',
			'error'       => 'We could not process your subscription. Please try again.',
		);
	}

	return array(
		'eyebrow'     => 'Desde el mercado',
		'title'       => 'Sigue descubriendo productores, productos e historias de origen',
		'body'        => 'Recibe por correo nuestros nuevos artículos y una selección de novedades de El Mercado de Origen.',
		'email_label' => 'Tu correo electrónico',
		'email'       => 'tu@correo.com',
		'button'      => 'Suscribirme',
		'consent'     => 'Acepto recibir correos de El Mercado de Origen. Puedo darme de baja en cualquier momento.',
		'privacy'     => 'Política de privacidad',
		'success'     => 'Revisa tu correo para confirmar la suscripción.',
		'already'     => 'Ya estás suscrito.',
		'error'       => 'No hemos podido procesar la suscripción. Inténtalo de nuevo.',
	);
}

/**
 * Comprueba si la API PHP de FluentCRM esta disponible.
 */
function elmercado_blog_newsletter_available(): bool {
	return function_exists( 'FluentCrmApi' );
}

/**
 * Construye el formulario de suscripcion a FluentCRM.
 */
function elmercado_blog_newsletter_html( int $post_id ): string {
	if ( ! elmercado_blog_newsletter_available() ) {
		return '';
	}

	$copy        = elmercado_blog_newsletter_copy();
	$privacy_url = get_privacy_policy_url();
	$status      = isset( $_GET['emo_newsletter'] ) ? sanitize_key( wp_unslash( $_GET['emo_newsletter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$message     = '';

	if ( 'success' === $status ) {
		$message = $copy['success'];
	} elseif ( 'already' === $status ) {
		$message = $copy['already'];
	} elseif ( 'error' === $status ) {
		$message = $copy['error'];
	}

	$redirect_url = get_permalink( $post_id );
	if ( ! is_string( $redirect_url ) || '' === $redirect_url ) {
		$redirect_url = home_url( '/' );
	}

	ob_start();
	?>
	<aside id="emo-newsletter" class="emo-inline-commerce emo-inline-newsletter" data-emo-commerce="newsletter" hidden aria-hidden="true">
		<span class="emo-inline-newsletter__eyebrow"><?php echo esc_html( $copy['eyebrow'] ); ?></span>
		<h3><?php echo esc_html( $copy['title'] ); ?></h3>
		<p class="emo-inline-newsletter__body"><?php echo esc_html( $copy['body'] ); ?></p>

		<?php if ( '' !== $message ) : ?>
			<p class="emo-inline-newsletter__notice" role="status"><?php echo esc_html( $message ); ?></p>
		<?php endif; ?>

		<form class="emo-inline-newsletter__form" action="<?php echo esc_url( $redirect_url ); ?>" method="post">
			<input type="hidden" name="emo_newsletter_submit" value="1">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_url ); ?>">
			<?php echo wp_nonce_field( 'elmercado_blog_subscribe', 'emo_newsletter_nonce', true, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="emo-inline-newsletter__honeypot" aria-hidden="true">
				<label for="emo_newsletter_company">Company</label>
				<input id="emo_newsletter_company" type="text" name="company" value="" tabindex="-1" autocomplete="off">
			</div>
			<div class="emo-inline-newsletter__row">
				<label class="screen-reader-text" for="emo_newsletter_email"><?php echo esc_html( $copy['email_label'] ); ?></label>
				<input id="emo_newsletter_email" type="email" name="email" placeholder="<?php echo esc_attr( $copy['email'] ); ?>" autocomplete="email" required>
				<button type="submit"><?php echo esc_html( $copy['button'] ); ?></button>
			</div>
			<label class="emo-inline-newsletter__consent">
				<input type="checkbox" name="consent" value="1" required>
				<span>
					<?php echo esc_html( $copy['consent'] ); ?>
					<?php if ( '' !== $privacy_url ) : ?>
						<a href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( $copy['privacy'] ); ?></a>.
					<?php endif; ?>
				</span>
			</label>
		</form>
	</aside>
	<?php
	return trim( (string) ob_get_clean() );
}

/**
 * Devuelve la posicion, en bytes, inmediatamente posterior al parrafo N.
 * Si el articulo tiene menos parrafos, devuelve el final del contenido.
 */
function elmercado_blog_offset_after_paragraph( string $html, int $paragraph ): int {
	if ( $paragraph < 1 ) {
		return 0;
	}

	$matches = array();
	preg_match_all( '/<\/p\s*>/i', $html, $matches, PREG_OFFSET_CAPTURE );

	if ( empty( $matches[0] ) || count( $matches[0] ) < $paragraph ) {
		return strlen( $html );
	}

	$match = $matches[0][ $paragraph - 1 ];

	return (int) $match[1] + strlen( (string) $match[0] );
}

/**
 * Inserta las anclas en posiciones calculadas sobre el HTML original.
 *
 * El newsletter nace junto al ancla temprana. Si JavaScript detecta un especial
 * vigente, mueve el newsletter al ancla posterior y coloca el especial primero.
 */
function elmercado_blog_inject_inline_commercial_blocks( string $content_html ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content_html;
	}

	$post_id = get_the_ID();
	if ( $post_id < 1 ) {
		return $content_html;
	}

	static $injected = array();
	if ( isset( $injected[ $post_id ] ) ) {
		return $content_html;
	}
	$injected[ $post_id ] = true;

	$newsletter_html = elmercado_blog_newsletter_html( $post_id );
	$early_html      = '<div class="emo-inline-commerce emo-inline-special-anchor" data-emo-commerce="special" data-emo-special-anchor hidden aria-hidden="true"></div>' . $newsletter_html;
	$later_html      = '<div class="emo-inline-newsletter-anchor" data-emo-newsletter-anchor></div>';
	$early_offset    = elmercado_blog_offset_after_paragraph( $content_html, 3 );
	$later_offset    = elmercado_blog_offset_after_paragraph( $content_html, 7 );

	$by_offset = array();
	$by_offset[ $early_offset ] = $early_html;
	if ( isset( $by_offset[ $later_offset ] ) ) {
		$by_offset[ $later_offset ] .= $later_html;
	} else {
		$by_offset[ $later_offset ] = $later_html;
	}

	krsort( $by_offset, SORT_NUMERIC );
	foreach ( $by_offset as $offset => $insert_html ) {
		$content_html = substr( $content_html, 0, (int) $offset ) . $insert_html . substr( $content_html, (int) $offset );
	}

	return $content_html;
}

/**
 * Procesa la suscripcion publica y la envia a FluentCRM con doble opt-in.
 */
function elmercado_blog_handle_subscription(): void {
	$redirect = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : home_url( '/' );
	$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );
	$redirect = remove_query_arg( 'emo_newsletter', $redirect );
	$result   = 'error';

	$nonce   = isset( $_POST['emo_newsletter_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['emo_newsletter_nonce'] ) ) : '';
	$company = isset( $_POST['company'] ) ? trim( (string) wp_unslash( $_POST['company'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$consent = isset( $_POST['consent'] ) && '1' === (string) wp_unslash( $_POST['consent'] );

	if ( '' !== $company ) {
		$result = 'success';
	} elseif ( ! wp_verify_nonce( $nonce, 'elmercado_blog_subscribe' ) || ! $consent || ! is_email( $email ) || ! elmercado_blog_newsletter_available() ) {
		$result = 'error';
	} else {
		try {
			$contact_api = FluentCrmApi( 'contacts' );
			$contact     = $contact_api ? $contact_api->getContact( $email ) : false;

			if ( $contact && isset( $contact->status ) && 'subscribed' === (string) $contact->status ) {
				$result = 'already';
			} else {
				$contact = $contact_api ? $contact_api->createOrUpdate(
					array(
						'email'  => $email,
						'status' => 'pending',
					),
					true
				) : false;

				if ( $contact && method_exists( $contact, 'sendDoubleOptinEmail' ) ) {
					$contact->sendDoubleOptinEmail();
					$result = 'success';
				}
			}
		} catch ( Throwable $exception ) {
			$result = 'error';
		}
	}

	$target = add_query_arg( 'emo_newsletter', $result, $redirect ) . '#emo-newsletter';
	wp_safe_redirect( $target );
	exit;
}

/**
 * Procesa solo los POST del formulario incrustado en la propia entrada.
 */
function elmercado_blog_maybe_handle_subscription(): void {
	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	$is_submit      = isset( $_POST['emo_newsletter_submit'] ) && '1' === (string) wp_unslash( $_POST['emo_newsletter_submit'] );

	if ( 'POST' === $request_method && $is_submit ) {
		elmercado_blog_handle_subscription();
	}
}
