# Tropitop — alta técnica y puesta en marcha (20/10/2026)

**Estado:** desarrollo en rama; **NO activado en tienda**. Sin enlace comercial ni aprobación de tarifas no se publican productos ni se aceptan pedidos.

## Identificación contrastada
- Marca: **Tropitop**.
- Titular: **NATURE TROPICAL FRUIT, S.L.U.**; NIF **B44531150**.
- Polígono Industrial Ull Fondo, Carrer Marisol 14, 12539 Les Alqueries (Castellón).
- Web: https://tropitop.com/es/
- Contacto público: info@tropitop.com · +34 964 975 107.
- Fuente legal: https://tropitop.com/es/content/3-terminos-y-condiciones

**Descripción original propuesta para ficha WCFM:** «Tropitop es un proyecto de fruta de origen vinculado a Castellón que comercializa aguacates, cítricos y otras especialidades de huerta y frutas tropicales. En El Mercado de Origen, las cajas se ofrecerán por su peso real, con precio y disponibilidad sujetos al catálogo actualizado del productor.»

## Productos y sincronización
- Origen exacto solicitado: https://tropitop.com/es/aguacates/33-3063-comprar-aguacate-bacon.html#/407-kilos-900_kg
- Una ficha WooCommerce **variable** (no tres fichas duplicadas) para aguacate piel lisa BIO / Bacon; peso obligatorio: **3 / 4 / 9 kg**.
- Referencia orientativa de la categoría española a 20/10/2026 (no hardcodear): 3 kg 14,90 €, 4 kg 17,90 €, 9 kg 32,90 €. La sincronización debe validar la matriz actual, el stock de cada referencia y las tarifas mostradas en **/es/**, no /eu/.
- El fragmento del enlace (`#/407-kilos-900_kg`) selecciona la variante de 9 kg en la web origen y no crea otro producto.
- Conector: `tropitop`, clase `MDO_Connector_Tropitop`, limitado al host HTTPS autorizado. Si la ficha no ofrece todas las combinaciones con precio, no importar una versión simple incorrecta. Mantener productos nuevos en revisión `pending`.
- Además del enlace directo, para ampliar el surtido utilizar categorías reales como `https://tropitop.com/es/12-aguacates`; sus paginaciones requieren pruebas con catálogo real antes de escalar a otros productos.

## Logística

Según la política de Tropitop:
- **Destinos**: España peninsular, Portugal, Francia, Países Bajos, Luxemburgo, Polonia, Dinamarca, Suecia, Austria, Rumanía y Finlandia. No Canarias, Ceuta, Melilla ni Baleares (la cobertura declarada es sólo peninsular).
- **Precio del envío**: varía con destino y cantidad; **no hay una tabla pública exacta**. NO poner tarifa fija, envío gratis ni inventar importes.
- **Preparación/salida**: cosecha y expedición martes/miércoles. Pedidos hasta miércoles 13 h se preparan en esa semana; los posteriores pasan a la siguiente. Tránsito habitual anunciado: 24–48 h **desde el envío**, sujeto al destino.
- La clase `MDO_Tropitop_Shipping` impide calcular tarifas y tramitar checkout del proveedor si `mdo_tropitop_shipping_rates_verified` no está activado tras validar la tabla completa en WCFM.
- La validación de zonas actúa sobre **paquetes del vendedor**, no sobre otros proveedores del carrito.

## Pasos obligatorios de puesta en producción

1. Desplegar EMDO **1.0.67** en staging y ejecutar `php -l` sobre sus PHP modificados.
2. Ejecutar una sola vez desde la raíz del WordPress: `wp eval-file wp-content/plugins/mdo-supplier-sync/scripts/onboard-tropitop.php`. Crea ficha EMDO **inactiva**, sin fabricar cuentas o acuerdos comerciales. Si ya existe, no duplica.
3. Crear/verificar el usuario WCFM real con identificador estable y asignarlo en **EMDO → Proveedores → Tropitop**. Introducir ficha WCFM, nombre visible, descripción original, logotipo/banner sólo con material autorizado o propio, datos reales y condiciones comerciales pactadas.
4. Pedir a Tropitop la **tabla completa de portes**, para los destinos servidos, tramos de peso/cantidad (3, 4, 9 kg y combinaciones) y qué ocurre con carritos mixtos. Comprobar cotizaciones de prueba en su checkout real y mantener una evidencia de cada zona/tarifa. Configurar las zonas y métodos **WCFM del vendedor** con idénticas reglas. Validar que no se aplica envío gratuito de WooCommerce si no corresponde.
5. Verificar autorización de uso de imágenes, logotipo y textos. El conector actual no copia imágenes sin autorización expresa; `mdo_tropitop_media_authorized` es falso por defecto. Usar recursos propios licenciados.
6. Hacer scraping y comparar **todas las variantes/precios/stock** con la web /es/; probar variaciones con WCFM, pesos, categorías, impuestos (fruta IVA aplicable), idiomas, correos, SEO y pruebas de carrito con 1 y varios vendedores.
7. Tras QA, y *sólo entonces*, establecer `wp option update mdo_tropitop_shipping_rates_verified 1`. Activar proveedor, asignar frecuencia `daily`, importar pendientes que pasen revisión, habilitar fichas y verificar checkouts ES, PT y otro país admitido; probar bloqueos en Baleares/Canarias/Ceuta/Melilla e indisponibilidad de stock.

## Pruebas mínimas exigidas antes de merge/producción

- URL principal y URL de combinación generan el mismo identificador de producto, y la misma variante 9 kg.
- 3, 4, 9 kg mantienen tarifas individuales correctas y pesos WooCommerce para el cálculo por tramos.
- Si una combinación desaparece o cambia precio, no se inventa ni se sustituye por el precio de otra.
- Sin tabla de portes verificada, la ficha y las compras quedan bloqueadas.
- Las rutas de otros proveedores y sus gastos de envío no cambian.
- No se copian imágenes, logotipos ni textos del origen sin licencia.
- Sin relación de proveedor/operativa de dropshipping confirmada, no activar venta.
