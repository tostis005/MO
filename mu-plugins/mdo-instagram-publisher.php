<?php
/**
 * Plugin Name: EMDO Instagram Publisher
 * Description: Internal Instagram publishing integration for El Mercado de Origen.
 * Version: 1.0.0
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EMDO_IG_APP_ID', '1387712023313615' );
define( 'EMDO_IG_API_VERSION', 'v24.0' );
define( 'EMDO_IG_GRAPH_BASE', 'https://graph.instagram.com/' . EMDO_IG_API_VERSION );
define( 'EMDO_IG_TOKEN_OPTION', 'emdo_ig_access_token_encrypted' );
define( 'EMDO_IG_ACCOUNT_OPTION', 'emdo_ig_account' );
define( 'EMDO_IG_LOG_OPTION', 'emdo_ig_publish_log' );

/**
 * Encrypt a token before storing it in WordPress.
 */
function emdo_ig_encrypt_token( $token ) {
	if ( ! function_exists( 'openssl_encrypt' ) ) {
		return new WP_Error( 'openssl_missing', 'El servidor no dispone de OpenSSL para cifrar el token.' );
	}

	$key = hash( 'sha256', wp_salt( 'auth' ) . '|' . wp_salt( 'secure_auth' ), true );
	$iv  = random_bytes( 12 );
	$tag = '';

	$ciphertext = openssl_encrypt( $token, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
	if ( false === $ciphertext ) {
		return new WP_Error( 'encrypt_failed', 'No se ha podido cifrar el token de Instagram.' );
	}

	return 'v1:' . base64_encode( $iv ) . ':' . base64_encode( $tag ) . ':' . base64_encode( $ciphertext );
}

/**
 * Read and decrypt the stored Instagram token.
 */
function emdo_ig_get_token() {
	$stored = get_option( EMDO_IG_TOKEN_OPTION, '' );
	if ( ! is_string( $stored ) || '' === $stored ) {
		return new WP_Error( 'token_missing', 'No hay ningún token de Instagram guardado.' );
	}

	$parts = explode( ':', $stored, 4 );
	if ( 4 !== count( $parts ) || 'v1' !== $parts[0] || ! function_exists( 'openssl_decrypt' ) ) {
		return new WP_Error( 'token_invalid_storage', 'El token guardado no se puede descifrar.' );
	}

	$iv         = base64_decode( $parts[1], true );
	$tag        = base64_decode( $parts[2], true );
	$ciphertext = base64_decode( $parts[3], true );
	if ( false === $iv || false === $tag || false === $ciphertext ) {
		return new WP_Error( 'token_invalid_storage', 'El token guardado tiene un formato no válido.' );
	}

	$key   = hash( 'sha256', wp_salt( 'auth' ) . '|' . wp_salt( 'secure_auth' ), true );
	$token = openssl_decrypt( $ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );

	if ( false === $token || '' === $token ) {
		return new WP_Error( 'token_decrypt_failed', 'No se ha podido descifrar el token de Instagram.' );
	}

	return $token;
}

/**
 * Make an authenticated request to the Instagram Graph API.
 */
function emdo_ig_graph_request( $method, $path, array $body = array() ) {
	$token = emdo_ig_get_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$url = trailingslashit( EMDO_IG_GRAPH_BASE ) . ltrim( $path, '/' );
	$args = array(
		'method'      => strtoupper( $method ),
		'timeout'     => 30,
		'redirection' => 2,
		'headers'     => array(
			'Authorization' => 'Bearer ' . $token,
			'User-Agent'    => 'EMDO-Instagram-Publisher/1.0',
		),
	);

	if ( ! empty( $body ) ) {
		$args['body'] = $body;
	}

	$response = wp_remote_request( $url, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	$raw    = wp_remote_retrieve_body( $response );
	$data   = json_decode( $raw, true );

	if ( $status < 200 || $status >= 300 ) {
		$message = 'Instagram ha devuelto un error HTTP ' . $status . '.';
		$code    = 'instagram_http_' . $status;
		if ( is_array( $data ) && ! empty( $data['error']['message'] ) ) {
			$message = (string) $data['error']['message'];
		}
		if ( is_array( $data ) && ! empty( $data['error']['code'] ) ) {
			$code = 'instagram_' . sanitize_key( (string) $data['error']['code'] );
		}
		return new WP_Error( $code, $message, array( 'status' => $status, 'response' => $data ) );
	}

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'instagram_invalid_json', 'Instagram ha devuelto una respuesta no válida.' );
	}

	return $data;
}

/**
 * Verify the stored token and persist account metadata.
 */
