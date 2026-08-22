<?php
/**
 * Plugin Name: MDO English Public URI Coherence
 * Description: Keeps the vendor SEO layer anchored to the browser-facing English URI after the earlier English SEO router performs its internal rewrite.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
 * MU plugins load alphabetically. mdo-production-english-seo-routes.php runs
 * before mdo-z-english-vendor-seo-010260.php and stores the real browser URI in
 * $GLOBALS['mdoer_public_request_uri'] before rewriting REQUEST_URI internally.
 * The vendor SEO plugin historically captured REQUEST_URI after that rewrite,
 * so clean English pages/categories looked like legacy Spanish-in-English paths
 * and were redirected back to their own public URL.
 *
 * This bridge runs immediately after mdo-z-* and repairs only that captured
 * public-URI value. It does not alter REQUEST_URI, queries, products, shipping,
 * ordering, vendor data or the global catalogue.
 */
if ( isset( $GLOBALS['mdoer_public_request_uri'] ) ) {
    $public_uri = (string) $GLOBALS['mdoer_public_request_uri'];
    if ( '' !== $public_uri ) {
        $GLOBALS['mdoev_original_public_uri_010260'] = $public_uri;
    }
}
