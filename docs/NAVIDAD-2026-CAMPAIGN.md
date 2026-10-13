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