<?php
/**
 * Productos relacionados dinámicos del blog 0.10.277.
 *
 * Convierte los shortcodes históricos de WooCommerce en bloques resueltos en
 * tiempo de renderizado y añade el mismo bloque automáticamente a las entradas
 * que no tenían shortcode. La categoría comercial se obtiene, por prioridad,
 * del shortcode histórico, de un override opcional de la entrada o de su
 * categoría editorial. Si la categoría/productos todavía no existen, no se
 * imprime nada; cuando existan, aparecen sin editar la entrada.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indica si la petición actual está usando la versión inglesa del sitio.
 */
function elmercado_blog_products_is_english_010277(): bool {
	/*
	 * Falang puede servir /en/ manteniendo el locale base de WordPress en es_ES.
	 * La ruta pública es la señal autoritativa cuando se conoce. No permitimos
	 * que el locale/HTTP_REFERER marquen inglés para una URL española.
	 */
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	if ( '' !== $path ) {
		return 1 === preg_match( '~^/en(?:/|$)~i', $path );
	}

	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	return 0 === strpos( strtolower( (string) $locale ), 'en' );
}

/**
 * Usa el nombre de la categoría comercial traducido en Falang cuando existe.
 * Si el término no tiene traducción publicada, deja la etiqueta original.
 */
function elmercado_blog_product_term_name_010277( WP_Term $term ): string {
	if ( ! elmercado_blog_products_is_english_010277() ) {
		return (string) $term->name;
	}
	if ( '1' !== (string) get_term_meta( (int) $term->term_id, '_en_US_published', true ) ) {
		return (string) $term->name;
	}
	$translated = trim( (string) get_term_meta( (int) $term->term_id, '_en_US_name', true ) );
	return '' !== $translated ? $translated : (string) $term->name;
}

/**
 * Mapa único de correspondencia: categoría editorial/alias -> product_cat.
 *
 * Este es el único punto que hay que ampliar si en el futuro una categoría del
 * blog y su categoría comercial no comparten slug. Los slugs iguales se dejan
 * también explícitos para que el contrato sea fácil de auditar.
 *
 * Inventario de producción revisado el 2026-09-01:
 * - aceite / aceites              -> aceites
 * - carnes                        -> carnes
 * - conservas                     -> conservas
 * - embutidos / embutidos-y-curados -> embutidos-y-curados
 * - hortalizas-y-verduras         -> hortalizas-verduras
 * - jamones-y-paletas             -> jamones-paletas
 * - legumbres                     -> legumbres
 * - naranjas                      -> naranjas
 * - packs-y-lotes                 -> packs-y-lotes
 * - quesos                        -> quesos
 *
 * @return array<string,string>
 */
function elmercado_blog_product_category_map_010277(): array {
	$map = array(
		'aceite'                 => 'aceites',
		'aceites'                => 'aceites',
		'carnes'                 => 'carnes',
		'conservas'              => 'conservas',
		'embutidos'              => 'embutidos-y-curados',
		'embutidos-y-curados'    => 'embutidos-y-curados',
		'hortalizas-y-verduras'  => 'hortalizas-verduras',
		'hortalizas-verduras'    => 'hortalizas-verduras',
		'jamones-y-paletas'      => 'jamones-paletas',
		'jamones-paletas'        => 'jamones-paletas',
		'legumbres'              => 'legumbres',
		'naranjas'               => 'naranjas',
		'packs-y-lotes'          => 'packs-y-lotes',
		'quesos'                 => 'quesos',
		'wagyu'                  => 'wagyu',
		'pato'                   => 'pato',
		'productos-de-pato'      => 'pato',
		'foie-pates-y-untables'  => 'foie-pates-untables',
		'foie-pates-untables'    => 'foie-pates-untables',
		'chocolates-y-cacao'     => 'chocolates-artesanos',
		'chocolate'              => 'chocolates-artesanos',
		'chocolates-artesanos'   => 'chocolates-artesanos',
		'carnes-exoticas'        => 'carnes-exoticas',
	);

	$map = (array) apply_filters( 'elmercado_blog_product_category_map', $map );
	$normalized = array();
	foreach ( $map as $editorial_slug => $product_slug ) {
		$editorial_slug = sanitize_title( (string) $editorial_slug );
		$product_slug   = sanitize_title( (string) $product_slug );
		if ( '' !== $editorial_slug && '' !== $product_slug ) {
			$normalized[ $editorial_slug ] = $product_slug;
		}
	}

	return $normalized;
}

