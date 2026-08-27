<?php
/**
 * Plugin Name: MDO - Dynamic multilingual sitemaps
 * Description: Canonical dynamic XML sitemaps for public ES/EN pages, blog posts, blog categories, product categories and products.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MDO_Dynamic_Multilingual_Sitemaps_20260818' ) ) {
	final class MDO_Dynamic_Multilingual_Sitemaps_20260818 {
		private const INDEX_FILE           = 'sitemap_index.xml';
		private const PAGES_FILE           = 'mdo-sitemap-pages.xml';
		private const POSTS_FILE           = 'mdo-sitemap-posts.xml';
		private const BLOG_CATEGORIES_FILE = 'mdo-sitemap-blog-categories.xml';
		private const CATEGORIES_FILE      = 'mdo-sitemap-categories.xml';
		private const PRODUCTS_FILE        = 'mdo-sitemap-products.xml';

		/** @var int[]|null */
		private static $eligible_product_ids = null;

		public static function boot(): void {
			// WordPress core sitemap must not expose a second, less restrictive source.
			add_filter( 'wp_sitemaps_enabled', '__return_false', PHP_INT_MAX );

			// Intercept before SEO plugins/template rendering can serve their own XML.
			add_action( 'parse_request', array( __CLASS__, 'maybe_serve' ), -9999 );

			// Register these after all plugins/MU-plugins so this is the final robots/AIOSEO policy.
			add_action( 'plugins_loaded', array( __CLASS__, 'register_late_filters' ), PHP_INT_MAX );
		}

		public static function register_late_filters(): void {
			add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), PHP_INT_MAX, 2 );
			add_filter( 'aioseo_sitemap_indexes', array( __CLASS__, 'filter_aioseo_indexes' ), PHP_INT_MAX );
		}

		public static function maybe_serve(): void {
			$file = self::requested_filename();
			if ( '' === $file ) {
				return;
			}

			$redirects = array(
				'sitemap.xml'                  => self::INDEX_FILE,
				'wp-sitemap.xml'               => self::INDEX_FILE,
				'english-sitemap.xml'          => self::INDEX_FILE,
				'page-sitemap.xml'             => self::PAGES_FILE,
				'post-sitemap.xml'             => self::POSTS_FILE,
				'category-sitemap.xml'         => self::BLOG_CATEGORIES_FILE,
				'product-sitemap.xml'          => self::PRODUCTS_FILE,
				'product_cat-sitemap.xml'      => self::CATEGORIES_FILE,
				'product-category-sitemap.xml' => self::CATEGORIES_FILE,
				'product_tag-sitemap.xml'      => self::INDEX_FILE,
				'product-tag-sitemap.xml'      => self::INDEX_FILE,
				'post_tag-sitemap.xml'         => self::INDEX_FILE,
				'tag-sitemap.xml'              => self::INDEX_FILE,
			);

			if ( isset( $redirects[ $file ] ) ) {
				wp_safe_redirect( home_url( '/' . $redirects[ $file ] ), 301, 'MDO Dynamic Sitemaps' );
				exit;
			}

			switch ( $file ) {
				case self::INDEX_FILE:
					self::serve_index();
					break;
				case self::PAGES_FILE:
					self::serve_pages();
					break;
				case self::POSTS_FILE:
					self::serve_posts();
					break;
				case self::BLOG_CATEGORIES_FILE:
					self::serve_blog_categories();
					break;
				case self::CATEGORIES_FILE:
					self::serve_categories();
					break;
				case self::PRODUCTS_FILE:
					self::serve_products();
					break;
			}

			// Retire any historical SEO-plugin sitemap endpoint (posts, authors,
			// tags, paginated product maps, etc.) without leaving duplicate XML
			// sources discoverable from old Google crawls.
			if ( preg_match( '/(?:^|-)sitemap(?:[^\\/]*)\\.xml$/i', $file )
				|| preg_match( '/^[a-z0-9_-]+-sitemap[0-9]*\\.xml$/i', $file ) ) {
				wp_safe_redirect( home_url( '/' . self::INDEX_FILE ), 301, 'MDO Dynamic Sitemaps' );
				exit;
			}
		}

		private static function requested_filename(): string {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
			$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
			$path        = untrailingslashit( $path );
			return '' !== $path ? basename( $path ) : '';
		}

		private static function send_xml_headers(): void {
			status_header( 200 );
			nocache_headers();
			header( 'Content-Type: application/xml; charset=UTF-8' );
			header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true );
		}

		private static function serve_index(): void {
			self::send_xml_headers();
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
			foreach ( array( self::PAGES_FILE, self::POSTS_FILE, self::BLOG_CATEGORIES_FILE, self::CATEGORIES_FILE, self::PRODUCTS_FILE ) as $file ) {
				echo "  <sitemap><loc>" . self::xml( home_url( '/' . $file ) ) . "</loc></sitemap>\n";
			}
			echo '</sitemapindex>';
			exit;
		}

		private static function serve_pages(): void {
			$exclude = array();
			if ( function_exists( 'wc_get_page_id' ) ) {
				foreach ( array( 'cart', 'checkout', 'myaccount' ) as $wc_page ) {
					$page_id = (int) wc_get_page_id( $wc_page );
					if ( $page_id > 0 ) {
						$exclude[] = $page_id;
					}
				}
			}

			$pages = get_posts(
				array(
					'post_type'        => 'page',
					'post_status'      => 'publish',
					'posts_per_page'   => -1,
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'post__not_in'     => array_values( array_unique( $exclude ) ),
					'has_password'     => false,
					'suppress_filters' => true,
				)
			);

			self::urlset_open();
			foreach ( $pages as $page ) {
				if ( ! $page instanceof WP_Post || '' !== (string) $page->post_password ) {
					continue;
				}

				$es = get_permalink( $page );
				if ( ! is_string( $es ) || '' === $es ) {
					continue;
				}

				$en = self::english_post_url( $page );
				self::render_language_pair( $es, $en, self::post_lastmod( $page ) );
			}
			self::urlset_close();
		}

		private static function serve_posts(): void {
			$posts = get_posts(
				array(
					'post_type'        => 'post',
					'post_status'      => 'publish',
					'posts_per_page'   => -1,
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'has_password'     => false,
					'suppress_filters' => true,
				)
			);

			self::urlset_open();
			foreach ( $posts as $post ) {
				if ( ! $post instanceof WP_Post || '' !== (string) $post->post_password ) {
					continue;
				}

				$es = get_permalink( $post );
				if ( ! is_string( $es ) || '' === $es ) {
					continue;
				}

				$en = self::english_post_url( $post );
				self::render_language_pair( $es, $en, self::post_lastmod( $post ) );
			}
			self::urlset_close();
		}

		private static function serve_blog_categories(): void {
			$terms = self::eligible_blog_categories();

			self::urlset_open();
			foreach ( $terms as $term ) {
				$es = get_term_link( $term );
				if ( is_wp_error( $es ) || ! is_string( $es ) || '' === $es ) {
					continue;
				}

				// Blog category archives currently use one canonical public URL.
				self::render_language_pair( $es, '', '' );
			}
			self::urlset_close();
		}

		/** @return WP_Term[] */
		private static function eligible_blog_categories(): array {
			if ( function_exists( 'elmercado_blog_categories_010263' ) ) {
				$terms = elmercado_blog_categories_010263();
				return array_values(
					array_filter(
						(array) $terms,
						static function( $term ): bool {
							return $term instanceof WP_Term && 'category' === $term->taxonomy && (int) $term->count > 0;
						}
					)
				);
			}

			$terms = get_terms(
				array(
					'taxonomy'   => 'category',
					'hide_empty' => true,
					'orderby'    => 'term_id',
					'order'      => 'ASC',
				)
			);
			if ( is_wp_error( $terms ) ) {
				return array();
			}

			$default_id = (int) get_option( 'default_category' );
			$output     = array();
			foreach ( $terms as $term ) {
				if ( ! $term instanceof WP_Term || (int) $term->term_id === $default_id ) {
					continue;
				}
				if ( in_array( sanitize_title( $term->slug ), array( 'sin-categoria', 'uncategorized' ), true ) ) {
					continue;
				}
				$output[] = $term;
			}
			return $output;
		}

		private static function serve_products(): void {
			$ids = self::eligible_product_ids();

			self::urlset_open();
			foreach ( $ids as $product_id ) {
				$post = get_post( $product_id );
				if ( ! $post instanceof WP_Post ) {
					continue;
				}

				$es = get_permalink( $post );
				if ( ! is_string( $es ) || '' === $es ) {
					continue;
				}

				$en = self::english_post_url( $post );
				self::render_language_pair( $es, $en, self::post_lastmod( $post ) );
			}
			self::urlset_close();
		}

		private static function serve_categories(): void {
			$term_ids = self::eligible_product_category_ids();

			self::urlset_open();
			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id, 'product_cat' );
				if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
					continue;
				}

				$es = get_term_link( $term );
				if ( is_wp_error( $es ) || ! is_string( $es ) || '' === $es ) {
					continue;
				}

				$en = self::english_term_url( $term );
				self::render_language_pair( $es, $en, '' );
			}
			self::urlset_close();
		}

		/**
		 * Published, public WooCommerce products whose marketplace seller is active.
		 * Mirrors WooCommerce's "hide out of stock items" setting so the sitemap
		 * never advertises URLs the storefront intentionally hides.
		 *
		 * @return int[]
		 */
		private static function eligible_product_ids(): array {
			if ( is_array( self::$eligible_product_ids ) ) {
				return self::$eligible_product_ids;
			}

			$query = new WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'has_password'           => false,
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'suppress_filters'       => true,
				)
			);

			$hide_out_of_stock = 'yes' === (string) get_option( 'woocommerce_hide_out_of_stock_items', 'no' );
			$eligible = array();
			foreach ( array_map( 'intval', (array) $query->posts ) as $product_id ) {
				$post = get_post( $product_id );
				if ( ! $post instanceof WP_Post || '' !== (string) $post->post_password ) {
					continue;
				}

				if ( function_exists( 'elmercado_wcfm_product_is_from_disabled_vendor_010210' )
					&& elmercado_wcfm_product_is_from_disabled_vendor_010210( $product_id ) ) {
					continue;
				}

				if ( function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $product_id );
					if ( ! $product || 'hidden' === $product->get_catalog_visibility() ) {
						continue;
					}
					if ( $hide_out_of_stock && 'outofstock' === (string) $product->get_stock_status() ) {
						continue;
					}
				}

				$eligible[] = $product_id;
			}

			self::$eligible_product_ids = array_values( array_unique( $eligible ) );
			return self::$eligible_product_ids;
		}

		/**
		 * Product categories that contain at least one eligible product, plus their
		 * ancestors, excluding the complete internal MENTTA tree.
		 *
		 * @return int[]
		 */
		private static function eligible_product_category_ids(): array {
			$product_ids = self::eligible_product_ids();
			if ( ! $product_ids ) {
				return array();
			}

			$term_ids = wp_get_object_terms( $product_ids, 'product_cat', array( 'fields' => 'ids' ) );
			if ( is_wp_error( $term_ids ) ) {
				return array();
			}

			$all_ids = array_map( 'intval', (array) $term_ids );
			foreach ( $all_ids as $term_id ) {
				$ancestors = get_ancestors( $term_id, 'product_cat', 'taxonomy' );
				$all_ids   = array_merge( $all_ids, array_map( 'intval', (array) $ancestors ) );
			}

			$all_ids = array_values( array_unique( array_filter( $all_ids ) ) );

			$mentta_ids = function_exists( 'mdo_mentta_internal_term_ids' )
				? array_map( 'intval', (array) mdo_mentta_internal_term_ids() )
				: array();

			$all_ids = array_values( array_diff( $all_ids, $mentta_ids ) );
			sort( $all_ids, SORT_NUMERIC );
			return $all_ids;
		}

		private static function english_post_url( WP_Post $post ): string {
			if ( function_exists( 'mdoer_en_url' ) ) {
				$url = mdoer_en_url( $post );
				return is_string( $url ) ? $url : '';
			}

			$front = (int) get_option( 'page_on_front' );
			$shop  = (int) get_option( 'woocommerce_shop_page_id' );
			if ( $post->ID === $front ) {
				return home_url( '/en/' );
			}
			if ( $post->ID === $shop ) {
				return home_url( '/en/shop/' );
			}
			if ( '1' !== (string) get_post_meta( $post->ID, '_en_US_published', true ) ) {
				return '';
			}

			$slug = sanitize_title( (string) get_post_meta( $post->ID, '_en_US_post_name', true ) );
			if ( '' === $slug ) {
				return '';
			}

			return 'product' === $post->post_type
				? home_url( '/en/product/' . $slug . '/' )
				: home_url( '/en/' . $slug . '/' );
		}

		private static function english_term_url( WP_Term $term ): string {
			if ( function_exists( 'mdoer_term_en_url' ) ) {
				$url = mdoer_term_en_url( $term );
				return is_string( $url ) ? $url : '';
			}

			if ( 'product_cat' !== $term->taxonomy
				|| '1' !== (string) get_term_meta( $term->term_id, '_en_US_published', true ) ) {
				return '';
			}

			$slug = sanitize_title( (string) get_term_meta( $term->term_id, '_en_US_slug', true ) );
			return '' !== $slug ? home_url( '/en/product-category/' . $slug . '/' ) : '';
		}

		private static function post_lastmod( WP_Post $post ): string {
			$timestamp = (int) get_post_modified_time( 'U', true, $post );
			return $timestamp > 0 ? gmdate( DATE_W3C, $timestamp ) : '';
		}

		private static function urlset_open(): void {
			self::send_xml_headers();
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
		}

		private static function urlset_close(): void {
			echo '</urlset>';
			exit;
		}

		private static function render_language_pair( string $es, string $en, string $lastmod ): void {
			$es = esc_url_raw( $es );
			$en = esc_url_raw( $en );
			if ( '' === $es ) {
				return;
			}

			$alternates = array();
			if ( '' !== $en ) {
				$alternates = array(
					'es'        => $es,
					'en'        => $en,
					'x-default' => $es,
				);
			}

			self::render_url( $es, $lastmod, $alternates );
			if ( '' !== $en && $en !== $es ) {
				self::render_url( $en, $lastmod, $alternates );
			}
		}

		/** @param array<string,string> $alternates */
		private static function render_url( string $loc, string $lastmod, array $alternates ): void {
			echo "  <url>\n";
			echo '    <loc>' . self::xml( $loc ) . "</loc>\n";
			if ( '' !== $lastmod ) {
				echo '    <lastmod>' . self::xml( $lastmod ) . "</lastmod>\n";
			}
			foreach ( $alternates as $lang => $href ) {
				echo '    <xhtml:link rel="alternate" hreflang="' . self::xml( $lang ) . '" href="' . self::xml( $href ) . '" />' . "\n";
			}
			echo "  </url>\n";
		}

		private static function xml( string $value ): string {
			return htmlspecialchars( $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
		}

		public static function filter_robots_txt( string $output, bool $public ): string {
			if ( ! $public ) {
				return $output;
			}

			$output = (string) preg_replace( '/^\s*Sitemap:\s*.*(?:\r?\n|$)/mi', '', $output );
			return rtrim( $output ) . "\nSitemap: " . home_url( '/' . self::INDEX_FILE ) . "\n";
		}

		/**
		 * Remove the old English-only custom index entry if AIOSEO builds an index
		 * internally. Public sitemap aliases are redirected to our canonical index.
		 *
		 * @param mixed $indexes
		 * @return mixed
		 */
		public static function filter_aioseo_indexes( $indexes ) {
			if ( ! is_array( $indexes ) ) {
				return $indexes;
			}

			return array_values(
				array_filter(
					$indexes,
					static function( $index ): bool {
						if ( ! is_array( $index ) || empty( $index['loc'] ) ) {
							return true;
						}
						$path = (string) wp_parse_url( (string) $index['loc'], PHP_URL_PATH );
						return 'english-sitemap.xml' !== basename( untrailingslashit( $path ) );
					}
				)
			);
		}
	}

	MDO_Dynamic_Multilingual_Sitemaps_20260818::boot();
}
