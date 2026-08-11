<?php
/**
 * Copy definitivo de la Home 0.10.165.
 *
 * Sustituye exclusivamente textos visibles sobre el HTML final ya compuesto.
 * No altera estructura, estilos, imágenes, productos, enlaces ni comportamiento.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aplica una transformación dentro de una sección existente de la Home.
 *
 * @param string   $html Documento HTML.
 * @param string   $class Clase de la sección.
 * @param callable $callback Transformación textual.
 * @return string
 */
function elmercado_home_copy_section_010165( string $html, string $class, callable $callback ): string {
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
 * Sustituye el copy final solicitado manteniendo todos los componentes actuales.
 *
 * @param string $html Documento HTML completo.
 * @return string
 */
function elmercado_home_copy_definitive_010165( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	/* Barra superior: conserva iconos, spans y distribución. */
	$announcement = array(
		'Productos cuidadosamente seleccionados',
		'Conoce el origen de cada producto',
		'Directamente del productor a tu casa',
	);
	$html = preg_replace_callback(
		'~<div class="emo-announcement__inner"[^>]*>.*?</div>~s',
		static function ( array $matches ) use ( $announcement ): string {
			$index = 0;
			$result = preg_replace_callback(
				'~<span([^>]*)>(.*?)</span>~s',
				static function ( array $span ) use ( &$index, $announcement ): string {
					if ( ! isset( $announcement[ $index ] ) ) {
						return $span[0];
					}

					$icon = '';
					if ( preg_match( '~<svg\\b.*?</svg>~s', $span[2], $svg ) ) {
						$icon = $svg[0];
					}

					$text = $announcement[ $index ];
					$index++;

					return '<span' . $span[1] . '>' . $icon . $text . '</span>';
				},
				$matches[0],
				3
			);

			return is_string( $result ) ? $result : $matches[0];
		},
		$html,
		1
	) ?? $html;

	/* Hero, etiqueta de la selección y tres conceptos. */
	$html = elmercado_home_copy_section_010165(
		$html,
		'emo-hero',
		static function ( string $section ): string {
			$section = preg_replace_callback(
				'~<span class="emo-kicker emo-kicker--light">.*?</span>~s',
				static fn (): string => '<span class="emo-kicker emo-kicker--light">NUESTRA SELECCIÓN</span>',
				$section,
				1
			) ?? $section;

			$section = preg_replace_callback(
				'~<h1>.*?</h1>~s',
				static fn (): string => '<h1>Una forma distinta <em>de elegir.</em></h1>',
				$section,
				1
			) ?? $section;

			$hero_text = 'En El Mercado de Origen buscamos productores que han conseguido hacer de sus productos algo especial.<br><br>Por su trayectoria, su conocimiento, su vínculo con el origen o una manera de elaborar que aporta un valor diferencial.<br><br>Seleccionamos sus productos y los reunimos en un mismo lugar para acercarlos directamente hasta tu casa.';
			$section = preg_replace_callback(
				'~(<div class="emo-hero__copy">.*?<p>).*?(</p>)~s',
				static function ( array $matches ) use ( $hero_text ): string {
					return $matches[1] . $hero_text . $matches[2];
				},
				$section,
				1
			) ?? $section;

			$section = preg_replace_callback(
				'~(<a class="emo-button emo-button--accent"[^>]*>).*?(</a>)~s',
				static fn ( array $matches ): string => $matches[1] . 'Descubrir la selección' . $matches[2],
				$section,
				1
			) ?? $section;

			$concepts = array(
				array( 'Origen', 'Nos fijamos en la procedencia, en quién está detrás y en cómo se elabora cada producto.' ),
				array( 'Selección', 'Elegimos cuidadosamente los productos que incorporamos a El Mercado de Origen.' ),
				array( 'Directo', 'Del productor a tu casa, sabiendo siempre quién lo hace y de dónde viene.' ),
			);
			$section = preg_replace_callback(
				'~<div class="emo-hero__proof">.*?</div>~s',
				static function ( array $proof_match ) use ( $concepts ): string {
					$index = 0;
					$proof = preg_replace_callback(
						'~<span><strong>.*?</strong>.*?</span>~s',
						static function ( array $item ) use ( &$index, $concepts ): string {
							if ( ! isset( $concepts[ $index ] ) ) {
								return $item[0];
							}
							$current = $concepts[ $index ];
							$index++;
							return '<span><strong>' . $current[0] . '</strong>' . $current[1] . '</span>';
						},
						$proof_match[0],
						3
					);
					return is_string( $proof ) ? $proof : $proof_match[0];
				},
				$section,
				1
			) ?? $section;

			return $section;
		}
	);

	/* Bloques 01 / 02 / 03. */
	$html = elmercado_home_copy_section_010165(
		$html,
		'emo-trust',
		static function ( string $section ): string {
			$items = array(
				'01' => array(
					'La selección empieza en el productor.',
					'Buscamos productores que destacan dentro de lo que hacen y conocemos sus propuestas antes de incorporarlas a El Mercado de Origen.<br><br>Ese es el punto de partida de nuestra selección.',
				),
				'02' => array(
					'Seleccionamos lo que mejor representa cada propuesta.',
					'Una vez elegido el productor, revisamos su oferta y seleccionamos cuidadosamente, uno a uno, los productos que queremos incorporar a El Mercado de Origen.<br><br>Buscamos aquellos que aportan un valor propio y que encajan con el criterio de nuestra selección.',
				),
				'03' => array(
					'Sabes de quién viene.',
					'Cada producto mantiene el vínculo con quien lo produce.<br><br>Puedes conocer quién está detrás, dónde se elabora y qué caracteriza su trabajo, además de comprarlo directamente al productor.',
				),
			);

			foreach ( $items as $number => $copy ) {
				$pattern = '~(<article><span>' . preg_quote( $number, '~' ) . '</span><div><strong>).*?(</strong><p>).*?(</p></div></article>)~s';
				$section = preg_replace_callback(
					$pattern,
					static function ( array $matches ) use ( $copy ): string {
						return $matches[1] . $copy[0] . $matches[2] . $copy[1] . $matches[3];
					},
					$section,
					1
				) ?? $section;
			}

			return $section;
		}
	);

	/* Categorías. No se crea ningún botón nuevo: solo se renombra si ya existe. */
	$html = elmercado_home_copy_section_010165(
		$html,
		'emo-categories',
		static function ( string $section ): string {
			$section = preg_replace_callback( '~<span class="emo-kicker">.*?</span>~s', static fn (): string => '<span class="emo-kicker">DESCUBRE POR CATEGORÍAS</span>', $section, 1 ) ?? $section;
			$section = preg_replace_callback( '~<h2>.*?</h2>~s', static fn (): string => '<h2>Encuentra lo que buscas.</h2>', $section, 1 ) ?? $section;
			$section = preg_replace_callback(
				'~(<div class="emo-section-heading">.*?</div><p>).*?(</p>)~s',
				static fn ( array $matches ): string => $matches[1] . 'Explora todos los productos de El Mercado de Origen y recorre fácilmente cada una de nuestras categorías.' . $matches[2],
				$section,
				1
			) ?? $section;
			$section = preg_replace_callback(
				'~(<a class="emo-text-link"[^>]*>).*?(<svg)~s',
				static fn ( array $matches ): string => $matches[1] . 'Ver categorías' . $matches[2],
				$section,
				1
			) ?? $section;
			return $section;
		}
	);

	/* Los más elegidos. */
	$html = elmercado_home_copy_section_010165(
		$html,
		'emo-featured-products',
		static function ( string $section ): string {
			$section = preg_replace_callback( '~<span class="emo-kicker">.*?</span>~s', static fn (): string => '<span class="emo-kicker">LOS MÁS ELEGIDOS</span>', $section, 1 ) ?? $section;
			$section = preg_replace_callback( '~<h2>.*?</h2>~s', static fn (): string => '<h2>Los favoritos de nuestros clientes.</h2>', $section, 1 ) ?? $section;
			$section = preg_replace_callback(
				'~(<div class="emo-section-heading">.*?<p>).*?(</p>)~s',
				static fn ( array $matches ): string => $matches[1] . 'Descubre los productos que más se eligen en El Mercado de Origen.' . $matches[2],
				$section,
				1
			) ?? $section;
			$section = preg_replace_callback(
				'~(<a class="emo-text-link"[^>]*>).*?(<svg)~s',
				static fn ( array $matches ): string => $matches[1] . 'Ver todos' . $matches[2],
				$section,
				1
			) ?? $section;
			return $section;
		}
	);

	/* Cómo elegimos + criterios. */
	$html = elmercado_home_copy_section_010165(
		$html,
		'emo-story',
		static function ( string $section ): string {
			$panel_text = 'Puede estar en una trayectoria construida durante años, en el conocimiento de quien lo elabora, en su procedencia, en una materia prima especialmente cuidada o en una forma de producción ligada a la tradición.<br><br>Nos fijamos en aquello que aporta un valor real al producto y que hace que destaque dentro de su categoría.<br><br>No todos tienen que hacerlo de la misma manera. Esa es precisamente la riqueza de nuestra selección.';
			$panel = preg_replace_callback(
				'~<div class="emo-story__panel">.*?</div>~s',
				static function ( array $matches ) use ( $panel_text ): string {
					$content = $matches[0];
					$content = preg_replace_callback( '~<span class="emo-kicker emo-kicker--light">.*?</span>~s', static fn (): string => '<span class="emo-kicker emo-kicker--light">CÓMO ELEGIMOS</span>', $content, 1 ) ?? $content;
					$content = preg_replace_callback( '~<h2>.*?</h2>~s', static fn (): string => '<h2>Hay muchas formas de hacer un producto diferente.</h2>', $content, 1 ) ?? $content;
					$content = preg_replace_callback(
						'~(<p>).*?(</p>)~s',
						static fn ( array $paragraph ) => $paragraph[1] . $panel_text . $paragraph[2],
						$content,
						1
					) ?? $content;
					return $content;
				},
				$section,
				1
			);
			if ( is_string( $panel ) ) {
				$section = $panel;
			}

			$criteria = array(
				'01' => array(
					'ORIGEN',
					'El lugar también forma parte del producto.',
					'Hay productos profundamente ligados a su procedencia, a las materias primas de su entorno o a una tradición vinculada a un territorio.<br><br>Cuando ese origen aporta algo al producto, queremos ponerlo en valor.',
				),
				'02' => array(
					'SABER HACER',
					'Hay conocimientos que se construyen con los años.',
					'La experiencia, la especialización y el dominio de una elaboración pueden marcar una diferencia difícil de reproducir.<br><br>Valoramos ese conocimiento cuando forma parte esencial del resultado.',
				),
				'03' => array(
					'IDENTIDAD',
					'Productos con algo propio.',
					'También buscamos aquello que consigue que un producto tenga personalidad y se distinga dentro de su categoría.<br><br>Puede venir de la manera de elaborarlo, de una especialización concreta o de una forma particular de entender lo que se hace.',
				),
			);

			foreach ( $criteria as $number => $copy ) {
				$pattern = '~(<article><span aria-label=")[^"]*(">)' . preg_quote( $number, '~' ) . '(.*?</span></span><h3>).*?(</h3><p>).*?(</p></article>)~s';
				$section = preg_replace_callback(
					$pattern,
					static function ( array $matches ) use ( $number, $copy ): string {
						return $matches[1] . $number . ' — ' . $copy[0] . $matches[2]
							. $number . ' — ' . $copy[0] . '<span hidden> — ' . $copy[0] . '</span></span><h3>'
							. $copy[1] . $matches[4] . $copy[2] . $matches[5];
					},
					$section,
					1
				) ?? $section;
			}

			return $section;
		}
	);

	/* Para productores. */
	$html = elmercado_home_copy_section_010165(
		$html,
		'emo-vendor-cta',
		static function ( string $section ): string {
			$section = preg_replace_callback( '~<span class="emo-kicker">.*?</span>~s', static fn (): string => '<span class="emo-kicker">PARA PRODUCTORES</span>', $section, 1 ) ?? $section;
			$section = preg_replace_callback( '~<h2>.*?</h2>~s', static fn (): string => '<h2>¿Quieres formar parte de El Mercado de Origen?</h2>', $section, 1 ) ?? $section;
			$section = preg_replace_callback(
				'~(<p>).*?(</p>)~s',
				static fn ( array $matches ): string => $matches[1] . 'Si eres productor y crees que tus productos pueden encajar en nuestra selección, queremos conocer tu proyecto.<br><br>Cuéntanos qué haces y qué productos te gustaría incorporar.' . $matches[2],
				$section,
				1
			) ?? $section;
			$section = preg_replace_callback(
				'~(<a class="emo-button emo-button--dark"[^>]*>).*?(</a>)~s',
				static fn ( array $matches ): string => $matches[1] . 'Presenta tu propuesta' . $matches[2],
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

		/*
		 * Se abre antes que las capas históricas para ser el buffer exterior:
		 * su callback se ejecuta el último y fija únicamente el copy definitivo.
		 */
		ob_start( 'elmercado_home_copy_definitive_010165' );
	},
	-10000
);
