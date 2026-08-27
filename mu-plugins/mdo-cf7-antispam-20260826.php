<?php
/**
 * Plugin Name: MDO Contact Form 7 Anti-spam Guard
 * Description: Invisible server-side anti-spam guard for Contact Form 7 using signed browser challenges, conservative rate limiting and generated-text detection.
 * Version: 1.1.0
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const MDO_CF7_GUARD_VERSION = '1.1.0';
const MDO_CF7_GUARD_FIELD   = '_mdo_cf7_guard';

/**
 * Return a stable hash of the current browser user-agent without storing the UA itself.
 */
function mdo_cf7_guard_ua_hash() {
    $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
    return substr( hash( 'sha256', $ua ), 0, 20 );
}

/**
 * Use REMOTE_ADDR only. Do not trust spoofable forwarding headers here.
 */
function mdo_cf7_guard_remote_hash() {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : 'unknown';
    return substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 24 );
}

/**
 * Cheap fixed-window limiter backed by transients.
 */
function mdo_cf7_guard_rate_limited( $scope, $limit, $window ) {
    $window = max( 60, (int) $window );
    $bucket = (int) floor( time() / $window );
    $key    = 'mdo_cf7g_' . md5( $scope . '|' . mdo_cf7_guard_remote_hash() . '|' . $bucket );
    $count  = (int) get_transient( $key );

    if ( $count >= (int) $limit ) {
        return true;
    }

    set_transient( $key, $count + 1, $window + 60 );
    return false;
}

/**
 * Issue a short-lived challenge. It is intentionally public: the protection comes
 * from requiring a browser-side interaction to request it and then validating it
 * server-side before Contact Form 7 is allowed to send mail.
 */
function mdo_cf7_guard_rest_challenge( WP_REST_Request $request ) {
    if ( mdo_cf7_guard_rate_limited( 'challenge', 60, MINUTE_IN_SECONDS ) ) {
        return new WP_Error( 'mdo_cf7_guard_rate', 'Too many challenge requests.', array( 'status' => 429 ) );
    }

    $form_id = absint( $request->get_param( 'form_id' ) );
    if ( ! $form_id ) {
        return new WP_Error( 'mdo_cf7_guard_form', 'Invalid form.', array( 'status' => 400 ) );
    }

    $issued = time();
    try {
        $nonce = bin2hex( random_bytes( 16 ) );
    } catch ( Exception $e ) {
        $nonce = wp_generate_password( 32, false, false );
    }

    $ua_hash = mdo_cf7_guard_ua_hash();
    $payload = $form_id . '|' . $issued . '|' . $nonce . '|' . $ua_hash;
    $sig     = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
    $token   = $issued . '.' . $nonce . '.' . $sig;

    $response = rest_ensure_response( array(
        'token' => $token,
        'ttl'   => 7200,
    ) );
    $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
    return $response;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'mdo/v1', '/cf7-guard', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'mdo_cf7_guard_rest_challenge',
        'permission_callback' => '__return_true',
        'args'                => array(
            'form_id' => array(
                'required'          => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => static function( $value ) {
                    return absint( $value ) > 0;
                },
            ),
        ),
    ) );
} );

// Track whether Contact Form 7 actually rendered a form on this request, so the
// browser guard is not printed on shop/blog/product pages that do not need it.
$GLOBALS['mdo_cf7_guard_form_rendered'] = false;
add_filter( 'wpcf7_form_elements', function( $content ) {
    $GLOBALS['mdo_cf7_guard_form_rendered'] = true;
    return $content;
}, 99, 1 );

/**
 * Add the invisible browser guard to CF7 forms. No CAPTCHA or extra user action is shown.
 */