/**
 * Normaliza una categoría comercial y aplica el mapa de correspondencias.
 */
function elmercado_blog_filter_product_category_slug_010277( string $slug, int $post_id, string $explicit = '' ): string {
	$slug = sanitize_title( $slug );
	$map  = elmercado_blog_product_category_map_010277();
	if ( isset( $map[ $slug ] ) ) {
		$slug = $map[ $slug ];
	}
	$slug = (string) apply_filters( 'elmercado_blog_product_category_slug', $slug, $post_id, $explicit );
	return sanitize_title( $slug );
}

/**
 * Devuelve las categorías editoriales candidatas a categoría de producto.
 *
 * Primero conserva categorías directamente asignadas y después sus ancestros.
 * Las taxonomías editoriales genéricas se excluyen para que, por ejemplo, una
 * entrada de "Quesos" + "Nutrición" se vincule con Quesos.
 *
 * @return string[]
 */
function elmercado_blog_product_category_candidates_010277( int $post_id ): array {
	$ignored = array(
		'sin-categoria',
		'uncategorized',
		'blog',
		'actualidad',
		'noticias',
		'nutricion',
		'recetas',
		'guias',
		'guias-de-compra',
		'guias-y-consejos',
		'consejos',
		'comparativas',
		'gastronomia',
		'cocina-y-producto',
		'despensa',
	);
	$ignored = (array) apply_filters( 'elmercado_blog_noncommercial_categories', $ignored, $post_id );
	$ignored = array_values( array_unique( array_map( 'sanitize_title', $ignored ) ) );

	$categories = get_the_category( $post_id );
	if ( empty( $categories ) ) {
		return array();
	}

	$candidates = array();
	$append_term = static function ( $term ) use ( &$candidates, $ignored ): void {
		if ( ! $term instanceof WP_Term ) {
			return;
		}
		$slug = sanitize_title( (string) $term->slug );
		if ( '' === $slug || in_array( $slug, $ignored, true ) || in_array( $slug, $candidates, true ) ) {
			return;
		}
		$candidates[] = $slug;
	};

	/* Categorías directamente asignadas: máxima prioridad editorial. */
	foreach ( $categories as $category ) {
		$append_term( $category );
	}

	/* Los ancestros sirven de fallback si la entrada usa una subcategoría genérica. */
	foreach ( $categories as $category ) {
		if ( ! $category instanceof WP_Term ) {
			continue;
		}
		foreach ( get_ancestors( (int) $category->term_id, 'category', 'taxonomy' ) as $ancestor_id ) {
			$append_term( get_term( (int) $ancestor_id, 'category' ) );
		}
	}

	return $candidates;
}

/**
 * Detecta la intención comercial real del artículo, sin cambiar su taxonomía
 * editorial. Los subtipos tienen preferencia sobre categorías genéricas.
 */
