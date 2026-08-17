<?php
if (!defined('ABSPATH')) { exit(1); }
global $wpdb;
$sql = "SELECT p.post_author, COUNT(DISTINCT p.ID) AS qty\n        FROM {$wpdb->posts} p\n        INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID\n        INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'\n        INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id\n        WHERE p.post_type = 'product'\n          AND p.post_status = 'publish'\n          AND t.slug = 'mentta'\n        GROUP BY p.post_author\n        ORDER BY p.post_author";
$rows = $wpdb->get_results($sql);
$total = 0;
foreach ($rows as $row) {
    $total += (int) $row->qty;
    echo 'author_' . (int) $row->post_author . '_published=' . (int) $row->qty . PHP_EOL;
}
echo 'mentta_published_total=' . $total . PHP_EOL;
