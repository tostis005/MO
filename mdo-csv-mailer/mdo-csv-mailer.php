<?php
/**
 * Plugin Name: EMDO · CSV Correos Personalizados
 * Description: Importa campañas CSV con asunto y mensaje diferentes por destinatario. Revisión, prueba, envío pausado, exclusiones y bajas.
 * Version: 0.1.0
 * Author: El Mercado de Origen
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) { exit; }

final class EMDO_CSV_Mailer {
    const HOOK = 'emdo_csv_mailer_worker';
    const MENU = 'emdo-csv-mailer';
    const MAX_FILE = 2097152;
    const MAX_ROWS = 2000;

    private static function tables(): array {
        global $wpdb;
        return [
            'campaigns' => $wpdb->prefix . 'emdo_csv_campaigns',
            'messages'  => $wpdb->prefix . 'emdo_csv_messages',
            'optouts'   => $wpdb->prefix . 'emdo_csv_optouts',
        ];
    }

    public static function activate(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $t = self::tables();
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$t['campaigns']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            from_email varchar(191) NOT NULL,
            from_name varchar(191) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset;");
        dbDelta("CREATE TABLE {$t['messages']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            email varchar(191) NOT NULL,
            subject text NOT NULL,
            message longtext NOT NULL,
            permission varchar(32) NOT NULL DEFAULT '',
            source_info text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'blocked',
            sent_at datetime NULL,
            error_message text NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY campaign_recipient (campaign_id,email),
            KEY status_lookup (campaign_id,status)
        ) $charset;");
        dbDelta("CREATE TABLE {$t['optouts']} (
            email varchar(191) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (email)
        ) $charset;");
    }

    public static function boot(): void {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_emdo_csv_import', [__CLASS__, 'import']);
        add_action('admin_post_emdo_csv_action', [__CLASS__, 'action']);
        add_action(self::HOOK, [__CLASS__, 'worker'], 10, 1);
        add_action('init', [__CLASS__, 'unsubscribe']);
    }

    public static function menu(): void {
        add_management_page('Correos CSV personalizados', 'Correos CSV', 'manage_options', self::MENU, [__CLASS__, 'page']);
    }

    private static function admin_url(array $args = []): string {
        return add_query_arg($args, admin_url('tools.php?page=' . self::MENU));
    }

    private static function redirect(string $message, int $id = 0): void {
        wp_safe_redirect(self::admin_url(['notice' => $message, 'campaign' => $id]));
        exit;
    }

    private static function check(string $nonce): void {
        if (!current_user_can('manage_options')) { wp_die('No autorizado.', '', ['response' => 403]); }
        check_admin_referer($nonce);
    }

    private static function csv_column(array $row, array $columns, array $aliases): string {
        foreach ($aliases as $alias) {
            if (isset($columns[$alias])) {
                return trim((string) ($row[$columns[$alias]] ?? ''));
            }
        }
        return '';
    }

    private static function normalize_header(string $header): string {
        $header = strtolower(remove_accents(trim($header)));
        return preg_replace('/[^a-z0-9]+/', '_', $header);
    }

    private static function is_opted_out(string $email): bool {
        global $wpdb;
        $t = self::tables();
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT email FROM {$t['optouts']} WHERE email = %s LIMIT 1", $email));
    }

    public static function import(): void {
        self::check('emdo_csv_import');
        global $wpdb;
        if (empty($_FILES['csv']['tmp_name']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            self::redirect('Selecciona un archivo CSV.');
        }
        if ((int) $_FILES['csv']['size'] > self::MAX_FILE || strtolower(pathinfo((string) $_FILES['csv']['name'], PATHINFO_EXTENSION)) !== 'csv') {
            self::redirect('Debe ser un CSV de 2 MB como máximo.');
        }
        $from = sanitize_email(wp_unslash($_POST['from_email'] ?? ''));
        $from_name = sanitize_text_field(wp_unslash($_POST['from_name'] ?? 'El Mercado de Origen'));
        if (!is_email($from)) { self::redirect('El remitente no es válido.'); }

        $handle = fopen($_FILES['csv']['tmp_name'], 'rb');
        if (!$handle) { self::redirect('No se pudo leer el CSV.'); }
        $first_line = (string) fgets($handle);
        rewind($handle);
        $delimiter = substr_count($first_line, ';') > substr_count($first_line, ',') ? ';' : ',';
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) { fclose($handle); self::redirect('CSV vacío.'); }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $columns = [];
        foreach ($headers as $position => $header) { $columns[self::normalize_header((string) $header)] = $position; }
        if (!array_intersect(['email', 'correo', 'correo_electronico'], array_keys($columns))
            || !array_intersect(['asunto', 'subject'], array_keys($columns))
            || !array_intersect(['mensaje', 'texto', 'body'], array_keys($columns))) {
            fclose($handle);
            self::redirect('Faltan columnas obligatorias: email, asunto y mensaje.');
        }

        $t = self::tables();
        $campaign_name = sanitize_text_field(pathinfo((string) $_FILES['csv']['name'], PATHINFO_FILENAME));
        $wpdb->insert($t['campaigns'], [
            'name' => $campaign_name ?: 'Campaña CSV',
            'from_email' => $from,
            'from_name' => $from_name,
            'status' => 'draft',
            'created_at' => current_time('mysql'),
        ]);
        $campaign_id = (int) $wpdb->insert_id;
        if (!$campaign_id) { fclose($handle); self::redirect('No se pudo crear la campaña.'); }

        $count = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $count < self::MAX_ROWS) {
            if (!$row || count($row) === 1 && trim((string) $row[0]) === '') { continue; }
            $count++;
            $email = strtolower(sanitize_email(self::csv_column($row, $columns, ['email', 'correo', 'correo_electronico'])));
            $subject = sanitize_text_field(self::csv_column($row, $columns, ['asunto', 'subject']));
            $message = sanitize_textarea_field(self::csv_column($row, $columns, ['mensaje', 'texto', 'body']));
            $permission = sanitize_key(self::csv_column($row, $columns, ['permiso', 'base_legal', 'autorizacion']));
            $source = sanitize_text_field(self::csv_column($row, $columns, ['origen', 'fuente', 'justificacion']));
            if (!is_email($email) || !$subject || !$message) { continue; }
            // El CSV solo habilita contactos con base documentada. El resto queda visible pero bloqueado.
            $allowed = in_array($permission, ['consentimiento', 'cliente_previo'], true) && $source !== '' && !self::is_opted_out($email);
            $wpdb->insert($t['messages'], [
                'campaign_id' => $campaign_id, 'email' => $email,
                'subject' => $subject, 'message' => $message,
                'permission' => $permission, 'source_info' => $source,
                'status' => $allowed ? 'pending' : 'blocked',
                'created_at' => current_time('mysql'),
            ]);
        }
        fclose($handle);
        self::redirect('CSV importado. Revisa las vistas previas y haz un envío de prueba.', $campaign_id);
    }

    private static function campaign(int $id) {
        global $wpdb;
        $t = self::tables();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id=%d", $id));
    }

    private static function schedule(int $id, int $delay = 120): void {
        if (false === wp_next_scheduled(self::HOOK, [$id])) {
            wp_schedule_single_event(time() + $delay, self::HOOK, [$id]);
        }
    }

    public static function action(): void {
        self::check('emdo_csv_action');
        global $wpdb;
        $id = absint($_POST['campaign'] ?? 0);
        $campaign = self::campaign($id);
        if (!$campaign) { self::redirect('Campaña inexistente.'); }
        $t = self::tables();
        $task = sanitize_key($_POST['task'] ?? '');
        if ($task === 'pause') {
            $wpdb->update($t['campaigns'], ['status' => 'paused'], ['id' => $id]);
            wp_clear_scheduled_hook(self::HOOK, [$id]);
            self::redirect('Campaña pausada.', $id);
        }
        if ($task === 'test') {
            $sample = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['messages']} WHERE campaign_id=%d AND status='pending' ORDER BY id ASC LIMIT 1", $id));
            if (!$sample) { self::redirect('No hay destinatarios habilitados para probar.', $id); }
            $admin = get_option('admin_email');
            $note = "PRUEBA. Destinatario final: {$sample->email}\n\n";
            $ok = self::mail($admin, '[PRUEBA] ' . $sample->subject, $note . $sample->message, $campaign, $sample->email);
            self::redirect($ok ? 'Prueba entregada al servidor de correo (comprueba tu bandeja de entrada).' : 'Ha fallado el envío de prueba; revisa SMTP.', $id);
        }
        if ($task === 'start') {
            if (empty($_POST['confirm_legal'])) { self::redirect('Debes confirmar que has revisado los permisos y las vistas previas.', $id); }
            $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['messages']} WHERE campaign_id=%d AND status='pending'", $id));
            if (!$count) { self::redirect('No hay correos pendientes y autorizados.', $id); }
            $wpdb->update($t['campaigns'], ['status' => 'running'], ['id' => $id]);
            self::schedule($id, 30);
            self::redirect('Campaña iniciada. El primer lote se ejecutará con WP-Cron.', $id);
        }
        self::redirect('Acción no reconocida.', $id);
    }

    private static function unsubscribe_link(string $email): string {
        $email = strtolower($email);
        $signature = hash_hmac('sha256', $email, wp_salt('auth'));
        return add_query_arg(['emdo_csv_unsub' => $email, 'sig' => $signature], home_url('/'));
    }

    public static function unsubscribe(): void {
        if (!isset($_GET['emdo_csv_unsub'], $_GET['sig'])) { return; }
        $email = strtolower(sanitize_email(wp_unslash($_GET['emdo_csv_unsub'])));
        $signature = sanitize_text_field(wp_unslash($_GET['sig']));
        if (!is_email($email) || !hash_equals(hash_hmac('sha256', $email, wp_salt('auth')), $signature)) {
            status_header(400);
            wp_die('Enlace de baja no válido.', 'Baja de comunicaciones', ['response' => 400]);
        }
        global $wpdb;
        $t = self::tables();
        $wpdb->replace($t['optouts'], ['email' => $email, 'created_at' => current_time('mysql')]);
        $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET status='blocked' WHERE email=%s AND status='pending'", $email));
        wp_die('Tu dirección ha quedado excluida de futuros envíos de este sistema.', 'Baja confirmada', ['response' => 200]);
    }

    private static function mail(string $to, string $subject, string $body, $campaign, string $recipient): bool {
        $link = self::unsubscribe_link($recipient);
        $footer = "\n\n---\nEl Mercado de Origen\nPara dejar de recibir comunicaciones: " . $link;
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $campaign->from_name . ' <' . $campaign->from_email . '>',
            'Reply-To: ' . $campaign->from_email,
            'List-Unsubscribe: <' . $link . '>',
        ];
        try {
            return (bool) wp_mail($to, $subject, $body . $footer, $headers);
        } catch (Throwable $error) {
            error_log('[EMDO CSV Mailer] wp_mail exception: ' . $error->getMessage());
            return false;
        }
    }

    public static function worker(int $id): void {
        global $wpdb;
        $t = self::tables();
        $campaign = self::campaign($id);
        if (!$campaign || $campaign->status !== 'running') { return; }
        // Se procesan solo 2 mensajes por lote, espaciados al menos 120 s.
        for ($i = 0; $i < 2; $i++) {
            $campaign = self::campaign($id);
            if (!$campaign || $campaign->status !== 'running') { return; }
            $message = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['messages']} WHERE campaign_id=%d AND status='pending' ORDER BY id LIMIT 1", $id));
            if (!$message) { break; }
            if (self::is_opted_out($message->email)) {
                $wpdb->update($t['messages'], ['status' => 'blocked'], ['id' => $message->id, 'status' => 'pending']);
                continue;
            }
            // Claim atómico para no repetir envíos entre procesos concurrentes.
            $claimed = $wpdb->update($t['messages'], ['status' => 'processing'], ['id' => $message->id, 'status' => 'pending']);
            if ($claimed !== 1) { continue; }
            $ok = self::mail($message->email, $message->subject, $message->message, $campaign, $message->email);
            $wpdb->update($t['messages'], [
                'status' => $ok ? 'sent' : 'failed',
                'sent_at' => $ok ? current_time('mysql') : null,
                'error_message' => $ok ? '' : 'wp_mail devolvió false; revisa la configuración SMTP.',
            ], ['id' => $message->id, 'status' => 'processing']);
        }
        $pending = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['messages']} WHERE campaign_id=%d AND status='pending'", $id));
        $campaign = self::campaign($id);
        if (!$campaign || $campaign->status !== 'running') { return; }
        if ($pending) { self::schedule($id); }
        else { $wpdb->update($t['campaigns'], ['status' => 'finished'], ['id' => $id]); }
    }

    public static function page(): void {
        if (!current_user_can('manage_options')) { return; }
        global $wpdb;
        $t = self::tables();
        $id = absint($_GET['campaign'] ?? 0);
        $campaign = $id ? self::campaign($id) : null;
        $campaigns = $wpdb->get_results("SELECT * FROM {$t['campaigns']} ORDER BY id DESC LIMIT 20");
        echo '<div class="wrap"><h1>Correos CSV personalizados</h1>';
        if (isset($_GET['notice'])) {
            echo '<div class="notice notice-info"><p>' . esc_html(sanitize_text_field(wp_unslash($_GET['notice']))) . '</p></div>';
        }
        echo '<p>Cada fila tiene su destinatario, asunto y mensaje propios. No se envía nada al importar. Los contactos sin permiso y origen documentados quedan bloqueados.</p>';
        echo '<h2>1. Importar archivo CSV</h2>';
        echo '<p>Columnas: <code>email;asunto;mensaje;permiso;origen</code>. Permiso: <code>consentimiento</code> o <code>cliente_previo</code>. Si falta una base documentada, la fila se guarda pero no se envía. Admite CSV separado por punto y coma o coma.</p>';
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" enctype="multipart/form-data">';
        wp_nonce_field('emdo_csv_import');
        echo '<input type="hidden" name="action" value="emdo_csv_import">';
        echo '<p><label>Remitente <input type="email" name="from_email" value="hola@elmercadodeorigen.com" required size="36"></label> ';
        echo '<label>Nombre <input type="text" name="from_name" value="El Mercado de Origen" required size="28"></label></p>';
        echo '<p><input type="file" name="csv" accept=".csv,text/csv" required></p>';
        submit_button('Importar sin enviar', 'secondary');
        echo '</form>';
        echo '<h2>2. Campañas</h2>';
        if (!$campaigns) { echo '<p>Aún no hay campañas.</p>'; }
        foreach ($campaigns as $c) {
            echo '<p><a href="' . esc_url(self::admin_url(['campaign' => $c->id])) . '"><strong>' . esc_html($c->name) . '</strong></a> — ' . esc_html($c->status) . ' — ' . esc_html($c->created_at) . '</p>';
        }
        if ($campaign) {
            $stats = $wpdb->get_results($wpdb->prepare("SELECT status, COUNT(*) AS total FROM {$t['messages']} WHERE campaign_id=%d GROUP BY status", $id));
            echo '<hr><h2>3. Revisar: ' . esc_html($campaign->name) . '</h2><p>Estado: <strong>' . esc_html($campaign->status) . '</strong>. Remitente: ' . esc_html($campaign->from_email) . '</p>';
            foreach ($stats as $stat) { echo '<span style="display:inline-block;margin-right:18px">' . esc_html($stat->status) . ': <strong>' . absint($stat->total) . '</strong></span>'; }
            echo '<p>«Enviado» significa aceptado por el servidor, no necesariamente entregado al buzón.</p>';
            $preview = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['messages']} WHERE campaign_id=%d ORDER BY id LIMIT 20", $id));
            echo '<table class="widefat striped"><thead><tr><th>Destinatario</th><th>Asunto</th><th>Estado / permiso</th><th>Mensaje</th></tr></thead><tbody>';
            foreach ($preview as $item) {
                echo '<tr><td>' . esc_html($item->email) . '</td><td>' . esc_html($item->subject) . '</td><td>' . esc_html($item->status . ' · ' . $item->permission) . '<br>' . esc_html($item->source_info) . '</td>';
                echo '<td><details><summary>Vista previa</summary><div style="white-space:pre-wrap;max-width:550px">' . esc_html($item->message) . '</div></details></td></tr>';
            }
            echo '</tbody></table>';
            if (count($preview) === 20) { echo '<p>Se muestran las primeras 20 filas. Todas las filas válidas se procesarán, no solo las visibles.</p>'; }
            echo '<h3>4. Probar, iniciar o pausar</h3>';
            foreach (['test' => 'Enviar prueba solo al administrador', 'start' => 'Iniciar campaña', 'pause' => 'Pausar campaña'] as $task => $caption) {
                echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" style="margin:12px 0">';
                wp_nonce_field('emdo_csv_action');
                echo '<input type="hidden" name="action" value="emdo_csv_action"><input type="hidden" name="campaign" value="' . absint($id) . '"><input type="hidden" name="task" value="' . esc_attr($task) . '">';
                if ($task === 'start') {
                    echo '<label><input type="checkbox" name="confirm_legal" value="1" required> He revisado los destinatarios, sus permisos, el remitente y las vistas previas.</label><br>';
                }
                submit_button($caption, $task === 'start' ? 'primary' : 'secondary', 'submit', false);
                echo '</form>';
            }
        }
        echo '</div>';
    }
}

register_activation_hook(__FILE__, ['EMDO_CSV_Mailer', 'activate']);
EMDO_CSV_Mailer::boot();
