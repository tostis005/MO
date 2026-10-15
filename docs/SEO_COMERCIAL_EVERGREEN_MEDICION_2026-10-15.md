# SEO comercial evergreen — control y medición

**Fecha de puesta en producción:** 15/10/2026. **Proyecto:** El Mercado de Origen. **Roadmap:** [#304](https://github.com/tostis005/MO/issues/304).

## Qué está publicado

- Home y categorías de hortalizas/carnes: metadatos transaccionales verificados ([despliegue](https://github.com/tostis005/MO/actions/runs/38715677063)).
- Descripción de hortalizas y cuatro guías de compra existentes: mejora ES/EN, sin texto duplicado y con enlaces verificados ([despliegue](https://github.com/tostis005/MO/actions/runs/38717149127)).
- Tres guías nuevas originales en ES/EN con imágenes de la biblioteca y seis URL públicas verificadas ([despliegue](https://github.com/tostis005/MO/actions/runs/38718674650)).
- Cuatro artículos existentes enlazados a las tres guías nuevas en el contexto editorial ([despliegue](https://github.com/tostis005/MO/actions/runs/38719091753)).

## Línea base de tráfico disponible: solo contexto global

La hoja de ruta anterior SEO_HOJA_DE_RUTA_2026-10-12.md registra una exportación de Search Console entre el **12 de agosto y el 10 de octubre de 2026 (60 días)** de aproximadamente **1,87 millones de impresiones** y **9.877 clics** para el sitio completo. Esto **no es** una línea base de conversión de las diez páginas del proyecto. La exportación separa consultas de páginas: no permite atribuir una consulta concreta a una URL. No se deben deducir aquí volúmenes, CTR por intención de compra, ventas ni tasas de conversión.

**Estado de acceso:** No hay acceso directo confirmado en esta ejecución a informes de Search Console filtrados por consulta × página × país × dispositivo, ni a transacciones/eventos de GA4. Las cifras comerciales de la cohorte quedan **pendientes**. No dar por instrumentado un evento solo porque Google Analytics exista en el código.

## Cohorte fija de seguimiento

Las diez URL españolas son: home, categorías de hortalizas y carnes, las cuatro guías existentes modificadas y las tres guías originales publicadas (rutas exactas registradas en tests/seo-commercial-evergreen-public-audit-20261015.py). Las tres guías nuevas en inglés forman parte del control de publicación; las traducciones de los artículos existentes se conservaron y se revisaron al desplegar.

Para atribución comercial, conviene analizar **las URL comerciales por separado de las guías** y segmentar países con capacidad de compra; los usuarios de otros países pueden ser valiosos para tráfico informativo y AdSense, pero no son prueba de mejora en ventas de la tienda.

## Datos necesarios del analítico real

1. **Search Console, por URL + consulta:** impresiones, clics, CTR y posición por día, filtrados a España y otros destinos de venta reales, con segmentación ES/EN y dispositivo. Identificar consultas transaccionales sin adjudicarlas por intuición a una URL.
2. **GA4, adquisición orgánica:** sesiones con fuente/medio google / organic o agrupación Organic Search y página de entrada. Evitar sumar visitas de distintos países sin segmentación.
3. **GA4 ecommerce:** eventos view_item, add_to_cart, begin_checkout y purchase, y valor monetario de purchase con currency, transaction_id y deduplicación. Comprobar en DebugView si existen; no instalarlos a ciegas sobre WooCommerce.
4. **Puente editorial → tienda:** proporción de lectores de guías que abren categorías o fichas, añaden productos al carrito y compran en la misma sesión o en recorridos posteriores. Un clic de enlace es complementario, no sustituye una compra.
5. **Evaluación a 30/60/90 días:** comparar con periodos anteriores comparables, por URL y país; no atribuir automáticamente estacionalidad o cambios de stock a los artículos.

## Control técnico ejecutable

GitHub Actions ejecuta la auditoría de solo lectura SEO commercial evergreen final public audit sobre 13 URL, con informe JSON descargable. Comprueba HTTP 200, title, meta description, canonical, H1, integridad de ampliaciones, enlaces de compra, texto de hortalizas sin duplicar, imágenes no provisionales y estructura de las guías nuevas. Los H1 duplicados existentes se informan como advertencias, no se corrigen fuera del alcance.

**Criterio de cierre:** cambios editoriales/SEO técnicamente verificados; medición de conversión no finalizada hasta disponer de datos reales de GA4 y GSC. El reporte técnico no representa crecimiento SEO ni ventas atribuidas a las mejoras.

## Próxima lectura de resultados

En revisiones futuras, completar URL, fecha de comparación, impresiones y clics de consultas comerciales, visitas que avanzan a producto, añadidos al carrito y compras atribuidas. Registrar tamaño de muestra y no publicar porcentajes de mejora sin denominadores comprobables. El primer mes muestra señales tempranas; una evaluación más robusta necesita periodos de 60–90 días y comparativas de estacionalidad.
