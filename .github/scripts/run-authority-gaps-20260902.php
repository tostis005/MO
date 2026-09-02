<?php
try {
    require __DIR__ . '/publish-authority-gaps-20260902.php';
} catch (Throwable $e) {
    fwrite(STDERR, 'EMDO_AUTHORITY_GAPS_ERROR: ' . get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, 'EMDO_AUTHORITY_GAPS_ERROR_FILE: ' . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(92);
}
