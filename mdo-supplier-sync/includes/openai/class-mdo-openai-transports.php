<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

interface MDO_OpenAI_Transport_Interface_20260909 {
    public function key(): string;
    public function is_configured(): bool;
    public function test_connection();
    public function upload( string $local_path );
}

function mdo_openai_secret_key_20260909(): string {
    $material = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . '|' . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '' ) . '|' . home_url( '/' );
    return hash( 'sha256', $material, true );
}

function mdo_openai_encrypt_secret_20260909( string $plain ): string {
    if ( '' === $plain || ! function_exists( 'openssl_encrypt' ) ) { return ''; }
    $iv = random_bytes( 12 );
    $tag = '';
    $encrypted = openssl_encrypt( $plain, 'aes-256-gcm', mdo_openai_secret_key_20260909(), OPENSSL_RAW_DATA, $iv, $tag, 'mdo-openai-commerce' );
    if ( false === $encrypted ) { return ''; }
    return base64_encode( wp_json_encode( array(
        'v'=>1,
        'iv'=>base64_encode( $iv ),
        'tag'=>base64_encode( $tag ),
        'data'=>base64_encode( $encrypted ),
    ) ) );
}

function mdo_openai_decrypt_secret_20260909( string $payload ): string {
    if ( '' === $payload || ! function_exists( 'openssl_decrypt' ) ) { return ''; }
    $json = base64_decode( $payload, true );
    $data = is_string( $json ) ? json_decode( $json, true ) : null;
    if ( ! is_array( $data ) || empty( $data['iv'] ) || empty( $data['tag'] ) || empty( $data['data'] ) ) { return ''; }
    $iv = base64_decode( (string) $data['iv'], true );
    $tag = base64_decode( (string) $data['tag'], true );
    $encrypted = base64_decode( (string) $data['data'], true );
    if ( ! is_string( $iv ) || ! is_string( $tag ) || ! is_string( $encrypted ) ) { return ''; }
    $plain = openssl_decrypt( $encrypted, 'aes-256-gcm', mdo_openai_secret_key_20260909(), OPENSSL_RAW_DATA, $iv, $tag, 'mdo-openai-commerce' );
    return is_string( $plain ) ? $plain : '';
}

function mdo_openai_remote_path_20260909( array $settings, string $local_path ): string {
    $dir = trim( str_replace( '\\', '/', (string) ( $settings['sftp_remote_directory'] ?? '' ) ) );
    $dir = '/' . trim( $dir, '/' );
    if ( '/' === $dir ) { $dir = ''; }
    $filename = sanitize_file_name( (string) ( $settings['sftp_remote_filename'] ?? '' ) );
    if ( '' === $filename ) { $filename = basename( $local_path ); }
    return $dir . '/' . $filename;
}

function mdo_openai_delivery_preflight_20260910( array $settings ) {
    if ( '1' !== (string) ( $settings['direct_feed_access_confirmed'] ?? '0' ) ) {
        return new WP_Error( 'openai_direct_feed_not_confirmed', 'Entrega bloqueada: OpenAI todavía no ha confirmado acceso a feed directo para esta cuenta.' );
    }
    if ( '1' !== (string) ( $settings['market_confirmed'] ?? '0' ) ) {
        return new WP_Error( 'openai_market_not_confirmed', 'Entrega bloqueada: OpenAI todavía no ha confirmado España/EUR para esta integración.' );
    }
    $format = mdo_openai_format_meta_20260909( (string) ( $settings['format'] ?? 'native_jsonl_gz' ) );
    if ( 'google_compatible' === $format['provider'] && '1' !== (string) ( $settings['google_compatible_confirmed'] ?? '0' ) ) {
        return new WP_Error( 'openai_google_compatible_not_confirmed', 'Entrega bloqueada: el feed Google-compatible no ha sido confirmado/registrado por OpenAI.' );
    }
    return true;
}

