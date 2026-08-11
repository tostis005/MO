<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sincroniza los extras detectados por EMDO con YITH WooCommerce Product Add-ons.
 *
 * Cada producto EMDO tiene como máximo un bloque YITH propio. El bloque se marca
 * mediante metadatos del producto y nunca se reutilizan ni modifican bloques
 * manuales que no pertenezcan a EMDO.
 */
final class MDO_YITH_Extras {
	private const BLOCK_META      = '_emdo_yith_wapo_block_id';
	private const ADDONS_META     = '_emdo_yith_wapo_addon_ids';
	private const LEGACY_ADDON    = '_emdo_yith_wapo_addon_id';
	private const SYNC_ERROR_META = '_emdo_yith_wapo_sync_error';
	private const BLOCK_PREFIX    = 'EMDO ·';

	public static function sync_product( int $product_id, array $payload ): void {
		if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
			return;
		}

		$groups = self::usable_groups( $payload );
		if ( ! $groups ) {
			self::remove_owned_block( $product_id );
			return;
		}

		if ( ! self::tables_available() ) {
			self::record_error( $product_id, 'YITH Product Add-ons no está disponible o sus tablas no existen.' );
			return;
		}

		$template = self::select_template();
		if ( ! $template ) {
			self::record_error( $product_id, 'No se encontró una plantilla de desplegable YITH compatible.' );
			return;
		}