function elmercado_blog_precise_product_category_20261019( int $post_id ): string {
	if ( $post_id <= 0 ) {
		return '';
	}
	$terms = get_the_category( $post_id );
	$slugs = array();
	foreach ( (array) $terms as $term ) {
		if ( $term instanceof WP_Term ) {
			$slugs[] = (string) $term->slug;
			foreach ( get_ancestors( (int) $term->term_id, 'category', 'taxonomy' ) as $ancestor_id ) {
				$parent = get_term( (int) $ancestor_id, 'category' );
				if ( $parent instanceof WP_Term ) {
					$slugs[] = (string) $parent->slug;
				}
			}
		}
	}
	$category_text = strtolower( remove_accents( implode( ' ', $slugs ) ) );
	$title = strtolower( remove_accents( (string) get_the_title( $post_id ) . ' ' . (string) get_post_field( 'post_name', $post_id ) ) );
	$in_wagyu = str_contains( $category_text, 'wagyu' ) || preg_match( '/\b(wagyu|kobe|miyazaki|kagoshima|kuroge|bms)\b/u', $title );
	$in_chocolate = preg_match( '/chocolate|cacao/u', $category_text )
		|| preg_match( '/chocolate|cacao|bombon|ganache|praline|trufa|turron|bean.to.bar/u', $title );
	$in_duck = preg_match( '/\bpato\b|foie|magret|confit/u', $category_text )
		|| preg_match( '/pato|foie.gras|magret|confit|rillettes|micuit|mi.cuit|manchon/u', $title );

	if ( $in_wagyu ) {
		return 'wagyu';
	}
	if ( $in_chocolate ) {
		$subtypes = array(
			'cremas-de-cacao' => '/crema|untar|untable/u',
			'bombones-y-chocolatinas' => '/bombon|chocolatina|praline|ganache|trufa/u',
			'cacao-para-beber-y-cocinar' => '/chocolate.a.la.taza|chocolate.caliente|cacao.en.polvo|reposteria|cocinar|bebida|beber/u',
			'kits-y-experiencias' => '/\bkit\b|elabora.*chocolate|hacer.*chocolate/u',
			'chocolates-de-temporada' => '/turron|navidad|pascua/u',
			'packs-y-regalos' => '/regal|surtido|pack|lote/u',
			'tabletas-de-chocolate' => '/tableta|bean.to.bar|porcentaje.de.cacao|chocolate.negro|chocolate.con.leche/u',
		);
		foreach ( $subtypes as $slug => $pattern ) {
			if ( preg_match( $pattern, $title ) && get_term_by( 'slug', $slug, 'product_cat' ) ) {
				return $slug;
			}
		}
		return 'chocolates-artesanos';
	}
	if ( $in_duck ) {
		$subtypes = array(
			'magret-de-pato' => '/magret/u',
			'confit-de-pato' => '/confit|muslo.de.pato/u',
			'jamon-de-pato' => '/jamon.de.pato/u',
			'foie-gras-de-pato' => '/foie.gras|micuit|mi.cuit/u',
			'pate-mousse-rillettes-de-pato' => '/rillettes|mousse|pate.de.pato|parfait/u',
			'pato-fresco' => '/pato.fresco|pechuga.de.pato|manchon|molleja.de.pato/u',
		);
		foreach ( $subtypes as $slug => $pattern ) {
			if ( preg_match( $pattern, $title ) && get_term_by( 'slug', $slug, 'product_cat' ) ) {
				return $slug;
			}
		}
		if ( preg_match( '/foie|pat[eé]|untable/u', $category_text ) && get_term_by( 'slug', 'foie-pates-untables', 'product_cat' ) ) {
			return 'foie-pates-untables';
		}
		return 'pato';
	}
	return '';
}

/**
 * Elige fichas existentes, visibles y disponibles, ponderando el tema de la
 * guía. Nunca presenta Wagyu australiano como si fuera japonés.
 *
 * Devuelve NULL para categorías históricas a las que se aplica el flujo normal.
 * Devuelve [] para categorías especializadas sin referencias adecuadas.
 *
 * @return ?int[]
 */
