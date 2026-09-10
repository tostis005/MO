<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_openai_admin_can_manage_20260910(): bool {
    return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
}

function mdo_openai_admin_parent_20260910(): string {
    return function_exists( 'mdo_gmf_admin_parent_v1' ) ? mdo_gmf_admin_parent_v1() : 'woocommerce';
}

function mdo_openai_admin_bool_20260910( array $source, string $key ): string {
    return isset( $source[ $key ] ) ? '1' : '0';
}

function mdo_openai_admin_sanitize_settings_20260910( array $raw, array $old ): array {
    $defaults = mdo_openai_default_settings_20260909();
    $next = $old;

    $enums = array(
        'merchant_status' => array( 'not_applied', 'applied', 'onboarding', 'approved' ),
        'format' => array( 'native_jsonl_gz', 'native_csv_gz', 'native_tsv_gz', 'google_csv_gz', 'google_tsv_gz' ),
        'schedule' => array( 'manual', 'hourly', 'six_hours', 'twelve_hours', 'daily' ),
        'delivery' => array( 'none', 'sftp', 'api' ),
        'seller_model' => array( 'marketplace_vendor', 'mercado', 'custom' ),
        'shipping_representation' => array( 'disabled', 'shipping_price', 'tuple' ),
        'sftp_auth_method' => array( 'private_key', 'password' ),
    );
    foreach ( $enums as $key => $allowed ) {
        $value = sanitize_key( (string) ( $raw[ $key ] ?? $defaults[ $key ] ?? '' ) );
        $next[ $key ] = in_array( $value, $allowed, true ) ? $value : (string) ( $defaults[ $key ] ?? '' );
    }

    foreach ( array(
        'auto_upload', 'marketplace_enabled', 'direct_feed_access_confirmed', 'market_confirmed',
        'google_compatible_confirmed', 'shipping_capability_confirmed', 'returns_enabled',
        'returns_accepts', 'sftp_enabled', 'api_enabled'
    ) as $key ) {
        $next[ $key ] = mdo_openai_admin_bool_20260910( $raw, $key );
    }

    $next['shipping_country'] = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $raw['shipping_country'] ?? 'ES' ) ), 0, 2 ) );
    if ( ! preg_match( '/^[A-Z]{2}$/', $next['shipping_country'] ) ) { $next['shipping_country'] = 'ES'; }
    $next['shipping_region'] = mdo_openai_clean_text_20260909( $raw['shipping_region'] ?? '', 80 );
    $next['shipping_service_class'] = mdo_openai_clean_text_20260909( $raw['shipping_service_class'] ?? 'standard', 80 );
    $next['returns_deadline_days'] = ( isset( $raw['returns_deadline_days'] ) && '' !== trim( (string) $raw['returns_deadline_days'] ) ) ? (string) max( 0, min( 365, (int) $raw['returns_deadline_days'] ) ) : '';
    $next['returns_policy_url'] = esc_url_raw( (string) ( $raw['returns_policy_url'] ?? '' ) );

    foreach ( array(
        'excluded_product_ids', 'excluded_category_ids', 'excluded_vendor_ids',
        'returns_excluded_product_ids', 'returns_excluded_category_ids', 'returns_excluded_vendor_ids'
    ) as $key ) {
        $ids = mdo_openai_parse_id_list_20260909( $raw[ $key ] ?? '' );
        $next[ $key ] = implode( ',', $ids );
    }

    $next['sftp_host'] = trim( sanitize_text_field( (string) ( $raw['sftp_host'] ?? '' ) ) );
    $next['sftp_port'] = (string) max( 1, min( 65535, (int) ( $raw['sftp_port'] ?? 22 ) ) );
    $next['sftp_username'] = trim( sanitize_text_field( (string) ( $raw['sftp_username'] ?? '' ) ) );
    $next['sftp_remote_directory'] = trim( sanitize_text_field( (string) ( $raw['sftp_remote_directory'] ?? '' ) ) );
    $next['sftp_remote_filename'] = sanitize_file_name( (string) ( $raw['sftp_remote_filename'] ?? '' ) );

    $next['api_base_endpoint'] = esc_url_raw( (string) ( $raw['api_base_endpoint'] ?? '' ) );
    $next['api_feed_id'] = mdo_openai_clean_text_20260909( $raw['api_feed_id'] ?? '', 200 );
    $next['api_version'] = mdo_openai_clean_text_20260909( $raw['api_version'] ?? '', 80 );
    $next['api_accept_language'] = mdo_openai_clean_text_20260909( $raw['api_accept_language'] ?? 'es-ES', 35 );

    $secret_map = array(
        'sftp_password' => 'sftp_password_enc',
        'sftp_private_key' => 'sftp_private_key_enc',
        'sftp_public_key' => 'sftp_public_key_enc',
        'sftp_passphrase' => 'sftp_passphrase_enc',
        'api_key' => 'api_key_enc',
    );
    foreach ( $secret_map as $plain_key => $stored_key ) {
        $plain = isset( $raw[ $plain_key ] ) ? trim( (string) wp_unslash( $raw[ $plain_key ] ) ) : '';
        if ( '' !== $plain ) {
            $encrypted = mdo_openai_encrypt_secret_20260909( $plain );
            if ( '' !== $encrypted ) { $next[ $stored_key ] = $encrypted; }
        } elseif ( ! empty( $raw[ 'clear_' . $plain_key ] ) ) {
            $next[ $stored_key ] = '';
        }
    }

    return wp_parse_args( $next, $defaults );
}

