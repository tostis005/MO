<?php
/**
 * Plugin Name: MDO Blog Product Images Repair 2026-08-31
 * Description: Guarantees a real product image for WooCommerce product cards embedded in blog articles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_blog_product_image_id_20260831( WC_Product $product ): int {
	$image_id = (int) $product->get_image_id();
	if ( $image_id <= 0 ) {
		$gallery = array_values( array_filter( array_map( 'intval', $product->get_gallery_image_ids() ) ) );
		if ( $gallery ) {
			$image_id = (int) $gallery[0];
		}
	}
	if ( $image_id <= 0 && $product->is_type( 'variation' ) ) {
		$parent = wc_get_product( $product->get_parent_id() );
		if ( $parent instanceof WC_Product ) {
			$image_id = (int) $parent->get_image_id();
		}
	}
	if ( $image_id <= 0 && $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child instanceof WC_Product && $child->get_image_id() ) {
				$image_id = (int) $child->get_image_id();
				break;
			}
		}
	}
	return $image_id;
}

function mdo_blog_product_image_url_20260831( WC_Product $product ): string {
	$image_id = mdo_blog_product_image_id_20260831( $product );
	if ( $image_id > 0 ) {
		$url = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
		if ( ! $url ) {
			$url = wp_get_attachment_image_url( $image_id, 'medium_large' );
		}
		if ( is_string( $url ) && '' !== trim( $url ) ) {
			return $url;
		}
	}
	return function_exists( 'wc_placeholder_img_src' ) ? (string) wc_placeholder_img_src( 'woocommerce_thumbnail' ) : '';
}

/**
 * Native responsive image hints for product cards (not full-size product gallery).
 * The browser chooses an existing WP/WooCommerce thumbnail, including WebP
 * candidates provided by the image plugin, instead of assuming 720px everywhere.
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attr, WP_Post $attachment, $size ): array {
		if ( is_admin() || 'woocommerce_thumbnail' !== $size ) {
			return $attr;
		}
		if ( is_singular( 'post' ) ) {
			$attr['sizes'] = '(max-width: 620px) 92vw, (max-width: 1100px) 46vw, 250px';
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			$attr['sizes'] = '(max-width: 599px) 46vw, (max-width: 991px) 30vw, 190px';
		} elseif ( is_front_page() ) {
			$attr['sizes'] = '(max-width: 767px) 75vw, (max-width: 1100px) 44vw, 260px';
		} else {
			$attr['sizes'] = '(max-width: 599px) 46vw, (max-width: 991px) 31vw, (max-width: 1400px) 24vw, 310px';
		}
		return $attr;
	},
	90,
	3
);

add_action(
	'woocommerce_before_shop_loop_item',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$url = mdo_blog_product_image_url_20260831( $product );
		if ( '' === $url ) {
			return;
		}

		if ( ! isset( $GLOBALS['mdo_blog_product_images_20260831'] ) || ! is_array( $GLOBALS['mdo_blog_product_images_20260831'] ) ) {
			$GLOBALS['mdo_blog_product_images_20260831'] = array();
		}

		$image_id = mdo_blog_product_image_id_20260831( $product );
		$source   = $image_id > 0 ? wp_get_attachment_image_src( $image_id, 'woocommerce_thumbnail' ) : false;
		$srcset   = $image_id > 0 ? wp_get_attachment_image_srcset( $image_id, 'woocommerce_thumbnail' ) : '';
		$GLOBALS['mdo_blog_product_images_20260831'][ (int) $product->get_id() ] = array(
			'url'    => esc_url_raw( $url ),
			'srcset' => is_string( $srcset ) ? $srcset : '',
			'sizes'  => '(max-width: 620px) 92vw, (max-width: 1100px) 46vw, 250px',
			'width'  => is_array( $source ) ? (int) $source[1] : 0,
			'height' => is_array( $source ) ? (int) $source[2] : 0,
			'alt'    => wp_strip_all_tags( $product->get_name() ),
			'link'   => esc_url_raw( $product->get_permalink() ),
		);
	},
	-9999
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<style id="mdo-blog-product-images-20260831-css">
			body.single-post ul.products li.product img.product-loop-image,
			body.single-post ul.products li.product img.attachment-woocommerce_thumbnail,
			body.single-post ul.products li.product img.wp-post-image,
			body.single-post ul.products li.product .product-loop-image-wrapper img,
			body.single-post ul.products li.product img.mdo-blog-related-product-image-20260831 {
				display: block !important;
				visibility: visible !important;
				opacity: 1 !important;
				max-width: 100% !important;
			}
			body.single-post ul.products li.product .product-loop-image-wrapper,
			body.single-post ul.products li.product .product-loop-image,
			body.single-post ul.products li.product .woocommerce-loop-product__link {
				visibility: visible !important;
				opacity: 1 !important;
			}
			body.single-post ul.products li.product .mdo-blog-related-product-media-20260831 {
				display: block !important;
				position: relative !important;
				width: 100% !important;
				margin: 0 0 12px !important;
				padding: 0 !important;
				overflow: hidden !important;
				background: #f5f3ef !important;
				text-decoration: none !important;
			}
			body.single-post ul.products li.product .mdo-blog-related-product-image-20260831 {
				width: 100% !important;
				height: auto !important;
				aspect-ratio: 1 / 1 !important;
				object-fit: cover !important;
			}
			/* Keep three-item recommendations compact on desktop as well as
			   four-item blog commerce blocks; mobile breakpoints stay unchanged. */
			@media (min-width: 1101px) {
				html body.single-post main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
					display: grid !important;
					grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
					gap: clamp(12px, 1.4vw, 20px) !important;
				}
				html body.single-post main#primary.emo-article-page .emo-article-content ul.products > li.product {
					float: none !important;
					clear: none !important;
					width: auto !important;
					max-width: none !important;
					min-width: 0 !important;
					margin: 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}

		$map = isset( $GLOBALS['mdo_blog_product_images_20260831'] ) && is_array( $GLOBALS['mdo_blog_product_images_20260831'] )
			? $GLOBALS['mdo_blog_product_images_20260831']
			: array();
		if ( ! $map ) {
			return;
		}
		?>
		<script id="mdo-blog-product-images-20260831-js">
		(() => {
			'use strict';
			const products = <?php echo wp_json_encode( $map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			const selectors = '.product-loop-image-wrapper img,img.product-loop-image,img.attachment-woocommerce_thumbnail,img.wp-post-image,.woocommerce-loop-product__link > img';
			const productId = (card) => {
				for (const cls of card.classList) {
					const match = /^post-(\d+)$/.exec(cls);
					if (match) return match[1];
				}
				return card.getAttribute('data-product_id') || card.getAttribute('data-product-id') || '';
			};
			// Do not overwrite working native images or remove their srcset.
			// Rewriting all images at the footer caused unnecessary fetches and
			// disabled browser selection of correctly sized responsive sources.
			const fallback = (card, img) => {
				const data = products[productId(card)];
				if (!data?.url || img?.dataset.mdoImageTried === '1') return;
				if (img instanceof HTMLImageElement && img.complete && img.naturalWidth > 40) return;
				if (!(img instanceof HTMLImageElement)) {
					if (card.querySelector('.mdo-blog-related-product-media-20260831')) return;
					const link = document.createElement('a');
					link.className = 'mdo-blog-related-product-media-20260831';
					link.href = data.link || card.querySelector('a[href]')?.href || '#';
					link.setAttribute('aria-label', data.alt ? 'Ver ' + data.alt : 'Ver producto');
					img = document.createElement('img');
					img.className = 'mdo-blog-related-product-image-20260831';
					link.appendChild(img);
					const target = card.querySelector('.product-loop-wrapper,.product-loop-inner') || card;
					target.insertBefore(link, target.firstChild);
				}
				img.dataset.mdoImageTried = '1';
				img.alt = data.alt || '';
				img.loading = 'eager'; // Only called for near-viewport or failed images.
				img.decoding = 'async';
				if (data.width && data.height) {
					img.width = data.width;
					img.height = data.height;
				}
				img.sizes = data.sizes || '250px';
				if (data.srcset) img.srcset = data.srcset;
				img.setAttribute('data-src', data.url);
				img.src = data.url;
				img.style.setProperty('display', 'block', 'important');
				img.style.setProperty('visibility', 'visible', 'important');
				img.style.setProperty('opacity', '1', 'important');
				card.classList.add('mdo-blog-product-image-repaired-20260831');
			};
			const observer = 'IntersectionObserver' in window
				? new IntersectionObserver((entries) => {
					for (const entry of entries) {
						if (!entry.isIntersecting) continue;
						observer.unobserve(entry.target);
						const card = entry.target;
						const img = card.querySelector(selectors);
						if (!img) {
							fallback(card, null);
						} else if (img.complete && img.naturalWidth < 40) {
							fallback(card, img);
						} else if (!img.getAttribute('src') || /^data:image\//i.test(img.getAttribute('src'))) {
							fallback(card, img);
						} else if (img.loading === 'lazy') {
							// Restore native loading if a third-party lazy loader never starts.
							img.loading = 'eager';
						}
					}
				}, { rootMargin: '350px 0px', threshold: 0.01 })
				: null;
			const prepare = (card) => {
				if (!(card instanceof Element) || !card.matches('li.product')) return;
				if (!products[productId(card)] || card.dataset.mdoImagePrepared === '1') return;
				card.dataset.mdoImagePrepared = '1';
				const img = card.querySelector(selectors);
				if (img instanceof HTMLImageElement) {
					img.addEventListener('error', () => fallback(card, img), { once: true });
				}
				if (observer) observer.observe(card);
				else if (!img || (img.complete && img.naturalWidth < 40)) fallback(card, img);
			};
			const scan = (root) => {
				if (root instanceof Element && root.matches('li.product')) prepare(root);
				root.querySelectorAll?.('ul.products li.product').forEach(prepare);
			};
			scan(document);
			const scope = document.querySelector('body.single-post main') || document.body;
			new MutationObserver((mutations) => {
				for (const mutation of mutations) {
					for (const node of mutation.addedNodes) {
						if (node.nodeType === Node.ELEMENT_NODE) scan(node);
					}
				}
			}).observe(scope, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
