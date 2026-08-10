<?php
/**
 * Copy final de Home 0.10.155.
 *
 * Sustituye únicamente los textos visibles de la portada. Mantiene intactos
 * estructura, componentes, enlaces, productos, imágenes y funcionalidades.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sustituye una única aparición de texto.
 *
 * @param string $html Documento HTML.
 * @param string $search Texto actual.
 * @param string $replace Texto nuevo.
 * @return string
 */
function elmercado_home_copy_replace_once_010155( string $html, string $search, string $replace ): string {
	$position = strpos( $html, $search );
	if ( false === $position ) {
		return $html;
	}

	return substr_replace( $html, $replace, $position, strlen( $search ) );
}

/**
 * Ejecuta una sustitución dentro de una sección concreta de la Home.
 *
 * @param string   $html Documento HTML.
 * @param string   $class Clase identificadora de la sección.
 * @param callable $callback Transformación textual.
 * @return string
 */
function elmercado_home_copy_section_010155( string $html, string $class, callable $callback ): string {
	$pattern = '~<section class="[^"]*\\b' . preg_quote( $class, '~' ) . '\\b[^"]*"[^>]*>.*?</section>~s';
	$result  = preg_replace_callback(
		$pattern,
		static function ( array $matches ) use ( $callback ): string {
			return (string) $callback( $matches[0] );
		},
		$html,
		1
	);

	return is_string( $result ) ? $result : $html;
}

/**
 * Aplica el copy aprobado sobre el HTML ya compuesto por las capas anteriores.
 *
 * @param string $html Documento HTML.
 * @return string
 */
