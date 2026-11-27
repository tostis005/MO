<?php
/**
 * EMDO Black Friday 2026: six 10% producer coupons.
 * Creates/updates one FluentCRM draft and sends at most ONE native test.
 * NO recipient-list campaign send and NO scheduling.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_WPCLI_REQUIRED\n"); exit(2);
}
$host = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($host, ['www.elmercadodeorigen.com', 'elmercadodeorigen.com'], true)) {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_WRONG_SITE\n"); exit(3);
}
$mode = getenv('EMDO_BFLAST_MODE') ?: 'preflight';
if (!in_array($mode, ['preflight', 'apply', 'verify', 'test'], true)) {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_INVALID_MODE\n"); exit(4);
}
$class = '\FluentCrm\App\Models\Campaign';
$controller_class = '\FluentCrm\App\Http\Controllers\CampaignController';
if (!class_exists($class) || !class_exists($controller_class) || !class_exists('WC_Coupon')) {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_REQUIRED_PLUGINS_UNAVAILABLE\n"); exit(5);
}
$html_path = getenv('EMDO_BFLAST_HTML_PATH');
if (!$html_path || !is_readable($html_path)) {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_HTML_UNREADABLE\n"); exit(6);
}
$html = file_get_contents($html_path);
$title = 'Black Friday 2026 · Últimos días · Todas las ofertas';
$subject = 'Últimos días de Black Friday: hasta el domingo 29 de noviembre';
$preheader = 'Hasta un 20 % de descuento, regalos, envío gratis con Tolecarnes en Madrid y Toledo y 2 % de cashback.';
$recipient = 'jose.fraga@gmail.com';
$test_key = '_emdo_fluentcrm_bflast_20261127_test';
$coupons = [
    'BFEMDO' => 'hidalgo-de-la-jara',
    'BFMONTJAM' => 'montjam',
    'SINFILTRO' => '1957',
    'BFTOLECARNES' => 'tolecarnes',
    'CHOCOISABEL' => 'chocolates-artesanos-isabel',
    'BFANAMARY' => 'la-huerta-de-ana-mary',
    'BFIKORE' => 'ikore-kofi',
    'BFPAJARETE' => 'quesos-pajarete',
    'BFSELECTOS' => 'selectos-de-castilla'
];
if (!is_string($html) || strlen($html) < 8000 ||
    !str_contains($html, '##crm.unsubscribe_url##') ||
    !str_contains($html, '##crm.manage_subscription_url##') ||
    !str_contains($html, 'Comunidad de Madrid') ||
    !str_contains($html, 'provincia de Toledo') ||
    !str_contains($html, '2 % de cashback') ||
    !str_contains($html, '/black-friday-2026/') ||
    !str_contains($html, 'tolecarnes-grill.jpg') ||
    !str_contains($html, 'domingo 29 de noviembre') ||
    !str_contains($html, 'Ver todas las ofertas del Black Friday')) {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_HTML_VALIDATION\n"); exit(7);
}
foreach ($coupons as $code => $slug) {
    if (!str_contains($html, $code) ||
        !str_contains($html, '/tienda/' . $slug . '/')) {
        fwrite(STDERR, "EMDO_BFLAST_ABORT_MISSING_HTML_CODE_OR_STORE " . $code . "\n"); exit(8);
    }
    $coupon = new WC_Coupon($code);
    $expected = ['BFEMDO'=>15, 'BFMONTJAM'=>15, 'BFSELECTOS'=>20][$code] ?? 10;
    if (!$coupon->get_id() || get_post_status($coupon->get_id()) !== 'publish' ||
        $coupon->get_discount_type() !== 'percent' ||
        abs((float)$coupon->get_amount() - $expected) > 0.001) {
        fwrite(STDERR, "EMDO_BFLAST_ABORT_COUPON_INVALID " . $code .
            " id=" . (int)$coupon->get_id() .
            " type=" . (string)$coupon->get_discount_type() .
            " amount=" . (string)$coupon->get_amount() . "\n");
        exit(9);
    }
    echo "EMDO_BFLAST_COUPON_VERIFIED " . $code . " id=" . (int)$coupon->get_id() . " percent=" . (string)$coupon->get_amount() . "\n";
    $expires = $coupon->get_date_expires();
    if ($expires && $expires->getTimestamp() < time()) {
        fwrite(STDERR, "EMDO_BFLAST_ABORT_COUPON_EXPIRED " . $code . "\n"); exit(9);
    }
}
$campaign = $class::where('title', $title)->first();
if ($campaign && (string)$campaign->status !== 'draft') {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_EXISTING_NOT_DRAFT id=" . $campaign->id . "\n"); exit(10);
}
if ($mode === 'preflight') {
    echo "EMDO_BFLAST_PREFLIGHT_OK coupons=9 existing_id=" .
        ($campaign ? $campaign->id : 'none') . " html_bytes=" . strlen($html) . "\n";
    exit(0);
}
function emdo_bflast_request(string $verb, string $route, array $params) {
    $request = new WP_REST_Request($verb, $route);
    $request->set_body_params($params);
    $response = rest_do_request($request);
    if (is_wp_error($response) || (int)$response->get_status() >= 400) {
        fwrite(STDERR, "EMDO_BFLAST_ABORT_REST status=" .
            (is_wp_error($response) ? 'wp_error' : $response->get_status()) .
            " response=" . wp_json_encode(is_wp_error($response) ? $response->get_error_message() : $response->get_data()) . "\n");
        exit(11);
    }
    return $response->get_data();
}
if ($mode === 'apply') {
    $admins = get_users(['role' => 'administrator', 'orderby' => 'ID', 'order' => 'ASC', 'number' => 30]);
    $admin = null;
    foreach ($admins as $u) {
        if (user_can($u, 'fcrm_manage_emails') || user_can($u, 'manage_options')) {
            $admin = $u; break;
        }
    }
    if (!$admin) { fwrite(STDERR, "EMDO_BFLAST_ABORT_NO_ADMIN\n"); exit(12); }
    wp_set_current_user((int)$admin->ID);
    if (!$campaign) {
        $created = emdo_bflast_request('POST', '/fluent-crm/v2/campaigns', [
            'title' => $title, 'design_template' => 'raw_html'
        ]);
        $id = (int)($created['id'] ?? $created['campaign']['id'] ?? 0);
        $campaign = $id ? $class::find($id) : $class::where('title', $title)->first();
        if (!$campaign || (string)$campaign->status !== 'draft') {
            fwrite(STDERR, "EMDO_BFLAST_ABORT_CREATE_FAILED\n"); exit(13);
        }
    }
    $id = (int)$campaign->id;
    emdo_bflast_request('PUT', '/fluent-crm/v2/campaigns/' . $id, [
        'title' => $title,
        'design_template' => 'raw_html',
        'email_subject' => $subject,
        'email_pre_header' => $preheader,
        'email_body' => $html
    ]);
}
$campaign = $class::where('title', $title)->first();
if (!$campaign || (string)$campaign->status !== 'draft' ||
    (string)$campaign->email_subject !== $subject ||
    (string)$campaign->email_body !== $html ||
    (string)$campaign->design_template !== 'raw_html') {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_PERSISTENCE " .
        ($campaign ? ('id=' . $campaign->id . ' status=' . $campaign->status) : 'missing') . "\n"); exit(14);
}
echo "EMDO_BFLAST_DRAFT_VERIFIED id=" . (int)$campaign->id . " status=draft bytes=" . strlen((string)$campaign->email_body) . "\n";
if ($mode !== 'test') exit(0);
// One native FluentCRM test only; never a bulk send. Mark attempt before invoking to prevent duplicate test on retry.
$state = get_option($test_key, []);
if (is_array($state) && !empty($state['attempted'])) {
    if (($state['recipient'] ?? '') !== $recipient || (int)($state['campaign_id'] ?? 0) !== (int)$campaign->id) {
        fwrite(STDERR, "EMDO_BFLAST_ABORT_TEST_STATE_MISMATCH\n"); exit(15);
    }
    if (!empty($state['sent'])) {
        echo "EMDO_BFLAST_TEST_ALREADY_SENT campaign_id=" . (int)$campaign->id . " recipient=" . $recipient . "\n";
        exit(0);
    }
    fwrite(STDERR, "EMDO_BFLAST_ABORT_TEST_PREVIOUS_ATTEMPT_UNRESOLVED\n"); exit(16);
}
update_option($test_key, [
    'attempted' => true,
    'sent' => false,
    'recipient' => $recipient,
    'campaign_id' => (int)$campaign->id,
    'attempted_at' => current_time('mysql')
], false);
$controller = new $controller_class();
$reflection = new ReflectionProperty($controller, 'request');
$reflection->setAccessible(true);
if ($reflection->getType()) {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_TYPED_TEST_REQUEST\n"); exit(17);
}
$mock_request = new class($campaign, $recipient) {
    private $params;
    public function __construct($campaign, $recipient) {
        $this->params = ['test_campaign' => 'no', 'campaign_id' => (int)$campaign->id, 'email' => $recipient];
    }
    public function get($key, $default = null) { return $this->params[$key] ?? $default; }
};
$reflection->setValue($controller, $mock_request);
try {
    $result = $controller->sendTestEmail();
} catch (Throwable $exception) {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_TEST_EXCEPTION " . get_class($exception) . " " . substr($exception->getMessage(), 0, 180) . "\n");
    exit(18);
}
if (!is_array($result) || ($result['result'] ?? null) !== true) {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_TEST_NOT_ACCEPTED type=" . gettype($result) . "\n"); exit(19);
}
update_option($test_key, [
    'attempted' => true,
    'sent' => true,
    'recipient' => $recipient,
    'campaign_id' => (int)$campaign->id,
    'sent_at' => current_time('mysql')
], false);
$check = $class::find((int)$campaign->id);
if (!$check || (string)$check->status !== 'draft') {
    fwrite(STDERR, "EMDO_BFLAST_ABORT_POST_TEST_NOT_DRAFT\n"); exit(20);
}
echo "EMDO_BFLAST_TEST_SENT campaign_id=" . (int)$campaign->id . " recipient=" . $recipient . " status=draft\n";
