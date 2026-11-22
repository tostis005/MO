<?php
/** Read-only readiness check. No recipient data and NO sends. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(2);
$host = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($host, ['elmercadodeorigen.com', 'www.elmercadodeorigen.com'], true)) exit(3);
$cls = '\FluentCrm\App\Models\Campaign';
if (!class_exists($cls)) exit(4);
function bf10_summary($c) {
    $settings = is_array($c->settings) ? $c->settings : [];
    $mailer = is_array($settings['mailer_settings'] ?? null) ? $settings['mailer_settings'] : [];
    $recipients = $settings['recipients'] ?? null;
    $recipients_summary = null;
    if (is_array($recipients)) {
        $recipients_summary = ['keys' => array_keys($recipients)];
        foreach ($recipients as $key => $value) {
            if (is_array($value)) {
                $recipients_summary[(string)$key] = [
                    'count' => count($value),
                    'values' => in_array((string)$key, ['lists','tags','contact_type','statuses','subscriber_statuses'], true) ? array_slice($value,0,30) : null
                ];
            } elseif (is_scalar($value)) {
                $recipients_summary[(string)$key] = $value;
            }
        }
    } elseif (is_scalar($recipients)) $recipients_summary = $recipients;
    return [
        'id' => (int)$c->id,
        'title' => (string)$c->title,
        'status' => (string)$c->status,
        'email_subject' => (string)$c->email_subject,
        'template' => (string)$c->design_template,
        'body_length' => strlen((string)$c->email_body),
        'has_grill' => str_contains((string)$c->email_body,'tolecarnes-grill.jpg'),
        'has_footer' => str_contains((string)$c->email_body,'RECUERDA · BLACK FRIDAY'),
        'settings_keys' => array_keys($settings),
        'mailer_settings' => [
            'from_name' => $mailer['from_name'] ?? null,
            'from_email' => $mailer['from_email'] ?? null,
            'reply_to_email' => $mailer['reply_to_email'] ?? null,
        ],
        'recipients' => $recipients_summary,
        'other_recipient_keys' => array_values(array_filter(array_keys($settings), static function($key){return stripos((string)$key,'recipient')!==false || stripos((string)$key,'segment')!==false || stripos((string)$key,'list')!==false;}))
    ];
}
$target = $cls::find(15);
if (!$target) {echo "BF10_CAMPAIGN_MISSING\n";exit(5);}
echo "BF10_TARGET=".wp_json_encode(bf10_summary($target),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
$rows = $cls::orderBy('id','DESC')->limit(18)->get();
foreach ($rows as $c) {
    if ((int)$c->id === 15) continue;
    if (!in_array((string)$c->status,['sent','archived','completed'],true)) continue;
    echo "BF10_REFERENCE=".wp_json_encode(bf10_summary($c),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    if ((int)$c->id < 8) break;
}
echo "BF10_READINESS_AUDIT_COMPLETE\n";