function emdo_ig_verify_connection() {
	$data = emdo_ig_graph_request( 'GET', 'me?fields=id,username,account_type,media_count' );
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	if ( empty( $data['id'] ) || empty( $data['username'] ) ) {
		return new WP_Error( 'instagram_account_invalid', 'Instagram no ha devuelto el ID y usuario esperados.' );
	}

	$account = array(
		'id'          => sanitize_text_field( (string) $data['id'] ),
		'username'    => sanitize_text_field( (string) $data['username'] ),
		'account_type'=> isset( $data['account_type'] ) ? sanitize_text_field( (string) $data['account_type'] ) : '',
		'media_count' => isset( $data['media_count'] ) ? (int) $data['media_count'] : null,
		'verified_at' => current_time( 'mysql', true ),
	);

	update_option( EMDO_IG_ACCOUNT_OPTION, $account, false );
	return $account;
}

/**
 * Publish one public HTTPS image to Instagram.
 *
 * @param string $image_url Public image URL reachable by Meta.
 * @param string $caption   Instagram caption.
 * @return array|WP_Error
 */
function emdo_instagram_publish_image( $image_url, $caption ) {
	$image_url = esc_url_raw( trim( (string) $image_url ) );
	$caption   = trim( (string) $caption );

	if ( '' === $image_url || 'https' !== strtolower( (string) wp_parse_url( $image_url, PHP_URL_SCHEME ) ) ) {
		return new WP_Error( 'invalid_image_url', 'La imagen debe tener una URL pública HTTPS válida.' );
	}

	if ( '' === $caption ) {
		return new WP_Error( 'caption_missing', 'La publicación necesita un texto.' );
	}

	if ( function_exists( 'mb_strlen' ) ? mb_strlen( $caption ) > 2200 : strlen( $caption ) > 2200 ) {
		return new WP_Error( 'caption_too_long', 'El texto supera los 2.200 caracteres admitidos por Instagram.' );
	}

	$account = get_option( EMDO_IG_ACCOUNT_OPTION, array() );
	if ( empty( $account['id'] ) ) {
		$account = emdo_ig_verify_connection();
		if ( is_wp_error( $account ) ) {
			return $account;
		}
	}

	$ig_user_id = preg_replace( '/[^0-9]/', '', (string) $account['id'] );
	if ( '' === $ig_user_id ) {
		return new WP_Error( 'instagram_user_missing', 'No se ha podido determinar el ID de la cuenta de Instagram.' );
	}

	$container = emdo_ig_graph_request(
		'POST',
		$ig_user_id . '/media',
		array(
			'image_url' => $image_url,
			'caption'   => $caption,
		)
	);
	if ( is_wp_error( $container ) ) {
		return $container;
	}
	if ( empty( $container['id'] ) ) {
		return new WP_Error( 'instagram_container_missing', 'Instagram no ha devuelto el ID del contenedor.' );
	}

	$container_id = sanitize_text_field( (string) $container['id'] );

	// Wait briefly for Meta to finish ingesting the media before publishing it.
	for ( $attempt = 0; $attempt < 6; $attempt++ ) {
		$status = emdo_ig_graph_request( 'GET', rawurlencode( $container_id ) . '?fields=status_code,status' );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$status_code = isset( $status['status_code'] ) ? strtoupper( (string) $status['status_code'] ) : '';
		if ( 'FINISHED' === $status_code || '' === $status_code ) {
			break;
		}
		if ( in_array( $status_code, array( 'ERROR', 'EXPIRED' ), true ) ) {
			return new WP_Error( 'instagram_container_' . strtolower( $status_code ), isset( $status['status'] ) ? (string) $status['status'] : 'Instagram no ha podido procesar la imagen.' );
		}
		sleep( 2 );
	}

	$published = emdo_ig_graph_request(
		'POST',
		$ig_user_id . '/media_publish',
		array( 'creation_id' => $container_id )
	);
	if ( is_wp_error( $published ) ) {
		return $published;
	}
	if ( empty( $published['id'] ) ) {
		return new WP_Error( 'instagram_media_missing', 'Instagram no ha devuelto el ID de la publicación.' );
	}

	$result = array(
		'media_id'     => sanitize_text_field( (string) $published['id'] ),
		'container_id' => $container_id,
		'image_url'    => $image_url,
		'caption'      => $caption,
		'published_at' => current_time( 'mysql', true ),
		'username'     => isset( $account['username'] ) ? sanitize_text_field( (string) $account['username'] ) : '',
	);

	$log = get_option( EMDO_IG_LOG_OPTION, array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	array_unshift( $log, $result );
	$log = array_slice( $log, 0, 20 );
	update_option( EMDO_IG_LOG_OPTION, $log, false );

	return $result;
}

/**
 * WordPress admin screen.
 */
function emdo_ig_admin_menu() {
	add_management_page(
		'Instagram API',
		'Instagram API',
		'manage_options',
		'emdo-instagram-api',
		'emdo_ig_render_admin_page'
	);
}
add_action( 'admin_menu', 'emdo_ig_admin_menu' );

function emdo_ig_admin_redirect( $notice, $type = 'success' ) {
	$url = add_query_arg(
		array(
			'page'        => 'emdo-instagram-api',
			'emdo_notice' => rawurlencode( $notice ),
			'emdo_type'   => sanitize_key( $type ),
		),
		admin_url( 'tools.php' )
	);
	wp_safe_redirect( $url );
	exit;
}

function emdo_ig_handle_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'No autorizado.' );
	}
	check_admin_referer( 'emdo_ig_save_token' );

	$token = isset( $_POST['access_token'] ) ? trim( (string) wp_unslash( $_POST['access_token'] ) ) : '';
	if ( '' === $token ) {
		emdo_ig_admin_redirect( 'Pega el access token antes de guardar.', 'error' );
	}

	$encrypted = emdo_ig_encrypt_token( $token );
	if ( is_wp_error( $encrypted ) ) {
		emdo_ig_admin_redirect( $encrypted->get_error_message(), 'error' );
	}

	update_option( EMDO_IG_TOKEN_OPTION, $encrypted, false );
	delete_option( EMDO_IG_ACCOUNT_OPTION );

	$verified = emdo_ig_verify_connection();
	if ( is_wp_error( $verified ) ) {
		delete_option( EMDO_IG_TOKEN_OPTION );
		delete_option( EMDO_IG_ACCOUNT_OPTION );
		emdo_ig_admin_redirect( 'El token no se ha guardado: ' . $verified->get_error_message(), 'error' );
	}

	emdo_ig_admin_redirect( 'Conexión correcta con @' . $verified['username'] . '. El token ha quedado cifrado en el servidor.' );
}
add_action( 'admin_post_emdo_ig_save_token', 'emdo_ig_handle_save' );

