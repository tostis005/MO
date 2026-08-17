<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
$expected = [4507 => 'Tolecarnes', 4508 => 'Puente Robles', 4509 => 'El Catedrático'];
foreach ( $expected as $uid => $needle ) {
    $u = get_user_by( 'id', $uid );
    if ( ! $u || stripos( remove_accents( $u->display_name ), remove_accents( $needle ) ) === false ) {
        fwrite( STDERR, "Vendor identity mismatch for {$uid}\n" ); exit(10);
    }
}
$out = ['site' => get_option('siteurl'), 'products' => []];
foreach ( $expected as $uid => $vendor ) {
    $ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID",
        $uid
    ) );
    foreach ( $ids as $id ) {
        $p = get_post( (int) $id ); if ( ! $p ) continue;
        $has_title = trim( wp_strip_all_tags( (string) get_post_meta($id,'_en_US_post_title',true) ) ) !== '';
        $has_content = trim( wp_strip_all_tags( (string) get_post_meta($id,'_en_US_post_content',true) ) ) !== '' || trim( wp_strip_all_tags($p->post_content) ) === '';
        $has_slug = trim( (string) get_post_meta($id,'_en_US_post_name',true) ) !== '';
        $published = (string) get_post_meta($id,'_en_US_published',true) === '1';
        // Tolecarnes already has 38 reviewed translations. Only export anything not fully ready.
        if ( $uid === 4507 && $has_title && $has_content && $has_slug && $published ) continue;
        // For the two pre-launch catalogues, export all products even if a prior staging attempt populated fields.
        $out['products'][] = [
            'id'=>(int)$id, 'author_id'=>(int)$uid, 'vendor'=>$vendor, 'status'=>$p->post_status,
            'title'=>$p->post_title, 'content'=>$p->post_content, 'excerpt'=>$p->post_excerpt,
            'spanish_slug'=>$p->post_name,
            'existing'=>[
                'title'=>(string)get_post_meta($id,'_en_US_post_title',true),
                'content'=>(string)get_post_meta($id,'_en_US_post_content',true),
                'excerpt'=>(string)get_post_meta($id,'_en_US_post_excerpt',true),
                'slug'=>(string)get_post_meta($id,'_en_US_post_name',true),
                'published'=>(string)get_post_meta($id,'_en_US_published',true),
                'ready'=>(string)get_post_meta($id,'_en_US_ready',true),
            ],
        ];
    }
}
$counts=[]; foreach($out['products'] as $p){ $counts[$p['vendor']] = ($counts[$p['vendor']] ?? 0) + 1; }
file_put_contents('/tmp/mdo-launch-products.json', wp_json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo 'EXPORT '.wp_json_encode(['count'=>count($out['products']),'vendors'=>$counts],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if ( count($out['products']) !== 202 ) { fwrite(STDERR,'Expected exactly 202 target products, got '.count($out['products'])."\n"); exit(11); }
if ( ($counts['Tolecarnes'] ?? 0) !== 1 || ($counts['Puente Robles'] ?? 0) !== 106 || ($counts['El Catedrático'] ?? 0) !== 95 ) {
    fwrite(STDERR,'Unexpected vendor distribution: '.wp_json_encode($counts,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n"); exit(12);
}