function elmercado_blog_precise_product_ids_20261019( int $post_id, string $slug ): ?array {
	$special_roots = array( 'wagyu', 'pato', 'pato-fresco', 'magret-de-pato',
		'confit-de-pato', 'jamon-de-pato', 'foie-gras-de-pato',
		'pate-mousse-rillettes-de-pato', 'foie-pates-untables',
		'chocolates-artesanos', 'tabletas-de-chocolate', 'bombones-y-chocolatinas',
		'cremas-de-cacao', 'cacao-para-beber-y-cocinar', 'kits-y-experiencias',
		'chocolates-de-temporada', 'packs-y-regalos', 'carnes-exoticas' );
	if ( ! in_array( $slug, $special_roots, true ) ) {
		return null;
	}

	$title = strtolower( remove_accents( (string) get_the_title( $post_id ) ) );
	$is_wagyu = 'wagyu' === $slug;
	$is_comparison = $is_wagyu && preg_match( '/(japones|japon).*(australian|american|europeo)|(australian|american|europeo).*(japones|japon)/u', $title );
	$is_australian = $is_wagyu && ! $is_comparison && preg_match( '/wagyu.australian|australian.*wagyu/u', $title );
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		return array();
	}
	$ids = get_posts( array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'numberposts' => 240,
		'fields' => 'ids',
		'suppress_filters' => false,
		'tax_query' => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array( (int) $term->term_id ), 'include_children' => true ) ),
	) );
	$keywords = array( 'entrecot', 'solomillo', 'lomo alto', 'lomo bajo', 'picanha', 'yakiniku',
		'aguja', 'ribete', 'kagoshima', 'miyazaki', 'a4', 'a5', 'bms',
		'magret', 'confit', 'jamon', 'foie', 'micuit', 'mousse', 'rillettes',
		'manchon', 'molleja', 'tableta', 'bombon', 'chocolatina', 'crema',
		'turron', 'cacao', 'chocolate negro', 'chocolate con leche', 'kit', 'regalo',
		'avestruz', 'canguro', 'bisonte', 'bufalo', 'venado', 'alce', 'potro' );
	$ranked = array();
	foreach ( $ids as $id ) {
		$product = wc_get_product( (int) $id );
		if ( ! $product || ! $product->is_visible() || ! $product->is_in_stock() ) {
			continue;
		}
		$name = strtolower( remove_accents( $product->get_name() ) );
		$japanese = preg_match( '/japon|kagoshima|miyazaki|kobe/u', $name ) && ! str_contains( $name, 'australian' );
		$australian = str_contains( $name, 'australian' );
		if ( $is_wagyu && ! $is_comparison ) {
			if ( $is_australian ? ! $australian : ! $japanese ) {
				continue;
			}
		}
		$score = 0;
		foreach ( $keywords as $word ) {
			if ( str_contains( $title, $word ) && str_contains( $name, $word ) ) {
				$score += 24;
			}
		}
		if ( $is_wagyu ) {
			$score += $japanese ? 35 : ( $australian ? 8 : 0 );
			if ( str_contains( $title, 'a5' ) && ! str_contains( $title, 'a4' ) ) {
				$score += str_contains( $name, 'a5' ) ? 24 : -8;
			}
			if ( str_contains( $title, 'a4' ) && ! str_contains( $title, 'a5' ) ) {
				$score += str_contains( $name, 'a4' ) ? 28 : -10;
			}
		} elseif ( str_starts_with( $slug, 'chocolate' ) || str_contains( $slug, 'cacao' ) || str_contains( $slug, 'bombon' ) || str_contains( $slug, 'tabletas' ) ) {
			$score += preg_match( '/chocolate|cacao|bombon|tableta|turron|kit/u', $name ) ? 6 : 0;
		}
		$ranked[] = array( 'id' => (int) $id, 'score' => $score, 'japanese' => (bool) $japanese,
			'australian' => (bool) $australian, 'a4' => str_contains( $name, 'a4' ), 'a5' => str_contains( $name, 'a5' ) );
	}
	usort( $ranked, static function ( array $a, array $b ): int {
		return ( $b['score'] <=> $a['score'] ) ?: ( $b['id'] <=> $a['id'] );
	} );
	if ( $is_wagyu && $is_comparison ) {
		$both = array();
		foreach ( array( 'japanese', 'australian' ) as $side ) {
			foreach ( $ranked as $candidate ) {
				if ( $candidate[ $side ] && ! in_array( $candidate['id'], $both, true ) ) {
					$both[] = $candidate['id'];
					break;
				}
			}
		}
		foreach ( $ranked as $candidate ) {
			if ( count( $both ) >= 4 ) break;
			if ( ! in_array( $candidate['id'], $both, true ) ) $both[] = $candidate['id'];
		}
		return $both;
	}
	if ( $is_wagyu && str_contains( $title, 'a4' ) && str_contains( $title, 'a5' ) ) {
		$grades = array();
		foreach ( array( 'a4', 'a5' ) as $grade ) {
			foreach ( $ranked as $candidate ) {
				if ( $candidate[ $grade ] && ! in_array( $candidate['id'], $grades, true ) ) {
					$grades[] = $candidate['id'];
					break;
				}
			}
		}
		foreach ( $ranked as $candidate ) {
			if ( count( $grades ) >= 4 ) break;
			if ( ! in_array( $candidate['id'], $grades, true ) ) $grades[] = $candidate['id'];
		}
		return $grades;
	}
	return array_slice( array_column( $ranked, 'id' ), 0, 4 );
}

