# Auditoría editorial Latitudes/Wagyu — 15 de octubre de 2026

## Resumen ejecutivo

**No declarar calidad editorial "perfecta".** El roadmap de publicación anterior sigue completado (10 ampliaciones + 18 publicaciones ES/EN), pero esta revisión revela mejoras de calidad que deben tratarse como un **nuevo bloque de QA**, separado de las 28 intervenciones iniciales.

Comprobación de producción: [auditor de 28 artículos](https://github.com/tostis005/MO/actions/runs/38714486679), **success**. Auditoría de los cuatro paquetes fuente completos `.github/data/editorial-exoticas-20261015*.json`.

## Resultados y evidencia

- **28/28** entradas publicadas disponibles en WordPress, con datos de ambos idiomas.
- **18 nuevos**: promedio de **701 palabras ES y 307 EN**, aprox. 44 % del desarrollo español. Las versiones inglesas son **adaptaciones resumidas**, no equivalencias completas.
- **10 existentes modificados**: promedio de **1477 palabras ES y 1138 EN**. Las ampliaciones preservaron URL y contenido anterior.
- **18/18 nuevos** usan encabezado «Preguntas frecuentes» y equivalente inglés; cantidad de H2 variable, pero la misma lógica de estructura se repite mucho.
- **12/18 nuevos** no tienen ningún enlace en el cuerpo de la versión inglesa; **1/18** sin enlaces en español.
- **18/18 nuevos** no tienen imagen destacada asignada, **10/10 existentes** sí disponen de imagen destacada (adjunto histórico 13359).
- Artículos con entidad temática propia y títulos/cortes diferenciados, pero repetición de secciones de conservación, temperatura y cierre FAQ; las recetas requieren mayor énfasis específico en ingredientes, cantidades y técnica. No son copias textuales entre sí, pero muestran un patrón editorial excesivamente uniforme.

## Corrección ya aplicada en producción: imagen de marca

La denominación **«Imagen provisional del blog de El Mercado de Origen»** estaba en el título y ALT del adjunto central 13442 y el adjunto histórico 13359; en inglés figuraba **«Temporary editorial image for El Mercado de Origen blog»**. Se corrigieron ambos adjuntos:

- `post_title`: `El Mercado de Origen`
- `_wp_attachment_image_alt`: `El Mercado de Origen`
- `_en_US_attachment_alt`: `El Mercado de Origen`
- Se conservaron el archivo físico y su URL histórica, para evitar roturas de enlaces/cachés. **El nombre del archivo aún contiene `provisional`**, pero el ALT y el título visibles ya no.
- [Ejecución inicial de cambio](https://github.com/tostis005/MO/actions/runs/38713843801) cambió metadatos correctamente pero el paso de verificación falló por URL EN antigua con 404.
- [Ejecución de verificación definitiva](https://github.com/tostis005/MO/actions/runs/38714603626) — **success**, comprobó páginas ES/EN y ausencia de la cadena provisional en ALT público de las URL de prueba.

## Siguiente bloque de QA editorial (fuera del roadmap de publicación)

| Tarea | Pendientes | Prioridad |
|---|---:|---|
| Q1. Reescritura editorial de inglés, con paridad de cobertura y utilidad, sin traducción mecánica | 18/18 | Muy alta |
| Q2. Revisar cada artículo ES/EN como unidad independiente: evitar esqueleto repetitivo, FAQ innecesarias y cierres genéricos; reforzar instrucciones de recetas y enlaces internos contextualizados | 18/18 | Muy alta |
| Q3. Imagen destacada distintiva y pertinente, con ALT descriptivo propio y derechos/autoría claros | 18/18 | Alta |
| Q4. Retirar «Imagen provisional» de metadatos de las 2 imágenes compartidas | **0/2 — resuelta** | Completada |

**Regla de cierre:** no marcar Q1-Q3 como finalizadas hasta revisar manualmente el artículo español y el inglés completo, sin imponer un número fijo de apartados; verificar en frontend HTML ES/EN y el resultado móvil cuando sea posible.

La auditoría es de calidad de contenido y metadatos, no una medición de posiciones orgánicas, impresiones ni conversiones SEO.
