# Cierre operativo del lote SEO comercial — 5 de noviembre de 2026

**Proyecto:** El Mercado de Origen · **índice único:** [GitHub #422](https://github.com/tostis005/MO/issues/422)

## Cambios publicados y verificados

Se conservó el CTA de nueva cosecha de la portada hacia la **tienda de 1957** `/tienda/1957/`, por decisión comercial. Se mejoraron las fichas AOVE 26/27 (metadatos, atributos visibles), el texto de Aceites y Setas, el enlazado contextual desde cinco guías y el puente entre las tres fichas 25/26 y sus formatos equivalentes 26/27. Se conservan precios, stock, IDs y canonicals históricos. La tienda de 1957 tiene título SEO comercial y una única metadescripción.

El marcado de producto y la indexación prioritaria se comprobaron en producción: [12 HTML Product/archives](https://github.com/tostis005/MO/actions/runs/42004038205) y [sitemaps/robots](https://github.com/tostis005/MO/actions/runs/42005777638). Los 8 productos muestreados tienen Product + Offer/AggregateOffer con EUR e InStock; 5 sitemaps en índice, 1.206 entradas de producto y 62 de categoría; productos AOVE y categorías objetivos presentes. La tienda 1957 sirve 5 nodos H1 por combinación de cabecera oculta del tema y banners escritorio/móvil; no se modifica a ciegas una plantilla que se representa de forma responsiva.

La antigua URL `/en/product/padron-peppers-kg/` devuelve 404. La auditoría de producción no encontró producto actual que contenga el término Padrón en título/slug, publicado o no publicado. **Decisión:** dejar el 404, sin redirección genérica ni resucitar una ficha de catálogo inexistente. Si aparece un equivalente exacto y vigente en el futuro, evaluar una redirección 301 específica.

## C02 · Google Merchant Center

[Feed público ES auditado](https://github.com/tostis005/MO/actions/runs/42005144500): 1.067 ofertas repartidas en 688 rutas; 11 variantes activas 26/27 (5+3+3) y 4 variantes 25/26 (2+1+1) de 1957; ningún aviso del validador propio por falta de imagen, precio EUR mal formateado, disponibilidad inválida o país de envío ES.

**Límite:** el XML correcto no acredita aprobación, elegibilidad ni exposición en Google Shopping. La instalación de Google Listings and Ads está inactiva en WordPress; no se ha comprobado el diagnóstico de Merchant Center de la cuenta Google. No marcar el criterio de cierre como completo hasta revisar “Necesita atención”, cuenta, destinos y estado de artículos desde una integración autorizada con Merchant Center.

## C04 · GA4 y ventas atribuidas

[Auditoría técnica 5-11](https://github.com/tostis005/MO/actions/runs/42010082699): el plugin `google-analytics-for-wordpress` está activo; la etiqueta `gtag.js`, un ID de medición GA4 y `dataLayer` están presentes en portada, ficha AOVE 26/27 y guía de aceite. Site Kit y Google Listings and Ads aparecen inactivos. **Esto no certifica el embudo WooCommerce**: las señales `view_item`, `add_to_cart`, `purchase` no se pudieron confirmar por inspección de HTML estático ni recepción en GA4. No publicar conversiones GA4 asumidas ni instalar un segundo contenedor a ciegas.

WooCommerce sí almacena atribución nativa a nivel de pedido: muestra de últimos 30 días limitada a **78 pedidos** en estados procesando/completado, con `organic: 12`, `typein: 38`, `referral: 8`, `utm: 1`, `mobile_app: 2`, `sin origen: 17`. Es una muestra de pedidos según fuente registrada, **no** ventas, ingresos ni compras GA4 verificadas. Revisar recepción del embudo en DebugView y eventos `purchase` reales/sin doble conteo con acceso autorizado a la propiedad GA4.

## C05 · GSC: base comparable de 28 días

Fuente: exportación GSC `elmercadodeorigen.com-Performance-on-Search-2026-11-05.xlsx`, disponible en la conversación, con último día **2 de noviembre**. Los informes por **Páginas** y **Consultas** están separados, por lo que **no proporcionan página×consulta**.

| Indicador global | 8 sep.–5 oct. 2026 | 6 oct.–2 nov. 2026 | Diferencia |
| --- | ---: | ---: | ---: |
| Clics | 7.212 | 8.372 | +16,1 % |
| Impresiones | 1.296.827 | 1.714.135 | +32,2 % |
| CTR ponderado | 0,5561 % | 0,4884 % | -0,0677 puntos |

Estas **dos ventanas son anteriores al cambio SEO del 5 de noviembre**. No son resultados ni evidencias causales de la intervención. La ventana posterior será **6 nov.–3 dic. 2026**, y los datos completos deberían consultarse a partir del **5–7 de diciembre** (sujetos al retraso real de Search Console).

Datos agregados por página del período completo 12 ago.–2 nov.: `/tienda/1957/` **119 clics / 681 impresiones**, `/categoria-producto/aceites/` **15 / 1.084**, `/categoria-producto/jamones-paletas/` **21 / 774** y la ficha AOVE 26/27 de 5L **2 / 16**. **No** mezclar estos agregados de 83 días con ventanas 28 días.

**Para cerrar C05:** exportar Search Console filtrando España y, por separado, móvil/ordenador; obtener dimensión conjunta `página + consulta` de los períodos completos anteriores y posteriores, analizar clics/impresiones/CTR/posición y después contrastar con las compras orgánicas identificadas por canal. Evitar asumir mejora de negocio por aumento de tráfico informativo.

## Resultado de esta revisión

Se pueden dar por comprobadas las cuestiones de indexabilidad de las páginas comerciales actuales. El resto de tareas **no debe fingirse completado**: C02 requiere diagnóstico del Merchant Center autenticado; C04 requiere confirmar los eventos WooCommerce dentro de GA4; C05 solo podrá cerrarse con una ventana poscambio de 28 días. No activar un segundo sistema de tracking, no editar pedidos y no modificar URLs de campañas anteriores sin evidencia.