/**
 * Las miniaturas al pie del post no dependen de una segunda activación lazy
 * mediante JS de optimización: solo se cargan en modo eager estas 4 imágenes.
 */
add_filter( 'wp_get_attachment_image_attributes', static function ( array $attrs ): array {
	if ( empty( $GLOBALS['elmercado_blog_related_product_grid_20261019'] ) ) {
		return $attrs;
	}
	$attrs['loading'] = 'eager';
	$attrs['decoding'] = 'async';
	$attrs['fetchpriority'] = 'low';
	$attrs['sizes'] = '(max-width: 620px) 90vw, (max-width: 1100px) 45vw, 260px';
	return $attrs;
}, 999 );

/**
 * Resuelve la categoría WooCommerce que corresponde a una entrada.
 *
 * Orden:
 * 1) categoría declarada por un shortcode histórico;
 * 2) meta opcional _elmercado_product_category;
 * 3) categoría editorial mapeada que ya exista como product_cat;
 * 4) primera categoría editorial comercial mapeada aunque aún no exista.
 *
 * El último punto es el que permite publicar hoy artículos de Quesos y que el
 * bloque aparezca automáticamente cuando se cree la categoría/productos.
 */
function elmercado_blog_product_category_slug_010277( int $post_id, string $explicit = '' ): string {
	$explicit = trim( $explicit );
	$precise = elmercado_blog_precise_product_category_20261019( $post_id );
	/* Una guía temática gana a shortcodes antiguos genéricos (p. ej. carnes). */
	$generic = array( '', 'carnes', 'conservas', 'packs-y-lotes', 'foie-pates-untables', 'pato', 'chocolates-artesanos', 'wagyu' );
	if ( '' !== $precise && in_array( sanitize_title( $explicit ), $generic, true ) ) {
		if ( '' !== $explicit && ! in_array( sanitize_title( $explicit ), array( 'carnes', 'conservas', 'packs-y-lotes', 'foie-pates-untables' ), true ) ) {
			return elmercado_blog_filter_product_category_slug_010277( $explicit, $post_id );
		}
		$meta_override = sanitize_title( (string) get_post_meta( $post_id, '_elmercado_product_category', true ) );
		if ( '' === $meta_override ) {
			return $precise;
		}
	}
	if ( '' !== $explicit ) {
		$parts = array_values(
			array_filter(
				array_map(
					'sanitize_title',
					array_map( 'trim', explode( ',', $explicit ) )
				)
			)
		);

		/* Un bloque con varias categorías no tiene un destino único para el CTA. */
		if ( 1 !== count( $parts ) ) {
			return '';
		}

		return elmercado_blog_filter_product_category_slug_010277( $parts[0], $post_id, $explicit );
	}

	if ( $post_id > 0 ) {
		$override = sanitize_title( (string) get_post_meta( $post_id, '_elmercado_product_category', true ) );
		if ( '' !== $override ) {
			return elmercado_blog_filter_product_category_slug_010277( $override, $post_id );
		}
	}

	$candidates = elmercado_blog_product_category_candidates_010277( $post_id );
	if ( empty( $candidates ) ) {
		return '';
	}

	/* Si WooCommerce ya conoce alguna candidata tras mapearla, esa gana. */
	if ( taxonomy_exists( 'product_cat' ) ) {
		foreach ( $candidates as $candidate ) {
			$mapped_candidate = elmercado_blog_filter_product_category_slug_010277( $candidate, $post_id );
			if ( '' === $mapped_candidate ) {
				continue;
			}
			$term = get_term_by( 'slug', $mapped_candidate, 'product_cat' );
			if ( $term instanceof WP_Term ) {
				return $mapped_candidate;
			}
		}
	}

	/* Categoría futura: queda preparada y ya normalizada por el mapa. */
	return elmercado_blog_filter_product_category_slug_010277( $candidates[0], $post_id );
}