function mdo_openai_admin_notice_20260910( string $type, string $message ): array {
    return array( 'type' => $type, 'message' => $message );
}

function mdo_openai_admin_process_20260910(): array {
    $state = array( 'notices' => array(), 'preview' => null );
    if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || empty( $_POST['mdo_openai_action'] ) ) { return $state; }
    if ( ! mdo_openai_admin_can_manage_20260910() ) { wp_die( 'Sin permisos.' ); }
    check_admin_referer( 'mdo_openai_admin_action_20260910' );
    $action = sanitize_key( (string) wp_unslash( $_POST['mdo_openai_action'] ) );

    if ( 'save' === $action ) {
        $old = mdo_openai_settings_20260909();
        $raw = isset( $_POST['mdo_openai'] ) && is_array( $_POST['mdo_openai'] ) ? wp_unslash( $_POST['mdo_openai'] ) : array();
        $next = mdo_openai_admin_sanitize_settings_20260910( $raw, $old );
        update_option( MDO_OPENAI_COMMERCE_OPTION, $next, false );
        delete_transient( 'mdo_openai_exportable_count' );
        mdo_openai_log_20260909( 'settings', 'Configuración OpenAI actualizada.' );
        $state['notices'][] = mdo_openai_admin_notice_20260910( 'success', 'Configuración guardada.' );
        return $state;
    }

    if ( 'validate' === $action ) {
        $report = mdo_openai_validate_catalog_20260909();
        $state['notices'][] = mdo_openai_admin_notice_20260910( 0 === (int) $report['errors'] ? 'success' : 'warning', sprintf( 'Validación terminada: %d ofertas válidas, %d errores y %d avisos.', (int) $report['exported'], (int) $report['errors'], (int) $report['warnings'] ) );
        return $state;
    }

    if ( 'preview' === $action ) {
        $state['preview'] = mdo_openai_provider_20260909()->preview( 20 );
        $state['notices'][] = mdo_openai_admin_notice_20260910( 'success', 'Vista previa calculada con hasta 20 registros válidos.' );
        return $state;
    }

    if ( 'generate' === $action ) {
        $result = mdo_openai_provider_20260909()->generate();
        if ( empty( $result['ok'] ) ) {
            $state['notices'][] = mdo_openai_admin_notice_20260910( 'error', (string) ( $result['error'] ?? 'No se pudo generar el snapshot.' ) );
        } else {
            $report = (array) ( $result['report'] ?? array() );
            $state['notices'][] = mdo_openai_admin_notice_20260910( 'success', sprintf( 'Snapshot generado: %d ofertas, %d errores, %d avisos.', (int) ( $report['exported'] ?? 0 ), (int) ( $report['errors'] ?? 0 ), (int) ( $report['warnings'] ?? 0 ) ) );
        }
        return $state;
    }

    if ( 'test_connection' === $action ) {
        $transport = mdo_openai_transport_20260909();
        $result = $transport ? $transport->test_connection() : new WP_Error( 'transport_none', 'Selecciona y configura un transporte antes de probar la conexión.' );
        mdo_openai_record_transport_result_20260909( $result, 'connection_test' );
        $state['notices'][] = is_wp_error( $result ) ? mdo_openai_admin_notice_20260910( 'error', $result->get_error_message() ) : mdo_openai_admin_notice_20260910( 'success', (string) ( $result['message'] ?? 'Conexión correcta.' ) );
        return $state;
    }

    if ( 'upload' === $action ) {
        $generation = mdo_openai_provider_20260909()->generate();
        if ( empty( $generation['ok'] ) ) {
            $state['notices'][] = mdo_openai_admin_notice_20260910( 'error', (string) ( $generation['error'] ?? 'No se pudo generar el snapshot antes de subirlo.' ) );
            return $state;
        }
        $result = mdo_openai_do_upload_20260909( (string) $generation['path'] );
        $state['notices'][] = is_wp_error( $result ) ? mdo_openai_admin_notice_20260910( 'error', $result->get_error_message() ) : mdo_openai_admin_notice_20260910( 'success', 'Snapshot enviado correctamente por el transporte configurado.' );
        return $state;
    }

    return $state;
}

