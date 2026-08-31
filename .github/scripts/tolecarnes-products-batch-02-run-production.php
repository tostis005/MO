<?php
/** Safe launcher for reviewed Batch 02 copy. Allows simple or variable parent products. */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
$source_file = __DIR__ . '/tolecarnes-products-batch-02-production.php';
if (!is_file($source_file)) { throw new RuntimeException('Batch 02 source file not found.'); }
$source = file_get_contents($source_file);
$needle = <<<'PHP'
if(is_wp_error($types)||!in_array('simple',$types,true)) mo_b2_fail("Unexpected product type {$key}");
PHP;
$replacement = <<<'PHP'
if(is_wp_error($types)||!array_intersect(['simple','variable'],$types)) mo_b2_fail("Unexpected product type {$key}");
PHP;
$count = 0;
$source = str_replace($needle, $replacement, $source, $count);
if ($count !== 1) { throw new RuntimeException('Expected exactly one product-type guard to patch; found '.$count); }
$source = preg_replace('/^<\?php\s*/', '', $source, 1, $tag_count);
if ($tag_count !== 1) { throw new RuntimeException('Could not strip PHP opening tag.'); }
eval($source);
