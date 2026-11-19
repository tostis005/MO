<?php
/**
 * One-time, idempotent FluentCRM draft creation for Black Friday 2026.
 *
 * This file is run under WP-CLI only in production.
 * It NEVER sends or schedules a bulk campaign.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "WP_CLI_REQUIRED\n"); exit(2);
}
$host = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($host, ['www.elmercadodeorigen.com','elmercadodeorigen.com'], true)) {
    fwrite(STDERR, "WRONG_SITE\n"); exit(3);
}
if (!class_exists('FluentCrm\\App\\Models\\Campaign')) {
    fwrite(STDERR, "FLUENTCRM_NOT_LOADED\n"); exit(3);
}
$mode = getenv('EMDO_FLUENTCRM_DRAFT_MODE') ?: 'preflight';
if (!in_array($mode, ['preflight','apply','verify'], true)) {
    fwrite(STDERR, "INVALID_MODE\n"); exit(3);
}
$htmlPath = getenv('EMDO_FLUENTCRM_HTML_PATH');
if (!$htmlPath || !is_readable($htmlPath)) {
    fwrite(STDERR, "HTML_NOT_READABLE\n"); exit(3);
}
$html = file_get_contents($htmlPath);
$slugs = [
    'pasaporte-salvaje','reserva-de-caza','safari-africano',
    'vuelta-al-mundo','parrilla-sin-fronteras-xl','universo-wagyu','leyendas-del-vacuno'
];
if (!is_string($html) || strlen($html) < 15000 ||
    !str_contains($html, 'BFMONTJAM') ||
    !str_contains($html, '2,5 % de descuento adicional') ||
    !str_contains($html, '##crm.unsubscribe_url##') ||
    !str_contains($html, '##crm.manage_subscription_url##')) {
    fwrite(STDERR, "HTML_VALIDATION_FAILED\n"); exit(4);
}
foreach ($slugs as $slug) {
    if (!str_contains($html, 'pack-musee-' . $slug . '-black-friday/')) {
        fwrite(STDERR, "MISSING_PACK_" . $slug . "\n"); exit(4);
    }
}
$title = 'Black Friday 2026 · Continuamos · Montjam y packs Latitudes';
$subject = '🖤 ¡El Black Friday continúa! Montjam + 7 packs especiales';
$preheader = '15 % en Montjam, 2,5 % de descuento adicional en jamones seleccionados, medio morcón y packs de Latitudes.';
$model = 'FluentCrm\\App\\Models\\Campaign';
$campaign = $model::where('title', $title)->first();
if ($campaign && (string)$campaign->status !== 'draft') {
    fwrite(STDERR, "CAMPAIGN_NOT_DRAFT_ID=" . $campaign->id . "\n"); exit(5);
}
if ($mode === 'preflight') {
    echo "FLUENTCRM_PREFLIGHT_OK existing_id=" . ($campaign ? $campaign->id : 'none') . " html_bytes=" . strlen($html) . "\n";
    exit(0);
}
function emdo_bf_fcrm_rest(string $method, string $route, array $params) {
    $req = new WP_REST_Request($method, $route);
    $req->set_body_params($params);
    $res = rest_do_request($req);
    if (is_wp_error($res) || (int)$res->get_status() >= 400) {
        fwrite(STDERR, "FLUENTCRM_API_ERROR status=" . (is_wp_error($res) ? 'wp_error' : $res->get_status()) .
            " response=" . wp_json_encode(is_wp_error($res) ? $res->get_error_message() : $res->get_data(), JSON_UNESCAPED_UNICODE) . "\n");
        exit(6);
    }
    return $res->get_data();
}
if ($mode === 'apply') {
    $users = get_users(['role'=>'administrator','orderby'=>'ID','order'=>'ASC','number'=>30]);
    $authorized = null;
    foreach ($users as $user) {
        if (user_can($user, 'fcrm_manage_emails') || user_can($user, 'manage_options')) {
            $authorized = $user;
            break;
        }
    }
    if (!$authorized) {
        fwrite(STDERR, "NO_AUTHORIZED_ADMIN\n"); exit(7);
    }
    wp_set_current_user((int)$authorized->ID);
    if (!$campaign) {
        $new = emdo_bf_fcrm_rest('POST', '/fluent-crm/v2/campaigns', [
            'title'=>$title, 'design_template'=>'raw_html'
        ]);
        $id = (int)($new['id'] ?? $new['campaign']['id'] ?? 0);
        $campaign = $id ? $model::find($id) : $model::where('title', $title)->first();
        if (!$campaign || (string)$campaign->status !== 'draft') {
            fwrite(STDERR, "CREATE_DID_NOT_PERSIST_DRAFT\n"); exit(8);
        }
    }
    $id = (int)$campaign->id;
    emdo_bf_fcrm_rest('PUT', '/fluent-crm/v2/campaigns/' . $id, [
        'title'=>$title,
        'design_template'=>'raw_html',
        'email_subject'=>$subject,
        'email_pre_header'=>$preheader,
        'email_body'=>$html
    ]);
}
$campaign = $model::where('title', $title)->first();
if (!$campaign) {
    fwrite(STDERR, "CAMPAIGN_NOT_FOUND\n"); exit(9);
}
$body = (string)$campaign->email_body;
if ((string)$campaign->status !== 'draft' ||
    (string)$campaign->email_subject !== $subject ||
    !str_contains($body, 'BFMONTJAM') ||
    !str_contains($body, 'Leyendas del Vacuno') ||
    !str_contains($body, '##crm.unsubscribe_url##') ||
    strlen($body) < 14500) {
    fwrite(STDERR, "PERSISTENCE_VALIDATION_FAILED id=" . $campaign->id .
      " status=" . $campaign->status . " bytes=" . strlen($body) .
      " subject=" . (string)$campaign->email_subject . "\n");
    exit(10);
}
foreach ($slugs as $slug) {
    if (!str_contains($body, 'pack-musee-' . $slug . '-black-friday/')) {
        fwrite(STDERR, "SAVED_PACK_MISSING_" . $slug . "\n"); exit(10);
    }
}
echo "FLUENTCRM_BLACK_FRIDAY_DRAFT_VERIFIED id=" . (int)$campaign->id .
     " status=" . (string)$campaign->status . " body_bytes=" . strlen($body) .
     " subject=" . $subject . "\n";
