# Roadmap SEO del blog — carnes exóticas y Wagyu (2026-10-15)

## Alcance

Solo artículos, metadatos SEO y categorías editoriales de WordPress (`category`). Sin cambios en WooCommerce, sincronización de proveedores, taxonomías `product_cat`, ni productos de la tienda.

## Dos contadores persistentes

- A: **10 mejoras de artículos existentes**. A01–A04 incluidas en el lote de despliegue 01; A05–A10 pendientes.
- B: **18 artículos nuevos**. B01–B05 incluidos en el lote de despliegue 01; B06–B18 pendientes.
- **El estado publicadas solo se incrementa cuando el workflow y el HTML público se hayan verificado.** La inclusión en esta rama no equivale a una publicación.

## Lote 01 — rama `editorial/blog-carnes-exoticas-20261015`

| ID | Intervención | Estado antes de producción |
|---|---|---|
| A01 | Wagyu japonés vs australiano | Código preparado |
| A02 | Comprar Wagyu japonés online | Código preparado |
| A03 | Qué corte de Wagyu elegir | Código preparado |
| A04 | Wagyu vs Angus | Código preparado |
| B01 | Carne de avestruz: sabor y preparación | Texto ES/EN preparado |
| B02 | Carne de canguro: cómo cocinarla | Texto ES/EN preparado |
| B03 | Carne de cocodrilo: sabor y preparación | Texto ES/EN preparado |
| B04 | Carne de bisonte vs ternera | Texto ES/EN preparado |
| B05 | Carne de venado: sabor, cortes y preparación | Texto ES/EN preparado |

La categoría **Carnes exóticas** (`carnes-exoticas`) se crea como hija de **Carnes** solo si no existe; contiene únicamente entradas del blog. No se modifica ninguna categoría de tienda.

## Resto de la tarea A

A05 A5+, A06 Wagyu/Kobe, A07 100% Wagyu, A08 Kobe/Miyazaki/Kagoshima, A09 descongelación de Wagyu, A10 técnicas yakiniku/teppanyaki/shabu-shabu/sukiyaki.

## Resto de la tarea B

B06 Estofado de jabalí; B07 Hamburguesas de carnes exóticas; B08 Búfalo vs bisonte; B09 Solomillo de avestruz; B10 Ancas de rana al ajillo; B11 Huevo de avestruz; B12 Ragout de caza; B13 Carne de cebra; B14 Carne de alce; B15 Corzo, gamo y venado; B16 Hamburguesa japonesa de Wagyu; B17 Entrecot Black Angus; B18 Carnes exóticas para barbacoa.

## Política de seguridad y SEO

- Los artículos existentes conservan su slug y el contenido previo: se añade contenido diferenciable con marcador idempotente y se ajustan título/metadescripción.
- Los nuevos artículos comprueban conflictos de slugs antes de escribir, conservan los metadatos bilingües usados por Falang y se asignan a las categorías editoriales.
- Los productos relacionados de WooCommerce y su categorización **se revisarán por separado** cuando se importen; este lote no los modifica.
- No se promete posicionamiento, volumen de búsqueda ni un resultado de producción sin comprobar.