function emdo_ig_handle_test() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'No autorizado.' );
	}
	check_admin_referer( 'emdo_ig_test_connection' );
	$verified = emdo_ig_verify_connection();
	if ( is_wp_error( $verified ) ) {
		emdo_ig_admin_redirect( 'Error de conexión: ' . $verified->get_error_message(), 'error' );
	}
	emdo_ig_admin_redirect( 'Conexión verificada con @' . $verified['username'] . '.' );
}
add_action( 'admin_post_emdo_ig_test_connection', 'emdo_ig_handle_test' );

function emdo_ig_handle_disconnect() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'No autorizado.' );
	}
	check_admin_referer( 'emdo_ig_disconnect' );
	delete_option( EMDO_IG_TOKEN_OPTION );
	delete_option( EMDO_IG_ACCOUNT_OPTION );
	emdo_ig_admin_redirect( 'La cuenta de Instagram se ha desconectado.' );
}
add_action( 'admin_post_emdo_ig_disconnect', 'emdo_ig_handle_disconnect' );

function emdo_ig_handle_manual_publish() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'No autorizado.' );
	}
	check_admin_referer( 'emdo_ig_manual_publish' );

	$image_url = isset( $_POST['image_url'] ) ? esc_url_raw( (string) wp_unslash( $_POST['image_url'] ) ) : '';
	$caption   = isset( $_POST['caption'] ) ? sanitize_textarea_field( (string) wp_unslash( $_POST['caption'] ) ) : '';
	$result    = emdo_instagram_publish_image( $image_url, $caption );

	if ( is_wp_error( $result ) ) {
		emdo_ig_admin_redirect( 'No se ha publicado: ' . $result->get_error_message(), 'error' );
	}

	emdo_ig_admin_redirect( 'Publicado correctamente. Media ID: ' . $result['media_id'] );
}
add_action( 'admin_post_emdo_ig_manual_publish', 'emdo_ig_handle_manual_publish' );

