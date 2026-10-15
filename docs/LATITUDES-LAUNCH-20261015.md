# Latitudes · diagnóstico y puesta en marcha (15/10/2026)

## Resultado y límites de acceso

- **Tienda pública:** se muestran 208 productos de seis productores, y Latitudes no aparece. No está demostrado si la causa es caché, atribución de vendedor, catálogo o envío.
- **Código GitHub:** diagnóstico de solo lectura, opciones de invalidación de caché, nueva lógica de envío y enlaces editoriales. **Los cambios de esta rama aún no están instalados en la web pública.**
- **WordPress privado:** no hay acceso autenticado a los ajustes del importador, metadatos reales de Latitudes, cachés del servidor, sesiones WooCommerce ni tarifas activas. No afirmar que se han modificado.

## Diagnóstico en WordPress

Tras instalar la rama **en staging**, abrir `Herramientas → Diagnóstico Latitudes` como gestor de WooCommerce y seguir esta secuencia:

1. Verificar si existe usuario WCFM con slug, nombre visible o nombre comercial "Latitudes". Si no existe, crear/identificar correctamente el vendedor; no basta con crear productos.
2. Verificar rol de vendedor o `wcfmmp_profile_settings.store_name`. El tema excluye cuentas `administrator` como vendedores, incluso si tienen productos.
3. Comprobar `_wcfm_store_offline`, `_disable_vendor` y rol `disable_vendor`.
4. Verificar si `MDO_Catalog_Destination_Frontend::excluded_vendor_ids()` excluye Latitudes para España; configurar el destino elegible.
5. Contar productos por `post_status`. Deben ser `publish`, estar visibles en catálogo y `instock`, sin exclusión taxonómica.
6. Comparar `post_author` con `wcfm_get_vendor_id_by_post()` para una selección de productos reales.
7. Revisar también el listado de autores de productos recientes: permite detectar si el importador los crea bajo el usuario administrador u otro proveedor. Corregir el origen en el importador, luego probar reimportación controlada.
8. **Después**, botón «Invalidar caché de productos»: transitorios WooCommerce, prefijo de caché y objetos de productos. Si el admin refleja productos válidos y la vista anónima sigue antigua, botón de borrado de cachés globales de plugins (WP Rocket, LiteSpeed, W3TC, WordPress, si están disponibles). Verificar por separado caché CDN (no existe integración directa aquí).
9. Abrir en incógnito `/tienda/`, `/productores/`, tienda WCFM de Latitudes y una ficha de producto; comparar recuentos y filtros. Limpiar las tablas de búsqueda WooCommerce si es preciso.

**No desactivar globalmente los filtros de catálogo para "forzar" a mostrar Latitudes**: las exclusiones protegen stock, vendedores desactivados y destinos no admitidos.

## Tarifa simplificada acordada para El Mercado de Origen

Regla comercial solicitada por el administrador, que **sustituye** los tramos de peso de Latitudes para esta tienda:

| Importe final de los productos de Latitudes (después de cupones, IVA incluido) | Envío |
|---|---:|
| Desde 0 hasta 199,99 € | 13,45 € |
| Desde 200,00 € | Gratis |

- Sin tramos ni recargos por peso; **200 € exactos dan envío gratis**.
- Solo para envíos de Latitudes en España peninsular, Portugal continental o Baleares.
- No se habilita Canarias, Ceuta, Melilla, Azores o Madeira: la web del productor los excluye.
- Sin cambios para los demás vendedores. Un carrito mixto debe estar **separado por paquetes por vendedor** para que se aplique esta tarifa únicamente al paquete Latitudes. El código evita sobrescribir un paquete mixto; si la separación falla, se necesita corregir WCFM antes del lanzamiento.
- El filtro `woocommerce_package_rates` devuelve una única tarifa para paquetes exclusivamente de Latitudes. Los identificadores de método y las comisiones deben comprobarse con el proveedor WCFM activo.
- Requiere validar el cálculo de IVA y los costes finales mostrados en checkout en la configuración real.
- Las condiciones del productor no ofrecen estas tarifas fijas para todos los pesos o Baleares; se trata de **una decisión comercial propia del marketplace**. Fuente del productor: https://latitudes.es/condiciones-de-venta/

### Casos de aceptación en checkout

1. Madrid, Latitudes 199,99 € → envío 13,45 €.
2. Madrid, Latitudes 200,00 € → gratis.
3. Madrid, Latitudes 200,01 € → gratis.
4. Madrid, Latitudes 210 € con cupón de 20,01 € → 13,45 € (si el umbral se calcula tras descuentos).
5. Baleares, Latitudes 50 € → 13,45 €.
6. Portugal continental, Latitudes 50 € → 13,45 €.
7. Canarias, Ceuta, Melilla, Azores y Madeira → no se ofrece el envío de Latitudes.
8. Carrito combinado Latitudes 80 € + otro vendedor 150 € → Latitudes 13,45 € y otro vendedor según su propia política (no enviar gratis Latitudes por sumar 230 €).
9. Carrito combinado Latitudes 210 € + otro vendedor 30 € → Latitudes gratis, el otro según su propia política.
10. Revisar importe final incluyendo impuestos de envío, caché de sesiones y separación de paquetes por vendedor.

## SEO, imagen y publicación

- En la rama se proponen módulos de enlace desde tienda de Latitudes hacia guías existentes de carnes, desde fichas de Latitudes hacia blog y desde posts titulados sobre carnes exóticas/wagyu hacia su tienda. No inventar URLs ni mostrar enlaces si los artículos/productos no existen.
- Creatividad disponible fuera de GitHub; cargarla como medio en WordPress/WCFM y cambiar el mensaje `pollo, ternera, cerdo` por `Carnes exóticas y cortes premium`.
- Los módulos están sujetos a revisión en staging: el hook de portada de WCFM puede variar; revisar ES/EN, cachés y semántica SEO.
- Se recomienda no fusionar a producción antes de comprobar el informe y los diez casos de checkout. El tema hijo solo despliega código: no cambia datos de la base de datos, páginas multimedia ni configuraciones WCFM.