function mdo_openai_admin_status_label_20260910( string $status ): string {
    $labels = array( 'not_applied' => 'No solicitado', 'applied' => 'Solicitud enviada', 'onboarding' => 'Onboarding', 'approved' => 'Aprobado' );
    return $labels[ $status ] ?? $status;
}

function mdo_openai_admin_feed_url_20260910( array $settings ): string {
    $filename = mdo_openai_format_meta_20260909( (string) $settings['format'] )['filename'];
    return home_url( '/emdo-feed/openai/' . rawurlencode( $filename ) );
}

function mdo_openai_admin_secret_row_20260910( string $label, string $name, bool $has_value, bool $textarea = false ): void {
    echo '<tr><th scope="row"><label for="mdo-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
    if ( $textarea ) {
        echo '<textarea id="mdo-' . esc_attr( $name ) . '" name="mdo_openai[' . esc_attr( $name ) . ']" rows="5" class="large-text code" autocomplete="new-password" placeholder="' . esc_attr( $has_value ? 'Guardado; dejar vacío para conservar' : 'No configurado' ) . '"></textarea>';
    } else {
        echo '<input id="mdo-' . esc_attr( $name ) . '" type="password" name="mdo_openai[' . esc_attr( $name ) . ']" value="" class="regular-text" autocomplete="new-password" placeholder="' . esc_attr( $has_value ? 'Guardado; dejar vacío para conservar' : 'No configurado' ) . '" />';
    }
    if ( $has_value ) { echo '<label style="margin-left:10px"><input type="checkbox" name="mdo_openai[clear_' . esc_attr( $name ) . ']" value="1" /> borrar valor guardado</label>'; }
    echo '</td></tr>';
}

