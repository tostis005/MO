<?php
try {
    $repairs = array(
        'cheese-batch-09-data-00.b64' => 'H',
        'cheese-batch-09-data-01.b64' => '2',
    );
    foreach ($repairs as $name => $prefix) {
        $path = __DIR__ . '/' . $name;
        $raw = file_get_contents($path);
        if ($raw === false) { throw new RuntimeException('Could not read ' . $name); }
        if (substr($raw, 0, 1) !== $prefix) {
            if (file_put_contents($path, $prefix . $raw) === false) {
                throw new RuntimeException('Could not repair transfer prefix for ' . $name);
            }
        }
    }
    require __DIR__ . '/publish-cheese-batch-09-production.php';
} catch (Throwable $e) {
    fwrite(STDERR, 'EMDO_CHEESE_BATCH09_ERROR: ' . get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, 'EMDO_CHEESE_BATCH09_ERROR_FILE: ' . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(91);
}
