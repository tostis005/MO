<?php
/**
 * Prepare narrow production H1 changes against the *live* child theme.
 * No writes to live theme. Emits patched files into a staging folder.
 *
 * php patch-seo-final-h1-production-20261025.php /path/live-theme /tmp/stage
 */
if ( PHP_SAPI !== 'cli' || count( $argv ) !== 3 ) {
    fwrite( STDERR, "Usage: php patcher.php LIVE_THEME STAGE_DIR\n" );
    exit( 2 );
}
$theme = rtrim( $argv[1], '/' );
$stage = rtrim( $argv[2], '/' );
if ( ! is_file( $theme . '/functions.php' ) || ! is_file( $theme . '/single.php' ) || ! is_dir( $stage ) ) {
    fwrite( STDERR, "Live files or staging directory missing\n" ); exit( 2 );
}
$functions = file_get_contents( $theme . '/functions.php' );
$single    = file_get_contents( $theme . '/single.php' );
if ( ! is_string( $functions ) || ! is_string( $single ) ) {
    fwrite( STDERR, "Cannot read current theme files\n" ); exit( 2 );
}
$module = "'inc/seo-final-heading-20261025.php',";
if ( false === strpos( $functions, $module ) ) {
    $anchor = "'inc/seo-gsc-batch-20-20261012.php',";
    if ( substr_count( $functions, $anchor ) !== 1 ) {
        fwrite( STDERR, "Refusing functions.php patch: known GSC module anchor missing/ambiguous\n" ); exit( 3 );
    }
    $functions = str_replace( $anchor, $anchor . "\n\t" . $module, $functions );
}
if ( false === strpos( $single, 'elmercado_blog_final_h1_guard_20261025' ) ) {
    $anchor = "\$content_html = apply_filters( 'the_content', get_the_content() );";
    if ( substr_count( $single, $anchor ) !== 1 ) {
        fwrite( STDERR, "Refusing single.php patch: article body anchor missing/ambiguous\n" ); exit( 3 );
    }
    $code = "\n\t\t\t\t\t\tif ( function_exists( 'elmercado_blog_final_h1_guard_20261025' ) ) {\n"
          . "\t\t\t\t\t\t\t\$content_html = elmercado_blog_final_h1_guard_20261025( \$content_html, get_the_title( \$post_id ) );\n"
          . "\t\t\t\t\t\t}\n";
    $single = str_replace( $anchor, $anchor . $code, $single );
}
if ( substr_count( $functions, $module ) !== 1 ||
     substr_count( $single, 'elmercado_blog_final_h1_guard_20261025' ) !== 2 ) {
    // two mentions in a live up-to-date single.php: condition and invocation.
    fwrite( STDERR, "Patch did not produce exactly one guard and module registration\n" ); exit( 3 );
}
if ( file_put_contents( $stage . '/functions.php', $functions ) === false ||
     file_put_contents( $stage . '/single.php', $single ) === false ) {
    fwrite( STDERR, "Failed to stage updated theme code\n" ); exit( 3 );
}
echo "SEO_H1_PREPARED=1\n";
