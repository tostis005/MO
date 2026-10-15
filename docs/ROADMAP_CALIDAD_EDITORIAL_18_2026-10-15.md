# Roadmap de calidad editorial 2026-10-15 — 18 guías publicadas

**Alcance:** 18 artículos de carnes exóticas/recetas publicados en octubre de 2026. Revisión de contenido, versiones en español e inglés, enlaces y fotografías del blog. Fuera de alcance: productos, importaciones, categorías de WooCommerce y cambios en pedidos. Se preservarán las URLs canónicas.

## Criterios de calidad (no una plantilla de escritura)

1. Cada artículo responde a una intención de búsqueda específica y se escribe con estructura propia; no hay número obligatorio de H2, preguntas frecuentes ni palabras. Evitar repetir introducciones, conclusiones o párrafos de conservación genéricos.
2. Cada versión es una guía editorial completa por sí misma. No imponer correspondencia frase por frase entre inglés y español, pero no resumir ni omitir detalles prácticos esenciales en EN.
3. Indicar límites del conocimiento: especie vs corte, procedencia verificada, nutrición dependiente del producto y seguridad alimentaria según fuentes oficiales. En especial carne picada, caza y carnes no habituales.
4. Enlazado contextual ES/EN a artículos existentes que realmente cubran el tema y fuentes oficiales cuando respalden afirmaciones técnicas; sin dirigir a productos inexistentes.
5. Imagen destacada propia de cada artículo, pertinente a especie/plato, con licencia verificable, ALT descriptivo ES/EN y crédito si procede. No asignar foto falsa de otra carne o especie.
6. Publicación verificable: no duplicar posts, conservar slug, verificar cuerpo completo de cada idioma, títulos, enlaces, etiquetas de idioma e imágenes cuando existan.

## Contadores independientes — 72 unidades

- **E. Revisión profunda de español:** **18/18**.
- **I. Redacción editorial completa en inglés:** **18/18**.
- **F. Fotografía/ilustración específica comprobada y publicada:** **18/18**.
- **V. Verificación final bilingüe de cada entrada:** **18/18**.

Una intervención no se cuenta por preparar código: E/I/V se completan solo tras publicación y QA de la URL pública; F solo si la imagen distintiva está asignada y verificable.

## Orden de ejecución

| ID | Guía/objetivo |
|---|---|
| 01 | Carne de avestruz: características, compra y cocinado |
| 02 | Canguro: preparación de corte magro con ejemplo reproducible |
| 03 | Cocodrilo: experiencia, diferentes elaborados y seguridad |
| 04 | Bisonte frente a ternera: comparativa con criterios reales |
| 05 | Venado: cortes y sabores de caza |
| 06 | Jabalí: estofado probado, triquina y procedimiento |
| 07 | Hamburguesas exóticas: elección y seguridad de picados |
| 08 | Búfalo vs bisonte: especie y nomenclatura comercial |
| 09 | Solomillo de avestruz: receta con cantidades y pasos |
| 10 | Ancas de rana: receta al ajillo |
| 11 | Huevo de avestruz: medidas y preparación |
| 12 | Ragout de caza: cortes y técnicas |
| 13 | Cebra: información fiable sin afirmar procedencias |
| 14 | Alce: corte y cocina |
| 15 | Corzo, gamo y venado: diferencias zoológicas/gastronómicas |
| 16 | Hamburguesa de Wagyu japonés: composición y cocción |
| 17 | Entrecot Black Angus: técnica y comparación |
| 18 | Carnes exóticas para barbacoa: selección y seguridad |

## Evidencia y estados

- La publicación inicial de 28 intervenciones (10 mejoras + 18 nuevos) está terminada y documentada en `docs/SEO_BLOG_CARNES_EXOTICAS_ROADMAP_2026-10-15.md`.
- Auditoría de calidad previa en `docs/AUDITORIA_CALIDAD_BLOG_LATITUDES_2026-10-15.md`: inglés resumido y 18/18 entradas sin imagen destacada.
- Primera ejecución de calidad prevista: entradas 01–04; el contador se actualizará solo tras la verificación en vivo.

## Hito intermedio histórico — 15/10/2026

**28/72 unidades verificadas (38,9 %).** E 8/18 · I 8/18 · F 4/18 · V 8/18.

