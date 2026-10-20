# Auditoría editorial: 24 artículos bilingües (20-10-2026)

## Alcance
Se han revisado los 24 manuscritos ES/EN (48 versiones), la comparación de títulos frente al inventario de 595 artículos anterior y las 48 URLs de producción mediante la ejecución de solo lectura https://github.com/tostis005/MO/actions/runs/39497975770 . La auditoría no ha alterado las entradas publicadas.

## Resultado
- Textos: español 834–1403 palabras (media 1021), inglés 787–1306 (media 952); entre cuatro y siete H2. Sin encabezados idénticos ni introducciones prácticamente iguales detectadas. Sin duplicación literal de título con el catálogo anterior.
- Comprobación web: 38/48 URLs devolvieron 200; diez (números 1–5 en ambos idiomas) agotaron el tiempo de lectura del auditor de 45 segundos. Sus verificaciones originales de publicación sí habían pasado; repetir sin concurrencia antes de declarar fallos.
- De las 38 que respondieron, las 38 muestran canonical propio correcto, metadescripción y JSON-LD, sin meta robots noindex.
- Incidencia prioritaria: las 19 URLs inglesas que respondieron generan hreflang hacia el español bajo la ruta /en/slug-espanol/ en vez de /slug-espanol/. Igual problema en x-default. Revisar el generador global de hreflang y comprobar reciprocidad ES/EN.
- Las 38 páginas accesibles presentan dos elementos H1 y no tienen etiqueta og:image. Determinar el origen del segundo H1 en la plantilla y comprobar configuración de imagen social; evitar inferir penalización automática.
- Imagen de reserva 13442 y sin enlace comercial automático en 15 artículos: café 1–9, mango 15–17, naranjas 18–20. Otros 9 usan imagen de producto y categoría que tenía stock al publicarse. Revisar productos reales, taxonomías, stock y fotos específicas.
- Once artículos no incluyen citas externas dentro del texto y cinco no contienen ningún enlace de origen: 1, 5, 10, 11 y 22. Las citas externas no son requisito automático, pero reforzar documentación donde aparezcan datos técnicos y enlaces internos donde ayuden a comprar.
- En las muestras revisadas la metadescripción HTML procede del excerpt, no necesariamente del campo seo_description redactado expresamente. Comprobar qué sistema de metas ES/EN prevalece en producción.

## Prioridad de actuación
1. Reparar hreflang/x-default en inglés para todo el sitio.
2. Medir respuesta/TTFB de las 10 URLs con timeout, sin múltiples solicitudes simultáneas.
3. Sustituir imágenes genéricas y enlazar categorías o productos verificablemente disponibles.
4. Revisar H1 y og:image a nivel de plantilla, y unificar los metadatos SEO por idioma.
5. Mejorar enlazado y atribución en guías seleccionadas, manteniendo estilo editorial propio.
6. Medir consultas e impresiones antes de decidir si existe canibalización SEO entre artículos próximos.

Veredicto: los contenidos y traducciones forman una base útil, pero el trabajo no está completamente optimizado. La auditoría es de solo lectura y no demuestra resultados de tráfico ni ventas.

## Intervención completada en producción — 21/10/2026

**Ejecución final con QA público satisfactorio:** https://github.com/tostis005/MO/actions/runs/39508487641

Se han preservado los 24 artículos publicados, sus dos idiomas y la estructura editorial original. El proceso actualizó los **24 registros** y guardó una copia de seguridad de los cuerpos ES/EN y de la imagen anterior por cada artículo mediante los metadatos `_emdo_editorial_repair_20261021_backup_*`. La ejecución es idempotente.

- **SEO multilingüe:** `.github/scripts/mdo-editorial-24-seo-runtime-20261021.php` se ha instalado como `wp-content/mu-plugins/0000-emdo-editorial-24-seo-20261021.php`. Solo interviene en las 48 rutas exactas de este plan editorial; corrige `hreflang es-ES`, `en`, `x-default`, actualiza metadescripción visible con el valor editorial almacenado, genera `og:image` y `twitter:image` si falta y elimina el H1 desnudo duplicado cuando repite exactamente el `entry-title`.
- **Imágenes:** 15 artículos (café nº 1–9, mangos nº 15–17, cítricos nº 18–20) han recibido fotografías ya presentes en la biblioteca de WordPress de Ikore Kofi y productos frescos pertinentes, en lugar de la imagen de reserva nº 13442. Las otras nueve entradas han conservado sus fotografías de producto ya asignadas. La selección aprovecha adjuntos existentes, no duplica medios ni cambia los atributos alt de las fotos compartidas por productos.
- **Enlaces comerciales:** los nueve artículos de café enlazan con la categoría real `cafe-de-especialidad`, con **seis referencias comprables y en stock** al momento del diagnóstico. Los tres artículos de mango y los tres de cítricos enlazan contextualmente a productos concretos, publicados y en stock, sin asegurar que se venden naranjas para zumo o variedades exactas que no hay en la ficha. Las guías de otras frutas, aguacate y cremas conservan sus enlaces existentes. Las frases añadidas son diferenciadas por tema en ambos idiomas.
- **Conservación del contenido:** no se ha reescrito la parte editorial ya validada ni se ha creado ninguna publicación duplicada; las frases comerciales se añadieron en contexto. La rutina de reparación incluye preflight de catálogo y copias de seguridad y no modifica productos ni inventario.
- **Verificación pública tras despliegue:** ocho páginas representativas ES/EN de café, mango, naranjas y cacao superaron todas las comprobaciones automáticas de HTTP 200, exactamente un H1, `og:image`, metadescripción y `hreflang` recíproco con URL española fuera de `/en/`. Portada, blog y tienda también respondieron HTTP 200. La inspección de WordPress validó los 24 registros. Este control de muestra **no equivale a afirmar que se haya repetido la descarga de las 48 URLs**.

**Pendiente fuera de este alcance:** el fallo de `hreflang` puede existir en contenidos ingleses ajenos a estas 48 rutas, debido a la construcción global del módulo `mdo-production-english-seo-routes.php`; requiere una auditoría técnica global con despliegue independiente. También sigue pendiente analizar resultados reales de Search Console tras indexación y revisar visualmente los recortes/alt individuales; la ausencia de datos de volumen de palabras clave no impide que los artículos estén publicados.
