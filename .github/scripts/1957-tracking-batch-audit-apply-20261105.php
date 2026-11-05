<?php
/**
 * 1957 — Seguimientos de pedidos (2026-11-05).
 *
 * NO contiene datos de clientes. La lista de envíos debe llegar desde un
 * secreto de GitHub Actions (MDO_1957_TRACKING_PAYLOAD_B64). Nunca se imprime.
 *
 * Por defecto: auditoría sin escrituras ni correos.
 * Para enviar: MDO_1957_TRACKING_MODE=apply + MDO_1957_TRACKING_APPROVED=yes.
 *
 * Ejecución en WordPress: wp eval-file ... --allow-root
 */
if (!defined('ABSPATH') || !function_exists('wc_get_orders') || !function_exists('wc_get_order_notes')) {
    throw new RuntimeException('WooCommerce no está disponible.');
}
$host = strtolower((string) wp_parse_url((string) get_option('siteurl'), PHP_URL_HOST));
if (!in_array($host, ['elmercadodeorigen.com', 'www.elmercadodeorigen.com'], true)) {
    throw new RuntimeException('La web de producción no coincide.');
}
$vendor = get_user_by('id', 3);
$profile = get_user_meta(3, 'wcfmmp_profile_settings', true);
if (!$vendor || !is_array($profile) || stripos((string)($profile['store_name'] ?? ''), '1957') === false) {
    throw new RuntimeException('No se pudo validar la tienda 1957.');
}

function mdo1957_norm($value) {
    $value = remove_accents((string)$value);
    $value = strtoupper($value);
    return trim(preg_replace('/[^A-Z0-9]+/', ' ', $value));
}
function mdo1957_1957_order($order) {
    foreach ($order->get_items('line_item') as $item) {
        $product = $item->get_product();
        if (!$product) continue;
        $id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        if ((int)get_post_field('post_author', (int)$id) === 3) return true;
    }
    return false;
}
function mdo1957_name_candidates($order) {
    $s = trim($order->get_shipping_first_name().' '.$order->get_shipping_last_name());
    $b = trim($order->get_billing_first_name().' '.$order->get_billing_last_name());
    return array_filter(array_unique([$s,$b]));
}
function mdo1957_notes($order) {
    return wc_get_order_notes(['order_id'=>$order->get_id(), 'limit'=>100, 'type'=>'customer']);
}
function mdo1957_template_from_note($order, $note) {
    $body = (string)$note->content;
    if (!preg_match('/\b(0[0-9]{4}[A-Z][0-9]{6,})\b/u', $body, $m)) return null;
    $code = $m[1];
    // Aceptar solo un formato de código y un saludo atribuible al comprador.
    $body = str_replace($code, '{SEGUIMIENTO}', $body);
    foreach (mdo1957_name_candidates($order) as $full) {
        $parts = preg_split('/\s+/u', trim($full));
        $first = $parts[0] ?? '';
        foreach ([$full,$first] as $name) {
            if ($name === '') continue;
            $pattern = '/(\bHola[\s,:-]*)(?:'.preg_quote($name,'/').')(?=\s*[,!:.<\r\n])/iu';
            $candidate = preg_replace($pattern, '$1{NOMBRE}', $body, 1, $count);
            if ($count === 1 && is_string($candidate)) {
                if (preg_match('/\b(?:pedido|orden|order)\s*[#ºnúm.]*\s*\d{4,}\b/iu',$candidate)) return null;
                return $candidate;
            }
        }
    }
    return null;
}
function mdo1957_postcode($order) {
    return (string)($order->get_shipping_postcode() ?: $order->get_billing_postcode());
}
function mdo1957_matches($order, $shipment) {
    $name = mdo1957_norm($shipment['recipient']);
    $postal = preg_replace('/\D/', '', $shipment['postal_code']);
    if ($postal !== preg_replace('/\D/', '', mdo1957_postcode($order))) return false;
    $match = false;
    foreach (mdo1957_name_candidates($order) as $n) {
        if (mdo1957_norm($n) === $name) { $match = true; break; }
    }
    if (!$match) return false;
    if (!empty($shipment['street'])) {
        $street = mdo1957_norm($shipment['street']);
        $actual = mdo1957_norm($order->get_shipping_address_1() ?: $order->get_billing_address_1());
        // Algunas direcciones usan abreviaturas CL/CALLE/AV: exigir coincidencia de número y vía.
        if (!preg_match_all('/\b[0-9]+\b/',$street,$nums) || !array_intersect($nums[0],preg_split('/\D+/', $actual))) {
            return false;
        }
    }
    return true;
}

$mode = strtolower((string)(getenv('MDO_1957_TRACKING_MODE') ?: 'audit'));
if (!in_array($mode,['audit','apply'],true)) throw new RuntimeException('Modo incorrecto.');
if ($mode === 'apply' && getenv('MDO_1957_TRACKING_APPROVED') !== 'yes') {
    throw new RuntimeException('Falta aprobación explícita para enviar.');
}
$payload = getenv('MDO_1957_TRACKING_PAYLOAD_B64');
$shipments = [];
if ($payload !== false && $payload !== '') {
    $decoded = base64_decode($payload, true);
    $data = is_string($decoded) ? json_decode($decoded, true) : null;
    if (!is_array($data) || !isset($data['shipments']) || !is_array($data['shipments'])) {
        throw new RuntimeException('El secreto no contiene JSON válido.');
    }
    $shipments = $data['shipments'];
}
if ($mode === 'apply' && count($shipments) !== 18) {
    throw new RuntimeException('Se esperaban exactamente 18 envíos autorizados.');
}
$known = [];
foreach ($shipments as $entry) {
    if (!is_array($entry)) throw new RuntimeException('Envío inválido.');
    $code = (string)($entry['tracking'] ?? '');
    $state = strtolower((string)($entry['status'] ?? ''));
    if (!preg_match('/^01303A[0-9]{6}$/', $code) || isset($known[$code])
        || empty($entry['recipient'])
        || !preg_match('/^\d{5}$/',(string)($entry['postal_code']??''))
        || !in_array($state,['recogido','pendiente'],true)) {
        throw new RuntimeException('Envío duplicado o sin validación.');
    }
    $known[$code] = true;
}

