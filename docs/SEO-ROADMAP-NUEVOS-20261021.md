# Roadmap SEO 2026-10-21 — nuevas altas y calidad del inglés

## Regla de alcance
En español solo tocar el contenido de nuevas fichas de Ikore Kofi (café), Chocolates Artesanos Isabel, Tropitop/fruta y sus artículos/categorías expresamente relacionados. Sin cambio de URL, slug, canonicals, robots, enlaces masivos, sitemap, precios ni variaciones. En inglés se permite corregir otras páginas cuando exista diagnóstico verificable; nunca reescribir a ciegas ni traducir cifras sin validar.

## Línea base auditable (no equivale a incidencias)
| ID | Actividad | Universo | Confirmados inicialmente | Modificados verificados | Pendientes |
| --- | --- | ---: | ---: | ---: | --- |
| A | SEO de fichas comprables en 5 familias (café 6, aguacate 7, mango 8, cítricos 11, chocolates 79) | 111 | 36 EN sin ficha nativa en auditoría 2026-10-21; otras incidencias por depurar | 0 | 36 EN por comprobar/solucionar |
| B | Redacción de kits de Isabel (2 fichas detectadas) | 2 | 2 intros poco naturales | 0 (workflow lanzado) | 2 hasta QA |
| C | Descripciones repetidas: café de especialidad, cremas de cacao | 2 categorías ES | 2 duplicidades | 2 ajustes publicados por deploy y QA genérica; verificación exacta pendiente | 2 QA puntual |
| D | Artículos de lote nuevo y guías de chocolates | 24+30=54 entradas; bilingüe | incidencia de hreflang/H1/imagen corregida en lote de 24 según auditoría | lote 24 reparado anteriormente | QA final y revisión selectiva de 30 |
| E | Inglés global (excepción autorizada de alcance) | primero 48 URLs del lote + muestra amplia del resto | landing EN muestra texto ES; más defectos por auditoría | 0 | diagnóstico general + cambios acotados |
| F | Enlazado recíproco blog-producto-categoría | 19 URL ejemplo | 0 fallos en último QA documentado 19/19 | ya desplegado anteriormente | mantener; no reconstruir enlaces |
| G | QA final | URLs alteradas | — | 0 | validar tras cada despliegue |

## Evidencia y controles
- Auditoría de clústeres: `docs/AUDITORIA-CLUSTERS-CAFES-FRUTAS-CHOCOLATE-20261021.md`.
- Auditoría editorial: `docs/AUDITORIA-ARTICULOS-24-20261020.md`.
- Modificación categoría acotada: `mu-plugins/emdo-category-seo-description.php` commit `19affd57676b3435a6e927067409395e36d4f2e9`.
- Reparación sólo kits: `.github/scripts/repair-isabel-kit-copy-targeted-20261021.php`; ejecución con copia reversible y verificación de precio/stock/variantes.
- Auditoría inglés: `.github/workflows/audit-global-english-hreflang-20261021.yml` (solo lectura).

## Reglas de actualización
Anotar 'modificado verificado' únicamente cuando el QA de producción termine bien. No sumar trabajos de otras sesiones como cambios de esta ejecución. No equiparar fichas revisadas con fichas corregidas. No concluir que Google ha indexado contenidos sin datos de Search Console.