/**
 * Comprueba que el HTML de WooCommerce contiene realmente una rejilla.
 */
function elmercado_blog_products_html_has_loop_010277( string $html ): bool {
	if ( '' === trim( $html ) ) {
		return false;
	}

	return 1 === preg_match(
		'/<ul[^>]+class=(?:"[^"]*\bproducts\b[^"]*"|\'[^\']*\bproducts\b[^\']*\')|wc-block-grid__products|wc-block-product-template/isu',
		$html
	);
}

/**
 * Selección editorial acotada a la guía EN del hierro de ternera.
 *
 * Antes se elegían los 4 productos más populares de 'carnes', que podían ser
 * únicamente productos de pato. Estos SKU han sido contrastados con fichas
 * públicas de carne de vacuno/ternera. El ID se resuelve en runtime y solo
 * participa si WooCommerce confirma producto publicado, visible y con stock.
 *
 * Null = URL no seleccionada (conservar lógica anterior).
 * []   = URL seleccionada, pero ninguna ficha apta (no rellenar con pato).
 *
 * @return ?int[]
 */
function elmercado_blog_iron_beef_product_ids_010277(): ?array {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	if ( '/en/how-much-iron-in-beef/' !== trailingslashit( $path ) ) {
		return null;
	}

	if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$ids = array();
	// Caja de filetes y magro, magro para ragú y chuletón de vaca madurada.
	foreach ( array( '/filetes-magro-awr', '/magro-ragu-ternera-az', '29325' ) as $sku ) {
		$id = (int) wc_get_product_id_by_sku( $sku );
		if ( $id <= 0 || in_array( $id, $ids, true ) || 'publish' !== get_post_status( $id ) ) {
			continue;
		}
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_visible() || ! $product->is_in_stock() ) {
			continue;
		}
		$ids[] = $id;
	}

	return $ids;
}

/**
 * Renderiza un bloque de hasta cuatro productos de la categoría resuelta.
 *
 * @param array|string $atts Atributos del shortcode.
 */
