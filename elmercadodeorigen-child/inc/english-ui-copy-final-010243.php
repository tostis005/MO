<?php
/**
 * Final manual English copy layer for runtime UI generated outside normal
 * TranslatePress rendering (notably late JavaScript-injected controls).
 *
 * This file contains only human-authored deterministic translations. It does
 * not call any translation service and does not perform automatic translation.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_is_english_request_010243(): bool {
	global $TRP_LANGUAGE;

	if ( isset( $TRP_LANGUAGE ) && is_string( $TRP_LANGUAGE ) && '' !== $TRP_LANGUAGE ) {
		return 0 === strpos( strtolower( $TRP_LANGUAGE ), 'en' );
	}
	if ( function_exists( 'trp_get_current_language' ) ) {
		$language = trp_get_current_language();
		if ( is_string( $language ) && '' !== $language ) {
			return 0 === strpos( strtolower( $language ), 'en' );
		}
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	return 1 === preg_match( '#^/en(?:/|$)#i', $path );
}

/** @return array<string,string> */
function elmercado_manual_english_ui_map_010243(): array {
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
	);
}

add_filter(
	'gettext',
	static function ( string $translated, string $text, string $domain ): string {
		if ( ! elmercado_is_english_request_010243() ) {
			return $translated;
		}
		$map = elmercado_manual_english_ui_map_010243();
		return $map[ $text ] ?? $translated;
	},
	PHP_INT_MAX,
	3
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! elmercado_is_english_request_010243() ) {
			return;
		}
		$map = elmercado_manual_english_ui_map_010243();
		?>
		<script id="elmercado-manual-english-ui-copy-010243">
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
