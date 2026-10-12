# Monetización del blog internacional (AdSense): diagnóstico y plan

**Corte:** 12-10-2026. **Estado:** auditoría de código + datos GSC agregados, no datos de cuenta AdSense. **No se han cambiado las reglas de anuncios ni de envío.**

## 1. Qué hace hoy el sistema

Comprobado directamente en `elmercadodeorigen-child/inc/adsense-geo-010266.php`, `inc/adsterra-blog-010290.php` y `functions.php`:

- El proveedor editorial activo es **AdSense**; el código de Adsterra permanece, pero el selector devuelve `adsense`.
- Solo se contemplan **entradas individuales** publicadas del blog, no listados, páginas de tienda o vistas previas.
- Las solicitudes de anuncios están **pausadas al cargar**. Un endpoint REST de elegibilidad sin caché resuelve el país de origen de la visita y compara con los países cubiertos por zonas WooCommerce con métodos de envío activos.
- Si el país se desconoce o sí admite envío, no se habilitan anuncios. Si no admite envío, la visita pasa a la siguiente capa de consentimiento; no equivale automáticamente a una impresión facturable.
- La clasificación TCF/consentimiento se conserva. Los placeholders de artículos se insertan por número de párrafos editoriales sin reservar espacio publicitario para visitas no elegibles. No se permite relajar esto para conseguir ingresos.
- El HTML de entradas **ES** puede ser servido mediante caché estática temprana; las entradas **EN** están explícitamente excluidas de esa caché del blog. Por tanto, al desplegar un cambio de títulos ES hay que comprobar también la invalidación del caché estático existente.

## 2. Línea de base disponible en GSC (sitio completo)

Periodo 12-08 a 10-10-2026, **60 días**, no el trimestre completo:

| Dimensión | Clics | Impresiones | Advertencia |
|---|---:|---:|---|
| Todas las URL dentro de las 1.000 páginas exportadas con prefijo `/en/` | 3.463 | 726.254 | Segmento parcial de URL |
| España | 5.425 | 790.988 | Toda la web, no tráfico inglés |
| Reino Unido | 1.103 | 109.957 | Toda la web, no se ha confirmado elegibilidad publicitaria |
| Estados Unidos | 612 | 203.261 | Toda la web, no se ha confirmado elegibilidad publicitaria |
| Móviles | 6.989 | 1.479.924 | Todos los países e idiomas |
| Escritorio | 2.675 | 372.840 | Todos los países e idiomas |
| Tablet | 213 | 20.304 | Todos los países e idiomas |

**No hay base para calcular ingreso por 1.000 sesiones EN o ingreso por país**: falta cruce `landing page × country × device` y los reportes propios de AdSense. El CTR SEO no es CTR de anuncio.

## 3. Auditoría y prioridades de monetización

| Orden | Revisión | Evidencia necesaria | Criterio |
|---|---|---|---|
| P0 | AdSense por URL + país + dispositivo | Page RPM, estimated earnings, impresiones, cobertura, ad requests | Saber qué entradas **EN elegibles** monetizan |
| P0 | GA4 por landing EN | Sesiones, fuente/medio, país, móvil/desktop, engaged sessions | Distinguir clic GSC de sesiones monetizadas |
| P0 | QA de elegibilidad geográfica | Visitas reales desde país con envío, sin envío y país desconocido; consentimientos | Ads nunca se solicitan en países compradores ni antes del consentimiento |
| P1 | Visibilidad de anuncios en móvil | CLS, LCP, tiempo de lectura, viewability, fill rate | No sacrificar experiencia para incrementar densidad |
| P1 | Guías EN con impresiones | Cruce GSC `page × query` + país | Optimizar título/contenido según consulta real, sin reescribir a ciegas |
| P2 | Ajustar posiciones si se demuestra mejora | Experimentos secuenciales con páginas comparables y 28 días | Cambios graduales, sin despliegue global ni comparación engañosa |

## 4. Checklist operativo antes de publicar cambios de SEO

- [ ] Desplegar rama de prueba de manera autorizada en staging, **sin fusionarla ni pasar a producción**. El deploy de staging admite `workflow_dispatch`; verificar que está seleccionada la rama correcta antes de ejecutarlo.
- [ ] Staging devuelve 401 a visitas externas: es un control de acceso, no una prueba de HTML publicada. Se requiere una sesión autorizada de staging para ver el resultado.
- [ ] Comprobar HTML real de 3 posts EN + 2 ES; title/meta/H1, canonical **autorreferente**, hreflang válido, contenidos por idioma y enlaces.
- [ ] Comprobar política de consentimiento/geolocalización sin desactivar AdSense ni zonas WooCommerce.
- [ ] Comprobar que un cambio de tema invalida o evita servir **HTML ES obsoleto** del directorio `wp-content/uploads/elmercado-blog-static-v2`. La invalidación automática actual escucha cambios de posts/stock/términos; **no está demostrado que un deploy del tema purgue esa caché**.
- [ ] Confirmar que las guías EN siguen funcionando con la caché temprana desactivada, sin duplicar carga publicitaria.
- [ ] Publicar progresivamente; monitorizar 404, 5xx, CWV, clics EN y revenue AdSense por landing a 7/28/56 días.

## 5. Conexiones de datos necesarias

**GSC Wizard** puede proporcionar consultas por URL, países, dispositivos e indexación y su cruce con GA4. **WPWriter** puede facilitar la edición de artículos en WordPress. El análisis de rendimiento real exige **AdSense** conectado o exportación AdSense por página y país. Al 12-10-2026 las conexiones GSC/WP no constan confirmadas.

**Seguimiento:** [SEO issue #238](https://github.com/tostis005/MO/issues/238), [PR #237](https://github.com/tostis005/MO/pull/237). Ninguna de las mejoras de la PR está en producción todavía.
