<?php
/**
 * Manual multilingual copy layer for runtime UI generated outside the normal
 * translated post/term fields (notably late JavaScript-injected controls).
 *
 * The translations here are human-authored and deterministic. Language
 * detection prefers the URL/referrer so WooCommerce AJAX requests keep the
 * language of the storefront that initiated them, then falls back to Falang.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_current_language_slug_010245(): string {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	if ( preg_match( '#^/(en|pt|fr|it)(?:/|$)#i', $path, $matches ) ) {
		return strtolower( $matches[1] );
	}

	$referer      = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
	$referer_path = (string) wp_parse_url( $referer, PHP_URL_PATH );
	if ( preg_match( '#^/(en|pt|fr|it)(?:/|$)#i', $referer_path, $matches ) ) {
		return strtolower( $matches[1] );
	}

	if ( function_exists( 'falang_current_language' ) ) {
		$language = falang_current_language( 'slug' );
		if ( is_string( $language ) && '' !== $language ) {
			return strtolower( $language );
		}
	}

	return 'es';
}

function elmercado_is_english_request_010245(): bool {
	return 'en' === elmercado_current_language_slug_010245();
}

/** @return array<string,string> */
function elmercado_manual_english_ui_map_010245(): array {
	return array(
		'Buscar' => 'Search',
		'Filtros' => 'Filters',
		'Filtrar productos' => 'Filter products',
		'Cerrar filtros' => 'Close filters',
		'Filtros activos' => 'Active filters',
		'Filtros aplicados' => 'Applied filters',
		'Limpiar todo' => 'Clear all',
		'Categorías' => 'Categories',
		'Vendedor' => 'Seller',
		'Precio' => 'Price',
		'Recomendados' => 'Recommended',
		'Más populares' => 'Most popular',
		'Mejor valorados' => 'Top rated',
		'Más recientes' => 'Newest',
		'Menor precio' => 'Lowest price',
		'Mayor precio' => 'Highest price',
		'VISITAR' => 'VISIT',
		'Visitar' => 'Visit',
		'TU SELECCIÓN' => 'YOUR SELECTION',
		'Revisa tu carrito' => 'Review your cart',
		'Comprueba cantidades y productos antes de continuar. Verás el coste final y las opciones disponibles en el siguiente paso.' => 'Check quantities and products before continuing. You’ll see the final cost and available options in the next step.',
		'Pago protegido durante todo el proceso' => 'Secure payment throughout the process',
		'Información clara antes de confirmar' => 'Clear information before you confirm',
		'Atención cercana si necesitas ayuda' => 'Personal support if you need help',
		'Alimentación' => 'Feeding',
		'Calidad' => 'Quality',
		'Con DOP' => 'With PDO',
		'Curación' => 'Curing',
		'Denominación de origen' => 'Protected Designation of Origin',
		'Origen' => 'Origin',
		'Peso' => 'Weight',
		'Preparación' => 'Preparation',
		'Productor' => 'Producer',
		'Raza ibérica' => 'Iberian breed',
		'Tamaño' => 'Size',
		'Tipo de pieza' => 'Piece type',
		'Tipo de producto' => 'Product type',
		'Variedad' => 'Variety',
	);
}

add_filter(
	'gettext',
	static function ( string $translated, string $text, string $domain ): string {
		if ( ! elmercado_is_english_request_010245() ) {
			return $translated;
		}
		$map = elmercado_manual_english_ui_map_010245();
		return $map[ $text ] ?? $translated;
	},
	PHP_INT_MAX,
	3
);

add_filter(
	'woocommerce_attribute_label',
	static function ( string $label, string $name, $product ): string {
		if ( ! elmercado_is_english_request_010245() ) {
			return $label;
		}
		$map = elmercado_manual_english_ui_map_010245();
		return $map[ $label ] ?? $label;
	},
	PHP_INT_MAX,
	3
);

/* The storefront intentionally does not expose the WCFM Policies product tab. */
add_filter(
	'woocommerce_product_tabs',
	static function ( array $tabs ): array {
		unset( $tabs['wcfm_policies_tab'], $tabs['policies'] );
		return $tabs;
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! elmercado_is_english_request_010245() ) {
			return;
		}
		$map = elmercado_manual_english_ui_map_010245();
		?>
		<script id="elmercado-manual-english-ui-copy-010245">
		(() => {
			'use strict';
			const map = <?php echo wp_json_encode( $map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
			const skip = new Set(['SCRIPT','STYLE','NOSCRIPT','TEXTAREA']);
			const translateString = (value) => {
				if (typeof value !== 'string') return value;
				if (Object.prototype.hasOwnProperty.call(map, value)) return map[value];
				const filterCount = value.match(/^Filtros\s*\((\d+)\)$/u);
				if (filterCount) return `Filters (${filterCount[1]})`;
				const productCount = value.match(/^(\d+)\s+product$/u);
				if (productCount && productCount[1] !== '1') return `${productCount[1]} products`;
				return value;
			};
			const replaceTextNode = (node) => {
				if (!node || node.nodeType !== Node.TEXT_NODE || !node.parentElement || skip.has(node.parentElement.tagName)) return;
				const raw = node.nodeValue || '';
				const core = raw.trim();
				if (!core) return;
				const translated = translateString(core);
				if (translated === core) return;
				const lead = raw.match(/^\s*/u)?.[0] || '';
				const trail = raw.match(/\s*$/u)?.[0] || '';
				node.nodeValue = lead + translated + trail;
			};
			const translateElement = (el) => {
				if (!(el instanceof Element) || skip.has(el.tagName)) return;
				for (const attr of ['aria-label','title','placeholder','alt']) {
					if (!el.hasAttribute(attr)) continue;
					const current = el.getAttribute(attr) || '';
					const translated = translateString(current.trim());
					if (translated !== current.trim()) el.setAttribute(attr, translated);
				}
				for (const child of el.childNodes) {
					if (child.nodeType === Node.TEXT_NODE) replaceTextNode(child);
				}
			};
			const scan = (root) => {
				if (!root) return;
				if (root.nodeType === Node.TEXT_NODE) { replaceTextNode(root); return; }
				if (!(root instanceof Element || root instanceof Document || root instanceof DocumentFragment)) return;
				if (root instanceof Element) translateElement(root);
				const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
				let node;
				while ((node = walker.nextNode())) {
					if (node.nodeType === Node.TEXT_NODE) replaceTextNode(node);
					else translateElement(node);
				}
			};
			const start = () => {
				scan(document.body);
				const observer = new MutationObserver((mutations) => {
					for (const mutation of mutations) {
						if (mutation.type === 'characterData') replaceTextNode(mutation.target);
						for (const node of mutation.addedNodes) scan(node);
					}
				});
				observer.observe(document.body, {subtree:true, childList:true, characterData:true});
			};
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once:true});
			else start();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
