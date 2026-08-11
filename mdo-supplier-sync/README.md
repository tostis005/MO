# El Mercado de Origen - Supplier Sync

Plugin interno para administrar proveedores y, progresivamente, sincronizar sus catálogos públicos con WooCommerce/WCFM.

## V0.1.0

- Crea y versiona tablas propias al activarse.
- Panel de resumen, proveedores, productos origen e historial.
- Ficha de proveedor con URL origen, código interno, vendedor WordPress/WCFM, conector, condiciones comerciales, frecuencia, email y exclusiones por fragmento de URL.
- Infraestructura de Action Scheduler/WP-Cron preparada sin modificar productos mientras no exista un conector validado.
- Historial técnico de ejecuciones.

Los conectores de scraping se incorporarán de forma incremental empezando por Tolecarnes en staging.
