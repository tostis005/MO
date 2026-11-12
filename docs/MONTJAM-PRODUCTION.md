# Despliegue en producción — mínimo de 5 sobres Montjam

## Qué se despliega

El workflow [deploy-montjam-sobres-production.yml](../.github/workflows/deploy-montjam-sobres-production.yml) instala **solo** el archivo `elmercadodeorigen-child/inc/montjam-sliced-minimum-20261111.php` como plugin de uso obligatorio (MU plugin) en:

`wp-content/mu-plugins/elmercado-montjam-minimum.php`

No sustituye el tema, plugins, base de datos ni configuraciones de WooCommerce.

La regla aplica a los productos publicados:
- `lomo-bellota-100-iberico-loncheado-montjam-90g`
- `chorizo-cular-iberico-extra-loncheado-montjam-90g`

También queda preparada para un futuro producto de salchichón **loncheado** Montjam. No afecta al salchichón en pieza.

El comprador empieza en 5 sobres y puede pedir 6, 7, 8, etc. Cada producto exige su propio mínimo.

## Secrets requeridos

Configurar en el entorno GitHub Actions `production` (Settings → Environments → production → Environment secrets):

| Secret | Valor |
| --- | --- |
| `PRODUCTION_HOST` | Host/IP de SSH verificado del servidor de producción (sin protocolo) |
| `PRODUCTION_PORT` | Puerto SSH, normalmente 22 (opcional) |
| `PRODUCTION_USER` | Usuario SSH con permiso para editar `wp-content/mu-plugins` |
| `PRODUCTION_PASSWORD` | Contraseña SSH, **no** la contraseña de WordPress |
| `PRODUCTION_KNOWN_HOSTS` | Clave/huella de host obtenida y **verificada** fuera de banda |
| `PRODUCTION_WP_PATH` | Ruta absoluta de la instalación WordPress de producción que contiene `wp-config.php` |

No escribir credenciales en archivos versionados. No copiar los secretos de desarrollo sin verificar permisos y destino.

## Ejecución

- Al modificar el módulo o el workflow en `main`, GitHub Actions intenta el despliegue.
- También puede lanzarse manualmente desde **Actions → Deploy Montjam sachet minimum to production → Run workflow**.
- Antes de escribir comprueba la URL exacta de producción y que el tema hijo esperado esté activo.
- Antes de reemplazar un MU plugin existente, hace una copia de seguridad; si falla la comprobación de las reglas, restaura la copia o elimina el nuevo archivo.
- Finalmente vacía la caché de objetos de WP-CLI y comprueba que las páginas públicas contienen «Pedido mínimo».

La ejecución se detiene **sin modificar producción** si falta algún secret o si el destino no corresponde exactamente a `elmercadodeorigen.com`.

## Comprobación manual

1. Abrir las fichas de lomo y chorizo loncheados Montjam.
2. El selector empieza en **5** y sube de **1 en 1**.
3. Una compra de **1–4** de cualquiera debe rechazarse; **5, 6, 7...** deben aceptarse.
4. Confirmar que las piezas enteras no están afectadas.

**Importante:** guardar cambios en GitHub no significa que ya se haya desplegado. Confirmar el workflow completado y probar el carrito real en producción.
