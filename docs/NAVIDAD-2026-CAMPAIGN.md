# Campaña Navidad 2026 — rama de validación

La campaña vive en la rama feature/navidad-2026-landing-banners-preview y NO altera la home, la tienda, el blog ni las landings anteriores.

## URLs cuando se despliegue esta rama en WordPress

- Landing: /cestas-navidad-empresas-2026/
- Banners: /vista-previa-banners-navidad-2026/ (noindex, nofollow).

Son rutas virtuales independientes, sin registro de páginas en la BBDD. Escribir archivos en GitHub no publica esas páginas: hay que desplegar la rama en un servidor de pruebas para verlas funcionando. No fusionar sin autorización.

## Estructura

- inc/navidad-2026-campaign.php: rutas y formulario.
- templates/navidad-2026-landing.php: contenidos, formulario, tres ejemplos y preguntas frecuentes.
- templates/navidad-2026-banners.php: propuestas independientes para home, tienda y blog.
- assets/css/navidad-2026.css: estilos responsive con scope .emdo-xmas.
- functions.php: alta de un único módulo sin modificar código SEO.

## Formulario y privacidad

Nombre, email, cantidad y presupuesto obligatorios; empresa, teléfono, fecha y comentarios opcionales. Nonce, honeypot, validaciones en servidor, limitación de envíos por IP durante 3 minutos, consentimiento de tratamiento para tramitar solicitudes, mensaje al email de administrador del WordPress mediante wp_mail. No suscribe usuarios a publicidad ni guarda los datos del formulario en la BBDD. Comprobar SMTP y recepción en staging antes de publicar.

## Restricciones de producto

- Las composiciones son ejemplos, no ofertas cerradas ni precios confirmados.
- Solo productos que según su etiqueta sean aptos para transporte y almacenamiento SIN refrigeración y con vida útil suficiente.
- Excluir producto fresco, foie micuit refrigerado, quesos y sobres loncheados que requieran frío.
- Verificar individualmente las conservas de Selectos de Castilla; distinguir esterilizados de refrigerados.
- Para premium con pieza entera usar fotografía REAL de jamón/paleta y embalaje, no imágenes generativas de piernas deformadas.
- No prometer expedición unificada, plazos, personalización o costes sin cerrar acuerdos con productores.

## Checklist de publicación

1. Seleccionar SKU reales y verificar etiquetado, conservación, stock, precios, logística y plazos.
2. Sustituir maquetas visuales por fotografías reales o composiciones de producto fieles a los SKU.
3. Test en staging de PHP, móvil, accesibilidad, consentimiento, spam, SMTP y emails.
4. Revisar textos legales y facturación de cestas multiproductor.
5. Validar los tres banners. Solo después integrarlos en páginas reales y retirar la demo.
6. Coordinar canónica/SEO con la landing histórica cestas-lotes-navidad-empresas para evitar duplicidad.

## PHP lint

php -l elmercadodeorigen-child/inc/navidad-2026-campaign.php
php -l elmercadodeorigen-child/templates/navidad-2026-landing.php
php -l elmercadodeorigen-child/templates/navidad-2026-banners.php
## Modelo económico solicitado (13 octubre 2026)

Objetivo: margen operativo del 15 % sobre precio final por cesta, incluyendo 15 € estimados de caja, pegatina, preparación y transporte, NO como margen. Los gastos reales pueden cambiar con peso, volumen, número de destinos y destinatarios. Confirmar si los márgenes por familia (25 % en ibéricos y aceite y 15 % en patés/conservas) se calculan sobre PVP, base imponible o coste.

Control básico por precio final P: resultado objetivo = 0,15 × P; coste total máximo = 0,85 × P; descontando gastos previstos de 15 € quedan para compras a productores 0,85 × P − 15 €:

| PVP cesta | Beneficio objetivo | Gasto logístico previsto | Coste máximo de adquisición de productos |
|---|---:|---:|---:|
| 60 € | 9,00 € | 15,00 € | 36,00 € |
| 150 € | 22,50 € | 15,00 € | 112,50 € |
| 350 € | 52,50 € | 15,00 € | 282,50 € |

Los importes mostrados al público de las fichas de WooCommerce son **precios de venta**, no costes de adquisición. No deben confundirse con la cuarta columna: calcular primero el importe que se liquida al productor, descuentos/cupones, IVA, transporte real, etiquetas y mermas. Si se mantiene el PVP de las fichas y se añade un cargo logístico exactamente igual al coste de 15 €, el margen sobre total es: (suma de comisiones efectivas de productos) / (suma de PVP de productos + 15). Para alcanzar el 15 % global, una cesta de 60 € con 45 € de productos requiere aproximadamente un 20 % de margen ponderado sobre esos productos; una de 150 € con 135 € requiere ~16,7 %; una de 350 € con 335 € requiere ~15,7 %. 

### Ejemplos de viabilidad con PVP visible (sin confirmar costes reales ni proveedores adicionales)

- Básica ~60 €: salchichón ibérico Montjam en pieza 28,13 € + AOVE botella PET 500 ml en caja de 12 (45,50 / 12 = 3,79 €, **solo si se permite fraccionar el lote**) + bloc de foie 95 g en conserva 9,80 € + dulce navideño estimado 5 € (**aún sin proveedor**) + 15 € embalaje/envío = **61,72 € orientativos**. Salchichón en pieza indica conservación fresco y seco; NO sustituir por loncheado refrigerado.
- Media ~150 €: paleta de cebo de campo 50 % ibérica Montjam, tramo inicial 122,72 € + AOVE 500 ml 3,79 € (fraccionamiento sujeto a autorización) + rillettes esterilizadas 90 g 3,35 € + dulce estimado 5 € + 15 € = **149,86 € orientativos**. Margen y disponibilidad por confirmar, y verificar que todo cabe en una caja que realmente cueste 15 € con el transporte.
- Premium ~350 €: jamón brida negra Montjam, tramo inicial 279,30 € + AOVE 500 ml 3,79 € + bloc de foie al PX 200 g en conserva 18,45 € + rillettes con foie esterilizadas 95 g 4,35 € + confit pato en conserva 12,15 € + dulces estimados 6 € + embalaje/envío 15 € = **339,04 € orientativos**. Una pieza más pesada o envío especial puede elevar la cifra.

**No publicar desglose de costes, comisiones ni simulaciones de margen:** es documentación interna. Mostrar solo ~60, ~150 y ~350 €, composición ilustrativa y presupuestos a medida. No usar imágenes de cestas inventadas ni afirmar disponibilidad de un queso o turrón que todavía no se comercializa.

**Stock y transporte:** algunos resultados indexados presentan precios distintos por idioma, región o fecha. Se verificará el PVP, stock y condiciones comerciales dentro de WooCommerce en el momento del presupuesto. Para los loncheados Montjam actuales la etiqueta exige 0–5 ºC: excluir. Las piezas enteras indicadas admiten conservación en lugar fresco y seco; concretar temperatura y transporte con productor.

## Estado de publicación

El muestrario de banners está en el repositorio pero no se ha integrado en home, tienda ni blog. Hasta validar las composiciones y la entrega del formulario, la landing se configura como noindex para evitar competir con la landing antigua.