<?php
/** Repair editorial images in production and persist the approved choices. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$authority_file = (string) getenv( 'MDO_AUTHORITY_IMAGE_OVERRIDES' );
$legacy_file    = (string) getenv( 'MDO_LEGACY_IMAGE_OVERRIDES' );
if ( '' === $authority_file || ! is_file( $authority_file ) ) { throw new RuntimeException( 'Authority image override file missing.' ); }
if ( '' === $legacy_file || ! is_file( $legacy_file ) ) { throw new RuntimeException( 'Legacy image override file missing.' ); }

$authority_overrides = require $authority_file;
$legacy_overrides    = require $legacy_file;
if ( ! is_array( $authority_overrides ) || ! is_array( $legacy_overrides ) ) { throw new RuntimeException( 'Invalid image override data.' ); }

// The third historic article is intentionally kept here so the shared two-post
// legacy map remains compatible with the already deployed workflow contract.
$legacy_overrides['naranjas'] = array(
	'featured' => array(
		'id'=>'34815908','direct'=>'https://images.pexels.com/photos/34815908/pexels-photo-34815908.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/valencia-orange-tree-under-clear-blue-sky-34815908/','photographer'=>'Emilio Sánchez Hernández','alt_es'=>'Naranjas maduras en un naranjo de Valencia bajo cielo despejado','alt_en'=>'Ripe oranges on a Valencia orange tree under a clear sky'
	),
	'inline' => array(
		array('id'=>'33707783','direct'=>'https://images.pexels.com/photos/33707783/pexels-photo-33707783.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/seville-oranges-on-tree-in-sunlight-33707783/','photographer'=>'Charlie Jordan','alt_es'=>'Naranjas maduras en un árbol de Sevilla iluminado por el sol','alt_en'=>'Ripe oranges on a tree in Seville in sunlight'),
		array('id'=>'37343441','direct'=>'https://images.pexels.com/photos/37343441/pexels-photo-37343441.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/ripe-oranges-on-tree-in-valencia-grove-37343441/','photographer'=>'Bor Jinson','alt_es'=>'Naranjas frescas creciendo en un naranjal de Valencia','alt_en'=>'Fresh oranges growing in a Valencia orange grove'),
		array('id'=>'7299666','direct'=>'https://images.pexels.com/photos/7299666/pexels-photo-7299666.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/orange-fruits-on-a-basket-7299666/','photographer'=>'Anna Tarazevich','alt_es'=>'Cesta de naranjas frescas con hojas verdes','alt_en'=>'Basket of fresh oranges with green leaves'),
		array('id'=>'18102965','direct'=>'https://images.pexels.com/photos/18102965/pexels-photo-18102965.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/ripe-oranges-on-tree-18102965/','photographer'=>'Jonathan Borba','alt_es'=>'Naranjas maduras listas para cosechar en un huerto','alt_en'=>'Ripe oranges ready for harvest in an orchard'),
		array('id'=>'7288784','direct'=>'https://images.pexels.com/photos/7288784/pexels-photo-7288784.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/orange-fruits-in-close-up-photography-7288784/','photographer'=>'Paco Álamo','alt_es'=>'Naranjas frescas reunidas en una cesta de mercado','alt_en'=>'Fresh oranges gathered in a market basket')
	),
);

function mdo_img_validate_010262( array $img, string $context ): void {
	foreach ( array( 'id', 'direct', 'page', 'photographer', 'alt_es' ) as $field ) {
		if ( empty( $img[ $field ] ) ) { throw new RuntimeException( $context . ': missing image field ' . $field ); }
	}
	if ( ! preg_match( '/^[0-9]+$/', (string) $img['id'] ) ) { throw new RuntimeException( $context . ': invalid Pexels id.' ); }
	if ( 0 !== strpos( (string) $img['direct'], 'https://images.pexels.com/' ) ) { throw new RuntimeException( $context . ': non-Pexels direct URL.' ); }
	if ( 0 !== strpos( (string) $img['page'], 'https://www.pexels.com/' ) ) { throw new RuntimeException( $context . ': non-Pexels source page.' ); }
}

function mdo_img_attachment_010262( int $post_id, array $img ): int {
	mdo_img_validate_010262( $img, 'attachment' );
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_emdo_pexels_photo_id',
			'meta_value'     => (string) $img['id'],
		)
	);
	$attachment_id = ! empty( $ids ) ? (int) $ids[0] : 0;

	if ( $attachment_id <= 0 ) {
		$attachment_id = media_sideload_image( (string) $img['direct'], $post_id, (string) $img['alt_es'], 'id' );
		if ( is_wp_error( $attachment_id ) ) { throw new RuntimeException( 'Image ' . $img['id'] . ': ' . $attachment_id->get_error_message() ); }
		$attachment_id = (int) $attachment_id;
	}

	wp_update_post(
		array(
			'ID'           => $attachment_id,
			'post_title'   => wp_strip_all_tags( (string) $img['alt_es'] ),
			'post_excerpt' => 'Fotografía: ' . wp_strip_all_tags( (string) $img['photographer'] ) . ' · Pexels.',
		)
	);
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', (string) $img['alt_es'] );
	update_post_meta( $attachment_id, '_en_US_attachment_alt', (string) ( $img['alt_en'] ?? $img['alt_es'] ) );
	update_post_meta( $attachment_id, '_emdo_pexels_photo_id', (string) $img['id'] );
	update_post_meta( $attachment_id, '_emdo_pexels_page', (string) $img['page'] );
	update_post_meta( $attachment_id, '_emdo_pexels_photographer', (string) $img['photographer'] );
	update_post_meta( $attachment_id, '_emdo_image_license', 'Pexels License - free personal and commercial use' );
	update_post_meta( $attachment_id, '_emdo_image_license_url', 'https://www.pexels.com/license/' );

	$meta = wp_get_attachment_metadata( $attachment_id );
	$width  = (int) ( $meta['width'] ?? 0 );
	$height = (int) ( $meta['height'] ?? 0 );
	if ( $width < 900 || $height < 550 ) { throw new RuntimeException( 'Image ' . $img['id'] . ' too small: ' . $width . 'x' . $height ); }

	return $attachment_id;
}

function mdo_authority_post_010262( string $key ): int {
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_emdo_authority_key',
			'meta_value'     => $key,
		)
	);
	return ! empty( $ids ) ? (int) $ids[0] : 0;
}

function mdo_set_approved_featured_010262( int $post_id, array $img ): int {
	$attachment_id = mdo_img_attachment_010262( $post_id, $img );
	set_post_thumbnail( $post_id, $attachment_id );
	update_post_meta( $post_id, '_emdo_editorial_image_approved_id', $attachment_id );
	update_post_meta( $post_id, '_emdo_editorial_image_approved_pexels_id', (string) $img['id'] );
	update_post_meta( $post_id, '_emdo_editorial_image_approved_at', gmdate( 'c' ) );
	update_post_meta( $post_id, '_emdo_editorial_image_override', '0.10.263' );
	return $attachment_id;
}

function mdo_rewrite_inline_images_010262( int $post_id, array $pool ): array {
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( '' === $content ) { return array( 'found'=>0, 'used'=>array(), 'removed'=>0 ); }

	$image_count = preg_match_all( '/<img\b[^>]*>/iu', $content, $matches );
	if ( ! is_int( $image_count ) || $image_count <= 0 ) { return array( 'found'=>0, 'used'=>array(), 'removed'=>0 ); }

	$attachments = array();
	foreach ( array_slice( $pool, 0, $image_count ) as $index => $img ) {
		mdo_img_validate_010262( $img, 'inline ' . $post_id . ':' . $index );
		$attachments[] = array(
			'id'         => mdo_img_attachment_010262( $post_id, $img ),
			'pexels_id'  => (string) $img['id'],
			'alt'        => (string) $img['alt_es'],
		);
	}

	$cursor = 0;
	$used   = array();
	$removed = 0;
	$rewritten = preg_replace_callback(
		'/<img\b[^>]*>/iu',
		static function ( array $match ) use ( &$cursor, &$used, &$removed, $attachments ): string {
			if ( ! isset( $attachments[ $cursor ] ) ) {
				++$removed;
				++$cursor;
				return '';
			}
			$item = $attachments[ $cursor++ ];
			$url  = wp_get_attachment_image_url( (int) $item['id'], 'full' );
			if ( ! is_string( $url ) || '' === $url ) { throw new RuntimeException( 'Could not resolve replacement image URL.' ); }
			$tag = (string) $match[0];
			$tag = preg_replace( '/\s+(?:src|srcset|sizes|alt|loading|decoding|data-src|data-srcset)\s*=\s*(?:"[^"]*"|\'[^\']*\')/iu', '', $tag );
			$replacement = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( (string) $item['alt'] ) . '" loading="lazy" decoding="async"';
			$tag = preg_replace( '/<img\b/iu', $replacement, (string) $tag, 1 );
			$used[] = (string) $item['pexels_id'];
			return is_string( $tag ) ? $tag : $replacement . ' />';
		},
		$content
	);
	if ( ! is_string( $rewritten ) ) { throw new RuntimeException( 'Inline image rewrite failed for post ' . $post_id ); }

	$found = $cursor;
	if ( $rewritten !== $content ) {
		$result = wp_update_post( array( 'ID'=>$post_id, 'post_content'=>$rewritten ), true );
		if ( is_wp_error( $result ) ) { throw new RuntimeException( 'Post ' . $post_id . ': ' . $result->get_error_message() ); }
	}
	return array( 'found'=>$found, 'used'=>$used, 'removed'=>$removed );
}

// Validate all curated IDs globally before making changes.
$curated_ids = array();
$register = static function ( array $img, string $context ) use ( &$curated_ids ): void {
	mdo_img_validate_010262( $img, $context );
	$id = (string) $img['id'];
	if ( isset( $curated_ids[ $id ] ) ) { throw new RuntimeException( 'Curated image id repeated: ' . $id . ' (' . $curated_ids[ $id ] . ' / ' . $context . ')' ); }
	$curated_ids[ $id ] = $context;
};
foreach ( $authority_overrides as $key => $img ) { $register( $img, 'authority:' . $key ); }
foreach ( $legacy_overrides as $slug => $config ) {
	if ( empty( $config['featured'] ) || ! isset( $config['inline'] ) || ! is_array( $config['inline'] ) ) { throw new RuntimeException( 'Invalid legacy config ' . $slug ); }
	$register( $config['featured'], 'legacy-featured:' . $slug );
	foreach ( $config['inline'] as $index => $img ) { $register( $img, 'legacy-inline:' . $slug . ':' . $index ); }
}

$report = array( 'release'=>'0.10.263', 'authority'=>array(), 'legacy'=>array(), 'duplicates'=>array() );
foreach ( $authority_overrides as $key => $img ) {
	$post_id = mdo_authority_post_010262( (string) $key );
	if ( $post_id <= 0 ) { throw new RuntimeException( 'Published authority post not found: ' . $key ); }
	$attachment_id = mdo_set_approved_featured_010262( $post_id, $img );
	if ( 'bellota-100-iberico-guide' === $key ) {
		delete_post_meta( $post_id, '_emdo_featured_from_product' );
		delete_post_meta( $post_id, '_emdo_featured_image_repaired' );
	}
	$report['authority'][] = array( 'key'=>$key, 'post_id'=>$post_id, 'attachment_id'=>$attachment_id, 'pexels_id'=>(string)$img['id'] );
}

foreach ( $legacy_overrides as $slug => $config ) {
	$post = get_page_by_path( (string) $slug, OBJECT, 'post' );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) { throw new RuntimeException( 'Legacy published post not found: ' . $slug ); }
	$post_id = (int) $post->ID;
	$featured_id = mdo_set_approved_featured_010262( $post_id, $config['featured'] );
	$inline = mdo_rewrite_inline_images_010262( $post_id, $config['inline'] );
	$report['legacy'][] = array(
		'slug'                => $slug,
		'post_id'             => $post_id,
		'featured_attachment' => $featured_id,
		'featured_pexels_id'  => (string) $config['featured']['id'],
		'inline_found'        => (int) $inline['found'],
		'inline_used'         => $inline['used'],
		'inline_removed'      => (int) $inline['removed'],
	);
}

// Final duplicate audit for every published authority article plus all historic articles.
$uses = array();
$authority_ids = get_posts( array( 'post_type'=>'post', 'post_status'=>'publish', 'posts_per_page'=>-1, 'fields'=>'ids', 'meta_key'=>'_emdo_authority_key' ) );
foreach ( $authority_ids as $post_id ) {
	$key = (string) get_post_meta( (int) $post_id, '_emdo_authority_key', true );
	$thumb = (int) get_post_thumbnail_id( (int) $post_id );
	$pexels = $thumb > 0 ? (string) get_post_meta( $thumb, '_emdo_pexels_photo_id', true ) : '';
	if ( '' !== $pexels ) { $uses[ $pexels ][] = 'authority:' . $key; }
}
foreach ( $report['legacy'] as $row ) {
	$uses[ (string) $row['featured_pexels_id'] ][] = 'legacy-featured:' . $row['slug'];
	foreach ( $row['inline_used'] as $pexels ) { $uses[ (string) $pexels ][] = 'legacy-inline:' . $row['slug']; }
}
foreach ( $uses as $pexels => $contexts ) {
	if ( count( $contexts ) > 1 ) { $report['duplicates'][ $pexels ] = $contexts; }
}
if ( ! empty( $report['duplicates'] ) ) { throw new RuntimeException( 'Duplicate editorial images remain: ' . wp_json_encode( $report['duplicates'] ) ); }

// Explicit semantic guards for the most sensitive claims and the third legacy post.
$bellota_id = mdo_authority_post_010262( 'bellota-100-iberico-guide' );
$montanera_id = mdo_authority_post_010262( 'montanera-iberian-ham-guide' );
if ( '34100094' !== (string) get_post_meta( (int) get_post_thumbnail_id( $bellota_id ), '_emdo_pexels_photo_id', true ) ) { throw new RuntimeException( 'Bellota article image guard failed.' ); }
if ( '11251733' !== (string) get_post_meta( (int) get_post_thumbnail_id( $montanera_id ), '_emdo_pexels_photo_id', true ) ) { throw new RuntimeException( 'Montanera article image guard failed.' ); }

$legacy_jamon = get_page_by_path( 'jamon-iberico', OBJECT, 'post' );
if ( $legacy_jamon instanceof WP_Post && false !== stripos( (string) get_post_field( 'post_content', $legacy_jamon->ID ), 'Jamones-en-bodega' ) ) {
	throw new RuntimeException( 'Legacy Jamones-en-bodega duplicate reference remains.' );
}
$legacy_oranges = get_page_by_path( 'naranjas', OBJECT, 'post' );
if ( ! $legacy_oranges instanceof WP_Post || '34815908' !== (string) get_post_meta( (int) get_post_thumbnail_id( $legacy_oranges->ID ), '_emdo_pexels_photo_id', true ) ) {
	throw new RuntimeException( 'Legacy Naranjas image guard failed.' );
}

$report['authority_post_count'] = count( $authority_ids );
$report['legacy_post_count']    = count( $report['legacy'] );
$report['curated_image_count']  = count( $curated_ids );
$report['status'] = 'ok';
echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
