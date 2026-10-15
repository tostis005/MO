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
8. **Después**, botón «Invalidar caché de productos, tienda y vendedor»: limpia transitorios y objetos de WooCommerce, archivo estático de Home y URLs de la portada, la Tienda, Productores y Latitudes con los motores WP Rocket/LiteSpeed/W3TC, si están instalados. Si el admin refleja productos válidos y la vista anónima sigue antigua, botón explícito de borrado global. Verificar por separado caché del CDN/hosting (no existe integración directa aquí). El código también agrupa la invalidación tras `save_post_product` del vendedor mediante WP-Cron; puede no cubrir importadores que escriban SQL directamente.
9. Abrir en incógnito `/tienda/`, `/productores/`, tienda WCFM de Latitudes y una ficha de producto; comparar recuentos y filtros. Limpiar las tablas de búsqueda WooCommerce si es preciso.

**No desactivar globalmente los filtros de catálogo para "forzar" a mostrar Latitudes**: las exclusiones protegen stock, vendedores desactivados y destinos no admitidos.

## Tarifa simplificada acordada para El Mercado de Origen

Regla comercial solicitada por el administrador, que **sustituye** los tramos de peso de Latitudes para esta tienda:

| Importe final de los productos de Latitudes (después de cupones, IVA incluido) | Envío |
|---|---:|
| Península, hasta 199,99 € | 13,45 € |
| Baleares, hasta 199,99 € | 29,00 € |
| Península y Baleares, desde 200,00 € | Gratis |

- Sin tramos ni recargos por peso; **200 € exactos dan envío gratis** tanto en Península como en Baleares.
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
5. Baleares, Latitudes 50 € → 29,00 €; 200 € → gratis.
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


## Hallazgo confirmado: caché temprana de catálogo y productos

El drop-in `elmercadodeorigen-child/dropins/advanced-cache.php` guarda y sirve páginas públicas **antes de cargar WordPress**:

- `/tienda/` → `wp-content/uploads/elmercado-hot-static-v1/es-shop-root.html` con TTL 300 s y stale hasta 3600 s cuando otra petición revalida.
- Fichas de producto → `wp-content/uploads/elmercado-product-static-v1/<sha256-ruta>.html` con TTL 600–1200 s y stale hasta 3600 s.
- La tienda `/tienda/latitudes/` **no está** incluida en la lista de rutas calientes del drop-in.
- El tema anterior vaciaba transitorios y caché de la portada, pero **no eliminaba el HTML estático de /tienda/**: esa omisión se ha corregido.
- El módulo `latitudes-cache-maintenance-20261015.php` ahora borra el archivo temprano del catálogo, las fichas individuales de productos de Latitudes ya publicadas, la portada y las URL correspondientes de otros motores reconocidos. También ofrece el botón manual y aplica un debounce de 1 minuto tras eventos normales `save_post_product`.
- El informe admin muestra si el archivo existe, su antigüedad y si `WP_CACHE` está activo. **No se ha ejecutado ninguna purga en producción** por falta de acceso al servidor; el código queda preparado en la PR.

**Diagnóstico importante:** este hallazgo puede mantener desactualizado el catálogo global, pero **no explica por sí solo** una tienda individual de Latitudes vacía. Si tras el borrado sigue sin mostrar productos, atender primero a la asignación del vendedor, `publish`, stock, `Offline`, `Disabled` y la exclusión por destino.

## Cómo configurar realmente WCFM por zonas

Las tarifas propuestas en `inc/latitudes-shipping-20261015.php` son una salvaguarda de cálculo de checkout para **paquetes de Latitudes**. **No escriben las reglas en la base de datos** y no sustituyen los requisitos de visibilidad de la capa `MDO_Catalog_Destination_Frontend` del plugin externo.

En WordPress/WCFM debe constar:
1. `WooCommerce > Ajustes > Envío > Zonas de envío`: zonas globales existentes de **Península** y **Baleares**, con método **Store shipping**, sin cambiar las zonas utilizadas por otros productores. La pantalla de diagnóstico enumera las zonas y sus geografías.
2. `WCFM > Vendedores > Latitudes > Ajustes > Envíos`: habilitado `_wcfmmp_user_shipping_enable = yes`, tipo `by_zone`, zonas configuradas para el vendedor. La pantalla de diagnóstico muestra los flags y las zonas reales.
3. Aplicar dentro de las zonas de Latitudes la tarifa plana **13,45 € Península / 29 € Baleares** y el umbral de gratuidad **200 €**; evitar sumar dos opciones o generar tasas globales del carrito.
4. Confirmar en el diagnóstico que Latitudes **ya no se encuentra en `MDO_Catalog_Destination_Frontend::excluded_vendor_ids()`**. En caso contrario revisar la lógica de destinos del plugin externo; un filtro tardío de tasas de envío no puede resolver la exclusión de catálogo.
5. Verificar categorías, estatus de vendedor, autor del producto y ocultación por `stock` antes de purgar.
6. Invalidar las cachés de páginas y abrir el directorio y la tienda en una ventana anónima. La ausencia de 404 en un navegador es válida; el 404 visto desde algún rastreador externo no prueba un error real del sitio.
7. Probar los casos de checkout anteriores y documentar tarifas finales.

**Estado técnico a 15/10/2026:** Cambios disponibles únicamente en la PR en borrador. Sin acceso a sesión administradora ni a las zonas reales de WooCommerce/WCFM no se ha pulsado el botón de purga, no se han escrito zonas ni se puede dar por resuelto el catálogo. 

Fuentes: [WCFM Store Shipping](https://docs.wclovers.com/store-shipping/), [Condiciones Latitudes](https://latitudes.es/condiciones-de-venta/).

## Comparativa Latitudes vs vendedores que funcionan (15/10/2026)

Comprobación pública: los productores Montjam (15 productos) y Selectos de Castilla (77 productos) muestran sus catálogos para visitantes. Latitudes figura como productor en `/productores/`, pero `/tienda/` continúa con **208 resultados** y solo seis vendedores con artículos. El rastreador externo no puede abrir la URL de Latitudes; el propietario sí la abre en incógnito, por lo que un 404 del rastreador no sirve para determinar el estado real.

La función `elmercado_vendor_store_product_ids_010225` exige `post_status=publish`, `post_author=ID del usuario vendedor` y términos de visibilidad válidos. `elmercado_catalog_counts_excluded_authors_010217` también excluye proveedores por estado Offline/Disabled y destino de envío. Diferencias **posibles pero aún no confirmadas en la base de datos**:
1. Productos importados con `post_author` administrador/u otro vendedor.
2. Propiedad detectada por WCFM distinta de `post_author`.
3. Productos no publicados, sin stock o con `exclude-from-catalog`.
4. Sin zonas habilitadas en WCFM para España y, por tanto, excluido por `MDO_Catalog_Destination_Frontend`.
5. Slug WCFM `latitudes` personalizado sin usuario con `user_nicename=latitudes`. La resolución del tema se ha reforzado validando la URL canónica de WCFM.

**Herramientas → Diagnóstico Latitudes** incluye ahora una tabla comparativa con productores activos y una búsqueda de artículos especializados por título para detectar propiedad incorrecta. Si WCFM identifica explícitamente un producto como suyo pero su autor WordPress es diferente, muestra un botón manual con nonce para corregir **únicamente esos autores** y limpiar caché. No modifica publicaciones, stock, precios ni artículos atribuidos a otros productores por WCFM.

## Banner específico de Latitudes

- Nueva ilustración vectorial original en `assets/images/latitudes-premium-banner.svg`, oscura y ligera, con temática gourmet/premium.
- Nuevo módulo `inc/latitudes-store-banner-20261015.php` con alcance exclusivo a Latitudes. Oculta la antigua fotografía/texto WCFM de esa tienda y presenta `Latitudes · Carnes exóticas y cortes premium`, subtítulo contextual y versión inglesa. Mantiene el logo del productor y el diseño responsive general del marketplace.
- También se ha generado una **versión fotográfica optimizada** independiente `banner_latitudes_carnes_premium_1920x576.webp` para subir cuando se disponga del acceso a medios de WordPress. No está incorporada a GitHub y no se le debe atribuir presencia en producción.
- Probar cabecera a 375, 768 y 1440 px, visibilidad del logo, texto legible y posibles banners antiguos de caché.

**Estado:** la PR contiene los cambios de código. No está fusionada; tampoco hay confirmación de que el importador o los métodos nativos WCFM hayan sido corregidos en producción. Para completar se requiere la pantalla de diagnóstico real y prueba de carrito con zonas.