function elmercado_home_copy_final_010155( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	/* 1. Franja superior. */
	$html = elmercado_home_copy_replace_once_010155( $html, 'Proveedores seleccionados con criterio', 'Productores seleccionados' );
	$html = elmercado_home_copy_replace_once_010155( $html, '>Confianza<', '>Compra segura<' );
	$html = elmercado_home_copy_replace_once_010155( $html, 'Envíos preparados desde el origen', 'Envíos desde origen' );

	/* 2 y 3. Hero principal y franja de tres conceptos. */
	$html = elmercado_home_copy_section_010155(
		$html,
		'emo-hero',
		static function ( string $section ): string {
			$section = preg_replace(
				'~(<span class="emo-kicker emo-kicker--light">).*?(</span>)~s',
				'$1UNA SELECCIÓN HECHA CON CRITERIO$2',
				$section,
				1
			) ?? $section;
			$section = preg_replace(
				'~(<h1>).*?(<em>).*?(</em></h1>)~s',
				'$1Seleccionamos cuidadosamente cada producto $2que ofrecemos.$3',
				$section,
				1
			) ?? $section;
			$section = preg_replace(
				'~(<div class="emo-hero__copy">.*?<p>).*?(</p>)~s',
				'$1Valoramos todo aquello que define su calidad y le aporta un carácter propio, para reunir productos que destacan por lo que son y por todo lo que hay detrás.$2',
				$section,
				1
			) ?? $section;
			$section = preg_replace(
				'~(<a class="emo-button emo-button--accent"[^>]*>).*?(</a>)~s',
				'$1Descubrir productos$2',
				$section,
				1
			) ?? $section;
			$section = preg_replace(
				'~(<a class="emo-button emo-button--ghost"[^>]*>).*?(</a>)~s',
				'$1Conocer a los productores$2',
				$section,
				1
			) ?? $section;

			$proof = preg_replace_callback(
				'~<div class="emo-hero__proof">.*?</div>~s',
				static function ( array $matches ): string {
					$content = $matches[0];
					$items   = array(
						array( 'Calidad', 'Como punto de partida.' ),
						array( 'Identidad', 'Porque buscamos algo propio.' ),
						array( 'Confianza', 'Para elegir con tranquilidad.' ),
					);

					foreach ( $items as $item ) {
						$content = preg_replace(
							'~(<span><strong>).*?(</strong>).*?(</span>)~s',
							'$1' . $item[0] . '$2' . $item[1] . '$3',
							$content,
							1
						) ?? $content;
					}

					return $content;
				},
				$section,
				1
			);

			return is_string( $proof ) ? $proof : $section;
		}
	);

	/* 4. Bloque 01 / 02 / 03. */
	$html = elmercado_home_copy_section_010155(
		$html,
		'emo-trust',
		static function ( string $section ): string {
			$items = array(
				'01' => array(
					'Productos elegidos',
					'Una propuesta cuidada en la que cada incorporación responde a un estándar de calidad y a un valor propio.',
				),
				'02' => array(
					'Productores con identidad',
					'Personas y proyectos que entienden su trabajo de una forma particular y tienen algo que aportar a lo que hacen.',
				),
				'03' => array(
					'Mucho por descubrir',
					'Queremos acercarte no solo el producto, sino también todo aquello que permite conocerlo, entenderlo y valorarlo mejor.',
				),
			);

			foreach ( $items as $number => $item ) {
				$pattern = '~(<article><span>' . preg_quote( $number, '~' ) . '</span><div><strong>).*?(</strong><p>).*?(</p></div></article>)~s';
				$section = preg_replace(
					$pattern,
					'$1' . $item[0] . '$2' . $item[1] . '$3',
					$section,
					1
				) ?? $section;
			}

			return $section;
		}
	);

	/* 5. Categorías. */
	$html = elmercado_home_copy_section_010155(
		$html,
		'emo-categories',
		static function ( string $section ): string {
			$section = preg_replace( '~(<span class="emo-kicker">).*?(</span>)~s', '$1EXPLORA NUESTRA SELECCIÓN$2', $section, 1 ) ?? $section;
			$section = preg_replace( '~(<h2>).*?(</h2>)~s', '$1Encuentra lo que buscas$2', $section, 1 ) ?? $section;
			$section = preg_replace(
				'~(<div class="emo-section-heading">.*?</div><p>).*?(</p>)~s',
				'$1Recorre las distintas categorías y descubre todo lo que forma parte de El Mercado de Origen.$2',
				$section,
				1
			) ?? $section;
			return $section;
		}
	);

	/* 6. Productos más elegidos. */
	$html = elmercado_home_copy_section_010155(
		$html,
		'emo-featured-products',
		static function ( string $section ): string {
			$section = preg_replace( '~(<span class="emo-kicker">).*?(</span>)~s', '$1LO MÁS ELEGIDO$2', $section, 1 ) ?? $section;
			$section = preg_replace( '~(<h2>).*?(</h2>)~s', '$1Los favoritos de nuestros clientes$2', $section, 1 ) ?? $section;
			$section = preg_replace(
				'~(<div class="emo-section-heading">.*?<p>).*?(</p>)~s',
				'$1Descubre los productos que más eligen quienes ya compran en El Mercado de Origen.$2',
				$section,
				1
			) ?? $section;
			$section = preg_replace(
				'~(<a class="emo-text-link"[^>]*>).*?(<svg)~s',
				'$1Ver todos los productos$2',
				$section,
				1
			) ?? $section;
			return $section;
		}
	);

	/* 7. Nuestro criterio. */
	$html = elmercado_home_copy_section_010155(
		$html,
		'emo-story',
		static function ( string $section ): string {
			$panel = preg_replace_callback(
				'~<div class="emo-story__panel">.*?</div>~s',
				static function ( array $matches ): string {
					$content = $matches[0];
					$content = preg_replace( '~(<span class="emo-kicker emo-kicker--light">).*?(</span>)~s', '$1NUESTRO CRITERIO$2', $content, 1 ) ?? $content;
					$content = preg_replace( '~(<h2>).*?(</h2>)~s', '$1La excelencia no responde a una sola fórmula.$2', $content, 1 ) ?? $content;
					$content = preg_replace(
						'~(<p>).*?(</p>)~s',
						'$1Cada producto puede tener una forma distinta de destacar. Por eso no buscamos una etiqueta concreta ni una única manera de hacer las cosas.<br><br>Valoramos cada propuesta por lo que es, por la calidad que ofrece y por la solidez de aquello que la hace especial.$2',
						$content,
						1
					) ?? $content;
					$content = preg_replace( '~(<a class="emo-text-link emo-text-link--light"[^>]*>).*?(<svg)~s', '$1Conoce el proyecto$2', $content, 1 ) ?? $content;
					return $content;
				},
				$section,
				1
			);
			if ( is_string( $panel ) ) {
				$section = $panel;
			}

			$items = array(
				'01' => array(
					'01 — CALIDAD',
					'Lo primero es el producto.',
					'Partimos de su calidad y de todo aquello que interviene en ella. Solo desde ahí valoramos si tiene sentido incorporarlo a nuestra selección.',
				),
				'02' => array(
					'02 — DIFERENCIACIÓN',
					'Tiene que haber algo más.',
					'Buscamos productos con atributos propios, capaces de aportar un valor diferencial.<br><br>No tiene que ser siempre el mismo. Pero tiene que existir.',
				),
				'03' => array(
					'03 — PRODUCTOR',
					'Importa quién está detrás.',
					'Conocer al productor nos permite entender mejor el producto: su forma de trabajar, el cuidado que dedica a su elaboración y aquello que define su manera de hacer las cosas.<br><br>Producto y productor forman parte de una misma elección.',
				),
			);

			foreach ( $items as $number => $item ) {
				$pattern = '~(<article><span>)' . preg_quote( $number, '~' ) . '(</span><h3>).*?(</h3><p>).*?(</p></article>)~s';
				$section = preg_replace(
					$pattern,
					'$1' . $item[0] . '$2' . $item[1] . '$3' . $item[2] . '$4',
					$section,
					1
				) ?? $section;
			}

			return $section;
		}
	);

	/* 8. Bloque para productores. */
	$html = elmercado_home_copy_section_010155(
		$html,
		'emo-vendor-cta',
		static function ( string $section ): string {
			$section = preg_replace( '~(<span class="emo-kicker">).*?(</span>)~s', '$1PARA PRODUCTORES$2', $section, 1 ) ?? $section;
			$section = preg_replace( '~(<h2>).*?(</h2>)~s', '$1Buscamos productores que tengan algo que aportar.$2', $section, 1 ) ?? $section;
			$section = preg_replace(
				'~(<p>).*?(</p>)~s',
				'$1Si cuidas lo que haces, trabajas con un estándar alto de calidad y crees que tus productos tienen algo que los distingue, queremos conocerte.$2',
				$section,
				1
			) ?? $section;
			$section = preg_replace(
				'~(<a class="emo-button emo-button--dark"[^>]*>).*?(</a>)~s',
				'$1Presenta tu propuesta$2',
				$section,
				1
			) ?? $section;
			return $section;
		}
	);

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		/* Capa exterior final: solo sustituye copy sobre el HTML ya construido. */
		ob_start( 'elmercado_home_copy_final_010155' );
	},
	-800
);
