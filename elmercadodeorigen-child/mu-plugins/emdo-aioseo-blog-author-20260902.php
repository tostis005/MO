<?php
/**
 * Plugin Name: EMDO AIOSEO Blog Author
 * Description: Uses El Mercado de Origen (Organization) as the Article author in AIOSEO schema and keeps evergreen dates out of the visible post header.
 * Version: 2026.09.02
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'aioseo_schema_output',
	static function ( $schema ) {
		if ( ! is_singular( 'post' ) || ! is_array( $schema ) ) {
			return $schema;
		}

		$organization_id = rtrim( home_url( '/' ), '/' ) . '/#organization';

		foreach ( $schema as $index => $graph ) {
			if ( ! is_array( $graph ) || empty( $graph['@type'] ) ) {
				continue;
			}

			$types = (array) $graph['@type'];
			$types = array_map( 'strtolower', $types );

			if ( array_intersect( $types, array( 'article', 'blogposting', 'newsarticle' ) ) ) {
				$schema[ $index ]['author'] = array( '@id' => $organization_id );
				continue;
			}

			if ( in_array( 'person', $types, true ) ) {
				$name = isset( $graph['name'] ) ? trim( wp_strip_all_tags( (string) $graph['name'] ) ) : '';
				$id   = isset( $graph['@id'] ) ? (string) $graph['@id'] : '';

				if ( 'El Mercado de Origen' === $name || str_contains( $id, '/author/admin-mercado/' ) ) {
					unset( $schema[ $index ] );
				}
			}
		}

		return array_values( $schema );
	},
	100
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<style id="emdo-evergreen-visible-date-20260902">
			.emo-article-hero__meta > span:first-of-type { display: none !important; }
		</style>
		<?php
	},
	100
);
