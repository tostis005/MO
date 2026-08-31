<?php
/**
 * Plugin Name: MDO Blog Product Images Repair 2026-08-31
 * Description: Guarantees a real product image for WooCommerce product cards embedded in blog articles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_blog_product_image_url_20260831( WC_Product $product ): string {
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

	if ( $image_id > 0 ) {
		$url = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
		if ( ! $url ) {
			$url = wp_get_attachment_url( $image_id );
		}
		if ( is_string( $url ) && '' !== trim( $url ) ) {
			return $url;
		}
	}

	return function_exists( 'wc_placeholder_img_src' ) ? (string) wc_placeholder_img_src( 'woocommerce_thumbnail' ) : '';
}

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

		$GLOBALS['mdo_blog_product_images_20260831'][ (int) $product->get_id() ] = array(
			'url'  => esc_url_raw( $url ),
			'alt'  => wp_strip_all_tags( $product->get_name() ),
			'link' => esc_url_raw( $product->get_permalink() ),
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
			const selectors = 'img.product-loop-image,img.attachment-woocommerce_thumbnail,img.wp-post-image,.product-loop-image-wrapper img';
			const productId = (card) => {
				for (const cls of card.classList) {
					const match = /^post-(\d+)$/.exec(cls);
					if (match) return match[1];
				}
				return card.getAttribute('data-product_id') || card.getAttribute('data-product-id') || '';
			};
			const repairCard = (card) => {
				if (!(card instanceof Element) || !card.matches('li.product')) return;
				const data = products[productId(card)];
				if (!data?.url) return;

				const native = card.querySelector(selectors);
				if (native instanceof HTMLImageElement) {
					native.setAttribute('src', data.url);
					native.removeAttribute('srcset');
					native.removeAttribute('sizes');
					native.setAttribute('data-src', data.url);
					native.setAttribute('alt', data.alt || '');
					native.style.setProperty('display', 'block', 'important');
					native.style.setProperty('visibility', 'visible', 'important');
					native.style.setProperty('opacity', '1', 'important');
					card.classList.add('mdo-blog-product-image-repaired-20260831');
					return;
				}

				if (card.querySelector('.mdo-blog-related-product-media-20260831')) return;
				const link = document.createElement('a');
				link.className = 'mdo-blog-related-product-media-20260831';
				link.href = data.link || card.querySelector('a[href]')?.href || '#';
				link.setAttribute('aria-label', data.alt ? `Ver ${data.alt}` : 'Ver producto');
				const img = document.createElement('img');
				img.className = 'mdo-blog-related-product-image-20260831';
				img.src = data.url;
				img.alt = data.alt || '';
				img.loading = 'lazy';
				img.decoding = 'async';
				link.appendChild(img);
				const target = card.querySelector('.product-loop-wrapper,.product-loop-inner') || card;
				target.insertBefore(link, target.firstChild);
				card.classList.add('mdo-blog-product-image-repaired-20260831');
			};
			const repair = (root = document) => {
				if (root instanceof Element && root.matches('li.product')) repairCard(root);
				root.querySelectorAll?.('body.single-post ul.products li.product').forEach(repairCard);
			};
			repair();
			window.setTimeout(repair, 250);
			window.setTimeout(repair, 1200);
			const scope = document.querySelector('body.single-post main') || document.body;
			new MutationObserver((mutations) => {
				for (const mutation of mutations) {
					for (const node of mutation.addedNodes) {
						if (node.nodeType === Node.ELEMENT_NODE) repair(node);
					}
				}
			}).observe(scope, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