$orders = wc_get_orders([
    'limit'=>750, 'orderby'=>'date', 'order'=>'DESC',
    'date_created'=>'>=2026-10-01', 'type'=>'shop_order',
]);
$vendor_orders = [];
$samples = [];
foreach ($orders as $order) {
    if (!mdo1957_1957_order($order)) continue;
    $vendor_orders[] = $order;
    $recent = mdo1957_notes($order);
    foreach ($recent as $n) {
        $template = mdo1957_template_from_note($order, $n);
        if ($template !== null) {
            $key = hash('sha256',preg_replace('/\s+/u',' ',trim($template)));
            if (!isset($samples[$key])) $samples[$key] = ['template'=>$template,'orders'=>[]];
            $samples[$key]['orders'][$order->get_id()] = true;
        }
    }
}
$reliable = [];
foreach($samples as $sample) {
    if (count($sample['orders']) >= 2) $reliable[] = $sample;
}
usort($reliable, function($a,$b){return count($b['orders']) <=> count($a['orders']);});
$exact_template = $reliable[0]['template'] ?? null;
// No copiar una plantilla que dos pedidos diferentes no corroboran.
if ($mode === 'apply' && $exact_template === null) {
    throw new RuntimeException('No hay plantilla pública reciente suficientemente contrastada.');
}

$ledger_key = 'mdo_1957_tracking_20261105_ledger';
$ledger = get_option($ledger_key, []);
if (!is_array($ledger)) $ledger=[];
$counts = ['source_orders'=>count($vendor_orders),'templates_reliable'=>count($reliable),
           'matched'=>0,'unmatched'=>0,'ambiguous'=>0,'already_notified'=>0,
           'ready'=>0,'sent'=>0,'skipped'=>0];
$results=[];
foreach ($shipments as $idx=>$shipment) {
    $code=(string)$shipment['tracking'];
    $matches=[];
    foreach ($vendor_orders as $order) {
        if (mdo1957_matches($order,$shipment)) $matches[]=$order;
    }
    $status = '';
    if (count($matches)===0) { $counts['unmatched']++; $status='unmatched'; }
    elseif(count($matches)>1) { $counts['ambiguous']++; $status='ambiguous'; }
    else {
        $counts['matched']++;
        $order = $matches[0];
        $notes = mdo1957_notes($order);
        $any_prior = isset($ledger[$code]);
        foreach ($notes as $n) {
            if (preg_match('/\b01303A[0-9]{6}\b|seguimiento|n[uú]mero\s+de\s+env[ií]o/iu',(string)$n->content)) {
                $any_prior = true;
                break;
            }
        }
        if ($any_prior) { $counts['already_notified']++; $status='already_notified'; }
        elseif (!$order->get_billing_email() || !is_email($order->get_billing_email())) {
            $counts['skipped']++; $status='missing_customer_email';
        }
        elseif (in_array($order->get_status(), ['cancelled','refunded','failed','trash'],true)) {
            $counts['skipped']++; $status='inactive_order';
        }
        else {
            $first=trim((string)$order->get_shipping_first_name());
            if ($first === '') $first=trim((string)$order->get_billing_first_name());
            if ($first === '') { $counts['skipped']++; $status='missing_first_name'; }
            else {
                $status='ready'; $counts['ready']++;
                if ($mode === 'apply') {
                    if ($shipment['status'] === 'recogido') {
                        $note=str_replace(['{NOMBRE}','{SEGUIMIENTO}'],[$first,$code],$exact_template);
                    } else {
                        $note="Hola {$first},\n\nTu pedido del productor 1957 ya tiene asignado el número de seguimiento {$code}. El transportista todavía lo indica como pendiente de recogida. Podrás consultar sus movimientos cuando la agencia lo recoja.\n\nMuchas gracias por confiar en El Mercado de Origen.";
                    }
                    $note_id=$order->add_order_note($note, true, false);
                    if ($note_id) {
                        $ledger[$code]=[
                            'order_id'=>$order->get_id(),'note_id'=>$note_id,
                            'date'=>current_time('mysql'), 'tracking'=>$code,
                            'status_at_notice'=>$shipment['status'], 'text'=>$note
                        ];
                        update_option($ledger_key,$ledger,false);
                        $counts['sent']++; $status='note_created';
                    } else {
                        $counts['skipped']++; $status='note_error';
                    }
                }
            }
        }
    }
    // Solo resultados agregados/índices, nunca destinatarios, direcciones ni códigos.
    $results[]=['position'=>$idx+1,'result'=>$status];
}
echo 'MDO_1957_TRACKING_RESULT='.wp_json_encode([
    'mode'=>$mode,
    'has_private_payload'=>count($shipments)>0,
    'shipments'=>count($shipments),
    'counts'=>$counts,
    'results'=>$results,
    'ledger_saved_privately'=>($mode==='apply'),
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