function mdo_cf7_guard_print_script() {
    if ( is_admin() || ! defined( 'WPCF7_VERSION' ) || empty( $GLOBALS['mdo_cf7_guard_form_rendered'] ) ) {
        return;
    }

    $endpoint = rest_url( 'mdo/v1/cf7-guard' );
    ?>
    <script id="mdo-cf7-antispam-guard">
    (() => {
        'use strict';
        const endpoint = <?php echo wp_json_encode( esc_url_raw( $endpoint ) ); ?>;
        const fieldName = <?php echo wp_json_encode( MDO_CF7_GUARD_FIELD ); ?>;

        function init(form) {
            if (!form || form.dataset.mdoCf7Guard === '1') return;
            const idInput = form.querySelector('input[name="_wpcf7"]');
            if (!idInput || !idInput.value) return;

            form.dataset.mdoCf7Guard = '1';
            let tokenInput = form.querySelector('input[name="' + fieldName + '"]');
            if (!tokenInput) {
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = fieldName;
                tokenInput.value = '';
                tokenInput.autocomplete = 'off';
                form.appendChild(tokenInput);
            }

            let requesting = false;
            let issuedAt = 0;

            const requestToken = () => {
                const now = Date.now();
                if (requesting) return;
                if (tokenInput.value && (now - issuedAt) < 90 * 60 * 1000) return;

                requesting = true;
                fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({form_id: parseInt(idInput.value, 10) || 0})
                })
                .then(r => r.ok ? r.json() : Promise.reject(new Error('guard_http_' + r.status)))
                .then(data => {
                    if (data && typeof data.token === 'string' && data.token.length > 40) {
                        tokenInput.value = data.token;
                        issuedAt = Date.now();
                    }
                })
                .catch(() => {})
                .finally(() => { requesting = false; });
            };

            // Real visitors naturally trigger one of these before completing a form.
            // Basic HTTP/form bots generally do not execute this interaction path.
            ['focusin', 'pointerdown', 'touchstart', 'keydown', 'input'].forEach(type => {
                form.addEventListener(type, requestToken, {passive: true});
            });

            // If CF7 resets the form after a successful send, require a fresh challenge
            // for a second message instead of letting the previous proof be replayed.
            document.addEventListener('wpcf7mailsent', event => {
                if (event.target === form) {
                    tokenInput.value = '';
                    issuedAt = 0;
                }
            });
        }

        const scan = () => document.querySelectorAll('.wpcf7 form').forEach(init);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', scan, {once: true});
        } else {
            scan();
        }

    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'mdo_cf7_guard_print_script', 99 );

/**
 * Leave a reason in CF7's spam log when supported (e.g. Flamingo).
 */
function mdo_cf7_guard_log_spam( $reason ) {
    if ( class_exists( 'WPCF7_Submission' ) ) {
        $submission = WPCF7_Submission::get_instance();
        if ( $submission && method_exists( $submission, 'add_spam_log' ) ) {
            $submission->add_spam_log( array(
                'agent'  => 'mdo_cf7_guard',
                'reason' => sanitize_text_field( $reason ),
            ) );
        }
    }
}

/**
 * Shannon entropy for a short ASCII token.
 */
function mdo_cf7_guard_entropy( $value ) {
    $length = strlen( $value );
    if ( $length < 1 ) {
        return 0.0;
    }

    $counts  = count_chars( strtolower( $value ), 1 );
    $entropy = 0.0;

    foreach ( $counts as $count ) {
        $probability = $count / $length;
        $entropy    -= $probability * log( $probability, 2 );
    }

    return $entropy;
}

/**
 * Identify the characteristic pseudo-random letter strings currently used by the
 * spam bot (for example mixed-case 15-25 character tokens). This deliberately
 * requires several independent signals so normal names, brands and product codes
 * are not rejected just because they contain mixed case.
 */
function mdo_cf7_guard_looks_generated_token( $raw_value ) {
    if ( ! is_scalar( $raw_value ) ) {
        return false;
    }

    $value  = trim( wp_strip_all_tags( (string) wp_unslash( $raw_value ) ) );
    $length = strlen( $value );

    // The observed spam tokens are long, unbroken ASCII-letter strings.
    if ( $length < 14 || $length > 40 || ! preg_match( '/^[A-Za-z]+$/', $value ) ) {
        return false;
    }

    $upper = preg_match_all( '/[A-Z]/', $value, $unused );
    $lower = preg_match_all( '/[a-z]/', $value, $unused );

    // A normal all-lowercase word or simple CamelCase label should not be enough.
    if ( $upper < 2 || $lower < 5 ) {
        return false;
    }

    $case_transitions  = 0;
    $consecutive_upper = 0;
    $vowels            = 0;
    $unique_letters    = count( array_unique( str_split( strtolower( $value ) ) ) );
    $characters        = str_split( $value );
    $character_count   = count( $characters );

    foreach ( $characters as $index => $character ) {
        if ( false !== strpos( 'aeiouAEIOU', $character ) ) {
            $vowels++;
        }

        if ( 0 === $index ) {
            continue;
        }

        $previous = $characters[ $index - 1 ];
        $prev_up  = ctype_upper( $previous );
        $curr_up  = ctype_upper( $character );

        if ( $prev_up !== $curr_up ) {
            $case_transitions++;
        }

        if ( $prev_up && $curr_up ) {
            $consecutive_upper++;
        }
    }

    // Random generator output tends to switch case often and contains uppercase
    // runs in the middle, unlike ordinary CamelCase names such as ElMercadoDeOrigen.
    if ( $case_transitions < 5 || $consecutive_upper < 1 ) {
        return false;
    }

    $unique_ratio = $unique_letters / $length;
    if ( $unique_ratio < 0.55 ) {
        return false;
    }

    $entropy = mdo_cf7_guard_entropy( $value );
    if ( $entropy < 3.25 ) {
        return false;
    }

    // Vowel balance is only an additional signal, not a standalone block.
    $vowel_ratio = $vowels / max( 1, $character_count );
    $score       = 0;

    if ( $case_transitions >= 7 ) {
        $score++;
    }
    if ( $consecutive_upper >= 2 ) {
        $score++;
    }
    if ( $entropy >= 3.45 ) {
        $score++;
    }
    if ( $unique_ratio >= 0.65 ) {
        $score++;
    }
    if ( $vowel_ratio <= 0.30 || $vowel_ratio >= 0.62 ) {
        $score++;
    }

    return $score >= 2;
}

