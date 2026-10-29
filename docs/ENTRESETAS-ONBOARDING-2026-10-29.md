# EntreSetas — alta técnica y catálogo en revisión (29/10/2026)

## Identificación
- Marca: EntreSetas. Web oficial: https://www.entresetas.com/
- Tienda origen del conector: https://www.entresetas.com/tienda/
- Actividad: cultivo y elaboración de setas frescas, deshidratadas, conservas, harinas, sales e infusiones.
- Ubicación: Paredes de Nava (Palencia), España.
- Condiciones y contacto: https://www.entresetas.com/condiciones-de-compra/
- Catálogo visible a 29/10/2026: aproximadamente 26 referencias; la cifra puede cambiar.
- Los precios públicos del productor no suponen un acuerdo de precios, margen o reventa con El Mercado de Origen.

## Integración EMDO
- Plugin: versión 1.0.70.
- Conector: MDO_Connector_EntreSetas en mdo-supplier-sync/connectors/class-mdo-connector-entresetas.php.
- Proveedor EMDO: entresetas; vendedor WCFM propio: entresetas.
- Fuente: /tienda/, con fichas /product/<slug>/ deduplicadas. Se aceptan sólo URLs HTTPS del dominio oficial, y 20–100 fichas antes de conciliar para evitar bajas por fallos del origen.
- Frecuencia de sincronización: diaria. Las fichas quedan en Productos origen, y luego pueden importarse a WooCommerce como borradores ocultos.
- Precio mayor que cero, stock coherente, matriz de variantes íntegra. No inventar cantidades, ingredientes, certificaciones, alérgenos, condiciones de conservación ni propiedades medicinales.
- Categorías: Setas y hongos → Setas frescas / Setas deshidratadas / Conservas y elaborados de setas / Harinas y sales de setas / Infusiones y extractos de setas. Familias ambiguas quedan pendientes.
- Los datos de imágenes se almacenan sólo como referencia en el registro del producto origen. No subir fotos de terceros sin autorización documentada.

## Provisión
- Script WP-CLI idempotente: mdo-supplier-sync/scripts/onboard-entresetas.php.
- Workflow: .github/workflows/production-entresetas-onboarding-20261029.yml, después de un deploy verificado del plugin. Permite ejecución manual.
- Fases ENTRESETAS_PHASE: setup, discover, import, report.
- setup: verifica WordPress producción, crea vendedor WCFM con alias interno comprobado y contraseña aleatoria, asigna descripción de tienda, lo deja deshabilitado (_disable_vendor=1) y registra el productor en EMDO con el conector propio.
- discover: analiza fuente y lanza sincronización EMDO sin depender de otros vendedores.
- import: lotes de ocho, sólo referencias verificables; exige estado draft, visibilidad hidden y autor del vendedor EntreSetas.
- report: informa proveedor, vendedor, categorías importadas, estado de sync y errores; sale con fallo ante productos publicados o vínculos incoherentes.
- No sobreescribir la configuración comercial de un productor ya existente en una nueva ejecución.

## Antes de vender
1. Acreditar acuerdo de suministro/dropshipping y comisión o margen comercial.
2. Validar zonas y precios de envío del vendedor WCFM: pedidos mínimos, pesos, destinos, perecederos/frío y carritos mixtos. La tarifa pública del checkout de EntreSetas no constituye por sí sola un acuerdo aplicable.
3. Acreditar derechos de las fotografías, logotipo y banner. Hasta entonces el catálogo queda sin esas imágenes, en privado.
4. Revisar SKU, precios, peso, impuestos, variantes, disponibilidad, ingredientes/alérgenos, vida útil y conservación; clasificar y redactar SEO con datos verificables.
5. Ensayar checkout de un vendedor y con varios vendedores y verificar tarifas, correos, IVA y países admitidos.
6. Sólo tras esas aprobaciones marcar los flags mdo_entresetas_commercial_ready y mdo_entresetas_media_authorized y habilitar voluntariamente la venta.

No confundir el despliegue de código con la importación real: el reporte ENTRESETAS_REPORT del workflow y la revisión del administrador WooCommerce acreditan el estado en producción.