final class MDO_OpenAI_Sftp_Transport_20260909 implements MDO_OpenAI_Transport_Interface_20260909 {
    private array $settings;
    public function __construct( ?array $settings = null ) { $this->settings = $settings ?: mdo_openai_settings_20260909(); }
    public function key(): string { return 'sftp'; }
    public function is_configured(): bool {
        return '1' === (string) ( $this->settings['sftp_enabled'] ?? '0' )
            && '' !== trim( (string) ( $this->settings['sftp_host'] ?? '' ) )
            && '' !== trim( (string) ( $this->settings['sftp_username'] ?? '' ) );
    }
    private function credentials(): array {
        return array(
            'host'=>trim( (string) ( $this->settings['sftp_host'] ?? '' ) ),
            'port'=>max( 1, min( 65535, (int) ( $this->settings['sftp_port'] ?? 22 ) ) ),
            'username'=>trim( (string) ( $this->settings['sftp_username'] ?? '' ) ),
            'method'=>(string) ( $this->settings['sftp_auth_method'] ?? 'private_key' ),
            'password'=>mdo_openai_decrypt_secret_20260909( (string) ( $this->settings['sftp_password_enc'] ?? '' ) ),
            'private_key'=>mdo_openai_decrypt_secret_20260909( (string) ( $this->settings['sftp_private_key_enc'] ?? '' ) ),
            'public_key'=>mdo_openai_decrypt_secret_20260909( (string) ( $this->settings['sftp_public_key_enc'] ?? '' ) ),
            'passphrase'=>mdo_openai_decrypt_secret_20260909( (string) ( $this->settings['sftp_passphrase_enc'] ?? '' ) ),
        );
    }
    private function phpseclib_connect() {
        if ( ! class_exists( '\\phpseclib3\\Net\\SFTP' ) ) { return new WP_Error( 'phpseclib_missing', 'phpseclib3 no está disponible.' ); }
        $c = $this->credentials();
        if ( '' === $c['host'] || '' === $c['username'] ) { return new WP_Error( 'sftp_config', 'Faltan host o usuario SFTP.' ); }
        try {
            $sftp = new \phpseclib3\Net\SFTP( $c['host'], $c['port'], 15 );
            if ( 'password' === $c['method'] ) {
                if ( '' === $c['password'] ) { return new WP_Error( 'sftp_password', 'Falta la contraseña SFTP.' ); }
                $ok = $sftp->login( $c['username'], $c['password'] );
            } else {
                if ( ! class_exists( '\\phpseclib3\\Crypt\\PublicKeyLoader' ) || '' === $c['private_key'] ) { return new WP_Error( 'sftp_key', 'Falta la clave privada SFTP o PublicKeyLoader.' ); }
                $key = \phpseclib3\Crypt\PublicKeyLoader::loadPrivateKey( $c['private_key'], '' !== $c['passphrase'] ? $c['passphrase'] : null );
                $ok = $sftp->login( $c['username'], $key );
            }
            return $ok ? $sftp : new WP_Error( 'sftp_auth', 'El servidor SFTP rechazó la autenticación.' );
        } catch ( Throwable $e ) {
            return new WP_Error( 'sftp_exception', mdo_openai_clean_text_20260909( $e->getMessage(), 300 ) );
        }
    }
    private function ssh2_connect_auth() {
        if ( ! function_exists( 'ssh2_connect' ) ) { return new WP_Error( 'ssh2_missing', 'No están disponibles phpseclib3 ni la extensión SSH2.' ); }
        $c = $this->credentials();
        $connection = @ssh2_connect( $c['host'], $c['port'] );
        if ( ! is_resource( $connection ) && ! is_object( $connection ) ) { return new WP_Error( 'sftp_connect', 'No se pudo conectar con el host SFTP.' ); }
        $ok = false;
        $tmp_private = '';
        $tmp_public = '';
        try {
            if ( 'password' === $c['method'] ) {
                $ok = '' !== $c['password'] && @ssh2_auth_password( $connection, $c['username'], $c['password'] );
            } elseif ( function_exists( 'ssh2_auth_pubkey_file' ) && '' !== $c['private_key'] && '' !== $c['public_key'] ) {
                $storage = mdo_openai_upload_dir_20260909();
                $tmp_private = tempnam( $storage['dir'], 'mdo-key-' );
                $tmp_public = tempnam( $storage['dir'], 'mdo-pub-' );
                if ( is_string( $tmp_private ) && is_string( $tmp_public ) ) {
                    file_put_contents( $tmp_private, $c['private_key'] );
                    file_put_contents( $tmp_public, $c['public_key'] );
                    @chmod( $tmp_private, 0600 );
                    @chmod( $tmp_public, 0600 );
                    $ok = @ssh2_auth_pubkey_file( $connection, $c['username'], $tmp_public, $tmp_private, $c['passphrase'] );
                }
            }
        } finally {
            if ( is_string( $tmp_private ) && '' !== $tmp_private ) { @unlink( $tmp_private ); }
            if ( is_string( $tmp_public ) && '' !== $tmp_public ) { @unlink( $tmp_public ); }
        }
        return $ok ? $connection : new WP_Error( 'sftp_auth', 'El servidor SFTP rechazó la autenticación.' );
    }
    public function test_connection() {
        if ( ! $this->is_configured() ) { return new WP_Error( 'sftp_not_configured', 'SFTP todavía no está configurado.' ); }
        if ( class_exists( '\\phpseclib3\\Net\\SFTP' ) ) {
            $sftp = $this->phpseclib_connect();
            if ( is_wp_error( $sftp ) ) { return $sftp; }
            return array( 'ok'=>true, 'message'=>'Conexión SFTP correcta mediante phpseclib3.' );
        }
        $connection = $this->ssh2_connect_auth();
        if ( is_wp_error( $connection ) ) { return $connection; }
        $sftp = @ssh2_sftp( $connection );
        return $sftp ? array( 'ok'=>true, 'message'=>'Conexión SFTP correcta mediante SSH2.' ) : new WP_Error( 'sftp_subsystem', 'No se pudo iniciar el subsistema SFTP.' );
    }
    public function upload( string $local_path ) {
        $gate = mdo_openai_delivery_preflight_20260910( $this->settings );
        if ( is_wp_error( $gate ) ) { return $gate; }
        if ( ! $this->is_configured() ) { return new WP_Error( 'sftp_not_configured', 'SFTP todavía no está configurado.' ); }
        if ( ! is_readable( $local_path ) ) { return new WP_Error( 'feed_missing', 'El snapshot local no existe o no es legible.' ); }
        $remote = mdo_openai_remote_path_20260909( $this->settings, $local_path );
        $remote_tmp = $remote . '.tmp';
        if ( class_exists( '\\phpseclib3\\Net\\SFTP' ) ) {
            $sftp = $this->phpseclib_connect();
            if ( is_wp_error( $sftp ) ) { return $sftp; }
            try {
                $ok = $sftp->put( $remote_tmp, $local_path, \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE );
                if ( ! $ok ) { return new WP_Error( 'sftp_put', 'Falló la subida SFTP.' ); }
                if ( ! $sftp->rename( $remote_tmp, $remote ) ) { $sftp->delete( $remote_tmp ); return new WP_Error( 'sftp_rename', 'El archivo subió pero no pudo reemplazar atómicamente el snapshot remoto.' ); }
                return array( 'ok'=>true, 'remote'=>$remote, 'bytes'=>(int) filesize( $local_path ) );
            } catch ( Throwable $e ) {
                return new WP_Error( 'sftp_exception', mdo_openai_clean_text_20260909( $e->getMessage(), 300 ) );
            }
        }
        $connection = $this->ssh2_connect_auth();
        if ( is_wp_error( $connection ) ) { return $connection; }
        $sftp = @ssh2_sftp( $connection );
        if ( ! $sftp ) { return new WP_Error( 'sftp_subsystem', 'No se pudo iniciar el subsistema SFTP.' ); }
        $stream = @fopen( 'ssh2.sftp://' . intval( $sftp ) . $remote_tmp, 'wb' );
        $local = @fopen( $local_path, 'rb' );
        if ( false === $stream || false === $local ) { if ( is_resource( $stream ) ) { fclose( $stream ); } if ( is_resource( $local ) ) { fclose( $local ); } return new WP_Error( 'sftp_stream', 'No se pudo abrir el stream SFTP.' ); }
        $bytes = stream_copy_to_stream( $local, $stream );
        fclose( $local );
        fclose( $stream );
        if ( false === $bytes ) { return new WP_Error( 'sftp_copy', 'Falló la transferencia SFTP.' ); }
        if ( function_exists( 'ssh2_sftp_rename' ) && ! @ssh2_sftp_rename( $sftp, $remote_tmp, $remote ) ) { @ssh2_sftp_unlink( $sftp, $remote_tmp ); return new WP_Error( 'sftp_rename', 'No se pudo reemplazar el snapshot remoto.' ); }
        return array( 'ok'=>true, 'remote'=>$remote, 'bytes'=>(int) $bytes );
    }
}

