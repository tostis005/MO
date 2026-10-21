<?php
/** WP-CLI only: list live published English-island URLs without scraping the public sitemap. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    throw new RuntimeException( 'WP-CLI required for URL export.' );
}
if ( ! function_exists( 'mdoer_sitemap' ) ) {
    throw new RuntimeException( 'English SEO route module not loaded.' );
}
$urls = mdoer_sitemap();
$valid = array_values( array_unique( array_filter( $urls, static function ( $url ): bool {
    $parts = wp_parse_url( (string) $url );
    return is_array( $parts )
        && in_array( strtolower( (string) ( $parts['host'] ?? '' ) ), array( 'www.elmercadodeorigen.com', 'elmercadodeorigen.com' ), true )
        && str_starts_with( (string) ( $parts['path'] ?? '' ), '/en/' );
} ) ) );
if ( count( $valid ) < 30 ) throw new RuntimeException( 'Too few live English URLs: '.count( $valid ) );
echo 'EMDO_ENGLISH_URL_EXPORT ' . wp_json_encode( array( 'count' => count( $valid ), 'urls' => $valid ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
