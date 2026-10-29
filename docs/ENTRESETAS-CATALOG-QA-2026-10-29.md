# EntreSetas — Auditoría de 29 fichas y portes (29/10/2026)

## Estado verificado en producción
- Proveedor EMDO #10, vendedor WCFM #4982 (entresetas).
- 30 referencias detectadas en la tienda original; 29 importadas en WooCommerce y 1 retenida: /product/trufa/ (trufa de precio variable bajo consulta).
- 29/29 WooCommerce en estado borrador (draft), catálogo hidden y autor WCFM #4982.
- 29/29 precios coinciden con el payload oficial; 29/29 stock coincidente.
- 29/29 descripciones cortas y largas presentes y previamente cotejadas exactamente con el snapshot de origen.
- 29/29 fotos destacadas, galerías completas según source_payload; 0 referencias sin imagen. Las imágenes de origen se incorporaron sólo a las fichas privadas para revisión de producto. **El flag mdo_entresetas_media_authorized sigue en 0**, y deben verificarse derechos de uso antes de publicar.
- Los pesos disponibles coinciden con la ficha original; si no se publica peso individual, no se inventa.
- Sin cambios en otros vendedores.

## Categorías definitivas
Todas bajo el nuevo parent product_cat 'Setas y hongos':
- Setas frescas: 4
- Setas deshidratadas: 7
- Conservas y elaborados de setas: 3
- Harinas y sales de setas: 5
- Infusiones y tés de setas: 6
- Extractos de setas: 4

El mapeo está integrado en EMDO 1.0.71; futuros refrescos de fichas existentes conservarán la clasificación.

## Portes comprobados directamente desde el carrito original
Se añadió una unidad de seta de ostra ecológica (500 g; 6,50 €) al carrito anónimo del dominio entresetas.com, se utilizó su propio formulario de cálculo por país/provincia/código postal y se leyó el método 'Precio fijo'. **No se realizó pedido ni pago**.

- España (28001 Madrid, 34001 Palencia): 9,95 €
- Baleares (07001 Palma): 9,95 €
- Portugal (1000-001 Lisboa): 30,00 €
- Francia (75001 París): 30,00 €
- Una cesta de 20 unidades (130,00 €): 9,95 € en Madrid, 30,00 € en Lisboa, sin umbral de gratuidad observado.
- El carrito original devolvió también 9,95 € para Canarias (35001), Ceuta (51001) y Melilla (52001), pero esas zonas NO están habilitadas en El Mercado de Origen al faltar zonas globales independientes y confirmación del suministro real. No se modifican zonas globales ni otros vendedores.

## Métodos instalados en WCFM (sólo vendedor 4982)
- Zona Woo #1: España (península): tarifa fija 9,95 €.
- Zona Woo #10: Baleares: tarifa fija 9,95 €.
- Zona Woo #19: Francia: tarifa fija 30,00 €.
- Zona Woo #20: Portugal: tarifa fija 30,00 €.
- Sin métodos de envío gratuito ni pedido mínimo inventados.
- Prueba tras escritura: métodos y tarifas correctos, destinos esperados admitidos, Canarias y Ceuta bloqueados y hash de filas WCFM del resto de vendedores intacto.
- Cuenta WCFM deshabilitada y mdo_entresetas_commercial_ready = 0.

## Límites y revisión previa a la publicación
- El carrito del productor devuelve tarifas técnicamente calculadas, no demuestra que un tercero esté autorizado a revender sus artículos ni a utilizar imágenes o logotipo.
- Confirmar acuerdo comercial, licencia de fotos y costes de embalaje/servicio, especialmente de productos frescos y perecederos.
- Verificar ingredientes, alérgenos y alegaciones de salud de los 4 extractos y de las 6 infusiones/tés antes de permitir compras, evitando replicar promesas terapéuticas no autorizadas.
- El checkout de prueba de El Mercado de Origen debe realizarse después de habilitar explícitamente el vendedor (no habilitar ahora).
- No declarar comercialización terminada hasta validar la relación de suministros.
- Para repetir el respaldo de fotos en borradores, usar manualmente la acción 'EntreSetas product media and category repair private drafts', que no se programa de forma recurrente.

Evidencias:
- Auditoría inicial: https://github.com/tostis005/MO/actions/runs/40862906650
- Imágenes y categorías corregidas: https://github.com/tostis005/MO/actions/runs/40863666836
- Cotización carrito original: https://github.com/tostis005/MO/actions/runs/40864217249
- Tarifas WCFM y verificación: https://github.com/tostis005/MO/actions/runs/40864611235
