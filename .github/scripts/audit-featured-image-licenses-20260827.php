<?php
/** Audit featured-image licensing for all published blog posts. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_img_audit_classify(string $license): string {
    $l = strtolower(trim($license));
    if ($l === '') { return 'unknown'; }
    if (preg_match('/\b(cc\s*by(?:-sa)?|creative commons attribution(?:-sharealike)?)\b/i', $license)) {
        if (preg_match('/\b(nc|noncommercial|non-commercial)\b/i', $license)) { return 'forbidden'; }
        return 'required';
    }
    if (preg_match('/\b(cc\s*by-nc|cc\s*by-nc-sa|noncommercial|non-commercial)\b/i', $license)) { return 'forbidden'; }
    if (preg_match('/\b(pexels|unsplash|pixabay|cc0|public domain|dominio p[uú]blico)\b/i', $license)) { return 'optional'; }
    return 'unknown';
}

function emdo_img_audit_meta(int $attachment_id, string $key): string {
    return trim((string)get_post_meta($attachment_id, $key, true));
}

$posts = get_posts(array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
));

$rows = array();
$counts = array('total_posts'=>count($posts),'with_featured_image'=>0,'no_featured_image'=>0,'required'=>0,'optional'=>0,'forbidden'=>0,'unknown'=>0);
foreach ($posts as $post) {
    $post_id = (int)$post->ID;
    $image_id = (int)get_post_thumbnail_id($post_id);
    if ($image_id <= 0) {
        $counts['no_featured_image']++;
        $rows[] = array(
            'post_id'=>$post_id,'slug'=>$post->post_name,'title'=>get_the_title($post_id),'image_id'=>0,'class'=>'no_image',
            'license'=>'','creator'=>'','source_page'=>'','license_url'=>'','source_key'=>'',
            'url'=>get_permalink($post_id),'en_url'=>'',
        );
        continue;
    }
    $counts['with_featured_image']++;
    $license = emdo_img_audit_meta($image_id, '_emdo_image_license');
    $creator = emdo_img_audit_meta($image_id, '_emdo_image_creator');
    $source_page = emdo_img_audit_meta($image_id, '_emdo_image_source_page');
    $license_url = emdo_img_audit_meta($image_id, '_emdo_image_license_url');
    $source_key = emdo_img_audit_meta($image_id, '_emdo_image_source_key');
    $changes = emdo_img_audit_meta($image_id, '_emdo_image_changes');

    // Conservative fallback: only use the media caption when it explicitly contains a license label.
    $caption = trim((string)get_post_field('post_excerpt', $image_id));
    if ($license === '' && preg_match('/Licencia:\s*([^\.]+(?:\.[0-9]+)?)/iu', $caption, $m)) { $license = trim($m[1]); }
    if ($creator === '' && preg_match('/Fotograf(?:í|i)a:\s*([^\.]+)\./iu', $caption, $m)) { $creator = trim($m[1]); }
    if ($source_page === '' && preg_match('~Fuente:\s*(https?://\S+)~iu', $caption, $m)) { $source_page = rtrim($m[1], '.,)'); }

    $class = emdo_img_audit_classify($license);
    $counts[$class]++;
    $en_slug = trim((string)get_post_meta($post_id, '_en_US_post_name', true));
    $en_pub = (string)get_post_meta($post_id, '_en_US_published', true) === '1';
    $rows[] = array(
        'post_id'=>$post_id,
        'slug'=>$post->post_name,
        'title'=>get_the_title($post_id),
        'image_id'=>$image_id,
        'class'=>$class,
        'license'=>$license,
        'creator'=>$creator,
        'source_page'=>$source_page,
        'license_url'=>$license_url,
        'source_key'=>$source_key,
        'changes'=>$changes,
        'url'=>get_permalink($post_id),
        'en_url'=>($en_pub && $en_slug !== '') ? home_url('/en/'.sanitize_title($en_slug).'/') : '',
        'metadata_complete'=>($license !== '' && $creator !== '' && $source_page !== '' && $license_url !== ''),
    );
}

$report = array(
    'audit'=>'featured-image-licenses',
    'generated_at'=>gmdate('c'),
    'site'=>home_url('/'),
    'counts'=>$counts,
    'posts'=>$rows,
);
echo wp_json_encode($report, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