function elmercado_blog_dynamic_products_shortcode_010277( $atts ): string {
	$atts = shortcode_atts(
		array(
			'category' => '',
			'heading'  => '1',
		),
		$atts,
		'elmercado_dynamic_products'
	);

	$post_id = (int) get_the_ID();
	$slug    = elmercado_blog_product_category_slug_010277( $post_id, (string) $atts['category'] );
	if ( '' === $slug || ! taxonomy_exists( 'product_cat' ) || ! shortcode_exists( 'products' ) ) {
		return '';
	}

	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	$term_link = get_term_link( $term );
	if ( is_wp_error( $term_link ) ) {
		return '';
	}

	/*
	 * Delegamos la selección a WooCommerce para respetar visibilidad de catálogo,
	 * proveedores desactivados y cualquier filtro comercial ya vigente en la web.
	 */
	$curated_iron_ids = elmercado_blog_iron_beef_product_ids_010277();
	$precise_ids = null === $curated_iron_ids ? elmercado_blog_precise_product_ids_20261019( $post_id, $slug ) : $curated_iron_ids;
	if ( null !== $precise_ids ) {
		if ( ! $precise_ids ) {
			return '';
		}
		$GLOBALS['elmercado_blog_related_product_grid_20261019'] = true;
		try {
			$products_html = do_shortcode( sprintf(
				'[products ids="%s" columns="4" orderby="post__in"]',
				implode( ',', array_map( 'intval', $precise_ids ) )
			) );
		} finally {
			unset( $GLOBALS['elmercado_blog_related_product_grid_20261019'] );
		}
	} else {
		$GLOBALS['elmercado_blog_related_product_grid_20261019'] = true;
		try {
			$products_html = do_shortcode( sprintf(
				'[products category="%s" limit="4" columns="4" orderby="popularity"]',
				esc_attr( $slug )
			) );
		} finally {
			unset( $GLOBALS['elmercado_blog_related_product_grid_20261019'] );
		}
	}

	/* Sin productos reales no existe ni sección, ni título, ni hueco, ni CTA. */
	if ( ! elmercado_blog_products_html_has_loop_010277( $products_html ) ) {
		return '';
	}

	$show_heading = ! in_array( strtolower( trim( (string) $atts['heading'] ) ), array( '0', 'false', 'no', 'off' ), true );
	$is_english   = elmercado_blog_products_is_english_010277();
	$heading      = $is_english ? 'Related products' : 'Productos relacionados';
	$term_name    = elmercado_blog_product_term_name_010277( $term );
	$label        = $is_english
		? sprintf( 'View all products in %s', $term_name )
		: sprintf( 'Ver todos los productos de %s', $term_name );

	$heading_html = $show_heading
		? sprintf( '<h2 class="emo-related-products-dynamic__title">%s</h2>', esc_html( $heading ) )
		: '';

	$cta = sprintf(
		'<div class="emo-product-category-more"><a class="emo-product-category-more__link" href="%1$s">%2$s <span aria-hidden="true">→</span></a></div>',
		esc_url( $term_link ),
		esc_html( $label )
	);

	return sprintf(
		'<section class="emo-related-products-dynamic" data-product-category="%1$s">%2$s%3$s%4$s</section>',
		esc_attr( $slug ),
		$heading_html,
		$products_html,
		$cta
	);
}
add_shortcode( 'elmercado_dynamic_products', 'elmercado_blog_dynamic_products_shortcode_010277' );

/**
 * Sustituye shortcodes históricos y prepara automáticamente entradas sin ellos.
 *
 * Es una función pura respecto al contexto de consulta para poder verificar la
 * migración en producción sin modificar el contenido almacenado en WordPress.
 */
function elmercado_blog_dynamic_products_transform_content_010277( string $content, int $post_id ): string {
	$has_dynamic = false !== strpos( $content, '[elmercado_dynamic_products' );
	$found_product_shortcode = false;

	if ( false !== strpos( $content, '[products' ) || false !== strpos( $content, '[product_category' ) ) {
		$regex = get_shortcode_regex( array( 'products', 'product_category' ) );
		$transformed = preg_replace_callback(
			'/' . $regex . '/s',
			static function ( array $matches ) use ( $post_id, &$found_product_shortcode ): string {
				/* Un shortcode escapado ([[products]]) es texto, no un bloque comercial. */
				if ( '[' === (string) $matches[1] && ']' === (string) $matches[6] ) {
					return (string) $matches[0];
				}

				$found_product_shortcode = true;
				$attributes = shortcode_parse_atts( (string) $matches[3] );
				$explicit   = is_array( $attributes ) && ! empty( $attributes['category'] )
					? (string) $attributes['category']
					: '';

				/* Sin una categoría inequívoca conservamos el shortcode original. */
				$slug = elmercado_blog_product_category_slug_010277( $post_id, $explicit );
				if ( '' === $explicit || '' === $slug ) {
					return (string) $matches[0];
				}

				return sprintf(
					'[elmercado_dynamic_products category="%s" heading="0"]',
					$slug
				);
			},
			$content
		);

		if ( is_string( $transformed ) ) {
			$content = $transformed;
		}
	}

	/*
	 * Las entradas sin bloque histórico reciben el marcador dinámico. Si hoy no
	 * hay categoría/productos, el shortcode devuelve cadena vacía. Cuando los
	 * haya, el mismo artículo empieza a mostrarlos automáticamente.
	 */
	if ( ! $has_dynamic && ! $found_product_shortcode ) {
		$slug = elmercado_blog_product_category_slug_010277( $post_id );
		if ( '' !== $slug ) {
			$content = rtrim( $content ) . sprintf(
				"\n\n[elmercado_dynamic_products category=\"%s\" heading=\"1\"]",
				$slug
			);
		}
	}

	return $content;
}

