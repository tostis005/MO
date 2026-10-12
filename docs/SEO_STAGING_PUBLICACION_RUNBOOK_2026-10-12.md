# Runbook de salida — primera y segunda tanda SEO (octubre 2026)

**Objetivo:** validar en staging el HTML REAL antes de fusionar las PR [#237](https://github.com/tostis005/MO/pull/237) y [#241](https://github.com/tostis005/MO/pull/241). Ambas siguen en borrador: no confundir CI de PHP con SEO publicado.

## Bloqueo actual

- La URL `https://dev.elmercadodeorigen.com/` devuelve **HTTP 401 al acceso anónimo** del comprobador web. Algunas herramientas sin acceso DNS también fallan, por lo que no se puede afirmar que el HTML de staging haya sido revisado.
- El repositorio ya tiene una workflow existente **Deploy child theme to staging** con `workflow_dispatch` en `main`. Esta puede seleccionarse desde GitHub Actions y elegir la rama `seo/gsc-20-posts-english-20261012`, **solo si el operador autorizado desea desplegar esa rama en el WordPress de staging**. Se sincroniza el tema hijo y activa el tema; puede sustituir contenido de ese directorio de staging.
- `.github/workflows/seo-staging-html-audit.yml` está preparado en la rama de PR, pero **GitHub Actions normalmente exige que exista en la rama por defecto para que un nuevo workflow_dispatch aparezca en el selector**. Por ahora la vía segura es comprobar el script desde un checkout autorizado o integrarlo en un workflow existente tras la revisión de permisos.
- El auditor Python admite ahora autenticar por HTTP Basic vía `SEO_STAGING_BASIC_USER` y `SEO_STAGING_BASIC_PASSWORD` (GitHub Secrets). Estos son **nombres de variables, no contraseñas existentes**. Si staging usa SSO u otro mecanismo, hay que usar esa autenticación legítima en vez de inventar credenciales.

## Ejecución mínima de QA (solo después de desplegar la rama correcta)

```bash
python3 tests/seo-html-audit.py --self-test
SEO_STAGING_BASIC_USER='...' SEO_STAGING_BASIC_PASSWORD='...' \
  python3 tests/seo-html-audit.py --base https://dev.elmercadodeorigen.com
```

Las variables de credenciales se leen solo en el proceso y no se deben guardar en GitHub ni en un archivo versionado. Es preferible que un operador autorizado las configure como secretos y ejecute el proceso en entorno seguro; nunca escribirlas en logs, tickets o conversaciones.

El auditor inspecciona 3 artículos EN + 2 ES: **title, meta description, H1, lang, canonical y hreflang**. Canonical hacia otra ruta bloquea publicación. Revisar también imagen, metadatos reales y móvil.

## Puerta de aprobación obligatoria

- [ ] Confirmar que el staging contiene el commit exacto de PR #237 y que las 5 páginas se renderizan con HTTP 200 al auditor autorizado.
- [ ] Comprobar que no hay múltiples `<h1>` o `<meta name=description>` ni canonical cruzadas entre ES/EN.
- [ ] En inglés, contar enlaces internos y verificar que la lista de Pato/Foie mantiene sus enlaces históricos tras los fallbacks.
- [ ] AdSense: probar con país con envío, país sin envío y país desconocido; respetar consentimiento y TCF. El auditor HTML **no verifica** elegibilidad/anuncios realmente servidos.
- [ ] Productos WooCommerce: comprobar que no desaparecen productos válidos; las guías de ternera no deben recomendar pato sin relación ([issue #242](https://github.com/tostis005/MO/issues/242)).
- [ ] Caché ES: verificar que el directorio estático `uploads/elmercado-blog-static-v2` no sirve HTML previo al deploy.
- [ ] Solo después de estos controles fusionar PR #237. Reintegrar entonces PR #241 (ambas tocan `functions.php` y `seo-meta-descriptions-010265.php`), resolver posibles conflictos, ejecutar CI y repetir validaciones.
- [ ] Confirmar despliegue real en producción sin afirmar resultados SEO anticipadamente; seguimiento GSC/AdSense a 7, 28 y 56 días.

**Datos faltantes:** acceso autorizado a staging, CMS/Falang para editar cuerpos, GSC consulta×página y RPM por URL de AdSense. Nunca sortear autenticación o consentimiento para obtener resultados.
