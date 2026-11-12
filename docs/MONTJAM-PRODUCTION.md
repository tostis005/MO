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

## Conexión de producción reutilizada

El workflow reutiliza las credenciales que ya utiliza
`.github/workflows/publish-approved-producer-banners-20261020.yml`:

- `STAGING_HOST`
- `STAGING_PORT` (si no existe, 22)
- `STAGING_USER`
- `STAGING_PASSWORD`

Aunque se llamen `STAGING_*`, el workflow de banners las usa para editar **producción**.
El destino está limitado a la instalación de WordPress confirmada en ese workflow:

`/var/www/vhosts/wizardly-shockley.82-223-25-247.plesk.page/httpdocs`

**Antes de escribir**, el workflow verifica `wp-config.php` y la opción `siteurl` para confirmar que la instalación es `elmercadodeorigen.com`. Si no puede comprobarlo, se detiene.

La publicación NO necesita secretos nuevos `PRODUCTION_*` ni sincroniza archivos del tema. La misma conexión SSH que publicaba los banners permite escribir exclusivamente el plugin MU de los sobres Montjam.

## Ejecución

- Al modificar el módulo o el workflow en `main`, GitHub Actions intenta el despliegue.
- También puede lanzarse manualmente desde **Actions → Deploy Montjam sachet minimum to production → Run workflow**.
- Antes de escribir comprueba la URL exacta de producción y que el tema hijo esperado esté activo.
- Antes de reemplazar un MU plugin existente, hace una copia de seguridad; si falla la comprobación de las reglas, restaura la copia o elimina el nuevo archivo.
- Finalmente vacía la caché de objetos de WP-CLI y comprueba que las páginas públicas contienen «Pedido mínimo».

La ejecución se detiene **sin modificar producción** si faltan las credenciales SSH existentes o si el destino no corresponde exactamente a `elmercadodeorigen.com`. Al finalizar correctamente publica el estado `montjam/production-verified` en el commit de GitHub, pero únicamente después de comprobar el servidor y las fichas públicas.

## Comprobación manual

1. Abrir las fichas de lomo y chorizo loncheados Montjam.
2. El selector empieza en **5** y sube de **1 en 1**.
3. Una compra de **1–4** de cualquiera debe rechazarse; **5, 6, 7...** deben aceptarse.
4. Confirmar que las piezas enteras no están afectadas.

**Importante:** guardar cambios en GitHub no significa que ya se haya desplegado. Confirmar el workflow completado y probar el carrito real en producción.
