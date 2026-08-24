<?php
if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress context required\n");
    exit(2);
}

$payload = 'W1siQW5hIiwgImFuYUBhbmFjb2Npbml0YXMuZXMiXSwgWyJQYXVsYSIsICJwYXVsYUBwYXVsYXN1bWFzaS5lcyJdLCBbIlBhdHJpY2lhIiwgImluZm8ucmVjZXRhc3BhdGlAZ21haWwuY29tIl0sIFsiUm9zYSIsICJyb3NhQG1lZ3VzdGFjb21lcnNhbm8uY29tIl0sIFsiVGVyZSIsICJtYXJpYWNvY2luaWxsYXNAZ21haWwuY29tIl0sIFsiTWFyw61hIiwgImluZm9AY2VuYXNwYXJhcGVxdWVzLmVzIl0sIFsiTWFyw61hIiwgIm1hcmlhcGVsYXphc0Bzb21vc21hZHRhbGVudHMuY29tIl0sIFsiTHVjw61hIiwgImx1LnNpbmdsdXRlbkBzb21vc21hZHRhbGVudHMuY29tIl0sIFsiUGF1bGEiLCAiaGVsbG9AcGF1bGFzYXByb24uY29tIl0sIFsiUGF0cmljaWEiLCAiY29sYWJvcmFjaW9uZXMudGljdGFjeXVtbXlAZ21haWwuY29tIl0sIFsiTWFydGEiLCAibWFydGFAZGVsaWNpb3VzbWFydGhhLmNvbSJdLCBbIlJhZmEiLCAidmlyZ2luaWFAdGhlcGhpbGlwcGFzLmNvbSJdLCBbIkVyaWMiLCAiZXJpY2xhaHVlcnRhQHR3aWMuZXMiXSwgWyJNYXLDrWEiLCAic2Fib3JlYW5kYUBnbWFpbC5jb20iXSwgWyJBbGJhIiwgImhvbGFAYWxiaXRyaXBzLmNvbSJdLCBbIk5hdGFsaWEgeSBNYW51ZWwiLCAiaW5mb0ByZWNldGFzZGVlc2NhbmRhbG8uY29tIl0sIFsiTWFyYSIsICJtYXJhb2xtb3NzQHNvbW9zbWFkdGFsZW50cy5jb20iXSwgWyJNYXJpYmVsIiwgInJlY2V0YXNwYXJhc2VyZmVsaXpAc29tb3NtYWR0YWxlbnRzLmNvbSJdLCBbIlZhbmVzc2EiLCAiaGVsbG9Ac3B0YWxlbnRzLmNvbSJdLCBbIiIsICJjb250YWN0b0Bjb2NpbmFjb25jb3F1aS5jb20iXSwgWyJQYWJsbyIsICJwb2VzaWFkZWZvZ29uQGdtYWlsLmNvbSJdLCBbIkFubmEiLCAiYW5uYXBwbGVhZGF5QHNvbW9zbWFkdGFsZW50cy5jb20iXSwgWyJDYXJtZW4iLCAiY2FybWVuaXJpLmJ1c2luZXNzQGdtYWlsLmNvbSJdLCBbIkFubmEiLCAiYW5uYUBhbm5hcmVjZXRhc2ZhY2lsZXMuY29tIl0sIFsiUGF1bGEiLCAiY29sYWJvcmFjaW9uZXMucGF1ZmVlbEBnbWFpbC5jb20iXSwgWyJPcmlhbmEiLCAidGFzdHlodW50aW5nQHR3aWMuZXMiXSwgWyJKdWFuIiwgImluZm9AcGF1c2F5cGxhdG8uY29tIl0sIFsiSW7DqXMiLCAiaG95Y29tZW1vc3Nhbm9AdHdpYy5lcyJdLCBbIkFsYmVydG8iLCAidWdhckBzb21vc21hZHRhbGVudHMuY29tIl0sIFsiVGFuaWEiLCAidGFuaWFib3JnQHR3aWMuZXMiXSwgWyJWZXLDs25pY2EiLCAiaG9sYUB2ZXJvenVtYS5jb20iXSwgWyJKdWFuIiwgImNvbnRyYXRhY2lvbmVzQGp1YW5sbG9yY2EuY29tIl0sIFsiTWF4aSIsICJtYXhpYWpAc29tb3NtYWR0YWxlbnRzLmNvbSJdLCBbIlNvZsOtYSIsICJjb250YWN0b0B3YXZldHJlbmRhZ2VuY3kuY29tIl0sIFsiTmVyZWEiLCAibGFtb3Jyb2Zpbm9AZ21haWwuY29tIl0sIFsiQW1heWEiLCAiaG9sYUBhbWF5YWNvY2luYS5jb20iXSwgWyIiLCAiY29va2FuZHRyYXZlbHNwYWluQGdtYWlsLmNvbSJdXQ==';
$contacts = json_decode(base64_decode($payload), true);
if (!is_array($contacts) || count($contacts) !== 37) {
    fwrite(STDERR, "Invalid campaign payload\n");
    exit(3);
}

