<?php
try {
    require __DIR__ . '/publish-cheese-batch-09-production.php';
} catch (Throwable $e) {
    fwrite(STDERR, 'EMDO_CHEESE_BATCH09_ERROR: ' . get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, 'EMDO_CHEESE_BATCH09_ERROR_FILE: ' . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(91);
}
