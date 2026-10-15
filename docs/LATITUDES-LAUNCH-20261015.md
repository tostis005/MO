# Latitudes · puesta en marcha, checklist de producción (15-10-2026)

Este documento no afirma que la tienda esté publicada. La importación y la configuración WCFM residen en la base de datos y los plugins de WordPress, fuera del despliegue de este tema hijo.

## Diagnóstico objetivo

1. Desplegar el tema en staging y abrir **Herramientas → Diagnóstico Latitudes** con un usuario con permiso `manage_woocommerce`.
2. Confirmar el usuario WCFM que corresponde a Latitudes, su ID y su URL de tienda.
3. Revisar los flags `_wcfm_store_offline` y `_disable_vendor`, además del rol `disable_vendor`. Los tres pueden impedir la publicación en el frontend.
4. Contar productos por `post_status`: los productos con estado `draft`, `pending`, `private` o `archived` no aparecen en el catálogo. Los productos deben tener `publish`, `catalog_visibility=visible`, `stock_status=instock` (conforme a la política del sitio) y asociarse al vendedor real de WCFM.
5. Si los productos recién importados aparecen atribuidos a otro autor, corregir la asignación desde el **importador original**, no mediante una actualización SQL indiscriminada. Después ejecutar la importación de prueba y confirmar que no reaparecen bajo otro autor.
6. Recalcular tablas de búsqueda de WooCommerce y vaciar las cachés después de editar los estados. Validar como visitante anónimo y en móvil las páginas `/tienda/`, `/productores/` y el perfil del vendedor.
7. Comprobar número de productos en el listado global, ficha individual, filtros de categoría y tienda WCFM. Probar el producto con el checkout sólo después de verificar envío.

## Condiciones oficiales de envío

Fuente: https://latitudes.es/condiciones-de-venta/ (consultada el 15-10-2026).

| Destino | Peso total del pedido del vendedor | Importe |
|---|---:|---:|
| España peninsular y Portugal peninsular | Hasta 4 kg | 13,45 € |
| España peninsular y Portugal peninsular | Más de 4 hasta 7 kg | 16,45 € |
| España peninsular y Portugal peninsular | Más de 7 hasta 20 kg | 21,45 € |
| Baleares | Hasta 20 kg | 29,00 € |
| Península o Baleares | Importe Latitudes >= 200 € | Gratis |
| Más de 20 kg | Cualquiera | Solicitar presupuesto; no automatizar |
| Canarias, Ceuta, Melilla y territorios no peninsulares de Portugal | Cualquiera | No disponibles según condiciones del productor |

- Seur Frío y entregas generalmente en 24/48 horas laborables, con las excepciones descritas por el productor.
- La fuente también contempla recogida gratuita en su establecimiento de Alcobendas, pero no se debe activar en el marketplace sin validar operativamente esa opción.
- No hay pedido mínimo publicado como requisito de compra: **200 € es el umbral de envío gratis**, no un mínimo de compra.
- Los costes son por **paquete de este vendedor**, no por la suma global de un carrito de varios vendedores.
- Verificar el régimen fiscal de las tarifas con el productor antes de introducirlas en el checkout.
- No deducir peso de títulos de producto ni usar peso = 0 para mercadería sin dato; revisar los pesos importados, incluidas variaciones, antes de activar reglas por peso.
- WCFM soporta envío por peso y configuración por vendedor: https://docs.wclovers.com/store-shipping/. Verificar si conviene más esa modalidad o métodos por zona, según el envío gratuito y las restricciones.
- No tocar la política ni las zonas del resto de vendedores; no activar métodos que abaraten accidentalmente carritos mixtos.

### Pruebas de checkout obligatorias

1. España peninsular, 3,9 kg y 199,99 € → 13,45 €.
2. España peninsular, 4,1 kg y 199,99 € → 16,45 €.
3. España peninsular, 7,1 kg y 199,99 € → 21,45 €.
4. España peninsular, 3,9 kg y 200,00 € → gratis.
5. Baleares, 2 kg y 199,99 € → 29 €; 200,00 € → gratis.
6. Canarias, Ceuta, Melilla, Madeira y Azores → sin opción de envío para Latitudes.
7. 20,1 kg → no permitir checkout normal para Latitudes hasta cotización confirmada.
8. Carrito Latitudes + otro vendedor → verificar que cada envío se calcule por separado y respete los umbrales **por vendedor**.
9. Verificar impuestos aplicados al método y tasa mostrada antes/después del pago.
10. Probar exactamente 4 kg, 7 kg y 20 kg; y artículos variables con varios pesos.

## Banner y enlazado SEO

- Sustituir la creatividad genérica «pollo, ternera, cerdo» por una identidad de **carnes exóticas y premium**, con fotografía gastronómica coherente y texto visible «Carnes exóticas y cortes premium». No confundir su oferta con carnicería convencional.
- Comprobar si el texto es parte de la imagen o un campo de WCFM antes de modificar el banner; usar imagen accesible y `alt` que no prometa disponibilidad de productos concretos.
- El módulo `latitudes-seo-links-20261015.php` vincula la ficha del productor y productos con artículos ya publicados de la categoría editorial de carnes, y añade un enlace comercial en artículos cuyos títulos tratan de carnes exóticas/wagyu. No genera enlaces si el vendedor no tiene stock visible.
- Para consolidar el clúster es recomendable revisar las entradas relevantes una a una y actualizar sus enlaces contextuales específicos y su redacción, sin forzar plantillas idénticas.
- Verificar versión española e inglesa, HTML de la tienda WCFM, títulos, repetición de bloques y canonicals antes de desplegar a producción.

## Estado de implementación

- Código listo para revisión mediante PR: diagnóstico (solo lectura) y enlaces editoriales condicionales.
- Pendiente acceso a base de datos / administración WordPress para confirmar y corregir estado/autor real de los productos y configurar y probar tarifas de envío.
- Pendiente subir y seleccionar la nueva creatividad en el banner del vendedor desde WCFM.
- No fusionar a producción ni dar por completado el lanzamiento hasta pasar las pruebas anteriores.