- **Textos 01–04:** las ocho versiones ES/EN sustituidas con enfoque editorial independiente, sin bloque FAQ obligatorio, enlaces contextuales y fuentes pertinentes. [WP-CLI y ocho URLs verificadas](https://github.com/tostis005/MO/actions/runs/38715992996).
- **Imágenes 01–04:** cuatro fotografías específicas del alimento, fuentes Wikimedia Commons con licencia Creative Commons comprobada, metadatos ALT ES/EN y créditos de autor y licencia visibles. [Cuatro imágenes verificadas en producción](https://github.com/tostis005/MO/actions/runs/38716747245).
- **Textos 05–08:** venado, estofado de jabalí, hamburguesas exóticas y búfalo/bisonte, reescritos ES/EN. Se han contrastado temperatura de triquina con AESAN y seguridad de carne picada con FSANZ/USDA. [Ocho URLs verificadas](https://github.com/tostis005/MO/actions/runs/38717829012).
- **Limpieza de presentación:** reejecución de créditos fotográficos para sustituir saltos de línea literales y facilitar repetición idempotente, mediante `.github/scripts/publish-exoticas-photos-20261015-01.php` (verificar workflow antes de cerrar la tarea técnica).

**Nota histórica:** este párrafo describía el estado intermedio de la ejecución y está superado por el cierre que aparece a continuación.

## Cierre de las 72 tareas — ejecutado y verificado

**72/72 = 100 %**. Español 18/18, inglés 18/18, imágenes editoriales 18/18, publicación bilingüe comprobada 18/18.

| Tanda | Alcance | Verificación |
|---|---|---|
| Textos 01–04 | Avestruz, canguro, cocodrilo, bisonte · 8 idiomas | https://github.com/tostis005/MO/actions/runs/38715992996 |
| Fotos 01–04 | 4 adjuntos individuales, créditos ES/EN | https://github.com/tostis005/MO/actions/runs/38716747245 |
| Textos 05–08 | Venado, jabalí, hamburguesas exóticas, búfalo/bisonte · 8 idiomas | https://github.com/tostis005/MO/actions/runs/38717829012 |
| Fotos 05–08 | 4 adjuntos individuales, créditos ES/EN | https://github.com/tostis005/MO/actions/runs/38719568232 |
| Textos 09–12 | Solomillo de avestruz, ancas de rana, huevo de avestruz, ragout · 8 idiomas | https://github.com/tostis005/MO/actions/runs/38719158663 |
| Textos 13–16 | Cebra, alce, corzo/gamo/venado, hamburguesa Wagyu · 8 idiomas | https://github.com/tostis005/MO/actions/runs/38720431612 |
| Textos 17–18 | Entrecot Black Angus, barbacoa exótica · 4 idiomas | https://github.com/tostis005/MO/actions/runs/38721051185 |
| Fotos 09–13 | 5 fotografías con licencia, autor y ALT ES/EN | https://github.com/tostis005/MO/actions/runs/38721383358 |
| Fotos 14–18 | 5 fotografías con licencia, autor y ALT ES/EN | https://github.com/tostis005/MO/actions/runs/38721598521 |

**Decisiones editoriales:** cada artículo se ha reescrito con intención y estructura propias, sin bloque de preguntas frecuentes obligatorio y con una versión en inglés desarrollada, no resumida. Se han conservado los slugs y añadido fuentes técnicas cuando procede. Para fotos de animales vivos o carnes ilustrativas, los pies distinguen explícitamente lo que muestran de un plato exacto o producto comercial. Todo el alcance ha sido exclusivamente editorial; sin modificaciones de productos o `product_cat` de WooCommerce.

### Control complementario de integridad (fuera de las 72 unidades)

Se ha añadido la auditoría `.github/workflows/audit-editorial-v2-final-18-20261015.yml` que comprueba directamente la base de datos de WordPress: 18 publicaciones bilingües completas, 18 imágenes únicas, títulos ALT y créditos, y enlaces internos canónicos. La primera ejecución identificó un enlace inglés antiguo de Black Angus a Wagyu y una aserción demasiado restrictiva sobre el estado `inherit` de los adjuntos de WordPress. **Estos controles técnicos no alteran el cómputo 72/72 de publicación; el cierre de esta auditoría complementaria exige reejecución exitosa.**
