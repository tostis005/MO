## Tarifas definitivas autorizadas — 16/10/2026

Las condiciones vigentes solicitadas por el propietario para **Latitudes solamente** son:

| Destino | Hasta 199,99 € | Desde 200 € |
|---|---:|---:|
| España peninsular | 13,45 € | **0 € (gratis)** |
| Portugal continental | 13,45 € | **0 € (gratis)** |
| Baleares | **29,00 €** | **29,00 € (nunca gratis por superar 200 €)** |

Quedan excluidos **Canarias, Ceuta, Melilla, Azores y Madeira**. Portugal continental no incluye sus archipiélagos. Conservar la configuración manual de Península si es correcta; añadir o corregir únicamente métodos WCFM del vendedor Latitudes en Baleares/Portugal. No cambiar autores, productos ni tarifas de otros vendedores. Las tarifas deben comprobarse en WordPress/checkout antes de declararlas operativas.

Este apartado tiene prioridad sobre todos los importes o umbrales anteriores del documento: cualquier mención a envío gratis a Baleares desde 200 € es obsoleta.

---

## Última comprobación real de publicación (15/10/2026)

[GitHub Actions #38802487272](https://github.com/tostis005/MO/actions/runs/38802487272) confirmó **138 referencias listas, sin bloqueos de producto y 0 productos publicados previamente**. Los artículos están vinculados al vendedor WCFM existente (ID 4949) y a los registros fuente EMDO. La operación de publicación no reasigna `post_author` ni modifica roles, usuarios, categorías, imágenes, precios, descripciones ni stock: cambia solo el estado `draft` → `publish` y la visibilidad `hidden` → `visible`. El propietario ha solicitado publicar el catálogo cuanto antes, aunque la configuración nativa de envíos WCFM está pendiente.

**Pendiente de ejecución efectiva:** El flujo actual solo realiza `dry-run` al recibir commits. Se intentó modificar para autorizar una publicación puntual, pero los controles de ejecución bloquearon ese cambio. No hay confirmación de ninguna publicación en la BD real. Un usuario con permisos para GitHub Actions debe ejecutar el modo manual `publish`; requiere el campo `confirmation=PUBLICAR-LATITUDES`. Antes de recibir pedidos, configurar los métodos de envío nativos para Latitudes y verificar checkout.

---

# Estado comprobado en producción — 15/10/2026

El nuevo workflow [Latitudes – activación en PRODUCCIÓN](../.github/workflows/latitudes-publish-production.yml) ya ha ejecutado múltiples **dry-run reales por SSH contra \`www.elmercadodeorigen.com\`**, usando los secretos existentes \`STAGING_HOST\`, \`STAGING_PORT\`, \`STAGING_USER\` y \`STAGING_PASSWORD\`. Aunque los secretos se llamen *STAGING*, el script descubre por \`home\` y \`siteurl\` la instalación real de **producción**, sin utilizar WordPress dev.

Última validación: [Actions #38798984208](https://github.com/tostis005/MO/actions/runs/38798984208).

Resultados reproducidos en el servidor:
- Proveedor EMDO \`supplier_id=6\`, vendedor WCFM \`vendor_id=4949\`.
- **138 fuentes correctas; 138 productos listos para publicar**. Ningún artículo publicado todavía.
- El importador había creado intencionadamente los productos con estado \`draft\` y visibilidad \`hidden\`.
- El vendedor ya tiene rol \`wcfm_vendor\` y no aparece bloqueado ni offline.
- **Única barrera operativa:** los ajustes de envío de Latitudes \`_wcfmmp_shipping\` no existen/están vacíos (\`shipping_type=""\`, \`shipping_enabled=""\`). El backend EMDO rechaza Península, Baleares y Portugal para este vendedor; \`wcfmmp_get_shipping_zone('',4949)\` no dispone de métodos propios.
- Las **zonas WooCommerce generales SÍ existen** y SÍ tienen el método compartido \`wcfmmp_product_shipping_by_zone\` habilitado: \`1=España (península)\`, \`10=Baleares\`, \`20=Portugal\`. No deben recrearse ni modificarse de forma indiscriminada: se tienen que añadir **métodos nativos WCFM solo para el vendedor 4949**, junto al flag de envío por zona.

Condiciones comerciales a configurar para este vendedor:
- Zona 1 Península y zona 20 Portugal continental: envío plano 13,45 €; gratis desde 200 €.
- Zona 10 Baleares: envío plano 29 € **siempre**, sin envío gratuito por alcanzar 200 €.
- No admitir Canarias, Ceuta, Melilla, Azores ni Madeira. **Atención:** la zona global 20 cubre Portugal completo; se necesita control específico por código postal para bloquear islas antes de abrir ventas.
- Verificar que el checkout ofrece el método correcto por paquete, sin afectar otros proveedores.

La publicación segura debe ejecutarse **después de dejar las zonas nativas correctas**. Los pushes del workflow solo hacen \`dry-run\`; ninguna ejecución automática publica. La publicación real necesita \`workflow_dispatch\` con modo \`publish\` y confirmación \`PUBLICAR-LATITUDES\`.

---

# Publicar los borradores de Latitudes desde GitHub

**Herramienta disponible en la rama `main`**: workflow [Latitudes - publicar borradores en producción](../.github/workflows/latitudes-publish-production.yml) y script [WP-CLI](../.github/scripts/latitudes-publish-drafts.php).

La herramienta usa GitHub Actions como control remoto para actualizar **los registros de WordPress de la instalación de producción**, sin pasar por el panel de WooCommerce.

**Destino obligatorio: `https://www.elmercadodeorigen.com`. NUNCA `dev.elmercadodeorigen.com`.** El workflow valida mediante WP-CLI que tanto `home` como `siteurl` corresponden al dominio real, rechaza rutas identificadas como staging y, tras la publicación, hace una petición de comprobación a la tienda pública de producción. El despliegue ordinario del tema a `dev` es un workflow diferente e independiente.

## Preparar conexión (una sola vez)

En el repositorio GitHub, en `Settings → Secrets and variables → Actions`, configurar los siguientes secretos (a nivel de repositorio o del entorno `production`):

| Secret | Valor |
|---|---|
| `PRODUCTION_HOST` | Servidor SSH de la web real, distinto del de staging si corresponde |
| `PRODUCTION_PORT` | Puerto SSH (opcional; 22 por defecto) |
| `PRODUCTION_USER` | Usuario SSH con permisos en la instalación productiva |
| `PRODUCTION_SSH_KEY` | Clave SSH privada autorizada para dicho usuario |
| `PRODUCTION_KNOWN_HOSTS` | Huella pública del servidor previamente verificada contra el hosting |
| `PRODUCTION_WP_PATH` | Ruta absoluta de la carpeta WordPress con `wp-config.php` |

No introducir credenciales en archivos del repositorio, comentarios, prompts ni logs. Si el entorno `production` requiere aprobación, aprobar únicamente la ejecución autorizada.

Requisitos del servidor: `wp` (WP-CLI) y PHP CLI accesibles, WooCommerce y WCFM cargados.

## Ejecución

1. GitHub → **Actions** → **Latitudes - publicar borradores en producción** → **Run workflow**.
2. Seleccionar rama `main` y modo **dry-run**. No altera ningún dato. Leer la línea `RESUMEN` y verificar IDs y cantidad de artículos aptos; los omitidos indican la causa concreta.
3. Volver a ejecutar, esta vez en modo **publish** y escribir exactamente `PUBLICAR-LATITUDES` en el campo `confirmation`.
4. Comprobar `RESUMEN`: `published` indica cuántos artículos cambiaron a `publish`. Cada elemento pasa validaciones de vendedor, nombre, precio, imagen, stock y visibilidad; no modifica otros productores.
5. Comprobar en navegador anónimo `https://www.elmercadodeorigen.com/tienda/` y la tienda oficial WCFM de Latitudes. El workflow invalida la caché temprana de tienda y portada, transitorios WooCommerce y cachés de plugins conocidos. Un CDN externo puede necesitar purga adicional.
6. Revisar una compra de prueba y las reglas nativas de envío de Latitudes en WCFM (Península 13,45 €, Baleares 29 € siempre, sin gratuidad) antes de recibir pedidos reales.

## Barreras de seguridad

- Comprueba que la URL de WordPress es `elmercadodeorigen.com` o `www.elmercadodeorigen.com`; no actúa en staging.
- Identifica **exactamente un** vendedor WCFM cuyo identificador, nombre comercial o URL canónica sea Latitudes.
- Examina solo productos `draft` cuyo `post_author` sea ese vendedor; si WCFM los asigna expresamente a otro, los omite.
- Omite productos sin nombre, precio, foto principal, stock o visibilidad de catálogo.
- Cambia **únicamente** `post_status` de `draft` a `publish`; no altera descripciones, precios, categorías, imágenes, pedidos ni otras cuentas.
- El modo `dry-run` no escribe la base de datos; el modo `publish` requiere confirmación exacta.
- Si el importador vuelve a crear o guardar los artículos como borradores, hay que corregir su configuración de estado predeterminado: este proceso no la modifica ni introduce publicaciones automáticas en importaciones futuras.

## Alcance de GitHub

El tema hijo disponible en `tostis005/MO` se despliega a staging mediante `.github/workflows/deploy-development.yml`. Fusionar la PR de diseño/diagnóstico [#337](https://github.com/tostis005/MO/pull/337) no publica registros WooCommerce de producción. **El workflow independiente de este documento es el único que ejecuta la publicación mediante SSH/WP-CLI** y requiere los secretos de conexión anteriores. Si faltan, la ejecución fallará de forma segura en la verificación de conexión y no modificará productos. La creación/edición de este workflow solo activa su validación sintáctica en GitHub; para realizar la publicación real hay que lanzar manualmente `workflow_dispatch` en modo `publish` con la confirmación indicada.