final class MDO_OpenAI_Api_Transport_20260909 implements MDO_OpenAI_Transport_Interface_20260909 {
    private array $settings;
    public function __construct( ?array $settings = null ) { $this->settings = $settings ?: mdo_openai_settings_20260909(); }
    public function key(): string { return 'api'; }
    public function is_configured(): bool {
        return '1' === (string) ( $this->settings['api_enabled'] ?? '0' )
            && '' !== trim( (string) ( $this->settings['api_base_endpoint'] ?? '' ) )
            && '' !== trim( (string) ( $this->settings['api_feed_id'] ?? '' ) )
            && '' !== trim( (string) ( $this->settings['api_version'] ?? '' ) )
            && '' !== mdo_openai_decrypt_secret_20260909( (string) ( $this->settings['api_key_enc'] ?? '' ) );
    }
    public function headers( bool $idempotent = false ): array {
        $headers = array(
            'Authorization'=>'Bearer ' . mdo_openai_decrypt_secret_20260909( (string) $this->settings['api_key_enc'] ),
            'Accept-Language'=>(string) ( $this->settings['api_accept_language'] ?? 'es-ES' ),
            'User-Agent'=>'ElMercadoDeOrigen-OpenAICommerce/' . MDO_OPENAI_COMMERCE_VERSION,
            'Request-Id'=>wp_generate_uuid4(),
            'Timestamp'=>gmdate( 'c' ),
            'API-Version'=>(string) ( $this->settings['api_version'] ?? '' ),
            'Content-Type'=>'application/json',
        );
        if ( $idempotent ) { $headers['Idempotency-Key'] = wp_generate_uuid4(); }
        return $headers;
    }
    public function test_connection() {
        if ( ! $this->is_configured() ) { return new WP_Error( 'api_not_configured', 'Commerce API todavía no está configurada.' ); }
        $custom = apply_filters( 'mdo_openai_api_test_connection', null, $this->settings, $this );
        if ( null !== $custom ) { return $custom; }
        return new WP_Error( 'api_contract_not_enabled', 'API preparada pero inactiva: no se prueba ninguna ruta hasta que OpenAI entregue y confirme el contrato/endpoint exacto para esta cuenta.' );
    }
    public function upload( string $local_path ) {
        $gate = mdo_openai_delivery_preflight_20260910( $this->settings );
        if ( is_wp_error( $gate ) ) { return $gate; }
        if ( ! $this->is_configured() ) { return new WP_Error( 'api_not_configured', 'Commerce API todavía no está configurada.' ); }
        if ( ! is_readable( $local_path ) ) { return new WP_Error( 'feed_missing', 'El snapshot local no existe o no es legible.' ); }
        $custom = apply_filters( 'mdo_openai_api_upload', null, $local_path, $this->settings, $this );
        if ( null !== $custom ) { return $custom; }
        return new WP_Error( 'api_mapper_not_enabled', 'La API incremental está preparada pero no activada. El snapshot no se enviará a una ruta inventada; debe implementarse el contrato exacto que OpenAI asigne a esta cuenta.' );
    }
}

