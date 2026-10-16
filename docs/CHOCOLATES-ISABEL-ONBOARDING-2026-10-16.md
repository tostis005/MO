# Chocolates Artesanos Isabel — alta segura en El Mercado de Origen (16/10/2026)

## Fuente y arquitectura
- Origen: https://chocolatesartesanosisabel.com/tienda/ (índice de categorías). Los productos reales se encuentran en archivos `/categoria-producto/<slug>/`, paginados, y las fichas individuales en `/producto/<slug>/`.
- Conector: `mdo-supplier-sync/connectors/class-mdo-connector-chocolates-isabel.php`. Deduplica fichas presentes en varias categorías, valida nombres, precios, fotos y disponibilidad (HTML + datos estructurados). Bloquea discrepancias o estados de stock desconocidos. Para variantes AJAX/personalización sin matriz fiable, NO importa una ficha simple incorrecta.
- Proveedor interno: `chocolates-artesanos-isabel`, sincronización diaria por EMDO. Importaciones ligadas al vendedor WCFM independiente de Latitudes.
- Ruta de puesta en marcha en PRODUCCIÓN: `.github/workflows/production-chocolates-isabel-onboarding-20261016.yml`. Se inicia tras desplegar correctamente el plugin EMDO a producción y admite ejecución manual. Verifica el dominio WordPress, los hashes de los archivos del plugin y el catálogo en origen.
- No usa la web de desarrollo; se resuelve por `home`/`siteurl` del WordPress real.

## Barreras antes de la venta
- El vendedor se crea **deshabilitado**, sin acceso público, utilizando el patrón ya existente de correos internos correlativos; nunca se reutiliza la identidad del vendedor de Latitudes.
- El perfil WCFM incluye descripción y activos visuales propios: logo oficial del sitio cuando se puede descargar y banner oficial suficientemente grande o montaje de tres fotografías reales de productos del proveedor.
- Los productos se importan como **borradores ocultos** y con `post_author` del nuevo vendedor.
- Mantener la información de composición, ingredientes, alérgenos, origen, peso, caducidad y conservación que figure en la ficha origen; revisar producto por producto tras sincronización. No inventar ninguna afirmación alimentaria.
- Categorías compactas: «Chocolates artesanos», «Tabletas de chocolate», «Bombones y chocolatinas», «Cremas de cacao», «Cacao para beber y cocinar». No crear una categoría por cada colección de la tienda.
- El informe `ISABEL_REPORT` da IDs, estado de fuentes, importaciones, logo, banner y anomalías. Los productos con valores ambiguos quedan pendientes de revisión, no publicados.

## Pendiente de acuerdo comercial, NO inferir de la web pública
- Confirmación del porcentaje/comisión para El Mercado de Origen y si hay reglas por pedido.
- Tarifas y destinos de envío por vendedor WCFM (Península, Baleares, Portugal, exclusiones e islas), umbral de envío gratuito y fecha de corte, pedido mínimo si aplica. La política minorista del proveedor (gratuito desde 59 € en su web) no constituye por sí misma un acuerdo para la plataforma.
- Revisar embalaje térmico y restricciones de transporte con calor (el proveedor recomienda conservar entre 14–20 ºC y advierte que a 24–25 ºC el chocolate puede alterarse físicamente).
- Revisar colecciones de temporada, artículos personalizados, talleres/visitas y packs complejos para decidir exclusión o implementación específica, siempre sin falsear stock/precio.
- Verificación del checkout del proveedor y compras de prueba antes de habilitar o publicar.

## Observabilidad
Tras un push en `main`:
1. Esperar conclusión de «Deploy MDO Supplier Sync to production».
2. Revisar «Chocolates Isabel - onboarding seguro en produccion», sus logs y la línea `ISABEL_REPORT`.
3. Si el conector encuentra una ficha no fiable, revisar incidentes; no forzar publicación.
4. Una vez estable y con acuerdo comercial, configurar métodos de envío nativos WCFM (sin tocar otros vendedores); una publicación posterior debe estar expresamente autorizada.
