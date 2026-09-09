<?php
/**
 * Plugin Name: MDO - ChatGPT discovery
 * Description: OpenAI/ChatGPT organic discovery endpoint and robots.txt policy, reusing the canonical MDO multilingual sitemaps.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MDO_ChatGPT_Discovery_20260909' ) ) {
	final class MDO_ChatGPT_Discovery_20260909 {
		private const SITEMAP_FILE = 'chatgpt-sitemap.xml';

		/**
		 * These are the canonical public MDO sitemap partitions. They already
		 * enforce the site's ES/EN, publication, vendor, stock and category rules.
		 * Keeping ChatGPT on the same URL set avoids a second eligibility model.
		 *
		 * @var string[]
		 */
		private const CHILD_SITEMAPS = array(
			'mdo-sitemap-pages.xml',
			'mdo-sitemap-posts.xml',
			'mdo-sitemap-blog-categories.xml',
			'mdo-sitemap-categories.xml',
			'mdo-sitemap-products.xml',
		);

		public static function boot(): void {
			// The canonical sitemap MU-plugin has a catch-all for historical sitemap
			// names at -9999. Serve the ChatGPT endpoint just before that handler.
			add_action( 'parse_request', array( __CLASS__, 'maybe_serve_sitemap' ), -10000 );

			// Register after all plugins so this is applied after the canonical MDO
			// robots.txt filter has normalised Sitemap directives.
			add_action( 'plugins_loaded', array( __CLASS__, 'register_late_filters' ), PHP_INT_MAX );
		}

		public static function register_late_filters(): void {
			add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), PHP_INT_MAX, 2 );
		}

		public static function maybe_serve_sitemap(): void {
			if ( self::SITEMAP_FILE !== self::requested_filename() ) {
				return;
			}

			status_header( 200 );
			nocache_headers();
			header( 'Content-Type: application/xml; charset=UTF-8' );
			header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true );

			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
			foreach ( self::CHILD_SITEMAPS as $file ) {
				echo '  <sitemap><loc>' . self::xml( home_url( '/' . $file ) ) . "</loc></sitemap>\n";
			}
			echo '</sitemapindex>';
			exit;
		}

		private static function requested_filename(): string {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
			$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
			$path        = untrailingslashit( $path );
			return '' !== $path ? basename( $path ) : '';
		}

		public static function filter_robots_txt( string $output, bool $public ): string {
			if ( ! $public ) {
				return $output;
			}

			// Do not touch GPTBot here: search/discovery and model-training controls
			// are intentionally separate. This plugin only opens ChatGPT search and
			// user-initiated ChatGPT access to public storefront/content URLs.
			$output = self::remove_agent_group( $output, 'OAI-SearchBot' );
			$output = self::remove_agent_group( $output, 'ChatGPT-User' );
			$output = (string) preg_replace(
				'/^\s*Sitemap:\s*' . preg_quote( home_url( '/' . self::SITEMAP_FILE ), '/' ) . '\s*(?:\r?\n|$)/mi',
				'',
				$output
			);

			$rules = array(
				'# OpenAI - ChatGPT Search discovery',
				'User-agent: OAI-SearchBot',
				'Allow: /',
				'Disallow: /wp-admin/',
				'Disallow: /wp-login.php',
				'Disallow: /cart/',
				'Disallow: /checkout/',
				'Disallow: /my-account/',
				'Disallow: /carrito/',
				'Disallow: /finalizar-compra/',
				'Disallow: /mi-cuenta/',
				'',
				'# OpenAI - user-initiated ChatGPT requests',
				'User-agent: ChatGPT-User',
				'Allow: /',
				'Disallow: /wp-admin/',
				'Disallow: /wp-login.php',
				'Disallow: /cart/',
				'Disallow: /checkout/',
				'Disallow: /my-account/',
				'Disallow: /carrito/',
				'Disallow: /finalizar-compra/',
				'Disallow: /mi-cuenta/',
				'',
				'Sitemap: ' . home_url( '/' . self::SITEMAP_FILE ),
			);

			return rtrim( $output ) . "\n\n" . implode( "\n", $rules ) . "\n";
		}

		private static function remove_agent_group( string $robots, string $agent ): string {
			$lines    = preg_split( '/\r?\n/', $robots );
			$output   = array();
			$skipping = false;

			if ( ! is_array( $lines ) ) {
				return $robots;
			}

			foreach ( $lines as $line ) {
				if ( preg_match( '/^\s*User-agent\s*:\s*(.+?)\s*$/i', $line, $matches ) ) {
					$current_agent = trim( (string) $matches[1] );
					$skipping      = 0 === strcasecmp( $current_agent, $agent );
				}

				if ( ! $skipping ) {
					$output[] = $line;
				}
			}

			return implode( "\n", $output );
		}

		private static function xml( string $value ): string {
			return htmlspecialchars( $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
		}
	}

	MDO_ChatGPT_Discovery_20260909::boot();
}
