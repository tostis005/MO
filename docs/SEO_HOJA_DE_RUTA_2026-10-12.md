# Hoja de ruta SEO y monetización — El Mercado de Origen

**Fecha de partida:** 12-10-2026. **Fuente base:** exportación de Google Search Console (12-08-2026 a 10-10-2026, **60 días**; no son tres meses completos). **Repositorio oficial:** `tostis005/MO` (`main`). **PR inicial:** [#237](https://github.com/tostis005/MO/pull/237).

## Objetivos y reglas innegociables

1. Aumentar **clics orgánicos cualificados** en artículos existentes, con especial prioridad al **inglés** y su monetización publicitaria; en español, priorizar también consultas comerciales y venta real.
2. Cambios incrementales: **no alterar URLs, canónicas, hreflang, traducciones Falang, indexación ni estructura sin diagnóstico demostrado y plan de reversión**.
3. No sobreoptimizar artículos que ya captan bien tráfico: comparar **consulta × página × país × dispositivo** siempre que haya datos.
4. Priorizar precisión editorial y seguridad alimentaria; no crear consejos absolutos ni datos inventados. Revisión de hechos para artículos de salud/seguridad.
5. Publicidad: mantener el control actual por **país comprador/no comprador**, consentimiento y cumplimiento AdSense; nunca sortearlo para incrementar ingresos.
6. Cualquier cambio de código pasa por **rama, PR, CI, comprobación en staging y validación en producción**. No confundir PR abierta con despliegue.

## Línea base (exportación indicada)

| Segmento | URLs/entradas | Impresiones | Clics | Observación |
| --- | ---: | ---: | ---: | --- |
| Sitio completo | resumen Search Console | 1.87 M aprox. | 9.877 | Periodo 60 días; no representa exactamente 3 meses |
| URL inglesas dentro de primeras 1.000 páginas exportadas | 441 | 726.254 | 3.463 | Segmentación por prefijo `/en/`; conjunto parcial de URLs |
| Primera tanda seleccionada | **20** (16 EN / 4 ES) | **189.684** | **782** | 139.112 impresiones y 482 clics EN; 50.572 y 300 ES |

**Limitación crítica:** las tablas de consultas y páginas de la exportación son independientes; sin cruce por página no sabemos **qué consulta concreta** coloca una URL en una posición determinada. No atribuir impresiones de consulta a URL sin GSC `page+query`.

## Estado por fases y criterio de completitud

### F0 — Inventario y protección del tráfico [EN CURSO]

- [x] Interpretar el periodo real de la exportación y registrar línea base global y del inglés.
- [x] Localizar **repositorio correcto `tostis005/MO`** y la capa existente `seo-search-console-010313.php`, que ya optimiza 15 URLs.
- [x] Seleccionar 20 páginas reales que aparecen en Search Console y calcular su base agregada.
- [x] Comprobar que las **31 rutas usadas para enlazado de la PR** aparecen en las primeras 1.000 URLs de la exportación.
- [x] Confirmar que existen las páginas de producto `/producto/lenteja-pardina/` y `/producto/garbanzo-pedrosillano/`.
- [ ] Exportar (o consultar mediante acceso autorizado) **consultas filtradas por URL**, dispositivo y país de las 20 candidatas.
- [ ] Capturar rendimiento de cada URL y diagnóstico de indexación/canonical cuando exista acceso a GSC.

**Salida:** un inventario de consultas reales y riesgos antes de modificar más copy.

### F1 — Primera tanda: 20 artículos / 16 EN [EN REVISIÓN, PR #237]

- [x] Implementar 20 title/meta descriptions sin reescribir post_content ni tocar URLs.
- [x] Añadir relaciones entre guías sin repetir el mismo href si ya existe en el artículo.
- [x] Incluir enlaces contextuales a lenteja pardina y garbanzo pedrosillano, solo cuando estén visibles y disponibles.
- [x] Adaptar etiqueta «min de lectura» a «min read» en páginas inglesas.
- [x] Superar `Validate child theme` de GitHub Actions (ejecución 38295470801).
- [x] Revisión preliminar del diff, enlaces GSC y productos reales. H1 duplicado comprobado en la guía de garbanzos: guardarraíl al renderizado final para URL GSC nuevas y anteriores.
- [x] Revisar y acortar 8 títulos de la tanda que superaban 70 caracteres contando la marca (los 20 quedan ahora en 70 o menos; no es un límite impuesto por Google).
- [x] Corregir en el tema las etiquetas «min de lectura» y «Leer el artículo» de las tarjetas inglesas y «artículos» del contador de categorías.
- [x] Añadir pruebas PHP automáticas de alcance, idioma, longitud y H1, integradas en el workflow de validación (run **38298126806**, success).
- [x] Añadir `tests/seo-html-audit.py` con prueba offline y auditoría real de **5 URLs** (3 EN + 2 ES) que verifica title, description, H1, lang, canonical y avisos hreflang.
- [x] Integrar `--self-test` en CI y añadir `.github/workflows/seo-staging-html-audit.yml` de ejecución manual tras desplegar el código en staging.
- [x] Elevar a **error** en el auditor staging una canonical que apunte a otra ruta, en especial `/en/`→ES (evita publicar páginas inglesas no indexables sin advertencia). El CI también se ejecuta cuando se modifican los tests SEO.
- [x] Corregir en single.php los enlaces de vuelta al Journal para mantener `/en/journal/` en entradas inglesas.
- [x] Corregir detección de idioma en productos dinámicos del blog: Falang puede servir `/en/` con `determine_locale() == es_ES`; ahora se respeta la ruta y el nombre traducido de WooCommerce cuando Falang lo tiene publicado. Test PHP de EN/ES y nombres de categorías, CI **success** (run 38307700639).
- [x] Evitar tarjetas especiales **con fecha de vencimiento explícita y ya pasada** (inglés/español) al renderizar el blog; preservar vigentes y ambiguas. Test PHPUnit-independent en `tests/seo-expired-special-20261012.php`, incorporado en CI.
- [x] Ampliar la limpieza de H1 idénticos a **20 URLs candidatas adicionales en inglés**, sin alterar sus títulos SEO ni descriptions, y añadir test de alcance.
- [ ] Comprobar HTML real post-cambio en staging: meta etiquetas, enlaces, canónica, hreflang y AdSense. **Bloqueo actual:** `dev.elmercadodeorigen.com` devuelve HTTP 401 al verificador anónimo. Se ha preparado autenticación HTTPS Basic opcional por secretos, pero no se afirma que existan credenciales ni que staging esté desplegado. [Runbook de acceso, QA y orden de merges](./SEO_STAGING_PUBLICACION_RUNBOOK_2026-10-12.md).
- [ ] Verificación de HTML en **staging**: 3 URL EN + 2 ES, H1, meta description única, hreflang, canonical, idioma y enlaces.
- [ ] Aprobar y desplegar cuando las comprobaciones sean satisfactorias; verificar producción y monitorizar errores 404, 5xx y CWV.

**Salida:** PR #237 fusionada/desplegada **solo después** de los controles anteriores.

### F2 — Mejoras editoriales profundas en esas mismas 20 URLs [PENDIENTE]

- [ ] Revisar para cada URL intención principal y 3–5 consultas reales (cruce page+query).
- [ ] Revisar exactitud, primer bloque de respuesta, subtítulos, tablas comparativas útiles y preguntas frecuentes **solo si mejoran realmente la lectura**.
- [ ] Mejorar imágenes descriptivas/alt cuando estén incorrectos, sin sustituir media que ya esté funcionando por capricho.
- [ ] Revisar la versión inglesa **como artículo natural en inglés**, no traducción literal; verificar datos y unidades (US/UK cuando corresponda).
- [ ] Investigar las entradas con título/extracto español que figuran en las categorías `/en/` (ver [issue #239](https://github.com/tostis005/MO/issues/239)); no ocultarlas hasta verificar traducción, canonical/hreflang y datos GSC.
- [x] Fallback visual **únicamente para categorías editoriales Duck/Foie** en `/en/` cuando Falang no marca la traducción como publicada: H1, introducción, etiquetas de navegación y tarjetas EN. El contenido almacenado ES y sus URLs no se tocan; si Falang publica traducción tiene prioridad. Tests PHP integrados en CI.
- [x] Revisar guías EN ya desarrolladas de lavado de carne y mitos Wagyu: tienen respuestas claras/FAQ, mejor no reescribir indiscriminadamente. Wagyu aún muestra una imagen «Imagen provisional del blog…» que requiere reemplazo editorial real en WordPress, no modificación del tema.
- [x] Auditoría pública de muestra: `/en/category/meat/` mezcla ES/EN, `/en/category/carnes/pato/` y `/en/category/foie-pates-untables/` muestran encabezados y descripciones españoles; `/en/category/cheese/` y `/en/category/vegetables/` conservan etiquetas `min de lectura` antes del despliegue. Estos hallazgos requieren editar Falang/CMS, no forzar redirecciones.
- [ ] Evitar contenido duplicado y fragmentación entre guías similares. Añadir enlaces solo donde aporten.
- [ ] Editar en el **CMS de WordPress** mediante acceso autorizado: el repositorio del tema **no almacena el cuerpo de cada artículo**; no prometer que GitHub lo publica.
- [ ] Controlar una muestra móvil y de escritorio y comparar 7/28 días tras el cambio.

**Salida:** 20 contenidos enriquecidos, con auditoría de lo realmente publicado.

### F3 — Segundo lote de alto potencial, énfasis inglés [CÓDIGO EN DRAFT PR #241]

- [x] Preseleccionar 20 URLs EN (94.123 impresiones y 169 clics GSC) en [SEO_COHORTE_2_20_INGLES_2026-10-12.md](./SEO_COHORTE_2_20_INGLES_2026-10-12.md). Sin cambios de metadatos/contenido; **solo la corrección conservadora de H1 idénticos** añadida a la PR #237.
- [ ] Repriorizar las URLs con consultas por página, RPM publicitario y revisión editorial, excluyendo las optimizadas previamente.
- [ ] Hacer hipótesis específicas por consulta de mayor volumen y probar mejoras parciales.
- [ ] Validar primero aquellas con bajo CTR; **no tocar a ciegas** la guía inglesa de oxidación de frutas y verduras, que ya tiene un CTR ~3,2 % y 838 clics.
- [x] Preparar títulos y meta descriptions para las 20 URLs EN en PR [#241](https://github.com/tostis005/MO/pull/241), con prueba de alcance/longitud. CI **#38310535552 success**; no se ha fusionado ni publicado.
- [x] Preparar [brief editorial de cada URL de la tanda 2](https://github.com/tostis005/MO/blob/seo/gsc-20-english-batch2-20261012/docs/SEO_EDITORIAL_COHORTE_2_20_INGLES_2026-10-12.md) con pautas food safety y enlaces candidatos. No se han editado post_content ni traducciones Falang.
- [ ] Verificar consulta × página, integración secuencial con PR #237, staging/producción y mediciones a 7/28/56 días. Si el tráfico mejora, repetir en lotes adicionales de 20.

**Salida:** segunda cohorte con control independiente y aprendizaje de F1/F2.

### Resultado ampliado de la selección — 60 URLs (3 tandas)

- **Tanda 1:** 20 URLs (16 EN, 4 ES), 189.684 impresiones, 782 clics. Títulos y metadatos preparados en PR #237; **sin publicar**.
- **Tanda 2:** 20 URLs EN, 94.123 impresiones, 169 clics. Solo H1 repetido en PR; los cambios de intención/meta/editorial todavía no se han ejecutado.
- **Tanda 3:** 20 URLs EN, 70.776 impresiones, 201 clics, [documento de selección](./SEO_COHORTE_3_20_INGLES_2026-10-12.md). **Sin cambios de código en estas URLs**.
- **Total de las tres cohortes, sin URL duplicadas:** 60 URLs, 56 EN, 354.583 impresiones y 1.152 clics en 60 días, CTR agregado ~0,325%.
- Libro Excel con el seguimiento, fórmulas, estados e indicadores disponible como archivo generado en esta conversación: `seguimiento_seo_60_articulos_2026-10-12.xlsx`.

**Seguridad al publicar:** la versión inglesa de artículos individuales no entra en la caché temprana del blog, pero algunas entradas españolas sí. Los cambios del tema no demuestran por sí solos que se haya invalidado el directorio `uploads/elmercado-blog-static-v2`. Comprobar esta caché tras el despliegue. Asimismo, el fallback EN de categorías conserva ahora los enlaces editoriales originales en un índice desplegable para no perderlos, y la navegación de `single.php` usa etiquetas inglesas explícitas respetando las traducciones del resto de idiomas.

### F4 — Enlaces y conversión hacia productos, categorías y productores [PENDIENTE]

- [ ] Auditar enlaces del blog a producto/categoría por relevancia y disponibilidad geográfica. No insertar CTA de compra en inglés a país sin envío.
- [ ] Revisar la [promoción Montjam caducada a 30/09/2026](https://github.com/tostis005/MO/issues/240): la landing pública aún anuncia 225 € como oferta especial pasada. Confirmar vigencia real y corregir contenido CMS/plugin, sin modificar precios de WooCommerce arbitrariamente.
- [ ] Revisar títulos/snippets de categorías prioritarias: aceites, jamones y paletas, productores y fichas con impresiones pero CTR bajo.
- [ ] Detectar páginas con productos agotados/no visibles y referencias cruzadas incorrectas.
- [ ] Medir clics de artículo a producto, interacciones, add-to-cart y pedidos atribuidos antes de ampliar los CTAs.

### F5 — Monetización inglesa / AdSense [PENDIENTE]

- [x] Auditoría del control actual de ads/zonas WooCommerce y línea base GSC por país/dispositivo; [informe](./SEO_ADSENSE_MONETIZACION_2026-10-12.md). **Sin modificar política geográfica, TCF, cookies ni densidad publicitaria.**

- [ ] Obtener o consultar datos **AdSense** por página, país y dispositivo: vistas monetizables, impresiones de anuncio, cobertura, CTR de anuncio, RPM de página y revenue.
- [ ] Cruzar tráfico SEO EN con países no compradores, **sin desactivar las protecciones de consentimiento ni geolocalización**.
- [ ] Medir CWV móvil/desktop y viewability, huecos sin rellenar y desplazamientos de maquetación causados por anuncios.
- [ ] Probar ajustes moderados de posición/densidad de anuncios cuando haya evidencia: prioridad experiencia y políticas Google.
- [ ] Separar métricas de tráfico EN de **ingresos publicitarios reales**; no asumir que cada clic genera monetización.

### F6 — Seguimiento, decisión y nuevos lotes [RECURRENTE]

- [ ] Comparaciones GSC en ventanas equivalentes **7, 28 y 56 días** (por página, país y dispositivo), con cuidado de estacionalidad.
- [ ] Cuadro de mando: clics EN / ES, impresiones, CTR, posición, páginas activas; además ingresos, page RPM y conversión a venta.
- [ ] Clasificar cada cambio: **mejora / neutro / empeora**, recuperar si genera caída significativa tras comparar consulta y demanda.
- [ ] Iterar por lotes de 20, priorizando el mayor rendimiento económico esperado y el menor riesgo.

## Orden práctico ante «continuar»

1. Terminar el **siguiente punto incompleto** de F0/F1 que sea posible con accesos disponibles, actualizar esta hoja y PR.
2. Pasar a F2, F3, F4 y F5 según dependencias; las tareas de código no quedan bloqueadas por métricas no conectadas, pero las decisiones de intención por keyword **sí** dependen del cruce `page+query`.
3. Marcar un punto como completado solo con evidencia: cambio fusionado o PR, CI, enlace de validación, métricas verificadas. **Nunca** afirmar que algo está en producción porque figura en una rama.
4. Si WordPress, Search Console o AdSense exigen autorización adicional, avanzar con tareas independientes y consignar el bloqueo, sin repetir preguntas ya respondidas.

## Referencias operativas

- [PR #237 (20 entradas)](https://github.com/tostis005/MO/pull/237).
- [Repositorio principal](https://github.com/tostis005/MO).
- [Módulo SEO previo](https://github.com/tostis005/MO/blob/main/elmercadodeorigen-child/inc/seo-search-console-010313.php).
- Exportación GSC suministrada en la conversación: `elmercadodeorigen.com-Performance-on-Search-2026-10-12.xlsx`.
- Informes guardados en conversación: `analisis_seo_gsc_el_mercado_de_origen_2026.xlsx` y `plan_seo_20_articulos_ingles_2026-10-12.xlsx`.

**Última revisión:** 2026-10-12 — octava iteración larga: PR #237 (primer lote 20) y PR #241 (segundo lote 20 EN) preparadas con tests automáticos **success** (#38310821007 y #38310535552), 0 URLs de meta solapadas; ambas **draft, no merge/no despliegue**. El auditor staging admite Basic auth HTTPS opcional mediante Secrets y existe [runbook](./SEO_STAGING_PUBLICACION_RUNBOOK_2026-10-12.md). Brief editorial por 20 URLs EN listo (sin edición WP), tercera tanda 20 EN todavía analizada, no optimizada. Incidencia de productos relacionados poco pertinentes [#242](https://github.com/tostis005/MO/issues/242). Faltan acceso staging, CMS/Falang, GSC page×query y AdSense por landing para completar publicación y medición.
