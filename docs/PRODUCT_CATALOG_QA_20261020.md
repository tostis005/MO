# Revisión de textos y galerías — 20/10/2026

## Lo que corrige esta rama

- El visor PhotoSwipe de WooCommerce incorpora botones legibles para Cerrar, Compartir, Pantalla completa y Zoom, en lugar de iconos diminutos de difícil interpretación.
- En pantallas estrechas quedan visibles Cerrar y Zoom (44 px de alto), manteniendo los gestos y la navegación nativa.
- Los botones tienen nombres accesibles en español y estados de foco visibles.
- Se añade una auditoría **de solo lectura** de fichas y fotos adicionales.

## Ejecución de auditoría en WordPress

Desde el directorio raíz de WordPress (requiere WP-CLI):

```sh
wp eval-file wp-content/themes/elmercadodeorigen-child/scripts/audit-product-content.php > /tmp/emdo-products.csv
```

El CSV contiene todas las fichas publicadas y en preparación, productor, URL, número de imágenes adicionales, ausencia de portada y detección de referencias impropias al proveedor. La detección de galerías vacías **no** prueba que falten fotos: se cotejan uno a uno los productos con la web del proveedor antes de importar.

No modifica base de datos, contenido comercial, precios, variantes, stock ni envíos.

## Evidencia inicial

- Latitudes muestra nueve imágenes (portada + ocho adicionales) para «Burger Meat wagyu 100% japones 150 gr.»: https://latitudes.es/premium/elaborados-premium/hamburguesas-elaborados-premium/burger-meat-wagyu-100-japones-150-gr/
- Las fotos se comprueban en resolución original, no en las miniaturas de 150 × 150 px. Ejemplos verificados: https://latitudes.es/wp-content/uploads/2026/02/PORTADA.jpg y https://latitudes.es/wp-content/uploads/2026/02/1.jpg
- Ficha con redacción a eliminar: https://www.elmercadodeorigen.com/producto/pack-burger-meat-wagyu-2x150-gr/

### Texto candidato verificado para esa ficha

**Descripción corta propuesta:** «Pack de dos burger meat de 150 g elaboradas con un 85 % de carne de wagyu chileno. Se presentan aliñadas, envasadas al vacío y congeladas. Contienen sulfitos.»

**Descripción larga propuesta:** «Este pack reúne dos burger meat de wagyu chileno, de 150 g cada una. Su composición incluye un 85 % de carne de wagyu chileno, junto con ingredientes de aliño y preparación. Se presentan envasadas al vacío y congeladas, listas para moldear y cocinar a la plancha, a la parrilla, al horno o en barbacoa. **Alérgenos:** contiene sulfitos. **Ingredientes:** carne de wagyu chileno (85 %), agua, fibra vegetal de guisante, semolina de arroz, sal, especias, dextrina de maíz, antioxidantes E-331(iii) y E-301, conservador E-223 (sulfitos) y colorante E-120.»

Validar las etiquetas originales del producto antes de sustituir el contenido. No confundir wagyu chileno, australiano y japonés.

## Reglas para la reconciliación completa de imágenes

1. Emparejar con identidad del producto (presentación, peso, proveedor y SKU o URL origen), no solo por título.
2. Enumerar **todas** las imágenes reales de la ficha del proveedor y compararlas con el ID de portada y los IDs de galería en WooCommerce.
3. Descargar siempre originales, comprobar MIME/tamaño y evitar duplicados con huella o URL de origen.
4. Mantener la portada actual si es adecuada; añadir solo las imágenes inexistentes, conservar su orden, generar miniaturas propias y texto alternativo específico.
5. No extraer imágenes de otros productos o variantes, ni importar miniaturas, logotipos, banners, reseñas o gráficos ajenos a la ficha.
6. Después de actualizar, comprobar que la galería y el lightbox funcionan en móvil y escritorio y que las imágenes no provocan errores 404 ni carga diferida fallida.

## Estado / límites

**Los cambios de código quedan propuestos en GitHub, no desplegados en producción.** La importación y corrección masiva requieren ejecutar el inventario contra la base de datos real de WooCommerce y validar la correspondencia con el productor. No se debe automatizar una reescritura indiscriminada basada solo en sustituciones de frases, ni afirmar que se han importado fotos sin verificar los IDs en WordPress.