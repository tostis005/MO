<?php
/**
 * Plugin Name: EMDO Instagram Content History
 * Description: Persistent publication history for El Mercado de Origen social content.
 * Version: 1.0.0
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EMDO_CONTENT_HISTORY_DB_VERSION', '1.0.0' );
define( 'EMDO_CONTENT_HISTORY_DB_OPTION', 'emdo_content_history_db_version' );

function emdo_content_history_table() {
	global $wpdb;
	return $wpdb->prefix . 'emdo_content_history';
}

function emdo_content_history_install() {
	global $wpdb;

	if ( get_option( EMDO_CONTENT_HISTORY_DB_OPTION ) === EMDO_CONTENT_HISTORY_DB_VERSION ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = emdo_content_history_table();
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		platform varchar(32) NOT NULL DEFAULT 'instagram',
		media_id varchar(191) NULL,
		request_id varchar(191) NULL,
		content_type varchar(64) NOT NULL DEFAULT 'image_post',
		topic varchar(191) NULL,
		image_url text NULL,
		caption longtext NOT NULL,
		caption_hash char(64) NOT NULL,
		source varchar(64) NOT NULL DEFAULT 'instagram_api',
		status varchar(32) NOT NULL DEFAULT 'published',
		published_at datetime NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY media_id (media_id),
		KEY caption_hash (caption_hash),
		KEY published_at (published_at),
		KEY status (status)
	) {$charset};";

	dbDelta( $sql );
	update_option( EMDO_CONTENT_HISTORY_DB_OPTION, EMDO_CONTENT_HISTORY_DB_VERSION, false );

	// Import any publications already recorded by the Instagram publisher.
	$legacy = get_option( 'emdo_ig_publish_log', array() );
	if ( is_array( $legacy ) ) {
		foreach ( array_reverse( $legacy ) as $item ) {
			emdo_content_history_record( $item, 'legacy_log' );
		}
	}
}
add_action( 'plugins_loaded', 'emdo_content_history_install', 30 );

function emdo_content_history_topic_from_caption( $caption ) {
	$text = wp_strip_all_tags( (string) $caption );
	$text = preg_replace( '/(?:^|\s)#[\p{L}\p{N}_-]+/u', '', $text );
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
	if ( '' === $text ) {
		return '';
	}

	$parts = preg_split( '/(?<=[.!?])\s+/u', $text, 2 );
	$topic = isset( $parts[0] ) ? $parts[0] : $text;
	return sanitize_text_field( function_exists( 'mb_substr' ) ? mb_substr( $topic, 0, 190 ) : substr( $topic, 0, 190 ) );
}

/**
 * Persist a publication. Duplicate media IDs are ignored.
 */
