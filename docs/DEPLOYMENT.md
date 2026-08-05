# Despliegue automático al entorno de desarrollo

El workflow `.github/workflows/deploy-development.yml` sincroniza únicamente la carpeta `elmercadodeorigen-child/` con la carpeta del tema hijo en el servidor. No toca la base de datos, `wp-content/uploads`, plugins ni el tema padre.

## 1. Requisitos del hosting

El servidor debe ofrecer:

- Acceso SSH para un usuario de despliegue.
- `rsync` instalado en el servidor.
- Permisos de escritura exclusivamente sobre `wp-content/themes/elmercadodeorigen-child`.
- El tema padre **Woostify** instalado en WordPress.

Se recomienda crear un usuario SSH específico para despliegues y no utilizar `root`.

## 2. Crear una clave SSH de despliegue

En tu ordenador:

```bash
ssh-keygen -t ed25519 -C "github-actions-elmercado" -f ./elmercado_deploy_key
```

- Añade el contenido de `elmercado_deploy_key.pub` al archivo `~/.ssh/authorized_keys` del usuario del servidor.
- Guarda el contenido completo de `elmercado_deploy_key` como el secret `SSH_PRIVATE_KEY`.
- No subas ninguno de estos archivos al repositorio.

## 3. Obtener la huella del servidor

Ejecuta, sustituyendo host y puerto:

```bash
ssh-keyscan -p 22 desarrollo.tudominio.com
```

Guarda la salida completa como `SSH_KNOWN_HOSTS`. Verifica la huella con tu proveedor de hosting antes de confiar en ella.

## 4. Secrets necesarios en GitHub

En el repositorio, ve a **Settings → Secrets and variables → Actions → New repository secret** y crea:

| Secret | Contenido |
|---|---|
| `SSH_HOST` | Host o IP del servidor de desarrollo |
| `SSH_PORT` | Puerto SSH, normalmente `22` |
| `SSH_USER` | Usuario SSH de despliegue |
| `SSH_PRIVATE_KEY` | Clave privada completa, incluidas cabecera y cierre |
| `SSH_KNOWN_HOSTS` | Salida verificada de `ssh-keyscan` |
| `WP_THEME_PATH` | Ruta absoluta terminada en `/wp-content/themes/elmercadodeorigen-child` |

Ejemplo de `WP_THEME_PATH`:

```text
/home/usuario/public_html/wp-content/themes/elmercadodeorigen-child
```

La validación del workflow rechazará cualquier ruta que no termine exactamente en la carpeta esperada.

## 5. Habilitar GitHub Actions

Ve a **Settings → Actions → General**:

1. Activa GitHub Actions para el repositorio.
2. Permite las acciones creadas por GitHub; el proyecto utiliza `actions/checkout@v4`.
3. En **Workflow permissions**, selecciona `Read repository contents permission`. Los workflows ya declaran `contents: read` y no necesitan permisos de escritura.

## 6. Primer despliegue

1. Fusiona el pull request en `main`.
2. Abre la pestaña **Actions**.
3. Entra en **Deploy child theme to development**.
4. El workflow se ejecutará automáticamente por el cambio en `main`; también puede lanzarse con **Run workflow**.
5. En WordPress, comprueba que Woostify está instalado y activa **El Mercado de Origen** como tema.

Después del primer despliegue, cada cambio fusionado en `main` dentro del tema iniciará una nueva sincronización.

## Producción

No uses las mismas credenciales para desarrollo y producción. Cuando el diseño esté aprobado se añadirá un workflow separado, con secretos separados y, cuando el plan de GitHub lo permita, un environment protegido con aprobación manual.
