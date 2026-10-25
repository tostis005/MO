# Control SEO operativo — 25 de octubre de 2026

**Fuente**: exportación adjunta de Search Console `elmercadodeorigen.com-Performance-on-Search-2026-10-25.xlsx`. Período real **12-08-2026 a 22-10-2026 (72 días)**. Código principal `tostis005/MO`. No se dispone del cruce página×consulta ni del API de Search Console en el repositorio.

## KPIs comprobados — línea base

| Indicador | Base | Definición | Objetivo/umbral |
| --- | ---: | --- | --- |
| Clics orgánicos totales (72 días) | 13.119 | Totales suma hoja Gráfico | Contexto, no objetivo aislado |
| Impresiones orgánicas (72 días) | 2.602.718 | Totales suma hoja Gráfico | Contexto |
| CTR ponderado (72 días) | 0,5040 % | 13.119/2.602.718 | Mejorar por consulta/página, NO forzar CTR global |
| Clics 14 días previos (25 sep.–8 oct.) | 3.637 | Intervalo diario exclusivo | Señal de control |
| Clics últimos 14 días (9–22 oct.) | 3.673 | Intervalo diario exclusivo | +1,0 % respecto a anterior |
| Impresiones 14 días previos | 702.627 | Intervalo diario exclusivo | Señal de control |
| Impresiones últimos 14 días | 834.331 | Intervalo diario exclusivo | +18,7 % |
| CTR 14 días previos | 0,518 % | Clics/impresiones | Señal de control |
| CTR últimos 14 días | 0,440 % | Clics/impresiones | Variación -0,078 puntos |
| Cinco guías ES priorizadas | 885 clics / 306.156 impresiones | Suma de filas de Páginas de toda la exportación | Medir cada URL 28 días antes/después cuando haya desglose; objetivo de prueba +15 % clics con posición estable |
| Legumbres + Aceites + Jamones | 31 clics / 1.685 impresiones | Tres archivos de categoría, 72 días | Objetivo de prueba +25 % clics comerciales en 90 días, con demanda/posición comparable |
| QA de cinco artículos prioritarios | Por comprobar | Una sola etiqueta H1, title, meta description y canonical autocoincidente | 5/5 sin errores críticos tras publicar |
| Indexabilidad comercial | Sin dato de cobertura | GSC Indexación, sitemap, robots y canonicals | 0 errores críticos confirmados en URLs elegibles |
| Tránsito blog → tienda → pedido | Sin GA4/eventos | Evento de enlace, sesión product-view, add-to-cart, pedido | Instrumentar antes de declarar crecimiento en ventas |

**Precaución**: el Excel exporta consultas y páginas en tablas independientes. NO atribuir consultas a URLs ni sacar conclusiones causales de modificaciones realizadas el 23–24 de octubre, posteriores al corte GSC.

### Cinco guías prioritarias (datos acumulados de 72 días)

| URL | Clics | Impresiones | CTR |
|---|---:|---:|---:|
| /hay-que-poner-lentejas-en-remojo-cuanto-tiempo/ | 261 | 95.861 | 0,27 % |
| /garbanzos-tiempo-remojo-cuanto-tardan-cocerse/ | 324 | 80.451 | 0,40 % |
| /cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias/ | 146 | 64.920 | 0,22 % |
| /cuanta-proteina-tiene-carne-ternera/ | 75 | 40.593 | 0,18 % |
| /cuanto-dura-carne-cocinada-nevera-conservacion-segura/ | 79 | 24.331 | 0,32 % |

### Estado y responsables operativos

1. **25 oct**: abrir intervención GitHub con corrección acotada de H1 idénticos al título en artículos; preservar H1 distintos y cuerpo almacenado; prueba PHP. **Estado: PR**; no considerar publicado sin verificación pública. Se detectaron dos H1 idénticos en los HTML consultados de garbanzos, legumbre por persona y proteína de ternera; pueden ser snapshots cacheados y deben verificarse tras despliegue.
2. **25–30 oct**: verificar HTML público de muestra y HTTP→HTTPS con petición que registre cadena de redirecciones; revisar caché. **Criterio de cierre:** 5/5 artículos con 1 H1, 1 canonical correcta, 1 title y 1 description; si hay error no publicar nuevas metas.
3. **25–30 oct**: auditar si el texto de archivo Legumbres se imprime varias veces y la cabecera «Categoría:» procede del tema o filtros; comprobar Aceites/Jamones en móvil. **Estado: pendiente, no corregir hasta identificar origen.**
4. **25–30 oct**: analizar enlaces hacia productos reales desde las cinco guías y si siguen elegibles para envío, sin crear CTAs irrelevantes en inglés. **Estado: pendiente.**
5. **10 nov**: usuario adjunta nuevo Excel GSC filtrado últimos 28 días (y, si es posible, tabla página×consulta por 5 URLs), comparar con período anterior por país y dispositivo; decisión mantener/ajustar.
6. **25 nov**: segunda exportación; seleccionar 5 URLs siguientes por clic incremental comercial, no por visitas absolutas.
7. **22 dic**: cierre 56 días y evaluación de categorías, conversión y 40 URLs inglesas en borrador (#245). No fusionar #245 solo por estar preparada.

### Regla de decisión

- **Mantener**: CTR/clics de consultas principales mejoran con posiciones y demanda similares, sin errores canónicos/H1/404.
- **Investigar**: posición de consultas principales cae más de 1–2 puestos, o clics caen más del 20 % en 28 días mientras impresiones no caen proporcionalmente. Comprobar estacionalidad, país, dispositivo y SERP antes de revertir.
- **Revertir**: aparición de canonical incorrecta, noindex inadvertido, 404/5xx por cambios, o metadatos duplicados confirmados tras despliegue.
- **Escalar**: solamente cuando el lote anterior tenga control de HTML y al menos una evaluación de 28 días con GSC comparable.