function emdo_content_history_record( $item, $source = 'instagram_api' ) {
	global $wpdb;

	if ( ! is_array( $item ) ) {
		return false;
	}

	$caption = isset( $item['caption'] ) ? trim( (string) $item['caption'] ) : '';
	if ( '' === $caption ) {
		return false;
	}

	$table    = emdo_content_history_table();
	$media_id = ! empty( $item['media_id'] ) ? sanitize_text_field( (string) $item['media_id'] ) : null;

	if ( $media_id ) {
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE media_id = %s LIMIT 1", $media_id ) );
		if ( $exists ) {
			return (int) $exists;
		}
	}

	$published_at = ! empty( $item['published_at'] ) ? sanitize_text_field( (string) $item['published_at'] ) : current_time( 'mysql', true );
	$topic        = ! empty( $item['topic'] ) ? sanitize_text_field( (string) $item['topic'] ) : emdo_content_history_topic_from_caption( $caption );
	$request_id   = ! empty( $item['request_id'] ) ? sanitize_text_field( (string) $item['request_id'] ) : null;
	$content_type = ! empty( $item['content_type'] ) ? sanitize_key( (string) $item['content_type'] ) : 'image_post';

	$inserted = $wpdb->insert(
		$table,
		array(
			'platform'      => 'instagram',
			'media_id'      => $media_id,
			'request_id'    => $request_id,
			'content_type'  => $content_type,
			'topic'         => $topic,
			'image_url'     => ! empty( $item['image_url'] ) ? esc_url_raw( (string) $item['image_url'] ) : '',
			'caption'       => $caption,
			'caption_hash'  => hash( 'sha256', wp_strip_all_tags( strtolower( $caption ) ) ),
			'source'        => sanitize_key( (string) $source ),
			'status'        => ! empty( $item['status'] ) ? sanitize_key( (string) $item['status'] ) : 'published',
			'published_at'  => $published_at,
			'created_at'    => current_time( 'mysql', true ),
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	return $inserted ? (int) $wpdb->insert_id : false;
}

/**
 * The existing publisher updates emdo_ig_publish_log after every successful post.
 * Watching that option means manual posts and ChatGPT/GitHub queued posts are both captured.
 */
function emdo_content_history_from_publish_log( $old_value, $value ) {
	if ( ! is_array( $value ) || empty( $value ) ) {
		return;
	}

	$newest = reset( $value );
	if ( is_array( $newest ) ) {
		emdo_content_history_record( $newest, 'instagram_api' );
	}
}
add_action( 'update_option_emdo_ig_publish_log', 'emdo_content_history_from_publish_log', 10, 2 );

function emdo_content_history_from_added_log( $option, $value ) {
	if ( 'emdo_ig_publish_log' !== $option || ! is_array( $value ) || empty( $value ) ) {
		return;
	}
	$newest = reset( $value );
	if ( is_array( $newest ) ) {
		emdo_content_history_record( $newest, 'instagram_api' );
	}
}
add_action( 'added_option', 'emdo_content_history_from_added_log', 10, 2 );

function emdo_content_history_recent( $limit = 50, $query = '' ) {
	global $wpdb;
	$table = emdo_content_history_table();
	$limit = max( 1, min( 100, (int) $limit ) );

	if ( '' !== trim( (string) $query ) ) {
		$like = '%' . $wpdb->esc_like( trim( (string) $query ) ) . '%';
		$sql  = $wpdb->prepare(
			"SELECT id, platform, media_id, content_type, topic, image_url, caption, status, published_at FROM {$table} WHERE caption LIKE %s OR topic LIKE %s ORDER BY published_at DESC, id DESC LIMIT %d",
			$like,
			$like,
			$limit
		);
	} else {
		$sql = $wpdb->prepare(
			"SELECT id, platform, media_id, content_type, topic, image_url, caption, status, published_at FROM {$table} ORDER BY published_at DESC, id DESC LIMIT %d",
			$limit
		);
	}

	return $wpdb->get_results( $sql, ARRAY_A );
}

function emdo_content_history_rest_routes() {
	register_rest_route(
		'emdo-instagram/v1',
		'/history',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true',
			'args'                => array(
				'limit' => array( 'default' => 50, 'sanitize_callback' => 'absint' ),
				'q'     => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
			),
			'callback'            => function ( WP_REST_Request $request ) {
				$rows = emdo_content_history_recent( $request->get_param( 'limit' ), $request->get_param( 'q' ) );
				return rest_ensure_response(
					array(
						'count' => count( $rows ),
						'items' => $rows,
					)
				);
			},
		)
	);
}
add_action( 'rest_api_init', 'emdo_content_history_rest_routes' );

function emdo_content_history_admin_menu() {
	add_management_page(
		'Historial de contenidos',
		'Historial de contenidos',
		'manage_options',
		'emdo-content-history',
		'emdo_content_history_admin_page'
	);
}
add_action( 'admin_menu', 'emdo_content_history_admin_menu' );

function emdo_content_history_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$rows = emdo_content_history_recent( 100 );
	?>
	<div class="wrap">
		<h1>Historial de contenidos — El Mercado de Origen</h1>
		<p>Registro auxiliar de lo publicado por la integración de Instagram. Se utiliza para revisar temas anteriores y evitar repeticiones.</p>
		<table class="widefat striped" style="max-width:1200px;">
			<thead><tr><th>Fecha UTC</th><th>Tema</th><th>Texto</th><th>Imagen</th><th>Media ID</th></tr></thead>
			<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="5">Todavía no hay publicaciones registradas.</td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td><?php echo esc_html( $row['published_at'] ); ?></td>
					<td><?php echo esc_html( $row['topic'] ); ?></td>
					<td><?php echo esc_html( wp_trim_words( $row['caption'], 28, '…' ) ); ?></td>
					<td><?php if ( ! empty( $row['image_url'] ) ) : ?><a href="<?php echo esc_url( $row['image_url'] ); ?>" target="_blank" rel="noopener">Ver</a><?php endif; ?></td>
					<td><code><?php echo esc_html( $row['media_id'] ); ?></code></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
