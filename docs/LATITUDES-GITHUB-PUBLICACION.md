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
6. Revisar una compra de prueba y las reglas nativas de envío de Latitudes en WCFM (Península 13,45 €, Baleares 29 €, gratis desde 200 €) antes de recibir pedidos reales.

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