function emdo_ig_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$account   = get_option( EMDO_IG_ACCOUNT_OPTION, array() );
	$connected = ! is_wp_error( emdo_ig_get_token() ) && ! empty( $account['id'] );
	$log       = get_option( EMDO_IG_LOG_OPTION, array() );
	$notice    = isset( $_GET['emdo_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['emdo_notice'] ) ) : '';
	$type      = isset( $_GET['emdo_type'] ) ? sanitize_key( wp_unslash( $_GET['emdo_type'] ) ) : 'success';
	?>
	<div class="wrap">
		<h1>Instagram API — El Mercado de Origen</h1>
		<p>Integración interna para publicar en Instagram mediante la API oficial de Meta. El token se cifra antes de guardarse y nunca se muestra de nuevo.</p>

		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo 'error' === $type ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<table class="widefat striped" style="max-width:900px;margin:20px 0;">
			<tbody>
				<tr><th style="width:220px;">Instagram App ID</th><td><code><?php echo esc_html( EMDO_IG_APP_ID ); ?></code></td></tr>
				<tr><th>API</th><td><code><?php echo esc_html( EMDO_IG_API_VERSION ); ?></code> · <code>graph.instagram.com</code></td></tr>
				<tr><th>Estado</th><td><?php echo $connected ? '<strong style="color:#008a20">Conectado</strong>' : '<strong style="color:#b32d2e">Sin conectar</strong>'; ?></td></tr>
				<?php if ( $connected ) : ?>
					<tr><th>Cuenta</th><td><strong>@<?php echo esc_html( $account['username'] ); ?></strong></td></tr>
					<tr><th>Instagram User ID</th><td><code><?php echo esc_html( $account['id'] ); ?></code></td></tr>
					<tr><th>Última verificación</th><td><?php echo esc_html( $account['verified_at'] ); ?> UTC</td></tr>
				<?php endif; ?>
			</tbody>
		</table>

		<?php if ( ! $connected ) : ?>
			<h2>1. Conectar Instagram</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:900px;">
				<input type="hidden" name="action" value="emdo_ig_save_token">
				<?php wp_nonce_field( 'emdo_ig_save_token' ); ?>
				<p><label for="emdo-access-token"><strong>Access Token</strong></label></p>
				<input id="emdo-access-token" type="password" name="access_token" value="" autocomplete="off" required style="width:100%;max-width:760px;" placeholder="Pega aquí el token generado en Meta">
				<p class="description">El token no se envía a GitHub. Se cifra y se guarda únicamente en la base de datos de WordPress.</p>
				<?php submit_button( 'Guardar y comprobar conexión' ); ?>
			</form>
		<?php else : ?>
			<div style="display:flex;gap:10px;align-items:center;margin:20px 0;">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="emdo_ig_test_connection">
					<?php wp_nonce_field( 'emdo_ig_test_connection' ); ?>
					<?php submit_button( 'Probar conexión', 'secondary', 'submit', false ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('¿Seguro que quieres eliminar el token guardado?');">
					<input type="hidden" name="action" value="emdo_ig_disconnect">
					<?php wp_nonce_field( 'emdo_ig_disconnect' ); ?>
					<?php submit_button( 'Desconectar', 'delete', 'submit', false ); ?>
				</form>
			</div>

			<hr>
			<h2>2. Publicación de prueba</h2>
			<p>Esta prueba publica de verdad en <strong>@<?php echo esc_html( $account['username'] ); ?></strong>.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:900px;">
				<input type="hidden" name="action" value="emdo_ig_manual_publish">
				<?php wp_nonce_field( 'emdo_ig_manual_publish' ); ?>
				<p><label><strong>URL pública HTTPS de la imagen</strong><br><input type="url" name="image_url" required style="width:100%;max-width:760px;" placeholder="https://www.elmercadodeorigen.com/wp-content/uploads/..."></label></p>
				<p><label><strong>Texto</strong><br><textarea name="caption" required rows="7" maxlength="2200" style="width:100%;max-width:760px;"></textarea></label></p>
				<?php submit_button( 'Publicar ahora en Instagram', 'primary' ); ?>
			</form>
		<?php endif; ?>

		<?php if ( is_array( $log ) && ! empty( $log ) ) : ?>
			<hr>
			<h2>Últimas publicaciones vía API</h2>
			<table class="widefat striped" style="max-width:1100px;">
				<thead><tr><th>Fecha UTC</th><th>Media ID</th><th>Imagen</th><th>Texto</th></tr></thead>
				<tbody>
				<?php foreach ( array_slice( $log, 0, 10 ) as $item ) : ?>
					<tr>
						<td><?php echo esc_html( isset( $item['published_at'] ) ? $item['published_at'] : '' ); ?></td>
						<td><code><?php echo esc_html( isset( $item['media_id'] ) ? $item['media_id'] : '' ); ?></code></td>
						<td><a href="<?php echo esc_url( isset( $item['image_url'] ) ? $item['image_url'] : '' ); ?>" target="_blank" rel="noopener">Ver imagen</a></td>
						<td><?php echo esc_html( wp_trim_words( isset( $item['caption'] ) ? $item['caption'] : '', 18, '…' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}
