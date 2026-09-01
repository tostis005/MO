<?php
/** Diagnostic-safe runner for cheese batch 07 publisher. */
if ( ! defined('ABSPATH') ) { exit; }
try {
    require __DIR__ . '/publish-cheese-batch-07-production.php';
} catch (Throwable $e) {
    fwrite(STDERR, "EMDO_CHEESE_BATCH07_ERROR: " . get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, "EMDO_CHEESE_BATCH07_ERROR_FILE: " . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(91);
}
