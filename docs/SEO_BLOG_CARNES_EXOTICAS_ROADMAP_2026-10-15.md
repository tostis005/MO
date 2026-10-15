# Roadmap SEO del blog — carnes exóticas y Wagyu (15-10-2026)

## Alcance editorial (sin WooCommerce)

Optimización de artículos publicados del blog y publicación de guías nuevas en castellano e inglés. Se puede crear o asignar la **categoría editorial de WordPress** `category` Carnes exóticas, dependiente de Carnes. No se modifica `product_cat`, ningún producto, importadores ni fichas comerciales. Los artículos existentes conservan slug/URL y reciben mejoras incrementales idempotentes.

## Estado a fecha de incorporación de la última tanda

| Bloque | Hecho y verificado en producción | Pendiente de verificar |
|---|---:|---:|
| A · Optimización de artículos existentes | **10/10** | 0 |
| B · Publicación de artículos nuevos | **13/18** | 5 (lote 04) |
| **Total** | **23/28** | **5** |

Los contadores solo se cierran cuando el workflow del lote concluye en `success` y se observan `PUBLISHED_OK` para las URLs públicas y `HEALTH_OK` para las páginas principales.

## A · Diez artículos existentes — todos verificados

1. A01 `wagyu-japones-vs-australiano-diferencias` — publicado en lote 01.
2. A02 `comprar-wagyu-japones-online-que-comprobar` — publicado en lote 01.
3. A03 `que-corte-wagyu-elegir` — publicado en lote 01.
4. A04 `wagyu-vs-angus-diferencias-marmoleo-sabor` — publicado en lote 01.
5. A05 `que-significa-a5-plus-wagyu` — publicado en lote 02.
6. A06 `wagyu-kobe-diferencias` — publicado en lote 02.
7. A07 `que-significa-100-wagyu-etiqueta` — publicado en lote 02.
8. A08 `kobe-miyazaki-kagoshima-diferencias-wagyu-japones` — publicado en lote 02.
9. A09 `como-descongelar-wagyu-correctamente` — publicado en lote 03.
10. A10 `wagyu-yakiniku-teppanyaki-shabu-shabu-sukiyaki` — publicado en lote 03.

## B · Dieciocho guías SEO nuevas

| ID | Slug nuevo | Lote | Estado |
|---|---|---:|---|
| B01 | `carne-de-avestruz-sabor-propiedades-como-cocinarla` | 01 | Verificado |
| B02 | `como-cocinar-carne-de-canguro-tierna` | 01 | Verificado |
| B03 | `carne-de-cocodrilo-sabor-como-cocinar` | 01 | Verificado |
| B04 | `carne-de-bisonte-sabor-propiedades-diferencias-ternera` | 01 | Verificado |
| B05 | `carne-de-venado-sabor-cortes-como-cocinar` | 01 | Verificado |
| B06 | `estofado-de-jabali-receta-carne-tierna` | 02 | Verificado |
| B07 | `hamburguesas-carnes-exoticas-comparativa` | 02 | Verificado |
| B08 | `bufalo-vs-bisonte-diferencias-carne` | 02 | Verificado |
| B09 | `solomillo-de-avestruz-a-la-plancha-receta` | 03 | Verificado |
| B10 | `ancas-de-rana-al-ajillo-receta` | 03 | Verificado |
| B11 | `huevo-de-avestruz-peso-personas-como-cocinar` | 03 | Verificado |
| B12 | `ragout-carne-de-caza-cortes-coccion` | 03 | Verificado |
| B13 | `carne-de-cebra-sabor-origen-como-cocinar` | 03 | Verificado |
| B14 | `carne-de-alce-sabor-cortes-como-cocinar` | 04 | Pendiente de verificación |
| B15 | `corzo-gamo-venado-diferencias-carne` | 04 | Pendiente de verificación |
| B16 | `hamburguesa-wagyu-japones-carne-ingredientes-coccion` | 04 | Pendiente de verificación |
| B17 | `entrecot-black-angus-plancha-receta` | 04 | Pendiente de verificación |
| B18 | `carnes-exoticas-para-barbacoa-elegir-preparar` | 04 | Pendiente de verificación |

## Evidencias de producción

- **Lote 01:** [GitHub Actions verificado](https://github.com/tostis005/MO/actions/runs/38709638213), 5 nuevos + 4 mejoras. Blog category `carnes-exoticas`, term ID 499.
- **Lote 02:** [GitHub Actions verificado](https://github.com/tostis005/MO/actions/runs/38710537446), 3 nuevos + 4 mejoras.
- **Lote 03:** [GitHub Actions verificado](https://github.com/tostis005/MO/actions/runs/38711336931), 5 nuevos + 2 mejoras.
- **Lote 04:** `.github/workflows/publish-exoticas-editorial-20261015-04.yml`, estado pendiente hasta completar Action + QA público.

## Trabajos SEO posteriores, fuera del alcance de las 28 intervenciones

Estas tareas solo deben plantearse como una **auditoría complementaria separada**: monitorizar impresiones, consultas, canibalización y conversiones en Search Console; sustituir imágenes provisionales por recursos realmente adecuados/licenciados; comprobar visualmente móvil/escritorio; relacionar los artículos con productos reales cuando las categorías de la tienda y el inventario estén listos, sin inventar enlaces a artículos agotados.

La publicación de un artículo o un HTML 200 no garantiza resultados de posicionamiento: medir orgánico a medio plazo.
