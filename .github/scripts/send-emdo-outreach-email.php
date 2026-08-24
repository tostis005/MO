<?php
if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress context required\n");
    exit(2);
}

$decode_env = static function (string $name): string {
    $raw = getenv($name);
    if ($raw === false || $raw === '') {
        return '';
    }
    $decoded = base64_decode($raw, true);
    return $decoded === false ? '' : $decoded;
};

$to = trim($decode_env('EMDO_MAIL_TO_B64'));
$subject = trim($decode_env('EMDO_MAIL_SUBJECT_B64'));
$message = $decode_env('EMDO_MAIL_BODY_B64');

if (!is_email($to)) {
    fwrite(STDERR, "Invalid recipient\n");
    exit(3);
}
if ($subject === '' || trim($message) === '') {
    fwrite(STDERR, "Subject and body are required\n");
    exit(4);
}

$from = 'hola@elmercadodeorigen.com';
$from_name = 'El Mercado de Origen';

add_filter('wp_mail_from', static function () use ($from) {
    return $from;
});
add_filter('wp_mail_from_name', static function () use ($from_name) {
    return $from_name;
});
add_action('phpmailer_init', static function ($phpmailer) use ($from, $from_name) {
    // Keep envelope sender / Return-Path aligned with the visible From domain.
    // This is what makes Plesk DKIM-sign the message correctly and allows Gmail delivery.
    $phpmailer->Sender = $from;
    $phpmailer->setFrom($from, $from_name, false);
});

$failure = null;
add_action('wp_mail_failed', static function ($error) use (&$failure) {
    $failure = $error instanceof WP_Error ? $error->get_error_message() : 'Unknown wp_mail error';
});

$headers = array(
    'Content-Type: text/plain; charset=UTF-8',
    'Reply-To: El Mercado de Origen <hola@elmercadodeorigen.com>',
);

$sent = wp_mail($to, $subject, $message, $headers);

echo 'EMDO_OUTREACH_MAIL_SENT=' . ($sent ? '1' : '0') . PHP_EOL;
if ($failure) {
    echo 'EMDO_OUTREACH_MAIL_ERROR=' . $failure . PHP_EOL;
}

exit($sent ? 0 : 1);