/**
 * Detect a submission where multiple human-readable fields independently look
 * machine-generated. One odd value is intentionally allowed; two separate strong
 * random-looking values are required to classify the submission as spam.
 */
function mdo_cf7_guard_generated_text_fields() {
    $matches = array();

    foreach ( $_POST as $field => $raw_value ) {
        if ( ! is_string( $field ) || 0 === strpos( $field, '_' ) || MDO_CF7_GUARD_FIELD === $field ) {
            continue;
        }

        if ( ! is_scalar( $raw_value ) ) {
            continue;
        }

        $value = trim( (string) wp_unslash( $raw_value ) );
        if ( '' === $value ) {
            continue;
        }

        // Email addresses, URLs and telephone/number-like values are legitimate
        // machine-shaped strings and are intentionally excluded from this detector.
        if (
            is_email( $value ) ||
            preg_match( '#^https?://#i', $value ) ||
            preg_match( '/^[+()\d\s.\-]{5,}$/', $value )
        ) {
            continue;
        }

        if ( mdo_cf7_guard_looks_generated_token( $value ) ) {
            $matches[] = sanitize_key( $field );
        }
    }

    return array_values( array_unique( $matches ) );
}

/**
 * Validate the signed challenge in Contact Form 7's official spam hook.
 * Returning true prevents CF7 from sending the mail.
 */
function mdo_cf7_guard_filter_spam( $spam ) {
    if ( $spam ) {
        return true;
    }

    $form_id = isset( $_POST['_wpcf7'] ) ? absint( wp_unslash( $_POST['_wpcf7'] ) ) : 0;
    if ( ! $form_id ) {
        return $spam;
    }

    // Block the observed bot pattern by content characteristics, not by email,
    // country, IP address or whether the visitor used both contact forms.
    $generated_fields = mdo_cf7_guard_generated_text_fields();
    if ( count( $generated_fields ) >= 2 ) {
        mdo_cf7_guard_log_spam( 'Multiple generated-looking text fields: ' . implode( ', ', $generated_fields ) . '.' );
        return true;
    }

    // Conservative cap: normal visitors will never approach this. This only affects CF7.
    if ( mdo_cf7_guard_rate_limited( 'submit', 30, 10 * MINUTE_IN_SECONDS ) ) {
        mdo_cf7_guard_log_spam( 'Rate limit exceeded.' );
        return true;
    }

    $token = isset( $_POST[ MDO_CF7_GUARD_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ MDO_CF7_GUARD_FIELD ] ) ) : '';
    if ( '' === $token ) {
        mdo_cf7_guard_log_spam( 'Missing browser challenge.' );
        return true;
    }

    $parts = explode( '.', $token, 3 );
    if ( 3 !== count( $parts ) ) {
        mdo_cf7_guard_log_spam( 'Malformed browser challenge.' );
        return true;
    }

    list( $issued_raw, $nonce, $sig ) = $parts;
    $issued = ctype_digit( $issued_raw ) ? (int) $issued_raw : 0;
    $age    = time() - $issued;

    if ( ! $issued || $age < 1 ) {
        mdo_cf7_guard_log_spam( 'Challenge submitted too quickly.' );
        return true;
    }

    if ( $age > 7200 ) {
        mdo_cf7_guard_log_spam( 'Expired browser challenge.' );
        return true;
    }

    if ( ! preg_match( '/^[a-zA-Z0-9]{20,64}$/', $nonce ) || ! preg_match( '/^[a-f0-9]{64}$/', $sig ) ) {
        mdo_cf7_guard_log_spam( 'Invalid challenge format.' );
        return true;
    }

    $payload  = $form_id . '|' . $issued . '|' . $nonce . '|' . mdo_cf7_guard_ua_hash();
    $expected = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
    if ( ! hash_equals( $expected, $sig ) ) {
        mdo_cf7_guard_log_spam( 'Challenge signature mismatch.' );
        return true;
    }

    // Prevent bulk replay of one harvested valid challenge while still allowing a retry.
    $replay_key = 'mdo_cf7r_' . substr( hash( 'sha256', $token ), 0, 32 );
    $uses       = (int) get_transient( $replay_key );
    if ( $uses >= 2 ) {
        mdo_cf7_guard_log_spam( 'Challenge replay limit exceeded.' );
        return true;
    }
    set_transient( $replay_key, $uses + 1, 2 * HOUR_IN_SECONDS );

    return false;
}
add_filter( 'wpcf7_spam', 'mdo_cf7_guard_filter_spam', 20, 1 );