		try {
			$block_id = self::upsert_block( $product_id, $template );
			$addon_ids = self::replace_addons( $block_id, $groups, $template );
			self::replace_associations( $block_id, $product_id );

			update_post_meta( $product_id, self::BLOCK_META, $block_id );
			update_post_meta( $product_id, self::ADDONS_META, wp_json_encode( $addon_ids ) );
			if ( $addon_ids ) {
				update_post_meta( $product_id, self::LEGACY_ADDON, (int) $addon_ids[0] );
			} else {
				delete_post_meta( $product_id, self::LEGACY_ADDON );
			}
			delete_post_meta( $product_id, self::SYNC_ERROR_META );
			clean_post_cache( $product_id );
			wp_cache_flush();
		} catch ( Throwable $error ) {
			// Los addons son una capa complementaria. No abortamos la sincronización
			// del producto, pero dejamos el error visible para poder reintentarlo.
			self::record_error( $product_id, $error->getMessage() );
		}
	}

	private static function usable_groups( array $payload ): array {
		$raw    = isset( $payload['extra_groups'] ) && is_array( $payload['extra_groups'] ) ? $payload['extra_groups'] : array();
		$groups = array();

		foreach ( $raw as $group ) {
			if ( ! is_array( $group ) || empty( $group['options'] ) || ! is_array( $group['options'] ) ) {
				continue;
			}
			$options = array();
			foreach ( $group['options'] as $option ) {
				if ( ! is_array( $option ) ) {
					continue;
				}
				$label = trim( html_entity_decode( (string) ( $option['label'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				if ( '' === $label ) {
					continue;
				}
				$price = isset( $option['price'] ) && is_numeric( $option['price'] ) ? (float) $option['price'] : 0.0;
				$options[] = array(
					'label'    => sanitize_text_field( $label ),
					'price'    => round( $price, 2 ),
					'disabled' => ! empty( $option['disabled'] ),
				);
			}
			if ( count( $options ) < 2 ) {
				continue;
			}
			$groups[] = array(
				'key'     => sanitize_key( (string) ( $group['key'] ?? 'extra' ) ),
				'label'   => sanitize_text_field( (string) ( $group['label'] ?? 'Opciones' ) ) ?: 'Opciones',
				'options' => $options,
			);
		}

		return $groups;
	}

	private static function tables_available(): bool {
		global $wpdb;
		foreach ( self::tables() as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			if ( $found !== $table ) {
				return false;
			}
		}
		return true;
	}

	private static function tables(): array {
		global $wpdb;
		return array(
			$wpdb->prefix . 'yith_wapo_blocks',
			$wpdb->prefix . 'yith_wapo_addons',
			$wpdb->prefix . 'yith_wapo_blocks_assoc',
		);
	}

	private static function select_template(): ?array {
		global $wpdb;
		list( $blocks, $addons ) = self::tables();
		$rows = $wpdb->get_results(
			"SELECT a.*, b.settings AS block_settings, b.name AS block_name FROM {$addons} a INNER JOIN {$blocks} b ON b.id = a.block_id ORDER BY a.id ASC LIMIT 100",
			ARRAY_A
		);

		foreach ( $rows as $row ) {
			$settings = maybe_unserialize( $row['settings'] ?? '' );
			if ( is_array( $settings ) && 'select' === (string) ( $settings['type'] ?? '' ) ) {
				$block_settings = maybe_unserialize( $row['block_settings'] ?? '' );
				if ( is_array( $block_settings ) ) {
					return array(
						'addon_settings' => $settings,
						'block_settings' => $block_settings,
					);
				}
			}
		}
		return null;
	}

	private static function owned_block_id( int $product_id ): int {
		global $wpdb;
		list( $blocks ) = self::tables();
		$block_id = (int) get_post_meta( $product_id, self::BLOCK_META, true );
		if ( $block_id <= 0 ) {
			return 0;
		}
		$name = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$blocks} WHERE id = %d", $block_id ) );
		return str_starts_with( $name, self::BLOCK_PREFIX ) ? $block_id : 0;
	}

	private static function upsert_block( int $product_id, array $template ): int {
		global $wpdb;
		list( $blocks ) = self::tables();
		$name = 'EMDO · Extras · ' . $product_id;
		$settings = $template['block_settings'];
		$settings['name'] = $name;
		$settings['priority'] = '1';
		$settings['rules'] = is_array( $settings['rules'] ?? null ) ? $settings['rules'] : array();
		$settings['rules']['show_in'] = 'products';
		$settings['rules']['show_in_products'] = array( (string) $product_id );
		$settings['rules']['show_in_categories'] = '';
		$settings['rules']['exclude_products'] = '';
		$settings['rules']['exclude_products_products'] = '';
		$settings['rules']['exclude_products_categories'] = '';
		$settings['rules']['show_to'] = 'all';
		$settings['rules']['show_to_user_roles'] = '';
		$settings['rules']['show_to_membership'] = '';

		$data = array(
			'user_id'             => null,
			'vendor_id'           => 0,
			'settings'            => maybe_serialize( $settings ),
			'priority'            => '1.00000',
			'visibility'          => 1,
			'last_update'         => current_time( 'mysql' ),
			'name'                => $name,
			'product_association' => 'products',
			'exclude_products'    => 0,
			'user_association'    => 'all',
			'exclude_users'       => 0,
		);
		$formats = array( '%d', '%d', '%s', '%f', '%d', '%s', '%s', '%s', '%d', '%s', '%d' );
		$block_id = self::owned_block_id( $product_id );

		if ( $block_id > 0 ) {
			$ok = $wpdb->update( $blocks, $data, array( 'id' => $block_id ), $formats, array( '%d' ) );
			if ( false === $ok ) {
				throw new RuntimeException( 'No se pudo actualizar el bloque YITH de EMDO: ' . $wpdb->last_error );
			}
			return $block_id;
		}

		$ok = $wpdb->insert( $blocks, $data, $formats );
		if ( false === $ok ) {
			throw new RuntimeException( 'No se pudo crear el bloque YITH de EMDO: ' . $wpdb->last_error );
		}
		return (int) $wpdb->insert_id;
	}

	private static function replace_addons( int $block_id, array $groups, array $template ): array {
		global $wpdb;
		list( , $addons ) = self::tables();
		$wpdb->delete( $addons, array( 'block_id' => $block_id ), array( '%d' ) );
		$ids = array();

		foreach ( $groups as $position => $group ) {
			$settings = $template['addon_settings'];
			$settings['type'] = 'select';
			$settings['title'] = self::upper( (string) $group['label'] );
			$settings['required'] = '';
			$settings['description'] = '';
			$settings['enable_rules'] = '';
			$settings['enable_rules_variations'] = '';
			$settings['conditional_logic_display'] = 'show';
			$settings['conditional_logic_display_if'] = 'all';
			$settings['conditional_rule_variations'] = '';
			$settings['conditional_rule_addon'] = array( 'empty' );
			$settings['conditional_rule_addon_is'] = array( '' );
			$settings['conditional_logic'] = array();

			$option_data = array(
				'default'       => array(),
				'addon_enabled' => array(),
				'label'         => array(),
				'description'   => array(),
				'image'         => array(),
				'price_method'  => array(),
				'price'         => array(),
				'price_sale'    => array(),
				'price_type'    => array(),
				'show_image'    => array(),
				'label_in_cart' => array(),
			);

			foreach ( $group['options'] as $index => $option ) {
				$price = (float) $option['price'];
				$option_data['default'][]       = 0 === $index ? 'yes' : 'no';
				$option_data['addon_enabled'][] = empty( $option['disabled'] ) ? 'yes' : 'no';
				$option_data['label'][]         = (string) $option['label'];
				$option_data['description'][]   = '';
				$option_data['image'][]         = '';
				$option_data['price_method'][]  = $price > 0 ? 'increase' : 'free';
				$option_data['price'][]         = wc_format_decimal( $price, 2 );
				$option_data['price_sale'][]    = '';
				$option_data['price_type'][]    = 'fixed';
				$option_data['show_image'][]    = 'no';
				$option_data['label_in_cart'][] = 'no';
			}

			$ok = $wpdb->insert(
				$addons,
				array(
					'block_id'    => $block_id,
					'settings'    => maybe_serialize( $settings ),
					'options'     => maybe_serialize( $option_data ),
					'priority'    => number_format( $position + 1, 5, '.', '' ),
					'visibility'  => 1,
					'last_update' => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%f', '%d', '%s' )
			);
			if ( false === $ok ) {
				throw new RuntimeException( 'No se pudo crear el desplegable YITH de EMDO: ' . $wpdb->last_error );
			}
			$ids[] = (int) $wpdb->insert_id;
		}

		return $ids;
	}

	private static function replace_associations( int $block_id, int $product_id ): void {
		global $wpdb;
		list( , , $assoc ) = self::tables();
		$ids = array( $product_id );
		$variation_ids = get_posts(
			array(
				'post_type'      => 'product_variation',
				'post_parent'    => $product_id,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		$ids = array_merge( $ids, array_map( 'intval', $variation_ids ) );
		$wpdb->delete( $assoc, array( 'rule_id' => $block_id ), array( '%d' ) );

		foreach ( array_values( array_unique( array_map( 'intval', $ids ) ) ) as $id ) {
			$ok = $wpdb->insert(
				$assoc,
				array( 'rule_id' => $block_id, 'object' => (string) $id, 'type' => 'product' ),
				array( '%d', '%s', '%s' )
			);
			if ( false === $ok ) {
				throw new RuntimeException( 'No se pudo asociar el bloque YITH de EMDO al producto/variación ' . $id . ': ' . $wpdb->last_error );
			}
		}
	}

	private static function remove_owned_block( int $product_id ): void {
		if ( ! self::tables_available() ) {
			return;
		}
		global $wpdb;
		list( $blocks, $addons, $assoc ) = self::tables();
		$block_id = self::owned_block_id( $product_id );
		if ( $block_id > 0 ) {
			$wpdb->delete( $assoc, array( 'rule_id' => $block_id ), array( '%d' ) );
			$wpdb->delete( $addons, array( 'block_id' => $block_id ), array( '%d' ) );
			$wpdb->delete( $blocks, array( 'id' => $block_id ), array( '%d' ) );
		}
		delete_post_meta( $product_id, self::BLOCK_META );
		delete_post_meta( $product_id, self::ADDONS_META );
		delete_post_meta( $product_id, self::LEGACY_ADDON );
		delete_post_meta( $product_id, self::SYNC_ERROR_META );
	}

	private static function record_error( int $product_id, string $message ): void {
		update_post_meta( $product_id, self::SYNC_ERROR_META, sanitize_text_field( $message ) );
		error_log( '[EMDO YITH] Producto ' . $product_id . ': ' . $message );
	}

	private static function upper( string $value ): string {
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $value, 'UTF-8' ) : strtoupper( $value );
	}
}