function mdo_openai_transport_20260909( ?array $settings = null ): ?MDO_OpenAI_Transport_Interface_20260909 {
    $settings = $settings ?: mdo_openai_settings_20260909();
    if ( 'sftp' === (string) ( $settings['delivery'] ?? 'none' ) ) { return new MDO_OpenAI_Sftp_Transport_20260909( $settings ); }
    if ( 'api' === (string) ( $settings['delivery'] ?? 'none' ) ) { return new MDO_OpenAI_Api_Transport_20260909( $settings ); }
    return null;
}

function mdo_openai_record_transport_result_20260909( $result, string $kind ): void {
    $now = time();
    if ( is_wp_error( $result ) ) {
        update_option( 'mdo_openai_last_error', array( 'time'=>$now, 'kind'=>$kind, 'message'=>mdo_openai_clean_text_20260909( $result->get_error_message(), 500 ) ), false );
        mdo_openai_log_20260909( $kind, 'Error: ' . $result->get_error_message() );
        return;
    }
    delete_option( 'mdo_openai_last_error' );
    if ( 'upload' === $kind ) { update_option( 'mdo_openai_last_upload', array( 'time'=>$now, 'result'=>is_array( $result ) ? $result : array() ), false ); }
    if ( 'connection_test' === $kind ) { update_option( 'mdo_openai_last_connection_test', array( 'time'=>$now, 'result'=>is_array( $result ) ? $result : array() ), false ); }
    mdo_openai_log_20260909( $kind, 'Operación correcta.', is_array( $result ) ? $result : array() );
}

function mdo_openai_do_upload_20260909( string $path ) {
    $transport = mdo_openai_transport_20260909();
    if ( ! $transport ) { return new WP_Error( 'transport_none', 'No hay transporte OpenAI configurado.' ); }
    $result = $transport->upload( $path );
    mdo_openai_record_transport_result_20260909( $result, 'upload' );
    return $result;
}

add_action( 'mdo_openai_request_upload', static function( string $path ): void {
    mdo_openai_do_upload_20260909( $path );
}, 10, 1 );
