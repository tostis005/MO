# Despliegue automático al entorno de desarrollo

El workflow `.github/workflows/deploy-development.yml` sincroniza únicamente la carpeta `elmercadodeorigen-child/` con la carpeta del tema hijo en el servidor. No toca la base de datos, `wp-content/uploads`, plugins ni el tema padre.

## 1. Requisitos del hosting

El servidor debe ofrecer:

- Acceso **SSH** mediante usuario y contraseña.
- `rsync` instalado en el servidor.
- Permisos de escritura sobre `wp-content/themes/elmercadodeorigen-child`.
- El tema padre **Woostify** instalado en WordPress.

`STAGING_PASSWORD` debe contener la contraseña del usuario SSH. No es la contraseña del administrador de WordPress. Una contraseña de FTP solo funcionará cuando el proveedor utilice las mismas credenciales para SSH.

## 2. Secrets necesarios en GitHub

En el repositorio, ve a **Settings → Secrets and variables → Actions → New repository secret** y crea:

| Secret | Contenido |
|---|---|
| `STAGING_HOST` | Host o IP del servidor de desarrollo, sin `https://` |
| `STAGING_PORT` | Puerto SSH, normalmente `22` |
| `STAGING_USER` | Usuario SSH del servidor |
| `STAGING_PASSWORD` | Contraseña del usuario SSH |
| `STAGING_KNOWN_HOSTS` | Huella SSH verificada del servidor |
| `STAGING_REMOTE_PATH` | Ruta absoluta terminada en `/wp-content/themes/elmercadodeorigen-child` |

Ejemplo de `STAGING_HOST`:

```text
desarrollo.elmercadodeorigen.com
```

Ejemplo de `STAGING_REMOTE_PATH`:

```text
/home/usuario/public_html/wp-content/themes/elmercadodeorigen-child
```

La validación rechazará rutas relativas, rutas con caracteres de shell o cualquier destino que no termine exactamente en la carpeta esperada.

## 3. Obtener `STAGING_KNOWN_HOSTS`

Desde una terminal, ejecuta sustituyendo host y puerto por los del hosting:

```bash
ssh-keyscan -p 22 desarrollo.elmercadodeorigen.com
```

Copia toda la salida en el secret `STAGING_KNOWN_HOSTS`.

Antes de guardarla, compara la huella con la que muestra el panel del hosting o solicítala al soporte. Esta comprobación evita que el workflow entregue la contraseña a un servidor suplantado.

En Windows se puede ejecutar desde PowerShell cuando el cliente OpenSSH está instalado. También puede obtenerse conectando una primera vez por SSH y verificando la huella mostrada por el proveedor.

## 4. Habilitar GitHub Actions

Ve a **Settings → Actions → General**:

1. En **Actions permissions**, permite GitHub Actions para el repositorio.
2. Permite las acciones creadas por GitHub; el proyecto utiliza `actions/checkout@v4`.
3. En **Workflow permissions**, deja `Read repository contents permission`.

Los workflows no necesitan permiso de escritura sobre el repositorio.

## 5. Comprobaciones antes de fusionar

- `STAGING_HOST` no debe incluir `https://`, barras ni una ruta.
- `STAGING_PORT` debe ser el puerto SSH, no el puerto del panel de WordPress.
- `STAGING_USER` debe poder iniciar sesión por SSH.
- `STAGING_PASSWORD` debe ser la contraseña SSH.
- `STAGING_REMOTE_PATH` debe ser la ruta absoluta del WordPress de staging, no una URL.
- Woostify debe estar instalado en staging.

Se recomienda probar esas credenciales desde un ordenador:

```bash
ssh -p 22 usuario@desarrollo.elmercadodeorigen.com
```

Después de entrar, comprueba la ruta:

```bash
ls -la /ruta/de/staging/wp-content/themes
```

## 6. Primera subida

1. Añade el secret pendiente `STAGING_KNOWN_HOSTS`.
2. Habilita Actions en **Settings → Actions → General**.
3. Fusiona el pull request en `main`.
4. Abre la pestaña **Actions** y entra en **Deploy child theme to staging**.
5. Revisa que terminen correctamente `Test SSH connection` y `Deploy child theme with rsync`.
6. En WordPress, comprueba que Woostify está instalado y activa **El Mercado de Origen** como tema.
7. Vacía las cachés de WordPress, del plugin de optimización y del CDN si existe.

Al fusionar cambios posteriores en `main`, solo se sincronizará el contenido del child theme.

## Producción

No reutilices las credenciales de staging en producción. Cuando el diseño esté aprobado se añadirá un workflow separado, con secretos separados y una aprobación manual antes de desplegar.
