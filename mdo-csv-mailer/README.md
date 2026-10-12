# EMDO · Correos personalizados desde CSV

Plugin independiente para WordPress. No modifica FluentCRM ni WooCommerce. Código en `mdo-csv-mailer.php`.

## CSV

Archivo UTF-8 con cabeceras (admite separador `; ` sin espacio o `,`):

```csv
email;asunto;mensaje;permiso;origen
cliente@example.com;"Propuesta personalizada";"Buenos días,\n\nGracias por confiar en nosotros.\n\nUn saludo";cliente_previo;"Compra anterior de productos similares; revisar requisitos LSSI"
```

Usa **saltos de línea reales** dentro de campos entrecomillados; no las secuencias `\\n` de este ejemplo de una sola línea. La vista previa de WordPress conserva los saltos de línea.

- `email`, `asunto`, `mensaje`: obligatorios. El asunto y el mensaje son literales y personalizados para cada destinatario. No hay plantillas ni variables obligatorias.
- `permiso`: `consentimiento` o `cliente_previo` (relación contractual previa + productos/servicios propios similares).
- `origen`: detalle del fundamento y de cómo se obtuvo la dirección. No se puede dejar vacío para envío.
- Filas sin base admitida o con dirección dada de baja quedan **bloqueadas**, aunque aparezcan en la vista previa. El campo CSV no sustituye el deber de comprobar que la base es cierta.
- La importación no envía nada.

## Instalación

1. Crear un ZIP con la carpeta `mdo-csv-mailer/`, de modo que el ZIP contenga `mdo-csv-mailer/mdo-csv-mailer.php`.
2. WordPress > Plugins > Añadir nuevo > Subir plugin. Activar.
3. Aparecerá **Herramientas > Correos CSV**.
4. Configurar y **comprobar la cuenta SMTP existente** antes de enviar.
5. Importar CSV y revisar destinatarios, asuntos, mensajes y fuentes.
6. Enviar una prueba al correo del administrador de WordPress.
7. Marcar revisión e iniciar. Dos correos por lote, con al menos 120 segundos entre lotes. WordPress Cron requiere ejecución regular; si no hay visitas o cron de servidor, puede demorarse.
8. Consultar estados y pausar cuando se desee. Los fallos no se reintentan automáticamente para evitar duplicados.

## Remitente: comprobar dominio

El valor sugerido por defecto es `hola@elmercadodeorigen.com`, que es el buzón mencionado previamente en el proyecto.
También se mencionó `hola@mercadoorigen.com`. **No son la misma dirección**. Antes de usar el plugin, comprobar cuál existe y está autenticada mediante el proveedor SMTP (SPF/DKIM). Las configuraciones SMTP que fuerzan el remitente pueden sustituir la cabecera `From` del plugin.

## Estados, bajas y límites

- `pending`: validado y pendiente.
- `blocked`: falta base/origen o está dado de baja.
- `processing`: reclamado para envío (no reenviar automáticamente; puede haberse completado antes de una interrupción).
- `sent`: `wp_mail()` aceptó el mensaje para transporte; **no prueba entrega final**.
- `failed`: falló el envío; no se reenvía solo.

Cada mensaje contiene enlace de baja y respuesta al remitente. Una baja excluye ese correo de futuros envíos del plugin. El límite actual es **2.000 filas por CSV y 2 MB**.

**Importante:** si la lista es prospección comercial en frío y no hay consentimiento ni excepción legal aplicable, no se debe marcar falsamente `cliente_previo` o `consentimiento`. La personalización individual no elimina los requisitos del artículo 21 LSSI.

## Estado del desarrollo

Primera versión escrita en GitHub; **no se ha instalado en producción ni se ha comprobado aún un envío SMTP real**. Validar sintaxis PHP, entorno y una campaña de prueba antes de usarlo en producción.