$from = 'hola@elmercadodeorigen.com';
add_filter('wp_mail_from', static fn() => $from);
add_filter('wp_mail_from_name', static fn() => 'El Mercado de Origen');
add_action('phpmailer_init', static function ($phpmailer) use ($from) {
    $phpmailer->Sender = $from;
    $phpmailer->setFrom($from, 'El Mercado de Origen', false);
});

$subject = 'Propuesta de colaboración — El Mercado de Origen';
$accepted = 0;
$failed = 0;
$failedIndexes = [];

foreach ($contacts as $i => $contact) {
    [$name, $to] = $contact;
    $greeting = $name !== '' ? "¡Hola, {$name}! 👋" : "¡Hola! 👋";
    $message = $greeting . "\n\n"
        . "Te escribo desde El Mercado de Origen. Hemos estado viendo tu perfil y creemos que puede haber bastante encaje entre el tipo de contenido que compartes, tu comunidad y los productos con los que trabajamos, así que queríamos proponerte una posible colaboración.\n\n"
        . "Llevamos activos desde 2020 y durante estos años hemos estado especialmente centrados en AOVE y productos ibéricos. Ahora estamos dando un nuevo impulso a El Mercado de Origen y en los próximos días vamos a incorporar nuevos productores, ampliando progresivamente la selección disponible en la web.\n\n"
        . "La idea del proyecto es reunir productores seleccionados y acercar sus productos directamente al consumidor, haciendo visible quién está detrás de cada producto y su origen.\n\n"
        . "Tenemos ya bastante recorrido y reputación detrás: actualmente contamos con 4,9/5 en Google con más de 300 reseñas y 4,6/5 en Trustpilot con 169 opiniones.\n\n"
        . "En paralelo estamos preparando un sistema de afiliación para creadores y perfiles gastronómicos. La propuesta es sencilla: 5 % del importe de cada venta que llegue a través de ti + 0,50 € adicionales por cada pedido, mediante enlaces personalizados y, cuando tenga sentido, también códigos o cupones de descuento.\n\n"
        . "No habría exclusividad ni obligación de publicar un número determinado de veces. La idea es facilitarte las herramientas para que puedas compartirlo con tu comunidad de la forma que mejor encaje contigo y recibir una comisión por las ventas que generes.\n\n"
        . "Nos gustaría saber qué te parece la propuesta y si te interesaría explorar una colaboración de este tipo. Si tienes cualquier duda sobre el funcionamiento, las comisiones o cualquier otro detalle, solo tienes que decírnoslo 😊\n\n"
        . "https://www.elmercadodeorigen.com/\n\n"
        . "Muchas gracias,\nEl Mercado de Origen";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: El Mercado de Origen <hola@elmercadodeorigen.com>',
    ];

    $sent = wp_mail($to, $subject, $message, $headers);
    if ($sent) {
        $accepted++;
    } else {
        $failed++;
        $failedIndexes[] = $i + 1;
    }

    if ($i < count($contacts) - 1) {
        sleep(6);
    }
}

echo 'EMDO_AFFILIATE_CAMPAIGN_TOTAL=' . count($contacts) . PHP_EOL;
echo 'EMDO_AFFILIATE_CAMPAIGN_ACCEPTED=' . $accepted . PHP_EOL;
echo 'EMDO_AFFILIATE_CAMPAIGN_FAILED=' . $failed . PHP_EOL;
if ($failedIndexes) {
    echo 'EMDO_AFFILIATE_CAMPAIGN_FAILED_INDEXES=' . implode(',', $failedIndexes) . PHP_EOL;
}

exit($failed === 0 ? 0 : 1);