function mdo_openai_admin_page_20260910(): void {
    if ( ! mdo_openai_admin_can_manage_20260910() ) { wp_die( 'Sin permisos.' ); }
    $state = mdo_openai_admin_process_20260910();
    $settings = mdo_openai_settings_20260909();
    $report = get_option( MDO_OPENAI_COMMERCE_REPORT_OPTION, array() );
    $logs = get_option( MDO_OPENAI_COMMERCE_LOG_OPTION, array() );
    $feed_url = mdo_openai_admin_feed_url_20260910( $settings );
    $file = mdo_openai_feed_path_20260909( (string) $settings['format'] );
    $has_file = is_readable( $file );
    ?>
    <div class="wrap">
        <h1>Feeds · OpenAI / ChatGPT</h1>
        <p><strong>Objetivo:</strong> Product Discovery en ChatGPT con checkout final en El Mercado de Origen. Este módulo no activa Instant Checkout.</p>
        <p><strong>Importante:</strong> la URL descargable de abajo es para QA y operación interna; OpenAI no la consume automáticamente. El alta de feed directo es un proceso de onboarding y la entrega se realiza únicamente por el mecanismo que OpenAI asigne a la cuenta.</p>

        <?php foreach ( (array) $state['notices'] as $notice ) : ?>
            <div class="notice notice-<?php echo esc_attr( 'error' === $notice['type'] ? 'error' : ( 'warning' === $notice['type'] ? 'warning' : 'success' ) ); ?> inline"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
        <?php endforeach; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;max-width:1200px;margin:16px 0">
            <div class="card"><h2>Onboarding</h2><p><strong><?php echo esc_html( mdo_openai_admin_status_label_20260910( (string) $settings['merchant_status'] ) ); ?></strong></p><p>Acceso feed directo: <?php echo '1' === $settings['direct_feed_access_confirmed'] ? '<strong style="color:#008a20">confirmado</strong>' : '<strong style="color:#b26200">pendiente</strong>'; ?></p></div>
            <div class="card"><h2>Mercado ES/EUR</h2><p><?php echo '1' === $settings['market_confirmed'] ? '<strong style="color:#008a20">confirmado por OpenAI</strong>' : '<strong style="color:#b26200">pendiente de confirmación</strong>'; ?></p><p>El generador puede validarse antes del onboarding; el envío se mantiene bloqueado.</p></div>
            <div class="card"><h2>Catálogo</h2><p><strong><?php echo esc_html( number_format_i18n( mdo_openai_exportable_count_20260909() ) ); ?></strong> ofertas exportables ahora.</p><p>Variables = una fila por variación comprable.</p></div>
            <div class="card"><h2>Último snapshot</h2><p><?php echo $has_file ? '<strong style="color:#008a20">Disponible</strong>' : '<strong style="color:#b26200">No generado</strong>'; ?></p><p><?php echo ! empty( $report['generated_at'] ) ? esc_html( wp_date( 'd/m/Y H:i', (int) $report['generated_at'] ) ) : 'Sin ejecución previa'; ?></p></div>
        </div>

        <h2>Datos para la solicitud a OpenAI</h2>
        <table class="widefat striped" style="max-width:900px"><tbody>
            <tr><th>Empresa / comercio</th><td>El Mercado de Origen</td></tr>
            <tr><th>Sitio</th><td><code>https://www.elmercadodeorigen.com/</code></td></tr>
            <tr><th>Categoría</th><td>Food &amp; Beverage</td></tr>
            <tr><th>Interés</th><td>Product Feed / Product Discovery</td></tr>
            <tr><th>Modelo comercial real</th><td>Marketplace: el productor/vendedor WCFM identificado en cada ficha es <code>seller_name</code>. El Mercado de Origen solo se envía como <code>marketplace_seller</code> si OpenAI habilita esa capacidad para la cuenta.</td></tr>
            <tr><th>Formato preferido</th><td><code>JSONL.gz</code>, snapshot completo, nombre estable <code>mercado-de-origen-products.jsonl.gz</code>.</td></tr>
        </tbody></table>

        <form method="post" style="max-width:1200px;margin-top:20px">
            <?php wp_nonce_field( 'mdo_openai_admin_action_20260910' ); ?>
            <input type="hidden" name="mdo_openai_action" value="save" />

            <h2>Estado y formato</h2>
            <table class="form-table"><tbody>
                <tr><th><label for="mdo-merchant-status">Estado merchant</label></th><td><select id="mdo-merchant-status" name="mdo_openai[merchant_status]">
                    <?php foreach ( array( 'not_applied'=>'No solicitado','applied'=>'Solicitud enviada','onboarding'=>'Onboarding','approved'=>'Aprobado' ) as $value=>$label ) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['merchant_status'],$value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
                </select></td></tr>
                <tr><th>Confirmaciones de OpenAI</th><td>
                    <label><input type="checkbox" name="mdo_openai[direct_feed_access_confirmed]" value="1" <?php checked($settings['direct_feed_access_confirmed'],'1'); ?> /> OpenAI ha confirmado acceso a feed directo</label><br>
                    <label><input type="checkbox" name="mdo_openai[market_confirmed]" value="1" <?php checked($settings['market_confirmed'],'1'); ?> /> OpenAI ha confirmado España/EUR para esta integración</label><br>
                    <label><input type="checkbox" name="mdo_openai[google_compatible_confirmed]" value="1" <?php checked($settings['google_compatible_confirmed'],'1'); ?> /> OpenAI ha confirmado que esta cuenta puede usar un feed Google-compatible registrado</label><br>
                    <label><input type="checkbox" name="mdo_openai[marketplace_enabled]" value="1" <?php checked($settings['marketplace_enabled'],'1'); ?> /> OpenAI ha habilitado <code>marketplace_seller</code> para este feed</label><br>
                    <label><input type="checkbox" name="mdo_openai[shipping_capability_confirmed]" value="1" <?php checked($settings['shipping_capability_confirmed'],'1'); ?> /> OpenAI ha confirmado la representación de shipping para este feed</label>
                    <p class="description">No marques estas casillas por inferencia: deben corresponder a la configuración real confirmada durante onboarding.</p>
                </td></tr>
                <tr><th><label for="mdo-format">Formato</label></th><td><select id="mdo-format" name="mdo_openai[format]">
                    <?php foreach ( array('native_jsonl_gz'=>'OpenAI nativo · JSONL.gz (recomendado)','native_csv_gz'=>'OpenAI nativo · CSV.gz','native_tsv_gz'=>'OpenAI nativo · TSV.gz','google_csv_gz'=>'Google-compatible · CSV.gz','google_tsv_gz'=>'Google-compatible · TSV.gz') as $value=>$label ) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['format'],$value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
                </select><p class="description">La variante Google-compatible solo debe usarse si OpenAI la ha confirmado y registrado para la cuenta.</p></td></tr>
                <tr><th><label for="mdo-schedule">Frecuencia</label></th><td><select id="mdo-schedule" name="mdo_openai[schedule]">
                    <?php foreach(array('manual'=>'Manual','hourly'=>'Cada hora','six_hours'=>'Cada 6 horas','twelve_hours'=>'Cada 12 horas','daily'=>'Diaria') as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['schedule'],$value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
                </select> <label style="margin-left:14px"><input type="checkbox" name="mdo_openai[auto_upload]" value="1" <?php checked($settings['auto_upload'],'1'); ?> /> enviar automáticamente después de generar, solo si la entrega está autorizada</label></td></tr>
            </tbody></table>

            <h2>Vendedor, marketplace y exclusiones</h2>
            <table class="form-table"><tbody>
                <tr><th><label for="mdo-seller-model">Modelo de vendedor</label></th><td><select id="mdo-seller-model" name="mdo_openai[seller_model]">
                    <option value="marketplace_vendor" <?php selected($settings['seller_model'],'marketplace_vendor'); ?>>Productor/vendor WCFM = seller_name (modelo real, recomendado)</option>
                    <option value="mercado" <?php selected($settings['seller_model'],'mercado'); ?>>El Mercado de Origen = seller_name</option>
                    <option value="custom" <?php selected($settings['seller_model'],'custom'); ?>>Personalizado por filtro PHP</option>
                </select><p class="description">El modo personalizado requiere el filtro <code>mdo_openai_seller_name</code>; nunca se deduce el vendedor del título del producto.</p></td></tr>
                <tr><th>Excluir IDs</th><td>
                    <p><label>Productos/variaciones <input class="regular-text" name="mdo_openai[excluded_product_ids]" value="<?php echo esc_attr($settings['excluded_product_ids']); ?>" placeholder="123,456" /></label></p>
                    <p><label>Categorías <input class="regular-text" name="mdo_openai[excluded_category_ids]" value="<?php echo esc_attr($settings['excluded_category_ids']); ?>" /></label></p>
                    <p><label>Vendedores WCFM <input class="regular-text" name="mdo_openai[excluded_vendor_ids]" value="<?php echo esc_attr($settings['excluded_vendor_ids']); ?>" /></label></p>
                </td></tr>
            </tbody></table>

            <h2>Shipping y devoluciones opcionales</h2>
            <table class="form-table"><tbody>
                <tr><th><label for="mdo-shipping">Shipping</label></th><td><select id="mdo-shipping" name="mdo_openai[shipping_representation]">
                    <option value="disabled" <?php selected($settings['shipping_representation'],'disabled'); ?>>Desactivado (seguro por defecto)</option>
                    <option value="shipping_price" <?php selected($settings['shipping_representation'],'shipping_price'); ?>>shipping_price</option>
                    <option value="tuple" <?php selected($settings['shipping_representation'],'tuple'); ?>>shipping tuple</option>
                </select> País <input size="3" maxlength="2" name="mdo_openai[shipping_country]" value="<?php echo esc_attr($settings['shipping_country']); ?>" /> Región <input name="mdo_openai[shipping_region]" value="<?php echo esc_attr($settings['shipping_region']); ?>" /> Servicio <input name="mdo_openai[shipping_service_class]" value="<?php echo esc_attr($settings['shipping_service_class']); ?>" /></td></tr>
                <tr><th>Devoluciones</th><td>
                    <label><input type="checkbox" name="mdo_openai[returns_enabled]" value="1" <?php checked($settings['returns_enabled'],'1'); ?> /> enviar política de devoluciones</label><br>
                    <label><input type="checkbox" name="mdo_openai[returns_accepts]" value="1" <?php checked($settings['returns_accepts'],'1'); ?> /> acepta devoluciones para los productos a los que aplique</label><br>
                    <label>Días <input type="number" min="0" max="365" name="mdo_openai[returns_deadline_days]" value="<?php echo esc_attr($settings['returns_deadline_days']); ?>" style="width:90px" /></label>
                    <label> Política <input type="url" class="regular-text" name="mdo_openai[returns_policy_url]" value="<?php echo esc_attr($settings['returns_policy_url']); ?>" /></label>
                    <p><label>Excluir productos <input class="regular-text" name="mdo_openai[returns_excluded_product_ids]" value="<?php echo esc_attr($settings['returns_excluded_product_ids']); ?>" /></label> <label>categorías <input class="regular-text" name="mdo_openai[returns_excluded_category_ids]" value="<?php echo esc_attr($settings['returns_excluded_category_ids']); ?>" /></label> <label>vendors <input class="regular-text" name="mdo_openai[returns_excluded_vendor_ids]" value="<?php echo esc_attr($settings['returns_excluded_vendor_ids']); ?>" /></label></p>
                    <p class="description">Desactivado por defecto. En alimentación perecedera no se aplica una política global por suposición; usa exclusiones o el filtro <code>mdo_openai_returns</code>.</p>
                </td></tr>
            </tbody></table>

            <h2>Entrega</h2>
            <table class="form-table"><tbody>
                <tr><th><label for="mdo-delivery">Transporte</label></th><td><select id="mdo-delivery" name="mdo_openai[delivery]"><option value="none" <?php selected($settings['delivery'],'none'); ?>>Ninguno</option><option value="sftp" <?php selected($settings['delivery'],'sftp'); ?>>SFTP asignado por OpenAI</option><option value="api" <?php selected($settings['delivery'],'api'); ?>>Commerce API (abstracción; inactiva hasta contrato real)</option></select></td></tr>
                <tr><th>SFTP</th><td><label><input type="checkbox" name="mdo_openai[sftp_enabled]" value="1" <?php checked($settings['sftp_enabled'],'1'); ?> /> habilitar configuración SFTP</label></td></tr>
                <tr><th><label>Host</label></th><td><input class="regular-text" name="mdo_openai[sftp_host]" value="<?php echo esc_attr($settings['sftp_host']); ?>" /> : <input type="number" min="1" max="65535" name="mdo_openai[sftp_port]" value="<?php echo esc_attr($settings['sftp_port']); ?>" style="width:90px" /></td></tr>
                <tr><th><label>Usuario</label></th><td><input class="regular-text" name="mdo_openai[sftp_username]" value="<?php echo esc_attr($settings['sftp_username']); ?>" /> <select name="mdo_openai[sftp_auth_method]"><option value="private_key" <?php selected($settings['sftp_auth_method'],'private_key'); ?>>Clave privada</option><option value="password" <?php selected($settings['sftp_auth_method'],'password'); ?>>Contraseña</option></select></td></tr>
                <tr><th>Ruta remota</th><td><input class="regular-text" name="mdo_openai[sftp_remote_directory]" value="<?php echo esc_attr($settings['sftp_remote_directory']); ?>" placeholder="La indicada por OpenAI" /> <input class="regular-text" name="mdo_openai[sftp_remote_filename]" value="<?php echo esc_attr($settings['sftp_remote_filename']); ?>" placeholder="Vacío = nombre estable local" /></td></tr>
                <?php mdo_openai_admin_secret_row_20260910('Contraseña SFTP','sftp_password',!empty($settings['sftp_password_enc'])); ?>
                <?php mdo_openai_admin_secret_row_20260910('Clave privada SFTP','sftp_private_key',!empty($settings['sftp_private_key_enc']),true); ?>
                <?php mdo_openai_admin_secret_row_20260910('Clave pública SFTP','sftp_public_key',!empty($settings['sftp_public_key_enc']),true); ?>
                <?php mdo_openai_admin_secret_row_20260910('Passphrase SFTP','sftp_passphrase',!empty($settings['sftp_passphrase_enc'])); ?>
                <tr><th>Commerce API</th><td><label><input type="checkbox" name="mdo_openai[api_enabled]" value="1" <?php checked($settings['api_enabled'],'1'); ?> /> habilitar configuración API</label><p class="description">No activa ningún endpoint por sí sola. Los datos siguientes solo se rellenan con valores entregados por OpenAI.</p></td></tr>
                <tr><th>Base endpoint</th><td><input type="url" class="large-text" name="mdo_openai[api_base_endpoint]" value="<?php echo esc_attr($settings['api_base_endpoint']); ?>" /></td></tr>
                <tr><th>Feed ID / versión</th><td><input name="mdo_openai[api_feed_id]" value="<?php echo esc_attr($settings['api_feed_id']); ?>" placeholder="Solo si OpenAI lo proporciona" /> <input name="mdo_openai[api_version]" value="<?php echo esc_attr($settings['api_version']); ?>" placeholder="Versión confirmada" /> <input name="mdo_openai[api_accept_language]" value="<?php echo esc_attr($settings['api_accept_language']); ?>" style="width:90px" /></td></tr>
                <?php mdo_openai_admin_secret_row_20260910('API key','api_key',!empty($settings['api_key_enc'])); ?>
            </tbody></table>
            <?php submit_button( 'Guardar configuración' ); ?>
        </form>

        <h2>Operaciones y QA</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
            <?php foreach(array('validate'=>'Validar catálogo','preview'=>'Vista previa (20)','generate'=>'Generar snapshot','test_connection'=>'Probar conexión','upload'=>'Generar y enviar ahora') as $action=>$label): ?>
                <form method="post" style="display:inline"><?php wp_nonce_field('mdo_openai_admin_action_20260910'); ?><input type="hidden" name="mdo_openai_action" value="<?php echo esc_attr($action); ?>" /><button class="button <?php echo 'generate'===$action?'button-primary':''; ?>"><?php echo esc_html($label); ?></button></form>
            <?php endforeach; ?>
        </div>
        <p><strong>Archivo estable:</strong> <code><?php echo esc_html( basename($file) ); ?></code> · <strong>URL QA:</strong> <input readonly class="large-text code" style="max-width:700px" value="<?php echo esc_attr($feed_url); ?>" /> <?php if($has_file): ?><a class="button" href="<?php echo esc_url($feed_url); ?>">Descargar</a><?php endif; ?></p>

        <?php if ( is_array( $state['preview'] ) ) : $rows=(array)($state['preview']['rows']??array()); ?>
            <h3>Vista previa</h3><div style="overflow:auto;max-width:100%"><table class="widefat striped"><thead><tr><th>item_id</th><th>title</th><th>brand</th><th>seller</th><th>availability</th><th>price</th><th>group_id</th><th>variant_dict</th><th>URL</th></tr></thead><tbody>
            <?php foreach($rows as $row): ?><tr><td><code><?php echo esc_html((string)($row['item_id']??$row['id']??'')); ?></code></td><td><?php echo esc_html((string)($row['title']??'')); ?></td><td><?php echo esc_html((string)($row['brand']??'')); ?></td><td><?php echo esc_html((string)($row['seller_name']??'')); ?></td><td><?php echo esc_html((string)($row['availability']??'')); ?></td><td><?php echo esc_html((string)($row['price']??'')); ?></td><td><code><?php echo esc_html((string)($row['group_id']??$row['item_group_id']??'')); ?></code></td><td><code><?php echo esc_html(wp_json_encode($row['variant_dict']??array(),JSON_UNESCAPED_UNICODE)); ?></code></td><td><a href="<?php echo esc_url((string)($row['url']??$row['link']??'')); ?>" target="_blank" rel="noopener">abrir</a></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>

        <h3>Último informe</h3>
        <?php if(is_array($report)&&!empty($report)): ?>
            <p>Exportadas: <strong><?php echo esc_html((string)($report['exported']??0)); ?></strong> · excluidas: <?php echo esc_html((string)($report['excluded']??0)); ?> · retiradas con <code>is_eligible_search=false</code>: <?php echo esc_html((string)($report['retired']??0)); ?> · errores: <strong><?php echo esc_html((string)($report['errors']??0)); ?></strong> · avisos: <?php echo esc_html((string)($report['warnings']??0)); ?>.</p>
            <?php if(!empty($report['issues'])): ?><table class="widefat striped" style="max-width:1100px"><thead><tr><th>Severidad</th><th>item_id</th><th>Detalle</th></tr></thead><tbody><?php foreach(array_slice((array)$report['issues'],0,100) as $issue): ?><tr><td><?php echo esc_html((string)($issue['severity']??'')); ?></td><td><code><?php echo esc_html((string)($issue['item_id']??'')); ?></code></td><td><?php echo esc_html((string)($issue['message']??'')); ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
        <?php else: ?><p>Sin informe todavía.</p><?php endif; ?>

        <h3>Logs recientes</h3>
        <table class="widefat striped" style="max-width:1100px"><thead><tr><th>Fecha</th><th>Evento</th><th>Mensaje</th></tr></thead><tbody><?php foreach(array_slice(is_array($logs)?$logs:array(),0,20) as $log): ?><tr><td><?php echo esc_html(wp_date('d/m/Y H:i',(int)($log['time']??0))); ?></td><td><code><?php echo esc_html((string)($log['event']??'')); ?></code></td><td><?php echo esc_html((string)($log['message']??'')); ?></td></tr><?php endforeach; ?></tbody></table>
    </div>
    <?php
}

add_action( 'admin_menu', static function(): void {
    add_submenu_page(
        mdo_openai_admin_parent_20260910(),
        'OpenAI / ChatGPT Product Feed',
        'Feeds · OpenAI / ChatGPT',
        'manage_woocommerce',
        'mdo-openai-chatgpt-feed',
        'mdo_openai_admin_page_20260910'
    );
}, 100 );
