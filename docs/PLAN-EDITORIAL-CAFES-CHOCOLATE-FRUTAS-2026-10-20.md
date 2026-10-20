# Plan editorial aprobado — Nuevos productores (20-10-2026)

Estado: **24 temas aprobados; 3 publicados y verificados en español e inglés; 21 pendientes**. Este archivo es la fuente persistente de control del trabajo en el repositorio MO (no sustituirlo por la memoria de una conversación).

## Reglas editoriales acordadas
- Publicar siempre versión española e inglesa con redacción natural, no traducción mecánica.
- Cada artículo tendrá una estructura propia según la intención de búsqueda: ningún número fijo de secciones, plantilla de encabezados, conclusión o FAQ repetida.
- Priorizar potencial de búsquedas y relación con una compra. No crear artículos para justificar un SKU, ni recetas hiperconcretas ni guías de etiquetas de productos que no vendemos.
- Contrastar antes de escribir contra las entradas publicadas en producción: evitar canibalización SEO y solapamientos con las 30 guías de chocolates (publicadas en octubre de 2026) y con guías existentes de hortalizas, tomates y verduras de temporada.
- Fuentes fidedignas, afirmaciones comprobables, utilidad práctica, títulos y metas únicos, enlaces contextualizados a artículos y categorías existentes.
- Enlazar únicamente productos y categorías reales; nunca mostrar una variante agotada como disponible ni prometer stock no verificado.
- Publicación en producción solamente después de preflight, control de duplicados, revisión de ambos idiomas y verificación pública de URLs.
- Este plan se divide en tandas para conservar calidad y permitir recuperación idempotente en caso de fallo.

## Títulos aprobados / cola

| Nº | Línea | Título ES | Estado |
|---:|---|---|---|
| 1 | Café | Cómo elegir el mejor café en grano para casa | **Publicado · ES/EN verificado** |
| 2 | Café | Café en grano o molido: diferencias y cuál comprar | Pendiente |
| 3 | Café | Café de especialidad: qué es y por qué merece la pena | Pendiente |
| 4 | Café | Qué café comprar para una cafetera italiana | Pendiente |
| 5 | Café | Qué café elegir para una cafetera superautomática | Pendiente |
| 6 | Café | Café arábica o robusta: diferencias y cuál es mejor | Pendiente |
| 7 | Café | Café natural, torrefacto o mezcla: diferencias y cuál elegir | Pendiente |
| 8 | Café | Qué café elegir para preparar un buen espresso en casa | Pendiente |
| 9 | Café | Tipos de tueste del café: claro, medio y oscuro | Pendiente |
| 10 | Chocolate | Cremas de cacao: diferencias, ingredientes y cómo elegir una buena | **Publicado · ES/EN verificado** |
| 11 | Aguacates | Tipos de aguacate: Hass, Bacon, Fuerte y sus diferencias | **Publicado · ES/EN verificado** |
| 12 | Aguacates | Cómo saber si un aguacate está maduro y cómo madurarlo | Pendiente |
| 13 | Aguacates | Cuándo es temporada de aguacates en España | Pendiente |
| 14 | Aguacates | Cómo conservar aguacates para que duren más | Pendiente |
| 15 | Mangos | Tipos de mango: variedades, diferencias y cuál elegir | Pendiente |
| 16 | Mangos | Cómo saber si un mango está maduro y cómo elegir uno bueno | Pendiente |
| 17 | Mangos | Cuándo es temporada de mango en España | Pendiente |
| 18 | Cítricos | Naranjas de mesa y de zumo: diferencias y cuáles comprar | Pendiente |
| 19 | Cítricos | Tipos de naranjas: variedades, sabor y cuál elegir | Pendiente |
| 20 | Cítricos | Temporada de naranjas y mandarinas en España: calendario y variedades | Pendiente |
| 21 | Frutas | Frutas de temporada en España: calendario mes a mes | Pendiente |
| 22 | Frutas | Comprar fruta online: cómo elegir fruta fresca y de calidad | Pendiente |
| 23 | Frutas | Frutas tropicales: cuáles son, a qué saben y cómo elegirlas | Pendiente |
| 24 | Frutas | Cajas de fruta de temporada a domicilio: cómo elegir y qué cantidad comprar | Pendiente |

## Tandas
- **Primera tanda publicada y verificada en producción el 20/10/2026:** nº 1 (café), nº 10 (cremas de cacao), nº 11 (variedades de aguacate).
- Informe de ejecución y QA de seis URLs: https://github.com/tostis005/MO/actions/runs/39485533109
- Nº 1: https://www.elmercadodeorigen.com/como-elegir-mejor-cafe-en-grano-para-casa/ · EN: https://www.elmercadodeorigen.com/en/how-to-choose-the-best-whole-bean-coffee-for-home/
- Nº 10: https://www.elmercadodeorigen.com/cremas-de-cacao-como-elegir-ingredientes-diferencias/ · EN: https://www.elmercadodeorigen.com/en/chocolate-spreads-how-to-choose-ingredients-and-differences/
- Nº 11: https://www.elmercadodeorigen.com/tipos-de-aguacate-hass-bacon-fuerte-diferencias/ · EN: https://www.elmercadodeorigen.com/en/avocado-varieties-hass-bacon-fuerte-differences/
- **Imágenes:** nº 10 y nº 11 usan fotografías de productos publicados; nº 1 ha utilizado imagen editorial genérica por falta de coincidencia segura con café comercializable. Revisar la fotografía del artículo de café en la siguiente tanda.
- Los restantes temas se producirán de forma progresiva, respetando este orden editorial o modificándolo con documentación del motivo.

## Comprobaciones previas
- Inventario de referencia: `.deploy-status/blog-related-products-inventory-20261019.json` (595 artículos), que debe contrastarse con producción en cada tanda.
- Las herramientas externas de volumen SEO no facilitaron cifras en esta sesión: NO asignar volúmenes imaginados ni afirmar que las prioridades están medidas.