/**
 * Aplica la transformación sólo al cuerpo principal de una entrada individual.
 */
function elmercado_blog_dynamic_products_content_010277( $content ) {
	if ( is_admin() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	return elmercado_blog_dynamic_products_transform_content_010277( (string) $content, (int) get_the_ID() );
}
/* Debe ejecutarse antes del do_shortcode de WordPress (prioridad 11). */
add_filter( 'the_content', 'elmercado_blog_dynamic_products_content_010277', 9 );

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-product-category-more-010277">
			/* Escritorio: cuatro productos en una única fila cuando el bloque contiene cuatro. */
			html body.single-post main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
				grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
			}

			html body.single-post main#primary.emo-article-page .emo-related-products-dynamic__title {
				margin-top: clamp(34px, 5vw, 52px);
				margin-bottom: 22px;
			}

			html body.single-post main#primary.emo-article-page .emo-product-category-more {
				display: flex;
				justify-content: flex-end;
				width: min(1040px, calc(100vw - 80px));
				max-width: 1040px;
				margin: -26px 50% 42px;
				padding: 0;
				transform: translateX(-50%);
				box-sizing: border-box;
			}

			html body.single-post main#primary.emo-article-page .emo-product-category-more__link {
				display: inline-flex;
				align-items: center;
				gap: 0.42rem;
				font-weight: 700;
				line-height: 1.35;
				text-decoration: none;
			}

			html body.single-post main#primary.emo-article-page .emo-product-category-more__link:hover,
			html body.single-post main#primary.emo-article-page .emo-product-category-more__link:focus-visible {
				text-decoration: underline;
				text-underline-offset: 0.18em;
			}

			/* Tablet y portátil estrecho: dos columnas. */
			@media (max-width: 1100px) {
				html body.single-post main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}

				html body.single-post main#primary.emo-article-page .emo-product-category-more {
					width: 100%;
					max-width: 100%;
					margin: -18px 0 34px;
					transform: none;
				}
			}

			/* Móvil: una columna. */
			@media (max-width: 620px) {
				html body.single-post main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
					grid-template-columns: minmax(0, 1fr) !important;
				}

				html body.single-post main#primary.emo-article-page .emo-product-category-more {
					justify-content: center;
					margin-top: -14px;
				}
			}
		</style>
		<script id="elmercado-blog-related-images-20261019">
		(function () {
			'use strict';
			function restoreProductImages() {
				var images = document.querySelectorAll('.emo-related-products-dynamic ul.products img');
				if (!images.length) return;
				function activate(img) {
					img.loading = 'eager';
					img.style.opacity = '1';
					img.style.visibility = 'visible';
					var src = img.getAttribute('src') || '';
					var replacement = img.getAttribute('data-src') || img.getAttribute('data-lazy-src');
					var replacementSet = img.getAttribute('data-srcset') || img.getAttribute('data-lazy-srcset');
					if (replacement && (!src || src.indexOf('data:image/') === 0 || src.indexOf('placeholder') !== -1)) {
						img.setAttribute('src', replacement);
					}
					if (replacementSet && !img.getAttribute('srcset')) img.setAttribute('srcset', replacementSet);
				}
				if ('IntersectionObserver' in window) {
					var observer = new IntersectionObserver(function (entries) {
						entries.forEach(function (entry) {
							if (entry.isIntersecting) { activate(entry.target); observer.unobserve(entry.target); }
						});
					}, { rootMargin: '600px 0px' });
					images.forEach(function (img) { observer.observe(img); });
				} else {
					images.forEach(activate);
				}
			}
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', restoreProductImages, { once: true });
			} else {
				restoreProductImages();
			}
		}());
		</script>
		<?php
	},
	PHP_INT_MAX
);